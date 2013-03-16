<?php
namespace TYPO3\CMS\Extensionmanager\Tests\Unit\Domain\Repository;

/***************************************************************
 * Copyright notice
 *
 * (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
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
 * Tests for ConfigurationItemRepository
 *
 */
class ConfigurationItemRepositoryTest extends \TYPO3\CMS\Extbase\Tests\Unit\BaseTestCase {

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository|\TYPO3\CMS\Core\Tests\AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $configurationItemRepository;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface|\PHPUnit_Framework_MockObject_MockObject
	 */
	protected $injectedObjectManagerMock;

	/**
	 * Set up
	 *
	 * @return void
	 */
	public function setUp() {
		// Mock system under test to make protected methods accessible
		$this->configurationItemRepository = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ConfigurationItemRepository',
			array('dummy')
		);

		$this->injectedObjectManagerMock = $this->getMock('TYPO3\\CMS\\Extbase\\Object\\ObjectManagerInterface');
		$this->configurationItemRepository->_set(
			'objectManager',
			$this->injectedObjectManagerMock
		);
	}

	/**
	 * @test
	 */
	public function getConfigurationArrayFromExtensionKeyReturnsSortedHierarchicArray() {
		$flatConfigurationItemArray = array(
			'item1' => array(
				'cat' => 'basic',
				'subcat_name' => 'enable',
				'subcat' => 'a/enable/10z',
				'type' => 'string',
				'label' => 'Item 1: This is the first configuration item',
				'name' =>'item1',
				'value' => 'one',
				'default_value' => 'one',
				'subcat_label' => 'Enable features',
			),
			'integerValue' => array(
				'cat' => 'basic',
				'subcat_name' => 'enable',
				'subcat' => 'a/enable/20z',
				'type' => 'int+',
				'label' => 'Integer Value: Please insert a positive integer value',
				'name' =>'integerValue',
				'value' => '1',
				'default_value' => '1',
				'subcat_label' => 'Enable features',
			),
			'enableJquery' => array(
				'cat' => 'advanced',
				'subcat_name' => 'file',
				'subcat' => 'c/file/10z',
				'type' => 'boolean',
				'label' => 'enableJquery: Insert jQuery plugin',
				'name' =>'enableJquery',
				'value' => '1',
				'default_value' => '1',
				'subcat_label' => 'Files',
			),
		);

		$configurationItemRepository = $this->getAccessibleMock(
			'TYPO3\\CMS\\Extensionmanager\\Domain\\Repository\\ConfigurationItemRepository',
			array('mergeWithExistingConfiguration')
		);
		$configurationItemRepository->_set(
			'objectManager',
			$this->injectedObjectManagerMock
		);
		$configurationUtilityMock = $this->getMock('TYPO3\\CMS\\Extensionmanager\\Utility\\ConfigurationUtility');
		$configurationUtilityMock
			->expects($this->once())
			->method('getDefaultConfigurationFromExtConfTemplateAsValuedArray')
			->will($this->returnValue($flatConfigurationItemArray));
		$this->injectedObjectManagerMock
			->expects($this->any())
			->method('get')
			->will($this->returnValue($configurationUtilityMock));
		$configurationItemRepository
			->expects($this->any())
			->method('mergeWithExistingConfiguration')
			->will($this->returnValue($flatConfigurationItemArray));

		$expectedArray = array(
			'basic' => array(
				'enable' => array(
					'item1' => array(
						'cat' => 'basic',
						'subcat_name' => 'enable',
						'subcat' => 'a/enable/10z',
						'type' => 'string',
						'label' => 'Item 1: This is the first configuration item',
						'name' =>'item1',
						'value' => 'one',
						'default_value' => 'one',
						'subcat_label' => 'Enable features',
						'labels' => array(
							0 => 'Item 1',
							1 => 'This is the first configuration item'
						)
					),
					'integerValue' => array(
						'cat' => 'basic',
						'subcat_name' => 'enable',
						'subcat' => 'a/enable/20z',
						'type' => 'int+',
						'label' => 'Integer Value: Please insert a positive integer value',
						'name' =>'integerValue',
						'value' => '1',
						'default_value' => '1',
						'subcat_label' => 'Enable features',
						'labels' => array(
							0 => 'Integer Value',
							1 => 'Please insert a positive integer value'
						)
					)
				)
			),
			'advanced' => array(
				'file' => array(
					'enableJquery' => array(
						'cat' => 'advanced',
						'subcat_name' => 'file',
						'subcat' => 'c/file/10z',
						'type' => 'boolean',
						'label' => 'enableJquery: Insert jQuery plugin',
						'name' =>'enableJquery',
						'value' => '1',
						'default_value' => '1',
						'subcat_label' => 'Files',
						'labels' => array(
							0 => 'enableJquery',
							1 => 'Insert jQuery plugin'
						)
					),
				)
			)
		);

		$this->assertSame(
			$expectedArray,
			$configurationItemRepository->_call('getConfigurationArrayFromExtensionKey', uniqid('some_extension'))
		);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addMetaInformationUnsetsOriginalConfigurationMetaKey() {
		$configuration = array(
			'__meta__' => 'metaInformation',
			'test123' => 'test123'
		);
		$this->configurationItemRepository->_callRef('addMetaInformation', $configuration);
		$this->assertEquals(array('test123' => 'test123'), $configuration);
	}

	/**
	 * @test
	 * @return void
	 */
	public function addMetaInformationReturnsMetaInformation() {
		$configuration = array(
			'__meta__' => 'metaInformation',
			'test123' => 'test123'
		);
		$meta = $this->configurationItemRepository->_callRef('addMetaInformation', $configuration);
		$this->assertEquals('metaInformation', $meta);
	}

	/**
	 * @return array
	 */
	public function extractInformationForConfigFieldsOfTypeUserAddsGenericAndTypeInformationDataProvider() {
		return array(
			array(
				array(
					'cat' => 'basic',
					'subcat_name' => 'enable',
					'subcat' => 'a/enable/z',
					'type' => 'user[EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend]',
					'label' => 'Frontend configuration check',
					'name' => 'checkConfigurationFE',
					'value' => 0,
					'default_value' => 0,
					'comparisonGeneric' => 'EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationFrontend'
				)
			),
			array(
				array(
					'cat' => 'basic',
					'subcat_name' => 'enable',
					'subcat' => 'a/enable/z',
					'type' => 'user[EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationBackend]',
					'label' => 'Backend configuration check',
					'name' => 'checkConfigurationBE',
					'value' => 0,
					'default_value' => 0,
					'comparisonGeneric' => 'EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->checkConfigurationBackend'
				)
			),
			array(
				array(
					'cat' => 'basic',
					'subcat_name' => 'enable',
					'subcat' => 'a/enable/z',
					'type' => 'user[EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->buildHashMethodSelectorFE]',
					'label' => 'Hashing method for the frontend: Defines salted hashing method to use. Choose "Portable PHP password hashing" to stay compatible with other CMS (e.g. Drupal, Wordpress). Choose "MD5 salted hashing" to reuse TYPO3 passwords for OS level authentication (other servers could use TYPO3 passwords). Choose "Blowfish salted hashing" for advanced security to reuse passwords on OS level (Blowfish might not be supported on your system TODO).',
					'name' => 'FE.saltedPWHashingMethod',
					'value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
					'default_value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\PhpassSalt',
					'comparisonGeneric' => 'EXT:saltedpasswords/classes/class.tx_saltedpasswords_emconfhelper.php:TYPO3\\CMS\\Saltedpasswords\\Utility\\ExtensionManagerConfigurationUtility->buildHashMethodSelectorFE'
				)
			)
		);
	}

	/**
	 * @test
	 * @dataProvider extractInformationForConfigFieldsOfTypeUserAddsGenericAndTypeInformationDataProvider
	 * @param $configurationOption
	 * @return void
	 */
	public function extractInformationForConfigFieldsOfTypeUserAddsGenericAndTypeInformation($configurationOption) {
		$configurationOptionModified = $this->configurationItemRepository->_callRef('extractInformationForConfigFieldsOfTypeUser', $configurationOption);
		$this->assertEquals('user', $configurationOptionModified['type']);
		$this->assertEquals($configurationOption['comparisonGeneric'], $configurationOptionModified['generic']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function extractInformationForConfigFieldsOfTypeOptionsAddsGenericTypeAndLabelInformation() {
		$option = array(
			'cat' => 'basic',
			'subcat_name' => 'enable',
			'subcat' => 'a/enable/100z',
			'type' => 'options[Minimal (Most features disabled. Administrator needs to enable them using TypoScript. For advanced administrators only.),Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.),Demo (Show-off configuration. Includes pre-configured styles. Not for production environments.)]',
			'label' => 'Default configuration settings',
			'name' => 'defaultConfiguration',
			'value' => 'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)',
			'default_value' => 'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)',
			'genericComparisonValue' => array(
				'Minimal (Most features disabled. Administrator needs to enable them using TypoScript. For advanced administrators only.)' => 'Minimal (Most features disabled. Administrator needs to enable them using TypoScript. For advanced administrators only.)',
				'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)' => 'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)',
				'Demo (Show-off configuration. Includes pre-configured styles. Not for production environments.)' => 'Demo (Show-off configuration. Includes pre-configured styles. Not for production environments.)'
			),
			'typeComparisonValue' => 'options'
		);
		$optionModified = $this->configurationItemRepository->_callRef('extractInformationForConfigFieldsOfTypeOptions', $option);
		$this->assertArrayHasKey('generic', $optionModified);
		$this->assertArrayHasKey('type', $optionModified);
		$this->assertArrayHasKey('label', $optionModified);
		$this->assertEquals($option['genericComparisonValue'], $optionModified['generic']);
		$this->assertEquals($option['typeComparisonValue'], $optionModified['type']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function extractInformationForConfigFieldsOfTypeOptionsWithLabelsAndValuesAddsGenericTypeAndLabelInformation() {
		$option = array(
			'cat' => 'basic',
			'subcat_name' => 'enable',
			'subcat' => 'a/enable/100z',
			'type' => 'options[Minimal (Most features disabled. Administrator needs to enable them using TypoScript. For advanced administrators only.)=MINIMAL,Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.) = TYPICAL,Demo (Show-off configuration. Includes pre-configured styles. Not for production environments.)=DEMO]',
			'label' => 'Default configuration settings',
			'name' => 'defaultConfiguration',
			'value' => 'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)',
			'default_value' => 'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)',
			'genericComparisonValue' => array(
				'Minimal (Most features disabled. Administrator needs to enable them using TypoScript. For advanced administrators only.)' => 'MINIMAL',
				'Typical (Most commonly used features are enabled. Select this option if you are unsure which one to use.)' => 'TYPICAL',
				'Demo (Show-off configuration. Includes pre-configured styles. Not for production environments.)' => 'DEMO'
			),
			'typeComparisonValue' => 'options'
		);
		$optionModified = $this->configurationItemRepository->_callRef('extractInformationForConfigFieldsOfTypeOptions', $option);
		$this->assertArrayHasKey('generic', $optionModified);
		$this->assertArrayHasKey('type', $optionModified);
		$this->assertArrayHasKey('label', $optionModified);
		$this->assertEquals($option['genericComparisonValue'], $optionModified['generic']);
		$this->assertEquals($option['typeComparisonValue'], $optionModified['type']);
	}

	/**
	 * @test
	 * @return void
	 */
	public function mergeDefaultConfigurationCatchesExceptionOfConfigurationManagerIfNoLocalConfigurationExists() {
		$exception = $this->getMock('RuntimeException');
		$configurationManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$configurationManagerMock
			->expects($this->once())
			->method('getConfigurationValueByPath')
			->will($this->throwException($exception));
		$this->injectedObjectManagerMock
			->expects($this->any())
			->method('get')
			->will($this->returnValue($configurationManagerMock));

		$this->configurationItemRepository->_call(
			'mergeWithExistingConfiguration',
			array(),
			uniqid('not_existing_extension')
		);
	}

	/**
	 * @test
	 * @return void
	 */
	public function mergeDefaultConfigurationWithNoCurrentValuesReturnsTheDefaultConfiguration() {
		$exception = $this->getMock('RuntimeException');
		$configurationManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$configurationManagerMock
			->expects($this->once())
			->method('getConfigurationValueByPath')
			->will($this->throwException($exception));
		$this->injectedObjectManagerMock
			->expects($this->any())
			->method('get')
			->will($this->returnValue($configurationManagerMock));
		$defaultConfiguration = array(
			'foo' => 'bar'
		);
		$configuration = $this->configurationItemRepository->_call(
			'mergeWithExistingConfiguration',
			$defaultConfiguration,
			uniqid('not_existing_extension')
		);
		$this->assertEquals($defaultConfiguration, $configuration);
	}

	/**
	 * @test
	 * @return void
	 */
	public function mergeWithExistingConfigurationOverwritesDefaultKeysWithCurrent() {
		$localConfiguration = serialize(array(
			'FE.' => array(
				'enabled' => '1',
				'saltedPWHashingMethod' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltInterface_sha1'
			),
			'CLI.' => array(
				'enabled' => '0'
			)
		));

		$configurationManagerMock = $this->getMock('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$configurationManagerMock
			->expects($this->once())
			->method('getConfigurationValueByPath')
			->with('EXT/extConf/testextensionkey')
			->will($this->returnValue($localConfiguration));

		$this->injectedObjectManagerMock
			->expects($this->any())
			->method('get')
			->will($this->returnValue($configurationManagerMock));

		$defaultConfiguration = array(
			'FE.enabled' => array(
				'value' => '0'
			),
			'FE.saltedPWHashingMethod' => array(
				'value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt'
			),
			'BE.enabled' => array(
				'value' => '1'
			),
			'BE.saltedPWHashingMethod' => array(
				'value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt'
			)
		);

		$expectedResult = array(
			'FE.enabled' => array(
				'value' => '1'
			),
			'FE.saltedPWHashingMethod' => array(
				'value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\SaltInterface_sha1'
			),
			'BE.enabled' => array(
				'value' => '1'
			),
			'BE.saltedPWHashingMethod' => array(
				'value' => 'TYPO3\\CMS\\Saltedpasswords\\Salt\\Md5Salt'
			),
			'CLI.enabled' => array(
				'value' => '0'
			)
		);

		$actualResult = $this->configurationItemRepository->_call(
			'mergeWithExistingConfiguration',
			$defaultConfiguration,
			'testextensionkey'
		);

		$this->assertEquals($expectedResult, $actualResult);
	}
}


?>