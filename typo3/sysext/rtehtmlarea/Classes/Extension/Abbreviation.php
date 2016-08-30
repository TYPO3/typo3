<?php
namespace TYPO3\CMS\Rtehtmlarea\Extension;

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
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\QueryGenerator;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Abbreviation extension for htmlArea RTE
 */
class Abbreviation extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'Abbreviation';

    /**
     * Comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'abbreviation';

    /**
     * Name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'abbreviation' => 'Abbreviation'
    ];

    /**
     * Absolute number of acronyms
     *
     * @var int
     */
    protected $acronymIndex = 0;

    /**
     * Absolute number of abbreviations
     *
     * @var int
     */
    protected $abbreviationIndex = 0;

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        $enabled = parent::main($configuration);
        // acronym button is deprecated as of TYPO3 CMS 7.0, use abbreviation instead
        // Convert the acronym button configuration
        if (isset($this->configuration['thisConfig']['buttons.']['acronym.']) && is_array($this->configuration['thisConfig']['buttons.']['acronym.'])) {
            if (!isset($this->configuration['thisConfig']['buttons.']['abbreviation.']) || !is_array($this->configuration['thisConfig']['buttons.']['abbreviation.'])) {
                $this->configuration['thisConfig']['buttons.']['abbreviation.'] = $this->configuration['thisConfig']['buttons.']['acronym.'];
            }
            unset($this->configuration['thisConfig']['buttons.']['acronym.']);
        }
        // Convert any other reference to acronym two levels down in Page TSconfig, except in processing options and removeFieldsets property
        foreach ($this->configuration['thisConfig'] as $key => $config) {
            if ($key !== 'proc.') {
                if (is_array($config)) {
                    foreach ($config as $subKey => $subConfig) {
                        if (is_array($subConfig)) {
                            foreach ($subConfig as $subSubKey => $subSubConfig) {
                                if ($subSubKey !== 'removeFieldsets') {
                                    $this->configuration['thisConfig'][$key][$subKey][$subSubKey] = str_replace('acronym', 'abbreviation', $subSubConfig);
                                }
                            }
                        } else {
                            if ($subKey !== 'removeFieldsets') {
                                $this->configuration['thisConfig'][$key][$subKey] = str_replace('acronym', 'abbreviation', $subConfig);
                            }
                        }
                    }
                } else {
                    if ($key !== 'removeFieldsets') {
                        $this->configuration['thisConfig'][$key] = str_replace('acronym', 'abbreviation', $config);
                    }
                }
            }
        }
        // Convert any reference to acronym in special configuration options
        if (is_array($this->configuration['specConf']['richtext']['parameters'])) {
            foreach ($this->configuration['specConf']['richtext']['parameters'] as $key => $config) {
                $this->configuration['specConf']['richtext']['parameters'][$key] = str_replace('acronym', 'abbreviation', $config);
            }
        }
        // Convert any reference to acronym in user TSconfig
        if (is_object($GLOBALS['BE_USER']) && isset($GLOBALS['BE_USER']->userTS['options.']['RTEkeyList'])) {
            $GLOBALS['BE_USER']->userTS['options.']['RTEkeyList'] = str_replace('acronym', 'abbreviation', $GLOBALS['BE_USER']->userTS['options.']['RTEkeyList']);
        }
        // Remove button if all fieldsets are removed
        $removedFieldsets = GeneralUtility::trimExplode(',', $this->configuration['thisConfig']['buttons.']['abbreviation.']['removeFieldsets'], true);
        return $enabled && ExtensionManagementUtility::isLoaded('static_info_tables') && count($removedFieldsets) < 4;
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins, in this case, JS configuration of block elements
     */
    public function buildJavascriptConfiguration()
    {
        $button = 'abbreviation';
        $jsArray = [];
        if (in_array($button, $this->toolbar)) {
            if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
                $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
            }
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.abbreviationUrl = "' . $this->writeTemporaryFile('abbreviation_' . $this->configuration['contentLanguageUid'], 'js', $this->buildJSAbbreviationArray()) . '";';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.noAcronym = ' . ($this->acronymIndex ? 'false' : 'true') . ';';
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.noAbbr =  ' . ($this->abbreviationIndex ? 'false' : 'true') . ';';
        }
        return implode(LF, $jsArray);
    }

    /**
     * Return an abbreviation array for the Abbreviation plugin
     *
     * @return string abbreviation Javascript array
     */
    protected function buildJSAbbreviationArray()
    {
        $database = $this->getDatabaseConnection();
        $backendUser = $this->getBackendUserAuthentication();
        $button = 'abbreviation';
        $acronymArray = [];
        $abbrArray = [];
        $tableA = 'tx_rtehtmlarea_acronym';
        $tableB = 'static_languages';
        $fields = $tableA . '.type,' . $tableA . '.term,' . $tableA . '.acronym,' . $tableB . '.lg_iso_2,' . $tableB . '.lg_country_iso_2';
        $tableAB = $tableA . ' LEFT JOIN ' . $tableB . ' ON ' . $tableA . '.static_lang_isocode=' . $tableB . '.uid';
        $whereClause = '1=1';
        $loadRecordsFromDatabase = true;
        // Get all abbreviations on pages to which the user has access
        $lockBeUserToDBmounts = isset($this->configuration['thisConfig']['buttons.'][$button . '.']['lockBeUserToDBmounts']) ? $this->configuration['thisConfig']['buttons.'][$button . '.']['lockBeUserToDBmounts'] : $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'];
        $savedGroupDataWebmounts = $backendUser->groupData['webmounts'];
        if (!$backendUser->isAdmin() && $GLOBALS['TYPO3_CONF_VARS']['BE']['lockBeUserToDBmounts'] && $lockBeUserToDBmounts) {
            // Temporarily setting alternative web browsing mounts
            $altMountPoints = trim($backendUser->getTSConfigVal('options.pageTree.altElementBrowserMountPoints'));
            if ($altMountPoints) {
                $backendUser->groupData['webmounts'] = implode(',', array_unique(GeneralUtility::intExplode(',', $altMountPoints)));
            }
            $webMounts = $backendUser->returnWebmounts();
            $perms_clause = $backendUser->getPagePermsClause(1);
            $recursive = isset($this->configuration['thisConfig']['buttons.'][$button . '.']['recursive']) ? (int)$this->configuration['thisConfig']['buttons.'][$button . '.']['recursive'] : 0;
            if (trim($this->configuration['thisConfig']['buttons.'][$button . '.']['pages'])) {
                $pids = GeneralUtility::trimExplode(',', $this->configuration['thisConfig']['buttons.'][$button . '.']['pages'], true);
                foreach ($pids as $key => $val) {
                    if (!$backendUser->isInWebMount($val, $perms_clause)) {
                        unset($pids[$key]);
                    }
                }
            } else {
                $pids = $webMounts;
            }
            // Restoring webmounts
            $backendUser->groupData['webmounts'] = $savedGroupDataWebmounts;
            $queryGenerator = GeneralUtility::makeInstance(QueryGenerator::class);
            $pageTree = '';
            $pageTreePrefix = '';
            foreach ($pids as $key => $val) {
                if ($pageTree) {
                    $pageTreePrefix = ',';
                }
                $pageTree .= $pageTreePrefix . $queryGenerator->getTreeList($val, $recursive, ($begin = 0), $perms_clause);
            }

            if ($pageTree !== '') {
                $whereClause .= ' AND ' . $tableA . '.pid IN (' . $pageTree . ')';
            } else {
                // If page tree is empty the user does not have access to any pages / acronyms.
                // This is why we do not try do read any records from the database.
                $loadRecordsFromDatabase = false;
            }
        }

        if ($loadRecordsFromDatabase) {
            // Restrict to abbreviations applicable to the language of current content element
            if ($this->configuration['contentLanguageUid'] > -1) {
                $whereClause .= ' AND (' . $tableA . '.sys_language_uid=' . $this->configuration['contentLanguageUid'] . ' OR ' . $tableA . '.sys_language_uid=-1) ';
            }
            // Restrict to abbreviations in certain languages
            if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.']['language.']) && isset($this->configuration['thisConfig']['buttons.']['language.']['restrictToItems'])) {
                $languageList = implode('\',\'', GeneralUtility::trimExplode(',', $database->fullQuoteStr(strtoupper($this->configuration['thisConfig']['buttons.']['language.']['restrictToItems']), $tableB)));
                $whereClause .= ' AND ' . $tableB . '.lg_iso_2 IN (' . $languageList . ') ';
            }
            $whereClause .= BackendUtility::BEenableFields($tableA);
            $whereClause .= BackendUtility::deleteClause($tableA);
            $whereClause .= BackendUtility::BEenableFields($tableB);
            $whereClause .= BackendUtility::deleteClause($tableB);
            $res = $database->exec_SELECTquery($fields, $tableAB, $whereClause);
            while ($abbreviationRow = $database->sql_fetch_assoc($res)) {
                $item = ['term' => $abbreviationRow['term'], 'abbr' => $abbreviationRow['acronym'], 'language' => strtolower($abbreviationRow['lg_iso_2']) . ($abbreviationRow['lg_country_iso_2'] ? '-' . $abbreviationRow['lg_country_iso_2'] : '')];
                if ($abbreviationRow['type'] == 1) {
                    $acronymArray[] = $item;
                } elseif ($abbreviationRow['type'] == 2) {
                    $abbrArray[] = $item;
                }
            }
            $database->sql_free_result($res);
        }

        $this->acronymIndex = count($acronymArray);
        $this->abbreviationIndex = count($abbrArray);
        return json_encode(['abbr' => $abbrArray, 'acronym' => $acronymArray]);
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUserAuthentication()
    {
        return $GLOBALS['BE_USER'];
    }
}
