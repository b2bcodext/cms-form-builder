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

namespace B2bCode\Bundle\CmsFormBundle\Entity;

use B2bCode\Bundle\CmsFormBundle\Helper\SlugifyHelper;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * A single field of a CMS form (name, label, type, options and sort order).
 */
#[ORM\Entity]
#[Config(
    defaultValues: ['entity' => ['icon' => 'fa-wpforms'], 'grid' => ['default' => 'b2bcode-cms-form-fields-grid']]
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'b2b_code_cms_form_field')]
#[ORM\UniqueConstraint(name: 'uidx_b2b_code_field_form_name', columns: ['form_id', 'name'])]
class CmsFormField implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * This value should start with a symbol and contain only alphabetic symbols, underscore and numbers.
     */
    #[ORM\Column(name: 'name', type: 'string', nullable: false)]
    #[ConfigField(defaultValues: ['importexport' => ['identity' => true]])]
    protected ?string $name = null;

    #[ORM\Column(name: 'label', type: 'string', nullable: false)]
    protected ?string $label = null;

    /**
     * @var CmsForm|null
     */
    #[ORM\ManyToOne(targetEntity: CmsForm::class, inversedBy: 'fields')]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    protected $form;

    #[ORM\Column(name: 'sort_order', type: 'smallint')]
    protected ?int $sortOrder = null;

    #[ORM\Column(name: 'type', type: 'string', nullable: false)]
    protected ?string $type = null;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(name: 'options', type: 'array', nullable: true)]
    protected $options = [];

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
    }

    /**
     * @return string|null
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     * @return static
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getLabel()
    {
        return $this->label;
    }

    /**
     * @param string $label
     * @return static
     */
    public function setLabel(string $label): static
    {
        $this->label = $label;

        return $this;
    }

    /**
     * @return CmsForm|null
     */
    public function getForm()
    {
        return $this->form;
    }

    /**
     * @param CmsForm $form
     * @return static
     */
    public function setForm(CmsForm $form): static
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return int|null
     */
    public function getSortOrder()
    {
        return $this->sortOrder;
    }

    /**
     * @param int $sortOrder
     *
     * @return static
     */
    public function setSortOrder(int $sortOrder): static
    {
        $this->sortOrder = $sortOrder;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getType()
    {
        return $this->type;
    }

    /**
     * @param string $type
     *
     * @return static
     */
    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @param array<string, mixed> $options
     * @return static
     */
    public function setOptions(array $options): static
    {
        foreach ($options as $name => $value) {
            $this->addOption($name, $value);
        }

        return $this;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return $this
     */
    public function addOption(string $name, $value): static
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
     * @return array<string, mixed>
     */
    public function getOptions()
    {
        return $this->options;
    }

    /**
     * Pre persist event handler.
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if ($this->getSortOrder() === null) {
            $this->incrementSortOrder();
        }
        // just in case...
        if ($this->getName() === null && $this->getLabel() !== null) {
            $this->setName(SlugifyHelper::slugify($this->getLabel()));
        }
        if (!$this->createdAt instanceof \DateTimeInterface) {
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
     */
    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
    }

    /**
     * @return array<string, mixed>
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
