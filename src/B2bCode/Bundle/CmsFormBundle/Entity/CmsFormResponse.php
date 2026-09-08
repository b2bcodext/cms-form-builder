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

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\CustomerBundle\Entity\CustomerVisitor;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareInterface;
use Oro\Bundle\EntityBundle\EntityProperty\DatesAwareTrait;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\ConfigField;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * A single submission of a CMS form, holding the per-field responses.
 */
#[ORM\Entity]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-envelope-open']])]
#[ORM\HasLifecycleCallbacks]
#[ORM\Table(name: 'b2b_code_cms_form_response')]
class CmsFormResponse implements DatesAwareInterface, ExtendEntityInterface
{
    use DatesAwareTrait;
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 10]])]
    protected ?int $id = null;

    /**
     * @var CmsForm|null
     */
    #[ORM\ManyToOne(targetEntity: CmsForm::class)]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 20]])]
    protected $form;

    /**
     * @var Collection<int, CmsFieldResponse>
     */
    #[ORM\OneToMany(mappedBy: 'formResponse', targetEntity: CmsFieldResponse::class, cascade: ['persist'])]
    #[ConfigField(
        defaultValues: ['dataaudit' => ['auditable' => true], 'importexport' => ['full' => true, 'order' => 30]]
    )]
    protected $fieldResponses;

    /**
     * @var CustomerVisitor|null
     */
    #[ORM\ManyToOne(targetEntity: CustomerVisitor::class)]
    #[ORM\JoinColumn(name: 'visitor_id', referencedColumnName: 'id', onDelete: 'SET NULL')]
    #[ConfigField(defaultValues: ['importexport' => ['order' => 40]])]
    protected $visitor;

    #[ORM\Column(name: 'is_resolved', type: 'boolean', nullable: true)]
    protected ?bool $resolved = false;

    public function __construct()
    {
        $this->fieldResponses = new ArrayCollection();
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
     * @return static
     */
    public function setForm(CmsForm $form): static
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @return Collection<int, CmsFieldResponse>
     */
    public function getFieldResponses()
    {
        return $this->fieldResponses;
    }

    /**
     * @param CmsFieldResponse $cmsFieldResponse
     * @return static
     */
    public function addFieldResponse(CmsFieldResponse $cmsFieldResponse): static
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
     * @return static
     */
    public function setVisitor(?CustomerVisitor $visitor): static
    {
        $this->visitor = $visitor;

        return $this;
    }

    /**
     * @param bool $resolved
     *
     * @return $this
     */
    public function setResolved(?bool $resolved): static
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
     * @return array<string, mixed>
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
