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

use PHPUnit\Framework\Attributes\Test;
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

    #[Test]
    public function setCacheCalculatesCacheTableName(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);
        self::assertEquals('cache_test', $subject->getCacheTable());
    }

    #[Test]
    public function setCacheCalculatesTagsTableName(): void
    {
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);

        self::assertEquals('cache_test_tags', $subject->getTagsTable());
    }

    #[Test]
    public function setThrowsExceptionIfFrontendWasNotSet(): void
    {
        $subject = new Typo3DatabaseBackend('Testing');
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject->set('identifier', 'data');
    }

    #[Test]
    public function setThrowsExceptionIfDataIsNotAString(): void
    {
        $this->expectException(InvalidDataException::class);
        $this->expectExceptionCode(1236518298);
        $frontend = new NullFrontend('test');
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->setCache($frontend);
        $subject->set('identifier', ['iAmAnArray']);
    }

    #[Test]
    public function getThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->get('identifier');
    }

    #[Test]
    public function hasThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->has('identifier');
    }

    #[Test]
    public function removeThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->remove('identifier');
    }

    #[Test]
    public function collectGarbageThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->collectGarbage();
    }

    #[Test]
    public function findIdentifiersByTagThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->findIdentifiersByTag('identifier');
    }

    #[Test]
    public function flushThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->flush();
    }

    #[Test]
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
        $connectionMock->expects($this->exactly(2))->method('truncate')
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
        $connectionMock->expects($this->exactly(2))
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
        $connectionMock->expects($this->exactly(2))
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

    #[Test]
    public function flushByTagThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->flushByTag('Tag');
    }

    #[Test]
    public function flushByTagsThrowsExceptionIfFrontendWasNotSet(): void
    {
        $this->expectException(Exception::class);
        $this->expectExceptionCode(1236518288);
        $subject = new Typo3DatabaseBackend('Testing');
        $subject->flushByTags([]);
    }
}
