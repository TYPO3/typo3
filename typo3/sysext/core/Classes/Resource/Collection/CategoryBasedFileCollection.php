<?php

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

namespace TYPO3\CMS\Core\Resource\Collection;

use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Database\Connection;
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
     * Populates the content-entries of the collection.
     *
     * Respects the current language context by filtering sys_file_metadata records
     * based on their sys_language_uid. Records are prioritized by language specificity
     * (current language > default language > all languages) to ensure translated
     * metadata takes precedence over default language metadata.
     */
    public function loadContents()
    {
        $context = GeneralUtility::makeInstance(Context::class);
        $languageAspect = $context->getAspect('language');
        $languageIds = array_unique([-1, 0, $languageAspect->getContentId()]);

        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_category');
        $queryBuilder->getRestrictions()->removeAll();
        $statement = $queryBuilder->select('sys_file_metadata.file', 'sys_file_metadata.sys_language_uid')
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
                    $queryBuilder->createNamedParameter($this->getItemsCriteria(), Connection::PARAM_INT)
                ),
                $queryBuilder->expr()->eq(
                    'sys_category_record_mm.tablenames',
                    $queryBuilder->createNamedParameter('sys_file_metadata')
                ),
                $queryBuilder->expr()->in(
                    'sys_file_metadata.sys_language_uid',
                    $queryBuilder->createNamedParameter($languageIds, Connection::PARAM_INT_ARRAY)
                )
            )
            ->orderBy('sys_file_metadata.sys_language_uid', 'DESC')
            ->executeQuery();

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
        $addedFiles = [];
        while ($record = $statement->fetchAssociative()) {
            $fileId = (int)$record['file'];
            // Skip if file was already added (from a more specific language record)
            if (isset($addedFiles[$fileId])) {
                continue;
            }
            $addedFiles[$fileId] = true;
            $this->add($resourceFactory->getFileObject($fileId));
        }
    }
}
