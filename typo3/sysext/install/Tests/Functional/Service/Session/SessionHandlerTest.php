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

namespace TYPO3\CMS\Install\Tests\Functional\Service\Session;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Install\Service\Session\FileSessionHandler;
use TYPO3\CMS\Install\Service\Session\RedisSessionHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SessionHandlerTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;
    protected array $coreExtensionsToLoad = ['install'];

    private string $originalSessionSavePath;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSessionSavePath = session_save_path();
    }

    protected function tearDown(): void
    {
        session_write_close();
        session_save_path($this->originalSessionSavePath);
        parent::tearDown();
    }

    public static function sessionHandlerDataProvider(): array
    {
        return [
            'file' => [FileSessionHandler::class],
            'redis' => [RedisSessionHandler::class],
        ];
    }

    private function createHandler(string $handlerClass): FileSessionHandler|RedisSessionHandler
    {
        if ($handlerClass === FileSessionHandler::class) {
            return new FileSessionHandler(15, $this->instancePath . '/sessions');
        }
        $host = getenv('typo3TestingRedisHost');
        if (!extension_loaded('redis') || !$host) {
            self::markTestSkipped('Redis extension and typo3TestingRedisHost are required.');
        }
        return new RedisSessionHandler(15, $host, (int)(getenv('typo3TestingRedisPort') ?: 6379));
    }

    #[Test]
    #[DataProvider('sessionHandlerDataProvider')]
    public function createsDistinctSessionIds(string $handlerClass): void
    {
        $handler = $this->createHandler($handlerClass);
        $id = $handler->create_sid();
        self::assertMatchesRegularExpression('/^[a-zA-Z0-9,-]+$/D', $id);
        self::assertNotSame($id, $handler->create_sid());
    }

    #[Test]
    #[DataProvider('sessionHandlerDataProvider')]
    public function rejectsUnknownSessionWithoutCreatingIt(string $handlerClass): void
    {
        $handler = $this->createHandler($handlerClass);
        $id = bin2hex(random_bytes(16));
        self::assertFalse($handler->validateId($id));
        self::assertFalse($handler->validateId($id));
    }

    #[Test]
    #[DataProvider('sessionHandlerDataProvider')]
    public function acceptsStoredSessionWithEmptyData(string $handlerClass): void
    {
        $handler = $this->createHandler($handlerClass);
        $id = bin2hex(random_bytes(16));
        try {
            self::assertTrue($handler->write($id, ''));
            self::assertTrue($handler->validateId($id));
        } finally {
            $handler->destroy($id);
        }
    }

    #[Test]
    #[DataProvider('sessionHandlerDataProvider')]
    public function rejectsDestroyedSession(string $handlerClass): void
    {
        $handler = $this->createHandler($handlerClass);
        $id = bin2hex(random_bytes(16));
        try {
            $handler->write($id, 'data');
            self::assertTrue($handler->validateId($id));
        } finally {
            $handler->destroy($id);
        }
        self::assertFalse($handler->validateId($id));
    }

    #[Test]
    #[DataProvider('sessionHandlerDataProvider')]
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function strictModeRejectsUnknownIdAndPreservesExistingSession(string $handlerClass): void
    {
        $handler = $this->createHandler($handlerClass);
        self::assertTrue(session_set_save_handler($handler));
        $unknownId = bin2hex(random_bytes(16));
        session_id($unknownId);
        $options = ['use_strict_mode' => true, 'use_cookies' => false, 'cache_limiter' => ''];
        try {
            self::assertTrue(session_start($options));
            $id = session_id();
            self::assertNotSame($unknownId, $id);
            self::assertFalse($handler->validateId(''));
            $_SESSION['value'] = 'preserved';
            session_write_close();

            session_id($id);
            self::assertTrue(session_start($options));
            self::assertSame($id, session_id());
            self::assertSame('preserved', $_SESSION['value']);

            self::assertTrue(session_regenerate_id(true));
            self::assertNotSame($id, session_id());
            self::assertFalse($handler->validateId($id));
            self::assertSame('preserved', $_SESSION['value']);
        } finally {
            if (session_status() === PHP_SESSION_ACTIVE) {
                session_destroy();
            }
            session_id('');
        }
    }
}
