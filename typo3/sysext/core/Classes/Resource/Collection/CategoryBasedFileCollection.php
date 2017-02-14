<?php
namespace TYPO3\CMS\Core\Resource\Collection;

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
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * A collection containing a set files belonging to certain categories.
 * This collection is persisted to the database with the accordant category identifiers.
 */
class CategoryBasedFileCollection extends AbstractFileCollection
{
    /**
     * @var string
     */
    protected static $storageTableName = 'sys_file_collection';

    /**
     * @var string
     */
    protected static $type = 'categories';

    /**
     * @var string
     */
    protected static $itemsCriteriaField = 'category';

    /**
     * @var string
     */
    protected $itemTableName = 'sys_category';

    /**
     * Populates the content-entries of the collection
     */
    public function loadContents()
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('sys_file_metadata.file')
            ->from('sys_category')
            ->join(
                'sys_category',
                'sys_category_record_mm',
                'sys_category_record_mm',
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_local',
                    $queryBuilder->quoteIdentifier('sys_category.uid')
                )
            )
            ->join(
                'sys_category_record_mm',
                'sys_file_metadata',
                'sys_file_metadata',
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.uid_foreign',
                    $queryBuilder->quoteIdentifier('sys_file_metadata.uid')
                )
            )
            ->where(
                $queryBuilder->expr()->eq(
                    'sys_category.uid',
                    $queryBuilder->createNamedParameter($this->getItemsCriteria(), \PDO::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $queryBuilder->createNamedParameter('sys_file_metadata', \PDO::PARAM_STR)
                )
            )
            ->execute();
        $resourceFactory = ResourceFactory::getInstance();
        while ($record = $statement->fetch()) {
            $this->add($resourceFactory->getFileObject((int)$record['file']));
        }
    }
}
