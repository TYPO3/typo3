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

namespace TYPO3\CMS\Core\Tests\Unit\Schema;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class TcaSchemaFactoryTest extends UnitTestCase
{
    public static function findRelevantFieldsForSubSchemaFindsRelevantFieldsDataProvider(): iterable
    {
        yield 'No type, fallback type 0' => [
            'tableTca' => [
                'types' => [
                    '0' => [
                        'showitem' => 'header,text',
                    ],
                ],
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaName' => null,
            'expected' => ['header', 'text'],
        ];

        yield 'No type, fallback type 1' => [
            'tableTca' => [
                'ctrl' => [
                    'type' => 'text',
                ],
                'types' => [
                    '1' => [
                        'showitem' => 'header,text',
                    ],
                ],
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaName' => null,
            'expected' => ['header', 'text'],
        ];

        yield 'Specific subSchema' => [
            'tableTca' => [
                'ctrl' => [
                    'type' => 'text',
                ],
                'types' => [
                    '0' => [
                        'showitem' => 'header,text',
                    ],
                    'text' => [
                        'showitem' => 'text',
                    ],
                ],
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaName' => 'text',
            'expected' => ['text'],
        ];

        yield 'complex showitem' => [
            'tableTca' => [
                'ctrl' => [
                    'type' => 'text',
                ],
                'types' => [
                    '0' => [
                        'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,header,text;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel',
                    ],
                ],
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaName' => '0',
            'expected' => ['header', 'text'],
        ];

        yield 'with palettes' => [
            'tableTca' => [
                'ctrl' => [
                    'type' => 'text',
                ],
                'types' => [
                    '0' => [
                        'showitem' => '--div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,--palette--;;palette_1',
                    ],
                ],
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
                'palettes' => [
                    'palette_1' => [
                        'label' => 'Palette 1',
                        'showitem' => 'header,text;LLL:EXT:frontend/Resources/Private/Language/locallang_ttc.xlf:bodytext.ALT.bulletlist_formlabel',
                    ],
                ],
            ],
            'subSchemaName' => '0',
            'expected' => ['header', 'text'],
        ];
    }

    #[DataProvider('findRelevantFieldsForSubSchemaFindsRelevantFieldsDataProvider')]
    #[Test]
    public function findRelevantFieldsForSubSchemaFindsRelevantFields(array $tableTca, ?string $subSchemaName, array $expected): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isType('string'))->willReturn(false);
        $subject = new TcaSchemaFactory(
            new RelationMapBuilder(),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $subject->load(['myschema' => $tableTca]);
        $schema = $subject->get('myschema');
        if ($subSchemaName !== null) {
            $schema = $schema->getSubSchema($subSchemaName);
        }
        $fieldNames = [];
        foreach ($schema->getFields() as $fieldName => $fieldConfiguration) {
            $fieldNames[] = $fieldName;
        }
        self::assertSame($expected, $fieldNames);
    }

    #[Test]
    public function getSubSchemaThrowsExceptionForUnavailableSubSchema(): void
    {
        $tableTca = [
            'types' => [
                'text' => [
                    'showitem' => 'text',
                ],
            ],
            'columns' => [
                'header' => [
                    'label' => 'Header',
                    'config' => [
                        'type' => 'input',
                    ],
                ],
                'text' => [
                    'label' => 'Text',
                    'config' => [
                        'type' => 'text',
                    ],
                ],
            ],
        ];
        $this->expectExceptionCode(1661617062);
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isType('string'))->willReturn(false);
        $subject = new TcaSchemaFactory(
            new RelationMapBuilder(),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $subject->load(['myschema' => $tableTca]);
        $subject->get('myschema')->getSubSchema('undefined');
    }

    public static function getFinalFieldConfigurationProcessesColumnOverridesDataProvider(): iterable
    {
        yield 'No overrides, no label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [],
            'fieldLabel' => null,
            'expected' => [
                'label' => 'Text',
                'config' => [
                    'type' => 'text',
                ],
            ],
        ];

        yield 'No overrides, alternative label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [],
            'fieldLabel' => 'Text alt',
            'expected' => [
                'label' => 'Text alt',
                'config' => [
                    'type' => 'text',
                ],
            ],
        ];

        yield 'overrides, no label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [
                'columnsOverrides' => [
                    'text' => [
                        'config' => [
                            'required' => true,
                        ],
                    ],
                ],
            ],
            'fieldLabel' => null,
            'expected' => [
                'label' => 'Text',
                'config' => [
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ];

        yield 'overrides, alternative label' => [
            'fieldName' => 'text',
            'schemaConfiguration' => [
                'columns' => [
                    'header' => [
                        'label' => 'Header',
                        'config' => [
                            'type' => 'input',
                        ],
                    ],
                    'text' => [
                        'label' => 'Text',
                        'config' => [
                            'type' => 'text',
                            'required' => false,
                        ],
                    ],
                ],
            ],
            'subSchemaConfiguration' => [
                'columnsOverrides' => [
                    'text' => [
                        'config' => [
                            'required' => true,
                        ],
                    ],
                ],
            ],
            'fieldLabel' => 'Alt label',
            'expected' => [
                'label' => 'Alt label',
                'config' => [
                    'type' => 'text',
                    'required' => true,
                ],
            ],
        ];
    }

    #[DataProvider('getFinalFieldConfigurationProcessesColumnOverridesDataProvider')]
    #[Test]
    public function getFinalFieldConfigurationProcessesColumnOverrides(string $fieldName, array $schemaConfiguration, array $subSchemaConfiguration, ?string $fieldLabel, array $expected): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isType('string'))->willReturn(false);
        $subject = $this->getAccessibleMock(TcaSchemaFactory::class, ['load'], [new RelationMapBuilder(), new FieldTypeFactory(), '', $cacheMock]);
        $result = $subject->_call('getFinalFieldConfiguration', $fieldName, $schemaConfiguration, $subSchemaConfiguration, $fieldLabel);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function recordTypesInfoIsMergedWithMainSchemaInformation(): void
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isType('string'))->willReturn(false);
        $subject = new TcaSchemaFactory(
            new RelationMapBuilder(),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        $subject->load([
            'myTable' => [
                'ctrl' => [
                    'type' => 'doktype',
                    'previewRenderer' => 'defaultRenderer',
                ],
                'columns' => [
                    'doktype' => [
                        'config' => ['type' => 'text'],
                    ],
                ],
                'types' => [
                    'oneType' => [
                        'previewRenderer' => 'typeSpecificRenderer',
                        'showitem' => '--div--, doktype',
                    ],
                ],
            ],
        ]);
        $schema = $subject->get('myTable');
        $subSchema = $schema->getSubSchema('oneType');

        self::assertSame('defaultRenderer', $schema->getRawConfiguration()['previewRenderer']);
        self::assertSame('typeSpecificRenderer', $subSchema->getRawConfiguration()['previewRenderer']);

    }
}
