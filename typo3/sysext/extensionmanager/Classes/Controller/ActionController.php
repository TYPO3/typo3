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

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Package\Exception;
use TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException;
use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;
use TYPO3\CMS\Extensionmanager\Utility\InstallUtility;

/**
 * Controller for handling extension related actions like
 * installing, removing, downloading of data or files
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class ActionController extends AbstractController
{
    /**
     * @var InstallUtility
     */
    protected $installUtility;

    /**
     * @var FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @var ExtensionModelUtility
     */
    protected $extensionModelUtility;

    /**
     * @var ExtensionManagementService
     */
    protected $managementService;

    /**
     * @param InstallUtility $installUtility
     */
    public function injectInstallUtility(InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    /**
     * @param FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * @param ExtensionModelUtility $extensionModelUtility
     */
    public function injectExtensionModelUtility(ExtensionModelUtility $extensionModelUtility)
    {
        $this->extensionModelUtility = $extensionModelUtility;
    }

    /**
     * @param ExtensionManagementService $managementService
     */
    public function injectManagementService(ExtensionManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * Toggle extension installation state action
     *
     * @param string $extensionKey
     */
    protected function toggleExtensionInstallationStateAction($extensionKey)
    {
        $installedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        try {
            if (in_array($extensionKey, $installedExtensions)) {
                // uninstall
                $this->installUtility->uninstall($extensionKey);
            } else {
                // install
                $extension = $this->extensionModelUtility->mapExtensionArrayToModel(
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
            self::TRIGGER_RefreshTopbar => true
        ]);
    }

    /**
     * Install an extension and omit dependency checking
     *
     * @param string $extensionKey
     */
    public function installExtensionWithoutSystemDependencyCheckAction($extensionKey)
    {
        $this->managementService->setSkipDependencyCheck(true);
        $this->forward('toggleExtensionInstallationState', null, null, ['extensionKey' => $extensionKey]);
    }

    /**
     * Remove an extension (if it is still installed, uninstall it first)
     *
     * @param string $extension
     * @return string
     */
    protected function removeExtensionAction($extension)
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
                )
            );
        } catch (ExtensionManagerException|Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', FlashMessage::ERROR);
        }

        return '';
    }

    /**
     * Download an extension as a zip file
     *
     * @param string $extension
     */
    protected function downloadExtensionZipAction($extension)
    {
        $fileName = $this->fileHandlingUtility->createZipFileFromExtension($extension);
        $this->sendZipFileToBrowserAndDelete($fileName);
    }

    /**
     * Sends a zip file to the browser and deletes it afterwards
     *
     * @param string $fileName
     */
    protected function sendZipFileToBrowserAndDelete(string $fileName): void
    {
        header('Content-Type: application/zip');
        header('Content-Length: ' . filesize($fileName));
        header('Content-Disposition: attachment; filename="' . PathUtility::basename($fileName) . '"');
        readfile($fileName);
        unlink($fileName);
        die;
    }

    /**
     * Reloads the static SQL data of an extension
     *
     * @param string $extension
     */
    protected function reloadExtensionDataAction($extension)
    {
        $extension = $this->installUtility->enrichExtensionWithDetails($extension, false);
        $registryKey = $extension['siteRelPath'] . 'ext_tables_static+adt.sql';

        $registry = GeneralUtility::makeInstance(Registry::class);
        $registry->remove('extensionDataImport', $registryKey);

        $this->installUtility->processExtensionSetup($extension['key']);

        $this->redirect('index', 'List');
    }
}
