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

use Doctrine\ORM\Mapping as ORM;
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityConfigBundle\Metadata\Attribute\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * An email notification configured for a CMS form (recipient email and email template).
 */
#[ORM\Entity]
#[Config(defaultValues: ['entity' => ['icon' => 'fa-bell']])]
#[ORM\Table(name: 'b2b_code_cms_form_notification')]
class CmsFormNotification implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    #[ORM\Column(name: 'id', type: 'integer')]
    #[ORM\Id]
    #[ORM\GeneratedValue(strategy: 'AUTO')]
    protected ?int $id = null;

    /**
     * @var CmsForm|null
     */
    #[ORM\ManyToOne(targetEntity: CmsForm::class, inversedBy: 'notifications')]
    #[ORM\JoinColumn(name: 'form_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    protected $form;

    /**
     * @var EmailTemplate|null
     */
    #[ORM\ManyToOne(targetEntity: EmailTemplate::class)]
    #[ORM\JoinColumn(name: 'template_id', referencedColumnName: 'id', nullable: true, onDelete: 'SET NULL')]
    protected $template;

    #[ORM\Column(name: 'email', type: 'string', nullable: true)]
    protected ?string $email = null;

    /**
     * @return int|null
     */
    public function getId()
    {
        return $this->id;
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
     * @param EmailTemplate $template
     *
     * @return static
     */
    public function setTemplate(?EmailTemplate $template = null): static
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return EmailTemplate|null
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string|null
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return static
     */
    public function setEmail(?string $email = null): static
    {
        $this->email = $email;

        return $this;
    }
}
