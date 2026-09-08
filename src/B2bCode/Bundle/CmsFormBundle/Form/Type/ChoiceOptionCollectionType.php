<?php

declare(strict_types=1);

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

/**
 * Form type for the collection of choice options of a CMS form field.
 */
class ChoiceOptionCollectionType extends AbstractType
{
    #[\Override]
    public function getParent(): ?string
    {
        return CollectionType::class;
    }
}
