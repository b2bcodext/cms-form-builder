<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Provider;

use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeProvider;
use B2bCode\Bundle\CmsFormBundle\Tests\Unit\Stub\OroReCaptchaStubs;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraint;

class FieldTypeProviderTest extends TestCase
{
    private FieldTypeProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new FieldTypeProvider();
    }

    public function testTheSixBuiltInTypesAreAlwaysOffered(): void
    {
        $byName = $this->indexByName($this->provider->getAvailableTypes());

        self::assertSame(
            ['text', 'textarea', 'email', 'dropdown', 'radio', 'hidden'],
            array_slice(array_keys($byName), 0, 6)
        );
        self::assertSame(TextType::class, $byName['text']->getFormType());
        self::assertSame(TextareaType::class, $byName['textarea']->getFormType());
        self::assertSame(EmailType::class, $byName['email']->getFormType());
        self::assertSame(ChoiceType::class, $byName['dropdown']->getFormType());
        self::assertSame(ChoiceType::class, $byName['radio']->getFormType());
        self::assertSame(HiddenType::class, $byName['hidden']->getFormType());
    }

    public function testEmailTypeCarriesAnEmailConstraint(): void
    {
        $byName = $this->indexByName($this->provider->getAvailableTypes());

        self::assertEquals(['constraints' => [new Email()]], $byName['email']->getFormOptions());
    }

    public function testDropdownAndRadioShareTheChoiceTypeAndDifferOnlyByExpanded(): void
    {
        $byName = $this->indexByName($this->provider->getAvailableTypes());

        self::assertSame(['expanded' => false], $byName['dropdown']->getFormOptions());
        self::assertSame(['expanded' => true], $byName['radio']->getFormOptions());
    }

    public function testPlainTypesCarryNoFormOptions(): void
    {
        $byName = $this->indexByName($this->provider->getAvailableTypes());

        self::assertSame([], $byName['text']->getFormOptions());
        self::assertSame([], $byName['textarea']->getFormOptions());
        self::assertSame([], $byName['hidden']->getFormOptions());
    }

    public function testTheRecaptchaTypeIsOfferedOnceTheOroReCaptchaExtensionIsInstalled(): void
    {
        OroReCaptchaStubs::install();

        $byName = $this->indexByName($this->provider->getAvailableTypes());

        self::assertArrayHasKey('oro-recaptcha-v3', $byName);
        self::assertSame(OroReCaptchaStubs::FORM_TYPE, $byName['oro-recaptcha-v3']->getFormType());

        $constraints = $byName['oro-recaptcha-v3']->getFormOptions()['constraints'];
        self::assertCount(1, $constraints);
        self::assertInstanceOf(Constraint::class, $constraints[0]);
        self::assertInstanceOf(OroReCaptchaStubs::CONSTRAINT, $constraints[0]);
    }

    /**
     * @param CmsFieldType[] $types
     *
     * @return array<string, CmsFieldType>
     */
    private function indexByName(array $types): array
    {
        $byName = [];
        foreach ($types as $type) {
            $byName[$type->getName()] = $type;
        }

        return $byName;
    }
}
