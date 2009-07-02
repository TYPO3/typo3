<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Christian Müller <christian@kitsunet.de>
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


/**
 * Testcase for class Tx_Extbase_Utility_TypoScript
 *
 * @package Extbase
 * @subpackage extbase
 */

class Tx_Extbase_Utility_TypoScript_testcase extends tx_phpunit_testcase {

	/**
	 * @test
	 * @see Tx_Extbase_Utility_TypoScript::convertExtbaseToClassicTS
	 */
	public function basicExtBase2ClassicTest() {
		$extbaseTS = array(
			'10' => array(
				'value' => 'Hallo',
				'_typoscriptNodeValue' => 'TEXT'
			)
		);

		$classic = array(
			'10' => 'TEXT',
			'10.' => array(
				'value' => 'Hallo'
			)
		);

		$converted = Tx_Extbase_Utility_TypoScript::convertExtbaseToClassicTS($extbaseTS);

		$this->assertEquals($converted, $classic);
	}
}
?>