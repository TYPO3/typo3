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
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidDataStructureException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaSchemaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class FlexFormToolsTest extends FunctionalTestCase
{
    #[Test]
    public function getDataStructureIdentifierWithNoListenersReturnsDefault(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithNoOpListenerReturnsDefault(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithListenerReturnsThatListenersValue(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier([], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        $expected = '{"type":"myExtension","further":"data"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithModifyListenerCallsListener(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        $eventListener = $this->get(ListenerProvider::class);
        $eventListener->addListener(AfterFlexFormDataStructureIdentifierInitializedEvent::class, 'modifier-one');
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default","beep":"boop"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionsIfDsIsEmptyAndNoTypeSpecificDefinitionExists(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198004);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfDsIsAnArrayAndNoTypeSpecificDefinitionExists(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => [],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198004);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionForMissingTcaSchema(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'someStringOnly',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182123);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName'], 'aTableName', 'aFieldName', []));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsDefaultIfDsIsSetAndNoTypeSpecificDefinitionExists(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'someStringOnly',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['aTableName']['columns']['aFieldName'], 'aTableName', 'aFieldName', [], $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsDefaultForInvalidRecordType(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'notExist',
        ];
        $expected = '{"type":"tca","tableName":"bTableName","fieldName":"bFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName')));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionForInvalidRecordTypeAndInvalidDefaultDs(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '',
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'notExist',
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198004);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName'));
    }

    /**
     * @todo - Is this correct?
     */
    #[Test]
    public function getDataStructureIdentifierReturnsDefaultStructureKeyForRecordTypeWithoutFlexField(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'foo',
        ];
        $expected = '{"type":"tca","tableName":"bTableName","fieldName":"bFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName')));
    }

    #[Test]
    public function getDataStructureIdentifierThorwsExceptionOnInvalidDsInRecordTypeOverride(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                    'columnsOverrides' => [
                        'bFieldName' => [
                            'config' => [
                                'ds' => '',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'bar',
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1751796940);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName'));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsIdentifierWithRecordTypeAsDataStructureKey(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'bar',
        ];
        $expected = '{"type":"tca","tableName":"bTableName","fieldName":"bFieldName","dataStructureKey":"bar"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName')));
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionOnRecordTypeWithNoSpecificConfigAndMissingDefaultFallback(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        // missing 'ds'
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'bar',
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1751796940);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName'));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsIdentifierWithRecordTypeWithSpecificConfigAndMissingDefaultFallback(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        // missing 'ds'
                    ],
                ],
            ],
            'types' => [
                'foo' => [
                    'showitem' => 'type',
                ],
                'bar' => [
                    'showitem' => 'type,bFieldName',
                    'columnsOverrides' => [
                        'bFieldName' => [
                            'config' => [
                                'ds' => '<T3DataStructure>...',
                            ],
                        ],
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $row = [
            'type' => 'bar',
        ];
        $expected = '{"type":"tca","tableName":"bTableName","fieldName":"bFieldName","dataStructureKey":"bar"}';
        self::assertSame($expected, $this->get(FlexFormTools::class)->getDataStructureIdentifier($GLOBALS['TCA']['bTableName']['columns']['bFieldName'], 'bTableName', 'bFieldName', $row, $this->get(TcaSchemaFactory::class)->get('bTableName')));
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
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113471);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidDataStructureKey(): void
    {
        $GLOBALS['TCA']['bTableName'] = [
            'ctrl' => [
                'type' => 'type',
            ],
            'columns' => [
                'type' => [
                    'config' => [
                        'type' => 'select',
                        'items' => [
                            ['foo', 'foo'],
                            ['bar', 'bar'],
                        ],
                    ],
                ],
                'bFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1732199538);
        $identifier = '{"type":"tca","tableName":"bTableName","fieldName":"bFieldName","dataStructureKey":"foo"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('bTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForMissingTcaSchema(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets></sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182125);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesTcaSyntaxPointer(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets></sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => '',
        ];
        self::assertSame($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionIfDataStructureFileDoesNotExist(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'FILE:EXT:core/Does/Not/Exist.xml',
                    ],
                ],
            ],
        ];
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478105826);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function parseDataStructureByIdentifierFetchesFromFile(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureWithSheet.xml',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidXmlStructure(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <bar>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478106090);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionIfStructureHasBothSheetAndRoot(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <ROOT></ROOT>
                                <sheets></sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1440676540);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function parseDataStructureByIdentifierCreatesDefaultSheet(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheets(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <aSheet>
                                        EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
                                    </aSheet>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheetsWithFilePrefix(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <aSheet>
                                        FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
                                    </aSheet>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierModifyEventManipulatesDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets></sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertSame($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForDataStructureTypeArrayWithoutSection(): void
    {
        $this->expectException(InvalidDataStructureException::class);
        $this->expectExceptionCode(1440685208);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <sDEF>
                                        <ROOT>
                                            <sheetTitle>aTitle</sheetTitle>
                                            <type>array</type>
                                            <el>
                                                <aSection>
                                                    <type>array</type>
                                                </aSection>
                                            </el>
                                        </ROOT>
                                    </sDEF>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForDataStructureSectionWithoutTypeArray(): void
    {
        $this->expectException(InvalidDataStructureException::class);
        $this->expectExceptionCode(1440685208);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <sDEF>
                                        <ROOT>
                                            <sheetTitle>aTitle</sheetTitle>
                                            <type>array</type>
                                            <el>
                                                <aSection>
                                                    <section>1</section>
                                                </aSection>
                                            </el>
                                        </ROOT>
                                    </sDEF>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForNestedSectionContainers(): void
    {
        $this->expectException(InvalidDataStructureException::class);
        $this->expectExceptionCode(1458745712);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <sDEF>
                                        <ROOT>
                                            <sheetTitle>aTitle</sheetTitle>
                                            <type>array</type>
                                            <el>
                                                <aSection>
                                                    <type>array</type>
                                                    <section>1</section>
                                                    <el>
                                                        <container_1>
                                                            <type>array</type>
                                                            <el>
                                                                <section_nested>
                                                                    <type>array</type>
                                                                    <section>1</section>
                                                                    <el></el>
                                                                </section_nested>
                                                            </el>
                                                        </container_1>
                                                    </el>
                                                </aSection>
                                            </el>
                                        </ROOT>
                                    </sDEF>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    public static function parseDataStructureByIdentifierThrowsExceptionForSectionContainerElementsWithDbRelationsDataProvider(): array
    {
        return [
            'inline' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>inline</type>
                        </config>
                    </aFLexField>
                ',
            ],
            'select MM' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>select</type>
                            <MM></MM>
                        </config>
                    </aFLexField>
                ',
            ],
            'select foreign_field' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>select</type>
                            <foreign_table></foreign_table>
                        </config>
                    </aFLexField>
                ',
            ],
            'group MM' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>group</type>
                            <MM></MM>
                        </config>
                    </aFLexField>
                ',
            ],
            'folder' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>folder</type>
                        </config>
                    </aFLexField>
                ',
            ],
            'file' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>file</type>
                        </config>
                    </aFLexField>
                ',
            ],
            'category' => [
                'element' => '
                    <aFLexField>
                        <label>aFlexFieldLabel</label>
                        <config>
                            <type>category</type>
                        </config>
                    </aFLexField>
                ',
            ],
        ];
    }

    #[DataProvider('parseDataStructureByIdentifierThrowsExceptionForSectionContainerElementsWithDbRelationsDataProvider')]
    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForSectionContainerElementsWithDbRelations(string $element): void
    {
        $this->expectException(InvalidDataStructureException::class);
        $this->expectExceptionCode(1458745468);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <sheets>
                                    <sDEF>
                                        <ROOT>
                                            <sheetTitle>aTitle</sheetTitle>
                                            <type>array</type>
                                            <el>
                                                <aSection>
                                                    <type>array</type>
                                                    <section>1</section>
                                                    <el>
                                                        <container_1>
                                                            <type>array</type>
                                                            <el>
                                                                ' . $element . '
                                                            </el>
                                                        </container_1>
                                                    </el>
                                                </aSection>
                                            </el>
                                        </ROOT>
                                    </sDEF>
                                </sheets>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function parseDataStructureByIdentifierMigratesSheetLevelFields(): void
    {
        // Register special error handler to suppress E_USER_DEPRECATED triggered by subject.
        // This is a feature of the subject, but usually lets the test fail.
        set_error_handler(fn() => false, E_USER_DEPRECATED);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
                                    // type=input with eval=email is now type=email, TcaMigration
                                    'type' => 'email',
                                    // added by TcaPreparation
                                    'softref' => 'email[subst]',
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
        restore_error_handler();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierMigratesContainerFields(): void
    {
        // Register special error handler to suppress E_USER_DEPRECATED triggered by subject.
        // This is a feature of the subject, but usually lets the test fail.
        set_error_handler(fn() => false, E_USER_DEPRECATED);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
                                                    // type=input with eval=email is now type=email, TcaMigration
                                                    'type' => 'email',
                                                    // added by TcaPreparation
                                                    'softref' => 'email[subst]',
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
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));
        restore_error_handler();
        self::assertEquals($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierPreparesCategoryField(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
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
        self::assertEquals($expected, $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName')));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidCategoryRelationship(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1627640208);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidMaxitemsForOneToOne(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1627335016);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidMaxitems(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1627335017);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithMissingTca(): void
    {
        $GLOBALS['TCA']['aTableName'] = [];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697554398);
        $this->get(FlexFormTools::class)->cleanFlexFormXML('fooTable', 'fooField', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithMissingDataField(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697554398);
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds'] = 'invalid';
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $this->get(FlexFormTools::class)->cleanFlexFormXML('fooTable', 'fooField', [], $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function cleanFlexFormXMLReturnsEmptyStringWithInvalidDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'invalid',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $result = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => 'invalid'], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        self::assertSame('', $result);
    }

    #[Test]
    public function cleanFlexFormXMLReturnsEmptyStringWithInvalidValue(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
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
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $result = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => 'invalid'], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        self::assertSame('', $result);
    }

    #[Test]
    public function cleanFlexFormXMLThrowsWithDataStructureWithoutSheets(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697555523);
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <ROOT></ROOT>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $flexXml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                </data>
            </T3FlexForms>
        ';
        $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXml], $this->get(TcaSchemaFactory::class)->get('aTableName'));
    }

    #[Test]
    public function cleanFlexFormHandlesValuesOfSimpleDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructure.xml'),
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $flexXmlInput = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructureValueInput.xml');
        $flexXmlExpected = trim(file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfSimpleDataStructureValueExpected.xml'));
        $flexXmlOutput = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXmlInput], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        self::assertSame($flexXmlExpected, $flexXmlOutput);
    }

    #[Test]
    public function cleanFlexFormHandlesValuesOfComplexDataStructure(): void
    {
        $GLOBALS['TCA']['aTableName'] = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructure.xml'),
                    ],
                ],
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $flexXmlInput = file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructureValueInput.xml');
        $flexXmlExpected = trim(file_get_contents(__DIR__ . '/Fixtures/cleanFlexFormHandlesValuesOfComplexDataStructureValueExpected.xml'));
        $flexXmlOutput = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => $flexXmlInput], $this->get(TcaSchemaFactory::class)->get('aTableName'));
        self::assertSame($flexXmlExpected, $flexXmlOutput);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaReturnsDefault(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>...',
                    ],
                ],
            ],
        ];

        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', [], $rawTca);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaThrowsExceptionForEmptySchema(): void
    {
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198005);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier([], 'aTableName', 'aFieldName', [], []);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaThrowsExceptionForMissingDs(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        // missing 'ds'
                    ],
                ],
            ],
        ];

        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198005);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', [], $rawTca);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaThrowsExceptionForEmptyDs(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '',
                    ],
                ],
            ],
        ];

        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1732198005);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', [], $rawTca);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaSupportsRecordTypes(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'record_type' => [
                    'config' => [
                        'type' => 'select',
                    ],
                ],
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>default...',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'record_type,aFieldName',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => '<T3DataStructure>special...',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $row = ['record_type' => 'special_type'];
        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row, $rawTca);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"special_type"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaFallsBackToDefaultForRecordTypeWithoutFieldInShowitem(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'record_type' => [
                    'config' => [
                        'type' => 'select',
                    ],
                ],
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>default...',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'record_type',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => '<T3DataStructure>special...',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $row = ['record_type' => 'special_type'];
        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row, $rawTca);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaThrowsExceptionForInvalidRecordTypeConfig(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'record_type' => [
                    'config' => [
                        'type' => 'select',
                    ],
                ],
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>default...',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'record_type,aFieldName',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'text', // wrong type
                                'ds' => '<T3DataStructure>special...',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $row = ['record_type' => 'special_type'];
        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1751796941);
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row, $rawTca);
    }

    #[Test]
    public function getDataStructureIdentifierWithRawTcaFallsBackToDefaultForUnknownRecordType(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'record_type' => [
                    'config' => [
                        'type' => 'select',
                    ],
                ],
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>default...',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'record_type,aFieldName',
                ],
            ],
        ];

        $row = ['record_type' => 'unknown_type'];
        $fieldTca = ['config' => $rawTca['columns']['aFieldName']['config']];
        $result = $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row, $rawTca);
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithRawTcaResolvesDefault(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure><sheets></sheets></T3DataStructure>',
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => '',
        ];
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithRawTcaResolvesRecordType(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure><sheets>default</sheets></T3DataStructure>',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'aFieldName',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => '<T3DataStructure><sheets>special</sheets></T3DataStructure>',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"special_type"}';
        $expected = [
            'sheets' => 'special',
        ];
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
        self::assertSame($expected, $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithRawTcaDoesNotFallBackToDefaultForRecordTypeWithoutFieldInShowitem(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure><sheets>default</sheets></T3DataStructure>',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'record_type',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'flex',
                                'ds' => '<T3DataStructure><sheets>special</sheets></T3DataStructure>',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1732199538);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"special_type"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithRawTcaThrowsExceptionForMissingSchema(): void
    {
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182125);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithRawTcaThrowsExceptionForEmptyDataStructure(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '', // empty
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1732199538);
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);
    }

    #[Test]
    public function cleanFlexFormXMLWithRawTcaWorksCorrectly(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure>
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
                        </T3DataStructure>',
                    ],
                ],
            ],
        ];

        $flexXml = '<?xml version="1.0" encoding="utf-8" standalone="yes" ?>
            <T3FlexForms>
                <data>
                    <sheet index="sDEF">
                        <language index="lDEF">
                            <field index="aFlexField">
                                <value index="vDEF">test_value</value>
                            </field>
                            <field index="nonExistentField">
                                <value index="vDEF">should_be_removed</value>
                            </field>
                        </language>
                    </sheet>
                </data>
            </T3FlexForms>';

        $row = ['aFieldName' => $flexXml];
        $result = $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', $row, $rawTca);

        // Should only contain the valid field
        self::assertStringContainsString('aFlexField', $result);
        self::assertStringContainsString('test_value', $result);
        self::assertStringNotContainsString('nonExistentField', $result);
        self::assertStringNotContainsString('should_be_removed', $result);
    }

    #[Test]
    public function cleanFlexFormXMLWithRawTcaThrowsExceptionForMissingField(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                // missing aFieldName
            ],
        ];

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1697554398);
        $this->get(FlexFormTools::class)->cleanFlexFormXML('aTableName', 'aFieldName', ['aFieldName' => 'test'], $rawTca);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionWithoutSchema(): void
    {
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182123);

        $this->get(FlexFormTools::class)->getDataStructureIdentifier(
            ['config' => ['type' => 'flex']],
            'aTableName',
            'aFieldName',
            []
            // no schema parameter
        );
    }

    #[Test]
    public function parseDataStructureByIdentifierWorksWithFileReference(): void
    {
        $rawTca = [
            'ctrl' => [],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'FILE:EXT:core/Tests/Functional/Configuration/FlexForm/Fixtures/DataStructureWithSheet.xml',
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);

        // Should contain the expected structure from the file
        self::assertArrayHasKey('sheets', $result);
        self::assertArrayHasKey('sDEF', $result['sheets']);
    }

    #[Test]
    public function getRecordTypeSpecificFieldConfigReturnsCorrectConfig(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'default_ds',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'aFieldName',
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'ds' => 'override_ds',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Use reflection to access the protected method
        $flexFormTools = $this->get(FlexFormTools::class);
        $reflection = new \ReflectionClass($flexFormTools);
        $method = $reflection->getMethod('getRecordTypeSpecificFieldConfig');
        $result = $method->invoke($flexFormTools, $rawTca, 'special_type', 'aFieldName');

        $expected = [
            'config' => [
                'type' => 'flex',
                'ds' => 'override_ds', // overridden value
            ],
        ];

        self::assertSame($expected, $result);
    }

    #[Test]
    public function getRecordTypeSpecificFieldConfigReturnsEmptyForMissingField(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'otherField' => [
                    'config' => [
                        'type' => 'input',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => 'otherField', // missing aFieldName
                    'columnsOverrides' => [
                        'aFieldName' => [
                            'config' => [
                                'type' => 'input',
                                'ds' => 'override_ds',
                            ],
                        ],
                    ],
                ],
            ],
        ];

        // Use reflection to access the protected method
        $flexFormTools = $this->get(FlexFormTools::class);
        $reflection = new \ReflectionClass($flexFormTools);
        $method = $reflection->getMethod('getRecordTypeSpecificFieldConfig');
        $result = $method->invoke($flexFormTools, $rawTca, 'special_type', 'aFieldName');

        self::assertSame([], $result);
    }

    #[Test]
    public function getRecordTypeSpecificFieldConfigHandlesPalettes(): void
    {
        $rawTca = [
            'ctrl' => [
                'type' => 'record_type',
            ],
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => 'default_ds',
                    ],
                ],
            ],
            'types' => [
                'special_type' => [
                    'showitem' => '--palette--;;test_palette',
                ],
            ],
            'palettes' => [
                'test_palette' => [
                    'showitem' => 'aFieldName',
                ],
            ],
        ];

        // Use reflection to access the protected method
        $flexFormTools = $this->get(FlexFormTools::class);
        $reflection = new \ReflectionClass($flexFormTools);
        $method = $reflection->getMethod('getRecordTypeSpecificFieldConfig');
        $result = $method->invoke($flexFormTools, $rawTca, 'special_type', 'aFieldName');

        $expected = [
            'config' => [
                'type' => 'flex',
                'ds' => 'default_ds',
            ],
        ];

        self::assertSame($expected, $result);
    }

    #[Test]
    public function flexArray2XmlConvertsArrayToValidXml(): void
    {
        $input = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'field1' => [
                            'vDEF' => 'value1',
                        ],
                        'field2' => [
                            'vDEF' => 'value2',
                        ],
                    ],
                ],
            ],
        ];

        $result = $this->get(FlexFormTools::class)->flexArray2Xml($input);

        self::assertStringStartsWith('<?xml version="1.0" encoding="utf-8" standalone="yes" ?>', $result);
        self::assertStringContainsString('<T3FlexForms>', $result);
        self::assertStringContainsString('<sheet index="sDEF">', $result);
        self::assertStringContainsString('<field index="field1">', $result);
        self::assertStringContainsString('<value index="vDEF">value1</value>', $result);
        self::assertStringContainsString('<field index="field2">', $result);
        self::assertStringContainsString('<value index="vDEF">value2</value>', $result);
    }

    #[Test]
    public function flexArray2XmlHandlesEmptyArray(): void
    {
        $input = [];
        $result = $this->get(FlexFormTools::class)->flexArray2Xml($input);

        self::assertStringStartsWith('<?xml version="1.0" encoding="utf-8" standalone="yes" ?>', $result);
        self::assertStringContainsString('</T3FlexForms>', $result);
    }

    #[Test]
    public function flexArray2XmlHandlesSectionElements(): void
    {
        $input = [
            'data' => [
                'sDEF' => [
                    'lDEF' => [
                        'section1' => [
                            'el' => [
                                '1' => [
                                    'container1' => [
                                        'el' => [
                                            'field1' => [
                                                'vDEF' => 'section_value',
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

        $result = $this->get(FlexFormTools::class)->flexArray2Xml($input);

        self::assertStringContainsString('<section index="1">', $result);
        self::assertStringContainsString('<itemType index="container1">', $result);
        self::assertStringContainsString('<value index="vDEF">section_value</value>', $result);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithMixedSchemaTypes(): void
    {
        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '
                            <T3DataStructure>
                                <ROOT>
                                    <sheetTitle>Test Sheet</sheetTitle>
                                    <type>array</type>
                                    <el>
                                        <field1>
                                            <config>
                                                <type>text</type>
                                            </config>
                                        </field1>
                                    </el>
                                </ROOT>
                            </T3DataStructure>
                        ',
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';

        $resultRaw = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);

        $GLOBALS['TCA']['aTableName'] = $rawTca;
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);
        $resultSchema = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $this->get(TcaSchemaFactory::class)->get('aTableName'));

        self::assertEquals($resultRaw, $resultSchema);
        self::assertArrayHasKey('sheets', $resultRaw);
        self::assertArrayHasKey('sDEF', $resultRaw['sheets']);
    }

    #[Test]
    public function getDataStructureIdentifierWithNullSchemaThrowsException(): void
    {
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182123);

        $fieldTca = ['config' => ['type' => 'flex', 'ds' => 'test']];
        $this->get(FlexFormTools::class)->getDataStructureIdentifier($fieldTca, 'test_table', 'test_field', [], null);
    }

    #[Test]
    public function parseDataStructureByIdentifierWithNullSchemaThrowsException(): void
    {
        $this->expectException(InvalidTcaSchemaException::class);
        $this->expectExceptionCode(1753182125);

        $identifier = '{"type":"tca","tableName":"test_table","fieldName":"test_field","dataStructureKey":"default"}';
        $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, null);
    }

    #[Test]
    public function parseDataStructureByIdentifierHandlesDataStructureAlreadyAsArray(): void
    {
        $dataStructureArray = [
            'ROOT' => [
                'sheetTitle' => 'Test',
                'type' => 'array',
                'el' => [
                    'field1' => [
                        'config' => [
                            'type' => 'text',
                        ],
                    ],
                ],
            ],
        ];

        /** @var Container $container */
        $container = $this->getContainer();
        $container->set(
            'BeforeFlexFormDataStructureParsedEvent',
            static function (BeforeFlexFormDataStructureParsedEvent $event) use ($dataStructureArray) {
                $event->setDataStructure($dataStructureArray);
            }
        );
        $listenerProvider = $this->get(ListenerProvider::class);
        $listenerProvider->addListener(BeforeFlexFormDataStructureParsedEvent::class, 'BeforeFlexFormDataStructureParsedEvent');

        $rawTca = [
            'columns' => [
                'aFieldName' => [
                    'config' => [
                        'type' => 'flex',
                        'ds' => '<T3DataStructure><ROOT><type>array</type></ROOT></T3DataStructure>',
                    ],
                ],
            ],
        ];

        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $result = $this->get(FlexFormTools::class)->parseDataStructureByIdentifier($identifier, $rawTca);

        self::assertArrayHasKey('sheets', $result);
        self::assertArrayHasKey('sDEF', $result['sheets']);
        self::assertEquals($dataStructureArray['ROOT'], $result['sheets']['sDEF']['ROOT']);
    }
}
