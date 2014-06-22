<?php
namespace TYPO3\CMS\Backend\Controller\File;

/**
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Gateway for TCE (TYPO3 Core Engine) file-handling through POST forms.
 * This script serves as the fileadministration part of the TYPO3 Core Engine.
 * Basically it includes two libraries which are used to manipulate files on the server.
 * Before TYPO3 4.3, it was located in typo3/tce_file.php and redirected back to a
 * $redirectURL. Since 4.3 this class is also used for accessing via AJAX
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class FileController {

	// Internal, static: GPvar:
	// Array of file-operations.
	protected $file;

	// Clipboard operations array
	protected $CB;

	// If existing files should be overridden.
	protected $overwriteExistingFiles;

	// VeriCode - a hash of server specific value and other things which
	// identifies if a submission is OK. (see $GLOBALS['BE_USER']->veriCode())
	protected $vC;

	// the page where the user should be redirected after everything is done
	protected $redirect;

	/**
	 * Internal, dynamic:
	 * File processor object
	 *
	 * @var \TYPO3\CMS\Core\Utility\File\ExtendedFileUtility
	 */
	protected $fileProcessor;

	// the result array from the file processor
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
		$this->vC = GeneralUtility::_GP('vC');
		$this->redirect = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('redirect'));
		$this->initClipboard();
		$this->fileProcessor = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility');
	}

	/**
	 * Initialize the Clipboard. This will fetch the data about files to paste/delete if such an action has been sent.
	 *
	 * @return void
	 */
	public function initClipboard() {
		if (is_array($this->CB)) {
			$clipObj = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
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
		$this->fileProcessor->dontCheckForUnique = $this->overwriteExistingFiles ? 1 : 0;
		// Checking referrer / executing:
		$refInfo = parse_url(GeneralUtility::getIndpEnv('HTTP_REFERER'));
		$httpHost = GeneralUtility::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost != $refInfo['host'] && $this->vC != $GLOBALS['BE_USER']->veriCode() && !$GLOBALS['TYPO3_CONF_VARS']['SYS']['doNotCheckReferer'] && $GLOBALS['CLIENT']['BROWSER'] != 'flash') {
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
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The AjaxRequestHandler object used to return content and set content types
	 * @return void
	 */
	public function processAjaxRequest(array $params, \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj) {
		$this->init();
		$this->main();
		$errors = $this->fileProcessor->getErrorMessages();
		if (count($errors)) {
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
	 * Flatten result value from FileProcessor
	 *
	 * The value can be a File, Folder or boolean
	 *
	 * @param bool|\TYPO3\CMS\Core\Resource\File|\TYPO3\CMS\Core\Resource\Folder $result
	 * @return bool|string|array
	 */
	protected function flattenResultDataValue($result) {

		if ($result instanceof \TYPO3\CMS\Core\Resource\File) {
			$result = array_merge(
				$result->toArray(),
				array (
					'date' => BackendUtility::date($result->getModificationTime()),
					'iconClasses' => \TYPO3\CMS\Backend\Utility\IconUtility::mapFileExtensionToSpriteIconClass($result->getExtension()),
				)
			);
		} elseif ($result instanceof \TYPO3\CMS\Core\Resource\Folder) {
			$result = $result->getIdentifier();
		}

		return $result;
	}
}
