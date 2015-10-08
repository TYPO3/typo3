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
 * A collection containing a static set of files. This collection is persisted
 * to the database with references to all files it contains.
 */
class StaticFileCollection extends \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection
{
    /**
     * @var string
     */
    protected static $type = 'static';

    /**
     * @var string
     */
    protected static $itemsCriteriaField = 'items';

    /**
     * @var string
     */
    protected $itemTableName = 'sys_file_reference';

    /**
     * Populates the content-entries of the storage
     *
     * Queries the underlying storage for entries of the collection
     * and adds them to the collection data.
     *
     * If the content entries of the storage had not been loaded on creation
     * ($fillItems = false) this function is to be used for loading the contents
     * afterwards.
     *
     * @return void
     */
    public function loadContents()
    {
        /** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
        $fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\FileRepository::class);
        $fileReferences = $fileRepository->findByRelation('sys_file_collection', 'files', $this->getIdentifier());
        foreach ($fileReferences as $file) {
            $this->add($file);
        }
    }
}
