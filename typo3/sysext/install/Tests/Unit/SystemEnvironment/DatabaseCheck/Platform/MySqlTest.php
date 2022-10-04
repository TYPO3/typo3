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

namespace TYPO3\CMS\Install\Tests\Unit\SystemEnvironment\DatabaseCheck\Platform;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Platforms\MariaDBPlatform;
use Doctrine\DBAL\Platforms\MySQLPlatform;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Install\SystemEnvironment\DatabaseCheck\Platform\MySql;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class MySqlTest extends UnitTestCase
{
    public function checkMySQLOrMariaDBVersionReportsExpectedStatusDataProvider(): \Generator
    {
        // invalid cases
        yield 'Invalid ServerVersionString returns version too low' => [
            'platform' => new MySQLPlatform(),
            'serverVersionString' => '',
            'expectedSeverity' => ContextualFeedbackSeverity::ERROR,
            'expectedTitle' => 'MySQL version invalid',
        ];

        // MySQL cases
        yield 'MySQL version valid' => [
            'platform' => new MySQLPlatform(),
            'serverVersionString' => 'MySQL 8.0.11',
            'expectedSeverity' => ContextualFeedbackSeverity::OK,
            'expectedTitle' => 'MySQL version 8.0.11 is fine',
        ];
        yield 'MySQL old version detects as too low' => [
            'platform' => new MySQLPlatform(),
            'serverVersionString' => 'MySQL 5.7.8',
            'expectedSeverity' => ContextualFeedbackSeverity::ERROR,
            'expectedTitle' => 'MySQL version too low',
        ];

        // MariaDB cases
        yield 'MariaDB version with prefix detects correct' => [
            'platform' => new MariaDBPlatform(),
            'serverVersionString' => 'MySQL 5.5.5-10.3.32-MariaDB-1:10.3.32+maria~focal-log',
            'expectedSeverity' => ContextualFeedbackSeverity::OK,
            'expectedTitle' => 'MariaDB version 10.3.32 is fine',
        ];
        yield 'MariaDB version without prefix detects correct' => [
            'platform' => new MariaDBPlatform(),
            'serverVersionString' => 'MySQL 10.3.32-MariaDB-1:10.3.32+maria~focal-log',
            'expectedSeverity' => ContextualFeedbackSeverity::OK,
            'expectedTitle' => 'MariaDB version 10.3.32 is fine',
        ];
        yield 'MariaDB old version with prefix detects as too low' => [
            'platform' => new MariaDBPlatform(),
            'serverVersionString' => 'MySQL 5.5.5-10.2.11-MariaDB-1:10.2.11+maria~focal-log',
            'expectedSeverity' => ContextualFeedbackSeverity::ERROR,
            'expectedTitle' => 'MariaDB version too low',
        ];
        yield 'MariaDB old version without prefix detects as too low' => [
            'platform' => new MariaDBPlatform(),
            'serverVersionString' => 'MySQL 10.2.11-MariaDB-1:10.2.11+maria~focal-log',
            'expectedSeverity' => ContextualFeedbackSeverity::ERROR,
            'expectedTitle' => 'MariaDB version too low',
        ];
    }

    /**
     * @test
     * @dataProvider checkMySQLOrMariaDBVersionReportsExpectedStatusDataProvider
     */
    public function checkMySQLOrMariaDBVersionReportsExpectedStatus(
        AbstractPlatform $platform,
        string $serverVersionString,
        ContextualFeedbackSeverity $expectedSeverity,
        string $expectedTitle
    ): void {
        /** @var Connection&MockObject $connectionMock */
        $connectionMock = $this->getMockBuilder(Connection::class)->disableOriginalConstructor()->getMock();
        $connectionMock->method('getServerVersion')->willReturn($serverVersionString);
        $connectionMock->method('getDatabasePlatform')->willReturn($platform);
        $subject = new class () extends MySql {
            public function callCheckMySQLOrMariaDBVersion(Connection $connection): void
            {
                $this->checkMySQLOrMariaDBVersion($connection);
            }
        };

        $subject->callCheckMySQLOrMariaDBVersion($connectionMock);
        $messages = $subject->getMessageQueue()->getAllMessagesAndFlush();
        $firstMessage = $messages[0] ?? null;

        self::assertCount(1, $messages);
        self::assertInstanceOf(FlashMessage::class, $firstMessage);
        if ($firstMessage instanceof FlashMessage) {
            self::assertSame($expectedTitle, $firstMessage->getTitle(), 'Message Title matches');
            self::assertSame($expectedSeverity, $firstMessage->getSeverity(), 'Message Severity matches');
        }
    }
}
