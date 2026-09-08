<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Validator\Loader;

use B2bCode\Bundle\CmsFormBundle\B2bCodeCmsFormBundle;
use B2bCode\Bundle\CmsFormBundle\Tests\Unit\Stub\InvalidRulesBundle\InvalidRulesBundle;
use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use Oro\Component\Config\CumulativeResourceManager;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Validator\Constraints\NotBlank;

class ValidationRuleLoaderTest extends TestCase
{
    private ArrayAdapter $cache;
    private ValidationRuleLoader $loader;

    #[\Override]
    protected function setUp(): void
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['B2bCodeCmsFormBundle' => B2bCodeCmsFormBundle::class]);

        $this->cache = new ArrayAdapter();
        $this->loader = new ValidationRuleLoader($this->cache);
    }

    #[\Override]
    protected function tearDown(): void
    {
        CumulativeResourceManager::getInstance()->clear();
    }

    public function testTheBundleRulesAreLoadedAndReturnedPerFormAlias(): void
    {
        self::assertSame(
            [
                'fields' => [
                    'are-you-satisfied' => [[NotBlank::class => null]],
                    'rating'            => [[NotBlank::class => null]],
                    'tell-us-more'      => [[NotBlank::class => null]],
                ],
            ],
            $this->loader->getForForm('feedback-form')
        );
    }

    public function testAFormAliasWithoutRulesGetsAnEmptyConfiguration(): void
    {
        self::assertSame([], $this->loader->getForForm('no-such-form'));
    }

    public function testTheConfigurationIsResolvedOnceAndServedFromTheCacheAfterwards(): void
    {
        $this->loader->getForForm('feedback-form');

        // Drop the bundles: a second resolve would now yield nothing, so a non-empty
        // result proves the cached configuration was reused.
        CumulativeResourceManager::getInstance()->clear()->setBundles([]);

        self::assertNotSame([], $this->loader->getForForm('feedback-form'));
    }

    public function testClearCacheForcesTheNextCallToResolveAgain(): void
    {
        $this->loader->getForForm('feedback-form');

        $this->loader->clearCache();
        CumulativeResourceManager::getInstance()->clear()->setBundles([]);

        self::assertSame([], $this->loader->getForForm('feedback-form'));
    }

    public function testACachedNonArrayConfigurationIsTreatedAsNoRules(): void
    {
        $item = $this->cache->getItem(ValidationRuleLoader::CONFIG_ID);
        $item->set('not-an-array');
        $this->cache->save($item);

        self::assertSame([], $this->loader->getForForm('feedback-form'));
    }

    public function testABundleWhoseRulesFileHasNoFormsRootIsRejected(): void
    {
        CumulativeResourceManager::getInstance()
            ->clear()
            ->setBundles(['InvalidRulesBundle' => InvalidRulesBundle::class]);

        self::expectException(\InvalidArgumentException::class);
        self::expectExceptionMessage('Root element of form_validation.yml should be `forms`.');

        $this->loader->getForForm('feedback-form');
    }
}
