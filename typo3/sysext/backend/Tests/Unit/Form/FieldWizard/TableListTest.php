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

namespace TYPO3\CMS\Backend\Tests\Unit\Form\FieldWizard;

use TYPO3\CMS\Backend\Form\FieldWizard\TableList;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class TableListTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider renderResolvesEntryPointDataProvider
     */
    public function renderResolvesEntryPoint(array $config, array $expected): void
    {
        $GLOBALS['TCA'] = [];
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);

        $iconMock = $this->createMock(Icon::class);
        $iconMock->method('render')->with(self::anything())->willReturn('icon html');
        $iconFactoryMock = $this->createMock(IconFactory::class);
        $iconFactoryMock->method('getIconForRecord')->with(self::anything())->willReturn($iconMock);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryMock);

        $nodeFactory = $this->createMock(NodeFactory::class);
        $tableList = new TableList($nodeFactory, [
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
        $result = $tableList->render();

        if ($expected === []) {
            self::assertStringNotContainsString('data-entry-point', $result['html']);
        }

        foreach ($expected as $value) {
            self::assertStringContainsString($value, $result['html']);
        }
    }

    public static function renderResolvesEntryPointDataProvider(): \Generator
    {
        yield 'Wildcard' => [
            [
                'allowed' => '*',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                ],
            ],
            [],
        ];
        yield 'One table' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    'pages' => 123,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
            ],
        ];
        yield 'One table with default' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
            ],
        ];
        yield 'One table with default and table definition' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                    'pages' => 124,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="124"',
            ],
        ];
        yield 'One table with invalid configuration' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    'some_table' => 123,
                ],
            ],
            [],
        ];
        yield 'One table without entry point configuration' => [
            [
                'allowed' => 'pages',
            ],
            [],
        ];
        yield 'Two tables without _default' => [
            [
                'allowed' => 'pages,some_table',
                'elementBrowserEntryPoints' => [
                    'pages' => 123,
                    'some_table' => 124,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
                'data-params="|||some_table" data-entry-point="124"',
            ],
        ];
        yield 'Two tables with just _default' => [
            [
                'allowed' => 'pages,some_table',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
                'data-params="|||some_table" data-entry-point="123"',
            ],
        ];
        yield 'Two tables with _default' => [
            [
                'allowed' => 'pages,some_table',
                'elementBrowserEntryPoints' => [
                    '_default' => 123,
                    'pages' => 124,
                    'some_table' => 125,
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="124"',
                'data-params="|||some_table" data-entry-point="125"',
            ],
        ];
        yield 'Entry point is escaped' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    'pages' => '<script>alert(1)</script>',
                ],
            ], [
                'data-params="|||pages" data-entry-point="&lt;script&gt;alert(1)&lt;/script&gt;"',
            ],
        ];
        yield 'Pid placeholder is resolved' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => '###CURRENT_PID###',
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
            ],
        ];
        yield 'Site placeholder is resolved' => [
            [
                'allowed' => 'pages',
                'elementBrowserEntryPoints' => [
                    '_default' => '###SITEROOT###',
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
            ],
        ];
    }
}
