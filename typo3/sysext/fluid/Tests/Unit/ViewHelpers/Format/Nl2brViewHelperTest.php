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
 * @version $Id: Nl2brViewHelperTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
class Tx_Fluid_ViewHelpers_Format_Nl2brViewHelperTest extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperDoesNotModifyTextWithoutLineBreaks() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('<p class="bodytext">Some Text without line breaks</p>'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('<p class="bodytext">Some Text without line breaks</p>', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperConvertsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(10) . 'Line 2', $actualResult);
	}

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function viewHelperConvertsWindowsLineBreaksToBRTags() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_Format_Nl2brViewHelper', array('renderChildren'));
		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('Line 1' . chr(13) . chr(10) . 'Line 2'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('Line 1<br />' . chr(13) . chr(10) . 'Line 2', $actualResult);
	}
}
?>
