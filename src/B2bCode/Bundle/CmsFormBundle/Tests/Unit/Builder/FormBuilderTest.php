<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Builder;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilder;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Exception\CmsFormNotFound;
use B2bCode\Bundle\CmsFormBundle\Form\Type\CmsFormType;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProviderInterface;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeRegistry;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;
use B2bCode\Bundle\CmsFormBundle\Validator\ConstraintProviderInterface;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Doctrine\Persistence\ObjectRepository;
use Oro\Bundle\EntityExtendBundle\Test\EntityExtendTestInitializer;
use Oro\Component\Testing\Unit\EntityTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface as SymfonyFormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

class FormBuilderTest extends TestCase
{
    use EntityTrait;

    private FormFactoryInterface&MockObject $formFactory;
    private ManagerRegistry&MockObject $managerRegistry;
    private ObjectRepository&MockObject $repository;
    private RouterInterface&MockObject $router;
    private ConstraintProviderInterface&MockObject $constraintProvider;
    private SymfonyFormBuilderInterface&MockObject $symfonyFormBuilder;
    private FormInterface&MockObject $form;
    private FormBuilder $formBuilder;

    #[\Override]
    protected function setUp(): void
    {
        // `getEntity()` on an extend entity resolves through extend metadata (unit-patterns.md).
        EntityExtendTestInitializer::initialize();

        $this->formFactory = $this->createMock(FormFactoryInterface::class);
        $this->managerRegistry = $this->createMock(ManagerRegistry::class);
        $this->repository = $this->createMock(ObjectRepository::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->constraintProvider = $this->createMock(ConstraintProviderInterface::class);
        $this->symfonyFormBuilder = $this->createMock(SymfonyFormBuilderInterface::class);
        $this->form = $this->createMock(FormInterface::class);

        $manager = $this->createMock(ObjectManager::class);
        $manager->expects(self::any())->method('getRepository')->with(CmsForm::class)->willReturn($this->repository);
        $this->managerRegistry->expects(self::any())
            ->method('getManagerForClass')
            ->with(CmsForm::class)
            ->willReturn($manager);

        $this->symfonyFormBuilder->expects(self::any())->method('getForm')->willReturn($this->form);

        $typeProvider = $this->createMock(FieldTypeProviderInterface::class);
        $typeProvider->expects(self::any())->method('getAvailableTypes')->willReturn([
            new CmsFieldType('text', TextType::class),
            new CmsFieldType('email', EmailType::class, ['constraints' => [new Email()]]),
            new CmsFieldType('dropdown', ChoiceType::class, ['expanded' => false]),
        ]);

        $this->formBuilder = new FormBuilder(
            $this->formFactory,
            $this->managerRegistry,
            new FieldTypeRegistry([$typeProvider]),
            $this->router,
            $this->constraintProvider
        );
    }

    public function testAnUnknownAliasIsRejected(): void
    {
        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['alias' => 'no-such-form'])
            ->willReturn(null);

        self::expectException(CmsFormNotFound::class);
        self::expectExceptionMessage('CmsForm with alias no-such-form not found');

        $this->formBuilder->getForm('no-such-form');
    }

    public function testTheSubmitActionDefaultsToTheFrontendRespondRouteOfThatForm(): void
    {
        $cmsForm = $this->cmsForm();
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));

        $this->router->expects(self::once())
            ->method('generate')
            ->with('b2b_code_cms_frontend_ajax_respond', ['uuid' => 'the-uuid'])
            ->willReturn('/cms-form/respond/the-uuid');

        $this->formFactory->expects(self::once())
            ->method('createBuilder')
            ->with(CmsFormType::class, null, ['action' => '/cms-form/respond/the-uuid'])
            ->willReturn($this->symfonyFormBuilder);

        self::assertSame($this->form, $this->formBuilder->getForm('contact-us'));
    }

    /**
     * @dataProvider emptyActionDataProvider
     */
    public function testAnEmptyActionOptionIsReplacedByTheGeneratedRoute(?string $action): void
    {
        $cmsForm = $this->cmsForm();
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));
        $this->router->method('generate')->willReturn('/cms-form/respond/the-uuid');

        $this->formFactory->expects(self::once())
            ->method('createBuilder')
            ->with(CmsFormType::class, null, ['action' => '/cms-form/respond/the-uuid'])
            ->willReturn($this->symfonyFormBuilder);

        $this->formBuilder->getForm('contact-us', ['action' => $action]);
    }

    /**
     * @return array<string, array{string|null}>
     */
    public function emptyActionDataProvider(): array
    {
        return ['null' => [null], 'empty string' => ['']];
    }

    public function testAnExplicitActionIsKeptAndNoRouteIsGenerated(): void
    {
        $cmsForm = $this->cmsForm();
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));

        $this->router->expects(self::never())->method('generate');
        $this->formFactory->expects(self::once())
            ->method('createBuilder')
            ->with(CmsFormType::class, null, ['action' => '/custom', 'csrf_protection' => false])
            ->willReturn($this->symfonyFormBuilder);

        $this->formBuilder->getForm('contact-us', ['action' => '/custom', 'csrf_protection' => false]);
    }

    public function testEachFieldIsAddedWithTheFormOptionsOfItsTypeMergedOverTheFieldOptions(): void
    {
        $cmsForm = $this->cmsForm([
            ['name' => 'sender', 'type' => 'email', 'options' => ['label' => 'Sender']],
            ['name' => 'topic', 'type' => 'dropdown', 'options' => ['expanded' => true]],
        ]);
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));
        $this->router->method('generate')->willReturn('/respond');
        $this->formFactory->method('createBuilder')->willReturn($this->symfonyFormBuilder);

        $added = $this->captureAddedFields();

        $this->formBuilder->getForm('contact-us');

        self::assertEquals(
            [
                ['sender', EmailType::class, ['constraints' => [new Email()], 'label' => 'Sender']],
                // the field's own options win over the type defaults
                ['topic', ChoiceType::class, ['expanded' => true]],
            ],
            $added->getArrayCopy()
        );
    }

    public function testAFieldWhoseTypeIsNotRegisteredIsSkipped(): void
    {
        $cmsForm = $this->cmsForm([
            ['name' => 'gone', 'type' => 'no-such-type', 'options' => []],
            ['name' => 'sender', 'type' => 'text', 'options' => []],
        ]);
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));
        $this->router->method('generate')->willReturn('/respond');
        $this->formFactory->method('createBuilder')->willReturn($this->symfonyFormBuilder);

        $added = $this->captureAddedFields();

        $this->formBuilder->getForm('contact-us');

        self::assertSame([['sender', TextType::class, []]], $added->getArrayCopy());
    }

    public function testConstraintsOfTheCollectionAreAppendedToTheTypeConstraints(): void
    {
        $cmsForm = $this->cmsForm([['name' => 'sender', 'type' => 'email', 'options' => []]]);
        $this->formIsFound($cmsForm);

        $collection = new FormConstraintCollection($cmsForm);
        $collection->addConstraintForField('sender', NotBlank::class);
        $this->constraintProvider->expects(self::once())
            ->method('getConstraintsForForm')
            ->with($cmsForm)
            ->willReturn($collection);
        $this->router->method('generate')->willReturn('/respond');
        $this->formFactory->method('createBuilder')->willReturn($this->symfonyFormBuilder);

        $added = $this->captureAddedFields();

        $this->formBuilder->getForm('contact-us');

        self::assertEquals(
            [['sender', EmailType::class, ['constraints' => [new Email(), new NotBlank()]]]],
            $added->getArrayCopy()
        );
    }

    public function testAFieldWithoutAnyConstraintCarriesNoConstraintsOption(): void
    {
        $cmsForm = $this->cmsForm([['name' => 'sender', 'type' => 'text', 'options' => ['label' => 'Sender']]]);
        $this->formIsFound($cmsForm);
        $this->constraintProvider->method('getConstraintsForForm')->willReturn(new FormConstraintCollection($cmsForm));
        $this->router->method('generate')->willReturn('/respond');
        $this->formFactory->method('createBuilder')->willReturn($this->symfonyFormBuilder);

        $added = $this->captureAddedFields();

        $this->formBuilder->getForm('contact-us');

        self::assertSame([['sender', TextType::class, ['label' => 'Sender']]], $added->getArrayCopy());
    }

    public function testBuildFieldPreviewsASingleFieldWithoutCsrfProtection(): void
    {
        $field = (new CmsFormField())->setName('sender')->setType('email')->setOptions(['label' => 'Sender']);

        $this->formFactory->expects(self::once())
            ->method('createBuilder')
            ->with(CmsFormType::class, null, ['csrf_protection' => false])
            ->willReturn($this->symfonyFormBuilder);

        $added = $this->captureAddedFields();

        self::assertSame($this->form, $this->formBuilder->buildField($field));
        self::assertEquals(
            [['sender', EmailType::class, ['constraints' => [new Email()], 'label' => 'Sender']]],
            $added->getArrayCopy()
        );
    }

    public function testBuildFieldSkipsAFieldWhoseTypeIsNotRegistered(): void
    {
        $field = (new CmsFormField())->setName('sender')->setType('no-such-type');

        $this->formFactory->method('createBuilder')->willReturn($this->symfonyFormBuilder);
        $added = $this->captureAddedFields();

        $this->formBuilder->buildField($field);

        self::assertSame([], $added->getArrayCopy());
    }

    /**
     * @return \ArrayObject<int, array{string, string, array<string, mixed>}>
     */
    private function captureAddedFields(): \ArrayObject
    {
        $added = new \ArrayObject();
        $this->symfonyFormBuilder->expects(self::any())
            ->method('add')
            ->willReturnCallback(
                function (string $child, string $type, array $options) use ($added): SymfonyFormBuilderInterface {
                    $added[] = [$child, $type, $options];

                    return $this->symfonyFormBuilder;
                }
            );

        return $added;
    }

    private function formIsFound(CmsForm $cmsForm): void
    {
        $this->repository->expects(self::once())
            ->method('findOneBy')
            ->with(['alias' => $cmsForm->getAlias()])
            ->willReturn($cmsForm);
    }

    /**
     * @param array<int, array{name: string, type: string, options: array<string, mixed>}> $fields
     */
    private function cmsForm(array $fields = []): CmsForm
    {
        /** @var CmsForm $cmsForm */
        $cmsForm = $this->getEntity(CmsForm::class, ['uuid' => 'the-uuid']);
        $cmsForm->setAlias('contact-us');
        foreach ($fields as $field) {
            $cmsForm->addField(
                (new CmsFormField())
                    ->setName($field['name'])
                    ->setType($field['type'])
                    ->setOptions($field['options'])
            );
        }

        return $cmsForm;
    }
}
