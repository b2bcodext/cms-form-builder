<?php

declare(strict_types=1);

namespace B2bCode\Bundle\CmsFormBundle\Tests\Unit\Helper;

use B2bCode\Bundle\CmsFormBundle\Helper\SlugifyHelper;
use PHPUnit\Framework\TestCase;

class SlugifyHelperTest extends TestCase
{
    /**
     * @dataProvider slugifyDataProvider
     */
    public function testSlugify(string $input, string $expected): void
    {
        self::assertSame($expected, SlugifyHelper::slugify($input));
    }

    /**
     * @return array<string, array{string, string}>
     */
    public function slugifyDataProvider(): array
    {
        return [
            'already a slug is untouched'   => ['contact-us', 'contact-us'],
            'lowercases'                    => ['Contact US', 'contact-us'],
            'collapses runs of whitespace'  => ["Contact \t  us", 'contact-us'],
            'collapses runs of dashes'      => ['contact---us', 'contact-us'],
            'trims leading/trailing dashes' => ['--contact us--', 'contact-us'],
            'transliterates diacritics'     => ['Zażółć gęślą jaźń', 'zazolc-gesla-jazn'],
            'transliterates cyrillic'       => ['Привет мир', 'privet-mir'],
            'drops punctuation'             => ['Contact us, please!', 'contact-us-please'],
            'keeps digits and underscores'  => ['Form_42 name', 'form_42-name'],
            'empty string stays empty'      => ['', ''],
            'punctuation only collapses'    => ['!!!', ''],
        ];
    }
}
