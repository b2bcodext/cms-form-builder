<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Provider;

use B2bCode\Bundle\CmsFormBundle\Provider\GeneralFieldProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormView;

class GeneralFieldProviderTest extends TestCase
{
    private GeneralFieldProvider $provider;

    #[\Override]
    protected function setUp(): void
    {
        $this->provider = new GeneralFieldProvider();
    }

    public function testTheShippedGeneralAndUpdateableFieldSets(): void
    {
        self::assertSame(
            ['name', 'sortOrder', 'type', 'required', 'label', 'placeholder', 'css_class', 'size'],
            $this->provider->getGeneralFields()
        );
        self::assertSame(
            ['label', 'name', 'type', 'size', 'placeholder', 'css_class', 'required'],
            $this->provider->getUpdateableFields()
        );
    }

    public function testAddGeneralFieldAppendsToBothSetsByDefault(): void
    {
        $this->provider->addGeneralField('custom');

        self::assertContains('custom', $this->provider->getGeneralFields());
        self::assertContains('custom', $this->provider->getUpdateableFields());
    }

    public function testANonUpdateableFieldIsGeneralOnly(): void
    {
        $this->provider->addGeneralField('custom', false);

        self::assertContains('custom', $this->provider->getGeneralFields());
        self::assertNotContains('custom', $this->provider->getUpdateableFields());
    }

    public function testAddingTheSameFieldTwiceDoesNotDuplicateIt(): void
    {
        $this->provider->addGeneralField('custom');
        $this->provider->addGeneralField('custom');

        self::assertSame(1, count(array_keys($this->provider->getGeneralFields(), 'custom', true)));
        self::assertSame(1, count(array_keys($this->provider->getUpdateableFields(), 'custom', true)));
    }

    public function testAFieldAlreadyGeneralCanStillBePromotedToUpdateable(): void
    {
        // `sortOrder` ships as general but NOT updateable.
        self::assertNotContains('sortOrder', $this->provider->getUpdateableFields());

        $this->provider->addGeneralField('sortOrder');

        self::assertContains('sortOrder', $this->provider->getUpdateableFields());
        self::assertSame(1, count(array_keys($this->provider->getGeneralFields(), 'sortOrder', true)));
    }

    public function testManipulateRemovesEveryGeneralChildFromTheFormView(): void
    {
        $formView = new FormView();
        foreach (['name', 'label', 'choices', 'multiple'] as $child) {
            $formView->children[$child] = new FormView($formView);
        }

        $this->provider->manipulate($formView);

        self::assertSame(['choices', 'multiple'], array_keys($formView->children));
    }

    public function testManipulateLeavesAViewWithoutGeneralChildrenUntouched(): void
    {
        $formView = new FormView();
        $formView->children['choices'] = new FormView($formView);

        $this->provider->manipulate($formView);

        self::assertSame(['choices'], array_keys($formView->children));
    }
}
