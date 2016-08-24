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

use Doctrine\DBAL\Driver\Statement;
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper;
use TYPO3\CMS\Extbase\Service\EnvironmentService;

/**
 * Test case
 */
class Typo3DbBackendTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var DataMapper
     */
    protected static $dataMapper;

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $GLOBALS['TSFE']->gr_list = '';
    }

    /**
     * Setup DataMapper
     */
    public static function setUpBeforeClass()
    {
        self::$dataMapper = new DataMapper();
    }

    /**
     * @return array
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectlyDataProvider(): array
    {
        return [
            'isFrontendEnvironment' => [true],
            'isBackendEnvironment' => [false],
        ];
    }

    /**
     * @test
     * @dataProvider uidOfAlreadyPersistedValueObjectIsDeterminedCorrectlyDataProvider
     */
    public function uidOfAlreadyPersistedValueObjectIsDeterminedCorrectly(bool $isFrontendEnvironment)
    {
        $mockValueObject = $this->getMockBuilder(\TYPO3\CMS\Extbase\DomainObject\AbstractValueObject::class)
            ->setMethods(['_getProperties'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockValueObject->expects($this->once())->method('_getProperties')
            ->will($this->returnValue(['propertyName' => 'propertyValue']));
        $mockColumnMap = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class)
            ->setMethods(['isPersistableProperty', 'getColumnName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockColumnMap->expects($this->any())->method('getColumnName')->will($this->returnValue('column_name'));
        $tableName = 'tx_foo_table';
        $mockDataMap = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMap::class)
            ->setMethods(['isPersistableProperty', 'getColumnMap', 'getTableName'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMap->expects($this->any())->method('isPersistableProperty')->will($this->returnValue(true));
        $mockDataMap->expects($this->any())->method('getColumnMap')->will($this->returnValue($mockColumnMap));
        $mockDataMap->expects($this->any())->method('getTableName')->will($this->returnValue($tableName));
        $mockDataMapper = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class)
            ->setMethods(['getDataMap', 'getPlainValue'])
            ->disableOriginalConstructor()
            ->getMock();
        $mockDataMapper->expects($this->once())->method('getDataMap')
            ->will($this->returnValue($mockDataMap));
        $mockDataMapper->expects($this->once())->method('getPlainValue')
            ->will($this->returnValue('plainPropertyValue'));
        $expectedUid = 52;

        $expressionBuilderProphet = $this->prophesize(ExpressionBuilder::class);
        $expressionBuilderProphet->eq(Argument::cetera())->willReturn('1 = 1');
        $queryResultProphet = $this->prophesize(Statement::class);
        $queryResultProphet->fetchColumn(Argument::cetera())->willReturn($expectedUid);
        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->execute()->willReturn($queryResultProphet->reveal());
        $queryBuilderProphet->expr()->willReturn($expressionBuilderProphet->reveal());
        $queryBuilderProphet->createNamedParameter(Argument::cetera())->willReturnArgument(0);
        $queryBuilderProphet->select('uid')->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->from($tableName)->willReturn($queryBuilderProphet->reveal());
        $queryBuilderProphet->where(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());

        $environmentServiceProphet = $this->prophesize(EnvironmentService::class);
        $environmentServiceProphet->isEnvironmentInFrontendMode()->willReturn($isFrontendEnvironment);

        if ($isFrontendEnvironment) {
            $queryBuilderProphet->setRestrictions(Argument::type(FrontendRestrictionContainer::class))
                ->shouldBeCalled();
        }

        $mockTypo3DbBackend = $this->getAccessibleMock(
            \TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class,
            ['dummy'],
            [],
            '',
            false
        );
        $mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
        $mockTypo3DbBackend->_set('connectionPool', $connectionPoolProphet->reveal());
        $mockTypo3DbBackend->_set('environmentService', $environmentServiceProphet->reveal());
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
        $mockQuerySettings = $this->getMockBuilder(\TYPO3\CMS\Extbase\Persistence\Generic\Typo3QuerySettings::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();

        $workspaceUid = 2;
        $sourceMock = new \TYPO3\CMS\Extbase\Persistence\Generic\Qom\Selector('tx_foo', 'Tx_Foo');
        /** @var $pageRepositoryMock \TYPO3\CMS\Frontend\Page\PageRepository|\PHPUnit_Framework_MockObject_MockObject */
        $pageRepositoryMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\Page\PageRepository::class)
            ->setMethods(['movePlhOL', 'getWorkspaceVersionOfRecord'])
            ->getMock();
        $pageRepositoryMock->versioningPreview = true;
        $pageRepositoryMock->expects($this->once())->method('getWorkspaceVersionOfRecord')->with($workspaceUid, 'tx_foo', '42')->will($this->returnValue($workspaceVersion));
        $mockTypo3DbBackend = $this->getAccessibleMock(\TYPO3\CMS\Extbase\Persistence\Generic\Storage\Typo3DbBackend::class, ['dummy'], [], '', false);
        $mockTypo3DbBackend->_set('pageRepository', $pageRepositoryMock);
        $this->assertSame([$comparisonRow], $mockTypo3DbBackend->_call('doLanguageAndWorkspaceOverlay', $sourceMock, [$row], $mockQuerySettings, $workspaceUid));
    }
}
