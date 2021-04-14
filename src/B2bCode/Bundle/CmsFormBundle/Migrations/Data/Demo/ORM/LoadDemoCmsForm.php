<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Migrations\Data\Demo\ORM;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoadDemoCmsForm extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $feedbackForm = $this->createFeedbackForm();
        $contactForm = $this->createContactUsForm();

        $manager->persist($feedbackForm);
        $manager->persist($contactForm);
        $manager->flush();
    }

    /**
     * @return CmsForm
     */
    protected function createFeedbackForm(): CmsForm
    {
        $form = new CmsForm();
        $form
            ->setName('Feedback form')
            ->setAlias('feedback-form')
            ->setPreviewEnabled(true);

        $satisfiedOptions = [
            'choices'  => ['Yes' => 'yes', 'No' => 'no'],
            'required' => true,
            'attr'     => [
                'data-size' => 'medium',
            ]
        ];
        $satisfied = $this->createField('Are you satisfied?', 'satisfied', 1, 'dropdown', $satisfiedOptions);

        $ratingOptions = [
            'choices'  => [1 => 1, 2 => 2, 3 => 3, 4 => 4, 5 => 5],
            'required' => true,
            'attr'     => [
                'data-size' => 'medium'
            ]
        ];
        $rating = $this->createField('Rating', 'rating', 2, 'radio', $ratingOptions);

        $tellMoreOptions = [
            'required' => true,
            'attr'     => [
                'placeholder' => 'Help us improve our services.',
                'data-size'   => 'large',
            ]
        ];
        $tellMore = $this->createField('Tell us more', 'tell-more', 3, 'textarea', $tellMoreOptions);

        $form
            ->addField($satisfied)
            ->addField($rating)
            ->addField($tellMore);

        return $form;
    }

    /**
     * @return CmsForm
     */
    protected function createContactUsForm(): CmsForm
    {
        $form = new CmsForm();
        $form
            ->setName('Contact Us')
            ->setAlias('contact-us')
            ->setPreviewEnabled(true);

        $firstNameOptions = [
            'required' => true,
            'attr'     => [
                'data-size'   => 'medium',
                'placeholder' => 'First name...'
            ]
        ];
        $firstName = $this->createField('First name', 'first-name', 1, 'text', $firstNameOptions);

        $lastNameOptions = [
            'required' => true,
            'attr'     => [
                'data-size'   => 'medium',
                'placeholder' => 'Last name...'
            ]
        ];
        $lastName = $this->createField('Last name', 'last-name', 2, 'text', $lastNameOptions);

        $emailOptions = [
            'required' => true,
            'attr'     => [
                'data-size'   => 'medium',
                'placeholder' => 'Email...'
            ]
        ];
        $email = $this->createField('Email', 'email', 3, 'email', $emailOptions);

        $organizationOptions = [
            'required' => false,
            'attr'     => [
                'data-size'   => 'medium',
                'placeholder' => 'Organization...'
            ]
        ];
        $organization = $this->createField('Organization', 'organization', 4, 'text', $organizationOptions);

        $contactReasonOptions = [
            'choices'  => [
                'Want to know more about the product' => 'product',
                'Interested in partnership'           => 'partnership',
                'Need help or assistance'             => 'help',
                'Have a complaint'                    => 'complaint',
                'Other'                               => 'other',
            ],
            'required' => true,
            'attr'     => [
                'data-size' => 'large'
            ]
        ];
        $contactReason = $this->createField('Contact reason', 'contact-reason', 5, 'dropdown', $contactReasonOptions);

        $commentOptions = [
            'required' => true,
            'attr'     => [
                'data-size' => 'large'
            ]
        ];
        $comment = $this->createField('Comment', 'comment', 6, 'textarea', $commentOptions);

        $form
            ->addField($firstName)
            ->addField($lastName)
            ->addField($email)
            ->addField($organization)
            ->addField($contactReason)
            ->addField($comment);

        return $form;
    }

    /**
     * @param string $label
     * @param string $name
     * @param int    $order
     * @param string $type
     * @param array  $options
     * @return CmsFormField
     */
    protected function createField(string $label, string $name, int $order, string $type, array $options): CmsFormField
    {
        $field = new CmsFormField();
        $field
            ->setLabel($label)
            ->setName($name)
            ->setSortOrder($order)
            ->setType($type)
            ->setOptions($options);

        return $field;
    }
}
