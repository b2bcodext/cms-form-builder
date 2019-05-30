<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Builder;

use B2bCode\Bundle\CmsFormBundle\Exception\CmsFormNotFound;
use Symfony\Component\Form\FormInterface;

interface FormBuilderInterface
{
    /**
     * @param string $alias
     * @param array  $options
     * @throws CmsFormNotFound
     * @return mixed
     */
    public function getForm(string $alias, array $options = []): FormInterface;
}
