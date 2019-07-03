<?php

namespace TYPO3\CMS\Backend\Configuration;

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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains translation tools
 *
 * @internal The whole class is subject to be removed, fetch all language info from the current site object.
 */
class TranslationConfigurationProvider
{
    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns array of system languages
     * The property flagIcon returns a string <flags-xx>.
     *
     * @param int $pageId Page id (used to get TSconfig configuration setting flag and label for default language)
     * @return array Array with languages (uid, title, ISOcode, flagIcon)
     */
    public function getSystemLanguages($pageId = 0)
    {
        try {
            $site = GeneralUtility::makeInstance(SiteMatcher::class)->matchByPageId((int)$pageId);
            $siteLanguages = $site->getAvailableLanguages($this->getBackendUserAuthentication(), true);

            if (!isset($siteLanguages[0])) {
                $siteLanguages[0] = $site->getDefaultLanguage();
                ksort($siteLanguages);
            }

            $languages = [];
            foreach ($siteLanguages as $id => $siteLanguage) {
                $languages[$id] = [
                    'uid' => $id,
                    'title' => $siteLanguage->getTitle(),
                    'ISOcode' => $siteLanguage->getTwoLetterIsoCode(),
                    'flagIcon' => $siteLanguage->getFlagIdentifier(),
                ];
            }
        } catch (SiteNotFoundException $e) {
            // default language and "all languages" are always present
            $modSharedTSconfig = BackendUtility::getPagesTSconfig($pageId)['mod.']['SHARED.'] ?? [];
            $languages = [
                // 0: default language
                0 => [
                    'uid' => 0,
                    'title' => $this->getDefaultLanguageLabel($modSharedTSconfig),
                    'ISOcode' => 'DEF',
                    'flagIcon' => $this->getDefaultLanguageFlag($modSharedTSconfig),
                ],
                // -1: all languages
                -1 => [
                    'uid' => -1,
                    'title' => $this->getLanguageService()->getLL('multipleLanguages'),
                    'ISOcode' => 'DEF',
                    'flagIcon' => 'flags-multiple',
                ],
            ];

            // add the additional languages from database records
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
            $languageRecords = $queryBuilder
                ->select('*')
                ->from('sys_language')
                ->orderBy('sorting')
                ->execute()
                ->fetchAll();
            foreach ($languageRecords as $languageRecord) {
                $languages[$languageRecord['uid']] = $languageRecord;
                // @todo: this should probably resolve language_isocode too and throw a deprecation if not filled
                if ($languageRecord['static_lang_isocode'] && ExtensionManagementUtility::isLoaded('static_info_tables')) {
                    $staticLangRow = BackendUtility::getRecord('static_languages', $languageRecord['static_lang_isocode'], 'lg_iso_2');
                    if ($staticLangRow['lg_iso_2']) {
                        $languages[$languageRecord['uid']]['ISOcode'] = $staticLangRow['lg_iso_2'];
                    }
                }
                if ($languageRecord['flag'] !== '') {
                    $languages[$languageRecord['uid']]['flagIcon'] = 'flags-' . $languageRecord['flag'];
                }
            }
        }

        return $languages;
    }

    /**
     * Information about translation for an element
     * Will overlay workspace version of record too!
     *
     * @param string $table Table name
     * @param int $uid Record uid
     * @param int $languageUid Language uid. If 0, then all languages are selected.
     * @param array $row The record to be translated
     * @param string $selFieldList Select fields for the query which fetches the translations of the current record
     * @return mixed Array with information or error message as a string.
     */
    public function translationInfo($table, $uid, $languageUid = 0, array $row = null, $selFieldList = '')
    {
        if (!$GLOBALS['TCA'][$table] || !$uid) {
            return 'No table "' . $table . '" or no UID value';
        }
        if ($row === null) {
            $row = BackendUtility::getRecordWSOL($table, $uid);
        }
        if (!is_array($row)) {
            return 'Record "' . $table . '_' . $uid . '" was not found';
        }
        if (!BackendUtility::isTableLocalizable($table)) {
            return 'Translation is not supported for this table!';
        }
        if ($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a language value "' . $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] . '", relation to record "' . $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] . '")';
        }
        if ($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a relation to record "' . $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] . '")';
        }
        // Look for translations of this record, index by language field value:
        if (!$selFieldList) {
            $selFieldList = 'uid,' . $GLOBALS['TCA'][$table]['ctrl']['languageField'];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $selFieldList))
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        $row['pid'],
                        \PDO::PARAM_INT
                    )
                )
            );
        if (!$languageUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gt(
                    $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        } else {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$table]['ctrl']['languageField'],
                        $queryBuilder->createNamedParameter($languageUid, \PDO::PARAM_INT)
                    )
                );
        }
        $translationRecords = $queryBuilder
            ->execute()
            ->fetchAll();

        $translations = [];
        $translationsErrors = [];
        foreach ($translationRecords as $translationRecord) {
            if (!isset($translations[$translationRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']]])) {
                $translations[$translationRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']]] = $translationRecord;
            } else {
                $translationsErrors[$translationRecord[$GLOBALS['TCA'][$table]['ctrl']['languageField']]][] = $translationRecord;
            }
        }
        return [
            'table' => $table,
            'uid' => $uid,
            'CType' => $row['CType'],
            'sys_language_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']],
            'translations' => $translations,
            'excessive_translations' => $translationsErrors
        ];
    }

    /**
     * Returns the table in which translations for input table is found.
     *
     * @param string $table The table name
     * @return string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 as foreign translation table is not supported anymore
     */
    public function getTranslationTable($table)
    {
        trigger_error('TranslationConfigurationProvider->getTranslationTable() will be removed in TYPO3 v10.0, as the translation table is always the same as the original table.', E_USER_DEPRECATED);
        return BackendUtility::isTableLocalizable($table) ? $table : '';
    }

    /**
     * Returns TRUE, if the input table has localization enabled and done so with records from the same table
     *
     * @param string $table The table name
     * @return bool
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 as foreign translation table is not supported anymore
     */
    public function isTranslationInOwnTable($table)
    {
        trigger_error('TranslationConfigurationProvider->isTranslationInOwnTable() will be removed in TYPO3 v10.0, as the translation table is always the same as the original table.', E_USER_DEPRECATED);
        return $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
    }

    /**
     * Returns foreign translation table, if any.
     * Since TYPO3 v9, even "pages" translations are stored in the same table, having this method return always
     * empty, as with other tables as well.
     *
     * @param string $table The table name
     * @return string Translation foreign table
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0 as foreign translation table is not supported anymore
     */
    public function foreignTranslationTable($table)
    {
        trigger_error('TranslationConfigurationProvider->foreignTranslationTable() will be removed in TYPO3 v10.0, as the translation table is always the same as the original table.', E_USER_DEPRECATED);
        return '';
    }

    /**
     * @param array $modSharedTSconfig
     * @return string
     */
    protected function getDefaultLanguageFlag(array $modSharedTSconfig)
    {
        if (strlen($modSharedTSconfig['defaultLanguageFlag'])) {
            $defaultLanguageFlag = 'flags-' . $modSharedTSconfig['defaultLanguageFlag'];
        } else {
            $defaultLanguageFlag = 'empty-empty';
        }
        return $defaultLanguageFlag;
    }

    /**
     * @param array $modSharedTSconfig
     * @return string
     */
    protected function getDefaultLanguageLabel(array $modSharedTSconfig)
    {
        if (strlen($modSharedTSconfig['defaultLanguageLabel'])) {
            $defaultLanguageLabel = $modSharedTSconfig['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage') . ')';
        } else {
            $defaultLanguageLabel = $this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage');
        }
        return $defaultLanguageLabel;
    }

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
