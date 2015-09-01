<?php
namespace TYPO3\CMS\Backend\Controller;

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
use TYPO3\CMS\Core\Http\Response;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TypoScript\Parser\TypoScriptParser;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;


/**
 * Script Class for grid wizard
 */
class BackendLayoutWizardController implements \TYPO3\CMS\Core\Http\ControllerInterface {

	// GET vars:
	// Wizard parameters, coming from TCEforms linking to the wizard.
	/**
	 * @var array
	 */
	public $P;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 */
	public $doc;

	// Accumulated content.
	/**
	 * @var string
	 */
	public $content;

	/**
	 * @var string
	 */
	public $formName;

	/**
	 * @var string
	 */
	public $fieldName;

	/**
	 * Constructor
	 */
	public function __construct(){
		$this->init();
	}

	/**
	 * Initialises the Class
	 *
	 * @return void
	 * @throws \InvalidArgumentException
	 */
	public function init() {
		$lang = $this->getLanguageService();
		$lang->includeLLFile('EXT:lang/locallang_wizards.xlf');

		// Setting GET vars (used in frameset script):
		$this->P = GeneralUtility::_GP('P');
		$this->formName = $this->P['formName'];
		$this->fieldName = $this->P['itemName'];
		$hmac_validate = GeneralUtility::hmac($this->formName . $this->fieldName, 'wizard_js');
		if (!$this->P['hmac'] || ($this->P['hmac'] !== $hmac_validate)) {
			throw new \InvalidArgumentException('Hmac Validation failed for backend_layout wizard', 1385811397);
		}
		$uid = (int)$this->P['uid'];
		// Initialize document object:
		$this->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		$pageRenderer->loadExtJS();
		$pageRenderer->addJsFile(ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/JavaScript/grideditor.js');
		$pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/Tooltip');
		$pageRenderer->addInlineSetting('ContextHelp', 'moduleUrl', BackendUtility::getModuleUrl('help_CshmanualCshmanual', array(
			'tx_cshmanual_help_cshmanualcshmanual' => array(
				'controller' => 'Help',
				'action' => 'detail'
			)
		)));
		$pageRenderer->addJsInlineCode('storeData', '
			function storeData(data) {
				if (parent.opener && parent.opener.document && parent.opener.document.' . $this->formName . ' && parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . ']) {
					parent.opener.document.' . $this->formName . '[' . GeneralUtility::quoteJSvalue($this->fieldName) . '].value = data;
					parent.opener.TBE_EDITOR.fieldChanged("backend_layout","' . $uid . '","config","data[backend_layout][' . $uid . '][config]");
				}
			}
			', FALSE);
		$languageLabels = array(
			'save' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_labelSave', TRUE),
			'title' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_windowTitle', TRUE),
			'editCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_editCell', TRUE),
			'mergeCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_mergeCell', TRUE),
			'splitCell' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_splitCell', TRUE),
			'name' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_name', TRUE),
			'column' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_column', TRUE),
			'notSet' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_notSet', TRUE),
			'nameHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_nameHelp', TRUE),
			'columnHelp' => $lang->sL('LLL:EXT:lang/locallang_wizards.xlf:grid_columnHelp', TRUE)
		);
		$pageRenderer->addInlineLanguageLabelArray($languageLabels);
		// Select record
		$record = $this->getDatabaseConnection()->exec_SELECTgetRows($this->P['field'], $this->P['table'], 'uid=' . (int)$this->P['uid']);
		if (trim($record[0][$this->P['field']]) == '') {
			$rows = array(array(array('colspan' => 1, 'rowspan' => 1, 'spanned' => FALSE, 'name' => '')));
			$colCount = 1;
			$rowCount = 1;
		} else {
			// load TS parser
			$parser = GeneralUtility::makeInstance(TypoScriptParser::class);
			$parser->parse($record[0][$this->P['field']]);
			$data = $parser->setup['backend_layout.'];
			$rows = array();
			$colCount = $data['colCount'];
			$rowCount = $data['rowCount'];
			$dataRows = $data['rows.'];
			$spannedMatrix = array();
			for ($i = 1; $i <= $rowCount; $i++) {
				$cells = array();
				$row = array_shift($dataRows);
				$columns = $row['columns.'];
				for ($j = 1; $j <= $colCount; $j++) {
					$cellData = array();
					if (!$spannedMatrix[$i][$j]) {
						if (is_array($columns) && !empty($columns)) {
							$column = array_shift($columns);
							if (isset($column['colspan'])) {
								$cellData['colspan'] = (int)$column['colspan'];
								$columnColSpan = (int)$column['colspan'];
								if (isset($column['rowspan'])) {
									$columnRowSpan = (int)$column['rowspan'];
									for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
										for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
											$spannedMatrix[$i + $spanRow][$j + $spanColumn] = 1;
										}
									}
								} else {
									for ($spanColumn = 0; $spanColumn < $columnColSpan; $spanColumn++) {
										$spannedMatrix[$i][$j + $spanColumn] = 1;
									}
								}
							} else {
								$cellData['colspan'] = 1;
								if (isset($column['rowspan'])) {
									$columnRowSpan = (int)$column['rowspan'];
									for ($spanRow = 0; $spanRow < $columnRowSpan; $spanRow++) {
										$spannedMatrix[$i + $spanRow][$j] = 1;
									}
								}
							}
							if (isset($column['rowspan'])) {
								$cellData['rowspan'] = (int)$column['rowspan'];
							} else {
								$cellData['rowspan'] = 1;
							}
							if (isset($column['name'])) {
								$cellData['name'] = $column['name'];
							}
							if (isset($column['colPos'])) {
								$cellData['column'] = (int)$column['colPos'];
							}
						}
					} else {
						$cellData = array('colspan' => 1, 'rowspan' => 1, 'spanned' => 1);
					}
					$cells[] = $cellData;
				}
				$rows[] = $cells;
				if (!empty($spannedMatrix[$i]) && is_array($spannedMatrix[$i])) {
					ksort($spannedMatrix[$i]);
				}
			}
		}
		$pageRenderer->addExtOnReadyCode('
			t3Grid = new TYPO3.Backend.t3Grid({
				data: ' . json_encode($rows, JSON_HEX_QUOT | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS) . ',
				colCount: ' . (int)$colCount . ',
				rowCount: ' . (int)$rowCount . ',
				targetElement: \'editor\'
			});
			t3Grid.drawTable();
			');
		$this->doc->styleSheetFile_post = ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/Css/grideditor.css';
	}

	/**
	 * Injects the request object for the current request or subrequest
	 * As this controller goes only through the main() method, it is rather simple for now
	 *
	 * @param ServerRequestInterface $request
	 * @return ResponseInterface $response
	 */
	public function processRequest(ServerRequestInterface $request) {
		$this->main();

		/** @var Response $response */
		$response = GeneralUtility::makeInstance(Response::class);
		$response->getBody()->write($this->doc->render('Grid wizard', $this->content));
		return $response;
	}

	/**
	 * Main Method, rendering either colorpicker or frameset depending on ->showPicker
	 *
	 * @return void
	 */
	public function main() {
		/** @var IconFactory $iconFactory */
		$iconFactory = GeneralUtility::makeInstance(IconFactory::class);
		$lang = $this->getLanguageService();
		$resourcePath = ExtensionManagementUtility::extRelPath('backend') . 'Resources/Public/Images/BackendLayoutWizard/';
		$content = '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());return true;">' . $iconFactory->getIcon('actions-document-save', Icon::SIZE_SMALL) . '</a>';
		$content .= '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.saveCloseDoc', TRUE) . '" onclick="storeData(t3Grid.export2LayoutRecord());window.close();return true;">' . IconUtility::getSpriteIcon('actions-document-save-close') . '</a>';
		$content .= '<a href="#" title="' . $lang->sL('LLL:EXT:lang/locallang_core.xlf:rm.closeDoc', TRUE) . '" onclick="window.close();return true;">' . $iconFactory->getIcon('actions-document-close', Icon::SIZE_SMALL) . '</a>';
		$content .= $this->doc->spacer(10);
		$content .= '
		<table border="0" width="90%" height="90%" id="outer_container">
			<tr>
				<td class="editor_cell">
					<div id="editor">
					</div>
				</td>
				<td width="20" valign="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addColumn') . '" onclick="t3Grid.addColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableright.png" border="0" />
					</a><br />
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeColumn') . '" onclick="t3Grid.removeColumn(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableleft.png" border="0" />
					</a>
				</td>
			</tr>
			<tr>
				<td colspan="2" height="20" align="center">
					<a class="addCol" href="#" title="' . $lang->getLL('grid_addRow') . '" onclick="t3Grid.addRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tabledown.png" border="0" />
					</a>
					<a class="removeCol" href="#" title="' . $lang->getLL('grid_removeRow') . '" onclick="t3Grid.removeRow(); t3Grid.drawTable(\'editor\');">
						<img src="' . $resourcePath . 't3grid-tableup.png" border="0" />
					</a>
				</td>
			</tr>
		</table>
		';
		$this->content = $content;
	}

	/**
	 * Returns the sourcecode to the browser
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8, use processRequest() instead
	 */
	public function printContent() {
		GeneralUtility::logDeprecatedFunction();
		echo $this->doc->render('Grid wizard', $this->content);
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
	 * Returns the database connection
	 *
	 * @return \TYPO3\CMS\Core\Database\DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

}
