<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Provider;

use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProviderInterface;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeRegistry;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class FieldTypeRegistryTest extends TestCase
{
    private CmsFieldType $text;
    private CmsFieldType $hidden;
    private FieldTypeProviderInterface&MockObject $firstProvider;
    private FieldTypeProviderInterface&MockObject $secondProvider;

    #[\Override]
    protected function setUp(): void
    {
        $this->text = new CmsFieldType('text', TextType::class);
        $this->hidden = new CmsFieldType('hidden', HiddenType::class);

        $this->firstProvider = $this->createMock(FieldTypeProviderInterface::class);
        $this->secondProvider = $this->createMock(FieldTypeProviderInterface::class);
    }

    public function testAvailableTypesAreEmptyWithoutProviders(): void
    {
        self::assertSame([], (new FieldTypeRegistry())->getAvailableTypes());
    }

    public function testAvailableTypesConcatenateEveryProviderInRegistrationOrder(): void
    {
        $this->firstProvider->expects(self::once())->method('getAvailableTypes')->willReturn([$this->text]);
        $this->secondProvider->expects(self::once())->method('getAvailableTypes')->willReturn([$this->hidden]);

        $registry = new FieldTypeRegistry([$this->firstProvider, $this->secondProvider]);

        self::assertSame([$this->text, $this->hidden], $registry->getAvailableTypes());
    }

    public function testAvailableTypesAcceptALazyIterableOfProviders(): void
    {
        $this->firstProvider->expects(self::once())->method('getAvailableTypes')->willReturn([$this->text]);

        $registry = new FieldTypeRegistry(new \ArrayIterator([$this->firstProvider]));

        self::assertSame([$this->text], $registry->getAvailableTypes());
    }

    public function testGetByKeyReturnsTheTypeWithTheMatchingName(): void
    {
        $this->firstProvider->method('getAvailableTypes')->willReturn([$this->text, $this->hidden]);

        $registry = new FieldTypeRegistry([$this->firstProvider]);

        self::assertSame($this->hidden, $registry->getByKey('hidden'));
    }

    public function testGetByKeyReturnsTheFirstMatchWhenTwoProvidersDeclareTheSameName(): void
    {
        $shadowing = new CmsFieldType('text', HiddenType::class);
        $this->firstProvider->method('getAvailableTypes')->willReturn([$this->text]);
        $this->secondProvider->method('getAvailableTypes')->willReturn([$shadowing]);

        $registry = new FieldTypeRegistry([$this->firstProvider, $this->secondProvider]);

        self::assertSame($this->text, $registry->getByKey('text'));
    }

    public function testGetByKeyReturnsNullForAnUnknownName(): void
    {
        $this->firstProvider->method('getAvailableTypes')->willReturn([$this->text]);

        $registry = new FieldTypeRegistry([$this->firstProvider]);

        self::assertNull($registry->getByKey('no-such-type'));
    }
}
