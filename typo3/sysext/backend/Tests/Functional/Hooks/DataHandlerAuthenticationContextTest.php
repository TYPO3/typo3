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

namespace TYPO3\CMS\Backend\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Middleware\SudoModeInterceptor;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessFactory;
use TYPO3\CMS\Backend\Security\SudoMode\Access\AccessStorage;
use TYPO3\CMS\Backend\Security\SudoMode\Exception\VerificationRequiredException;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerAuthenticationContextTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/DataHandlerAuthenticationContext/be_users_with_avatar.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    protected function tearDown(): void
    {
        $this->get(SudoModeInterceptor::class)->currentRequest = null;
        parent::tearDown();
    }

    #[Test]
    public function copyingBackendUserWithoutGrantDoesNotDuplicateFileReferencesOfSourceRecord(): void
    {
        // Simulate a backend request in which the sudo mode interceptor middleware is active
        $this->get(SudoModeInterceptor::class)->currentRequest = new ServerRequest('https://typo3-testing.local/typo3/record/commit', 'POST');

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['be_users' => [2 => ['copy' => 0]]]);
        $verificationRequiredExceptionThrown = false;
        try {
            $dataHandler->process_cmdmap();
        } catch (VerificationRequiredException) {
            $verificationRequiredExceptionThrown = true;
        }
        self::assertTrue($verificationRequiredExceptionThrown, 'Step-up authentication was not requested');

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_reference');
        $numberOfAvatarReferencesOfSourceUser = $connection->count(
            '*',
            'sys_file_reference',
            ['uid_foreign' => 2, 'tablenames' => 'be_users', 'fieldname' => 'avatar', 'deleted' => 0]
        );
        self::assertSame(1, $numberOfAvatarReferencesOfSourceUser, 'File reference of source record has been duplicated before step-up authentication was confirmed');
        self::assertSame(1, $connection->count('*', 'sys_file_reference', []), 'File reference has been copied before step-up authentication was confirmed');
    }

    #[Test]
    public function copyingBackendUserWithGrantsCopiesFileReferencesToCopiedRecordAndConsumesNonRepeatableGrant(): void
    {
        // Simulate a backend request in which the sudo mode interceptor middleware is active
        $this->get(SudoModeInterceptor::class)->currentRequest = new ServerRequest('https://typo3-testing.local/typo3/record/commit', 'POST');

        $accessFactory = $this->get(AccessFactory::class);
        $accessStorage = $this->get(AccessStorage::class);
        $accessStorage->addGrant($accessFactory->buildGrantForSubject(
            $accessFactory->buildTableAccessSubject('be_users', 'username', 'NEW', ['group' => 'be.userManagement'])
        ));
        $passwordSubject = $accessFactory->buildTableAccessSubject('be_users', 'password', 'NEW', ['once' => true]);
        $accessStorage->addGrant($accessFactory->buildGrantForSubject($passwordSubject));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['be_users' => [2 => ['copy' => 0]]]);
        $dataHandler->process_cmdmap();

        $copiedUid = (int)($dataHandler->copyMappingArray_merged['be_users'][2] ?? 0);
        self::assertGreaterThan(2, $copiedUid, 'Backend user has not been copied');

        // Admin (uid 1), source record (uid 2) and its copy
        self::assertSame(3, $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->count('*', 'be_users', ['deleted' => 0]));

        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_reference');
        self::assertSame(2, $connection->count('*', 'sys_file_reference', ['deleted' => 0]));
        self::assertSame(
            1,
            $connection->count('*', 'sys_file_reference', ['uid_foreign' => 2, 'tablenames' => 'be_users', 'fieldname' => 'avatar', 'deleted' => 0]),
            'File reference of source record has been duplicated'
        );
        self::assertSame(
            1,
            $connection->count('*', 'sys_file_reference', ['uid_foreign' => $copiedUid, 'tablenames' => 'be_users', 'fieldname' => 'avatar', 'deleted' => 0]),
            'File reference has not been attached to the copied record'
        );

        self::assertSame([], $accessStorage->findGrantsBySubject($passwordSubject), 'Non-repeatable grant has not been consumed');
    }

    #[Test]
    public function copyingBackendUserWithoutNonRepeatableGrantDoesNotWriteAnything(): void
    {
        // Simulate a backend request in which the sudo mode interceptor middleware is active
        $this->get(SudoModeInterceptor::class)->currentRequest = new ServerRequest('https://typo3-testing.local/typo3/record/commit', 'POST');

        $accessFactory = $this->get(AccessFactory::class);
        $this->get(AccessStorage::class)->addGrant($accessFactory->buildGrantForSubject(
            $accessFactory->buildTableAccessSubject('be_users', 'username', 'NEW', ['group' => 'be.userManagement'])
        ));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['be_users' => [2 => ['copy' => 0]]]);
        $verificationRequiredExceptionThrown = false;
        try {
            $dataHandler->process_cmdmap();
        } catch (VerificationRequiredException) {
            $verificationRequiredExceptionThrown = true;
        }
        self::assertTrue($verificationRequiredExceptionThrown, 'Step-up authentication was not requested');

        self::assertSame(2, $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->count('*', 'be_users', []), 'Backend user has been copied before step-up authentication was confirmed');
        self::assertSame(1, $this->get(ConnectionPool::class)->getConnectionForTable('sys_file_reference')->count('*', 'sys_file_reference', []), 'File reference has been copied before step-up authentication was confirmed');
    }

    #[Test]
    public function deletingBackendUserWithoutGrantDoesNotRequireStepUpAuthentication(): void
    {
        // Simulate a backend request in which the sudo mode interceptor middleware is active
        $this->get(SudoModeInterceptor::class)->currentRequest = new ServerRequest('https://typo3-testing.local/typo3/record/commit', 'POST');

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['be_users' => [2 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        // Deleted records are excluded by the default restrictions
        self::assertSame(
            0,
            $this->get(ConnectionPool::class)->getConnectionForTable('be_users')->count('*', 'be_users', ['uid' => 2]),
            'Backend user has not been deleted'
        );
    }
}
