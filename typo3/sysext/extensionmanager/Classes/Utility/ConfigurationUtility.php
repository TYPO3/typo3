<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Susanne Moog, <susanne.moog@typo3.org>
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
 * Utility for dealing with ext_emconf and ext_conf_template settings
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class ConfigurationUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * Get default configuration from ext_conf_template of an extension
	 * and save as initial configuration to LocalConfiguration ['EXT']['extConf'].
	 *
	 * Used by the InstallUtility to initialize local extension config.
	 *
	 * @param string $extensionKey Extension key
	 * @return void
	 */
	public function saveDefaultConfiguration($extensionKey) {
		$currentConfiguration = $this->getCurrentConfiguration($extensionKey);
		$nestedConfiguration = $this->convertValuedToNestedConfiguration($currentConfiguration);
		$this->writeConfiguration($nestedConfiguration, $extensionKey);
	}

	/**
	 * Writes extension specific configuration to LocalConfiguration file
	 * in array ['EXT']['extConf'][$extensionKey].
	 *
	 * Removes core cache files afterwards.
	 *
	 * This low level method expects a nested configuration array that
	 * was already merged with default configuration and maybe new form values.
	 *
	 * @param array $configuration Configuration to save
	 * @param string $extensionKey Extension key
	 * @return void
	 */
	public function writeConfiguration(array $configuration = array(), $extensionKey) {
		/** @var $configurationManager \TYPO3\CMS\Core\Configuration\ConfigurationManager */
		$configurationManager = $this->objectManager->get('TYPO3\\CMS\\Core\\Configuration\\ConfigurationManager');
		$configurationManager->setLocalConfigurationValueByPath('EXT/extConf/' . $extensionKey, serialize($configuration));
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::removeCacheFiles();
	}

	/**
	 * Get current configuration of an extension. Will return the configuration as a valued object
	 *
	 * @param string $extensionKey
	 * @return array
	 */
	public function getCurrentConfiguration($extensionKey) {
		$defaultConfiguration = $this->getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey);
		$currentExtensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);
		$currentExtensionConfig = is_array($currentExtensionConfig) ? $currentExtensionConfig : array();
		$currentExtensionConfig = $this->convertNestedToValuedConfiguration($currentExtensionConfig);
		$currentFullConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule(
			$defaultConfiguration,
			$currentExtensionConfig
		);
		return $currentFullConfiguration;
	}

	/**
	 * Create a flat array of configuration options from
	 * ext_conf_template.txt of an extension using core's typoscript parser.
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
	 * @param string $extensionKey Extension key
	 * @return array
	 */
	public function getDefaultConfigurationFromExtConfTemplateAsValuedArray($extensionKey) {
		$rawConfigurationString = $this->getDefaultConfigurationRawString($extensionKey);

		$theConstants = array();

		if (strlen($rawConfigurationString) > 0) {
			$extensionPathInformation = $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];

			$tsStyleConfig = $this->objectManager->get('TYPO3\\CMS\\Core\\TypoScript\\ConfigurationForm');
			$tsStyleConfig->doNotSortCategoriesBeforeMakingForm = TRUE;

			$theConstants = $tsStyleConfig->ext_initTSstyleConfig(
				$rawConfigurationString,
				$extensionPathInformation['siteRelPath'],
				PATH_site . $extensionPathInformation['siteRelPath'],
				$GLOBALS['BACK_PATH']
			);

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
		}

		return $theConstants;
	}

	/**
	 * Return content of an extensions ext_conf_template.txt file if
	 * the file exists, empty string if file does not exist.
	 *
	 * @param string $extensionKey Extension key
	 * @return string
	 */
	protected function getDefaultConfigurationRawString($extensionKey) {
		$rawString = '';
		$extConfTemplateFileLocation = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName(
			'EXT:' . $extensionKey . '/ext_conf_template.txt',
			FALSE
		);
		if (file_exists($extConfTemplateFileLocation)) {
			$rawString = file_get_contents($extConfTemplateFileLocation);
		}
		return $rawString;
	}

	/**
	 * Converts a valued configuration to a nested configuration.
	 *
	 * array('first.second' => array('value' => 1))
	 * will become
	 * array('first.' => array('second' => ))
	 *
	 * @param array $valuedConfiguration
	 * @return array
	 */
	public function convertValuedToNestedConfiguration(array $valuedConfiguration) {
		$nestedConfiguration = array();
		foreach ($valuedConfiguration as $name => $section) {
			$path = str_replace('.', './', $name);
			$nestedConfiguration = \TYPO3\CMS\Core\Utility\ArrayUtility::setValueByPath($nestedConfiguration, $path, $section['value'], '/');
		}
		return $nestedConfiguration;
	}

	/**
	 * Convert a nested configuration to a valued configuration
	 *
	 * array('first.' => array('second' => 1))
	 * will become
	 * array('first.second' => array('value' => 1)
	 * @param array $nestedConfiguration
	 * @return array
	 */
	public function convertNestedToValuedConfiguration(array $nestedConfiguration) {
		$flatExtensionConfig = \TYPO3\CMS\Core\Utility\ArrayUtility::flatten($nestedConfiguration);
		$valuedCurrentExtensionConfig = array();
		foreach ($flatExtensionConfig as $key => $value) {
			$valuedCurrentExtensionConfig[$key]['value'] = $value;
		}
		return $valuedCurrentExtensionConfig;
	}
}

?>
