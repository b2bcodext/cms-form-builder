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
use B2bCode\Bundle\CmsFormBundle\Model\ExtendCmsForm;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;
use Oro\Bundle\SecurityBundle\Tools\UUIDGenerator;

/**
 * @ORM\Entity
 * @ORM\Table(name="b2b_code_cms_form")
 * @Config(
 *      routeName="b2b_code_cms_form_index",
 *      routeView="b2b_code_cms_form_view",
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-wpforms"
 *          },
 *          "grid"={
 *              "default"="b2bcode-cms-forms-grid"
 *          }
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class CmsForm extends ExtendCmsForm implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var int
     *
     * @ORM\Column(name="name", type="string", length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $name;

    /**
     * This value should start with a symbol and contain only alphabetic symbols, underscore and numbers.
     *
     * @var string
     *
     * @ORM\Column(name="alias", type="string", unique=true, length=255)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "identity"=true
     *          }
     *      }
     * )
     */
    protected $alias;

    /**
     * @var string
     *
     * @ORM\Column(name="uuid", type="string", unique=true)
     */
    protected $uuid;

    /**
     * @var bool
     *
     * @ORM\Column(name="preview_enabled", type="boolean", nullable=true)
     */
    protected $previewEnabled = false;

    /**
     * @var Collection|CmsFormField[]
     *
     * @ORM\OneToMany(targetEntity="CmsFormField", mappedBy="form", cascade={"persist"})
     * @ORM\OrderBy({"sortOrder" = "ASC"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $fields;

    /**
     * @var bool
     *
     * @ORM\Column(name="notifications_enabled", type="boolean", nullable=true)
     */
    protected $notificationsEnabled = false;
    
    /**
     * @var Collection|CmsFormNotification[]
     *
     * @ORM\OneToMany(targetEntity="CmsFormNotification", mappedBy="form", cascade={"all"}, orphanRemoval=true)
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          }
     *      }
     * )
     */
    protected $notifications;

    /**
     * @var string|null
     *
     * @ORM\Column(name="redirect_url", type="string", length=1024, nullable=true)
     */
    protected $redirectUrl;
    
    public function __construct()
    {
        $this->fields = new ArrayCollection();
        $this->notifications = new ArrayCollection();
    }

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
    public function uuid()
    {
        return $this->uuid;
    }

    /**
     * @return int
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @param string $name
     */
    public function setName(string $name)
    {
        $this->name = $name;

        return $this;
    }

    /**
     * @return string
     */
    public function getAlias()
    {
        return $this->alias;
    }

    /**
     * @param string $alias
     * @return CmsForm
     */
    public function setAlias(string $alias)
    {
        $this->alias = $alias;

        return $this;
    }

    /**
     * @param bool $enabled
     * @return $this
     */
    public function setPreviewEnabled(bool $enabled)
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
     * @return CmsFormField[]|Collection
     */
    public function getFields()
    {
        return $this->fields;
    }

    /**
     * @param iterable $fields
     */
    public function setFields(iterable $fields)
    {
        foreach ($fields as $field) {
            $this->addField($field);
        }
    }

    /**
     * @param CmsFormField $field
     * @return $this
     */
    public function addField(CmsFormField $field)
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
    public function setNotificationsEnabled(bool $enabled)
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
     * @return CmsFormNotification[]|Collection
     */
    public function getNotifications()
    {
        return $this->notifications;
    }

    /**
     * @param CmsFormNotification $notification
     * @return $this
     */
    public function addNotification(CmsFormNotification $notification)
    {
        if (!$this->notifications->contains($notification)) {
            $this->notifications->add($notification);
            $notification->setForm($this);
        }

        return $this;
    }

    /**
     * @param CmsFormNotification[] $notifications
     * @return CmsForm
     */
    public function setNotifications(iterable $notifications)
    {
        foreach ($notifications as $notification) {
            $this->addNotification($notification);
        }

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
     * @param CmsFormNotification $notification
     * @return $this
     */
    public function removeNotification(CmsFormNotification $notification)
    {
        $this->notifications->removeElement($notification);

        return $this;
    }

    /**
     * Pre persist event handler.
     *
     * @ORM\PrePersist
     */
    public function prePersist()
    {
        if ($this->createdAt === null) {
            $this->createdAt = new \DateTime('now', new \DateTimeZone('UTC'));
        }

        // just in case...
        if ($this->alias === null) {
            // may cause non-unique doctrine exception
            $this->alias = SlugifyHelper::slugify($this->getName());
        }
        if ($this->uuid === null) {
            $this->uuid = UUIDGenerator::v4();
        }

        $this->updatedAt = new \DateTime('now', new \DateTimeZone('UTC'));
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
