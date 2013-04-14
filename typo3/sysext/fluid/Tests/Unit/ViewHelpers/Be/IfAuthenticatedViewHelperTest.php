<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\Be;

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
require_once __DIR__ . '/../ViewHelperBaseTestcase.php';

/**
 * Testcase for be.security.ifAuthenticated view helper
 */
class IfAuthenticatedViewHelperTest extends \TYPO3\CMS\Fluid\Tests\Unit\ViewHelpers\ViewHelperBaseTestcase {

	/**
	 * @var \TYPO3\CMS\Fluid\ViewHelpers\Be\Security\IfAuthenticatedViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var \TYPO3\CMS\Backend\FrontendBackendUserAuthentication
	 */
	protected $beUserBackup;

	public function setUp() {
		parent::setUp();
		$this->beUserBackup = isset($GLOBALS['BE_USER']) ? $GLOBALS['BE_USER'] : NULL;
		$GLOBALS['BE_USER'] = new \stdClass();
		$this->viewHelper = $this->getAccessibleMock('TYPO3\\CMS\\Fluid\\ViewHelpers\\Be\\Security\\IfAuthenticatedViewHelper', array('renderThenChild', 'renderElseChild'));
		$this->viewHelper->expects($this->any())->method('renderThenChild')->will($this->returnValue('then child'));
		$this->viewHelper->expects($this->any())->method('renderElseChild')->will($this->returnValue('else child'));
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	public function tearDown() {
		$GLOBALS['BE_USER'] = $this->beUserBackup;
	}

	/**
	 * @test
	 */
	public function viewHelperRendersThenChildIfBeUserIsLoggedIn() {
		$GLOBALS['BE_USER']->user = array('uid' => 1);
		$actualResult = $this->viewHelper->render();
		$this->assertEquals('then child', $actualResult);
	}

	/**
	 * @test
	 */
	public function viewHelperRendersElseChildIfBeUserIsNotLoggedIn() {
		$actualResult = $this->viewHelper->render();
		$this->assertEquals('else child', $actualResult);
	}
}

?>