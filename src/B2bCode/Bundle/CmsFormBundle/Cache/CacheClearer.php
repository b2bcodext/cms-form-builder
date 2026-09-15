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

namespace B2bCode\Bundle\CmsFormBundle\Cache;

use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use Symfony\Component\HttpKernel\CacheClearer\CacheClearerInterface;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Warms up and clears the cached CMS form validation rules.
 */
class CacheClearer implements CacheWarmerInterface, CacheClearerInterface
{
    protected ValidationRuleLoader $ruleLoader;

    public function __construct(ValidationRuleLoader $ruleLoader)
    {
        $this->ruleLoader = $ruleLoader;
    }

    /**
     * @return string[]
     */
    #[\Override]
    public function warmUp(string $cacheDir, ?string $buildDir = null): array
    {
        $this->ruleLoader->getForForm('dummy-call');

        return [];
    }

    #[\Override]
    public function isOptional(): bool
    {
        return true;
    }

    #[\Override]
    public function clear(string $cacheDir): void
    {
        $this->ruleLoader->clearCache();
    }
}
