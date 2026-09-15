<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

/**
 * Adds the hidden-value field to CMS form fields of the hidden type.
 */
class HiddenFieldExtension extends AbstractTypeExtension
{
    #[\Override]
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }

    #[\Override]
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event): void {
                $form = $event->getForm();
                /** @var CmsFormField|null $formField */
                $formField = $event->getData();

                if ($formField && $formField->getType() === 'hidden') {
                    $this->addField($form);
                }
            }
        );

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event): void {
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && $formType === 'hidden') {
                    $this->addField($form);
                }
            }
        );
    }

    protected function addField(FormInterface $form): void
    {
        // default value for a field is stored in a `data` option
        $form
            ->add(
                'data',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.data.label',
                    'property_path' => 'options[data]'
                ]
            );
    }
}
