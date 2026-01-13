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

namespace TYPO3\CMS\Core\Tests\Functional\DataHandling\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DeleteActionTest extends FunctionalTestCase
{
    private const LOG_TEMPLATE_TABLE = 'Cannot delete "%s:%d" without permission';
    private const LOG_TEMPLATE_WEBMOUNT = 'Attempt to delete page without permissions';

    protected array $coreExtensionsToLoad = ['workspaces'];
    private BackendUserAuthentication $backendUser;
    private DataHandler $subject;

    protected function setUp(): void
    {
        parent::setUp();

        $this->importCSVDataSet(dirname(__DIR__, 2) . '/Fixtures/be_groups.csv');
        $this->importCSVDataSet(dirname(__DIR__, 2) . '/Fixtures/be_users.csv');
        $this->importCSVDataSet(dirname(__DIR__, 2) . '/Fixtures/pages.csv');

        $this->backendUser = $this->setUpBackendUser(9);
        // allow modifying the live workspace
        $this->backendUser->groupData['workspace_perms'] = 1;
        $this->backendUser->setWorkspace(0);
        $this->backendUser->setWebmounts([1]);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);

        $this->subject = GeneralUtility::makeInstance(DataHandler::class);
        $this->subject->start([], []);
    }

    #[Test]
    public function softDeletingPageInWebMountIsAllowed(): void
    {
        $this->subject->deleteEl('pages', 10);
        self::assertSame([], $this->subject->errorLog);
        self::assertTrue($this->databaseRecordExists('pages', 10, true));
    }

    #[Test]
    public function softDeletingNestedPageInWebMountIsDenied(): void
    {
        $this->subject->deleteEl('pages', 4);
        // @todo due to https://forge.typo3.org/issues/101635, the `runtime` cache cannot be a `NullCache`
        // (besides the fact, that `DataHandler` fails to clear `runtime` caches when generating the root-line)
        $this->get('cache.runtime')->flush();
        $this->subject->deleteEl('pages', 10);
        $this->assertLogEntry(self::LOG_TEMPLATE_WEBMOUNT);
        self::assertTrue($this->databaseRecordExists('pages', 4, true));
        self::assertTrue($this->databaseRecordExists('pages', 10, true));
    }

    #[Test]
    public function softDeletingPageWithoutTablePermissionIsDenied(): void
    {
        $this->backendUser->groupData['tables_modify'] = '';

        $this->subject->deleteEl('pages', 10);
        $this->assertLogEntry(self::LOG_TEMPLATE_TABLE, 'pages', 10);
        self::assertTrue($this->databaseRecordExists('pages', 10, false));
    }

    #[Test]
    public function softDeletingPageNotInWebMountIsDenied(): void
    {
        $this->backendUser->setWebmounts([9]);

        $this->subject->deleteEl('pages', 10);
        $this->assertLogEntry(self::LOG_TEMPLATE_WEBMOUNT);
        self::assertTrue($this->databaseRecordExists('pages', 10, false));
    }

    #[Test]
    public function softDeletingDanglingPageVersionIsDenied(): void
    {
        $this->subject->deleteEl('pages', 11);
        $this->assertLogEntry(self::LOG_TEMPLATE_WEBMOUNT);
        self::assertTrue($this->databaseRecordExists('pages', 11, false));
    }

    #[Test]
    public function hardDeletingPageInWebMountIsAllowed(): void
    {
        // first soft-delete
        $this->subject->deleteEl('pages', 10);
        // second hard-delete
        $this->subject->deleteEl('pages', 10, false, true);
        self::assertSame([], $this->subject->errorLog);
        self::assertFalse($this->databaseRecordExists('pages', 10, null));
    }

    #[Test]
    public function hardDeletingNestedPageInWebMountIsDenied(): void
    {
        // first soft-delete the parent page
        $this->subject->deleteEl('pages', 4);
        // @todo due to https://forge.typo3.org/issues/101635, the `runtime` cache cannot be a `NullCache`
        // (besides the fact, that `DataHandler` fails to clear `runtime` caches when generating the root-line)
        $this->get('cache.runtime')->flush();
        // second hard-delete the nested page
        $this->subject->deleteEl('pages', 10, false, true);
        self::assertSame([], $this->subject->errorLog);
        self::assertTrue($this->databaseRecordExists('pages', 4, true));
        self::assertFalse($this->databaseRecordExists('pages', 10, null));
    }

    private function databaseRecordExists(string $tableName, int $id, ?bool $expectDeleted): bool
    {
        $identifiers = ['uid' => $id];
        $softDeleteFieldName = $GLOBALS['TCA'][$tableName]['ctrl']['delete'] ?? null;
        if ($expectDeleted !== null && !empty($softDeleteFieldName)) {
            $identifiers[$softDeleteFieldName] = (int)$expectDeleted;
        }

        $queryBuilder = $this->getConnectionPool()
            ->getQueryBuilderForTable($tableName)
            ->count('uid')
            ->from($tableName);
        $queryBuilder->getRestrictions()->removeAll();
        foreach ($identifiers as $identifier => $value) {
            $queryBuilder->andWhere($queryBuilder->expr()->eq($identifier, $queryBuilder->createNamedParameter($value)));
        }
        return (int)$queryBuilder->executeQuery()->fetchOne() === 1;
    }

    private function assertLogEntry(string $logTemplate, ?string $tableName = null, ?int $id = null): void
    {
        $text = sprintf($logTemplate, (string)$tableName, $id !== null ? (string)$id : '');
        $matches = array_filter(
            $this->subject->errorLog,
            static fn(string $entry): bool => str_ends_with($entry, $text)
        );
        self::assertNotSame([], $matches, 'Unable to find log entry: ' . $text);
    }
}
