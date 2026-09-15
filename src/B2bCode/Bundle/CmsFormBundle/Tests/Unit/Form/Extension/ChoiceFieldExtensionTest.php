<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Extension\ChoiceFieldExtension;
use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionCollectionType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class ChoiceFieldExtensionTest extends TestCase
{
    private ChoiceFieldExtension $extension;

    /** @var array<string, callable> */
    private array $rootListeners = [];

    /** @var array<string, callable> */
    private array $typeListeners = [];

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    private FormInterface&MockObject $form;

    #[\Override]
    protected function setUp(): void
    {
        $this->extension = new ChoiceFieldExtension();
        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects(self::any())
            ->method('add')
            ->willReturnCallback(function (string $child, string $type, array $options): FormInterface {
                $this->added[] = [$child, $type, $options];

                return $this->form;
            });

        $this->extension->buildForm($this->builder(), []);
    }

    public function testTheExtensionAppliesToTheFieldType(): void
    {
        self::assertSame([FieldType::class], [...ChoiceFieldExtension::getExtendedTypes()]);
    }

    public function testTheChoiceWidgetsAreAddedWhenAStoredFieldIsAChoiceField(): void
    {
        $field = (new CmsFormField())->setType('dropdown');

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, $field));

        self::assertSame(['choices', 'multiple', 'choice_placeholder'], array_column($this->added, 0));
        self::assertSame(
            [ChoiceOptionCollectionType::class, CheckboxType::class, TextType::class],
            array_column($this->added, 1)
        );
        self::assertSame('options[multiple]', $this->added[1][2]['property_path']);
        self::assertSame('options[placeholder]', $this->added[2][2]['property_path']);
    }

    public function testTheStoredChoiceMapIsUnfoldedIntoNameValueRowsForTheCollection(): void
    {
        $field = (new CmsFormField())->setType('radio')
            ->setOptions(['choices' => ['Poland' => 'pl', 'Germany' => 'de']]);

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, $field));

        self::assertSame(
            [['name' => 'Poland', 'value' => 'pl'], ['name' => 'Germany', 'value' => 'de']],
            $this->added[0][2]['data']
        );
        self::assertSame(ChoiceOptionType::class, $this->added[0][2]['entry_type']);
        self::assertFalse($this->added[0][2]['mapped']);
        self::assertTrue($this->added[0][2]['allow_add']);
    }

    public function testAChoiceFieldWithoutStoredChoicesGetsACollectionWithoutPresetData(): void
    {
        $field = (new CmsFormField())->setType('dropdown')->setOptions(['choices' => []]);

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, $field));

        self::assertArrayNotHasKey('data', $this->added[0][2]);
    }

    public function testNoChoiceWidgetIsAddedForANonChoiceField(): void
    {
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('text'))
        );

        self::assertSame([], $this->added);
    }

    public function testNoChoiceWidgetIsAddedWhenThereIsNoFieldYet(): void
    {
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, null));

        self::assertSame([], $this->added);
    }

    public function testPickingAChoiceTypeInTheTypeSelectorAddsTheChoiceWidgetsToTheParentForm(): void
    {
        $typeChild = $this->createMock(FormInterface::class);
        $typeChild->expects(self::any())->method('getParent')->willReturn($this->form);

        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($typeChild, 'dropdown'));

        self::assertSame(['choices', 'multiple', 'choice_placeholder'], array_column($this->added, 0));
    }

    public function testPickingANonChoiceTypeInTheTypeSelectorAddsNothing(): void
    {
        $typeChild = $this->createMock(FormInterface::class);
        $typeChild->expects(self::any())->method('getParent')->willReturn($this->form);

        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($typeChild, 'text'));
        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($typeChild, null));

        self::assertSame([], $this->added);
    }

    public function testASupportedTypeCanBeRegisteredFromOutside(): void
    {
        $this->extension->addSupportedType('custom-choice');

        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('custom-choice'))
        );

        self::assertSame(['choices', 'multiple', 'choice_placeholder'], array_column($this->added, 0));
    }

    public function testOnSubmitFoldsTheCollectionRowsBackIntoTheStoredChoiceMap(): void
    {
        $field = (new CmsFormField())->setType('dropdown');

        $this->formHasChoicesData([
            ['name' => 'Poland', 'value' => 'pl'],
            ['name' => 'Germany', 'value' => 'de'],
        ]);

        $this->extension->onSubmit(new FormEvent($this->form, $field));

        self::assertSame(['Poland' => 'pl', 'Germany' => 'de'], $field->getOption('choices'));
    }

    public function testOnSubmitLeavesANonChoiceFieldAlone(): void
    {
        $field = (new CmsFormField())->setType('text');
        $this->form->expects(self::never())->method('has');

        $this->extension->onSubmit(new FormEvent($this->form, $field));

        self::assertNull($field->getOption('choices'));
    }

    public function testOnSubmitIsANoOpWhenTheFormCarriesNoChoicesChild(): void
    {
        $field = (new CmsFormField())->setType('dropdown');
        $this->form->expects(self::once())->method('has')->with('choices')->willReturn(false);
        $this->form->expects(self::never())->method('get');

        $this->extension->onSubmit(new FormEvent($this->form, $field));

        self::assertNull($field->getOption('choices'));
    }

    public function testOnSubmitIgnoresANonArrayCollectionValue(): void
    {
        $field = (new CmsFormField())->setType('dropdown');
        $this->formHasChoicesData(null);

        $this->extension->onSubmit(new FormEvent($this->form, $field));

        self::assertNull($field->getOption('choices'));
    }

    public function testOnSubmitStoresAnEmptyMapWhenEveryRowWasRemoved(): void
    {
        $field = (new CmsFormField())->setType('dropdown');
        $this->formHasChoicesData([]);

        $this->extension->onSubmit(new FormEvent($this->form, $field));

        self::assertSame([], $field->getOption('choices'));
    }

    private function formHasChoicesData(mixed $data): void
    {
        $choices = $this->createMock(FormInterface::class);
        $choices->expects(self::once())->method('getData')->willReturn($data);

        $this->form->expects(self::once())->method('has')->with('choices')->willReturn(true);
        $this->form->expects(self::once())->method('get')->with('choices')->willReturn($choices);
    }

    private function builder(): FormBuilderInterface&MockObject
    {
        $typeBuilder = $this->createMock(FormBuilderInterface::class);
        $typeBuilder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(
                function (string $event, callable $listener) use ($typeBuilder): FormBuilderInterface {
                    $this->typeListeners[$event] = $listener;

                    return $typeBuilder;
                }
            );

        $builder = $this->createMock(FormBuilderInterface::class);
        $builder->expects(self::any())->method('get')->with('type')->willReturn($typeBuilder);
        $builder->expects(self::any())
            ->method('addEventListener')
            ->willReturnCallback(function (string $event, callable $listener) use ($builder): FormBuilderInterface {
                $this->rootListeners[$event] = $listener;

                return $builder;
            });

        return $builder;
    }
}
