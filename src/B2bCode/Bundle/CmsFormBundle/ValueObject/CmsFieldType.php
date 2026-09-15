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

namespace B2bCode\Bundle\CmsFormBundle\ValueObject;

/**
 * Value object describing a CMS form field type: its name, Symfony form type and form options.
 */
class CmsFieldType
{
    protected string $name;

    protected string $formType;

    /** @var array<string, mixed> */
    protected $formOptions;

    /**
     * @param string $name
     * @param string $formType
     * @param array<string, mixed> $formOptions
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
     * @return array<string, mixed>
     */
    public function getFormOptions(): array
    {
        return $this->formOptions;
    }

    /**
     * @param array<string, mixed> $formOptions
     * @return CmsFieldType
     */
    public function setFormOptions(array $formOptions): CmsFieldType
    {
        $this->formOptions = $formOptions;

        return $this;
    }
}
