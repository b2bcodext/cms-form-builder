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
use B2bCode\Bundle\CmsFormBundle\Event\ConstraintBuild;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;
use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Builds the validation constraint collection for a CMS form and lets listeners extend it.
 */
class ConstraintProvider implements ConstraintProviderInterface
{
    protected ValidationRuleLoader $ruleLoader;

    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(ValidationRuleLoader $ruleLoader, EventDispatcherInterface $eventDispatcher)
    {
        $this->ruleLoader = $ruleLoader;
        $this->eventDispatcher = $eventDispatcher;
    }

    #[\Override]
    public function getConstraintsForForm(CmsForm $form): FormConstraintCollection
    {
        // @todo caching here instead of rule loader?
        $collection = new FormConstraintCollection($form);
        $configuration = $this->ruleLoader->getForForm($form->getAlias());

        if (array_key_exists('fields', $configuration)) {
            foreach ($configuration['fields'] as $fieldName => $fieldConfig) {
                foreach ($fieldConfig as $constraint) {
                    $constraintClass = key($constraint);
                    $constraintOptions = $constraint[$constraintClass];
                    $collection->addConstraintForField($fieldName, $constraintClass, $constraintOptions);
                }
            }
        }

        foreach ($form->getFields() as $field) {
            $options = $field->getOptions();
            if (array_key_exists('required', $options) && $options['required'] === true) {
                $collection->addConstraintForField($field->getName(), NotBlank::class);
            }
        }

        $event = new ConstraintBuild($collection, $form);
        $this->eventDispatcher->dispatch($event);

        return $event->getConstraintCollection();
    }
}
