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

namespace TYPO3\CMS\Core\Tests\Unit\Authentication;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Authentication\AbstractUserAuthentication;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\ExpressionBuilder;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;
use TYPO3\CMS\Core\Tests\Unit\Database\Mocks\MockPlatform;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Testcase for class \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication
 */
class AbstractUserAuthenticationTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getAuthInfoArrayReturnsEmptyPidListIfNoCheckPidValueIsGiven()
    {
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        /** @var Connection|ObjectProphecy $connection */
        $connection = $this->prophesize(Connection::class);
        $connection->getDatabasePlatform()->willReturn(new MockPlatform());
        $connection->getExpressionBuilder()->willReturn(new ExpressionBuilder($connection->reveal()));

        // TODO: This should rather be a functional test if we need a query builder
        // or we should clean up the code itself to not need to mock internal behavior here
        $queryBuilder = new QueryBuilder(
            $connection->reveal(),
            null,
            $this->prophesize(DoctrineQueryBuilder::class)->reveal()
        );

        /** @var ConnectionPool|ObjectProphecy $connection */
        $connectionPool = $this->prophesize(ConnectionPool::class);
        $connectionPool->getQueryBuilderForTable(Argument::cetera())->willReturn($queryBuilder);

        GeneralUtility::addInstance(ConnectionPool::class, $connectionPool->reveal());

        /** @var $mock \TYPO3\CMS\Core\Authentication\AbstractUserAuthentication */
        $mock = $this->getMockBuilder(AbstractUserAuthentication::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $mock->loginType = 'BE';
        $mock->checkPid = true;
        $mock->checkPid_value = null;
        $mock->user_table = 'be_users';
        $result = $mock->getAuthInfoArray();
        self::assertEquals('', $result['db_user']['checkPidList']);
    }

    /**
     * @test
     */
    public function getHttpHeadersReturnsNormalNoCacheHeadersForNonIe()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows NT 6.2; rv:22.0) Gecko/20130405 Firefox/23.0';
        $_SERVER['HTTPS'] = 'on';
        $subject = $this->getAccessibleMockForAbstractClass(AbstractUserAuthentication::class, [], '', false);
        $subject->_set('loginType', 'BE');
        $result = $subject->_call('getHttpHeaders');
        self::assertEquals($result['Pragma'], 'no-cache');
    }

    /**
     * @test
     */
    public function getHttpHeadersReturnsSpecialNoCacheHeadersForIe()
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0 (Windows; U; MSIE 9.0; WIndows NT 9.0; en-US)';
        $_SERVER['HTTPS'] = 'on';
        $subject = $this->getAccessibleMockForAbstractClass(AbstractUserAuthentication::class, [], '', false);
        $result = $subject->_call('getHttpHeaders');
        self::assertEquals($result['Pragma'], 'private');
    }
}
