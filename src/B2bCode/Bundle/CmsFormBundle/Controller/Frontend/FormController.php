<?php

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
use Oro\Bundle\LayoutBundle\Annotation\Layout;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Symfony\Bundle\FrameworkBundle\Controller\Controller;

class FormController extends Controller
{
    /**
     * @Route("/preview/{uuid}", name="b2b_code_cms_form_frontend_form_preview")
     * @Layout
     * @param CmsForm $form
     * @return array
     */
    public function formViewAction(CmsForm $form)
    {
        return [
            'data' => [
                'entity' => $form
            ]
        ];
    }
}
