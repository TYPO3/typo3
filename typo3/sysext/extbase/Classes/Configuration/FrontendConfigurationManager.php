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

use TYPO3\CMS\Core\Service\FlexFormService;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Configuration\Exception\ParseErrorException;

/**
 * A general purpose configuration manager used in frontend mode.
 *
 * Should NOT be singleton, as a new configuration manager is needed per plugin.
 * @internal only to be used within Extbase, not part of TYPO3 Core API.
 */
class FrontendConfigurationManager extends AbstractConfigurationManager
{
    protected FlexFormService $flexFormService;

    public function __construct(
        TypoScriptService $typoScriptService,
        FlexFormService $flexFormService
    ) {
        parent::__construct($typoScriptService);
        $this->flexFormService = $flexFormService;
    }

    /**
     * Returns TypoScript Setup array from current Environment.
     *
     * @return array the raw TypoScript setup
     */
    public function getTypoScriptSetup(): array
    {
        return $GLOBALS['TSFE']->tmpl->setup ?? [];
    }

    /**
     * Returns the TypoScript configuration found in plugin.tx_yourextension_yourplugin
     * merged with the global configuration of your extension from plugin.tx_yourextension
     *
     * @param string $extensionName
     * @param string $pluginName
     * @return array
     */
    protected function getPluginConfiguration(string $extensionName, string $pluginName = null): array
    {
        $setup = $this->getTypoScriptSetup();
        $pluginConfiguration = [];
        if (isset($setup['plugin.']['tx_' . strtolower($extensionName) . '.']) && is_array($setup['plugin.']['tx_' . strtolower($extensionName) . '.'])) {
            $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . strtolower($extensionName) . '.']);
        }
        if ($pluginName !== null) {
            $pluginSignature = strtolower($extensionName . '_' . $pluginName);
            if (isset($setup['plugin.']['tx_' . $pluginSignature . '.']) && is_array($setup['plugin.']['tx_' . $pluginSignature . '.'])) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $pluginConfiguration,
                    $this->typoScriptService->convertTypoScriptArrayToPlainArray($setup['plugin.']['tx_' . $pluginSignature . '.'])
                );
            }
        }
        return $pluginConfiguration;
    }

    /**
     * Returns the configured controller/action configuration of the specified plugin in the format
     * array(
     * 'Controller1' => array('action1', 'action2'),
     * 'Controller2' => array('action3', 'action4')
     * )
     *
     * @param string $extensionName
     * @param string $pluginName
     * @return array
     */
    protected function getControllerConfiguration(string $extensionName, string $pluginName): array
    {
        $controllerConfiguration = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['plugins'][$pluginName]['controllers'] ?? [];
        if (!is_array($controllerConfiguration)) {
            $controllerConfiguration = [];
        }
        return $controllerConfiguration;
    }

    /**
     * Get context specific framework configuration.
     * - Overrides storage PID with setting "Startingpoint"
     * - merge flexForm configuration, if needed
     *
     * @param array $frameworkConfiguration The framework configuration to modify
     * @return array the modified framework configuration
     */
    protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration): array
    {
        $frameworkConfiguration = $this->overrideStoragePidIfStartingPointIsSet($frameworkConfiguration);
        $frameworkConfiguration = $this->overrideConfigurationFromPlugin($frameworkConfiguration);
        $frameworkConfiguration = $this->overrideConfigurationFromFlexForm($frameworkConfiguration);
        return $frameworkConfiguration;
    }

    /**
     * Overrides the storage PID settings, in case the "Startingpoint" settings
     * is set in the plugin configuration.
     *
     * @param array $frameworkConfiguration the framework configurations
     * @return array the framework configuration with overridden storagePid
     */
    protected function overrideStoragePidIfStartingPointIsSet(array $frameworkConfiguration): array
    {
        $pages = $this->contentObject->data['pages'] ?? '';
        if (is_string($pages) && $pages !== '') {
            $list = [];
            if ($this->contentObject->data['recursive'] > 0) {
                $explodedPages = GeneralUtility::trimExplode(',', $pages);
                foreach ($explodedPages as $pid) {
                    $pids = $this->contentObject->getTreeList($pid, $this->contentObject->data['recursive']);
                    if ($pids !== '') {
                        $list[] = $pids;
                    }
                }
            }
            if (!empty($list)) {
                $pages = $pages . ',' . implode(',', $list);
            }
            ArrayUtility::mergeRecursiveWithOverrule($frameworkConfiguration, [
                'persistence' => [
                    'storagePid' => $pages,
                ],
            ]);
        }
        return $frameworkConfiguration;
    }

    /**
     * Overrides configuration settings from the plugin typoscript (plugin.tx_myext_pi1.)
     *
     * @param array $frameworkConfiguration the framework configuration
     * @return array the framework configuration with overridden data from typoscript
     */
    protected function overrideConfigurationFromPlugin(array $frameworkConfiguration): array
    {
        if (!isset($frameworkConfiguration['extensionName']) || !isset($frameworkConfiguration['pluginName'])) {
            return $frameworkConfiguration;
        }

        $setup = $this->getTypoScriptSetup();
        $pluginSignature = strtolower($frameworkConfiguration['extensionName'] . '_' . $frameworkConfiguration['pluginName']);
        $pluginConfiguration = $setup['plugin.']['tx_' . $pluginSignature . '.'] ?? null;
        if (is_array($pluginConfiguration)) {
            $pluginConfiguration = $this->typoScriptService->convertTypoScriptArrayToPlainArray($pluginConfiguration);
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'settings');
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'persistence');
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $pluginConfiguration, 'view');
        }
        return $frameworkConfiguration;
    }

    /**
     * Overrides configuration settings from flexForms.
     * This merges the whole flexForm data, and overrides the controller configuration with possibly configured
     * switchable controller actions.
     *
     * @param array $frameworkConfiguration the framework configuration
     * @return array the framework configuration with overridden data from flexForm
     */
    protected function overrideConfigurationFromFlexForm(array $frameworkConfiguration): array
    {
        $flexFormConfiguration = $this->contentObject->data['pi_flexform'] ?? [];
        if (is_string($flexFormConfiguration)) {
            if ($flexFormConfiguration !== '') {
                $flexFormConfiguration = $this->flexFormService->convertFlexFormContentToArray($flexFormConfiguration);
            } else {
                $flexFormConfiguration = [];
            }
        }
        if (is_array($flexFormConfiguration) && !empty($flexFormConfiguration)) {
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'settings');
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'persistence');
            $frameworkConfiguration = $this->mergeConfigurationIntoFrameworkConfiguration($frameworkConfiguration, $flexFormConfiguration, 'view');
            $frameworkConfiguration = $this->overrideControllerConfigurationWithSwitchableControllerActionsFromFlexForm($frameworkConfiguration, $flexFormConfiguration);
        }
        return $frameworkConfiguration;
    }

    /**
     * Merge a configuration into the framework configuration.
     *
     * @param array $frameworkConfiguration the framework configuration to merge the data on
     * @param array $configuration The configuration
     * @param string $configurationPartName The name of the configuration part which should be merged.
     * @return array the processed framework configuration
     */
    protected function mergeConfigurationIntoFrameworkConfiguration(array $frameworkConfiguration, array $configuration, string $configurationPartName): array
    {
        if (isset($configuration[$configurationPartName]) && is_array($configuration[$configurationPartName])) {
            if (isset($frameworkConfiguration[$configurationPartName]) && is_array($frameworkConfiguration[$configurationPartName])) {
                ArrayUtility::mergeRecursiveWithOverrule($frameworkConfiguration[$configurationPartName], $configuration[$configurationPartName]);
            } else {
                $frameworkConfiguration[$configurationPartName] = $configuration[$configurationPartName];
            }
        }
        return $frameworkConfiguration;
    }

    /**
     * Overrides the controller configuration with possibly registered switchable controller actions of the flex form
     * configuration.
     *
     * @param array $frameworkConfiguration The original framework configuration
     * @param array $flexFormConfiguration The full flexForm configuration
     * @throws Exception\ParseErrorException
     * @return array the modified framework configuration, if needed
     * @deprecated since TYPO3 v10, will be removed in one of the next major versions of TYPO3, probably version 11.0 or 12.0.
     */
    protected function overrideControllerConfigurationWithSwitchableControllerActionsFromFlexForm(array $frameworkConfiguration, array $flexFormConfiguration): array
    {
        if (!isset($flexFormConfiguration['switchableControllerActions']) || is_array($flexFormConfiguration['switchableControllerActions'])) {
            return $frameworkConfiguration;
        }
        // As "," is the flexForm field value delimiter, we need to use ";" as in-field delimiter. That's why we need to replace ; by  , first.
        // The expected format is: "Controller1->action2;Controller2->action3;Controller2->action1"
        $switchableControllerActionPartsFromFlexForm = GeneralUtility::trimExplode(',', str_replace(';', ',', $flexFormConfiguration['switchableControllerActions']), true);
        $overriddenControllerConfiguration = [];
        foreach ($switchableControllerActionPartsFromFlexForm as $switchableControllerActionPartFromFlexForm) {
            [$controller, $action] = GeneralUtility::trimExplode('->', $switchableControllerActionPartFromFlexForm);
            if (empty($controller) || empty($action)) {
                throw new ParseErrorException('Controller or action were empty when overriding switchableControllerActions from flexForm.', 1257146403);
            }
            $overriddenControllerConfiguration[$controller][] = $action;
        }
        if (!empty($overriddenControllerConfiguration)) {
            $this->overrideControllerConfigurationWithSwitchableControllerActions($frameworkConfiguration, $overriddenControllerConfiguration);
        }
        return $frameworkConfiguration;
    }

    /**
     * Returns a comma separated list of storagePid that are below a certain storage pid.
     *
     * @param array|int[] $storagePids Storage PIDs to start at; multiple PIDs possible as comma-separated list
     * @param int $recursionDepth Maximum number of levels to search, 0 to disable recursive lookup
     * @return array|int[] storage PIDs
     */
    protected function getRecursiveStoragePids(array $storagePids, int $recursionDepth = 0): array
    {
        array_map('intval', $storagePids);

        if ($recursionDepth <= 0) {
            return $storagePids;
        }

        $recursiveStoragePids = [];
        foreach ($storagePids as $startPid) {
            $pids = $this->getContentObject()->getTreeList($startPid, $recursionDepth, 0);
            foreach (GeneralUtility::intExplode(',', $pids, true) as $pid) {
                $recursiveStoragePids[] = $pid;
            }
        }
        return array_unique($recursiveStoragePids);
    }
}
