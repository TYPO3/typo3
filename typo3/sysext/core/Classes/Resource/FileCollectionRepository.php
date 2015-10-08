<?php
namespace TYPO3\CMS\Core\Resource;

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

use TYPO3\CMS\Core\Resource\Exception\ResourceDoesNotExistException;

/**
 * Repository for accessing the collections stored in the database
 */
class FileCollectionRepository extends \TYPO3\CMS\Core\Collection\RecordCollectionRepository
{
    /**
     * @var string
     */
    protected $table = 'sys_file_collection';

    /**
     * @var string
     */
    protected $typeField = 'type';

    /**
     * Finds a record collection by uid.
     *
     * @param int $uid The uid to be looked up
     * @return NULL|Collection\AbstractFileCollection
     * @throws ResourceDoesNotExistException
     */
    public function findByUid($uid)
    {
        $object = parent::findByUid($uid);
        if ($object === null) {
            throw new ResourceDoesNotExistException('Could not find row with uid "' . $uid . '" in table "' . $this->table . '"', 1314354066);
        }
        return $object;
    }

    /**
     * Creates a record collection domain object.
     *
     * @param array $record Database record to be reconsituted
     *
     * @return Collection\AbstractFileCollection
     */
    protected function createDomainObject(array $record)
    {
        return $this->getFileFactory()->createCollectionObject($record);
    }

    /**
     * Gets the file factory.
     *
     * @return ResourceFactory
     */
    protected function getFileFactory()
    {
        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
    }
}
