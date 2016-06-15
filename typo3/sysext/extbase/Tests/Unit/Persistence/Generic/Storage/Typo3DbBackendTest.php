<?php
namespace TYPO3\CMS\Extbase\Tests\Unit\Persistence\Generic\Storage;

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

use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend;
use TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbQueryParser;
use TYPO3\CMS\Extbase\Persistence\QueryInterface;

/**
 * Test case
 */
class Typo3DbBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var DataMapper
     */
    protected static $dataMapper;

    /**
     * Setup DataMapper
     */
    public static function setUpBeforeClass()
    {
        self::$dataMapper = new DataMapper();
    }

    /**
     * @test
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly()
    {
        $mockValueObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject::class)
            ->setMethods(array('_getProperties'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockValueObject->expects($this->once())->method('_getProperties')->will($this->returnValue(array('propertyName' => 'propertyValue')));
        $mockColumnMap = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class)
            ->setMethods(array('isPersistableProperty', 'getColumnName'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockColumnMap->expects($this->any())->method('getColumnName')->will($this->returnValue('column_name'));
        $tableName = 'tx_foo_table';
        $mockDataMap = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class)
            ->setMethods(array('isPersistableProperty', 'getColumnMap', 'getTableName'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMap->expects($this->any())->method('isPersistableProperty')->will($this->returnValue(true));
        $mockDataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($mockColumnMap));
        $mockDataMap->expects($this->any())->method('getTableName')->will($this->returnValue($tableName));
        $mockDataMapper = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class)
            ->setMethods(array('getDataMap', 'getPlainValue'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects($this->once())->method('getDataMap')->will($this->returnValue($mockDataMap));
        $mockDataMapper->expects($this->once())->method('getPlainValue')->will($this->returnValue('plainPropertyValue'));
        $expectedStatement = 'SELECT * FROM tx_foo_table WHERE column_name=?';
        $expectedParameters = array('plainPropertyValue');
        $expectedUid = 52;
        $mockDataBaseHandle = $this->getMockBuilder(\TYPO3\CMS\Core\Database\DatabaseConnection::class)
            ->setMethods(array('sql_query', 'sql_fetch_assoc'))
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataBaseHandle->expects($this->once())->method('sql_query')->will($this->returnValue('resource'));
        $mockDataBaseHandle->expects($this->any())->method('sql_fetch_assoc')->with('resource')->will($this->returnValue(array('uid' => $expectedUid)));
        $mockTypo3DbBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, array('checkSqlErrors', 'replacePlaceholders', 'addVisibilityConstraintStatement'), array(), '', false);
        $mockTypo3DbBackend->expects($this->once())->method('addVisibilityConstraintStatement')->with($this->isInstanceOf(\TYPO3\CMS\Extbase\Persistence\Generic\QuerySettingsInterface::class), $tableName, $this->isType('array'));
        $mockTypo3DbBackend->expects($this->once())->method('replacePlaceholders')->with($expectedStatement, $expectedParameters);
        $mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
        $mockTypo3DbBackend->_set('databaseHandle', $mockDataBaseHandle);
        $result = $mockTypo3DbBackend->_callRef('getUidOfAlreadyPersistedValueObject', $mockValueObject);
        $this->assertSame($expectedUid, $result);
    }

    /**
     * @test
     */
    public function doLanguageAndWorkspaceOverlayChangesUidIfInPreview()
    {
        $comparisonRow = array(
            'uid' => '42',
            'pid' => '42',
            '_ORIG_pid' => '-1',
            '_ORIG_uid' => '43'
        );
        $row = array(
            'uid' => '42',
            'pid' => '42'
        );
        $workspaceVersion = array(
            'uid' => '43',
            'pid' => '-1'
        );
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $mockQuerySettings = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class)
            ->setMethods(array('dummy'))
            ->disableOriginalConstructor()
            ->getMock();

        $workspaceUid = 2;
        $sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
        /** @var $pageRepositoryMock \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject */
        $pageRepositoryMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\Page\PageRepository::class)
            ->setMethods(array('movePlhOL', 'getWorkspaceVersionOfRecord'))
            ->getMock();
        $pageRepositoryMock->versioningPreview = true;
        $pageRepositoryMock->expects($this->once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->will($this->returnValue($workspaceVersion));
        $mockTypo3DbBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, array('dummy'), array(), '', false);
        $mockTypo3DbBackend->_set('pageRepository', $pageRepositoryMock);
        $this->assertSame(array($comparisonRow), $mockTypo3DbBackend->_call('doLanguageAndWorkspaceOverlay', $sourceMock, array($row), $mockQuerySettings, $workspaceUid));
    }

    /**
     * @return array
     */
    public function resolveParameterPlaceholdersReplacesValuesDataProvider()
    {
        return array(
            'string' => array('bar', '123', '123'),
            'array' => array('bar', array(1,2,3), '1,2,3'),
        );
    }

    /**
     * @param $parameter
     * @param $value
     * @param $expected
     * @test
     * @dataProvider resolveParameterPlaceholdersReplacesValuesDataProvider
     */
    public function resolveParameterPlaceholdersReplacesValues($parameter, $value, $expected)
    {
        $mock = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, array('quoteTextValueCallback'));
        $mock->expects($this->any())->method('quoteTextValueCallback')->will($this->returnArgument(0));
        $mock->_set('dataMapper', self::$dataMapper);
        $stmtParts = array('tables' => array('foo'), 'where' => $parameter);
        $parameters = array($parameter => $value);
        $result = $mock->_call('resolveParameterPlaceholders', $stmtParts, $parameters);
        $this->assertSame($expected, $result['where']);
    }

    /**
     * @test
     * @return void
     */
    public function getObjectCountByQueryThrowsExceptionIfOffsetWithoutLimitIsUsed()
    {
        $querySettingsProphecy = $this->prophesize(QuerySettingsInterface::class);
        $queryInterfaceProphecy = $this->prophesize(QueryInterface::class);
        $queryParserProphecy = $this->prophesize(Typo3DbQueryParser::class);
        $queryParserProphecy->preparseQuery($queryInterfaceProphecy->reveal())->willReturn([123, []]);
        $queryParserProphecy->parseQuery($queryInterfaceProphecy->reveal())->willReturn(
            ['tables' => ['tt_content']]
        );
        $queryParserProphecy->addDynamicQueryParts(\Prophecy\Argument::cetera())->willReturn();
        $queryInterfaceProphecy->getQuerySettings()->willReturn($querySettingsProphecy->reveal());
        $queryInterfaceProphecy->getConstraint()->willReturn();
        $queryInterfaceProphecy->getLimit()->willReturn();
        $queryInterfaceProphecy->getOffset()->willReturn(10);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1465223252);

        $typo3DbQueryParser = new Typo3DbBackend();
        $typo3DbQueryParser->injectQueryParser($queryParserProphecy->reveal());
        $typo3DbQueryParser->getObjectCountByQuery($queryInterfaceProphecy->reveal());
    }
}
