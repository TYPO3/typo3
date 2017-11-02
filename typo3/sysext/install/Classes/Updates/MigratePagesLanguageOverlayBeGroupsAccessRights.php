<?php
declare(strict_types=1);
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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Merge access rights from be_groups concerning pages_language_overlay
 * into pages
 */
class MigratePagesLanguageOverlayBeGroupsAccessRights extends AbstractUpdate
{
    /**
     * The human-readable title of the upgrade wizard
     *
     * @var string
     */
    protected $title = 'Merge be_groups access rights from pages_language_overlay to pages';

    /**
     * Checks whether updates are required.
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        $description = 'The table pages_language_overlay will be removed to align the translation ' .
            'handling for pages with the rest of the core. This wizard transfers all be_groups with ' .
            'access restrictions to pages_language_overlay into pages.';

        $updateNeeded = false;

        if (!$this->isWizardDone()) {
            $updateNeeded = true;
        }

        return $updateNeeded;
    }

    /**
     * Performs the accordant updates.
     *
     * @param array &$dbQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool Whether everything went smoothly or not
     * @throws \InvalidArgumentException
     */
    public function performUpdate(array &$dbQueries, &$customMessage)
    {
        $beGroupsQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
        $beGroupsQueryBuilder->getRestrictions()->removeAll();
        $beGroupsRows = $beGroupsQueryBuilder
            ->select('uid', 'non_exclude_fields', 'tables_modify')
            ->from('be_groups')
            ->execute();
        while ($beGroupsRow = $beGroupsRows->fetch()) {
            $updateNeeded = false;
            if (!empty($beGroupsRow['tables_modify'])) {
                // If 'pages_language_overlay' is allowed as table-modify, remove it and add
                // 'pages' if it is not in there, yet.
                $tablesArray = GeneralUtility::trimExplode(',', $beGroupsRow['tables_modify'], true);
                $newTablesArray = $tablesArray;
                if (in_array('pages_language_overlay', $tablesArray, true)) {
                    $updateNeeded = true;
                    $newTablesArray = array_diff($tablesArray, ['pages_language_overlay']);
                    if (!in_array('pages', $newTablesArray, true)) {
                        $newTablesArray[] = 'pages';
                    }
                }
            } else {
                $newTablesArray = [];
            }
            if (!empty($beGroupsRow['non_exclude_fields'])) {
                // Exclude fields on 'pages_language_overlay' are removed and added as
                // exclude fields on 'pages'
                $excludeFields = GeneralUtility::trimExplode(',', $beGroupsRow['non_exclude_fields'], true);
                $newExcludeFields = [];
                foreach ($excludeFields as $tableFieldCombo) {
                    if (strpos($tableFieldCombo, 'pages_language_overlay:') === 0) {
                        $updateNeeded = true;
                        $field = substr($tableFieldCombo, strlen('pages_language_overlay:'));
                        $newExcludeFields[] = 'pages:' . $field;
                    } else {
                        $newExcludeFields[] = $tableFieldCombo;
                    }
                }
                array_unique($newExcludeFields);
            } else {
                $newExcludeFields = [];
            }
            if ($updateNeeded) {
                $updateBeGroupsQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_groups');
                $updateBeGroupsQueryBuilder
                    ->update('be_groups')
                    ->set('tables_modify', implode(',', $newTablesArray))
                    ->set('non_exclude_fields', implode(',', $newExcludeFields))
                    ->where(
                        $updateBeGroupsQueryBuilder->expr()->eq(
                            'uid',
                            $updateBeGroupsQueryBuilder->createNamedParameter($beGroupsRow['uid'], \PDO::PARAM_INT)
                        )
                    )
                    ->execute();
            }
        }
        $this->markWizardAsDone();
        return true;
    }
}
