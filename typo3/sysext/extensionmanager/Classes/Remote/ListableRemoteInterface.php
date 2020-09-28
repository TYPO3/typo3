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

namespace TYPO3\CMS\Extensionmanager\Remote;

/**
 * API for downloading a list of available packages from a server.
 */
interface ListableRemoteInterface
{
    /**
     * Fetches a list of available packages / extensions from a remote source.
     *
     * @param bool $force
     */
    public function getAvailablePackages(bool $force = false): void;

    /**
     * Checks whether the remote is outdated and should fetch latest updates.
     *
     * @return bool
     */
    public function needsUpdate(): bool;

    /**
     * Get the time when the remote was updated the last time.
     *
     * @return \DateTimeInterface
     */
    public function getLastUpdate(): \DateTimeInterface;
}
