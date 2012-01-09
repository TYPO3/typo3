<?php
namespace TYPO3\CMS\Backend\Tests\Unit\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Wouter Wolters <typo3@wouterwolters.nl>
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
 * Testcase for class TYPO3\CMS\Backend\Form\FlexFormsHelper
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 * @package TYPO3
 * @subpackage backend
 */
class FlexFormsHelperTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * Helper function to call protected or private methods
	 *
	 * @param string $className The className
	 * @param string $name The name of the method to call
	 * @param array $arguments Array of arguments to pass to the function
	 * @return mixed
	 */
	protected function callInaccessibleMethod($className, $name, array $arguments) {
		$class = new \ReflectionClass($className);
		$object = new $className;
		$method = $class->getMethod($name);
		$method->setAccessible(TRUE);
		return $method->invokeArgs($object, $arguments);
	}

	/**
	 * DataProvider for testGetFlexFormFieldConfiguration
	 *
	 * @return array
	 */
	public function sheetDataProvider() {
		return array(
			'Empty sheetConf' => array(
				array(),
				'field',
				array()
			),
			'Empty fieldName' => array(
				array(),
				'',
				array()
			),
			'Get configuration on fieldName without dot' => array(
				array(
					'propertyA' => array(
						'keyA' => array(
							'valueA' => 1,
						),
						'keyB' => 2,
					),
					'propertyB' => 3,
				),
				'propertyB',
				3
			),
			'Get configuration on fieldName with dot' => array(
				array(
					'propertyA' => array(
						'keyA' => array(
							'valueA' => 1,
						),
						'keyB' => 2,
					),
					'firstsetting' => array(
						'firstsetting' => 3
					),
				),
				'settings.firstsetting',
				array(
					'firstsetting' => 3
				)
			),
		);
	}

	/**
	 * @test
	 * @dataProvider sheetDataProvider
	 */
	public function getFlexFormFieldConfigurationBySheetConfAndFieldName($sheetConf, $fieldName, $expected) {
		$arguments = array($sheetConf, $fieldName);
		$fieldConf = $this->callInaccessibleMethod('TYPO3\CMS\Backend\Form\FlexFormsHelper', 'getFlexFormFieldConfiguration', $arguments);
		$this->assertEquals($expected, $fieldConf);
	}

}
?>