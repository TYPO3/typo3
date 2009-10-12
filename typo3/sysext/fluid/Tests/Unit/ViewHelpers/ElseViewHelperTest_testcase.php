<?php

/*                                                                        *
 * This script is part of the TYPO3 project - inspiring people to share!  *
 *                                                                        *
 * TYPO3 is free software; you can redistribute it and/or modify it under *
 * the terms of the GNU General Public License version 2 as published by  *
 * the Free Software Foundation.                                          *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU General      *
 * Public License for more details.                                       *
 *                                                                        */

require_once(dirname(__FILE__) . '/ViewHelperBaseTestcase.php');
/**
 * Testcase for ElseViewHelper
 *
 * @version $Id: ElseViewHelperTest.php 2914 2009-07-28 18:26:38Z bwaidelich $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */

class Tx_Fluid_ViewHelpers_ElseViewHelperTest_testcase extends Tx_Fluid_ViewHelpers_ViewHelperBaseTestcase {

	/**
	 * @test
	 * @author Bastian Waidelich <bastian@typo3.org>
	 */
	public function renderRendersChildren() {
		$viewHelper = $this->getMock('Tx_Fluid_ViewHelpers_ElseViewHelper', array('renderChildren'));

		$viewHelper->expects($this->once())->method('renderChildren')->will($this->returnValue('foo'));
		$actualResult = $viewHelper->render();
		$this->assertEquals('foo', $actualResult);
	}
}

?>
