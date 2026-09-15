<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\ImportExport\Serializer;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFieldResponse;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;
use B2bCode\Bundle\CmsFormBundle\ImportExport\Serializer\FieldResponseNormalizer;
use Oro\Bundle\EntityBundle\Helper\FieldHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class FieldResponseNormalizerTest extends TestCase
{
    private FieldHelper&MockObject $fieldHelper;
    private FieldResponseNormalizer $normalizer;

    #[\Override]
    protected function setUp(): void
    {
        $this->fieldHelper = $this->createMock(FieldHelper::class);
        $this->fieldHelper->expects(self::any())
            ->method('getEntityFields')
            ->willReturn([['name' => 'value', 'type' => 'text']]);

        $this->normalizer = new FieldResponseNormalizer($this->fieldHelper);
    }

    public function testOnlyFieldResponsesAreNormalizedByThisNormalizer(): void
    {
        self::assertTrue($this->normalizer->supportsNormalization(new CmsFieldResponse()));
        self::assertFalse($this->normalizer->supportsNormalization(new CmsFormResponse()));
        self::assertFalse($this->normalizer->supportsNormalization(new \stdClass()));
        self::assertFalse($this->normalizer->supportsNormalization('a string'));
    }

    public function testOnlyTheFieldResponseClassIsDenormalizedByThisNormalizer(): void
    {
        self::assertTrue($this->normalizer->supportsDenormalization([], CmsFieldResponse::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], CmsFormResponse::class));
        self::assertFalse($this->normalizer->supportsDenormalization([], \stdClass::class));
    }

    public function testTheExportedValueIsTheChoiceLabelRatherThanTheStoredValue(): void
    {
        $fieldResponse = $this->fieldResponse(['choices' => ['Poland' => 'pl']], 'pl');
        $this->fieldHelper->expects(self::once())
            ->method('getObjectValue')
            ->with($fieldResponse, 'value')
            ->willReturn('pl');

        self::assertSame(['value' => 'Poland'], $this->normalizer->normalize($fieldResponse));
    }

    public function testAMultiValueResponseIsExportedAsACommaSeparatedListOfLabels(): void
    {
        $fieldResponse = $this->fieldResponse(
            ['multiple' => true, 'choices' => ['Poland' => 'pl', 'Germany' => 'de']],
            '["pl","de"]'
        );
        $this->fieldHelper->method('getObjectValue')->willReturn(['pl', 'de']);

        self::assertSame(['value' => 'Poland, Germany'], $this->normalizer->normalize($fieldResponse));
    }

    public function testAFieldWithoutChoicesKeepsItsRawValue(): void
    {
        $fieldResponse = $this->fieldResponse([], 'john@example.org');
        $this->fieldHelper->method('getObjectValue')->willReturn('john@example.org');

        self::assertSame(['value' => 'john@example.org'], $this->normalizer->normalize($fieldResponse));
    }

    /**
     * @param array<string, mixed> $fieldOptions
     */
    private function fieldResponse(array $fieldOptions, ?string $value): CmsFieldResponse
    {
        return (new CmsFieldResponse())
            ->setField((new CmsFormField())->setName('country')->setOptions($fieldOptions))
            ->setValue($value);
    }
}
