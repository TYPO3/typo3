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
use TYPO3\CMS\Core\Site\Entity\NullSite;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Contains translation tools
 *
 * @internal The whole class is subject to be removed, fetch all language info from the current site object.
 */
class TranslationConfigurationProvider
{
    /**
     * Returns array of languages given for a specific site (or "nullSite" if on page=0)
     * The property flagIcon returns a string <flags-xx>.
     *
     * @param int $pageId Page id (used to get TSconfig configuration setting flag and label for default language)
     * @return array Array with languages (uid, title, ISOcode, flagIcon)
     */
    public function getSystemLanguages($pageId = 0)
    {
        try {
            $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByPageId((int)$pageId);
        } catch (SiteNotFoundException $e) {
            $site = new NullSite();
        }
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

    protected function getBackendUserAuthentication(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
