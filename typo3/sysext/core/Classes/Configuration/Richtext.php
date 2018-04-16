<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Configuration;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Prepare richtext configuration. Used in DataHandler and FormEngine
 *
 * @internal Internal class for the time being - may change / vanish any time
 * @todo When I grow up, I want to become a data provider
 */
class Richtext
{
    /**
     * This is an intermediate class / method to retrieve RTE
     * configuration until all core places use data providers to do that.
     *
     * @param string $table The table the field is in
     * @param string $field Field name
     * @param int $pid Real page id
     * @param string $recordType Record type value
     * @param array $tcaFieldConf ['config'] section of TCA field
     * @return array
     */
    public function getConfiguration(string $table, string $field, int $pid, string $recordType, array $tcaFieldConf): array
    {
        // create instance of NodeFactory, ask for "text" element
        //
        // As soon an the Data handler starts using FormDataProviders, this class can vanish again, and the hack to
        // test for specific rich text instances can be dropped: Split the "TcaText" data provider into multiple parts, each
        // RTE should register and own data provider that does the transformation / configuration providing. This way,
        // the explicit check for different RTE classes is removed from core and "hooked in" by the RTE's.

        // The main problem here is that all parameters that the processing needs is handed over to as TSconfig
        // "dotted array" syntax. We convert at least the processing information available under "processing"
        // together with pageTS, this way it can be overridden and understood in RteHtmlParser.
        // However, all other parts of the core will depend on the non-dotted syntax (coming from Yaml directly)

        $pageTs = $this->getPageTsConfiguration($table, $field, $pid, $recordType);

        // determine which preset to use
        $usePreset = $pageTs['preset'] ?? $tcaFieldConf['richtextConfiguration'] ?? 'default';

        // load configuration from preset
        $configuration = $this->loadConfigurationFromPreset($usePreset);

        // overlay preset configuration with pageTs
        ArrayUtility::mergeRecursiveWithOverrule(
            $configuration,
            $this->addFlattenedPageTsConfig($pageTs)
        );

        // Handle "mode" / "transformation" config when overridden
        if (isset($configuration['proc.']['overruleMode']) && $configuration['proc.']['overruleMode'] === 'ts_css') {
            // Change legacy 'ts_css' to 'default'
            $configuration['proc.']['overruleMode'] = 'default';
        } elseif (!isset($configuration['proc.']['mode']) && !isset($configuration['proc.']['overruleMode'])) {
            $configuration['proc.']['overruleMode'] = 'default';
        }

        return $configuration;
    }

    /**
     * Load a configuration preset from an external resource (currently only YAML is supported).
     * This is the default behaviour and can be overridden by pageTSconfig.
     *
     * @param string $presetName
     * @return array the parsed configuration
     */
    protected function loadConfigurationFromPreset(string $presetName = ''): array
    {
        $configuration = [];
        if (!empty($presetName) && isset($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$presetName])) {
            $fileLoader = GeneralUtility::makeInstance(YamlFileLoader::class);
            $configuration = $fileLoader->load($GLOBALS['TYPO3_CONF_VARS']['RTE']['Presets'][$presetName]);
            // For future versions, you should however rely on the "processing" key and not the "proc" key.
            if (is_array($configuration['processing'])) {
                $configuration['proc.'] = $this->convertPlainArrayToTypoScriptArray($configuration['processing']);
            }
        }
        return $configuration;
    }

    /**
     * Return RTE section of page TS
     *
     * @param int $pid Page ts of given pid
     * @return array RTE section of pageTs of given pid
     */
    protected function getRtePageTsConfigOfPid(int $pid): array
    {
        // Override with pageTs if needed
        $backendUser = $this->getBackendUser();
        return $backendUser->getTSConfig('RTE', BackendUtility::getPagesTSconfig($pid));
    }

    /**
     * Returns an array with Typoscript the old way (with dot)
     * Since the functionality in Yaml is without the dots, but the new configuration is used without the dots
     * this functionality adds also an explicit = 1 to the arrays
     *
     * @param array $plainArray An array
     * @return array array with TypoScript as usual (with dot)
     */
    protected function convertPlainArrayToTypoScriptArray(array $plainArray)
    {
        $typoScriptArray = [];
        foreach ($plainArray as $key => $value) {
            if (is_array($value)) {
                if (!isset($typoScriptArray[$key])) {
                    $typoScriptArray[$key] = 1;
                }
                $typoScriptArray[$key . '.'] = $this->convertPlainArrayToTypoScriptArray($value);
            } else {
                $typoScriptArray[$key] = is_null($value) ? '' : $value;
            }
        }
        return $typoScriptArray;
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * Add all PageTS.RTE options keys to configuration without dots
     *
     * We need to keep the dotted keys for backwards compatibility like ext:rtehtmlarea
     *
     * @param array $typoScriptArray TypoScriptArray
     * @return array array with config without dots added
     */
    protected function addFlattenedPageTsConfig(array $typoScriptArray): array
    {
        foreach ($typoScriptArray as $key => $data) {
            if (substr($key, -1) !== '.') {
                continue;
            }
            /** @var TypoScriptService $typoScriptService */
            $typoScriptService = GeneralUtility::makeInstance(TypoScriptService::class);
            $typoScriptArray[substr($key, 0, -1)] = $typoScriptService->convertTypoScriptArrayToPlainArray($typoScriptArray[$key]);
        }

        return $typoScriptArray;
    }

    /**
     * Load PageTS configuration for the RTE
     *
     * Return RTE section of page TS, taking into account overloading via table, field and record type
     *
     * @param string $table The table the field is in
     * @param string $field Field name
     * @param int $pid Real page id
     * @param string $recordType Record type value
     * @return array
     */
    protected function getPageTsConfiguration(string $table, string $field, int $pid, string $recordType): array
    {
        // Load PageTSconfig configuration
        $fullPageTsConfig = $this->getRtePageTsConfigOfPid($pid);
        $fullPageTsConfig = !empty($fullPageTsConfig['properties']) ? $fullPageTsConfig['properties'] : [];
        $defaultPageTsConfigOverrides = isset($fullPageTsConfig['default.']) ? $fullPageTsConfig['default.'] : null;
        $fieldSpecificPageTsConfigOverrides = isset($fullPageTsConfig['config.'][$table . '.'][$field . '.']) ? $fullPageTsConfig['config.'][$table . '.'][$field . '.'] : null;
        unset($fullPageTsConfig['default.'], $fullPageTsConfig['config.']);

        // First use RTE.*
        $rtePageTsConfiguration = $fullPageTsConfig;

        // Then overload with RTE.default.*
        if (is_array($defaultPageTsConfigOverrides)) {
            ArrayUtility::mergeRecursiveWithOverrule($rtePageTsConfiguration, $defaultPageTsConfigOverrides);
        }

        // Then overload with RTE.config.tt_content.bodytext
        if (is_array($fieldSpecificPageTsConfigOverrides)) {
            $fieldSpecificPageTsConfigOverridesWithoutType = $fieldSpecificPageTsConfigOverrides;
            unset($fieldSpecificPageTsConfigOverridesWithoutType['types.']);
            ArrayUtility::mergeRecursiveWithOverrule($rtePageTsConfiguration, $fieldSpecificPageTsConfigOverridesWithoutType);

            // Then overload with RTE.config.tt_content.bodytext.types.textmedia
            if (
                $recordType
                && isset($fieldSpecificPageTsConfigOverrides['types.'][$recordType . '.'])
                && is_array($fieldSpecificPageTsConfigOverrides['types.'][$recordType . '.'])
            ) {
                ArrayUtility::mergeRecursiveWithOverrule(
                    $rtePageTsConfiguration,
                    $fieldSpecificPageTsConfigOverrides['types.'][$recordType . '.']
                );
            }
        }

        return $rtePageTsConfiguration;
    }
}
