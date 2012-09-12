<?php
namespace TYPO3\CMS\Extensionmanager\Domain\Repository;

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
class ConfigurationItemRepository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * Injects the object manager
	 *
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * Find configuration options by extension
	 *
	 * @param array $extension array with extension information
	 * @return null|SplObjectStorage
	 */
	public function findByExtension(array $extension) {
		$configRaw = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt');
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
			$hierarchicConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($this->buildConfigurationArray($configurationOption, $extension), $hierarchicConfiguration);
		}
		$configurationObjectStorage = $this->convertHierarchicArrayToObject(\TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($hierarchicConfiguration, $metaInformation));
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
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'user')) {
			$configurationOption = $this->extractInformationForConfigFieldsOfTypeUser($configurationOption);
		} elseif (\TYPO3\CMS\Core\Utility\GeneralUtility::isFirstPartOfStr($configurationOption['type'], 'options')) {
			$configurationOption = $this->extractInformationForConfigFieldsOfTypeOptions($configurationOption);
		}
		if (\TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($configurationOption['label'], $extension['key'])) {
			$configurationOption['label'] = \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate($configurationOption['label'], $extension['key']);
		}
		$configurationOption['labels'] = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(':', $configurationOption['label'], FALSE, 2);
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
		preg_match('/options\\[(.*)\\]/is', $configurationOption['type'], $typeMatches);
		preg_match('/options\\[(.*)\\]/is', $configurationOption['label'], $labelMatches);
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
		preg_match('/user\\[(.*)\\]/is', $configurationOption['type'], $matches);
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
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig($configRaw, $extension['siteRelPath'], PATH_site . $extension['siteRelPath'], $GLOBALS['BACK_PATH']);
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
	 * @return \TYPO3\CMS\Core\TypoScript\ConfigurationForm
	 */
	protected function getT3libTsStyleConfig() {
		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm');
	}

	/**
	 * Merge new configuration with existing configuration
	 *
	 * @param array $configuration the new configuration array
	 * @param array $extension the extension information
	 * @return array
	 */
	protected function mergeWithExistingConfiguration(array $configuration, array $extension) {
		try {
			$currentExtensionConfig = unserialize(
				\TYPO3\CMS\Core\Configuration\ConfigurationManager::getConfigurationValueByPath(
					'EXT/extConf/' . $extension['key']
				)
			);
		} catch (\RuntimeException $e) {
			$currentExtensionConfig = array();
		}
		$flatExtensionConfig = \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($currentExtensionConfig);
		$valuedCurrentExtensionConfig = array();
		foreach ($flatExtensionConfig as $key => $value) {
			$valuedCurrentExtensionConfig[$key]['value'] = $value;
		}
		$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($configuration, $valuedCurrentExtensionConfig);
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
		$configurationObjectStorage = new \SplObjectStorage();
		foreach ($configuration as $category => $subcategory) {
			/** @var $configurationCategoryObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationCategory */
			$configurationCategoryObject = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\ConfigurationCategory');
			$configurationCategoryObject->setName($category);
			if ($subcategory['highlightText']) {
				$configurationCategoryObject->setHighlightText($subcategory['highlightText']);
				unset($subcategory['highlightText']);
			}
			foreach ($subcategory as $subcatName => $configurationItems) {
				/** @var $configurationSubcategoryObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationSubcategory */
				$configurationSubcategoryObject = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\ConfigurationSubcategory');
				$configurationSubcategoryObject->setName($subcatName);
				foreach ($configurationItems as $configurationItem) {
					/** @var $configurationObject \TYPO3\CMS\Extensionmanager\Domain\Model\ConfigurationItem */
					$configurationObject = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Domain\\Model\\ConfigurationItem');
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