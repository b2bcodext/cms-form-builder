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
use Oro\Bundle\EmailBundle\Entity\EmailTemplate;
use Oro\Bundle\EntityConfigBundle\Metadata\Annotation\Config;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityInterface;
use Oro\Bundle\EntityExtendBundle\Entity\ExtendEntityTrait;

/**
 * @ORM\Entity
 * @ORM\Table(
 *     name="b2b_code_cms_form_notification"
 * )
 * @Config(
 *      defaultValues={
 *          "entity"={
 *              "icon"="fa-bell"
 *          }
 *     }
 * )
 */
class CmsFormNotification implements ExtendEntityInterface
{
    use ExtendEntityTrait;

    /**
     * @var int
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    protected $id;

    /**
     * @var CmsForm
     *
     * @ORM\ManyToOne(targetEntity="CmsForm", inversedBy="notifications")
     * @ORM\JoinColumn(name="form_id", referencedColumnName="id", onDelete="CASCADE", nullable=false)
     */
    protected $form;

    /**
     * @var EmailTemplate
     *
     * @ORM\ManyToOne(targetEntity="Oro\Bundle\EmailBundle\Entity\EmailTemplate")
     * @ORM\JoinColumn(name="template_id", referencedColumnName="id", onDelete="SET NULL", nullable=true)
     */
    protected $template;

    /**
     * @var string
     *
     * @ORM\Column(name="email", type="string", nullable=true)
     */
    protected $email;

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
     * @return CmsFormNotification
     */
    public function setForm(CmsForm $form)
    {
        $this->form = $form;

        return $this;
    }

    /**
     * @param EmailTemplate $template
     *
     * @return CmsFormNotification
     */
    public function setTemplate(EmailTemplate $template = null)
    {
        $this->template = $template;

        return $this;
    }

    /**
     * Get template
     *
     * @return EmailTemplate
     */
    public function getTemplate()
    {
        return $this->template;
    }

    /**
     * @return string
     */
    public function getEmail()
    {
        return $this->email;
    }

    /**
     * @param string $email
     *
     * @return CmsFormNotification
     */
    public function setEmail(string $email = null)
    {
        $this->email = $email;

        return $this;
    }
}
