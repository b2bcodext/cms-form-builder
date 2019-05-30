<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Validator\Loader;

use Doctrine\Common\Cache\CacheProvider;
use Oro\Component\Config\Loader\CumulativeConfigLoader;
use Oro\Component\Config\Loader\YamlCumulativeFileLoader;
use Oro\Component\PhpUtils\ArrayUtil;

class ValidationRuleLoader
{
    public const CONFIG_ID = 'b2b_code_cms_form_validation';

    /** @var CacheProvider */
    protected $cacheProvider;

    /**
     * @param CacheProvider $cacheProvider
     */
    public function __construct(CacheProvider $cacheProvider)
    {
        $this->cacheProvider = $cacheProvider;
    }

    /**
     * @param string $alias
     * @return array
     */
    public function getForForm(string $alias): array
    {
        $this->ensureConfigurationLoaded();

        $configuration = $this->cacheProvider->fetch(static::CONFIG_ID);

        if (is_array($configuration) && array_key_exists($alias, $configuration)) {
            return $configuration[$alias];
        }

        return [];
    }

    public function clearCache(): void
    {
        $this->cacheProvider->delete(static::CONFIG_ID);
    }

    /**
     * @throws \InvalidArgumentException
     */
    protected function ensureConfigurationLoaded(): void
    {
        if ($this->cacheProvider->contains(static::CONFIG_ID)) {
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

        $this->cacheProvider->save(static::CONFIG_ID, $config);
    }
}
