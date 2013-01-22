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
 */
class ConfigurationItemRepository {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Configuration\ConfigurationManager
	 */
	protected $configurationManager;

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
	 * Inject configuration manager
	 *
	 * @param \TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager
	 * @return void
	 */
	public function injectConfigurationManager(\TYPO3\CMS\Core\Configuration\ConfigurationManager $configurationManager) {
		$this->configurationManager = $configurationManager;
	}

	/**
	 * Find configuration options by extension
	 *
	 * @param array $extension array with extension information
	 * @return null|\SplObjectStorage
	 */
	public function findByExtension(array $extension) {
		$configRaw = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(
			PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt'
		);
		$configurationObjectStorage = NULL;
		if ($configRaw) {
			$configurationArray = $this->convertRawConfigurationToArray($configRaw, $extension);
			$configurationObjectStorage = $this->convertHierarchicArrayToObject($configurationArray);
		}
		return $configurationObjectStorage;
	}

	/**
	 * Converts the raw configuration file content to an configuration object storage
	 *
	 * @param string $configRaw
	 * @param array $extension array with extension information
	 * @return array
	 */
	protected function convertRawConfigurationToArray($configRaw, array $extension) {
		$defaultConfiguration = $this->createArrayFromConstants($configRaw, $extension);
		$metaInformation = $this->addMetaInformation($defaultConfiguration);
		$configuration = $this->mergeWithExistingConfiguration($defaultConfiguration, $extension);
		$hierarchicConfiguration = array();
		foreach ($configuration as $configurationOption) {
			$hierarchicConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
				$this->buildConfigurationArray($configurationOption, $extension),
				$hierarchicConfiguration
			);
		}

		// Flip category array as it was merged the other way around
		$hierarchicConfiguration = array_reverse($hierarchicConfiguration);

		// Sort configurations of each subcategory
		foreach ($hierarchicConfiguration as &$catConfigurationArray) {
			foreach ($catConfigurationArray as &$subcatConfigurationArray) {
				uasort($subcatConfigurationArray, function ($a, $b) {
					return strnatcmp($a['subcat'], $b['subcat']);
				});
			}
			unset($subcatConfigurationArray);
		}
		unset($tempConfiguration);

		return \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($hierarchicConfiguration, $metaInformation);
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
		preg_match('/options\[(.*)\]/is', $configurationOption['type'], $typeMatches);
		$optionItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $typeMatches[1]);
		foreach ($optionItems as $optionItem) {
			$optionPair = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('=', $optionItem);
			if (count($optionPair) === 2) {
				$configurationOption['generic'][$optionPair[0]] = $optionPair[1];
			} else {
				$configurationOption['generic'][$optionPair[0]] = $optionPair[0];
			}
		}
		$configurationOption['type'] = 'options';
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
	 * Create a flat array of configuration options from
	 * incoming raw configuration file using core's typoscript parser.
	 *
	 * Generates an array from the typoscript style constants and
	 * adds meta data like TSConstantEditor comments
	 *
	 * Result is an array, with configuration item as array keys,
	 * and item properties as key-value sub-array:
	 *
	 * array(
	 *   'fooOption' => array(
	 *     'type' => 'string',
	 *     'value' => 'foo',
	 *     ...
	 *   ),
	 *   'barOption' => array(
	 *     'type' => boolean,
	 *     'default_value' => 0,
	 *     ...
	 *   ),
	 *   ...
	 * )
	 *
	 * @param string $configRaw Raw configuration string
	 * @param array $extension Extension name
	 * @return array
	 *
	 * @TODO: Code smell, this method is used in ConfigurationUtility as well.
	 * 		This code should be moved elsewhere, it is not cool to have this
	 * 		public method here in the repository class.
	 */
	public function createArrayFromConstants($configRaw, array $extension) {
		$tsStyleConfig = $this->getT3libTsStyleConfig();
		$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;
		$theConstants = $tsStyleConfig->ext_initTSstyleConfig($configRaw, $extension['siteRelPath'], PATH_site . $extension['siteRelPath'], $GLOBALS['BACK_PATH']);

		// Loop through configuration items, see if it is assigned to a sub category
		// and add the sub category label to the item property if so.
		foreach ($theConstants as $configurationOptionName => $configurationOption) {
			if (
				array_key_exists('subcat_name', $configurationOption)
				&& isset($tsStyleConfig->subCategories[$configurationOption['subcat_name']])
				&& isset($tsStyleConfig->subCategories[$configurationOption['subcat_name']][0])
			) {
				$theConstants[$configurationOptionName]['subcat_label'] = $tsStyleConfig->subCategories[$configurationOption['subcat_name']][0];
			}
		}

		// Set up the additional descriptions
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
				$this->configurationManager->getConfigurationValueByPath(
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
	 * @return \SplObjectStorage
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
					// Set sub category label if configuration item contains a subcat label.
					// The sub category label is set multiple times if there is more than one item
					// in a sub category, but that is ok since all items of one sub category
					// share the same label. @see createArrayFromConstants()
					if (array_key_exists('subcat_label', $configurationItem)) {
						$configurationSubcategoryObject->setLabel($configurationItem['subcat_label']);
					}

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