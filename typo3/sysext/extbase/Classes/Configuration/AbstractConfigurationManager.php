<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Extbase\Configuration;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\ApplicationType;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Utility\FrontendSimulatorUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;

/**
 * Abstract base class for a general purpose configuration manager
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
abstract class AbstractConfigurationManager implements SingletonInterface
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
     * @var ContentObjectRenderer
     */
    protected $contentObject;

    protected TypoScriptService $typoScriptService;

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

    public function __construct(TypoScriptService $typoScriptService)
    {
        $this->typoScriptService = $typoScriptService;
    }

    /**
     * @param ContentObjectRenderer $contentObject
     * @todo: See note on getContentObject() below.
     */
    public function setContentObject(ContentObjectRenderer $contentObject): void
    {
        $this->contentObject = $contentObject;
    }

    /**
     * @return ContentObjectRenderer
     * @todo: This dependency to ContentObjectRenderer on a singleton object is unfortunate:
     *      The current instance is set through USER cObj and extbase Bootstrap, its null in Backend.
     *      This getter is misused to retrieve current ContentObjectRenderer state by some extensions (eg. ext:form).
     *      This dependency should be removed altogether.
     *      Although the current implementation *always* returns an instance of ContentObjectRenderer, we do not want to
     *      hard-expect consuming classes on that, since this methods needs to be dropped anyways, so potential null return is kept.
     */
    public function getContentObject(): ?ContentObjectRenderer
    {
        if ($this->contentObject instanceof ContentObjectRenderer) {
            return $this->contentObject;
        }
        $this->contentObject = GeneralUtility::makeInstance(ContentObjectRenderer::class);
        return $this->contentObject;
    }

    /**
     * Sets the specified raw configuration coming from the outside.
     * Note that this is a low level method and only makes sense to be used by Extbase internally.
     *
     * @param array $configuration The new configuration
     */
    public function setConfiguration(array $configuration = []): void
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
     * @param string|null $extensionName if specified, the configuration for the given extension will be returned (plugin.tx_extensionname)
     * @param string|null $pluginName if specified, the configuration for the given plugin will be returned (plugin.tx_extensionname_pluginname)
     * @return array the Extbase framework configuration
     */
    public function getConfiguration(?string $extensionName = null, ?string $pluginName = null): array
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
        // only merge $this->configuration and override controller configuration when retrieving configuration of the current plugin
        if ($extensionName === null || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
            $pluginConfiguration = $this->getPluginConfiguration((string)$this->extensionName, (string)$this->pluginName);
            ArrayUtility::mergeRecursiveWithOverrule($pluginConfiguration, $this->configuration);
            $pluginConfiguration['controllerConfiguration'] = $this->getControllerConfiguration((string)$this->extensionName, (string)$this->pluginName);
            if (isset($this->configuration['switchableControllerActions'])) {
                $this->overrideControllerConfigurationWithSwitchableControllerActions($pluginConfiguration, $this->configuration['switchableControllerActions']);
            }
        } else {
            $pluginConfiguration = $this->getPluginConfiguration((string)$extensionName, (string)$pluginName);
            $pluginConfiguration['controllerConfiguration'] = $this->getControllerConfiguration((string)$extensionName, (string)$pluginName);
        }
        ArrayUtility::mergeRecursiveWithOverrule($frameworkConfiguration, $pluginConfiguration);
        // only load context specific configuration when retrieving configuration of the current plugin
        if ($extensionName === null || $extensionName === $this->extensionName && $pluginName === $this->pluginName) {
            $frameworkConfiguration = $this->getContextSpecificFrameworkConfiguration($frameworkConfiguration);
        }

        if (!empty($frameworkConfiguration['persistence']['storagePid'])) {
            if (is_array($frameworkConfiguration['persistence']['storagePid'])) {
                // We simulate the frontend to enable the use of cObjects in
                // stdWrap. We then convert the configuration to normal TypoScript
                // and apply the stdWrap to the storagePid
                $isBackend = ($GLOBALS['TYPO3_REQUEST'] ?? null) instanceof ServerRequestInterface
                    && ApplicationType::fromRequest($GLOBALS['TYPO3_REQUEST'])->isBackend();
                if ($isBackend) {
                    // @todo: This BE specific switch should be moved to BackendConfigurationManager to drop the dependency to $GLOBALS['TYPO3_REQUEST'] here.
                    // Use makeInstance here since extbase Bootstrap always setContentObject(null) in Backend, no need to call getContentObject().
                    FrontendSimulatorUtility::simulateFrontendEnvironment(GeneralUtility::makeInstance(ContentObjectRenderer::class));
                }
                $conf = $this->typoScriptService->convertPlainArrayToTypoScriptArray($frameworkConfiguration['persistence']);
                $frameworkConfiguration['persistence']['storagePid'] = $GLOBALS['TSFE']->cObj->stdWrapValue('storagePid', $conf);
                if ($isBackend) {
                    FrontendSimulatorUtility::resetFrontendEnvironment();
                }
            }

            if (!empty($frameworkConfiguration['persistence']['recursive'])) {
                // All implementations of getTreeList allow to pass the ids negative to include them into the result
                // otherwise only childpages are returned
                $storagePids = GeneralUtility::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
                array_walk($storagePids, static function (&$storagePid) {
                    if ($storagePid > 0) {
                        $storagePid = -$storagePid;
                    }
                });
                $storagePids = $this->getRecursiveStoragePids(
                    $storagePids,
                    (int)$frameworkConfiguration['persistence']['recursive']
                );

                $frameworkConfiguration['persistence']['storagePid'] = implode(',', $storagePids);
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
    protected function getExtbaseConfiguration(): array
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
     * @return int
     */
    public function getDefaultBackendStoragePid(): int
    {
        return self::DEFAULT_BACKEND_STORAGE_PID;
    }

    /**
     * This method possibly overrides the controller configuration with an alternative configuration passed along via
     * $frameworkConfiguration.
     *
     * If called by \TYPO3\CMS\Extbase\Configuration\AbstractConfigurationManager::getConfiguration,
     * $switchableControllerActions may contain an alternative controller configuration defined via typoscript:
     *
     * Example:
     * tt_content.list.20.indexedsearch_pi2.switchableControllerActions {
     *     Search {
     *         0 = search
     *     }
     * }
     *
     * If called by \TYPO3\CMS\Extbase\Configuration\FrontendConfigurationManager::overrideSwitchableControllerActionsFromFlexForm,
     * $switchableControllerActions may contain an alternative controller configuration defined via plugin flexform.
     *
     * @param array $frameworkConfiguration
     * @param array $switchableControllerActions
     * @deprecated since TYPO3 v10, will be removed in one of the next major versions of TYPO3, probably version 11.0 or 12.0.
     */
    protected function overrideControllerConfigurationWithSwitchableControllerActions(array &$frameworkConfiguration, array $switchableControllerActions): void
    {
        trigger_error(
            sprintf(
                'Plugin "%s" of extension "%s" uses switchable controller actions which has been marked as deprecated as of version TYPO3 10 and will be removed in one of the next major versions of TYPO3, probably version 11.0 or 12.0',
                $frameworkConfiguration['pluginName'],
                $frameworkConfiguration['extensionName']
            ),
            E_USER_DEPRECATED
        );

        $controllerAliasToClass = [];
        foreach ($frameworkConfiguration['controllerConfiguration'] as $controllerClass => $controllerConfiguration) {
            $controllerAliasToClass[$controllerConfiguration['alias']] = $controllerClass;
        }

        $overriddenControllerConfiguration = [];
        foreach ($switchableControllerActions as $controllerName => $actions) {
            // Trim leading backslashes if a fully qualified controller class name with leading slashes is used.
            $controllerName = ltrim($controllerName, '\\');

            $controllerIsConfigured = false;
            if (array_key_exists($controllerName, $controllerAliasToClass)) {
                /*
                 * If $controllerName can be found in the keys of $controllerAliasToClass, $controllerName is a
                 * controller alias and not a FQCN. In this case switchable controller actions have been defined with
                 * controller aliases as such:
                 *
                 * tt_content.list.20.indexedsearch_pi2.switchableControllerActions {
                 *     Search {
                 *         0 = search
                 *     }
                 * }
                 */
                $controllerIsConfigured = true;
                $controllerClassName = $controllerAliasToClass[$controllerName];
                $controllerAlias = $controllerName;
            }

            if (in_array($controllerName, $controllerAliasToClass, true)) {
                /*
                 * If $controllerName can be found in the values of $controllerAliasToClass, $controllerName is a
                 * FQCN. In this case switchable controller actions have been defined with fully qualified controller
                 * class names as such:
                 *
                 * tt_content.list.20.indexedsearch_pi2.switchableControllerActions {
                 *     TYPO3\CMS\IndexedSearch\Controller\SearchController {
                 *         0 = search
                 *     }
                 * }
                 */
                $controllerIsConfigured = true;
                $controllerClassName = $controllerName;
                $controllerAlias = $frameworkConfiguration['controllerConfiguration'][$controllerName]['alias'];
            }

            if (!$controllerIsConfigured) {
                continue;
            }

            if (!isset($overriddenControllerConfiguration[$controllerClassName])) {
                $overriddenControllerConfiguration[$controllerClassName] = [
                    'alias' => $controllerAlias,
                    'className' => $controllerClassName,
                    'actions' => [],
                ];
            }
            $overriddenControllerConfiguration[$controllerClassName]['actions'] = array_merge(
                $overriddenControllerConfiguration[$controllerClassName]['actions'],
                $actions
            );
            $nonCacheableActions = $frameworkConfiguration['controllerConfiguration'][$controllerClassName]['nonCacheableActions'] ?? null;
            if (!is_array($nonCacheableActions)) {
                // There are no non-cacheable actions, thus we can directly continue
                // with the next controller name.
                continue;
            }
            $overriddenNonCacheableActions = array_intersect($nonCacheableActions, $actions);
            if (!empty($overriddenNonCacheableActions)) {
                $overriddenControllerConfiguration[$controllerClassName]['nonCacheableActions'] = $overriddenNonCacheableActions;
            }
        }
        $frameworkConfiguration['controllerConfiguration'] = $overriddenControllerConfiguration;
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
    abstract protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration): array;

    /**
     * Returns TypoScript Setup array from current Environment.
     *
     * @return array the TypoScript setup
     */
    abstract public function getTypoScriptSetup(): array;

    /**
     * Returns the TypoScript configuration found in plugin.tx_yourextension_yourplugin / module.tx_yourextension_yourmodule
     * merged with the global configuration of your extension from plugin.tx_yourextension / module.tx_yourextension
     *
     * @param string $extensionName
     * @param string $pluginName in FE mode this is the specified plugin name, in BE mode this is the full module signature
     * @return array
     */
    abstract protected function getPluginConfiguration(string $extensionName, string $pluginName = null): array;

    /**
     * Returns the configured controller/action configuration of the specified plugin/module in the format
     * array(
     * 'Controller1' => array('action1', 'action2'),
     * 'Controller2' => array('action3', 'action4')
     * )
     *
     * @param string $extensionName
     * @param string $pluginName in FE mode this is the specified plugin name, in BE mode this is the full module signature
     * @return array
     */
    abstract protected function getControllerConfiguration(string $extensionName, string $pluginName): array;

    /**
     * The implementation of the methods to return a list of storagePid that are below a certain
     * storage pid.
     *
     * @param array|int[] $storagePids Storage PIDs to start at; multiple PIDs possible as comma-separated list
     * @param int $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
     * @return array|int[] storage PIDs
     */
    abstract protected function getRecursiveStoragePids(array $storagePids, int $recursionDepth = 0): array;
}
