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

namespace TYPO3\CMS\Install\FolderStructure;

use TYPO3\CMS\Core\Messaging\FlashMessage;

/**
 * Interface for structure nodes root, link, file, ...
 */
interface NodeInterface
{
    /**
     * Constructor gets structure and parent object defaulting to NULL
     *
     * @param array $structure Structure
     * @param NodeInterface $parent Parent
     */
    public function __construct(array $structure, NodeInterface $parent = null);

    /**
     * Get node name
     *
     * @return string Node name
     */
    public function getName();

    /**
     * Get absolute path of node
     *
     * @return string Absolute path
     */
    public function getAbsolutePath();

    /**
     * Get the status of the object tree, recursive for directory and root node
     *
     * @return FlashMessage[]
     */
    public function getStatus(): array;

    /**
     * Check if node is writable - can be created and permission can be fixed
     *
     * @return bool TRUE if node is writable
     */
    public function isWritable();

    /**
     * Fix structure
     *
     * If there is nothing to fix, returns an empty array
     *
     * @return FlashMessage[]
     */
    public function fix(): array;
}
