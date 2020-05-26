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

namespace TYPO3\CMS\Core\Resource;

/**
 * The interface for a resource storage containing all constants
 */
interface ResourceStorageInterface
{
    /**
     * Capability for being browsable by (backend) users
     */
    const CAPABILITY_BROWSABLE = 1;
    /**
     * Capability for publicly accessible storages (= accessible from the web)
     */
    const CAPABILITY_PUBLIC = 2;
    /**
     * Capability for writable storages. This only signifies writability in
     * general - this might also be further limited by configuration.
     */
    const CAPABILITY_WRITABLE = 4;
    /**
     * Whether identifiers contain hierarchy information (folder structure).
     */
    const CAPABILITY_HIERARCHICAL_IDENTIFIERS = 8;
    /**
     * Name of the default processing folder
     */
    const DEFAULT_ProcessingFolder = '_processed_';
}
