<?php
namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

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
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Testcase for class \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
 *
 */
class AbstractUserAuthenticationTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @test
     */
    public function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGiven()
    {
        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));

        // TODO: This should rather be a functional test if we need a query builder
        // or we should clean up the code itself to not need to mock internal behavior here
        $queryBuilder = new QueryBuilder(
            $connection->reveal(),
            null,
            $this->prophesize(\Doctrine\DBAL\Query\QueryBuilder::class)->reveal()
        );

        /** @var ConnectionPool|ObjectProphecy $connection */
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        /** @var $mock \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication */
        $mock = $this->getMockBuilder(\TYPO3\CMS\Core\Authentication\AbstractUserAuthentication::class)
            ->setMethods(array('dummy'))
            ->getMock();
        $mock->checkPid = true;
        $mock->checkPid_value = null;
        $mock->user_table = 'be_users';
        $result = $mock->getAuthInfoArray();
        $this->assertEquals('', $result['db_user']['checkPidList']);
    }
}
