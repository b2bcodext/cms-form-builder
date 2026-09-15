<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Controller;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Controller\FormController;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FormType;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Bundle\FormBundle\Model\UpdateHandlerFacade;
use Oro\Component\Testing\Unit\EntityTrait;
use Oro\Component\Testing\Unit\TestContainerBuilder;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class FormControllerTest extends TestCase
{
    use EntityTrait;

    private FormFactoryInterface&MockObject $formFactory;
    private RouterInterface&MockObject $router;
    private UpdateHandlerFacade&MockObject $formHandler;
    private TranslatorInterface&MockObject $translator;
    private FormController $controller;

    #[\Override]
    protected function setUp(): void
    {
        EntityExtendTestInitializer::initialize();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->formHandler = $this->createMock(UpdateHandlerFacade::class);
        $this->translator = $this->createMock(TranslatorInterface::class);
        $this->translator->expects(self::any())->method('trans')->willReturnArgument(0);

        $this->controller = new FormController();
        $this->controller->setContainer(
            TestContainerBuilder::create()
                ->add('form.factory', $this->formFactory)
                ->add('router', $this->router)
                ->getContainer($this)
        );
    }

    public function testTheIndexOnlyRendersItsGrid(): void
    {
        self::assertSame([], $this->controller->indexAction());
    }

    public function testTheViewShowsTheFormEntityBesideItsRenderedFrontendForm(): void
    {
        $cmsForm = (new CmsForm())->setAlias('contact-us');
        $formView = new FormView();

        $renderedForm = $this->createMock(FormInterface::class);
        $renderedForm->expects(self::once())->method('createView')->willReturn($formView);

        $formBuilder = $this->createMock(FormBuilderInterface::class);
        $formBuilder->expects(self::once())->method('getForm')->with('contact-us')->willReturn($renderedForm);

        self::assertSame(
            ['entity' => $cmsForm, 'form' => $formView],
            $this->controller->viewAction($cmsForm, $formBuilder)
        );
    }

    public function testTheResponsesPageOnlyCarriesTheForm(): void
    {
        $cmsForm = new CmsForm();

        self::assertSame(['entity' => $cmsForm], $this->controller->responsesAction($cmsForm));
    }

    public function testCreatingAFormHandsANewEntityAndTheEditorFormToTheUpdateHandler(): void
    {
        $request = new Request();
        $editor = $this->createMock(FormInterface::class);

        $handled = null;
        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturnCallback(function (string $type, CmsForm $entity) use ($editor, &$handled): FormInterface {
                self::assertSame(FormType::class, $type);
                $handled = $entity;

                return $editor;
            });

        $this->formHandler->expects(self::once())
            ->method('update')
            ->with(
                self::isInstanceOf(CmsForm::class),
                $editor,
                'b2bcode.cmsform.saved_message',
                $request
            )
            ->willReturn(['form' => $editor]);

        self::assertSame(
            ['form' => $editor],
            $this->controller->createAction($request, $this->formHandler, $this->translator)
        );
        self::assertNull($handled->getId(), 'a brand new form is handed to the handler');
    }

    public function testASavedNewFormSendsTheUserStraightToItsFirstFieldPage(): void
    {
        $this->formFactory->method('create')->willReturnCallback(
            function (string $type, CmsForm $entity): FormInterface {
                // the handler persists the entity, which is what gives it an id
                $this->setValue($entity, 'id', 42);

                return $this->createMock(FormInterface::class);
            }
        );
        $this->formHandler->method('update')->willReturn(new RedirectResponse('/cms-form/update/42'));
        $this->router->expects(self::once())
            ->method('generate')
            ->with('b2b_code_cms_form_field_create', ['id' => 42])
            ->willReturn('/cms-form/42/field/create');

        $response = $this->controller->createAction(new Request(), $this->formHandler, $this->translator);

        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame('/cms-form/42/field/create', $response->getTargetUrl());
    }

    public function testANewFormThatWasNotSavedKeepsRenderingTheEditor(): void
    {
        $this->formFactory->method('create')->willReturn($this->createMock(FormInterface::class));
        $this->formHandler->method('update')->willReturn(['form' => 'view']);
        $this->router->expects(self::never())->method('generate');

        self::assertSame(
            ['form' => 'view'],
            $this->controller->createAction(new Request(), $this->formHandler, $this->translator)
        );
    }

    public function testUpdatingAFormPassesTheExistingEntityThrough(): void
    {
        $request = new Request();
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntity(CmsForm::class, ['id' => 7]);
        $editor = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FormType::class, $cmsForm)
            ->willReturn($editor);
        $this->formHandler->expects(self::once())
            ->method('update')
            ->with($cmsForm, $editor, 'b2bcode.cmsform.saved_message', $request)
            ->willReturn(['result']);

        self::assertSame(
            ['result'],
            $this->controller->updateAction($request, $cmsForm, $this->formHandler, $this->translator)
        );
    }

    public function testCreatingAFieldAttachesItToTheFormBeforeHandingItOver(): void
    {
        $request = new Request();
        $cmsForm = new CmsForm();
        $editor = $this->createMock(FormInterface::class);

        $handled = null;
        $this->formFactory->expects(self::once())
            ->method('create')
            ->willReturnCallback(
                function (string $type, CmsFormField $field) use ($editor, &$handled): FormInterface {
                    self::assertSame(FieldType::class, $type);
                    $handled = $field;

                    return $editor;
                }
            );
        $this->formHandler->expects(self::once())
            ->method('update')
            ->with(
                self::isInstanceOf(CmsFormField::class),
                $editor,
                'b2bcode.cmsform.cmsformfield.saved_message',
                $request
            )
            ->willReturn(['result']);

        self::assertSame(
            ['result'],
            $this->controller->createFieldAction($request, $cmsForm, $this->formHandler, $this->translator)
        );
        self::assertSame($cmsForm, $handled->getForm());
    }

    public function testUpdatingAFieldPassesTheExistingFieldThrough(): void
    {
        $request = new Request();
        $field = (new CmsFormField())->setName('email');
        $editor = $this->createMock(FormInterface::class);

        $this->formFactory->expects(self::once())
            ->method('create')
            ->with(FieldType::class, $field)
            ->willReturn($editor);
        $this->formHandler->expects(self::once())
            ->method('update')
            ->with($field, $editor, 'b2bcode.cmsform.cmsformfield.saved_message', $request)
            ->willReturn(['result']);

        self::assertSame(
            ['result'],
            $this->controller->updateFieldAction($request, $field, $this->formHandler, $this->translator)
        );
    }
}
