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

namespace B2bCode\Bundle\CmsFormBundle\Event;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched while the validation constraints of a CMS form are being built, so listeners can extend them.
 */
class ConstraintBuild extends Event
{
    public const NAME = 'b2b_code_cms_form.constraints.build';

    protected FormConstraintCollection $constraintCollection;

    protected CmsForm $form;

    /**
     * @param FormConstraintCollection $constraintCollection
     * @param CmsForm                  $form
     */
    public function __construct(FormConstraintCollection $constraintCollection, CmsForm $form)
    {
        $this->constraintCollection = $constraintCollection;
        $this->form = $form;
    }

    /**
     * @return FormConstraintCollection
     */
    public function getConstraintCollection(): FormConstraintCollection
    {
        return $this->constraintCollection;
    }

    /**
     * @return CmsForm
     */
    public function getForm(): CmsForm
    {
        return $this->form;
    }
}
