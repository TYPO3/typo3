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

namespace TYPO3\CMS\Core\Tests\Unit\Cache\Backend;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\Exception;
use TYPO3\CMS\Core\Cache\Exception\InvalidDataException;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class Typo3DatabaseBackendTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    /**
     * @test
     */
    public function setCacheCalculatesCacheTableName(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        self::assertEquals('cache_test', $subject->getCacheTable());
    }

    /**
     * @test
     */
    public function setCacheCalculatesTagsTableName(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        self::assertEquals('cache_test_tags', $subject->getTagsTable());
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->set('identifier', 'data');
    }

    /**
     * @test
     */
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1236518298);

        $subject->set('identifier', ['iAmAnArray']);
    }

    /**
     * @test
     */
    public function getThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->get('identifier');
    }

    /**
     * @test
     */
    public function hasThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->has('identifier');
    }

    /**
     * @test
     */
    public function removeThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->remove('identifier');
    }

    /**
     * @test
     */
    public function collectGarbageThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->collectGarbage();
    }

    /**
     * @test
     */
    public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->findIdentifiersByTag('identifier');
    }

    /**
     * @test
     */
    public function flushThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flush();
    }

    /**
     * @test
     */
    public function flushRemovesAllCacheEntries(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        $connectionMock = $this->createMock(Connection::class);
        $series = [
            ['cache_test'],
            ['cache_test_tags'],
        ];
        $connectionMock->expects(self::exactly(2))->method('truncate')
            ->willReturnCallback(function (string $table) use (&$series): int {
                $arguments = array_shift($series);
                self::assertSame($arguments[0], $table);
                return 0;
            });
        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $subject->flush();
    }

    public function flushByTagCallsDeleteOnConnection(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::exactly(2))
            ->method('delete')
            ->willReturnMap(
                [
                    ['cache_test', 0],
                    ['cache_test_tags', 0],
                ]
            );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionPoolMock);

        $subject->flushByTag('Tag');
    }

    public function flushByTagsCallsDeleteOnConnection(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        $connectionMock = $this->createMock(Connection::class);
        $connectionMock->expects(self::exactly(2))
            ->method('delete')
            ->willReturnMap(
                [
                    ['cache_test', 0],
                    ['cache_test_tags', 0],
                ]
            );

        $connectionPoolMock = $this->createMock(ConnectionPool::class);
        $connectionPoolMock->method('getConnectionForTable')->with(self::anything())->willReturn($connectionMock);

        // Two instances are required as there are different tables being cleared
        GeneralUtility::addInstance(ConnectionPool::class, $connectionMock);
        GeneralUtility::addInstance(ConnectionPool::class, $connectionMock);

        $subject->flushByTags(['Tag1', 'Tag2']);
    }

    /**
     * @test
     */
    public function flushByTagThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flushByTag('Tag');
    }

    /**
     * @test
     */
    public function flushByTagsThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->flushByTags([]);
    }
}
