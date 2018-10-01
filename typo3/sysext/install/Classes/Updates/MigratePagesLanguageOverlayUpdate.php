<?php
declare(strict_types = 1);
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

use Symfony\Component\Console\Output\OutputInterface;
use TYPO3\CMS\Core\Configuration\ConfigurationManager;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\Service\LoadTcaService;

/**
 * Merge pages_language_overlay rows into pages table
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class MigratePagesLanguageOverlayUpdate implements UpgradeWizardInterface, ChattyInterface
{
    /**
     * @var OutputInterface
     */
    protected $output;

    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'pagesLanguageOverlay';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Migrate content from pages_language_overlay to pages';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'The table pages_language_overlay will be removed to align the translation '
            . 'handling for pages with the rest of the core. This wizard transfers all data to the pages '
            . 'table by creating new entries and linking them to the l10n parent. This might take a while, '
            . 'because max. (amount of pages) x (active languages) new entries need be created.';
    }

    /**
     * Checks whether updates are required.
     *
     * @return bool Whether an update is required (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
        // Check if the database table even exists
        if ($this->checkIfWizardIsRequired()) {
            return true;
        }
        return false;
    }

    /**
     * @return string[] All new fields and tables must exist
     */
    public function getPrerequisites(): array
    {
        return [
            DatabaseUpdatedPrerequisite::class
        ];
    }

    /**
     * Additional output if there are columns with mm config
     *
     * @param OutputInterface $output
     */
    public function setOutput(OutputInterface $output): void
    {
        $this->output = $output;
    }

    /**
     * Performs the update.
     *
     * @return bool Whether everything went smoothly or not
     */
    public function executeUpdate(): bool
    {
        // Warn for TCA relation configurations which are not migrated.
        if (isset($GLOBALS['TCA']['pages_language_overlay']['columns'])
            && is_array($GLOBALS['TCA']['pages_language_overlay']['columns'])
        ) {
            foreach ($GLOBALS['TCA']['pages_language_overlay']['columns'] as $fieldName => $fieldConfiguration) {
                if (isset($fieldConfiguration['config']['MM'])) {
                    $this->output->writeln('The pages_language_overlay field ' . $fieldName
                        . ' with its MM relation configuration can not be migrated'
                        . ' automatically. Existing data relations to this field have'
                        . ' to be migrated manually.');
                }
            }
        }

        // Ensure pages_language_overlay is still available in TCA
        GeneralUtility::makeInstance(LoadTcaService::class)->loadExtensionTablesWithoutMigration();
        $this->mergePagesLanguageOverlayIntoPages();
        $this->updateInlineRelations();
        $this->updateSysHistoryRelations();
        $this->enableFeatureFlag();
        return true;
    }

    /**
     * 1. Fetches ALL pages_language_overlay (= translations) records
     * 2. Fetches the given page record (= original language) for each translation
     * 3. Populates the values from the original language IF the field in the translation record is NOT SET (empty is fine)
     * 4. Adds proper fields for the translations which is
     *   - l10n_parent = UID of the original-language-record
     *   - pid = PID of the original-language-record (please note: THIS IS DIFFERENT THAN IN pages_language_overlay)
     *   - l10n_source = UID of the original-language-record (only this is supported currently)
     */
    protected function mergePagesLanguageOverlayIntoPages()
    {
        $overlayQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages_language_overlay');
        $overlayQueryBuilder->getRestrictions()->removeAll();
        $overlayRecords = $overlayQueryBuilder
            ->select('*')
            ->from('pages_language_overlay')
            ->execute();
        $pagesConnection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('pages');
        $pagesColumns = $pagesConnection->getSchemaManager()->listTableDetails('pages')->getColumns();
        $pagesColumnTypes = [];
        foreach ($pagesColumns as $pageColumn) {
            $pagesColumnTypes[$pageColumn->getName()] = $pageColumn->getType()->getBindingType();
        }
        while ($overlayRecord = $overlayRecords->fetch()) {
            // Early continue if record has been migrated before
            if ($this->isOverlayRecordMigratedAlready((int)$overlayRecord['uid'])) {
                continue;
            }

            $values = [];
            $originalPageId = (int)$overlayRecord['pid'];
            $page = $this->fetchDefaultLanguagePageRecord($originalPageId);
            if (!empty($page)) {
                foreach ($pagesColumns as $pageColumn) {
                    $name = $pageColumn->getName();
                    if (isset($overlayRecord[$name])) {
                        $values[$name] = $overlayRecord[$name];
                    } elseif (isset($page[$name])) {
                        $values[$name] = $page[$name];
                    }
                }

                $values['pid'] = $page['pid'];
                $values['l10n_parent'] = $originalPageId;
                $values['l10n_source'] = $originalPageId;
                $values['legacy_overlay_uid'] = $overlayRecord['uid'];
                unset($values['uid']);
                $pagesConnection->insert(
                    'pages',
                    $values,
                    $pagesColumnTypes
                );
            }
        }
    }

    /**
     * Inline relations with foreign_field, foreign_table, foreign_table_field on
     * pages_language_overlay TCA get their existing relations updated to new
     * uid and pages table.
     */
    protected function updateInlineRelations()
    {
        if (isset($GLOBALS['TCA']['pages_language_overlay']['columns']) && is_array($GLOBALS['TCA']['pages_language_overlay']['columns'])) {
            foreach ($GLOBALS['TCA']['pages_language_overlay']['columns'] as $fieldName => $fieldConfiguration) {
                // Migrate any 1:n relations
                if ($fieldConfiguration['config']['type'] === 'inline'
                    && !empty($fieldConfiguration['config']['foreign_field'])
                    && !empty($fieldConfiguration['config']['foreign_table'])
                    && !empty($fieldConfiguration['config']['foreign_table_field'])
                ) {
                    $foreignTable = trim($fieldConfiguration['config']['foreign_table']);
                    $foreignField = trim($fieldConfiguration['config']['foreign_field']);
                    $foreignTableField = trim($fieldConfiguration['config']['foreign_table_field']);
                    $translatedPagesQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
                    $translatedPagesQueryBuilder->getRestrictions()->removeAll();
                    $translatedPagesRows = $translatedPagesQueryBuilder
                        ->select('uid', 'legacy_overlay_uid')
                        ->from('pages')
                        ->where(
                            $translatedPagesQueryBuilder->expr()->gt(
                                'l10n_parent',
                                $translatedPagesQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                            )
                        )
                        ->execute();
                    while ($translatedPageRow = $translatedPagesRows->fetch()) {
                        $foreignTableQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($foreignTable);
                        $foreignTableQueryBuilder->getRestrictions()->removeAll();
                        $foreignTableQueryBuilder
                            ->update($foreignTable)
                            ->set($foreignField, $translatedPageRow['uid'])
                            ->set($foreignTableField, 'pages')
                            ->where(
                                $foreignTableQueryBuilder->expr()->eq(
                                    $foreignField,
                                    $foreignTableQueryBuilder->createNamedParameter($translatedPageRow['legacy_overlay_uid'], \PDO::PARAM_INT)
                                ),
                                $foreignTableQueryBuilder->expr()->eq(
                                    $foreignTableField,
                                    $foreignTableQueryBuilder->createNamedParameter('pages_language_overlay', \PDO::PARAM_STR)
                                )
                            )
                            ->execute();
                    }
                }
            }
        }
    }

    /**
     * Update recuid and tablename of sys_history table to pages and new uid
     * for all pages_language_overlay rows
     */
    protected function updateSysHistoryRelations()
    {
        $translatedPagesQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $translatedPagesQueryBuilder->getRestrictions()->removeAll();
        $translatedPagesRows = $translatedPagesQueryBuilder
            ->select('uid', 'legacy_overlay_uid')
            ->from('pages')
            ->where(
                $translatedPagesQueryBuilder->expr()->gt(
                    'l10n_parent',
                    $translatedPagesQueryBuilder->createNamedParameter(0, \PDO::PARAM_INT)
                )
            )
            ->execute();
        while ($translatedPageRow = $translatedPagesRows->fetch()) {
            $historyTableQueryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_history');
            $historyTableQueryBuilder->getRestrictions()->removeAll();
            $historyTableQueryBuilder
                ->update('sys_history')
                ->set('tablename', 'pages')
                ->set('recuid', $translatedPageRow['uid'])
                ->where(
                    $historyTableQueryBuilder->expr()->eq(
                        'recuid',
                        $historyTableQueryBuilder->createNamedParameter($translatedPageRow['legacy_overlay_uid'], \PDO::PARAM_INT)
                    ),
                    $historyTableQueryBuilder->expr()->eq(
                        'tablename',
                        $historyTableQueryBuilder->createNamedParameter('pages_language_overlay', \PDO::PARAM_STR)
                    )
                )
                ->execute();
        }
    }

    /**
     * Fetches a certain page
     *
     * @param int $pageId
     * @return array
     */
    protected function fetchDefaultLanguagePageRecord(int $pageId): array
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $page = $queryBuilder
            ->select('*')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return $page ?: [];
    }

    /**
     * Verify if a single overlay record has been migrated to pages already
     * by checking the db field legacy_overlay_uid for the orig uid
     *
     * @param int $overlayUid
     * @return bool
     */
    protected function isOverlayRecordMigratedAlready(int $overlayUid): bool
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $migratedRecord = $queryBuilder
            ->select('uid')
            ->from('pages')
            ->where(
                $queryBuilder->expr()->eq(
                    'legacy_overlay_uid',
                    $queryBuilder->createNamedParameter($overlayUid, \PDO::PARAM_INT)
                )
            )
            ->execute()
            ->fetch();
        return !empty($migratedRecord);
    }

    /**
     * Check if the database table "pages_language_overlay" exists and if so, if there are entries in the DB table.
     *
     * @return bool
     * @throws \InvalidArgumentException
     */
    protected function checkIfWizardIsRequired(): bool
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName('Default');
        $tableNames = $connection->getSchemaManager()->listTableNames();
        if (in_array('pages_language_overlay', $tableNames, true)) {
            // table is available, now check if there are entries in it
            $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
                ->getQueryBuilderForTable('pages_language_overlay');
            $numberOfEntries = $queryBuilder->count('*')
                ->from('pages_language_overlay')
                ->execute()
                ->fetchColumn();
            return (bool)$numberOfEntries;
        }

        return false;
    }

    /**
     * Once the update wizard is run through, the feature to not load any pages_language_overlay data can
     * be activated.
     *
     * Basically writes 'SYS/features/unifiedPageTranslationHandling' to LocalConfiguration.php
     */
    protected function enableFeatureFlag()
    {
        GeneralUtility::makeInstance(ConfigurationManager::class)->enableFeature('unifiedPageTranslationHandling');
    }
}
