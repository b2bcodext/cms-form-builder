<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Form\Type;

use B2bCode\Bundle\CmsFormBundle\Form\Type\ChoiceOptionCollectionType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class ChoiceOptionCollectionTypeTest extends TestCase
{
    /**
     * The choice editor's JS widget is wired to the collection prototype, so the parent type is behaviour,
     * not decoration: pinned here because losing it silently breaks add/remove of choices in the UI.
     */
    public function testTheChoiceEditorIsACollectionOfChoiceRows(): void
    {
        self::assertSame(CollectionType::class, (new ChoiceOptionCollectionType())->getParent());
    }
}
