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

namespace TYPO3\CMS\Filelist\Controller;

use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Exception\InsufficientFolderAccessPermissionsException;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\StorageRepository;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Fluid\View\StandaloneView;
use TYPO3Fluid\Fluid\View\ViewInterface;

/**
 * Script Class for creating the list of files in the File > Filelist module
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileListController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected array $MOD_MENU = [];
    protected array $MOD_SETTINGS = [];
    protected string $id = '';
    protected string $cmd = '';
    protected string $searchTerm = '';
    protected int $pointer = 0;
    protected ?Folder $folderObject = null;
    protected ?DuplicationBehavior $overwriteExistingFiles = null;

    protected UriBuilder $uriBuilder;
    protected PageRenderer $pageRenderer;
    protected IconFactory $iconFactory;
    protected ResourceFactory $resourceFactory;
    protected ModuleTemplateFactory $moduleTemplateFactory;
    protected ResponseFactoryInterface $responseFactory;

    protected ?ModuleTemplate $moduleTemplate = null;
    protected ?ViewInterface $view = null;
    protected ?FileList $filelist = null;

    public function __construct(
        UriBuilder $uriBuilder,
        PageRenderer $pageRenderer,
        IconFactory $iconFactory,
        ResourceFactory $resourceFactory,
        ModuleTemplateFactory $moduleTemplateFactory,
        ResponseFactoryInterface $responseFactory
    ) {
        $this->uriBuilder = $uriBuilder;
        $this->pageRenderer = $pageRenderer;
        $this->iconFactory = $iconFactory;
        $this->resourceFactory = $resourceFactory;
        $this->moduleTemplateFactory = $moduleTemplateFactory;
        $this->responseFactory = $responseFactory;
    }

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $this->moduleTemplate = $this->moduleTemplateFactory->create($request);
        $this->moduleTemplate->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:mlang_tabs_tab'));

        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $this->id = (string)($parsedBody['id'] ?? $queryParams['id'] ?? '');
        $this->cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        $this->searchTerm = (string)($parsedBody['searchTerm'] ?? $queryParams['searchTerm'] ?? '');
        $this->pointer = (int)($request->getParsedBody()['pointer'] ?? $request->getQueryParams()['pointer'] ?? 0);
        $this->overwriteExistingFiles = DuplicationBehavior::cast(
            $parsedBody['overwriteExistingFiles'] ?? $queryParams['overwriteExistingFiles'] ?? null
        );

        try {
            if ($this->id !== '') {
                $backendUser->evaluateUserSpecificFileFilterSettings();
                $storage = GeneralUtility::makeInstance(StorageRepository::class)->findByCombinedIdentifier($this->id);
                if ($storage !== null) {
                    $identifier = substr($this->id, strpos($this->id, ':') + 1);
                    if (!$storage->hasFolder($identifier)) {
                        $identifier = $storage->getFolderIdentifierFromFileIdentifier($identifier);
                    }
                    $this->folderObject = $storage->getFolder($identifier);
                    // Disallow access to fallback storage 0
                    if ($storage->getUid() === 0) {
                        throw new InsufficientFolderAccessPermissionsException(
                            'You are not allowed to access files outside your storages',
                            1434539815
                        );
                    }
                    // Disallow the rendering of the processing folder (e.g. could be called manually)
                    if ($this->folderObject && $storage->isProcessingFolder($this->folderObject)) {
                        $this->folderObject = $storage->getRootLevelFolder();
                    }
                }
            } else {
                // Take the first object of the first storage
                $fileStorages = $backendUser->getFileStorages();
                $fileStorage = reset($fileStorages);
                if ($fileStorage) {
                    $this->folderObject = $fileStorage->getRootLevelFolder();
                } else {
                    throw new \RuntimeException('Could not find any folder to be displayed.', 1349276894);
                }
            }

            if ($this->folderObject && !$this->folderObject->getStorage()->isWithinFileMountBoundaries($this->folderObject)) {
                throw new \RuntimeException('Folder not accessible.', 1430409089);
            }
        } catch (InsufficientFolderAccessPermissionsException $permissionException) {
            $this->folderObject = null;
            $this->addFlashMessage(
                sprintf($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:missingFolderPermissionsMessage'), $this->id),
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:missingFolderPermissionsTitle'),
                FlashMessage::ERROR
            );
        } catch (Exception $fileException) {
            $this->folderObject = null;
            // Take the first object of the first storage
            $fileStorages = $backendUser->getFileStorages();
            $fileStorage = reset($fileStorages);
            if ($fileStorage instanceof ResourceStorage) {
                $this->folderObject = $fileStorage->getRootLevelFolder();
                if (!$fileStorage->isWithinFileMountBoundaries($this->folderObject)) {
                    $this->folderObject = null;
                }
            }
            if (!$this->folderObject) {
                $this->addFlashMessage(
                    sprintf($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:folderNotFoundMessage'), $this->id),
                    $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:folderNotFoundTitle'),
                    FlashMessage::ERROR
                );
            }
        } catch (\RuntimeException $e) {
            $this->folderObject = null;
            $this->addFlashMessage(
                $e->getMessage() . ' (' . $e->getCode() . ')',
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:folderNotFoundTitle'),
                FlashMessage::ERROR
            );
        }

        if ($this->folderObject
            && !$this->folderObject->getStorage()->checkFolderActionPermission('read', $this->folderObject)
        ) {
            $this->folderObject = null;
        }

        $this->initializeView();
        $this->initializeModule($request);

        // In case the folderObject is NULL, the request is either invalid or the user
        // does not have necessary permissions. Just render and return the "empty" view.
        if ($this->folderObject === null) {
            return $this->htmlResponse(
                $this->moduleTemplate->setContent($this->view->render())->renderContent()
            );
        }

        return $this->processRequest($request);
    }

    protected function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();

        // Initialize FileList, including the clipboard actions
        $this->initializeFileList($request);

        // Generate the file listing markup
        $this->generateFileList();

        // Generate the clipboard, if enabled
        $this->view->assign('showClipboardPanel', (bool)($this->MOD_SETTINGS['clipBoard'] ?? false));

        // Register drag-uploader
        $this->registerDrapUploader();

        // Register the display thumbnails / show clipboard checkboxes
        $this->registerFileListCheckboxes();

        // Register additional doc header buttons
        $this->registerAdditionalDocHeaderButtons($request);

        // Add additional view variables
        $this->view->assignMultiple([
            'headline' => $this->getModuleHeadline(),
            'folderIdentifier' => $this->folderObject->getCombinedIdentifier(),
            'searchTerm' => $this->searchTerm,
        ]);

        // Overwrite the default module title, adding the specific module headline (the folder name)
        $this->moduleTemplate->setTitle(
            $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:mlang_tabs_tab'),
            $this->getModuleHeadline()
        );

        // Additional doc header information: current path and folder info
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformation([
            '_additional_info' => $this->filelist->getFolderInfo(),
        ]);
        $this->moduleTemplate->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);

        // Render view and return the response
        return $this->htmlResponse(
            $this->moduleTemplate->setContent($this->view->render())->renderContent()
        );
    }

    protected function initializeView(): void
    {
        $this->view = GeneralUtility::makeInstance(StandaloneView::class);
        $this->view->setTemplateRootPaths(['EXT:filelist/Resources/Private/Templates/FileList']);
        $this->view->setPartialRootPaths(['EXT:filelist/Resources/Private/Partials']);
        $this->view->setLayoutRootPaths(['EXT:filelist/Resources/Private/Layouts']);
        $this->view->setTemplatePathAndFilename(
            GeneralUtility::getFileAbsFileName('EXT:filelist/Resources/Private/Templates/File/List.html')
        );
        $this->view->assign('currentIdentifier', $this->folderObject ? $this->folderObject->getCombinedIdentifier() : '');

        // @todo: These modules should be merged into one module
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileList');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileDelete');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ClipboardPanel');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/MultiRecordSelection');
        $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ColumnSelectorButton');
        $this->pageRenderer->addInlineLanguageLabelFile(
            'EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf',
            'buttons'
        );
    }

    protected function initializeModule(ServerRequestInterface $request): void
    {
        // Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = ['sort' => '', 'reverse' => '', 'displayThumbs' => '', 'clipBoard' => ''];
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            $request->getParsedBody()['SET'] ?? $request->getQueryParams()['SET'] ?? null,
            'file_list'
        );

        $userTsConfig = $this->getBackendUser()->getTSConfig();

        // Set predefined value for DisplayThumbnails:
        if (($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'activated') {
            $this->MOD_SETTINGS['displayThumbs'] = true;
        } elseif (($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'deactivated') {
            $this->MOD_SETTINGS['displayThumbs'] = false;
        }
        // Set predefined value for Clipboard:
        if (($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'activated') {
            $this->MOD_SETTINGS['clipBoard'] = true;
        } elseif (($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'deactivated') {
            $this->MOD_SETTINGS['clipBoard'] = false;
        }
        if (!isset($this->MOD_SETTINGS['sort'])) {
            // Set default sorting
            $this->MOD_SETTINGS['sort'] = 'file';
            $this->MOD_SETTINGS['reverse'] = 0;
        }

        // Finally add the help button doc header button to the module
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('filelist_module');
        $buttonBar->addButton($cshButton);
    }

    protected function initializeFileList(ServerRequestInterface $request): void
    {
        // Create the file list
        $this->filelist = GeneralUtility::makeInstance(FileList::class, $request);
        $this->filelist->thumbs = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && ($this->MOD_SETTINGS['displayThumbs'] ?? false);

        // Create clipboard object and initialize it
        $CB = array_replace_recursive($request->getQueryParams()['CB'] ?? [], $request->getParsedBody()['CB'] ?? []);
        if (($this->cmd === 'copyMarked' || $this->cmd === 'removeMarked')) {
            // Get CBC from request, and map the element values, since they must either be the file identifier,
            // in case the element should be transferred to the clipboard, or false if it should be removed.
            $CBC = array_map(fn ($item) => $this->cmd === 'copyMarked' ? $item : false, (array)($request->getParsedBody()['CBC'] ?? []));
            // Cleanup CBC
            $CB['el'] = $this->filelist->clipObj->cleanUpCBC($CBC, '_FILE');
        }
        if (!($this->MOD_SETTINGS['clipBoard'] ?? false)) {
            $CB['setP'] = 'normal';
        }
        $this->filelist->clipObj->setCmd($CB);
        $this->filelist->clipObj->cleanCurrent();
        $this->filelist->clipObj->endClipboard();

        // If the "cmd" was to delete files from the list, do that:
        if ($this->cmd === 'delete') {
            $items = $this->filelist->clipObj->cleanUpCBC(
                (array)($request->getParsedBody()['CBC'] ?? []),
                '_FILE',
                true
            );
            if (!empty($items)) {
                // Make command array:
                $FILE = [];
                foreach ($items as $clipboardIdentifier => $combinedIdentifier) {
                    $FILE['delete'][] = ['data' => $combinedIdentifier];
                    $this->filelist->clipObj->removeElement($clipboardIdentifier);
                }
                // Init file processing object for deleting and pass the cmd array.
                $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
                $fileProcessor->setActionPermissions();
                $fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
                $fileProcessor->start($FILE);
                $fileProcessor->processData();
                // Clean & Save clipboard state
                $this->filelist->clipObj->cleanCurrent();
                $this->filelist->clipObj->endClipboard();
            }
        }

        // Start up the file list by including processed settings.
        $this->filelist->start(
            $this->folderObject,
            MathUtility::forceIntegerInRange($this->pointer, 0, 100000),
            (string)($this->MOD_SETTINGS['sort'] ?? ''),
            (bool)($this->MOD_SETTINGS['reverse'] ?? false)
        );
        $this->filelist->setColumnsToRender($this->getBackendUser()->getModuleData('list/displayFields')['_FILE'] ?? []);
    }

    protected function generateFileList(): void
    {
        $lang = $this->getLanguageService();

        // If a searchTerm is provided, create the searchDemand object
        $searchDemand = $this->searchTerm !== ''
            ? FileSearchDemand::createForSearchTerm($this->searchTerm)->withRecursive()
            : null;

        // Generate the list, if accessible
        if ($this->folderObject->getStorage()->isBrowsable()) {
            $this->view->assignMultiple([
                'listHtml' => $this->filelist->getTable($searchDemand),
                'listUrl' => $this->filelist->listURL(),
                'fileUploadUrl' => $this->getFileUploadUrl(),
                'totalItems' => $this->filelist->totalItems,
            ]);
            // Assign meta information for the multi record selection
            $this->view->assignMultiple([
                'editActionConfiguration' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    'idField' => 'metadataUid',
                    'table' => 'sys_file_metadata',
                    'returnUrl' => $this->filelist->listURL(),
                ], true),
                'deleteActionConfiguration' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    'ok' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.delete'),
                    'title' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clip_deleteMarked'),
                    'content' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clip_deleteMarkedWarning'),
                ], true),
            ]);

            // Add download button configuration, if file download is enabled
            if ($this->getBackendUser()->getTSConfig()['options.']['file_list.']['fileDownload.']['enabled'] ?? true) {
                $this->view->assign(
                    'downloadActionConfiguration',
                    GeneralUtility::jsonEncodeForHtmlAttribute([
                        'fileIdentifier' => 'fileUid',
                        'folderIdentifier' => 'combinedIdentifier',
                        'downloadUrl' => (string)$this->uriBuilder->buildUriFromRoute('file_download'),
                    ], true)
                );
            }

            // Add column selector information if enabled
            if ($this->getBackendUser()->getTSConfig()['options.']['file_list.']['displayColumnSelector'] ?? true) {
                $this->view->assign('columnSelector', [
                    'url' => $this->uriBuilder->buildUriFromRoute(
                        'ajax_show_columns_selector',
                        ['id' => $this->id, 'table' => '_FILE']
                    ),
                    'title' => sprintf(
                        $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumnsSelection'),
                        $lang->sL($GLOBALS['TCA']['sys_file']['ctrl']['title'] ?? ''),
                    ),
                ]);
            }
        } else {
            $this->addFlashMessage(
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:storageNotBrowsableMessage'),
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:storageNotBrowsableTitle')
            );
        }
    }

    protected function registerDrapUploader(): void
    {
        // Include DragUploader only if we have write access
        if ($this->folderObject->checkActionPermission('write')
            && $this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
        ) {
            $lang = $this->getLanguageService();
            $this->pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DragUploader');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_upload');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_download');
            $this->pageRenderer->addInlineLanguageLabelArray([
                'type.file' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file'),
                'permissions.read' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:read'),
                'permissions.write' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:write'),
            ]);
            $this->view->assign('drapUploader', [
                'fileDenyPattern' => $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] ?? null,
                'maxFileSize' => GeneralUtility::getMaxUploadFileSize() * 1024,
                'defaultDuplicationBehaviourAction', $this->getDefaultDuplicationBehaviourAction(),
            ]);
        }
    }

    protected function registerFileListCheckboxes(): void
    {
        $lang = $this->getLanguageService();
        $userTsConfig = $this->getBackendUser()->getTSConfig();
        $addParams = '';

        if ($this->searchTerm) {
            $addParams .= '&searchTerm=' . htmlspecialchars($this->searchTerm);
        }
        if ($this->pointer) {
            $addParams .= '&pointer=' . $this->pointer;
        }

        $this->view->assign('displayThumbs', [
            'enabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && ($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'selectable',
            'label' => htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:displayThumbs')),
            'html' => BackendUtility::getFuncCheck(
                $this->id,
                'SET[displayThumbs]',
                $this->MOD_SETTINGS['displayThumbs'] ?? '',
                '',
                $addParams,
                'id="checkDisplayThumbs"'
            ),
        ]);
        $this->view->assign('enableClipBoard', [
            'enabled' => ($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'selectable',
            'label' => htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clipBoard')),
            'mode' => $this->filelist->clipObj->current,
            'html' => BackendUtility::getFuncCheck(
                $this->id,
                'SET[clipBoard]',
                $this->MOD_SETTINGS['clipBoard'] ?? '',
                '',
                $addParams,
                'id="checkClipBoard"'
            ),
        ]);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function registerAdditionalDocHeaderButtons(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        $buttonBar = $this->moduleTemplate->getDocHeaderComponent()->getButtonBar();

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Level up
        try {
            $currentStorage = $this->folderObject->getStorage();
            $parentFolder = $this->folderObject->getParentFolder();
            if ($currentStorage->isWithinFileMountBoundaries($parentFolder)
                && $parentFolder->getIdentifier() !== $this->folderObject->getIdentifier()
            ) {
                $levelUpButton = $buttonBar->makeLinkButton()
                    ->setDataAttributes([
                        'tree-update-request' => htmlspecialchars('folder' . GeneralUtility::md5int($parentFolder->getCombinedIdentifier())),
                    ])
                    ->setHref(
                        (string)$this->uriBuilder->buildUriFromRoute(
                            'file_FilelistList',
                            ['id' => $parentFolder->getCombinedIdentifier()]
                        )
                    )
                    ->setShowLabelText(true)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.upOneLevel'))
                    ->setIcon($this->iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL));
                $buttonBar->addButton($levelUpButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            }
        } catch (\Exception $e) {
        }

        // Shortcut
        $shortCutButton = $buttonBar->makeShortcutButton()
            ->setRouteIdentifier('file_FilelistList')
            ->setDisplayName(sprintf(
                '%s: %s',
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:mlang_tabs_tab'),
                $this->folderObject->getName() ?: $this->folderObject->getIdentifier()
            ))
            ->setArguments(array_filter([
                'id' => $this->id,
                'searchTerm' => $this->searchTerm,
            ]));
        $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Upload button (only if upload to this directory is allowed)
        if ($this->folderObject
            && $this->folderObject->checkActionPermission('write')
            && $this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
        ) {
            $uploadButton = $buttonBar->makeLinkButton()
                ->setHref($this->getFileUploadUrl())
                ->setClasses('t3js-drag-uploader-trigger')
                ->setShowLabelText(true)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload'))
                ->setIcon($this->iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL));
            $buttonBar->addButton($uploadButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
        }

        // New folder button
        if ($this->folderObject && $this->folderObject->checkActionPermission('write')
            && ($this->folderObject->getStorage()->checkUserActionPermission(
                'add',
                'File'
            ) || $this->folderObject->checkActionPermission('add'))
        ) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    'file_newfolder',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->listURL(),
                    ]
                ))
                ->setShowLabelText(true)
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new'))
                ->setIcon($this->iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // Add paste button if clipboard is initialized
        if ($this->filelist->clipObj instanceof Clipboard && $this->folderObject->checkActionPermission('write')) {
            $elFromTable = $this->filelist->clipObj->elFromTable('_FILE');
            if (!empty($elFromTable)) {
                $addPasteButton = true;
                $elToConfirm = [];
                foreach ($elFromTable as $key => $element) {
                    $clipBoardElement = $this->resourceFactory->retrieveFileOrFolderObject($element);
                    if ($clipBoardElement instanceof Folder && $clipBoardElement->getStorage()->isWithinFolder(
                        $clipBoardElement,
                        $this->folderObject
                    )
                    ) {
                        $addPasteButton = false;
                    }
                    $elToConfirm[$key] = $clipBoardElement->getName();
                }
                if ($addPasteButton) {
                    $confirmText = $this->filelist->clipObj
                        ->confirmMsgText('_FILE', $this->folderObject->getReadablePath(), 'into', $elToConfirm);
                    $pastButtonTitle = $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clip_paste');
                    $pasteButton = $buttonBar->makeLinkButton()
                        ->setHref($this->filelist->clipObj
                            ->pasteUrl('_FILE', $this->folderObject->getCombinedIdentifier()))
                        ->setClasses('t3js-modal-trigger')
                        ->setDataAttributes([
                            'severity' => 'warning',
                            'bs-content' => $confirmText,
                            'title' => $pastButtonTitle,
                        ])
                        ->setShowLabelText(true)
                        ->setTitle($pastButtonTitle)
                        ->setIcon($this->iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
                }
            }
        }
    }

    /**
     * Get main headline based on active folder or storage for backend module
     * Folder names are resolved to their special names like done in the tree view.
     *
     * @return string
     */
    protected function getModuleHeadline(): string
    {
        $name = $this->folderObject->getName();
        if ($name === '') {
            // Show storage name on storage root
            if ($this->folderObject->getIdentifier() === '/') {
                $name = $this->folderObject->getStorage()->getName();
            }
        } else {
            $name = key(ListUtility::resolveSpecialFolderNames(
                [$name => $this->folderObject]
            ));
        }
        return (string)$name;
    }

    /**
     * Return the default duplication behaviour action, set in TSconfig
     *
     * @return string
     */
    protected function getDefaultDuplicationBehaviourAction(): string
    {
        $defaultAction = $this->getBackendUser()->getTSConfig()
            ['options.']['file_list.']['uploader.']['defaultAction'] ?? '';

        if ($defaultAction === '') {
            return DuplicationBehavior::CANCEL;
        }

        if (!in_array($defaultAction, [
            DuplicationBehavior::REPLACE,
            DuplicationBehavior::RENAME,
            DuplicationBehavior::CANCEL,
        ], true)) {
            $this->logger->warning('TSConfig: options.file_list.uploader.defaultAction contains an invalid value ("{value}"), fallback to default value: "{default}"', [
                'value' => $defaultAction,
                'default' => DuplicationBehavior::CANCEL,
            ]);
            $defaultAction = DuplicationBehavior::CANCEL;
        }
        return $defaultAction;
    }

    /**
     * Generate a response by either the given $html or by rendering the module content.
     *
     * @param string $html
     * @return ResponseInterface
     */
    protected function htmlResponse(string $html): ResponseInterface
    {
        $response = $this->responseFactory
            ->createResponse()
            ->withHeader('Content-Type', 'text/html; charset=utf-8');

        $response->getBody()->write($html);
        return $response;
    }

    /**
     * Adds a flash message to the default flash message queue
     *
     * @param string $message
     * @param string $title
     * @param int $severity
     */
    protected function addFlashMessage(string $message, string $title = '', int $severity = FlashMessage::INFO): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns the URL for uploading files
     *
     * @return string
     */
    protected function getFileUploadUrl(): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute(
            'file_upload',
            [
                'target' => $this->folderObject->getCombinedIdentifier(),
                'returnUrl' => $this->filelist->listURL(),
            ]
        );
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return BackendUserAuthentication
     */
    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
