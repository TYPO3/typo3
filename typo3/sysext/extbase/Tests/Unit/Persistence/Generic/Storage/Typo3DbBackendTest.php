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
        $mockValueObject = $this->getMock(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject::class, ['_getProperties'], [], '', false);
        $mockValueObject->expects($this->once())->method('_getProperties')->will($this->returnValue(['propertyName' => 'propertyValue']));
        $mockColumnMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, ['isPersistableProperty', 'getColumnName'], [], '', false);
        $mockColumnMap->expects($this->any())->method('getColumnName')->will($this->returnValue('column_name'));
        $tableName = 'tx_foo_table';
        $mockDataMap = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class, ['isPersistableProperty', 'getColumnMap', 'getTableName'], [], '', false);
        $mockDataMap->expects($this->any())->method('isPersistableProperty')->will($this->returnValue(true));
        $mockDataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($mockColumnMap));
        $mockDataMap->expects($this->any())->method('getTableName')->will($this->returnValue($tableName));
        $mockDataMapper = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class, ['getDataMap', 'getPlainValue'], [], '', false);
        $mockDataMapper->expects($this->once())->method('getDataMap')->will($this->returnValue($mockDataMap));
        $mockDataMapper->expects($this->once())->method('getPlainValue')->will($this->returnValue('plainPropertyValue'));
        $expectedStatement = 'SELECT * FROM tx_foo_table WHERE column_name=?';
        $expectedParameters = ['plainPropertyValue'];
        $expectedUid = 52;
        $mockDataBaseHandle = $this->getMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class, ['sql_query', 'sql_fetch_assoc'], [], '', false);
        $mockDataBaseHandle->expects($this->once())->method('sql_query')->will($this->returnValue('resource'));
        $mockDataBaseHandle->expects($this->any())->method('sql_fetch_assoc')->with('resource')->will($this->returnValue(['uid' => $expectedUid]));
        $mockTypo3DbBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, ['checkSqlErrors', 'replacePlaceholders', 'addVisibilityConstraintStatement'], [], '', false);
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
        $comparisonRow = [
            'uid' => '42',
            'pid' => '42',
            '_ORIG_pid' => '-1',
            '_ORIG_uid' => '43'
        ];
        $row = [
            'uid' => '42',
            'pid' => '42'
        ];
        $workspaceVersion = [
            'uid' => '43',
            'pid' => '-1'
        ];
        /** @var \TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings|\PHPUnit_Framework_MockObject_MockObject $querySettings */
        $mockQuerySettings = $this->getMock(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class, ['dummy'], [], '', false);

        $workspaceUid = 2;
        $sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
        /** @var $pageRepositoryMock \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject */
        $pageRepositoryMock = $this->getMock(\TYPO3\CMS\Frontend\Page\PageRepository::class, ['movePlhOL', 'getWorkspaceVersionOfRecord']);
        $pageRepositoryMock->versioningPreview = true;
        $pageRepositoryMock->expects($this->once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->will($this->returnValue($workspaceVersion));
        $mockTypo3DbBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, ['dummy'], [], '', false);
        $mockTypo3DbBackend->_set('pageRepository', $pageRepositoryMock);
        $this->assertSame([$comparisonRow], $mockTypo3DbBackend->_call('doLanguageAndWorkspaceOverlay', $sourceMock, [$row], $mockQuerySettings, $workspaceUid));
    }

    /**
     * @return array
     */
    public function resolveParameterPlaceholdersReplacesValuesDataProvider()
    {
        return [
            'string' => ['bar', '123', '123'],
            'array' => ['bar', [1, 2, 3], '1,2,3'],
        ];
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
        $mock = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, ['quoteTextValueCallback']);
        $mock->expects($this->any())->method('quoteTextValueCallback')->will($this->returnArgument(0));
        $mock->_set('dataMapper', self::$dataMapper);
        $stmtParts = ['tables' => ['foo'], 'where' => $parameter];
        $parameters = [$parameter => $value];
        $result = $mock->_call('resolveParameterPlaceholders', $stmtParts, $parameters);
        $this->assertSame($expected, $result['where']);
    }

    /**
     * @test
     * @expectedException \InvalidArgumentException
     * @expectedExceptionCode 1465223252
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

        $typo3DbQueryParser = new Typo3DbBackend();
        $typo3DbQueryParser->injectQueryParser($queryParserProphecy->reveal());
        $typo3DbQueryParser->getObjectCountByQuery($queryInterfaceProphecy->reveal());
    }
}
