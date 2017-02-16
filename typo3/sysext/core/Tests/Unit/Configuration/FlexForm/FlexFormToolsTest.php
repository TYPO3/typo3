<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm;

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

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidCombinedPointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidIdentifierException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowLoopException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidParentRowRootException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidPointerFieldValueException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidSinglePointerFieldException;
use TYPO3\CMS\Core\Configuration\FlexForm\Exception\InvalidTcaException;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionContainerInterface;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPostProcessHookReturnArray;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPostProcessHookReturnEmptyArray;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPostProcessHookReturnString;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPostProcessHookThrowException;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPreProcessHookReturnArray;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPreProcessHookReturnEmptyArray;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPreProcessHookReturnString;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureIdentifierPreProcessHookThrowException;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePostProcessHookReturnArray;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePostProcessHookReturnString;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePostProcessHookThrowException;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePreProcessHookReturnEmptyString;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePreProcessHookReturnObject;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePreProcessHookReturnString;
use TYPO3\CMS\Core\Tests\Unit\Configuration\FlexForm\Fixtures\DataStructureParsePreProcessHookThrowException;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class FlexFormToolsTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function getDataStructureIdentifierCallsRegisteredPreProcessHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPreProcessHookThrowException::class,
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478098527);
        (new FlexFormTools())->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPreProcessHookReturnsNoArray()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPreProcessHookReturnString::class
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478096535);
        (new FlexFormTools())->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierUsesCasualLogicIfPreProcessHookReturnsNoIdentifier()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPreProcessHookReturnEmptyArray::class
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsStringFromPreProcessHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPreProcessHookReturnArray::class
        ];
        $expected = '{"type":"myExtension","further":"data"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsStringFromFirstMatchingPreProcessHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPreProcessHookReturnEmptyArray::class,
            DataStructureIdentifierPreProcessHookReturnArray::class,
            DataStructureIdentifierPreProcessHookThrowException::class
        ];
        $expected = '{"type":"myExtension","further":"data"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier([], 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierCallsRegisteredPostProcessHook()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPostProcessHookThrowException::class,
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478342067);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPostProcessHookReturnsNoArray()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPostProcessHookReturnString::class
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478350835);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPostProcessHookReturnsEmptyArray()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPostProcessHookReturnEmptyArray::class
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478350835);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierPostProcessHookCanEnrichIdentifier()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureIdentifierPostProcessHookReturnArray::class
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default","myExtensionData":"foo"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfDsIsNotAnArrayAndNoDsPointerField()
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

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsDefaultIfDsIsSetButNoDsPointerField()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => '<T3DataStructure>...'
                ],
            ],
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionsIfNoDsPointerFieldIsSetAndDefaultDoesNotExist()
    {
        $fieldTca = [
            'config' => [
                'ds' => [],
            ],
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1463652560);
        $this->assertSame('default', (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', []));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldStringHasMoreThanTwoFields()
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

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldWithStringSingleFieldDoesNotExist()
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

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldSWithTwoFieldsFirstDoesNotExist()
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

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldSWithTwoFieldsSecondDoesNotExist()
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

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsPointerFieldValueIfDataStructureExists()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'thePointerValue' => 'FILE:...'
                ],
                'ds_pointerField' => 'aField'
            ],
        ];
        $row = [
            'aField' => 'thePointerValue',
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"thePointerValue"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsDefaultIfPointerFieldValueDoesNotExist()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'default' => 'theDataStructure'
                ],
                'ds_pointerField' => 'aField'
            ],
        ];
        $row = [
            'aField' => 'thePointerValue',
        ];
        $expected = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfPointerFieldValueDoesNotExistAndDefaultToo()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'aDifferentDataStructure' => 'aDataStructure'
                ],
                'ds_pointerField' => 'aField'
            ],
        ];
        $row = [
            'aField' => 'aNotDefinedDataStructure',
        ];
        $this->expectException(InvalidSinglePointerFieldException::class);
        $this->expectExceptionCode(1463653197);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * Data provider for getDataStructureIdentifierReturnsValidNameForTwoFieldCombinations
     */
    public function getDataStructureIdentifierReturnsValidNameForTwoFieldCombinationsDataProvider()
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
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,secondValue"}'
            ],
            'match on first field, * for second' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue,*' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,*"}'
            ],
            '@deprecated match on second field, * for first' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'secondValue,*' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"secondValue,*"}'
            ],
            'match on second field, * for first' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    '*,secondValue' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"*,secondValue"}'
            ],
            'match on first field only' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue"}'
            ],
            'fallback to default' => [
                [
                    'firstField' => 'firstValue',
                    'secondField' => 'secondValue',
                ],
                [
                    'default' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}'
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
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"firstValue,*"}'
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
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"*,secondValue"}'
            ],
            '@deprecated chain falls through with no match on first value to *' => [
                [
                    'firstField' => 'noMatch',
                    'secondField' => 'secondValue',
                ],
                [
                    'firstValue,secondValue' => '',
                    'secondValue,*' => '',
                ],
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"secondValue,*"}'
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
                '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}'
            ],
        ];
    }

    /**
     * @test
     * @dataProvider getDataStructureIdentifierReturnsValidNameForTwoFieldCombinationsDataProvider
     * @param array $row
     * @param array $ds
     * @param $expected
     */
    public function getDataStructureIdentifierReturnsValidNameForTwoFieldCombinations(array $row, array $ds, string $expected)
    {
        $fieldTca = [
            'config' => [
                'ds' => $ds,
                'ds_pointerField' => 'firstField,secondField'
            ],
        ];
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionForTwoFieldsWithNoMatchAndNoDefault()
    {
        $fieldTca = [
            'config' => [
                'ds' => [
                    'firstValue,secondValue' => '',
                ],
                'ds_pointerField' => 'firstField,secondField'
            ],
        ];
        $row = [
            'firstField' => 'noMatch',
            'secondField' => 'noMatchToo',
        ];
        $this->expectException(InvalidCombinedPointerFieldException::class);
        $this->expectExceptionCode(1463678524);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfParentRowLookupFails()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ]
        ];
        $row = [
            'pid' => 42,
            'tx_templavoila_ds' => null,
        ];

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(42, 1)->willReturn(42);
        $expressionBuilderProphecy->eq('uid', 42)->shouldBeCalled()->willReturn('uid = 42');
        $queryBuilderProphecy->where('uid = 42')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());

        // Error case that is tested here: Do not return a valid parent row from db -> exception should be thrown
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(0);
        $this->expectException(InvalidParentRowException::class);
        $this->expectExceptionCode(1463833794);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfParentRowsFormALoop()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $queryBuilderProphecy->createNamedParameter(1, 1)->willReturn(1);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $expressionBuilderProphecy->eq('uid', 1)->shouldBeCalled()->willReturn('uid = 1');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->where('uid = 1')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow, second returns $thirdRow, which points back to $initialRow -> exception
        $statementProphecy->fetch()->willReturn($secondRow, $thirdRow);

        $this->expectException(InvalidParentRowLoopException::class);
        $this->expectExceptionCode(1464110956);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfNoValidPointerFoundUntilRoot()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $queryBuilderProphecy->createNamedParameter(1, 1)->willReturn(1);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $expressionBuilderProphecy->eq('uid', 1)->shouldBeCalled()->willReturn('uid = 1');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->where('uid = 1')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow, second returns $thirdRow. $thirdRow has pid 0 and still no ds -> exception
        $statementProphecy->fetch()->willReturn($secondRow, $thirdRow);

        $this->expectException(InvalidParentRowRootException::class);
        $this->expectExceptionCode(1464112555);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfNoValidPointerValueFound()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ]
        ];
        $row = [
            'aPointerField' => null,
        ];
        $this->expectException(InvalidPointerFieldValueException::class);
        $this->expectExceptionCode(1464114011);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfResorvedPointerValueIsIntegerButDsFieldNameIsNotConfigured()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ]
        ];
        $row = [
            'aPointerField' => 3,
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1464115639);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierThrowsExceptionIfDsTableFieldIsMisconfigured()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
                'ds_tableField' => 'misconfigured',
            ]
        ];
        $row = [
            'aPointerField' => 3,
        ];
        $this->expectException(InvalidTcaException::class);
        $this->expectExceptionCode(1464116002);
        (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row);
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForPointerField()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
            ]
        ];
        $row = [
            'uid' => 42,
            'aPointerField' => '<T3DataStructure>...',
        ];
        $expected = '{"type":"record","tableName":"aTableName","uid":42,"fieldName":"aPointerField"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookup()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $queryBuilderProphecy->createNamedParameter(1, 1)->willReturn(1);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $expressionBuilderProphecy->eq('uid', 1)->shouldBeCalled()->willReturn('uid = 1');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->where('uid = 1')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow, second returns $thirdRow. $thirdRow resolves ds
        $statementProphecy->fetch()->willReturn($secondRow, $thirdRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":1,"fieldName":"tx_templavoila_ds"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookupAndBreaksLoop()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow. $secendRow resolves DS and does not look further up
        $statementProphecy->fetch()->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":2,"fieldName":"tx_templavoila_ds"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForParentLookupAndPrefersSubField()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
                'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->addSelect('tx_templavoila_next_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow. $secendRow resolves DS and does not look further up
        $statementProphecy->fetch()->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"aTableName","uid":2,"fieldName":"tx_templavoila_next_ds"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForTableAndFieldPointer()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'aPointerField',
                'ds_tableField' => 'foreignTableName:foreignTableField',
            ]
        ];
        $row = [
            'uid' => 3,
            'pid' => 2,
            'aPointerField' => 42,
        ];
        $expected = '{"type":"record","tableName":"foreignTableName","uid":42,"fieldName":"foreignTableField"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $row));
    }

    /**
     * @test
     */
    public function getDataStructureIdentifierReturnsValidIdentifierForTableAndFieldPointerWithParentLookup()
    {
        $fieldTca = [
            'config' => [
                'ds_pointerField' => 'tx_templavoila_ds',
                'ds_pointerField_searchParent' => 'pid',
                'ds_pointerField_searchParent_subField' => 'tx_templavoila_next_ds',
                'ds_tableField' => 'foreignTableName:foreignTableField',
            ]
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

        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        // Two queries are done, so we need two instances
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('uid', 'pid', 'tx_templavoila_ds')->shouldBeCalled();
        $queryBuilderProphecy->addSelect('tx_templavoila_next_ds')->shouldBeCalled();
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(2, 1)->willReturn(2);
        $expressionBuilderProphecy->eq('uid', 2)->shouldBeCalled()->willReturn('uid = 2');
        $queryBuilderProphecy->where('uid = 2')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->rowCount()->shouldBeCalled()->willReturn(1);

        // First db call returns $secondRow. $secendRow resolves DS and does not look further up
        $statementProphecy->fetch()->willReturn($secondRow);

        $expected = '{"type":"record","tableName":"foreignTableName","uid":42,"fieldName":"foreignTableField"}';
        $this->assertSame($expected, (new FlexFormTools())->getDataStructureIdentifier($fieldTca, 'aTableName', 'aFieldName', $initialRow));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionWithEmptyString()
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478100828);
        (new FlexFormTools())->parseDataStructureByIdentifier('');
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierIfIdentifierDoesNotResolveToArray()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478345642);
        (new FlexFormTools())->parseDataStructureByIdentifier('egon');
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierCallsRegisteredHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePreProcessHookThrowException::class,
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478112411);
        (new FlexFormTools())->parseDataStructureByIdentifier('{"some":"input"}');
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionIfHookReturnsNoString()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePreProcessHookReturnObject::class
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478168512);
        (new FlexFormTools())->parseDataStructureByIdentifier('{"some":"input"}');
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierUsesCasualLogicIfHookReturnsNoIdentifier()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePreProcessHookReturnEmptyString::class
        ];
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $expected = [
            'sheets' => '',
        ];
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierParsesDataStructureReturnedByHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePreProcessHookReturnString::class
        ];
        $identifier = '{"type":"myExtension"}';
        $expected = [
            'sheets' => '',
        ];
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierParsesDataStructureFromFirstMatchingHook()
    {
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePreProcessHookReturnEmptyString::class,
            DataStructureParsePreProcessHookReturnString::class,
            DataStructureParsePreProcessHookThrowException::class
        ];
        $identifier = '{"type":"myExtension"}';
        $expected = [
            'sheets' => '',
        ];
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidSyntax()
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478104554);
        (new FlexFormTools())->parseDataStructureByIdentifier('{"type":"bernd"}');
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionForIncompleteTcaSyntax()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113471);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName"}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidTcaSyntaxPointer()
    {
        $this->expectException(InvalidIdentifierException::class);
        $this->expectExceptionCode(1478105491);
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierResolvesTcaSyntaxPointer()
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
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionForIncompleteRecordSyntax()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478113873);
        $identifier = '{"type":"record","tableName":"foreignTableName","uid":42}';
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierResolvesRecordSyntaxPointer()
    {
        // Prophecies and revelations for a lot of the database stack classes
        $queryBuilderProphecy = $this->prophesize(QueryBuilder::class);
        $queryBuilderRevelation = $queryBuilderProphecy->reveal();
        $connectionPoolProphecy = $this->prophesize(ConnectionPool::class);
        $queryRestrictionContainerProphecy = $this->prophesize(QueryRestrictionContainerInterface::class);
        $queryRestrictionContainerRevelation = $queryRestrictionContainerProphecy->reveal();
        $expressionBuilderProphecy = $this->prophesize(ExpressionBuilder::class);
        $statementProphecy = $this->prophesize(Statement::class);

        // Register connection pool revelation in framework, this is the entry point used by system under test
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphecy->reveal());

        // Simulate method call flow on database objects and verify correct query is built
        $connectionPoolProphecy->getQueryBuilderForTable('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryRestrictionContainerProphecy->removeAll()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryRestrictionContainerProphecy->add(Argument::cetera())->shouldBeCalled();
        $queryBuilderProphecy->getRestrictions()->shouldBeCalled()->willReturn($queryRestrictionContainerRevelation);
        $queryBuilderProphecy->select('dataprot')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->from('aTableName')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->expr()->shouldBeCalled()->willReturn($expressionBuilderProphecy->reveal());
        $queryBuilderProphecy->createNamedParameter(42, 1)->willReturn(42);
        $expressionBuilderProphecy->eq('uid', 42)->shouldBeCalled()->willReturn('uid = 42');
        $queryBuilderProphecy->where('uid = 42')->shouldBeCalled()->willReturn($queryBuilderRevelation);
        $queryBuilderProphecy->execute()->shouldBeCalled()->willReturn($statementProphecy->reveal());
        $statementProphecy->fetchColumn(0)->willReturn('
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ');
        $identifier = '{"type":"record","tableName":"aTableName","uid":42,"fieldName":"dataprot"}';
        $expected = [
            'sheets' => '',
        ];
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionIfDataStructureFileDoesNotExist()
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default']
            = 'FILE:EXT:core/Does/Not/Exist.xml';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478105826);
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierFetchesFromFile()
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
                                'TCEforms' => [
                                    'label' => 'aFlexFieldLabel',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                        'TCEforms' => [
                            'sheetTitle' => 'aTitle',
                        ],
                    ],
                ],
            ]
        ];
        $this->assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionForInvalidXmlStructure()
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

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionIfStructureHasBothSheetAndRoot()
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

    /**
     * @test
     */
    public function parseDataStructureByIdentifierCreatesDefaultSheet()
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <ROOT>
                    <TCEforms>
                        <sheetTitle>aTitle</sheetTitle>
                    </TCEforms>
                    <type>array</type>
                    <el>
                        <aFlexField>
                            <TCEforms>
                                <label>aFlexFieldLabel</label>
                                <config>
                                    <type>input</type>
                                </config>
                            </TCEforms>
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
                                'TCEforms' => [
                                    'label' => 'aFlexFieldLabel',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                        'TCEforms' => [
                            'sheetTitle' => 'aTitle',
                        ],
                    ],
                ],
            ]
        ];
        $this->assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheets()
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
                                'TCEforms' => [
                                    'label' => 'aFlexFieldLabel',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                        'TCEforms' => [
                            'sheetTitle' => 'aTitle',
                        ],
                    ],
                ],
            ]
        ];
        $this->assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierResolvesExtReferenceForSingleSheetsWithFilePrefix()
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
                                'TCEforms' => [
                                    'label' => 'aFlexFieldLabel',
                                    'config' => [
                                        'type' => 'input',
                                    ],
                                ],
                            ],
                        ],
                        'TCEforms' => [
                            'sheetTitle' => 'aTitle',
                        ],
                    ],
                ],
            ]
        ];
        $this->assertEquals($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierCallsPostProcessHook()
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePostProcessHookThrowException::class,
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478351691);
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierThrowsExceptionIfPostProcessHookReturnsNoArray()
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePostProcessHookReturnString::class,
        ];
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1478350806);
        (new FlexFormTools())->parseDataStructureByIdentifier($identifier);
    }

    /**
     * @test
     */
    public function parseDataStructureByIdentifierPostProcessHookManipulatesDataStructure()
    {
        $GLOBALS['TCA']['aTableName']['columns']['aFieldName']['config']['ds']['default'] = '
            <T3DataStructure>
                <sheets></sheets>
            </T3DataStructure>
        ';
        $identifier = '{"type":"tca","tableName":"aTableName","fieldName":"aFieldName","dataStructureKey":"default"}';
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS'][FlexFormTools::class]['flexParsing'] = [
            DataStructureParsePostProcessHookReturnArray::class,
        ];
        $expected = [
            'sheets' => [
                'foo' => 'bar'
            ]
        ];
        $this->assertSame($expected, (new FlexFormTools())->parseDataStructureByIdentifier($identifier));
    }

    /**
     * @test
     */
    public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingField()
    {
        $dataStruct = [
            'dummy_field' => [
                'TCEforms' => [
                    'config' => [],
                ],
            ],
        ];
        $pA = [
            'vKeys' => ['ES'],
            'callBackMethod_value' => 'dummy',
        ];
        $editData = '';
        /** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->getMockBuilder(FlexFormTools::class)
            ->setMethods(['executeCallBackMethod'])
            ->getMock();
        $subject->expects($this->never())->method('executeCallBackMethod');
        $subject->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA);
    }

    /**
     * @test
     */
    public function traverseFlexFormXmlDataRecurseDoesNotFailOnNotExistingArrayField()
    {
        $dataStruct = [
            'dummy_field' => [
                'type' => 'array',
                'el' => 'field_not_in_data',
            ],
        ];
        $pA = [
            'vKeys' => ['ES'],
            'callBackMethod_value' => 'dummy',
        ];
        $editData = [
            'field' => [
                'el' => 'dummy',
            ],
        ];
        $editData2 = '';
        /** @var \TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools|\PHPUnit_Framework_MockObject_MockObject $subject */
        $subject = $this->createMock(FlexFormTools::class);
        $this->assertEquals(
            $subject->traverseFlexFormXMLData_recurse($dataStruct, $editData, $pA),
            $subject->traverseFlexFormXMLData_recurse($dataStruct, $editData2, $pA)
        );
    }
}
