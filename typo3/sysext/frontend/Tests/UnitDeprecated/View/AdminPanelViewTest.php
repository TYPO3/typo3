<?php
namespace TYPO3\CMS\Frontend\Tests\UnitDeprecated\View;

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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
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

    /////////////////////////////////////////////
    // Test concerning extendAdminPanel hook
    /////////////////////////////////////////////

    /**
     * @test
     */
    public function extendAdminPanelHookThrowsExceptionIfHookClassDoesNotImplementInterface()
    {
        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionCode(1311942539);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = \TYPO3\CMS\Frontend\Tests\Unit\Fixtures\AdminPanelHookWithoutInterfaceFixture::class;
        /** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $adminPanelMock->display();
    }

    /**
     * @test
     */
    public function extendAdminPanelHookCallsExtendAdminPanelMethodOfHook()
    {
        $hookClass = $this->getUniqueId('tx_coretest');
        $hookMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelViewHookInterface::class)
            ->setMockClassName($hookClass)
            ->getMock();
        GeneralUtility::addInstance($hookClass, $hookMock);
        $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tslib/class.tslib_adminpanel.php']['extendAdminPanel'][] = $hookClass;
        /** @var $adminPanelMock \PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Frontend\View\AdminPanelView */
        $adminPanelMock = $this->getMockBuilder(\TYPO3\CMS\Frontend\View\AdminPanelView::class)
            ->setMethods(['extGetLL'])
            ->disableOriginalConstructor()
            ->getMock();
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $iconProphecy = $this->prophesize(Icon::class);
        $iconFactoryProphecy->getIcon(Argument::cetera())->willReturn($iconProphecy->reveal());
        $iconProphecy->render(Argument::cetera())->willReturn('');
        $adminPanelMock->initialize();
        $hookMock->expects($this->once())->method('extendAdminPanel')->with($this->isType('string'), $this->isInstanceOf(\TYPO3\CMS\Frontend\View\AdminPanelView::class));
        $adminPanelMock->display();
    }
}
