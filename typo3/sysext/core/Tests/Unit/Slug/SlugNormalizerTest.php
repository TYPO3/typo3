<?php

declare(strict_types=1);

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

namespace TYPO3\CMS\Core\Tests\Unit\Slug;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\Charset\CharsetProvider;
use TYPO3\CMS\Core\Slug\SlugNormalizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SlugNormalizerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function normalizeDataProvider(): array
    {
        return [
            'empty string' => [
                'input' => '',
                'fallbackCharacter' => null,
                'expected' => '',
            ],
            'existing base' => [
                'input' => '/',
                'fallbackCharacter' => null,
                'expected' => '',
            ],
            'invalid base' => [
                'input' => '//',
                'fallbackCharacter' => null,
                'expected' => '',
            ],
            'invalid slug' => [
                'input' => '/slug//',
                'fallbackCharacter' => null,
                'expected' => 'slug/',
            ],
            'lowercase characters' => [
                'input' => '1AZÄ',
                'fallbackCharacter' => null,
                'expected' => '1azae',
            ],
            'strig tags' => [
                'input' => '<foo>bar</foo>',
                'fallbackCharacter' => null,
                'expected' => 'bar',
            ],
            'replace special chars to -' => [
                'input' => '1 2-3+4_5',
                'fallbackCharacter' => null,
                'expected' => '1-2-3-4-5',
            ],
            'empty fallback character' => [
                'input' => '1_2',
                'fallbackCharacter' => '',
                'expected' => '12',
            ],
            'different fallback character' => [
                'input' => '1-2',
                'fallbackCharacter' => '_',
                'expected' => '1_2',
            ],
            'convert umlauts' => [
                'input' => 'ä ß Ö',
                'fallbackCharacter' => null,
                'expected' => 'ae-ss-oe',
            ],
            'keep slashes' => [
                'input' => '1/2',
                'fallbackCharacter' => null,
                'expected' => '1/2',
            ],
            'keep pending slash' => [
                'input' => '/1/2',
                'fallbackCharacter' => null,
                'expected' => '1/2',
            ],
            'do not remove trailing slash' => [
                'input' => '1/2/',
                'fallbackCharacter' => null,
                'expected' => '1/2/',
            ],
            'keep pending slash and remove fallback' => [
                'input' => '/-1/2',
                'fallbackCharacter' => null,
                'expected' => '1/2',
            ],
            'do not remove trailing slash, but remove fallback' => [
                'input' => '1/2-/',
                'fallbackCharacter' => null,
                'expected' => '1/2/',
            ],
            'reduce multiple fallback chars to one' => [
                'input' => '1---2',
                'fallbackCharacter' => null,
                'expected' => '1-2',
            ],
            'various special chars' => [
                'input' => 'special-chars-«-∑-€-®-†-Ω-¨-ø-π-å-‚-∂-ƒ-©-ª-º-∆-@-¥-≈-ç-√-∫-~-µ-∞-…-–',
                'fallbackCharacter' => null,
                'expected' => 'special-chars-eur-r-o-oe-p-aa-f-c-a-o-yen-c-u',
            ],
            'ensure colon and other http related parts are disallowed' => [
                'input' => 'https://example.com:80/my/page/slug/',
                'fallbackCharacter' => null,
                'expected' => 'https//examplecom80/my/page/slug/',
            ],
            'non-ASCII characters are kept' => [
                'input' => 'bla-arg应---用-ascii',
                'fallbackCharacter' => null,
                'expected' => 'bla-arg应-用-ascii',
            ],
            'non-normalized characters' => [
                'input' => hex2bin('667275cc88686e65757a6569746c696368656e'),
                'fallbackCharacter' => null,
                'expected' => 'fruehneuzeitlichen',
            ],
        ];
    }

    #[DataProvider('normalizeDataProvider')]
    #[Test]
    public function normalizeConvertsString(string $input, ?string $fallbackCharacter, string $expected): void
    {
        $subject = new SlugNormalizer(new CharsetConverter(new CharsetProvider()));
        self::assertEquals($expected, $subject->normalize($input, $fallbackCharacter));
    }
}
