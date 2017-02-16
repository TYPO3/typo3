<?php
namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

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
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class Typo3DatabaseBackendTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * @test
     */
    public function setCacheCalculatesCacheTableName()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertEquals('cf_cache_test', $subject->getCacheTable());
    }

    /**
     * @test
     */
    public function setCacheCalculatesTagsTableName()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->assertEquals('cf_cache_test_tags', $subject->getTagsTable());
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->set('identifier', 'data');
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfDataIsNotAString()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1236518298);

        $subject->set('identifier', ['iAmAnArray']);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->get('identifier');
    }

    /**
     * @test
     */
    public function hasThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->has('identifier');
    }

    /**
     * @test
     */
    public function removeThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->remove('identifier');
    }

    /**
     * @test
     */
    public function collectGarbageThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->collectGarbage();
    }

    /**
     * @test
     */
    public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->findIdentifiersByTag('identifier');
    }

    /**
     * @test
     */
    public function flushThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flush();
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->truncate('cf_cache_test')->shouldBeCalled()->willReturn(0);
        $connectionProphet->truncate('cf_cache_test_tags')->shouldBeCalled()->willReturn(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable(Argument::cetera())->willReturn($connectionProphet->reveal());

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $subject->flush();
    }

    public function flushByTagCallsDeleteOnConnection()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->delete('cf_cache_test')->shouldBeCalled()->willReturn(0);
        $connectionProphet->delete('cf_cache_test_tags')->shouldBeCalled()->willReturn(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable(Argument::cetera())->willReturn($connectionProphet->reveal());

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $subject->flushByTag('Tag');
    }

    public function flushByTagsCallsDeleteOnConnection()
    {
        $frontendProphecy = $this->prophesize(FrontendInterface::class);
        $frontendProphecy->getIdentifier()->willReturn('cache_test');

        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontendProphecy->reveal());

        $connectionProphet = $this->prophesize(Connection::class);
        $connectionProphet->delete('cf_cache_test')->shouldBeCalled()->willReturn(0);
        $connectionProphet->delete('cf_cache_test_tags')->shouldBeCalled()->willReturn(0);

        $connectionPoolProphet = $this->prophesize(ConnectionPool::class);
        $connectionPoolProphet->getConnectionForTable(Argument::cetera())->willReturn($connectionProphet->reveal());

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolProphet->reveal());

        $subject->flushByTag(['Tag1', 'Tag2']);
    }

    /**
     * @test
     */
    public function flushByTagThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flushByTag('Tag');
    }
    /**
     * @test
     */
    public function flushByTagsThrowsExceptionIfFrontendWasNotSet()
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flushByTags([]);
    }
}
