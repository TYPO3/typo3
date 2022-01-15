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

namespace TYPO3\CMS\Extbase\Service;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\FrontendRestrictionContainer;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\ConfigurationManagerInterface;
use TYPO3\CMS\Extbase\Exception;

/**
 * Service for determining basic extension params
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class ExtensionService implements SingletonInterface
{
    /**
     * @var ConfigurationManagerInterface
     */
    protected $configurationManager;

    /**
     * Cache of result for getTargetPidByPlugin()
     * @var array
     */
    protected $targetPidPluginCache = [];

    /**
     * @param ConfigurationManagerInterface $configurationManager
     */
    public function injectConfigurationManager(ConfigurationManagerInterface $configurationManager): void
    {
        $this->configurationManager = $configurationManager;
    }

    /**
     * Determines the plugin namespace of the specified plugin (defaults to "tx_[extensionname]_[pluginname]")
     * If plugin.tx_$pluginSignature.view.pluginNamespace is set, this value is returned
     * If pluginNamespace is not specified "tx_[extensionname]_[pluginname]" is returned.
     *
     * @param string|null $extensionName name of the extension to retrieve the namespace for
     * @param string|null $pluginName name of the plugin to retrieve the namespace for
     * @return string plugin namespace
     */
    public function getPluginNamespace(?string $extensionName, ?string $pluginName): string
    {
        // todo: with $extensionName and $pluginName being null, tx__ will be returned here which is questionable.
        // todo: find out, if and why this case could happen and maybe avoid this methods being called with null
        // todo: arguments afterwards.
        $pluginSignature = strtolower($extensionName . '_' . $pluginName);
        $defaultPluginNamespace = 'tx_' . $pluginSignature;
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName, $pluginName);
        if (!isset($frameworkConfiguration['view']['pluginNamespace']) || empty($frameworkConfiguration['view']['pluginNamespace'])) {
            return $defaultPluginNamespace;
        }
        return $frameworkConfiguration['view']['pluginNamespace'];
    }

    /**
     * Iterates through the global TypoScript configuration and returns the name of the plugin
     * that matches specified extensionName, controllerName and actionName.
     * If no matching plugin was found, NULL is returned.
     * If more than one plugin matches and the current plugin is not configured to handle the action,
     * an Exception will be thrown
     *
     * @param string $extensionName name of the target extension (UpperCamelCase)
     * @param string $controllerName name of the target controller (UpperCamelCase)
     * @param string|null $actionName name of the target action (lowerCamelCase)
     * @throws \TYPO3\CMS\Extbase\Exception
     * @return string|null name of the target plugin (UpperCamelCase) or NULL if no matching plugin configuration was found
     */
    public function getPluginNameByAction(string $extensionName, string $controllerName, ?string $actionName): ?string
    {
        // check, whether the current plugin is configured to handle the action
        if (($pluginName = $this->getPluginNameFromFrameworkConfiguration($extensionName, $controllerName, $actionName)) !== null) {
            return $pluginName;
        }

        $plugins = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'] ?? false;
        if (!$plugins) {
            return null;
        }
        $pluginNames = [];
        foreach ($plugins as $pluginName => $pluginConfiguration) {
            $controllers = $pluginConfiguration['controllers'] ?? [];
            $controllerAliases = array_column($controllers, 'actions', 'alias');

            foreach ($controllerAliases as $pluginControllerName => $pluginControllerActions) {
                if (strtolower($pluginControllerName) !== strtolower($controllerName)) {
                    continue;
                }
                if (in_array($actionName, $pluginControllerActions, true)) {
                    $pluginNames[] = $pluginName;
                }
            }
        }
        if (count($pluginNames) > 1) {
            throw new Exception('There is more than one plugin that can handle this request (Extension: "' . $extensionName . '", Controller: "' . $controllerName . '", action: "' . $actionName . '"). Please specify "pluginName" argument', 1280825466);
        }
        return !empty($pluginNames) ? $pluginNames[0] : null;
    }

    private function getPluginNameFromFrameworkConfiguration(string $extensionName, string $controllerAlias, ?string $actionName): ?string
    {
        if ($actionName === null) {
            return null;
        }

        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK);

        if (!is_string($pluginName = ($frameworkConfiguration['pluginName'] ?? null))) {
            return null;
        }

        $configuredExtensionName = $frameworkConfiguration['extensionName'] ?? '';
        $configuredExtensionName = is_string($configuredExtensionName) ? $configuredExtensionName : '';

        if ($configuredExtensionName === '' || $configuredExtensionName !== $extensionName) {
            return null;
        }

        $configuredControllers = $frameworkConfiguration['controllerConfiguration'] ?? [];
        $configuredControllers = is_array($configuredControllers) ? $configuredControllers : [];

        $configuredActionsByControllerAliases = array_column($configuredControllers, 'actions', 'alias');

        $actions = $configuredActionsByControllerAliases[$controllerAlias] ?? [];
        $actions = is_array($actions) ? $actions : [];

        return in_array($actionName, $actions, true) ? $pluginName : null;
    }

    /**
     * Determines the target page of the specified plugin.
     * If plugin.tx_$pluginSignature.view.defaultPid is set, this value is used as target page id
     * If defaultPid is set to "auto", a the target pid is determined by loading the tt_content record that contains this plugin
     * If the page could not be determined, NULL is returned
     * If defaultPid is "auto" and more than one page contains the specified plugin, an Exception is thrown
     *
     * @param string $extensionName name of the extension to retrieve the target PID for
     * @param string $pluginName name of the plugin to retrieve the target PID for
     * @throws \TYPO3\CMS\Extbase\Exception
     * @return int|null uid of the target page or NULL if target page could not be determined
     */
    public function getTargetPidByPlugin(string $extensionName, string $pluginName): ?int
    {
        $frameworkConfiguration = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName, $pluginName);
        if (!isset($frameworkConfiguration['view']['defaultPid']) || empty($frameworkConfiguration['view']['defaultPid'])) {
            return null;
        }
        $pluginSignature = strtolower($extensionName . '_' . $pluginName);
        if ($frameworkConfiguration['view']['defaultPid'] === 'auto') {
            if (!array_key_exists($pluginSignature, $this->targetPidPluginCache)) {
                $languageId = GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('language', 'id', 0);
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('tt_content');
                $queryBuilder->setRestrictions(GeneralUtility::makeInstance(FrontendRestrictionContainer::class));

                $pages = $queryBuilder
                    ->select('pid')
                    ->from('tt_content')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'list_type',
                            $queryBuilder->createNamedParameter($pluginSignature, \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'CType',
                            $queryBuilder->createNamedParameter('list', \PDO::PARAM_STR)
                        ),
                        $queryBuilder->expr()->eq(
                            'sys_language_uid',
                            $queryBuilder->createNamedParameter($languageId, \PDO::PARAM_INT)
                        )
                    )
                    ->setMaxResults(2)
                    ->executeQuery()
                    ->fetchAllAssociative();

                if (count($pages) > 1) {
                    throw new Exception('There is more than one "' . $pluginSignature . '" plugin in the current page tree. Please remove one plugin or set the TypoScript configuration "plugin.tx_' . $pluginSignature . '.view.defaultPid" to a fixed page id', 1280773643);
                }
                $this->targetPidPluginCache[$pluginSignature] = !empty($pages) ? (int)$pages[0]['pid'] : null;
            }
            return $this->targetPidPluginCache[$pluginSignature];
        }
        return (int)$frameworkConfiguration['view']['defaultPid'];
    }

    /**
     * This returns the name of the first controller of the given plugin.
     *
     * @param string $extensionName name of the extension to retrieve the target PID for
     * @param string $pluginName name of the plugin to retrieve the target PID for
     * @return string|null
     */
    public function getDefaultControllerNameByPlugin(string $extensionName, string $pluginName): ?string
    {
        $controllers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'] ?? [];
        $controllerAliases = array_column($controllers, 'alias');
        $defaultControllerName = (string)($controllerAliases[0] ?? '');
        return $defaultControllerName !== '' ? $defaultControllerName : null;
    }

    /**
     * This returns the name of the first action of the given plugin controller.
     *
     * @param string $extensionName name of the extension to retrieve the target PID for
     * @param string $pluginName name of the plugin to retrieve the target PID for
     * @param string $controllerName name of the controller to retrieve default action for
     * @return string|null
     */
    public function getDefaultActionNameByPluginAndController(string $extensionName, string $pluginName, string $controllerName): ?string
    {
        $controllers = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'] ?? [];
        $controllerActionsByAlias = array_column($controllers, 'actions', 'alias');
        $actions = $controllerActionsByAlias[$controllerName] ?? [];
        $defaultActionName = (string)($actions[0] ?? '');
        return $defaultActionName !== '' ? $defaultActionName : null;
    }

    /**
     * Resolve the page type number to use for building a link for a specific format
     *
     * @param string|null $extensionName name of the extension that has defined the target page type
     * @param string $format The format for which to look up the page type
     * @return int Page type number for target page
     */
    public function getTargetPageTypeByFormat(?string $extensionName, string $format): int
    {
        // Default behaviour
        $settings = $this->configurationManager->getConfiguration(ConfigurationManagerInterface::CONFIGURATION_TYPE_FRAMEWORK, $extensionName);
        $formatToPageTypeMapping = $settings['view']['formatToPageTypeMapping'] ?? [];
        $formatToPageTypeMapping = is_array($formatToPageTypeMapping) ? $formatToPageTypeMapping : [];
        return (int)($formatToPageTypeMapping[$format] ?? 0);
    }
}
