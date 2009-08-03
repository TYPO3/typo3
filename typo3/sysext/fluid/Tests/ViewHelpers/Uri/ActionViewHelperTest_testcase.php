<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
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
 * Testcase for the action uri view helper
 *
 * @version $Id: ActionViewHelperTest.php 2914 2009-07-28 18:26:38Z bwaidelich $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 * @scope prototype
 */
require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));
class Tx_Fluid_ViewHelpers_Uri_ActionViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_Uri_ActionViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = new Tx_Fluid_ViewHelpers_Uri_ActionViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsUriReturnedFromUriBuilder() {
		$this->uriBuilder->expects($this->any())->method('URIFor')->will($this->returnValue('some/uri'));

		$this->viewHelper->initialize();
		$actualResult = $this->viewHelper->render();

		$this->assertEquals('some/uri', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlyPassesDefaultArgumentsToUriBuilder() {
		$this->uriBuilder->expects($this->any())->method('URIFor')->with(
			NULL, array(), NULL, NULL, NULL, '', '', FALSE
		);

		$this->viewHelper->initialize();
		$this->viewHelper->render();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderCorrectlyPassesAllArgumentsToUriBuilder() {
		$this->uriBuilder->expects($this->any())->method('URIFor')->with(
			'someAction', array('some' => 'argument'), 'someController', 'somePackage', 'someSubpackage', 'someSection', 'someFormat', TRUE
		);

		$this->viewHelper->initialize();
		$this->viewHelper->render('someAction', array('some' => 'argument'), 'someController', 'somePackage', 'someSubpackage', 'someSection', 'someFormat', TRUE);
	}
}


?>
