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

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');

/**
 * Testcase for CountViewHelper
 *
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_CountViewHelperTest extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * var Tx_Fluid_ViewHelpers_CountViewHelper
	 */
	protected $viewHelper;

	public function setUp() {
		parent::setUp();
		$this->viewHelper = new Tx_Fluid_ViewHelpers_CountViewHelper();
		$this->injectDependenciesIntoViewHelper($this->viewHelper);
		$this->viewHelper->initializeArguments();
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsNumberOfElementsInAnArray() {
		$expectedResult = 3;
		$actualResult = $this->viewHelper->render(array('foo', 'bar', 'Baz'));
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsNumberOfElementsInAnArrayObject() {
		$expectedResult = 2;
		$actualResult = $this->viewHelper->render(new ArrayObject(array('foo', 'bar')));
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsZeroIfGivenArrayIsEmpty() {
		$expectedResult = 0;
		$actualResult = $this->viewHelper->render(array());
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderReturnsZeroIfGivenArrayIsNull() {
		$expectedResult = 0;
		$actualResult = $this->viewHelper->render(NULL);
		$this->assertSame($expectedResult, $actualResult);
	}

	/**
	 * @test
	 * @expectedException Tx_Fluid_Core_ViewHelper_Exception
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderThrowsExceptionIfGivenSubjectIsNotCountable() {
		$object = new stdClass();
		$this->viewHelper->render($object);
	}

}

?>
