<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Utility;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Configuration utility test
 *
 */
class ConfigurationUtilityTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @test
	 */
	public function getCurrentConfigurationReturnsExtensionConfigurationAsValuedConfiguration() {
		/** @var $configurationUtility \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject */
		$configurationUtility = $this->getMock(
			\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class,
			array('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
		);
		$configurationUtility
			->expects($this->once())
			->method('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
			->will($this->returnValue(array()));
		$extensionKey = $this->getUniqueId('some-extension');

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
			\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class,
			array('getDefaultConfigurationRawString', 'getExtensionPathInformation')
		);
		$configurationUtility
			->expects($this->once())
			->method('getDefaultConfigurationRawString')
			->will($this->returnValue('foo'));

		$configurationUtility
			->expects($this->once())
			->method('getExtensionPathInformation')
			->will($this->returnValue(NULL));

		$tsStyleConfig = $this->getMock(\TYPO3\CMS\Core\TypoScript\ConfigurationForm::class);

		$objectManagerMock = $this->getMock(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface::class);
		$configurationUtility->_set('objectManager', $objectManagerMock);
		$objectManagerMock
			->expects($this->once())
			->method('get')
			->with(\TYPO3\CMS\Core\TypoScript\ConfigurationForm::class)
			->will($this->returnValue($tsStyleConfig));

		$constants = array(
			'checkConfigurationFE' => array(
				'cat' => 'basic',
				'subcat_name' => 'enable',
				'subcat' => 'a/enable/z',
				'type' => 'user[TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
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
				'type' => 'user[TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
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

		$result = $configurationUtility->getDefaultConfigurationFromExtConfTemplateAsValuedArray($this->getUniqueId('some_extension'));
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
		/** @var $subject \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$subject = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class, array('dummy'), array(), '', FALSE);
		$this->assertEquals($expected, $subject->convertValuedToNestedConfiguration($configuration));
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
		/** @var $subject \TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility|\PHPUnit_Framework_MockObject_MockObject|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface */
		$subject = $this->getAccessibleMock(\TYPO3\CMS\Extensionmanager\Utility\ConfigurationUtility::class, array('dummy'), array(), '', FALSE);
		$this->assertEquals($expected, $subject->convertNestedToValuedConfiguration($configuration));
	}

}
