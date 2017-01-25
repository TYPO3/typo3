<?php
namespace TYPO3\CMS\Impexp\Controller;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Module\BaseScriptClass;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Tree\View\PageTreeView;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Filter\FileExtensionFilter;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3\CMS\Impexp\Domain\Repository\PresetRepository;
use TYPO3\CMS\Impexp\Export;
use TYPO3\CMS\Impexp\Import;
use TYPO3\CMS\Impexp\View\ExportPageTreeView;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Main script class for the Import / Export facility
 */
class ImportExportController extends BaseScriptClass
{
    /**
     * @var array|\TYPO3\CMS\Core\Resource\File[]
     */
    protected $uploadedFiles = [];

    /**
     * Array containing the current page.
     *
     * @var array
     */
    public $pageinfo;

    /**
     * @var Export
     */
    protected $export;

    /**
     * @var Import
     */
    protected $import;

    /**
     * @var ExtendedFileUtility
     */
    protected $fileProcessor;

    /**
     * @var string
     */
    protected $vC = '';

    /**
     * @var LanguageService
     */
    protected $lang = null;

    /**
     * @var string
     */
    protected $treeHTML = '';

    /**
     * @var IconFactory
     */
    protected $iconFactory;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'xMOD_tximpexp';

    /**
     * ModuleTemplate Container
     *
     * @var ModuleTemplate
     */
    protected $moduleTemplate;

    /**
     *  The name of the shortcut for this page
     *
     * @var string
     */
    protected $shortcutName;

    /**
     * preset repository
     *
     * @var PresetRepository
     */
    protected $presetRepository;

    /**
     * @var StandaloneView
     */
    protected $standaloneView = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
        $this->moduleTemplate = GeneralUtility::makeInstance(ModuleTemplate::class);
        $this->presetRepository = GeneralUtility::makeInstance(PresetRepository::class);

        $templatePath = ExtensionManagementUtility::extPath('impexp') . 'Resources/Private/';

        /* @var $view StandaloneView */
        $this->standaloneView = GeneralUtility::makeInstance(StandaloneView::class);
        $this->standaloneView->setTemplateRootPaths([$templatePath . 'Templates/ImportExport/']);
        $this->standaloneView->setLayoutRootPaths([$templatePath . 'Layouts/']);
        $this->standaloneView->setPartialRootPaths([$templatePath . 'Partials/']);
        $this->standaloneView->getRequest()->setControllerExtensionName('impexp');
    }

    /**
     * @return void
     */
    public function init()
    {
        $this->MCONF['name'] = $this->moduleName;
        parent::init();
        $this->vC = GeneralUtility::_GP('vC');
        $this->lang = $this->getLanguageService();
    }

    /**
     * Main module function
     *
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return void
     */
    public function main()
    {
        $this->lang->includeLLFile('EXT:impexp/Resources/Private/Language/locallang.xlf');

        // Start document template object:
        // We keep this here, in case somebody relies on the old doc being here
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->doc->bodyTagId = 'imp-exp-mod';
        $this->pageinfo = BackendUtility::readPageAccess($this->id, $this->perms_clause);
        if (is_array($this->pageinfo)) {
            $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation($this->pageinfo);
        }
        // Setting up the context sensitive menu:
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->moduleTemplate->getPageRenderer()->loadRequireJsModule('TYPO3/CMS/Impexp/ImportExport');
        $this->moduleTemplate->addJavaScriptCode(
            'ImpexpInLineJS',
            'if (top.fsMod) top.fsMod.recentIds["web"] = ' . (int)$this->id . ';'
        );

        // Input data grabbed:
        $inData = GeneralUtility::_GP('tx_impexp');
        $this->standaloneView->assign('moduleUrl', BackendUtility::getModuleUrl('xMOD_tximpexp'));
        $this->standaloneView->assign('id', $this->id);
        $this->standaloneView->assign('inData', $inData);

        switch ((string)$inData['action']) {
            case 'export':
                $this->shortcutName = $this->lang->getLL('title_export');
                // Call export interface
                $this->exportData($inData);
                $this->standaloneView->setTemplate('Export.html');
                break;
            case 'import':
                $backendUser = $this->getBackendUser();
                $isEnabledForNonAdmin = $backendUser->getTSConfig('options.impexp.enableImportForNonAdminUser');
                if (!$backendUser->isAdmin() && empty($isEnabledForNonAdmin['value'])) {
                    throw new \RuntimeException(
                        'Import module is disabled for non admin users and '
                        . 'userTsConfig options.impexp.enableImportForNonAdminUser is not enabled.',
                        1464435459
                    );
                }
                $this->shortcutName = $this->lang->getLL('title_import');
                if (GeneralUtility::_POST('_upload')) {
                    $this->checkUpload();
                }
                // Finally: If upload went well, set the new file as the import file:
                if (!empty($this->uploadedFiles[0])) {
                    // Only allowed extensions....
                    $extension = $this->uploadedFiles[0]->getExtension();
                    if ($extension === 't3d' || $extension === 'xml') {
                        $inData['file'] = $this->uploadedFiles[0]->getCombinedIdentifier();
                    }
                }
                // Call import interface:
                $this->importData($inData);
                $this->standaloneView->setTemplate('Import.html');
                break;
        }

        // Setting up the buttons and markers for docheader
        $this->getButtons();
    }

    /**
     * Print the content
     *
     * @return void
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
     */
    public function printContent()
    {
        GeneralUtility::logDeprecatedFunction();
        echo $this->content;
    }

    /**
     * Injects the request object for the current request and gathers all data
     *
     * IMPORTING DATA:
     *
     * Incoming array has syntax:
     * GETvar 'id' = import page id (must be readable)
     *
     * file = 	(pointing to filename relative to PATH_site)
     *
     * [all relation fields are clear, but not files]
     * - page-tree is written first
     * - then remaining pages (to the root of import)
     * - then all other records are written either to related included pages or if not found to import-root (should be a sysFolder in most cases)
     * - then all internal relations are set and non-existing relations removed, relations to static tables preserved.
     *
     * EXPORTING DATA:
     *
     * Incoming array has syntax:
     *
     * file[] = file
     * dir[] = dir
     * list[] = table:pid
     * record[] = table:uid
     *
     * pagetree[id] = (single id)
     * pagetree[levels]=1,2,3, -1 = currently unpacked tree, -2 = only tables on page
     * pagetree[tables][]=table/_ALL
     *
     * external_ref[tables][]=table/_ALL
     *
     * @param ServerRequestInterface $request the current request
     * @param ResponseInterface $response
     * @return ResponseInterface the response with the content
     */
    public function mainAction(ServerRequestInterface $request, ResponseInterface $response)
    {
        $GLOBALS['SOBE'] = $this;
        $this->init();
        $this->main();
        $this->moduleTemplate->setContent($this->standaloneView->render());
        $response->getBody()->write($this->moduleTemplate->renderContent());

        return $response;
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array all available buttons as an associated array
     */
    protected function getButtons()
    {
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortcutButton = $buttonBar->makeShortcutButton()
                ->setGetVariables(['tx_impexp'])
                ->setDisplayName($this->shortcutName)
                ->setModuleName($this->moduleName);
            $buttonBar->addButton($shortcutButton);
        }
        // Input data grabbed:
        $inData = GeneralUtility::_GP('tx_impexp');
        if ((string)$inData['action'] == 'import') {
            if ($this->id && is_array($this->pageinfo) || $this->getBackendUser()->user['admin'] && !$this->id) {
                if (is_array($this->pageinfo) && $this->pageinfo['uid']) {
                    // View
                    $onClick = BackendUtility::viewOnClick(
                        $this->pageinfo['uid'],
                        '',
                        BackendUtility::BEgetRootLine($this->pageinfo['uid'])
                    );
                    $viewButton = $buttonBar->makeLinkButton()
                        ->setTitle($this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.showPage'))
                        ->setHref('#')
                        ->setIcon($this->iconFactory->getIcon('actions-document-view', Icon::SIZE_SMALL))
                        ->setOnClick($onClick);
                    $buttonBar->addButton($viewButton);
                }
            }
        }
    }

    /**************************
     * EXPORT FUNCTIONS
     **************************/

    /**
     * Export part of module
     * Setting content in $this->content
     *
     * @param array $inData Content of POST VAR tx_impexp[]..
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @throws \TYPO3\CMS\Core\Resource\Exception\ExistingTargetFileNameException
     * @return void
     */
    public function exportData($inData)
    {
        // BUILDING EXPORT DATA:
        // Processing of InData array values:
        $inData['pagetree']['maxNumber'] = MathUtility::forceIntegerInRange($inData['pagetree']['maxNumber'], 1, 1000000, 100);
        $inData['listCfg']['maxNumber'] = MathUtility::forceIntegerInRange($inData['listCfg']['maxNumber'], 1, 1000000, 100);
        $inData['maxFileSize'] = MathUtility::forceIntegerInRange($inData['maxFileSize'], 1, 1000000, 1000);
        $inData['filename'] = trim(preg_replace('/[^[:alnum:]._-]*/', '', preg_replace('/\\.(t3d|xml)$/', '', $inData['filename'])));
        if (strlen($inData['filename'])) {
            $inData['filename'] .= $inData['filetype'] == 'xml' ? '.xml' : '.t3d';
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
        $this->export->setCharset($this->lang->charSet);
        $this->export->maxFileSize = $inData['maxFileSize'] * 1024;
        $this->export->excludeMap = (array)$inData['exclude'];
        $this->export->softrefCfg = (array)$inData['softrefCfg'];
        $this->export->extensionDependencies = ($inData['extension_dep'] === '') ? [] : (array)$inData['extension_dep'];
        $this->export->showStaticRelations = $inData['showStaticRelations'];
        $this->export->includeExtFileResources = !$inData['excludeHTMLfileResources'];

        // Static tables:
        if (is_array($inData['external_static']['tables'])) {
            $this->export->relStaticTables = $inData['external_static']['tables'];
        }
        // Configure which tables external relations are included for:
        if (is_array($inData['external_ref']['tables'])) {
            $this->export->relOnlyTables = $inData['external_ref']['tables'];
        }
        $saveFilesOutsideExportFile = false;
        if (isset($inData['save_export']) && isset($inData['saveFilesOutsideExportFile']) && $inData['saveFilesOutsideExportFile'] === '1') {
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
                $this->export->export_addRecord($rParts[0], BackendUtility::getRecord($rParts[0], $rParts[1]));
            }
        }
        // Configure which tables to export
        if (is_array($inData['list'])) {
            $db = $this->getDatabaseConnection();
            foreach ($inData['list'] as $ref) {
                $rParts = explode(':', $ref);
                if ($beUser->check('tables_select', $rParts[0])) {
                    $res = $this->exec_listQueryPid($rParts[0], $rParts[1], MathUtility::forceIntegerInRange($inData['listCfg']['maxNumber'], 1));
                    while ($subTrow = $db->sql_fetch_assoc($res)) {
                        $this->export->export_addRecord($rParts[0], $subTrow);
                    }
                    $db->sql_free_result($res);
                }
            }
        }
        // Pagetree
        if (isset($inData['pagetree']['id'])) {
            // Based on click-expandable tree
            $idH = null;
            if ($inData['pagetree']['levels'] == -1) {
                $pagetree = GeneralUtility::makeInstance(ExportPageTreeView::class);
                $tree = $pagetree->ext_tree($inData['pagetree']['id'], $this->filterPageIds($this->export->excludeMap));
                $this->treeHTML = $pagetree->printTree($tree);
                $idH = $pagetree->buffer_idH;
            } elseif ($inData['pagetree']['levels'] == -2) {
                $this->addRecordsForPid($inData['pagetree']['id'], $inData['pagetree']['tables'], $inData['pagetree']['maxNumber']);
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
                    $sPage = BackendUtility::getRecordWSOL('pages', $inData['pagetree']['id'], '*', ' AND ' . $this->perms_clause);
                }
                if (is_array($sPage)) {
                    $pid = $inData['pagetree']['id'];
                    $tree = GeneralUtility::makeInstance(PageTreeView::class);
                    $tree->init('AND ' . $this->perms_clause . $this->filterPageIds($this->export->excludeMap));
                    $HTML = $this->iconFactory->getIconForRecord('pages', $sPage, Icon::SIZE_SMALL)->render();
                    $tree->tree[] = ['row' => $sPage, 'HTML' => $HTML];
                    $tree->buffer_idH = [];
                    if ($inData['pagetree']['levels'] > 0) {
                        $tree->getTree($pid, $inData['pagetree']['levels'], '');
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
                    $this->addRecordsForPid($k, $inData['pagetree']['tables'], $inData['pagetree']['maxNumber']);
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
            switch ((string)$inData['filetype']) {
                case 'xml':
                    $out = $this->export->compileMemoryToFileContent('xml');
                    $fExt = '.xml';
                    break;
                case 't3d':
                    $this->export->dontCompress = 1;
                    // intentional fall-through
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
                header('Content-Disposition: attachment; filename=' . basename($dlFile));
                echo $out;
                die;
            }
            // Export by saving:
            if ($inData['save_export']) {
                $saveFolder = $this->getDefaultImportExportFolder();
                $lang = $this->getLanguageService();
                if ($saveFolder !== false && $saveFolder->checkActionPermission('write')) {
                    $temporaryFileName = GeneralUtility::tempnam('export');
                    file_put_contents($temporaryFileName, $out);
                    $file = $saveFolder->addFile($temporaryFileName, $dlFile, 'replace');
                    if ($saveFilesOutsideExportFile) {
                        $filesFolderName = $dlFile . '.files';
                        $filesFolder = $saveFolder->createFolder($filesFolderName);
                        $temporaryFolderForExport = ResourceFactory::getInstance()->retrieveFileOrFolderObject($this->export->getTemporaryFilesPathForExport());
                        $temporaryFilesForExport = $temporaryFolderForExport->getFiles();
                        foreach ($temporaryFilesForExport as $temporaryFileForExport) {
                            $filesFolder->getStorage()->moveFile($temporaryFileForExport, $filesFolder);
                        }
                        $temporaryFolderForExport->delete();
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
                /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
                $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
                /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
                $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
                $defaultFlashMessageQueue->enqueue($flashMessage);
            }
        }

        $this->makeConfigurationForm($inData);

        $this->makeSaveForm($inData);

        $this->makeAdvancedOptionsForm($inData);

        // Print errors that might be:
        $errors = $this->export->printErrorLog();
        $this->standaloneView->assign('errors', trim($errors));

        // Generate overview:
        $this->standaloneView->assign('contentOverview', $this->export->displayContentOverview());
    }

    /**
     * Adds records to the export object for a specific page id.
     *
     * @param int $k Page id for which to select records to add
     * @param array $tables Array of table names to select from
     * @param int $maxNumber Max amount of records to select
     * @return void
     */
    public function addRecordsForPid($k, $tables, $maxNumber)
    {
        if (!is_array($tables)) {
            return;
        }
        $db = $this->getDatabaseConnection();
        foreach ($GLOBALS['TCA'] as $table => $value) {
            if ($table != 'pages' && (in_array($table, $tables) || in_array('_ALL', $tables))) {
                if ($this->getBackendUser()->check('tables_select', $table) && !$GLOBALS['TCA'][$table]['ctrl']['is_static']) {
                    $res = $this->exec_listQueryPid($table, $k, MathUtility::forceIntegerInRange($maxNumber, 1));
                    while ($subTrow = $db->sql_fetch_assoc($res)) {
                        $this->export->export_addRecord($table, $subTrow);
                    }
                    $db->sql_free_result($res);
                }
            }
        }
    }

    /**
     * Selects records from table / pid
     *
     * @param string $table Table to select from
     * @param int $pid Page ID to select from
     * @param int $limit Max number of records to select
     * @return \mysqli_result|object Database resource
     */
    public function exec_listQueryPid($table, $pid, $limit)
    {
        $db = $this->getDatabaseConnection();
        $orderBy = $GLOBALS['TCA'][$table]['ctrl']['sortby']
            ? 'ORDER BY ' . $GLOBALS['TCA'][$table]['ctrl']['sortby']
            : $GLOBALS['TCA'][$table]['ctrl']['default_sortby'];
        $res = $db->exec_SELECTquery(
            '*',
            $table,
            'pid=' . (int)$pid . BackendUtility::deleteClause($table) . BackendUtility::versioningPlaceholderClause($table),
            '',
            $db->stripOrderBy($orderBy),
            $limit
        );
        // Warning about hitting limit:
        if ($db->sql_num_rows($res) == $limit) {
            $limitWarning = sprintf($this->lang->getLL('makeconfig_anSqlQueryReturned'), $limit);
            /** @var FlashMessage $flashMessage */
            $flashMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $this->lang->getLL('execlistqu_maxNumberLimit'),
                $limitWarning,
                FlashMessage::WARNING
            );
            /** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
            $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
            /** @var $defaultFlashMessageQueue \TYPO3\CMS\Core\Messaging\FlashMessageQueue */
            $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
            $defaultFlashMessageQueue->enqueue($flashMessage);
        }
        return $res;
    }

    /**
     * Create configuration form
     *
     * @param array $inData Form configuration data
     * @return void
     */
    public function makeConfigurationForm($inData)
    {
        $nameSuggestion = '';
        // Page tree export options:
        if (isset($inData['pagetree']['id'])) {
            $this->standaloneView->assign('treeHTML', $this->treeHTML);

            $opt = [
                '-2' => $this->lang->getLL('makeconfig_tablesOnThisPage'),
                '-1' => $this->lang->getLL('makeconfig_expandedTree'),
                '0' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
                '1' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
                '2' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
                '3' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
                '4' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
                '999' => $this->lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi'),
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
                $tName = $rParts[0];
                $rUid = $rParts[1];
                $nameSuggestion .= $tName . '_' . $rUid;
                $rec = BackendUtility::getRecordWSOL($tName, $rUid);
                if (!empty($rec)) {
                    $records[] = '
					<tr class="bgColor4">
						<td><strong>' . $this->lang->getLL('makeconfig_record', true) . '</strong></td>
						<td>' . $this->iconFactory->getIconForRecord($tName, $rec, Icon::SIZE_SMALL)->render() . BackendUtility::getRecordTitle($tName, $rec, true)
                            . '<input type="hidden" name="tx_impexp[record][]" value="' . htmlspecialchars(($tName . ':' . $rUid)) . '" /></td>
					</tr>';
                }
            }
            $this->standaloneView->assign('records', implode('', $records));
        }
        // Single tables/pids:
        if (is_array($inData['list'])) {

            // Display information about pages from which the export takes place
            $tblList = '';
            foreach ($inData['list'] as $reference) {
                $referenceParts = explode(':', $reference);
                $tableName = $referenceParts[0];
                if ($this->getBackendUser()->check('tables_select', $tableName)) {
                    // If the page is actually the root, handle it differently
                    // NOTE: we don't compare integers, because the number actually comes from the split string above
                    if ($referenceParts[1] === '0') {
                        $iconAndTitle = $this->iconFactory->getIcon('apps-pagetree-root', Icon::SIZE_SMALL)->render() . $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'];
                    } else {
                        $record = BackendUtility::getRecordWSOL('pages', $referenceParts[1]);
                        $iconAndTitle = $this->iconFactory->getIconForRecord('pages', $record, Icon::SIZE_SMALL)->render()
                            . BackendUtility::getRecordTitle('pages', $record, true);
                    }
                    $tblList .= 'Table "' . $tableName . '" from ' . $iconAndTitle
                        . '<input type="hidden" name="tx_impexp[list][]" value="' . htmlspecialchars($reference) . '" /><br/>';
                }
            }
            $this->standaloneView->assign('tableList', $tblList);
        }

        $this->standaloneView->assign('externalReferenceTableSelectOptions', $this->getTableSelectOptions());
        $this->standaloneView->assign('externalStaticTableSelectOptions', $this->getTableSelectOptions());

        // Exclude:
        $excludeHiddenFields = '';
        if (is_array($inData['exclude'])) {
            foreach ($inData['exclude'] as $key => $value) {
                $excludeHiddenFields .= '<input type="hidden" name="tx_impexp[exclude][' . $key . ']" value="1" />';
            }
            $this->standaloneView->assign('excludedKeys', implode(', ', array_keys($inData['exclude'])));
        }
        $this->standaloneView->assign('nameSuggestion', $nameSuggestion);
    }

    /**
     * Create advanced options form
     * Sets content in $this->content
     *
     * @param array $inData Form configurat data
     * @return void
     */
    public function makeAdvancedOptionsForm($inData)
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
     * @return void
     */
    public function makeSaveForm($inData)
    {

        // Presets:
        $opt = [''];
        $where = '(public>0 OR user_uid=' . (int)$this->getBackendUser()->user['uid'] . ')'
            . ($inData['pagetree']['id'] ? ' AND (item_uid=' . (int)$inData['pagetree']['id'] . ' OR item_uid=0)' : '');
        $presets = $this->getDatabaseConnection()->exec_SELECTgetRows('*', 'tx_impexp_presets', $where);
        if (is_array($presets)) {
            foreach ($presets as $presetCfg) {
                $opt[$presetCfg['uid']] = $presetCfg['title'] . ' [' . $presetCfg['uid'] . ']'
                    . ($presetCfg['public'] ? ' [Public]' : '')
                    . ($presetCfg['user_uid'] === $this->getBackendUser()->user['uid'] ? ' [Own]' : '');
            }
        }

        $this->standaloneView->assign('presetSelectOptions', $opt);

        $saveFolder = $this->getDefaultImportExportFolder();
        if ($saveFolder) {
            $this->standaloneView->assign('saveFolder', $saveFolder->getCombinedIdentifier());
        }

        // Add file options:
        $opt = [];
        if ($this->export->compress) {
            $opt['t3d_compressed'] = $this->lang->getLL('makesavefo_t3dFileCompressed');
        }
        $opt['t3d'] = $this->lang->getLL('makesavefo_t3dFile');
        $opt['xml'] = $this->lang->getLL('makesavefo_xml');

        $this->standaloneView->assign('filetypeSelectOptions', $opt);

        $fileName = '';
        if ($saveFolder) {
            $fileName = sprintf($this->lang->getLL('makesavefo_filenameSavedInS', true), $saveFolder->getPublicUrl())
                . '<br/>
						<input type="text" name="tx_impexp[filename]" value="'
                . htmlspecialchars($inData['filename']) . '" /><br/>';

            $this->standaloneView->assign('hasSaveFolder', true);
        }
        $this->standaloneView->assign('fileName', $fileName);
    }

    /**************************
     * IMPORT FUNCTIONS
     **************************/

    /**
     * Import part of module
     *
     * @param array $inData Content of POST VAR tx_impexp[]..
     * @throws \BadFunctionCallException
     * @throws \InvalidArgumentException
     * @throws \RuntimeException
     * @return void Setting content in $this->content
     */
    public function importData($inData)
    {
        $access = is_array($this->pageinfo) ? 1 : 0;
        $beUser = $this->getBackendUser();
        if ($this->id && $access || $beUser->user['admin'] && !$this->id) {
            if ($beUser->user['admin'] && !$this->id) {
                $this->pageinfo = ['title' => '[root-level]', 'uid' => 0, 'pid' => 0];
            }
            if ($inData['new_import']) {
                unset($inData['import_mode']);
            }
            /** @var $import Import */
            $import = GeneralUtility::makeInstance(Import::class);
            $import->init();
            $import->update = $inData['do_update'];
            $import->import_mode = $inData['import_mode'];
            $import->enableLogging = $inData['enableLogging'];
            $import->global_ignore_pid = $inData['global_ignore_pid'];
            $import->force_all_UIDS = $inData['force_all_UIDS'];
            $import->showDiff = !$inData['notShowDiff'];
            $import->allowPHPScripts = $inData['allowPHPScripts'];
            $import->softrefInputValues = $inData['softrefInputValues'];

            // OUTPUT creation:

            // Make input selector:
            // must have trailing slash.
            $path = $this->getDefaultImportExportFolder();
            $exportFiles = $this->getExportFiles();

            $this->shortcutName .= ' (' . $this->pageinfo['title'] . ')';

            // Configuration
            $selectOptions = [''];
            foreach ($exportFiles as $file) {
                $selectOptions[$file->getCombinedIdentifier()] = $file->getPublicUrl();
            }

            $this->standaloneView->assign('import', $import);
            $this->standaloneView->assign('inData', $inData);
            $this->standaloneView->assign('fileSelectOptions', $selectOptions);

            if ($path) {
                $this->standaloneView->assign('importPath', sprintf($this->lang->getLL('importdata_fromPathS', true), $path->getCombinedIdentifier()));
            } else {
                $this->standaloneView->assign('importPath', $this->lang->getLL('importdata_no_default_upload_folder', true));
            }
            $this->standaloneView->assign('isAdmin', $beUser->isAdmin());

            // Upload file:
            $tempFolder = $this->getDefaultImportExportFolder();
            if ($tempFolder) {
                $this->standaloneView->assign('tempFolder', $tempFolder->getCombinedIdentifier());
                $this->standaloneView->assign('hasTempUploadFolder', true);
                if (GeneralUtility::_POST('_upload')) {
                    $this->standaloneView->assign('submitted', GeneralUtility::_POST('_upload'));
                    $this->standaloneView->assign('noFileUploaded', $this->fileProcessor->internalUploadMap[1]);
                    if ($this->uploadedFiles[0]) {
                        $this->standaloneView->assign('uploadedFile', $this->uploadedFiles[0]->getName());
                    }
                }
            }

            // Perform import or preview depending:
            $inFile = $this->getFile($inData['file']);
            if ($inFile !== null && $inFile->exists()) {
                $this->standaloneView->assign('metaDataInFileExists', true);
                $importInhibitedMessages = [];
                if ($import->loadFile($inFile->getForLocalProcessing(false), 1)) {
                    $importInhibitedMessages = $import->checkImportPrerequisites();
                    if ($inData['import_file']) {
                        if (empty($importInhibitedMessages)) {
                            $import->importData($this->id);
                            BackendUtility::setUpdateSignal('updatePageTree');
                        }
                    }
                    $import->display_import_pid_record = $this->pageinfo;
                    $this->standaloneView->assign('contentOverview', $import->displayContentOverview());
                }
                // Compile messages which are inhibiting a proper import and add them to output.
                if (!empty($importInhibitedMessages)) {
                    $flashMessageQueue = GeneralUtility::makeInstance(FlashMessageService::class)->getMessageQueueByIdentifier('impexp.errors');
                    foreach ($importInhibitedMessages as $message) {
                        $flashMessageQueue->addMessage(GeneralUtility::makeInstance(
                            FlashMessage::class,
                            $message,
                            '',
                            FlashMessage::ERROR
                        ));
                    }
                }
            }
            // Print errors that might be:
            $errors = $import->printErrorLog();
            $this->standaloneView->assign('errors', trim($errors));
        }
    }

    /****************************
     * Helper functions
     ****************************/

    /**
     * Returns a \TYPO3\CMS\Core\Resource\Folder object for saving export files
     * to the server and is also used for uploading import files.
     *
     * @throws \InvalidArgumentException
     * @return NULL|\TYPO3\CMS\Core\Resource\Folder
     */
    protected function getDefaultImportExportFolder()
    {
        $defaultImportExportFolder = null;

        $defaultTemporaryFolder = $this->getBackendUser()->getDefaultUploadTemporaryFolder();
        if ($defaultTemporaryFolder !== null) {
            $importExportFolderName = 'importexport';
            $createFolder = !$defaultTemporaryFolder->hasFolder($importExportFolderName);
            if ($createFolder === true) {
                try {
                    $defaultImportExportFolder = $defaultTemporaryFolder->createFolder($importExportFolderName);
                } catch (Exception $folderAccessException) {
                }
            } else {
                $defaultImportExportFolder = $defaultTemporaryFolder->getSubfolder($importExportFolderName);
            }
        }

        return $defaultImportExportFolder;
    }

    /**
     * Check if a file has been uploaded
     *
     * @throws \InvalidArgumentException
     * @throws \UnexpectedValueException
     * @return void
     */
    public function checkUpload()
    {
        $file = GeneralUtility::_GP('file');
        // Initializing:
        $this->fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
        $this->fileProcessor->init([], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
        $this->fileProcessor->setActionPermissions();
        $this->fileProcessor->setExistingFilesConflictMode((int)GeneralUtility::_GP('overwriteExistingFiles') === 1 ? DuplicationBehavior::REPLACE : DuplicationBehavior::CANCEL);
        // Checking referer / executing:
        $refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
        $httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
        if (
            $httpHost != $refInfo['host']
            && !$GLOBALS['$TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']
            && $this->vC != $this->getBackendUser()->veriCode()
        ) {
            $this->fileProcessor->writeLog(0, 2, 1, 'Referer host "%s" and server host "%s" did not match!', [$refInfo['host'], $httpHost]);
        } else {
            $this->fileProcessor->start($file);
            $result = $this->fileProcessor->processData();
            if (!empty($result['upload'])) {
                foreach ($result['upload'] as $uploadedFiles) {
                    $this->uploadedFiles += $uploadedFiles;
                }
            }
        }
    }

    /**
     * Returns option array to be used in Fluid
     *
     * @param string $excludeList Table names (and the string "_ALL") to exclude. Comma list
     * @return array
     */
    public function getTableSelectOptions($excludeList = '')
    {
        $optValues = [];
        if (!GeneralUtility::inList($excludeList, '_ALL')) {
            $optValues['_ALL'] = '[' . $this->lang->getLL('ALL_tables') . ']';
        }
        foreach ($GLOBALS['TCA'] as $table => $_) {
            if ($this->getBackendUser()->check('tables_select', $table) && !GeneralUtility::inList($excludeList, $table)) {
                $optValues[$table] = $table;
            }
        }
        return $optValues;
    }

    /**
     * Filter page IDs by traversing exclude array, finding all
     * excluded pages (if any) and making an AND NOT IN statement for the select clause.
     *
     * @param array $exclude Exclude array from import/export object.
     * @return string AND where clause part to filter out page uids.
     */
    public function filterPageIds($exclude)
    {
        // Get keys:
        $exclude = array_keys($exclude);
        // Traverse
        $pageIds = [];
        foreach ($exclude as $element) {
            list($table, $uid) = explode(':', $element);
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
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }

    /**
     * @return DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Gets all export files.
     *
     * @throws \InvalidArgumentException
     * @return array|\TYPO3\CMS\Core\Resource\File[]
     */
    protected function getExportFiles()
    {
        $exportFiles = [];

        $folder = $this->getDefaultImportExportFolder();
        if ($folder !== null) {

            /** @var $filter FileExtensionFilter */
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
     * @return NULL|\TYPO3\CMS\Core\Resource\File
     */
    protected function getFile($combinedIdentifier)
    {
        try {
            $file = ResourceFactory::getInstance()->getFileObjectFromCombinedIdentifier($combinedIdentifier);
        } catch (\Exception $exception) {
            $file = null;
        }

        return $file;
    }
}
