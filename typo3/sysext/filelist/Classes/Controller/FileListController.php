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

use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
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
use TYPO3\CMS\Extbase\Utility\LocalizationUtility;
use TYPO3\CMS\Filelist\FileList;

/**
 * Script Class for creating the list of files in the File > Filelist module
 */
class FileListController extends \TYPO3\CMS\Extbase\Mvc\Controller\ActionController {

	/**
	 * Module configuration
	 *
	 * @var array
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8. The Module gets configured by ExtensionManagementUtility::addModule() in ext_tables.php
	 */
	public $MCONF = array();

	/**
	 * @var array
	 */
	public $MOD_MENU = array();

	/**
	 * @var array
	 */
	public $MOD_SETTINGS = array();

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
	 *
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
	public $filelist = NULL;

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
	 * @param \TYPO3\CMS\Core\Resource\FileRepository $fileRepository
	 */
	public function injectFileRepository(\TYPO3\CMS\Core\Resource\FileRepository $fileRepository) {
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
	public function initializeObject() {
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
				/** @var $fileFactory ResourceFactory */
				$fileFactory = GeneralUtility::makeInstance(ResourceFactory::class);
				$storage = $fileFactory->getStorageObjectFromCombinedIdentifier($combinedIdentifier);
				$identifier = substr($combinedIdentifier, strpos($combinedIdentifier, ':') + 1);
				if (!$storage->hasFolder($identifier)) {
					$identifier = $storage->getFolderIdentifierFromFileIdentifier($identifier);
				}

				$this->folderObject = $fileFactory->getFolderObjectFromCombinedIdentifier($storage->getUid() . ':' . $identifier);
				// Disallow access to fallback storage 0
				if ($storage->getUid() === 0) {
					throw new Exception\InsufficientFolderAccessPermissionsException('You are not allowed to access files outside your storages', 1434539815);
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
			$this->folderObject = NULL;
			$this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
				sprintf(
					$this->getLanguageService()->getLL('missingFolderPermissionsMessage', TRUE),
					htmlspecialchars($this->id)
				),
				$this->getLanguageService()->getLL('missingFolderPermissionsTitle', TRUE),
				FlashMessage::NOTICE
			);
		} catch (Exception $fileException) {
			// Set folder object to null and throw a message later on
			$this->folderObject = NULL;
			// Take the first object of the first storage
			$fileStorages = $this->getBackendUser()->getFileStorages();
			$fileStorage = reset($fileStorages);
			if ($fileStorage instanceof \TYPO3\CMS\Core\Resource\ResourceStorage) {
				$this->folderObject = $fileStorage->getRootLevelFolder();
				if (!$fileStorage->isWithinFileMountBoundaries($this->folderObject)) {
					$this->folderObject = NULL;
				}
			}
			$this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
				sprintf(
					$this->getLanguageService()->getLL('folderNotFoundMessage', TRUE),
					htmlspecialchars($this->id)
				),
				$this->getLanguageService()->getLL('folderNotFoundTitle', TRUE),
				FlashMessage::NOTICE
			);
		} catch (\RuntimeException $e) {
			$this->folderObject = NULL;
			$this->errorMessage = GeneralUtility::makeInstance(FlashMessage::class,
				$e->getMessage() . ' (' . $e->getCode() . ')',
				$this->getLanguageService()->getLL('folderNotFoundTitle', TRUE),
				FlashMessage::NOTICE
			);
		}

		if ($this->folderObject && !$this->folderObject->getStorage()->checkFolderActionPermission('read', $this->folderObject)) {
			$this->folderObject = NULL;
		}

		// Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
		$this->menuConfig();
	}

	/**
	 * Setting the menu/session variables
	 *
	 * @return void
	 */
	public function menuConfig() {
		// MENU-ITEMS:
		// If array, then it's a selector box menu
		// If empty string it's just a variable, that will be saved.
		// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'sort' => '',
			'reverse' => '',
			'displayThumbs' => '',
			'clipBoard' => '',
			'bigControlPanel' => ''
		);
		// CLEANSE SETTINGS
		$this->MOD_SETTINGS = BackendUtility::getModuleData(
			$this->MOD_MENU,
			GeneralUtility::_GP('SET'),
			$this->moduleName
		);
	}

	/**
	 * @return void
	 */
	public function initializeIndexAction() {
		// Apply predefined values for hidden checkboxes
		// Set predefined value for DisplayBigControlPanel:
		$backendUser = $this->getBackendUser();
		if ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'activated') {
			$this->MOD_SETTINGS['bigControlPanel'] = TRUE;
		} elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'deactivated') {
			$this->MOD_SETTINGS['bigControlPanel'] = FALSE;
		}
		// Set predefined value for DisplayThumbnails:
		if ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'activated') {
			$this->MOD_SETTINGS['displayThumbs'] = TRUE;
		} elseif ($backendUser->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'deactivated') {
			$this->MOD_SETTINGS['displayThumbs'] = FALSE;
		}
		// Set predefined value for Clipboard:
		if ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'activated') {
			$this->MOD_SETTINGS['clipBoard'] = TRUE;
		} elseif ($backendUser->getTSConfigVal('options.file_list.enableClipBoard') === 'deactivated') {
			$this->MOD_SETTINGS['clipBoard'] = FALSE;
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
	public function indexAction() {

		// There there was access to this file path, continue, make the list
		if ($this->folderObject) {

			$requireJsModules = ['TYPO3/CMS/Filelist/FileListLocalisation', 'TYPO3/CMS/Filelist/FileSearch'];
			$addJsInlineLabels = [];

			// Create filelisting object
			$this->filelist = GeneralUtility::makeInstance(FileList::class, $this);
			$this->filelist->thumbs = $this->MOD_SETTINGS['displayThumbs'];
			// Create clipboard object and initialize that
			$this->filelist->clipObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
			$this->filelist->clipObj->fileMode = 1;
			$this->filelist->clipObj->initializeClipboard();
			$CB = GeneralUtility::_GET('CB');
			if ($this->cmd == 'setCB') {
				$CB['el'] = $this->filelist->clipObj->cleanUpCBC(array_merge(GeneralUtility::_POST('CBH'), (array)GeneralUtility::_POST('CBC')), '_FILE');
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
					$FILE = array();
					foreach ($items as $v) {
						$FILE['delete'][] = array('data' => $v);
					}
					// Init file processing object for deleting and pass the cmd array.
					/** @var ExtendedFileUtility $fileProcessor */
					$fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
					$fileProcessor->init(array(), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
					$fileProcessor->setActionPermissions();
					$fileProcessor->setExistingFilesConflictMode($this->overwriteExistingFiles);
					$fileProcessor->start($FILE);
					$fileProcessor->processData();
					$fileProcessor->pushErrorMessagesToFlashMessageQueue();
				}
			}
			// Start up filelisting object, include settings.
			$this->pointer = MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
			$this->filelist->start($this->folderObject, $this->pointer, $this->MOD_SETTINGS['sort'], $this->MOD_SETTINGS['reverse'], $this->MOD_SETTINGS['clipBoard'], $this->MOD_SETTINGS['bigControlPanel']);
			// Generate the list
			$this->filelist->generateList();
			// Set top JavaScript:
			$addJsInline = 'if (top.fsMod) top.fsMod.recentIds["file"] = "' . rawurlencode($this->id) . '";' . $this->filelist->CBfunctions();
			// Setting up the buttons and markers for docheader
			list($buttons, $otherMarkers) = $this->filelist->getButtonsAndOtherMarkers($this->folderObject);
			// add the folder info to the marker array
			$otherMarkers['FOLDER_INFO'] = $this->filelist->getFolderInfo();
			$docHeaderButtons = array_merge($this->getButtons(), $buttons);

			// Include DragUploader only if we have write access
			if ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File')
				&& $this->folderObject->checkActionPermission('write')
			) {
				$requireJsModules[] = 'TYPO3/CMS/Backend/DragUploader';
				$addJsInlineLabelFiles[] = [
					'file' => ExtensionManagementUtility::extPath('lang') . 'locallang_core.xlf',
					'prefix' => 'file_upload'
				];
			}

			$this->view->assign('otherMarkers', $otherMarkers);
			$this->view->assign('docHeaderButtons', $docHeaderButtons);
			$this->view->assign('pageTitle', $this->getLanguageService()->getLL('files'));
			$this->view->assign('requireJsModules', $requireJsModules);
			$this->view->assign('addJsInlineLabelFiles', $addJsInlineLabelFiles);
			$this->view->assign('addJsInline', $addJsInline);
			$this->view->assign('headline', $this->getModuleHeadline());
			$this->view->assign('listHtml', $this->filelist->HTMLcode);
			$this->view->assign('checkboxes', [
				'bigControlPanel' => [
					'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'selectable',
					'label' => $this->getLanguageService()->getLL('bigControlPanel', TRUE),
					'html' => BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '', '', 'id="bigControlPanel"'),
				],
				'displayThumbs' => [
					'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'selectable',
					'label' => $this->getLanguageService()->getLL('displayThumbs', TRUE),
					'html' => BackendUtility::getFuncCheck($this->id, 'SET[displayThumbs]', $this->MOD_SETTINGS['displayThumbs'], '', '', 'id="checkDisplayThumbs"'),
				],
				'enableClipBoard' => [
					'enabled' => $this->getBackendUser()->getTSConfigVal('options.file_list.enableClipBoard') === 'selectable',
					'label' => $this->getLanguageService()->getLL('clipBoard', TRUE),
					'html' => BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '', '', 'id="checkClipBoard"'),
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
	public function missingFolderAction() {
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
	public function searchAction($searchWord = '') {
		if (empty($searchWord)) {
			$this->forward('index');
		}

		$fileFacades = [];
		$files = $this->fileRepository->searchByName($this->folderObject, $searchWord);

		if (empty($files)) {
			$this->controllerContext->getFlashMessageQueue('core.template.flashMessages')->addMessage(
				new FlashMessage(LocalizationUtility::translate('flashmessage.no_results', 'filelist'), '', FlashMessage::INFO)
			);
		} else {
			foreach ($files as $file) {
				$fileFacades[] = new \TYPO3\CMS\Filelist\FileFacade($file);
			}
		}

		$this->view->assign('requireJsModules', ['TYPO3/CMS/Filelist/FileList', 'TYPO3/CMS/Filelist/FileSearch']);
		$this->view->assign('searchWord', $searchWord);
		$this->view->assign('files', $fileFacades);
		$this->view->assign('settings', [
			'jsConfirmationDelete' => $this->getBackendUser()->jsConfirmation(JsConfirmation::DELETE)
		]);
	}

	/**
	 * Get main headline based on active folder or storage for backend module
	 *
	 * Folder names are resolved to their special names like done in the tree view.
	 *
	 * @return string
	 */
	protected function getModuleHeadline() {
		$name = $this->folderObject->getName();
		if ($name === '') {
			// Show storage name on storage root
			if ($this->folderObject->getIdentifier() === '/') {
				$name = $this->folderObject->getStorage()->getName();
			}
		} else {
			$name = key(ListUtility::resolveSpecialFolderNames(
				array($name => $this->folderObject)
			));
		}
		return $name;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	public function getButtons() {
		/** @var IconFactory $iconFactory */
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);

		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'upload' => '',
			'new' => ''
		);
		// Add shortcut
		if ($this->getBackendUser()->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('pointer,id,target,table', implode(',', array_keys($this->MOD_MENU)), $this->moduleName);
		}
		// FileList Module CSH:
		$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'filelist_module');
		// Upload button (only if upload to this directory is allowed)
		if ($this->folderObject && $this->folderObject->getStorage()->checkUserActionPermission('add', 'File') && $this->folderObject->checkActionPermission('write')) {
			$buttons['upload'] = '<a href="' . htmlspecialchars(
				BackendUtility::getModuleUrl(
					'file_upload',
					array(
						'target' => $this->folderObject->getCombinedIdentifier(),
						'returnUrl' => $this->filelist->listURL(),
					)
				)) . '" id="button-upload" title="' . $this->getLanguageService()->makeEntities($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.upload', TRUE)) . '">' . $iconFactory->getIcon('actions-edit-upload', Icon::SIZE_SMALL)->render() . '</a>';
		}
		// New folder button
		if ($this->folderObject && $this->folderObject->checkActionPermission('write')
			&& ($this->folderObject->getStorage()->checkUserActionPermission('add', 'File') || $this->folderObject->checkActionPermission('add'))
		) {
			$buttons['new'] = '<a href="' . htmlspecialchars(
				BackendUtility::getModuleUrl(
					'file_newfolder',
					array(
						'target' => $this->folderObject->getCombinedIdentifier(),
						'returnUrl' => $this->filelist->listURL(),
					)
				)) . '" title="' . $this->getLanguageService()->makeEntities($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.new', TRUE)) . '">' . $iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL)->render() . '</a>';
		}
		return $buttons;
	}

	/**
	 * Returns an instance of LanguageService
	 *
	 * @return \TYPO3\CMS\Lang\LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Returns the current BE user.
	 *
	 * @return \TYPO3\CMS\Core\Authentication\BackendUserAuthentication
	 */
	protected function getBackendUser() {
		return $GLOBALS['BE_USER'];
	}
}
