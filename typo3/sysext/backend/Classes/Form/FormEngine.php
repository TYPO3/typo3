<?php
namespace TYPO3\CMS\Backend\Form;

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

use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Template\DocumentTemplate;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\FormProtection\FormProtectionFactory;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Lang\LanguageService;

/**
 * This is form engine - Class for creating the backend editing forms.
 */
class FormEngine {

	/**
	 * @var bool
	 */
	public $disableWizards = FALSE;

	/**
	 * List of additional preview languages that should be shown to the user. Initialized early.
	 *
	 * array(
	 *   $languageUid => array(
	 *     'uid' => $languageUid,
	 *     'ISOcode' => $isoCodeOfLanguage
	 *   )
	 * )
	 *
	 * @var array
	 */
	protected $additionalPreviewLanguages = array();

	/**
	 * @var string
	 */
	protected $extJSCODE = '';

	/**
	 * @var array HTML of additional hidden fields rendered by sub containers
	 */
	protected $hiddenFieldAccum = array();

	/**
	 * @var string
	 */
	public $TBE_EDITOR_fieldChanged_func = '';

	/**
	 * @var bool
	 */
	public $loadMD5_JS = TRUE;

	/**
	 * Alternative return URL path (default is \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript())
	 *
	 * @var string
	 */
	public $returnUrl = '';

	/**
	 * Can be set to point to a field name in the form which will be set to '1' when the form
	 * is submitted with a *save* button. This way the recipient script can determine that
	 * the form was submitted for save and not "close" for example.
	 *
	 * @var string
	 */
	public $doSaveFieldName = '';

	/**
	 * If this evaluates to TRUE, the forms are rendering only localization relevant fields of the records.
	 *
	 * @var string
	 */
	public $localizationMode = '';

	/**
	 * When enabled all elements are rendered non-editable
	 *
	 * @var bool
	 */
	protected $renderReadonly = FALSE;

	/**
	 * @var InlineStackProcessor
	 */
	protected $inlineStackProcessor;

	/**
	 * @var array Data array from IRRE pushed to frontend as json array
	 */
	protected $inlineData = array();

	/**
	 * Set by readPerms()  (caching)
	 *
	 * @var string
	 */
	public $perms_clause = '';

	/**
	 * Set by readPerms()  (caching-flag)
	 *
	 * @var bool
	 */
	public $perms_clause_set = FALSE;

	/**
	 * Total wrapping for the table rows
	 *
	 * @var string
	 * @todo: This is overwritten in __construct
	 */
	public $totalWrap = '<hr />|<hr />';

	/**
	 * This array of fields will be set as hidden-fields instead of rendered normally!
	 * This is used by EditDocumentController to force some field values if set as "overrideVals" in _GP
	 *
	 * @var array
	 */
	public $hiddenFieldListArr = array();

	// Internal, registers for user defined functions etc.
	/**
	 * Additional HTML code, printed before the form
	 *
	 * @var array
	 */
	public $additionalCode_pre = array();

	/**
	 * Additional JavaScript printed after the form
	 *
	 * @var array
	 */
	protected $additionalJS_post = array();

	/**
	 * Additional JavaScript executed on submit; If you set "OK" variable it will raise an error
	 * about RTEs not being loaded and offer to block further submission.
	 *
	 * @var array
	 */
	public $additionalJS_submit = array();

	/**
	 * Array containing hook class instances called once for a form
	 *
	 * @var array
	 */
	public $hookObjectsMainFields = array();

	/**
	 * Rows getting inserted into the headers (when called from the EditDocumentController)
	 *
	 * @var array
	 */
	public $extraFormHeaders = array();

	/**
	 * Form template, relative to typo3 directory
	 *
	 * @var string
	 */
	public $templateFile = '';

	/**
	 * @var string The table that is handled
	 */
	protected $table = '';

	/**
	 * @var array Database row data
	 */
	protected $databaseRow = array();

	/**
	 * @var NodeFactory Factory taking care of creating appropriate sub container and elements
	 */
	protected $nodeFactory;

	/**
	 * Array with requireJS modules, use module name as key, the value could be callback code.
	 * Use NULL as value if no callback is used.
	 *
	 * @var array
	 */
	protected $requireJsModules = array();

	/**
	 * @var PageRenderer
	 */
	protected $pageRenderer = NULL;

	/**
	 * Constructor function, setting internal variables, loading the styles used.
	 *
	 */
	public function __construct() {
		$this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$this->initializeAdditionalPreviewLanguages();
		// Prepare user defined objects (if any) for hooks which extend this function:
		$this->hookObjectsMainFields = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef) {
				$this->hookObjectsMainFields[] = GeneralUtility::getUserObj($classRef);
			}
		}
		$this->templateFile = 'sysext/backend/Resources/Private/Templates/FormEngine.html';
		$template = GeneralUtility::getUrl(PATH_typo3 . $this->templateFile);
		// Wrapping all table rows for a particular record being edited:
		$this->totalWrap = HtmlParser::getSubpart($template, '###TOTALWRAP###');
		$this->nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
	}

	/**
	 * Set render read only flag
	 *
	 * @param bool $value
	 */
	public function setRenderReadonly($value) {
		$this->renderReadonly = (bool)$value;
	}

	/*******************************************************
	 *
	 * Rendering the forms, fields etc
	 *
	 *******************************************************/

	/**
	 * Based on the $table and $row of content, this displays the complete TCEform for the record.
	 * The input-$row is required to be preprocessed if necessary by eg.
	 * the \TYPO3\CMS\Backend\Form\DataPreprocessor class. For instance the RTE content
	 * should be transformed through this class first.
	 *
	 * @param string $table The table name
	 * @param array $databaseRow The record from the table for which to render a field.
	 * @return string HTML output
	 */
	public function getMainFields($table, array $databaseRow) {
		$this->table = $table;
		$this->databaseRow = $databaseRow;

		// Hook: getMainFields_preProcess
		foreach ($this->hookObjectsMainFields as $hookObj) {
			if (method_exists($hookObj, 'getMainFields_preProcess')) {
				$hookObj->getMainFields_preProcess($table, $databaseRow, $this);
			}
		}

		$options = $this->getConfigurationOptionsForChildElements();
		$options['renderType'] = 'fullRecordContainer';
		$resultArray = $this->nodeFactory->create($options)->render();

		$content = $resultArray['html'];
		$this->mergeResult($resultArray);

		// Hook: getMainFields_postProcess
		foreach ($this->hookObjectsMainFields as $hookObj) {
			if (method_exists($hookObj, 'getMainFields_postProcess')) {
				$hookObj->getMainFields_postProcess($table, $databaseRow, $this);
			}
		}

		return $content;
	}

	/**
	 * Will return the TCEform element for just a single field from a record.
	 * The field must be listed in the currently displayed fields (as found in [types][showitem]) for the record.
	 * This also means that the $table/$row supplied must be complete so the list of fields to show can be found correctly
	 * This method is used by "full screen RTE". Difference to getListedFields() is basically that no wrapper html is rendered around the element.
	 *
	 * @param string $table The table name
	 * @param array $databaseRow The record from the table for which to render a field.
	 * @param string $theFieldToReturn The field name to return the TCEform element for.
	 * @return string HTML output
	 */
	public function getSoloField($table, $databaseRow, $theFieldToReturn) {
		$this->table = $table;
		$this->databaseRow = $databaseRow;

		$options = $this->getConfigurationOptionsForChildElements();
		$options['singleFieldToRender'] = $theFieldToReturn;
		$options['renderType'] = 'soloFieldContainer';
		$resultArray = $this->nodeFactory->create($options)->render();
		$html = $resultArray['html'];

		$this->additionalJS_post = $resultArray['additionalJavaScriptPost'];
		$this->additionalJS_submit = $resultArray['additionalJavaScriptSubmit'];
		$this->extJSCODE = $resultArray['extJSCODE'];
		$this->inlineData = $resultArray['inlineData'];
		$this->hiddenFieldAccum = $resultArray['additionalHiddenFields'];
		$this->additionalCode_pre = $resultArray['additionalHeadTags'];

		return $html;
	}

	/**
	 * Will return the TCEform elements for a pre-defined list of fields.
	 * Notice that this will STILL use the configuration found in the list [types][showitem] for those fields which are found there.
	 * So ideally the list of fields given as argument to this function should also be in the current [types][showitem] list of the record.
	 * Used for displaying forms for the frontend edit icons for instance.
	 *
	 * @todo: The list module calls this method multiple times on the same class instance if single fields
	 * @todo: of multiple records are edited. This is why the properties are accumulated here.
	 *
	 * @param string $table The table name
	 * @param array $databaseRow The record array.
	 * @param string $list Commalist of fields from the table. These will be shown in the specified order in a form.
	 * @return string TCEform elements in a string.
	 */
	public function getListedFields($table, $databaseRow, $list) {
		$this->table = $table;
		$this->databaseRow = $databaseRow;

		$options = $this->getConfigurationOptionsForChildElements();
		$options['fieldListToRender'] = $list;
		$options['renderType'] = 'listOfFieldsContainer';
		$resultArray = $this->nodeFactory->create($options)->render();
		$html = $resultArray['html'];
		$this->mergeResult($resultArray);

		return $html;
	}


	/**
	 * Merge existing data with the given result array
	 *
	 * @param array $resultArray Array returned by child
	 * @return void
	 */
	protected function mergeResult(array $resultArray) {
		foreach ($resultArray['additionalJavaScriptPost'] as $element) {
			$this->additionalJS_post[] = $element;
		}
		foreach ($resultArray['additionalJavaScriptSubmit'] as $element) {
			$this->additionalJS_submit[] = $element;
		}
		if (!empty($resultArray['requireJsModules'])) {
			foreach ($resultArray['requireJsModules'] as $module) {
				$moduleName = NULL;
				$callback = NULL;
				if (is_string($module)) {
					// if $module is a string, no callback
					$moduleName = $module;
					$callback = NULL;
				} elseif (is_array($module)) {
					// if $module is an array, callback is possible
					foreach ($module as $key => $value) {
						$moduleName = $key;
						$callback = $value;
						break;
					}
				}
				if ($moduleName !== NULL) {
					if (!empty($this->requireJsModules[$moduleName]) && $callback !== NULL) {
						$existingValue = $this->requireJsModules[$moduleName];
						if (!is_array($existingValue)) {
							$existingValue = array($existingValue);
						}
						$existingValue[] = $callback;
						$this->requireJsModules[$moduleName] = $existingValue;
					} else {
						$this->requireJsModules[$moduleName] = $callback;
					}
				}
			}
		}
		$this->extJSCODE = $this->extJSCODE . LF . $resultArray['extJSCODE'];
		$this->inlineData = $resultArray['inlineData'];
		foreach ($resultArray['additionalHiddenFields'] as $element) {
			$this->hiddenFieldAccum[] = $element;
		}
		foreach ($resultArray['additionalHeadTags'] as $element) {
			$this->additionalCode_pre[] = $element;
		}

		if (!empty($resultArray['inlineData'])) {
			$resultArrayInlineData = $this->inlineData;
			$resultInlineData = $resultArray['inlineData'];
			ArrayUtility::mergeRecursiveWithOverrule($resultArrayInlineData, $resultInlineData);
			$this->inlineData = $resultArrayInlineData;
		}
	}

	/**
	 * Returns an array of global form settings to be given to child elements.
	 *
	 * @return array
	 */
	protected function getConfigurationOptionsForChildElements() {
		return array(
			'renderReadonly' => $this->renderReadonly,
			'disabledWizards' => $this->disableWizards,
			'returnUrl' => $this->thisReturnUrl(),
			'table' => $this->table,
			'databaseRow' => $this->databaseRow,
			'recordTypeValue' => '',
			'additionalPreviewLanguages' => $this->additionalPreviewLanguages,
			'localizationMode' => $this->localizationMode, // @todo: find out the details, Warning, this overlaps with inline behaviour localizationMode
			'elementBaseName' => '',
			'tabAndInlineStack' => array(),
			'inlineFirstPid' => $this->getInlineFirstPid(),
			'inlineExpandCollapseStateArray' => $this->getInlineExpandCollapseStateArrayForTableUid($this->table, $this->databaseRow['uid']),
			'inlineData' => $this->inlineData,
			'inlineStructure' => $this->inlineStackProcessor->getStructure(),
			'overruleTypesArray' => array(),
			'hiddenFieldListArray' => $this->hiddenFieldListArr,
			'flexFormFieldIdentifierPrefix' => 'ID',
			'nodeFactory' => $this->nodeFactory,
		);
	}

	/**
	 * General processor for AJAX requests concerning IRRE.
	 *
	 * @param array $_ Additional parameters (not used here)
	 * @param AjaxRequestHandler $ajaxObj The AjaxRequestHandler object of this request
	 * @throws \RuntimeException
	 * @return void
	 */
	public function processInlineAjaxRequest($_, AjaxRequestHandler $ajaxObj) {
		$ajaxArguments = GeneralUtility::_GP('ajax');
		$ajaxIdParts = explode('::', $GLOBALS['ajaxID'], 2);
		if (isset($ajaxArguments) && is_array($ajaxArguments) && !empty($ajaxArguments)) {
			$ajaxMethod = $ajaxIdParts[1];
			$ajaxObj->setContentFormat('jsonbody');
			// Construct runtime environment for Inline Relational Record Editing
			$this->setUpRuntimeEnvironmentForAjaxRequests();
			// @todo: ajaxArguments[2] is "returnUrl" in the first 3 calls - still needed?
			switch ($ajaxMethod) {
				case 'synchronizeLocalizeRecords':
					$domObjectId = $ajaxArguments[0];
					$type = $ajaxArguments[1];
					// Parse the DOM identifier (string), add the levels to the structure stack (array), load the TCA config:
					$this->inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
					$this->inlineStackProcessor->injectAjaxConfiguration($ajaxArguments['context']);
					$inlineFirstPid = FormEngineUtility::getInlineFirstPidFromDomObjectId($domObjectId);
					$ajaxObj->setContent($this->renderInlineSynchronizeLocalizeRecords($type, $inlineFirstPid));
					break;
				case 'createNewRecord':
					$domObjectId = $ajaxArguments[0];
					$createAfterUid = 0;
					if (isset($ajaxArguments[1])) {
						$createAfterUid = $ajaxArguments[1];
					}
					// Parse the DOM identifier (string), add the levels to the structure stack (array), load the TCA config:
					$this->inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
					$this->inlineStackProcessor->injectAjaxConfiguration($ajaxArguments['context']);
					$ajaxObj->setContent($this->renderInlineNewChildRecord($domObjectId, $createAfterUid));
					break;
				case 'getRecordDetails':
					$domObjectId = $ajaxArguments[0];
					// Parse the DOM identifier (string), add the levels to the structure stack (array), load the TCA config:
					$this->inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId);
					$this->inlineStackProcessor->injectAjaxConfiguration($ajaxArguments['context']);
					$ajaxObj->setContent($this->renderInlineChildRecord($domObjectId));
					break;
				case 'setExpandedCollapsedState':
					$domObjectId = $ajaxArguments[0];
					// Parse the DOM identifier (string), add the levels to the structure stack (array), don't load TCA config
					$this->inlineStackProcessor->initializeByParsingDomObjectIdString($domObjectId, FALSE);
					$expand = $ajaxArguments[1];
					$collapse = $ajaxArguments[2];
					$this->setInlineExpandedCollapsedState($expand, $collapse);
					break;
				default:
					throw new \RuntimeException('Not a valid ajax identifier', 1428227862);
			}
		}
	}

	/**
	 * Handle AJAX calls to dynamically load the form fields of a given inline record.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @return array An array to be used for JSON
	 */
	protected function renderInlineChildRecord($domObjectId) {
		// The current table - for this table we should add/import records
		$current = $this->inlineStackProcessor->getUnstableStructure();
		// The parent table - this table embeds the current table
		$parent = $this->inlineStackProcessor->getStructureLevel(-1);
		$config = $parent['config'];

		if (empty($config['foreign_table']) || !is_array($GLOBALS['TCA'][$config['foreign_table']])) {
			return $this->getErrorMessageForAJAX('Wrong configuration in table ' . $parent['table']);
		}

		$config = FormEngineUtility::mergeInlineConfiguration($config);

		// Set flag in config so that only the fields are rendered
		$config['renderFieldsOnly'] = TRUE;
		$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
		$expandSingle = isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle'];

		$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);
		$record = $inlineRelatedRecordResolver->getRecord($current['table'], $current['uid']);

		$inlineFirstPid = FormEngineUtility::getInlineFirstPidFromDomObjectId($domObjectId);
		// The HTML-object-id's prefix of the dynamically created record
		$objectPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid) . '-' . $current['table'];
		$objectId = $objectPrefix . '-' . $record['uid'];

		$options = $this->getConfigurationOptionsForChildElements();
		$options['databaseRow'] = array('uid' => $parent['uid']);
		$options['inlineFirstPid'] = $inlineFirstPid;
		$options['inlineRelatedRecordToRender'] = $record;
		$options['inlineRelatedRecordConfig'] = $config;
		$options['inlineStructure'] = $this->inlineStackProcessor->getStructure();

		$options['renderType'] = 'inlineRecordContainer';
		$childArray = $this->nodeFactory->create($options)->render();

		if ($childArray === FALSE) {
			return $this->getErrorMessageForAJAX('Access denied');
		}

		$this->mergeResult($childArray);

		$jsonArray = array(
			'data' => $childArray['html'],
			'scriptCall' => array(),
		);
		$jsonArray['scriptCall'][] = 'inline.domAddRecordDetails(' . GeneralUtility::quoteJSvalue($domObjectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . ($expandSingle ? '1' : '0') . ',json.data);';
		if ($config['foreign_unique']) {
			$jsonArray['scriptCall'][] = 'inline.removeUsed(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ');';
		}

		$jsonArray = $this->getInlineAjaxCommonScriptCalls($jsonArray, $config, $inlineFirstPid);

		// Collapse all other records if requested:
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ');';
		}

		return $jsonArray;
	}

	/**
	 * Handle AJAX calls to show a new inline-record of the given table.
	 *
	 * @param string $domObjectId The calling object in hierarchy, that requested a new record.
	 * @param string|int $foreignUid If set, the new record should be inserted after that one.
	 * @return array An array to be used for JSON
	 */
	protected function renderInlineNewChildRecord($domObjectId, $foreignUid) {
		// The current table - for this table we should add/import records
		$current = $this->inlineStackProcessor->getUnstableStructure();
		// The parent table - this table embeds the current table
		$parent = $this->inlineStackProcessor->getStructureLevel(-1);
		$config = $parent['config'];

		if (empty($config['foreign_table']) || !is_array($GLOBALS['TCA'][$config['foreign_table']])) {
			return $this->getErrorMessageForAJAX('Wrong configuration in table ' . $parent['table']);
		}

		$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);

		$config = FormEngineUtility::mergeInlineConfiguration($config);

		$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
		$expandSingle = isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle'];

		$inlineFirstPid = FormEngineUtility::getInlineFirstPidFromDomObjectId($domObjectId);

		// Dynamically create a new record using \TYPO3\CMS\Backend\Form\DataPreprocessor
		if (!$foreignUid || !MathUtility::canBeInterpretedAsInteger($foreignUid) || $config['foreign_selector']) {
			$record = $inlineRelatedRecordResolver->getNewRecord($inlineFirstPid, $current['table']);
			// Set default values for new created records
			if (isset($config['foreign_record_defaults']) && is_array($config['foreign_record_defaults'])) {
				$foreignTableConfig = $GLOBALS['TCA'][$current['table']];
				// The following system relevant fields can't be set by foreign_record_defaults
				$notSettableFields = array(
					'uid', 'pid', 't3ver_oid', 't3ver_id', 't3ver_label', 't3ver_wsid', 't3ver_state', 't3ver_stage',
					't3ver_count', 't3ver_tstamp', 't3ver_move_id'
				);
				$configurationKeysForNotSettableFields = array(
					'crdate', 'cruser_id', 'delete', 'origUid', 'transOrigDiffSourceField', 'transOrigPointerField',
					'tstamp'
				);
				foreach ($configurationKeysForNotSettableFields as $configurationKey) {
					if (isset($foreignTableConfig['ctrl'][$configurationKey])) {
						$notSettableFields[] = $foreignTableConfig['ctrl'][$configurationKey];
					}
				}
				foreach ($config['foreign_record_defaults'] as $fieldName => $defaultValue) {
					if (isset($foreignTableConfig['columns'][$fieldName]) && !in_array($fieldName, $notSettableFields)) {
						$record[$fieldName] = $defaultValue;
					}
				}
			}
			// Set language of new child record to the language of the parent record:
			if ($parent['localizationMode'] === 'select') {
				$parentRecord = $inlineRelatedRecordResolver->getRecord($parent['table'], $parent['uid']);
				$parentLanguageField = $GLOBALS['TCA'][$parent['table']]['ctrl']['languageField'];
				$childLanguageField = $GLOBALS['TCA'][$current['table']]['ctrl']['languageField'];
				if ($parentRecord[$parentLanguageField] > 0) {
					$record[$childLanguageField] = $parentRecord[$parentLanguageField];
				}
			}
		} else {
			// @todo: Check this: Else also hits if $foreignUid = 0?
			$record = $inlineRelatedRecordResolver->getRecord($current['table'], $foreignUid);
		}
		// Now there is a foreign_selector, so there is a new record on the intermediate table, but
		// this intermediate table holds a field, which is responsible for the foreign_selector, so
		// we have to set this field to the uid we get - or if none, to a new uid
		if ($config['foreign_selector'] && $foreignUid) {
			$selConfig = FormEngineUtility::getInlinePossibleRecordsSelectorConfig($config, $config['foreign_selector']);
			// For a selector of type group/db, prepend the tablename (<tablename>_<uid>):
			$record[$config['foreign_selector']] = $selConfig['type'] != 'groupdb' ? '' : $selConfig['table'] . '_';
			$record[$config['foreign_selector']] .= $foreignUid;
			if ($selConfig['table'] === 'sys_file') {
				$fileRecord = $inlineRelatedRecordResolver->getRecord($selConfig['table'], $foreignUid);
				if ($fileRecord !== FALSE && !$this->checkInlineFileTypeAccessForField($selConfig, $fileRecord)) {
					return $this->getErrorMessageForAJAX('File extension ' . $fileRecord['extension'] . ' is not allowed here!');
				}
			}
		}
		// The HTML-object-id's prefix of the dynamically created record
		$objectName = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
		$objectPrefix = $objectName . '-' . $current['table'];
		$objectId = $objectPrefix . '-' . $record['uid'];

		$options = $this->getConfigurationOptionsForChildElements();
		$options['databaseRow'] = array('uid' => $parent['uid']);
		$options['inlineFirstPid'] = $inlineFirstPid;
		$options['inlineRelatedRecordToRender'] = $record;
		$options['inlineRelatedRecordConfig'] = $config;
		$options['inlineStructure'] = $this->inlineStackProcessor->getStructure();

		$options['renderType'] = 'inlineRecordContainer';
		$childArray = $this->nodeFactory->create($options)->render();

		if ($childArray === FALSE) {
			return $this->getErrorMessageForAJAX('Access denied');
		}

		$this->mergeResult($childArray);

		$jsonArray = array(
			'data' => $childArray['html'],
			'scriptCall' => array(),
		);

		if (!$current['uid']) {
			$jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'bottom\',' . GeneralUtility::quoteJSvalue($objectName . '_records') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
			$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ',null,' . GeneralUtility::quoteJSvalue($foreignUid) . ');';
		} else {
			$jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'after\',' . GeneralUtility::quoteJSvalue($domObjectId . '_div') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
			$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ',' . GeneralUtility::quoteJSvalue($current['uid']) . ',' . GeneralUtility::quoteJSvalue($foreignUid) . ');';
		}

		$jsonArray = $this->getInlineAjaxCommonScriptCalls($jsonArray, $config, $inlineFirstPid);

		// Collapse all other records if requested:
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ', ' . GeneralUtility::quoteJSvalue($objectPrefix) . ', ' . GeneralUtility::quoteJSvalue($record['uid']) . ');';
		}
		// Tell the browser to scroll to the newly created record

		$jsonArray['scriptCall'][] = 'Element.scrollTo(' . GeneralUtility::quoteJSvalue($objectId . '_div') . ');';
		// Fade out and fade in the new record in the browser view to catch the user's eye
		$jsonArray['scriptCall'][] = 'inline.fadeOutFadeIn(' . GeneralUtility::quoteJSvalue($objectId . '_div') . ');';

		return $jsonArray;
	}

	/**
	 * Handle AJAX calls to localize all records of a parent, localize a single record or to synchronize with the original language parent.
	 *
	 * @param string $type Defines the type 'localize' or 'synchronize' (string) or a single uid to be localized (int)
	 * @param int $inlineFirstPid Inline first pid
	 * @return array An array to be used for JSON
	 */
	protected function renderInlineSynchronizeLocalizeRecords($type, $inlineFirstPid) {
		$jsonArray = FALSE;
		if (GeneralUtility::inList('localize,synchronize', $type) || MathUtility::canBeInterpretedAsInteger($type)) {
			$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);
			// The parent level:
			$parent = $this->inlineStackProcessor->getStructureLevel(-1);
			$current = $this->inlineStackProcessor->getUnstableStructure();
			$parentRecord = $inlineRelatedRecordResolver->getRecord($parent['table'], $parent['uid']);

			$cmd = array();
			$cmd[$parent['table']][$parent['uid']]['inlineLocalizeSynchronize'] = $parent['field'] . ',' . $type;
			/** @var $tce \TYPO3\CMS\Core\DataHandling\DataHandler */
			$tce = GeneralUtility::makeInstance(\TYPO3\CMS\Core\DataHandling\DataHandler::class);
			$tce->stripslashes_values = FALSE;
			$tce->start(array(), $cmd);
			$tce->process_cmdmap();

			$oldItemList = $parentRecord[$parent['field']];
			$newItemList = $tce->registerDBList[$parent['table']][$parent['uid']][$parent['field']];

			$jsonArray = array(
				'scriptCall' => array(),
			);
			$nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
			$nameObjectForeignTable = $nameObject . '-' . $current['table'];
			// Get the name of the field pointing to the original record:
			$transOrigPointerField = $GLOBALS['TCA'][$current['table']]['ctrl']['transOrigPointerField'];
			// Get the name of the field used as foreign selector (if any):
			$foreignSelector = isset($parent['config']['foreign_selector']) && $parent['config']['foreign_selector'] ? $parent['config']['foreign_selector'] : FALSE;
			// Convert lists to array with uids of child records:
			$oldItems = FormEngineUtility::getInlineRelatedRecordsUidArray($oldItemList);
			$newItems = FormEngineUtility::getInlineRelatedRecordsUidArray($newItemList);
			// Determine the items that were localized or localized:
			$removedItems = array_diff($oldItems, $newItems);
			$localizedItems = array_diff($newItems, $oldItems);
			// Set the items that should be removed in the forms view:
			foreach ($removedItems as $item) {
				$jsonArray['scriptCall'][] = 'inline.deleteRecord(' . GeneralUtility::quoteJSvalue($nameObjectForeignTable . '-' . $item) . ', {forceDirectRemoval: true});';
			}
			// Set the items that should be added in the forms view:
			$html = '';
			$resultArray = NULL;
			// @todo: This should be another container ...
			foreach ($localizedItems as $item) {
				$row = $inlineRelatedRecordResolver->getRecord($current['table'], $item);
				$selectedValue = $foreignSelector ? GeneralUtility::quoteJSvalue($row[$foreignSelector]) : 'null';

				$options = $this->getConfigurationOptionsForChildElements();
				$options['databaseRow'] = array('uid' => $parent['uid']);
				$options['inlineFirstPid'] = $inlineFirstPid;
				$options['inlineRelatedRecordToRender'] = $row;
				$options['inlineRelatedRecordConfig'] = $parent['config'];
				$options['inlineStructure'] = $this->inlineStackProcessor->getStructure();

				$options['renderType'] = 'inlineRecordContainer';
				$childArray = $this->nodeFactory->create($options)->render();
				$html .= $childArray['html'];
				$childArray['html'] = '';

				// @todo: Obsolete if a container and copied from AbstractContainer for now
				if ($resultArray === NULL) {
					$resultArray = $childArray;
				} else {
					if (!empty($childArray['extJSCODE'])) {
						$resultArray['extJSCODE'] .= LF . $childArray['extJSCODE'];
					}
					foreach ($childArray['additionalJavaScriptPost'] as $value) {
						$resultArray['additionalJavaScriptPost'][] = $value;
					}
					foreach ($childArray['additionalJavaScriptSubmit'] as $value) {
						$resultArray['additionalJavaScriptSubmit'][] = $value;
					}
					if (!empty($childArray['inlineData'])) {
						$resultArrayInlineData = $resultArray['inlineData'];
						$childInlineData = $childArray['inlineData'];
						ArrayUtility::mergeRecursiveWithOverrule($resultArrayInlineData, $childInlineData);
						$resultArray['inlineData'] = $resultArrayInlineData;
					}
				}

				$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($nameObjectForeignTable) . ', ' . GeneralUtility::quoteJSvalue($item) . ', null, ' . $selectedValue . ');';
				// Remove possible virtual records in the form which showed that a child records could be localized:
				if (isset($row[$transOrigPointerField]) && $row[$transOrigPointerField]) {
					$jsonArray['scriptCall'][] = 'inline.fadeAndRemove(' . GeneralUtility::quoteJSvalue($nameObjectForeignTable . '-' . $row[$transOrigPointerField] . '_div') . ');';
				}
			}
			if (!empty($html)) {
				$jsonArray['data'] = $html;
				array_unshift($jsonArray['scriptCall'], 'inline.domAddNewRecord(\'bottom\', ' . GeneralUtility::quoteJSvalue($nameObject . '_records') . ', ' . GeneralUtility::quoteJSvalue($nameObjectForeignTable) . ', json.data);');
			}

			$this->mergeResult($resultArray);

			$jsonArray = $this->getInlineAjaxCommonScriptCalls($jsonArray, $parent['config'], $inlineFirstPid);
		}
		return $jsonArray;
	}

	/**
	 * Save the expanded/collapsed state of a child record in the BE_USER->uc.
	 *
	 * @param string $expand Whether this record is expanded.
	 * @param string $collapse Whether this record is collapsed.
	 * @return void
	 */
	protected function setInlineExpandedCollapsedState($expand, $collapse) {
		$backendUser = $this->getBackendUserAuthentication();
		// The current table - for this table we should add/import records
		$currentTable = $this->inlineStackProcessor->getUnstableStructure();
		$currentTable = $currentTable['table'];
		// The top parent table - this table embeds the current table
		$top = $this->inlineStackProcessor->getStructureLevel(0);
		$topTable = $top['table'];
		$topUid = $top['uid'];
		$inlineView = $this->getInlineExpandCollapseStateArray();
		// Only do some action if the top record and the current record were saved before
		if (MathUtility::canBeInterpretedAsInteger($topUid)) {
			$expandUids = GeneralUtility::trimExplode(',', $expand);
			$collapseUids = GeneralUtility::trimExplode(',', $collapse);
			// Set records to be expanded
			foreach ($expandUids as $uid) {
				$inlineView[$topTable][$topUid][$currentTable][] = $uid;
			}
			// Set records to be collapsed
			foreach ($collapseUids as $uid) {
				$inlineView[$topTable][$topUid][$currentTable] = $this->removeFromArray($uid, $inlineView[$topTable][$topUid][$currentTable]);
			}
			// Save states back to database
			if (is_array($inlineView[$topTable][$topUid][$currentTable])) {
				$inlineView[$topTable][$topUid][$currentTable] = array_unique($inlineView[$topTable][$topUid][$currentTable]);
				$backendUser->uc['inlineView'] = serialize($inlineView);
				$backendUser->writeUC();
			}
		}
	}

	/**
	 * Construct runtime environment for Inline Relational Record Editing.
	 * - creates an anonymous \TYPO3\CMS\Backend\Controller\EditDocumentController in $GLOBALS['SOBE']
	 * - sets $this to $GLOBALS['SOBE']->tceforms
	 *
	 * @return void
	 */
	protected function setUpRuntimeEnvironmentForAjaxRequests() {
		$this->getLanguageService()->includeLLFile('EXT:lang/locallang_alt_doc.xlf');
		// Create a new anonymous object:
		$GLOBALS['SOBE'] = new \stdClass();
		$GLOBALS['SOBE']->MOD_MENU = array();
		// Setting virtual document name
		$GLOBALS['SOBE']->MCONF['name'] = 'xMOD_alt_doc.php';
		// CLEANSE SETTINGS
		$GLOBALS['SOBE']->MOD_SETTINGS = array();
		// Create an instance of the document template object
		// @todo: resolve clash getDocumentTemplate() / getControllerDocumenttemplate()
		$GLOBALS['SOBE']->doc = GeneralUtility::makeInstance(DocumentTemplate::class);
		$GLOBALS['SOBE']->doc->backPath = $GLOBALS['BACK_PATH'];
		// Initialize FormEngine (rendering the forms)
		// @todo: check if this is still needed, simplify
		$GLOBALS['SOBE']->tceforms = $this;
	}

	/**
	 * Return expand / collapse state array for a given table / uid combination
	 *
	 * @param string $table Handled table
	 * @param int $uid Handled uid
	 * @return array
	 */
	protected function getInlineExpandCollapseStateArrayForTableUid($table, $uid) {
		$inlineView = $this->getInlineExpandCollapseStateArray();
		$result = array();
		if (MathUtility::canBeInterpretedAsInteger($uid)) {
			if (!empty($inlineView[$table][$uid])) {
				$result = $inlineView[$table][$uid];
			}
		}
		return $result;
	}

	/**
	 * Get expand / collapse state of inline items
	 *
	 * @return array
	 */
	protected function getInlineExpandCollapseStateArray() {
		$backendUser = $this->getBackendUserAuthentication();
		$inlineView = unserialize($backendUser->uc['inlineView']);
		if (!is_array($inlineView)) {
			$inlineView = array();
		}
		return $inlineView;
	}

	/**
	 * The "entry" pid for inline records. Nested inline records can potentially hang around on different
	 * pid's, but the entry pid is needed for AJAX calls, so that they would know where the action takes place on the page structure.
	 *
	 * @return integer
	 */
	protected function getInlineFirstPid() {
		$table = $this->table;
		$row = $this->databaseRow;
		// If the parent is a page, use the uid(!) of the (new?) page as pid for the child records:
		if ($table == 'pages') {
			$liveVersionId = BackendUtility::getLiveVersionIdOfRecord('pages', $row['uid']);
			$pid = is_null($liveVersionId) ? $row['uid'] : $liveVersionId;
		} elseif ($row['pid'] < 0) {
			$prevRec = BackendUtility::getRecord($table, abs($row['pid']));
			$pid = $prevRec['pid'];
		} else {
			$pid = $row['pid'];
		}
		return $pid;
	}

	/**
	 * Checks if a record selector may select a certain file type
	 *
	 * @param array $selectorConfiguration
	 * @param array $fileRecord
	 * @return bool
	 */
	protected function checkInlineFileTypeAccessForField(array $selectorConfiguration, array $fileRecord) {
		if (!empty($selectorConfiguration['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'])) {
			$allowedFileExtensions = GeneralUtility::trimExplode(
				',',
				$selectorConfiguration['PA']['fieldConf']['config']['appearance']['elementBrowserAllowed'],
				TRUE
			);
			if (!in_array(strtolower($fileRecord['extension']), $allowedFileExtensions, TRUE)) {
				return FALSE;
			}
		}
		return TRUE;
	}

	/**
	 * Remove an element from an array.
	 *
	 * @param mixed $needle The element to be removed.
	 * @param array $haystack The array the element should be removed from.
	 * @param mixed $strict Search elements strictly.
	 * @return array The array $haystack without the $needle
	 */
	protected function removeFromArray($needle, $haystack, $strict = NULL) {
		$pos = array_search($needle, $haystack, $strict);
		if ($pos !== FALSE) {
			unset($haystack[$pos]);
		}
		return $haystack;
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
	 * Determines and sets several script calls to a JSON array, that would have been executed if processed in non-AJAX mode.
	 *
	 * @param array &$jsonArray Reference of the array to be used for JSON
	 * @param array $config The configuration of the IRRE field of the parent record
	 * @param int $inlineFirstPid Inline first pid
	 * @return array Modified array
	 * @todo: Basically, this methods shouldn't be there at all ...
	 */
	protected function getInlineAjaxCommonScriptCalls($jsonArray, $config, $inlineFirstPid) {
		// Add data that would have been added at the top of a regular FormEngine call:
		if ($headTags = $this->getInlineHeadTags()) {
			$jsonArray['headData'] = $headTags;
		}
		// Add the JavaScript data that would have been added at the bottom of a regular FormEngine call:
		$jsonArray['scriptCall'][] = $this->JSbottom('editform', TRUE);
		// If script.aculo.us Sortable is used, update the Observer to know the record:
		if ($config['appearance']['useSortable']) {
			$inlineObjectName = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
			$jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
		}
		// If FormEngine has some JavaScript code to be executed, just do it
		// @todo: this is done by JSBottom() already?!
		if ($this->extJSCODE) {
			$jsonArray['scriptCall'][] = $this->extJSCODE;
		}

		// require js handling
		foreach ($this->requireJsModules as $moduleName => $callbacks) {
			if (!is_array($callbacks)) {
				$callbacks = array($callbacks);
			}
			foreach ($callbacks as $callback) {
				$inlineCodeKey = $moduleName;
				$javaScriptCode = 'require(["' . $moduleName . '"]';
				if ($callback !== NULL) {
					$inlineCodeKey .= sha1($callback);
					$javaScriptCode .= ', ' . $callback;
				}
				$javaScriptCode .= ');';
				$jsonArray['scriptCall'][] = '/*RequireJS-Module-' . $inlineCodeKey . '*/' . LF . $javaScriptCode;
			}
		}
		return $jsonArray;
	}

	/**
	 * Parses the HTML tags that would have been inserted to the <head> of a HTML document and returns the found tags as multidimensional array.
	 *
	 * @return array The parsed tags with their attributes and innerHTML parts
	 * @todo: WTF?
	 */
	protected function getInlineHeadTags() {
		$headTags = array();
		$headDataRaw = $this->JStop() . $this->getJavaScriptOfPageRenderer();
		if ($headDataRaw) {
			// Create instance of the HTML parser:
			$parseObj = GeneralUtility::makeInstance(HtmlParser::class);
			// Removes script wraps:
			$headDataRaw = str_replace(array('/*<![CDATA[*/', '/*]]>*/'), '', $headDataRaw);
			// Removes leading spaces of a multi-line string:
			$headDataRaw = trim(preg_replace('/(^|\\r|\\n)( |\\t)+/', '$1', $headDataRaw));
			// Get script and link tags:
			$tags = array_merge(
				$parseObj->getAllParts($parseObj->splitTags('link', $headDataRaw)),
				$parseObj->getAllParts($parseObj->splitIntoBlock('script', $headDataRaw))
			);
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
	 * @todo: aaaargs ...
	 */
	protected function getJavaScriptOfPageRenderer() {
		/** @var $pageRenderer PageRenderer */
		$pageRenderer = clone $this->getPageRenderer();
		$pageRenderer->setCharSet($this->getLanguageService()->charSet);
		$pageRenderer->setTemplateFile('EXT:backend/Resources/Private/Templates/helper_javascript_css.html');
		return $pageRenderer->render();
	}

	/**
	 * Returns the "returnUrl" of the form. Can be set externally or will be taken from "GeneralUtility::linkThisScript()"
	 *
	 * @return string Return URL of current script
	 */
	protected function thisReturnUrl() {
		return $this->returnUrl ? $this->returnUrl : GeneralUtility::linkThisScript();
	}

	/********************************************
	 *
	 * Template functions
	 *
	 ********************************************/
	/**
	 * Wraps all the table rows into a single table.
	 * Used externally from scripts like EditDocumentController and PageLayoutController (which uses FormEngine)
	 *
	 * @param string $c Code to output between table-parts; table rows
	 * @param array $rec The record
	 * @param string $table The table name
	 * @return string
	 */
	public function wrapTotal($c, $rec, $table) {
		$parts = $this->replaceTableWrap(explode('|', $this->totalWrap, 2), $rec, $table);
		return $parts[0] . $c . $parts[1] . implode(LF, $this->hiddenFieldAccum);
	}

	/**
	 * Generates a token and returns an input field with it
	 *
	 * @param string $formName Context of the token
	 * @param string $tokenName The name of the token GET/POST variable
	 * @return string A complete input field
	 */
	static public function getHiddenTokenField($formName = 'securityToken', $tokenName = 'formToken') {
		$formprotection = FormProtectionFactory::get();
		return '<input type="hidden" name="' . $tokenName . '" value="' . $formprotection->generateToken($formName) . '" />';
	}

	/**
	 * This replaces markers in the total wrap
	 *
	 * @param array $arr An array of template parts containing some markers.
	 * @param array $rec The record
	 * @param string $table The table name
	 * @return string
	 */
	public function replaceTableWrap($arr, $rec, $table) {
		$icon = IconUtility::getSpriteIconForRecord($table, $rec, array('title' => $this->getRecordPath($table, $rec)));
		// Make "new"-label
		$languageService = $this->getLanguageService();
		if (strstr($rec['uid'], 'NEW')) {
			$newLabel = ' <span class="typo3-TCEforms-newToken">' . $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.new', TRUE) . '</span>';
			// BackendUtility::fixVersioningPid Should not be used here because NEW records are not offline workspace versions...
			$truePid = BackendUtility::getTSconfig_pidValue($table, $rec['uid'], $rec['pid']);
			$prec = BackendUtility::getRecordWSOL('pages', $truePid, 'title');
			$pageTitle = BackendUtility::getRecordTitle('pages', $prec, TRUE, FALSE);
			$rLabel = '<em>[PID: ' . $truePid . '] ' . $pageTitle . '</em>';
			// Fetch translated title of the table
			$tableTitle = $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle);
			} else {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecord', TRUE);
				if ($rec['pid'] == 0) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecordRootLevel', TRUE);
				}
				$pageTitle = sprintf($label, $tableTitle, $pageTitle);
			}
		} else {
			$newLabel = ' <span class="typo3-TCEforms-recUid">[' . $rec['uid'] . ']</span>';
			$rLabel = BackendUtility::getRecordTitle($table, $rec, TRUE, FALSE);
			$prec = BackendUtility::getRecordWSOL('pages', $rec['pid'], 'uid,title');
			// Fetch translated title of the table
			$tableTitle = $languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editPage', TRUE);
				// Just take the record title and prepend an edit label.
				$pageTitle = sprintf($label, $tableTitle, $rLabel);
			} else {
				$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecord', TRUE);
				$pageTitle = BackendUtility::getRecordTitle('pages', $prec, TRUE, FALSE);
				if ($rLabel === BackendUtility::getNoRecordTitle(TRUE)) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordNoTitle', TRUE);
				}
				if ($rec['pid'] == 0) {
					$label = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordRootLevel', TRUE);
				}
				if ($rLabel !== BackendUtility::getNoRecordTitle(TRUE)) {
					// Just take the record title and prepend an edit label.
					$pageTitle = sprintf($label, $tableTitle, $rLabel, $pageTitle);
				} else {
					// Leave out the record title since it is not set.
					$pageTitle = sprintf($label, $tableTitle, $pageTitle);
				}
			}
			$icon = $this->getControllerDocumentTemplate()->wrapClickMenuOnIcon($icon, $table, $rec['uid'], 1, '', '+copy,info,edit,view');
		}
		foreach ($arr as $k => $v) {
			// Make substitutions:
			$arr[$k] = str_replace(
				array(
					'###PAGE_TITLE###',
					'###ID_NEW_INDICATOR###',
					'###RECORD_LABEL###',
					'###TABLE_TITLE###',
					'###RECORD_ICON###'
				),
				array(
					$pageTitle,
					$newLabel,
					$rLabel,
					htmlspecialchars($languageService->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
					$icon
				),
				$arr[$k]
			);
		}
		return $arr;
	}


	/********************************************
	 *
	 * JavaScript related functions
	 *
	 ********************************************/
	/**
	 * JavaScript code added BEFORE the form is drawn:
	 *
	 * @return string A <script></script> section with JavaScript.
	 */
	public function JStop() {
		$out = '';
		if (!empty($this->additionalCode_pre)) {
			$out = implode(LF, $this->additionalCode_pre) . LF;
		}
		return $out;
	}

	/**
	 * JavaScript bottom code
	 *
	 * @param string $formname The identification of the form on the page.
	 * @param bool $update Just extend/update existing settings, e.g. for AJAX call
	 * @return string A section with JavaScript - if $update is FALSE, embedded in <script></script>
	 */
	public function JSbottom($formname = 'forms[0]', $update = FALSE) {
		$languageService = $this->getLanguageService();
		$jsFile = array();
		$out = '';
		$this->TBE_EDITOR_fieldChanged_func = 'TBE_EDITOR.fieldChanged_fName(fName,formObj[fName+"_list"]);';
		if (!$update) {
			if ($this->loadMD5_JS) {
				$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/md5.js');
			}
			// load the main module for FormEngine with all important JS functions
			$this->requireJsModules['TYPO3/CMS/Backend/FormEngine'] = 'function(FormEngine) {
				FormEngine.setBrowserUrl(' . GeneralUtility::quoteJSvalue(BackendUtility::getModuleUrl('browser')) . ');
			}';
			$this->requireJsModules['TYPO3/CMS/Backend/FormEngineValidation'] = 'function(FormEngineValidation) {
				FormEngineValidation.setUsMode(' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '1' : '0') . ');
				FormEngineValidation.registerReady();
			}';

			$pageRenderer = $this->getPageRenderer();
			foreach ($this->requireJsModules as $moduleName => $callbacks) {
				if (!is_array($callbacks)) {
					$callbacks = array($callbacks);
				}
				foreach ($callbacks as $callback) {
					$pageRenderer->loadRequireJsModule($moduleName, $callback);
				}
			}
			$pageRenderer->loadPrototype();
			$pageRenderer->loadJquery();
			$pageRenderer->loadExtJS();
			// rtehtmlarea needs extjs quick tips (?)
			$pageRenderer->enableExtJSQuickTips();
			$beUserAuth = $this->getBackendUserAuthentication();
			// Make textareas resizable and flexible ("autogrow" in height)
			$textareaSettings = array(
				'autosize'  => (bool)$beUserAuth->uc['resizeTextareas_Flexible']
			);
			$pageRenderer->addInlineSettingArray('Textarea', $textareaSettings);

			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tbe_editor.js');
			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/ValueSlider');
			// Needed for FormEngine manipulation (date picker)
			$dateFormat = ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? array('MM-DD-YYYY', 'HH:mm MM-DD-YYYY') : array('DD-MM-YYYY', 'HH:mm DD-MM-YYYY'));
			$pageRenderer->addInlineSetting('DateTimePicker', 'DateFormat', $dateFormat);

			// support placeholders for IE9 and lower
			$clientInfo = GeneralUtility::clientInfo();
			if ($clientInfo['BROWSER'] == 'msie' && $clientInfo['VERSION'] <= 9) {
				$this->loadJavascriptLib('sysext/core/Resources/Public/JavaScript/Contrib/placeholders.jquery.min.js');
			}

			// @todo: remove scriptaclous once suggest & flex form foo is moved to RequireJS, see #55575
			$pageRenderer->loadScriptaculous();
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tceforms_suggest.js');

			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Filelist/FileListLocalisation');
			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/DragUploader');

			$pageRenderer->addInlineLanguagelabelFile(
				\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('lang') . 'locallang_core.xlf',
				'file_upload'
			);

			// We want to load jQuery-ui inside our js. Enable this using requirejs.
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.inline.js');
			$out .= '
			inline.setNoTitleString("' . addslashes(BackendUtility::getNoRecordTitle(TRUE)) . '");
			';

			$out .= '
			TBE_EDITOR.formname = "' . $formname . '";
			TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
			TBE_EDITOR.backPath = "";
			TBE_EDITOR.isPalettedoc = null;
			TBE_EDITOR.doSaveFieldName = "' . ($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '') . '";
			TBE_EDITOR.labels.fieldsChanged = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsChanged')) . ';
			TBE_EDITOR.labels.fieldsMissing = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsMissing')) . ';
			TBE_EDITOR.labels.maxItemsAllowed = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.maxItemsAllowed')) . ';
			TBE_EDITOR.labels.refresh_login = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.refresh_login')) . ';
			TBE_EDITOR.labels.onChangeAlert = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:mess.onChangeAlert')) . ';
			TBE_EDITOR.labels.remainingCharacters = ' . GeneralUtility::quoteJSvalue($languageService->sL('LLL:EXT:lang/locallang_core.xlf:labels.remainingCharacters')) . ';
			TBE_EDITOR.customEvalFunctions = {};

			';
		}
		// Add JS required for inline fields
		if (!empty($this->inlineData)) {
			$out .= '
			inline.addToDataArray(' . json_encode($this->inlineData) . ');
			';
		}
		// $this->additionalJS_submit:
		if ($this->additionalJS_submit) {
			$additionalJS_submit = implode('', $this->additionalJS_submit);
			$additionalJS_submit = str_replace(array(CR, LF), '', $additionalJS_submit);
			$out .= '
			TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJS_submit) . '");
			';
		}
		$out .= LF . implode(LF, $this->additionalJS_post) . LF . $this->extJSCODE;
		// Regular direct output:
		if (!$update) {
			$spacer = LF . TAB;
			$out = $spacer . implode($spacer, $jsFile) . GeneralUtility::wrapJS($out);
		}
		return $out;
	}

	/**
	 * Prints necessary JavaScript for TCEforms (after the form HTML).
	 * currently this is used to transform page-specific options in the TYPO3.Settings array for JS
	 * so the JS module can access these values
	 *
	 * @return string
	 */
	public function printNeededJSFunctions() {
		// set variables to be accessible for JS
		$pageRenderer = $this->getPageRenderer();
		$pageRenderer->addInlineSetting('FormEngine', 'formName', 'editform');
		$pageRenderer->addInlineSetting('FormEngine', 'backPath', '');

		// Integrate JS functions for the element browser if such fields or IRRE fields were processed
		$pageRenderer->addInlineSetting('FormEngine', 'legacyFieldChangedCb', 'function() { ' . $this->TBE_EDITOR_fieldChanged_func . ' };');

		return $this->JSbottom('editform');
	}

	/**
	 * Returns necessary JavaScript for the top
	 *
	 * @return string
	 */
	public function printNeededJSFunctions_top() {
		return $this->JStop('editform');
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied.
	 * This method acts as wrapper for $GLOBALS['SOBE']->doc->loadJavascriptLib($lib).
	 *
	 * @param string $lib Library name. Call it with the full path like "sysext/core/Resources/Public/JavaScript/QueryGenerator.js" to load it
	 * @return void
	 */
	public function loadJavascriptLib($lib) {
		$this->getControllerDocumentTemplate()->loadJavascriptLib($lib);
	}

	/********************************************
	 *
	 * Various helper functions
	 *
	 ********************************************/

	/**
	 * Return record path (visually formatted, using BackendUtility::getRecordPath() )
	 *
	 * @param string $table Table name
	 * @param array $rec Record array
	 * @return string The record path.
	 * @see BackendUtility::getRecordPath()
	 */
	public function getRecordPath($table, $rec) {
		BackendUtility::fixVersioningPid($table, $rec);
		list($tscPID, $thePidValue) = BackendUtility::getTSCpidCached($table, $rec['uid'], $rec['pid']);
		if ($thePidValue >= 0) {
			return BackendUtility::getRecordPath($tscPID, $this->readPerms(), 15);
		}
		return '';
	}

	/**
	 * Returns the select-page read-access SQL clause.
	 * Returns cached string, so you can call this function as much as you like without performance loss.
	 *
	 * @return string
	 */
	public function readPerms() {
		if (!$this->perms_clause_set) {
			$this->perms_clause = $this->getBackendUserAuthentication()->getPagePermsClause(1);
			$this->perms_clause_set = TRUE;
		}
		return $this->perms_clause;
	}

	/**
	 * Returns TRUE if descriptions should be loaded always
	 *
	 * @param string $table Table for which to check
	 * @return bool
	 */
	public function doLoadTableDescr($table) {
		return $GLOBALS['TCA'][$table]['interface']['always_description'];
	}

	/**
	 * Initialize list of additional preview languages.
	 * Sets according list in $this->additionalPreviewLanguages
	 *
	 * @return void
	 */
	protected function initializeAdditionalPreviewLanguages() {
		$backendUserAuthentication = $this->getBackendUserAuthentication();
		$additionalPreviewLanguageListOfUser = $backendUserAuthentication->getTSConfigVal('options.additionalPreviewLanguages');
		$additionalPreviewLanguages = array();
		if ($additionalPreviewLanguageListOfUser) {
			$uids = GeneralUtility::intExplode(',', $additionalPreviewLanguageListOfUser);
			foreach ($uids as $uid) {
				if ($sys_language_rec = BackendUtility::getRecord('sys_language', $uid)) {
					$additionalPreviewLanguages[$uid]['uid'] = $uid;
					if (!empty($sys_language_rec['language_isocode'])) {
						$additionalPreviewLanguages[$uid]['ISOcode'] = $sys_language_rec['language_isocode'];
					} elseif ($sys_language_rec['static_lang_isocode'] && ExtensionManagementUtility::isLoaded('static_info_tables')) {
						GeneralUtility::deprecationLog('Usage of the field "static_lang_isocode" is discouraged, and will stop working with CMS 8. Use the built-in language field "language_isocode" in your sys_language records.');
						$staticLangRow = BackendUtility::getRecord('static_languages', $sys_language_rec['static_lang_isocode'], 'lg_iso_2');
						if ($staticLangRow['lg_iso_2']) {
							$additionalPreviewLanguages[$uid]['ISOcode'] = $staticLangRow['lg_iso_2'];
						}
					}
				}
			}
		}
		$this->additionalPreviewLanguages = $additionalPreviewLanguages;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return DocumentTemplate
	 */
	protected function getControllerDocumentTemplate() {
		// $GLOBALS['SOBE'] might be any kind of PHP class (controller most of the times)
		// These class do not inherit from any common class, but they all seem to have a "doc" member
		return $GLOBALS['SOBE']->doc;
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

	/**
	 * Wrapper for access to the current page renderer object
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		if ($this->pageRenderer === NULL) {
			$this->pageRenderer = GeneralUtility::makeInstance(PageRenderer::class);
		}

		return $this->pageRenderer;
	}

}
