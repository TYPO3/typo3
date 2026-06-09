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
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class UndeleteRecordTest extends FunctionalTestCase
{
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

        $this->subject = $this->get(DataHandler::class);
    }

    #[Test]
    public function undeleteWorksAsAnEditor(): void
    {
        $this->subject->start([], []);
        $this->subject->deleteAction('pages', 10);
        self::assertTrue($this->databaseRecordExists('pages', 10, true));

        $cmd = [
            'pages' => [
                10 => [
                    'undelete' => 1,
                ],
            ],
        ];

        $this->subject->start([], $cmd);
        $this->subject->process_cmdmap();
        self::assertTrue($this->databaseRecordExists('pages', 10, false));
    }

    #[Test]
    public function undeleteIsProhibitedIfMissingWritePermissionToParentPageAsAnEditor(): void
    {
        $this->subject->start([], []);
        $this->subject->deleteAction('pages', 10);
        self::assertTrue($this->databaseRecordExists('pages', 10, true));

        $this->getConnectionPool()
            ->getConnectionForTable('pages')
            ->update(
                'pages',
                // deny new page creation on page with uid 4 (page 10 has pid 4)
                ['perms_everybody' => Permission::ALL & ~Permission::PAGE_NEW],
                ['uid' => 4]
            );

        $cmd = [
            'pages' => [
                10 => [
                    'undelete' => 1,
                ],
            ],
        ];

        $this->subject->start([], $cmd);
        $this->subject->process_cmdmap();

        // Page must not have been deleted
        self::assertTrue($this->databaseRecordExists('pages', 10, true));
        $this->assertLogEntry('Record "pages:10" can\'t be restored: Insufficient user permissions to target page 4', 'pages', 10);
    }

    #[Test]
    public function undeleteIsProhibitedIfMissingTablePermissionsAsAnEditor(): void
    {
        $this->subject->start([], []);
        $this->subject->deleteAction('pages', 10);
        self::assertTrue($this->databaseRecordExists('pages', 10, true));

        $this->getConnectionPool()
            ->getConnectionForTable('be_groups')
            ->update(
                'be_groups',
                // deny new page modification
                ['tables_modify' => 'tt_content'],
                ['uid' => 9]
            );

        // Reload backend user after changes to the user group
        $this->backendUser = $this->setUpBackendUser(9);

        $cmd = [
            'pages' => [
                10 => [
                    'undelete' => 1,
                ],
            ],
        ];

        $this->subject->start([], $cmd);
        $this->subject->process_cmdmap();

        // Page must not have been deleted
        self::assertTrue($this->databaseRecordExists('pages', 10, true));
        $this->assertLogEntry('Attempt to modify table "pages" without permission');
    }

    private function databaseRecordExists(string $tableName, int $id, ?bool $expectDeleted): bool
    {
        $schema = $this->get(TcaSchemaFactory::class)->get($tableName);
        $softDeleteFieldName = $schema->hasCapability(TcaSchemaCapability::SoftDelete)
            ? $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()
            : null;

        $identifiers = ['uid' => $id];
        if ($expectDeleted !== null && $softDeleteFieldName !== null) {
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
