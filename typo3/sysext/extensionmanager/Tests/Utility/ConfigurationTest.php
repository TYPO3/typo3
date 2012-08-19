<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2012 Oliver Hader <oliver.hader@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Testcase for the Tx_Extensionmanager_Utility_Configuration class in the TYPO3 Core.
 *
 * @package Extension Manager
 * @subpackage Tests
 */
class Tx_Extensionmanager_Utility_ConfigurationTest extends Tx_Extbase_Tests_Unit_BaseTestCase {
	/**
	 * @param array $configuration
	 * @param array $expected
	 * @dataProvider convertValuedToNestedConfigurationDataProvider
	 * @test
	 */
	public function convertValuedToNestedConfiguration(array $configuration, array $expected) {
		/** @var $fixture Tx_Extensionmanager_Utility_Configuration */
		$fixture = $this->objectManager->get('Tx_Extensionmanager_Utility_Configuration');

		$this->assertEquals(
			$expected,
			$fixture->convertValuedToNestedConfiguration($configuration)
		);
	}

	/**
	 * @return array
	 */
	public function convertValuedToNestedConfigurationDataProvider() {
		return array(
			'plain array' => array(
				array(
					'first' => array(
						'value' => 'value1',
					),
					'second' => array(
						'value' => 'value2',
					),
				),
				array(
					'first' => 'value1',
					'second' => 'value2',
				),
			),
			'nested value with 2 levels' => array(
				array(
					'first.firstSub' => array(
						'value' => 'value1',
					),
					'second.secondSub' => array(
						'value' => 'value2',
					),
				),
				array(
					'first.' => array(
						'firstSub' => 'value1',
					),
					'second.' => array(
						'secondSub' => 'value2',
					),
				),
			),
			'nested value with 3 levels' => array(
				array(
					'first.firstSub.firstSubSub' => array(
						'value' => 'value1',
					),
					'second.secondSub.secondSubSub' => array(
						'value' => 'value2',
					),
				),
				array(
					'first.' => array(
						'firstSub.' => array(
							'firstSubSub' => 'value1',
						),
					),
					'second.' => array(
						'secondSub.' => array(
							'secondSubSub' => 'value2',
						),
					),
				),
			),
			'mixed nested value with 2 levels' => array(
				array(
					'first' => array(
						'value' => 'firstValue',
					),
					'first.firstSub' => array(
						'value' => 'value1',
					),
					'second.secondSub' => array(
						'value' => 'value2',
					),
				),
				array(
					'first' => 'firstValue',
					'first.' => array(
						'firstSub' => 'value1',
					),
					'second.' => array(
						'secondSub' => 'value2',
					),
				),
			),
		);
	}
}
?>