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

use Doctrine\DBAL\Result;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\AfterFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureIdentifierInitializedEvent;
use TYPO3\CMS\Core\Configuration\Event\BeforeFlexFormDataStructureParsedEvent;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * @todo: This test contains various tests that mock DB calls. Those should
 *        be removed and substituted with DB fixture data.
 */
final class FlexFormToolsTest extends FunctionalTestCase
{
    protected bool $resetSingletonInstances = true;

    public function setUp(): void
    {
        parent::setUp();
        // Underlying static GeneralUtility::xml2array() uses caches that have to be mocked here
        $cacheManagerMock = $this->createMock(CacheManager::class);
        $cacheMock = $this->createMock(FrontendInterface::class);
        $cacheManagerMock->method('getCache')->with('runtime')->willReturn($cacheMock);
        $cacheMock->method('get')->with(self::anything())->willReturn(false);
        $cacheMock->method('set')->with(self::anything())->willReturn(false);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerMock);
    }

    #[Test]
    public function getDataStructureIdentifierWithNoListenersReturnsDefault(): void
    {
        $subject = new FlexFormTools();

        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...',
                ],
            ],
        ];

        $result = $subject->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);

        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithNoOpListenerReturnsDefault(): void
    {
        $subject = new FlexFormTools();

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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureIdentifierInitializedEvent::class, 'noop');

        $result = $subject->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);

        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithListenerReturnsThatListenersValue(): void
    {
        $subject = new FlexFormTools();

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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureIdentifierInitializedEvent::class, 'identifier-one');

        $result = $subject->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []);

        $expected = '{"type":"myExtension","further":"data"}';
        self::assertSame($expected, $result);
    }

    #[Test]
    public function getDataStructureIdentifierWithModifyListenerCallsListener(): void
    {
        $subject = new FlexFormTools();

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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(AfterFlexFormDataStructureIdentifierInitializedEvent::class, 'modifier-one');

        $result = $subject->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);

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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
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
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
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
        self::assertSame('default', (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
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
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
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
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
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
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
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
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfParentRowLookupFails(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ],
        ];
        $row = [
            'uid' => 23,
            'pid' => 42,
            'tx_templavoila_ds' => null,
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(42, 1)->willReturn('42');
        $expressionBuilderMock->expects(self::atLeastOnce())->method('eq')->with('uid', 42)->willReturn('uid = 42');
        $queryBuilderMock->expects(self::atLeastOnce())->method('where')->with('uid = 42')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);

        // Error case that is tested here: Do not return a valid parent row from db -> exception should be thrown
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);
        $this->expectException(InvalidParentRowException::class);
        $this->expectExceptionCode(1463833794);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfParentRowsFormALoop(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => null,
        ];
        $thirdRow = [
            'uid' => 1,
            'pid' => 3,
            'tx_templavoila_ds' => null,
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $seriesOne = [
            [2, Connection::PARAM_INT, '2'],
            [1, Connection::PARAM_INT, '1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('createNamedParameter')
            ->willReturnCallback(function (int $value, int $type) use (&$seriesOne): string {
                $arguments = array_shift($seriesOne);
                self::assertSame($arguments[0], $value);
                self::assertSame($arguments[1], $type);
                return $arguments[2];
            });
        $seriesTwo = [
            ['uid', '2', 'uid = 2'],
            ['uid', '1', 'uid = 1'],
        ];
        $expressionBuilderMock->expects(self::exactly(2))->method('eq')
            ->willReturnCallback(function (string $field, string $value) use (&$seriesTwo): string {
                $arguments = array_shift($seriesTwo);
                self::assertSame($arguments[0], $field);
                self::assertSame($arguments[1], $value);
                return $arguments[2];
            });
        $seriesThree = [
            ['uid = 2'],
            ['uid = 1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('where')
            ->willReturnCallback(function (string $predicate) use (&$seriesThree, $queryBuilderMock): QueryBuilder {
                $arguments = array_shift($seriesThree);
                self::assertSame($arguments[0], $predicate);
                return $queryBuilderMock;
            });
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);

        // First db call returns $secondRow, second returns $thirdRow, which points back to $initialRow -> exception
        $statementMock->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow, $thirdRow);

        $this->expectException(InvalidParentRowLoopException::class);
        $this->expectExceptionCode(1464110956);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfNoValidPointerFoundUntilRoot(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => null,
        ];
        $thirdRow = [
            'uid' => 1,
            'pid' => 0,
            'tx_templavoila_ds' => null,
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $seriesOne = [
            [2, Connection::PARAM_INT, '2'],
            [1, Connection::PARAM_INT, '1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('createNamedParameter')
            ->willReturnCallback(function (int $value, int $type) use (&$seriesOne): string {
                $arguments = array_shift($seriesOne);
                self::assertSame($arguments[0], $value);
                self::assertSame($arguments[1], $type);
                return $arguments[2];
            });
        $seriesTwo = [
            ['uid', '2', 'uid = 2'],
            ['uid', '1', 'uid = 1'],
        ];
        $expressionBuilderMock->expects(self::exactly(2))->method('eq')
            ->willReturnCallback(function (string $field, string $value) use (&$seriesTwo): string {
                $arguments = array_shift($seriesTwo);
                self::assertSame($arguments[0], $field);
                self::assertSame($arguments[1], $value);
                return $arguments[2];
            });
        $seriesThree = [
            ['uid = 2'],
            ['uid = 1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('where')
            ->willReturnCallback(function (string $predicate) use (&$seriesThree, $queryBuilderMock): QueryBuilder {
                $arguments = array_shift($seriesThree);
                self::assertSame($arguments[0], $predicate);
                return $queryBuilderMock;
            });
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);

        // First db call returns $secondRow, second returns $thirdRow. $thirdRow has pid 0 and still no ds -> exception
        $statementMock->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow, $thirdRow);

        $this->expectException(InvalidParentRowRootException::class);
        $this->expectExceptionCode(1464112555);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfNoValidPointerValueFound(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ],
        ];
        $row = [
            'aPointerField' => null,
        ];
        $this->expectException(InvalidPointerFieldValueException::class);
        $this->expectExceptionCode(1464114011);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfReservedPointerValueIsIntegerButDsFieldNameIsNotConfigured(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ],
        ];
        $row = [
            'aPointerField' => 3,
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1464115639);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierThrowsExceptionIfDsTableFieldIsMisconfigured(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
                'ds_tableField' => 'misconfigured',
            ],
        ];
        $row = [
            'aPointerField' => 3,
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1464116002);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForPointerField(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ],
        ];
        $row = [
            'uid' => 42,
            'aPointerField' => '<T3DataStructure>...',
        ];
        $expected = '{"type":"record","tableName":"aTableName","uid":42,"fieldName":"aPointerField"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookup(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => 0,
        ];
        $thirdRow = [
            'uid' => 1,
            'pid' => 0,
            'tx_templavoila_ds' => '<T3DataStructure>...',
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $seriesOne = [
            [2, Connection::PARAM_INT, '2'],
            [1, Connection::PARAM_INT, '1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('createNamedParameter')
            ->willReturnCallback(function (int $value, int $type) use (&$seriesOne): string {
                $arguments = array_shift($seriesOne);
                self::assertSame($arguments[0], $value);
                self::assertSame($arguments[1], $type);
                return $arguments[2];
            });
        $seriesTwo = [
            ['uid', '2', 'uid = 2'],
            ['uid', '1', 'uid = 1'],
        ];
        $expressionBuilderMock->expects(self::exactly(2))->method('eq')
            ->willReturnCallback(function (string $field, string $value) use (&$seriesTwo): string {
                $arguments = array_shift($seriesTwo);
                self::assertSame($arguments[0], $field);
                self::assertSame($arguments[1], $value);
                return $arguments[2];
            });
        $seriesThree = [
            ['uid = 2'],
            ['uid = 1'],
        ];
        $queryBuilderMock->expects(self::exactly(2))->method('where')
            ->willReturnCallback(function (string $predicate) use (&$seriesThree, $queryBuilderMock): QueryBuilder {
                $arguments = array_shift($seriesThree);
                self::assertSame($arguments[0], $predicate);
                return $queryBuilderMock;
            });
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);

        // First db call returns $secondRow, second returns $thirdRow. $thirdRow resolves ds
        $statementMock->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow, $thirdRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":1,"fieldName":"tx_templavoila_ds"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookupAndBreaksLoop(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => '<T3DataStructure>...',
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(2, 1)->willReturn('2');
        $expressionBuilderMock->expects(self::atLeastOnce())->method('eq')->with('uid', 2)->willReturn('uid = 2');
        $queryBuilderMock->expects(self::atLeastOnce())->method('where')->with('uid = 2')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);

        // First db call returns $secondRow. $secondRow resolves DS and does not look further up
        $statementMock->expects(self::atLeastOnce())->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":2,"fieldName":"tx_templavoila_ds"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookupAndPrefersSubField(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
                'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
            'tx_templavoila_next_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => '<T3DataStructure>...',
            'tx_templavoila_next_ds' => 'anotherDataStructure',
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('addSelect')->with('tx_templavoila_next_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(2, 1)->willReturn('2');
        $expressionBuilderMock->expects(self::atLeastOnce())->method('eq')->with('uid', 2)->willReturn('uid = 2');
        $queryBuilderMock->expects(self::atLeastOnce())->method('where')->with('uid = 2')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);

        // First db call returns $secondRow. $secondRow resolves DS and does not look further up
        $statementMock->expects(self::atLeastOnce())->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":2,"fieldName":"tx_templavoila_next_ds"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForTableAndFieldPointer(): void
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
                'ds_tableField' => 'foreignTableName:foreignTableField',
            ],
        ];
        $row = [
            'uid' => 3,
            'pid' => 2,
            'aPointerField' => 42,
        ];
        $expected = '{"type":"record","tableName":"foreignTableName","uid":42,"fieldName":"foreignTableField"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    #[Test]
    public function getDataStructureIdentifierReturnsValidIdentifierForTableAndFieldPointerWithParentLookup(): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
                'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
                'ds_tableField' => 'foreignTableName:foreignTableField',
            ],
        ];
        $initialRow = [
            'uid' => 3,
            'pid' => 2,
            'tx_templavoila_ds' => null,
            'tx_templavoila_next_ds' => null,
        ];
        $secondRow = [
            'uid' => 2,
            'pid' => 1,
            'tx_templavoila_ds' => '<T3DataStructure>...',
            'tx_templavoila_next_ds' => '42',
        ];

        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->method('select')->with('uid', 'pid', 'tx_templavoila_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('addSelect')->with('tx_templavoila_next_ds');
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(2, 1)->willReturn('2');
        $expressionBuilderMock->expects(self::atLeastOnce())->method('eq')->with('uid', 2)->willReturn('uid = 2');
        $queryBuilderMock->expects(self::atLeastOnce())->method('where')->with('uid = 2')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('count')->with('uid')->willReturn($queryBuilderMock);

        // First db call returns $secondRow. $secondRow resolves DS and does not look further up
        $statementMock->expects(self::atLeastOnce())->method('fetchOne')->willReturn(1);
        $statementMock->method('fetchAssociative')->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"foreignTableName","uid":42,"fieldName":"foreignTableField"}';
        self::assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionWithEmptyString(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478100828);
        (new FlexFormTools())->parseDataStructureByIdentifier('');
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionOnInvalidJson(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478345642);
        (new FlexFormTools())->parseDataStructureByIdentifier('egon');
    }

    #[Test]
    public function parseDataStructureByIdentifierRejectsInvalidInput(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478104554);
        (new FlexFormTools())->parseDataStructureByIdentifier('{"some":"input"}');
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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(BeforeFlexFormDataStructureParsedEvent::class, 'string');

        $identifier = '{"type":"myExtension"}';
        $expected = [
            'sheets' => '',
        ];
        self::assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidSyntax(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478104554);
        (new FlexFormTools())->parseDataStructureByIdentifier('{"type":"bernd"}');
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForIncompleteTcaSyntax(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113471);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName"}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidTcaSyntaxPointer(): void
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478105491);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
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
        self::assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionForIncompleteRecordSyntax(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113873);
        $identifier = '{"type":"record","tableName":"foreignTableName","uid":42}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesRecordSyntaxPointer(): void
    {
        // Mocks for a lot of the database stack classes
        $queryBuilderMock = $this->createMock(QueryBuilder::class);
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $queryRestrictionContainerMock = $this->createMock(QueryRestrictionContainerInterface::class);
        $expressionBuilderMock = $this->createMock(ExpressionBuilder::class);
        $statementMock = $this->createMock(Result::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolMock->expects(self::atLeastOnce())->method('getQueryBuilderForTable')->with('aTableName')->willReturn($queryBuilderMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('removeAll')->willReturn($queryRestrictionContainerMock);
        $queryRestrictionContainerMock->expects(self::atLeastOnce())->method('add')->with(self::anything());
        $queryBuilderMock->expects(self::atLeastOnce())->method('getRestrictions')->willReturn($queryRestrictionContainerMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('select')->with('dataprot')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('from')->with('aTableName')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('expr')->willReturn($expressionBuilderMock);
        $queryBuilderMock->method('createNamedParameter')->with(42, 1)->willReturn('42');
        $expressionBuilderMock->expects(self::atLeastOnce())->method('eq')->with('uid', 42)->willReturn('uid = 42');
        $queryBuilderMock->expects(self::atLeastOnce())->method('where')->with('uid = 42')->willReturn($queryBuilderMock);
        $queryBuilderMock->expects(self::atLeastOnce())->method('executeQuery')->willReturn($statementMock);
        $statementMock->method('fetchOne')->willReturn('
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ');
        $identifier = '{"type":"record","tableName":"aTableName","uid":42,"fieldName":"dataprot"}';
        $expected = [
            'sheets' => '',
        ];
        self::assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsExceptionIfDataStructureFileDoesNotExist(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default']
            = 'FILE:EXT:core/Does/Not/Exist.xml';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478105826);
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierFetchesFromFile(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default']
            = ' FILE:EXT:core/Tests/Unit/Configuration/FlexForm/Fixtures/DataStructureWithSheet.xml ';
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
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
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
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
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
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
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
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheets(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <aSheet>
                        EXT:core/Tests/Unit/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
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
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    #[Test]
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheetsWithFilePrefix(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets>
                    <aSheet>
                        FILE:EXT:core/Tests/Unit/Configuration/FlexForm/Fixtures/DataStructureOfSingleSheet.xml
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
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
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

        $eventListener = GeneralUtility::makeInstance(ListenerProvider::class);
        $eventListener->addListener(AfterFlexFormDataStructureParsedEvent::class, 'mock');

        $expected = [
            'sheets' => [
                'foo' => 'bar',
            ],
        ];
        self::assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
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
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
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

        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierThrowsEsxceptionOnInvalidMaxitemsForOneToOne(): void
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

        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
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

        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    #[Test]
    public function parseDataStructureByIdentifierPreservesManuallyForeignTableWhereForCategory(): void
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <sheetTitle>aTitle</sheetTitle>
                    <type>array</type>
                    <el>
                        <category>
                            <label>Custom category</label>
                            <config>
                                <type>category</type>
                                <relationship>oneToOne</relationship>
                                <foreign_table_where> AND {#sys_category}.{#title} LIKE "%special%"</foreign_table_where>
                            </config>
                        </category>
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
                                'label' => 'Custom category',
                                'config' => [
                                    'type' => 'category',
                                    'relationship' => 'oneToOne',
                                    'foreign_table' => 'sys_category',
                                    'foreign_table_where' => ' AND {#sys_category}.{#title} LIKE "%special%"',
                                    'maxitems' => 1,
                                    'size' => 20,
                                    'default' => 0,
                                ],
                            ],
                        ],
                        'sheetTitle' => 'aTitle',
                    ],
                ],
            ],
        ];
        self::assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }
}
