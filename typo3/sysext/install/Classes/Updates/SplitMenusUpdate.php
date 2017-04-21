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
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Split menu types into dedicated content elements
 */
class SplitMenusUpdate extends AbstractUpdate
{
    /**
     * @var string
     */
    protected $title = 'Split menu types into dedicated content elements';

    /**
     * Checks if an update is needed
     *
     * @param string &$description The description for the update
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function checkForUpdate(&$description)
    {
        if ($this->isWizardDone()) {
            return false;
        }
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $tableColumns = $connection->getSchemaManager()->listTableColumns('tt_content');
        // Only proceed if menu_type field still exists
        if (!isset($tableColumns['menu_type'])) {
            return false;
        }
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('tt_content');
        $queryBuilder->getRestrictions()->removeAll();
        $elementCount = $queryBuilder->count('uid')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq('CType', $queryBuilder->createNamedParameter('menu', \PDO::PARAM_STR))
            )
            ->execute()->fetchColumn(0);
        if ($elementCount) {
            $description = 'Menus have been splitted into dedicated content elements to provide '
                . 'a better maintainability and more easy to adjustable template with single '
                . 'responsibility for the rendering.';
        }
        return (bool)$elementCount;
    }

    /**
     * Performs the database update
     *
     * @param array &$databaseQueries Queries done in this update
     * @param string &$customMessage Custom message
     * @return bool
     */
    public function performUpdate(array &$databaseQueries, &$customMessage)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable('tt_content');
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('uid', 'header', 'menu_type')
            ->from('tt_content')
            ->where(
                $queryBuilder->expr()->eq(
                    'CType',
                    $queryBuilder->createNamedParameter('menu', \PDO::PARAM_STR)
                )
            )
            ->execute();
        while ($record = $statement->fetch()) {
            $queryBuilder = $connection->createQueryBuilder();
            $queryBuilder->update('tt_content')
                ->where(
                    $queryBuilder->expr()->eq(
                        'uid',
                        $queryBuilder->createNamedParameter($record['uid'], \PDO::PARAM_INT)
                    )
                )
                ->set('CType', $this->mapMenuTypes($record['menu_type']));
            $databaseQueries[] = $queryBuilder->getSQL();
            $queryBuilder->execute();
        }
        $this->markWizardAsDone();
        return true;
    }

    /**
     * Map the old to the new values
     *
     * @param string $menuType The content of the FlexForm
     * @return string The equivalent CType
     */
    protected function mapMenuTypes($menuType)
    {
        $mapping = [
            0 => 'menu_pages',
            1 => 'menu_subpages',
            2 => 'menu_sitemap',
            3 => 'menu_section',
            4 => 'menu_abstract',
            5 => 'menu_recently_updated',
            6 => 'menu_related_pages',
            7 => 'menu_section_pages',
            8 => 'menu_sitemap_pages',
            'categorized_pages' => 'menu_categorized_pages',
            'categorized_content' => 'menu_categorized_content'
        ];
        if (array_key_exists($menuType, $mapping)) {
            return $mapping[$menuType];
        }
        return 'menu_' . $menuType;
    }
}
