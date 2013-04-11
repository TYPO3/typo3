<?php
namespace TYPO3\CMS\Backend\Form\Element;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2006-2013 Oliver Hader <oliver@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * The Inline-Relational-Record-Editing (IRRE) functions as part of the TCEforms.
 *
 * @author Oliver Hader <oliver@typo3.org>
 */
class InlineElement {

	const Structure_Separator = '-';
	const FlexForm_Separator = '---';
	const FlexForm_Substitute = ':';
	const Disposal_AttributeName = 'Disposal_AttributeName';
	const Disposal_AttributeId = 'Disposal_AttributeId';
	/**
	 * Reference to the calling TCEforms instance
	 *
	 * @var \TYPO3\CMS\Backend\Form\FormEngine
	 * @todo Define visibility
	 */
	public $fObj;

	// Reference to $fObj->backPath
	/**
	 * @todo Define visibility
	 */
	public $backPath;

	// Indicates if a field is rendered upon an AJAX call
	/**
	 * @todo Define visibility
	 */
	public $isAjaxCall = FALSE;

	// The structure/hierarchy where working in, e.g. cascading inline tables
	/**
	 * @todo Define visibility
	 */
	public $inlineStructure = array();

	// The first call of an inline type appeared on this page (pid of record)
	/**
	 * @todo Define visibility
	 */
	public $inlineFirstPid;

	// Keys: form, object -> hold the name/id for each of them
	/**
	 * @todo Define visibility
	 */
	public $inlineNames = array();

	// Inline data array used for JSON output
	/**
	 * @todo Define visibility
	 */
	public $inlineData = array();

	// Expanded/collapsed states for the current BE user
	/**
	 * @todo Define visibility
	 */
	public $inlineView = array();

	// Count the number of inline types used
	/**
	 * @todo Define visibility
	 */
	public $inlineCount = 0;

	/**
	 * @todo Define visibility
	 */
	public $inlineStyles = array();

	// How the $this->fObj->prependFormFieldNames should be set ('data' is default)
	/**
	 * @todo Define visibility
	 */
	public $prependNaming = 'data';

	// Reference to $this->fObj->prependFormFieldNames
	/**
	 * @todo Define visibility
	 */
	public $prependFormFieldNames;

	// Reference to $this->fObj->prependCmdFieldNames
	/**
	 * @todo Define visibility
	 */
	public $prependCmdFieldNames;

	// Array containing instances of hook classes called once for IRRE objects
	protected $hookObjects = array();

	/**
	 * Initialize
	 *
	 * @param \TYPO3\CMS\Backend\Form\FormEngine $tceForms Reference to an TCEforms instance
	 * @return void
	 * @todo Define visibility
	 */
	public function init(&$tceForms) {
		$this->fObj = $tceForms;
		$this->backPath = &$tceForms->backPath;
		$this->prependFormFieldNames = &$this->fObj->prependFormFieldNames;
		$this->prependCmdFieldNames = &$this->fObj->prependCmdFieldNames;
		$this->inlineStyles['margin-right'] = '5';
		$this->initHookObjects();
	}

	/**
	 * Initialized the hook objects for this class.
	 * Each hook object has to implement the interface
	 * \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface
	 *
	 * @return void
	 */
	protected function initHookObjects() {
		$this->hookObjects = array();
		if (isset($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'])) {
			$tceformsInlineHook = &$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms_inline.php']['tceformsInlineHook'];
			if (is_array($tceformsInlineHook)) {
				foreach ($tceformsInlineHook as $classData) {
					$processObject = \TYPO3\CMS\Core\Utility\GeneralUtility::getUserObj($classData);
					if (!$processObject instanceof \TYPO3\CMS\Backend\Form\Element\InlineElementHookInterface) {
						throw new \UnexpectedValueException('$processObject must implement interface TYPO3\\CMS\\Backend\\Form\\Element\\InlineElementHookInterface', 1202072000);
					}
					$processObject->init($this);
					$this->hookObjects[] = $processObject;
				}
			}
		}
	}

	/**
	 * Generation of TCEform elements of the type "inline"
	 * This will render inline-relational-record sets. Relations.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeInline($table, $field, $row, &$PA) {
		// Check the TCA configuration - if FALSE is returned, something was wrong
		if ($this->checkConfiguration($PA['fieldConf']['config']) === FALSE) {
			return FALSE;
		}
		$item = '';
		// Count the number of processed inline elements
		$this->inlineCount++;
		// Init:
		$config = $PA['fieldConf']['config'];
		$foreign_table = $config['foreign_table'];
		if (\TYPO3\CMS\Backend\Utility\BackendUtility::isTableLocalizable($table)) {
			$language = intval($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
		}
		$minitems = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($config['minitems'], 0);
		$maxitems = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		// Register the required number of elements:
		$this->fObj->requiredElements[$PA['itemFormElName']] = array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field);
		// Remember the page id (pid of record) where inline editing started first
		// We need that pid for ajax calls, so that they would know where the action takes place on the page structure
		if (!isset($this->inlineFirstPid)) {
			// If this record is not new, try to fetch the inlineView states
			// @TODO: Add checking/cleaning for unused tables, records, etc. to save space in uc-field
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($row['uid'])) {
				$inlineView = unserialize($GLOBALS['BE_USER']->uc['inlineView']);
				$this->inlineView = $inlineView[$table][$row['uid']];
			}
			// If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
			if ($table == 'pages') {
				$liveVersionId = \TYPO3\CMS\Backend\Utility\BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
				$this->inlineFirstPid = is_null($liveVersionId) ? $row['uid'] : $liveVersionId;
			} elseif ($row['pid'] < 0) {
				$prevRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, abs($row['pid']));
				$this->inlineFirstPid = $prevRec['pid'];
			} else {
				$this->inlineFirstPid = $row['pid'];
			}
		}
		// Add the current inline job to the structure stack
		$this->pushStructure($table, $row['uid'], $field, $config, $PA);
		// e.g. data[<table>][<uid>][<field>]
		$nameForm = $this->inlineNames['form'];
		// e.g. data-<pid>-<table1>-<uid1>-<field1>-<table2>-<uid2>-<field2>
		$nameObject = $this->inlineNames['object'];
		// Get the records related to this inline record
		$relatedRecords = $this->getRelatedRecords($table, $field, $row, $PA, $config);
		// Set the first and last record to the config array
		$relatedRecordsUids = array_keys($relatedRecords['records']);
		$config['inline']['first'] = reset($relatedRecordsUids);
		$config['inline']['last'] = end($relatedRecordsUids);
		// Tell the browser what we have (using JSON later):
		$top = $this->getStructureLevel(0);
		$this->inlineData['config'][$nameObject] = array(
			'table' => $foreign_table,
			'md5' => md5($nameObject)
		);
		$this->inlineData['config'][$nameObject . self::Structure_Separator . $foreign_table] = array(
			'min' => $minitems,
			'max' => $maxitems,
			'sortable' => $config['appearance']['useSortable'],
			'top' => array(
				'table' => $top['table'],
				'uid' => $top['uid']
			),
			'context' => array(
				'config' => $config,
				'hmac' => \TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($config)),
			),
		);
		// Set a hint for nested IRRE and tab elements:
		$this->inlineData['nested'][$nameObject] = $this->fObj->getDynNestedStack(FALSE, $this->isAjaxCall);
		// If relations are required to be unique, get the uids that have already been used on the foreign side of the relation
		if ($config['foreign_unique']) {
			// If uniqueness *and* selector are set, they should point to the same field - so, get the configuration of one:
			$selConfig = $this->getPossibleRecordsSelectorConfig($config, $config['foreign_unique']);
			// Get the used unique ids:
			$uniqueIds = $this->getUniqueIds($relatedRecords['records'], $config, $selConfig['type'] == 'groupdb');
			$possibleRecords = $this->getPossibleRecords($table, $field, $row, $config, 'foreign_unique');
			$uniqueMax = $config['appearance']['useCombination'] || $possibleRecords === FALSE ? -1 : count($possibleRecords);
			$this->inlineData['unique'][$nameObject . self::Structure_Separator . $foreign_table] = array(
				'max' => $uniqueMax,
				'used' => $uniqueIds,
				'type' => $selConfig['type'],
				'table' => $config['foreign_table'],
				'elTable' => $selConfig['table'],
				// element/record table (one step down in hierarchy)
				'field' => $config['foreign_unique'],
				'selector' => $selConfig['selector'],
				'possible' => $this->getPossibleRecordsFlat($possibleRecords)
			);
		}
		// If it's required to select from possible child records (reusable children), add a selector box
		if ($config['foreign_selector'] && $config['appearance']['showPossibleRecordsSelector'] !== FALSE) {
			// If not already set by the foreign_unique, set the possibleRecords here and the uniqueIds to an empty array
			if (!$config['foreign_unique']) {
				$possibleRecords = $this->getPossibleRecords($table, $field, $row, $config);
				$uniqueIds = array();
			}
			$selectorBox = $this->renderPossibleRecordsSelector($possibleRecords, $config, $uniqueIds);
			$item .= $selectorBox;
		}
		// Wrap all inline fields of a record with a <div> (like a container)
		$item .= '<div id="' . $nameObject . '">';
		// Define how to show the "Create new record" link - if there are more than maxitems, hide it
		if ($relatedRecords['count'] >= $maxitems || $uniqueMax > 0 && $relatedRecords['count'] >= $uniqueMax) {
			$config['inline']['inlineNewButtonStyle'] = 'display: none;';
		}
		// Render the level links (create new record, localize all, synchronize):
		if ($config['appearance']['levelLinksPosition'] != 'none') {
			$levelLinks = $this->getLevelInteractionLink('newRecord', $nameObject . self::Structure_Separator . $foreign_table, $config);
			if ($language > 0 && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] > 0) {
				// Add the "Localize all records" link before all child records:
				if (isset($config['appearance']['showAllLocalizationLink']) && $config['appearance']['showAllLocalizationLink']) {
					$levelLinks .= $this->getLevelInteractionLink('localize', $nameObject . self::Structure_Separator . $foreign_table, $config);
				}
				// Add the "Synchronize with default language" link before all child records:
				if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
					$levelLinks .= $this->getLevelInteractionLink('synchronize', $nameObject . self::Structure_Separator . $foreign_table, $config);
				}
			}
		}
		// Add the level links before all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'top'))) {
			$item .= $levelLinks;
		}
		$item .= '<div id="' . $nameObject . '_records">';
		$relationList = array();
		if (count($relatedRecords['records'])) {
			foreach ($relatedRecords['records'] as $rec) {
				$item .= $this->renderForeignRecord($row['uid'], $rec, $config);
				if (!isset($rec['__virtual']) || !$rec['__virtual']) {
					$relationList[] = $rec['uid'];
				}
			}
		}
		$item .= '</div>';
		// Add the level links after all child records:
		if (in_array($config['appearance']['levelLinksPosition'], array('both', 'bottom'))) {
			$item .= $levelLinks;
		}
		if (is_array($config['customControls'])) {
			$item .= '<div id="' . $nameObject . '_customControls">';
			foreach ($config['customControls'] as $customControlConfig) {
				$parameters = array(
					'table' => $table,
					'field' => $field,
					'row' => $row,
					'nameObject' => $nameObject,
					'nameForm' => $nameForm,
					'config' => $config
				);
				$item .= \TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($customControlConfig, $parameters, $this);
			}
			$item .= '</div>';
		}
		// Add Drag&Drop functions for sorting to TCEforms::$additionalJS_post
		if (count($relationList) > 1 && $config['appearance']['useSortable']) {
			$this->addJavaScriptSortable($nameObject . '_records');
		}
		// Publish the uids of the child records in the given order to the browser
		$item .= '<input type="hidden" name="' . $nameForm . '" value="' . implode(',', $relationList) . '" class="inlineRecord" />';
		// Close the wrap for all inline fields (container)
		$item .= '</div>';
		// On finishing this section, remove the last item from the structure stack
		$this->popStructure();
		// If this was the first call to the inline type, restore the values
		if (!$this->getStructureDepth()) {
			unset($this->inlineFirstPid);
		}
		return $item;
	}

	/*******************************************************
	 *
	 * Regular rendering of forms, fields, etc.
	 *
	 *******************************************************/
	/**
	 * Render the form-fields of a related (foreign) record.
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param array $rec The table record of the child/embedded table (normaly post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor)
	 * @param array $config Content of $PA['fieldConf']['config']
	 * @return string The HTML code for this "foreign record
	 * @todo Define visibility
	 */
	public function renderForeignRecord($parentUid, $rec, $config = array()) {
		$foreign_table = $config['foreign_table'];
		$foreign_field = $config['foreign_field'];
		$foreign_selector = $config['foreign_selector'];
		// Register default localization content:
		$parent = $this->getStructureLevel(-1);
		if (isset($parent['localizationMode']) && $parent['localizationMode'] != FALSE) {
			$this->fObj->registerDefaultLanguageData($foreign_table, $rec);
		}
		// Send a mapping information to the browser via JSON:
		// e.g. data[<curTable>][<curId>][<curField>] => data-<pid>-<parentTable>-<parentId>-<parentField>-<curTable>-<curId>-<curField>
		$this->inlineData['map'][$this->inlineNames['form']] = $this->inlineNames['object'];
		// Set this variable if we handle a brand new unsaved record:
		$isNewRecord = \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rec['uid']) ? FALSE : TRUE;
		// Set this variable if the record is virtual and only show with header and not editable fields:
		$isVirtualRecord = isset($rec['__virtual']) && $rec['__virtual'];
		// If there is a selector field, normalize it:
		if ($foreign_selector) {
			$rec[$foreign_selector] = $this->normalizeUid($rec[$foreign_selector]);
		}
		if (!$this->checkAccess(($isNewRecord ? 'new' : 'edit'), $foreign_table, $rec['uid'])) {
			return FALSE;
		}
		// Get the current naming scheme for DOM name/id attributes:
		$nameObject = $this->inlineNames['object'];
		$appendFormFieldNames = '[' . $foreign_table . '][' . $rec['uid'] . ']';
		$objectId = $nameObject . self::Structure_Separator . $foreign_table . self::Structure_Separator . $rec['uid'];
		// Put the current level also to the dynNestedStack of TCEforms:
		$this->fObj->pushToDynNestedStack('inline', $objectId);
		$class = '';
		if (!$isVirtualRecord) {
			// Get configuration:
			$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
			$expandAll = isset($config['appearance']['collapseAll']) && !$config['appearance']['collapseAll'];
			$ajaxLoad = isset($config['appearance']['ajaxLoad']) && !$config['appearance']['ajaxLoad'] ? FALSE : TRUE;
			if ($isNewRecord) {
				// Show this record expanded or collapsed
				$isExpanded = $expandAll || (!$collapseAll ? 1 : 0);
			} else {
				$isExpanded = $config['renderFieldsOnly'] || !$collapseAll && $this->getExpandedCollapsedState($foreign_table, $rec['uid']) || $expandAll;
			}
			// Render full content ONLY IF this is a AJAX-request, a new record, the record is not collapsed or AJAX-loading is explicitly turned off
			if ($isNewRecord || $isExpanded || !$ajaxLoad) {
				$combination = $this->renderCombinationTable($rec, $appendFormFieldNames, $config);
				$overruleTypesArray = isset($config['foreign_types']) ? $config['foreign_types'] : array();
				$fields = $this->renderMainFields($foreign_table, $rec, $overruleTypesArray);
				$fields = $this->wrapFormsSection($fields);
				// Replace returnUrl in Wizard-Code, if this is an AJAX call
				$ajaxArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajax');
				if (isset($ajaxArguments[2]) && trim($ajaxArguments[2]) != '') {
					$fields = str_replace('P[returnUrl]=%2F' . rawurlencode(TYPO3_mainDir) . 'ajax.php', 'P[returnUrl]=' . rawurlencode($ajaxArguments[2]), $fields);
				}
			} else {
				$combination = '';
				// This string is the marker for the JS-function to check if the full content has already been loaded
				$fields = '<!--notloaded-->';
			}
			if ($isNewRecord) {
				// Get the top parent table
				$top = $this->getStructureLevel(0);
				$ucFieldName = 'uc[inlineView][' . $top['table'] . '][' . $top['uid'] . ']' . $appendFormFieldNames;
				// Set additional fields for processing for saving
				$fields .= '<input type="hidden" name="' . $this->prependFormFieldNames . $appendFormFieldNames . '[pid]" value="' . $rec['pid'] . '"/>';
				$fields .= '<input type="hidden" name="' . $ucFieldName . '" value="' . $isExpanded . '" />';
			} else {
				// Set additional field for processing for saving
				$fields .= '<input type="hidden" name="' . $this->prependCmdFieldNames . $appendFormFieldNames . '[delete]" value="1" disabled="disabled" />';
				if (!$isExpanded && !empty($GLOBALS['TCA'][$foreign_table]['ctrl']['enablecolumns']['disabled'])) {
					$checked = !empty($rec['hidden']) ? ' checked="checked"' : '';
					$fields .= '<input type="checkbox" name="' . $this->prependFormFieldNames . $appendFormFieldNames . '[hidden]_0" value="1"' . $checked . ' />';
					$fields .= '<input type="input" name="' . $this->prependFormFieldNames . $appendFormFieldNames . '[hidden]" value="' . $rec['hidden'] . '" />';
				}
			}
			// If this record should be shown collapsed
			if (!$isExpanded) {
				$class = 't3-form-field-container-inline-collapsed';
			}
		}
		if ($config['renderFieldsOnly']) {
			$out = $fields . $combination;
		} else {
			// Set the record container with data for output
			if ($isVirtualRecord) {
				$class .= ' t3-form-field-container-inline-placeHolder';
			}
			if (isset($rec['hidden']) && intval($rec['hidden'])) {
				$class .= ' t3-form-field-container-inline-hidden';
			}
			$out = '<div class="t3-form-field-record-inline" id="' . $objectId . '_fields" data-expandSingle="' . ($config['appearance']['expandSingle'] ? 1 : 0) . '" data-returnURL="' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI')) . '">' . $fields . $combination . '</div>';
			$header = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('apps-irre-' . ($class != '' ? 'collapsed' : 'expanded'));
			$header .= $this->renderForeignRecordHeader($parentUid, $foreign_table, $rec, $config, $isVirtualRecord);
			$out = '<div class="t3-form-field-header-inline" id="' . $objectId . '_header">' . $header . '</div>' . $out;
			// Wrap the header, fields and combination part of a child record with a div container
			$classMSIE = $this->fObj->clientInfo['BROWSER'] == 'msie' && $this->fObj->clientInfo['VERSION'] < 8 ? 'MSIE' : '';
			$class .= ' inlineDiv' . $classMSIE . ($isNewRecord ? ' inlineIsNewRecord' : '');
			$out = '<div id="' . $objectId . '_div" class="t3-form-field-container-inline ' . trim($class) . '">' . $out . '</div>';
		}
		// Remove the current level also from the dynNestedStack of TCEforms:
		$this->fObj->popFromDynNestedStack();
		return $out;
	}

	/**
	 * Wrapper for TCEforms::getMainFields().
	 *
	 * @param string $table The table name
	 * @param array $row The record to be rendered
	 * @param array $overruleTypesArray Overrule TCA [types] array, e.g to overrride [showitem] configuration of a particular type
	 * @return string The rendered form
	 */
	protected function renderMainFields($table, array $row, array $overruleTypesArray = array()) {
		// The current render depth of \TYPO3\CMS\Backend\Form\FormEngine
		$depth = $this->fObj->renderDepth;
		// If there is some information about already rendered palettes of our parent, store this info:
		if (isset($this->fObj->palettesRendered[$depth][$table])) {
			$palettesRendered = $this->fObj->palettesRendered[$depth][$table];
		}
		// Render the form:
		$content = $this->fObj->getMainFields($table, $row, $depth, $overruleTypesArray);
		// If there was some info about rendered palettes stored, write it back for our parent:
		if (isset($palettesRendered)) {
			$this->fObj->palettesRendered[$depth][$table] = $palettesRendered;
		}
		return $content;
	}

	/**
	 * Renders the HTML header for a foreign record, such as the title, toggle-function, drag'n'drop, etc.
	 * Later on the command-icons are inserted here.
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreign_table The foreign_table we create a header for
	 * @param array $rec The current record of that foreign_table
	 * @param array $config content of $PA['fieldConf']['config']
	 * @param boolean $isVirtualRecord
	 * @return string The HTML code of the header
	 * @todo Define visibility
	 */
	public function renderForeignRecordHeader($parentUid, $foreign_table, $rec, $config, $isVirtualRecord = FALSE) {
		// Init:
		$objectId = $this->inlineNames['object'] . self::Structure_Separator . $foreign_table . self::Structure_Separator . $rec['uid'];
		// We need the returnUrl of the main script when loading the fields via AJAX-call (to correct wizard code, so include it as 3rd parameter)
		// Pre-Processing:
		$isOnSymmetricSide = \TYPO3\CMS\Core\Database\RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
		$hasForeignLabel = !$isOnSymmetricSide && $config['foreign_label'] ? TRUE : FALSE;
		$hasSymmetricLabel = $isOnSymmetricSide && $config['symmetric_label'] ? TRUE : FALSE;
		// Get the record title/label for a record:
		// render using a self-defined user function
		if ($GLOBALS['TCA'][$foreign_table]['ctrl']['label_userFunc']) {
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
			\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TCA'][$foreign_table]['ctrl']['label_userFunc'], $params, $null);
			$recTitle = $params['title'];
		} elseif ($hasForeignLabel || $hasSymmetricLabel) {
			$titleCol = $hasForeignLabel ? $config['foreign_label'] : $config['symmetric_label'];
			$foreignConfig = $this->getPossibleRecordsSelectorConfig($config, $titleCol);
			// Render title for everything else than group/db:
			if ($foreignConfig['type'] != 'groupdb') {
				$recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getProcessedValueExtra($foreign_table, $titleCol, $rec[$titleCol], 0, 0, FALSE);
			} else {
				// $recTitle could be something like: "tx_table_123|...",
				$valueParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $rec[$titleCol]);
				$itemParts = \TYPO3\CMS\Core\Utility\GeneralUtility::revExplode('_', $valueParts[0], 2);
				$recTemp = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordWSOL($itemParts[0], $itemParts[1]);
				$recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($itemParts[0], $recTemp, FALSE);
			}
			$recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitlePrep($recTitle);
			if (!strcmp(trim($recTitle), '')) {
				$recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getNoRecordTitle(TRUE);
			}
		} else {
			$recTitle = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordTitle($foreign_table, $rec, TRUE);
		}
		// Renders a thumbnail for the header
		if (!empty($config['appearance']['headerThumbnail']['field'])) {
			$fieldValue = $rec[$config['appearance']['headerThumbnail']['field']];
			$firstElement = array_shift(\TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $fieldValue));
			$fileUid = array_pop(\TYPO3\CMS\Backend\Utility\BackendUtility::splitTable_Uid($firstElement));
			if (!empty($fileUid)) {
				$fileObject = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getFileObject($fileUid);
				if ($fileObject) {
					$imageSetup = $config['appearance']['headerThumbnail'];
					unset($imageSetup['field']);
					$imageSetup = array_merge(array('width' => 64, 'height' => 64), $imageSetup);
					$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, $imageSetup)->getPublicUrl(TRUE);
					$thumbnail = '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($recTitle) . '">';
				} else {
					$thumbnail = FALSE;
				}
			}

		}
		$altText = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordIconAltText($rec, $foreign_table);
		$iconImg = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIconForRecord($foreign_table, $rec, array('title' => htmlspecialchars($altText), 'id' => $objectId . '_icon'));
		$label = '<span id="' . $objectId . '_label">' . $recTitle . '</span>';
		$ctrl = $this->renderForeignRecordHeaderControl($parentUid, $foreign_table, $rec, $config, $isVirtualRecord);
		$header = '<table>' . '<tr>' . (!empty($config['appearance']['headerThumbnail']['field']) && $thumbnail ?
				'<td class="t3-form-field-header-inline-thumbnail" id="' . $objectId . '_thumbnailcontainer">' . $thumbnail . '</td>' :
				'<td class="t3-form-field-header-inline-icon" id="' . $objectId . '_iconcontainer">' . $iconImg . '</td>') . '<td class="t3-form-field-header-inline-summary">' . $label . '</td>' . '<td clasS="t3-form-field-header-inline-ctrl">' . $ctrl . '</td>' . '</tr>' . '</table>';
		return $header;
	}

	/**
	 * Render the control-icons for a record header (create new, sorting, delete, disable/enable).
	 * Most of the parts are copy&paste from class.db_list_extra.inc and modified for the JavaScript calls here
	 *
	 * @param string $parentUid The uid of the parent (embedding) record (uid or NEW...)
	 * @param string $foreign_table The table (foreign_table) we create control-icons for
	 * @param array $rec The current record of that foreign_table
	 * @param array $config (modified) TCA configuration of the field
	 * @return string The HTML code with the control-icons
	 * @todo Define visibility
	 */
	public function renderForeignRecordHeaderControl($parentUid, $foreign_table, $rec, $config = array(), $isVirtualRecord = FALSE) {
		// Initialize:
		$cells = array();
		$isNewItem = substr($rec['uid'], 0, 3) == 'NEW';
		$tcaTableCtrl = &$GLOBALS['TCA'][$foreign_table]['ctrl'];
		$tcaTableCols = &$GLOBALS['TCA'][$foreign_table]['columns'];
		$isPagesTable = $foreign_table == 'pages' ? TRUE : FALSE;
		$isOnSymmetricSide = \TYPO3\CMS\Core\Database\RelationHandler::isOnSymmetricSide($parentUid, $config, $rec);
		$enableManualSorting = $tcaTableCtrl['sortby'] || $config['MM'] || !$isOnSymmetricSide && $config['foreign_sortby'] || $isOnSymmetricSide && $config['symmetric_sortby'] ? TRUE : FALSE;
		$nameObject = $this->inlineNames['object'];
		$nameObjectFt = $nameObject . self::Structure_Separator . $foreign_table;
		$nameObjectFtId = $nameObjectFt . self::Structure_Separator . $rec['uid'];
		$calcPerms = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::readPageAccess($rec['pid'], $GLOBALS['BE_USER']->getPagePermsClause(1)));
		// If the listed table is 'pages' we have to request the permission settings for each page:
		if ($isPagesTable) {
			$localCalcPerms = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $rec['uid']));
		}
		// This expresses the edit permissions for this particular element:
		$permsEdit = $isPagesTable && $localCalcPerms & 2 || !$isPagesTable && $calcPerms & 16;
		// Controls: Defines which controls should be shown
		$enabledControls = $config['appearance']['enabledControls'];
		// Hook: Can disable/enable single controls for specific child records:
		foreach ($this->hookObjects as $hookObj) {
			$hookObj->renderForeignRecordHeaderControl_preProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $enabledControls);
		}
		// Icon to visualize that a required field is nested in this inline level:
		$cells['required'] = '<img name="' . $nameObjectFtId . '_req" src="clear.gif" width="10" height="10" hspace="4" vspace="3" alt="" />';
		if (isset($rec['__create'])) {
			$cells['localize.isLocalizable'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-localize-status-low', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:localize.isLocalizable', TRUE)));
		} elseif (isset($rec['__remove'])) {
			$cells['localize.wasRemovedInOriginal'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-localize-status-high', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:localize.wasRemovedInOriginal', 1)));
		}
		// "Info": (All records)
		if ($enabledControls['info'] && !$isNewItem) {
			$cells['info'] = '<a href="#" onclick="' . htmlspecialchars(('top.launchView(\'' . $foreign_table . '\', \'' . $rec['uid'] . '\'); return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-dialog-information', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:showInfo', TRUE))) . '</a>';
		}
		// If the table is NOT a read-only table, then show these links:
		if (!$tcaTableCtrl['readOnly'] && !$isVirtualRecord) {
			// "New record after" link (ONLY if the records in the table are sorted by a "sortby"-row or if default values can depend on previous record):
			if ($enabledControls['new'] && ($enableManualSorting || $tcaTableCtrl['useColumnsForDefaultValues'])) {
				if (!$isPagesTable && $calcPerms & 16 || $isPagesTable && $calcPerms & 8) {
					$onClick = 'return inline.createNewRecord(\'' . $nameObjectFt . '\',\'' . $rec['uid'] . '\')';
					$class = ' class="inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'] . '"';
					if ($config['inline']['inlineNewButtonStyle']) {
						$style = ' style="' . $config['inline']['inlineNewButtonStyle'] . '"';
					}
					$cells['new'] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '"' . $class . $style . '>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon(('actions-' . ($isPagesTable ? 'page' : 'document') . '-new'), array(
						'title' => $GLOBALS['LANG']->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:new' . ($isPagesTable ? 'Page' : 'Record')), 1)
					)) . '</a>';
				}
			}
			// "Up/Down" links
			if ($enabledControls['sort'] && $permsEdit && $enableManualSorting) {
				$onClick = 'return inline.changeSorting(\'' . $nameObjectFtId . '\', \'1\')';
				// Up
				$style = $config['inline']['first'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
				$cells['sort.up'] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '" class="sortingUp" ' . $style . '>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-up', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveUp', TRUE))) . '</a>';
				$onClick = 'return inline.changeSorting(\'' . $nameObjectFtId . '\', \'-1\')';
				// Down
				$style = $config['inline']['last'] == $rec['uid'] ? 'style="visibility: hidden;"' : '';
				$cells['sort.down'] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '" class="sortingDown" ' . $style . '>' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-down', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:moveDown', TRUE))) . '</a>';
			}
			// "Delete" link:
			if ($enabledControls['delete'] && ($isPagesTable && $localCalcPerms & 4 || !$isPagesTable && $calcPerms & 16)) {
				$onClick = 'inline.deleteRecord(\'' . $nameObjectFtId . '\');';
				$cells['delete'] = '<a href="#" onclick="' . htmlspecialchars(('if (confirm(' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->getLL('deleteWarning')) . ')) {	' . $onClick . ' } return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-delete', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_web_list.xlf:delete', TRUE))) . '</a>';
			}

			// "Hide/Unhide" links:
			$hiddenField = $tcaTableCtrl['enablecolumns']['disabled'];
			if ($enabledControls['hide'] && $permsEdit && $hiddenField && $tcaTableCols[$hiddenField] && (!$tcaTableCols[$hiddenField]['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $foreign_table . ':' . $hiddenField))) {
				$onClick = 'return inline.enableDisableRecord(\'' . $nameObjectFtId . '\')';
				if ($rec[$hiddenField]) {
					$cells['hide.unhide'] = '<a href="#" class="hiddenHandle" onclick="' . htmlspecialchars($onClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-unhide', array(
						'title' => $GLOBALS['LANG']->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:unHide' . ($isPagesTable ? 'Page' : '')), 1),
						'id' => ($nameObjectFtId . '_disabled')
					)) . '</a>';
				} else {
					$cells['hide.hide'] = '<a href="#" class="hiddenHandle" onclick="' . htmlspecialchars($onClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-hide', array(
						'title' => $GLOBALS['LANG']->sL(('LLL:EXT:lang/locallang_mod_web_list.xlf:hide' . ($isPagesTable ? 'Page' : '')), 1),
						'id' => ($nameObjectFtId . '_disabled')
					)) . '</a>';
				}
			}
			// Drag&Drop Sorting: Sortable handler for script.aculo.us
			if ($enabledControls['dragdrop'] && $permsEdit && $enableManualSorting && $config['appearance']['useSortable']) {
				$cells['dragdrop'] = \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-move-move', array('class' => 'sortableHandle', 'title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.move', TRUE)));
			}
		} elseif ($isVirtualRecord) {
			if ($enabledControls['localize'] && isset($rec['__create'])) {
				$onClick = 'inline.synchronizeLocalizeRecords(\'' . $nameObjectFt . '\', ' . $rec['uid'] . ');';
				$cells['localize'] = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-localize', array('title' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:localize', TRUE))) . '</a>';
			}
		}
		// If the record is edit-locked	by another user, we will show a little warning sign:
		if ($lockInfo = \TYPO3\CMS\Backend\Utility\BackendUtility::isRecordLocked($foreign_table, $rec['uid'])) {
			$cells['locked'] = '<a href="#" onclick="' . htmlspecialchars(('alert(' . $GLOBALS['LANG']->JScharCode($lockInfo['msg']) . ');return false;')) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('status-warning-in-use', array('title' => htmlspecialchars($lockInfo['msg']))) . '</a>';
		}
		// Hook: Post-processing of single controls for specific child records:
		foreach ($this->hookObjects as $hookObj) {
			$hookObj->renderForeignRecordHeaderControl_postProcess($parentUid, $foreign_table, $rec, $config, $isVirtualRecord, $cells);
		}
		return '<!-- CONTROL PANEL: ' . $foreign_table . ':' . $rec['uid'] . ' -->' . implode('', $cells);
	}

	/**
	 * Render a table with TCEforms, that occurs on a intermediate table but should be editable directly,
	 * so two tables are combined (the intermediate table with attributes and the sub-embedded table).
	 * -> This is a direct embedding over two levels!
	 *
	 * @param array $rec The table record of the child/embedded table (normaly post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor)
	 * @param string $appendFormFieldNames The [<table>][<uid>] of the parent record (the intermediate table)
	 * @param array $config content of $PA['fieldConf']['config']
	 * @return string A HTML string with <table> tag around.
	 * @todo Define visibility
	 */
	public function renderCombinationTable(&$rec, $appendFormFieldNames, $config = array()) {
		$foreign_table = $config['foreign_table'];
		$foreign_selector = $config['foreign_selector'];
		if ($foreign_selector && $config['appearance']['useCombination']) {
			$comboConfig = $GLOBALS['TCA'][$foreign_table]['columns'][$foreign_selector]['config'];
			$comboRecord = array();
			// If record does already exist, load it:
			if ($rec[$foreign_selector] && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($rec[$foreign_selector])) {
				$comboRecord = $this->getRecord($this->inlineFirstPid, $comboConfig['foreign_table'], $rec[$foreign_selector]);
				$isNewRecord = FALSE;
			} else {
				$comboRecord = $this->getNewRecord($this->inlineFirstPid, $comboConfig['foreign_table']);
				$isNewRecord = TRUE;
			}
			// Get the TCEforms interpretation of the TCA of the child table
			$out = $this->renderMainFields($comboConfig['foreign_table'], $comboRecord);
			$out = $this->wrapFormsSection($out, array(), array('class' => 'wrapperAttention'));
			// If this is a new record, add a pid value to store this record and the pointer value for the intermediate table
			if ($isNewRecord) {
				$comboFormFieldName = $this->prependFormFieldNames . '[' . $comboConfig['foreign_table'] . '][' . $comboRecord['uid'] . '][pid]';
				$out .= '<input type="hidden" name="' . $comboFormFieldName . '" value="' . $comboRecord['pid'] . '" />';
			}
			// If the foreign_selector field is also responsible for uniqueness, tell the browser the uid of the "other" side of the relation
			if ($isNewRecord || $config['foreign_unique'] == $foreign_selector) {
				$parentFormFieldName = $this->prependFormFieldNames . $appendFormFieldNames . '[' . $foreign_selector . ']';
				$out .= '<input type="hidden" name="' . $parentFormFieldName . '" value="' . $comboRecord['uid'] . '" />';
			}
		}
		return $out;
	}

	/**
	 * Get a selector as used for the select type, to select from all available
	 * records and to create a relation to the embedding record (e.g. like MM).
	 *
	 * @param array $selItems Array of all possible records
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param array $uniqueIds The uids that have already been used and should be unique
	 * @return string A HTML <select> box with all possible records
	 * @todo Define visibility
	 */
	public function renderPossibleRecordsSelector($selItems, $conf, $uniqueIds = array()) {
		$foreign_table = $conf['foreign_table'];
		$foreign_selector = $conf['foreign_selector'];
		$selConfig = $this->getPossibleRecordsSelectorConfig($conf, $foreign_selector);
		$config = $selConfig['PA']['fieldConf']['config'];
		if ($selConfig['type'] == 'select') {
			$item = $this->renderPossibleRecordsSelectorTypeSelect($selItems, $conf, $selConfig['PA'], $uniqueIds);
		} elseif ($selConfig['type'] == 'groupdb') {
			$item = $this->renderPossibleRecordsSelectorTypeGroupDB($conf, $selConfig['PA']);
		}
		return $item;
	}

	/**
	 * Get a selector as used for the select type, to select from all available
	 * records and to create a relation to the embedding record (e.g. like MM).
	 *
	 * @param array $selItems Array of all possible records
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param array $PA An array with additional configuration options
	 * @param array $uniqueIds The uids that have already been used and should be unique
	 * @return string A HTML <select> box with all possible records
	 * @todo Define visibility
	 */
	public function renderPossibleRecordsSelectorTypeSelect($selItems, $conf, &$PA, $uniqueIds = array()) {
		$foreign_table = $conf['foreign_table'];
		$foreign_selector = $conf['foreign_selector'];
		$PA = array();
		$PA['fieldConf'] = $GLOBALS['TCA'][$foreign_table]['columns'][$foreign_selector];
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];
		// Using "form_type" locally in this script
		$PA['fieldTSConfig'] = $this->fObj->setTSconfig($foreign_table, array(), $foreign_selector);
		$config = $PA['fieldConf']['config'];
		//TODO: $disabled is not present - should be read from config?
		$disabled = FALSE;
		if (!$disabled) {
			// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach ($selItems as $p) {
				if ($config['iconsInOptionTags']) {
					$styleAttrValue = $this->fObj->optionTagStyle($p[2]);
				}
				if (!in_array($p[1], $uniqueIds)) {
					$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . ' style="' . (in_array($p[1], $uniqueIds) ? '' : '') . ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) : '') . '">' . htmlspecialchars($p[0]) . '</option>';
				}
			}
			// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : ' style="' . $this->fObj->defaultMultipleSelectorStyle . '"';
			$size = intval($conf['size']);
			$size = $conf['autoSizeMax'] ? \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange(count($selItems) + 1, \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($size, 1), $conf['autoSizeMax']) : $size;
			$onChange = 'return inline.importNewRecord(\'' . $this->inlineNames['object'] . self::Structure_Separator . $conf['foreign_table'] . '\')';
			$item = '
				<select id="' . $this->inlineNames['object'] . self::Structure_Separator . $conf['foreign_table'] . '_selector"' . $this->fObj->insertDefStyle('select') . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($onChange) . '"' . $PA['onFocus'] . $selector_itemListStyle . ($conf['foreign_unique'] ? ' isunique="isunique"' : '') . '>
					' . implode('
					', $opt) . '
				</select>';
			// Add a "Create new relation" link for adding new relations
			// This is neccessary, if the size of the selector is "1" or if
			// there is only one record item in the select-box, that is selected by default
			// The selector-box creates a new relation on using a onChange event (see some line above)
			if (!empty($conf['appearance']['createNewRelationLinkTitle'])) {
				$createNewRelationText = $GLOBALS['LANG']->sL($conf['appearance']['createNewRelationLinkTitle'], TRUE);
			} else {
				$createNewRelationText = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.createNewRelation', 1);
			}
			$item .= '<a href="#" onclick="' . htmlspecialchars($onChange) . '" align="abstop">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new', array('title' => $createNewRelationText)) . $createNewRelationText . '</a>';
			// Wrap the selector and add a spacer to the bottom
			$item = '<div style="margin-bottom: 20px;">' . $item . '</div>';
		}
		return $item;
	}

	/**
	 * Generate a link that opens an element browser in a new window.
	 * For group/db there is no way o use a "selector" like a <select>|</select>-box.
	 *
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param array $PA An array with additional configuration options
	 * @return string A HTML link that opens an element browser in a new window
	 * @todo Define visibility
	 */
	public function renderPossibleRecordsSelectorTypeGroupDB($conf, &$PA) {
		$foreign_table = $conf['foreign_table'];
		$config = $PA['fieldConf']['config'];
		$allowed = $config['allowed'];
		$objectPrefix = $this->inlineNames['object'] . self::Structure_Separator . $foreign_table;
		$mode = 'db';
		if (!empty($conf['appearance']['createNewRelationLinkTitle'])) {
			$createNewRelationText = $GLOBALS['LANG']->sL($conf['appearance']['createNewRelationLinkTitle'], TRUE);
		} else {
			$createNewRelationText = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.createNewRelation', 1);
		}
		if (is_array($config['appearance'])) {
			if (isset($config['appearance']['elementBrowserType'])) {
				$mode = $config['appearance']['elementBrowserType'];
			}
			if (isset($config['appearance']['elementBrowserAllowed'])) {
				$allowed = $config['appearance']['elementBrowserAllowed'];
			}
		}
		$browserParams = '|||' . $allowed . '|' . $objectPrefix . '|inline.checkUniqueElement||inline.importElement';
		$onClick = 'setFormValueOpenBrowser(\'' . $mode . '\', \'' . $browserParams . '\'); return false;';
		$item = '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-insert-record', array('title' => $createNewRelationText)) . $createNewRelationText . '</a>';
		return $item;
	}

	/**
	 * Creates the HTML code of a general link to be used on a level of inline children.
	 * The possible keys for the parameter $type are 'newRecord', 'localize' and 'synchronize'.
	 *
	 * @param string $type The link type, values are 'newRecord', 'localize' and 'synchronize'.
	 * @param string $objectPrefix The "path" to the child record to create (e.g. 'data-parentPageId-partenTable-parentUid-parentField-childTable]')
	 * @param array $conf TCA configuration of the parent(!) field
	 * @return string The HTML code of the new link, wrapped in a div
	 */
	protected function getLevelInteractionLink($type, $objectPrefix, $conf = array()) {
		$nameObject = $this->inlineNames['object'];
		$attributes = array();
		switch ($type) {
		case 'newRecord':
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:cm.createnew', 1);
			$icon = 'actions-document-new';
			$className = 'typo3-newRecordLink';
			$attributes['class'] = 'inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
			$attributes['onclick'] = 'return inline.createNewRecord(\'' . $objectPrefix . '\')';
			if (isset($conf['inline']['inlineNewButtonStyle']) && $conf['inline']['inlineNewButtonStyle']) {
				$attributes['style'] = $conf['inline']['inlineNewButtonStyle'];
			}
			if (isset($conf['appearance']['newRecordLinkAddTitle']) && $conf['appearance']['newRecordLinkAddTitle']) {
				$titleAddon = ' ' . $GLOBALS['LANG']->sL($GLOBALS['TCA'][$conf['foreign_table']]['ctrl']['title'], 1);
			}
			break;
		case 'localize':
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:localizeAllRecords', 1);
			$icon = 'actions-document-localize';
			$className = 'typo3-localizationLink';
			$attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(\'' . $objectPrefix . '\', \'localize\')';
			break;
		case 'synchronize':
			$title = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:synchronizeWithOriginalLanguage', 1);
			$icon = 'actions-document-synchronize';
			$className = 'typo3-synchronizationLink';
			$attributes['class'] = 'inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
			$attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(\'' . $objectPrefix . '\', \'synchronize\')';
			break;
		}
		// Create the link:
		$icon = $icon ? \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon($icon, array('title' => htmlspecialchars($title . $titleAddon))) : '';
		$link = $this->wrapWithAnchor($icon . $title . $titleAddon, '#', $attributes);
		return '<div' . ($className ? ' class="' . $className . '"' : '') . '>' . $link . '</div>';
	}

	/**
	 * Add Sortable functionality using script.acolo.us "Sortable".
	 *
	 * @param string $objectId The container id of the object - elements inside will be sortable
	 * @return void
	 * @todo Define visibility
	 */
	public function addJavaScriptSortable($objectId) {
		$this->fObj->additionalJS_post[] = '
			inline.createDragAndDropSorting("' . $objectId . '");
		';
	}

	/*******************************************************
	 *
	 * Handling of AJAX calls
	 *
	 *******************************************************/
	/**
	 * General processor for AJAX requests concerning IRRE.
	 * (called by typo3/ajax.php)
	 *
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler $ajaxObj The TYPO3AJAX object of this request
	 * @return void
	 */
	public function processAjaxRequest($params, $ajaxObj) {
		$ajaxArguments = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('ajax');
		$ajaxIdParts = explode('::', $GLOBALS['ajaxID'], 2);
		if (isset($ajaxArguments) && is_array($ajaxArguments) && count($ajaxArguments)) {
			$ajaxMethod = $ajaxIdParts[1];
			switch ($ajaxMethod) {
			case 'createNewRecord':

			case 'synchronizeLocalizeRecords':

			case 'getRecordDetails':
				$this->isAjaxCall = TRUE;
				// Construct runtime environment for Inline Relational Record Editing:
				$this->processAjaxRequestConstruct($ajaxArguments);
				// Parse the DOM identifier (string), add the levels to the structure stack (array) and load the TCA config:
				$this->parseStructureString($ajaxArguments[0], TRUE);
				$this->injectAjaxConfiguration($ajaxArguments);
				// Render content:
				$ajaxObj->setContentFormat('jsonbody');
				$ajaxObj->setContent(call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments));
				break;
			case 'setExpandedCollapsedState':
				$ajaxObj->setContentFormat('jsonbody');
				call_user_func_array(array(&$this, $ajaxMethod), $ajaxArguments);
				break;
			}
		}
	}

	/**
	 * Injects configuration via AJAX calls.
	 * The configuration is validated using HMAC to avoid hijacking.
	 *
	 * @param array $ajaxArguments
	 * @return void
	 */
	protected function injectAjaxConfiguration(array $ajaxArguments) {
		$level = $this->calculateStructureLevel(-1);

		if (empty($ajaxArguments['context']) || $level === FALSE) {
			return;
		}

		$current = &$this->inlineStructure['stable'][$level];
		$context = json_decode($ajaxArguments['context'], TRUE);

		if (\TYPO3\CMS\Core\Utility\GeneralUtility::hmac(serialize($context['config'])) !== $context['hmac']) {
			return;
		}

		$current['config'] = $context['config'];
		$current['localizationMode'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode(
			$current['table'],
			$current['config']
		);
	}

	/**
	 * Construct runtime environment for Inline Relational Record Editing.
	 * - creates an anoymous SC_alt_doc in $GLOBALS['SOBE']
	 * - creates a \TYPO3\CMS\Backend\Form\FormEngine in $GLOBALS['SOBE']->tceforms
	 * - sets ourself as reference to $GLOBALS['SOBE']->tceforms->inline
	 * - sets $GLOBALS['SOBE']->tceforms->RTEcounter to the current situation on client-side
	 *
	 * @param array &$ajaxArguments The arguments to be processed by the AJAX request
	 * @return void
	 */
	protected function processAjaxRequestConstruct(&$ajaxArguments) {
		$GLOBALS['LANG']->includeLLFile('EXT:lang/locallang_alt_doc.xml');
		// Create a new anonymous object:
		$GLOBALS['SOBE'] = new \stdClass();
		$GLOBALS['SOBE']->MOD_MENU = array(
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => ''
		);
		// Setting virtual document name
		$GLOBALS['SOBE']->MCONF['name'] = 'xMOD_alt_doc.php';
		// CLEANSE SETTINGS
		$GLOBALS['SOBE']->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($GLOBALS['SOBE']->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $GLOBALS['SOBE']->MCONF['name']);
		// Create an instance of the document template object
		$GLOBALS['SOBE']->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		// Initialize TCEforms (rendering the forms)
		$GLOBALS['SOBE']->tceforms = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormEngine');
		$GLOBALS['SOBE']->tceforms->inline = $this;
		$GLOBALS['SOBE']->tceforms->RTEcounter = intval(array_shift($ajaxArguments));
		$GLOBALS['SOBE']->tceforms->initDefaultBEMode();
		$GLOBALS['SOBE']->tceforms->palettesCollapsed = !$GLOBALS['SOBE']->MOD_SETTINGS['showPalettes'];
		$GLOBALS['SOBE']->tceforms->disableRTE = $GLOBALS['SOBE']->MOD_SETTINGS['disableRTE'];
		$GLOBALS['SOBE']->tceforms->enableClickMenu = TRUE;
		$GLOBALS['SOBE']->tceforms->enableTabMenu = TRUE;
		// Clipboard is initialized:
		// Start clipboard
		$GLOBALS['SOBE']->tceforms->clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
		// Initialize - reads the clipboard content from the user session
		$GLOBALS['SOBE']->tceforms->clipObj->initializeClipboard();
	}

	/**
	 * Determines and sets several script calls to a JSON array, that would have been executed if processed in non-AJAX mode.
	 *
	 * @param array &$jsonArray Reference of the array to be used for JSON
	 * @param array $config The configuration of the IRRE field of the parent record
	 * @return void
	 */
	protected function getCommonScriptCalls(&$jsonArray, $config) {
		// Add data that would have been added at the top of a regular TCEforms call:
		if ($headTags = $this->getHeadTags()) {
			$jsonArray['headData'] = $headTags;
		}
		// Add the JavaScript data that would have been added at the bottom of a regular TCEforms call:
		$jsonArray['scriptCall'][] = $this->fObj->JSbottom($this->fObj->formName, TRUE);
		// If script.aculo.us Sortable is used, update the Observer to know the record:
		if ($config['appearance']['useSortable']) {
			$jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(\'' . $this->inlineNames['object'] . '_records\');';
		}
		// if TCEforms has some JavaScript code to be executed, just do it
		if ($this->fObj->extJSCODE) {
			$jsonArray['scriptCall'][] = $this->fObj->extJSCODE;
		}
	}

	/**
	 * Generates an error message that transferred as JSON for AJAX calls
	 *
	 * @param string $message The error message to be shown
	 * @return array The error message in a JSON array
	 */
	protected function getErrorMessageForAJAX($message) {
		$jsonArray = array(
			'data' => $message,
			'scriptCall' => array(
				'alert("' . $message . '");'
			)
		);
		return $jsonArray;
	}

	/**
	 * Handle AJAX calls to show a new inline-record of the given table.
	 * Normally this method is never called from inside TYPO3. Always from outside by AJAX.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @param string $foreignUid If set, the new record should be inserted after that one.
	 * @return array An array to be used for JSON
	 * @todo Define visibility
	 */
	public function createNewRecord($domObjectId, $foreignUid = 0) {
		// The current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
		// The parent table - this table embeds the current table
		$parent = $this->getStructureLevel(-1);
		// Get TCA 'config' of the parent table
		if (!$this->checkConfiguration($parent['config'])) {
			return $this->getErrorMessageForAJAX('Wrong configuration in table ' . $parent['table']);
		}
		$config = $parent['config'];
		$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
		$expandSingle = isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle'];
		// Put the current level also to the dynNestedStack of TCEforms:
		$this->fObj->pushToDynNestedStack('inline', $this->inlineNames['object']);
		// Dynamically create a new record using \TYPO3\CMS\Backend\Form\DataPreprocessor
		if (!$foreignUid || !\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($foreignUid) || $config['foreign_selector']) {
			$record = $this->getNewRecord($this->inlineFirstPid, $current['table']);
			// Set language of new child record to the language of the parent record:
			if ($config['localizationMode'] == 'select') {
				$parentRecord = $this->getRecord(0, $parent['table'], $parent['uid']);
				$parentLanguageField = $GLOBALS['TCA'][$parent['table']]['ctrl']['languageField'];
				$childLanguageField = $GLOBALS['TCA'][$current['table']]['ctrl']['languageField'];
				if ($parentRecord[$parentLanguageField] > 0) {
					$record[$childLanguageField] = $parentRecord[$parentLanguageField];
				}
			}
		} else {
			$record = $this->getRecord($this->inlineFirstPid, $current['table'], $foreignUid);
		}
		// Now there is a foreign_selector, so there is a new record on the intermediate table, but
		// this intermediate table holds a field, which is responsible for the foreign_selector, so
		// we have to set this field to the uid we get - or if none, to a new uid
		if ($config['foreign_selector'] && $foreignUid) {
			$selConfig = $this->getPossibleRecordsSelectorConfig($config, $config['foreign_selector']);
			// For a selector of type group/db, prepend the tablename (<tablename>_<uid>):
			$record[$config['foreign_selector']] = $selConfig['type'] != 'groupdb' ? '' : $selConfig['table'] . '_';
			$record[$config['foreign_selector']] .= $foreignUid;
		}
		// The HTML-object-id's prefix of the dynamically created record
		$objectPrefix = $this->inlineNames['object'] . self::Structure_Separator . $current['table'];
		$objectId = $objectPrefix . self::Structure_Separator . $record['uid'];
		// Render the foreign record that should passed back to browser
		$item = $this->renderForeignRecord($parent['uid'], $record, $config);
		if ($item === FALSE) {
			return $this->getErrorMessageForAJAX('Access denied');
		}
		if (!$current['uid']) {
			$jsonArray = array(
				'data' => $item,
				'scriptCall' => array(
					'inline.domAddNewRecord(\'bottom\',\'' . $this->inlineNames['object'] . '_records\',\'' . $objectPrefix . '\',json.data);',
					'inline.memorizeAddRecord(\'' . $objectPrefix . '\',\'' . $record['uid'] . '\',null,\'' . $foreignUid . '\');'
				)
			);
		} else {
			$jsonArray = array(
				'data' => $item,
				'scriptCall' => array(
					'inline.domAddNewRecord(\'after\',\'' . $domObjectId . '_div' . '\',\'' . $objectPrefix . '\',json.data);',
					'inline.memorizeAddRecord(\'' . $objectPrefix . '\',\'' . $record['uid'] . '\',\'' . $current['uid'] . '\',\'' . $foreignUid . '\');'
				)
			);
		}
		$this->getCommonScriptCalls($jsonArray, $config);
		// Collapse all other records if requested:
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = 'inline.collapseAllRecords(\'' . $objectId . '\', \'' . $objectPrefix . '\', \'' . $record['uid'] . '\');';
		}
		// Tell the browser to scroll to the newly created record
		$jsonArray['scriptCall'][] = 'Element.scrollTo(\'' . $objectId . '_div\');';
		// Fade out and fade in the new record in the browser view to catch the user's eye
		$jsonArray['scriptCall'][] = 'inline.fadeOutFadeIn(\'' . $objectId . '_div\');';
		// Remove the current level also from the dynNestedStack of TCEforms:
		$this->fObj->popFromDynNestedStack();
		// Return the JSON array:
		return $jsonArray;
	}

	/**
	 * Handle AJAX calls to localize all records of a parent, localize a single record or to synchronize with the original language parent.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @param mixed $type Defines the type 'localize' or 'synchronize' (string) or a single uid to be localized (integer)
	 * @return array An array to be used for JSON
	 */
	protected function synchronizeLocalizeRecords($domObjectId, $type) {
		$jsonArray = FALSE;
		if (\TYPO3\CMS\Core\Utility\GeneralUtility::inList('localize,synchronize', $type) || \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($type)) {
			// The current level:
			$current = $this->inlineStructure['unstable'];
			// The parent level:
			$parent = $this->getStructureLevel(-1);
			$parentRecord = $this->getRecord(0, $parent['table'], $parent['uid']);
			$cmd = array();
			$cmd[$parent['table']][$parent['uid']]['inlineLocalizeSynchronize'] = $parent['field'] . ',' . $type;
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\DataHandling\\DataHandler');
			$tce->stripslashes_values = FALSE;
			$tce->start(array(), $cmd);
			$tce->process_cmdmap();
			$newItemList = $tce->registerDBList[$parent['table']][$parent['uid']][$parent['field']];
			unset($tce);
			$jsonArray = $this->getExecuteChangesJsonArray($parentRecord[$parent['field']], $newItemList);
			$this->getCommonScriptCalls($jsonArray, $parent['config']);
		}
		return $jsonArray;
	}

	/**
	 * Handle AJAX calls to dynamically load the form fields of a given record.
	 * (basically a copy of "createNewRecord")
	 * Normally this method is never called from inside TYPO3. Always from outside by AJAX.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @return array An array to be used for JSON
	 * @todo Define visibility
	 */
	public function getRecordDetails($domObjectId) {
		// The current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
		// The parent table - this table embeds the current table
		$parent = $this->getStructureLevel(-1);
		// Get TCA 'config' of the parent table
		if (!$this->checkConfiguration($parent['config'])) {
			return $this->getErrorMessageForAJAX('Wrong configuration in table ' . $parent['table']);
		}
		$config = $parent['config'];
		// Set flag in config so that only the fields are rendered
		$config['renderFieldsOnly'] = TRUE;
		$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
		$expandSingle = isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle'];
		// Put the current level also to the dynNestedStack of TCEforms:
		$this->fObj->pushToDynNestedStack('inline', $this->inlineNames['object']);
		$record = $this->getRecord($this->inlineFirstPid, $current['table'], $current['uid']);
		// The HTML-object-id's prefix of the dynamically created record
		$objectPrefix = $this->inlineNames['object'] . self::Structure_Separator . $current['table'];
		$objectId = $objectPrefix . self::Structure_Separator . $record['uid'];
		$item = $this->renderForeignRecord($parent['uid'], $record, $config);
		if ($item === FALSE) {
			return $this->getErrorMessageForAJAX('Access denied');
		}
		$jsonArray = array(
			'data' => $item,
			'scriptCall' => array(
				'inline.domAddRecordDetails(\'' . $domObjectId . '\',\'' . $objectPrefix . '\',' . ($expandSingle ? '1' : '0') . ',json.data);'
			)
		);
		if ($config['foreign_unique']) {
			$jsonArray['scriptCall'][] = 'inline.removeUsed(\'' . $objectPrefix . '\',\'' . $record['uid'] . '\');';
		}
		$this->getCommonScriptCalls($jsonArray, $config);
		// Collapse all other records if requested:
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = 'inline.collapseAllRecords(\'' . $objectId . '\',\'' . $objectPrefix . '\',\'' . $record['uid'] . '\');';
		}
		// Remove the current level also from the dynNestedStack of TCEforms:
		$this->fObj->popFromDynNestedStack();
		// Return the JSON array:
		return $jsonArray;
	}

	/**
	 * Generates a JSON array which executes the changes and thus updates the forms view.
	 *
	 * @param string $oldItemList List of related child reocrds before changes were made (old)
	 * @param string $newItemList List of related child records after changes where made (new)
	 * @return array An array to be used for JSON
	 */
	protected function getExecuteChangesJsonArray($oldItemList, $newItemList) {
		$data = '';
		$parent = $this->getStructureLevel(-1);
		$current = $this->inlineStructure['unstable'];
		$jsonArray = array('scriptCall' => array());
		$jsonArrayScriptCall = &$jsonArray['scriptCall'];
		$nameObject = $this->inlineNames['object'];
		$nameObjectForeignTable = $nameObject . self::Structure_Separator . $current['table'];
		// Get the name of the field pointing to the original record:
		$transOrigPointerField = $GLOBALS['TCA'][$current['table']]['ctrl']['transOrigPointerField'];
		// Get the name of the field used as foreign selector (if any):
		$foreignSelector = isset($parent['config']['foreign_selector']) && $parent['config']['foreign_selector'] ? $parent['config']['foreign_selector'] : FALSE;
		// Convert lists to array with uids of child records:
		$oldItems = $this->getRelatedRecordsUidArray($oldItemList);
		$newItems = $this->getRelatedRecordsUidArray($newItemList);
		// Determine the items that were localized or localized:
		$removedItems = array_diff($oldItems, $newItems);
		$localizedItems = array_diff($newItems, $oldItems);
		// Set the items that should be removed in the forms view:
		foreach ($removedItems as $item) {
			$jsonArrayScriptCall[] = 'inline.deleteRecord(\'' . $nameObjectForeignTable . self::Structure_Separator . $item . '\', {forceDirectRemoval: true});';
		}
		// Set the items that should be added in the forms view:
		foreach ($localizedItems as $item) {
			$row = $this->getRecord($this->inlineFirstPid, $current['table'], $item);
			$selectedValue = $foreignSelector ? '\'' . $row[$foreignSelector] . '\'' : 'null';
			$data .= $this->renderForeignRecord($parent['uid'], $row, $parent['config']);
			$jsonArrayScriptCall[] = 'inline.memorizeAddRecord(\'' . $nameObjectForeignTable . '\', \'' . $item . '\', null, ' . $selectedValue . ');';
			// Remove possible virtual records in the form which showed that a child records could be localized:
			if (isset($row[$transOrigPointerField]) && $row[$transOrigPointerField]) {
				$jsonArrayScriptCall[] = 'inline.fadeAndRemove(\'' . $nameObjectForeignTable . self::Structure_Separator . $row[$transOrigPointerField] . '_div' . '\');';
			}
		}
		if ($data) {
			$jsonArray['data'] = $data;
			array_unshift($jsonArrayScriptCall, 'inline.domAddNewRecord(\'bottom\', \'' . $nameObject . '_records\', \'' . $nameObjectForeignTable . '\', json.data);');
		}
		return $jsonArray;
	}

	/**
	 * Save the expanded/collapsed state of a child record in the BE_USER->uc.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @param string $expand Whether this record is expanded.
	 * @param string $collapse Whether this record is collapsed.
	 * @return void
	 * @todo Define visibility
	 */
	public function setExpandedCollapsedState($domObjectId, $expand, $collapse) {
		// Parse the DOM identifier (string), add the levels to the structure stack (array), but don't load TCA config
		$this->parseStructureString($domObjectId, FALSE);
		// The current table - for this table we should add/import records
		$current = $this->inlineStructure['unstable'];
		// The top parent table - this table embeds the current table
		$top = $this->getStructureLevel(0);
		// Only do some action if the top record and the current record were saved before
		if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($top['uid'])) {
			$inlineView = (array) unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			$inlineViewCurrent = &$inlineView[$top['table']][$top['uid']];
			$expandUids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $expand);
			$collapseUids = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $collapse);
			// Set records to be expanded
			foreach ($expandUids as $uid) {
				$inlineViewCurrent[$current['table']][] = $uid;
			}
			// Set records to be collapsed
			foreach ($collapseUids as $uid) {
				$inlineViewCurrent[$current['table']] = $this->removeFromArray($uid, $inlineViewCurrent[$current['table']]);
			}
			// Save states back to database
			if (is_array($inlineViewCurrent[$current['table']])) {
				$inlineViewCurrent[$current['table']] = array_unique($inlineViewCurrent[$current['table']]);
				$GLOBALS['BE_USER']->uc['inlineView'] = serialize($inlineView);
				$GLOBALS['BE_USER']->writeUC();
			}
		}
	}

	/*******************************************************
	 *
	 * Get data from database and handle relations
	 *
	 *******************************************************/
	/**
	 * Get the related records of the embedding item, this could be 1:n, m:n.
	 * Returns an associative array with the keys records and count. 'count' contains only real existing records on the current parent record.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @return array The records related to the parent item as associative array.
	 * @todo Define visibility
	 */
	public function getRelatedRecords($table, $field, $row, &$PA, $config) {
		$pid = $row['pid'];
		$elements = $PA['itemFormElValue'];
		$foreignTable = $config['foreign_table'];
		$localizationMode = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($table, $config);
		if ($localizationMode != FALSE) {
			$language = intval($row[$GLOBALS['TCA'][$table]['ctrl']['languageField']]);
			$transOrigPointer = intval($row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]);
			$transOrigTable = \TYPO3\CMS\Backend\Utility\BackendUtility::getOriginalTranslationTable($table);

			if ($language > 0 && $transOrigPointer) {
				// Localization in mode 'keep', isn't a real localization, but keeps the children of the original parent record:
				if ($localizationMode == 'keep') {
					$transOrigRec = $this->getRecord(0, $transOrigTable, $transOrigPointer);
					$elements = $transOrigRec[$field];
					$pid = $transOrigRec['pid'];
				} elseif ($localizationMode == 'select') {
					$transOrigRec = $this->getRecord(0, $transOrigTable, $transOrigPointer);
					$pid = $transOrigRec['pid'];
					$recordsOriginal = $this->getRelatedRecordsArray($pid, $foreignTable, $transOrigRec[$field]);
				}
			}
		}
		$records = $this->getRelatedRecordsArray($pid, $foreignTable, $elements);
		$relatedRecords = array('records' => $records, 'count' => count($records));
		// Merge original language with current localization and show differences:
		if (is_array($recordsOriginal)) {
			$options = array(
				'showPossible' => isset($config['appearance']['showPossibleLocalizationRecords']) && $config['appearance']['showPossibleLocalizationRecords'],
				'showRemoved' => isset($config['appearance']['showRemovedLocalizationRecords']) && $config['appearance']['showRemovedLocalizationRecords']
			);
			if ($options['showPossible'] || $options['showRemoved']) {
				$relatedRecords['records'] = $this->getLocalizationDifferences($foreignTable, $options, $recordsOriginal, $records);
			}
		}
		return $relatedRecords;
	}

	/**
	 * Gets the related records of the embedding item, this could be 1:n, m:n.
	 *
	 * @param integer $pid The pid of the parent record
	 * @param string $table The table name of the record
	 * @param string $itemList The list of related child records
	 * @return array The records related to the parent item
	 */
	protected function getRelatedRecordsArray($pid, $table, $itemList) {
		$records = array();
		$itemArray = $this->getRelatedRecordsUidArray($itemList);
		// Perform modification of the selected items array:
		foreach ($itemArray as $uid) {
			// Get the records for this uid using \TYPO3\CMS\Backend\Form\DataPreprocessor
			if ($record = $this->getRecord($pid, $table, $uid)) {
				$records[$uid] = $record;
			}
		}
		return $records;
	}

	/**
	 * Gets an array with the uids of related records out of a list of items.
	 * This list could contain more information than required. This methods just
	 * extracts the uids.
	 *
	 * @param string $itemList The list of related child records
	 * @return array An array with uids
	 */
	protected function getRelatedRecordsUidArray($itemList) {
		$itemArray = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $itemList, 1);
		// Perform modification of the selected items array:
		foreach ($itemArray as $key => &$value) {
			$parts = explode('|', $value, 2);
			$value = $parts[0];
		}
		unset($value);
		return $itemArray;
	}

	/**
	 * Gets the difference between current localized structure and the original language structure.
	 * If there are records which once were localized but don't exist in the original version anymore, the record row is marked with '__remove'.
	 * If there are records which can be localized and exist only in the original version, the record row is marked with '__create' and '__virtual'.
	 *
	 * @param string $table The table name of the parent records
	 * @param array $options Options defining what kind of records to display
	 * @param array $recordsOriginal The uids of the child records of the original language
	 * @param array $recordsLocalization The uids of the child records of the current localization
	 * @return array Merged array of uids of the child records of both versions
	 */
	protected function getLocalizationDifferences($table, array $options, array $recordsOriginal, array $recordsLocalization) {
		$records = array();
		$transOrigPointerField = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'];
		// Compare original to localized version of the records:
		foreach ($recordsLocalization as $uid => $row) {
			// If the record points to a original translation which doesn't exist anymore, it could be removed:
			if (isset($row[$transOrigPointerField]) && $row[$transOrigPointerField] > 0) {
				$transOrigPointer = $row[$transOrigPointerField];
				if (isset($recordsOriginal[$transOrigPointer])) {
					unset($recordsOriginal[$transOrigPointer]);
				} elseif ($options['showRemoved']) {
					$row['__remove'] = TRUE;
				}
			}
			$records[$uid] = $row;
		}
		// Process the remaining records in the original unlocalized parent:
		if ($options['showPossible']) {
			foreach ($recordsOriginal as $uid => $row) {
				$row['__create'] = TRUE;
				$row['__virtual'] = TRUE;
				$records[$uid] = $row;
			}
		}
		return $records;
	}

	/**
	 * Get possible records.
	 * Copied from TCEform and modified.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $conf An array with additional configuration options.
	 * @param string $checkForConfField For which field in the foreign_table the possible records should be fetched
	 * @return mixed Array of possible record items; FALSE if type is "group/db", then everything could be "possible
	 * @todo Define visibility
	 */
	public function getPossibleRecords($table, $field, $row, $conf, $checkForConfField = 'foreign_selector') {
		// ctrl configuration from TCA:
		$tcaTableCtrl = $GLOBALS['TCA'][$table]['ctrl'];
		// Field configuration from TCA:
		$foreign_table = $conf['foreign_table'];
		$foreign_check = $conf[$checkForConfField];
		$foreignConfig = $this->getPossibleRecordsSelectorConfig($conf, $foreign_check);
		$PA = $foreignConfig['PA'];
		$config = $PA['fieldConf']['config'];
		if ($foreignConfig['type'] == 'select') {
			// Getting the selector box items from the system
			$selItems = $this->fObj->addSelectOptionsToItemArray($this->fObj->initItemArray($PA['fieldConf']), $PA['fieldConf'], $this->fObj->setTSconfig($table, $row), $field);
			// Possibly filter some items:
			$keepItemsFunc = create_function('$value', 'return $value[1];');
			$selItems = \TYPO3\CMS\Core\Utility\GeneralUtility::keepItemsInArray($selItems, $PA['fieldTSConfig']['keepItems'], $keepItemsFunc);
			// Possibly add some items:
			$selItems = $this->fObj->addItems($selItems, $PA['fieldTSConfig']['addItems.']);
			if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
				$selItems = $this->fObj->procItems($selItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
			}
			// Possibly remove some items:
			$removeItems = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], 1);
			foreach ($selItems as $tk => $p) {
				// Checking languages and authMode:
				$languageDeny = $tcaTableCtrl['languageField'] && !strcmp($tcaTableCtrl['languageField'], $field) && !$GLOBALS['BE_USER']->checkLanguageAccess($p[1]);
				$authModeDeny = $config['form_type'] == 'select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table, $field, $p[1], $config['authMode']);
				if (in_array($p[1], $removeItems) || $languageDeny || $authModeDeny) {
					unset($selItems[$tk]);
				} elseif (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
					$selItems[$tk][0] = htmlspecialchars($this->fObj->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]));
				}
				// Removing doktypes with no access:
				if (($table === 'pages' || $table === 'pages_language_overlay') && $field === 'doktype') {
					if (!($GLOBALS['BE_USER']->isAdmin() || \TYPO3\CMS\Core\Utility\GeneralUtility::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'], $p[1]))) {
						unset($selItems[$tk]);
					}
				}
			}
		} else {
			$selItems = FALSE;
		}
		return $selItems;
	}

	/**
	 * Gets the uids of a select/selector that should be unique an have already been used.
	 *
	 * @param array $records All inline records on this level
	 * @param array $conf The TCA field configuration of the inline field to be rendered
	 * @param boolean $splitValue For usage with group/db, values come like "tx_table_123|Title%20abc", but we need "tx_table" and "123
	 * @return array The uids, that have been used already and should be used unique
	 * @todo Define visibility
	 */
	public function getUniqueIds($records, $conf = array(), $splitValue = FALSE) {
		$uniqueIds = array();
		if (isset($conf['foreign_unique']) && $conf['foreign_unique'] && count($records)) {
			foreach ($records as $rec) {
				// Skip virtual records (e.g. shown in localization mode):
				if (!isset($rec['__virtual']) || !$rec['__virtual']) {
					$value = $rec[$conf['foreign_unique']];
					// Split the value and extract the table and uid:
					if ($splitValue) {
						$valueParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode('|', $value);
						$itemParts = explode('_', $valueParts[0]);
						$value = array(
							'uid' => array_pop($itemParts),
							'table' => implode('_', $itemParts)
						);
					}
					$uniqueIds[$rec['uid']] = $value;
				}
			}
		}
		return $uniqueIds;
	}

	/**
	 * Determines the corrected pid to be used for a new record.
	 * The pid to be used can be defined by a Page TSconfig.
	 *
	 * @param string $table The table name
	 * @param integer $parentPid The pid of the parent record
	 * @return integer The corrected pid to be used for a new record
	 */
	protected function getNewRecordPid($table, $parentPid = NULL) {
		$newRecordPid = $this->inlineFirstPid;
		$pageTS = \TYPO3\CMS\Backend\Utility\BackendUtility::getPagesTSconfig($parentPid, TRUE);
		if (isset($pageTS['TCAdefaults.'][$table . '.']['pid']) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($pageTS['TCAdefaults.'][$table . '.']['pid'])) {
			$newRecordPid = $pageTS['TCAdefaults.'][$table . '.']['pid'];
		} elseif (isset($parentPid) && \TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($parentPid)) {
			$newRecordPid = $parentPid;
		}
		return $newRecordPid;
	}

	/**
	 * Get a single record row for a TCA table from the database.
	 * \TYPO3\CMS\Backend\Form\DataPreprocessor is used for "upgrading" the
	 * values, especially the relations.
	 *
	 * @param integer $pid The pid of the page the record should be stored (only relevant for NEW records)
	 * @param string $table The table to fetch data from (= foreign_table)
	 * @param string $uid The uid of the record to fetch, or the pid if a new record should be created
	 * @param string $cmd The command to perform, empty or 'new'
	 * @return array A record row from the database post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor
	 * @todo Define visibility
	 */
	public function getRecord($pid, $table, $uid, $cmd = '') {
		// Fetch workspace version of a record (if any):
		if ($cmd !== 'new' && $GLOBALS['BE_USER']->workspace !== 0) {
			$workspaceVersion = \TYPO3\CMS\Backend\Utility\BackendUtility::getWorkspaceVersionOfRecord($GLOBALS['BE_USER']->workspace, $table, $uid, 'uid');
			if ($workspaceVersion !== FALSE) {
				$uid = $workspaceVersion['uid'];
			}
		}
		$trData = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\DataPreprocessor');
		$trData->addRawData = TRUE;
		$trData->lockRecords = 1;
		$trData->disableRTE = $GLOBALS['SOBE']->MOD_SETTINGS['disableRTE'];
		// If a new record should be created
		$trData->fetchRecord($table, $uid, $cmd === 'new' ? 'new' : '');
		$rec = reset($trData->regTableItems_data);
		return $rec;
	}

	/**
	 * Wrapper. Calls getRecord in case of a new record should be created.
	 *
	 * @param integer $pid The pid of the page the record should be stored (only relevant for NEW records)
	 * @param string $table The table to fetch data from (= foreign_table)
	 * @return array A record row from the database post-processed by \TYPO3\CMS\Backend\Form\DataPreprocessor
	 * @todo Define visibility
	 */
	public function getNewRecord($pid, $table) {
		$rec = $this->getRecord($pid, $table, $pid, 'new');
		$rec['uid'] = uniqid('NEW');
		$rec['pid'] = $this->getNewRecordPid($table, $pid);
		return $rec;
	}

	/*******************************************************
	 *
	 * Structure stack for handling inline objects/levels
	 *
	 *******************************************************/
	/**
	 * Add a new level on top of the structure stack. Other functions can access the
	 * stack and determine, if there's possibly a endless loop.
	 *
	 * @param string $table The table name of the record
	 * @param string $uid The uid of the record that embeds the inline data
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $config The TCA-configuration of the inline field
	 * @param array $parameters The full parameter array (PA)
	 * @return void
	 * @todo Define visibility
	 */
	public function pushStructure($table, $uid, $field = '', $config = array(), array $parameters = array()) {
		$structure = array(
			'table' => $table,
			'uid' => $uid,
			'field' => $field,
			'config' => $config,
			'localizationMode' => \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($table, $config),
		);

		// Extract FlexForm parts (if any) from element name,
		// e.g. array('vDEF', 'lDEF', 'FlexField', 'vDEF')
		if (!empty($parameters['itemFormElName'])) {
			$flexFormParts = $this->extractFlexFormParts($parameters['itemFormElName']);

			if ($flexFormParts !== NULL) {
				$structure['flexform'] = $flexFormParts;
			}
		}

		$this->inlineStructure['stable'][] = $structure;
		$this->updateStructureNames();
	}

	/**
	 * Remove the item on top of the structure stack and return it.
	 *
	 * @return array The top item of the structure stack - array(<table>,<uid>,<field>,<config>)
	 * @todo Define visibility
	 */
	public function popStructure() {
		$popItem = NULL;

		if (count($this->inlineStructure['stable'])) {
			$popItem = array_pop($this->inlineStructure['stable']);
			$this->updateStructureNames();
		}
		return $popItem;
	}

	/**
	 * For common use of DOM object-ids and form field names of a several inline-level,
	 * these names/identifiers are preprocessed and set to $this->inlineNames.
	 * This function is automatically called if a level is pushed to or removed from the
	 * inline structure stack.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function updateStructureNames() {
		$current = $this->getStructureLevel(-1);
		// If there are still more inline levels available
		if ($current !== FALSE) {
			$this->inlineNames = array(
				'form' => $this->prependFormFieldNames . $this->getStructureItemName($current, self::Disposal_AttributeName),
				'object' => $this->prependNaming . self::Structure_Separator . $this->inlineFirstPid . self::Structure_Separator . $this->getStructurePath()
			);
		} else {
			$this->inlineNames = array();
		}
	}

	/**
	 * Create a name/id for usage in HTML output of a level of the structure stack to be used in form names.
	 *
	 * @param array $levelData Array of a level of the structure stack (containing the keys table, uid and field)
	 * @param string $disposal How the structure name is used (e.g. as <div id="..."> or <input name="..." />)
	 * @return string The name/id of that level, to be used for HTML output
	 * @todo Define visibility
	 */
	public function getStructureItemName($levelData, $disposal = self::Disposal_AttributeId) {
		$name = NULL;

		if (is_array($levelData)) {
			$parts = array($levelData['table'], $levelData['uid']);

			if (!empty($levelData['field'])) {
				$parts[] = $levelData['field'];
			}

			// Use in name attributes:
			if ($disposal === self::Disposal_AttributeName) {
				if (!empty($levelData['field']) && !empty($levelData['flexform']) && $this->getStructureLevel(-1) === $levelData) {
					$parts[] = implode('][', $levelData['flexform']);
				}
				$name = '[' . implode('][', $parts) . ']';
			// Use in object id attributes:
			} else {
				$name = implode(self::Structure_Separator, $parts);

				if (!empty($levelData['field']) && !empty($levelData['flexform'])) {
					array_unshift($levelData['flexform'], $name);
					$name = implode(self::FlexForm_Separator, $levelData['flexform']);
				}
			}
		}

		return $name;
	}

	/**
	 * Get a level from the stack and return the data.
	 * If the $level value is negative, this function works top-down,
	 * if the $level value is positive, this function works bottom-up.
	 *
	 * @param integer $level Which level to return
	 * @return array The item of the stack at the requested level
	 * @todo Define visibility
	 */
	public function getStructureLevel($level) {
		$level = $this->calculateStructureLevel($level);

		if ($level !== FALSE) {
			return $this->inlineStructure['stable'][$level];
		} else {
			return FALSE;
		}
	}

	/**
	 * Calculates structure level.
	 *
	 * @param integer $level Which level to return
	 * @return boolean|integer
	 */
	protected function calculateStructureLevel($level) {
		$result = FALSE;

		$inlineStructureCount = count($this->inlineStructure['stable']);
		if ($level < 0) {
			$level = $inlineStructureCount + $level;
		}
		if ($level >= 0 && $level < $inlineStructureCount) {
			$result = $level;
		}

		return $result;
	}

	/**
	 * Get the identifiers of a given depth of level, from the top of the stack to the bottom.
	 * An identifier looks like "<table>-<uid>-<field>".
	 *
	 * @param integer $structureDepth How much levels to output, beginning from the top of the stack
	 * @return 	string		The path of identifiers
	 * @todo Define visibility
	 */
	public function getStructurePath($structureDepth = -1) {
		$structureLevels = array();
		$structureCount = count($this->inlineStructure['stable']);
		if ($structureDepth < 0 || $structureDepth > $structureCount) {
			$structureDepth = $structureCount;
		}
		for ($i = 1; $i <= $structureDepth; $i++) {
			array_unshift($structureLevels, $this->getStructureItemName($this->getStructureLevel(-$i), self::Disposal_AttributeId));
		}
		return implode(self::Structure_Separator, $structureLevels);
	}

	/**
	 * Convert the DOM object-id of an inline container to an array.
	 * The object-id could look like 'data-parentPageId-tx_mmftest_company-1-employees'.
	 * The result is written to $this->inlineStructure.
	 * There are two keys:
	 * - 'stable': Containing full qualified identifiers (table, uid and field)
	 * - 'unstable': Containting partly filled data (e.g. only table and possibly field)
	 *
	 * @param string $domObjectId The DOM object-id
	 * @param boolean $loadConfig Load the TCA configuration for that level (default: TRUE)
	 * @return void
	 * @todo Define visibility
	 */
	public function parseStructureString($string, $loadConfig = TRUE) {
		$unstable = array();
		$vector = array('table', 'uid', 'field');

		// Substitute FlexForm additon and make parsing a bit easier
		$string = str_replace(self::FlexForm_Separator, self::FlexForm_Substitute, $string);
		// The starting pattern of an object identifer (e.g. "data-<firstPidValue>-<anything>)
		$pattern = '/^' . $this->prependNaming . self::Structure_Separator . '(.+?)' . self::Structure_Separator . '(.+)$/';

		if (preg_match($pattern, $string, $match)) {
			$this->inlineFirstPid = $match[1];
			$parts = explode(self::Structure_Separator, $match[2]);
			$partsCnt = count($parts);
			for ($i = 0; $i < $partsCnt; $i++) {
				if ($i > 0 && $i % 3 == 0) {
					// Load the TCA configuration of the table field and store it in the stack
					if ($loadConfig) {
						$unstable['config'] = $GLOBALS['TCA'][$unstable['table']]['columns'][$unstable['field']]['config'];
						// Fetch TSconfig:
						$TSconfig = $this->fObj->setTSconfig($unstable['table'], array('uid' => $unstable['uid'], 'pid' => $this->inlineFirstPid), $unstable['field']);
						// Override TCA field config by TSconfig:
						if (!$TSconfig['disabled']) {
							$unstable['config'] = $this->fObj->overrideFieldConf($unstable['config'], $TSconfig);
						}
						$unstable['localizationMode'] = \TYPO3\CMS\Backend\Utility\BackendUtility::getInlineLocalizationMode($unstable['table'], $unstable['config']);
					}

					// Extract FlexForm from field part (if any)
					if (strpos($unstable['field'], self::FlexForm_Substitute) !== FALSE) {
						$fieldParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(self::FlexForm_Substitute, $unstable['field']);
						$unstable['field'] = array_shift($fieldParts);
						// FlexForm parts start with data:
						if (count($fieldParts) > 0 && $fieldParts[0] === 'data') {
							$unstable['flexform'] = $fieldParts;
						}
					}

					$this->inlineStructure['stable'][] = $unstable;
					$unstable = array();
				}
				$unstable[$vector[$i % 3]] = $parts[$i];
			}
			$this->updateStructureNames();
			if (count($unstable)) {
				$this->inlineStructure['unstable'] = $unstable;
			}
		}
	}

	/*******************************************************
	 *
	 * Helper functions
	 *
	 *******************************************************/
	/**
	 * Does some checks on the TCA configuration of the inline field to render.
	 *
	 * @param array $config Reference to the TCA field configuration
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array of the parent
	 * @return boolean If critical configuration errors were found, FALSE is returned
	 * @todo Define visibility
	 */
	public function checkConfiguration(&$config) {
		$foreign_table = $config['foreign_table'];
		// An inline field must have a foreign_table, if not, stop all further inline actions for this field:
		if (!$foreign_table || !is_array($GLOBALS['TCA'][$foreign_table])) {
			return FALSE;
		}
		// Init appearance if not set:
		if (!isset($config['appearance']) || !is_array($config['appearance'])) {
			$config['appearance'] = array();
		}
		// Set the position/appearance of the "Create new record" link:
		if (isset($config['foreign_selector']) && $config['foreign_selector'] && (!isset($config['appearance']['useCombination']) || !$config['appearance']['useCombination'])) {
			$config['appearance']['levelLinksPosition'] = 'none';
		} elseif (!isset($config['appearance']['levelLinksPosition']) || !in_array($config['appearance']['levelLinksPosition'], array('top', 'bottom', 'both', 'none'))) {
			$config['appearance']['levelLinksPosition'] = 'top';
		}
		// Defines which controls should be shown in header of each record:
		$enabledControls = array(
			'info' => TRUE,
			'new' => TRUE,
			'dragdrop' => TRUE,
			'sort' => TRUE,
			'hide' => TRUE,
			'delete' => TRUE,
			'localize' => TRUE
		);
		if (isset($config['appearance']['enabledControls']) && is_array($config['appearance']['enabledControls'])) {
			$config['appearance']['enabledControls'] = array_merge($enabledControls, $config['appearance']['enabledControls']);
		} else {
			$config['appearance']['enabledControls'] = $enabledControls;
		}
		return TRUE;
	}

	/**
	 * Checks the page access rights (Code for access check mostly taken from alt_doc.php)
	 * as well as the table access rights of the user.
	 *
	 * @param string $cmd The command that sould be performed ('new' or 'edit')
	 * @param string $table The table to check access for
	 * @param string $theUid The record uid of the table
	 * @return boolean Returns TRUE is the user has access, or FALSE if not
	 * @todo Define visibility
	 */
	public function checkAccess($cmd, $table, $theUid) {
		// Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
		// First, resetting flags.
		$hasAccess = 0;
		$deniedAccessReason = '';
		// Admin users always have acces:
		if ($GLOBALS['BE_USER']->isAdmin()) {
			return TRUE;
		}
		// If the command is to create a NEW record...:
		if ($cmd == 'new') {
			// If the pid is numerical, check if it's possible to write to this page:
			if (\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($this->inlineFirstPid)) {
				$calcPRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $this->inlineFirstPid);
				if (!is_array($calcPRec)) {
					return FALSE;
				}
				// Permissions for the parent page
				$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
				// If pages:
				if ($table == 'pages') {
					// Are we allowed to create new subpages?
					$hasAccess = $CALC_PERMS & 8 ? 1 : 0;
				} else {
					// Are we allowed to edit content on this page?
					$hasAccess = $CALC_PERMS & 16 ? 1 : 0;
				}
			} else {
				$hasAccess = 1;
			}
		} else {
			// Edit:
			$calcPRec = \TYPO3\CMS\Backend\Utility\BackendUtility::getRecord($table, $theUid);
			\TYPO3\CMS\Backend\Utility\BackendUtility::fixVersioningPid($table, $calcPRec);
			if (is_array($calcPRec)) {
				// If pages:
				if ($table == 'pages') {
					$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms($calcPRec);
					$hasAccess = $CALC_PERMS & 2 ? 1 : 0;
				} else {
					// Fetching pid-record first.
					$CALC_PERMS = $GLOBALS['BE_USER']->calcPerms(\TYPO3\CMS\Backend\Utility\BackendUtility::getRecord('pages', $calcPRec['pid']));
					$hasAccess = $CALC_PERMS & 16 ? 1 : 0;
				}
				// Check internals regarding access:
				if ($hasAccess) {
					$hasAccess = $GLOBALS['BE_USER']->recordEditAccessInternals($table, $calcPRec);
				}
			}
		}
		if (!$GLOBALS['BE_USER']->check('tables_modify', $table)) {
			$hasAccess = 0;
		}
		if (!$hasAccess) {
			$deniedAccessReason = $GLOBALS['BE_USER']->errorMsg;
			if ($deniedAccessReason) {
				debug($deniedAccessReason);
			}
		}
		return $hasAccess ? TRUE : FALSE;
	}

	/**
	 * Check the keys and values in the $compare array against the ['config'] part of the top level of the stack.
	 * A boolean value is return depending on how the comparison was successful.
	 *
	 * @param array $compare Keys and values to compare to the ['config'] part of the top level of the stack
	 * @return boolean Whether the comparison was successful
	 * @see arrayCompareComplex
	 * @todo Define visibility
	 */
	public function compareStructureConfiguration($compare) {
		$level = $this->getStructureLevel(-1);
		$result = $this->arrayCompareComplex($level, $compare);
		return $result;
	}

	/**
	 * Normalize a relation "uid" published by transferData, like "1|Company%201"
	 *
	 * @param string $string A transferData reference string, containing the uid
	 * @return string The normalized uid
	 * @todo Define visibility
	 */
	public function normalizeUid($string) {
		$parts = explode('|', $string);
		return $parts[0];
	}

	/**
	 * Wrap the HTML code of a section with a table tag.
	 *
	 * @param string $section The HTML code to be wrapped
	 * @param array $styleAttrs Attributes for the style argument in the table tag
	 * @param array $tableAttrs Attributes for the table tag (like width, border, etc.)
	 * @return string The wrapped HTML code
	 * @todo Define visibility
	 */
	public function wrapFormsSection($section, $styleAttrs = array(), $tableAttrs = array()) {
		$style = '';
		$table = '';
		foreach ($styleAttrs as $key => $value) {
			$style .= ($style ? ' ' : '') . $key . ': ' . htmlspecialchars($value) . '; ';
		}
		if ($style) {
			$style = ' style="' . $style . '"';
		}
		if (!$tableAttrs['background'] && $this->fObj->borderStyle[2]) {
			$tableAttrs['background'] = $this->backPath . $this->borderStyle[2];
		}
		if (!$tableAttrs['class'] && $this->borderStyle[3]) {
			$tableAttrs['class'] = $this->borderStyle[3];
		}
		foreach ($tableAttrs as $key => $value) {
			$table .= ($table ? ' ' : '') . $key . '="' . htmlspecialchars($value) . '"';
		}
		$out = '<table ' . $table . $style . '>' . $section . '</table>';
		return $out;
	}

	/**
	 * Checks if the $table is the child of a inline type AND the $field is the label field of this table.
	 * This function is used to dynamically update the label while editing. This has no effect on labels,
	 * that were processed by a TCEmain-hook on saving.
	 *
	 * @param string $table The table to check
	 * @param string $field The field on this table to check
	 * @return boolean Is inline child and field is responsible for the label
	 * @todo Define visibility
	 */
	public function isInlineChildAndLabelField($table, $field) {
		$level = $this->getStructureLevel(-1);
		if ($level['config']['foreign_label']) {
			$label = $level['config']['foreign_label'];
		} else {
			$label = $GLOBALS['TCA'][$table]['ctrl']['label'];
		}
		return $level['config']['foreign_table'] === $table && $label == $field ? TRUE : FALSE;
	}

	/**
	 * Get the depth of the stable structure stack.
	 * (count($this->inlineStructure['stable'])
	 *
	 * @return integer The depth of the structure stack
	 * @todo Define visibility
	 */
	public function getStructureDepth() {
		return count($this->inlineStructure['stable']);
	}

	/**
	 * Handles complex comparison requests on an array.
	 * A request could look like the following:
	 *
	 * $searchArray = array(
	 * '%AND'	=> array(
	 * 'key1'	=> 'value1',
	 * 'key2'	=> 'value2',
	 * '%OR'	=> array(
	 * 'subarray' => array(
	 * 'subkey' => 'subvalue'
	 * ),
	 * 'key3'	=> 'value3',
	 * 'key4'	=> 'value4'
	 * )
	 * )
	 * );
	 *
	 * It is possible to use the array keys '%AND.1', '%AND.2', etc. to prevent
	 * overwriting the sub-array. It could be neccessary, if you use complex comparisons.
	 *
	 * The example above means, key1 *AND* key2 (and their values) have to match with
	 * the $subjectArray and additional one *OR* key3 or key4 have to meet the same
	 * condition.
	 * It is also possible to compare parts of a sub-array (e.g. "subarray"), so this
	 * function recurses down one level in that sub-array.
	 *
	 * @param array $subjectArray The array to search in
	 * @param array $searchArray The array with keys and values to search for
	 * @param string $type Use '%AND' or '%OR' for comparision
	 * @return boolean The result of the comparison
	 * @todo Define visibility
	 */
	public function arrayCompareComplex($subjectArray, $searchArray, $type = '') {
		$localMatches = 0;
		$localEntries = 0;
		if (is_array($searchArray) && count($searchArray)) {
			// If no type was passed, try to determine
			if (!$type) {
				reset($searchArray);
				$type = key($searchArray);
				$searchArray = current($searchArray);
			}
			// We use '%AND' and '%OR' in uppercase
			$type = strtoupper($type);
			// Split regular elements from sub elements
			foreach ($searchArray as $key => $value) {
				$localEntries++;
				// Process a sub-group of OR-conditions
				if ($key == '%OR') {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, '%OR') ? 1 : 0;
				} elseif ($key == '%AND') {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, '%AND') ? 1 : 0;
				} elseif (is_array($value) && $this->isAssociativeArray($searchArray)) {
					$localMatches += $this->arrayCompareComplex($subjectArray[$key], $value, $type) ? 1 : 0;
				} elseif (is_array($value)) {
					$localMatches += $this->arrayCompareComplex($subjectArray, $value, $type) ? 1 : 0;
				} else {
					if (isset($subjectArray[$key]) && isset($value)) {
						// Boolean match:
						if (is_bool($value)) {
							$localMatches += !($subjectArray[$key] xor $value) ? 1 : 0;
						} elseif (is_numeric($subjectArray[$key]) && is_numeric($value)) {
							$localMatches += $subjectArray[$key] == $value ? 1 : 0;
						} else {
							$localMatches += $subjectArray[$key] === $value ? 1 : 0;
						}
					}
				}
				// If one or more matches are required ('OR'), return TRUE after the first successful match
				if ($type == '%OR' && $localMatches > 0) {
					return TRUE;
				}
				// If all matches are required ('AND') and we have no result after the first run, return FALSE
				if ($type == '%AND' && $localMatches == 0) {
					return FALSE;
				}
			}
		}
		// Return the result for '%AND' (if nothing was checked, TRUE is returned)
		return $localEntries == $localMatches ? TRUE : FALSE;
	}

	/**
	 * Checks whether an object is an associative array.
	 *
	 * @param mixed $object The object to be checked
	 * @return boolean Returns TRUE, if the object is an associative array
	 * @todo Define visibility
	 */
	public function isAssociativeArray($object) {
		return is_array($object) && count($object) && array_keys($object) !== range(0, sizeof($object) - 1) ? TRUE : FALSE;
	}

	/**
	 * Remove an element from an array.
	 *
	 * @param mixed $needle The element to be removed.
	 * @param array $haystack The array the element should be removed from.
	 * @param mixed $strict Search elements strictly.
	 * @return array The array $haystack without the $needle
	 * @todo Define visibility
	 */
	public function removeFromArray($needle, $haystack, $strict = NULL) {
		$pos = array_search($needle, $haystack, $strict);
		if ($pos !== FALSE) {
			unset($haystack[$pos]);
		}
		return $haystack;
	}

	/**
	 * Makes a flat array from the $possibleRecords array.
	 * The key of the flat array is the value of the record,
	 * the value of the flat array is the label of the record.
	 *
	 * @param array $possibleRecords The possibleRecords array (for select fields)
	 * @return mixed A flat array with key=uid, value=label; if $possibleRecords isn't an array, FALSE is returned.
	 * @todo Define visibility
	 */
	public function getPossibleRecordsFlat($possibleRecords) {
		$flat = FALSE;
		if (is_array($possibleRecords)) {
			$flat = array();
			foreach ($possibleRecords as $record) {
				$flat[$record[1]] = $record[0];
			}
		}
		return $flat;
	}

	/**
	 * Determine the configuration and the type of a record selector.
	 *
	 * @param array $conf TCA configuration of the parent(!) field
	 * @return array Associative array with the keys 'PA' and 'type', both are FALSE if the selector was not valid.
	 * @todo Define visibility
	 */
	public function getPossibleRecordsSelectorConfig($conf, $field = '') {
		$foreign_table = $conf['foreign_table'];
		$foreign_selector = $conf['foreign_selector'];
		$PA = FALSE;
		$type = FALSE;
		$table = FALSE;
		$selector = FALSE;
		if ($field) {
			$PA = array();
			$PA['fieldConf'] = $GLOBALS['TCA'][$foreign_table]['columns'][$field];
			if ($PA['fieldConf'] && $conf['foreign_selector_fieldTcaOverride']) {
				$PA['fieldConf'] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($PA['fieldConf'], $conf['foreign_selector_fieldTcaOverride']);
			}
			$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];
			// Using "form_type" locally in this script
			$PA['fieldTSConfig'] = $this->fObj->setTSconfig($foreign_table, array(), $field);
			$config = $PA['fieldConf']['config'];
			// Determine type of Selector:
			$type = $this->getPossibleRecordsSelectorType($config);
			// Return table on this level:
			$table = $type == 'select' ? $config['foreign_table'] : $config['allowed'];
			// Return type of the selector if foreign_selector is defined and points to the same field as in $field:
			if ($foreign_selector && $foreign_selector == $field && $type) {
				$selector = $type;
			}
		}
		return array(
			'PA' => $PA,
			'type' => $type,
			'table' => $table,
			'selector' => $selector
		);
	}

	/**
	 * Determine the type of a record selector, e.g. select or group/db.
	 *
	 * @param array $config TCE configuration of the selector
	 * @return mixed The type of the selector, 'select' or 'groupdb' - FALSE not valid
	 * @todo Define visibility
	 */
	public function getPossibleRecordsSelectorType($config) {
		$type = FALSE;
		if ($config['type'] == 'select') {
			$type = 'select';
		} elseif ($config['type'] == 'group' && $config['internal_type'] == 'db') {
			$type = 'groupdb';
		}
		return $type;
	}

	/**
	 * Check, if a field should be skipped, that was defined to be handled as foreign_field or foreign_sortby of
	 * the parent record of the "inline"-type - if so, we have to skip this field - the rendering is done via "inline" as hidden field
	 *
	 * @param string $table The table name
	 * @param string $field The field name
	 * @param array $row The record row from the database
	 * @param array $config TCA configuration of the field
	 * @return boolean Determines whether the field should be skipped.
	 * @todo Define visibility
	 */
	public function skipField($table, $field, $row, $config) {
		$skipThisField = FALSE;
		if ($this->getStructureDepth()) {
			$searchArray = array(
				'%OR' => array(
					'config' => array(
						0 => array(
							'%AND' => array(
								'foreign_table' => $table,
								'%OR' => array(
									'%AND' => array(
										'appearance' => array('useCombination' => TRUE),
										'foreign_selector' => $field
									),
									'MM' => $config['MM']
								)
							)
						),
						1 => array(
							'%AND' => array(
								'foreign_table' => $config['foreign_table'],
								'foreign_selector' => $config['foreign_field']
							)
						)
					)
				)
			);
			// Get the parent record from structure stack
			$level = $this->getStructureLevel(-1);
			// If we have symmetric fields, check on which side we are and hide fields, that are set automatically:
			if (\TYPO3\CMS\Core\Database\RelationHandler::isOnSymmetricSide($level['uid'], $level['config'], $row)) {
				$searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_field'] = $field;
				$searchArray['%OR']['config'][0]['%AND']['%OR']['symmetric_sortby'] = $field;
			} else {
				$searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_field'] = $field;
				$searchArray['%OR']['config'][0]['%AND']['%OR']['foreign_sortby'] = $field;
			}
			$skipThisField = $this->compareStructureConfiguration($searchArray, TRUE);
		}
		return $skipThisField;
	}

	/**
	 * Checks if a uid of a child table is in the inline view settings.
	 *
	 * @param string $table Name of the child table
	 * @param integer $uid uid of the the child record
	 * @return boolean TRUE=expand, FALSE=collapse
	 * @todo Define visibility
	 */
	public function getExpandedCollapsedState($table, $uid) {
		if (isset($this->inlineView[$table]) && is_array($this->inlineView[$table])) {
			if (in_array($uid, $this->inlineView[$table]) !== FALSE) {
				return TRUE;
			}
		}
		return FALSE;
	}

	/**
	 * Update expanded/collapsed states on new inline records if any.
	 *
	 * @param array $uc The uc array to be processed and saved (by reference)
	 * @param \TYPO3\CMS\Core\DataHandling\DataHandler $tce Instance of TCEmain that saved data before
	 * @return void
	 * @todo Define visibility
	 */
	public function updateInlineView(&$uc, $tce) {
		if (isset($uc['inlineView']) && is_array($uc['inlineView'])) {
			$inlineView = (array) unserialize($GLOBALS['BE_USER']->uc['inlineView']);
			foreach ($uc['inlineView'] as $topTable => $topRecords) {
				foreach ($topRecords as $topUid => $childElements) {
					foreach ($childElements as $childTable => $childRecords) {
						$uids = array_keys($tce->substNEWwithIDs_table, $childTable);
						if (count($uids)) {
							$newExpandedChildren = array();
							foreach ($childRecords as $childUid => $state) {
								if ($state && in_array($childUid, $uids)) {
									$newChildUid = $tce->substNEWwithIDs[$childUid];
									$newExpandedChildren[] = $newChildUid;
								}
							}
							// Add new expanded child records to UC (if any):
							if (count($newExpandedChildren)) {
								$inlineViewCurrent = &$inlineView[$topTable][$topUid][$childTable];
								if (is_array($inlineViewCurrent)) {
									$inlineViewCurrent = array_unique(array_merge($inlineViewCurrent, $newExpandedChildren));
								} else {
									$inlineViewCurrent = $newExpandedChildren;
								}
							}
						}
					}
				}
			}
			$GLOBALS['BE_USER']->uc['inlineView'] = serialize($inlineView);
			$GLOBALS['BE_USER']->writeUC();
		}
	}

	/**
	 * Returns the the margin in pixels, that is used for each new inline level.
	 *
	 * @return integer A pixel value for the margin of each new inline level.
	 * @todo Define visibility
	 */
	public function getLevelMargin() {
		$margin = ($this->inlineStyles['margin-right'] + 1) * 2;
		return $margin;
	}

	/**
	 * Parses the HTML tags that would have been inserted to the <head> of a HTML document and returns the found tags as multidimensional array.
	 *
	 * @return array The parsed tags with their attributes and innerHTML parts
	 */
	protected function getHeadTags() {
		$headTags = array();
		$headDataRaw = $this->fObj->JStop() . $this->getJavaScriptAndStyleSheetsOfPageRenderer();
		if ($headDataRaw) {
			// Create instance of the HTML parser:
			$parseObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Html\\HtmlParser');
			// Removes script wraps:
			$headDataRaw = str_replace(array('/*<![CDATA[*/', '/*]]>*/'), '', $headDataRaw);
			// Removes leading spaces of a multiline string:
			$headDataRaw = trim(preg_replace('/(^|\\r|\\n)( |\\t)+/', '$1', $headDataRaw));
			// Get script and link tags:
			$tags = array_merge($parseObj->getAllParts($parseObj->splitTags('link', $headDataRaw)), $parseObj->getAllParts($parseObj->splitIntoBlock('script', $headDataRaw)));
			foreach ($tags as $tagData) {
				$tagAttributes = $parseObj->get_tag_attributes($parseObj->getFirstTag($tagData), TRUE);
				$headTags[] = array(
					'name' => $parseObj->getFirstTagName($tagData),
					'attributes' => $tagAttributes[0],
					'innerHTML' => $parseObj->removeFirstAndLastTag($tagData)
				);
			}
		}
		return $headTags;
	}

	/**
	 * Gets the JavaScript of the pageRenderer.
	 * This can be used to extract newly added files which have been added
	 * during an AJAX request. Due to the spread possibilities of the pageRenderer
	 * to add JavaScript rendering and extracting seems to be the easiest way.
	 *
	 * @return string
	 */
	protected function getJavaScriptAndStyleSheetsOfPageRenderer() {
		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = clone $GLOBALS['SOBE']->doc->getPageRenderer();
		$pageRenderer->setCharSet($GLOBALS['LANG']->charSet);
		$pageRenderer->setTemplateFile(TYPO3_mainDir . 'templates/helper_javascript_css.html');
		$javaScriptAndStyleSheets = $pageRenderer->render();
		return $javaScriptAndStyleSheets;
	}

	/**
	 * Wraps a text with an anchor and returns the HTML representation.
	 *
	 * @param string $text The text to be wrapped by an anchor
	 * @param string $link  The link to be used in the anchor
	 * @param array $attributes Array of attributes to be used in the anchor
	 * @return string The wrapped texted as HTML representation
	 */
	protected function wrapWithAnchor($text, $link, $attributes = array()) {
		$link = trim($link);
		$result = '<a href="' . ($link ? $link : '#') . '"';
		foreach ($attributes as $key => $value) {
			$result .= ' ' . $key . '="' . htmlspecialchars(trim($value)) . '"';
		}
		$result .= '>' . $text . '</a>';
		return $result;
	}

	/**
	 * Extracts FlexForm parts of a form element name like
	 * data[table][uid][field][sDEF][lDEF][FlexForm][vDEF]
	 *
	 * @param string $formElementName The form element name
	 * @return array|NULL
	 */
	protected function extractFlexFormParts($formElementName) {
		$flexFormParts = NULL;

		$matches = array();
		$prefix = preg_quote($this->fObj->prependFormFieldNames, '#');

		if (preg_match('#^' . $prefix . '(?:\[[^]]+\]){3}(\[data\](?:\[[^]]+\]){4,})$#', $formElementName, $matches)) {
			$flexFormParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(
				'][',
				trim($matches[1], '[]')
			);
		}

		return $flexFormParts;
	}

}


?>