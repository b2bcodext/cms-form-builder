<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Stub;

/**
 * The bundle detects the optional ORO reCAPTCHA extension with `class_exists()`. That extension is not part
 * of this package's dependency set, so the branches behind the detection are only reachable once the two
 * classes it looks for exist. This installs stand-ins for them; it is idempotent, and `class_alias()` is
 * process-wide, so a test that needs the extension ABSENT must run before any test that installs them.
 */
final class OroReCaptchaStubs
{
    public const FORM_TYPE = 'OroLab\\Bundle\\ReCaptchaBundle\\Form\\Type\\ReCaptchaType';
    public const CONSTRAINT = 'OroLab\\Bundle\\ReCaptchaBundle\\Validator\\Constraints\\IsVerified';

    public static function install(): void
    {
        if (!class_exists(self::FORM_TYPE, false)) {
            class_alias(ReCaptchaTypeStub::class, self::FORM_TYPE);
        }
        if (!class_exists(self::CONSTRAINT, false)) {
            class_alias(IsVerifiedStub::class, self::CONSTRAINT);
        }
    }

    public static function isInstalled(): bool
    {
        return class_exists(self::FORM_TYPE, false);
    }
}
