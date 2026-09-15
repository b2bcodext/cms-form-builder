<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Stub\InvalidRulesBundle;

use Symfony\Component\HttpKernel\Bundle\Bundle;

/**
 * Stub bundle shipping a `form_validation.yml` without the mandatory `forms` root element,
 * used to drive ValidationRuleLoader's configuration-shape guard.
 */
class InvalidRulesBundle extends Bundle
{
}
