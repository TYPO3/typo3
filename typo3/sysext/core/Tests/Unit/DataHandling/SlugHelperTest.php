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
            'ensure colon and other http related parts are disallowed' => [
                [],
                'https://example.com:80/my/page/slug/',
                'https//examplecom80/my/page/slug/'
            ],
            'non-ASCII characters are kept' => [
                [],
                'bla-arg应---用-ascii',
                'bla-arg应-用-ascii'
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
        self::assertEquals(
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
        self::assertEquals(
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
            'ensure colon and other http related parts are disallowed' => [
                [],
                'https://example.com:80/my/page/slug/',
                '/https//examplecom80/my/page/slug/'
            ],
            'chinese' => [
                [],
                '应用',
                '/应用'
            ],
            'hindi' => [
                [],
                'कंपनी',
                '/कंपनी'
            ],
            'hindi with plain accent character' => [
                [],
                'कंपनी^',
                '/कंपनी'
            ],
            'hindi with combined accent character' => [
                [],
                'कंपनीâ',
                '/कंपनीa'
            ],
            'japanese numbers (sino-japanese)' => [
                [],
                'さん',
                '/さん'
            ],
            'japanese numbers (kanji)' => [
                [],
                '三つ',
                '/三つ'
            ],
            'persian numbers' => [
                [],
                '۴',
                '/4'
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
        self::assertEquals(
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
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    /**
     * @return array
     */
    public function generatePrependsSlugsForPagesDataProvider(): array
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
                ]
            ],
            'title with spaces' => [
                'Product Cow',
                '/parent-page/product-cow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ]
            ],
            'title with slash' => [
                'Product/Cow',
                '/parent-page/product/cow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ]
            ],
            'title with slash and replace' => [
                'Product/Cow',
                '/parent-page/productcow',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                        'replacements' => [
                            '/' => ''
                        ]
                    ],
                ]
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
                            '/' => '-'
                        ]
                    ],
                ]
            ],
            'title with invalid characters' => [
                'Products - Cows',
                '/parent-page/products-cows',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ]
            ],
            'title with only invalid characters' => [
                '!!!',
                '/parent-page/default-51cf35392c',
                [
                    'generatorOptions' => [
                        'fields' => ['title'],
                        'prefixParentPageSlug' => true,
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider generatePrependsSlugsForPagesDataProvider
     * @param string $input
     * @param string $expected
     * @test
     */
    public function generatePrependsSlugsForPages(string $input, string $expected, array $options)
    {
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
                $options
            ]
        );
        $subject->expects(self::at(0))
            ->method('resolveParentPageRecord')->with(13)->willReturn($parentPage);
        $subject->expects(self::at(1))
            ->method('resolveParentPageRecord')->with(10)->willReturn(null);
        self::assertEquals(
            $expected,
            $subject->generate(['title' => $input, 'uid' => 13], 13)
        );
    }

    /**
     * @return array
     */
    public function generateSlugWithNavTitleAndFallbackForPagesDataProvider(): array
    {
        return [
            'title and empty nav_title' => [
                ['title' => 'Products', 'nav_title' => '', 'subtitle' => ''],
                '/products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title']
                        ],
                    ],
                ]
            ],
            'title and nav_title' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => ''],
                '/best-products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title']
                        ],
                    ],
                ]
            ],
            'title and nav_title and subtitle' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/product-subtitle',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['subtitle', 'nav_title', 'title']
                        ],
                    ],
                ]
            ],
            'definition with a non existing field (misconfiguration)' => [
                ['title' => 'Products', 'nav_title' => '', 'subtitle' => ''],
                '/products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['custom_field', 'title']
                        ],
                    ],
                ]
            ],
            'empty fields deliver default slug' => [
                ['title' => '', 'nav_title' => '', 'subtitle' => ''],
                '/default-b4dac929c2',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title']
                        ],
                    ],
                ]
            ],
            'fallback combined with a second field' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/best-products/product-subtitle',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['nav_title', 'title'], 'subtitle'
                        ],
                    ],
                ]
            ],
            'empty config array deliver default slug' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/default-e13d142b36',
                [
                    'generatorOptions' => [
                        'fields' => [
                            []
                        ],
                    ],
                ]
            ],
            'empty config deliver default slug' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle'],
                '/default-e13d142b36',
                [
                    'generatorOptions' => [
                        'fields' => [],
                    ],
                ]
            ],
            'combine two fallbacks' => [
                ['title' => 'Products', 'nav_title' => 'Best products', 'subtitle' => 'Product subtitle', 'seo_title' => 'SEO product title'],
                '/seo-product-title/best-products',
                [
                    'generatorOptions' => [
                        'fields' => [
                            ['seo_title', 'title'], ['nav_title', 'subtitle']
                        ],
                    ],
                ]
            ],
        ];
    }

    /**
     * @dataProvider generateSlugWithNavTitleAndFallbackForPagesDataProvider
     * @param array $input
     * @param string $expected
     * @param array $options
     * @test
     */
    public function generateSlugWithNavTitleAndFallbackForPages(array $input, string $expected, array $options)
    {
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
                'uid' => 13
            ], 13)
        );
    }

    /**
     * @test
     */
    public function generateSlugWithHookModifiers()
    {
        $options = [];
        $options['fallbackCharacter'] = '-';
        $options['generatorOptions'] = [
            'fields' => ['title'],
            'postModifiers' => [
                0 => function ($parameters, $subject) {
                    $slug = $parameters['slug'];
                    if ($parameters['pid'] == 13) {
                        $slug = 'prepend' . $slug;
                    }
                    return $slug;
                }
            ]
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
                'uid' => 23
            ], 13)
        );
    }
}
