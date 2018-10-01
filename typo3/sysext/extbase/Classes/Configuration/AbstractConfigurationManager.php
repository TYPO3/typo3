<?php
namespace TYPO3\CMS\Extbase\Configuration;

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
 * Abstract base class for a general purpose configuration manager
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractConfigurationManager implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * Default backend storage PID
     */
    const DEFAULT_BACKEND_STORAGE_PID = 0;

    /**
     * Storage of the raw TypoScript configuration
     *
     * @var array
     */
    protected $configuration = [];

    /**
     * @var \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer
     */
    protected $contentObject;

    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManagerInterface
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Core\TypoScript\TypoScriptService
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
    protected $configurationCache = [];

    /**
     * @var \TYPO3\CMS\Extbase\Service\EnvironmentService
     */
    protected $environmentService;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManagerInterface $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService
     */
    public function injectTypoScriptService(\TYPO3\CMS\Core\TypoScript\TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @param \TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService
     */
    public function injectEnvironmentService(\TYPO3\CMS\Extbase\Service\EnvironmentService $environmentService)
    {
        $this->environmentService = $environmentService;
    }

    /**
     * @param \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject
     */
    public function setContentObject(\TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer $contentObject = null)
    {
        $this->contentObject = $contentObject;
    }

    /**
     * @return \TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer|null
     */
    public function getContentObject()
    {
        if ($this->contentObject !== null) {
            return $this->contentObject;
        }
        return null;
    }

    /**
     * Sets the specified raw configuration coming from the outside.
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param array $configuration The new configuration
     */
    public function setConfiguration(array $configuration = [])
    {
        // reset 1st level cache
        $this->configurationCache = [];
        $this->extensionName = $configuration['extensionName'] ?? null;
        $this->pluginName = $configuration['pluginName'] ?? null;
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
    public function getConfiguration($extensionName = null, $pluginName = null)
    {
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
        if ($extensionName === null || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
            $pluginConfiguration = $this->getPluginConfiguration($this->extensionName, $this->pluginName);
            \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $this->configuration);
            $pluginConfiguration['controllerConfiguration'] = $this->getSwitchableControllerActions($this->extensionName, $this->pluginName);
            if (isset($this->configuration['switchableControllerActions'])) {
                $this->overrideSwitchableControllerActions($pluginConfiguration, $this->configuration['switchableControllerActions']);
            }
        } else {
            $pluginConfiguration = $this->getPluginConfiguration($extensionName, $pluginName);
            $pluginConfiguration['controllerConfiguration'] = $this->getSwitchableControllerActions($extensionName, $pluginName);
        }
        \TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($frameworkConfiguration, $pluginConfiguration);
        // only load context specific configuration when retrieving configuration of the current plugin
        if ($extensionName === null || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
            $frameworkConfiguration = $this->getContextSpecificFrameworkConfiguration($frameworkConfiguration);
        }

        if (!empty($frameworkConfiguration['persistence']['storagePid'])) {
            if (is_array($frameworkConfiguration['persistence']['storagePid'])) {
                // We simulate the frontend to enable the use of cObjects in
                // stdWrap. Than we convert the configuration to normal TypoScript
                // and apply the stdWrap to the storagePid
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
                // All implementations of getTreeList allow to pass the ids negative to include them into the result
                // otherwise only childpages are returned
                $storagePids = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
                array_walk($storagePids, function (&$storagePid) {
                    if ($storagePid > 0) {
                        $storagePid = -$storagePid;
                    }
                });
                $frameworkConfiguration['persistence']['storagePid'] = $this->getRecursiveStoragePids(
                    implode(',', $storagePids),
                    (int)$frameworkConfiguration['persistence']['recursive']
                );
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
    protected function getExtbaseConfiguration()
    {
        $setup = $this->getTypoScriptSetup();
        $extbaseConfiguration = [];
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
    public function getDefaultBackendStoragePid()
    {
        return self::DEFAULT_BACKEND_STORAGE_PID;
    }

    /**
     * @param array &$frameworkConfiguration
     * @param array $switchableControllerActions
     */
    protected function overrideSwitchableControllerActions(array &$frameworkConfiguration, array $switchableControllerActions)
    {
        $overriddenSwitchableControllerActions = [];
        foreach ($switchableControllerActions as $controllerName => $actions) {
            if (!isset($frameworkConfiguration['controllerConfiguration'][$controllerName])) {
                continue;
            }
            $overriddenSwitchableControllerActions[$controllerName] = ['actions' => $actions];
            $nonCacheableActions = $frameworkConfiguration['controllerConfiguration'][$controllerName]['nonCacheableActions'] ?? null;
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
    abstract protected function getPluginConfiguration($extensionName, $pluginName = null);

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
     * @param int $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
     * @return string storage PIDs
     */
    abstract protected function getRecursiveStoragePids($storagePid, $recursionDepth = 0);
}
