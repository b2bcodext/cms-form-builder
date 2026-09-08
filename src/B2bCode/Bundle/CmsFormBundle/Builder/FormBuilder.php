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

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Exception\CmsFormNotFound;
use B2bCode\Bundle\CmsFormBundle\Form\Type\CmsFormType;
use B2bCode\Bundle\CmsFormBundle\Provider\FieldTypeRegistry;
use B2bCode\Bundle\CmsFormBundle\Validator\Config\FormConstraintCollection;
use B2bCode\Bundle\CmsFormBundle\Validator\ConstraintProviderInterface;
use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;
use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\Form\FormBuilderInterface as SymfonyFormBuilderInterface;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Routing\RouterInterface;

/**
 * Builds the Symfony form (and single fields) of a CMS form, applying its validation constraints.
 */
class FormBuilder implements FormBuilderInterface
{
    protected FormFactoryInterface $formFactory;

    protected ManagerRegistry $managerRegistry;

    protected FieldTypeRegistry $fieldTypeRegistry;

    protected RouterInterface $router;

    protected ConstraintProviderInterface $constraintProvider;

    public function __construct(
        FormFactoryInterface $formFactory,
        ManagerRegistry $managerRegistry,
        FieldTypeRegistry $fieldTypeRegistry,
        RouterInterface $router,
        ConstraintProviderInterface $constraintProvider
    ) {
        $this->formFactory = $formFactory;
        $this->managerRegistry = $managerRegistry;
        $this->fieldTypeRegistry = $fieldTypeRegistry;
        $this->router = $router;
        $this->constraintProvider = $constraintProvider;
    }

    /**
     * @param array<string, mixed> $options
     */
    #[\Override]
    public function getForm(string $alias, array $options = []): FormInterface
    {
        $repository = $this->managerRegistry->getManagerForClass(CmsForm::class)->getRepository(CmsForm::class);
        /** @var CmsForm|null $cmsForm */
        $cmsForm = $repository->findOneBy(['alias' => $alias]);

        if ($cmsForm === null) {
            throw new CmsFormNotFound(sprintf('CmsForm with alias %s not found', $alias));
        }

        if (!array_key_exists('action', $options) || $options['action'] === null || $options['action'] === '') {
            $options['action'] = $this->router->generate('b2b_code_cms_frontend_ajax_respond', [
                'uuid' => $cmsForm->uuid()
            ]);
        }

        return $this->buildForm($cmsForm, $options);
    }

    /**
     * Builds form containing only one field. Useful for the field preview.
     *
     */
    #[\Override]
    public function buildField(CmsFormField $field): FormInterface
    {
        $formBuilder = $this->formFactory->createBuilder(CmsFormType::class, null, ['csrf_protection' => false]);
        $this->addField($formBuilder, $field);

        return $formBuilder->getForm();
    }

    /**
     * @param array<string, mixed> $options
     */
    protected function buildForm(CmsForm $cmsForm, array $options = []): FormInterface
    {
        $formBuilder = $this->formFactory->createBuilder(CmsFormType::class, null, $options);
        $constraintCollection = $this->constraintProvider->getConstraintsForForm($cmsForm);

        // configure fields
        foreach ($cmsForm->getFields() as $field) {
            $this->addField($formBuilder, $field, $constraintCollection);
        }

        return $formBuilder->getForm();
    }

    protected function addField(
        SymfonyFormBuilderInterface $formBuilder,
        CmsFormField $field,
        ?FormConstraintCollection $constraintCollection = null
    ): void {
        if (!$constraintCollection instanceof FormConstraintCollection) {
            $constraintCollection = new FormConstraintCollection(new CmsForm());
        }

        $formType = $this->fieldTypeRegistry->getByKey($field->getType());
        if (!$formType instanceof CmsFieldType) {
            return;
        }
        $fieldOptions = array_merge($formType->getFormOptions(), $field->getOptions());
        $constraints = $this->buildConstraintsForField($field, $constraintCollection, $fieldOptions);
        if (count($constraints) > 0) {
            $fieldOptions['constraints'] = $constraints;
        }

        $formBuilder->add($field->getName(), $formType->getFormType(), $fieldOptions);
    }

    /**
     * @param array<string, mixed> $fieldOptions
     * @return array<int, object> constraint objects that apply to the field
     */
    protected function buildConstraintsForField(
        CmsFormField $field,
        FormConstraintCollection $constraintCollection,
        array $fieldOptions
    ): array {
        $constraints = [];

        if (array_key_exists('constraints', $fieldOptions)) {
            $constraints = $fieldOptions['constraints'];
        }

        if ($constraintCollection->hasFieldAnyConstraints($field->getName())) {
            return array_merge($constraints, $constraintCollection->getConstraintsForField($field->getName()));
        }

        return $constraints;
    }
}
