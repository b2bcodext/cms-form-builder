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

use B2bCode\Bundle\CmsFormBundle\Model\ExtendCmsFormResponse;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\ConfigField;


/**
 * @ORM\Entity
 * @ORM\Table(name="b2b_code_cms_form_response")
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-envelope-open"
 *          }
 *     }
 * )
 * @ORM\HasLifecycleCallbacks()
 */
class CmsFormResponse extends ExtendCmsFormResponse implements DatesAwareInterface
{
    use DatesAwareTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=10
     *          }
     *      }
     * )
     */
    protected $id;

    /**
     * @var CmsForm
     *
     * @ORM\ManyToOne(targetEntity="CmsForm")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=20
     *          }
     *      }
     * )
     */
    protected $form;

    /**
     * @var Collection|CmsFieldResponse[]
     *
     * @ORM\OneToMany(targetEntity="CmsFieldResponse", mappedBy="formResponse", cascade={"persist"})
     * @ConfigField(
     *      defaultValues={
     *          "dataaudit"={
     *              "auditable"=true
     *          },
     *          "importexport"={
     *              "full"=true,
     *              "order"=30
     *          }
     *      }
     * )
     */
    protected $fieldResponses;

    /**
     * @var CustomerVisitor|null
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\CustomerBundle\Entity\CustomerVisitor")
     * @ORM\JoinColumn(name="visitor_id", referencedColumnName="id", onDelete="SET NULL")
     * @ConfigField(
     *      defaultValues={
     *          "importexport"={
     *              "order"=40
     *          }
     *      }
     * )
     */
    protected $visitor;

    /**
     * @var bool
     *
     * @ORM\Column(name="is_resolved", type="boolean", nullable=true)
     */
    protected $resolved = false;

    public function __construct()
    {
        $this->fieldResponses = new ArrayCollection();

        parent::__construct();
    }

    /**
     * @return int
     */
    public function getId()
    {
        return $this->id;
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
     *
     * @return CmsFormResponse
     */
    public function setForm(CmsForm $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return CmsFieldResponse[]|Collection
     */
    public function getFieldResponses()
    {
        return $this->fieldResponses;
    }

    /**
     * @param CmsFieldResponse $cmsFieldResponse
     * @return CmsFormResponse
     */
    public function addFieldResponse(CmsFieldResponse $cmsFieldResponse)
    {
        if (!$this->fieldResponses->contains($cmsFieldResponse)) {
            $this->fieldResponses->add($cmsFieldResponse);
            $cmsFieldResponse->setFormResponse($this);
        }

        return $this;
    }

    /**
     * @return CustomerVisitor|null
     */
    public function getVisitor()
    {
        return $this->visitor;
    }

    /**
     * @param CustomerVisitor|null $visitor
     * @return CmsFormResponse
     */
    public function setVisitor(?CustomerVisitor $visitor)
    {
        $this->visitor = $visitor;

        return $this;
    }

    /**
     * @param bool $resolved
     *
     * @return $this
     */
    public function setResolved(?bool $resolved)
    {
        $this->resolved = (bool) $resolved;

        return $this;
    }

    /**
     * @return bool
     */
    public function isResolved()
    {
        return (bool) $this->resolved;
    }

    /**
     * @return array
     */
    public function toArray(): array
    {
        $fieldsResponsesArray = [];
        $fieldsResponses = $this->getFieldResponses();
        foreach ($fieldsResponses as $fieldsResponse) {
            $fieldsResponsesArray[] = $fieldsResponse->toArray();
        }

        return [
            'form'           => $this->getForm()->toArray(),
            'fieldResponses' => $fieldsResponsesArray,
        ];
    }
}
