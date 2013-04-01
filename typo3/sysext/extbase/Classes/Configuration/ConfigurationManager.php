<?php
namespace TYPO3\CMS\Extbase\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2013 Extbase Team (http://forge.typo3.org/projects/typo3v4-mvc)
 *  Extbase is a backport of TYPO3 Flow. All credits go to the TYPO3 Flow team.
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
 * A configuration manager following the strategy pattern (GoF315). It hides the concrete
 * implementation of the configuration manager and provides an unified acccess point.
 *
 * Use the shutdown() method to drop the concrete implementation.
 */
class ConfigurationManager implements \TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager
	 */
	protected $concreteConfigurationManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
	 */
	protected $environmentService;

	/**
	 * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
	 * @return void
	 */
	public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager) {
		$this->objectManager = $objectManager;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * Initializes the object
	 *
	 * @return void
	 */
	public function initializeObject() {
		$this->initializeConcreteConfigurationManager();
	}

	/**
	 * @return void
	 */
	protected function initializeConcreteConfigurationManager() {
		if ($this->environmentService->isEnvironmentInFrontendMode()) {
			$this->concreteConfigurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\FrontendConfigurationManager');
		} else {
			$this->concreteConfigurationManager = $this->objectManager->get('TYPO3\\CMS\\Extbase\\Configuration\\BackendConfigurationManager');
		}
	}

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 * @return void
	 */
	public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = NULL) {
		$this->concreteConfigurationManager->setContentObject($contentObject);
	}

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	public function getContentObject() {
		return $this->concreteConfigurationManager->getContentObject();
	}

	/**
	 * Sets the specified raw configuration coming from the outside.
	 * Note that this is a low level method and only makes sense to be used by Extbase internally.
	 *
	 * @param array $configuration The new configuration
	 * @return void
	 */
	public function setConfiguration(array $configuration = array()) {
		$this->concreteConfigurationManager->setConfiguration($configuration);
	}

	/**
	 * Returns the specified configuration.
	 * The actual configuration will be merged from different sources in a defined order.
	 *
	 * You can get the following types of configuration invoking:
	 * CONFIGURATION_TYPE_EXTBASE: Extbase settings
	 * CONFIGURATION_TYPE_FRAMEWORK: the current module/plugin settings
	 * CONFIGURATION_TYPE_TYPOSCRIPT: a raw TS array
	 *
	 * Note that this is a low level method and only makes sense to be used by Extbase internally.
	 *
	 * @param string $configurationType The kind of configuration to fetch - must be one of the CONFIGURATION_TYPE_* constants
	 * @param string $extensionName if specified, the configuration for the given extension will be returned.
	 * @param string $pluginName if specified, the configuration for the given plugin will be returned.
	 * @throws Exception\InvalidConfigurationTypeException
	 * @return array The configuration
	 */
	public function getConfiguration($configurationType, $extensionName = NULL, $pluginName = NULL) {
		switch ($configurationType) {
			case self::CONFIGURATION_TYPE_SETTINGS:
				$configuration = $this->concreteConfigurationManager->getConfiguration($extensionName, $pluginName);
				return $configuration['settings'];
			case self::CONFIGURATION_TYPE_FRAMEWORK:
				return $this->concreteConfigurationManager->getConfiguration($extensionName, $pluginName);
			case self::CONFIGURATION_TYPE_FULL_TYPOSCRIPT:
				return $this->concreteConfigurationManager->getTypoScriptSetup();
			default:
				throw new \TYPO3\CMS\Extbase\Configuration\Exception\InvalidConfigurationTypeException('Invalid configuration type "' . $configurationType . '"', 1206031879);
		}
	}

	/**
	 * Returns TRUE if a certain feature, identified by $featureName
	 * should be activated, FALSE for backwards-compatible behavior.
	 *
	 * This is an INTERNAL API used throughout Extbase and Fluid for providing backwards-compatibility.
	 * Do not use it in your custom code!
	 *
	 * @param string $featureName
	 * @return boolean
	 */
	public function isFeatureEnabled($featureName) {
		$configuration = $this->getConfiguration(self::CONFIGURATION_TYPE_FRAMEWORK);
		return (boolean) (isset($configuration['features'][$featureName]) && $configuration['features'][$featureName]);
	}
}

?>