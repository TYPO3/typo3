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

use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
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
    use ProphecyTrait;

    /**
     * @test
     * @dataProvider renderResolvesEntryPointDataProvider
     */
    public function renderResolvesEntryPoint(array $config, array $expected): void
    {
        $GLOBALS['TCA'] = [];

        $languageServiceProphecy = $this->prophesize(LanguageService::class);
        $languageServiceProphecy->sL(Argument::cetera())->willReturn('');
        $GLOBALS['LANG'] = $languageServiceProphecy->reveal();

        $iconProphecy = $this->prophesize(Icon::class);
        $iconProphecy->render(Argument::any())->willReturn('icon html');
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        $iconFactoryProphecy->getIconForRecord(Argument::cetera())->willReturn($iconProphecy->reveal());
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());

        $nodeFactory = $this->prophesize(NodeFactory::class);
        $tableList = new TableList($nodeFactory->reveal(), [
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

    public function renderResolvesEntryPointDataProvider(): \Generator
    {
        yield 'Wildcard' => [
            [
                'allowed' => '*',
                'entryPoints' => [
                    '_default' => 123,
                ],
            ],
            [],
        ];
        yield 'One table' => [
            [
                'allowed' => 'pages',
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
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
                'entryPoints' => [
                    'pages' => '<script>alert(1)</script>',
                ],
            ], [
                'data-params="|||pages" data-entry-point="&lt;script&gt;alert(1)&lt;/script&gt;"',
            ],
        ];
        yield 'Pid placeholder is resolved' => [
            [
                'allowed' => 'pages',
                'entryPoints' => [
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
                'entryPoints' => [
                    '_default' => '###SITEROOT###',
                ],
            ],
            [
                'data-params="|||pages" data-entry-point="123"',
            ],
        ];
    }
}
