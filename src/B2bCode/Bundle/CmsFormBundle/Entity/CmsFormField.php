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

use B2bCode\Bundle\CmsFormBundle\Helper\SlugifyHelper;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="b2b_code_cms_form_field",
 *     uniqueConstraints={
 *          @ORM\UniqueConstraint(
 *              name="uidx_b2b_code_field_form_name",
 *              columns={"form_id", "name"}
 *          )
 *      }
 * )
 * @Config(
 *     defaultValues={
 *          "entity"={
 *              "icon"="fa-wpforms"
 *          },
 *          "grid"={
 *              "default"="b2bcode-cms-form-fields-grid"
 *          }
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class CmsFormField implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * This value should start with a symbol and contain only alphabetic symbols, underscore and numbers.
     *
     * @var string
     *
     * @ORM\Column(name="name", type="string", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * @var string
     *
     * @ORM\Column(name="label", type="string", nullable=false)
     */
    protected $label;

    /**
     * @var CmsForm
     *
     * @ORM\ManyToOne(targetEntity="CmsForm", inversedBy="fields")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", onDelete="CASCADE")
     */
    protected $form;

    /**
     * @var string
     *
     * @ORM\Column(name="sort_order", type="smallint")
     */
    protected $sortOrder;

    /**
     * @var string
     *
     * @ORM\Column(name="type", type="string", nullable=false)
     */
    protected $type;

    /**
     * @var array
     *
     * @ORM\Column(name="options", type="array", nullable=true)
     */
    protected $options = [];

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return CmsFormField
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return CmsFormField
     */
    public function setLabel(string $label)
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return CmsForm
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param CmsForm $form
     * @return CmsFormField
     */
    public function setForm(CmsForm $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return int
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     *
     * @return CmsFormField
     */
    public function setSortOrder(int $sortOrder)
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return CmsFormField
     */
    public function setType(string $type)
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param array $options
     * @return CmsFormField
     */
    public function setOptions(array $options)
    {
        foreach ($options as $name => $value) {
            $this->addOption($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param        $value
     * @return $this
     */
    public function addOption(string $name, $value)
    {
        if (is_scalar($value) || is_array($value) || is_null($value)) {
            $this->options[$name] = $value;

            return $this;
        }

        throw new \InvalidArgumentException(sprintf(
            'Only scalar and arrays are allowed. %s given',
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }

    /**
     * @param string $name
     * @return mixed|null
     */
    public function getOption(string $name)
    {
        return $this->options[$name] ?? null;
    }

    /**
     * @return array
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Pre persist event handler.
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if ($this->getSortOrder() === null) {
            $this->incrementSortOrder();
        }
        // just in case...
        if ($this->getName() === null && !($this->getLabel() === null)) {
            $this->setName(SlugifyHelper::slugify($this->getLabel()));
        }
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    public function incrementSortOrder(): void
    {
        $cmsForm = $this->getForm();

        if ($cmsForm === null) {
            return;
        }

        $maxSortOrder = 1;
        foreach ($cmsForm->getFields() as $field) {
            if ($field->getSortOrder() >= $maxSortOrder) {
                $maxSortOrder = $field->getSortOrder() + 1;
            }
        }

        $this->setSortOrder($maxSortOrder);
    }

    /**
     * Pre update event handler.
     *
     * @ORM\PreUpdate
     */
    public function preUpdate()
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        return [
            'name'    => $this->getName(),
            'label'   => $this->getLabel(),
            'options' => $this->getOptions(),
        ];
    }
}
