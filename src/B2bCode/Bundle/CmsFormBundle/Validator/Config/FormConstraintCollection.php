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

namespace B2bCode\Bundle\CmsFormBundle\Validator\Config;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;

/**
 * Collection of validation constraints per CMS form field, instantiated on demand.
 */
class FormConstraintCollection
{
    protected CmsForm $form;

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
     * @var array<string, array<int, array<string, mixed>>>
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
     * @param mixed $constraintOptions
     * @return static
     */
    public function addConstraintForField(string $field, string $constraintClass, $constraintOptions = null): static
    {
        if (!$this->form->hasField($field)) {
            // @todo Exception?
            return $this;
        }

        if (!array_key_exists($field, $this->constraints)) {
            $this->constraints[$field] = [];
        }

        $this->constraints[$field][] = [$constraintClass => $constraintOptions];

        return $this;
    }

    /**
     * @param string $field
     * @return array<int, array<string, mixed>>
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
     * @return array<int, object> instantiated constraint objects
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
