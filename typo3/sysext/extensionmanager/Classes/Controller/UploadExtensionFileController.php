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

use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Exception\DependencyConfigurationNotFoundException;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;

/**
 * Controller for handling upload of a local extension file
 * Handles .t3x or .zip files
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UploadExtensionFileController extends AbstractController
{
    use BlockSerializationTrait;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository
     */
    protected $extensionRepository;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility
     */
    protected $fileHandlingUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility
     */
    protected $terUtility;

    /**
     * @var \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService
     */
    protected $managementService;

    /**
     * @var string
     */
    protected $extensionBackupPath = '';

    /**
     * @var bool
     */
    protected $removeFromOriginalPath = false;

    /**
     * @param \TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository
     */
    public function injectExtensionRepository(\TYPO3\CMS\Extensionmanager\Domain\Repository\ExtensionRepository $extensionRepository)
    {
        $this->extensionRepository = $extensionRepository;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility
     */
    public function injectFileHandlingUtility(\TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility $fileHandlingUtility)
    {
        $this->fileHandlingUtility = $fileHandlingUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility
     */
    public function injectTerUtility(\TYPO3\CMS\Extensionmanager\Utility\Connection\TerUtility $terUtility)
    {
        $this->terUtility = $terUtility;
    }

    /**
     * @param \TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService
     */
    public function injectManagementService(\TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService $managementService)
    {
        $this->managementService = $managementService;
    }

    /**
     * Remove backup folder before destruction
     */
    public function __destruct()
    {
        $this->removeBackupFolder();
    }

    /**
     * Render upload extension form
     */
    public function formAction()
    {
        if (Environment::isComposerMode()) {
            throw new ExtensionManagerException(
                'Composer mode is active. You are not allowed to upload any extension file.',
                1444725828
            );
        }
    }

    /**
     * Extract an uploaded file and install the matching extension
     *
     * @param bool $overwrite Overwrite existing extension if TRUE
     * @throws \TYPO3\CMS\Extbase\Mvc\Exception\StopActionException
     */
    public function extractAction($overwrite = false)
    {
        if (Environment::isComposerMode()) {
            throw new ExtensionManagerException(
                'Composer mode is active. You are not allowed to upload any extension file.',
                1444725853
            );
        }
        $file = $_FILES['tx_extensionmanager_tools_extensionmanagerextensionmanager'];
        $fileName = pathinfo($file['name']['extensionFile'], PATHINFO_BASENAME);
        try {
            // If the file name isn't valid an error will be thrown
            $this->checkFileName($fileName);
            if (!empty($file['tmp_name']['extensionFile'])) {
                $tempFile = GeneralUtility::upload_to_tempfile($file['tmp_name']['extensionFile']);
            } else {
                throw new ExtensionManagerException(
                    'Creating temporary file failed. Check your upload_max_filesize and post_max_size limits.',
                    1342864339
                );
            }
            $extensionData = $this->extractExtensionFromFile($tempFile, $fileName, $overwrite);
            $isAutomaticInstallationEnabled = (bool)GeneralUtility::makeInstance(ExtensionConfiguration::class)->get('extensionmanager', 'automaticInstallation');
            if (!$isAutomaticInstallationEnabled) {
                $this->addFlashMessage(
                    $this->translate('extensionList.uploadFlashMessage.message', [$extensionData['extKey']]),
                    $this->translate('extensionList.uploadFlashMessage.title'),
                    FlashMessage::OK
                );
            } else {
                if ($this->activateExtension($extensionData['extKey'])) {
                    $this->addFlashMessage(
                        $this->translate('extensionList.installedFlashMessage.message', [$extensionData['extKey']]),
                        '',
                        FlashMessage::OK
                    );
                } else {
                    $this->redirect('unresolvedDependencies', 'List', null, ['extensionKey' => $extensionData['extKey']]);
                }
            }
        } catch (\TYPO3\CMS\Extbase\Mvc\Exception\StopActionException $exception) {
            throw $exception;
        } catch (DependencyConfigurationNotFoundException $exception) {
            $this->addFlashMessage($exception->getMessage(), '', FlashMessage::ERROR);
        } catch (\Exception $exception) {
            $this->removeExtensionAndRestoreFromBackup($fileName);
            $this->addFlashMessage($exception->getMessage(), '', FlashMessage::ERROR);
        }
        $this->redirect('index', 'List', null, [
            self::TRIGGER_RefreshModuleMenu => true,
            self::TRIGGER_RefreshTopbar => true
        ]);
    }

    /**
     * Validate the filename of an uploaded file
     *
     * @param string $fileName
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    public function checkFileName($fileName)
    {
        $extension = pathinfo($fileName, PATHINFO_EXTENSION);
        if (empty($fileName)) {
            throw new ExtensionManagerException('No file given.', 1342858852);
        }
        if ($extension !== 't3x' && $extension !== 'zip') {
            throw new ExtensionManagerException('Wrong file format "' . $extension . '" given. Allowed formats are t3x and zip.', 1342858853);
        }
    }

    /**
     * Extract a given t3x or zip file
     *
     * @param string $uploadPath Path to existing extension file
     * @param string $fileName Filename of the uploaded file
     * @param bool $overwrite If true, extension will be replaced
     * @return array Extension data
     * @throws ExtensionManagerException
     * @throws DependencyConfigurationNotFoundException
     */
    public function extractExtensionFromFile($uploadPath, $fileName, $overwrite)
    {
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileExtension === 't3x') {
            $extensionData = $this->getExtensionFromT3xFile($uploadPath, $overwrite);
        } else {
            $extensionData = $this->getExtensionFromZipFile($uploadPath, $fileName, $overwrite);
        }

        return $extensionData;
    }

    /**
     * @param string $extensionKey
     * @return bool
     */
    public function activateExtension($extensionKey)
    {
        $this->managementService->reloadPackageInformation($extensionKey);
        $extension = $this->managementService->getExtension($extensionKey);
        return is_array($this->managementService->installExtension($extension));
    }

    /**
     * Extracts a given t3x file and installs the extension
     *
     * @param string $file Path to uploaded file
     * @param bool $overwrite Overwrite existing extension if TRUE
     * @throws ExtensionManagerException
     * @throws DependencyConfigurationNotFoundException
     * @return array
     */
    protected function getExtensionFromT3xFile($file, $overwrite = false)
    {
        $fileContent = file_get_contents($file);
        if (!$fileContent) {
            throw new ExtensionManagerException('File had no or wrong content.', 1342859339);
        }
        $extensionData = $this->terUtility->decodeExchangeData($fileContent);
        if (empty($extensionData['extKey'])) {
            throw new ExtensionManagerException('Decoding the file went wrong. No extension key found', 1342864309);
        }
        $isExtensionAvailable = $this->managementService->isAvailable($extensionData['extKey']);
        if (!$overwrite && $isExtensionAvailable) {
            throw new ExtensionManagerException($this->translate('extensionList.overwritingDisabled'), 1342864310);
        }
        if ($isExtensionAvailable) {
            $this->copyExtensionFolderToTempFolder($extensionData['extKey']);
        }
        $this->removeFromOriginalPath = true;
        $extension = $this->extensionRepository->findOneByExtensionKeyAndVersion($extensionData['extKey'], $extensionData['EM_CONF']['version']);
        $this->fileHandlingUtility->unpackExtensionFromExtensionDataArray($extensionData, $extension);

        if (empty($extension)
            && empty($extensionData['EM_CONF']['constraints'])
            && !isset($extensionData['FILES']['ext_emconf.php'])
            && !isset($extensionData['FILES']['/ext_emconf.php'])
        ) {
            throw new DependencyConfigurationNotFoundException('Extension cannot be installed automatically because no dependencies could be found! Please check dependencies manually (on typo3.org) before installing the extension.', 1439587168);
        }

        return $extensionData;
    }

    /**
     * Extracts a given zip file and installs the extension
     * As there is no information about the extension key in the zip
     * we have to use the file name to get that information
     * filename format is expected to be extensionkey_version.zip
     *
     * @param string $file Path to uploaded file
     * @param string $fileName Filename (basename) of uploaded file
     * @param bool $overwrite Overwrite existing extension if TRUE
     * @return array
     * @throws ExtensionManagerException
     */
    protected function getExtensionFromZipFile($file, $fileName, $overwrite = false)
    {
        // Remove version and extension from filename to determine the extension key
        $extensionKey = $this->getExtensionKeyFromFileName($fileName);
        $isExtensionAvailable = $this->managementService->isAvailable($extensionKey);
        if (!$overwrite && $isExtensionAvailable) {
            throw new ExtensionManagerException('Extension is already available and overwriting is disabled.', 1342864311);
        }
        if ($isExtensionAvailable) {
            $this->copyExtensionFolderToTempFolder($extensionKey);
        }
        $this->removeFromOriginalPath = true;
        $this->fileHandlingUtility->unzipExtensionFromFile($file, $extensionKey);

        return ['extKey' => $extensionKey];
    }

    /**
     * Removes version and file extension from filename to determine extension key
     *
     * @param string $fileName
     * @return string
     */
    protected function getExtensionKeyFromFileName($fileName)
    {
        return preg_replace('/_(\\d+)(\\.|\\-)(\\d+)(\\.|\\-)(\\d+).*/i', '', strtolower(substr($fileName, 0, -4)));
    }

    /**
     * Copies current extension folder to typo3temp directory as backup
     *
     * @param string $extensionKey
     * @throws \TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException
     */
    protected function copyExtensionFolderToTempFolder($extensionKey)
    {
        $this->extensionBackupPath = Environment::getVarPath() . '/transient/' . $extensionKey . substr(sha1($extensionKey . microtime()), 0, 7) . '/';
        GeneralUtility::mkdir($this->extensionBackupPath);
        GeneralUtility::copyDirectory(
            $this->fileHandlingUtility->getExtensionDir($extensionKey),
            $this->extensionBackupPath
        );
    }

    /**
     * Removes the extension directory and restores the extension from the backup directory
     *
     * @param string $fileName
     * @see UploadExtensionFileController::extractAction
     */
    protected function removeExtensionAndRestoreFromBackup($fileName)
    {
        $extDirPath = $this->fileHandlingUtility->getExtensionDir($this->getExtensionKeyFromFileName($fileName));
        if ($this->removeFromOriginalPath && is_dir($extDirPath)) {
            GeneralUtility::rmdir($extDirPath, true);
        }
        if (!empty($this->extensionBackupPath)) {
            GeneralUtility::mkdir($extDirPath);
            GeneralUtility::copyDirectory($this->extensionBackupPath, $extDirPath);
        }
    }

    /**
     * Removes the backup folder in typo3temp
     */
    protected function removeBackupFolder()
    {
        if (!empty($this->extensionBackupPath)) {
            GeneralUtility::rmdir($this->extensionBackupPath, true);
            $this->extensionBackupPath = '';
        }
    }
}
