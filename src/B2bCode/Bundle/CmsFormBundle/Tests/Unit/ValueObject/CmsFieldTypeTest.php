<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\ValueObject;

use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;

class CmsFieldTypeTest extends TestCase
{
    public function testConstructorDefaultsFormOptionsToAnEmptyArray(): void
    {
        $fieldType = new CmsFieldType('text', TextType::class);

        self::assertSame('text', $fieldType->getName());
        self::assertSame(TextType::class, $fieldType->getFormType());
        self::assertSame([], $fieldType->getFormOptions());
    }

    public function testConstructorKeepsTheGivenFormOptions(): void
    {
        $options = ['expanded' => true, 'multiple' => false];

        $fieldType = new CmsFieldType('radio', TextType::class, $options);

        self::assertSame($options, $fieldType->getFormOptions());
    }

    public function testSettersReplaceTheStateAndReturnTheSameInstanceForChaining(): void
    {
        $fieldType = new CmsFieldType('text', TextType::class);

        $returned = $fieldType
            ->setName('textarea')
            ->setFormType(TextareaType::class)
            ->setFormOptions(['attr' => ['rows' => 5]]);

        self::assertSame($fieldType, $returned);
        self::assertSame('textarea', $fieldType->getName());
        self::assertSame(TextareaType::class, $fieldType->getFormType());
        self::assertSame(['attr' => ['rows' => 5]], $fieldType->getFormOptions());
    }
}
