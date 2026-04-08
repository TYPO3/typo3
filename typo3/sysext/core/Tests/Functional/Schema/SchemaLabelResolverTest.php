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

namespace TYPO3\CMS\Core\Tests\Functional\Schema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Schema\SchemaLabelResolver;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchemaLabelResolverTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    private SchemaLabelResolver $subject;

    protected function setUp(): void
    {
        parent::setUp();
        $this->subject = $this->get(SchemaLabelResolver::class);
    }

    public static function getLabelForFieldValueDataProvider(): iterable
    {
        yield 'matching item returns label' => [
            'tt_content',
            'menu_type',
            '1',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Item 1', 'value' => '0'],
                                ['label' => 'Item 2', 'value' => '1'],
                                ['label' => 'Item 3', 'value' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            'Item 2',
        ];

        yield 'duplicate values return first match' => [
            'tt_content',
            'menu_type',
            '1',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Item 1', 'value' => '0'],
                                ['label' => 'Item 2a', 'value' => '1'],
                                ['label' => 'Item 2b', 'value' => '1'],
                                ['label' => 'Item 3', 'value' => '3'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            'Item 2a',
        ];

        yield 'non-matching value returns empty string' => [
            'tt_content',
            'menu_type',
            '5',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Item 1', 'value' => '0'],
                                ['label' => 'Item 2', 'value' => '1'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            '',
        ];

        yield 'itemsProcFunc items are resolved' => [
            'tt_content',
            'menu_type',
            '1',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'radio',
                            'items' => [],
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    ['label' => 'Item 1', 'value' => '0'],
                                    ['label' => 'Item 2', 'value' => '1'],
                                    ['label' => 'Item 3', 'value' => '2'],
                                ];
                            },
                        ],
                    ],
                ],
            ],
            [],
            [],
            'Item 2',
        ];

        yield 'TSconfig addItems overrides TCA' => [
            'tt_content',
            'menu_type',
            'custom',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Item 1', 'value' => '0'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            ['addItems.' => ['custom' => 'Custom Label']],
            'Custom Label',
        ];

        yield 'TSconfig altLabels overrides TCA' => [
            'tt_content',
            'menu_type',
            '0',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'Original Label', 'value' => '0'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            ['altLabels.' => ['0' => 'Overridden Label']],
            'Overridden Label',
        ];

        yield 'TSconfig addItems takes precedence over altLabels' => [
            'tt_content',
            'menu_type',
            'foo',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [],
                        ],
                    ],
                ],
            ],
            [],
            [
                'addItems.' => ['foo' => 'Added Label'],
                'altLabels.' => ['foo' => 'Alt Label'],
            ],
            'Added Label',
        ];

        yield 'unknown table returns empty string' => [
            'nonexistent_table',
            'some_field',
            '1',
            [],
            [],
            [],
            '',
        ];

        yield 'returns raw LLL label without translation' => [
            'tt_content',
            'menu_type',
            '0',
            [
                'columns' => [
                    'menu_type' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:bookmark', 'value' => '0'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            'LLL:EXT:core/Resources/Private/Language/locallang_misc.xlf:bookmark',
        ];
    }

    #[DataProvider('getLabelForFieldValueDataProvider')]
    #[Test]
    public function getLabelForFieldValueReturnsExpectedLabel(
        string $table,
        string $field,
        string $value,
        array $tca,
        array $row,
        array $columnTsConfig,
        string $expectedLabel,
    ): void {
        if ($tca !== []) {
            $GLOBALS['TCA'][$table] = $tca;
            $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        }
        self::assertSame($expectedLabel, $this->subject->getLabelForFieldValue($table, $field, $value, $row, $columnTsConfig));
    }

    public static function getLabelsForFieldValuesDataProvider(): iterable
    {
        yield 'multiple values resolved' => [
            'foobar',
            'someColumn',
            'foo, bar',
            [
                'columns' => [
                    'someColumn' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'aFooLabel', 'value' => 'foo'],
                                ['label' => 'aBarLabel', 'value' => 'bar'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            ['aFooLabel', 'aBarLabel'],
        ];

        yield 'TSconfig overrides for multiple values' => [
            'foobar',
            'someColumn',
            'foo,bar,add',
            [
                'columns' => [
                    'someColumn' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'aFooLabel', 'value' => 'foo'],
                                ['label' => 'aBarLabel', 'value' => 'bar'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [
                'addItems.' => ['add' => 'aNewLabel'],
                'altLabels.' => ['bar' => 'aBarDiffLabel'],
            ],
            ['aFooLabel', 'aBarDiffLabel', 'aNewLabel'],
        ];

        yield 'TSconfig altLabels for empty value' => [
            'foobar',
            'someColumn',
            ',foo',
            [
                'columns' => [
                    'someColumn' => [
                        'config' => [
                            'type' => 'select',
                            'renderType' => 'selectSingle',
                            'items' => [
                                ['label' => 'aEmptyValueOptionLabel', 'value' => ''],
                                ['label' => 'a option with value', 'value' => 'foo'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            ['altLabels' => 'aEmptyValueOptionLabelOverride'],
            ['aEmptyValueOptionLabelOverride', 'a option with value'],
        ];

        yield 'empty keyList returns empty array' => [
            'foobar',
            'someColumn',
            '',
            [
                'columns' => [
                    'someColumn' => [
                        'config' => [
                            'type' => 'select',
                            'items' => [
                                ['label' => 'aFooLabel', 'value' => 'foo'],
                            ],
                        ],
                    ],
                ],
            ],
            [],
            [],
            [],
        ];

        yield 'itemsProcFunc items are resolved for multiple values' => [
            'foobar',
            'someColumn',
            'foo,bar',
            [
                'columns' => [
                    'someColumn' => [
                        'config' => [
                            'type' => 'select',
                            'itemsProcFunc' => static function (array $parameters, $pObj) {
                                $parameters['items'] = [
                                    ['label' => 'aFooLabel', 'value' => 'foo'],
                                    ['label' => 'aBarLabel', 'value' => 'bar'],
                                ];
                            },
                        ],
                    ],
                ],
            ],
            [],
            [],
            ['aFooLabel', 'aBarLabel'],
        ];
    }

    #[DataProvider('getLabelsForFieldValuesDataProvider')]
    #[Test]
    public function getLabelsForFieldValuesReturnsExpectedLabels(
        string $table,
        string $field,
        string $valueList,
        array $tca,
        array $row,
        array $columnTsConfig,
        array $expectedLabels,
    ): void {
        if ($tca !== []) {
            $GLOBALS['TCA'][$table] = $tca;
            $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        }
        self::assertSame($expectedLabels, $this->subject->getLabelsForFieldValues($table, $field, $valueList, $row, $columnTsConfig));
    }

    #[Test]
    public function getLabelForFieldValueWithExplicitFieldConfiguration(): void
    {
        $fieldConfiguration = [
            'type' => 'select',
            'items' => [
                ['label' => 'Explicit Item', 'value' => 'x'],
            ],
        ];
        self::assertSame('Explicit Item', $this->subject->getLabelForFieldValue('any_table', 'any_field', 'x', fieldConfiguration: $fieldConfiguration));
    }
}
