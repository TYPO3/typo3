<?php
namespace TYPO3\CMS\Frontend\Tests\Functional\Page;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\DateTimeAspect;
use TYPO3\CMS\Core\Context\LanguageAspect;
use TYPO3\CMS\Core\Context\WorkspaceAspect;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Page\PageRepository;
use TYPO3\CMS\Frontend\Page\PageRepositoryGetPageHookInterface;

/**
 * Test case
 */
class PageRepositoryTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    protected $coreExtensionsToLoad = ['frontend'];

    protected function setUp()
    {
        parent::setUp();
        $this->importDataSet(__DIR__ . '/../Fixtures/pages.xml');
    }

    /**
     * @test
     */
    public function getMenuSingleUidRoot()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(1, 'uid, title');
        $this->assertArrayHasKey(2, $rows);
        $this->assertArrayHasKey(3, $rows);
        $this->assertArrayHasKey(4, $rows);
        $this->assertCount(3, $rows);
    }

    /**
     * @test
     */
    public function getMenuSingleUidSubpage()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu(2, 'uid, title');
        $this->assertArrayHasKey(5, $rows);
        $this->assertArrayHasKey(7, $rows);
        $this->assertCount(2, $rows);
    }

    /**
     * @test
     */
    public function getMenuMultipleUid()
    {
        $subject = new PageRepository();
        $rows = $subject->getMenu([2, 3], 'uid, title');
        $this->assertArrayHasKey(5, $rows);
        $this->assertArrayHasKey(7, $rows);
        $this->assertArrayHasKey(8, $rows);
        $this->assertArrayHasKey(9, $rows);
        $this->assertCount(4, $rows);
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
        $this->assertEquals('Attrappe 1-2-5', $rows[5]['title']);
        $this->assertEquals('Dummy 1-2-7', $rows[7]['title']);
        $this->assertEquals('Dummy 1-3-8', $rows[8]['title']);
        $this->assertEquals('Attrappe 1-3-9', $rows[9]['title']);
        $this->assertCount(4, $rows);
    }

    /**
     * @test
     */
    public function getPageOverlayById()
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(1, 1);
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPageOverlayByIdWithoutTranslation()
    {
        $subject = new PageRepository();
        $row = $subject->getPageOverlay(4, 1);
        $this->assertInternalType('array', $row);
        $this->assertCount(0, $row);
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
        $this->assertEquals(1, $row['uid']);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function getPageOverlayByRowWithoutTranslation()
    {
        $subject = new PageRepository();
        $orig = $subject->getPage(4);
        $row = $subject->getPageOverlay($orig, 1);
        $this->assertInternalType('array', $row);
        $this->assertEquals(4, $row['uid']);
        $this->assertEquals('Dummy 1-4', $row['title']);//original title
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(2, $rows);
        $this->assertArrayHasKey(0, $rows);
        $this->assertArrayHasKey(1, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        $this->assertEquals('Attrappe 1-2-5', $row['title']);
        $this->assertEquals('904', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(2, $rows);
        $this->assertArrayHasKey(0, $rows);
        $this->assertArrayHasKey(2, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);

        $row = $rows[2];
        $this->assertOverlayRow($row);
        $this->assertEquals('Attrappe 1-2-5', $row['title']);
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(1, $rows);
        $this->assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
    }

    /**
     * @test
     */
    public function groupRestrictedPageCanBeOverlaid()
    {
        $subject = new PageRepository();
        $origRow = $subject->getPage(6, true);

        $subject = new PageRepository(new Context([
            'language' => new LanguageAspect(1)
        ]));
        $rows = $subject->getPagesOverlay([$origRow]);
        self::assertIsArray($rows);
        self::assertCount(1, $rows);
        self::assertArrayHasKey(0, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        self::assertEquals('Attrappe 1-2-6', $row['title']);
        self::assertEquals('905', $row['_PAGES_OVERLAY_UID']);
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(2, $rows);
        $this->assertArrayHasKey(1, $rows);
        $this->assertArrayHasKey(5, $rows);

        $row = $rows[1];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);
        $this->assertEquals('901', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);

        $row = $rows[5];
        $this->assertOverlayRow($row);
        $this->assertEquals('Attrappe 1-2-5', $row['title']);
        $this->assertEquals('904', $row['_PAGES_OVERLAY_UID']);
        $this->assertEquals(1, $row['_PAGES_OVERLAY_LANGUAGE']);
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
        $this->assertInternalType('array', $rows);
        $this->assertCount(3, $rows);
        $this->assertArrayHasKey(0, $rows);
        $this->assertArrayHasKey(1, $rows);
        $this->assertArrayHasKey(2, $rows);

        $row = $rows[0];
        $this->assertOverlayRow($row);
        $this->assertEquals('Wurzel 1', $row['title']);

        $row = $rows[1];
        $this->assertNotOverlayRow($row);
        $this->assertEquals('Dummy 1-2-7', $row['title']);

        $row = $rows[2];
        $this->assertOverlayRow($row);
        $this->assertEquals('Attrappe 1-3-9', $row['title']);
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
            ' AND (%s = 0) AND ((%s = 0) OR (%s = 2)) AND (%s < 200)',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.t3ver_wsid'),
            $connection->quoteIdentifier('pages.doktype')
        );

        $this->assertSame($expectedSQL, $subject->where_hid_del);
    }

    /**
     * @test
     */
    public function initSetsPublicPropertyCorrectlyForLive()
    {
        $subject = new PageRepository(new Context([
            'date' => new DateTimeAspect(new \DateTimeImmutable('@1451779200'))
        ]));

        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $expectedSQL = sprintf(
            ' AND ((%s = 0) AND (%s <= 0) AND (%s = 0) AND (%s <= 1451779200) AND ((%s = 0) OR (%s > 1451779200))) AND (%s < 200)',
            $connection->quoteIdentifier('pages.deleted'),
            $connection->quoteIdentifier('pages.t3ver_state'),
            $connection->quoteIdentifier('pages.hidden'),
            $connection->quoteIdentifier('pages.starttime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.endtime'),
            $connection->quoteIdentifier('pages.doktype')
        );

        $this->assertSame($expectedSQL, $subject->where_hid_del);
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

        $this->assertEquals(11, $pageRec['uid']);
        $this->assertEquals(11, $pageRec['t3ver_oid']);
        $this->assertEquals(987654321, $pageRec['t3ver_wsid']);
        $this->assertEquals(-1, $pageRec['t3ver_state']);
        $this->assertSame('First draft version', $pageRec['t3ver_label']);
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

        $this->assertEquals(12, $pageRec['uid']);
        $this->assertEquals(11, $pageRec['t3ver_oid']);
        $this->assertEquals(987654321, $pageRec['t3ver_wsid']);
        $this->assertEquals(-1, $pageRec['t3ver_state']);
        $this->assertSame('First draft version', $pageRec['t3ver_label']);
    }

    ////////////////////////////////
    // Tests concerning versioning
    ////////////////////////////////

    /**
     * @test
     */
    public function enableFieldsHidesVersionedRecordsAndPlaceholders()
    {
        $table = $this->getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true
            ]
        ];

        $subject = new PageRepository(new Context());

        $conditions = $subject->enableFields($table);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $this->assertThat(
            $conditions,
            $this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)'),
            'Versioning placeholders'
        );
        $this->assertThat(
            $conditions,
            $this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.pid') . ' <> -1)'),
            'Records from page -1'
        );
    }

    /**
     * @test
     */
    public function enableFieldsDoesNotHidePlaceholdersInPreview()
    {
        $table = $this->getUniqueId('aTable');
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

        $this->assertThat(
            $conditions,
            $this->logicalNot($this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)')),
            'No versioning placeholders'
        );
        $this->assertThat(
            $conditions,
            $this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.pid') . ' <> -1)'),
            'Records from page -1'
        );
    }

    /**
     * @test
     */
    public function enableFieldsDoesFilterToCurrentAndLiveWorkspaceForRecordsInPreview()
    {
        $table = $this->getUniqueId('aTable');
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

        $this->assertThat(
            $conditions,
            $this->stringContains(' AND ((' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 0) OR (' . $connection->quoteIdentifier($table . '.t3ver_wsid') . ' = 2))'),
            'No versioning placeholders'
        );
    }

    /**
     * @test
     */
    public function enableFieldsDoesNotHideVersionedRecordsWhenCheckingVersionOverlays()
    {
        $table = $this->getUniqueId('aTable');
        $GLOBALS['TCA'][$table] = [
            'ctrl' => [
                'versioningWS' => true
            ]
        ];

        $subject = new PageRepository(new Context([
            'workspace' => new WorkspaceAspect(23)
        ]));

        $conditions = $subject->enableFields($table, -1, [], true);
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);

        $this->assertThat(
            $conditions,
            $this->logicalNot($this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.t3ver_state') . ' <= 0)')),
            'No versioning placeholders'
        );
        $this->assertThat(
            $conditions,
            $this->logicalNot($this->stringContains(' AND (' . $connection->quoteIdentifier($table . '.pid') . ' <> -1)')),
            'No records from page -1'
        );
    }

    protected function assertOverlayRow($row)
    {
        $this->assertInternalType('array', $row);

        $this->assertArrayHasKey('_PAGES_OVERLAY', $row);
        $this->assertArrayHasKey('_PAGES_OVERLAY_UID', $row);
        $this->assertArrayHasKey('_PAGES_OVERLAY_LANGUAGE', $row);

        $this->assertTrue($row['_PAGES_OVERLAY']);
    }

    protected function assertNotOverlayRow($row)
    {
        $this->assertInternalType('array', $row);

        $this->assertFalse(isset($row['_PAGES_OVERLAY']));
        $this->assertFalse(isset($row['_PAGES_OVERLAY_UID']));
        $this->assertFalse(isset($row['_PAGES_OVERLAY_LANGUAGE']));
    }
}
