<?php

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\DataFixtures;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;

class LoadCmsFormField extends AbstractFixture
{
    /**
     * {@inheritdoc}
     */
    public function load(ObjectManager $manager)
    {
        $firstNameOptions = [
            'required' => true,
            'attr'     => [
                'data-size'   => 'medium',
                'placeholder' => 'First name...',
                'class'       => 'custom_css',
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

        $manager->persist($firstName);
        $manager->persist($lastName);
        $manager->persist($email);
        $manager->persist($organization);
        $manager->persist($contactReason);
        $manager->persist($comment);
        $manager->flush();

        $this->setReference($firstName->getName(), $firstName);
        $this->setReference($lastName->getName(), $lastName);
        $this->setReference($email->getName(), $email);
        $this->setReference($organization->getName(), $organization);
        $this->setReference($contactReason->getName(), $contactReason);
        $this->setReference($comment->getName(), $comment);
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
