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

/**
 * Testcase for security.ifAuthenticated view helper
 */
class IfAuthenticatedViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Security\IfAuthenticatedViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $GLOBALS['TSFE'] = new \stdClass();
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Security\IfAuthenticatedViewHelper::class, array('renderThenChild', 'renderElseChild'));
        $this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
        $this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('else child'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfFeUserIsLoggedIn()
    {
        $GLOBALS['TSFE']->loginUser = 1;
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfFeUserIsNotLoggedIn()
    {
        $GLOBALS['TSFE']->loginUser = 0;
        $actualResult = $this->viewHelper->render();
        $this->assertEquals('else child', $actualResult);
    }
}
