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
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Extbase\Object\ObjectManager;
use TYPO3\CMS\Extbase\Service\EnvironmentService;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class Typo3DbBackendTest extends UnitTestCase
{
    /**
     * Due to nested PageRepository / FrontendRestriction Container issues, the Context object is set
     * @var bool
     */
    protected $resetSingletonInstances = true;

    public function setUp()
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
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
        $mockObjectManager = $this->createMock(ObjectManager::class);
        $mockObjectManager->expects($this->any())
            ->method('get')
            ->with(\TYPO3\CMS\Extbase\Persistence\Generic\Mapper\DataMapper::class)
            ->will($this->returnValue($mockDataMapper));

        $mockTypo3DbBackend->_set('objectManager', $mockObjectManager);
        $mockTypo3DbBackend->_set('dataMapper', $mockDataMapper);
        $mockTypo3DbBackend->_set('connectionPool', $connectionPoolProphet->reveal());
        $mockTypo3DbBackend->_set('environmentService', $environmentServiceProphet->reveal());
        $result = $mockTypo3DbBackend->_callRef('getUidOfAlreadyPersistedValueObject', $mockValueObject);
        $this->assertSame($expectedUid, $result);
    }
}
