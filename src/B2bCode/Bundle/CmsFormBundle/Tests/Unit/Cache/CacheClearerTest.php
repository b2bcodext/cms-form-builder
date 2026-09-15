<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Cache;

use B2bCode\Bundle\CmsFormBundle\Cache\CacheClearer;
use B2bCode\Bundle\CmsFormBundle\Validator\Loader\ValidationRuleLoader;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class CacheClearerTest extends TestCase
{
    private ValidationRuleLoader&MockObject $ruleLoader;
    private CacheClearer $cacheClearer;

    #[\Override]
    protected function setUp(): void
    {
        $this->ruleLoader = $this->createMock(ValidationRuleLoader::class);
        $this->cacheClearer = new CacheClearer($this->ruleLoader);
    }

    public function testWarmUpResolvesTheValidationRulesSoTheyLandInTheCache(): void
    {
        $this->ruleLoader->expects(self::once())
            ->method('getForForm')
            ->with('dummy-call')
            ->willReturn([]);

        $this->cacheClearer->warmUp(sys_get_temp_dir());
    }

    public function testTheWarmerIsOptional(): void
    {
        self::assertTrue($this->cacheClearer->isOptional());
    }

    public function testClearDropsTheCachedValidationRules(): void
    {
        $this->ruleLoader->expects(self::once())->method('clearCache');

        $this->cacheClearer->clear(sys_get_temp_dir());
    }
}
