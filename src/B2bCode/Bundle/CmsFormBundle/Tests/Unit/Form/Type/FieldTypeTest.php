<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProviderInterface;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeRegistry;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use Oro\Bundle\FormBundle\Form\Type\Select2ChoiceType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Contracts\Translation\TranslatorInterface;

class FieldTypeTest extends TestCase
{
    private FieldType $type;

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    /** @var array<string, callable|array{object, string}> */
    private array $listeners = [];

    #[\Override]
    protected function setUp(): void
    {
        $typeProvider = $this->createMock(FieldTypeProviderInterface::class);
        $typeProvider->expects(self::any())->method('getAvailableTypes')->willReturn([
            new CmsFieldType('text', TextType::class),
            new CmsFieldType('hidden', HiddenType::class),
        ]);

        $translator = $this->createMock(TranslatorInterface::class);
        $translator->expects(self::any())
            ->method('trans')
            ->willReturnCallback(static fn (string $id): string => match ($id) {
                'b2bcode.cmsform.field_type.text.label'   => 'Single line text',
                'b2bcode.cmsform.field_type.hidden.label' => 'Hidden',
                default                                   => $id,
            });

        $this->type = new FieldType(new FieldTypeRegistry([$typeProvider]), $translator);
        $this->type->buildForm($this->builder(), []);
    }

    public function testAFieldIsALabelASlugAndATypeSelector(): void
    {
        self::assertSame(['label', 'name', 'type'], array_column($this->added, 0));
        self::assertSame(
            [TextType::class, SlugType::class, Select2ChoiceType::class],
            array_column($this->added, 1)
        );
    }

    public function testTheSlugIsDerivedFromTheLabel(): void
    {
        self::assertSame('label', $this->added[1][2]['source_field']);
    }

    public function testTheTypeSelectorOffersEveryRegisteredTypeUnderItsTranslatedLabel(): void
    {
        self::assertSame(
            ['Single line text' => 'text', 'Hidden' => 'hidden'],
            $this->added[2][2]['choices']
        );
        self::assertSame('b2bcode.cmsform.cmsformfield.type.placeholder', $this->added[2][2]['placeholder']);
        self::assertTrue($this->added[2][2]['required']);
    }

    public function testEveryChildIsMandatory(): void
    {
        foreach ($this->added as [, , $options]) {
            self::assertTrue($options['required']);
        }
    }

    public function testOnSubmitIsRegisteredForTheSubmitEvent(): void
    {
        self::assertSame([$this->type, 'onSubmit'], $this->listeners[FormEvents::SUBMIT]);
    }

    public function testTheLabelIsMirroredIntoTheRenderedFieldOptionsOnSubmit(): void
    {
        $field = (new CmsFormField())->setLabel('Your e-mail');

        $this->type->onSubmit(new FormEvent($this->createMock(FormInterface::class), $field));

        self::assertSame('Your e-mail', $field->getOption('label'));
    }

    public function testAFieldWithoutALabelGetsNoLabelOptionOnSubmit(): void
    {
        $field = new CmsFormField();

        $this->type->onSubmit(new FormEvent($this->createMock(FormInterface::class), $field));

        self::assertSame([], $field->getOptions());
    }

    public function testTheFormIsBoundToTheCmsFormFieldEntity(): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        self::assertSame(CmsFormField::class, $resolver->resolve()['data_class']);
    }

    private function builder(): FormBuilderInterface&MockObject
    {
        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())
            ->method('add')
            ->willReturnCallback(
                function (string $child, string $type, array $options) use ($builder): FormBuilderInterface {
                    $this->added[] = [$child, $type, $options];

                    return $builder;
                }
            );
        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function (string $event, $listener) use ($builder): FormBuilderInterface {
                $this->listeners[$event] = $listener;

                return $builder;
            });

        return $builder;
    }
}
