<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be\Security;

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
 * Testcase for be.security.ifHasRole view helper
 */
class IfHasRoleViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase
{
    /**
     * @var \TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfAuthenticatedViewHelper
     */
    protected $viewHelper;

    protected function setUp()
    {
        parent::setUp();
        $GLOBALS['BE_USER'] = new \stdClass();
        $GLOBALS['BE_USER']->userGroups = array(
            array(
                'uid' => 1,
                'title' => 'Editor'
            ),
            array(
                'uid' => 2,
                'title' => 'OtherRole'
            )
        );
        $this->viewHelper = $this->getAccessibleMock(\TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfHasRoleViewHelper::class, array('renderThenChild', 'renderElseChild'));
        $this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
        $this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('else child'));
        $this->injectDependenciesIntoViewHelper($this->viewHelper);
        $this->viewHelper->initializeArguments();
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIsLoggedIn()
    {
        $this->arguments['role'] = 'Editor';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->render('Editor');
        $this->assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIdIsLoggedIn()
    {
        $this->arguments['role'] = 1;
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->render(1);
        $this->assertEquals('then child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIsNotLoggedIn()
    {
        $this->arguments['role'] = 'editor';
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->render('editor');
        $this->assertEquals('else child', $actualResult);
    }

    /**
     * @test
     */
    public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIdIsNotLoggedIn()
    {
        $this->arguments['role'] = 123;
        $this->injectDependenciesIntoViewHelper($this->viewHelper);

        $actualResult = $this->viewHelper->render(123);
        $this->assertEquals('else child', $actualResult);
    }
}
