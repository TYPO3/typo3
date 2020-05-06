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
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Compatibility\PublicMethodDeprecationTrait;
use TYPO3\CMS\Core\Compatibility\PublicPropertyDeprecationTrait;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\DuplicationBehavior;
use TYPO3\CMS\Core\Resource\Exception;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\Search\FileSearchDemand;
use TYPO3\CMS\Core\Resource\Utility\ListUtility;
use TYPO3\CMS\Core\Type\Bitmask\JsConfirmation;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\Mvc\View\ViewInterface;
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\Configuration\ThumbnailConfiguration;
use TYPO3\CMS\Filelist\FileList;

/**
 * Script Class for creating the list of files in the File > Filelist module
 * @internal this is a concrete TYPO3 controller implementation and solely used for EXT:filelist and not part of TYPO3's Core API.
 */
class FileListController extends ActionController
{
    use PublicPropertyDeprecationTrait;
    use PublicMethodDeprecationTrait;

    private $deprecatedPublicProperties = [
        'MOD_MENU' => 'Using FileListController::$MOD_MENU is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'MOD_SETTINGS' => 'Using FileListController::$MOD_SETTINGS is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'doc' => 'Using FileListController::$doc is deprecated, property will be removed in TYPO3 v10.0.',
        'id' => 'Using FileListController::$id is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'pointer' => 'Using FileListController::$pointer is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'table' => 'Using FileListController::$table is deprecated, , property will be removed in TYPO3 v10.0.',
        'imagemode' => 'Using FileListController::$imagemode is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'cmd' => 'Using FileListController::$cmd is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'filelist' => 'Using FileListController::$filelist is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    private $deprecatedPublicMethods = [
        'menuConfig' => 'Using FileListController::menuConfig() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'initializeView' => 'Using FileListController::initializeView() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'initializeIndexAction' => 'Using FileListController::initializeIndexAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'indexAction' => 'Using FileListController::indexAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'missingFolderAction' => 'Using FileListController::missingFolderAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
        'searchAction' => 'Using FileListController::searchAction() is deprecated and will not be possible anymore in TYPO3 v10.0.',
    ];

    /**
     * @var array
     */
    protected $MOD_MENU = [];

    /**
     * @var array
     */
    protected $MOD_SETTINGS = [];

    /**
     * Document template object
     *
     * @var DocumentTemplate
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $doc;

    /**
     * "id" -> the path to list.
     *
     * @var string
     */
    protected $id;

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
    protected $pointer;

    /**
     * "Table"
     * @var string
     * @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0
     */
    protected $table;

    /**
     * Thumbnail mode.
     *
     * @var string
     */
    protected $imagemode;

    /**
     * @var string
     */
    protected $cmd;

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
    protected $filelist;

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
     * @throws \RuntimeException
     */
    public function initializeObject()
    {
        $this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
        $this->getLanguageService()->includeLLFile('EXT:filelist/Resources/Private/Language/locallang_mod_file_list.xlf');
        $this->getLanguageService()->includeLLFile('EXT:core/Resources/Private/Language/locallang_misc.xlf');

        // Setting GPvars:
        $this->id = ($combinedIdentifier = GeneralUtility::_GP('id'));
        $this->pointer = GeneralUtility::_GP('pointer');
        $this->table = GeneralUtility::_GP('table');
        $this->imagemode = GeneralUtility::_GP('imagemode');
        $this->cmd = GeneralUtility::_GP('cmd');
        $this->overwriteExistingFiles = DuplicationBehavior::cast(GeneralUtility::_GP('overwriteExistingFiles'));

        try {
            if ($combinedIdentifier) {
                $this->getBackendUser()->evaluateUserSpecificFileFilterSettings();
                $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);
                $storage = $resourceFactory->getStorageObjectFromCombinedIdentifier($combinedIdentifier);
                $identifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
                if (!$storage->hasFolder($identifier)) {
                    $identifier = $storage->getFolderIdentifierFromFileIdentifier($identifier);
                }

                $this->folderObject = $resourceFactory->getFolderObjectFromCombinedIdentifier($storage->getUid() . ':' . $identifier);
                // Disallow access to fallback storage 0
                if ($storage->getUid() === 0) {
                    throw new Exception\InsufficientFolderAccessPermissionsException(
                        'You are not allowed to access files outside your storages',
                        1434539815
                    );
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
            $this->errorMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->getLL('missingFolderPermissionsMessage'),
                    $this->id
                ),
                $this->getLanguageService()->getLL('missingFolderPermissionsTitle'),
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
            $this->errorMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                sprintf(
                    $this->getLanguageService()->getLL('folderNotFoundMessage'),
                    $this->id
                ),
                $this->getLanguageService()->getLL('folderNotFoundTitle'),
                FlashMessage::NOTICE
            );
        } catch (\RuntimeException $e) {
            $this->folderObject = null;
            $this->errorMessage = GeneralUtility::makeInstance(
                FlashMessage::class,
                $e->getMessage() . ' (' . $e->getCode() . ')',
                $this->getLanguageService()->getLL('folderNotFoundTitle'),
                FlashMessage::NOTICE
            );
        }

        if ($this->folderObject && !$this->folderObject->getStorage()->checkFolderActionPermission(
            'read',
            $this->folderObject
        )
        ) {
            $this->folderObject = null;
        }

        // Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
        $this->menuConfig();
    }

    /**
     * Setting the menu/session variables
     */
    protected function menuConfig()
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
     */
    protected function initializeView(ViewInterface $view)
    {
        /** @var BackendTemplateView $view */
        parent::initializeView($view);
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileListLocalisation');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileSearch');
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ContextMenu');
        $this->registerDocHeaderButtons();
    }

    /**
     */
    protected function initializeIndexAction()
    {
        // Apply predefined values for hidden checkboxes
        // Set predefined value for DisplayBigControlPanel:
        $backendUser = $this->getBackendUser();
        $userTsConfig = $backendUser->getTSConfig();
        if (($userTsConfig['options.']['file_list.']['enableDisplayBigControlPanel'] ?? '') === 'activated') {
            $this->MOD_SETTINGS['bigControlPanel'] = true;
        } elseif (($userTsConfig['options.']['file_list.']['enableDisplayBigControlPanel'] ?? '') === 'deactivated') {
            $this->MOD_SETTINGS['bigControlPanel'] = false;
        }
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
     */
    protected function indexAction()
    {
        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->setTitle($this->getLanguageService()->getLL('files'));

        // There there was access to this file path, continue, make the list
        if ($this->folderObject) {
            $userTsConfig = $this->getBackendUser()->getTSConfig();
            // @deprecated since TYPO3 v9, will be removed in TYPO3 v10.0. Argument $this will be removed in TYPO3 v10.0.
            $this->filelist = GeneralUtility::makeInstance(FileList::class, $this);
            $this->filelist->thumbs = $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && $this->MOD_SETTINGS['displayThumbs'];
            // Create clipboard object and initialize that
            $this->filelist->clipObj = GeneralUtility::makeInstance(Clipboard::class);
            $this->filelist->clipObj->fileMode = true;
            $this->filelist->clipObj->initializeClipboard();
            $CB = GeneralUtility::_GET('CB');
            if ($this->cmd === 'setCB') {
                $CB['el'] = $this->filelist->clipObj->cleanUpCBC(array_merge(
                    GeneralUtility::_POST('CBH'),
                    (array)GeneralUtility::_POST('CBC')
                ), '_FILE');
            }
            if (!$this->MOD_SETTINGS['clipBoard']) {
                $CB['setP'] = 'normal';
            }
            $this->filelist->clipObj->setCmd($CB);
            $this->filelist->clipObj->cleanCurrent();
            // Saves
            $this->filelist->clipObj->endClipboard();
            // If the "cmd" was to delete files from the list (clipboard thing), do that:
            if ($this->cmd === 'delete') {
                $items = $this->filelist->clipObj->cleanUpCBC(GeneralUtility::_POST('CBC'), '_FILE', 1);
                if (!empty($items)) {
                    // Make command array:
                    $FILE = [];
                    foreach ($items as $clipboardIdentifier => $combinedIdentifier) {
                        $FILE['delete'][] = ['data' => $combinedIdentifier];
                        $this->filelist->clipObj->removeElement($clipboardIdentifier);
                    }
                    // Init file processing object for deleting and pass the cmd array.
                    /** @var ExtendedFileUtility $fileProcessor */
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
            // Start up filelisting object, include settings.
            $this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
            $this->filelist->start(
                $this->folderObject,
                $this->pointer,
                $this->MOD_SETTINGS['sort'],
                $this->MOD_SETTINGS['reverse'],
                $this->MOD_SETTINGS['clipBoard'],
                $this->MOD_SETTINGS['bigControlPanel']
            );
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
                '
            );
            $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileDelete');
            $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf', 'buttons');

            // Include DragUploader only if we have write access
            if ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
                && $this->folderObject->checkActionPermission('write')
            ) {
                $pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DragUploader');
                $pageRenderer->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf', 'file_upload');
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
                    'enabled' => ($userTsConfig['options.']['file_list.']['enableDisplayBigControlPanel'] ?? '') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('bigControlPanel')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[bigControlPanel]',
                        $this->MOD_SETTINGS['bigControlPanel'] ?? '',
                        '',
                        '',
                        'id="bigControlPanel"'
                    ),
                ],
                'displayThumbs' => [
                    'enabled' => $GLOBALS['TYPO3_CONF_VARS']['GFX']['thumbnails'] && ($userTsConfig['options.']['file_list.']['enableDisplayThumbnails'] ?? '') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('displayThumbs')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[displayThumbs]',
                        $this->MOD_SETTINGS['displayThumbs'] ?? '',
                        '',
                        '',
                        'id="checkDisplayThumbs"'
                    ),
                ],
                'enableClipBoard' => [
                    'enabled' => ($userTsConfig['options.']['file_list.']['enableClipBoard'] ?? '') === 'selectable',
                    'label' => htmlspecialchars($this->getLanguageService()->getLL('clipBoard')),
                    'html' => BackendUtility::getFuncCheck(
                        $this->id,
                        'SET[clipBoard]',
                        $this->MOD_SETTINGS['clipBoard'] ?? '',
                        '',
                        '',
                        'id="checkClipBoard"'
                    ),
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
     */
    protected function missingFolderAction()
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
    protected function searchAction($searchWord = '')
    {
        if (empty($searchWord)) {
            $this->forward('index');
        }
        $searchDemand = FileSearchDemand::createForSearchTerm($searchWord)->withRecursive();
        $files = $this->folderObject->searchFiles($searchDemand);

        $fileFacades = [];
        if (count($files) === 0) {
            $this->controllerContext->getFlashMessageQueue('core.template.flashMessages')->addMessage(
                new FlashMessage(
                    LocalizationUtility::translate('flashmessage.no_results', 'filelist'),
                    '',
                    FlashMessage::INFO
                )
            );
        } else {
            foreach ($files as $file) {
                $fileFacades[] = new \TYPO3\CMS\Filelist\FileFacade($file);
            }
        }

        /** @var \TYPO3\CMS\Backend\Routing\UriBuilder $uriBuilder */
        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        $pageRenderer = $this->view->getModuleTemplate()->getPageRenderer();
        $pageRenderer->addInlineSetting('ShowItem', 'moduleUrl', (string)$uriBuilder->buildUriFromRoute('show_item'));
        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileList');

        $thumbnailConfiguration = GeneralUtility::makeInstance(ThumbnailConfiguration::class);
        $this->view->assign('thumbnail', [
            'width' => $thumbnailConfiguration->getWidth(),
            'height' => $thumbnailConfiguration->getHeight(),
        ]);

        $this->view->assign('searchWord', $searchWord);
        $this->view->assign('files', $fileFacades);
        $this->view->assign('deleteUrl', (string)$uriBuilder->buildUriFromRoute('tce_file'));
        $this->view->assign('settings', [
            'jsConfirmationDelete' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)
        ]);

        $pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileDelete');
        $pageRenderer->addInlineLanguageLabelFile('EXT:backend/Resources/Private/Language/locallang_alt_doc.xlf', 'buttons');
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
     */
    protected function registerButtons()
    {
        /** @var ButtonBar $buttonBar */
        $buttonBar = $this->view->getModuleTemplate()->getDocHeaderComponent()->getButtonBar();

        /** @var IconFactory $iconFactory */
        $iconFactory = $this->view->getModuleTemplate()->getIconFactory();

        $resourceFactory = GeneralUtility::makeInstance(ResourceFactory::class);

        $lang = $this->getLanguageService();

        $uriBuilder = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Routing\UriBuilder::class);

        // Refresh page
        $refreshLink = GeneralUtility::linkThisScript(
            [
                'target' => rawurlencode($this->folderObject->getCombinedIdentifier()),
                'imagemode' => $this->filelist->thumbs
            ]
        );
        $refreshButton = $buttonBar->makeLinkButton()
            ->setHref($refreshLink)
            ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.reload'))
            ->setIcon($iconFactory->getIcon('actions-refresh', Icon::SIZE_SMALL));
        $buttonBar->addButton($refreshButton, ButtonBar::BUTTON_POSITION_RIGHT);

        // Level up
        try {
            $currentStorage = $this->folderObject->getStorage();
            $parentFolder = $this->folderObject->getParentFolder();
            if ($parentFolder->getIdentifier() !== $this->folderObject->getIdentifier()
                && $currentStorage->isWithinFileMountBoundaries($parentFolder)
            ) {
                $levelUpClick = 'top.document.getElementsByName("nav_frame")[0].contentWindow.Tree.highlightActiveItem("file","folder'
                    . GeneralUtility::md5int($parentFolder->getCombinedIdentifier()) . '_"+top.fsMod.currentBank)';
                $levelUpButton = $buttonBar->makeLinkButton()
                    ->setHref((string)$uriBuilder->buildUriFromRoute('file_FilelistList', ['id' => $parentFolder->getCombinedIdentifier()]))
                    ->setOnClick($levelUpClick)
                    ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.upOneLevel'))
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
        if ($this->folderObject && $this->folderObject->getStorage()->checkUserActionPermission(
            'add',
            'File'
        ) && $this->folderObject->checkActionPermission('write')
        ) {
            $uploadButton = $buttonBar->makeLinkButton()
                ->setHref((string)$uriBuilder->buildUriFromRoute(
                    'file_upload',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->listURL(),
                    ]
                ))
                ->setClasses('t3js-drag-uploader-trigger')
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.upload'))
                ->setIcon($iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL));
            $buttonBar->addButton($uploadButton, ButtonBar::BUTTON_POSITION_LEFT, 1);
        }

        // New folder button
        if ($this->folderObject && $this->folderObject->checkActionPermission('write')
            && ($this->folderObject->getStorage()->checkUserActionPermission(
                'add',
                'File'
            ) || $this->folderObject->checkActionPermission('add'))
        ) {
            $newButton = $buttonBar->makeLinkButton()
                ->setHref((string)$uriBuilder->buildUriFromRoute(
                    'file_newfolder',
                    [
                        'target' => $this->folderObject->getCombinedIdentifier(),
                        'returnUrl' => $this->filelist->listURL(),
                    ]
                ))
                ->setTitle($lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:cm.new'))
                ->setIcon($iconFactory->getIcon('actions-add', Icon::SIZE_SMALL));
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
