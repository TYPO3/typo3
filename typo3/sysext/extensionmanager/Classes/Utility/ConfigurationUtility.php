<?php
namespace TYPO3\CMS\Extensionmanager\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Susanne Moog, <susanne.moog@typo3.org>
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
 * Utility for dealing with ext_emconf
 *
 * @author Susanne Moog <susanne.moog@typo3.org>
 */
class ConfigurationUtility implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository
	 */
	protected $configurationItemRepository;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository $configurationItemRepository
	 * @return void
	 */
	public function injectConfigurationItemRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ConfigurationItemRepository $configurationItemRepository) {
		$this->configurationItemRepository = $configurationItemRepository;
	}

	/**
	 * Saves default configuration of an extension to localConfiguration
	 *
	 * @param string $extensionKey
	 * @return void
	 */
	public function saveDefaultConfiguration($extensionKey) {
		$currentConfiguration = $this->getCurrentConfiguration($extensionKey);
		$nestedConfiguration = $this->convertValuedToNestedConfiguration($currentConfiguration);
		$this->writeConfiguration($nestedConfiguration, $extensionKey);
	}

	/**
	 * Write extension specific configuration to localconf
	 *
	 * @param array $configuration
	 * @param $extensionKey
	 * @return void
	 */
	public function writeConfiguration(array $configuration, $extensionKey) {
		/** @var $installUtility \TYPO3\CMS\Extensionmanager\Utility\InstallUtility */
		$installUtility = $this->objectManager->get('TYPO3\\CMS\\Extensionmanager\\Utility\\InstallUtility');
		$installUtility->writeExtensionTypoScriptStyleConfigurationToLocalconf($extensionKey, $configuration);
	}

	/**
	 * Get current configuration of an extension
	 *
	 * @param string $extensionKey
	 * @return array
	 */
	public function getCurrentConfiguration($extensionKey) {
		$extension = $GLOBALS['TYPO3_LOADED_EXT'][$extensionKey];
		$defaultConfig = $this->configurationItemRepository->createArrayFromConstants(\TYPO3\CMS\Core\Utility\GeneralUtility::getUrl(PATH_site . $extension['siteRelPath'] . '/ext_conf_template.txt'), $extension);
		$currentExtensionConfig = unserialize($GLOBALS['TYPO3_CONF_VARS']['EXT']['extConf'][$extensionKey]);
		$currentExtensionConfig = is_array($currentExtensionConfig) ? $currentExtensionConfig : array();
		$currentFullConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($defaultConfig, $currentExtensionConfig);
		return $currentFullConfiguration;
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

}


?>