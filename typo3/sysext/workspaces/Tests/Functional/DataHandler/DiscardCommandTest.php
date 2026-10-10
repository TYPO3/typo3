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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Workspaces\DataHandler\CommandMap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\ActionService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Scenarios for the "discard" command and its parity with the legacy "version"
 * command with action "clearWSID" / "flush". Dependencies are only resolved for
 * delete placeholders, so the scenarios delete a record with an inline child.
 * The legacy data sets guard the parity of both command formats.
 */
final class DiscardCommandTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    protected array $testExtensionsToLoad = ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv'];

    public static function discardCommandDataProvider(): array
    {
        return [
            'discard' => [['discard' => true]],
            'legacy clearWSID' => [['version' => ['action' => 'clearWSID']]],
            'legacy flush' => [['version' => ['action' => 'flush']]],
        ];
    }

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

    /**
     * Creates a live content element with a live inline hotel and deletes the
     * content element in the workspace, which creates delete placeholders for both.
     *
     * @return array{0: int, 1: int} versioned uids of the content element and the hotel
     */
    private function deleteParentWithInlineChildInWorkspace(ActionService $actionService, int $workspaceId): array
    {
        $backendUser = $GLOBALS['BE_USER'];
        $backendUser->workspace = 0;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect(0));
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'Live parent', 'tx_testirrecsv_hotels' => '__nextUid'],
            'tx_testirrecsv_hotel' => ['title' => 'Live hotel'],
        ]);
        $liveContentId = (int)$map['tt_content'][0];
        $liveHotelId = (int)$map['tx_testirrecsv_hotel'][0];
        $backendUser->workspace = $workspaceId;
        $this->get(Context::class)->setAspect('workspace', new WorkspaceAspect($workspaceId));
        $actionService->deleteRecord('tt_content', $liveContentId);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        return [
            $this->resolveVersionId('tt_content', $liveContentId, $workspaceId),
            $this->resolveVersionId('tx_testirrecsv_hotel', $liveHotelId, $workspaceId),
        ];
    }

    private function processCommandMap(array $commandMap): DataHandler
    {
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->start([], $commandMap);
        $dataHandler->process_cmdmap();
        return $dataHandler;
    }

    private function resolveVersionId(string $table, int $liveId, int $workspaceId): int
    {
        $row = $this->getConnectionPool()->getConnectionForTable($table)
            ->select(['uid'], $table, ['t3ver_oid' => $liveId, 't3ver_wsid' => $workspaceId])
            ->fetchAssociative();
        self::assertIsArray($row, sprintf('no workspace version found for %s:%d', $table, $liveId));
        return (int)$row['uid'];
    }

    private function recordExists(string $table, int $uid): bool
    {
        return $this->getConnectionPool()->getConnectionForTable($table)
            ->count('uid', $table, ['uid' => $uid]) > 0;
    }

    #[Test]
    #[DataProvider('discardCommandDataProvider')]
    public function deletedParentIsDiscardedAlongWithRequestedChild(array $command): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        // Only the child is requested, the parent is added by dependency resolution
        $dataHandler = $this->processCommandMap(['tx_testirrecsv_hotel' => [$hotelVersionId => $command]]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse($this->recordExists('tx_testirrecsv_hotel', $hotelVersionId));
        self::assertFalse($this->recordExists('tt_content', $contentVersionId));
    }

    #[Test]
    #[DataProvider('discardCommandDataProvider')]
    public function deletedParentIsDiscardedWithItsChild(array $command): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $dataHandler = $this->processCommandMap(['tt_content' => [$contentVersionId => $command]]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse($this->recordExists('tt_content', $contentVersionId));
        self::assertFalse($this->recordExists('tx_testirrecsv_hotel', $hotelVersionId));
    }

    #[Test]
    #[DataProvider('discardCommandDataProvider')]
    public function deletedParentAndChildRequestedTogetherAreDiscarded(array $command): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $dataHandler = $this->processCommandMap([
            'tt_content' => [$contentVersionId => $command],
            'tx_testirrecsv_hotel' => [$hotelVersionId => $command],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse($this->recordExists('tt_content', $contentVersionId));
        self::assertFalse($this->recordExists('tx_testirrecsv_hotel', $hotelVersionId));
    }

    #[Test]
    public function requestedElementsOfOneStructureKeepTheirOwnCommandShape(): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $commandMap = (new CommandMap($this->get(EventDispatcherInterface::class)))->process([
            'tt_content' => [$contentVersionId => ['version' => ['action' => 'clearWSID']]],
            'tx_testirrecsv_hotel' => [$hotelVersionId => ['discard' => true]],
        ], 91);

        self::assertSame(
            [
                'tt_content' => [$contentVersionId => ['version' => ['action' => 'clearWSID']]],
                'tx_testirrecsv_hotel' => [$hotelVersionId => ['discard' => true]],
            ],
            $commandMap
        );
    }

    #[Test]
    public function requestedElementsOfOneStructureWithMixedCommandsAreDiscarded(): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $dataHandler = $this->processCommandMap([
            'tt_content' => [$contentVersionId => ['version' => ['action' => 'clearWSID']]],
            'tx_testirrecsv_hotel' => [$hotelVersionId => ['discard' => true]],
        ]);

        self::assertSame([], $dataHandler->errorLog);
        self::assertFalse($this->recordExists('tt_content', $contentVersionId));
        self::assertFalse($this->recordExists('tx_testirrecsv_hotel', $hotelVersionId));
    }

    /**
     * processCmdmap hooks registered after the workspaces hook receive the command map
     * that CommandMap returns. Every element of a dependency structure must carry the
     * command the caller sent, never an empty or converted one.
     */
    #[Test]
    #[DataProvider('discardCommandDataProvider')]
    public function dependentElementsKeepTheShapeOfTheRequestedCommand(array $command): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $commandMap = (new CommandMap($this->get(EventDispatcherInterface::class)))->process(['tx_testirrecsv_hotel' => [$hotelVersionId => $command]], 91);

        self::assertSame(
            [
                'tt_content' => [$contentVersionId => $command],
                'tx_testirrecsv_hotel' => [$hotelVersionId => $command],
            ],
            $commandMap
        );
    }

    #[Test]
    public function legacyAndNewCommandForTheSameRecordAreResolvedToTheNewCommand(): void
    {
        $actionService = $this->setUpWorkspace(91);
        [$contentVersionId, $hotelVersionId] = $this->deleteParentWithInlineChildInWorkspace($actionService, 91);

        $commandMap = (new CommandMap($this->get(EventDispatcherInterface::class)))->process(['tx_testirrecsv_hotel' => [$hotelVersionId => [
            'discard' => true,
            'version' => ['action' => 'flush'],
        ]]], 91);

        self::assertSame(
            [
                'tt_content' => [$contentVersionId => ['discard' => true]],
                'tx_testirrecsv_hotel' => [$hotelVersionId => ['discard' => true]],
            ],
            $commandMap
        );
    }

    #[Test]
    public function standaloneDiscardCommandIsNotRewritten(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $actionService->modifyRecord('tt_content', 1, ['header' => 'Changed in workspace']);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $commandMap = (new CommandMap($this->get(EventDispatcherInterface::class)))->process(['tt_content' => [$versionId => ['discard' => true]]], 91);

        self::assertSame(['tt_content' => [$versionId => ['discard' => true]]], $commandMap);
    }
}
