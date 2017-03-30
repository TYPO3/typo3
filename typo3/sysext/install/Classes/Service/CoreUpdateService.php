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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Service\OpcodeCacheService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Install\FolderStructure\DefaultFactory;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;
use TYPO3\CMS\Install\Status\ErrorStatus;
use TYPO3\CMS\Install\Status\NoticeStatus;
use TYPO3\CMS\Install\Status\OkStatus;
use TYPO3\CMS\Install\Status\StatusInterface;
use TYPO3\CMS\Install\Status\StatusUtility;
use TYPO3\CMS\Install\Status\WarningStatus;

/**
 * Core update service.
 * This service handles core updates, all the nasty details are encapsulated
 * here. The single public methods 'depend' on each other, for example a new
 * core has to be downloaded before it can be unpacked.
 *
 * Each method returns only TRUE of FALSE indicating if it was successful or
 * not. Detailed information can be fetched with getMessages() and will return
 * a list of status messages of the previous operation.
 */
class CoreUpdateService
{
    /**
     * @var \TYPO3\CMS\Install\Service\CoreVersionService
     */
    protected $coreVersionService;

    /**
     * @var StatusInterface[]
     */
    protected $messages = [];

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
        $this->setDownloadTargetPath(PATH_site . 'typo3temp/var/transient/');
        $this->symlinkToCoreFiles = $this->discoverCurrentCoreSymlink();
        $this->downloadBaseUri = $this->coreVersionService->getDownloadBaseUri();
    }

    /**
     * Check if this installation wants to enable the core updater
     *
     * @return bool
     */
    public function isCoreUpdateEnabled()
    {
        $coreUpdateDisabled = getenv('TYPO3_DISABLE_CORE_UPDATER') ?: (getenv('REDIRECT_TYPO3_DISABLE_CORE_UPDATER') ?: false);
        return !Bootstrap::usesComposerClassLoading() && !$coreUpdateDisabled;
    }

    /**
     * In future implementations we might implement some smarter logic here
     *
     * @return string
     */
    protected function discoverCurrentCoreSymlink()
    {
        return PATH_site . 'typo3_src';
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
     * @return StatusInterface[]
     */
    public function getMessages()
    {
        return $this->messages;
    }

    /**
     * Wrapper method for CoreVersionService
     *
     * @return bool TRUE on success
     */
    public function updateVersionMatrix()
    {
        $success = true;
        try {
            $this->coreVersionService->updateVersionMatrix();
        } catch (RemoteFetchException $e) {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('Version matrix could not be fetched from get.typo3.org');
            $message->setMessage(
                'Current version specification could not be fetched from http://get.typo3.org/json.'
                . ' This is probably a network issue, please fix it.'
            );
            $this->messages = [$message];
        }
        return $success;
    }

    /**
     * Check if an update is possible at all
     *
     * @param string $version The target version number
     * @return bool TRUE on success
     * @throws \TYPO3\CMS\Install\Status\Exception
     */
    public function checkPreConditions($version)
    {
        $success = true;
        $messages = [];

        /** @var StatusUtility $statusUtility */
        $statusUtility = GeneralUtility::makeInstance(StatusUtility::class);

        // Folder structure test: Update can be done only if folder structure returns no errors
        /** @var $folderStructureFacade \TYPO3\CMS\Install\FolderStructure\StructureFacade */
        $folderStructureFacade = GeneralUtility::makeInstance(DefaultFactory::class)->getStructure();
        $folderStructureErrors = $statusUtility->filterBySeverity($folderStructureFacade->getStatus(), 'error');
        $folderStructureWarnings = $statusUtility->filterBySeverity($folderStructureFacade->getStatus(), 'warning');
        if (!empty($folderStructureErrors) || !empty($folderStructureWarnings) || !is_link(PATH_site . 'typo3_src')) {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('Automatic TYPO3 CMS core update not possible: Folder structure has errors or warnings');
            $message->setMessage(
                'To perform an update, the folder structure of this TYPO3 CMS instance must'
                . ' stick to the conventions, or the update process could lead to unexpected'
                . ' results and may be hazardous to your system'
            );
            $messages[] = $message;
        }

        // No core update on windows
        if (TYPO3_OS === 'WIN') {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('Automatic TYPO3 CMS core update not possible: Update not supported on Windows OS');
            $messages[] = $message;
        }

        if ($success) {
            // Explicit write check to document root
            $file = PATH_site . StringUtility::getUniqueId('install-core-update-test-');
            $result = @touch($file);
            if (!$result) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Automatic TYPO3 CMS core update not possible: No write access to document root');
                $message->setMessage('Could not write a file in path "' . PATH_site . '"!');
                $messages[] = $message;
            } else {
                unlink($file);
            }

            if (!$this->checkCoreFilesAvailable($version)) {
                // Explicit write check to upper directory of current core location
                $coreLocation = @realpath($this->symlinkToCoreFiles . '/../');
                $file = $coreLocation . '/' . StringUtility::getUniqueId('install-core-update-test-');
                $result = @touch($file);
                if (!$result) {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('Automatic TYPO3 CMS core update not possible: No write access to TYPO3 CMS core location');
                    $message->setMessage(
                        'New TYPO3 CMS core should be installed in "' . $coreLocation . '", but this directory is not writable!'
                    );
                    $messages[] = $message;
                } else {
                    unlink($file);
                }
            }
        }

        if ($success && !$this->coreVersionService->isInstalledVersionAReleasedVersion()) {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('Automatic TYPO3 CMS core update not possible: You are running a development version of TYPO3');
            $message->setMessage(
                'Your current version is specified as ' . $this->coreVersionService->getInstalledVersion() . '.'
                . ' This is a development version and can not be updated automatically. If this is a "git"'
                . ' checkout, please update using git directly.'
            );
            $messages[] = $message;
        }

        $this->messages = $messages;
        return $success;
    }

    /**
     * Download the specified version
     *
     * @param string $version A version to download
     * @return bool TRUE on success
     */
    public function downloadVersion($version)
    {
        $messages = [];
        $success = true;

        if ($this->checkCoreFilesAvailable($version)) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setTitle('Skipped download of TYPO3 CMS core. A core source directory already exists in destination path. Using this instead.');
            $messages[] = $message;
        } else {
            $downloadUri = $this->downloadBaseUri . $version;
            $fileLocation = $this->getDownloadTarGzTargetPath($version);

            if (@file_exists($fileLocation)) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('TYPO3 CMS core download exists in download location: ' . PathUtility::stripPathSitePrefix($this->downloadTargetPath));
                $messages[] = $message;
            } else {
                $fileContent = GeneralUtility::getUrl($downloadUri);
                if (!$fileContent) {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('Download not successful');
                    $messages[] = $message;
                } else {
                    $fileStoreResult = file_put_contents($fileLocation, $fileContent);
                    if (!$fileStoreResult) {
                        $success = false;
                        /** @var $message StatusInterface */
                        $message = GeneralUtility::makeInstance(ErrorStatus::class);
                        $message->setTitle('Unable to store download content');
                        $messages[] = $message;
                    } else {
                        $message = GeneralUtility::makeInstance(OkStatus::class);
                        $message->setTitle('TYPO3 CMS core download finished');
                        $messages[] = $message;
                    }
                }
            }
        }
        $this->messages = $messages;
        return $success;
    }

    /**
     * Verify checksum of downloaded version
     *
     * @param string $version A downloaded version to check
     * @return bool TRUE on success
     */
    public function verifyFileChecksum($version)
    {
        $messages = [];
        $success = true;

        if ($this->checkCoreFilesAvailable($version)) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(WarningStatus::class);
            $message->setTitle('Verifying existing TYPO3 CMS core checksum is not possible');
            $messages[] = $message;
        } else {
            $fileLocation = $this->getDownloadTarGzTargetPath($version);
            $expectedChecksum = $this->coreVersionService->getTarGzSha1OfVersion($version);

            if (!file_exists($fileLocation)) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Downloaded TYPO3 CMS core not found');
                $messages[] = $message;
            } else {
                $actualChecksum = sha1_file($fileLocation);
                if ($actualChecksum !== $expectedChecksum) {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('New TYPO3 CMS core checksum mismatch');
                    $message->setMessage(
                        'The official TYPO3 CMS version system on https://get.typo3.org expects a sha1 checksum of '
                        . $expectedChecksum . ' from the content of the downloaded new TYPO3 CMS core version ' . $version . '.'
                        . ' The actual checksum is ' . $actualChecksum . '. The update is stopped. This may be a'
                        . ' failed download, an attack, or an issue with the typo3.org infrastructure.'
                    );
                    $messages[] = $message;
                } else {
                    $message = GeneralUtility::makeInstance(OkStatus::class);
                    $message->setTitle('Checksum verified');
                    $messages[] = $message;
                }
            }
        }
        $this->messages = $messages;
        return $success;
    }

    /**
     * Unpack a downloaded core
     *
     * @param string $version A version to unpack
     * @return bool TRUE on success
     */
    public function unpackVersion($version)
    {
        $messages = [];
        $success = true;

        if ($this->checkCoreFilesAvailable($version)) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setTitle('Unpacking TYPO3 CMS core files skipped');
            $messages[] = $message;
        } else {
            $fileLocation = $this->downloadTargetPath . $version . '.tar.gz';

            if (!@is_file($fileLocation)) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Downloaded TYPO3 CMS core not found');
                $messages[] = $message;
            } elseif (@file_exists($this->downloadTargetPath . 'typo3_src-' . $version)) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Unpacked TYPO3 CMS core exists in download location: ' . PathUtility::stripPathSitePrefix($this->downloadTargetPath));
                $messages[] = $message;
            } else {
                $unpackCommand = 'tar xf ' . escapeshellarg($fileLocation) . ' -C ' . escapeshellarg($this->downloadTargetPath) . ' 2>&1';
                exec($unpackCommand, $output, $errorCode);
                if ($errorCode) {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('Unpacking TYPO3 CMS core not successful');
                    $messages[] = $message;
                } else {
                    $removePackedFileResult = unlink($fileLocation);
                    if (!$removePackedFileResult) {
                        $success = false;
                        /** @var $message StatusInterface */
                        $message = GeneralUtility::makeInstance(ErrorStatus::class);
                        $message->setTitle('Removing packed TYPO3 CMS core not successful');
                        $messages[] = $message;
                    } else {
                        $message = GeneralUtility::makeInstance(OkStatus::class);
                        $message->setTitle('Unpacking TYPO3 CMS core successful');
                        $messages[] = $message;
                    }
                }
            }
        }
        $this->messages = $messages;
        return $success;
    }

    /**
     * Move an unpacked core to its final destination
     *
     * @param string $version A version to move
     * @return bool TRUE on success
     */
    public function moveVersion($version)
    {
        $messages = [];
        $success = true;

        if ($this->checkCoreFilesAvailable($version)) {
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(NoticeStatus::class);
            $message->setTitle('Moving TYPO3 CMS core files skipped');
            $messages[] = $message;
        } else {
            $downloadedCoreLocation = $this->downloadTargetPath . 'typo3_src-' . $version;
            $newCoreLocation = @realpath($this->symlinkToCoreFiles . '/../') . '/typo3_src-' . $version;

            if (!@is_dir($downloadedCoreLocation)) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Unpacked TYPO3 CMS core not found');
                $messages[] = $message;
            } else {
                $moveResult = rename($downloadedCoreLocation, $newCoreLocation);
                if (!$moveResult) {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('Moving TYPO3 CMS core to ' . $newCoreLocation . ' failed');
                    $messages[] = $message;
                } else {
                    $message = GeneralUtility::makeInstance(OkStatus::class);
                    $message->setTitle('Moved TYPO3 CMS core to final location');
                    $messages[] = $message;
                }
            }
        }

        $this->messages = $messages;
        return $success;
    }

    /**
     * Activate a core version
     *
     * @param string $version A version to activate
     * @return bool TRUE on success
     */
    public function activateVersion($version)
    {
        $newCoreLocation = @realpath($this->symlinkToCoreFiles . '/../') . '/typo3_src-' . $version;

        $messages = [];
        $success = true;

        if (!is_dir($newCoreLocation)) {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('New TYPO3 CMS core not found');
            $messages[] = $message;
        } elseif (!is_link($this->symlinkToCoreFiles)) {
            $success = false;
            /** @var $message StatusInterface */
            $message = GeneralUtility::makeInstance(ErrorStatus::class);
            $message->setTitle('TYPO3 CMS core source directory (typo3_src) is not a link');
            $messages[] = $message;
        } else {
            $isCurrentCoreSymlinkAbsolute = PathUtility::isAbsolutePath(readlink($this->symlinkToCoreFiles));
            $unlinkResult = unlink($this->symlinkToCoreFiles);
            if (!$unlinkResult) {
                $success = false;
                /** @var $message StatusInterface */
                $message = GeneralUtility::makeInstance(ErrorStatus::class);
                $message->setTitle('Removing old symlink failed');
                $messages[] = $message;
            } else {
                if (!$isCurrentCoreSymlinkAbsolute) {
                    $newCoreLocation = $this->getRelativePath($newCoreLocation);
                }
                $symlinkResult = symlink($newCoreLocation, $this->symlinkToCoreFiles);
                if ($symlinkResult) {
                    GeneralUtility::makeInstance(OpcodeCacheService::class)->clearAllActive();
                } else {
                    $success = false;
                    /** @var $message StatusInterface */
                    $message = GeneralUtility::makeInstance(ErrorStatus::class);
                    $message->setTitle('Linking new TYPO3 CMS core failed');
                    $messages[] = $message;
                }
            }
        }

        $this->messages = $messages;
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
        $sourcePath = explode(DIRECTORY_SEPARATOR, rtrim(PATH_site, DIRECTORY_SEPARATOR));
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
