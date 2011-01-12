<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU General Public License as published by the Free   *
 * Software Foundation, either version 3 of the License, or (at your      *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        *
 * You should have received a copy of the GNU General Public License      *
 * along with the script.                                                 *
 * If not, see http://www.gnu.org/licenses/gpl.html                       *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 */
class Tx_Fluid_Tests_Unit_ViewHelpers_Format_CropViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * var Tx_Fluid_ViewHelpers_Format_CropViewHelper
	 */
	protected $viewHelper;

	/**
	 * @var tslib_cObj
	 */
	protected $mockContentObject;

	public function setUp() {
		parent::setUp();

		$this->mockContentObject = $this->getMock('tslib_cObj', array(), array(), '', FALSE);
		$this->viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CropViewHelper', array('renderChildren'), array($this->mockContentObject));
		$this->viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Some Content'));
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperCallsCropHtmlByDefault() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123);
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperCallsCropHtmlByDefault2() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '-321|custom suffix|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(-321, 'custom suffix');
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function respectWordBoundariesCanBeDisabled() {
		$this->mockContentObject->expects($this->once())->method('cropHTML')->with('Some Content', '123|...|')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123, '...', FALSE);
		$this->assertEquals('Cropped Content', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function respectHtmlCanBeDisabled() {
		$this->mockContentObject->expects($this->once())->method('crop')->with('Some Content', '123|...|1')->will($this->returnValue('Cropped Content'));
		$actualResult = $this->viewHelper->render(123, '...', TRUE, FALSE);
		$this->assertEquals('Cropped Content', $actualResult);
	}
}
?>
