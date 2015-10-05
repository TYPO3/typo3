<?php
namespace TYPO3\CMS\Backend\Form\Container;

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

use TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedContentEditException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\InlineRelatedRecordResolver;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Database\RelationHandler;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Type\Bitmask\Permission;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * Render a single inline record relation.
 *
 * This container is called by InlineControlContainer to render single existing records.
 * Furthermore it is called by FormEngine for an incoming ajax request to expand an existing record
 * or to create a new one.
 *
 * This container creates the outer HTML of single inline records - eg. drag and drop and delete buttons.
 * For rendering of the record itself processing is handed over to FullRecordContainer.
 */
class InlineRecordContainer extends AbstractContainer {

	/**
	 * Inline data array used for JSON output
	 *
	 * @var array
	 */
	protected $inlineData = array();

	/**
	 * @var InlineStackProcessor
	 */
	protected $inlineStackProcessor;

	/**
	 * Array containing instances of hook classes called once for IRRE objects
	 *
	 * @var array
	 */
	protected $hookObjects = array();

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Default constructor
	 *
	 * @param NodeFactory $nodeFactory
	 * @param array $data
	 */
	public function __construct(NodeFactory $nodeFactory, array $data) {
		parent::__construct($nodeFactory, $data);
		$this->iconFactory = GeneralUtility::makeInstance(IconFactory::class);
	}

	/**
	 * Entry method
	 *
	 * @return array As defined in initializeResultArray() of AbstractNode
	 * @throws AccessDeniedContentEditException
	 */
	public function render() {
		$this->inlineData = $this->data['inlineData'];

		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$this->inlineStackProcessor = $inlineStackProcessor;
		$inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

		$this->initHookObjects();

		$row = $this->data['databaseRow'];
		$parentUid = $row['uid'];
		$record = $this->data['inlineRelatedRecordToRender'];
		$config = $this->data['inlineRelatedRecordConfig'];

		$foreign_table = $config['foreign_table'];
		$foreign_selector = $config['foreign_selector'];
		$resultArray = $this->initializeResultArray();

		// Send a mapping information to the browser via JSON:
		// e.g. data[<curTable>][<curId>][<curField>] => data-<pid>-<parentTable>-<parentId>-<parentField>-<curTable>-<curId>-<curField>
		$formPrefix = $inlineStackProcessor->getCurrentStructureFormPrefix();
		$domObjectId = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
		$this->inlineData['map'][$formPrefix] = $domObjectId;

		$resultArray['inlineData'] = $this->inlineData;

		// Set this variable if we handle a brand new unsaved record:
		$isNewRecord = !MathUtility::canBeInterpretedAsInteger($record['uid']);
		// Set this variable if the record is virtual and only show with header and not editable fields:
		$isVirtualRecord = isset($record['__virtual']) && $record['__virtual'];
		// If there is a selector field, normalize it:
		if ($foreign_selector) {
			$valueToNormalize = $record[$foreign_selector];
			if (is_array($record[$foreign_selector])) {
				// @todo: this can be kicked again if always prepared rows are handled here
				$valueToNormalize = implode(',', $record[$foreign_selector]);
			}
			$record[$foreign_selector] = $this->normalizeUid($valueToNormalize);
		}
		if (!$this->checkAccess(($isNewRecord ? 'new' : 'edit'), $foreign_table, $record['uid'])) {
			// This is caught by InlineControlContainer or FormEngine, they need to handle this case differently
			// @todo: This is actually not the correct exception, but this code will vanish if inline data stuff is within provider
			throw new AccessDeniedContentEditException('Access denied', 1437081986);
		}
		// Get the current naming scheme for DOM name/id attributes:
		$appendFormFieldNames = '[' . $foreign_table . '][' . $record['uid'] . ']';
		$objectId = $domObjectId . '-' . $foreign_table . '-' . $record['uid'];
		$class = '';
		$html = '';
		$combinationHtml = '';
		if (!$isVirtualRecord) {
			// Get configuration:
			$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
			$expandAll = isset($config['appearance']['collapseAll']) && !$config['appearance']['collapseAll'];
			$ajaxLoad = !isset($config['appearance']['ajaxLoad']) || $config['appearance']['ajaxLoad'];
			if ($isNewRecord) {
				// Show this record expanded or collapsed
				$isExpanded = $expandAll || (!$collapseAll ? 1 : 0);
			} else {
				$isExpanded = $config['renderFieldsOnly'] || !$collapseAll && $this->getExpandedCollapsedState($foreign_table, $record['uid']) || $expandAll;
			}
			// Render full content ONLY IF this is an AJAX request, a new record, the record is not collapsed or AJAX loading is explicitly turned off
			if ($isNewRecord || $isExpanded || !$ajaxLoad) {
				$combinationChildArray = $this->renderCombinationTable($record, $appendFormFieldNames, $config);
				$combinationHtml = $combinationChildArray['html'];
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $combinationChildArray);

				$overruleTypesArray = isset($config['foreign_types']) ? $config['foreign_types'] : array();
				$childArray = $this->renderRecord($foreign_table, $record, $overruleTypesArray);
				$html = $childArray['html'];
				$childArray['html'] = '';
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);

				// Replace returnUrl in Wizard-Code, if this is an AJAX call
				$ajaxArguments = GeneralUtility::_GP('ajax');
				if (isset($ajaxArguments[2]) && trim($ajaxArguments[2]) != '') {
					$html = str_replace('P[returnUrl]=%2F' . rawurlencode(TYPO3_mainDir) . 'ajax.php', 'P[returnUrl]=' . rawurlencode($ajaxArguments[2]), $html);
				}
			} else {
				// This string is the marker for the JS-function to check if the full content has already been loaded
				$html = '<!--notloaded-->';
			}
			if ($isNewRecord) {
				// Get the top parent table
				$top = $this->inlineStackProcessor->getStructureLevel(0);
				$ucFieldName = 'uc[inlineView][' . $top['table'] . '][' . $top['uid'] . ']' . $appendFormFieldNames;
				// Set additional fields for processing for saving
				$html .= '<input type="hidden" name="data' . $appendFormFieldNames . '[pid]" value="' . $record['pid'] . '"/>';
				$html .= '<input type="hidden" name="' . $ucFieldName . '" value="' . $isExpanded . '" />';
			} else {
				// Set additional field for processing for saving
				$html .= '<input type="hidden" name="cmd' . $appendFormFieldNames . '[delete]" value="1" disabled="disabled" />';
				if (!$isExpanded
					&& !empty($GLOBALS['TCA'][$foreign_table]['ctrl']['enablecolumns']['disabled'])
					&& $ajaxLoad
				) {
					$checked = !empty($record['hidden']) ? ' checked="checked"' : '';
					$html .= '<input type="checkbox" name="data' . $appendFormFieldNames . '[hidden]_0" value="1"' . $checked . ' />';
					$html .= '<input type="input" name="data' . $appendFormFieldNames . '[hidden]" value="' . $record['hidden'] . '" />';
				}
			}
			// If this record should be shown collapsed
			$class = $isExpanded ? 'panel-visible' : 'panel-collapsed';
		}
		if ($config['renderFieldsOnly']) {
			$html = $html . $combinationHtml;
		} else {
			// Set the record container with data for output
			if ($isVirtualRecord) {
				$class .= ' t3-form-field-container-inline-placeHolder';
			}
			if (isset($record['hidden']) && (int)$record['hidden']) {
				$class .= ' t3-form-field-container-inline-hidden';
			}
			$class .= ($isNewRecord ? ' inlineIsNewRecord' : '');
			$html = '
				<div class="panel panel-default panel-condensed ' . trim($class) . '" id="' . $objectId . '_div">
					<div class="panel-heading" data-toggle="formengine-inline" id="' . $objectId . '_header" data-expandSingle="' . ($config['appearance']['expandSingle'] ? 1 : 0) . '">
						<div class="form-irre-header">
							<div class="form-irre-header-cell form-irre-header-icon">
								<span class="caret"></span>
							</div>
							' . $this->renderForeignRecordHeader($parentUid, $foreign_table, $record, $config, $isVirtualRecord) . '
						</div>
					</div>
					<div class="panel-collapse" id="' . $objectId . '_fields" data-expandSingle="' . ($config['appearance']['expandSingle'] ? 1 : 0) . '" data-returnURL="' . htmlspecialchars(GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . $html . $combinationHtml . '</div>
				</div>';
		}

		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * Creates main container for foreign record and renders it
	 *
	 * @param string $table The table name
	 * @param array $row The record to be rendered
	 * @param array $overruleTypesArray Overrule TCA [types] array, e.g to override [showitem] configuration of a particular type
	 * @return string The rendered form
	 */
	protected function renderRecord($table, array $row, array $overruleTypesArray = array()) {
		$domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

		$options = $this->data['tabAndInlineStack'];
		$options['tabAndInlineStack'][] = array(
			'inline',
			$domObjectId . '-' . $table . '-' . $row['uid'],
		);

		$command = 'edit';
		$vanillaUid = (int)$row['uid'];

		// If dealing with a new record, take pid as vanillaUid and set command to new
		if (!MathUtility::canBeInterpretedAsInteger($row['uid'])) {
			$command = 'new';
			$vanillaUid = (int)$row['pid'];
		}

		/** @var TcaDatabaseRecord $formDataGroup */
		$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
		/** @var FormDataCompiler $formDataCompiler */
		$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
		$formDataCompilerInput = [
			'command' => $command,
			'vanillaUid' => $vanillaUid,
			'tableName' => $table,
			'inlineData' => $this->inlineData,
			'tabAndInlineStack' => $options['tabAndInlineStack'],
			'overruleTypesArray' => $overruleTypesArray,
			'inlineStructure' => $this->data['inlineStructure'],
		];
		$options = $formDataCompiler->compile($formDataCompilerInput);
		$options['renderType'] = 'fullRecordContainer';

		// @todo: This hack merges data from already prepared row over fresh row again.
		// @todo: This really must fall ...
		foreach ($row as $field => $value) {
			if ($command === 'new' && is_string($value) && $value !== '' && array_key_exists($field, $options['databaseRow'])) {
				$options['databaseRow'][$field] = $value;
			}
		}

		return $this->nodeFactory->create($options)->render();
	}

	/**
	 * Render a table with FormEngine, that occurs on an intermediate table but should be editable directly,
	 * so two tables are combined (the intermediate table with attributes and the sub-embedded table).
	 * -> This is a direct embedding over two levels!
	 *
	 * @param array $record The table record of the child/embedded table
	 * @param string $appendFormFieldNames The [<table>][<uid>] of the parent record (the intermediate table)
	 * @param array $config content of $PA['fieldConf']['config']
	 * @return array As defined in initializeResultArray() of AbstractNode
	 * @todo: Maybe create another container from this?
	 */
	protected function renderCombinationTable($record, $appendFormFieldNames, $config = array()) {
		$resultArray = $this->initializeResultArray();

		$foreign_table = $config['foreign_table'];
		$foreign_selector = $config['foreign_selector'];

		if ($foreign_selector && $config['appearance']['useCombination']) {
			$comboConfig = $GLOBALS['TCA'][$foreign_table]['columns'][$foreign_selector]['config'];
			// If record does already exist, load it:
			/** @var InlineRelatedRecordResolver $inlineRelatedRecordResolver */
			$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);
			if ($record[$foreign_selector] && MathUtility::canBeInterpretedAsInteger($record[$foreign_selector])) {
				$comboRecord = $inlineRelatedRecordResolver->getRecord($comboConfig['foreign_table'], $record[$foreign_selector]);
				$isNewRecord = FALSE;
			} else {
				$comboRecord = $inlineRelatedRecordResolver->getNewRecord($this->data['inlineFirstPid'], $comboConfig['foreign_table']);
				$isNewRecord = TRUE;
			}

			// Display Warning FlashMessage if it is not suppressed
			if (!isset($config['appearance']['suppressCombinationWarning']) || empty($config['appearance']['suppressCombinationWarning'])) {
				$combinationWarningMessage = 'LLL:EXT:lang/locallang_core.xlf:warning.inline_use_combination';
				if (!empty($config['appearance']['overwriteCombinationWarningMessage'])) {
					$combinationWarningMessage = $config['appearance']['overwriteCombinationWarningMessage'];
				}
				$flashMessage = GeneralUtility::makeInstance(
					FlashMessage::class,
					$this->getLanguageService()->sL($combinationWarningMessage),
					'',
					FlashMessage::WARNING
				);
				$resultArray['html'] = $flashMessage->render();
			}

			// Get the FormEngine interpretation of the TCA of the child table
			$childArray = $this->renderRecord($comboConfig['foreign_table'], $comboRecord);
			$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);

			// If this is a new record, add a pid value to store this record and the pointer value for the intermediate table
			if ($isNewRecord) {
				$comboFormFieldName = 'data[' . $comboConfig['foreign_table'] . '][' . $comboRecord['uid'] . '][pid]';
				$resultArray['html'] .= '<input type="hidden" name="' . $comboFormFieldName . '" value="' . $comboRecord['pid'] . '" />';
			}
			// If the foreign_selector field is also responsible for uniqueness, tell the browser the uid of the "other" side of the relation
			if ($isNewRecord || $config['foreign_unique'] === $foreign_selector) {
				$parentFormFieldName = 'data' . $appendFormFieldNames . '[' . $foreign_selector . ']';
				$resultArray['html'] .= '<input type="hidden" name="' . $parentFormFieldName . '" value="' . $comboRecord['uid'] . '" />';
			}
		}
		return $resultArray;
	}

	/**
	 * Renders the HTML header for a foreign record, such as the title, toggle-function, drag'n'drop, etc.
	 * Later on the command-icons are inserted here.
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreign_table The foreign_table we create a header for
	 * @param array $rec The current record of that foreign_table
	 * @param array $config content of $PA['fieldConf']['config']
	 * @param bool $isVirtualRecord
	 * @return string The HTML code of the header
	 */
	protected function renderForeignRecordHeader($parentUid, $foreign_table, $rec, $config, $isVirtualRecord = FALSE) {
		// Init:
		$domObjectId = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
		$objectId = $domObjectId . '-' . $foreign_table . '-' . $rec['uid'];
		// We need the returnUrl of the main script when loading the fields via AJAX-call (to correct wizard code, so include it as 3rd parameter)
		// Pre-Processing:
		$isOnSymmetricSide = RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
		$hasForeignLabel = (bool)(!$isOnSymmetricSide && $config['foreign_label']);
		$hasSymmetricLabel = (bool)$isOnSymmetricSide && $config['symmetric_label'];

		// Get the record title/label for a record:
		// Try using a self-defined user function only for formatted labels
		if (isset($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc'])) {
			$params = array(
				'table' => $foreign_table,
				'row' => $rec,
				'title' => '',
				'isOnSymmetricSide' => $isOnSymmetricSide,
				'options' => isset($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc_options'])
					? $GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc_options']
					: array(),
				'parent' => array(
					'uid' => $parentUid,
					'config' => $config
				)
			);
			// callUserFunction requires a third parameter, but we don't want to give $this as reference!
			$null = NULL;
			GeneralUtility::callUserFunction($GLOBALS['TCA'][$foreign_table]['ctrl']['formattedLabel_userFunc'], $params, $null);
			$recTitle = $params['title'];

			// Try using a normal self-defined user function
		} elseif (isset($GLOBALS['TCA'][$foreign_table]['ctrl']['label_userFunc'])) {
			$params = array(
				'table' => $foreign_table,
				'row' => $rec,
				'title' => '',
				'isOnSymmetricSide' => $isOnSymmetricSide,
				'parent' => array(
					'uid' => $parentUid,
					'config' => $config
				)
			);
			// callUserFunction requires a third parameter, but we don't want to give $this as reference!
			$null = NULL;
			GeneralUtility::callUserFunction($GLOBALS['TCA'][$foreign_table]['ctrl']['label_userFunc'], $params, $null);
			$recTitle = $params['title'];
		} elseif ($hasForeignLabel || $hasSymmetricLabel) {
			$titleCol = $hasForeignLabel ? $config['foreign_label'] : $config['symmetric_label'];
			$foreignConfig = FormEngineUtility::getInlinePossibleRecordsSelectorConfig($config, $titleCol);
			// Render title for everything else than group/db:
			if ($foreignConfig['type'] !== 'groupdb') {
				$recTitle = BackendUtility::getProcessedValueExtra($foreign_table, $titleCol, $rec[$titleCol], 0, 0, FALSE);
			} else {
				// $recTitle could be something like: "tx_table_123|...",
				$valueParts = GeneralUtility::trimExplode('|', $rec[$titleCol]);
				$itemParts = GeneralUtility::revExplode('_', $valueParts[0], 2);
				$recTemp = BackendUtility::getRecordWSOL($itemParts[0], $itemParts[1]);
				$recTitle = BackendUtility::getRecordTitle($itemParts[0], $recTemp, FALSE);
			}
			$recTitle = BackendUtility::getRecordTitlePrep($recTitle);
			if (trim($recTitle) === '') {
				$recTitle = BackendUtility::getNoRecordTitle(TRUE);
			}
		} else {
			$recTitle = BackendUtility::getRecordTitle($foreign_table, FormEngineUtility::databaseRowCompatibility($rec), TRUE);
		}

		$altText = BackendUtility::getRecordIconAltText($rec, $foreign_table);

		$iconImg = '<span title="' . $altText . '" id="' . htmlspecialchars($objectId) . '_icon' . '">' . $this->iconFactory->getIconForRecord($foreign_table, $rec, Icon::SIZE_SMALL)->render() . '</span>';
		$label = '<span id="' . $objectId . '_label">' . $recTitle . '</span>';
		$ctrl = $this->renderForeignRecordHeaderControl($parentUid, $foreign_table, $rec, $config, $isVirtualRecord);
		$thumbnail = FALSE;

		// Renders a thumbnail for the header
		if (!empty($config['appearance']['headerThumbnail']['field'])) {
			$fieldValue = $rec[$config['appearance']['headerThumbnail']['field']];
			$firstElement = array_shift(GeneralUtility::trimExplode(',', $fieldValue));
			$fileUid = array_pop(BackendUtility::splitTable_Uid($firstElement));

			if (!empty($fileUid)) {
				$fileObject = ResourceFactory::getInstance()->getFileObject($fileUid);
				if ($fileObject && $fileObject->isMissing()) {
					$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($fileObject);
					$thumbnail = $flashMessage->render();
				} elseif ($fileObject) {
					$imageSetup = $config['appearance']['headerThumbnail'];
					unset($imageSetup['field']);
					if (!empty($rec['crop'])) {
						$imageSetup['crop'] = $rec['crop'];
					}
					$imageSetup = array_merge(array('width' => '45', 'height' => '45c'), $imageSetup);
					$processedImage = $fileObject->process(ProcessedFile::CONTEXT_IMAGECROPSCALEMASK, $imageSetup);
					// Only use a thumbnail if the processing process was successful by checking if image width is set
					if ($processedImage->getProperty('width')) {
						$imageUrl = $processedImage->getPublicUrl(TRUE);
						$thumbnail = '<img src="' . $imageUrl . '" ' .
							'width="' . $processedImage->getProperty('width') . '" ' .
							'height="' . $processedImage->getProperty('height') . '" ' .
							'alt="' . htmlspecialchars($altText) . '" ' .
							'title="' . htmlspecialchars($altText) . '">';
					}
				}
			}
		}

		if (!empty($config['appearance']['headerThumbnail']['field']) && $thumbnail) {
			$mediaContainer = '<div class="form-irre-header-cell form-irre-header-thumbnail" id="' . $objectId . '_thumbnailcontainer">' . $thumbnail . '</div>';
		} else {
			$mediaContainer = '<div class="form-irre-header-cell form-irre-header-icon" id="' . $objectId . '_iconcontainer">' . $iconImg . '</div>';
		}
		$header = $mediaContainer . '
				<div class="form-irre-header-cell form-irre-header-body">' . $label . '</div>
				<div class="form-irre-header-cell form-irre-header-control t3js-formengine-irre-control">' . $ctrl . '</div>';

		return $header;
	}

	/**
	 * Render the control-icons for a record header (create new, sorting, delete, disable/enable).
	 * Most of the parts are copy&paste from TYPO3\CMS\Recordlist\RecordList\DatabaseRecordList and
	 * modified for the JavaScript calls here
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreign_table The table (foreign_table) we create control-icons for
	 * @param array $rec The current record of that foreign_table
	 * @param array $config (modified) TCA configuration of the field
	 * @param bool $isVirtualRecord TRUE if the current record is virtual, FALSE otherwise
	 * @return string The HTML code with the control-icons
	 */
	protected function renderForeignRecordHeaderControl($parentUid, $foreign_table, $rec, $config = array(), $isVirtualRecord = FALSE) {
		$languageService = $this->getLanguageService();
		$backendUser = $this->getBackendUserAuthentication();
		// Initialize:
		$cells = array();
		$additionalCells = array();
		$isNewItem = substr($rec['uid'], 0, 3) == 'NEW';
		$isParentExisting = MathUtility::canBeInterpretedAsInteger($parentUid);
		$tcaTableCtrl = &$GLOBALS['TCA'][$foreign_table]['ctrl'];
		$tcaTableCols = &$GLOBALS['TCA'][$foreign_table]['columns'];
		$isPagesTable = $foreign_table === 'pages';
		$isSysFileReferenceTable = $foreign_table === 'sys_file_reference';
		$isOnSymmetricSide = RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
		$enableManualSorting = $tcaTableCtrl['sortby'] || $config['MM'] || !$isOnSymmetricSide && $config['foreign_sortby'] || $isOnSymmetricSide && $config['symmetric_sortby'];
		$nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
		$nameObjectFt = $nameObject . '-' . $foreign_table;
		$nameObjectFtId = $nameObjectFt . '-' . $rec['uid'];
		$calcPerms = $backendUser->calcPerms(BackendUtility::readPageAccess($rec['pid'], $backendUser->getPagePermsClause(1)));
		// If the listed table is 'pages' we have to request the permission settings for each page:
		$localCalcPerms = FALSE;
		if ($isPagesTable) {
			$localCalcPerms = $backendUser->calcPerms(BackendUtility::getRecord('pages', $rec['uid']));
		}
		// This expresses the edit permissions for this particular element:
		$permsEdit = $isPagesTable && $localCalcPerms & Permission::PAGE_EDIT || !$isPagesTable && $calcPerms & Permission::CONTENT_EDIT;
		// Controls: Defines which controls should be shown
		$enabledControls = $config['appearance']['enabledControls'];
		// Hook: Can disable/enable single controls for specific child records:
		foreach ($this->hookObjects as $hookObj) {
			/** @var InlineElementHookInterface $hookObj */
			$hookObj->renderForeignRecordHeaderControl_preProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $enabledControls);
		}
		if (isset($rec['__create'])) {
			$cells['localize.isLocalizable'] = '<span title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize.isLocalizable', TRUE) . '">'
				. $this->iconFactory->getIcon('actions-edit-localize-status-low', Icon::SIZE_SMALL)->render()
				. '</span>';
		} elseif (isset($rec['__remove'])) {
			$cells['localize.wasRemovedInOriginal'] = '<span title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize.wasRemovedInOriginal', TRUE) . '">'
				. $this->iconFactory->getIcon('actions-edit-localize-status-high', Icon::SIZE_SMALL)->render()
				. '</span>';
		}
		// "Info": (All records)
		if ($enabledControls['info'] && !$isNewItem) {
			if ($rec['table_local'] === 'sys_file') {
				$uid = (int)substr($rec['uid_local'], 9);
				$table = '_FILE';
			} else {
				$uid = $rec['uid'];
				$table = $foreign_table;
			}
			$cells['info'] = '
				<a class="btn btn-default" href="#" onclick="' . htmlspecialchars(('top.launchView(' . GeneralUtility::quoteJSvalue($table) . ', ' . GeneralUtility::quoteJSvalue($uid) . '); return false;')) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:showInfo', TRUE) . '">
					' . $this->iconFactory->getIcon('actions-document-info', Icon::SIZE_SMALL)->render() . '
				</a>';
		}
		// If the table is NOT a read-only table, then show these links:
		if (!$tcaTableCtrl['readOnly'] && !$isVirtualRecord) {
			// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
			if ($enabledControls['new'] && ($enableManualSorting || $tcaTableCtrl['useColumnsForDefaultValues'])) {
				if (!$isPagesTable && $calcPerms & Permission::CONTENT_EDIT || $isPagesTable && $calcPerms & Permission::PAGE_NEW) {
					$onClick = 'return inline.createNewRecord(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ',' . GeneralUtility::quoteJSvalue($rec['uid']) . ')';
					$style = '';
					if ($config['inline']['inlineNewButtonStyle']) {
						$style = ' style="' . $config['inline']['inlineNewButtonStyle'] . '"';
					}
					$cells['new'] = '
						<a class="btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'] . '" href="#" onclick="' . htmlspecialchars($onClick) . '" title="' . $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:new' . ($isPagesTable ? 'Page' : 'Record')), TRUE) . '" ' . $style . '>
							' . $this->iconFactory->getIcon('actions-' . ($isPagesTable ? 'page' : 'document') . '-new', Icon::SIZE_SMALL)->render() . '
						</a>';
				}
			}
			// "Up/Down" links
			if ($enabledControls['sort'] && $permsEdit && $enableManualSorting) {
				// Up
				$onClick = 'return inline.changeSorting(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ', \'1\')';
				$style = $config['inline']['first'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
				$cells['sort.up'] = '
					<a class="btn btn-default sortingUp" href="#" onclick="' . htmlspecialchars($onClick) . '" ' . $style . ' title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveUp', TRUE) . '">
						' . $this->iconFactory->getIcon('actions-move-up', Icon::SIZE_SMALL)->render() . '
					</a>';
				// Down
				$onClick = 'return inline.changeSorting(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ', \'-1\')';
				$style = $config['inline']['last'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
				$cells['sort.down'] = '
					<a class="btn btn-default sortingDown" href="#" onclick="' . htmlspecialchars($onClick) . '" ' . $style . ' title="' . $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveDown', TRUE) . '">
						' . $this->iconFactory->getIcon('actions-move-down', Icon::SIZE_SMALL)->render() . '
					</a>';
			}
			// "Edit" link:
			if (($rec['table_local'] === 'sys_file') && !$isNewItem) {
				$sys_language_uid = 0;
				if (!empty($rec['sys_language_uid'])) {
					$sys_language_uid = $rec['sys_language_uid'][0];
				}
				$recordInDatabase = $this->getDatabaseConnection()->exec_SELECTgetSingleRow(
					'uid',
					'sys_file_metadata',
					'file = ' . (int)substr($rec['uid_local'], 9) . ' AND sys_language_uid = ' . $sys_language_uid
				);
				if ($backendUser->check('tables_modify', 'sys_file_metadata')) {
					$url = BackendUtility::getModuleUrl('record_edit', array(
						'edit[sys_file_metadata][' . (int)$recordInDatabase['uid'] . ']' => 'edit'
					));
					$editOnClick = 'if (top.content.list_frame) {' .
						'top.content.list_frame.location.href=' .
						GeneralUtility::quoteJSvalue($url . '&returnUrl=') .
						'+top.rawurlencode(top.content.list_frame.document.location.pathname+top.content.list_frame.document.location.search)' .
						';' .
					'}';
					$title = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:cm.editMetadata');
					$cells['editmetadata'] = '
						<a class="btn btn-default" href="#" class="btn" onclick="' . htmlspecialchars($editOnClick) . '" title="' . htmlspecialchars($title) . '">
							' . $this->iconFactory->getIcon('actions-document-open', Icon::SIZE_SMALL)->render() . '
						</a>';
				}
			}
			// "Delete" link:
			if ($enabledControls['delete'] && ($isPagesTable && $localCalcPerms & Permission::PAGE_DELETE
					|| !$isPagesTable && $calcPerms & Permission::CONTENT_EDIT
					|| $isSysFileReferenceTable && $calcPerms & Permission::PAGE_EDIT)
			) {
				$title = $languageService->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:delete', TRUE);
				$icon = $this->iconFactory->getIcon('actions-edit-delete', Icon::SIZE_SMALL)->render();
				$cells['delete'] = '<a href="#" class="btn btn-default t3js-editform-delete-inline-record" data-objectid="' . htmlspecialchars($nameObjectFtId) . '" title="' . $title . '">' . $icon . '</a>';
			}

			// "Hide/Unhide" links:
			$hiddenField = $tcaTableCtrl['enablecolumns']['disabled'];
			if ($enabledControls['hide'] && $permsEdit && $hiddenField && $tcaTableCols[$hiddenField] && (!$tcaTableCols[$hiddenField]['exclude'] || $backendUser->check('non_exclude_fields', $foreign_table . ':' . $hiddenField))) {
				$onClick = 'return inline.enableDisableRecord(' . GeneralUtility::quoteJSvalue($nameObjectFtId) . ')';
				$className = 't3js-' . $nameObjectFtId . '_disabled';
				if ($rec[$hiddenField]) {
					$title = $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:unHide' . ($isPagesTable ? 'Page' : '')), TRUE);
					$cells['hide.unhide'] = '
						<a class="btn btn-default hiddenHandle ' . $className . '" href="#" onclick="'
						. htmlspecialchars($onClick) . '"' . 'title="' . $title . '">' .
						$this->iconFactory->getIcon('actions-edit-unhide', Icon::SIZE_SMALL)->render() . '
						</a>';
				} else {
					$title = $languageService->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:hide' . ($isPagesTable ? 'Page' : '')), TRUE);
					$cells['hide.hide'] = '
						<a class="btn btn-default hiddenHandle ' . $className . '" href="#" onclick="'
						. htmlspecialchars($onClick) . '"' . 'title="' . $title . '">' .
						$this->iconFactory->getIcon('actions-edit-hide', Icon::SIZE_SMALL)->render() . '
						</a>';
				}
			}
			// Drag&Drop Sorting: Sortable handler for script.aculo.us
			if ($enabledControls['dragdrop'] && $permsEdit && $enableManualSorting && $config['appearance']['useSortable']) {
				$additionalCells['dragdrop'] = '
					<span class="btn btn-default sortableHandle" data-id="' . htmlspecialchars($rec['uid']) . '" title="' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.move', TRUE) . '">
						' . $this->iconFactory->getIcon('actions-move-move', Icon::SIZE_SMALL)->render() . '
					</span>';
			}
		} elseif ($isVirtualRecord && $isParentExisting) {
			if ($enabledControls['localize'] && isset($rec['__create'])) {
				$onClick = 'inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($nameObjectFt) . ', ' . GeneralUtility::quoteJSvalue($rec['uid']) . ');';
				$cells['localize'] = '
					<a class="btn btn-default" href="#" onclick="' . htmlspecialchars($onClick) . 'title="' . $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localize', TRUE) . '">
						' . $this->iconFactory->getIcon('actions-document-localize', Icon::SIZE_SMALL)->render() . '
					</a>';
			}
		}
		// If the record is edit-locked by another user, we will show a little warning sign:
		if ($lockInfo = BackendUtility::isRecordLocked($foreign_table, $rec['uid'])) {
			$cells['locked'] = '
				<a class="btn btn-default" href="#" onclick="alert(' . GeneralUtility::quoteJSvalue($lockInfo['msg']) . ');return false;">
					' . '<span title="' . htmlspecialchars($lockInfo['msg']) . '">' . $this->iconFactory->getIcon('status-warning-in-use', Icon::SIZE_SMALL)->render() . '</span>' . '
				</a>';
		}
		// Hook: Post-processing of single controls for specific child records:
		foreach ($this->hookObjects as $hookObj) {
			$hookObj->renderForeignRecordHeaderControl_postProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $cells);
		}

		$out = '';
		if (!empty($cells)) {
			$out .= ' <div class="btn-group btn-group-sm" role="group">' . implode('', $cells) . '</div>';
		}
		if (!empty($additionalCells)) {
			$out .= ' <div class="btn-group btn-group-sm" role="group">' . implode('', $additionalCells) . '</div>';
		}
		return $out;
	}

	/**
	 * Checks the page access rights (Code for access check mostly taken from alt_doc.php)
	 * as well as the table access rights of the user.
	 *
	 * @param string $cmd The command that should be performed ('new' or 'edit')
	 * @param string $table The table to check access for
	 * @param string $theUid The record uid of the table
	 * @return bool Returns TRUE is the user has access, or FALSE if not
	 */
	protected function checkAccess($cmd, $table, $theUid) {
		// @todo This should be within data provider
		$backendUser = $this->getBackendUserAuthentication();
		// Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
		// First, resetting flags.
		$hasAccess = FALSE;
		// Admin users always have access:
		if ($backendUser->isAdmin()) {
			return TRUE;
		}
		// If the command is to create a NEW record...:
		if ($cmd === 'new') {
			// If the pid is numerical, check if it's possible to write to this page:
			if (MathUtility::canBeInterpretedAsInteger($this->data['inlineFirstPid'])) {
				$calcPRec = BackendUtility::getRecord('pages', $this->data['inlineFirstPid']);
				if (!is_array($calcPRec)) {
					return FALSE;
				}
				// Permissions for the parent page
				$CALC_PERMS = $backendUser->calcPerms($calcPRec);
				// If pages:
				if ($table === 'pages') {
					// Are we allowed to create new subpages?
					$hasAccess = (bool)($CALC_PERMS & Permission::PAGE_NEW);
				} else {
					// Are we allowed to edit the page?
					if ($table === 'sys_file_reference' && $this->isMediaOnPages($theUid)) {
						$hasAccess = (bool)($CALC_PERMS & Permission::PAGE_EDIT);
					}
					if (!$hasAccess) {
						// Are we allowed to edit content on this page?
						$hasAccess = (bool)($CALC_PERMS & Permission::CONTENT_EDIT);
					}
				}
			} else {
				$hasAccess = TRUE;
			}
		} else {
			// Edit:
			$calcPRec = BackendUtility::getRecord($table, $theUid);
			BackendUtility::fixVersioningPid($table, $calcPRec);
			if (is_array($calcPRec)) {
				// If pages:
				if ($table === 'pages') {
					$CALC_PERMS = $backendUser->calcPerms($calcPRec);
					$hasAccess = (bool)($CALC_PERMS & Permission::PAGE_EDIT);
				} else {
					// Fetching pid-record first.
					$CALC_PERMS = $backendUser->calcPerms(BackendUtility::getRecord('pages', $calcPRec['pid']));
					if ($table === 'sys_file_reference' && $this->isMediaOnPages($theUid)) {
						$hasAccess = (bool)($CALC_PERMS & Permission::PAGE_EDIT);
					}
					if (!$hasAccess) {
						$hasAccess = (bool)($CALC_PERMS & Permission::CONTENT_EDIT);
					}
				}
				// Check internals regarding access
				$isRootLevelRestrictionIgnored = BackendUtility::isRootLevelRestrictionIgnored($table);
				if ($hasAccess || (int)$calcPRec['pid'] === 0 && $isRootLevelRestrictionIgnored) {
					$hasAccess = (bool)$backendUser->recordEditAccessInternals($table, $calcPRec);
				}
			}
		}
		if (!$backendUser->check('tables_modify', $table)) {
			$hasAccess = FALSE;
		}
		if (
			!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['checkAccess'])
			&& is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['checkAccess'])
		) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['checkAccess'] as $_funcRef) {
				$_params = array(
					'table' => $table,
					'uid' => $theUid,
					'cmd' => $cmd,
					'hasAccess' => $hasAccess
				);
				$hasAccess = GeneralUtility::callUserFunction($_funcRef, $_params, $this);
			}
		}
		if (!$hasAccess) {
			$deniedAccessReason = $backendUser->errorMsg;
			if ($deniedAccessReason) {
				debug($deniedAccessReason);
			}
		}
		return $hasAccess;
	}

	/**
	 * Checks if a uid of a child table is in the inline view settings.
	 *
	 * @param string $table Name of the child table
	 * @param int $uid uid of the the child record
	 * @return bool TRUE=expand, FALSE=collapse
	 */
	protected function getExpandedCollapsedState($table, $uid) {
		$inlineView = $this->data['inlineExpandCollapseStateArray'];
		// @todo Add checking/cleaning for unused tables, records, etc. to save space in uc-field
		if (isset($inlineView[$table]) && is_array($inlineView[$table])) {
			if (in_array($uid, $inlineView[$table]) !== FALSE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Normalize a relation "uid" published by transferData, like "1|Company%201"
	 *
	 * @param string $string A transferData reference string, containing the uid
	 * @return string The normalized uid
	 */
	protected function normalizeUid($string) {
		$parts = explode('|', $string);
		return $parts[0];
	}

	/**
	 * Initialized the hook objects for this class.
	 * Each hook object has to implement the interface
	 * \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface
	 *
	 * @throws \UnexpectedValueException
	 * @return void
	 */
	protected function initHookObjects() {
		$this->hookObjects = array();
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'])) {
			$tceformsInlineHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'];
			if (is_array($tceformsInlineHook)) {
				foreach ($tceformsInlineHook as $classData) {
					$processObject = GeneralUtility::getUserObj($classData);
					if (!$processObject instanceof InlineElementHookInterface) {
						throw new \UnexpectedValueException('$processObject must implement interface ' . InlineElementHookInterface::class, 1202072000);
					}
					$this->hookObjects[] = $processObject;
				}
			}
		}
	}

	/**
	 * Check if the record is a media element on a page.
	 *
	 * @param string $theUid Uid of the sys_file_reference record to be checked
	 * @return bool TRUE if the record has media in the column 'fieldname' and pages in the column 'tablenames'
	 */
	protected function isMediaOnPages($theUid) {
		if (StringUtility::beginsWith($theUid, 'NEW')) {
			return TRUE;
		}
		$row = BackendUtility::getRecord('sys_file_reference', $theUid);
		return ($row['fieldname'] === 'media') && ($row['tablenames'] === 'pages');
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DatabaseConnection
	 */
	protected function getDatabaseConnection() {
		return $GLOBALS['TYPO3_DB'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
