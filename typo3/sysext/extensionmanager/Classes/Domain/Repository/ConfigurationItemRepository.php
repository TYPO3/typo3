<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012
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
 * A repository for extensions
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
	 * @param array $extension
	 * @return null|SplObjectStorage
	 */
	public function findByExtension(array $extension) {
		$configRaw = t3lib_div::getUrl(PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt');
		$configurationObjectStorage = NULL;
		if ($configRaw) {
			$configuration = $this->createArrayFromConstants($configRaw, $extension);
			$metaInformation = $configuration['__meta__'] ? $configuration['__meta__'] : array();
			unset($configuration['__meta__']);
			$configuration = $this->mergeWithExistingConfiguration($configuration, $extension);
			$hierarchicConfiguration = array();
			foreach($configuration as $configurationOption) {
				if(t3lib_div::isFirstPartOfStr($configurationOption['type'], 'user')) {
					preg_match('/user\[(.*)\]/is', $configurationOption['type'], $matches);
					$configurationOption['generic'] = $matches[1];
					$configurationOption['type'] = 'user';
				} else if(t3lib_div::isFirstPartOfStr($configurationOption['type'], 'options')) {
					preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
					preg_match('/options\[(.*)\]/is', $configurationOption['label'], $labelMatches);
					$optionValues = explode(',', $typeMatches[1]);
					$optionLabels = explode(',', $labelMatches[1]);
					$configurationOption['generic'] = $labelMatches ? array_combine($optionLabels, $optionValues) :  array_combine($optionValues, $optionValues);
					$configurationOption['type'] = 'options';
					$configurationOption['label'] = str_replace($labelMatches[0], '', $configurationOption['label']);
				}
				if(Tx_Extbase_Utility_Localization::translate($configurationOption['label'], $extension['key'])) {
					$configurationOption['label'] = Tx_Extbase_Utility_Localization::translate($configurationOption['label'], $extension['key']);
				}
				$configurationOption['labels'] = t3lib_div::trimExplode(
					':',
					$configurationOption['label'],
					FALSE,
					2
				);
				$configurationOption['subcat_name'] = $configurationOption['subcat_name'] ? $configurationOption['subcat_name'] : '__default';
					// build temporary hierarchy
				$hierarchicConfiguration[$configurationOption['cat']][$configurationOption['subcat_name']][$configurationOption['name']] = $configurationOption;
			}
			$configurationObjectStorage = $this->convertHierarchicArrayToObject(t3lib_div::array_merge_recursive_overrule($hierarchicConfiguration, $metaInformation));
		}
		return $configurationObjectStorage;
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
		/** @var $tsStyleConfig t3lib_tsStyleConfig */
		$tsStyleConfig = t3lib_div::makeInstance('t3lib_tsStyleConfig');
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
			$configRaw,
			$extension['siteRelPath'],
			PATH_site . $extension['siteRelPath'],
			$GLOBALS['BACK_PATH']
		);
		foreach($tsStyleConfig->setup['constants']['TSConstantEditor.'] as $category => $highlights) {
			$theConstants['__meta__'][rtrim($category, '.')]['highlightText'] = $highlights['description'];
			foreach($highlights as $highlightNumber => $value) {
				if(rtrim($category, '.') == $theConstants[$value]['cat']) {
					$theConstants[$value]['highlight'] = $highlightNumber;
				}
			}
		}
		return $theConstants;
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
		if(is_array($currentExtensionConfig)) {
			$configuration =  t3lib_div::array_merge_recursive_overrule($configuration, $currentExtensionConfig);
		}
		return $configuration;
	}

	/**
	 * Converts a hierarchic configuration array to an hierarchic object storage structure
	 *
	 * @param array $configuration
	 * @return SplObjectStorage
	 */
	protected function convertHierarchicArrayToObject(array $configuration) {
		$configurationObjectStorage = new SplObjectStorage();
		foreach($configuration as $category => $subcategory) {
			/** @var $configurationCategoryObject Tx_Extensionmanager_Domain_Model_ConfigurationCategory */
			$configurationCategoryObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationCategory');
			$configurationCategoryObject->setName($category);
			if($subcategory['highlightText']) {
				$configurationCategoryObject->setHighlightText($subcategory['highlightText']);
				unset($subcategory['highlightText']);
			}
			foreach($subcategory as $subcatName => $configurationItems) {
				/** @var $configurationSubcategoryObject Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory */
				$configurationSubcategoryObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationSubcategory');
				$configurationSubcategoryObject->setName($subcatName);
				foreach($configurationItems as $configurationItem) {
					/** @var $configurationObject Tx_Extensionmanager_Domain_Model_ConfigurationItem */
					$configurationObject = $this->objectManager->get('Tx_Extensionmanager_Domain_Model_ConfigurationItem');
					if(isset($configurationItem['generic'])) {
						$configurationObject->setGeneric($configurationItem['generic']);
					}
					if(isset($configurationItem['cat'])) {
						$configurationObject->setCategory($configurationItem['cat']);
					}
					if(isset($configurationItem['subcat_name'])) {
						$configurationObject->setSubCategory($configurationItem['subcat_name']);
					}
					if(isset($configurationItem['labels']) && isset($configurationItem['labels'][0])) {
						$configurationObject->setLabelHeadline($configurationItem['labels'][0]);
					}
					if(isset($configurationItem['labels']) && isset($configurationItem['labels'][1])) {
						$configurationObject->setLabelText($configurationItem['labels'][1]);
					}
					if(isset($configurationItem['type'])) {
						$configurationObject->setType($configurationItem['type']);
					}
					if(isset($configurationItem['name'])) {
						$configurationObject->setName($configurationItem['name']);
					}
					if(isset($configurationItem['value'])) {
						$configurationObject->setValue($configurationItem['value']);
					}
					if(isset($configurationItem['highlight'])) {
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
