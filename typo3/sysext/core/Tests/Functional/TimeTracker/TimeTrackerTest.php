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

namespace TYPO3\CMS\Core\Tests\Functional\TimeTracker;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LogLevel;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TimeTrackerTest extends FunctionalTestCase
{
    public static function logLevelDataProvider(): array
    {
        return [
            'emergency' => [LogLevel::EMERGENCY],
            'alert' => [LogLevel::ALERT],
            'critical' => [LogLevel::CRITICAL],
            'error' => [LogLevel::ERROR],
            'warning' => [LogLevel::WARNING],
            'notice' => [LogLevel::NOTICE],
            'info' => [LogLevel::INFO],
            'debug' => [LogLevel::DEBUG],
        ];
    }

    #[DataProvider('logLevelDataProvider')]
    #[Test]
    public function setTSlogMessageHandlesAllPsrLogLevels(string $logLevel): void
    {
        $subject = new TimeTracker();
        $subject->push('someLabel');
        $subject->setTSlogMessage('someMessage', $logLevel);
        $subject->pull();

        $logStack = $subject->getTypoScriptLogStack();
        $messages = array_column($logStack, 'message');
        self::assertStringContainsString('someMessage', $messages[0][0]);
    }

    #[Test]
    public function setTSlogMessageHandlesUnknownLogLevel(): void
    {
        $subject = new TimeTracker();
        $subject->push('someLabel');
        $subject->setTSlogMessage('someMessage', 'someUnknownLevel');
        $subject->pull();

        $logStack = $subject->getTypoScriptLogStack();
        $messages = array_column($logStack, 'message');
        self::assertStringContainsString('someMessage', $messages[0][0]);
    }
}
