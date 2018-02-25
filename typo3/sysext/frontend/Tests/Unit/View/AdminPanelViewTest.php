<?php
namespace TYPO3\CMS\Frontend\Tests\Unit\View;

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;

/**
 * Test case
 */
class AdminPanelViewTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    /**
     * Subject is not notice free, disable E_NOTICES
     */
    protected static $suppressNotices = true;

    /**
     * Set up
     */
    protected function setUp()
    {
        $GLOBALS['LANG'] = $this->createMock(LanguageService::class);
        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
        $cacheFrontendProphecy = $this->prophesize(FrontendInterface::class);
        $cacheManagerProphecy->getCache('cache_pages')->willReturn($cacheFrontendProphecy->reveal());
        $GLOBALS['TSFE'] = new TypoScriptFrontendController([], 1, 1);
    }

    protected function tearDown()
    {
        GeneralUtility::purgeInstances();
        parent::tearDown();
    }

    /**
     * @test
     */
    public function extGetFeAdminValueReturnsTimestamp()
    {
        $strTime = '2013-01-01 01:00:00';
        $timestamp = strtotime($strTime);

        $backendUser = $this->getMockBuilder(\TYPO3\CMS\Core\Authentication\BackendUserAuthentication::class)->getMock();
        $backendUser->uc['TSFE_adminConfig']['preview_simulateDate'] = $timestamp;
        unset($backendUser->extAdminConfig['override.']['preview.']);
        unset($backendUser->extAdminConfig['override.']['preview']);
        $GLOBALS['BE_USER'] = $backendUser;

        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['isAdminModuleEnabled', 'isAdminModuleOpen'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminPanelMock->expects($this->any())->method('isAdminModuleEnabled')->will($this->returnValue(true));
        $adminPanelMock->expects($this->any())->method('isAdminModuleOpen')->will($this->returnValue(true));

        $timestampReturned = $adminPanelMock->extGetFeAdminValue('preview', 'simulateDate');
        $this->assertEquals($timestamp, $timestampReturned);
    }
}
