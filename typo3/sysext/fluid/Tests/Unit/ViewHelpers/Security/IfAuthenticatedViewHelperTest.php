<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Security;

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

use TYPO3\CMS\Fluid\ViewHelpers\Security\IfAuthenticatedViewHelper;
use TYPO3\TestingFramework\Fluid\Unit\ViewHelpers\ViewHelperBaseTestcase;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Testcase for security.ifAuthenticated view helper
 */
class IfAuthenticatedViewHelperTest extends ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Security\IfAuthenticatedViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $this->viewHelper = new IfAuthenticatedViewHelper();
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    protected function tearDown()
    {
        unset($GLOBALS['TSFE']);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfFeUserIsLoggedIn()
    {
        $GLOBALS['TSFE']->loginUser = 1;

        $actualResult = $this->viewHelper->renderStatic(
            ['then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        $this->assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfFeUserIsNotLoggedIn()
    {
        $GLOBALS['TSFE']->loginUser = 0;

        $actualResult = $this->viewHelper->renderStatic(
            ['then' => 'then child', 'else' => 'else child'],
            function () {
            },
            $this->prophesize(RenderingContextInterface::class)->reveal()
        );

        $this->assertEquals('else child', $actualResult);
    }
}
