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
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * A CMS form: its alias, fields, notifications and submitted responses.
 */
#[ORM\Entity]
#[Config(
    routeName: 'b2b_code_cms_form_index',
    routeView: 'b2b_code_cms_form_view',
    defaultValues: ['entity' => ['icon' => 'fa-wpforms'], 'grid' => ['default' => 'b2bcode-cms-forms-grid']]
)]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'b2b_code_cms_form')]
class CmsForm implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    /**
     * @var int|null
     */
    #[ORM\Column(type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected $id;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'name', type: 'string', length: 255)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected $name;

    /**
     * This value should start with a symbol and contain only alphabetic symbols, underscore and numbers.
     *
     * @var string|null
     */
    #[ORM\Column(name: 'alias', type: 'string', length: 255, unique: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['identity' => true]])]
    protected $alias;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'uuid', type: 'string', unique: true)]
    protected $uuid;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'preview_enabled', type: 'boolean', nullable: true)]
    protected $previewEnabled = false;

    /**
     * @var Collection<int, CmsFormField>
     */
    #[ORM\OneToMany(mappedBy: 'form', targetEntity: CmsFormField::class, cascade: ['persist'])]
    #[ORM\OrderBy(['sortOrder' => 'ASC'])]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected $fields;

    /**
     * @var bool
     */
    #[ORM\Column(name: 'notifications_enabled', type: 'boolean', nullable: true)]
    protected $notificationsEnabled = false;


    /**
     * @var Collection<int, CmsFormNotification>
     */
    #[ORM\OneToMany(mappedBy: 'form', targetEntity: CmsFormNotification::class, cascade: ['all'], orphanRemoval: true)]
    #[ConfigField(defaultValues: ['dataaudit' => ['auditable' => true]])]
    protected $notifications;

    /**
     * @var string|null
     */
    #[ORM\Column(name: 'redirect_url', type: 'string', length: 1024, nullable: true)]
    protected $redirectUrl;

    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

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
    public function uuid()
    {
        return $this->uuid;
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
     */
    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string|null
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return static
     */
    public function setAlias(string $alias): static
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setPreviewEnabled(bool $enabled): static
    {
        $this->previewEnabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isPreviewEnabled()
    {
        return $this->previewEnabled;
    }

    /**
     * @return Collection<int, CmsFormField>
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param iterable<CmsFormField> $fields
     */
    public function setFields(iterable $fields): void
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * @param CmsFormField $field
     * @return $this
     */
    public function addField(CmsFormField $field): static
    {
        if (!$this->fields->contains($field)) {
            $this->fields->add($field);
            $field->setForm($this);
        }

        return $this;
    }

    /**
     * @param string $name
     * @return CmsFormField|null
     */
    public function getField(string $name)
    {
        foreach ($this->getFields() as $field) {
            if ($field->getName() === $name) {
                return $field;
            }
        }

        return null;
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasField(string $name)
    {
        foreach ($this->getFields() as $field) {
            if ($field->getName() === $name) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setNotificationsEnabled(bool $enabled): static
    {
        $this->notificationsEnabled = $enabled;

        return $this;
    }

    /**
     * @return bool
     */
    public function isNotificationsEnabled()
    {
        return $this->notificationsEnabled;
    }

    /**
     * @return Collection<int, CmsFormNotification>
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param CmsFormNotification $notification
     * @return $this
     */
    public function addNotification(CmsFormNotification $notification): static
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setForm($this);
        }

        return $this;
    }

    /**
     * @param iterable<CmsFormNotification> $notifications
     * @return static
     */
    public function setNotifications(iterable $notifications): static
    {
        foreach ($notifications as $notification) {
            $this->addNotification($notification);
        }

        return $this;
    }

    /**
     * @param CmsFormNotification $notification
     * @return $this
     */
    public function removeNotification(CmsFormNotification $notification): static
    {
        $this->notifications->removeElement($notification);

        return $this;
    }

    /**
     * @return string|null
     */
    public function getRedirectUrl(): ?string
    {
        return $this->redirectUrl;
    }

    /**
     * @param string|null $redirectUrl
     */
    public function setRedirectUrl(?string $redirectUrl): void
    {
        $this->redirectUrl = $redirectUrl;
    }

    /**
     * Pre persist event handler.
     */
    #[ORM\PrePersist]
    public function prePersist(): void
    {
        if (!$this->createdAt instanceof \DateTimeInterface) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        // just in case...
        if ($this->alias === null) {
            // may cause non-unique doctrine exception
            $this->alias = SlugifyHelper::slugify((string) $this->getName());
        }
        if ($this->uuid === null) {
            $this->uuid = UUIDGenerator::v4();
        }

        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
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
        $fieldsArray = [];
        $fields = $this->getFields();
        foreach ($fields as $field) {
            $fieldsArray[] = $field->toArray();
        }

        return [
            'name'   => $this->getName(),
            'fields' => $fieldsArray,
        ];
    }
}
