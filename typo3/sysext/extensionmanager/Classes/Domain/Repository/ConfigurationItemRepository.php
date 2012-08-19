<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <typo3@susannemoog.de>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A repository for extension configuration items
 *
 * @author Susanne Moog <typo3@susannemoog.de>
 * @package Extension Manager
 * @subpackage Repository
 */
class Tx_Extensionmanager_Domain_Repository_ConfigurationItemRepository {

	/**
	 * @var Tx_Extbase_Object_ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param Tx_Extbase_Object_ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(Tx_Extbase_Object_ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Find configuration options by extension
	 *
	 * @param array $extension array with extension information
	 * @return null|SplObjectStorage
	 */
	public function findByExtension(array $extension) {
		$configRaw = t3lib_div::getUrl(PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt');
		$configurationObjectStorage = NULL;
		if ($configRaw) {
			$configurationObjectStorage = $this->convertRawConfigurationToObject($configRaw, $extension);
		}
		return $configurationObjectStorage;
	}

	/**
	 * Converts the raw configuration file content to an configuration object storage
	 *
	 * @param string $configRaw
	 * @param array $extension array with extension information
	 * @return SplObjectStorage
	 */
	protected function convertRawConfigurationToObject($configRaw, array $extension) {
		$defaultConfiguration = $this->createArrayFromConstants($configRaw, $extension);
		$metaInformation = $this->addMetaInformation($defaultConfiguration);
		$configuration = $this->mergeWithExistingConfiguration($defaultConfiguration, $extension);
		$hierarchicConfiguration = array();

		foreach ($configuration as $configurationOption) {
			$hierarchicConfiguration = t3lib_div::array_merge_recursive_overrule(
				$this->buildConfigurationArray($configurationOption, $extension),
				$hierarchicConfiguration
			);
		}
		$configurationObjectStorage = $this->convertHierarchicArrayToObject(
			t3lib_div::array_merge_recursive_overrule(
				$hierarchicConfiguration,
				$metaInformation
			)
		);
		return $configurationObjectStorage;
	}

	/**
	 * Builds a configuration array from each line (option) of the config file
	 *
	 * @param string $configurationOption config file line representing one setting
	 * @param array $extension
	 * @return array
	 */
	protected function buildConfigurationArray($configurationOption, $extension) {
		$hierarchicConfiguration = array();
		if (t3lib_div::isFirstPartOfStr($configurationOption['type'], 'user')) {
			$configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
		} elseif (t3lib_div::isFirstPartOfStr($configurationOption['type'], 'options')) {
			$configurationOption = $this->extractInformationForConfigFieldsOfTypeOptions($configurationOption);
		}
		if (Tx_Extbase_Utility_Localization::translate($configurationOption['label'], $extension['key'])) {
			$configurationOption['label'] = Tx_Extbase_Utility_Localization::translate($configurationOption['label'], $extension['key']);
		}
		$configurationOption['labels'] = t3lib_div::trimExplode(
			':',
			$configurationOption['label'],
			FALSE,
			2
		);
		$configurationOption['subcat_name'] = $configurationOption['subcat_name'] ? $configurationOption['subcat_name'] : '__default';
		$hierarchicConfiguration[$configurationOption['cat']][$configurationOption['subcat_name']][$configurationOption['name']] = $configurationOption;
		return $hierarchicConfiguration;
	}

	/**
	 * Extracts additional information for fields of type "options"
	 * Extracts "type", "label" and values information
	 *
	 * @param array $configurationOption
	 * @return array
	 */
	protected function extractInformationForConfigFieldsOfTypeOptions(array $configurationOption) {
		preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
		preg_match('/options\[(.*)\]/is', $configurationOption['label'], $labelMatches);
		$optionValues = explode(',', $typeMatches[1]);
		$optionLabels = explode(',', $labelMatches[1]);
		$configurationOption['generic'] = $labelMatches ? array_combine($optionLabels, $optionValues) : array_combine($optionValues, $optionValues);
		$configurationOption['type'] = 'options';
		$configurationOption['label'] = str_replace($labelMatches[0], '', $configurationOption['label']);
		return $configurationOption;
	}

	/**
	 * Extract additional information for fields of type "user"
	 * Extracts "type" and the function to be called
	 *
	 * @param array $configurationOption
	 * @return array
	 */
	protected function extractInformationForConfigFieldsOfTypeUser(array $configurationOption) {
		preg_match('/user\[(.*)\]/is', $configurationOption['type'], $matches);
		$configurationOption['generic'] = $matches[1];
		$configurationOption['type'] = 'user';
		return $configurationOption;
	}

	/**
	 * Gets meta information from configuration array and
	 * returns only the meta information
	 *
	 * @param array $configuration
	 * @return array
	 */
	protected function addMetaInformation(&$configuration) {
		$metaInformation = $configuration['__meta__'] ? $configuration['__meta__'] : array();
		unset($configuration['__meta__']);
		return $metaInformation;
	}

	/**
	 * Generate an array from the typoscript style constants
	 * Add meta data like TSConstantEditor comments
	 *
	 * @param string $configRaw
	 * @param array $extension
	 * @return array
	 */
	public function createArrayFromConstants($configRaw, array $extension) {
		$tsStyleConfig = $this->getT3libTsStyleConfig();
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
			$configRaw,
			$extension['siteRelPath'],
			PATH_site . $extension['siteRelPath'],
			$GLOBALS['BACK_PATH']
		);
		if (isset($tsStyleConfig->setup['constants']['TSConstantEditor.'])) {
			foreach ($tsStyleConfig->setup['constants']['TSConstantEditor.'] as $category => $highlights) {
				$theConstants['__meta__'][rtrim($category, '.')]['highlightText'] = $highlights['description'];
				foreach ($highlights as $highlightNumber => $value) {
					if (rtrim($category, '.') == $theConstants[$value]['cat']) {
						$theConstants[$value]['highlight'] = $highlightNumber;
					}
				}
			}
		}
		return $theConstants;
	}

	/**
	 * Wrapper for makeInstance to make it possible to mock
	 * the class
	 *
	 * @return t3lib_tsStyleConfig
	 */
	protected function getT3libTsStyleConfig() {
		return t3lib_div::makeInstance('t3lib_tsStyleConfig');
	}

	/**
	 * Merge new configuration with existing configuration
	 *
	 * @param array $configuration the new configuration array
	 * @param array $extension the extension information
	 * @return array
	 */
	protected function mergeWithExistingConfiguration(array $configuration, array $extension) {
		$currentExtensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extension['key']]);
		$flatExtensionConfig = t3lib_utility_Array::flatten($currentExtensionConfig);
		$valuedCurrentExtensionConfig = array();

		foreach ($flatExtensionConfig as $key => $value) {
			$valuedCurrentExtensionConfig[$key]['value'] = $value;
		}

		if (is_array($currentExtensionConfig)) {
			$configuration =  t3lib_div::array_merge_recursive_overrule($configuration, $valuedCurrentExtensionConfig);
		}

		return $configuration;
	}

	/**
	 * Converts a hierarchic configuration array to an
	 * hierarchic object storage structure
	 *
	 * @param array $configuration
	 * @return SplObjectStorage
	 */
	protected function convertHierarchicArrayToObject(array $configuration) {
		$configurationObjectStorage = new SplObjectStorage();
		foreach ($configuration as $category => $subcategory) {
			/** @var $configurationCategoryObject Tx_Extensionmanager_Domain_Model_ConfigurationCategory */
			$configurationCategoryObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationCategory');
			$configurationCategoryObject->setName($category);
			if ($subcategory['highlightText']) {
				$configurationCategoryObject->setHighlightText($subcategory['highlightText']);
				unset($subcategory['highlightText']);
			}
			foreach ($subcategory as $subcatName => $configurationItems) {
				/** @var $configurationSubcategoryObject Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory */
				$configurationSubcategoryObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory');
				$configurationSubcategoryObject->setName($subcatName);
				foreach ($configurationItems as $configurationItem) {
					/** @var $configurationObject Tx_Extensionmanager_Domain_Model_ConfigurationItem */
					$configurationObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationItem');
					if (isset($configurationItem['generic'])) {
						$configurationObject->setGeneric($configurationItem['generic']);
					}
					if (isset($configurationItem['cat'])) {
						$configurationObject->setCategory($configurationItem['cat']);
					}
					if (isset($configurationItem['subcat_name'])) {
						$configurationObject->setSubCategory($configurationItem['subcat_name']);
					}
					if (isset($configurationItem['labels']) && isset($configurationItem['labels'][0])) {
						$configurationObject->setLabelHeadline($configurationItem['labels'][0]);
					}
					if (isset($configurationItem['labels']) && isset($configurationItem['labels'][1])) {
						$configurationObject->setLabelText($configurationItem['labels'][1]);
					}
					if (isset($configurationItem['type'])) {
						$configurationObject->setType($configurationItem['type']);
					}
					if (isset($configurationItem['name'])) {
						$configurationObject->setName($configurationItem['name']);
					}
					if (isset($configurationItem['value'])) {
						$configurationObject->setValue($configurationItem['value']);
					}
					if (isset($configurationItem['highlight'])) {
						$configurationObject->setHighlight($configurationItem['highlight']);
					}
					$configurationSubcategoryObject->addItem($configurationObject);
				}
				$configurationCategoryObject->addSubcategory($configurationSubcategoryObject);
			}
			$configurationObjectStorage->attach($configurationCategoryObject);
		}
		return $configurationObjectStorage;
	}
}
?>