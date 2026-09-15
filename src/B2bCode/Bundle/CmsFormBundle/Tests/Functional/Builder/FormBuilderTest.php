<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Builder;

use B2bCode\Bundle\CmsFormBundle\Builder\FormBuilderInterface;
use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormField;
use B2bCode\Bundle\CmsFormBundle\Exception\CmsFormNotFound;
use B2bCode\Bundle\CmsFormBundle\Tests\Functional\DataFixtures\LoadFeedbackFormData;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Validator\Constraints\Email;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The wired FormBuilder: the tagged field-type providers, the constraint provider (which merges the
 * per-field `required` flag with the rules declared in Resources/config/form_validation.yml) and the
 * router-generated form action all have to line up for a storefront form to be usable.
 */
class FormBuilderTest extends WebTestCase
{
    private FormBuilderInterface $formBuilder;

    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([
            '@B2bCodeCmsFormBundle/Tests/Functional/DataFixtures/cms_forms.yml',
            LoadFeedbackFormData::class,
        ]);

        $this->formBuilder = self::getContainer()->get(FormBuilderInterface::class);
    }

    public function testGetFormBuildsEveryFieldOfTheFormInSortOrder(): void
    {
        $form = $this->formBuilder->getForm('preview-enabled');

        self::assertSame(
            ['first-name', 'last-name', 'email', 'organization'],
            array_keys($form->all())
        );
        self::assertInstanceOf(
            TextType::class,
            $form->get('first-name')->getConfig()->getType()->getInnerType()
        );
        self::assertInstanceOf(
            EmailType::class,
            $form->get('email')->getConfig()->getType()->getInnerType()
        );
        self::assertSame(
            'First name...',
            $form->get('first-name')->getConfig()->getOption('attr')['placeholder']
        );
    }

    public function testGetFormDefaultsTheActionToTheRespondEndpointOfThatForm(): void
    {
        $cmsForm = $this->getReference('form_preview_enabled');

        self::assertSame(
            $this->getUrl('b2b_code_cms_frontend_ajax_respond', ['uuid' => $cmsForm->uuid()]),
            $this->formBuilder->getForm('preview-enabled')->getConfig()->getOption('action')
        );
    }

    public function testGetFormKeepsAnExplicitlyPassedAction(): void
    {
        self::assertSame(
            '/custom/action',
            $this->formBuilder->getForm('preview-enabled', ['action' => '/custom/action'])
                ->getConfig()->getOption('action')
        );
    }

    public function testGetFormThrowsWhenTheAliasIsUnknown(): void
    {
        self::expectException(CmsFormNotFound::class);
        self::expectExceptionMessage('CmsForm with alias no-such-form not found');

        $this->formBuilder->getForm('no-such-form');
    }

    public function testRequiredFieldsAndFieldTypeDefaultsBecomeConstraints(): void
    {
        $form = $this->formBuilder->getForm('preview-enabled');

        self::assertSame([NotBlank::class], $this->getConstraintClasses($form->get('first-name')));
        // the Email constraint comes from the field type itself, the NotBlank from the `required` option
        self::assertSame([Email::class, NotBlank::class], $this->getConstraintClasses($form->get('email')));
        // `organization` is not required and no rule declares it
        self::assertSame([], $this->getConstraintClasses($form->get('organization')));
    }

    public function testRulesDeclaredInFormValidationYmlAreAppliedToTheMatchingAlias(): void
    {
        $form = $this->formBuilder->getForm('feedback-form');

        // none of these fields carries `required: true`, so the constraints can only come from
        // Resources/config/form_validation.yml, loaded through the cumulative config loader
        self::assertSame([NotBlank::class], $this->getConstraintClasses($form->get('are-you-satisfied')));
        self::assertSame([NotBlank::class], $this->getConstraintClasses($form->get('rating')));
        self::assertSame([NotBlank::class], $this->getConstraintClasses($form->get('tell-us-more')));
    }

    public function testBuildFieldBuildsASingleFieldPreviewFormWithoutCsrfProtection(): void
    {
        $field = new CmsFormField();
        $field->setName('single-choice')
            ->setLabel('Single choice')
            ->setType('dropdown')
            ->setSortOrder(1)
            ->setOptions(['choices' => ['Yes' => 'yes', 'No' => 'no']]);

        $form = $this->formBuilder->buildField($field);

        self::assertSame(['single-choice'], array_keys($form->all()));
        self::assertInstanceOf(
            ChoiceType::class,
            $form->get('single-choice')->getConfig()->getType()->getInnerType()
        );
        self::assertFalse($form->getConfig()->getOption('csrf_protection'));
    }

    public function testBuildFieldIgnoresAFieldWhoseTypeNoProviderKnows(): void
    {
        $field = new CmsFormField();
        $field->setName('exotic')->setLabel('Exotic')->setType('not-a-registered-type')->setSortOrder(1)
            ->setOptions([]);

        self::assertSame([], $this->formBuilder->buildField($field)->all());
    }

    /**
     * @return array<int, class-string>
     */
    private function getConstraintClasses(FormInterface $field): array
    {
        return array_map(
            static fn (object $constraint): string => $constraint::class,
            $field->getConfig()->getOption('constraints') ?? []
        );
    }
}
