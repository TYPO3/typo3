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

namespace TYPO3\CMS\Core\Collection;

/**
 * Interface for collection class being persistable
 *
 * Collections are containers-classes handling the storage
 * of data values (f.e. strings, records, relations) in a
 * common and generic way, while the class manages the storage
 * in an appropriate way itself
 */
interface PersistableCollectionInterface
{
    /**
     * Get the identifier of the collection
     *
     * For database stored collections, this will be an integer,
     * session stored, registry stored or other collections might
     * use a string as well
     *
     * @return int|string
     */
    public function getIdentifier();

    /**
     * Sets the identifier of the collection
     *
     * @param int|string $id
     */
    public function setIdentifier($id);

    /**
     * Loads the collections with the given id from persistence
     *
     * For memory reasons, per default only f.e. title, database-table,
     * identifier (what ever static data is defined) is loaded.
     * Entries can be load on first access.
     *
     * @param int|string $id
     * @param bool $fillItems Populates the entries directly on load, might be bad for memory on large collections
     * @return \TYPO3\CMS\Core\Collection\CollectionInterface
     */
    public static function load($id, $fillItems = false);

    /**
     * Persists current collection state to underlying storage
     */
    public function persist();

    /**
     * Populates the content-entries of the storage
     *
     * Queries the underlying storage for entries of the collection
     * and adds them to the collection data.
     *
     * If the content entries of the storage had not been loaded on creation
     * ($fillItems = false) this function is to be used for loading the contents
     * afterwards.
     */
    public function loadContents();
}
