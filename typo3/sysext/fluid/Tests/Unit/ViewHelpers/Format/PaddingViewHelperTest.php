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
 * @version $Id: PaddingViewHelperTest.php 3190 2009-09-16 16:48:39Z bwaidelich $
 */
class Tx_Fluid_ViewHelpers_Format_PaddingViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function stringsArePaddedWithBlanksByDefault() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		$actualResult = $viewHelper->render(10);
		$this->assertEquals('foo       ', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function paddingStringCanBeSpecified() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		$actualResult = $viewHelper->render(10, '-=');
		$this->assertEquals('foo-=-=-=-', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function stringIsNotTruncatedIfPadLengthIsBelowStringLength() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('some long string'));
		$actualResult = $viewHelper->render(5);
		$this->assertEquals('some long string', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function integersArePaddedCorrectly() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PaddingViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render(5, '0');
		$this->assertEquals('12300', $actualResult);
	}
}
?>