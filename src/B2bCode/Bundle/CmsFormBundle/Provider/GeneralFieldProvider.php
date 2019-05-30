<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Provider;

use Symfony\Component\Form\FormView;

/**
 * Add CompilerPass with `addMethodCall('addGeneralField', ['custom'])` in order to add your custom fields here.
 */
class GeneralFieldProvider
{
    /** @var array */
    protected $generalFields = ['name', 'sortOrder', 'type', 'required', 'label', 'placeholder', 'css_class', 'size'];

    /** @var array General fields that are rendered at the form field update page */
    protected $updateableFields = ['label', 'name', 'type', 'size', 'placeholder', 'css_class', 'required'];

    /**
     * @param string $field
     * @param bool   $updateable
     */
    public function addGeneralField(string $field, bool $updateable = true): void
    {
        if (!in_array($field, $this->generalFields)) {
            $this->generalFields[] = $field;
        }

        if ($updateable === true && !in_array($field, $this->updateableFields)) {
            $this->updateableFields[] = $field;
        }
    }

    /**
     * @return array
     */
    public function getUpdateableFields(): array
    {
        return $this->updateableFields;
    }

    /**
     * @return array
     */
    public function getGeneralFields(): array
    {
        return $this->generalFields;
    }

    /**
     * Used to set `rendered` flag on fields that should not be rendered in a `Type-specific` section.
     *
     * @param FormView $formView
     */
    public function manipulate(FormView $formView)
    {
        foreach ($this->generalFields as $renderedField) {
            if ($formView->offsetExists($renderedField)) {
                $formView[$renderedField]->setRendered();
            }
        }
    }
}
