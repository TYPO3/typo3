<?php
namespace TYPO3\CMS\Backend\Controller\File;

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

use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Utility\File\ExtendedFileUtility;

/**
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the file administration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 * Before TYPO3 4.3, it was located in typo3/tce_file.php and redirected back to a
 * $redirectURL. Since 4.3 this class is also used for accessing via AJAX
 */
class FileController {

	/**
	 * Array of file-operations.
	 *
	 * @var array
	 */
	protected $file;

	/**
	 * Clipboard operations array
	 *
	 * @var array
	 */
	protected $CB;

	/**
	 * Defines behaviour when uploading files with names that already exist; possible value are 'cancel', 'replace', 'changeName'
	 *
	 * @var string
	 */
	protected $overwriteExistingFiles;

	/**
	 * VeriCode - a hash of server specific value and other things which
	 * identifies if a submission is OK. (see $GLOBALS['BE_USER']->veriCode())
	 *
	 * @var string
	 */
	protected $vC;

	/**
	 * The page where the user should be redirected after everything is done
	 *
	 * @var string
	 */
	protected $redirect;

	/**
	 * Internal, dynamic:
	 * File processor object
	 *
	 * @var ExtendedFileUtility
	 */
	protected $fileProcessor;

	/**
	 * The result array from the file processor
	 *
	 * @var array
	 */
	protected $fileData;

	/**
	 * Constructor
	 */
	public function __construct() {
		$GLOBALS['SOBE'] = $this;
		$this->init();
	}

	/**
	 * Registering incoming data
	 *
	 * @return void
	 */
	protected function init() {
		// Set the GPvars from outside
		$this->file = GeneralUtility::_GP('file');
		$this->CB = GeneralUtility::_GP('CB');
		$this->overwriteExistingFiles = GeneralUtility::_GP('overwriteExistingFiles');

		if ((string)$this->overwriteExistingFiles === '1') {
			GeneralUtility::deprecationLog('overwriteExitingFiles = 1 is deprecated. Use overwriteExitingFiles = "replace". Support for old behavior will be removed in TYPO3 CMS 8.');
			$this->overwriteExistingFiles = 'replace';
		}

		$this->vC = GeneralUtility::_GP('vC');
		$this->redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
		$this->initClipboard();
		$this->fileProcessor = GeneralUtility::makeInstance(ExtendedFileUtility::class);
	}

	/**
	 * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
	 *
	 * @return void
	 */
	public function initClipboard() {
		if (is_array($this->CB)) {
			$clipObj = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Clipboard\Clipboard::class);
			$clipObj->initializeClipboard();
			if ($this->CB['paste']) {
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->file = $clipObj->makePasteCmdArray_file($this->CB['paste'], $this->file);
			}
			if ($this->CB['delete']) {
				$clipObj->setCurrentPad($this->CB['pad']);
				$this->file = $clipObj->makeDeleteCmdArray_file($this->file);
			}
		}
	}

	/**
	 * Performing the file admin action:
	 * Initializes the objects, setting permissions, sending data to object.
	 *
	 * @return void
	 */
	public function main() {
		// Initializing:
		$this->fileProcessor->init(array(), $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
		$this->fileProcessor->setActionPermissions();
		switch ($this->overwriteExistingFiles) {
			case 'replace':
			case 'changeName':
				$conflictMode = $this->overwriteExistingFiles;
				break;
			default:
				$conflictMode = 'cancel';
				break;
		}
		$this->fileProcessor->setExistingFilesConflictMode($conflictMode);
		// Checking referrer / executing:
		$refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost !== $refInfo['host'] && $this->vC !== $this->getBackendUser()->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer']) {
			$this->fileProcessor->writeLog(0, 2, 1, 'Referrer host "%s" and server host "%s" did not match!', array($refInfo['host'], $httpHost));
		} else {
			$this->fileProcessor->start($this->file);
			$this->fileData = $this->fileProcessor->processData();
		}
	}

	/**
	 * Redirecting the user after the processing has been done.
	 * Might also display error messages directly, if any.
	 *
	 * @return void
	 */
	public function finish() {
		// Push errors to flash message queue, if there are any
		$this->fileProcessor->pushErrorMessagesToFlashMessageQueue();
		BackendUtility::setUpdateSignal('updateFolderTree');
		if ($this->redirect) {
			\TYPO3\CMS\Core\Utility\HttpUtility::redirect($this->redirect);
		}
	}

	/**
	 * Handles the actual process from within the ajaxExec function
	 * therefore, it does exactly the same as the real typo3/tce_file.php
	 * but without calling the "finish" method, thus makes it simpler to deal with the
	 * actual return value
	 *
	 * @param array $params Always empty.
	 * @param AjaxRequestHandler $ajaxObj The AjaxRequestHandler object used to return content and set content types
	 * @return void
	 */
	public function processAjaxRequest(array $params, AjaxRequestHandler $ajaxObj) {
		$this->init();
		$this->main();
		$errors = $this->fileProcessor->getErrorMessages();
		if (!empty($errors)) {
			$ajaxObj->setError(implode(',', $errors));
		} else {
			$flatResult = array();
			foreach ($this->fileData as $action => $results) {
				foreach ($results as $result) {
					if (is_array($result)) {
						foreach ($result as $subResult) {
							$flatResult[$action][] = $this->flattenResultDataValue($subResult);
						}
					} else {
						$flatResult[$action][] = $this->flattenResultDataValue($result);
					}
				}
			}
			$ajaxObj->addContent('result', $flatResult);
			if ($this->redirect) {
				$ajaxObj->addContent('redirect', $this->redirect);
			}
			$ajaxObj->setContentFormat('json');
		}
	}

	/**
	 * Ajax entry point to check if a file exists in a folder
	 *
	 * @param array $params Always empty.
	 * @param AjaxRequestHandler $ajaxObj The AjaxRequestHandler object used to return content and set content types
	 * @return void
	 */
	public function fileExistsAjaxRequest(array $params, AjaxRequestHandler $ajaxObj) {
		$fileName = GeneralUtility::_GP('fileName');
		$fileTarget = GeneralUtility::_GP('fileTarget');

		/** @var \TYPO3\CMS\Core\Resource\ResourceFactory $fileFactory */
		$fileFactory = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\ResourceFactory::class);
		/** @var Folder $fileTargetObject */
		$fileTargetObject = $fileFactory->retrieveFileOrFolderObject($fileTarget);
		$processedFileName = $fileTargetObject->getStorage()->sanitizeFileName($fileName, $fileTargetObject);

		$result = FALSE;
		if ($fileTargetObject->hasFile($processedFileName)) {
			$result = $this->flattenResultDataValue($fileTargetObject->getStorage()->getFileInFolder($processedFileName, $fileTargetObject));
		}
		$ajaxObj->addContent('result', $result);
		$ajaxObj->setContentFormat('json');
	}

	/**
	 * Flatten result value from FileProcessor
	 *
	 * The value can be a File, Folder or boolean
	 *
	 * @param bool|\TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $result
	 * @return bool|string|array
	 */
	protected function flattenResultDataValue($result) {
		if ($result instanceof \TYPO3\CMS\Core\Resource\File) {
			$thumbUrl = '';
			if (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $result->getExtension())) {
				$processedFile = $result->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array());
				if ($processedFile) {
					$thumbUrl = $processedFile->getPublicUrl(TRUE);
				}
			}
			$result = array_merge(
				$result->toArray(),
				array (
					'date' => BackendUtility::date($result->getModificationTime()),
					'iconClasses' => \TYPO3\CMS\Backend\Utility\IconUtility::mapFileExtensionToSpriteIconClass($result->getExtension()),
					'thumbUrl' => $thumbUrl
				)
			);
		} elseif ($result instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$result = $result->getIdentifier();
		}

		return $result;
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
