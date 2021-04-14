<?php

namespace B2bCode\Bundle\CmsFormBundle\Form\Extension;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Form\Type\FieldType;
use Symfony\Component\Form\AbstractTypeExtension;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;

class HiddenFieldExtension extends AbstractTypeExtension
{
    /**
     * {@inheritdoc}
     */
    public static function getExtendedTypes(): iterable
    {
        return [FieldType::class];
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var CmsFormField $formField */
                $formField = $event->getData();

                if ($formField && $formField->getType() === 'hidden') {
                    $this->addField($form);
                }
            });

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var CmsFormField $formField */
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && $formType === 'hidden') {
                    $this->addField($form);
                }
            }
        );
    }

    /**
     * @param FormInterface $form
     */
    protected function addField(FormInterface $form)
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
