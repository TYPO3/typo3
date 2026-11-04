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
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_irre_csv',
        'typo3/sysext/workspaces/Tests/Functional/Fixtures/Extensions/test_workspace_dependencies',
    ];

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

    #[Test]
    public function requestedInlineChildPublishesItsParentAlong(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'New parent', 'tx_testworkspacedependencies_children_list' => '__nextUid'],
            'tx_testworkspacedependencies_child' => ['title' => 'New child'],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $childVersionId = (int)$map['tx_testworkspacedependencies_child'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->publish(['tx_testworkspacedependencies_child' => [$childVersionId => ['publish' => []]]]);

        self::assertSame(0, (int)$this->fetchRecord('tt_content', $contentVersionId)['t3ver_wsid']);
        self::assertSame(0, (int)$this->fetchRecord('tx_testworkspacedependencies_child', $childVersionId)['t3ver_wsid']);
    }

    #[Test]
    public function fileReferenceIsPublishedAlongWithItsParent(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file.csv');
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'New content with image', 'image' => '__nextUid'],
            'sys_file_reference' => ['uid_local' => 1],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $fileReferenceVersionId = (int)$map['sys_file_reference'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->publish(['tt_content' => [$contentVersionId => ['publish' => []]]]);

        self::assertSame(0, (int)$this->fetchRecord('tt_content', $contentVersionId)['t3ver_wsid']);
        self::assertSame(0, (int)$this->fetchRecord('sys_file_reference', $fileReferenceVersionId)['t3ver_wsid']);
    }

    /**
     * tt_content.tx_testworkspacedependencies_image is a type=file field with
     * "relationship" => "manyToOne".
     */
    #[Test]
    public function fileReferenceOfSingleFileFieldIsPublishedAlongWithItsParent(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_file.csv');
        $map = $actionService->createNewRecords(1, [
            'tt_content' => ['header' => 'New content with single image', 'tx_testworkspacedependencies_image' => '__nextUid'],
            'sys_file_reference' => ['uid_local' => 1],
        ]);
        $contentVersionId = (int)$map['tt_content'][0];
        $fileReferenceVersionId = (int)$map['sys_file_reference'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->publish(['tt_content' => [$contentVersionId => ['publish' => []]]]);

        $publishedContent = $this->fetchRecord('tt_content', $contentVersionId);
        self::assertSame(0, (int)$publishedContent['t3ver_wsid']);
        self::assertSame(1, (int)$publishedContent['tx_testworkspacedependencies_image']);
        self::assertSame(0, (int)$this->fetchRecord('sys_file_reference', $fileReferenceVersionId)['t3ver_wsid']);
    }

    /**
     * The published content element points to a content element that only exists in
     * the workspace.
     *
     * @todo Whether group relations should be considered for dependencies up to discussion,
     *       see DependencyResolverTest.
     */
    #[Test]
    public function newRecordReferencedByGroupFieldIsNotPublishedAlong(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $targetId = (int)$actionService->createNewRecord('tt_content', 1, ['header' => 'New target'])['tt_content'][0];
        $actionService->modifyRecord('tt_content', 1, ['records' => 'tt_content_' . $targetId]);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->publish(['tt_content' => [$versionId => ['publish' => []]]]);

        self::assertSame((string)$targetId, $this->fetchRecord('tt_content', 1)['records']);
        self::assertSame(91, (int)$this->fetchRecord('tt_content', $targetId)['t3ver_wsid']);
    }

    /**
     * The published content element links to a page that only exists in the
     * workspace.
     *
     * @todo Whether soft references should be considered for dependencies up to discussion,
     *       see DependencyResolverTest.
     */
    #[Test]
    public function newPageLinkedBySoftReferenceIsNotPublishedAlong(): void
    {
        $actionService = $this->setUpWorkspace(91);
        $pageId = (int)$actionService->createNewRecord('pages', 1, ['title' => 'New page', 'hidden' => 0])['pages'][0];
        $bodytext = '<p><a href="t3://page?uid=' . $pageId . '">New page</a></p>';
        $actionService->modifyRecord('tt_content', 1, ['bodytext' => $bodytext]);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $versionId = $this->resolveVersionId('tt_content', 1, 91);

        $this->publish(['tt_content' => [$versionId => ['publish' => []]]]);

        self::assertSame($bodytext, $this->fetchRecord('tt_content', 1)['bodytext']);
        self::assertSame(91, (int)$this->fetchRecord('pages', $pageId)['t3ver_wsid']);
    }

    public static function publishingRecordReferencedInFlexFormAlsoPublishesReferencingRecordDataProvider(): iterable
    {
        yield 'group field' => [
            'flexFormField' => 'related',
            'referencedTable' => 'tt_content',
            'referencedRecord' => ['header' => 'Referenced'],
            'flexFormValue' => 'tt_content_%d',
        ];
        yield 'soft reference in link field' => [
            'flexFormField' => 'link',
            'referencedTable' => 'pages',
            'referencedRecord' => ['title' => 'Referenced', 'hidden' => 0],
            'flexFormValue' => 't3://page?uid=%d',
        ];
    }

    /**
     * Content element A references the new record B in a FlexForm. Publishing only B
     * also publishes A.
     *
     * @todo The core listener considers relations regardless of the direction they are
     *       resolved in, see
     *       DependencyResolverTest::referencedRecordOfFlexFormResolvesTheReferencingStructure().
     */
    #[Test]
    #[DataProvider('publishingRecordReferencedInFlexFormAlsoPublishesReferencingRecordDataProvider')]
    public function publishingRecordReferencedInFlexFormAlsoPublishesReferencingRecord(
        string $flexFormField,
        string $referencedTable,
        array $referencedRecord,
        string $flexFormValue,
    ): void {
        $actionService = $this->setUpWorkspace(91);
        $referencedId = (int)$actionService->createNewRecord($referencedTable, 1, $referencedRecord)[$referencedTable][0];
        $referencingId = (int)$actionService->createNewRecord('tt_content', 1, [
            'header' => 'Referencing',
            'tx_testworkspacedependencies_flex' => [
                'data' => ['sDEF' => ['lDEF' => [$flexFormField => ['vDEF' => sprintf($flexFormValue, $referencedId)]]]],
            ],
        ])['tt_content'][0];
        $this->get(ReferenceIndex::class)->updateIndex(false);

        $this->publish([$referencedTable => [$referencedId => ['publish' => []]]]);

        self::assertSame(0, (int)$this->fetchRecord($referencedTable, $referencedId)['t3ver_wsid']);
        self::assertSame(0, (int)$this->fetchRecord('tt_content', $referencingId)['t3ver_wsid']);
    }

    public static function recordReferencedInFlexFormIsPublishedAlongDataProvider(): iterable
    {
        yield 'group field' => [
            'flexFormField' => 'related',
            'referencedTable' => 'tt_content',
            'referencedRecord' => ['header' => 'Referenced'],
            'referencedChange' => ['header' => 'Referenced changed'],
            'flexFormValue' => 'tt_content_%d',
        ];
        yield 'soft reference in link field' => [
            'flexFormField' => 'link',
            'referencedTable' => 'pages',
            'referencedRecord' => ['title' => 'Referenced', 'hidden' => 0],
            'referencedChange' => ['title' => 'Referenced changed'],
            'flexFormValue' => 't3://page?uid=%d',
        ];
        yield 'soft reference in rich text field' => [
            'flexFormField' => 'text',
            'referencedTable' => 'pages',
            'referencedRecord' => ['title' => 'Referenced', 'hidden' => 0],
            'referencedChange' => ['title' => 'Referenced changed'],
            'flexFormValue' => '<p><a href="t3://page?uid=%d">Referenced</a></p>',
        ];
    }

    /**
     * Content element A references record B in a FlexForm. B has a change of its own in
     * the workspace, which is published along with A, although only A was requested.
     * The same kinds of relations in regular fields do not publish anything along, see
     * newRecordReferencedByGroupFieldIsNotPublishedAlong() and
     * newPageLinkedBySoftReferenceIsNotPublishedAlong().
     *
     * @todo Every relation of a FlexForm field is considered, regardless of the FlexForm
     *       field it originates from, see DependencyResolverTest.
     */
    #[Test]
    #[DataProvider('recordReferencedInFlexFormIsPublishedAlongDataProvider')]
    public function recordReferencedInFlexFormIsPublishedAlong(
        string $flexFormField,
        string $referencedTable,
        array $referencedRecord,
        array $referencedChange,
        string $flexFormValue,
    ): void {
        $actionService = $this->setUpWorkspace(91);
        $referencingId = (int)$actionService->createNewRecord('tt_content', 1, ['header' => 'Referencing'])['tt_content'][0];
        $referencedId = (int)$actionService->createNewRecord($referencedTable, 1, $referencedRecord)[$referencedTable][0];
        $commandMap = [];
        $commandMap['tt_content'][$referencingId]['publish'] = [];
        $commandMap[$referencedTable][$referencedId]['publish'] = [];
        $this->publish($commandMap);

        $actionService->modifyRecord($referencedTable, $referencedId, $referencedChange);
        $actionService->modifyRecord('tt_content', $referencingId, [
            'header' => 'Referencing changed',
            'tx_testworkspacedependencies_flex' => [
                'data' => ['sDEF' => ['lDEF' => [$flexFormField => ['vDEF' => sprintf($flexFormValue, $referencedId)]]]],
            ],
        ]);
        $this->get(ReferenceIndex::class)->updateIndex(false);
        $referencingVersionId = $this->resolveVersionId('tt_content', $referencingId, 91);

        $this->publish(['tt_content' => [$referencingVersionId => ['publish' => []]]]);

        self::assertSame('Referencing changed', $this->fetchRecord('tt_content', $referencingId)['header']);
        $publishedReferencedRecord = $this->fetchRecord($referencedTable, $referencedId);
        foreach ($referencedChange as $fieldName => $value) {
            self::assertSame($value, $publishedReferencedRecord[$fieldName]);
        }
    }
}
