<?php

/*                                                                        *
 * This script is backported from the FLOW3 package "TYPO3.Fluid".        *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

require_once(dirname(__FILE__) . '/../ViewHelperBaseTestcase.php');

/**
 * Testcase for be.security.ifHasRole view helper
 *
 */
class Tx_Fluid_ViewHelpers_Be_Security_IfHasRoleViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Be_Security_IfAuthenticatedViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var t3lib_tsfeBeUserAuth
	 */
	protected $beUserBackup;

	public function setUp() {
		parent::setUp();

		$this->beUserBackup = isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : NULL;
		$GLOBALS['BE_USER'] = new stdClass();
		$GLOBALS['BE_USER']->userGroups = array(
			array(
				'uid' => 1,
				'title' => 'Editor',
			),
			array(
				'uid' => 2,
				'title' => 'OtherRole',
			),
		);
		$this->viewHelper = $this->getAccessibleMock('Tx_Fluid_ViewHelpers_Be_Security_IfHasRoleViewHelper', array('renderThenChild', 'renderElseChild'));
		$this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
		$this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue("else child"));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['BE_USER'] = $this->beUserBackup;
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

?>
