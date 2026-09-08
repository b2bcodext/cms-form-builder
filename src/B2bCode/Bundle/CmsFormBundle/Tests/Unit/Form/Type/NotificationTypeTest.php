<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormNotification;
use B2bCode\Bundle\CmsFormBundle\Form\Type\NotificationType;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\TranslationBundle\Form\Type\Select2TranslatableEntityType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class NotificationTypeTest extends TestCase
{
    private NotificationType $type;

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new NotificationType();
        $this->type->buildForm($this->builder(), []);
    }

    public function testANotificationIsARecipientAndAnOptionalTemplate(): void
    {
        self::assertSame(['email', 'template'], array_column($this->added, 0));
        self::assertSame([TextType::class, Select2TranslatableEntityType::class], array_column($this->added, 1));
        self::assertFalse($this->added[0][2]['required']);
        self::assertFalse($this->added[1][2]['required']);
    }

    public function testTheTemplateIsPickedFromTheEmailTemplatesByName(): void
    {
        $template = $this->added[1][2];

        self::assertSame(EmailTemplate::class, $template['class']);
        self::assertSame('name', $template['choice_label']);
        self::assertSame('', $template['placeholder']);
        self::assertTrue($template['configs']['allowClear']);
    }

    public function testTheFormIsBoundToTheNotificationEntity(): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        self::assertSame(CmsFormNotification::class, $resolver->resolve()['data_class']);
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
