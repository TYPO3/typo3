<?php
namespace TYPO3\CMS\Filelist\Controller;

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

use TYPO3\CMS\Backend\Clipboard\Clipboard;
use TYPO3\CMS\Backend\Template\Components\ButtonBar;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\View\BackendTemplateView;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\FileList;

/**
 * Script Class for creating the list of files in the File > Filelist module
 */
class FileListController extends ActionController
{
    /**
     * Module configuration
     *
     * @var array
     * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. The Module gets configured by ExtensionManagementUtility::addModule() in ext_tables.php
     */
    public $MCONF = [];

    /**
     * @var array
     */
    public $MOD_MENU = [];

    /**
     * @var array
     */
    public $MOD_SETTINGS = [];

    /**
     * Document template object
     *
     * @var DocumentTemplate
     */
    public $doc;

    /**
     * "id" -> the path to list.
     *
     * @var string
     */
    public $id;

    /**
     * @var Folder
     */
    protected $folderObject;

    /**
     * @var FlashMessage
     */
    protected $errorMessage;

    /**
     * Pointer to listing
     *
     * @var int
     */
    public $pointer;

    /**
     * "Table"
     * @var string
     */
    public $table;

    /**
     * Thumbnail mode.
     *
     * @var string
     */
    public $imagemode;

    /**
     * @var string
     */
    public $cmd;

    /**
     * Defines behaviour when uploading files with names that already exist; possible values are
     * the values of the \TYPO3\CMS\Core\Resource\DuplicationBehavior enumeration
     *
     * @var \TYPO3\CMS\Core\Resource\DuplicationBehavior
     */
    protected $overwriteExistingFiles;

    /**
     * The filelist object
     *
     * @var FileList
     */
    public $filelist = null;

    /**
     * The name of the module
     *
     * @var string
     */
    protected $moduleName = 'file_list';

    /**
     * @var \TYPO3\CMS\Core\Resource\FileRepository
     */
    protected $fileRepository;

    /**
     * @var BackendTemplateView
     */
    protected $view;

    /**
     * BackendTemplateView Container
     *
     * @var BackendTemplateView
     */
    protected $defaultViewObjectName = BackendTemplateView::class;

    /**
     * @param \TYPO3\CMS\Core\Resource\FileRepository $fileRepository
     */
    public function injectFileRepository(\TYPO3\CMS\Core\Resource\FileRepository $fileRepository)
    {
        $this->fileRepository = $fileRepository;
    }

    /**
     * Initialize variables, file object
     * Incoming GET vars include id, pointer, table, imagemode
     *
     * @return void
     * @throws \RuntimeException
     * @throws Exception\InsufficientFolderAccessPermissionsException
     */
    public function initializeObject()
    {
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_mod_file_list.xlf');
        $this->getLanguageService()->includeLLFile('EXT:lang/locallang_misc.xlf');

        // Setting GPvars:
        $this->id = ($combinedIdentifier = GeneralUtility::_GP('id'));
        $this->pointer = GeneralUtility::_GP('pointer');
        $this->table = GeneralUtility::_GP('table');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->overwriteExistingFiles = DuplicationBehavior::cast(GeneralUtility::_GP('overwriteExistingFiles'));

        try {
            if ($combinedIdentifier) {
                /** @var $resourceFactory ResourceFactory */
                $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                $storage = $resourceFactory->getStorageObjectFromCombinedIdentifier($combinedIdentifier);
                $identifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
                if (!$storage->hasFolder($identifier)) {
                    $identifier = $storage->getFolderIdentifierFromFileIdentifier($identifier);
                }

                $this->folderObject = $resourceFactory->getFolderObjectFromCombinedIdentifier($storage->getUid() . ':' . $identifier);
                // Disallow access to fallback storage 0
                if ($storage->getUid() === 0) {
                    throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to access files outside your storages',
                        1434539815);
                }
                // Disallow the rendering of the processing folder (e.g. could be called manually)
                if ($this->folderObject && $storage->isProcessingFolder($this->folderObject)) {
                    $this->folderObject = $storage->getRootLevelFolder();
                }
            } else {
                // Take the first object of the first storage
                $fileStorages = $this->getBackendUser()->getFileStorages();
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
        } catch (Exception\InsufficientFolderAccessPermissionsException $permissionException) {
            $this->folderObject = null;
            $this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->getLL('missingFolderPermissionsMessage', true),
                    htmlspecialchars($this->id)
                ),
                $this->getLanguageService()->getLL('missingFolderPermissionsTitle', true),
                FlashMessage::NOTICE
            );
        } catch (Exception $fileException) {
            // Set folder object to null and throw a message later on
            $this->folderObject = null;
            // Take the first object of the first storage
            $fileStorages = $this->getBackendUser()->getFileStorages();
            $fileStorage = reset($fileStorages);
            if ($fileStorage instanceof \TYPO3\CMS\Core\Resource\ResourceStorage) {
                $this->folderObject = $fileStorage->getRootLevelFolder();
                if (!$fileStorage->isWithinFileMountBoundaries($this->folderObject)) {
                    $this->folderObject = null;
                }
            }
            $this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->getLL('folderNotFoundMessage', true),
                    htmlspecialchars($this->id)
                ),
                $this->getLanguageService()->getLL('folderNotFoundTitle', true),
                FlashMessage::NOTICE
            );
        } catch (\RuntimeException $e) {
            $this->folderObject = null;
            $this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
                $e->getMessage() . ' (' . $e->getCode() . ')',
                $this->getLanguageService()->getLL('folderNotFoundTitle', true),
                FlashMessage::NOTICE
            );
        }

        if ($this->folderObject && !$this->folderObject->getStorage()->checkFolderActionPermission('read',
                $this->folderObject)
        ) {
            $this->folderObject = null;
        }

        // Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
        $this->menuConfig();
    }

    /**
     * Setting the menu/session variables
     *
     * @return void
     */
    public function menuConfig()
    {
        // MENU-ITEMS:
        // If array, then it's a selector box menu
        // If empty string it's just a variable, that will be saved.
        // Values NOT in this array will not be saved in the settings-array for the module.
        $this->MOD_MENU = [
            'sort' => '',
            'reverse' => '',
            'displayThumbs' => '',
            'clipBoard' => '',
            'bigControlPanel' => ''
        ];
        // CLEANSE SETTINGS
        $this->MOD_SETTINGS = BackendUtility::getModuleData(
            $this->MOD_MENU,
            GeneralUtility::_GP('SET'),
            $this->moduleName
        );
    }

    /**
     * Initialize the view
     *
     * @param ViewInterface $view The view
     * @return void
     */
    public function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileListLocalisation');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileSearch');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ClickMenu');
        $this->registerDocHeaderButtons();
    }

    /**
     * @return void
     */
    public function initializeIndexAction()
    {
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        $backendUser = $this->getBackendUser();
        if ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'activated') {
            $this->MOD_SETTINGS['bigControlPanel'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'deactivated') {
            $this->MOD_SETTINGS['bigControlPanel'] = false;
        }
        // Set predefined value for DisplayThumbnails:
        if ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'activated') {
            $this->MOD_SETTINGS['displayThumbs'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'deactivated') {
            $this->MOD_SETTINGS['displayThumbs'] = false;
        }
        // Set predefined value for Clipboard:
        if ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'activated') {
            $this->MOD_SETTINGS['clipBoard'] = true;
        } elseif ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'deactivated') {
            $this->MOD_SETTINGS['clipBoard'] = false;
        }
        // If user never opened the list module, set the value for displayThumbs
        if (!isset($this->MOD_SETTINGS['displayThumbs'])) {
            $this->MOD_SETTINGS['displayThumbs'] = $backendUser->uc['thumbnailsByDefault'];
        }
        if (!isset($this->MOD_SETTINGS['sort'])) {
            // Set default sorting
            $this->MOD_SETTINGS['sort'] = 'file';
            $this->MOD_SETTINGS['reverse'] = 0;
        }
    }

    /**
     * @return void
     */
    public function indexAction()
    {
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->setTitle($this->getLanguageService()->getLL('files'));

        // There there was access to this file path, continue, make the list
        if ($this->folderObject) {
            // Create fileListing object
            $this->filelist = GeneralUtility::makeInstance(FileList::class, $this);
            $this->filelist->thumbs = $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && $this->MOD_SETTINGS['displayThumbs'];
            // Create clipboard object and initialize that
            $this->filelist->clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $this->filelist->clipObj->fileMode = 1;
            $this->filelist->clipObj->initializeClipboard();
            $CB = GeneralUtility::_GET('CB');
            if ($this->cmd == 'setCB') {
                $CB['el'] = $this->filelist->clipObj->cleanUpCBC(array_merge(GeneralUtility::_POST('CBH'),
                    (array)GeneralUtility::_POST('CBC')), '_FILE');
            }
            if (!$this->MOD_SETTINGS['clipBoard']) {
                $CB['setP'] = 'normal';
            }
            $this->filelist->clipObj->setCmd($CB);
            $this->filelist->clipObj->cleanCurrent();
            // Saves
            $this->filelist->clipObj->endClipboard();
            // If the "cmd" was to delete files from the list (clipboard thing), do that:
            if ($this->cmd == 'delete') {
                $items = $this->filelist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), '_FILE', 1);
                if (!empty($items)) {
                    // Make command array:
                    $FILE = [];
                    foreach ($items as $v) {
                        $FILE['delete'][] = ['data' => $v];
                    }
                    // Init file processing object for deleting and pass the cmd array.
                    /** @var ExtendedFileUtility $fileProcessor */
                    $fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
                    $fileProcessor->init([], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
                    $fileProcessor->setActionPermissions();
                    $fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
                    $fileProcessor->start($FILE);
                    $fileProcessor->processData();
                }
            }
            // Start up filelisting object, include settings.
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $this->filelist->start($this->folderObject, $this->pointer, $this->MOD_SETTINGS['sort'],
                $this->MOD_SETTINGS['reverse'], $this->MOD_SETTINGS['clipBoard'],
                $this->MOD_SETTINGS['bigControlPanel']);
            // Generate the list
            $this->filelist->generateList();
            // Set top JavaScript:
            $this->view->getModuleTemplate()->addJavaScriptCode(
                'FileListIndex',
                'if (top.fsMod) top.fsMod.recentIds["file"] = "' . rawurlencode($this->id) . '";' . $this->filelist->CBfunctions() . '
                function jumpToUrl(URL) {
                    window.location.href = URL;
                    return false;
                }
                ');
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileDelete');
            $pageRenderer->addInlineLanguageLabelFile(
                ExtensionManagementUtility::extPath('lang') . 'locallang_alt_doc.xlf',
                'buttons'
            );

            // Include DragUploader only if we have write access
            if ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
                && $this->folderObject->checkActionPermission('write')
            ) {
                $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DragUploader');
                $pageRenderer->addInlineLanguageLabelFile(
                    ExtensionManagementUtility::extPath('lang') . 'locallang_core.xlf',
                    'file_upload'
                );
                $pageRenderer->addInlineLanguageLabelArray([
                    'permissions.read' => $this->getLanguageService()->getLL('read'),
                    'permissions.write' => $this->getLanguageService()->getLL('write'),
                ]);
            }

            // Setting up the buttons
            $this->registerButtons();

            $pageRecord = [
                '_additional_info' => $this->filelist->getFolderInfo(),
                'combined_identifier' => $this->folderObject->getCombinedIdentifier(),
            ];
            $this->view->getModuleTemplate()->getDocHeaderComponent()->setMetaInformation($pageRecord);

            $this->view->assign('headline', $this->getModuleHeadline());
            $this->view->assign('listHtml', $this->filelist->HTMLcode);

            $this->view->assign('checkboxes', [
                'bigControlPanel' => [
                    'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'selectable',
                    'label' => $this->getLanguageService()->getLL('bigControlPanel', true),
                    'html' => BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'], '', '', 'id="bigControlPanel"'),
                ],
                'displayThumbs' => [
                    'enabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'selectable',
                    'label' => $this->getLanguageService()->getLL('displayThumbs', true),
                    'html' => BackendUtility::getFuncCheck($this->id, 'SET[displayThumbs]',
                        $this->MOD_SETTINGS['displayThumbs'], '', '', 'id="checkDisplayThumbs"'),
                ],
                'enableClipBoard' => [
                    'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableClipBoard') === 'selectable',
                    'label' => $this->getLanguageService()->getLL('clipBoard', true),
                    'html' => BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]',
                        $this->MOD_SETTINGS['clipBoard'], '', '', 'id="checkClipBoard"'),
                ]
            ]);
            $this->view->assign('showClipBoard', (bool)$this->MOD_SETTINGS['clipBoard']);
            $this->view->assign('clipBoardHtml', $this->filelist->clipObj->printClipboard());
            $this->view->assign('folderIdentifier', $this->folderObject->getCombinedIdentifier());
            $this->view->assign('fileDenyPattern', $GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']);
            $this->view->assign('maxFileSize', GeneralUtility::getMaxUploadFileSize() * 1024);
        } else {
            $this->forward('missingFolder');
        }
    }

    /**
     * @return void
     */
    public function missingFolderAction()
    {
        if ($this->errorMessage) {
            $this->errorMessage->setSeverity(FlashMessage::ERROR);
            $this->controllerContext->getFlashMessageQueue('core.template.flashMessages')->addMessage($this->errorMessage);
        }
    }

    /**
     * Search for files by name and pass them with a facade to fluid
     *
     * @param string $searchWord
     */
    public function searchAction($searchWord = '')
    {
        if (empty($searchWord)) {
            $this->forward('index');
        }

        $fileFacades = [];
        $files = $this->fileRepository->searchByName($this->folderObject, $searchWord);

        if (empty($files)) {
            $this->controllerContext->getFlashMessageQueue('core.template.flashMessages')->addMessage(
                new FlashMessage(LocalizationUtility::translate('flashmessage.no_results', 'filelist'), '',
                    FlashMessage::INFO)
            );
        } else {
            foreach ($files as $file) {
                $fileFacades[] = new \TYPO3\CMS\Filelist\FileFacade($file);
            }
        }

        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileList');

        $this->view->assign('searchWord', $searchWord);
        $this->view->assign('files', $fileFacades);
        $this->view->assign('veriCode', $this->getBackendUser()->veriCode());
        $this->view->assign('deleteUrl', BackendUtility::getModuleUrl('tce_file'));
        $this->view->assign('settings', [
            'jsConfirmationDelete' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)
        ]);

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileDelete');
        $pageRenderer->addInlineLanguageLabelFile(
            ExtensionManagementUtility::extPath('lang') . 'locallang_alt_doc.xlf',
            'buttons'
        );
    }

    /**
     * Get main headline based on active folder or storage for backend module
     * Folder names are resolved to their special names like done in the tree view.
     *
     * @return string
     */
    protected function getModuleHeadline()
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
        return $name;
    }

    /**
     * Registers the Icons into the docheader
     *
     * @return void
     * @throws \InvalidArgumentException
     */
    protected function registerDocHeaderButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        // CSH
        $cshButton = $buttonBar->makeHelpButton()
            ->setModuleName('xMOD_csh_corebe')
            ->setFieldName('filelist_module');
        $buttonBar->addButton($cshButton);
    }

    /**
     * Create the panel of buttons for submitting the form or otherwise perform operations.
     *
     * @return array All available buttons as an assoc. array
     */
    protected function registerButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        /** @var IconFactory $iconFactory */
        $iconFactory = $this->view->getModuleTemplate()->getIconFactory();

        /** @var $resourceFactory ResourceFactory */
        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $lang = $this->getLanguageService();

        // Refresh page
        $refreshLink = GeneralUtility::linkThisScript(
            [
                'target' => rawurlencode($this->folderObject->getCombinedIdentifier()),
                'imagemode' => $this->filelist->thumbs
            ]
        );
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($refreshLink)
            ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Level up
        try {
            $currentStorage = $this->folderObject->getStorage();
            $parentFolder = $this->folderObject->getParentFolder();
            if ($parentFolder->getIdentifier() !== $this->folderObject->getIdentifier()
                && $currentStorage->isWithinFileMountBoundaries($parentFolder)
            ) {
                $levelUpClick = 'top.document.getElementsByName("navigation")[0].contentWindow.Tree.highlightActiveItem("file","folder'
                    . GeneralUtility::md5int($parentFolder->getCombinedIdentifier()) . '_"+top.fsMod.currentBank)';
                $levelUpButton = $buttonBar->makeLinkButton()
                    ->setHref(BackendUtility::getModuleUrl('file_FilelistList', ['id' => $parentFolder->getCombinedIdentifier()]))
                    ->setOnClick($levelUpClick)
                    ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.upOneLevel'))
                    ->setIcon($iconFactory->getIcon('actions-view-go-up', Icon::SIZE_SMALL));
                $buttonBar->addButton($levelUpButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
            }
        } catch (\Exception $e) {
        }

        // Shortcut
        if ($this->getBackendUser()->mayMakeShortcut()) {
            $shortCutButton = $buttonBar->makeShortcutButton()->setModuleName('file_FilelistList');
            $buttonBar->addButton($shortCutButton, ButtonBar::BUTTON_POSITION_RIGHT);
        }

        // Upload button (only if upload to this directory is allowed)
        if ($this->folderObject && $this->folderObject->getStorage()->checkUserActionPermission('add',
                'File') && $this->folderObject->checkActionPermission('write')
        ) {
            $uploadButton = $buttonBar->makeLinkButton()
                ->setHref(BackendUtility::getModuleUrl(
                    'file_upload',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->listURL(),
                    ]
                ))
                ->setClasses('t3js-drag-uploader-trigger')
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:cm.upload'))
                ->setIcon($iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL));
            $buttonBar->addButton($uploadButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }

        // New folder button
        if ($this->folderObject && $this->folderObject->checkActionPermission('write')
            && ($this->folderObject->getStorage()->checkUserActionPermission('add',
                    'File') || $this->folderObject->checkActionPermission('add'))
        ) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref(BackendUtility::getModuleUrl(
                    'file_newfolder',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->listURL(),
                    ]
                ))
                ->setTitle($lang->sL('LLL:EXT:lang/locallang_core.xlf:cm.new'))
                ->setIcon($iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL));
            $buttonBar->addButton($newButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }

        // Add paste button if clipboard is initialized
        if ($this->filelist->clipObj instanceof Clipboard && $this->folderObject->checkActionPermission('write')) {
            $elFromTable = $this->filelist->clipObj->elFromTable('_FILE');
            if (!empty($elFromTable)) {
                $addPasteButton = true;
                $elToConfirm = [];
                foreach ($elFromTable as $key => $element) {
                    $clipBoardElement = $resourceFactory->retrieveFileOrFolderObject($element);
                    if ($clipBoardElement instanceof Folder && $clipBoardElement->getStorage()->isWithinFolder($clipBoardElement,
                            $this->folderObject)
                    ) {
                        $addPasteButton = false;
                    }
                    $elToConfirm[$key] = $clipBoardElement->getName();
                }
                if ($addPasteButton) {
                    $confirmText = $this->filelist->clipObj
                        ->confirmMsgText('_FILE', $this->folderObject->getReadablePath(), 'into', $elToConfirm);
                    $pasteButton = $buttonBar->makeLinkButton()
                        ->setHref($this->filelist->clipObj
                            ->pasteUrl('_FILE', $this->folderObject->getCombinedIdentifier()))
                        ->setClasses('t3js-modal-trigger')
                        ->setDataAttributes([
                            'severity' => 'warning',
                            'content' => $confirmText,
                            'title' => $lang->getLL('clip_paste')
                        ])
                        ->setTitle($lang->getLL('clip_paste'))
                        ->setIcon($iconFactory->getIcon('actions-document-paste-into', Icon::SIZE_SMALL));
                    $buttonBar->addButton($pasteButton, ButtonBar::BUTTON_POSITION_LEFT, 2);
                }
            }
        }
    }

    /**
     * Returns an instance of LanguageService
     *
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * Returns the current BE user.
     *
     * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
     */
    protected function getBackendUser()
    {
        return $GLOBALS['BE_USER'];
    }
}
