<?php
declare(strict_types=1);
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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Tests\Unit\View\Fixtures\AdminPanelDisabledModuleFixture;
use TYPO3\CMS\Frontend\Tests\Unit\View\Fixtures\AdminPanelEnabledShownOnSubmitInitializeModuleFixture;
use TYPO3\CMS\Frontend\View\AdminPanelView;

/**
 * Test case
 */
class AdminPanelViewTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
{
    public function setUp()
    {
        parent::setUp();
        $iconFactoryProphecy = $this->prophesize(IconFactory::class);
        GeneralUtility::addInstance(IconFactory::class, $iconFactoryProphecy->reveal());
        $beUserProphecy = $this->prophesize(BackendUserAuthentication::class);
        $GLOBALS['BE_USER'] = $beUserProphecy->reveal();
    }

    /**
     * @test
     */
    public function initializeCallsOnSubmitIfInputVarsAreSet()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['frontend']['adminPanelModules'] = [
            'fixtureOnSubmit' => [
                'module' => AdminPanelEnabledShownOnSubmitInitializeModuleFixture::class
            ]
        ];

        $postVars = ['preview_showFluidDebug' => '1'];
        $_GET['TSFE_ADMIN_PANEL'] = $postVars;

        $this->expectExceptionCode('1519997815');

        new AdminPanelView();
    }

    /**
     * @test
     */
    public function initializeCallsInitializeModulesForEnabledModules()
    {
        $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['frontend']['adminPanelModules'] = [
            'enabledModule' => [
                'module' => AdminPanelEnabledShownOnSubmitInitializeModuleFixture::class
            ],
            'disabledModule' => [
                'module' => AdminPanelDisabledModuleFixture::class,
                'before' => ['enabledModule']
            ]
        ];

        $this->expectExceptionCode(1519999273);
        new AdminPanelView();
    }
}
