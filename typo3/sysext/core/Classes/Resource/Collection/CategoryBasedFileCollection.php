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

/**
 * A collection containing a set files belonging to certain categories.
 * This collection is persisted to the database with the accordant category identifiers.
 */
class CategoryBasedFileCollection extends \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection
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
     *
     * @return void
     */
    public function loadContents()
    {
        $resource = $this->getDatabaseConnection()->exec_SELECT_mm_query(
            'sys_file_metadata.file',
            'sys_category',
            'sys_category_record_mm',
            'sys_file_metadata',
            'AND sys_category.uid=' . (int)$this->getItemsCriteria() .
            ' AND sys_category_record_mm.tablenames = \'sys_file_metadata\''
        );

        $resourceFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
        if ($resource) {
            while (($record = $this->getDatabaseConnection()->sql_fetch_assoc($resource)) !== false) {
                $this->add($resourceFactory->getFileObject((int)$record['file']));
            }
            $this->getDatabaseConnection()->sql_free_result($resource);
        }
    }

    /**
     * Gets the database object.
     *
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
