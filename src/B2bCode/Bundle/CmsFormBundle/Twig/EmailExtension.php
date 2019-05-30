<?php

/*
 * This file is part of the B2Bcodext CMS Form Builder.
 *
 * (c) Daniel Nahrebecki <daniel@b2bcodext.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace B2bCode\Bundle\CmsFormBundle\Twig;

use B2bCode\Bundle\CmsFormBundle\Entity\CmsFormResponse;

class EmailExtension extends \Twig_Extension
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new \Twig_SimpleFunction('b2b_code_form_response_array', [$this, 'getResponse'], ['is_safe' => ['html']]),
        ];
    }

    /**
     * @param CmsFormResponse $formResponse
     * @return array
     */
    public function getResponse(CmsFormResponse $formResponse): array
    {
        return $formResponse->toArray();
    }
}
