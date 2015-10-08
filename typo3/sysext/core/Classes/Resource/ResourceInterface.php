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
 * ResourceInterface
 *
 */
interface ResourceInterface
{
    /**
     * Returns the identifier of this file
     *
     * @return string
     */
    public function getIdentifier();

    /**
     * Returns the name of this file
     *
     * @return string
     */
    public function getName();

    /**
     * Get the storage this file is located in
     *
     * @return ResourceStorage
     */
    public function getStorage();

    /**
     * Get hashed identifier
     *
     * @return string
     */
    public function getHashedIdentifier();

    /**
     * @return FolderInterface
     */
    public function getParentFolder();
}
