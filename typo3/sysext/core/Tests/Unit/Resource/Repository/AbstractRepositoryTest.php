<?php
namespace TYPO3\CMS\Core\Tests\Unit\Resource\Repository;

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
use Prophecy\Argument;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class AbstractRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Core\Resource\AbstractRepository
     */
    protected $subject;

    protected $mockedDb;

    protected function createDatabaseMock()
    {
        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->quoteIdentifier(Argument::cetera())->willReturnArgument(0);

        $queryBuilderProphet = $this->prophesize(QueryBuilder::class);
        $queryBuilderProphet->expr()->willReturn(
            GeneralUtility::makeInstance(ExpressionBuilder::class, $connectionProphet->reveal())
        );

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilderProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $this->mockedDb = $this->createMock(\TYPO3\CMS\Core\Database\DatabaseConnection::class);
        $GLOBALS['TYPO3_DB'] = $this->mockedDb;
    }

    protected function setUp()
    {
        $this->subject = $this->getMockForAbstractClass(\TYPO3\CMS\Core\Resource\AbstractRepository::class, array(), '', false);
    }

    /**
     * @test
     */
    public function findByUidFailsIfUidIsString()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1316779798);
        $this->subject->findByUid('asdf');
    }

    /**
     * @test
     */
    public function findByUidAcceptsNumericUidInString()
    {
        $this->createDatabaseMock();
        $this->mockedDb->expects($this->once())->method('exec_SELECTgetSingleRow')->with($this->anything(), $this->anything(), $this->stringContains('uid=' . 123))->will($this->returnValue(array('uid' => 123)));
        $this->subject->findByUid('123');
    }

    /**
     * test runs on a concrete implementation of AbstractRepository
     * to ease the pain of testing a protected method. Feel free to improve.
     *
     * @test
     */
    public function getWhereClauseForEnabledFieldsIncludesDeletedCheckInBackend()
    {
        $this->createDatabaseMock();

        $GLOBALS['TCA'] = array(
            'sys_file_storage' => array(
                'ctrl' => array(
                    'delete' => 'deleted',
                ),
            ),
        );
        /** @var \TYPO3\CMS\Core\Resource\StorageRepository|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface $storageRepositoryMock */
        $storageRepositoryMock = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Resource\StorageRepository::class,
            array('dummy'),
            array(),
            '',
            false
        );
        $result = $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
        $this->assertContains('sys_file_storage.deleted = 0', $result);
    }

    /**
     * test runs on a concrete implementation of AbstractRepository
     * to ease the pain of testing a protected method. Feel free to improve.
     *
     * @test
     */
    public function getWhereClauseForEnabledFieldsCallsSysPageForDeletedFlagInFrontend()
    {
        $GLOBALS['TSFE'] = new \stdClass();
        $sysPageMock = $this->createMock(\TYPO3\CMS\Frontend\Page\PageRepository::class);
        $GLOBALS['TSFE']->sys_page = $sysPageMock;
        $sysPageMock
            ->expects($this->once())
            ->method('deleteClause')
            ->with('sys_file_storage');
        $storageRepositoryMock = $this->getAccessibleMock(
            \TYPO3\CMS\Core\Resource\StorageRepository::class,
            array('getEnvironmentMode'),
            array(),
            '',
            false
        );
        $storageRepositoryMock->expects($this->any())->method('getEnvironmentMode')->will($this->returnValue('FE'));
        $storageRepositoryMock->_call('getWhereClauseForEnabledFields');
    }
}
