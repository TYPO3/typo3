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
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\File;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Impexp\Import;

/**
 * Import module controller
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ImportController
{
    protected const NO_UPLOAD = 0;
    protected const UPLOAD_DONE = 1;
    protected const UPLOAD_FAILED = 2;

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ExtendedFileUtility $fileProcessor,
        protected readonly ResourceFactory $resourceFactory
    ) {
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if (!$this->getBackendUser()->isImportEnabled()) {
            throw new \RuntimeException(
                'Import module is disabled for non admin users and user TSconfig options.impexp.enableImportForNonAdminUser is not enabled.',
                1464435459
            );
        }

        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageInfo = BackendUtility::readPageAccess($id, $permsClause) ?: [];
        if ($pageInfo === []) {
            throw new \RuntimeException("You don't have access to this page.", 1604308205);
        }

        $inputData = $request->getParsedBody()['tx_impexp'] ?? $request->getQueryParams()['tx_impexp'] ?? [];
        if ($inputData['new_import'] ?? false) {
            unset($inputData['import_mode']);
        }

        $view = $this->moduleTemplateFactory->create($request);

        $uploadStatus = self::NO_UPLOAD;
        $uploadedFileName = '';
        if ($request->getMethod() === 'POST' && empty($parsedBody)) {
            // This happens if the post request was larger than allowed on the server.
            $view->addFlashMessage(
                $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:importdata_upload_nodata'),
                $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:importdata_upload_error'),
                ContextualFeedbackSeverity::ERROR
            );
        }
        if ($request->getMethod() === 'POST' && isset($parsedBody['_upload'])) {
            $uploadStatus = self::UPLOAD_FAILED;
            $file = $this->handleFileUpload($request);
            if (($file instanceof File) && in_array($file->getExtension(), ['t3d', 'xml'], true)) {
                $inputData['file'] = $file->getCombinedIdentifier();
                $uploadStatus = self::UPLOAD_DONE;
                $uploadedFileName = $file->getName();
            }
        }

        $import = $this->configureImportFromFormDataAndImportIfRequested($view, $id, $inputData);
        $importFolder = $import->getOrCreateDefaultImportExportFolder();

        $view->assignMultiple([
            'importFolder' => ($importFolder instanceof Folder) ? $importFolder->getCombinedIdentifier() : '',
            'import' => $import,
            'errors' => $import->getErrorLog(),
            'preview' => $import->renderPreview(),
            'id' => $id,
            'fileSelectOptions' => $this->getSelectableFileList($import),
            'inData' => $inputData,
            'isAdmin' => $this->getBackendUser()->isAdmin(),
            'uploadedFile' => $uploadedFileName,
            'uploadStatus' => $uploadStatus,
        ]);
        $view->setModuleName('');
        $view->getDocHeaderComponent()->setMetaInformation($pageInfo);
        if ((int)($pageInfo['uid'] ?? 0) > 0) {
            $this->addDocHeaderPreviewButton($view, (int)$pageInfo['uid']);
        }
        return $view->renderResponse('Import');
    }

    protected function addDocHeaderPreviewButton(ModuleTemplate $view, int $pageUid): void
    {
        $buttonBar = $view->getDocHeaderComponent()->getButtonBar();
        $previewDataAttributes = PreviewUriBuilder::create($pageUid)
            ->withRootLine(BackendUtility::BEgetRootLine($pageUid))
            ->buildDispatcherDataAttributes();
        $viewButton = $buttonBar->makeLinkButton()
            ->setHref('#')
            ->setDataAttributes($previewDataAttributes ?? [])
            ->setDisabled(!$previewDataAttributes)
            ->setTitle($this->getLanguageService()->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.showPage'))
            ->setIcon($this->iconFactory->getIcon('actions-view-page', Icon::SIZE_SMALL))
            ->setShowLabelText(true);
        $buttonBar->addButton($viewButton);
    }

    protected function handleFileUpload(ServerRequestInterface $request): ?File
    {
        $parsedBody = $request->getParsedBody() ?? [];
        $file = $parsedBody['file'] ?? [];
        $conflictMode = empty($parsedBody['overwriteExistingFiles']) ? DuplicationBehavior::CANCEL : DuplicationBehavior::REPLACE;
        $this->fileProcessor->setActionPermissions();
        $this->fileProcessor->setExistingFilesConflictMode(DuplicationBehavior::cast($conflictMode));
        $this->fileProcessor->start($file);
        $result = $this->fileProcessor->processData();
        if (isset($result['upload'][0][0])) {
            // If upload went well, set the new file as the import file.
            return $result['upload'][0][0];
        }
        return null;
    }

    /**
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     */
    protected function configureImportFromFormDataAndImportIfRequested(ModuleTemplate $view, int $id, array $inputData): Import
    {
        $import = GeneralUtility::makeInstance(Import::class);
        $import->setPid($id);
        $import->setUpdate((bool)($inputData['do_update'] ?? false));
        $import->setImportMode((array)($inputData['import_mode'] ?? null));
        $import->setEnableLogging((bool)($inputData['enableLogging'] ?? false));
        $import->setGlobalIgnorePid((bool)($inputData['global_ignore_pid'] ?? false));
        $import->setForceAllUids((bool)($inputData['force_all_UIDS'] ?? false));
        $import->setShowDiff(!(bool)($inputData['notShowDiff'] ?? false));
        $import->setSoftrefInputValues((array)($inputData['softrefInputValues'] ?? null));
        if (!empty($inputData['file'])) {
            if (PathUtility::isExtensionPath($inputData['file'])) {
                $filePath = $inputData['file'];
            } else {
                $filePath = $this->getFilePathWithinFileMountBoundaries((string)$inputData['file']);
            }
            try {
                $import->loadFile($filePath, true);
                $import->checkImportPrerequisites();
                if ($inputData['import_file'] ?? false) {
                    $import->importData();
                    BackendUtility::setUpdateSignal('updatePageTree');
                }
            } catch (\Exception $e) {
                $view->addFlashMessage($e->getMessage(), '', ContextualFeedbackSeverity::ERROR);
            }
        }
        return $import;
    }

    protected function getFilePathWithinFileMountBoundaries(string $filePath): string
    {
        try {
            $file = $this->resourceFactory->getFileObjectFromCombinedIdentifier($filePath);
            return $file->getForLocalProcessing(false);
        } catch (\Exception $exception) {
            return '';
        }
    }

    protected function getSelectableFileList(Import $import): array
    {
        $exportFiles = [];

        // Fileadmin
        $folder = $import->getOrCreateDefaultImportExportFolder();
        if ($folder !== null) {
            $filter = GeneralUtility::makeInstance(FileExtensionFilter::class);
            $filter->setAllowedFileExtensions(['t3d', 'xml']);
            $folder->getStorage()->addFileAndFolderNameFilter([$filter, 'filterFileList']);
            $exportFiles = $folder->getFiles();
        }
        $selectableFiles = [''];
        foreach ($exportFiles as $file) {
            $selectableFiles[$file->getCombinedIdentifier()] = $file->getPublicUrl();
        }

        // Extension Distribution
        if ($this->getBackendUser()->isAdmin()) {
            $possibleImportFiles = [
                'Initialisation/data.t3d',
                'Initialisation/data.xml',
            ];
            $activePackages = GeneralUtility::makeInstance(PackageManager::class)->getActivePackages();
            foreach ($activePackages as $package) {
                foreach ($possibleImportFiles as $possibleImportFile) {
                    if (!file_exists($package->getPackagePath() . $possibleImportFile)) {
                        continue;
                    }
                    $selectableFiles['EXT:' . $package->getPackageKey() . '/' . $possibleImportFile] = 'EXT:' . $package->getPackageKey() . '/' . $possibleImportFile;
                }
            }
        }

        return $selectableFiles;
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
