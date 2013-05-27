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
 * @version $Id: PrintfViewHelperTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
class Tx_Fluid_ViewHelpers_Format_PrintfViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Christopher Hlubek <hlubek@networkteam.com>
	 */
	public function viewHelperCanUseArrayAsArgument() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PrintfViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%04d-%02d-%02d'));
		$actualResult = $viewHelper->render(array('year' => 2009, 'month' => 4, 'day' => 5));
		$this->assertEquals('2009-04-05', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperCanSwapMultipleArguments() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_PrintfViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('%2$s %1$d %3$s %2$s'));
		$actualResult = $viewHelper->render(array(123, 'foo', 'bar'));
		$this->assertEquals('foo 123 bar foo', $actualResult);
	}	
}
?>