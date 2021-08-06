<?php
namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageQueue;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\CoreVersion\CoreRelease;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;

/**
 * Core update service.
 * This service handles core updates, all the nasty details are encapsulated
 * here. The single public methods 'depend' on each other, for example a new
 * core has to be downloaded before it can be unpacked.
 *
 * Each method returns only TRUE of FALSE indicating if it was successful or
 * not. Detailed information can be fetched with getMessages() and will return
 * a list of status messages of the previous operation.
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CoreUpdateService
{
    /**
     * @var \TYPO3\CMS\Install\Service\CoreVersionService
     */
    protected $coreVersionService;

    /**
     * @var FlashMessageQueue
     */
    protected $messages;

    /**
     * Absolute path to download location
     *
     * @var string
     */
    protected $downloadTargetPath;

    /**
     * Absolute path to the symlink pointing to the currently used TYPO3 core files
     *
     * @var string
     */
    protected $symlinkToCoreFiles;

    /**
     * Base URI for TYPO3 downloads
     *
     * @var string
     */
    protected $downloadBaseUri;

    /**
     * @param CoreVersionService $coreVersionService
     */
    public function __construct(CoreVersionService $coreVersionService = null)
    {
        $this->coreVersionService = $coreVersionService ?: GeneralUtility::makeInstance(CoreVersionService::class);
        $this->setDownloadTargetPath(Environment::getVarPath() . '/transient/');
        $this->symlinkToCoreFiles = $this->discoverCurrentCoreSymlink();
        $this->downloadBaseUri = 'https://get.typo3.org';
        $this->messages = new FlashMessageQueue('install');
    }

    /**
     * Check if this installation wants to enable the core updater
     *
     * @return bool
     */
    public function isCoreUpdateEnabled()
    {
        $coreUpdateDisabled = getenv('TYPO3_DISABLE_CORE_UPDATER') ?: (getenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER') ?: false);
        return !Environment::isComposerMode() && !$coreUpdateDisabled;
    }

    /**
     * In future implementations we might implement some smarter logic here
     *
     * @return string
     */
    protected function discoverCurrentCoreSymlink()
    {
        return Environment::getPublicPath() . '/typo3_src';
    }

    /**
     * Create download location in case the folder does not exist
     * @todo move this to folder structure
     *
     * @param string $downloadTargetPath
     */
    protected function setDownloadTargetPath($downloadTargetPath)
    {
        if (!is_dir($downloadTargetPath)) {
            GeneralUtility::mkdir_deep($downloadTargetPath);
        }
        $this->downloadTargetPath = $downloadTargetPath;
    }

    /**
     * Get messages of previous method call
     *
     * @return FlashMessageQueue
     */
    public function getMessages(): FlashMessageQueue
    {
        return $this->messages;
    }

    /**
     * Wrapper method for CoreVersionService
     *
     * @deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0 - use REST api directly (see https://get.typo3.org/v1/api/doc)
     * @return bool TRUE on success
     */
    public function updateVersionMatrix()
    {
        trigger_error(
            'The method updateVersionMatrix() is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0, use the REST api directly (see https://get.typo3.org/v1/api/doc).',
            E_USER_DEPRECATED
        );
        $success = true;
        try {
            $this->coreVersionService->getYoungestPatchRelease();
        } catch (RemoteFetchException $e) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                'Current version specification could not be fetched from https://get.typo3.org.'
                    . ' This is probably a network issue, please fix it.',
                'Version information could not be fetched from get.typo3.org',
                FlashMessage::ERROR
            ));
        }
        return $success;
    }

    /**
     * Check if an update is possible at all
     *
     * @param CoreRelease $coreRelease The target core release
     * @return bool TRUE on success
     */
    public function checkPreConditions(CoreRelease $coreRelease)
    {
        $success = true;

        // Folder structure test: Update can be done only if folder structure returns no errors
        $folderStructureFacade = GeneralUtility::makeInstance(DefaultFactory::class)->getStructure();
        $folderStructureMessageQueue = $folderStructureFacade->getStatus();
        $folderStructureErrors = $folderStructureMessageQueue->getAllMessages(FlashMessage::ERROR);
        $folderStructureWarnings = $folderStructureMessageQueue->getAllMessages(FlashMessage::WARNING);
        if (!empty($folderStructureErrors) || !empty($folderStructureWarnings) || !is_link(Environment::getPublicPath() . '/typo3_src')) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                'To perform an update, the folder structure of this TYPO3 CMS instance must'
                    . ' stick to the conventions, or the update process could lead to unexpected'
                    . ' results and may be hazardous to your system',
                'Automatic TYPO3 CMS core update not possible: Folder structure has errors or warnings',
                FlashMessage::ERROR
            ));
        }

        // No core update on windows
        if (Environment::isWindows()) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                '',
                'Automatic TYPO3 CMS core update not possible: Update not supported on Windows OS',
                FlashMessage::ERROR
            ));
        }

        if ($success) {
            // Explicit write check to document root
            $file = Environment::getPublicPath() . '/' . StringUtility::getUniqueId('install-core-update-test-');
            $result = @touch($file);
            if (!$result) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    'Could not write a file in path "' . Environment::getPublicPath() . '/"!',
                    'Automatic TYPO3 CMS core update not possible: No write access to document root',
                    FlashMessage::ERROR
                ));
            } else {
                // Check symlink creation
                $link = Environment::getPublicPath() . '/' . StringUtility::getUniqueId('install-core-update-test-');
                @symlink($file, $link);
                if (!is_link($link)) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        'Could not create a symbolic link in path "' . Environment::getPublicPath() . '/"!',
                        'Automatic TYPO3 CMS core update not possible: No symlink creation possible',
                        FlashMessage::ERROR
                    ));
                } else {
                    unlink($link);
                }
                unlink($file);
            }

            if (!$this->checkCoreFilesAvailable($coreRelease->getVersion())) {
                // Explicit write check to upper directory of current core location
                $coreLocation = @realpath($this->symlinkToCoreFiles . '/../');
                $file = $coreLocation . '/' . StringUtility::getUniqueId('install-core-update-test-');
                $result = @touch($file);
                if (!$result) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        'New TYPO3 CMS core should be installed in "' . $coreLocation . '", but this directory is not writable!',
                        'Automatic TYPO3 CMS core update not possible: No write access to TYPO3 CMS core location',
                        FlashMessage::ERROR
                    ));
                } else {
                    unlink($file);
                }
            }
        }

        if ($success && !$this->coreVersionService->isInstalledVersionAReleasedVersion()) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                'Your current version is specified as ' . $this->coreVersionService->getInstalledVersion() . '.'
                    . ' This is a development version and can not be updated automatically. If this is a "git"'
                    . ' checkout, please update using git directly.',
                'Automatic TYPO3 CMS core update not possible: You are running a development version of TYPO3',
                FlashMessage::ERROR
            ));
        }

        return $success;
    }

    /**
     * Download the specified version
     *
     * @param CoreRelease $coreRelease A core release to download
     * @return bool TRUE on success
     */
    public function downloadVersion(CoreRelease $coreRelease)
    {
        $version = $coreRelease->getVersion();
        $success = true;
        if ($this->checkCoreFilesAvailable($version)) {
            $this->messages->enqueue(new FlashMessage(
                '',
                'Skipped download of TYPO3 CMS core. A core source directory already exists in destination path. Using this instead.',
                FlashMessage::NOTICE
            ));
        } else {
            $downloadUri = $this->downloadBaseUri . '/' . $version;
            $fileLocation = $this->getDownloadTarGzTargetPath($version);

            if (@file_exists($fileLocation)) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'TYPO3 CMS core download exists in download location: ' . PathUtility::stripPathSitePrefix($this->downloadTargetPath),
                    FlashMessage::ERROR
                ));
            } else {
                $fileContent = GeneralUtility::getUrl($downloadUri);
                if (!$fileContent) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        'Failed to download ' . $downloadUri,
                        'Download not successful',
                        FlashMessage::ERROR
                    ));
                } else {
                    $fileStoreResult = file_put_contents($fileLocation, $fileContent);
                    if (!$fileStoreResult) {
                        $success = false;
                        $this->messages->enqueue(new FlashMessage(
                            '',
                            'Unable to store download content',
                            FlashMessage::ERROR
                        ));
                    } else {
                        $this->messages->enqueue(new FlashMessage(
                            '',
                            'TYPO3 CMS core download finished'
                        ));
                    }
                }
            }
        }
        return $success;
    }

    /**
     * Verify checksum of downloaded version
     *
     * @param CoreRelease $coreRelease A downloaded core release to check
     * @return bool TRUE on success
     */
    public function verifyFileChecksum(CoreRelease $coreRelease)
    {
        $version = $coreRelease->getVersion();
        $success = true;
        if ($this->checkCoreFilesAvailable($version)) {
            $this->messages->enqueue(new FlashMessage(
                '',
                'Verifying existing TYPO3 CMS core checksum is not possible',
                FlashMessage::WARNING
            ));
        } else {
            $fileLocation = $this->getDownloadTarGzTargetPath($version);
            $expectedChecksum = $coreRelease->getChecksum();
            if (!file_exists($fileLocation)) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'Downloaded TYPO3 CMS core not found',
                    FlashMessage::ERROR
                ));
            } else {
                $actualChecksum = sha1_file($fileLocation);
                if ($actualChecksum !== $expectedChecksum) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        'The official TYPO3 CMS version system on https://get.typo3.org expects a sha1 checksum of '
                            . $expectedChecksum . ' from the content of the downloaded new TYPO3 CMS core version ' . $version . '.'
                            . ' The actual checksum is ' . $actualChecksum . '. The update is stopped. This may be a'
                            . ' failed download, an attack, or an issue with the typo3.org infrastructure.',
                        'New TYPO3 CMS core checksum mismatch',
                        FlashMessage::ERROR
                    ));
                } else {
                    $this->messages->enqueue(new FlashMessage(
                        '',
                        'Checksum verified'
                    ));
                }
            }
        }
        return $success;
    }

    /**
     * Unpack a downloaded core
     *
     * @param CoreRelease $coreRelease A core release to unpack
     * @return bool TRUE on success
     */
    public function unpackVersion(CoreRelease $coreRelease)
    {
        $version = $coreRelease->getVersion();
        $success = true;
        if ($this->checkCoreFilesAvailable($version)) {
            $this->messages->enqueue(new FlashMessage(
                '',
                'Unpacking TYPO3 CMS core files skipped',
                FlashMessage::NOTICE
            ));
        } else {
            $fileLocation = $this->downloadTargetPath . $version . '.tar.gz';
            if (!@is_file($fileLocation)) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'Downloaded TYPO3 CMS core not found',
                    FlashMessage::ERROR
                ));
            } elseif (@file_exists($this->downloadTargetPath . 'typo3_src-' . $version)) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'Unpacked TYPO3 CMS core exists in download location: ' . PathUtility::stripPathSitePrefix($this->downloadTargetPath),
                    FlashMessage::ERROR
                ));
            } else {
                $unpackCommand = 'tar xf ' . escapeshellarg($fileLocation) . ' -C ' . escapeshellarg($this->downloadTargetPath) . ' 2>&1';
                exec($unpackCommand, $output, $errorCode);
                if ($errorCode) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        '',
                        'Unpacking TYPO3 CMS core not successful',
                        FlashMessage::ERROR
                    ));
                } else {
                    $removePackedFileResult = unlink($fileLocation);
                    if (!$removePackedFileResult) {
                        $success = false;
                        $this->messages->enqueue(new FlashMessage(
                            '',
                            'Removing packed TYPO3 CMS core not successful',
                            FlashMessage::ERROR
                        ));
                    } else {
                        $this->messages->enqueue(new FlashMessage(
                            '',
                            'Unpacking TYPO3 CMS core successful'
                        ));
                    }
                }
            }
        }
        return $success;
    }

    /**
     * Move an unpacked core to its final destination
     *
     * @param CoreRelease $coreRelease A core release to move
     * @return bool TRUE on success
     */
    public function moveVersion(CoreRelease $coreRelease)
    {
        $version = $coreRelease->getVersion();
        $success = true;
        if ($this->checkCoreFilesAvailable($version)) {
            $this->messages->enqueue(new FlashMessage(
                '',
                'Moving TYPO3 CMS core files skipped',
                FlashMessage::NOTICE
            ));
        } else {
            $downloadedCoreLocation = $this->downloadTargetPath . 'typo3_src-' . $version;
            $newCoreLocation = @realpath($this->symlinkToCoreFiles . '/../') . '/typo3_src-' . $version;

            if (!@is_dir($downloadedCoreLocation)) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'Unpacked TYPO3 CMS core not found',
                    FlashMessage::ERROR
                ));
            } else {
                $moveResult = rename($downloadedCoreLocation, $newCoreLocation);
                if (!$moveResult) {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        '',
                        'Moving TYPO3 CMS core to ' . $newCoreLocation . ' failed',
                        FlashMessage::ERROR
                    ));
                } else {
                    $this->messages->enqueue(new FlashMessage(
                        '',
                        'Moved TYPO3 CMS core to final location'
                    ));
                }
            }
        }
        return $success;
    }

    /**
     * Activate a core version
     *
     * @param CoreRelease $coreRelease A core release to activate
     * @return bool TRUE on success
     */
    public function activateVersion(CoreRelease $coreRelease)
    {
        $newCoreLocation = @realpath($this->symlinkToCoreFiles . '/../') . '/typo3_src-' . $coreRelease->getVersion();
        $success = true;
        if (!is_dir($newCoreLocation)) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                '',
                'New TYPO3 CMS core not found',
                FlashMessage::ERROR
            ));
        } elseif (!is_link($this->symlinkToCoreFiles)) {
            $success = false;
            $this->messages->enqueue(new FlashMessage(
                '',
                'TYPO3 CMS core source directory (typo3_src) is not a link',
                FlashMessage::ERROR
            ));
        } else {
            $isCurrentCoreSymlinkAbsolute = PathUtility::isAbsolutePath(readlink($this->symlinkToCoreFiles));
            $unlinkResult = unlink($this->symlinkToCoreFiles);
            if (!$unlinkResult) {
                $success = false;
                $this->messages->enqueue(new FlashMessage(
                    '',
                    'Removing old symlink failed',
                    FlashMessage::ERROR
                ));
            } else {
                if (!$isCurrentCoreSymlinkAbsolute) {
                    $newCoreLocation = $this->getRelativePath($newCoreLocation);
                }
                $symlinkResult = symlink($newCoreLocation, $this->symlinkToCoreFiles);
                if ($symlinkResult) {
                    GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
                } else {
                    $success = false;
                    $this->messages->enqueue(new FlashMessage(
                        '',
                        'Linking new TYPO3 CMS core failed',
                        FlashMessage::ERROR
                    ));
                }
            }
        }
        return $success;
    }

    /**
     * Absolute path of downloaded .tar.gz
     *
     * @param string $version A version number
     * @return string
     */
    protected function getDownloadTarGzTargetPath($version)
    {
        return $this->downloadTargetPath . $version . '.tar.gz';
    }

    /**
     * Get relative path to TYPO3 source directory from webroot
     *
     * @param string $absolutePath to TYPO3 source directory
     * @return string relative path to TYPO3 source directory
     */
    protected function getRelativePath($absolutePath)
    {
        $sourcePath = explode(DIRECTORY_SEPARATOR, Environment::getPublicPath());
        $targetPath = explode(DIRECTORY_SEPARATOR, rtrim($absolutePath, DIRECTORY_SEPARATOR));
        while (count($sourcePath) && count($targetPath) && $sourcePath[0] === $targetPath[0]) {
            array_shift($sourcePath);
            array_shift($targetPath);
        }
        return str_pad('', count($sourcePath) * 3, '..' . DIRECTORY_SEPARATOR) . implode(DIRECTORY_SEPARATOR, $targetPath);
    }

    /**
     * Check if there is are already core files available
     * at the download destination.
     *
     * @param string $version A version number
     * @return bool true when core files are available
     */
    protected function checkCoreFilesAvailable($version)
    {
        $newCoreLocation = @realpath($this->symlinkToCoreFiles . '/../') . '/typo3_src-' . $version;
        return @is_dir($newCoreLocation);
    }
}
