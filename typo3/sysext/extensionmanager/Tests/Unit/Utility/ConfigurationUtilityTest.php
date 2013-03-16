<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Oliver Hader <oliver.hader@typo3.org>
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
	 * @test
	 */
	public function getCurrentConfigurationReturnsExtensionConfigurationAsValuedConfiguration() {
		/** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$configurationUtility = $this->getMock(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility',
			array('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
		);
		$configurationUtility
			->expects($this->once())
			->method('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
			->will($this->returnValue(array()));
		$extensionKey = uniqid('some-extension');

		$currentConfiguration = array(
			'key1' => 'value1',
			'key2.' => array(
				'subkey1' => 'value2'
			)
		);

		$expected = array(
			'key1' => array(
				'value' => 'value1',
			),
			'key2.subkey1' => array(
				'value' => 'value2',
			),
		);

		$GLOBALS['TYPO3_LOADED_EXT'][$extensionKey]= array();
		$GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey] = serialize($currentConfiguration);
		$actual = $configurationUtility->getCurrentConfiguration($extensionKey);
		$this->assertEquals($expected, $actual);
	}

	/**
	 * @test
	 */
	public function getDefaultConfigurationFromExtConfTemplateAsValuedArrayReturnsExpectedExampleArray() {
		/** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$configurationUtility = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility',
			array('getDefaultConfigurationRawString')
		);
		$configurationUtility
			->expects($this->once())
			->method('getDefaultConfigurationRawString')
			->will($this->returnValue('foo'));

		$tsStyleConfig = $this->getMock('TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm');

		$objectManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$configurationUtility->_set('objectManager', $objectManagerMock);
		$objectManagerMock
			->expects($this->once())
			->method('get')
			->with('TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm')
			->will($this->returnValue($tsStyleConfig));

		$constants = array(
			'checkConfigurationFE' => array(
				'cat' => 'basic',
				'subcat_name' => 'enable',
				'subcat' => 'a/enable/z',
				'type' => 'user[EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
				'label' => 'Frontend configuration check',
				'name' => 'checkConfigurationFE',
				'value' => '0',
				'default_value' => '0'
			),
			'BE.forceSalted' => array(
				'cat' => 'advancedbackend',
				'subcat' => 'x/z',
				'type' => 'boolean',
				'label' => 'Force salted passwords: Enforce usage of SaltedPasswords. Old MD5 hashed passwords will stop working.',
				'name' => 'BE.forceSalted',
				'value' => '0',
				'default_value' => '0'
			)
		);
		$tsStyleConfig
			->expects($this->once())
			->method('ext_initTSstyleConfig')
			->will($this->returnValue($constants));

		$setupTsConstantEditor = array(
			'advancedbackend.' => array(
				'description' => '<span style="background:red; padding:1px 2px; color:#fff; font-weight:bold;">1</span> Install tool has hardcoded md5 hashing, enabling this setting will prevent use of a install-tool-created BE user.<br />Currently same is for changin password with user setup module unless you use pending patch!',
				1 => 'BE.forceSalted'
			)
		);
		$tsStyleConfig->setup['constants']['TSConstantEditor.'] = $setupTsConstantEditor;

		$expected = array(
			'checkConfigurationFE' => array(
				'cat' => 'basic',
				'subcat_name' => 'enable',
				'subcat' => 'a/enable/z',
				'type' => 'user[EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
				'label' => 'Frontend configuration check',
				'name' => 'checkConfigurationFE',
				'value' => '0',
				'default_value' => '0',
				'subcat_label' => 'Enable features',
			),
			'BE.forceSalted' => array(
				'cat' => 'advancedbackend',
				'subcat' => 'x/z',
				'type' => 'boolean',
				'label' => 'Force salted passwords: Enforce usage of SaltedPasswords. Old MD5 hashed passwords will stop working.',
				'name' => 'BE.forceSalted',
				'value' => '0',
				'default_value' => '0',
				'highlight' => 1,
			),
			'__meta__' => array(
				'advancedbackend' => array(
					'highlightText' => '<span style="background:red; padding:1px 2px; color:#fff; font-weight:bold;">1</span> Install tool has hardcoded md5 hashing, enabling this setting will prevent use of a install-tool-created BE user.<br />Currently same is for changin password with user setup module unless you use pending patch!'
				)
			)
		);

		$result = $configurationUtility->getDefaultConfigurationFromExtConfTemplateAsValuedArray(uniqid('some_extension'));
		$this->assertEquals($expected, $result);
	}

	/**
	 * Data provider for convertValuedToNestedConfiguration
	 *
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
	 * @test
	 * @dataProvider convertValuedToNestedConfigurationDataProvider
	 *
	 * @param array $configuration
	 * @param array $expected
	 * @return void
	 */
	public function convertValuedToNestedConfiguration(array $configuration, array $expected) {
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$fixture = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$this->assertEquals($expected, $fixture->convertValuedToNestedConfiguration($configuration));
	}

	/**
	 * Data provider for convertNestedToValuedConfiguration
	 *
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
	 * @dataProvider convertNestedToValuedConfigurationDataProvider
	 *
	 * @param array $configuration
	 * @param array $expected
	 * @return void
	 */
	public function convertNestedToValuedConfiguration(array $configuration, array $expected) {
		/** @var $fixture \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility */
		$fixture = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$this->assertEquals($expected, $fixture->convertNestedToValuedConfiguration($configuration));
	}
}
?>
