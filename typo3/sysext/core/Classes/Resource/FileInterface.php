<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Resource;

use TYPO3\CMS\Core\Resource\Enum\DuplicationBehavior;

/**
 * Interface for a file object. This can be any kind of file object,
 * e.g. a processed file (which is not a FAL file), or a file reference object,
 * which is a decorator around a "File" object, but of course without any additional
 * file on the file system.
 */
interface FileInterface extends ResourceInterface
{
    /*******************************
     * VARIOUS FILE PROPERTY GETTERS
     *******************************/
    /**
     * Returns true if the given key exists for this file.
     *
     * @param non-empty-string $key
     */
    public function hasProperty(string $key): bool;

    /**
     * Get the value of the $key property.
     *
     * @param non-empty-string $key
     */
    public function getProperty(string $key): mixed;

    /**
     * MUST return the size of the file as unsigned int i.e. 0-max.
     *
     * In case of errors, e.g. when the file is deleted or not readable,
     * this method MAY either throw an Exception or return 0.
     *
     * @return int<0, max>
     */
    public function getSize(): int;

    /**
     * Returns the Sha1 of this file
     *
     * @return non-empty-string
     */
    public function getSha1(): string;

    /**
     * Returns the basename (the name without extension) of this file.
     */
    public function getNameWithoutExtension(): string;

    /**
     * Get the file extension
     */
    public function getExtension(): string;

    /**
     * Get the MIME type of this file
     *
     * @return non-empty-string mime type
     */
    public function getMimeType(): string;

    /**
     * Returns the modification time of the file as Unix timestamp
     */
    public function getModificationTime(): int;

    /**
     * Returns the creation time of the file as Unix timestamp
     */
    public function getCreationTime(): int;

    /******************
     * CONTENTS RELATED
     ******************/
    /**
     * Get the contents of this file
     */
    public function getContents(): string;

    /**
     * Replace the current file contents with the given string.
     *
     * @todo: Consider to remove this function from the interface, as its
     *        implementation in FileInUse could cause unforseen side-effects by setting
     *        contents on the original file instead of just on the Usage of the file.
     * @todo: At the same time, it could be considered whether to make the whole
     *        interface a read-only FileInterface, so that all file management and
     *        modification functions are removed...
     * @return $this
     */
    public function setContents(string $contents): self;

    /****************************************
     * STORAGE AND MANAGEMENT RELATED METHODS
     ****************************************/
    /**
     * Deletes this file from its storage. This also means that this object becomes useless.
     */
    public function delete(): bool;

    /**
     * Renames this file.
     *
     * @param non-empty-string $newName The new file name
     * @param DuplicationBehavior $conflictMode
     */
    public function rename(string $newName, DuplicationBehavior $conflictMode = DuplicationBehavior::RENAME): FileInterface;

    /*****************
     * SPECIAL METHODS
     *****************/
    /**
     * Returns a publicly accessible URL for this file
     *
     * WARNING: Access to the file may be restricted by further means, e.g.
     * some web-based authentication. You have to take care of this yourself.
     *
     * @return non-empty-string|null NULL if file is missing or deleted, the generated url otherwise
     */
    public function getPublicUrl(): ?string;

    /**
     * Returns TRUE if this file is indexed
     */
    public function isIndexed(): bool;

    /**
     * Returns a path to a local version of this file to process it locally (e.g. with some system tool).
     * If the file is normally located on a remote storages, this creates a local copy.
     * If the file is already on the local system, this only makes a new copy if $writable is set to TRUE.
     *
     * @param bool $writable Set this to FALSE if you only want to do read operations on the file.
     * @return non-empty-string
     */
    public function getForLocalProcessing(bool $writable = true): string;

    /**
     * Returns an array representation of the file.
     * (This is used by the generic listing module vidi when displaying file records.)
     *
     * @return array<string, mixed> Array of main data of the file. Don't rely on all data to be present here, it's just a selection of the most relevant information.
     */
    public function toArray(): array;
}
