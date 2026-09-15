<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FormType;
use B2bCode\Bundle\CmsFormBundle\Form\Type\NotificationType;
use Oro\Bundle\RedirectBundle\Form\Type\SlugType;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * The CMS form editor is the only place a `CmsForm` is created, so its field set is part of the bundle's
 * contract: these tests pin which children it exposes, under which types, and how each maps onto the entity.
 */
class FormTypeTest extends TestCase
{
    private FormType $type;

    /** @var array<int, array{string, string, array<string, mixed>}> */
    private array $added = [];

    #[\Override]
    protected function setUp(): void
    {
        $this->type = new FormType();
        $this->type->buildForm($this->builder(), []);
    }

    public function testTheEditorExposesTheEditableFormPropertiesInOrder(): void
    {
        self::assertSame(
            ['name', 'alias', 'previewEnabled', 'notificationsEnabled', 'notifications', 'redirectUrl'],
            array_column($this->added, 0)
        );
        self::assertSame(
            [
                TextType::class,
                SlugType::class,
                CheckboxType::class,
                CheckboxType::class,
                CollectionType::class,
                UrlType::class,
            ],
            array_column($this->added, 1)
        );
    }

    public function testTheAliasIsSluggedFromTheName(): void
    {
        $alias = $this->optionsOf('alias');

        self::assertSame('name', $alias['source_field']);
        self::assertTrue($alias['required']);
    }

    public function testOnlyTheNameAndTheAliasAreMandatory(): void
    {
        self::assertTrue($this->optionsOf('name')['required']);
        self::assertTrue($this->optionsOf('alias')['required']);
        self::assertFalse($this->optionsOf('previewEnabled')['required']);
        self::assertFalse($this->optionsOf('notificationsEnabled')['required']);
        self::assertFalse($this->optionsOf('notifications')['required']);
        self::assertFalse($this->optionsOf('redirectUrl')['required']);
    }

    public function testNotificationsAreAnEditableCollectionOwnedByTheForm(): void
    {
        $notifications = $this->optionsOf('notifications');

        self::assertSame(NotificationType::class, $notifications['entry_type']);
        self::assertTrue($notifications['allow_add']);
        self::assertTrue($notifications['allow_delete']);
        self::assertTrue($notifications['delete_empty']);
        self::assertTrue($notifications['prototype']);
        // orphanRemoval on CmsForm::$notifications only fires when the collection is mutated, not replaced.
        self::assertFalse($notifications['by_reference']);
    }

    public function testTheFormIsBoundToTheCmsFormEntity(): void
    {
        $resolver = new OptionsResolver();

        $this->type->configureOptions($resolver);

        self::assertSame(CmsForm::class, $resolver->resolve()['data_class']);
    }

    /**
     * @return array<string, mixed>
     */
    private function optionsOf(string $child): array
    {
        foreach ($this->added as [$name, , $options]) {
            if ($name === $child) {
                return $options;
            }
        }

        self::fail(sprintf('The "%s" child was not added to the form.', $child));
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
