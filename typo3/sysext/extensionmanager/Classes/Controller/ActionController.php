<?php
namespace TYPO3\CMS\Extensionmanager\Controller;

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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Controller for handling extension related actions like
 * installing, removing, downloading of data or files
 */
class ActionController extends AbstractController
{
    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\InstallUtility
     */
    protected $installUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility
     */
    protected $extensionModelUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
     */
    protected $managementService;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility
     */
    public function injectInstallUtility(\TYPO3\CMS\Extensionmanager\Utility\InstallUtility $installUtility)
    {
        $this->installUtility = $installUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility $extensionModelUtility
     */
    public function injectExtensionModelUtility(\TYPO3\CMS\Extensionmanager\Utility\ExtensionModelUtility $extensionModelUtility)
    {
        $this->extensionModelUtility = $extensionModelUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService
     */
    public function injectManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService)
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
        $installedExtensions = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
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
        } catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
            $this->addFlashMessage($e->getMessage(), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        } catch (\TYPO3\CMS\Core\Package\Exception\PackageStatesFileNotWritableException $e) {
            $this->addFlashMessage($e->getMessage(), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
            $this->installUtility->removeExtension($extension);
            $this->addFlashMessage(
                \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate(
                    'extensionList.remove.message',
                    'extensionmanager',
                    [
                        'extension' => $extension,
                    ]
                )
            );
        } catch (\TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException $e) {
            $this->addFlashMessage($e->getMessage(), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
        } catch (\TYPO3\CMS\Core\Package\Exception $e) {
            $this->addFlashMessage($e->getMessage(), '', \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
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
        $this->fileHandlingUtility->sendZipFileToBrowserAndDelete($fileName);
    }

    /**
     * Download data of an extension as sql statements
     *
     * @param string $extension
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    protected function downloadExtensionDataAction($extension)
    {
        $error = null;
        $sqlData = $this->installUtility->getExtensionSqlDataDump($extension);
        $dump = $sqlData['extTables'] . $sqlData['staticSql'];
        $fileName = $extension . '_sqlDump.sql';
        $filePath = PATH_site . 'typo3temp/var/ExtensionManager/' . $fileName;
        $error = \TYPO3\CMS\Core\Utility\GeneralUtility::writeFileToTypo3tempDir($filePath, $dump);
        if (is_string($error)) {
            throw new \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException($error, 1343048718);
        }
        $this->fileHandlingUtility->sendSqlDumpFileToBrowserAndDelete($filePath, $fileName);
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

        $this->installUtility->processExtensionSetup($extension);

        $this->redirect('index', 'List');
    }
}
