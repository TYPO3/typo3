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

/**
 * Some functional tests for the backport of the reflection service
 */
class Tx_Extbase_Reflection_Service_testcase extends Tx_Extbase_BaseTestCase {
	
	/**
	 * @param array $foo The foo parameter
	 * @return nothing
	 */
	public function fixtureMethodForMethodTagsValues(array $foo) {
		
	}
	
	public function test_GetMethodTagsValues() {
		$service = new Tx_Extbase_Reflection_Service();
		$tagsValues = $service->getMethodTagsValues('Tx_Extbase_Reflection_Service_testcase', 'fixtureMethodForMethodTagsValues');
		
		$this->assertEquals(array(
			'param' => array('array $foo The foo parameter'),
			'return' => array('nothing')
		), $tagsValues);
	}

	public function test_GetMethodParameters() {
		$service = new Tx_Extbase_Reflection_Service();
		$parameters = $service->getMethodParameters('Tx_Extbase_Reflection_Service_testcase', 'fixtureMethodForMethodTagsValues');
		
		$this->assertEquals(array(
			'foo' => array(
				'position' => 0,
				'byReference' => FALSE,
				'array' => TRUE,
				'optional' => FALSE,
				'allowsNull' => FALSE,
				'class' => NULL,
				'type' => 'array'
			)
		), $parameters);
	}
}
?>