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

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Exception as CoreException;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Impexp\Domain\Repository\PresetRepository;
use TYPO3\CMS\Impexp\Exception\InsufficientUserPermissionsException;
use TYPO3\CMS\Impexp\Exception\MalformedPresetException;
use TYPO3\CMS\Impexp\Exception\PresetNotFoundException;
use TYPO3\CMS\Impexp\Export;

/**
 * Export module controller
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ExportController
{
    protected array $defaultInputData = [
        'excludeDisabled' => 1,
        'preset' => [],
        'external_static' => [
            'tables' => [],
        ],
        'external_ref' => [
            'tables' => [],
        ],
        'pagetree' => [
            'tables' => [],
        ],
        'extension_dep' => [],
        'meta' => [
            'title' => '',
            'description' => '',
            'notes' => '',
        ],
        'record' => [],
        'list' => [],
    ];

    public function __construct(
        protected readonly IconFactory $iconFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
        protected readonly PresetRepository $presetRepository
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        if ($this->getBackendUser()->isExportEnabled() === false) {
            throw new \RuntimeException(
                'Export module is disabled for non admin users and '
                . 'user TSconfig options.impexp.enableExportForNonAdminUser is not enabled.',
                1636901978
            );
        }

        $backendUser = $this->getBackendUser();
        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $id = (int)($parsedBody['id'] ?? $queryParams['id'] ?? 0);
        $permsClause = $backendUser->getPagePermsClause(Permission::PAGE_SHOW);
        $pageInfo = BackendUtility::readPageAccess($id, $permsClause) ?: [];
        if ($pageInfo === []) {
            throw new \RuntimeException("You don't have access to this page.", 1604308206);
        }

        // @todo: Only small parts of tx_impexp can be hand over as GET, e.g. ['list'] and 'id', drop GET of everything else.
        //        Also, there's a clash with id: it can be ['list']'table:id', it can be 'id', it can be tx_impexp['id']. This
        //        should be de-messed somehow.
        $inputDataFromGetPost = $parsedBody['tx_impexp'] ?? $queryParams['tx_impexp'] ?? [];
        $inputData = $this->defaultInputData;
        ArrayUtility::mergeRecursiveWithOverrule($inputData, $inputDataFromGetPost);
        if ($inputData['resetExclude'] ?? false) {
            $inputData['exclude'] = [];
        }
        $inputData['preset']['public'] = (int)($inputData['preset']['public'] ?? 0);

        $view = $this->moduleTemplateFactory->create($request);

        $presetAction = $parsedBody['preset'] ?? [];
        $inputData = $this->processPresets($view, $presetAction, $inputData);

        $export = $this->configureExportFromFormData($inputData);
        $export->process();

        if ($inputData['download_export'] ?? false) {
            return $this->getDownload($export);
        }
        $saveFolder = $export->getOrCreateDefaultImportExportFolder();
        if (($inputData['save_export'] ?? false) && $saveFolder !== null) {
            $this->saveExportToFile($view, $export, $saveFolder);
        }
        $inputData['filename'] = $export->getExportFileName();

        $view->assignMultiple([
            'id' => $id,
            'errors' => $export->getErrorLog(),
            'preview' => $export->renderPreview(),
            'tableSelectOptions' => $this->getTableSelectOptions(['pages']),
            'treeHTML' => $export->getTreeHTML(),
            'levelSelectOptions' => $this->getPageLevelSelectOptions($inputData),
            'records' => $this->getRecordSelectOptions($inputData),
            'tableList' => $this->getSelectableTableList($inputData),
            'externalReferenceTableSelectOptions' => $this->getTableSelectOptions(),
            'externalStaticTableSelectOptions' => $this->getTableSelectOptions(),
            'presetSelectOptions' => $this->presetRepository->getPresets($id),
            'fileName' => '',
            'filetypeSelectOptions' => $this->getFileSelectOptions($export),
            'saveFolder' => $saveFolder?->getPublicUrl() ?? '',
            'hasSaveFolder' => true,
            'extensions' => $this->getExtensionList(),
            'inData' => $inputData,
        ]);
        $view->setModuleName('');
        $view->getDocHeaderComponent()->setMetaInformation($pageInfo);
        return $view->renderResponse('Export');
    }

    protected function processPresets(ModuleTemplate $view, array $presetAction, array $inputData): array
    {
        if (empty($presetAction)) {
            return $inputData;
        }
        $presetUid = (int)$presetAction['select'];
        try {
            if (isset($presetAction['save'])) {
                if ($presetUid > 0) {
                    // Update existing
                    $this->presetRepository->updatePreset($presetUid, $inputData);
                    $view->addFlashMessage('Preset #' . $presetUid . ' saved!', 'Presets', ContextualFeedbackSeverity::INFO);
                } else {
                    // Insert new
                    $this->presetRepository->createPreset($inputData);
                    $view->addFlashMessage('New preset "' . $inputData['preset']['title'] . '" is created', 'Presets', ContextualFeedbackSeverity::INFO);
                }
            }
            if (isset($presetAction['delete'])) {
                if ($presetUid > 0) {
                    $this->presetRepository->deletePreset($presetUid);
                    $view->addFlashMessage('Preset #' . $presetUid . ' deleted!', 'Presets', ContextualFeedbackSeverity::INFO);
                } else {
                    $view->addFlashMessage('ERROR: No preset selected for deletion.', 'Presets', ContextualFeedbackSeverity::ERROR);
                }
            }
            if (isset($presetAction['load']) || isset($presetAction['merge'])) {
                if ($presetUid > 0) {
                    $presetData = $this->presetRepository->loadPreset($presetUid);
                    if (isset($presetAction['merge'])) {
                        // Merge records
                        if (is_array($presetData['record'] ?? null)) {
                            $inputData['record'] = array_merge((array)$inputData['record'], $presetData['record']);
                        }
                        // Merge lists
                        if (is_array($presetData['list'] ?? null)) {
                            $inputData['list'] = array_merge((array)$inputData['list'], $presetData['list']);
                        }
                        $view->addFlashMessage('Preset #' . $presetUid . ' merged!', 'Presets', ContextualFeedbackSeverity::INFO);
                    } else {
                        $inputData = $presetData;
                        $view->addFlashMessage('Preset #' . $presetUid . ' loaded!', 'Presets', ContextualFeedbackSeverity::INFO);
                    }
                } else {
                    $view->addFlashMessage('ERROR: No preset selected for loading.', 'Presets', ContextualFeedbackSeverity::ERROR);
                }
            }
        } catch (PresetNotFoundException|InsufficientUserPermissionsException|MalformedPresetException $e) {
            $view->addFlashMessage($e->getMessage(), 'Presets', ContextualFeedbackSeverity::ERROR);
        }
        return $inputData;
    }

    protected function configureExportFromFormData(array $inputData): Export
    {
        $export = GeneralUtility::makeInstance(Export::class);
        $export->setExcludeMap((array)($inputData['exclude'] ?? []));
        $export->setSoftrefCfg((array)($inputData['softrefCfg'] ?? []));
        $export->setExtensionDependencies((($inputData['extension_dep'] ?? '') === '') ? [] : (array)$inputData['extension_dep']);
        $export->setShowStaticRelations((bool)($inputData['showStaticRelations'] ?? false));
        $export->setIncludeExtFileResources(!($inputData['excludeHTMLfileResources'] ?? false));
        $export->setExcludeDisabledRecords((bool)($inputData['excludeDisabled'] ?? false));
        if (!empty($inputData['filetype'])) {
            $export->setExportFileType((string)$inputData['filetype']);
        }
        $export->setExportFileName((string)($inputData['filename'] ?? ''));
        $export->setRelStaticTables((($inputData['external_static']['tables'] ?? '') === '') ? [] : (array)$inputData['external_static']['tables']);
        $export->setRelOnlyTables((($inputData['external_ref']['tables'] ?? '') === '') ? [] : (array)$inputData['external_ref']['tables']);
        if (isset($inputData['save_export'], $inputData['saveFilesOutsideExportFile']) && $inputData['saveFilesOutsideExportFile'] === '1') {
            $export->setSaveFilesOutsideExportFile(true);
        }
        $export->setTitle((string)($inputData['meta']['title'] ?? ''));
        $export->setDescription((string)($inputData['meta']['description'] ?? ''));
        $export->setNotes((string)($inputData['meta']['notes'] ?? ''));
        $export->setRecord((($inputData['record'] ?? '') === '') ? [] : (array)$inputData['record']);
        $export->setList((($inputData['list'] ?? '') === '') ? [] : (array)$inputData['list']);
        if (MathUtility::canBeInterpretedAsInteger($inputData['pagetree']['id'] ?? null)) {
            $export->setPid((int)$inputData['pagetree']['id']);
        }
        if (MathUtility::canBeInterpretedAsInteger($inputData['pagetree']['levels'] ?? null)) {
            $export->setLevels((int)$inputData['pagetree']['levels']);
        }
        $export->setTables((($inputData['pagetree']['tables'] ?? '') === '') ? [] : (array)$inputData['pagetree']['tables']);
        return $export;
    }

    protected function getDownload(Export $export): ResponseInterface
    {
        $fileName = $export->getOrGenerateExportFileNameWithFileExtension();
        $fileContent = $export->render();
        $response = $this->responseFactory->createResponse()
            ->withHeader('Content-Type', 'application/octet-stream')
            ->withHeader('Content-Length', (string)strlen($fileContent))
            ->withHeader('Content-Disposition', 'attachment; filename=' . PathUtility::basename($fileName));
        $response->getBody()->write($export->render());
        return $response;
    }

    protected function saveExportToFile(ModuleTemplate $view, Export $export, Folder $saveFolder): void
    {
        $languageService = $this->getLanguageService();
        try {
            $saveFile = $export->saveToFile();
            $saveFileSize = $saveFile->getProperty('size');
            $view->addFlashMessage(
                sprintf($languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:exportdata_savedInSBytes'), $saveFile->getPublicUrl(), GeneralUtility::formatSize($saveFileSize)),
                $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:exportdata_savedFile')
            );
        } catch (CoreException $e) {
            $view->addFlashMessage(
                sprintf($languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:exportdata_badPathS'), $saveFolder->getPublicUrl()),
                $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:exportdata_problemsSavingFile'),
                ContextualFeedbackSeverity::ERROR
            );
        }
    }

    protected function getPageLevelSelectOptions(array $inputData): array
    {
        $languageService = $this->getLanguageService();
        $options = [];
        if (MathUtility::canBeInterpretedAsInteger($inputData['pagetree']['id'] ?? '')) {
            $options = [
                Export::LEVELS_RECORDS_ON_THIS_PAGE => $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:makeconfig_tablesOnThisPage'),
                Export::LEVELS_EXPANDED_TREE => $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:makeconfig_expandedTree'),
                0 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                Export::LEVELS_INFINITE => $languageService->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ];
        }
        return $options;
    }

    protected function getRecordSelectOptions(array $inputData): array
    {
        $records = [];
        foreach ($inputData['record'] ?? [] as $tableNameColonUid) {
            [$tableName, $recordUid] = explode(':', $tableNameColonUid);
            if ($record = BackendUtility::getRecordWSOL((string)$tableName, (int)$recordUid)) {
                $records[] = [
                    'icon' => $this->iconFactory->getIconForRecord($tableName, $record, Icon::SIZE_SMALL)->render(),
                    'title' => BackendUtility::getRecordTitle($tableName, $record, true),
                    'tableName' => $tableName,
                    'recordUid' => $recordUid,
                ];
            }
        }
        return $records;
    }

    protected function getSelectableTableList(array $inputData): array
    {
        $backendUser = $this->getBackendUser();
        $languageService = $this->getLanguageService();
        $tableList = [];
        foreach ($inputData['list'] ?? [] as $reference) {
            $referenceParts = explode(':', $reference);
            $tableName = $referenceParts[0];
            if ($backendUser->check('tables_select', $tableName)) {
                // If the page is actually the root, handle it differently.
                // NOTE: we don't compare integers, because the number comes from the split string above
                if ($referenceParts[1] === '0') {
                    $iconAndTitle = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
                } else {
                    $record = BackendUtility::getRecordWSOL('pages', (int)$referenceParts[1]);
                    $iconAndTitle = $this->iconFactory->getIconForRecord('pages', $record, Icon::SIZE_SMALL)->render()
                        . BackendUtility::getRecordTitle('pages', $record, true);
                }
                $tableList[] = [
                    'iconAndTitle' => sprintf($languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:makeconfig_tableListEntry'), $tableName, $iconAndTitle),
                    'reference' => $reference,
                ];
            }
        }
        return $tableList;
    }

    protected function getExtensionList(): array
    {
        $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        return array_combine($loadedExtensions, $loadedExtensions);
    }

    protected function getFileSelectOptions(Export $export): array
    {
        $languageService = $this->getLanguageService();
        $fileTypeOptions = [];
        foreach ($export->getSupportedFileTypes() as $supportedFileType) {
            $fileTypeOptions[$supportedFileType] = $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:makesavefo_' . $supportedFileType);
        }
        return $fileTypeOptions;
    }

    /**
     * Get a list of all exportable tables - basically all TCA tables. Blacklist some if wanted.
     * Returned array keys are table names, values are "translations".
     */
    protected function getTableSelectOptions(array $excludeList = []): array
    {
        $languageService = $this->getLanguageService();
        $backendUser = $this->getBackendUser();
        $options = [
            '_ALL' => $languageService->sL('LLL:EXT:impexp/Resources/Private/Language/locallang.xlf:ALL_tables'),
        ];
        $availableTables = array_keys($GLOBALS['TCA']);
        foreach ($availableTables as $table) {
            if (!in_array($table, $excludeList, true) && $backendUser->check('tables_select', $table)) {
                $options[$table] = $table;
            }
        }
        natsort($options);
        return $options;
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
