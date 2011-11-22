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
 * Test for the Abstract Form view helper
 *
 */
abstract class Tx_Fluid_Tests_Unit_ViewHelpers_Form_FormFieldViewHelperBaseTestcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @var Tx_Extbase_Configuration_ConfigurationManagerInterface
	 */
	protected $mockConfigurationManager;

	/**
	 * @return void
	 */
	public function setUp() {
		parent::setUp();
		$this->mockConfigurationManager = $this->getMock('Tx_Extbase_Configuration_ConfigurationManagerInterface');
	}

	/**
	 * @param Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper
	 * @return void
	 */
	protected function injectDependenciesIntoViewHelper(Tx_Fluid_Core_ViewHelper_AbstractViewHelper $viewHelper) {
		$viewHelper->injectConfigurationManager($this->mockConfigurationManager);
		parent::injectDependenciesIntoViewHelper($viewHelper);
	}
}

?>