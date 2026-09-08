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

namespace B2bCode\Bundle\CmsFormBundle\Validator;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;

/**
 * Provides the validation constraints that apply to a CMS form.
 */
interface ConstraintProviderInterface
{
    /**
     * @param CmsForm $form
     * @return FormConstraintCollection
     */
    public function getConstraintsForForm(CmsForm $form): FormConstraintCollection;
}
