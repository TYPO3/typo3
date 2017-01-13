<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Utility\ArrayUtility;

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
        // if (isset($tcaFieldConf['richtextConfiguration'])) {
            // @todo with yaml parser
            // create instance of NodeFactory, ask for "text" element
            //
            // If text element is instanceof "old" rtehtmlarea, do nothing, or if rtehtml should support ,yml, too
            // unpack extConf settings, see if "demo", "normal" or whatever is configured, let rtehtmlarea register
            // these three as possible configuration options in typo3_conf_vars, then yaml parse config. if "richtextConfiguration"
            // is already set for rtehtmlarea and is "default" then fetch the config that is selected in extConf, else pick
            // configured one.
            //
            // If text element is instanceof "new" ckeditor, and richtextConfiguration is not set, the "default", else
            // look up in typo3_conf_vars.
            //
            // As soon an the Data handler starts using FormDataProviders, this class can vanish again, and the hack to
            // test for specific rich text instances can be dropped: Split the "TcaText" data provider into multiple parts, each
            // RTE should register and own data provider that does the transformation / configuration providing. This way,
            // the explicit check for different RTE classes is removed from core and "hooked in" by the RTE's.
        // }

        $rtePageTs = $this->getRtePageTsConfigOfPid($pid);
        $configuration = $rtePageTs['properties'];
        unset($configuration['default.']);
        unset($configuration['config.']);
        if (is_array($rtePageTs['properties']['default.'])) {
            ArrayUtility::mergeRecursiveWithOverrule($configuration, $rtePageTs['properties']['default.']);
        }
        $rtePageTsField = $rtePageTs['properties']['config.'][$table . '.'][$field . '.'];
        if (is_array($rtePageTsField)) {
            unset($rtePageTsField['types.']);
            ArrayUtility::mergeRecursiveWithOverrule($configuration, $rtePageTsField);
        }
        if ($recordType && is_array($rtePageTs['properties']['config.'][$table . '.'][$field . '.']['types.'][$recordType . '.'])) {
            ArrayUtility::mergeRecursiveWithOverrule(
                $configuration,
                $rtePageTs['properties']['config.'][$table . '.'][$field . '.']['types.'][$recordType . '.']
            );
        }

        // Handle "mode" / "transformation" config for RteHtmlParser
        if (!isset($configuration['proc.']['overruleMode'])) {
            // Fall back to 'default' transformations
            $configuration['proc.']['overruleMode'] = 'default';
        }
        if ($configuration['proc.']['overruleMode'] === 'ts_css') {
            // Change legacy 'ts_css' to 'default'
            $configuration['proc.']['overruleMode'] = 'default';
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
     * @return BackendUserAuthentication
     */
    protected function getBackendUser() : BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
