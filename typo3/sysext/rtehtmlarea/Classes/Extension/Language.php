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

use SJBR\StaticInfoTables\Utility\LocalizationUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Rtehtmlarea\RteHtmlAreaApi;

/**
 * Language plugin for htmlArea RTE
 */
class Language extends RteHtmlAreaApi
{
    /**
     * The name of the plugin registered by the extension
     *
     * @var string
     */
    protected $pluginName = 'Language';

    /**
     * The comma-separated list of button names that the registered plugin is adding to the htmlArea RTE toolbar
     *
     * @var string
     */
    protected $pluginButtons = 'lefttoright,righttoleft,language,showlanguagemarks';

    /**
     * The name-converting array, converting the button names used in the RTE PageTSConfing to the button id's used by the JS scripts
     *
     * @var array
     */
    protected $convertToolbarForHtmlAreaArray = [
        'lefttoright' => 'LeftToRight',
        'righttoleft' => 'RightToLeft',
        'language' => 'Language',
        'showlanguagemarks' => 'ShowLanguageMarks'
    ];

    /**
     * Returns TRUE if the plugin is available and correctly initialized
     *
     * @param array $configuration Configuration array given from calling object down to the single plugins
     * @return bool TRUE if this plugin object should be made available in the current environment and is correctly initialized
     */
    public function main(array $configuration)
    {
        if (!ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $this->pluginButtons = GeneralUtility::rmFromList('language', $this->pluginButtons);
        }
        return parent::main($configuration);
    }

    /**
     * Return JS configuration of the htmlArea plugins registered by the extension
     *
     * @return string JS configuration for registered plugins
     */
    public function buildJavascriptConfiguration()
    {
        $button = 'language';
        $jsArray = [];
        if (!is_array($this->configuration['thisConfig']['buttons.']) || !is_array($this->configuration['thisConfig']['buttons.'][$button . '.'])) {
            $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . ' = new Object();';
        }
        $languages = [
            'none' => $this->getLanguageService()->sL(
                'LLL:EXT:rtehtmlarea/Resources/Private/Language/Plugins/Language/locallang.xlf:No language mark'
            ),
        ];
        $languages = array_flip(array_merge($languages, $this->getLanguages()));
        $languagesJSArray = [];
        foreach ($languages as $key => $value) {
            $languagesJSArray[] = ['text' => $key, 'value' => $value];
        }
        $languagesJSArray = json_encode(['options' => $languagesJSArray]);
        $jsArray[] = 'RTEarea[editornumber].buttons.' . $button . '.dataUrl = "' . $this->writeTemporaryFile($button . '_' . $this->configuration['contentLanguageUid'], 'js', $languagesJSArray) . '";';
        return implode(LF, $jsArray);
    }

    /**
     * Getting all languages into an array
     * where the key is the ISO alpha-2 code of the language
     * and where the value are the name of the language in the current language
     * Note: we exclude sacred and constructed languages
     *
     * @return array An array of names of languages
     */
    protected function getLanguages()
    {
        $databaseConnection = $this->getDatabaseConnection();
        $nameArray = [];
        if (ExtensionManagementUtility::isLoaded('static_info_tables')) {
            $where = '1=1';
            $table = 'static_languages';
            $lang = LocalizationUtility::getCurrentLanguage();
            $titleFields = LocalizationUtility::getLabelFields($table, $lang);
            $prefixedTitleFields = [];
            foreach ($titleFields as $titleField) {
                $prefixedTitleFields[] = $table . '.' . $titleField;
            }
            $labelFields = implode(',', $prefixedTitleFields);
            // Restrict to certain languages
            if (is_array($this->configuration['thisConfig']['buttons.']) && is_array($this->configuration['thisConfig']['buttons.']['language.']) && isset($this->configuration['thisConfig']['buttons.']['language.']['restrictToItems'])) {
                $languageList = implode('\',\'', GeneralUtility::trimExplode(',', $databaseConnection->fullQuoteStr(strtoupper($this->configuration['thisConfig']['buttons.']['language.']['restrictToItems']), $table)));
                $where .= ' AND ' . $table . '.lg_iso_2 IN (' . $languageList . ')';
            }
            $res = $databaseConnection->exec_SELECTquery(
                $table . '.lg_iso_2,' . $table . '.lg_country_iso_2,' . $labelFields,
                $table,
                $where . ' AND lg_constructed = 0 ' . BackendUtility::BEenableFields($table) . BackendUtility::deleteClause($table)
            );
            $prefixLabelWithCode = (bool)$this->configuration['thisConfig']['buttons.']['language.']['prefixLabelWithCode'];
            $postfixLabelWithCode = (bool)$this->configuration['thisConfig']['buttons.']['language.']['postfixLabelWithCode'];
            while ($row = $databaseConnection->sql_fetch_assoc($res)) {
                $code = strtolower($row['lg_iso_2']) . ($row['lg_country_iso_2'] ? '-' . strtoupper($row['lg_country_iso_2']) : '');
                foreach ($titleFields as $titleField) {
                    if ($row[$titleField]) {
                        $nameArray[$code] = $prefixLabelWithCode ? $code . ' - ' . $row[$titleField] : ($postfixLabelWithCode ? $row[$titleField] . ' - ' . $code : $row[$titleField]);
                        break;
                    }
                }
            }
            $databaseConnection->sql_free_result($res);
            uasort($nameArray, 'strcoll');
        }
        return $nameArray;
    }

    /**
     * Return an updated array of toolbar enabled buttons
     *
     * @param array $show: array of toolbar elements that will be enabled, unless modified here
     * @return array toolbar button array, possibly updated
     */
    public function applyToolbarConstraints($show)
    {
        if (!ExtensionManagementUtility::isLoaded('static_info_tables')) {
            return array_diff($show, ['language']);
        } else {
            return $show;
        }
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
