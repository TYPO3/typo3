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

namespace TYPO3\CMS\Backend\Tests\Functional\Authentication;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Psr\Log\LoggerTrait;
use Symfony\Component\RateLimiter\RateLimiterFactory;
use Symfony\Component\RateLimiter\Storage\InMemoryStorage;
use TYPO3\CMS\Backend\Authentication\PasswordReset;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Crypto\HashService;
use TYPO3\CMS\Core\Crypto\PasswordHashing\PasswordHashFactory;
use TYPO3\CMS\Core\Crypto\Random;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\NormalizedParams;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Mail\MailerInterface;
use TYPO3\CMS\Core\Session\SessionManager;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PasswordResetTest extends FunctionalTestCase
{
    #[Test]
    public function isNotEnabledWorks(): void
    {
        $subject = $this->get(PasswordReset::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertFalse($subject->isEnabled());
    }

    #[Test]
    public function isNotEnabledWithNoUsers(): void
    {
        $subject = $this->get(PasswordReset::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertFalse($subject->isEnabled());
    }

    #[Test]
    public function isEnabledExcludesAdministrators(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users_only_admins.csv');
        $subject = $this->get(PasswordReset::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = false;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;
        self::assertFalse($subject->isEnabled());
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        self::assertTrue($subject->isEnabled());
    }

    #[Test]
    public function isEnabledForUserTest(): void
    {
        $subject = $this->get(PasswordReset::class);
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = false;

        // False since no users exist
        self::assertFalse($subject->isEnabledForUser(3));

        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');

        // False since reset for admins is not enabled
        self::assertFalse($subject->isEnabledForUser(1));
        // False since user has no email set
        self::assertFalse($subject->isEnabledForUser(2));
        // False since user has no password set
        self::assertFalse($subject->isEnabledForUser(4));
        // False since user is disabled
        self::assertFalse($subject->isEnabledForUser(7));

        // Now true since user with email+password exist
        self::assertTrue($subject->isEnabledForUser(3));

        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        // True since "passwordResetForAdmins" is now set
        self::assertTrue($subject->isEnabledForUser(1));
    }

    #[Test]
    public function noEmailIsFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'does-not-exist@example.com';
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->atLeastOnce())->method('warning')->with(
            'Password reset requested for email {email} but no valid users',
            ['email' => 'does-not-exist@example.com']
        );
        $subject = new PasswordReset(
            $loggerMock,
            $this->get(MailerInterface::class),
            new HashService(),
            new Random(),
            $this->get(ConnectionPool::class),
            new NoopEventDispatcher(),
            new PasswordHashFactory(),
            $this->get(UriBuilder::class),
            new SessionManager(),
            $this->createRateLimiterFactory(),
        );
        $subject->initiateReset(new ServerRequest(), new Context(), $emailAddress);
    }

    #[Test]
    public function ambiguousEmailIsTriggeredForMultipleValidUsers(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'duplicate@example.com';
        $logger = new class () implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
        $subject = new PasswordReset(
            $logger,
            $this->get(MailerInterface::class),
            new HashService(),
            new Random(),
            $this->get(ConnectionPool::class),
            new NoopEventDispatcher(),
            new PasswordHashFactory(),
            $this->get(UriBuilder::class),
            new SessionManager(),
            $this->createRateLimiterFactory(),
        );
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $subject->initiateReset($request, new Context(), $emailAddress);
        self::assertEquals('warning', $logger->records[0]['level']);
        self::assertEquals($emailAddress, $logger->records[0]['context']['email']);
    }

    #[Test]
    public function passwordResetEmailIsTriggeredForValidUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'editor-with-email@example.com';
        $username = 'editor-with-email';
        $logger = new class () implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
        $subject = new PasswordReset(
            $logger,
            $this->get(MailerInterface::class),
            new HashService(),
            new Random(),
            $this->get(ConnectionPool::class),
            new NoopEventDispatcher(),
            new PasswordHashFactory(),
            $this->get(UriBuilder::class),
            new SessionManager(),
            $this->createRateLimiterFactory(),
        );
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $subject->initiateReset($request, new Context(), $emailAddress);
        self::assertEquals('info', $logger->records[0]['level']);
        self::assertEquals($emailAddress, $logger->records[0]['context']['email']);
        self::assertEquals($username, $logger->records[0]['context']['username']);
    }

    #[Test]
    public function invalidTokenCannotResetPassword(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $loggerMock = $this->createMock(LoggerInterface::class);
        $loggerMock->expects($this->exactly(2))->method('warning')->with('Password reset not possible. Valid user for token not found.');
        $subject = new PasswordReset(
            $loggerMock,
            $this->get(MailerInterface::class),
            new HashService(),
            new Random(),
            $this->get(ConnectionPool::class),
            new NoopEventDispatcher(),
            new PasswordHashFactory(),
            $this->get(UriBuilder::class),
            new SessionManager(),
            $this->createRateLimiterFactory(),
        );
        $request = new ServerRequest();
        $request = $request->withQueryParams(['t' => 'token', 'i' => 'identity', 'e' => 13465444]);
        $subject->resetPassword($request, new Context());
        // Now with a password
        $request = $request->withParsedBody(['password' => 'str0NGpassw0RD!', 'passwordrepeat' => 'str0NGpassw0RD!']);
        $subject->resetPassword($request, new Context());
    }

    /**
     * This test uses the given RateLimiterFactory configuration allowing 3 password reset attempts within a
     * sliding window timeframe of 30 minutes.
     */
    #[Test]
    public function passwordResetEmailIsRateLimitedForValidUser(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordReset'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['BE']['passwordResetForAdmins'] = true;
        $GLOBALS['TYPO3_CONF_VARS']['MAIL']['transport'] = 'null';
        $emailAddress = 'editor-with-email@example.com';
        $logger = new class () implements LoggerInterface {
            use LoggerTrait;
            public array $records = [];
            public function log($level, string|\Stringable $message, array $context = []): void
            {
                $this->records[] = [
                    'level' => $level,
                    'message' => $message,
                    'context' => $context,
                ];
            }
        };
        $subject = new PasswordReset(
            $logger,
            $this->get(MailerInterface::class),
            new HashService(),
            new Random(),
            $this->get(ConnectionPool::class),
            new NoopEventDispatcher(),
            new PasswordHashFactory(),
            $this->get(UriBuilder::class),
            new SessionManager(),
            $this->createRateLimiterFactory(),
        );
        $normalizedParams = $this->createMock(NormalizedParams::class);
        $normalizedParams->method('getSitePath')->willReturn('/');
        $request = (new ServerRequest('https://localhost/typo3/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE)
            ->withAttribute('normalizedParams', $normalizedParams);
        $subject->initiateReset($request, new Context(), $emailAddress); // 1st successful password reset
        $subject->initiateReset($request, new Context(), $emailAddress); // 2nd successful password reset
        $subject->initiateReset($request, new Context(), $emailAddress); // 3rd successful password reset
        $subject->initiateReset($request, new Context(), $emailAddress); // Rate limiter steps in, no email sent
        // 3rd successful password reset
        self::assertEquals('info', $logger->records[2]['level']);
        self::assertEquals($emailAddress, $logger->records[2]['context']['email']);
        // blocked 4th attempt due to rate limiter
        self::assertEquals('alert', $logger->records[3]['level']);
        self::assertEquals($emailAddress, $logger->records[3]['context']['email']);
    }

    private function createRateLimiterFactory(): RateLimiterFactory
    {
        return new RateLimiterFactory(
            ['id' => 'backend', 'policy' => 'sliding_window', 'limit' => 3, 'interval' => '30 minutes'],
            new InMemoryStorage()
        );
    }
}
