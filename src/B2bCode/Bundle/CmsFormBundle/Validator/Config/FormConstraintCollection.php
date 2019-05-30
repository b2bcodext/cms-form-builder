<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Validator\Config;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;

class FormConstraintCollection
{
    /** @var CmsForm */
    protected $form;

    /**
     * Structure:
     *      ['field' => [
     *          ['constraint_class' => 'constraint_options']
     *      ]]
     * Example:
     *      ['customer-email' => [
     *          ['Symfony\Component\Validator\Constraints\NotBlank' => null]
     *      ]]
     *
     * @var array
     */
    protected $constraints = [];

    /**
     * @param CmsForm $form
     */
    public function __construct(CmsForm $form)
    {
        $this->form = $form;
    }

    /**
     * @param string $field
     * @param string $constraintClass
     * @param null   $constraintOptions
     * @return FormConstraintCollection
     */
    public function addConstraintForField(string $field, string $constraintClass, $constraintOptions = null)
    {
        if (!$this->form->hasField($field)) {
            // @todo Exception?
            return $this;
        }

        if (!array_key_exists($field, $this->constraints)) {
            $this->constraints[$field] = [];
        }

        array_push($this->constraints[$field], [$constraintClass => $constraintOptions]);

        return $this;
    }

    /**
     * @param string $field
     * @return array
     */
    public function getRawConstraintsForField(string $field): array
    {
        if (!array_key_exists($field, $this->constraints)) {
            return [];
        }

        return $this->constraints[$field];
    }

    /**
     * @param string $field
     * @return array
     */
    public function getConstraintsForField(string $field): array
    {
        $fieldConstraints = $this->getRawConstraintsForField($field);

        $constraints = [];
        foreach ($fieldConstraints as $fieldConstraint) {
            $constraintClass = key($fieldConstraint);
            $constraintOptions = $fieldConstraint[$constraintClass];
            if (!class_exists($constraintClass)) {
                // @todo error? logger?
                continue;
            }
            $constraints[] = new $constraintClass($constraintOptions);
        }

        return $constraints;
    }

    /**
     * @param string $field
     * @return bool
     */
    public function hasFieldAnyConstraints(string $field): bool
    {
        return count($this->getRawConstraintsForField($field)) > 0;
    }
}
