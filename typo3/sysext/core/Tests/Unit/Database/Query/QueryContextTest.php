<?php
declare (strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Unit\Database\Query;

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

use Prophecy\Prophecy\ObjectProphecy;
use TYPO3\CMS\Core\Database\Query\QueryContext;
use TYPO3\CMS\Core\Tests\UnitTestCase;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;

class QueryContextTest extends UnitTestCase
{
    /**
     * @var QueryContext
     */
    protected $subject;

    /**
     * @var TypoScriptFrontendController|ObjectProphecy
     */
    protected $typoScriptFrontendController;

    /**
     * Create a new database connection mock object for every test.
     *
     * @return void
     */
    protected function setUp()
    {
        parent::setUp();

        $this->typoScriptFrontendController = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $this->typoScriptFrontendController->reveal();

        $this->subject = GeneralUtility::makeInstance(QueryContext::class);
    }

    /**
     * @test
     */
    public function contextCanBeSetByConstructiorArgument()
    {
        $subject = GeneralUtility::makeInstance(QueryContext::class, 'FRONTEND');

        $this->assertSame('FRONTEND', $subject->getContext());
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     * @expectedExceptionMessage Invalid value DUMMY for TYPO3\CMS\Core\Database\Query\QueryContextType
     */
    public function unknownContextThrowExceptionInConstructor()
    {
        GeneralUtility::makeInstance(QueryContext::class, 'DUMMY');
    }

    /**
     * @test
     * @expectedException \TYPO3\CMS\Core\Type\Exception\InvalidEnumerationValueException
     * @expectedExceptionMessage Invalid value DUMMY for TYPO3\CMS\Core\Database\Query\QueryContextType
     */
    public function unknownContextThrowExceptionWhenSet()
    {
        $this->subject->setContext('DUMMY');
    }

    /**
     * @test
     */
    public function getMemberGroupsPrefersExplicitlySetInformation()
    {
        $GLOBALS['TSFE']->gr_list = '3,5';
        $this->subject->setMemberGroups([1, 2]);

        $this->assertSame([1, 2], $this->subject->getMemberGroups());
    }

    /**
     * @test
     */
    public function getMemberGroupsFallsBackToTSFE()
    {
        $GLOBALS['TSFE']->gr_list = '3,5';

        $this->assertSame([3, 5], $this->subject->getMemberGroups());
    }

    /**
     * @test
     */
    public function getCurrentWorkspacePrefersExplicitlySetInformation()
    {
        /** @var PageRepository|ObjectProphecy $pageRepository */
        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->versioningWorkspaceId = 3;

        $GLOBALS['TSFE']->sys_page = $pageRepository->reveal();

        $this->subject->setCurrentWorkspace(1);
        $this->subject->setContext('FRONTEND');

        $this->assertSame(1, $this->subject->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getCurrentWorkspaceFallsBackToTSFE()
    {
        /** @var PageRepository|ObjectProphecy $pageRepository */
        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->versioningWorkspaceId = 3;

        $GLOBALS['TSFE']->sys_page = $pageRepository->reveal();

        $this->subject->setContext('FRONTEND');

        $this->assertSame(3, $this->subject->getCurrentWorkspace());
    }

    /**
     * @test
     */
    public function getAccessTimePrefersExplicitlySetInformation()
    {
        $GLOBALS['SIM_ACCESS_TIME'] = 100;
        $this->subject->setAccessTime(200);

        $this->assertSame(200, $this->subject->getAccessTime());
    }

    /**
     * @test
     */
    public function getAccessTimeFallsBackToTSFE()
    {
        $GLOBALS['SIM_ACCESS_TIME'] = 100;

        $this->assertSame(100, $this->subject->getAccessTime());
    }

    /**
     * @test
     */
    public function getIncludeHiddenForTablePrefersExplicitlySetInformation()
    {
        $GLOBALS['TSFE']->showHiddenPage = false;
        $GLOBALS['TSFE']->showHiddenRecords = false;
        $this->subject->setIncludeHidden(true);

        $this->assertSame(true, $this->subject->getIncludeHiddenForTable('pages'));
    }

    /**
     * @test
     */
    public function getIncludeHiddenForTablePagesFallsBackToTSFE()
    {
        $GLOBALS['TSFE']->showHiddenPage = true;

        $this->assertSame(true, $this->subject->getIncludeHiddenForTable('pages'));
    }

    /**
     * @test
     */
    public function getIncludeHiddenForTablePagesLanguageOverlayFallsBackToTSFE()
    {
        $GLOBALS['TSFE']->showHiddenPage = true;

        $this->assertSame(true, $this->subject->getIncludeHiddenForTable('pages'));
    }

    /**
     * @test
     */
    public function getIncludeHiddenForRecordsFallsBackToTSFE()
    {
        $GLOBALS['TSFE']->showHiddenRecords = true;

        $this->assertSame(true, $this->subject->getIncludeHiddenForTable('tt_content'));
    }

    /**
     * @test
     */
    public function getIncludePlaceholdersPrefersExplicitlySetInformation()
    {
        $this->subject->setIncludePlaceholders(true);

        $this->assertSame(true, $this->subject->getIncludePlaceholders());
    }

    /**
     * @test
     */
    public function getIncludePlaceholdersFallsBackToTSFE()
    {
        /** @var PageRepository|ObjectProphecy $pageRepository */
        $pageRepository = $this->prophesize(PageRepository::class);
        $pageRepository->versioningPreview = true;

        $GLOBALS['TSFE']->sys_page = $pageRepository->reveal();

        $this->subject->setContext('FRONTEND');
        $this->assertSame(true, $this->subject->getIncludePlaceholders());
    }

    /**
     * @test
     */
    public function getIgnoredEnableFieldsForTableFallsBackToGlobalList()
    {
        $this->subject->setIgnoredEnableFields(['disabled']);

        $this->assertSame(['disabled'], $this->subject->getIgnoredEnableFieldsForTable('pages'));
    }

    /**
     * @test
     */
    public function getIgnoredEnableFieldsForTablePrefersExplictlySetInformation()
    {
        $this->subject->setIgnoredEnableFields(['disabled']);
        $this->subject->setIgnoredEnableFieldsForTable('pages', ['starttime', 'endtime']);

        $this->assertSame(['starttime', 'endtime'], $this->subject->getIgnoredEnableFieldsForTable('pages'));
    }

    /**
     * @test
     */
    public function getIgnoredEnableFieldsForTableReturnsEmptyArrayWithoutInformation()
    {
        $this->assertSame([], $this->subject->getIgnoredEnableFieldsForTable('pages'));
    }

    /**
     * @test
     */
    public function getTableConfigPrefersExplicitlySetInformation()
    {
        $this->subject->setTableConfigs(['pages' => ['delete' => 'deleted']]);
        $GLOBALS['TCA']['pages']['ctrl'] = ['delete' => 'deleted'];

        $this->assertSame(['delete' => 'deleted'], $this->subject->getTableConfig('pages'));
    }

    /**
     * @test
     */
    public function getTableConfigFallsBackToTCA()
    {
        $GLOBALS['TCA']['pages']['ctrl'] = [
            'label' => 'title',
            'tstamp' => 'tstamp',
            'delete' => 'deleted',
            'enablecolumns' => [
                'disabled' => 'hidden',
            ],
        ];

        $this->assertSame(
            ['delete' => 'deleted', 'enablecolumns' => ['disabled' => 'hidden']],
            $this->subject->getTableConfig('pages')
        );
    }
}
