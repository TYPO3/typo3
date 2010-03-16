<?php

/*                                                                        *
 * This script belongs to the FLOW3 package "Fluid".                      *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License as published by the *
 * Free Software Foundation, either version 3 of the License, or (at your *
 * option) any later version.                                             *
 *                                                                        *
 * This script is distributed in the hope that it will be useful, but     *
 * WITHOUT ANY WARRANTY; without even the implied warranty of MERCHAN-    *
 * TABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU Lesser       *
 * General Public License for more details.                               *
 *                                                                        *
 * You should have received a copy of the GNU Lesser General Public       *
 * License along with the script.                                         *
 * If not, see http://www.gnu.org/licenses/lgpl.html                      *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for Tx_Fluid_Core_ViewHelper_ArgumentDefinition
 *
 * @version $Id: ArgumentDefinitionTest.php 3751 2010-01-22 15:56:47Z k-fish $
 * @license http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License, version 3 or later
 */
class Tx_Fluid_Core_ArgumentDefinitionTest extends Tx_Extbase_BaseTestCase {

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
