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

namespace TYPO3\CMS\Core\Tests\Functional\Configuration\FlexForm;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormToolsTest extends FunctionalTestCase
{
    #[Test]
    public function getDataStructureIdentifierWithNoListenersReturnsDefault(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...',
                ],
            ],
        ];
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithNoOpListenerReturnsDefault(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...',
                ],
            ],
        ];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'noop',
            static function (BeforeFlexFormDataStructureIdentifierInitializedEvent $event) {
                // noop
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureIdentifierInitializedEvent::class, 'noop');
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithListenerReturnsThatListenersValue(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'identifier-one',
            static function (BeforeFlexFormDataStructureIdentifierInitializedEvent $event) {
                $event->setIdentifier([
                    'type' => 'myExtension',
                    'further' => 'data',
                ]);
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureIdentifierInitializedEvent::class, 'identifier-one');
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []);
        $expected = '{"type":"myExtension","further":"data"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithModifyListenerCallsListener(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modifier-one',
            static function (AfterFlexFormDataStructureIdentifierInitializedEvent $event) {
                $id = $event->getIdentifier();
                $id['beep'] = 'boop';
                $event->setIdentifier($id);
            }
        );
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...',
                ],
            ],
        ];
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(AfterFlexFormDataStructureIdentifierInitializedEvent::class, 'modifier-one');
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default","beep":"boop"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfDsIsNotAnArrayAndNoDsPointerField(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => 'someStringOnly',
                // no ds_pointerField,
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1463826960);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
    }

    #[Test]
    public function getDataStructureIdentifierReturnsDefaultIfDsIsSetButNoDsPointerField(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...',
                ],
            ],
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionsIfNoDsPointerFieldIsSetAndDefaultDoesNotExist(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
            ],
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1463652560);
        self::assertSame('default', $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldStringHasMoreThanTwoFields(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
                'ds_pointerField' => 'first,second,third',
            ],
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1463577497);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldWithStringSingleFieldDoesNotExist(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
                'ds_pointerField' => 'notExist',
            ],
        ];
        $row = [
            'foo' => '',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1463578899);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldSWithTwoFieldsFirstDoesNotExist(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
                'ds_pointerField' => 'notExist,second',
            ],
        ];
        $row = [
            'second' => '',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1463578899);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldSWithTwoFieldsSecondDoesNotExist(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
                'ds_pointerField' => 'first,notExist',
            ],
        ];
        $row = [
            'first' => '',
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1463578900);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierReturnsPointerFieldValueIfDataStructureExists(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'thePointerValue' => 'FILE:...',
                ],
                'ds_pointerField' => 'aField',
            ],
        ];
        $row = [
            'aField' => 'thePointerValue',
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"thePointerValue"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsDefaultIfPointerFieldValueDoesNotExist(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => 'theDataStructure',
                ],
                'ds_pointerField' => 'aField',
            ],
        ];
        $row = [
            'aField' => 'thePointerValue',
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldValueDoesNotExistAndDefaultToo(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'aDifferentDataStructure' => 'aDataStructure',
                ],
                'ds_pointerField' => 'aField',
            ],
        ];
        $row = [
            'uid' => 23,
            'aField' => 'aNotDefinedDataStructure',
        ];
        $this->expectException(InvalidSinglePointerFieldException::class);
        $this->expectExceptionCode(1463653197);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * Data provider for getDataStructureIdentifierReturnsValidNameForTwoFieldCombinations
     */
    public static function getDataStructureIdentifierReturnsValidNameForTwoFieldCombinationsDataProvider(): array
    {
        return [
            'direct match of two fields' => [
                [
                    // $row
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    // registered data structure names
                    'firstValue,secondValue' => '',
                ],
                // expected name
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,secondValue"}',
            ],
            'match on first field, * for second' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue,*' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,*"}',
            ],
            'match on second field, * for first' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    '*,secondValue' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"*,secondValue"}',
            ],
            'match on first field only' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue"}',
            ],
            'fallback to default' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'default' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}',
            ],
            'chain falls through with no match on second value to *' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'noMatch',
                ],
                [
                    'firstValue,secondValue' => '',
                    'firstValue,*' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,*"}',
            ],
            'chain falls through with no match on first value to *' => [
                [
                    'firstField' => 'noMatch',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue,secondValue' => '',
                    '*,secondValue' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"*,secondValue"}',
            ],
            'chain falls through with no match on any field to default' => [
                [
                    'firstField' => 'noMatch',
                    'secondField' => 'noMatchToo',
                ],
                [
                    'firstValue,secondValue' => '',
                    'secondValue,*' => '',
                    'default' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}',
            ],
        ];
    }

    #[DataProvider('getDataStructureIdentifierReturnsValidNameForTwoFieldCombinationsDataProvider')]
    #[Test]
    public function getDataStructureIdentifierReturnsValidNameForTwoFieldCombinations(array $row, array $ds, string $expected): void
    {
        $fieldTca = [
            'config' => [
                'ds' => $ds,
                'ds_pointerField' => 'firstField,secondField',
            ],
        ];
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionForTwoFieldsWithNoMatchAndNoDefault(): void
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'firstValue,secondValue' => '',
                ],
                'ds_pointerField' => 'firstField,secondField',
            ],
        ];
        $row = [
            'uid' => 23,
            'firstField' => 'noMatch',
            'secondField' => 'noMatchToo',
        ];
        $this->expectException(InvalidCombinedPointerFieldException::class);
        $this->expectExceptionCode(1463678524);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionWithEmptyString(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478100828);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier('');
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478345642);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier('egon');
    }

    #[Test]
    public function parseDataStructureByIdentifierRejectsInvalidInput(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478104554);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier('{"some":"input"}');
    }

    #[Test]
    public function parseDataStructureByIdentifierParsesDataStructureReturnedByEvent(): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'string',
            static function (BeforeFlexFormDataStructureParsedEvent $event) {
                if ($event->getIdentifier()['type'] === 'myExtension') {
                    $event->setDataStructure('
                        <T3DataStructure>
                            <sheets></sheets>
                        </T3DataStructure>
                    ');
                }
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureParsedEvent::class, 'string');
        $identifier = '{"type":"myExtension"}';
        $expected = [
            'sheets' => '',
        ];
        self::assertSame($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidSyntax(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478104554);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier('{"type":"bernd"}');
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForIncompleteTcaSyntax(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113471);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidTcaSyntaxPointer(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478105491);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesTcaSyntaxPointer(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => '',
        ];
        self::assertSame($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionIfDataStructureFileDoesNotExist(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default']
            = 'FILE:EXT:core/Does/Not/Exist.xml';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478105826);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierFetchesFromFile(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default']
            = ' FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureWithSheet.xml ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidXmlStructure(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <bar>
                </sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478106090);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionIfStructureHasBothSheetAndRoot(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT></ROOT>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1440676540);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierCreatesDefaultSheet(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <aFlexField>
                            <label>aFlexFieldLabel</label>
                            <config>
                                <type>input</type>
                            </config>
                        </aFlexField>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheets(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <aSheet>
                        EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
                    </aSheet>
                </sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'aSheet' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheetsWithFilePrefix(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <aSheet>
                        FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
                    </aSheet>
                </sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'aSheet' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    'type' => 'input',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierModifyEventManipulatesDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'mock',
            static function (AfterFlexFormDataStructureParsedEvent $event) {
                $event->setDataStructure([
                    'sheets' => [
                        'foo' => 'bar',
                    ],
                ]);
            }
        );
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(AfterFlexFormDataStructureParsedEvent::class, 'mock');
        $expected = [
            'sheets' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierMigratesSheetLevelFields(): void
    {
        // Register special error handler to suppress E_USER_DEPRECATED triggered by subject.
        // This is a feature of the subject, but usually lets the test fail.
        set_error_handler(fn() => false, E_USER_DEPRECATED);
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <sDEF>
                        <ROOT>
                            <sheetTitle>aTitle</sheetTitle>
                            <type>array</type>
                            <el>
                                <aFlexField>
                                    <label>aFlexFieldLabel</label>
                                    <config>
                                        <type>input</type>
                                        <eval>email</eval>
                                    </config>
                                </aFlexField>
                            </el>
                        </ROOT>
                    </sDEF>
                </sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'aFlexField' => [
                                'label' => 'aFlexFieldLabel',
                                'config' => [
                                    // type=input with eval=email is now type=email
                                    'type' => 'email',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
        restore_error_handler();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierMigratesContainerFields(): void
    {
        // Register special error handler to suppress E_USER_DEPRECATED triggered by subject.
        // This is a feature of the subject, but usually lets the test fail.
        set_error_handler(fn() => false, E_USER_DEPRECATED);
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <sDEF>
                        <ROOT>
                            <type>array</type>
                            <el>
                                <section_1>
                                    <title>section_1</title>
                                    <type>array</type>
                                    <section>1</section>
                                    <el>
                                        <container_1>
                                            <type>array</type>
                                            <title>container_1 label</title>
                                            <el>
                                                <aFlexField>
                                                    <label>aFlexFieldLabel</label>
                                                    <config>
                                                        <type>input</type>
                                                        <eval>email</eval>
                                                    </config>
                                                </aFlexField>
                                            </el>
                                        </container_1>
                                    </el>
                                </section_1>
                            </el>
                        </ROOT>
                    </sDEF>
                </sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'section_1' => [
                                'title' => 'section_1',
                                'type' => 'array',
                                'section' => '1',
                                'el' => [
                                    'container_1' => [
                                        'type' => 'array',
                                        'title' => 'container_1 label',
                                        'el' => [
                                            'aFlexField' => [
                                                'label' => 'aFlexFieldLabel',
                                                'config' => [
                                                    // type=input with eval=email is now type=email
                                                    'type' => 'email',
                                                ],
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
        restore_error_handler();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierPreparesCategoryField(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <category>
                            <label>Single category</label>
                            <config>
                                <type>category</type>
                                <relationship>oneToOne</relationship>
                            </config>
                        </category>
                        <categories>
                            <config>
                                <type>category</type>
                            </config>
                        </categories>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => [
                'sDEF' => [
                    'ROOT' => [
                        'type' => 'array',
                        'el' => [
                            'category' => [
                                'label' => 'Single category',
                                'config' => [
                                    'type' => 'category',
                                    'relationship' => 'oneToOne',
                                    'foreign_table' => 'sys_category',
                                    'foreign_table_where' =>  ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                                    'maxitems' => 1,
                                    'size' => 20,
                                    'default' => 0,
                                ],
                            ],
                            'categories' => [
                                'label' => 'LLL:EXT:core/Resources/Private/Language/locallang_tca.xlf:sys_category.categories',
                                'config' => [
                                    'type' => 'category',
                                    'relationship' => 'oneToMany',
                                    'foreign_table' => 'sys_category',
                                    'foreign_table_where' => ' AND {#sys_category}.{#sys_language_uid} IN (-1, 0)',
                                    'maxitems' => 99999,
                                    'size' => 20,
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidCategoryRelationship(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <categories>
                            <config>
                                <type>category</type>
                                <relationship>manyToMany</relationship>
                            </config>
                        </categories>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627640208);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidMaxitemsForOneToOne(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <categories>
                            <config>
                                <type>category</type>
                                <relationship>oneToOne</relationship>
                                <maxitems>12</maxitems>
                            </config>
                        </categories>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627640209);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidMaxitems(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <categories>
                            <config>
                                <type>category</type>
                                <maxitems>1</maxitems>
                            </config>
                        </categories>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627640210);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithMissingTca(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697554398);
        $this->get(FlexFormTools::class)->cleanFlexFormXML('fooTable', 'fooField', []);
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithMissingDataField(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697554398);
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = 'invalid';
        $this->get(FlexFormTools::class)->cleanFlexFormXML('fooTable', 'fooField', []);
    }

    #[Test]
    public function cleanFlexFormXMLReturnsEmptyStringWithInvalidDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = 'invalid';
        $result = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => 'invalid']);
        self::assertSame('', $result);
    }

    #[Test]
    public function cleanFlexFormXMLReturnsEmptyStringWithInvalidValue(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <type>array</type>
                    <el>
                        <aFlexField>
                            <label>aFlexFieldLabel</label>
                            <config>
                                <type>input</type>
                            </config>
                        </aFlexField>
                    </el>
                </ROOT>
            </T3DataStructure>
        ';
        $result = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => 'invalid']);
        self::assertSame('', $result);
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithDataStructureWithoutSheets(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697555523);
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT></ROOT>
            </T3DataStructure>
        ';
        $flexXml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                </data>
            </T3FlexForms>
        ';
        $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXml]);
    }

    #[Test]
    public function cleanFlexFormHandlesValuesOfSimpleDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructure.xml');
        $flexXmlInput = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructureValueInput.xml');
        $flexXmlExpected = trim(file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructureValueExpected.xml'));
        $flexXmlOutput = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXmlInput]);
        self::assertSame($flexXmlExpected, $flexXmlOutput);
    }

    #[Test]
    public function cleanFlexFormHandlesValuesOfComplexDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructure.xml');
        $flexXmlInput = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructureValueInput.xml');
        $flexXmlExpected = trim(file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructureValueExpected.xml'));
        $flexXmlOutput = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXmlInput]);
        self::assertSame($flexXmlExpected, $flexXmlOutput);
    }
}
