<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is backported from the TYPO3 Flow package "TYPO3.Fluid".
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
class IfHasRoleViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfAuthenticatedViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
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
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfHasRoleViewHelper', array('renderThenChild', 'renderElseChild'));
		$this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
		$this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('else child'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 */
	public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIsLoggedIn() {
		$actualResult = $this->viewHelper->render('Editor');
		$this->assertEquals('then child', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersThenChildIfBeUserWithSpecifiedRoleIdIsLoggedIn() {
		$actualResult = $this->viewHelper->render(1);
		$this->assertEquals('then child', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIsNotLoggedIn() {
		$actualResult = $this->viewHelper->render('editor');
		$this->assertEquals('else child', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersElseChildIfBeUserWithSpecifiedRoleIdIsNotLoggedIn() {
		$actualResult = $this->viewHelper->render(123);
		$this->assertEquals('else child', $actualResult);
	}
}
