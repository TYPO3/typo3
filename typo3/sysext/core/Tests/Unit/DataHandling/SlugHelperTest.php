<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\DataHandling;

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

use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SlugHelperTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @return array
     */
    public function sanitizeDataProvider(): array
    {
        return [
            'empty string' => [
                [],
                '',
                '',
            ],
            'existing base' => [
                [],
                '/',
                '',
            ],
            'invalid base' => [
                [],
                '//',
                '',
            ],
            'invalid slug' => [
                [],
                '/slug//',
                'slug/',
            ],
            'lowercase characters' => [
                [],
                '1AZÄ',
                '1azae',
            ],
            'strig tags' => [
                [],
                '<foo>bar</foo>',
                'bar'
            ],
            'replace special chars to -' => [
                [],
                '1 2-3+4_5',
                '1-2-3-4-5',
            ],
            'empty fallback character' => [
                [
                    'fallbackCharacter' => '',
                ],
                '1_2',
                '12',
            ],
            'different fallback character' => [
                [
                    'fallbackCharacter' => '_',
                ],
                '1-2',
                '1_2',
            ],
            'convert umlauts' => [
                [],
                'ä ß Ö',
                'ae-ss-oe'
            ],
            'keep slashes' => [
                [],
                '1/2',
                '1/2',
            ],
            'keep pending slash' => [
                [],
                '/1/2',
                '1/2',
            ],
            'do not remove trailing slash' => [
                [],
                '1/2/',
                '1/2/',
            ],
            'keep pending slash and remove fallback' => [
                [],
                '/-1/2',
                '1/2',
            ],
            'do not remove trailing slash, but remove fallback' => [
                [],
                '1/2-/',
                '1/2/',
            ],
            'reduce multiple fallback chars to one' => [
                [],
                '1---2',
                '1-2',
            ],
            'various special chars' => [
                [],
                'special-chars-«-∑-€-®-†-Ω-¨-ø-π-å-‚-∂-ƒ-©-ª-º-∆-@-¥-≈-ç-√-∫-~-µ-∞-…-–',
                'special-chars-eur-r-o-oe-p-aa-f-c-a-o-yen-c-u'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeDataProvider
     * @param array $configuration
     * @param string $input
     * @param string $expected
     */
    public function sanitizeConvertsString(array $configuration, string $input, string $expected)
    {
        $subject = new SlugHelper(
            'dummyTable',
            'dummyField',
            $configuration
        );
        static::assertEquals(
            $expected,
            $subject->sanitize($input)
        );
    }

    public function generateNeverDeliversEmptySlugDataProvider()
    {
        return [
            'simple title' => [
                'Products',
                'products'
            ],
            'title with spaces' => [
                'Product Cow',
                'product-cow'
            ],
            'title with invalid characters' => [
                'Products - Cows',
                'products-cows'
            ],
            'title with only invalid characters' => [
                '!!!',
                'default-51cf35392c'
            ],
        ];
    }

    /**
     * @dataProvider generateNeverDeliversEmptySlugDataProvider
     * @param string $input
     * @param string $expected
     * @test
     */
    public function generateNeverDeliversEmptySlug(string $input, string $expected)
    {
        $GLOBALS['dummyTable']['ctrl'] = [];
        $subject = new SlugHelper(
            'dummyTable',
            'dummyField',
            ['generatorOptions' => ['fields' => ['title']]]
        );
        static::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    /**
     * @return array
     */
    public function sanitizeForPagesDataProvider(): array
    {
        return [
            'empty string' => [
                [],
                '',
                '/',
            ],
            'existing base' => [
                [],
                '/',
                '/',
            ],
            'invalid base' => [
                [],
                '//',
                '/',
            ],
            'invalid slug' => [
                [],
                '/slug//',
                '/slug/',
            ],
            'lowercase characters' => [
                [],
                '1AZÄ',
                '/1azae',
            ],
            'strig tags' => [
                [],
                '<foo>bar</foo>',
                '/bar'
            ],
            'replace special chars to -' => [
                [],
                '1 2-3+4_5',
                '/1-2-3-4-5',
            ],
            'empty fallback character' => [
                [
                    'fallbackCharacter' => '',
                ],
                '1_2',
                '/12',
            ],
            'different fallback character' => [
                [
                    'fallbackCharacter' => '_',
                ],
                '1-2',
                '/1_2',
            ],
            'convert umlauts' => [
                [],
                'ä ß Ö',
                '/ae-ss-oe'
            ],
            'keep slashes' => [
                [],
                '1/2',
                '/1/2',
            ],
            'keep pending slash' => [
                [],
                '/1/2',
                '/1/2',
            ],
            'do not remove trailing slash' => [
                [],
                '1/2/',
                '/1/2/',
            ],
            'keep pending slash and remove fallback' => [
                [],
                '/-1/2',
                '/1/2',
            ],
            'do not remove trailing slash, but remove fallback' => [
                [],
                '1/2-/',
                '/1/2/',
            ],
            'reduce multiple fallback chars to one' => [
                [],
                '1---2',
                '/1-2',
            ],
            'various special chars' => [
                [],
                'special-chars-«-∑-€-®-†-Ω-¨-ø-π-å-‚-∂-ƒ-©-ª-º-∆-@-¥-≈-ç-√-∫-~-µ-∞-…-–',
                '/special-chars-eur-r-o-oe-p-aa-f-c-a-o-yen-c-u'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider sanitizeForPagesDataProvider
     * @param array $configuration
     * @param string $input
     * @param string $expected
     */
    public function sanitizeConvertsStringForPages(array $configuration, string $input, string $expected)
    {
        $subject = new SlugHelper(
            'pages',
            'slug',
            $configuration
        );
        static::assertEquals(
            $expected,
            $subject->sanitize($input)
        );
    }

    public function generateNeverDeliversEmptySlugForPagesDataProvider()
    {
        return [
            'simple title' => [
                'Products',
                '/products'
            ],
            'title with spaces' => [
                'Product Cow',
                '/product-cow'
            ],
            'title with invalid characters' => [
                'Products - Cows',
                '/products-cows'
            ],
            'title with only invalid characters' => [
                '!!!',
                '/default-51cf35392c'
            ],
        ];
    }

    /**
     * @dataProvider generateNeverDeliversEmptySlugForPagesDataProvider
     * @param string $input
     * @param string $expected
     * @test
     */
    public function generateNeverDeliversEmptySlugForPages(string $input, string $expected)
    {
        $GLOBALS['dummyTable']['ctrl'] = [];
        $subject = new SlugHelper(
            'pages',
            'slug',
            ['generatorOptions' => ['fields' => ['title']]]
        );
        static::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }
}
