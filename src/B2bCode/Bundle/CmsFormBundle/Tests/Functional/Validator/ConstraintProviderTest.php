<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Functional\Validator;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use B2bCode\Bundle\CmsFormBundle\Event\ConstraintBuild;
use B2bCode\Bundle\CmsFormBundle\Tests\Functional\DataFixtures\LoadFeedbackFormData;
use B2bCode\Bundle\CmsFormBundle\Validator\ConstraintProviderInterface;
use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use Oro\Bundle\TestFrameworkBundle\Test\WebTestCase;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The validation rules the bundle ships in Resources/config/form_validation.yml only reach a form
 * through the cumulative config loader + the shared cache pool, and the resulting collection is
 * offered to listeners on the ConstraintBuild event. None of that exists outside a booted kernel.
 *
 * @dbIsolationPerTest
 */
class ConstraintProviderTest extends WebTestCase
{
    #[\Override]
    protected function setUp(): void
    {
        $this->initClient();
        $this->loadFixtures([LoadFeedbackFormData::class]);
    }

    public function testTheBundlesOwnFormValidationYmlIsLoadedByAlias(): void
    {
        self::assertSame(
            [
                'fields' => [
                    'are-you-satisfied' => [[NotBlank::class => null]],
                    'rating' => [[NotBlank::class => null]],
                    'tell-us-more' => [[NotBlank::class => null]],
                ],
            ],
            $this->getRuleLoader()->getForForm('feedback-form')
        );
    }

    public function testAnAliasWithoutDeclaredRulesGetsAnEmptyConfiguration(): void
    {
        self::assertSame([], $this->getRuleLoader()->getForForm('no-rules-declared-for-this-alias'));
    }

    public function testTheConfigurationIsRebuiltAfterTheCacheIsCleared(): void
    {
        $ruleLoader = $this->getRuleLoader();
        $before = $ruleLoader->getForForm('feedback-form');

        $ruleLoader->clearCache();
        self::assertFalse(
            $this->getValidationCache()->hasItem(ValidationRuleLoader::CONFIG_ID),
            'clearCache() really drops the pool item'
        );

        self::assertSame($before, $ruleLoader->getForForm('feedback-form'));
        self::assertTrue($this->getValidationCache()->hasItem(ValidationRuleLoader::CONFIG_ID));
    }

    public function testTheCollectionMergesDeclaredRulesWithTheRequiredFlagAndIsDispatched(): void
    {
        $cmsForm = $this->getReference(LoadFeedbackFormData::FORM_REFERENCE);
        self::assertInstanceOf(CmsForm::class, $cmsForm);
        // `tell-us-more` already gains a NotBlank from form_validation.yml; marking it required as
        // well is what pins the CURRENT merge behaviour (both sources add their own constraint)
        $cmsForm->getField('tell-us-more')->setOptions(['required' => true]);

        $dispatched = [];
        $listener = static function (ConstraintBuild $event) use (&$dispatched): void {
            $dispatched[] = $event->getConstraintCollection();
        };
        $dispatcher = self::getContainer()->get('event_dispatcher');
        self::assertInstanceOf(EventDispatcherInterface::class, $dispatcher);
        $dispatcher->addListener(ConstraintBuild::class, $listener);

        try {
            $collection = $this->getConstraintProvider()->getConstraintsForForm($cmsForm);
        } finally {
            $dispatcher->removeListener(ConstraintBuild::class, $listener);
        }

        self::assertCount(1, $dispatched, 'the ConstraintBuild event reaches the real dispatcher');
        self::assertSame($collection, $dispatched[0]);

        self::assertSame([[NotBlank::class => null]], $collection->getRawConstraintsForField('are-you-satisfied'));
        self::assertSame([[NotBlank::class => null]], $collection->getRawConstraintsForField('rating'));
        self::assertSame(
            [[NotBlank::class => null], [NotBlank::class => null]],
            $collection->getRawConstraintsForField('tell-us-more'),
            'a declared rule and the `required` flag each add their own constraint'
        );
    }

    private function getValidationCache(): CacheItemPoolInterface
    {
        return self::getContainer()->get('b2b_code_cms_form.validation.cache');
    }

    private function getRuleLoader(): ValidationRuleLoader
    {
        return self::getContainer()->get(ValidationRuleLoader::class);
    }

    private function getConstraintProvider(): ConstraintProviderInterface
    {
        return self::getContainer()->get(ConstraintProviderInterface::class);
    }
}
