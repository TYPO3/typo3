<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Resource\Search\QueryRestrictions;

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

use TYPO3\CMS\Core\Database\Query\Restriction\AbstractRestrictionContainer;
use TYPO3\CMS\Core\Database\Query\Restriction\QueryRestrictionInterface;
use TYPO3\CMS\Core\Resource\Folder;

/**
 * Limits result to storage given by the folder
 * and also restricts result to the given folder, respecting whether the storage
 * has hierarchical identifiers or not.
 */
class FolderRestriction extends AbstractRestrictionContainer
{
    /**
     * @var Folder
     */
    private $folder;

    /**
     * @var bool
     */
    private $recursive;

    public function __construct(Folder $folder, bool $recursive)
    {
        $this->folder = $folder;
        $this->recursive = $recursive;
        $this->populateRestrictions();
    }

    private function populateRestrictions(): void
    {
        $storage = $this->folder->getStorage();
        $this->add(new StorageRestriction($storage));
        if (!$this->recursive) {
            $this->add($this->createFolderRestriction());
            return;
        }
        if ($this->folder->getIdentifier() === $storage->getRootLevelFolder(false)->getIdentifier()) {
            return;
        }
        if ($storage->hasHierarchicalIdentifiers()) {
            $this->add($this->createHierarchicalFolderRestriction());
        } else {
            $this->add($this->createFolderRestriction());
        }
    }

    private function createHierarchicalFolderRestriction(): QueryRestrictionInterface
    {
        return $this->recursive ? new FolderIdentifierRestriction($this->folder->getIdentifier()) : new FolderHashesRestriction([$this->folder->getHashedIdentifier()]);
    }

    private function createFolderRestriction(): QueryRestrictionInterface
    {
        $hashedFolderIdentifiers[] = $this->folder->getHashedIdentifier();
        if ($this->recursive) {
            foreach ($this->folder->getSubfolders(0, 0, Folder::FILTER_MODE_NO_FILTERS, true) as $subFolder) {
                $hashedFolderIdentifiers[] = $subFolder->getHashedIdentifier();
            }
        }

        return new FolderHashesRestriction($hashedFolderIdentifiers);
    }
}
