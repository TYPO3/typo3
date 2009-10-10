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

/**
 * @version $Id: ArgumentDefinitionTest.php 2813 2009-07-16 14:02:34Z k-fish $
 */
/**
 * Testcase for Tx_Fluid_Core_ViewHelper_ArgumentDefinition
 *
 * @version $Id: ArgumentDefinitionTest.php 2813 2009-07-16 14:02:34Z k-fish $
 * @license http://opensource.org/licenses/gpl-license.php GNU Public License, version 2
 */
class Tx_Fluid_Core_ArgumentDefinitionTest_testcase extends Tx_Extbase_BaseTestCase {

	/**
	 * @test
	 * @author Sebastian Kurfürst <sebastian@typo3.org>
	 */
	public function objectStoresDataCorrectly() {
		$name = "This is a name";
		$description = "Example desc";
		$type = "string";
		$isRequired = TRUE;
		$isMethodParameter = TRUE;
		$argumentDefinition = new Tx_Fluid_Core_ViewHelper_ArgumentDefinition($name, $type, $description, $isRequired, null, $isMethodParameter);

		$this->assertEquals($argumentDefinition->getName(), $name, 'Name could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->getDescription(), $description, 'Description could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->getType(), $type, 'Type could not be retrieved correctly');
		$this->assertEquals($argumentDefinition->isRequired(), $isRequired, 'Required flag could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->isMethodParameter(), $isMethodParameter, 'isMethodParameter flag could not be retrieved correctly.');
	}
}



?>
