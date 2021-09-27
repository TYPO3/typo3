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

use Doctrine\DBAL\Driver\Statement;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Routing\Exception\RouteNotFoundException;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryHelper;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Database\Query\Restriction\WorkspaceRestriction;
use TYPO3\CMS\Core\Exception;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\PathUtility;
use TYPO3\CMS\Impexp\Domain\Repository\PresetRepository;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\View\ExportPageTreeView;

/**
 * Main script class for the Export facility
 *
 * @internal this is a TYPO3 Backend controller implementation and not part of TYPO3's Core API.
 */
class ExportController extends ImportExportController
{
    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'tx_impexp_export';

    /**
     * @var Export
     */
    protected $export;

    /**
     * @var string
     */
    protected $treeHTML = '';

    /**
     * @var bool
     */
    protected $excludeDisabledRecords = false;

    /**
     * preset repository
     *
     * @var PresetRepository
     */
    protected $presetRepository;

    public function __construct()
    {
        parent::__construct();

        $this->presetRepository = GeneralUtility::makeInstance(PresetRepository::class);
    }

    /**
     * @param ServerRequestInterface $request
     * @return ResponseInterface
     * @throws Exception
     * @throws ExistingTargetFileNameException
     * @throws RouteNotFoundException
     */
    public function mainAction(ServerRequestInterface $request): ResponseInterface
    {
        $this->lang->includeLLFile('EXT:impexp/Resources/Private/Language/locallang.xlf');

        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        if (is_array($this->pageinfo)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Impexp/ImportExport');
        $this->moduleTemplate->addJavaScriptCode(
            'ImpexpInLineJS',
            'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';'
        );

        // Input data grabbed:
        $inData = $request->getParsedBody()['tx_impexp'] ?? $request->getQueryParams()['tx_impexp'] ?? [];
        if (!array_key_exists('excludeDisabled', $inData)) {
            // flag doesn't exist initially; state is on by default
            $inData['excludeDisabled'] = 1;
        }
        $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
        $this->standaloneView->assign('moduleUrl', (string)$uriBuilder->buildUriFromRoute($this->moduleName));
        $this->standaloneView->assign('id', $this->id);
        $this->standaloneView->assign('inData', $inData);

        $this->shortcutName = $this->lang->getLL('title_export');
        // Call export interface
        $this->exportData($inData);
        $this->standaloneView->setTemplate('Export.html');

        // Setting up the buttons and markers for docheader
        $this->getButtons();

        $this->moduleTemplate->setContent($this->standaloneView->render());
        return new HtmlResponse($this->moduleTemplate->renderContent());
    }

    /**
     * Export part of module
     *
     * @param array $inData
     * @throws ExistingTargetFileNameException
     * @throws Exception
     */
    protected function exportData(array $inData)
    {
        // BUILDING EXPORT DATA:
        // Processing of InData array values:
        $inData['filename'] = trim((string)preg_replace('/[^[:alnum:]._-]*/', '', preg_replace('/\\.(t3d|xml)$/', '', $inData['filename'])));
        if ($inData['filename'] !== '') {
            $inData['filename'] .= $inData['filetype'] === 'xml' ? '.xml' : '.t3d';
        }
        // Set exclude fields in export object:
        if (!is_array($inData['exclude'])) {
            $inData['exclude'] = [];
        }
        // Saving/Loading/Deleting presets:
        $this->presetRepository->processPresets($inData);
        // Create export object and configure it:
        $this->export = GeneralUtility::makeInstance(Export::class);
        $this->export->init(0);
        $this->export->excludeMap = (array)$inData['exclude'];
        $this->export->softrefCfg = (array)$inData['softrefCfg'];
        $this->export->extensionDependencies = ($inData['extension_dep'] === '') ? [] : (array)$inData['extension_dep'];
        $this->export->showStaticRelations = $inData['showStaticRelations'];
        $this->export->includeExtFileResources = !$inData['excludeHTMLfileResources'];
        $this->excludeDisabledRecords = (bool)$inData['excludeDisabled'];
        $this->export->setExcludeDisabledRecords($this->excludeDisabledRecords);

        // Pagetree tables
        if (!is_array($inData['pagetree']['tables'])) {
            $inData['pagetree']['tables'] = [];
        }
        // Static tables:
        if (is_array($inData['external_static']['tables'])) {
            $this->export->relStaticTables = $inData['external_static']['tables'];
        }
        // Configure which tables external relations are included for:
        if (is_array($inData['external_ref']['tables'])) {
            $this->export->relOnlyTables = $inData['external_ref']['tables'];
        }
        $saveFilesOutsideExportFile = false;
        if (isset($inData['save_export'], $inData['saveFilesOutsideExportFile']) && $inData['saveFilesOutsideExportFile'] === '1') {
            $this->export->setSaveFilesOutsideExportFile(true);
            $saveFilesOutsideExportFile = true;
        }
        $this->export->setHeaderBasics();
        // Meta data setting:

        $beUser = $this->getBackendUser();
        $this->export->setMetaData(
            $inData['meta']['title'],
            $inData['meta']['description'],
            $inData['meta']['notes'],
            $beUser->user['username'],
            $beUser->user['realName'],
            $beUser->user['email']
        );
        // Configure which records to export
        if (is_array($inData['record'])) {
            foreach ($inData['record'] as $ref) {
                $rParts = explode(':', $ref);
                $this->export->export_addRecord($rParts[0], BackendUtility::getRecord($rParts[0], (int)$rParts[1]));
            }
        }
        // Configure which tables to export
        if (is_array($inData['list'])) {
            foreach ($inData['list'] as $ref) {
                $rParts = explode(':', $ref);
                if ($beUser->check('tables_select', $rParts[0])) {
                    $statement = $this->exec_listQueryPid($rParts[0], (int)$rParts[1]);
                    while ($subTrow = $statement->fetch()) {
                        $this->export->export_addRecord($rParts[0], $subTrow);
                    }
                }
            }
        }
        // Pagetree
        if (MathUtility::canBeInterpretedAsInteger($inData['pagetree']['id'])) {
            // Based on click-expandable tree
            $idH = null;
            $pid = (int)$inData['pagetree']['id'];
            $levels = (int)$inData['pagetree']['levels'];
            if ($levels === -1) {
                $pagetree = GeneralUtility::makeInstance(ExportPageTreeView::class);
                if ($this->excludeDisabledRecords) {
                    $pagetree->init(BackendUtility::BEenableFields('pages'));
                }
                $tree = $pagetree->ext_tree($pid, $this->filterPageIds($this->export->excludeMap));
                $this->treeHTML = $pagetree->printTree($tree);
                $idH = $pagetree->buffer_idH;
            } elseif ($levels === -2) {
                $this->addRecordsForPid($pid, $inData['pagetree']['tables']);
            } else {
                // Based on depth
                // Drawing tree:
                // If the ID is zero, export root
                if (!$inData['pagetree']['id'] && $beUser->isAdmin()) {
                    $sPage = [
                        'uid' => 0,
                        'title' => 'ROOT'
                    ];
                } else {
                    $sPage = BackendUtility::getRecordWSOL('pages', $pid, '*', ' AND ' . $this->perms_clause);
                }
                if (is_array($sPage)) {
                    $tree = GeneralUtility::makeInstance(PageTreeView::class);
                    $initClause = 'AND ' . $this->perms_clause . $this->filterPageIds($this->export->excludeMap);
                    if ($this->excludeDisabledRecords) {
                        $initClause .= BackendUtility::BEenableFields('pages');
                    }
                    $tree->init($initClause);
                    $HTML = $this->iconFactory->getIconForRecord('pages', $sPage, Icon::SIZE_SMALL)->render();
                    $tree->tree[] = ['row' => $sPage, 'HTML' => $HTML];
                    $tree->buffer_idH = [];
                    if ($levels > 0) {
                        $tree->getTree($pid, $levels);
                    }
                    $idH = [];
                    $idH[$pid]['uid'] = $pid;
                    if (!empty($tree->buffer_idH)) {
                        $idH[$pid]['subrow'] = $tree->buffer_idH;
                    }
                    $pagetree = GeneralUtility::makeInstance(ExportPageTreeView::class);
                    $this->treeHTML = $pagetree->printTree($tree->tree);
                    $this->shortcutName .= ' (' . $sPage['title'] . ')';
                }
            }
            // In any case we should have a multi-level array, $idH, with the page structure
            // here (and the HTML-code loaded into memory for nice display...)
            if (is_array($idH)) {
                // Sets the pagetree and gets a 1-dim array in return with the pages (in correct submission order BTW...)
                $flatList = $this->export->setPageTree($idH);
                foreach ($flatList as $k => $value) {
                    $this->export->export_addRecord('pages', BackendUtility::getRecord('pages', $k));
                    $this->addRecordsForPid((int)$k, $inData['pagetree']['tables']);
                }
            }
        }
        // After adding ALL records we set relations:
        for ($a = 0; $a < 10; $a++) {
            $addR = $this->export->export_addDBRelations($a);
            if (empty($addR)) {
                break;
            }
        }
        // Finally files are added:
        // MUST be after the DBrelations are set so that files from ALL added records are included!
        $this->export->export_addFilesFromRelations();

        $this->export->export_addFilesFromSysFilesRecords();

        // If the download button is clicked, return file
        if ($inData['download_export'] || $inData['save_export']) {
            switch ($inData['filetype']) {
                case 'xml':
                    $out = $this->export->compileMemoryToFileContent('xml');
                    $fExt = '.xml';
                    break;
                case 't3d':
                    $this->export->dontCompress = 1;
                    // intentional fall-through
                    // no break
                default:
                    $out = $this->export->compileMemoryToFileContent();
                    $fExt = ($this->export->doOutputCompress() ? '-z' : '') . '.t3d';
            }
            // Filename:
            $dlFile = $inData['filename'];
            if (!$dlFile) {
                $exportName = substr(preg_replace('/[^[:alnum:]_]/', '-', $inData['download_export_name']), 0, 20);
                $dlFile = 'T3D_' . $exportName . '_' . date('Y-m-d_H-i') . $fExt;
            }

            // Export for download:
            if ($inData['download_export']) {
                $mimeType = 'application/octet-stream';
                header('Content-Type: ' . $mimeType);
                header('Content-Length: ' . strlen($out));
                header('Content-Disposition: attachment; filename=' . PathUtility::basename($dlFile));
                echo $out;
                die;
            }

            // Export by saving:
            if ($inData['save_export']) {
                $saveFolder = $this->getDefaultImportExportFolder();
                $lang = $this->getLanguageService();
                if ($saveFolder instanceof Folder && $saveFolder->checkActionPermission('write')) {
                    $temporaryFileName = GeneralUtility::tempnam('export');
                    GeneralUtility::writeFile($temporaryFileName, $out);
                    $file = $saveFolder->addFile($temporaryFileName, $dlFile, 'replace');
                    if ($saveFilesOutsideExportFile) {
                        $filesFolderName = $dlFile . '.files';
                        $filesFolder = $saveFolder->createFolder($filesFolderName);
                        $temporaryFilesForExport = GeneralUtility::getFilesInDir($this->export->getTemporaryFilesPathForExport(), '', true);
                        foreach ($temporaryFilesForExport as $temporaryFileForExport) {
                            $filesFolder->addFile($temporaryFileForExport);
                            GeneralUtility::unlink_tempfile($temporaryFileForExport);
                        }
                        GeneralUtility::rmdir($this->export->getTemporaryFilesPathForExport());
                    }

                    /** @var FlashMessage $flashMessage */
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($lang->getLL('exportdata_savedInSBytes'), $file->getPublicUrl(), GeneralUtility::formatSize(strlen($out))),
                        $lang->getLL('exportdata_savedFile'),
                        FlashMessage::OK
                    );
                } else {
                    /** @var FlashMessage $flashMessage */
                    $flashMessage = GeneralUtility::makeInstance(
                        FlashMessage::class,
                        sprintf($lang->getLL('exportdata_badPathS'), $saveFolder->getPublicUrl()),
                        $lang->getLL('exportdata_problemsSavingFile'),
                        FlashMessage::ERROR
                    );
                }
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }

        $this->makeConfigurationForm($inData);

        $this->makeSaveForm($inData);

        $this->makeAdvancedOptionsForm($inData);

        $this->standaloneView->assign('errors', $this->export->errorLog);

        // Generate overview:
        $this->standaloneView->assign(
            'contentOverview',
            $this->export->displayContentOverview()
        );
    }

    /**
     * Adds records to the export object for a specific page id.
     *
     * @param int $k Page id for which to select records to add
     * @param array $tables Array of table names to select from
     */
    protected function addRecordsForPid(int $k, array $tables): void
    {
        foreach ($GLOBALS['TCA'] as $table => $value) {
            if ($table !== 'pages'
                && (in_array($table, $tables, true) || in_array('_ALL', $tables, true))
                && $this->getBackendUser()->check('tables_select', $table)
                && !$GLOBALS['TCA'][$table]['ctrl']['is_static']
            ) {
                $statement = $this->exec_listQueryPid($table, $k);
                while ($subTrow = $statement->fetch()) {
                    $this->export->export_addRecord($table, $subTrow);
                }
            }
        }
    }

    /**
     * Selects records from table / pid
     *
     * @param string $table Table to select from
     * @param int $pid Page ID to select from
     * @return Statement Query statement
     */
    protected function exec_listQueryPid(string $table, int $pid): Statement
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable($table);

        $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby'] ?: $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
        $queryBuilder->getRestrictions()->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));

        if ($this->excludeDisabledRecords === false) {
            $queryBuilder->getRestrictions()
                ->removeAll()
                ->add(GeneralUtility::makeInstance(DeletedRestriction::class))
                ->add(GeneralUtility::makeInstance(WorkspaceRestriction::class, 0));
        }

        $queryBuilder->select('*')
            ->from($table)
            ->where(
                $queryBuilder->expr()->eq(
                    'pid',
                    $queryBuilder->createNamedParameter($pid, \PDO::PARAM_INT)
                )
            );

        foreach (QueryHelper::parseOrderBy((string)$orderBy) as $orderPair) {
            [$fieldName, $order] = $orderPair;
            $queryBuilder->addOrderBy($fieldName, $order);
        }

        return $queryBuilder->execute();
    }

    /**
     * Create configuration form
     *
     * @param array $inData Form configuration data
     */
    protected function makeConfigurationForm(array $inData): void
    {
        $nameSuggestion = '';
        // Page tree export options:
        if (MathUtility::canBeInterpretedAsInteger($inData['pagetree']['id'])) {
            $this->standaloneView->assign('treeHTML', $this->treeHTML);

            $opt = [
                -2 => $this->lang->getLL('makeconfig_tablesOnThisPage'),
                -1 => $this->lang->getLL('makeconfig_expandedTree'),
                0 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
                1 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
                2 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
                3 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
                4 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
                999 => $this->lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
            ];
            $this->standaloneView->assign('levelSelectOptions', $opt);
            $this->standaloneView->assign('tableSelectOptions', $this->getTableSelectOptions('pages'));
            $nameSuggestion .= 'tree_PID' . $inData['pagetree']['id'] . '_L' . $inData['pagetree']['levels'];
        }
        // Single record export:
        if (is_array($inData['record'])) {
            $records = [];
            foreach ($inData['record'] as $ref) {
                $rParts = explode(':', $ref);
                [$tName, $rUid] = $rParts;
                $nameSuggestion .= $tName . '_' . $rUid;
                $rec = BackendUtility::getRecordWSOL((string)$tName, (int)$rUid);
                if (!empty($rec)) {
                    $records[] = [
                        'icon' => $this->iconFactory->getIconForRecord($tName, $rec, Icon::SIZE_SMALL)->render(),
                        'title' => BackendUtility::getRecordTitle($tName, $rec, true),
                        'tableName' => $tName,
                        'recordUid' => $rUid
                    ];
                }
            }
            $this->standaloneView->assign('records', $records);
        }

        // Single tables/pids:
        if (is_array($inData['list'])) {
            // Display information about pages from which the export takes place
            $tableList = [];
            foreach ($inData['list'] as $reference) {
                $referenceParts = explode(':', $reference);
                $tableName = $referenceParts[0];
                if ($this->getBackendUser()->check('tables_select', $tableName)) {
                    // If the page is actually the root, handle it differently
                    // NOTE: we don't compare integers, because the number actually comes from the split string above
                    if ($referenceParts[1] === '0') {
                        $iconAndTitle = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
                    } else {
                        $record = BackendUtility::getRecordWSOL('pages', (int)$referenceParts[1]);
                        $iconAndTitle = $this->iconFactory->getIconForRecord('pages', $record, Icon::SIZE_SMALL)->render()
                            . BackendUtility::getRecordTitle('pages', $record, true);
                    }

                    $tableList[] = [
                        'iconAndTitle' => sprintf($this->lang->getLL('makeconfig_tableListEntry'), $tableName, $iconAndTitle),
                        'reference' => $reference
                    ];
                }
            }
            $this->standaloneView->assign('tableList', $tableList);
        }

        $this->standaloneView->assign('externalReferenceTableSelectOptions', $this->getTableSelectOptions());
        $this->standaloneView->assign('externalStaticTableSelectOptions', $this->getTableSelectOptions());
        $this->standaloneView->assign('nameSuggestion', $nameSuggestion);
    }

    /**
     * Create advanced options form
     *
     * @param array $inData Form configuration data
     */
    protected function makeAdvancedOptionsForm(array $inData): void
    {
        $loadedExtensions = ExtensionManagementUtility::getLoadedExtensionListArray();
        $loadedExtensions = array_combine($loadedExtensions, $loadedExtensions);
        $this->standaloneView->assign('extensions', $loadedExtensions);
        $this->standaloneView->assign('inData', $inData);
    }

    /**
     * Create configuration form
     *
     * @param array $inData Form configuration data
     */
    protected function makeSaveForm(array $inData): void
    {
        $opt = $this->presetRepository->getPresets((int)$inData['pagetree']['id']);

        $this->standaloneView->assign('presetSelectOptions', $opt);

        $saveFolder = $this->getDefaultImportExportFolder();
        if ($saveFolder) {
            $this->standaloneView->assign('saveFolder', $saveFolder->getCombinedIdentifier());
        }

        // Add file options:
        $opt = [];
        $opt['xml'] = $this->lang->getLL('makesavefo_xml');
        if ($this->export->compress) {
            $opt['t3d_compressed'] = $this->lang->getLL('makesavefo_t3dFileCompressed');
        }
        $opt['t3d'] = $this->lang->getLL('makesavefo_t3dFile');

        $this->standaloneView->assign('filetypeSelectOptions', $opt);

        $fileName = '';
        if ($saveFolder) {
            $this->standaloneView->assign('saveFolder', $saveFolder->getPublicUrl());
            $this->standaloneView->assign('hasSaveFolder', true);
        }
        $this->standaloneView->assign('fileName', $fileName);
    }

    /**
     * Returns option array to be used in Fluid
     *
     * @param string $excludeList Table names (and the string "_ALL") to exclude. Comma list
     * @return array
     */
    protected function getTableSelectOptions(string $excludeList = ''): array
    {
        $optValues = [];
        if (!GeneralUtility::inList($excludeList, '_ALL')) {
            $optValues['_ALL'] = '[' . $this->lang->getLL('ALL_tables') . ']';
        }
        foreach ($GLOBALS['TCA'] as $table => $_) {
            if (!GeneralUtility::inList($excludeList, $table) && $this->getBackendUser()->check('tables_select', $table)) {
                $optValues[$table] = $table;
            }
        }
        natsort($optValues);
        return $optValues;
    }

    /**
     * Filter page IDs by traversing exclude array, finding all
     * excluded pages (if any) and making an AND NOT IN statement for the select clause.
     *
     * @param array $exclude Exclude array from import/export object.
     * @return string AND where clause part to filter out page uids.
     */
    protected function filterPageIds(array $exclude): string
    {
        // Get keys:
        $exclude = array_keys($exclude);
        // Traverse
        $pageIds = [];
        foreach ($exclude as $element) {
            [$table, $uid] = explode(':', $element);
            if ($table === 'pages') {
                $pageIds[] = (int)$uid;
            }
        }
        // Add to clause:
        if (!empty($pageIds)) {
            return ' AND uid NOT IN (' . implode(',', $pageIds) . ')';
        }
        return '';
    }

    /**
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
