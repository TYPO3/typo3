<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog <typo3@susannemoog.de>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Test class for module menu utilities
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package TYPO3
 * @subpackage tests
 */
class Typo3_Utility_BackendModuleUtilityTest extends Tx_PhpUnit_TestCase {

	/**
	 * @var Typo3_BackendModule_Utility
	 */
	protected $moduleMenuUtility;

	/**
	 * Helper function to call protected or private methods
	 *
	 * @param string $className The className
	 * @param string $name the name of the method to call
	 * @param mixed $argument The argument for the method call (only one in this test class needed)
	 * @return mixed
	 */
	public function callInaccessibleMethod($className, $name, $argument) {
		$class = new \ReflectionClass($className);
		$object = new $className;
		$method = $class->getMethod($name);
		$method->setAccessible(TRUE);
		return $method->invoke($object, $argument);
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataGeneratesMenuEntry() {
		$entry = $this->callInaccessibleMethod('Typo3_Utility_BackendModuleUtility', 'createEntryFromRawData', array());
		$this->assertInstanceOf('Typo3_Domain_Model_BackendModule', $entry);
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataSetsPropertiesInEntryObject() {
		$rawModule = array(
			'name' => 'nameTest',
			'title' => 'titleTest',
			'onclick' => 'onclickTest',
			'icon' => array(
				'test' => '123'
			),
			'link' => 'linkTest',
			'description' => 'descriptionTest',
			'navigationComponentId' => 'navigationComponentIdTest'
		);
		$entry = $this->callInaccessibleMethod('Typo3_Utility_BackendModuleUtility', 'createEntryFromRawData', $rawModule);
		$this->assertEquals('nameTest', $entry->getName());
		$this->assertEquals('titleTest', $entry->getTitle());
		$this->assertEquals('linkTest', $entry->getLink());
		$this->assertEquals('onclickTest', $entry->getOnClick());
		$this->assertEquals('navigationComponentIdTest', $entry->getNavigationComponentId());
		$this->assertEquals('descriptionTest', $entry->getDescription());
		$this->assertEquals(array('test' => '123'), $entry->getIcon());
	}

	/**
	 * @test
	 */
	public function createEntryFromRawDataSetsLinkIfPathIsGivenInEntryObject() {
		$rawModule = array(
			'path' => 'pathTest',
		);
		$entry = $this->callInaccessibleMethod('Typo3_Utility_BackendModuleUtility', 'createEntryFromRawData', $rawModule);
		$this->assertEquals('pathTest', $entry->getLink());
	}
}

?>