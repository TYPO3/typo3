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

namespace TYPO3\CMS\Workspaces\Tests\Functional\DataHandler;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Scenarios for the "publish" command, which is keyed by the versioned record uid.
 * The dependency resolution in \TYPO3\CMS\Workspaces\DataHandler\CommandMap and the
 * whole \TYPO3\CMS\Workspaces\Dependency namespace address records by their versioned
 * uid as well, so no id mapping happens in between. These tests pin that invariant.
 */
final class PublishCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    protected array $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv'];

    private function setUpWorkspace(int $workspaceId): ActionService
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/tt_content.csv');
        $backendUser->workspace = $workspaceId;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
        return new ActionService();
    }

    private function publish(array $commandMap): void
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
    }

    private function resolveVersionId(string $table, int $liveId, int $workspaceId): int
    {
        $row = $this->getConnectionPool()->getConnectionForTable($table)
            ->select(['uid'], $table, ['t3ver_oid' => $liveId, 't3ver_wsid' => $workspaceId])
            ->fetchAssociative();
        self::assertIsArray($row, sprintf('no workspace version found for %s:%d', $table, $liveId));
        return (int)$row['uid'];
    }

    private function fetchRecord(string $table, int $uid): ?array
    {
        $row = $this->getConnectionPool()->getConnectionForTable($table)
            ->select(['*'], $table, ['uid' => $uid])
            ->fetchAssociative();
        return $row === false ? null : $row;
    }

    #[Test]
    public function changedRecordIsPublishedByItsVersionedUid(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $actionService->modifyRecord('tt_content', 1, ['header' => 'Changed in workspace']);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->publish(['tt_content' => [$versionId => ['publish' => []]]]);

        $liveRecord = $this->fetchRecord('tt_content', 1);
        self::assertSame('Changed in workspace', $liveRecord['header']);
        self::assertSame(0, (int)$liveRecord['t3ver_wsid']);
    }

    /**
     * New records carry t3ver_oid=0, so the live uid the legacy "version" command
     * required had to be faked by the caller. The "publish" command resolves it.
     */
    #[Test]
    public function newRecordIsPublishedByItsVersionedUid(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecord('tt_content', 1, ['header' => 'Created in workspace']);
        $versionId = (int)$map['tt_content'][0];
        self::assertSame(0, (int)$this->fetchRecord('tt_content', $versionId)['t3ver_oid']);

        $this->publish(['tt_content' => [$versionId => ['publish' => []]]]);

        $publishedRecord = $this->fetchRecord('tt_content', $versionId);
        self::assertSame('Created in workspace', $publishedRecord['header']);
        self::assertSame(0, (int)$publishedRecord['t3ver_wsid']);
    }

    /**
     * The dependency resolver pulls dependent records into the command map even when
     * they were not part of the incoming command. This must work with the versioned
     * uid the "publish" command is keyed by.
     */
    #[Test]
    public function dependentInlineChildIsPublishedAlongWithItsParent(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Live parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Live hotel'],
        ]);
        $liveContentId = (int)$map['tt_content'][0];
        $liveHotelId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->publish([
            'tt_content' => [$liveContentId => ['publish' => []]],
            'tx_testirrecsv_hotel' => [$liveHotelId => ['publish' => []]],
        ]);

        $actionService->modifyRecord('tx_testirrecsv_hotel', $liveHotelId, ['title' => 'Hotel changed']);
        $actionService->modifyRecord('tt_content', $liveContentId, ['header' => 'Parent changed']);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $parentVersionId = $this->resolveVersionId('tt_content', $liveContentId, 91);

        // Only the parent is requested, the child is added by dependency resolution
        $this->publish(['tt_content' => [$parentVersionId => ['publish' => []]]]);

        self::assertSame('Parent changed', $this->fetchRecord('tt_content', $liveContentId)['header']);
        self::assertSame('Hotel changed', $this->fetchRecord('tx_testirrecsv_hotel', $liveHotelId)['title']);
    }

    /**
     * A new record and its new inline child have no live counterpart at all. The
     * dependency structure is built from sys_refindex, which stores the versioned
     * uid in "recuid" / "ref_uid".
     */
    #[Test]
    public function newRecordWithNewInlineChildIsPublishedAsAWhole(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'New parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'New hotel'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $hotelVersionId = (int)$map['tx_testirrecsv_hotel'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->publish(['tt_content' => [$contentVersionId => ['publish' => []]]]);

        self::assertSame(0, (int)$this->fetchRecord('tt_content', $contentVersionId)['t3ver_wsid']);
        self::assertSame(0, (int)$this->fetchRecord('tx_testirrecsv_hotel', $hotelVersionId)['t3ver_wsid']);
    }

    /**
     * The legacy "version" command is keyed by the live uid, the new "publish" command
     * by the versioned uid. Both must end up as the same dependency element, so a
     * command map mixing them may not publish anything twice.
     */
    #[Test]
    public function legacyVersionCommandAndPublishCommandResolveToTheSameElement(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $actionService->modifyRecord('tt_content', 1, ['header' => 'Changed in workspace']);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->publish([
            'tt_content' => [
                $versionId => ['publish' => []],
                1 => ['version' => ['action' => 'publish', 'swapWith' => $versionId]],
            ],
        ]);

        self::assertSame('Changed in workspace', $this->fetchRecord('tt_content', 1)['header']);
        self::assertSame(0, (int)$this->fetchRecord('tt_content', 1)['t3ver_wsid']);
        self::assertNull($this->fetchRecord('tt_content', $versionId));
    }
}
