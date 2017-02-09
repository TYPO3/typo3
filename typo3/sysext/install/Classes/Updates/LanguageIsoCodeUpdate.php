<?php
namespace TYPO3\CMS\Install\Updates;

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

use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Update sys_language records to use the newly created
 * field language_isocode, if they have used the now deprecated
 * static_lang_isocode
 */
class LanguageIsoCodeUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Update sys_language records to use new ISO 639-1 letter-code field';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone() || !ExtensionManagementUtility::isLoaded('static_info_tables')) {
            return false;
        }

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $numberOfAffectedRows = $queryBuilder->count('uid')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('language_isocode', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)),
                $queryBuilder->expr()->isNotNull('static_lang_isocode')
            )
            ->execute()
            ->fetchColumn(0);
        if ((bool)$numberOfAffectedRows) {
            $description = 'The sys_language records have a new iso code field which removes the dependency of the'
                . ' TYPO3 CMS Core to the extension "static_info_tables". This upgrade wizard migrates the data of the'
                . ' existing "static_lang_isocode" field to the new DB field.';
        }
        return (bool)$numberOfAffectedRows;
    }

    /**
     * Performs the database update if the old field "static_lang_isocode"
     * is in use and populates the new field "language_isocode" with the
     * data of the old relation.
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_language');
        $statement = $queryBuilder->select('uid', 'language_isocode', 'static_lang_isocode')
            ->from('sys_language')
            ->where(
                $queryBuilder->expr()->eq('language_isocode', $queryBuilder->createNamedParameter('', \PDO::PARAM_STR)),
                $queryBuilder->expr()->isNotNull('static_lang_isocode')
            )
            ->execute();
        while ($languageRecord = $statement->fetch()) {
            $staticLanguageRecord = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getConnectionForTable('static_languages')
                ->select(
                    ['lg_iso_2'],
                    'static_languages',
                    ['uid' => (int)$languageRecord['static_lang_isocode']]
                )
                ->fetch();
            if (!empty($staticLanguageRecord['lg_iso_2'])) {
                $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                    ->getQueryBuilderForTable('sys_language');
                $queryBuilder->update('sys_language')
                    ->where(
                        $queryBuilder->expr()->eq(
                            'uid',
                            $queryBuilder->createNamedParameter($languageRecord['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->set('language_isocode', strtolower($staticLanguageRecord['lg_iso_2']));
                $databaseQueries[] = $queryBuilder->getSQL();
                $queryBuilder->execute();
            }
        }
        $this->markWizardAsDone();
        return true;
    }
}
