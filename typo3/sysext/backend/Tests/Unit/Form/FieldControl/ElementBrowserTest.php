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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FieldControl;

use TYPO3\CMS\Backend\Form\FieldControl\ElementBrowser;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class ElementBrowserTest extends UnitTestCase
{
    /**
     * @test
     */
    public function renderTrimsAllowedValuesFromConfigSection(): void
    {
        $nodeFactory = $this->createMock(NodeFactory::class);
        $elementBrowser = new ElementBrowser($nodeFactory, [
            'fieldName' => 'somefield',
            'isInlineChild' => false,
            'tableName' => 'tt_content',
            'inlineStructure' => [],
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'config' => [
                        'type' => 'group',
                        'allowed' => 'be_users, be_groups',
                    ],
                ],
            ],
        ]);

        $result = $elementBrowser->render();
        self::assertSame($result['linkAttributes']['data-params'], '|||be_users,be_groups|');
    }

    /**
     * @test
     */
    public function renderTrimsAllowedValues(): void
    {
        $nodeFactory = $this->createMock(NodeFactory::class);
        $elementBrowser = new ElementBrowser($nodeFactory, [
            'fieldName' => 'somefield',
            'isInlineChild' => false,
            'tableName' => 'tt_content',
            'inlineStructure' => [],
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'config' => [
                        'type' => 'file',
                        'allowed' => 'jpg, png',
                    ],
                ],
            ],
        ]);
        $result = $elementBrowser->render();
        self::assertSame($result['linkAttributes']['data-params'], '|||jpg,png|');
    }

    /**
     * @test
     * @dataProvider renderResolvesEntryPointDataProvider
     */
    public function renderResolvesEntryPoint(array $config, string $expected): void
    {
        $nodeFactory = $this->createMock(NodeFactory::class);
        $elementBrowser = new ElementBrowser($nodeFactory, [
            'fieldName' => 'somefield',
            'isInlineChild' => false,
            'effectivePid' => 123,
            'site' => new Site('some-site', 123, []),
            'tableName' => 'tt_content',
            'inlineStructure' => [],
            'parameterArray' => [
                'itemFormElName' => '',
                'fieldConf' => [
                    'config' => $config,
                ],
            ],
        ]);
        $result = $elementBrowser->render();
        self::assertEquals($expected, $result['linkAttributes']['data-entry-point'] ?? '');
    }

    public static function renderResolvesEntryPointDataProvider(): \Generator
    {
        yield 'Wildcard' => [
            [
                'type' => 'group',
                'allowed' => '*',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                ],
            ],
            '123',
        ];
        yield 'One table' => [
            [
                'type' => 'group',
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    'pages' => 123,
                ],
            ],
            '123',
        ];
        yield 'One table with default' => [
            [
                'type' => 'group',
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                ],
            ],
            '123',
        ];
        yield 'One table with default and table definition' => [
            [
                'type' => 'group',
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                    'pages' => 124,
                ],
            ],
            '123',
        ];
        yield 'One table with invalid configuration' => [
            [
                'type' => 'group',
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    'some_table' => 123,
                ],
            ],
            '',
        ];
        yield 'Two tables without _default' => [
            [
                'type' => 'group',
                'allowed' => 'pages,some_table',
                'elementBrowserEntryPoints' => [
                    'pages' => 123,
                    'some_table' => 124,
                ],
            ],
            '',
        ];
        yield 'Two tables with _default' => [
            [
                'type' => 'group',
                'allowed' => 'pages,some_table',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                    'pages' => 124,
                    'some_table' => 125,
                ],
            ],
            '123',
        ];
        yield 'Folder' => [
            [
                'type' => 'folder',
                'elementBrowserEntryPoints' => [
                    '_default' => '1:/storage/',
                ],
            ],
            '1:/storage/',
        ];
        yield 'Folder without mandatory _default' => [
            [
                'type' => 'folder',
                'elementBrowserEntryPoints' => [
                    'file' => 123,
                ],
            ],
            '',
        ];
        yield 'Entry point is escaped' => [
            [
                'type' => 'folder',
                'elementBrowserEntryPoints' => [
                    '_default' => '1:/<script>alert(1)</script>/',
                ],
            ],
            '1:/&lt;script&gt;alert(1)&lt;/script&gt;/',
        ];
        yield 'Pid placeholder is resolved' => [
            [
                'type' => 'group',
                'allowed' => '*',
                'elementBrowserEntryPoints' => [
                    '_default' => '###CURRENT_PID###',
                ],
            ],
            '123',
        ];
        yield 'Site placeholder is resolved' => [
            [
                'type' => 'group',
                'allowed' => '*',
                'elementBrowserEntryPoints' => [
                    '_default' => '###SITEROOT###',
                ],
            ],
            '123',
        ];
    }
}
