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

namespace B2bCode\Bundle\CmsFormBundle\Builder;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Exception\CmsFormNotFound;
use Symfony\Component\Form\FormInterface;

/**
 * Builds the Symfony form of a CMS form from its configuration.
 */
interface FormBuilderInterface
{
    /**
     * @param string $alias
     * @param array<string, mixed> $options
     * @throws CmsFormNotFound
     */
    public function getForm(string $alias, array $options = []): FormInterface;

    /**
     * Builds a form containing only the given field. Useful for the field preview.
     */
    public function buildField(CmsFormField $field): FormInterface;
}
