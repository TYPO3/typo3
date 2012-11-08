<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

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
 * Configuration utility test
 *
 */
class ConfigurationUtilityTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @param array $configuration
	 * @param array $expected
	 * @dataProvider convertValuedToNestedConfigurationDataProvider
	 * @test
	 */
	public function convertValuedToNestedConfiguration(array $configuration, array $expected) {
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$fixture = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$this->assertEquals($expected, $fixture->convertValuedToNestedConfiguration($configuration));
	}

	/**
	 * @return array
	 */
	public function convertValuedToNestedConfigurationDataProvider() {
		return array(
			'plain array' => array(
				array(
					'first' => array(
						'value' => 'value1'
					),
					'second' => array(
						'value' => 'value2'
					)
				),
				array(
					'first' => 'value1',
					'second' => 'value2'
				)
			),
			'nested value with 2 levels' => array(
				array(
					'first.firstSub' => array(
						'value' => 'value1'
					),
					'second.secondSub' => array(
						'value' => 'value2'
					)
				),
				array(
					'first.' => array(
						'firstSub' => 'value1'
					),
					'second.' => array(
						'secondSub' => 'value2'
					)
				)
			),
			'nested value with 3 levels' => array(
				array(
					'first.firstSub.firstSubSub' => array(
						'value' => 'value1'
					),
					'second.secondSub.secondSubSub' => array(
						'value' => 'value2'
					)
				),
				array(
					'first.' => array(
						'firstSub.' => array(
							'firstSubSub' => 'value1'
						)
					),
					'second.' => array(
						'secondSub.' => array(
							'secondSubSub' => 'value2'
						)
					)
				)
			),
			'mixed nested value with 2 levels' => array(
				array(
					'first' => array(
						'value' => 'firstValue'
					),
					'first.firstSub' => array(
						'value' => 'value1'
					),
					'second.secondSub' => array(
						'value' => 'value2'
					)
				),
				array(
					'first' => 'firstValue',
					'first.' => array(
						'firstSub' => 'value1'
					),
					'second.' => array(
						'secondSub' => 'value2'
					)
				)
			)
		);
	}

	/**
	 * @param array $configuration
	 * @param array $expected
	 * @test
	 * @dataProvider convertNestedToValuedConfigurationDataProvider
	 */
	public function convertNestedToValuedConfiguration(array $configuration, array $expected) {
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$fixture = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$this->assertEquals($expected, $fixture->convertNestedToValuedConfiguration($configuration));
	}

	/**
	 * @return array
	 */
	public function convertNestedToValuedConfigurationDataProvider() {
		return array(
			'plain array' => array(
				array(
					'first' => 'value1',
					'second' => 'value2'
				),
				array(
					'first' => array('value' => 'value1'),
					'second' => array('value' => 'value2'),
				)
			),
			'two levels' => array(
				array(
					'first.' => array('firstSub' => 'value1'),
					'second.' => array('firstSub' => 'value2'),
				),
				array(
					'first.firstSub' => array('value' => 'value1'),
					'second.firstSub' => array('value' => 'value2'),
				)
			),
			'three levels' => array(
				array(
					'first.' => array('firstSub.' => array('firstSubSub' => 'value1')),
					'second.' => array('firstSub.' => array('firstSubSub' => 'value2'))
				),
				array(
					'first.firstSub.firstSubSub' => array('value' => 'value1'),
					'second.firstSub.firstSubSub' => array('value' => 'value2'),
				)
			),
			'mixed' => array(
				array(
					'first.' => array('firstSub' => 'value1'),
					'second.' => array('firstSub.' => array('firstSubSub' => 'value2')),
					'third' => 'value3'
				),
				array(
					'first.firstSub' => array('value' => 'value1'),
					'second.firstSub.firstSubSub' => array('value' => 'value2'),
					'third' => array('value' => 'value3')
				)
			)
		);
	}

	/**
	 * @test
	 */
	public function thatGetCurrentConfigurationReturnsCorrectExtensionConfigurationAsValuedConfiguration() {
		$mockedConfigurationItemRepository = $this->getAccessibleMock('\TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository', array(
			'createArrayFromConstants'
		));
		$mockedConfigurationItemRepository->expects($this->any())
			->method('createArrayFromConstants')
			->will($this->returnValue(array()));
		$GLOBALS['TYPO3_LOADED_EXT']['MY_DUMMY_EXT']= array();
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf']['MY_DUMMY_EXT'] = serialize(array('key1' => 'value1', 'key2.' => array('subkey1' => 'value2')));
		$configurationUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$configurationUtility->injectConfigurationItemRepository($mockedConfigurationItemRepository);

		$configuration = $configurationUtility->getCurrentConfiguration('MY_DUMMY_EXT');
		$this->assertEquals('value1', $configuration['key1']['value']);
		$this->assertEquals('value2', $configuration['key2.subkey1']['value']);
	}

}
?>
