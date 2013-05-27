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
 * Abstract base class for a general purpose configuration manager
 */
abstract class AbstractConfigurationManager implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * Default backend storage PID
	 */
	const DEFAULT_BACKEND_STORAGE_PID = 0;

	/**
	 * Storage of the raw TypoScript configuration
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
	 */
	protected $contentObject;

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Extbase\Service\TypoScriptService
	 */
	protected $typoScriptService;

	/**
	 * name of the extension this Configuration Manager instance belongs to
	 *
	 * @var string
	 */
	protected $extensionName;

	/**
	 * name of the plugin this Configuration Manager instance belongs to
	 *
	 * @var string
	 */
	protected $pluginName;

	/**
	 * 1st level configuration cache
	 *
	 * @var array
	 */
	protected $configurationCache = array();

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
	 * @param \TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService
	 * @return void
	 */
	public function injectTypoScriptService(\TYPO3\CMS\Extbase\Service\TypoScriptService $typoScriptService) {
		$this->typoScriptService = $typoScriptService;
	}

	/**
	 * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
	 * @return void
	 */
	public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService) {
		$this->environmentService = $environmentService;
	}

	/**
	 * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
	 * @return void
	 */
	public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = NULL) {
		$this->contentObject = $contentObject;
	}

	/**
	 * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|NULL
	 */
	public function getContentObject() {
		if ($this->contentObject !== NULL) {
			return $this->contentObject;
		}
		return NULL;
	}

	/**
	 * Sets the specified raw configuration coming from the outside.
	 * Note that this is a low level method and only makes sense to be used by Extbase internally.
	 *
	 * @param array $configuration The new configuration
	 * @return void
	 */
	public function setConfiguration(array $configuration = array()) {
		// reset 1st level cache
		$this->configurationCache = array();
		$this->extensionName = isset($configuration['extensionName']) ? $configuration['extensionName'] : NULL;
		$this->pluginName = isset($configuration['pluginName']) ? $configuration['pluginName'] : NULL;
		$this->configuration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($configuration);
	}

	/**
	 * Loads the Extbase Framework configuration.
	 *
	 * The Extbase framework configuration HAS TO be retrieved using this method, as they are come from different places than the normal settings.
	 * Framework configuration is, in contrast to normal settings, needed for the Extbase framework to operate correctly.
	 *
	 * @param string $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
	 * @param string $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
	 * @return array the Extbase framework configuration
	 */
	public function getConfiguration($extensionName = NULL, $pluginName = NULL) {
		// 1st level cache
		$configurationCacheKey = strtolower(($extensionName ?: $this->extensionName) . '_' . ($pluginName ?: $this->pluginName));
		if (isset($this->configurationCache[$configurationCacheKey])) {
			return $this->configurationCache[$configurationCacheKey];
		}
		$frameworkConfiguration = $this->getExtbaseConfiguration();
		if (!isset($frameworkConfiguration['persistence']['storagePid'])) {
			$frameworkConfiguration['persistence']['storagePid'] = $this->getDefaultBackendStoragePid();
		}
		// only merge $this->configuration and override switchableControllerActions when retrieving configuration of the current plugin
		if ($extensionName === NULL || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
			$pluginConfiguration = $this->getPluginConfiguration($this->extensionName, $this->pluginName);
			$pluginConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($pluginConfiguration, $this->configuration);
			$pluginConfiguration['controllerConfiguration'] = $this->getSwitchableControllerActions($this->extensionName, $this->pluginName);
			if (isset($this->configuration['switchableControllerActions'])) {
				$this->overrideSwitchableControllerActions($pluginConfiguration, $this->configuration['switchableControllerActions']);
			}
		} else {
			$pluginConfiguration = $this->getPluginConfiguration($extensionName, $pluginName);
			$pluginConfiguration['controllerConfiguration'] = $this->getSwitchableControllerActions($extensionName, $pluginName);
		}
		$frameworkConfiguration = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($frameworkConfiguration, $pluginConfiguration);
		// only load context specific configuration when retrieving configuration of the current plugin
		if ($extensionName === NULL || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
			$frameworkConfiguration = $this->getContextSpecificFrameworkConfiguration($frameworkConfiguration);
		}

		if (!empty($frameworkConfiguration['persistence']['storagePid'])) {
			if (is_array($frameworkConfiguration['persistence']['storagePid'])) {
					/**
					* We simulate the frontend to enable the use of cObjects in
					* stdWrap. Than we convert the configuration to normal TypoScript
					* and apply the stdWrap to the storagePid
					*/
				if (!$this->environmentService->isEnvironmentInFrontendMode()) {
					\TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility::simulateFrontendEnvironment($this->getContentObject());
				}
				$conf = $this->typoScriptService->convertPlainArrayToTypoScriptArray($frameworkConfiguration['persistence']);
				$frameworkConfiguration['persistence']['storagePid'] = $GLOBALS['TSFE']->cObj->stdWrap($conf['storagePid'], $conf['storagePid.']);
				if (!$this->environmentService->isEnvironmentInFrontendMode()) {
					\TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility::resetFrontendEnvironment();
				}
			}

			if (!empty($frameworkConfiguration['persistence']['recursive'])) {
				$frameworkConfiguration['persistence']['storagePid'] = $this->getRecursiveStoragePids($frameworkConfiguration['persistence']['storagePid'], (int) $frameworkConfiguration['persistence']['recursive']);
			}
		}
		// 1st level cache
		$this->configurationCache[$configurationCacheKey] = $frameworkConfiguration;
		return $frameworkConfiguration;
	}

	/**
	 * Returns the TypoScript configuration found in config.tx_extbase
	 *
	 * @return array
	 */
	protected function getExtbaseConfiguration() {
		$setup = $this->getTypoScriptSetup();
		$extbaseConfiguration = array();
		if (isset($setup['config.']['tx_extbase.'])) {
			$extbaseConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['config.']['tx_extbase.']);
		}
		return $extbaseConfiguration;
	}

	/**
	 * Returns the default backend storage pid
	 *
	 * @return string
	 */
	public function getDefaultBackendStoragePid() {
		return self::DEFAULT_BACKEND_STORAGE_PID;
	}

	/**
	 * @param array &$frameworkConfiguration
	 * @param array $switchableControllerActions
	 * @return void
	 */
	protected function overrideSwitchableControllerActions(array &$frameworkConfiguration, array $switchableControllerActions) {
		$overriddenSwitchableControllerActions = array();
		foreach ($switchableControllerActions as $controllerName => $actions) {
			if (!isset($frameworkConfiguration['controllerConfiguration'][$controllerName])) {
				continue;
			}
			$overriddenSwitchableControllerActions[$controllerName] = array('actions' => $actions);
			$nonCacheableActions = $frameworkConfiguration['controllerConfiguration'][$controllerName]['nonCacheableActions'];
			if (!is_array($nonCacheableActions)) {
				// There are no non-cacheable actions, thus we can directly continue
				// with the next controller name.
				continue;
			}
			$overriddenNonCacheableActions = array_intersect($nonCacheableActions, $actions);
			if (!empty($overriddenNonCacheableActions)) {
				$overriddenSwitchableControllerActions[$controllerName]['nonCacheableActions'] = $overriddenNonCacheableActions;
			}
		}
		$frameworkConfiguration['controllerConfiguration'] = $overriddenSwitchableControllerActions;
	}

	/**
	 * The context specific configuration returned by this method
	 * will override the framework configuration which was
	 * obtained from TypoScript. This can be used f.e. to override the storagePid
	 * with the value set inside the Plugin Instance.
	 *
	 * WARNING: Make sure this method ALWAYS returns an array!
	 *
	 * @param array $frameworkConfiguration The framework configuration until now
	 * @return array context specific configuration which will override the configuration obtained by TypoScript
	 */
	abstract protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration);

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the TypoScript setup
	 */
	abstract public function getTypoScriptSetup();

	/**
	 * Returns the TypoScript configuration found in plugin.tx_yourextension_yourplugin / module.tx_yourextension_yourmodule
	 * merged with the global configuration of your extension from plugin.tx_yourextension / module.tx_yourextension
	 *
	 * @param string $extensionName
	 * @param string $pluginName in FE mode this is the specified plugin name, in BE mode this is the full module signature
	 * @return array
	 */
	abstract protected function getPluginConfiguration($extensionName, $pluginName = NULL);

	/**
	 * Returns the configured controller/action pairs of the specified plugin/module in the format
	 * array(
	 * 'Controller1' => array('action1', 'action2'),
	 * 'Controller2' => array('action3', 'action4')
	 * )
	 *
	 * @param string $extensionName
	 * @param string $pluginName in FE mode this is the specified plugin name, in BE mode this is the full module signature
	 * @return array
	 */
	abstract protected function getSwitchableControllerActions($extensionName, $pluginName);

	/**
	 * The implementation of the methods to return a list of storagePid that are below a certain
	 * storage pid.
	 *
	 * @param string $storagePid Storage PID to start at; multiple PIDs possible as comma-separated list
	 * @param integer $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
	 * @return string storage PIDs
	 */
	abstract protected function getRecursiveStoragePids($storagePid, $recursionDepth = 0);

}

?>