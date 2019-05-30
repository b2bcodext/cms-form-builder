<?php

namespace B2bCode\Bundle\CmsFormBundle\Provider;

use B2bCode\Bundle\CmsFormBundle\ValueObject\CmsFieldType;

/**
 * @todo Would be good to cache providers' output, as this is not a frequently changeable thing
 */
class FieldTypeRegistry
{
    /** @var iterable|FieldTypeProviderInterface[] */
    protected $providers;

    /**
     * @param iterable $providers
     */
    public function __construct(iterable $providers = [])
    {
        $this->providers = $providers;
    }

    /**
     * @return CmsFieldType[]
     */
    public function getAvailableTypes(): array
    {
        $availableTypes = [];
        foreach ($this->providers as $provider) {
            $availableTypes = array_merge($availableTypes, $provider->getAvailableTypes());
        }

        return $availableTypes;
    }

    /**
     * @param string $name
     *
     * @return CmsFieldType|null
     */
    public function getByKey(string $name): ?CmsFieldType
    {
        $fieldTypes = $this->getAvailableTypes();

        foreach ($fieldTypes as $fieldType) {
            if ($fieldType->getName() === $name) {
                return $fieldType;
            }
        }

        return null;
    }
}
