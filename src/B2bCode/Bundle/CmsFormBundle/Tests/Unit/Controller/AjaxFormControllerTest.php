<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Controller;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilder;
use B2bCode\Bundle\CmsFormBundle\Controller\AjaxFormController;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use B2bCode\Bundle\CmsFormBundle\Provider\GeneralFieldProvider;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

class AjaxFormControllerTest extends TestCase
{
    private FormFactoryInterface&MockObject $formFactory;
    private AjaxFormController $controller;

    #[\Override]
    protected function setUp(): void
    {
        $this->formFactory = $this->createMock(FormFactoryInterface::class);

        $this->controller = new AjaxFormController();
        $this->controller->setContainer(
            TestContainerBuilder::create()->add('form.factory', $this->formFactory)->getContainer($this)
        );
    }

    public function testTheFieldEditorViewHidesTheChildrenTheGeneralSectionAlreadyRenders(): void
    {
        $request = new Request();
        $formView = new FormView();
        foreach (['name', 'label', 'choices'] as $child) {
            $formView->children[$child] = new FormView($formView);
        }

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FieldType::class, self::isInstanceOf(CmsFormField::class), [])
            ->willReturn($this->formHandling($request, $formView));

        self::assertSame(
            ['form' => $formView],
            $this->controller->formViewAction($request, new GeneralFieldProvider())
        );
        self::assertSame(['choices'], array_keys($formView->children));
    }

    public function testTheFieldPreviewRendersTheFieldTheSubmittedDefinitionDescribes(): void
    {
        $request = new Request();
        $previewView = new FormView();

        $editorForm = $this->createMock(FormInterface::class);
        $editorForm->expects(self::once())->method('handleRequest')->with($request)->willReturnSelf();

        $definition = null;
        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturnCallback(
                function (string $type, CmsFormField $field) use ($editorForm, &$definition): FormInterface {
                    self::assertSame(FieldType::class, $type);
                    $definition = $field;

                    return $editorForm;
                }
            );

        $preview = $this->createMock(FormInterface::class);
        $preview->expects(self::once())->method('createView')->willReturn($previewView);

        // The action type-hints `Builder\FormBuilderInterface`, but `buildField()` is declared only on the
        // concrete `Builder\FormBuilder`; it resolves at runtime solely because
        // `Resources/config/services.yml` registers the concrete class under the interface's service id.
        // Mocking the concrete class is therefore what the action actually receives, not a shortcut.
        $formBuilder = $this->createMock(FormBuilder::class);
        $formBuilder->expects(self::once())
            ->method('buildField')
            ->willReturnCallback(function (CmsFormField $field) use ($preview, &$definition): FormInterface {
                self::assertSame($definition, $field, 'the preview must render the submitted definition');

                return $preview;
            });

        $result = $this->controller->fieldPreviewAction($request, $formBuilder);

        self::assertSame($previewView, $result['form']);
        self::assertSame($definition, $result['entity']);
    }

    public function testReorderPersistsTheNewPositionOfEveryKnownField(): void
    {
        $cmsForm = new CmsForm();
        $email = (new CmsFormField())->setName('email')->setSortOrder(1);
        $message = (new CmsFormField())->setName('message')->setSortOrder(2);
        $cmsForm->addField($email);
        $cmsForm->addField($message);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('persist')->with($cmsForm);
        $manager->expects(self::once())->method('flush');

        $response = $this->controller->reorderAction(
            $this->reorderRequest(['fields' => [
                'email'   => ['sortOrder' => '5'],
                'message' => ['sortOrder' => '3'],
            ]]),
            $cmsForm,
            $this->registryFor($manager)
        );

        self::assertSame(['success' => true], json_decode($response->getContent(), true));
        self::assertSame(5, $email->getSortOrder());
        self::assertSame(3, $message->getSortOrder());
    }

    public function testReorderIgnoresUnknownFieldsAndEntriesWithoutAPosition(): void
    {
        $cmsForm = new CmsForm();
        $email = (new CmsFormField())->setName('email')->setSortOrder(1);
        $cmsForm->addField($email);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::once())->method('flush');

        $this->controller->reorderAction(
            $this->reorderRequest(['fields' => [
                'no-such-field' => ['sortOrder' => '9'],
                'email'         => ['nothing' => '9'],
            ]]),
            $cmsForm,
            $this->registryFor($manager)
        );

        self::assertSame(1, $email->getSortOrder());
    }

    public function testReorderWithoutAFieldsPayloadChangesNothing(): void
    {
        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::never())->method('persist');
        $manager->expects(self::never())->method('flush');

        $response = $this->controller->reorderAction(
            $this->reorderRequest(['unexpected' => []]),
            new CmsForm(),
            $this->registryFor($manager)
        );

        self::assertInstanceOf(JsonResponse::class, $response);
        self::assertSame(['success' => false], json_decode($response->getContent(), true));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function reorderRequest(array $payload): Request
    {
        return new Request([], ['cms_form_reorder' => $payload]);
    }

    private function registryFor(ObjectManager&MockObject $manager): ManagerRegistry&MockObject
    {
        $registry = $this->createMock(ManagerRegistry::class);
        $registry->expects(self::any())
            ->method('getManagerForClass')
            ->with(CmsForm::class)
            ->willReturn($manager);

        return $registry;
    }

    private function formHandling(Request $request, FormView $formView): FormInterface&MockObject
    {
        $form = $this->createMock(FormInterface::class);
        $form->expects(self::once())->method('handleRequest')->with($request)->willReturnSelf();
        $form->expects(self::once())->method('createView')->willReturn($formView);

        return $form;
    }
}
