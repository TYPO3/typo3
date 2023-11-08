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
use TYPO3\CMS\Backend\Module\ModuleData;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownDivider;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownItem;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownRadio;
use TYPO3\CMS\Backend\Template\Components\Buttons\DropDown\DropDownToggle;
use TYPO3\CMS\Backend\Template\ModuleTemplate;
use TYPO3\CMS\Backend\Template\ModuleTemplateFactory;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
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
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Filelist\ElementBrowser\CreateFolderBrowser;
use TYPO3\CMS\Filelist\FileList;
use TYPO3\CMS\Filelist\Matcher\Matcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFileTypeMatcher;
use TYPO3\CMS\Filelist\Matcher\ResourceFolderTypeMatcher;
use TYPO3\CMS\Filelist\Type\ViewMode;

/**
 * Script Class for creating the list of files in the File > Filelist module
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileListController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    protected string $id = '';
    protected string $cmd = '';
    protected string $searchTerm = '';
    protected int $currentPage = 1;

    protected ?Folder $folderObject = null;
    protected ?DuplicationBehavior $overwriteExistingFiles = null;
    protected ?ModuleTemplate $view = null;
    protected ?FileList $filelist = null;
    protected ?ModuleData $moduleData = null;

    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly PageRenderer $pageRenderer,
        protected readonly IconFactory $iconFactory,
        protected readonly ResourceFactory $resourceFactory,
        protected readonly ModuleTemplateFactory $moduleTemplateFactory,
        protected readonly BackendViewFactory $viewFactory,
        protected readonly ResponseFactoryInterface $responseFactory,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();
        $backendUser = $this->getBackendUser();

        $this->moduleData = $request->getAttribute('moduleData');

        $this->view = $this->moduleTemplateFactory->create($request);
        $this->view->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:mlang_tabs_tab'));

        $queryParams = $request->getQueryParams();
        $parsedBody = $request->getParsedBody();

        $this->id = (string)($parsedBody['id'] ?? $queryParams['id'] ?? '');
        $this->cmd = (string)($parsedBody['cmd'] ?? $queryParams['cmd'] ?? '');
        $this->searchTerm = (string)trim($parsedBody['searchTerm'] ?? $queryParams['searchTerm'] ?? '');
        $this->currentPage = (int)($parsedBody['currentPage'] ?? $queryParams['currentPage'] ?? 1);
        $this->overwriteExistingFiles = DuplicationBehavior::cast(
            $parsedBody['overwriteExistingFiles'] ?? $queryParams['overwriteExistingFiles'] ?? null
        );

        $storage = null;
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
                    if ($this->folderObject instanceof Folder && $storage->isProcessingFolder($this->folderObject)) {
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
            if ($storage !== null && $storage->getDriverType() === 'Local' && !$storage->isOnline()) {
                // If the base folder for a local storage does not exists, the storage is marked as offline and the
                // access permission exception is thrown. In this case we however want to display another error message.
                // @see https://forge.typo3.org/issues/85323
                $this->addFlashMessage(
                    sprintf($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:localStorageOfflineMessage'), $storage->getName()),
                    $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:localStorageOfflineTitle'),
                    ContextualFeedbackSeverity::ERROR
                );
            } else {
                $this->addFlashMessage(
                    sprintf($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:missingFolderPermissionsMessage'), $this->id),
                    $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:missingFolderPermissionsTitle'),
                    ContextualFeedbackSeverity::ERROR
                );
            }
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
                    ContextualFeedbackSeverity::ERROR
                );
            }
        } catch (\RuntimeException $e) {
            $this->folderObject = null;
            $this->addFlashMessage(
                $e->getMessage() . ' (' . $e->getCode() . ')',
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:folderNotFoundTitle'),
                ContextualFeedbackSeverity::ERROR
            );
        }

        if ($this->folderObject
            && !$this->folderObject->getStorage()->checkFolderActionPermission('read', $this->folderObject)
        ) {
            $this->folderObject = null;
        }

        $this->view->assign('currentIdentifier', $this->folderObject ? $this->folderObject->getCombinedIdentifier() : '');
        $javaScriptRenderer = $this->pageRenderer->getJavaScriptRenderer();
        $javaScriptRenderer->addJavaScriptModuleInstruction(
            JavaScriptModuleInstruction::create('@typo3/filelist/file-list.js')->instance()
        );

        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-dragdrop.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-transfer-handler.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:filelist/Resources/Private/Language/locallang_transfer_handler.xlf');

        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-actions.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-list-rename-handler.js');
        $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_rename');

        $this->pageRenderer->loadJavaScriptModule('@typo3/filelist/file-delete.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/context-menu.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/clipboard-panel.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/multi-record-selection.js');
        $this->pageRenderer->loadJavaScriptModule('@typo3/backend/column-selector-button.js');

        $this->pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf', 'buttons');

        $this->initializeModule($request);

        // In case the folderObject is NULL, the request is either invalid or the user
        // does not have necessary permissions. Just render and return the "empty" view.
        if ($this->folderObject === null) {
            return $this->view->renderResponse('File/List');
        }

        return $this->processRequest($request);
    }

    protected function processRequest(ServerRequestInterface $request): ResponseInterface
    {
        $lang = $this->getLanguageService();

        // Initialize FileList, including the clipboard actions
        $this->initializeFileList($request);

        // Generate the file listing markup
        $this->generateFileList($request);

        // Generate the clipboard, if enabled
        $this->view->assign('showClipboardPanel', (bool)$this->moduleData->get('clipBoard'));

        // Register drag-uploader
        $this->registerDragUploader();

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
        $this->view->setTitle(
            $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:mlang_tabs_tab'),
            $this->getModuleHeadline()
        );

        // Additional doc header information: current path and folder info
        $this->view->getDocHeaderComponent()->setMetaInformation([
            '_additional_info' => $this->filelist->getFolderInfo(),
        ]);
        $this->view->getDocHeaderComponent()->setMetaInformationForResource($this->folderObject);

        return $this->view->renderResponse('File/List');
    }

    protected function initializeModule(ServerRequestInterface $request): void
    {
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        // Set predefined value for DisplayThumbnails:
        if (($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'activated') {
            $this->moduleData->set('displayThumbs', true);
        } elseif (($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'deactivated') {
            $this->moduleData->set('displayThumbs', false);
        }
        // Set predefined value for Clipboard:
        if (($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'activated') {
            $this->moduleData->set('clipBoard', true);
        } elseif (($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'deactivated') {
            $this->moduleData->set('clipBoard', false);
        }
    }

    protected function initializeFileList(ServerRequestInterface $request): void
    {
        // Create the file list
        $this->filelist = GeneralUtility::makeInstance(FileList::class, $request);
        $this->filelist->viewMode = ViewMode::tryFrom($this->moduleData->get('viewMode')) ?? ViewMode::TILES;
        $this->filelist->thumbs = ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] ?? false) && $this->moduleData->get('displayThumbs');

        // Create clipboard object and initialize it
        $CB = array_replace_recursive($request->getQueryParams()['CB'] ?? [], $request->getParsedBody()['CB'] ?? []);
        if (($this->cmd === 'copyMarked' || $this->cmd === 'removeMarked')) {
            // Get CBC from request, and map the element values, since they must either be the file identifier,
            // in case the element should be transferred to the clipboard, or false if it should be removed.
            $CBC = array_map(fn($item) => $this->cmd === 'copyMarked' ? $item : false, (array)($request->getParsedBody()['CBC'] ?? []));
            // Cleanup CBC
            $CB['el'] = $this->filelist->clipObj->cleanUpCBC($CBC, '_FILE');
        }
        if (!$this->moduleData->get('clipBoard')) {
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
            MathUtility::forceIntegerInRange($this->currentPage, 1, 100000),
            (string)$this->moduleData->get('sort'),
            (bool)$this->moduleData->get('reverse')
        );
        $this->filelist->setColumnsToRender($this->getBackendUser()->getModuleData('list/displayFields')['_FILE'] ?? []);

        $resourceSelectableMatcher = GeneralUtility::makeInstance(Matcher::class);
        $resourceSelectableMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileTypeMatcher::class));
        $resourceSelectableMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->filelist->setResourceSelectableMatcher($resourceSelectableMatcher);

        $resourceDownloadMatcher = GeneralUtility::makeInstance(Matcher::class);
        $resourceDownloadMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFileTypeMatcher::class));
        $resourceDownloadMatcher->addMatcher(GeneralUtility::makeInstance(ResourceFolderTypeMatcher::class));
        $this->filelist->setResourceDownloadMatcher($resourceDownloadMatcher);
    }

    protected function generateFileList(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();

        // If a searchTerm is provided, create the searchDemand object
        $searchDemand = $this->searchTerm !== ''
            ? FileSearchDemand::createForSearchTerm($this->searchTerm)->withRecursive()
            : null;

        // Generate the list, if accessible
        if ($this->folderObject->getStorage()->isBrowsable()) {
            $fileListView = $this->viewFactory->create($request);
            $this->view->assignMultiple([
                'listHtml' => $this->filelist->render($searchDemand, $fileListView),
                'listUrl' => $this->filelist->createModuleUri(),
                'fileUploadUrl' => $this->getFileUploadUrl(),
                'totalItems' => $this->filelist->totalItems,
            ]);
            // Assign meta information for the multi record selection
            $this->view->assignMultiple([
                'editActionConfiguration' => GeneralUtility::jsonEncodeForHtmlAttribute([
                    'idField' => 'filelistMetaUid',
                    'table' => 'sys_file_metadata',
                    'returnUrl' => $this->filelist->createModuleUri(),
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
                        'downloadUrl' => (string)$this->uriBuilder->buildUriFromRoute('file_download'),
                    ], true)
                );
            }
        } else {
            $this->addFlashMessage(
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:storageNotBrowsableMessage'),
                $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:storageNotBrowsableTitle')
            );
        }
    }

    protected function registerDragUploader(): void
    {
        // Include DragUploader only if we have write access
        if ($this->folderObject->checkActionPermission('write')
            && $this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
        ) {
            $lang = $this->getLanguageService();
            $this->pageRenderer->loadJavaScriptModule('@typo3/backend/drag-uploader.js');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_upload');
            $this->pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_download');
            $this->pageRenderer->addInlineLanguageLabelArray([
                'type.file' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_common.xlf:file'),
                'permissions.read' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:read'),
                'permissions.write' => $lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:write'),
            ]);
            $this->view->assign('dragUploader', [
                'fileDenyPattern' => $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern'] ?? null,
                'maxFileSize' => GeneralUtility::getMaxUploadFileSize() * 1024,
                'defaultDuplicationBehaviourAction' => $this->getDefaultDuplicationBehaviourAction(),
            ]);
        }
    }

    protected function registerFileListCheckboxes(): void
    {
        $lang = $this->getLanguageService();
        $userTsConfig = $this->getBackendUser()->getTSConfig();

        $this->view->assign('enableClipBoard', [
            'enabled' => ($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'selectable',
            'label' => htmlspecialchars($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf:clipBoard')),
            'mode' => $this->filelist->clipObj->current,
        ]);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     */
    protected function registerAdditionalDocHeaderButtons(ServerRequestInterface $request): void
    {
        $lang = $this->getLanguageService();
        $buttonBar = $this->view->getDocHeaderComponent()->getButtonBar();

        // Refresh
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($request->getAttribute('normalizedParams')->getRequestUri())
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($this->iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // ViewMode
        $viewModeItems = [];
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->moduleData->get('viewMode') === ViewMode::TILES->value)
            ->setHref($this->filelist->createModuleUri(['viewMode' => ViewMode::TILES->value]))
            ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.tiles'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-tiles'));
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownRadio::class)
            ->setActive($this->moduleData->get('viewMode') === ViewMode::LIST->value)
            ->setHref($this->filelist->createModuleUri(['viewMode' => ViewMode::LIST->value]))
            ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.list'))
            ->setIcon($this->iconFactory->getIcon('actions-viewmode-list'));
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownDivider::class);
        if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && ($this->getBackendUser()->getTSConfig()['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'selectable') {
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
                ->setActive((bool)$this->moduleData->get('displayThumbs'))
                ->setHref($this->filelist->createModuleUri(['displayThumbs' => $this->moduleData->get('displayThumbs') ? 0 : 1]))
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showThumbnails'))
                ->setIcon($this->iconFactory->getIcon('actions-image'));
        }
        $viewModeItems[] = GeneralUtility::makeInstance(DropDownToggle::class)
            ->setActive((bool)$this->moduleData->get('clipBoard'))
            ->setHref($this->filelist->createModuleUri(['clipBoard' => $this->moduleData->get('clipBoard') ? 0 : 1]))
            ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.showClipboard'))
            ->setIcon($this->iconFactory->getIcon('actions-clipboard'));
        if (($this->getBackendUser()->getTSConfig()['options.']['file_list.']['displayColumnSelector'] ?? true)
            && $this->moduleData->get('viewMode') === ViewMode::LIST->value) {
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownDivider::class);
            $viewModeItems[] = GeneralUtility::makeInstance(DropDownItem::class)
                ->setTag('typo3-backend-column-selector-button')
                ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view.selectColumns'))
                ->setAttributes([
                    'data-url' => $this->uriBuilder->buildUriFromRoute(
                        'ajax_show_columns_selector',
                        ['id' => $this->id, 'table' => '_FILE']
                    ),
                    'data-target' => $this->filelist->createModuleUri(),
                    'data-title' => sprintf(
                        $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:showColumnsSelection'),
                        $lang->sL($GLOBALS['TCA']['sys_file']['ctrl']['title'] ?? ''),
                    ),
                    'data-button-ok' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView'),
                    'data-button-close' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.cancel'),
                    'data-error-message' => $lang->sL('LLL:EXT:backend/Resources/Private/Language/locallang_column_selector.xlf:updateColumnView.error'),
                ])
                ->setIcon($this->iconFactory->getIcon('actions-options'));
        }
        $viewModeButton = $buttonBar->makeDropDownButton()
            ->setLabel($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.view'))
            ->setShowLabelText(true);
        foreach ($viewModeItems as $viewModeItem) {
            $viewModeButton->addItem($viewModeItem);
        }
        $buttonBar->addButton($viewModeButton, ButtonBar::BUTTON_POSITION_RIGHT, 2);

        // Level up
        try {
            $currentStorage = $this->folderObject->getStorage();
            $parentFolder = $this->folderObject->getParentFolder();
            if ($currentStorage->isWithinFileMountBoundaries($parentFolder)
                && $parentFolder->getIdentifier() !== $this->folderObject->getIdentifier()
                && $parentFolder instanceof Folder
            ) {
                $levelUpButton = $buttonBar->makeLinkButton()
                    ->setDataAttributes([
                        'tree-update-request' => htmlspecialchars('folder' . GeneralUtility::md5int($parentFolder->getCombinedIdentifier())),
                    ])
                    ->setHref(
                        (string)$this->uriBuilder->buildUriFromRoute(
                            'media_management',
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
            ->setRouteIdentifier('media_management')
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
        if ($this->folderObject && $this->folderObject->checkActionPermission('write') && $this->folderObject->checkActionPermission('add')) {
            $newButton = $buttonBar->makeLinkButton()
                ->setClasses('t3js-element-browser')
                ->setHref((string)$this->uriBuilder->buildUriFromRoute('wizard_element_browser'))
                ->setDataAttributes([
                    'identifier' => $this->folderObject->getCombinedIdentifier(),
                    'mode' => CreateFolderBrowser::IDENTIFIER,
                ])
                ->setShowLabelText(true)
                ->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:actions.create_folder'))
                ->setIcon($this->iconFactory->getIcon('actions-folder-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 3);
        }

        // New file button
        if ($this->folderObject && $this->folderObject->checkActionPermission('write')
            && $this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
        ) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref((string)$this->uriBuilder->buildUriFromRoute(
                    'file_create',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->createModuleUri(),
                    ]
                ))
                ->setShowLabelText(true)
                ->setTitle($lang->sL('LLL:EXT:filelist/Resources/Private/Language/locallang.xlf:actions.create_file'))
                ->setIcon($this->iconFactory->getIcon('actions-file-add', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 4);
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
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 10);
                }
            }
        }
    }

    /**
     * Get main headline based on active folder or storage for backend module
     * Folder names are resolved to their special names like done in the tree view.
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
     */
    protected function addFlashMessage(string $message, string $title = '', ContextualFeedbackSeverity $severity = ContextualFeedbackSeverity::INFO): void
    {
        $flashMessage = GeneralUtility::makeInstance(FlashMessage::class, $message, $title, $severity, true);
        $flashMessageService = GeneralUtility::makeInstance(FlashMessageService::class);
        $defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
        $defaultFlashMessageQueue->enqueue($flashMessage);
    }

    /**
     * Returns the URL for uploading files
     */
    protected function getFileUploadUrl(): string
    {
        return (string)$this->uriBuilder->buildUriFromRoute(
            'file_upload',
            [
                'target' => $this->folderObject->getCombinedIdentifier(),
                'returnUrl' => $this->filelist->createModuleUri(),
            ]
        );
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }

    protected function getBackendUser(): BackendUserAuthentication
    {
        return $GLOBALS['BE_USER'];
    }
}
