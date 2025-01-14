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

namespace TYPO3\CMS\Extensionmanager\Controller;

use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Core\Configuration\ExtensionConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\AllowedMethodsTrait;
use TYPO3\CMS\Core\Security\BlockSerializationTrait;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extensionmanager\Exception\ExtensionManagerException;
use TYPO3\CMS\Extensionmanager\Exception\InvalidFileException;
use TYPO3\CMS\Extensionmanager\Service\ExtensionManagementService;
use TYPO3\CMS\Extensionmanager\Utility\FileHandlingUtility;

/**
 * Controller for handling upload of a .zip file which is then placed as an extension.
 *
 * @internal This class is a specific controller implementation and is not considered part of the Public TYPO3 API.
 */
class UploadExtensionFileController extends AbstractController
{
    use AllowedMethodsTrait;
    use BlockSerializationTrait;

    protected string $extensionBackupPath = '';
    protected bool $removeFromOriginalPath = false;

    public function __construct(
        protected readonly FileHandlingUtility $fileHandlingUtility,
        protected readonly ExtensionManagementService $managementService,
        protected readonly ExtensionConfiguration $extensionConfiguration,
    ) {}

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
    public function formAction(): ResponseInterface
    {
        if (Environment::isComposerMode()) {
            throw new ExtensionManagerException(
                'Composer mode is active. You are not allowed to upload any extension file.',
                1444725828
            );
        }

        return $this->htmlResponse();
    }

    /**
     * Extract an uploaded file and install the matching extension
     *
     * @param bool $overwrite Overwrite existing extension if TRUE
     */
    public function extractAction(bool $overwrite = false): ResponseInterface
    {
        $this->assertAllowedHttpMethod($this->request, 'POST');

        if (Environment::isComposerMode()) {
            throw new ExtensionManagerException(
                'Composer mode is active. You are not allowed to upload any extension file.',
                1444725853
            );
        }

        try {
            $fileName = null;
            $file = $this->request->getUploadedFiles()['extensionFile'] ?? null;
            if ($file === null) {
                throw new ExtensionManagerException(
                    'Uploading file failed. Check your upload_max_filesize and post_max_size limits.',
                    1342864339
                );
            }
            $fileName = pathinfo($file->getClientFilename(), PATHINFO_BASENAME);
            // If the file name isn't valid an error will be thrown
            $this->checkFileName($fileName);

            $tempFile = GeneralUtility::tempnam('upload_temp_');
            $file->moveTo($tempFile);

            // Remove version and extension from filename to determine the extension key
            $extensionKey = $this->getExtensionKeyFromFileName($fileName);
            if (empty($extensionKey)) {
                throw new ExtensionManagerException(
                    'Could not extract extension key from uploaded file name. File name must be something like "my_extension_4.2.2.zip".',
                    1603087515
                );
            }
            $this->extractExtensionFromZipFile($tempFile, $extensionKey, (bool)$overwrite);
            $isAutomaticInstallationEnabled = (bool)$this->extensionConfiguration->get('extensionmanager', 'automaticInstallation');
            if (!$isAutomaticInstallationEnabled) {
                $this->addFlashMessage(
                    $this->translate('extensionList.uploadFlashMessage.message', [$extensionKey]),
                    $this->translate('extensionList.uploadFlashMessage.title')
                );
            } else {
                // @todo This cannot work without reloading the package information
                if ($this->activateExtension($extensionKey)) {
                    $this->addFlashMessage(
                        $this->translate('extensionList.installedFlashMessage.message', [$extensionKey]),
                        ''
                    );
                } else {
                    return $this->redirect(
                        'unresolvedDependencies',
                        'List',
                        null,
                        [
                            'extensionKey' => $extensionKey,
                            'returnAction' => ['controller' => 'List', 'action' => 'index'],
                        ]
                    );
                }
            }
        } catch (InvalidFileException $exception) {
            $this->addFlashMessage($exception->getMessage(), '', ContextualFeedbackSeverity::ERROR);
        } catch (\Exception $exception) {
            if ($fileName !== null) {
                $this->removeExtensionAndRestoreFromBackup($fileName);
            }
            $this->addFlashMessage($exception->getMessage(), '', ContextualFeedbackSeverity::ERROR);
        }
        return $this->redirect('index', 'List', null, [
            self::TRIGGER_RefreshModuleMenu => true,
            self::TRIGGER_RefreshTopbar => true,
        ]);
    }

    /**
     * Validate the filename of an uploaded file
     *
     * @param string $fileName
     * @throws InvalidFileException
     */
    protected function checkFileName($fileName)
    {
        if (empty($fileName)) {
            throw new InvalidFileException('No file given.', 1342858852);
        }
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        if ($fileExtension !== 'zip') {
            throw new InvalidFileException('Wrong file format "' . $fileExtension . '" given. Only .zip files are allowed.', 1342858853);
        }
    }

    protected function activateExtension(string $extensionKey): bool
    {
        $this->managementService->reloadPackageInformation($extensionKey);
        $extension = $this->managementService->getExtension($extensionKey);
        return is_array($this->managementService->installExtension($extension));
    }

    /**
     * Extracts a given zip file and installs the extension
     *
     * @param string $uploadedFile Path to uploaded file
     * @param bool $overwrite Overwrite existing extension if TRUE
     * @throws ExtensionManagerException
     */
    protected function extractExtensionFromZipFile(string $uploadedFile, string $extensionKey, bool $overwrite = false): string
    {
        $isExtensionAvailable = $this->managementService->isAvailable($extensionKey);
        if (!$overwrite && $isExtensionAvailable) {
            throw new ExtensionManagerException('Extension is already available and overwriting is disabled.', 1342864311);
        }
        if ($isExtensionAvailable) {
            $this->copyExtensionFolderToTempFolder($extensionKey);
        }
        $this->removeFromOriginalPath = true;
        $this->fileHandlingUtility->unzipExtensionFromFile($uploadedFile, $extensionKey);
        return $extensionKey;
    }

    /**
     * As there is no information about the extension key in the zip
     * we have to use the file name to get that information
     * filename format is expected to be extensionkey_version.zip.
     * Removes version and file extension from filename to determine extension key
     *
     * @param string $fileName
     * @return string
     */
    protected function getExtensionKeyFromFileName(string $fileName): string
    {
        return (string)preg_replace('/_(\\d+)(\\.|\\-)(\\d+)(\\.|\\-)(\\d+).*/i', '', strtolower(substr($fileName, 0, -4)));
    }

    /**
     * Copies current extension folder to typo3temp directory as backup
     */
    protected function copyExtensionFolderToTempFolder(string $extensionKey): void
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
    protected function removeExtensionAndRestoreFromBackup(string $fileName): void
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
    protected function removeBackupFolder(): void
    {
        if (!empty($this->extensionBackupPath)) {
            GeneralUtility::rmdir($this->extensionBackupPath, true);
            $this->extensionBackupPath = '';
        }
    }
}
