<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * @ORM\Entity(repositoryClass="B2bCode\Bundle\CmsFormBundle\Entity\Repository\CmsFieldResponseRepository")
 * @ORM\Table(name="b2b_code_cms_field_response")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-envelope-open"
 *          }
 *     }
 * )
 */
class CmsFieldResponse implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var CmsFormField
     *
     * @ORM\ManyToOne(targetEntity="CmsFormField")
     * @ORM\JoinColumn(name="field_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $field;

    /**
     * @var CmsFormResponse
     *
     * @ORM\ManyToOne(targetEntity="CmsFormResponse", inversedBy="fieldResponses")
     * @ORM\JoinColumn(name="form_response_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "excluded"=true
     *          }
     *      }
     * )
     */
    protected $formResponse;

    /**
     * @var string
     *
     * @ORM\Column(name="value", type="text", nullable=true)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     * )
     */
    protected $value;

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return CmsFormField
     */
    public function getField()
    {
        return $this->field;
    }

    /**
     * @param CmsFormField $field
     * @return CmsFieldResponse
     */
    public function setField(CmsFormField $field)
    {
        $this->field = $field;

        return $this;
    }

    /**
     * @return CmsFormResponse
     */
    public function getFormResponse()
    {
        return $this->formResponse;
    }

    /**
     * @param CmsFormResponse $formResponse
     * @return CmsFieldResponse
     */
    public function setFormResponse(CmsFormResponse $formResponse)
    {
        $this->formResponse = $formResponse;

        return $this;
    }

    /**
     * @return string
     */
    public function getRawValue()
    {
        return $this->value;
    }

    /**
     * @param bool $asLabel When true labels will be returned instead of values. Useful in case of having dropdown
     *                      with Label => value generated options. E.g. Country (Poland => pl). When $asLabel is set
     *                      to `true` "Poland" will be returned. When $asLabel is false, "pl" will be returned.
     * @return string|array
     */
    public function getValue(bool $asLabel = false)
    {
        $choices = [];
        if ($asLabel === true && is_array($this->getField()->getOption('choices'))) {
            $choices = $this->getField()->getOption('choices');
        }

        if ($this->getField()->getOption('multiple') === true) {
            $values = json_decode($this->value);
            $returnedValue = [];
            foreach ($values as $value) {
                $returnedValue[] = array_search($value, $choices) ?: $value;
            }
        } else {
            $returnedValue = array_search($this->value, $choices) ?: $this->value;
        }

        return $returnedValue;
    }

    /**
     * @param string $value
     * @return CmsFieldResponse
     */
    public function setValue(?string $value)
    {
        $this->value = $value;

        return $this;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'field'        => $this->getField()->toArray(),
            'rawValue'     => $this->getRawValue(),
            'value'        => $this->getValue(),
            'valueAsLabel' => $this->getValue(true),
        ];
    }
}
