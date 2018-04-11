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

/**
 * File Interface
 */
interface FileInterface extends ResourceInterface
{
    /*******************************
     * VARIOUS FILE PROPERTY GETTERS
     *******************************/
    /**
     * Returns true if the given key exists for this file.
     *
     * @param string $key
     * @return bool
     */
    public function hasProperty($key);

    /**
     * Get the value of the $key property.
     *
     * @param string $key
     * @return string
     */
    public function getProperty($key);

    /**
     * Returns the size of this file
     *
     * @return int
     */
    public function getSize();

    /**
     * Returns the Sha1 of this file
     *
     * @return string
     */
    public function getSha1();

    /**
     * Returns the basename (the name without extension) of this file.
     *
     * @return string
     */
    public function getNameWithoutExtension();

    /**
     * Get the file extension
     *
     * @return string The file extension
     */
    public function getExtension();

    /**
     * Get the MIME type of this file
     *
     * @return string mime type
     */
    public function getMimeType();

    /**
     * Returns the modification time of the file as Unix timestamp
     *
     * @return int
     */
    public function getModificationTime();

    /**
     * Returns the creation time of the file as Unix timestamp
     *
     * @return int
     */
    public function getCreationTime();

    /******************
     * CONTENTS RELATED
     ******************/
    /**
     * Get the contents of this file
     *
     * @return string File contents
     */
    public function getContents();

    /**
     * Replace the current file contents with the given string.
     *
     * @TODO : Consider to remove this function from the interface, as its
     * @TODO : At the same time, it could be considered whether to make the whole
     * @param string $contents The contents to write to the file.
     * @return File The file object (allows chaining).
     */
    public function setContents($contents);

    /****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     ****************************************/
    /**
     * Deletes this file from its storage. This also means that this object becomes useless.
     *
     * @return bool TRUE if deletion succeeded
     */
    public function delete();

    /**
     * Renames this file.
     *
     * @param string $newName The new file name
     * @param string $conflictMode
     * @return File
     */
    public function rename($newName, $conflictMode = DuplicationBehavior::RENAME);

    /*****************
     * SPECIAL METHODS
     *****************/
    /**
     * Returns a publicly accessible URL for this file
     *
     * WARNING: Access to the file may be restricted by further means, e.g.
     * some web-based authentication. You have to take care of this yourself.
     *
     * @param bool $relativeToCurrentScript Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
     * @return string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl($relativeToCurrentScript = false);

    /**
     * Returns TRUE if this file is indexed
     *
     * @return bool
     */
    public function isIndexed();

    /**
     * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
     * If the file is normally located on a remote storages, this creates a local copy.
     * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
     *
     * @param bool $writable Set this to FALSE if you only want to do read operations on the file.
     * @return string
     */
    public function getForLocalProcessing($writable = true);

    /**
     * Returns an array representation of the file.
     * (This is used by the generic listing module vidi when displaying file records.)
     *
     * @return array Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
     */
    public function toArray();
}
