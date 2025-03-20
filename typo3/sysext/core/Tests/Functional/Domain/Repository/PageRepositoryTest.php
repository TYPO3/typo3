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

namespace TYPO3\CMS\Core\Tests\Functional\Domain\Repository;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Expression\CompositeExpression;
use TYPO3\CMS\Core\Domain\Event\BeforePageIsRetrievedEvent;
use TYPO3\CMS\Core\Domain\Event\ModifyDefaultConstraintsForDatabaseQueryEvent;
use TYPO3\CMS\Core\Domain\Page;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Core\Versioning\VersionState;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRepositoryTest extends FunctionalTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
    }

    #[Test]
    public function getMenuSingleUidRoot(): void
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(1);
        self::assertArrayHasKey(2, $rows);
        self::assertArrayHasKey(3, $rows);
        self::assertArrayHasKey(4, $rows);
        self::assertCount(3, $rows);
    }

    #[Test]
    public function getMenuSingleUidSubpage(): void
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(2);
        self::assertArrayHasKey(5, $rows);
        self::assertArrayHasKey(7, $rows);
        self::assertCount(2, $rows);
    }

    #[Test]
    public function getMenuMultipleUid(): void
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu([2, 3]);
        self::assertArrayHasKey(5, $rows);
        self::assertArrayHasKey(7, $rows);
        self::assertArrayHasKey(8, $rows);
        self::assertArrayHasKey(9, $rows);
        self::assertCount(4, $rows);
    }

    #[Test]
    public function getMenuPageOverlay(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getMenu([2, 3]);
        self::assertEquals('Attrappe 1-2-5', $rows[5]['title']);
        self::assertEquals('Dummy 1-2-7', $rows[7]['title']);
        self::assertEquals('Dummy 1-3-8', $rows[8]['title']);
        self::assertEquals('Attrappe 1-3-9', $rows[9]['title']);
        self::assertCount(4, $rows);
    }

    #[Test]
    public function getMenuWithMountPoint(): void
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu([1000]);
        self::assertEquals('root default language', $rows[1003]['title']);
        self::assertEquals('1001', $rows[1003]['uid']);
        self::assertEquals('1001-1003', $rows[1003]['_MP_PARAM']);
        self::assertCount(2, $rows);
    }

    #[Test]
    public function getMenuPageOverlayWithMountPoint(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getMenu([1000]);
        self::assertEquals('root translation', $rows[1003]['title']);
        self::assertEquals('1001', $rows[1003]['uid']);
        self::assertEquals('1002', $rows[1003]['_LOCALIZED_UID']);
        self::assertEquals('1001-1003', $rows[1003]['_MP_PARAM']);
        self::assertCount(2, $rows);
    }

    #[Test]
    public function getPageOverlayById(): void
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(1, 1);
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
    }

    #[Test]
    public function getPageOverlayByIdWithoutTranslation(): void
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(4, 1);
        self::assertCount(0, $row);
    }

    #[Test]
    public function getPageOverlayByRow(): void
    {
        $subject = new PageRepository();
        $orig = $subject->getPage(1);
        $row = $subject->getPageOverlay($orig, 1);
        $this->assertOverlayRow($row);
        self::assertEquals(1, $row['uid']);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
    }

    #[Test]
    public function getPageOverlayByRowWithoutTranslation(): void
    {
        $subject = new PageRepository();
        $orig = $subject->getPage(4);
        $row = $subject->getPageOverlay($orig, 1);
        self::assertEquals(4, $row['uid']);
        self::assertEquals('Dummy 1-4', $row['title']);//original title
    }

    #[Test]
    public function getPagesOverlayByIdSingle(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([1]);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
    }

    #[Test]
    public function getPagesOverlayByIdMultiple(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([1, 5, 15]);
        self::assertCount(2, $rows);
        self::assertArrayHasKey(0, $rows);
        self::assertArrayHasKey(1, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-5', $row['title']);
        self::assertEquals('904', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
    }

    #[Test]
    public function getPagesOverlayByIdMultipleSomeNotOverlaid(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([1, 4, 5, 8]);
        self::assertCount(2, $rows);
        self::assertArrayHasKey(0, $rows);
        self::assertArrayHasKey(2, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);

        $row = $rows[2];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-5', $row['title']);
    }

    #[Test]
    public function getPagesOverlayByRowSingle(): void
    {
        $subject = new PageRepository();
        $origRow = $subject->getPage(1);

        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([$origRow]);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
        self::assertEquals(new Page($origRow), $row['_TRANSLATION_SOURCE']);
    }

    #[Test]
    public function groupRestrictedPageCanBeOverlaid(): void
    {
        $subject = new PageRepository();
        $origRow = $subject->getPage(6, true);

        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([$origRow]);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-6', $row['title']);
        self::assertEquals('905', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
    }

    #[Test]
    public function getPagesOverlayByRowMultiple(): void
    {
        $subject = new PageRepository();
        $orig1 = $subject->getPage(1);
        $orig2 = $subject->getPage(5);

        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([1 => $orig1, 5 => $orig2]);
        self::assertCount(2, $rows);
        self::assertArrayHasKey(1, $rows);
        self::assertArrayHasKey(5, $rows);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
        self::assertEquals(new Page($orig1), $row['_TRANSLATION_SOURCE']);

        $row = $rows[5];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-5', $row['title']);
        self::assertEquals('904', $row['_LOCALIZED_UID']);
        self::assertEquals(1, $row['sys_language_uid']);
        self::assertEquals(new Page($orig2), $row['_TRANSLATION_SOURCE']);
    }

    #[Test]
    public function getPagesOverlayByRowMultipleSomeNotOverlaid(): void
    {
        $subject = new PageRepository();
        $orig1 = $subject->getPage(1);
        $orig2 = $subject->getPage(7);
        $orig3 = $subject->getPage(9);

        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $rows = $subject->getPagesOverlay([$orig1, $orig2, $orig3]);
        self::assertCount(3, $rows);
        self::assertArrayHasKey(0, $rows);
        self::assertArrayHasKey(1, $rows);
        self::assertArrayHasKey(2, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals(new Page($orig1), $row['_TRANSLATION_SOURCE']);

        $row = $rows[1];
        $this->assertNotOverlayRow($row);
        self::assertEquals('Dummy 1-2-7', $row['title']);
        self::assertFalse(isset($row['_TRANSLATION_SOURCE']));

        $row = $rows[2];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-3-9', $row['title']);
        self::assertEquals(new Page($orig3), $row['_TRANSLATION_SOURCE']);
    }

    ////////////////////////////////
    // Tests concerning mountpoints
    ////////////////////////////////
    ///
    #[Test]
    public function getMountPointInfoForDefaultLanguage(): void
    {
        $subject = new PageRepository();
        $mountPointInfo = $subject->getMountPointInfo(1003);
        self::assertEquals('1001-1003', $mountPointInfo['MPvar']);
    }

    #[Test]
    public function getMountPointInfoForTranslation(): void
    {
        $mpVar = '1001-1003';
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1));
        $subject = new PageRepository($context);
        $mountPointInfo = $subject->getMountPointInfo(1003);
        self::assertEquals($mpVar, $mountPointInfo['MPvar']);

        $mountPointInfo = $subject->getMountPointInfo(1004);
        self::assertEquals($mpVar, $mountPointInfo['MPvar']);
    }

    ////////////////////////////////
    // Tests concerning workspaces
    ////////////////////////////////
    #[Test]
    public function previewShowsPagesFromLiveAndCurrentWorkspace(): void
    {
        $wsid = 987654321;
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect($wsid));
        $subject = new PageRepository($context);
        $pageRec = $subject->getPage(11);

        self::assertEquals(11, $pageRec['uid']);
        self::assertEquals(0, $pageRec['t3ver_oid']);
        self::assertEquals(987654321, $pageRec['t3ver_wsid']);
        self::assertEquals(VersionState::NEW_PLACEHOLDER->value, $pageRec['t3ver_state']);
    }

    #[Test]
    public function getWorkspaceVersionReturnsTheCorrectMethod(): void
    {
        $wsid = 987654321;
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect($wsid));
        $subject = new PageRepository($context);

        $pageRec = $subject->getWorkspaceVersionOfRecord('pages', 11);

        self::assertEquals(11, $pageRec['uid']);
        self::assertEquals(0, $pageRec['t3ver_oid']);
        self::assertEquals($wsid, $pageRec['t3ver_wsid']);
        self::assertEquals(VersionState::NEW_PLACEHOLDER->value, $pageRec['t3ver_state']);
    }

    ////////////////////////////////
    // Tests concerning versioning
    ////////////////////////////////
    #[Test]
    public function getDefaultConstraintsHidesVersionedRecordsAndPlaceholders(): void
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true,
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $subject = new PageRepository(new Context());

        $conditions = $subject->getDefaultConstraints($table);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($table);
        $expr = $connection->getExpressionBuilder();

        self::assertThat(
            (string)$expr->and(...$conditions),
            self::stringContains('((((' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)'),
            'Versioning placeholders'
        );
        self::assertThat(
            (string)$expr->and(...$conditions),
            self::stringContains('(((' . $connection->quoteIdentifier($table . '.t3ver_oid') . ' = 0) OR (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' = 4)))'),
            'Records with online version'
        );
    }

    #[Test]
    public function getDefaultConstraintsDoesNotHidePlaceholdersInPreview(): void
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true,
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(13));
        $subject = new PageRepository($context);

        $conditions = $subject->getDefaultConstraints($table);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($table);
        $expr = $connection->getExpressionBuilder();

        self::assertThat(
            (string)$expr->and(...$conditions),
            self::logicalNot(self::stringContains('(' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)')),
            'No versioning placeholders'
        );
        self::assertThat(
            (string)$expr->and(...$conditions),
            self::stringContains(' AND (((' . $connection->quoteIdentifier($table . '.t3ver_oid') . ' = 0) OR (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' = 4)))'),
            'Records from online versions'
        );
    }

    #[Test]
    public function getDefaultConstraintsDoesFilterToCurrentAndLiveWorkspaceForRecordsInPreview(): void
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true,
            ],
        ];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(2));
        $subject = new PageRepository($context);

        $conditions = $subject->getDefaultConstraints($table);
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($table);
        $expr = $connection->getExpressionBuilder();

        self::assertThat(
            (string)$expr->and(...$conditions),
            self::stringContains('((' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 0) OR (' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 2)'),
            'No versioning placeholders'
        );
    }

    protected function assertOverlayRow($row): void
    {
        self::assertIsArray($row);
        self::assertArrayHasKey('_LOCALIZED_UID', $row);
    }

    protected function assertNotOverlayRow($row): void
    {
        self::assertIsArray($row);
        self::assertFalse(isset($row['_LOCALIZED_UID']));
    }

    #[Test]
    public function getPageIdsRecursiveTest(): void
    {
        $subject = new PageRepository();
        // empty array does not do anything
        $result = $subject->getPageIdsRecursive([], 1);
        self::assertEquals([], $result);
        // pid=0 does not do anything
        $result = $subject->getPageIdsRecursive([0], 1);
        self::assertEquals([0], $result);
        // depth=0 does return given ids int-casted
        $result = $subject->getPageIdsRecursive(['1'], 0);
        self::assertEquals([1], $result);
        $result = $subject->getPageIdsRecursive([1], 1);
        self::assertEquals([1, 2, 3, 4], $result);
        $result = $subject->getPageIdsRecursive([1], 2);
        self::assertEquals([1, 2, 5, 7, 3, 8, 9, 4, 10], $result);
        $result = $subject->getPageIdsRecursive([1000], 99);
        self::assertEquals([1000, 1001], $result);
    }

    #[Test]
    public function getDescendantPageIdsRecursiveTest(): void
    {
        $subject = new PageRepository();
        // Negative numbers or "0" do not return anything
        $result = $subject->getDescendantPageIdsRecursive(-1, 1);
        self::assertEquals([], $result);
        $result = $subject->getDescendantPageIdsRecursive(0, 1);
        self::assertEquals([], $result);
        $result = $subject->getDescendantPageIdsRecursive(1, 1);
        self::assertEquals([2, 3, 4], $result);
        $result = $subject->getDescendantPageIdsRecursive(1, 2);
        self::assertEquals([2, 5, 7, 3, 8, 9, 4, 10], $result);
        // "Begin" leaves out a level
        $result = $subject->getDescendantPageIdsRecursive(1, 2, 1);
        self::assertEquals([5, 7, 8, 9, 10], $result);
        // Exclude a branch (3)
        $result = $subject->getDescendantPageIdsRecursive(1, 2, excludePageIds: [3]);
        self::assertEquals([2, 5, 7, 4, 10], $result);
        // Include Page ID 6
        $result = $subject->getDescendantPageIdsRecursive(1, 2, bypassEnableFieldsCheck: true);
        self::assertEquals([2, 5, 6, 7, 3, 8, 9, 4, 10], $result);
    }

    #[Test]
    public function getLanguageOverlayResolvesContentWithNullInValues(): void
    {
        $context = new Context();
        $context->setAspect('language', new LanguageAspect(1, 1, LanguageAspect::OVERLAYS_ON_WITH_FLOATING, [0]));
        $subject = new PageRepository($context);
        $record = $subject->getRawRecord('tt_content', 1);
        self::assertSame('Default Content #1', $record['header']);
        $overlaidRecord = $subject->getLanguageOverlay('tt_content', $record);
        self::assertSame(2, $overlaidRecord['_LOCALIZED_UID']);
        self::assertSame('Translated Content #1', $overlaidRecord['header']);

        // Check if "bodytext" is actually overlaid with a NULL value
        $record = $subject->getRawRecord('tt_content', 3);
        $overlaidRecord = $subject->getLanguageOverlay('tt_content', $record);
        self::assertSame('Translated #2', $overlaidRecord['header']);
        self::assertNull($overlaidRecord['bodytext']);
    }

    /**
     * @return array<string, array{0: array<string, int>}>
     */
    public static function invalidRowForVersionOLDataProvider(): array
    {
        return [
            'no uid and no t3ver_oid' => [[]],
            'zero uid and no t3ver_oid' => [['uid' => 0]],
            'positive uid and no t3ver_oid' => [['uid' => 1]],
            'no uid but t3ver_oid' => [['t3ver_oid' => 1]],
        ];
    }

    /**
     * @param array<string, int> $input
     */
    #[DataProvider('invalidRowForVersionOLDataProvider')]
    #[Test]
    public function versionOLForAnInvalidRowUnchangedRowData(array $input): void
    {
        $context = new Context();
        $context->setAspect('workspace', new WorkspaceAspect(4));
        $subject = new PageRepository($context);
        $originalInput = $input;

        $subject->versionOL('pages', $input);

        self::assertSame($originalInput, $input);
    }

    #[Test]
    public function modifyDefaultConstraintsForDatabaseQueryEventIsCalled(): void
    {
        $modifyDefaultConstraintsForDatabaseQueryEvent = null;
        $defaultConstraint = new CompositeExpression('foo');

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'modify-default-constraints-for-database-query-listener',
            static function (ModifyDefaultConstraintsForDatabaseQueryEvent $event) use (&$modifyDefaultConstraintsForDatabaseQueryEvent, $defaultConstraint) {
                $modifyDefaultConstraintsForDatabaseQueryEvent = $event;
                $event->setConstraints([$defaultConstraint]);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(ModifyDefaultConstraintsForDatabaseQueryEvent::class, 'modify-default-constraints-for-database-query-listener');

        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = ['ctrl' => []];
        $this->get(TcaSchemaFactory::class)->rebuild($GLOBALS['TCA']);

        $defaultConstraints = (new PageRepository(new Context()))->getDefaultConstraints($table);

        self::assertEquals([$defaultConstraint], $defaultConstraints);
        self::assertInstanceOf(ModifyDefaultConstraintsForDatabaseQueryEvent::class, $modifyDefaultConstraintsForDatabaseQueryEvent);
        self::assertEquals($table, $modifyDefaultConstraintsForDatabaseQueryEvent->getTable());
        self::assertEquals([$defaultConstraint], $modifyDefaultConstraintsForDatabaseQueryEvent->getConstraints());
    }

    #[Test]
    public function beforePageIsRetrievedEventIsCalled(): void
    {
        $pageId = 2004;
        $page = new Page(['uid' => $pageId]);
        $beforePageIsRetrievedEvent = null;

        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-page-is-retrieved-listener',
            static function (BeforePageIsRetrievedEvent $event) use (&$beforePageIsRetrievedEvent, $page, $pageId) {
                $beforePageIsRetrievedEvent = $event;
                $beforePageIsRetrievedEvent->setPageId($pageId);
                $beforePageIsRetrievedEvent->setPage($page);
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforePageIsRetrievedEvent::class, 'before-page-is-retrieved-listener');

        $result = (new PageRepository(new Context()))->getPage(1234);

        self::assertEquals($page->getPageId(), $result['uid']);
        self::assertInstanceOf(BeforePageIsRetrievedEvent::class, $beforePageIsRetrievedEvent);
        self::assertEquals($page, $beforePageIsRetrievedEvent->getPage());
        self::assertEquals($pageId, $beforePageIsRetrievedEvent->getPageId());
        self::assertTrue($beforePageIsRetrievedEvent->hasPage());
    }
}
