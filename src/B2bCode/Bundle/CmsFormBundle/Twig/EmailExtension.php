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
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class EmailExtension extends AbstractExtension
{
    /**
     * {@inheritDoc}
     */
    public function getFunctions()
    {
        return [
            new TwigFunction('b2b_code_form_response_array', [$this, 'getResponse'], ['is_safe' => ['html']]),
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
