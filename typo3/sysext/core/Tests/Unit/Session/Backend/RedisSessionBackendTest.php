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

namespace TYPO3\CMS\Core\Tests\Unit\Session\Backend;

use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Session\Backend\RedisSessionBackend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[BackupGlobals(true)]
#[RequiresPhpExtension('redis')]
final class RedisSessionBackendTest extends UnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'] = '12345';
    }

    #[Test]
    public function databaseConfigurationMustBeInteger(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1481270871);
        $subject = new RedisSessionBackend();
        $subject->initialize(
            'default',
            [
                'database' => 'numberZero',
            ]
        );
        $subject->validateConfiguration();
    }

    #[Test]
    public function databaseConfigurationMustBeZeroOrGreater(): void
    {
        $subject = new RedisSessionBackend();
        $subject->initialize(
            'default',
            [
                'database' => -1,
            ]
        );

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1481270923);
        $subject->validateConfiguration();
    }

    /**
     * Verifies that a warning is logged when del() fails for an expired
     * anonymous session during garbage collection, instead of silently
     * discarding the failure.
     */
    #[Test]
    public function collectGarbageLogsWarningWhenDelFailsForAnonymousSession(): void
    {
        [$subject, $redisMock, $loggerMock] = $this->createSubjectWithMockedRedis();

        $this->configureScanAndMGetForSessions($redisMock, [
            ['ses_id' => 'anon1', 'ses_userid' => 0, 'ses_tstamp' => 100],
        ]);

        $redisMock->method('del')->willReturn(false);
        $redisMock->method('getLastError')->willReturn('NOPERM');

        $this->expectLoggerWarningForCommand($loggerMock, 'del', 'collectGarbage()');

        $GLOBALS['EXEC_TIME'] = 200;
        $subject->collectGarbage(60, 10);
    }

    /**
     * Verifies that a warning is logged when del() fails for an expired
     * authenticated session during garbage collection, instead of silently
     * discarding the failure.
     */
    #[Test]
    public function collectGarbageLogsWarningWhenDelFailsForAuthenticatedSession(): void
    {
        [$subject, $redisMock, $loggerMock] = $this->createSubjectWithMockedRedis();

        $this->configureScanAndMGetForSessions($redisMock, [
            ['ses_id' => 'auth1', 'ses_userid' => 1, 'ses_tstamp' => 100],
        ]);

        $redisMock->method('del')->willReturn(false);
        $redisMock->method('getLastError')->willReturn('NOPERM');

        $this->expectLoggerWarningForCommand($loggerMock, 'del', 'collectGarbage()');

        $GLOBALS['EXEC_TIME'] = 200;
        $subject->collectGarbage(60);
    }

    /**
     * Verifies that garbage collection continues processing remaining sessions
     * even when del() fails for one session. Both sessions should trigger a
     * del() call and a warning log entry.
     */
    #[Test]
    public function collectGarbageContinuesProcessingAfterDelFailure(): void
    {
        [$subject, $redisMock, $loggerMock] = $this->createSubjectWithMockedRedis();

        $this->configureScanAndMGetForSessions($redisMock, [
            ['ses_id' => 'auth1', 'ses_userid' => 1, 'ses_tstamp' => 100],
            ['ses_id' => 'auth2', 'ses_userid' => 1, 'ses_tstamp' => 100],
        ]);

        $redisMock->expects($this->exactly(2))->method('del')->willReturn(false);
        $redisMock->method('getLastError')->willReturn('NOPERM');

        $loggerMock->expects($this->exactly(2))->method('warning')->with(
            'Redis command {command} failed in {method}.',
            self::callback(static fn(array $context): bool => $context['command'] === 'del' && $context['method'] === 'collectGarbage()')
        );

        $GLOBALS['EXEC_TIME'] = 200;
        $subject->collectGarbage(60);
    }

    /**
     * Creates a RedisSessionBackend with mocked Redis instance and logger,
     * bypassing the actual connection initialization.
     *
     * @return array{0: RedisSessionBackend, 1: \Redis&MockObject, 2: LoggerInterface&MockObject}
     */
    private function createSubjectWithMockedRedis(): array
    {
        $redisMock = $this->createMock(\Redis::class);
        $loggerMock = $this->createMock(LoggerInterface::class);

        $subject = new RedisSessionBackend();
        $subject->initialize('default', []);
        $subject->setLogger($loggerMock);

        // Inject mocked Redis instance and set connected = true
        $redisProperty = new \ReflectionProperty(RedisSessionBackend::class, 'redis');
        $redisProperty->setValue($subject, $redisMock);

        $connectedProperty = new \ReflectionProperty(RedisSessionBackend::class, 'connected');
        $connectedProperty->setValue($subject, true);

        return [$subject, $redisMock, $loggerMock];
    }

    /**
     * Expects exactly one logger->warning() call for a Redis command failure
     * with PSR-3 placeholder message and matching context values.
     */
    private function expectLoggerWarningForCommand(
        LoggerInterface&MockObject $loggerMock,
        string $command,
        string $method,
    ): void {
        $loggerMock->expects($this->once())->method('warning')->with(
            'Redis command {command} failed in {method}.',
            self::callback(static fn(array $context): bool => $context['command'] === $command && $context['method'] === $method)
        );
    }

    /**
     * Configures the Redis mock's scan() and mGet() methods to return
     * the provided session records, simulating getAll() behavior without
     * an actual Redis connection.
     */
    private function configureScanAndMGetForSessions(
        \Redis&MockObject $redisMock,
        array $sessions,
    ): void {
        $keys = [];
        $jsonSessions = [];
        foreach ($sessions as $i => $session) {
            $keys[] = 'session_key_' . $i;
            $jsonSessions[] = json_encode($session);
        }

        $scanCallCount = 0;
        $redisMock->method('setOption')->willReturn(true);
        $redisMock->method('scan')->willReturnCallback(
            static function (&$iterator, $pattern) use (&$scanCallCount, $keys): array|false {
                $scanCallCount++;
                if ($scanCallCount === 1) {
                    $iterator = 0;
                    return $keys;
                }
                return false;
            }
        );
        $redisMock->method('mGet')->willReturn($jsonSessions);
    }
}
