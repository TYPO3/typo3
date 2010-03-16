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
 * @version $Id: CurrencyViewHelperTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
class Tx_Fluid_ViewHelpers_Format_CurrencyViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRoundsFloatCorrectly() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123.456));
		$actualResult = $viewHelper->render();
		$this->assertEquals('123,46', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersCurrencySign() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(123));
		$actualResult = $viewHelper->render('foo');
		$this->assertEquals('123,00 foo', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRespectsDecimalSeparator() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
		$actualResult = $viewHelper->render('', '|');
		$this->assertEquals('12.345|00', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRespectsThousandsSeparator() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(12345));
		$actualResult = $viewHelper->render('', ',', '|');
		$this->assertEquals('12|345,00', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersNullValues() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(NULL));
		$actualResult = $viewHelper->render();
		$this->assertEquals('0,00', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperRendersNegativeAmounts() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_CurrencyViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue(-123.456));
		$actualResult = $viewHelper->render();
		$this->assertEquals('-123,46', $actualResult);
	}
}
?>
