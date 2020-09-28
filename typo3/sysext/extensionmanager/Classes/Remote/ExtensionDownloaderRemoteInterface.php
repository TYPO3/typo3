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

use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;

/**
 * API for downloading packages from a remote server and validating the downloaded results.
 *
 * Please note that this API might be modified in the future.
 */
interface ExtensionDownloaderRemoteInterface
{
    /**
     * Returns the remote identifier
     *
     * @return string
     */
    public function getIdentifier(): string;

    /**
     * Downloads a remote package / extension on the TYPO3 installation without installing it.
     *
     * @param string $extensionKey a lower-cased extension key
     * @param string $version the version to be fetched e.g. 1.5.2
     * @param FileHandlingUtility $fileHandler the file handler which deals with unpacking the files
     * @param string|null $verificationHash if given the file is verified (depending on the remote, the verification hash can be anything)
     * @param string $pathType either "Local", "System" or "Global"
     * @throws DownloadFailedException when a remote file could not be loaded.
     * @throws VerificationFailedException when the remote file could not be unpacked or validated.
     */
    public function downloadExtension(string $extensionKey, string $version, FileHandlingUtility $fileHandler, string $verificationHash = null, string $pathType = 'Local'): void;
}
