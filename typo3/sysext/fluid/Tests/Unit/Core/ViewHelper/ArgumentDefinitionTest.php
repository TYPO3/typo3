<?php
namespace TYPO3\CMS\Fluid\Tests\Unit\Core\ViewHelper;

/*                                                                        *
 * This script is backported from the TYPO3 Flow package "TYPO3.Fluid".   *
 *                                                                        *
 * It is free software; you can redistribute it and/or modify it under    *
 * the terms of the GNU Lesser General Public License, either version 3   *
 *  of the License, or (at your option) any later version.                *
 *                                                                        *
 * The TYPO3 project - inspiring people to share!                         *
 *                                                                        */

/**
 * Testcase for \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition
 */
class ArgumentDefinitionTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @test
	 */
	public function objectStoresDataCorrectly() {
		$name = 'This is a name';
		$description = 'Example desc';
		$type = 'string';
		$isRequired = TRUE;
		$isMethodParameter = TRUE;
		$argumentDefinition = new \TYPO3\CMS\Fluid\Core\ViewHelper\ArgumentDefinition($name, $type, $description, $isRequired, NULL, $isMethodParameter);

		$this->assertEquals($argumentDefinition->getName(), $name, 'Name could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->getDescription(), $description, 'Description could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->getType(), $type, 'Type could not be retrieved correctly');
		$this->assertEquals($argumentDefinition->isRequired(), $isRequired, 'Required flag could not be retrieved correctly.');
		$this->assertEquals($argumentDefinition->isMethodParameter(), $isMethodParameter, 'isMethodParameter flag could not be retrieved correctly.');
	}
}

?>
