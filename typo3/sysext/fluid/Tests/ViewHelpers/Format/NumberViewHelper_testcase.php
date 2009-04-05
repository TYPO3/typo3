<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christopher Hlubek <hlubek@networkteam.com>
*  All rights reserved
*
*  This script is part of the TYPO3 project. The TYPO3 project is
*  free software; you can redistribute it and/or modify
*  it under the terms of the GNU General Public License as published by
*  the Free Software Foundation; either version 2 of the License, or
*  (at your option) any later version.
*
*  The GNU General Public License can be found at
*  http://www.gnu.org/copyleft/gpl.html.
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

require_once(t3lib_extMgm::extPath('extbase', 'Tests/Base_testcase.php'));

class Tx_Fluid_ViewHelpers_Format_NumberViewHelper_testcase extends Tx_ExtBase_Base_testcase {
	public function test_FormatNumberDefaultsToEnglishNotationWithTwoDecimals() {
		$helper = new Tx_Fluid_ViewHelpers_Format_NumberViewHelper();
		$result = $helper->render(10000.0 / 3.0);
		$this->assertEquals('3,333.33', $result);
	}

	public function test_FormatNumberWithDecimalsDecimalPointAndSeparator() {
		$helper = new Tx_Fluid_ViewHelpers_Format_NumberViewHelper();
		$result = $helper->render(10000.0 / 3.0, 3, ',', '.');
		$this->assertEquals('3.333,333', $result);
	}
}
?>