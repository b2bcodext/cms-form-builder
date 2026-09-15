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

namespace B2bCode\Bundle\CmsFormBundle\Controller\Frontend;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsForm;
use Oro\Bundle\LayoutBundle\Attribute\Layout;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/**
 * Renders a CMS form on the storefront.
 */
class FormController extends AbstractController
{
    /**
     * @param CmsForm $form
     * @return array<string, mixed>
     */
    #[Route(path: '/preview/{uuid}', name: 'b2b_code_cms_form_frontend_form_preview')]
    #[Layout]
    public function formViewAction(CmsForm $form)
    {
        return [
            'data' => [
                'entity' => $form
            ]
        ];
    }
}
