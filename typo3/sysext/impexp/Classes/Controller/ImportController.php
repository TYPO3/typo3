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

namespace TYPO3\CMS\Impexp\Controller;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\PreviewUriBuilder;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Import;

/**
 * Main script class for the Import facility
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ImportController extends ImportExportController
{
    protected const NO_UPLOAD = 0;
    protected const UPLOAD_DONE = 1;
    protected const UPLOAD_FAILED = 2;

    /**
     * @var string
     */
    protected $routeName = 'tx_impexp_import';

    /**
     * @var Import
     */
    protected $import;

    /**
     * Incoming array has syntax:
     *
     * id = import page id (must be readable)
     *
     * file = pointing to filename relative to public web path
     *
     * [all relation fields are clear, but not files]
     * - page-tree is written first
     * - then remaining pages (to the root of import)
     * - then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
     * - then all internal relations are set and non-existing relations removed, relations to static tables preserved.
     *
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Resource\Exception
     * @throws \RuntimeException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->getBackendUser()->isImportEnabled() === false) {
            throw new \RuntimeException(
                'Import module is disabled for non admin users and '
                . 'userTsConfig options.impexp.enableImportForNonAdminUser is not enabled.',
                1464435459
            );
        }

        $this->main($request);

        // Input data
        $inData = $request->getParsedBody()['tx_impexp'] ?? $request->getQueryParams()['tx_impexp'] ?? [];
        $inData = $this->preprocessInputData($inData);

        // Handle upload
        $inData = $this->handleUpload($request, $inData);

        // Perform import
        $inData = $this->importData($inData);

        // Prepare view
        $this->registerDocHeaderButtons();
        $this->makeForm();
        $this->standaloneView->assign('inData', $inData);
        $this->standaloneView->assign('isAdmin', $this->getBackendUser()->isAdmin());
        $this->standaloneView->setTemplate('Import.html');
        $this->moduleTemplate->setContent($this->standaloneView->render());

        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    protected function preprocessInputData(array $inData): array
    {
        if ($inData['new_import'] ?? false) {
            unset($inData['import_mode']);
        }
        return $inData;
    }

    /**
     * Handle upload of an export file
     *
     * @param ServerRequestInterface $request
     * @param array $inData
     * @return array Modified data
     * @throws Exception
     * @throws \TYPO3\CMS\Core\Resource\Exception
     */
    protected function handleUpload(ServerRequestInterface $request, array $inData): array
    {
        if ($request->getMethod() !== 'POST') {
            return $inData;
        }

        $parsedBody = $request->getParsedBody() ?? [];

        if (empty($parsedBody)) {
            // This happens if the post request was larger than allowed on the server.
            $this->moduleTemplate->addFlashMessage(
                $this->lang->getLL('importdata_upload_nodata'),
                $this->lang->getLL('importdata_upload_error'),
                FlashMessage::ERROR
            );
            return $inData;
        }

        $uploadStatus = self::NO_UPLOAD;

        if (isset($parsedBody['_upload'])) {
            $file = $parsedBody['file'];
            $conflictMode = empty($parsedBody['overwriteExistingFiles']) ? DuplicationBehavior::CANCEL : DuplicationBehavior::REPLACE;
            $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
            $fileProcessor->setActionPermissions();
            $fileProcessor->setExistingFilesConflictMode(DuplicationBehavior::cast($conflictMode));
            $fileProcessor->start($file);
            $result = $fileProcessor->processData();
            // Finally: If upload went well, set the new file as the import file.
            if (isset($result['upload'][0][0])) {
                /** @var File $uploadedFile */
                $uploadedFile = $result['upload'][0][0];
                if (in_array($uploadedFile->getExtension(), ['t3d', 'xml'], true)) {
                    $inData['file'] = $uploadedFile->getCombinedIdentifier();
                }
                $this->standaloneView->assign('uploadedFile', $uploadedFile->getName());
                $uploadStatus = self::UPLOAD_DONE;
            } else {
                $uploadStatus = self::UPLOAD_FAILED;
            }
        }

        $this->standaloneView->assign('uploadStatus', $uploadStatus);
        return $inData;
    }

    /**
     * Import part of module
     *
     * @param array $inData
     * @return array Modified data
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function importData(array $inData): array
    {
        // Create import object and configure it:
        $this->import = GeneralUtility::makeInstance(Import::class);
        $this->import->setPid($this->id);
        $this->import->setUpdate((bool)($inData['do_update'] ?? false));
        $this->import->setImportMode((array)($inData['import_mode'] ?? null));
        $this->import->setEnableLogging((bool)($inData['enableLogging'] ?? false));
        $this->import->setGlobalIgnorePid((bool)($inData['global_ignore_pid'] ?? false));
        $this->import->setForceAllUids((bool)($inData['force_all_UIDS'] ?? false));
        $this->import->setShowDiff(!(bool)($inData['notShowDiff'] ?? false));
        $this->import->setSoftrefInputValues((array)($inData['softrefInputValues'] ?? null));

        // Perform preview and import:
        if (!empty($inData['file'])) {
            $filePath = $this->getFilePathWithinFileMountBoundaries((string)$inData['file']);
            try {
                $this->import->loadFile($filePath, true);
                $this->import->checkImportPrerequisites();
                if ($inData['import_file'] ?? false) {
                    $this->import->importData();
                    BackendUtility::setUpdateSignal('updatePageTree');
                }
            } catch (\Exception $e) {
                $this->moduleTemplate->addFlashMessage($e->getMessage(), '', FlashMessage::ERROR);
            }
        }

        $this->standaloneView->assign('import', $this->import);
        $this->standaloneView->assign('errors', $this->import->getErrorLog());
        $this->standaloneView->assign('preview', $this->import->renderPreview());
        return $inData;
    }

    protected function getFilePathWithinFileMountBoundaries(string $filePath): string
    {
        try {
            $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($filePath);
            return $file->getForLocalProcessing(false);
        } catch (\Exception $exception) {
            return '';
        }
    }

    protected function registerDocHeaderButtons(): void
    {
        parent::registerDocHeaderButtons();

        if ($this->pageInfo['uid'] ?? false) {
            $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
            $previewDataAttributes = PreviewUriBuilder::create((int)$this->pageInfo['uid'])
                ->withRootLine(BackendUtility::BEgetRootLine($this->pageInfo['uid']))
                ->buildDispatcherDataAttributes();
            $viewButton = $buttonBar->makeLinkButton()
                ->setTitle($this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
                ->setHref('#')
                ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
                ->setDataAttributes($previewDataAttributes ?? []);
            $buttonBar->addButton($viewButton);
        }
    }

    /**
     * Create module forms
     */
    protected function makeForm(): void
    {
        $selectOptions = [''];
        foreach ($this->getExportFiles() as $file) {
            $selectOptions[$file->getCombinedIdentifier()] = $file->getPublicUrl();
        }

        $importFolder = $this->import->getOrCreateDefaultImportExportFolder();
        if ($importFolder) {
            $this->standaloneView->assign('importFolder', $importFolder->getCombinedIdentifier());
            $this->standaloneView->assign(
                'importFolderHint',
                sprintf(
                    $this->lang->getLL('importdata_fromPathS'),
                    $importFolder->getCombinedIdentifier()
                )
            );
        } else {
            $this->standaloneView->assign(
                'importFolderHint',
                $this->lang->getLL('importdata_no_default_upload_folder')
            );
        }

        $this->standaloneView->assign('fileSelectOptions', $selectOptions);
    }

    /**
     * Gets all export files.
     *
     * @return File[]
     * @throws \InvalidArgumentException
     */
    protected function getExportFiles(): array
    {
        $exportFiles = [];

        $folder = $this->import->getOrCreateDefaultImportExportFolder();
        if ($folder !== null) {
            $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filter->setAllowedFileExtensions(['t3d', 'xml']);
            $folder->getStorage()->addFileAndFolderNameFilter([$filter, 'filterFileList']);

            $exportFiles = $folder->getFiles();
        }

        return $exportFiles;
    }

    /**
     * Gets a file by combined identifier.
     *
     * @param string $combinedIdentifier
     * @return File|ProcessedFile|null
     */
    protected function getFile(string $combinedIdentifier)
    {
        try {
            $file = GeneralUtility::makeInstance(ResourceFactory::class)->getFileObjectFromCombinedIdentifier($combinedIdentifier);
        } catch (\Exception $exception) {
            $file = null;
        }

        return $file;
    }
}
