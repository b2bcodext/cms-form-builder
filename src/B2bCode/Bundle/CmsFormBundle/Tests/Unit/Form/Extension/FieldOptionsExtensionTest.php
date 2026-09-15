<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Extension\FieldOptionsExtension;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class FieldOptionsExtensionTest extends TestCase
{
    private FieldOptionsExtension $extension;

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    /** @var array<string, callable|array{object, string}> */
    private array $listeners = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new FieldOptionsExtension();
        $this->extension->buildForm($this->builder(), []);
    }

    public function testTheExtensionAppliesToTheFieldType(): void
    {
        self::assertSame([FieldType::class], [...FieldOptionsExtension::getExtendedTypes()]);
    }

    public function testTheGeneralFieldOptionsAreAddedInTheirRenderingOrder(): void
    {
        self::assertSame(['required', 'placeholder', 'css_class', 'size'], array_column($this->added, 0));
        self::assertSame(
            [CheckboxType::class, TextType::class, TextType::class, ChoiceType::class],
            array_column($this->added, 1)
        );
    }

    public function testEachOptionIsMappedOntoItsSlotInTheFieldOptionsArray(): void
    {
        self::assertSame(
            [
                'options[required]',
                'options[attr][placeholder]',
                'options[attr][class]',
                'options[attr][data-size]',
            ],
            array_column(array_column($this->added, 2), 'property_path')
        );
    }

    public function testTheSizeIsAMandatoryChoiceOfThreeWidths(): void
    {
        $size = $this->added[3][2];

        self::assertTrue($size['required']);
        self::assertSame(
            [
                'b2bcode.cmsform.cmsformfield.options.size.choices.small'  => 'small',
                'b2bcode.cmsform.cmsformfield.options.size.choices.medium' => 'medium',
                'b2bcode.cmsform.cmsformfield.options.size.choices.large'  => 'large',
            ],
            $size['choices']
        );
        self::assertEquals([new NotBlank()], $size['constraints']);
    }

    public function testTheFreeTextOptionsAreNotRequired(): void
    {
        self::assertFalse($this->added[0][2]['required']);
        self::assertFalse($this->added[1][2]['required']);
        self::assertFalse($this->added[2][2]['required']);
    }

    public function testOnSubmitIsRegisteredForTheSubmitEvent(): void
    {
        self::assertSame([$this->extension, 'onSubmit'], $this->listeners[FormEvents::SUBMIT]);
    }

    public function testANewFieldIsPlacedAfterTheLastFieldOfItsFormOnSubmit(): void
    {
        $form = new CmsForm();
        $form->addField((new CmsFormField())->setName('first')->setSortOrder(4));
        $field = (new CmsFormField())->setName('second');
        $form->addField($field);

        $this->extension->onSubmit(new FormEvent($this->createMock(FormInterface::class), $field));

        self::assertSame(5, $field->getSortOrder());
    }

    public function testAFieldThatAlreadyHasAPositionKeepsItOnSubmit(): void
    {
        $form = new CmsForm();
        $form->addField((new CmsFormField())->setName('first')->setSortOrder(4));
        $field = (new CmsFormField())->setName('second')->setSortOrder(2);
        $form->addField($field);

        $this->extension->onSubmit(new FormEvent($this->createMock(FormInterface::class), $field));

        self::assertSame(2, $field->getSortOrder());
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
