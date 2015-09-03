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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Http\Response;

/**
 * Script Class for rendering the file editing screen
 */
class EditFileController implements \TYPO3\CMS\Core\Http\ControllerInterface {

	/**
	 * Module content accumulated.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * @var string
	 */
	public $title;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	/**
	 * Original input target
	 *
	 * @var string
	 */
	public $origTarget;

	/**
	 * The original target, but validated.
	 *
	 * @var string
	 */
	public $target;

	/**
	 * Return URL of list module.
	 *
	 * @var string
	 */
	public $returnUrl;

	/**
	 * the file that is being edited on
	 *
	 * @var \TYPO3\CMS\Core\Resource\AbstractFile
	 */
	protected $fileObject;

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$GLOBALS['SOBE'] = $this;
		$this->init();
	}

	/**
	 * Initialize script class
	 *
	 * @return void
	 * @throws \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException
	 */
	protected function init() {
		// Setting target, which must be a file reference to a file within the mounts.
		$this->target = ($this->origTarget = ($fileIdentifier = GeneralUtility::_GP('target')));
		$this->returnUrl = GeneralUtility::sanitizeLocalUrl(GeneralUtility::_GP('returnUrl'));
		// create the file object
		if ($fileIdentifier) {
			$this->fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->retrieveFileOrFolderObject($fileIdentifier);
		}
		// Cleaning and checking target directory
		if (!$this->fileObject) {
			$title = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:paramError', TRUE);
			$message = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_mod_file_list.xlf:targetNoDir', TRUE);
			throw new \RuntimeException($title . ': ' . $message, 1294586841);
		}
		if ($this->fileObject->getStorage()->getUid() === 0) {
			throw new \TYPO3\CMS\Core\Resource\Exception\InsufficientFileAccessPermissionsException('You are not allowed to access files outside your storages', 1375889832);
		}

		// Setting the title and the icon
		$icon = IconUtility::getSpriteIcon('apps-filetree-root');
		$this->title = $icon . htmlspecialchars($this->fileObject->getStorage()->getName()) . ': ' . htmlspecialchars($this->fileObject->getIdentifier());

		// Setting template object
		$this->doc = GeneralUtility::makeInstance(\TYPO3\CMS\Backend\Template\DocumentTemplate::class);
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/file_edit.html');
		$this->doc->JScode = $this->doc->wrapScriptTags('
			function backToList() {
				top.goToModule("file_FilelistList");
			}
		');
		$this->doc->form = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_file')) . '" method="post" name="editform">';
	}

	/**
	 * Main function, redering the actual content of the editing page
	 *
	 * @return void
	 */
	public function main() {
		$docHeaderButtons = $this->getButtons();
		$this->content = $this->doc->startPage($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.pagetitle'));
		// Hook	before compiling the output
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'])) {
			$preOutputProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['preOutputProcessingHook'];
			if (is_array($preOutputProcessingHook)) {
				$hookParameters = array(
					'content' => &$this->content,
					'target' => &$this->target
				);
				foreach ($preOutputProcessingHook as $hookFunction) {
					GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}
		$pageContent = $this->doc->header($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.pagetitle') . ' ' . htmlspecialchars($this->fileObject->getName()));
		$pageContent .= $this->doc->spacer(2);
		$code = '';
		$extList = $GLOBALS['TYPO3_CONF_VARS']['SYS']['textfile_ext'];
		try {
			if (!$extList || !GeneralUtility::inList($extList, $this->fileObject->getExtension())) {
				throw new \Exception('Files with that extension are not editable.');
			}
			// Read file content to edit:
			$fileContent = $this->fileObject->getContents();
			// Making the formfields
			$hValue = BackendUtility::getModuleUrl('file_edit', array(
				'target' => $this->origTarget,
				'returnUrl' => $this->returnUrl
			));
			// Edit textarea:
			$code .= '
				<div id="c-edit">
					<textarea rows="30" name="file[editfile][0][data]" wrap="off" ' . $this->doc->formWidth(48, TRUE, 'width:98%;height:80%') . ' class="text-monospace t3js-enable-tab">' . htmlspecialchars($fileContent) . '</textarea>
					<input type="hidden" name="file[editfile][0][target]" value="' . $this->fileObject->getUid() . '" />
					<input type="hidden" name="redirect" value="' . htmlspecialchars($hValue) . '" />
					' . \TYPO3\CMS\Backend\Form\FormEngine::getHiddenTokenField('tceAction') . '
				</div>
				<br />';
			// Make shortcut:
			if ($this->getBackendUser()->mayMakeShortcut()) {
				$docHeaderButtons['shortcut'] = $this->doc->makeShortcutIcon('target', '', 'file_edit', 1);
			} else {
				$docHeaderButtons['shortcut'] = '';
			}
		} catch (\Exception $e) {
			$code .= sprintf($this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.coundNot'), $extList);
		}
		// Ending of section and outputting editing form:
		$pageContent .= $this->doc->sectionEnd();
		$pageContent .= $code;
		// Hook	after compiling the output
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'])) {
			$postOutputProcessingHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['typo3/file_edit.php']['postOutputProcessingHook'];
			if (is_array($postOutputProcessingHook)) {
				$hookParameters = array(
					'pageContent' => &$pageContent,
					'target' => &$this->target
				);
				foreach ($postOutputProcessingHook as $hookFunction) {
					GeneralUtility::callUserFunction($hookFunction, $hookParameters, $this);
				}
			}
		}
		// Add the HTML as a section:
		$markerArray = array(
			'CSH' => $docHeaderButtons['csh'],
			'FUNC_MENU' => '',
			'BUTTONS' => $docHeaderButtons,
			'PATH' => $this->title,
			'CONTENT' => $pageContent
		);
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markerArray);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Processes the request, currently everything is handled and put together via "main()"
	 *
	 * @param ServerRequestInterface $request The request object
	 * @return ResponseInterface $response The response, created by the controller
	 */
	public function processRequest(ServerRequestInterface $request) {
		$this->main();
		/** @var Response $response */
		$response = GeneralUtility::makeInstance(Response::class);
		$response->getBody()->write($this->content);
		return $response;
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use the processRequest() method instead
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		echo $this->content;
	}

	/**
	 * Builds the buttons for the docheader and returns them as an array
	 *
	 * @return array
	 */
	public function getButtons() {
		$lang = $this->getLanguageService();
		$buttons = array();
		// CSH button
		$buttons['csh'] = BackendUtility::cshItem('xMOD_csh_corebe', 'file_edit');
		// Save button
		$theIcon = $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL);
		$buttons['SAVE'] = '<a href="#" onclick="document.editform.submit();" title="' . $lang->makeEntities($lang->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.submit', TRUE)) . '">' . $theIcon . '</a>';
		// Save and Close button
		$theIcon = $this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL);
		$buttons['SAVE_CLOSE'] = '<a href="#" onclick="document.editform.redirect.value=' . htmlspecialchars(GeneralUtility::quoteJSvalue($this->returnUrl)) . '; document.editform.submit();" title="' . $lang->makeEntities($lang->sL('LLL:EXT:lang/locallang_core.xlf:file_edit.php.saveAndClose', TRUE)) . '">' . $theIcon . '</a>';
		// Cancel button
		$theIcon = $this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL);
		$buttons['CANCEL'] = '<a href="#" onclick="backToList(); return false;" title="' . $lang->makeEntities($lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.cancel', TRUE)) . '">' . $theIcon . '</a>';
		return $buttons;
	}

	/**
	 * Returns LanguageService
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
