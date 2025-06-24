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

namespace TYPO3\CMS\Backend\Tests\Unit\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\EditDocumentController;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Schema\FieldTypeFactory;
use TYPO3\CMS\Core\Schema\RelationMapBuilder;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class EditDocumentControllerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    #[DataProvider('slugDependentFieldsAreAddedToColumnsOnlyDataProvider')]
    #[Test]
    public function slugDependentFieldsAreAddedToColumnsOnly(array $result, array $selectedFields, string $tableName, array $configuration): void
    {
        $GLOBALS['TCA'][$tableName]['columns'] = $configuration;

        $editDocumentControllerMock = $this->getAccessibleMock(EditDocumentController::class, null, [], '', false);
        $editDocumentControllerMock->_set('columnsOnly', [$tableName => $selectedFields]);
        $queryParams = [
            'edit' => [
                $tableName => [
                    '123,456' => 'edit',
                ],
            ],
        ];

        $tcaSchemaFactory = $this->getTcaSchemaFactory();
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);
        $editDocumentControllerMock->_set('tcaSchemaFactory', $tcaSchemaFactory);
        $editDocumentControllerMock->_call('addSlugFieldsToColumnsOnly', $queryParams);

        self::assertEquals($selectedFields, array_values($editDocumentControllerMock->_get('columnsOnly')[$tableName] ?? []));
        self::assertEquals($result, array_values($editDocumentControllerMock->_get('columnsOnly')['__hiddenGeneratorFields'][$tableName] ?? []));
    }

    public static function slugDependentFieldsAreAddedToColumnsOnlyDataProvider(): array
    {
        return [
            'fields in string' => [
                ['title'],
                ['fo', 'bar', 'slug'],
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => ['title'],
                            ],
                        ],
                    ],
                ],
            ],
            'fields in string and array' => [
                ['nav_title', 'other_field'],
                ['slug', 'fo', 'title'],
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => [['nav_title', 'title'], 'other_field'],
                            ],
                        ],
                    ],
                ],
            ],
            'unique fields' => [
                ['some_field'],
                ['slug', 'fo', 'title'],
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => ['title', 'some_field'],
                            ],
                        ],
                    ],
                ],
            ],
            'fields as comma-separated list' => [
                ['nav_title', 'some_field'],
                ['slug', 'fo', 'title'],
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'slug',
                            'generatorOptions' => [
                                'fields' => 'nav_title,some_field',
                            ],
                        ],
                    ],
                ],
            ],
            'no slug field given' => [
                [],
                ['slug', 'fo'],
                'fake',
                [
                    'slug' => [
                        'config' => [
                            'type' => 'input',
                            'generatorOptions' => [
                                'fields' => [['nav_title', 'title'], 'other_field'],
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    #[Test]
    public function addSlugFieldsToColumnsOnlySupportsMultipleTables(): void
    {
        $GLOBALS['TCA']['aTable']['columns'] = [
            'aField' => [
                'config' => [
                    'type' => 'slug',
                    'generatorOptions' => [
                        'fields' => ['aTitle'],
                    ],
                ],
            ],
        ];
        $GLOBALS['TCA']['bTable']['columns'] = [
            'bField' => [
                'config' => [
                    'type' => 'slug',
                    'generatorOptions' => [
                        'fields' => ['bTitle'],
                    ],
                ],
            ],
        ];

        $editDocumentControllerMock = $this->getAccessibleMock(EditDocumentController::class, null, [], '', false);
        $editDocumentControllerMock->_set('columnsOnly', [
            'aTable' => ['aField'],
            'bTable' => ['bField'],
        ]);
        $queryParams = [
            'edit' => [
                'aTable' => [
                    '123' => 'edit',
                ],
                'bTable' => [
                    '456' => 'edit',
                ],
            ],
        ];

        $tcaSchemaFactory = $this->getTcaSchemaFactory();
        $tcaSchemaFactory->rebuild($GLOBALS['TCA']);

        $editDocumentControllerMock->_set('tcaSchemaFactory', $tcaSchemaFactory);

        $editDocumentControllerMock->_call('addSlugFieldsToColumnsOnly', $queryParams);

        self::assertEquals(['aField'], array_values($editDocumentControllerMock->_get('columnsOnly')['aTable']));
        self::assertEquals(['aTitle'], array_values($editDocumentControllerMock->_get('columnsOnly')['__hiddenGeneratorFields']['aTable']));
        self::assertEquals(['bField'], array_values($editDocumentControllerMock->_get('columnsOnly')['bTable']));
        self::assertEquals(['bTitle'], array_values($editDocumentControllerMock->_get('columnsOnly')['__hiddenGeneratorFields']['bTable']));
    }

    private function getTcaSchemaFactory(): TcaSchemaFactory
    {
        $cacheMock = $this->createMock(PhpFrontend::class);
        $cacheMock->method('has')->with(self::isString())->willReturn(false);
        $tcaSchemaFactory = new TcaSchemaFactory(
            new RelationMapBuilder($this->createMock(FlexFormTools::class)),
            new FieldTypeFactory(),
            '',
            $cacheMock
        );
        return $tcaSchemaFactory;
    }
}
