<?php
namespace TYPO3\CMS\Backend\Controller\Wizard;

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

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\FormResultCompiler;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\DataPreprocessor;
use TYPO3\CMS\Backend\Form\FormEngine;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Script class for rendering the full screen RTE display
 */
class RteController extends AbstractWizardController {

	/**
	 * Document template object
	 *
	 * @var DocumentTemplate
	 */
	public $doc;

	/**
	 * Content accumulation for the module.
	 *
	 * @var string
	 */
	public $content;

	/**
	 * Wizard parameters, coming from FormEngine linking to the wizard.
	 *
	 * @var array
	 */
	public $P;

	/**
	 * If set, launch a new window with the current records pid.
	 *
	 * @var string
	 */
	public $popView;

	/**
	 * Set to the URL of this script including variables which is needed to re-display the form. See main()
	 *
	 * @var string
	 */
	public $R_URI;

	/**
	 * Module configuration
	 *
	 * @var array
	 */
	public $MCONF = array();

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_wizards.xlf');
		$GLOBALS['SOBE'] = $this;

		$this->init();
	}

	/**
	 * Initialization of the class
	 *
	 * @return void
	 */
	protected function init() {
		// Setting GPvars:
		$this->P = GeneralUtility::_GP('P');
		$this->popView = GeneralUtility::_GP('popView');
		$this->R_URI = GeneralUtility::linkThisScript(array('popView' => ''));
		// "Module name":
		$this->MCONF['name'] = 'wizard_rte';
		// Starting the document template object:
		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$this->doc->setModuleTemplate('EXT:backend/Resources/Private/Templates/wizard_rte.html');
		// Need to NOT have the page wrapped in DIV since if we do that we destroy
		// the feature that the RTE spans the whole height of the page!!!
		$this->doc->divClass = '';
		$this->doc->form = '<form action="' . htmlspecialchars(BackendUtility::getModuleUrl('tce_db')) . '" method="post" enctype="multipart/form-data" name="editform" onsubmit="return TBE_EDITOR.checkSubmit(1);">';
	}

	/**
	 * Injects the request object for the current request or subrequest
	 * As this controller goes only through the main() method, it is rather simple for now
	 *
	 * @param ServerRequestInterface $request
	 * @param ResponseInterface $response
	 * @return ResponseInterface
	 */
	public function mainAction(ServerRequestInterface $request, ResponseInterface $response) {
		$this->main();

		$response->getBody()->write($this->content);
		return $response;
	}

	/**
	 * Main function, rendering the document with the iFrame with the RTE in.
	 *
	 * @return void
	 */
	public function main() {
		// Translate id to the workspace version:
		if ($versionedRecord = BackendUtility::getWorkspaceVersionOfRecord($this->getBackendUserAuthentication()->workspace, $this->P['table'], $this->P['uid'], 'uid')) {
			$this->P['uid'] = $versionedRecord['uid'];
		}
		// If all parameters are available:
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			/** @var TcaDatabaseRecord $formDataGroup */
			$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
			/** @var FormDataCompiler $formDataCompiler */
			$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
			/** @var NodeFactory $nodeFactory */
			$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);

			$formDataCompilerInput = [
				'vanillaUid' => (int)$this->P['uid'],
				'tableName' => $this->P['table'],
				'command' => 'edit',
				'disabledWizards' => TRUE,
			];

			$formData = $formDataCompiler->compile($formDataCompilerInput);

			$formData['fieldListToRender'] = $this->P['field'];
			$formData['renderType'] = 'outerWrapContainer';
			$formResult = $nodeFactory->create($formData)->render();

			/** @var FormResultCompiler $formResultCompiler */
			$formResultCompiler = GeneralUtility::makeInstance(FormResultCompiler::class);
			$formResultCompiler->mergeResult($formResult);

			// override the default jumpToUrl
			$this->doc->JScodeArray['jumpToUrl'] = '
				function jumpToUrl(URL,formEl) {
					if (document.editform) {
						if (!TBE_EDITOR.isFormChanged()) {
							window.location.href = URL;
						} else if (formEl) {
							if (formEl.type=="checkbox") formEl.checked = formEl.checked ? 0 : 1;
						}
					} else {
						window.location.href = URL;
					}
				}
			';

			// Setting JavaScript of the pid value for viewing:
			if ($this->popView) {
				$this->doc->JScode = $this->doc->wrapScriptTags(
					BackendUtility::viewOnClick($formData['databaseRow']['pid'], '', BackendUtility::BEgetRootLine($formData['databaseRow']['pid']))
				);
			}

			$pageTsConfigMerged = $formData['pageTsConfigMerged'];
			if ((string)$pageTsConfigMerged['TCEFORM.'][$this->P['table'] . '.'][$this->P['field'] . '.']['RTEfullScreenWidth'] !== '') {
				$width = (string)$pageTsConfigMerged['TCEFORM.'][$this->P['table'] . '.'][$this->P['field'] . '.']['RTEfullScreenWidth'];
			} else {
				$width = '100%';
			}
			// Get the form field and wrap it in the table with the buttons:
			$formContent = $formResult['html'];
			$formContent = '
				<table border="0" cellpadding="0" cellspacing="0" width="' . $width . '" id="typo3-rtewizard">
					<tr>
						<td width="' . $width . '" colspan="2" id="c-formContent">' . $formContent . '</td>
						<td></td>
					</tr>
				</table>';

			// Adding hidden fields:
			$formContent .= '<input type="hidden" name="redirect" value="' . htmlspecialchars($this->R_URI) . '" />
						<input type="hidden" name="_serialNumber" value="' . md5(microtime()) . '" />';
			// Finally, add the whole setup:
			$this->content .= $formResultCompiler->JStop() . $formContent . $formResultCompiler->printNeededJSFunctions();
		} else {
			// ERROR:
			$this->content .= $this->doc->section($this->getLanguageService()->getLL('forms_title'), '<span class="text-danger">' . $this->getLanguageService()->getLL('table_noData', TRUE) . '</span>', 0, 1);
		}
		// Setting up the buttons and markers for docHeader
		$docHeaderButtons = $this->getButtons();
		$markers['CONTENT'] = $this->content;
		// Build the <body> for the module
		$this->content = $this->doc->startPage('');
		$this->content .= $this->doc->moduleBody(array(), $docHeaderButtons, $markers);
		$this->content .= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use mainAction() instead
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 */
	protected function getButtons() {
		$buttons = array(
			'close' => '',
			'save' => '',
			'save_view' => '',
			'save_close' => '',
			'shortcut' => '',
			'undo' => ''
		);
		if ($this->P['table'] && $this->P['field'] && $this->P['uid'] && $this->checkEditAccess($this->P['table'], $this->P['uid'])) {
			$closeUrl = GeneralUtility::sanitizeLocalUrl($this->P['returnUrl']);
			// Getting settings for the undo button:
			$undoButton = 0;
			$databaseConnection = $this->getDatabaseConnection();
			$undoRes = $databaseConnection->exec_SELECTquery('tstamp', 'sys_history', 'tablename=' . $databaseConnection->fullQuoteStr($this->P['table'], 'sys_history') . ' AND recuid=' . (int)$this->P['uid'], '', 'tstamp DESC', '1');
			if ($undoButtonR = $databaseConnection->sql_fetch_assoc($undoRes)) {
				$undoButton = 1;
			}
			// Close
			$buttons['close'] = '<a href="#" onclick="' . htmlspecialchars('jumpToUrl(' . GeneralUtility::quoteJSvalue($closeUrl) . '); return false;') . '" title="' . $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '">' . $this->iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL) . '</a>';
			// Save
			$buttons['save'] = '<button class="c-inputButton" name="_savedok_x" onclick="TBE_EDITOR.checkAndDoSubmit(1); return false;" title="'
				. $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '">'
				. $this->iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL)
				. '</button>';
			// Save & View
			$buttons['save_view'] = '<button class="c-inputButton" name="_savedokview_x" onclick="' . htmlspecialchars('document.editform.redirect.value+=\'&popView=1\'; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '"  title="'
				. $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDocShow', TRUE) . '">'
				. $this->iconFactory->getIcon('actions-document-save-view', Icon::SIZE_SMALL)
				. '</button>';
			// Save & Close
			$buttons['save_close'] = '<button class="c-inputButton" name="_saveandclosedok_x" onclick="' . htmlspecialchars('document.editform.redirect.value=' . GeneralUtility::quoteJSvalue($closeUrl) . '; TBE_EDITOR.checkAndDoSubmit(1); return false;') . '" title="'
				. $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '">'
				. $this->iconFactory->getIcon('actions-document-save-close', Icon::SIZE_SMALL)
				. '</button>';
			// Undo/Revert:
			if ($undoButton) {
				$aOnClick = 'window.location.href=' .
					GeneralUtility::quoteJSvalue(
						BackendUtility::getModuleUrl(
							'record_history',
							array(
								'element' => $this->P['table'] . ':' . $this->P['uid'],
								'revert' => 'field:' . $this->P['field'],
								'sumUp' => -1,
								'returnUrl' => $this->R_URI,
							)
						)
					) . '; return false;';
				$buttons['undo'] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"' . ' title="' . htmlspecialchars(sprintf($this->getLanguageService()->getLL('undoLastChange'), BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $undoButtonR['tstamp']), $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')))) . '">' . $this->iconFactory->getIcon('actions-edit-undo', Icon::SIZE_SMALL) . '</a>';
			}
			// Shortcut
			if ($this->getBackendUserAuthentication()->mayMakeShortcut()) {
				$buttons['shortcut'] = $this->doc->makeShortcutIcon('P', '', $this->MCONF['name'], 1);
			}
		}
		return $buttons;
	}

}
