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
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class SplitMenusUpdate implements UpgradeWizardInterface
{
    /**
     * @return string Unique identifier of this updater
     */
    public function getIdentifier(): string
    {
        return 'splitMenusUpdate';
    }

    /**
     * @return string Title of this updater
     */
    public function getTitle(): string
    {
        return 'Split menu types into dedicated content elements';
    }

    /**
     * @return string Longer description of this updater
     */
    public function getDescription(): string
    {
        return 'Menus have been split into dedicated content elements to provide '
            . 'a better maintainability and more easy to adjustable template with single '
            . 'responsibility for the rendering.';
    }

    /**
     * Checks if an update is needed
     *
     * @return bool Whether an update is needed (TRUE) or not (FALSE)
     */
    public function updateNecessary(): bool
    {
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
        return (bool)$elementCount;
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
     * Performs the database update
     *
     * @return bool
     */
    public function executeUpdate(): bool
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
            $queryBuilder->execute();
        }
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
