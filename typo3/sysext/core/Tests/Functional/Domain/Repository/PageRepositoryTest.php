<?php

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Domain\Repository\PageRepositoryGetPageHookInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class PageRepositoryTest extends FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['frontend'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../../Fixtures/pages.xml');
    }

    /**
     * @test
     */
    public function getMenuSingleUidRoot()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(1, 'uid, title');
        self::assertArrayHasKey(2, $rows);
        self::assertArrayHasKey(3, $rows);
        self::assertArrayHasKey(4, $rows);
        self::assertCount(3, $rows);
    }

    /**
     * @test
     */
    public function getMenuSingleUidSubpage()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(2, 'uid, title');
        self::assertArrayHasKey(5, $rows);
        self::assertArrayHasKey(6, $rows);
        self::assertArrayHasKey(7, $rows);
        self::assertCount(3, $rows);
    }

    /**
     * @test
     */
    public function getMenuMultipleUid()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu([2, 3], 'uid, title');
        self::assertArrayHasKey(5, $rows);
        self::assertArrayHasKey(6, $rows);
        self::assertArrayHasKey(7, $rows);
        self::assertArrayHasKey(8, $rows);
        self::assertArrayHasKey(9, $rows);
        self::assertCount(5, $rows);
    }

    /**
     * @test
     */
    public function getMenuPageOverlay()
    {
        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));

        $rows = $subject->getMenu([2, 3], 'uid, title');
        self::assertEquals('Attrappe 1-2-5', $rows[5]['title']);
        self::assertEquals('Attrappe 1-2-6', $rows[6]['title']);
        self::assertEquals('Dummy 1-2-7', $rows[7]['title']);
        self::assertEquals('Dummy 1-3-8', $rows[8]['title']);
        self::assertEquals('Attrappe 1-3-9', $rows[9]['title']);
        self::assertCount(5, $rows);
    }

    /**
     * @test
     */
    public function getPageOverlayById()
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(1, 1);
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPageOverlayByIdWithoutTranslation()
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(4, 1);
        self::assertIsArray($row);
        self::assertCount(0, $row);
    }

    /**
     * @test
     */
    public function getPageOverlayByRow()
    {
        $subject = new PageRepository();
        $orig = $subject->getPage(1);
        $row = $subject->getPageOverlay($orig, 1);
        $this->assertOverlayRow($row);
        self::assertEquals(1, $row['uid']);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPageOverlayByRowWithoutTranslation()
    {
        $subject = new PageRepository();
        $orig = $subject->getPage(4);
        $row = $subject->getPageOverlay($orig, 1);
        self::assertIsArray($row);
        self::assertEquals(4, $row['uid']);
        self::assertEquals('Dummy 1-4', $row['title']);//original title
    }

    /**
     * @test
     */
    public function getPagesOverlayByIdSingle()
    {
        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([1]);
        self::assertIsArray($rows);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPagesOverlayByIdMultiple()
    {
        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([1, 5]);
        self::assertIsArray($rows);
        self::assertCount(2, $rows);
        self::assertArrayHasKey(0, $rows);
        self::assertArrayHasKey(1, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-5', $row['title']);
        self::assertEquals('904', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPagesOverlayByIdMultipleSomeNotOverlaid()
    {
        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([1, 4, 5, 8]);
        self::assertIsArray($rows);
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

    /**
     * @test
     */
    public function getPagesOverlayByRowSingle()
    {
        $subject = new PageRepository();
        $origRow = $subject->getPage(1);

        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([$origRow]);
        self::assertIsArray($rows);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPagesOverlayByRowMultiple()
    {
        $subject = new PageRepository();
        $orig1 = $subject->getPage(1);
        $orig2 = $subject->getPage(5);

        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([1 => $orig1, 5 => $orig2]);
        self::assertIsArray($rows);
        self::assertCount(2, $rows);
        self::assertArrayHasKey(1, $rows);
        self::assertArrayHasKey(5, $rows);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);
        self::assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);

        $row = $rows[5];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-5', $row['title']);
        self::assertEquals('904', $row['_PAGES_OVERLAY_UID']);
        self::assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPagesOverlayByRowMultipleSomeNotOverlaid()
    {
        $subject = new PageRepository();
        $orig1 = $subject->getPage(1);
        $orig2 = $subject->getPage(7);
        $orig3 = $subject->getPage(9);

        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([$orig1, $orig2, $orig3]);
        self::assertIsArray($rows);
        self::assertCount(3, $rows);
        self::assertArrayHasKey(0, $rows);
        self::assertArrayHasKey(1, $rows);
        self::assertArrayHasKey(2, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Wurzel 1', $row['title']);

        $row = $rows[1];
        $this->assertNotOverlayRow($row);
        self::assertEquals('Dummy 1-2-7', $row['title']);

        $row = $rows[2];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-3-9', $row['title']);
    }

    /**
     * Tests whether the getPage Hook is called correctly.
     *
     * @test
     */
    public function isGetPageHookCalled()
    {
        // Create a hook mock object
        $getPageHookProphet = $this->prophesize(\stdClass::class);
        $getPageHookProphet->willImplement(PageRepositoryGetPageHookInterface::class);
        $getPageHookProphet->getPage_preProcess(42, false, Argument::type(PageRepository::class))->shouldBeCalled();
        $getPageHookMock = $getPageHookProphet->reveal();
        $className = get_class($getPageHookMock);

        // Register hook mock object
        GeneralUtility::addInstance($className, $getPageHookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'][] = $className;
        $subject = new PageRepository();
        $subject->getPage(42, false);
    }

    /**
     * @test
     */
    public function initSetsPublicPropertyCorrectlyForWorkspacePreview()
    {
        $workspaceId = 2;
        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect($workspaceId)
        ]));

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');

        $expectedSQL = sprintf(
            ' AND (%s = 0) AND ((%s = 0) OR (%s = 2)) AND (%s <> 255)',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.doktype')
        );

        self::assertSame($expectedSQL, $subject->where_hid_del);
    }

    /**
     * @test
     */
    public function initSetsEnableFieldsCorrectlyForLive(): void
    {
        $subject = new PageRepository(new Context([
            'date' => new DateTimeAspect(new \DateTimeImmutable('@1451779200'))
        ]));

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $expectedSQL = sprintf(
            ' AND ((%s = 0) AND (%s <= 0) AND (%s = 0) AND (%s = 0) AND (%s = 0) AND (%s <= 1451779200) AND ((%s = 0) OR (%s > 1451779200))) AND (%s <> 255)',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_state'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.t3ver_oid'),
            $connection->quoteIdentifier('pages.hidden'),
            $connection->quoteIdentifier('pages.starttime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.doktype')
        );

        self::assertSame($expectedSQL, $subject->where_hid_del);
    }

    ////////////////////////////////
    // Tests concerning workspaces
    ////////////////////////////////

    /**
     * @test
     */
    public function previewShowsPagesFromLiveAndCurrentWorkspace()
    {
        // initialization
        $wsid = 987654321;
        // simulate calls from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fetch_the_id()
        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect($wsid)
        ]));

        $pageRec = $subject->getPage(11);

        self::assertEquals(11, $pageRec['uid']);
        self::assertEquals(11, $pageRec['t3ver_oid']);
        self::assertEquals(987654321, $pageRec['t3ver_wsid']);
        self::assertEquals(-1, $pageRec['t3ver_state']);
    }

    /**
     * @test
     */
    public function getWorkspaceVersionReturnsTheCorrectMethod()
    {
        // initialization
        $wsid = 987654321;

        // simulate calls from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fetch_the_id()
        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect($wsid)
        ]));

        $pageRec = $subject->getWorkspaceVersionOfRecord($wsid, 'pages', 11);

        self::assertEquals(12, $pageRec['uid']);
        self::assertEquals(11, $pageRec['t3ver_oid']);
        self::assertEquals(987654321, $pageRec['t3ver_wsid']);
        self::assertEquals(-1, $pageRec['t3ver_state']);
    }

    ////////////////////////////////
    // Tests concerning versioning
    ////////////////////////////////

    /**
     * @test
     */
    public function enableFieldsHidesVersionedRecordsAndPlaceholders()
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true
            ]
        ];

        $subject = new PageRepository(new Context());

        $conditions = $subject->enableFields($table);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        self::assertThat(
            $conditions,
            self::stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)'),
            'Versioning placeholders'
        );
        self::assertThat(
            $conditions,
            self::stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_oid') . ' = 0)'),
            'Records with online version'
        );
    }

    /**
     * @test
     */
    public function enableFieldsDoesNotHidePlaceholdersInPreview()
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true
            ]
        ];

        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect(13)
        ]));

        $conditions = $subject->enableFields($table);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        self::assertThat(
            $conditions,
            self::logicalNot(self::stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)')),
            'No versioning placeholders'
        );
        self::assertThat(
            $conditions,
            self::stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_oid') . ' = 0)'),
            'Records from online versions'
        );
    }

    /**
     * @test
     */
    public function enableFieldsDoesFilterToCurrentAndLiveWorkspaceForRecordsInPreview()
    {
        $table = StringUtility::getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true
            ]
        ];

        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect(2)
        ]));

        $conditions = $subject->enableFields($table);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        self::assertThat(
            $conditions,
            self::stringContains(' AND ((' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 0) OR (' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 2))'),
            'No versioning placeholders'
        );
    }

    protected function assertOverlayRow($row)
    {
        self::assertIsArray($row);

        self::assertArrayHasKey('_PAGES_OVERLAY', $row);
        self::assertArrayHasKey('_PAGES_OVERLAY_UID', $row);
        self::assertArrayHasKey('_PAGES_OVERLAY_LANGUAGE', $row);

        self::assertTrue($row['_PAGES_OVERLAY']);
    }

    protected function assertNotOverlayRow($row)
    {
        self::assertIsArray($row);

        self::assertFalse(isset($row['_PAGES_OVERLAY']));
        self::assertFalse(isset($row['_PAGES_OVERLAY_UID']));
        self::assertFalse(isset($row['_PAGES_OVERLAY_LANGUAGE']));
    }
}
