<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Extension\HiddenFieldExtension;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class HiddenFieldExtensionTest extends TestCase
{
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
        $this->form = $this->createMock(FormInterface::class);
        $this->form->expects(self::any())
            ->method('add')
            ->willReturnCallback(function (string $child, string $type, array $options): FormInterface {
                $this->added[] = [$child, $type, $options];

                return $this->form;
            });

        (new HiddenFieldExtension())->buildForm($this->builder(), []);
    }

    public function testTheExtensionAppliesToTheFieldType(): void
    {
        self::assertSame([FieldType::class], [...HiddenFieldExtension::getExtendedTypes()]);
    }

    public function testAStoredHiddenFieldGetsItsDefaultValueInput(): void
    {
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('hidden'))
        );

        self::assertSame([['data', TextType::class, [
            'required'      => false,
            'label'         => 'b2bcode.cmsform.cmsformfield.options.data.label',
            'property_path' => 'options[data]',
        ]]], $this->added);
    }

    public function testAStoredFieldOfAnotherTypeGetsNoDefaultValueInput(): void
    {
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(
            new FormEvent($this->form, (new CmsFormField())->setType('text'))
        );
        ($this->rootListeners[FormEvents::PRE_SET_DATA])(new FormEvent($this->form, null));

        self::assertSame([], $this->added);
    }

    public function testPickingTheHiddenTypeAddsTheDefaultValueInputToTheParentForm(): void
    {
        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), 'hidden'));

        self::assertSame(['data'], array_column($this->added, 0));
    }

    public function testPickingAnotherTypeAddsNothing(): void
    {
        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), 'text'));
        ($this->typeListeners[FormEvents::POST_SUBMIT])(new FormEvent($this->typeChild(), null));

        self::assertSame([], $this->added);
    }

    private function typeChild(): FormInterface&MockObject
    {
        $typeChild = $this->createMock(FormInterface::class);
        $typeChild->expects(self::any())->method('getParent')->willReturn($this->form);

        return $typeChild;
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
