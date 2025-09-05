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

namespace TYPO3\CMS\Core\Tests\Unit\DataHandling;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Charset\CharsetConverter;
use TYPO3\CMS\Core\DataHandling\SlugHelper;
use TYPO3\CMS\Core\Slug\SlugNormalizer;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SlugHelperTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function sanitizeDataProvider(): array
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
                'bar',
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
                'ae-ss-oe',
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
                'special-chars-eur-r-o-oe-p-aa-f-c-a-o-yen-c-u',
            ],
            'ensure colon and other http related parts are disallowed' => [
                [],
                'https://example.com:80/my/page/slug/',
                'https//examplecom80/my/page/slug/',
            ],
            'non-ASCII characters are kept' => [
                [],
                'bla-arg应---用-ascii',
                'bla-arg应-用-ascii',
            ],
            'non-normalized characters' => [
                [],
                hex2bin('667275cc88686e65757a6569746c696368656e'),
                'fruehneuzeitlichen',
            ],
        ];
    }

    #[DataProvider('sanitizeDataProvider')]
    #[Test]
    public function sanitizeConvertsString(array $configuration, string $input, string $expected): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $subject = new SlugHelper(
            'dummyTable',
            'dummyField',
            $configuration
        );
        self::assertEquals(
            $expected,
            $subject->sanitize($input)
        );
    }

    public static function generateNeverDeliversEmptySlugDataProvider(): array
    {
        return [
            'simple title' => [
                'Products',
                'products',
            ],
            'title with spaces' => [
                'Product Cow',
                'product-cow',
            ],
            'title with invalid characters' => [
                'Products - Cows',
                'products-cows',
            ],
            'title with only invalid characters' => [
                '!!!',
                'default-51cf35392ca400f2fce656a936831917',
            ],
        ];
    }

    #[DataProvider('generateNeverDeliversEmptySlugDataProvider')]
    #[Test]
    public function generateNeverDeliversEmptySlug(string $input, string $expected): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $GLOBALS['dummyTable']['ctrl'] = [];
        $subject = new SlugHelper(
            'dummyTable',
            'dummyField',
            ['generatorOptions' => ['fields' => ['title']]]
        );
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    public static function sanitizeForPagesDataProvider(): array
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
                '/bar',
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
                '/ae-ss-oe',
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
                '/special-chars-eur-r-o-oe-p-aa-f-c-a-o-yen-c-u',
            ],
            'ensure colon and other http related parts are disallowed' => [
                [],
                'https://example.com:80/my/page/slug/',
                '/https//examplecom80/my/page/slug/',
            ],
            'chinese' => [
                [],
                '应用',
                '/应用',
            ],
            'hindi' => [
                [],
                'कंपनी',
                '/कंपनी',
            ],
            'hindi with plain accent character' => [
                [],
                'कंपनी^',
                '/कंपनी',
            ],
            'hindi with combined accent character' => [
                [],
                'कंपनीâ',
                '/कंपनीa',
            ],
            'japanese numbers (sino-japanese)' => [
                [],
                'さん',
                '/さん',
            ],
            'japanese numbers (kanji)' => [
                [],
                '三つ',
                '/三つ',
            ],
            'persian numbers' => [
                [],
                '۴',
                '/4',
            ],
        ];
    }

    #[DataProvider('sanitizeForPagesDataProvider')]
    #[Test]
    public function sanitizeConvertsStringForPages(array $configuration, string $input, string $expected): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $subject = new SlugHelper(
            'pages',
            'slug',
            $configuration
        );
        self::assertEquals(
            $expected,
            $subject->sanitize($input)
        );
    }

    public static function generateNeverDeliversEmptySlugForPagesDataProvider(): array
    {
        return [
            'simple title' => [
                'Products',
                '/products',
            ],
            'title with spaces' => [
                'Product Cow',
                '/product-cow',
            ],
            'title with invalid characters' => [
                'Products - Cows',
                '/products-cows',
            ],
            'title with only invalid characters' => [
                '!!!',
                '/default-51cf35392ca400f2fce656a936831917',
            ],
        ];
    }

    #[DataProvider('generateNeverDeliversEmptySlugForPagesDataProvider')]
    #[Test]
    public function generateNeverDeliversEmptySlugForPages(string $input, string $expected): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $GLOBALS['dummyTable']['ctrl'] = [];
        $subject = new SlugHelper(
            'pages',
            'slug',
            ['generatorOptions' => ['fields' => ['title']]]
        );
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    public static function generatePrependsSlugsForPagesDataProvider(): array
    {
        return [
            'simple title' => [
                'Products',
                '/parent-page/products',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
            'title with spaces' => [
                'Product Cow',
                '/parent-page/product-cow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
            'title with slash' => [
                'Product/Cow',
                '/parent-page/product/cow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
            'title with slash and replace' => [
                'Product/Cow',
                '/parent-page/productcow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                        'replacements' => [
                            '/' => '',
                        ],
                    ],
                ],
            ],
            'title with slash and replace #2' => [
                'Some Job in city1/city2 (m/w)',
                '/parent-page/some-job-in-city1-city2',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                        'replacements' => [
                            '(m/w)' => '',
                            '/' => '-',
                        ],
                    ],
                ],
            ],
            'title with invalid characters' => [
                'Products - Cows',
                '/parent-page/products-cows',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
            'title with only invalid characters' => [
                '!!!',
                '/parent-page/default-51cf35392ca400f2fce656a936831917',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('generatePrependsSlugsForPagesDataProvider')]
    #[Test]
    public function generatePrependsSlugsForPages(string $input, string $expected, array $options): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $GLOBALS['dummyTable']['ctrl'] = [];
        $parentPage = [
            'uid' => '13',
            'pid' => '10',
            'title' => 'Parent Page',
        ];
        $subject = $this->getAccessibleMock(
            SlugHelper::class,
            ['resolveParentPageRecord'],
            [
                'pages',
                'slug',
                $options,
            ]
        );
        $series = [
            [13, $parentPage],
            [10, null],
        ];
        $subject->expects($this->exactly(2))
            ->method('resolveParentPageRecord')
            ->willReturnCallback(function (int $pid) use (&$series): ?array {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $pid);
                return $arguments[1];
            });
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    public static function generateSlugWithNavTitleAndFallbackForPagesDataProvider(): array
    {
        return [
            'title and empty nav_title' => [
                ['title' => 'Products', 'nav_title' => '', 'subtitle' => ''],
                '/products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title'],
                        ],
                    ],
                ],
            ],
            'title and nav_title' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => ''],
                '/best-products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title'],
                        ],
                    ],
                ],
            ],
            'title and nav_title and subtitle' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/product-subtitle',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['subtitle', 'nav_title', 'title'],
                        ],
                    ],
                ],
            ],
            'definition with a non existing field (misconfiguration)' => [
                ['title' => 'Products', 'nav_title' => '', 'subtitle' => ''],
                '/products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['custom_field', 'title'],
                        ],
                    ],
                ],
            ],
            'empty fields deliver default slug' => [
                ['title' => '', 'nav_title' => '', 'subtitle' => ''],
                '/default-b4dac929c2d313b7ff79fc5edeedd207',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title'],
                        ],
                    ],
                ],
            ],
            'fallback combined with a second field' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/best-products/product-subtitle',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title'], 'subtitle',
                        ],
                    ],
                ],
            ],
            'empty config array deliver default slug' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/default-e13d142b36dcca110f2c3b57ee7a2dd3',
                [
                    'generatorOptions' => [
                        'fields' => [
                            [],
                        ],
                    ],
                ],
            ],
            'empty config deliver default slug' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/default-e13d142b36dcca110f2c3b57ee7a2dd3',
                [
                    'generatorOptions' => [
                        'fields' => [],
                    ],
                ],
            ],
            'combine two fallbacks' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle', 'seo_title' => 'SEO product title'],
                '/seo-product-title/best-products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['seo_title', 'title'], ['nav_title', 'subtitle'],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('generateSlugWithNavTitleAndFallbackForPagesDataProvider')]
    #[Test]
    public function generateSlugWithNavTitleAndFallbackForPages(array $input, string $expected, array $options): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $GLOBALS['dummyTable']['ctrl'] = [];
        $subject = new SlugHelper(
            'pages',
            'slug',
            ['generatorOptions' => $options['generatorOptions']]
        );
        self::assertEquals(
            $expected,
            $subject->generate([
                'title' => $input['title'],
                'nav_title' => $input['nav_title'],
                'subtitle' => $input['subtitle'],
                'seo_title' => $input['seo_title'] ?? '',
                'uid' => 13,
            ], 13)
        );
    }

    #[Test]
    public function generateSlugWithHookModifiers(): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $options = [];
        $options['fallbackCharacter'] = '-';
        $options['generatorOptions'] = [
            'fields' => ['title'],
            'postModifiers' => [
                0 => static function ($parameters, $subject) {
                    $slug = $parameters['slug'];
                    if ($parameters['pid'] == 13) {
                        $slug = 'prepend' . $slug;
                    }
                    return $slug;
                },
            ],
        ];
        $subject = new SlugHelper(
            'pages',
            'slug',
            $options
        );
        $expected = '/prepend/products';
        self::assertEquals(
            $expected,
            $subject->generate([
                'title' => 'Products',
                'nav_title' => 'Best products',
                'subtitle' => 'Product subtitle',
                'seo_title' => 'SEO product title',
                'uid' => 23,
            ], 13)
        );
    }

    public static function generateSlugWithPid0DataProvider(): array
    {
        return [
            'pages' => [
                ['table' => 'pages', 'title' => 'Products'],
                '/',
            ],
            'dummyTable' => [
                ['table' => 'dummyTable', 'title' => 'Products'],
                'products',
            ],
        ];
    }

    #[DataProvider('generateSlugWithPid0DataProvider')]
    #[Test]
    public function generateSlugWithPid0(array $input, string $expected)
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        if (empty($GLOBALS[$input['table']]['ctrl'])) {
            $GLOBALS[$input['table']]['ctrl'] = [];
        }
        $subject = new SlugHelper(
            $input['table'],
            'title',
            ['generatorOptions' => ['fields' => ['title']]]
        );
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input['title'], 'uid' => 13], 0)
        );
    }

    public static function generatePrependsSlugsForNonPagesDataProvider(): array
    {
        return [
            'simple title' => [
                'Product Name',
                'product-name',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('generatePrependsSlugsForNonPagesDataProvider')]
    #[Test]
    public function generatePrependsSlugsForNonPages(string $input, string $expected, array $options): void
    {
        GeneralUtility::addInstance(SlugNormalizer::class, new SlugNormalizer(new CharsetConverter()));
        $GLOBALS['dummyTable']['ctrl'] = [];
        $parentPage = [
            'uid' => '0',
            'pid' => null,
        ];
        $subject = $this->getAccessibleMock(
            SlugHelper::class,
            ['resolveParentPageRecord'],
            [
                'another_table',
                'slug',
                $options,
            ]
        );
        $subject->expects($this->any())
            ->method('resolveParentPageRecord')
            ->withAnyParameters()
            ->willReturn($parentPage);
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }
}
