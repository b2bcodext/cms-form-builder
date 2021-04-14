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

class ReCaptchaFieldExtension extends AbstractTypeExtension
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
        // works only with ORO recaptcha extension
        if (!class_exists('OroLab\Bundle\ReCaptchaBundle\Form\Type\ReCaptchaType')) {
            return;
        }

        $builder->addEventListener(
            FormEvents::PRE_SET_DATA,
            function (FormEvent $event) {
                $form = $event->getForm();
                /** @var CmsFormField $formField */
                $formField = $event->getData();

                if ($formField && $formField->getType() === 'oro-recaptcha-v3') {
                    $this->addField($form);
                }
            });

        $builder->get('type')->addEventListener(
            FormEvents::POST_SUBMIT,
            function (FormEvent $event) {
                /** @var CmsFormField $formField */
                $form = $event->getForm()->getParent();
                $formType = $event->getData();

                if ($formType && $formType === 'oro-recaptcha-v3') {
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
        $form
            ->add(
                'data-re-captcha-action',
                TextType::class,
                [
                    'required'      => false,
                    'label'         => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.label',
                    'tooltip'       => 'b2bcode.cmsform.cmsformfield.options.oro-recaptcha.recaptcha-action.tooltip',
                    'property_path' => 'options[attr][data-re-captcha-action]',
                ]
            );
    }
}
