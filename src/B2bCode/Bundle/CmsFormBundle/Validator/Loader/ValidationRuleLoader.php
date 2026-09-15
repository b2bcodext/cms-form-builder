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

namespace B2bCode\Bundle\CmsFormBundle\Validator\Loader;

use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Loads and caches the CMS form validation rules declared in the bundle configuration.
 */
class ValidationRuleLoader
{
    public const CONFIG_ID = 'b2b_code_cms_form_validation';

    public function __construct(protected CacheItemPoolInterface $cacheProvider)
    {
    }

    /**
     * @return array<string, mixed> validation rules of the form, keyed by field name
     */
    public function getForForm(string $alias): array
    {
        $this->ensureConfigurationLoaded();

        $cacheItem = $this->cacheProvider->getItem(static::CONFIG_ID);

        if (!$cacheItem->isHit()) {
            return [];
        }

        $configuration = $cacheItem->get();
        if (is_array($configuration) && array_key_exists($alias, $configuration)) {
            return $configuration[$alias];
        }

        return [];
    }

    public function clearCache(): void
    {
        $this->cacheProvider->deleteItem(static::CONFIG_ID);
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function ensureConfigurationLoaded(): void
    {
        $cacheItem = $this->cacheProvider->getItem(static::CONFIG_ID);
        if ($cacheItem->isHit()) {
            return;
        }

        $config = [];
        $configLoader = new CumulativeConfigLoader(
            static::CONFIG_ID,
            new YamlCumulativeFileLoader('Resources/config/form_validation.yml')
        );
        $resources = $configLoader->load();
        foreach ($resources as $resource) {
            if (!array_key_exists('forms', $resource->data)) {
                throw new \InvalidArgumentException('Root element of form_validation.yml should be `forms`.');
            }
            $rules = $resource->data['forms'];
            if (is_array($rules)) {
                $config = ArrayUtil::arrayMergeRecursiveDistinct($config, $rules);
            }
        }
        $cacheItem->set($config);
        $this->cacheProvider->save($cacheItem);
    }
}
