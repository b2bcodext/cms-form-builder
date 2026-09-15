<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class ChoiceOptionTypeTest extends TestCase
{
    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    #[\Override]
    protected function setUp(): void
    {
        (new ChoiceOptionType())->buildForm($this->builder(), []);
    }

    public function testAChoiceRowIsALabelAndAValueBothMandatory(): void
    {
        self::assertSame(['name', 'value'], array_column($this->added, 0));
        self::assertSame([TextType::class, TextType::class], array_column($this->added, 1));

        foreach ($this->added as [, , $options]) {
            self::assertTrue($options['required']);
            self::assertEquals([new NotBlank()], $options['constraints']);
        }
    }

    public function testEachInputCarriesItsOwnLabelAsThePlaceholder(): void
    {
        foreach ($this->added as [, , $options]) {
            self::assertSame($options['label'], $options['attr']['placeholder']);
        }
        self::assertSame(
            'b2bcode.cmsform.cmsformfield.options.choice.choices.name.label',
            $this->added[0][2]['label']
        );
        self::assertSame(
            'b2bcode.cmsform.cmsformfield.options.choice.choices.value.label',
            $this->added[1][2]['label']
        );
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

        return $builder;
    }
}
