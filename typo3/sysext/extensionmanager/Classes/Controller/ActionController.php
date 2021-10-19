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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Http\ForwardResponse;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Controller for handling extension related actions like
 * installing, removing, downloading of data or files
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ActionController extends AbstractController
{
    protected InstallUtility $installUtility;
    protected ExtensionManagementService $managementService;

    public function __construct(
        InstallUtility $installUtility,
        ExtensionManagementService $managementService
    ) {
        $this->installUtility = $installUtility;
        $this->managementService = $managementService;
    }

    /**
     * Toggle extension installation state action
     *
     * @param string $extensionKey
     */
    protected function toggleExtensionInstallationStateAction($extensionKey)
    {
        try {
            if (Environment::isComposerMode()) {
                throw new ExtensionManagerException(
                    'The system is set to composer mode. You are not allowed to activate or deactivate any extension.',
                    1629922856
                );
            }
            $installedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
            if (in_array($extensionKey, $installedExtensions)) {
                // uninstall
                $this->installUtility->uninstall($extensionKey);
            } else {
                // install
                $extension = Extension::createFromExtensionArray(
                    $this->installUtility->enrichExtensionWithDetails($extensionKey, false)
                );
                if ($this->managementService->installExtension($extension) === false) {
                    $this->redirect('unresolvedDependencies', 'List', null, ['extensionKey' => $extensionKey]);
                }
            }
        } catch (ExtensionManagerException|PackageStatesFileNotWritableException $e) {
            $this->addFlashMessage($e->getMessage(), '', FlashMessage::ERROR);
        }
        $this->redirect('index', 'List', null, [
            self::TRIGGER_RefreshModuleMenu => true,
            self::TRIGGER_RefreshTopbar => true,
        ]);
    }

    /**
     * Install an extension and omit dependency checking
     *
     * @param string $extensionKey
     */
    public function installExtensionWithoutSystemDependencyCheckAction($extensionKey): ResponseInterface
    {
        $this->managementService->setSkipDependencyCheck(true);
        return (new ForwardResponse('toggleExtensionInstallationState'))->withArguments(['extensionKey' => $extensionKey]);
    }

    /**
     * Remove an extension (if it is still installed, uninstall it first)
     *
     * @param string $extension
     * @return ResponseInterface
     */
    protected function removeExtensionAction($extension): ResponseInterface
    {
        try {
            if (Environment::isComposerMode()) {
                throw new ExtensionManagerException(
                    'The system is set to composer mode. You are not allowed to remove any extension.',
                    1590314046
                );
            }

            $this->installUtility->removeExtension($extension);
            $this->addFlashMessage(
                LocalizationUtility::translate(
                    'extensionList.remove.message',
                    'extensionmanager',
                    [
                        'extension' => $extension,
                    ]
                ) ?? ''
            );
        } catch (ExtensionManagerException|Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', FlashMessage::ERROR);
        }

        return $this->htmlResponse('');
    }

    /**
     * Download an extension as a zip file
     *
     * @param string $extension
     * @return ResponseInterface
     */
    protected function downloadExtensionZipAction($extension): ResponseInterface
    {
        if (Environment::isComposerMode()) {
            throw new ExtensionManagerException(
                'The system is set to composer mode. You are not allowed to export extension archives.',
                1634662405
            );
        }

        $fileName = $this->createZipFileFromExtension($extension);
        $response = $this->responseFactory
            ->createResponse()
            ->withAddedHeader('Content-Type', 'application/zip')
            ->withAddedHeader('Content-Length', (string)(filesize($fileName) ?: ''))
            ->withAddedHeader('Content-Disposition', 'attachment; filename="' . PathUtility::basename($fileName) . '"')
            ->withBody($this->streamFactory->createStreamFromFile($fileName));

        unlink($fileName);

        return $response;
    }

    /**
     * Reloads the static SQL data of an extension
     *
     * @param string $extension
     */
    protected function reloadExtensionDataAction($extension)
    {
        $extension = $this->installUtility->enrichExtensionWithDetails($extension, false);
        $registryKey = PathUtility::stripPathSitePrefix($extension['packagePath']) . 'ext_tables_static+adt.sql';

        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->remove('extensionDataImport', $registryKey);

        $this->installUtility->processExtensionSetup($extension['key']);

        $this->redirect('index', 'List');
    }

    /**
     * Create a zip file from an extension
     *
     * @param string $extensionKey
     * @return string Name and path of create zip file
     */
    protected function createZipFileFromExtension(string $extensionKey): string
    {
        $extensionDetails = $this->installUtility->enrichExtensionWithDetails($extensionKey);
        $extensionPath = $extensionDetails['packagePath'];

        // Add trailing slash to the extension path, getAllFilesAndFoldersInPath explicitly requires that.
        $extensionPath = PathUtility::sanitizeTrailingSeparator($extensionPath);

        $version = (string)$extensionDetails['version'];
        if (empty($version)) {
            $version = '0.0.0';
        }

        $temporaryPath = Environment::getVarPath() . '/transient/';
        if (!@is_dir($temporaryPath)) {
            GeneralUtility::mkdir($temporaryPath);
        }
        $fileName = $temporaryPath . $extensionKey . '_' . $version . '_' . date('YmdHi', $GLOBALS['EXEC_TIME']) . '.zip';

        $zip = new \ZipArchive();
        $zip->open($fileName, \ZipArchive::CREATE);

        $excludePattern = $GLOBALS['TYPO3_CONF_VARS']['EXT']['excludeForPackaging'];

        // Get all the files of the extension, but exclude the ones specified in the excludePattern
        $files = GeneralUtility::getAllFilesAndFoldersInPath(
            [], // No files pre-added
            $extensionPath, // Start from here
            '', // Do not filter files by extension
            true, // Include subdirectories
            PHP_INT_MAX, // Recursion level
            $excludePattern        // Files and directories to exclude.
        );

        // Make paths relative to extension root directory.
        $files = GeneralUtility::removePrefixPathFromList($files, $extensionPath);
        $files = is_array($files) ? $files : [];

        // Remove the one empty path that is the extension dir itself.
        $files = array_filter($files);

        foreach ($files as $file) {
            $fullPath = $extensionPath . $file;
            // Distinguish between files and directories, as creation of the archive
            // fails on Windows when trying to add a directory with "addFile".
            if (is_dir($fullPath)) {
                $zip->addEmptyDir($file);
            } else {
                $zip->addFile($fullPath, $file);
            }
        }

        $zip->close();
        return $fileName;
    }
}
