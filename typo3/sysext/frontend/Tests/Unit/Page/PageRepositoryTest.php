<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\Page;

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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Test case
 */
class PageRepositoryTest extends \TYPO3\CMS\Core\Tests\UnitTestCase
{
    /**
     * @var \TYPO3\CMS\Frontend\Page\PageRepository|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface
     */
    protected $pageSelectObject;

    protected $defaultTcaForPages = array(
        'ctrl' => array(
            'label' => 'title',
            'tstamp' => 'tstamp',
            'sortby' => 'sorting',
            'type' => 'doktype',
            'versioningWS' => true,
            'origUid' => 't3_origuid',
            'delete' => 'deleted',
            'enablecolumns' => array(
                'disabled' => 'hidden',
                'starttime' => 'starttime',
                'endtime' => 'endtime',
                'fe_group' => 'fe_group'
            ),
        ),
        'columns' => array()
    );

    /**
     * Sets up this testcase
     */
    protected function setUp()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMockBuilder(\TYPO3\CMS\Core\Database\DatabaseConnection::class)
            ->setMethods(array('exec_SELECTquery', 'sql_fetch_assoc', 'sql_free_result', 'exec_SELECTgetSingleRow'))
            ->getMock();
        $this->pageSelectObject = $this->getAccessibleMock(\TYPO3\CMS\Frontend\Page\PageRepository::class, array('getMultipleGroupsWhereClause'));
        $this->pageSelectObject->expects($this->any())->method('getMultipleGroupsWhereClause')->will($this->returnValue(' AND 1=1'));
    }

    /**
     * Tests whether the getPage Hook is called correctly.
     *
     * @test
     */
    public function isGetPageHookCalled()
    {
        // Create a hook mock object
        $className = $this->getUniqueId('tx_coretest');
        $getPageHookMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\Page\PageRepositoryGetPageHookInterface::class)
            ->setMethods(array('getPage_preProcess'))
            ->setMockClassName($className)
            ->getMock();
        // Register hook mock object
        GeneralUtility::addInstance($className, $getPageHookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_page.php']['getPage'][] = $className;
        // Test if hook is called and register a callback method to check given arguments
        $getPageHookMock->expects($this->once())->method('getPage_preProcess')->will($this->returnCallback(array($this, 'isGetPagePreProcessCalledCallback')));
        $this->pageSelectObject->getPage(42, false);
    }

    /**
     * Handles the arguments that have been sent to the getPage_preProcess hook
     *
     * @param int $uid
     * @param $disableGroupAccessCheck
     * @param \TYPO3\CMS\Frontend\Page\PageRepository $parent
     */
    public function isGetPagePreProcessCalledCallback($uid, $disableGroupAccessCheck, $parent)
    {
        $this->assertEquals(42, $uid);
        $this->assertFalse($disableGroupAccessCheck);
        $this->assertTrue($parent instanceof \TYPO3\CMS\Frontend\Page\PageRepository);
    }

    /////////////////////////////////////////
    // Tests concerning getPathFromRootline
    /////////////////////////////////////////
    /**
     * @test
     */
    public function getPathFromRootLineForEmptyRootLineReturnsEmptyString()
    {
        $this->assertEquals('', $this->pageSelectObject->getPathFromRootline(array()));
    }

    ///////////////////////////////
    // Tests concerning getExtURL
    ///////////////////////////////
    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType1AddsHttpSchemeToUrl()
    {
        $this->assertEquals('http://www.example.com', $this->pageSelectObject->getExtURL(array(
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 1,
            'url' => 'www.example.com'
        )));
    }

    /**
     * @test
     */
    public function getExtUrlForDokType3AndUrlType0PrependsSiteUrl()
    {
        $this->assertEquals(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('TYPO3_SITE_URL') . 'hello/world/', $this->pageSelectObject->getExtURL(array(
            'doktype' => \TYPO3\CMS\Frontend\Page\PageRepository::DOKTYPE_LINK,
            'urltype' => 0,
            'url' => 'hello/world/'
        )));
    }

    /////////////////////////////////////////
    // Tests concerning shouldFieldBeOverlaid
    /////////////////////////////////////////
    /**
     * @test
     * @dataProvider getShouldFieldBeOverlaidData
     */
    public function shouldFieldBeOverlaid($field, $table, $value, $expected, $comment = '')
    {
        $GLOBALS['TCA']['fake_table']['columns'] = array(
            'exclude' => array(
                'l10n_mode' => 'exclude',
                'config' => array('type' => 'input'),
            ),
            'mergeIfNotBlank' => array(
                'l10n_mode' => 'mergeIfNotBlank',
                'config' => array('type' => 'input'),
            ),
            'mergeIfNotBlank_group' => array(
                'l10n_mode' => 'mergeIfNotBlank',
                'config' => array('type' => 'group'),
            ),
            'default' => array(
                // no l10n_mode set
                'config' => array('type' => 'input'),
            ),
            'noCopy' => array(
                'l10n_mode' => 'noCopy',
                'config' => array('type' => 'input'),
            ),
            'prefixLangTitle' => array(
                'l10n_mode' => 'prefixLangTitle',
                'config' => array('type' => 'input'),
            ),
        );

        $result = $this->pageSelectObject->_call('shouldFieldBeOverlaid', $table, $field, $value);
        unset($GLOBALS['TCA']['fake_table']);

        $this->assertSame($expected, $result, $comment);
    }

    /**
     * Data provider for shouldFieldBeOverlaid
     */
    public function getShouldFieldBeOverlaidData()
    {
        return array(
            array('default',               'fake_table', 'foobar', true,  'default is to merge non-empty string'),
            array('default',               'fake_table', '',       true,  'default is to merge empty string'),

            array('exclude',               'fake_table', '',       false, 'exclude field with empty string'),
            array('exclude',               'fake_table', 'foobar', false, 'exclude field with non-empty string'),

            array('mergeIfNotBlank',       'fake_table', '',       false, 'mergeIfNotBlank is not merged with empty string'),
            array('mergeIfNotBlank',       'fake_table', 0,        true,  'mergeIfNotBlank is merged with 0'),
            array('mergeIfNotBlank',       'fake_table', '0',      true,  'mergeIfNotBlank is merged with "0"'),
            array('mergeIfNotBlank',       'fake_table', 'foobar', true,  'mergeIfNotBlank is merged with non-empty string'),

            array('mergeIfNotBlank_group', 'fake_table', '',       false, 'mergeIfNotBlank on group is not merged empty string'),
            array('mergeIfNotBlank_group', 'fake_table', 0,        false, 'mergeIfNotBlank on group is not merged with 0'),
            array('mergeIfNotBlank_group', 'fake_table', '0',      false, 'mergeIfNotBlank on group is not merged with "0"'),
            array('mergeIfNotBlank_group', 'fake_table', 'foobar', true,  'mergeIfNotBlank on group is merged with non-empty string'),

            array('noCopy',                'fake_table', 'foobar', true,  'noCopy is merged with non-empty string'),
            array('noCopy',                'fake_table', '',       true,  'noCopy is merged with empty string'),

            array('prefixLangTitle',       'fake_table', 'foobar', true,  'prefixLangTitle is merged with non-empty string'),
            array('prefixLangTitle',       'fake_table', '',       true,  'prefixLangTitle is merged with empty string'),
        );
    }

    ////////////////////////////////
    // Tests concerning workspaces
    ////////////////////////////////

    /**
     * @test
     */
    public function noPagesFromWorkspaceAreShownLive()
    {
        // initialization
        $wsid = 987654321;
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages
        );

        // simulate calls from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fetch_the_id()
        $this->pageSelectObject->versioningPreview = false;
        $this->pageSelectObject->versioningWorkspaceId = $wsid;
        $this->pageSelectObject->init(false);

        // check SQL created by \TYPO3\CMS\Frontend\Page\PageRepository->getPage()
        $GLOBALS['TYPO3_DB']->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with(
            '*',
            'pages',
            $this->logicalAnd(
                $this->logicalNot(
                    $this->stringContains('(pages.t3ver_wsid=0 or pages.t3ver_wsid=' . $wsid . ')')
                ),
                $this->stringContains('AND pages.t3ver_state<=0')
            )
        );

        $this->pageSelectObject->getPage(1);
    }

    /**
     * @test
     */
    public function previewShowsPagesFromLiveAndCurrentWorkspace()
    {
        // initialization
        $wsid = 987654321;
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages
        );

        // simulate calls from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fetch_the_id()
        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->versioningWorkspaceId = $wsid;
        $this->pageSelectObject->init(false);

        // check SQL created by \TYPO3\CMS\Frontend\Page\PageRepository->getPage()
        $GLOBALS['TYPO3_DB']->expects($this->once())
            ->method('exec_SELECTgetSingleRow')
            ->with(
            '*',
            'pages',
            $this->stringContains('(pages.t3ver_wsid=0 or pages.t3ver_wsid=' . $wsid . ')')
        );

        $this->pageSelectObject->getPage(1);
    }

    /**
     * @test
     */
    public function getWorkspaceVersionReturnsTheCorrectMethod()
    {
        // initialization
        $wsid = 987654321;
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages
        );
        $GLOBALS['SIM_ACCESS_TIME'] = 123;

        // simulate calls from \TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController->fetch_the_id()
        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->versioningWorkspaceId = $wsid;
        $this->pageSelectObject->init(false);

        $GLOBALS['TYPO3_DB']->expects($this->at(0))
            ->method('exec_SELECTgetSingleRow')
            ->with(
            '*',
            'pages',
            $this->logicalAnd(
                $this->stringContains('pid=-1 AND'),
                $this->stringContains('t3ver_oid=1 AND'),
                $this->stringContains('t3ver_wsid=' . $wsid . ' AND pages.deleted=0')
            )
        )->willReturn(array('uid' => 1));
        $GLOBALS['TYPO3_DB']->expects($this->at(1))
            ->method('exec_SELECTgetSingleRow')
            ->with(
            'uid',
            'pages',
            $this->logicalAnd(
                $this->stringContains('t3ver_wsid=' . $wsid . ' AND pages.deleted=0 AND pages.hidden=0 AND pages.starttime<=123 AND (pages.endtime=0 OR pages.endtime>123) AND 1=1')
            )
        );
        $this->pageSelectObject->getWorkspaceVersionOfRecord($wsid, 'pages', 1);
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
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
            $table => array(
                'ctrl' => array(
                    'versioningWS' => true
                )
            )
        );

        $this->pageSelectObject->versioningPreview = false;
        $this->pageSelectObject->init(false);

        $conditions = $this->pageSelectObject->enableFields($table);

        $this->assertThat($conditions, $this->stringContains(' AND ' . $table . '.t3ver_state<=0'), 'Versioning placeholders');
        $this->assertThat($conditions, $this->stringContains(' AND ' . $table . '.pid<>-1'), 'Records from page -1');
    }

    /**
     * @test
     */
    public function enableFieldsDoesNotHidePlaceholdersInPreview()
    {
        $table = $this->getUniqueId('aTable');
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
            $table => array(
                'ctrl' => array(
                    'versioningWS' => true
                )
            )
        );

        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->init(false);

        $conditions = $this->pageSelectObject->enableFields($table);

        $this->assertThat($conditions, $this->logicalNot($this->stringContains(' AND ' . $table . '.t3ver_state<=0')), 'No versioning placeholders');
        $this->assertThat($conditions, $this->stringContains(' AND ' . $table . '.pid<>-1'), 'Records from page -1');
    }

    /**
     * @test
     */
    public function enableFieldsDoesFilterToCurrentAndLiveWorkspaceForRecordsInPreview()
    {
        $table = $this->getUniqueId('aTable');
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
            $table => array(
                'ctrl' => array(
                    'versioningWS' => true
                )
            )
        );

        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->versioningWorkspaceId = 2;
        $this->pageSelectObject->init(false);

        $conditions = $this->pageSelectObject->enableFields($table);

        $this->assertThat($conditions, $this->stringContains(' AND (' . $table . '.t3ver_wsid=0 OR ' . $table . '.t3ver_wsid=2)'), 'No versioning placeholders');
    }

    /**
     * @test
     */
    public function initSetsPublicPropertyCorrectlyForWorkspacePreview()
    {
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
        );

        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->versioningWorkspaceId = 2;
        $this->pageSelectObject->init(false);

        $this->assertSame(' AND pages.deleted=0 AND (pages.t3ver_wsid=0 OR pages.t3ver_wsid=2)', $this->pageSelectObject->where_hid_del);
    }

    /**
     * @test
     */
    public function initSetsPublicPropertyCorrectlyForLive()
    {
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
        );
        $GLOBALS['SIM_ACCESS_TIME'] = 123;
        $this->pageSelectObject->versioningPreview = false;
        $this->pageSelectObject->versioningWorkspaceId = 0;
        $this->pageSelectObject->init(false);

        $this->assertSame(' AND pages.deleted=0 AND pages.t3ver_state<=0 AND pages.hidden=0 AND pages.starttime<=123 AND (pages.endtime=0 OR pages.endtime>123)', $this->pageSelectObject->where_hid_del);
    }

    /**
     * @test
     */
    public function enableFieldsDoesNotHideVersionedRecordsWhenCheckingVersionOverlays()
    {
        $table = $this->getUniqueId('aTable');
        $GLOBALS['TCA'] = array(
            'pages' => $this->defaultTcaForPages,
            $table => array(
                'ctrl' => array(
                    'versioningWS' => true
                )
            )
        );

        $this->pageSelectObject->versioningPreview = true;
        $this->pageSelectObject->init(false);

        $conditions = $this->pageSelectObject->enableFields($table, -1, array(), true);

        $this->assertThat($conditions, $this->logicalNot($this->stringContains(' AND ' . $table . '.t3ver_state<=0')), 'No versioning placeholders');
        $this->assertThat($conditions, $this->logicalNot($this->stringContains(' AND ' . $table . '.pid<>-1')), 'No ecords from page -1');
    }
}
