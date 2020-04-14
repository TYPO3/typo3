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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use TYPO3\CMS\Core\Page\Event\BeforeJavaScriptsRenderingEvent;
use TYPO3\CMS\Core\Page\Event\BeforeStylesheetsRenderingEvent;

class AssetDataProvider
{
    public static function filesDataProvider(): array
    {
        return [
            '1 file from fileadmin' => [
                [
                    ['file1', 'fileadmin/foo.ext', [], []]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="fileadmin/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="fileadmin/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file from extension' => [
                [
                    ['file1', 'EXT:core/Resource/Public/foo.ext', [], []]
                ],
                [
                    'file1' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="typo3/sysext/core/Resource/Public/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="typo3/sysext/core/Resource/Public/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file from external source' => [
                [
                    ['file1', 'https://typo3.org/foo.ext', [], []]
                ],
                [
                    'file1' => [
                        'source' => 'https://typo3.org/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="https://typo3.org/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="https://typo3.org/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '2 files' => [
                [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resource/Public/foo.ext', [], []]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'file2' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="fileadmin/foo.ext" rel="stylesheet" type="text/css" >' . LF . '<link href="typo3/sysext/core/Resource/Public/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="fileadmin/foo.ext"></script>' . LF . '<script src="typo3/sysext/core/Resource/Public/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '2 files with override' => [
                [
                    ['file1', 'fileadmin/foo.ext', [], []],
                    ['file2', 'EXT:core/Resource/Public/foo.ext', [], []],
                    ['file1', 'EXT:core/Resource/Public/bar.ext', [], []]
                ],
                [
                    'file1' => [
                        'source' => 'EXT:core/Resource/Public/bar.ext',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'file2' => [
                        'source' => 'EXT:core/Resource/Public/foo.ext',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="typo3/sysext/core/Resource/Public/bar.ext" rel="stylesheet" type="text/css" >' . LF . '<link href="typo3/sysext/core/Resource/Public/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="typo3/sysext/core/Resource/Public/bar.ext"></script>' . LF . '<script src="typo3/sysext/core/Resource/Public/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file with attributes' => [
                [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo'], []]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'rel' => 'foo'
                        ],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link rel="foo" href="fileadmin/foo.ext" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="foo" src="fileadmin/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file with controlled type' => [
                [
                    ['file1', 'fileadmin/foo.ext', ['type' => 'module'], []]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'type' => 'module'
                        ],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link type="module" href="fileadmin/foo.ext" rel="stylesheet" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script type="module" src="fileadmin/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file with attributes override' => [
                [
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['file1', 'fileadmin/foo.ext', ['rel' => 'bar'], []]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [
                            'rel' => 'bar',
                            'another' => 'keep on override'
                        ],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<link rel="bar" another="keep on override" href="fileadmin/foo.ext" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="bar" another="keep on override" src="fileadmin/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
            '1 file with options' => [
                [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true]]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [
                            'priority' => true
                        ],
                    ]
                ],
                [
                    'css_no_prio' => '',
                    'css_prio' => '<link href="fileadmin/foo.ext" rel="stylesheet" type="text/css" >',
                    'js_no_prio' => '',
                    'js_prio' => '<script src="fileadmin/foo.ext"></script>',
                ]
            ],
            '1 file with options override' => [
                [
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => true, 'another' => 'keep on override']],
                    ['file1', 'fileadmin/foo.ext', [], ['priority' => false]]
                ],
                [
                    'file1' => [
                        'source' => 'fileadmin/foo.ext',
                        'attributes' => [],
                        'options' => [
                            'priority' => false,
                            'another' => 'keep on override'
                        ],
                    ]
                ],
                [
                    'css_no_prio' => '<link href="fileadmin/foo.ext" rel="stylesheet" type="text/css" >',
                    'css_prio' => '',
                    'js_no_prio' => '<script src="fileadmin/foo.ext"></script>',
                    'js_prio' => '',
                ]
            ],
        ];
    }

    public static function inlineDataProvider(): array
    {
        return [
            'simple data' => [
                [
                    ['identifier_1', 'foo bar baz', [], []]
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<style>foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>',
                    'js_prio' => '',
                ]
            ],
            '2 times simple data' => [
                [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []]
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'identifier_2' => [
                        'source' => 'bar baz foo',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<style>foo bar baz</style>' . LF . '<style>bar baz foo</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>' . LF . '<script>bar baz foo</script>',
                    'js_prio' => '',
                ]
            ],
            '2 times simple data with override' => [
                [
                    ['identifier_1', 'foo bar baz', [], []],
                    ['identifier_2', 'bar baz foo', [], []],
                    ['identifier_1', 'baz foo bar', [], []],
                ],
                [
                    'identifier_1' => [
                        'source' => 'baz foo bar',
                        'attributes' => [],
                        'options' => [],
                    ],
                    'identifier_2' => [
                        'source' => 'bar baz foo',
                        'attributes' => [],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<style>baz foo bar</style>' . LF . '<style>bar baz foo</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>baz foo bar</script>' . LF . '<script>bar baz foo</script>',
                    'js_prio' => '',
                ]
            ],
            'simple data with attributes' => [
                [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo'], []],
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [
                            'rel' => 'foo'
                        ],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<style rel="foo">foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="foo">foo bar baz</script>',
                    'js_prio' => '',
                ]
            ],
            'simple data with attributes override' => [
                [
                    ['identifier_1', 'foo bar baz', ['rel' => 'foo', 'another' => 'keep on override'], []],
                    ['identifier_1', 'foo bar baz', ['rel' => 'bar'], []],
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [
                            'rel' => 'bar',
                            'another' => 'keep on override'
                        ],
                        'options' => [],
                    ]
                ],
                [
                    'css_no_prio' => '<style rel="bar" another="keep on override">foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script rel="bar" another="keep on override">foo bar baz</script>',
                    'js_prio' => '',
                ]
            ],
            'simple data with options' => [
                [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true]]
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [
                            'priority' => true
                        ],
                    ]
                ],
                [
                    'css_no_prio' => '',
                    'css_prio' => '<style>foo bar baz</style>',
                    'js_no_prio' => '',
                    'js_prio' => '<script>foo bar baz</script>',
                ]
            ],
            'simple data with options override' => [
                [
                    ['identifier_1', 'foo bar baz', [], ['priority' => true, 'another' => 'keep on override']],
                    ['identifier_1', 'foo bar baz', [], ['priority' => false]]
                ],
                [
                    'identifier_1' => [
                        'source' => 'foo bar baz',
                        'attributes' => [],
                        'options' => [
                            'priority' => false,
                            'another' => 'keep on override'
                        ],
                    ]
                ],
                [
                    'css_no_prio' => '<style>foo bar baz</style>',
                    'css_prio' => '',
                    'js_no_prio' => '<script>foo bar baz</script>',
                    'js_prio' => '',
                ]
            ],
        ];
    }

    public static function mediaDataProvider(): array
    {
        return [
            '1 image no additional information' => [
                [
                    ['fileadmin/foo.png', []]
                ],
                [
                    'fileadmin/foo.png' => []
                ]
            ],
            '2 images no additional information' => [
                [
                    ['fileadmin/foo.png', []],
                    ['fileadmin/bar.png', []],
                ],
                [
                    'fileadmin/foo.png' => [],
                    'fileadmin/bar.png' => [],
                ]
            ],
            '1 image with additional information' => [
                [
                    ['fileadmin/foo.png', ['foo' => 'bar']]
                ],
                [
                    'fileadmin/foo.png' => ['foo' => 'bar']
                ]
            ],
            '2 images with additional information' => [
                [
                    ['fileadmin/foo.png', ['foo' => 'bar']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                ],
                [
                    'fileadmin/foo.png' => ['foo' => 'bar'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ]
            ],
            '2 images with additional information override' => [
                [
                    ['fileadmin/foo.png', ['foo' => 'bar']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                    ['fileadmin/foo.png', ['foo' => 'baz']],
                ],
                [
                    'fileadmin/foo.png' => ['foo' => 'baz'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ]
            ],
            '2 images with additional information override keep existing' => [
                [
                    ['fileadmin/foo.png', ['foo' => 'bar', 'bar' => 'baz']],
                    ['fileadmin/bar.png', ['foo' => 'baz']],
                    ['fileadmin/foo.png', ['foo' => 'baz']],
                ],
                [
                    'fileadmin/foo.png' => ['foo' => 'baz', 'bar' => 'baz'],
                    'fileadmin/bar.png' => ['foo' => 'baz'],
                ]
            ],
        ];
    }

    /**
     * cross-product of all combinations of AssetRenderer::render*() methods and priorities
     * @return array[] [render method name, isInline, isPriority, event class]
     */
    public static function renderMethodsAndEventsDataProvider(): array
    {
        return [
            ['renderInlineJavaScript', true, true, BeforeJavaScriptsRenderingEvent::class],
            ['renderInlineJavaScript', true, false, BeforeJavaScriptsRenderingEvent::class],
            ['renderJavaScript', false, true, BeforeJavaScriptsRenderingEvent::class],
            ['renderJavaScript', false, false, BeforeJavaScriptsRenderingEvent::class],
            ['renderInlineStylesheets', true, true, BeforeStylesheetsRenderingEvent::class],
            ['renderInlineStylesheets', true, false, BeforeStylesheetsRenderingEvent::class],
            ['renderStylesheets', false, true, BeforeStylesheetsRenderingEvent::class],
            ['renderStylesheets', false, false, BeforeStylesheetsRenderingEvent::class],
        ];
    }
}
