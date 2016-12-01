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
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\BackendWorkspaceRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Contains translation tools
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
        $modSharedTSconfig = BackendUtility::getModTSconfig($pageId, 'mod.SHARED');

        // default language and "all languages" are always present
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
        $translationTable = $this->getTranslationTable($table);
        if ($translationTable === '') {
            return 'Translation is not supported for this table!';
        }
        if ($translationTable === $table && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a language value "' . $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] . '", relation to record "' . $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] . '")';
        }
        if ($translationTable === $table && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] != 0) {
            return 'Record "' . $table . '_' . $uid . '" seems to be a translation already (has a relation to record "' . $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] . '")';
        }
        // Look for translations of this record, index by language field value:
        if (!$selFieldList) {
            $selFieldList = 'uid,' . $GLOBALS['TCA'][$translationTable]['ctrl']['languageField'];
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($translationTable);
        $queryBuilder->getRestrictions()
            ->removeAll()
            ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
            ->add(GeneralUtility::makeInstance(BackendWorkspaceRestriction::class));
        $queryBuilder
            ->select(...GeneralUtility::trimExplode(',', $selFieldList))
            ->from($translationTable)
            ->where(
                $queryBuilder->expr()->eq(
                    $GLOBALS['TCA'][$translationTable]['ctrl']['transOrigPointerField'],
                    $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter(
                        ($table === 'pages' ? $row['uid'] : $row['pid']),
                        \PDO::PARAM_INT
                    )
                )
            );
        if (!$languageUid) {
            $queryBuilder->andWhere(
                $queryBuilder->expr()->gt(
                    $GLOBALS['TCA'][$translationTable]['ctrl']['languageField'],
                    $queryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            );
        } else {
            $queryBuilder
                ->andWhere(
                    $queryBuilder->expr()->eq(
                        $GLOBALS['TCA'][$translationTable]['ctrl']['languageField'],
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
            if (!isset($translations[$translationRecord[$GLOBALS['TCA'][$translationTable]['ctrl']['languageField']]])) {
                $translations[$translationRecord[$GLOBALS['TCA'][$translationTable]['ctrl']['languageField']]] = $translationRecord;
            } else {
                $translationsErrors[$translationRecord[$GLOBALS['TCA'][$translationTable]['ctrl']['languageField']]][] = $translationRecord;
            }
        }
        return [
            'table' => $table,
            'uid' => $uid,
            'CType' => $row['CType'],
            'sys_language_uid' => $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']],
            'translation_table' => $translationTable,
            'translations' => $translations,
            'excessive_translations' => $translationsErrors
        ];
    }

    /**
     * Returns the table in which translations for input table is found.
     *
     * @param string $table The table name
     * @return string
     */
    public function getTranslationTable($table)
    {
        return $this->isTranslationInOwnTable($table) ? $table : $this->foreignTranslationTable($table);
    }

    /**
     * Returns TRUE, if the input table has localization enabled and done so with records from the same table
     *
     * @param string $table The table name
     * @return bool
     */
    public function isTranslationInOwnTable($table)
    {
        return $GLOBALS['TCA'][$table]['ctrl']['languageField'] && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && $table !== 'pages_language_overlay';
    }

    /**
     * Returns foreign translation table, if any
     *
     * @param string $table The table name
     * @return string Translation foreign table
     */
    public function foreignTranslationTable($table)
    {
        return $table === 'pages' ? 'pages_language_overlay' : '';
    }

    /**
     * @param array $modSharedTSconfig
     * @return string
     */
    protected function getDefaultLanguageFlag(array $modSharedTSconfig)
    {
        if (strlen($modSharedTSconfig['properties']['defaultLanguageFlag'])) {
            $defaultLanguageFlag = 'flags-' . $modSharedTSconfig['properties']['defaultLanguageFlag'];
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
        if (strlen($modSharedTSconfig['properties']['defaultLanguageLabel'])) {
            $defaultLanguageLabel = $modSharedTSconfig['properties']['defaultLanguageLabel'] . ' (' . $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage') . ')';
        } else {
            $defaultLanguageLabel = $this->getLanguageService()->sL('LLL:EXT:lang/Resources/Private/Language/locallang_mod_web_list.xlf:defaultLanguage');
        }
        return $defaultLanguageLabel;
    }
}
