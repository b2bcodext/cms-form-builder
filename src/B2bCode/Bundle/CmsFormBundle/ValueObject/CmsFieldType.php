<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\ValueObject;

class CmsFieldType
{
    /** @var string */
    protected $name;

    /** @var string */
    protected $formType;

    /** @var array */
    protected $formOptions;

    /**
     * @param string $name
     * @param string $formType
     * @param array   $formOptions
     */
    public function __construct(string $name, string $formType, $formOptions = [])
    {
        $this->name = $name;
        $this->formType = $formType;
        $this->formOptions = $formOptions;
    }

    /**
     * @return string
     */
    public function getFormType(): string
    {
        return $this->formType;
    }

    /**
     * @param string $formType
     *
     * @return CmsFieldType
     */
    public function setFormType(string $formType): CmsFieldType
    {
        $this->formType = $formType;

        return $this;
    }

    /**
     * @return string
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return CmsFieldType
     */
    public function setName(string $name): CmsFieldType
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return array
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param array $formOptions
     * @return CmsFieldType
     */
    public function setFormOptions(array $formOptions): CmsFieldType
    {
        $this->formOptions = $formOptions;

        return $this;
    }
}
