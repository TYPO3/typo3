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

use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;
use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\TcaDatabaseRecord;
use TYPO3\CMS\Backend\Form\InlineRelatedRecordResolver;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Http\AjaxRequestHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Handle FormEngine inline ajax calls
 */
class FormInlineAjaxController {

	/**
	 * @var InlineStackProcessor
	 */
	protected $inlineStackProcessor;

	/**
	 * General processor for AJAX requests concerning IRRE.
	 *
	 * @param array $_ Additional parameters (not used here)
	 * @param AjaxRequestHandler $ajaxObj The AjaxRequestHandler object of this request
	 * @throws \RuntimeException
	 * @return void
	 */
	public function processInlineAjaxRequest($_, AjaxRequestHandler $ajaxObj) {
		$this->inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$ajaxArguments = GeneralUtility::_GP('ajax');
		$ajaxIdParts = explode('::', $GLOBALS['ajaxID'], 2);
		if (isset($ajaxArguments) && is_array($ajaxArguments) && !empty($ajaxArguments)) {
			$ajaxMethod = $ajaxIdParts[1];
			$ajaxObj->setContentFormat('jsonbody');
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

		$formDataInput = [];
		$formDataInput['vanillaUid'] = (int)$parent['uid'];
		$formDataInput['command'] = 'edit';
		$formDataInput['tableName'] = $parent['table'];
		$formDataInput['inlineFirstPid'] = $inlineFirstPid;
		$formDataInput['inlineStructure'] = $this->inlineStackProcessor->getStructure();

		/** @var TcaDatabaseRecord $formDataGroup */
		$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
		/** @var FormDataCompiler $formDataCompiler */
		$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

		$formData = $formDataCompiler->compile($formDataInput);
		$formData['renderType'] = 'inlineRecordContainer';
		$formData['inlineRelatedRecordToRender'] = $record;
		$formData['inlineRelatedRecordConfig'] = $config;

		try {
			// Access to this record may be denied, create an according error message in this case
			$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
			$childArray = $nodeFactory->create($formData)->render();
		} catch (AccessDeniedException $e) {
			return $this->getErrorMessageForAJAX('Access denied');
		}

		$jsonArray = [
			'data' => '',
			'stylesheetFiles' => [],
			'scriptCall' => [],
		];
		$jsonArray['scriptCall'][] = 'inline.domAddRecordDetails(' . GeneralUtility::quoteJSvalue($domObjectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . ($expandSingle ? '1' : '0') . ',json.data);';
		if ($config['foreign_unique']) {
			$jsonArray['scriptCall'][] = 'inline.removeUsed(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ');';
		}
		if (!empty($childArray['inlineData'])) {
			$jsonArray['scriptCall'][] = 'inline.addToDataArray(' . json_encode($childArray['inlineData']) . ');';
		}
		$jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childArray);
		if ($config['appearance']['useSortable']) {
			$inlineObjectName = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
			$jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
		}
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

		/** @var InlineRelatedRecordResolver $inlineRelatedRecordResolver */
		$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);

		$config = FormEngineUtility::mergeInlineConfiguration($config);

		$collapseAll = isset($config['appearance']['collapseAll']) && $config['appearance']['collapseAll'];
		$expandSingle = isset($config['appearance']['expandSingle']) && $config['appearance']['expandSingle'];

		$inlineFirstPid = FormEngineUtility::getInlineFirstPidFromDomObjectId($domObjectId);

		// Dynamically create a new record
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
			if ($parent['localizationMode'] === 'select' && MathUtility::canBeInterpretedAsInteger($parent['uid'])) {
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

		$formDataInput = [];
		$formDataInput['vanillaUid'] = (int)$parent['uid'];
		$formDataInput['command'] = 'edit';
		$formDataInput['tableName'] = $parent['table'];
		$formDataInput['inlineFirstPid'] = $inlineFirstPid;
		$formDataInput['inlineStructure'] = $this->inlineStackProcessor->getStructure();

		if (!MathUtility::canBeInterpretedAsInteger($parent['uid']) && (int)$formDataInput['inlineFirstPid'] > 0) {
			$formDataInput['command'] = 'new';
			$formDataInput['vanillaUid'] = (int)$formDataInput['inlineFirstPid'];
		}

		/** @var TcaDatabaseRecord $formDataGroup */
		$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
		/** @var FormDataCompiler $formDataCompiler */
		$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

		$formData = $formDataCompiler->compile($formDataInput);
		$formData['renderType'] = 'inlineRecordContainer';
		$formData['inlineRelatedRecordToRender'] = $record;
		$formData['inlineRelatedRecordConfig'] = $config;

		try {
			// Access to this record may be denied, create an according error message in this case
			$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
			$childArray = $nodeFactory->create($formData)->render();
		} catch (AccessDeniedException $e) {
			return $this->getErrorMessageForAJAX('Access denied');
		}

		$jsonArray = [
			'data' => '',
			'stylesheetFiles' => [],
			'scriptCall' => [],
		];
		if (!$current['uid']) {
			$jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'bottom\',' . GeneralUtility::quoteJSvalue($objectName . '_records') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
			$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ',null,' . GeneralUtility::quoteJSvalue($foreignUid) . ');';
		} else {
			$jsonArray['scriptCall'][] = 'inline.domAddNewRecord(\'after\',' . GeneralUtility::quoteJSvalue($domObjectId . '_div') . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',json.data);';
			$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ',' . GeneralUtility::quoteJSvalue($current['uid']) . ',' . GeneralUtility::quoteJSvalue($foreignUid) . ');';
		}
		$jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childArray);
		if ($config['appearance']['useSortable']) {
			$inlineObjectName = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($inlineFirstPid);
			$jsonArray['scriptCall'][] = 'inline.createDragAndDropSorting(' . GeneralUtility::quoteJSvalue($inlineObjectName . '_records') . ');';
		}
		if (!$collapseAll && $expandSingle) {
			$jsonArray['scriptCall'][] = 'inline.collapseAllRecords(' . GeneralUtility::quoteJSvalue($objectId) . ',' . GeneralUtility::quoteJSvalue($objectPrefix) . ',' . GeneralUtility::quoteJSvalue($record['uid']) . ');';
		}
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
			/** @var $tce DataHandler */
			$tce = GeneralUtility::makeInstance(DataHandler::class);
			$tce->stripslashes_values = FALSE;
			$tce->start(array(), $cmd);
			$tce->process_cmdmap();

			$oldItemList = $parentRecord[$parent['field']];
			$newItemList = $tce->registerDBList[$parent['table']][$parent['uid']][$parent['field']];

			$jsonArray = array(
				'data' => '',
				'stylesheetFiles' => [],
				'scriptCall' => [],
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
			foreach ($localizedItems as $item) {
				$row = $inlineRelatedRecordResolver->getRecord($current['table'], $item);
				$selectedValue = $foreignSelector ? GeneralUtility::quoteJSvalue($row[$foreignSelector]) : 'null';

				$formDataInput = [];
				$formDataInput['vanillaUid'] = (int)$parent['uid'];
				$formDataInput['command'] = 'edit';
				$formDataInput['tableName'] = $parent['table'];
				$formDataInput['inlineFirstPid'] = $inlineFirstPid;
				$formDataInput['inlineStructure'] = $this->inlineStackProcessor->getStructure();

				/** @var TcaDatabaseRecord $formDataGroup */
				$formDataGroup = GeneralUtility::makeInstance(TcaDatabaseRecord::class);
				/** @var FormDataCompiler $formDataCompiler */
				$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);

				$formData = $formDataCompiler->compile($formDataInput);
				$formData['renderType'] = 'inlineRecordContainer';
				$formData['inlineRelatedRecordToRender'] = $row;
				$formData['inlineRelatedRecordConfig'] = $parent['config'];

				try {
					// Access to this record may be denied, create an according error message in this case
					$nodeFactory = GeneralUtility::makeInstance(NodeFactory::class);
					$childArray = $nodeFactory->create($formData)->render();
				} catch (AccessDeniedException $e) {
					return $this->getErrorMessageForAJAX('Access denied');
				}

				$jsonArray['html'] .= $childArray['html'];
				$jsonArray = [
					'data' => '',
					'stylesheetFiles' => [],
					'scriptCall' => [],
				];
				$jsonArray = $this->mergeChildResultIntoJsonResult($jsonArray, $childArray);
				$jsonArray['scriptCall'][] = 'inline.memorizeAddRecord(' . GeneralUtility::quoteJSvalue($nameObjectForeignTable) . ', ' . GeneralUtility::quoteJSvalue($item) . ', null, ' . $selectedValue . ');';
				// Remove possible virtual records in the form which showed that a child records could be localized:
				if (isset($row[$transOrigPointerField]) && $row[$transOrigPointerField]) {
					$jsonArray['scriptCall'][] = 'inline.fadeAndRemove(' . GeneralUtility::quoteJSvalue($nameObjectForeignTable . '-' . $row[$transOrigPointerField] . '_div') . ');';
				}
			}
			if (!empty($jsonArray['data'])) {
				array_unshift($jsonArray['scriptCall'], 'inline.domAddNewRecord(\'bottom\', ' . GeneralUtility::quoteJSvalue($nameObject . '_records') . ', ' . GeneralUtility::quoteJSvalue($nameObjectForeignTable) . ', json.data);');
			}
		}
		return $jsonArray;
	}

	/**
	 * Merge stuff from child array into json array.
	 * This method is needed since ajax handling methods currently need to put scriptCalls before and after child code.
	 *
	 * @param array $jsonResult Given json result
	 * @param array $childResult Given child result
	 * @return array Merged json array
	 */
	protected function mergeChildResultIntoJsonResult(array $jsonResult, array $childResult) {
		$jsonResult['data'] = $childResult['html'];
		$jsonResult['stylesheetFiles'] = $childResult['stylesheetFiles'];
		if (!empty($childResult['inlineData'])) {
			$jsonResult['scriptCall'][] = 'inline.addToDataArray(' . json_encode($childResult['inlineData']) . ');';
		}
		if (!empty($childResult['additionalJavaScriptSubmit'])) {
			$additionalJavaScriptSubmit = implode('', $childResult['additionalJavaScriptSubmit']);
			$additionalJavaScriptSubmit = str_replace(array(CR, LF), '', $additionalJavaScriptSubmit);
			$jsonResult['scriptCall'][] = 'TBE_EDITOR.addActionChecks("submit", "' . addslashes($additionalJavaScriptSubmit) . '");';
		}
		foreach ($childResult['additionalJavaScriptPost'] as $singleAdditionalJavaScriptPost) {
			$jsonResult['scriptCall'][] = $singleAdditionalJavaScriptPost;
		}
		$jsonResult['scriptCall'][] = $childResult['extJSCODE'];
		foreach ($childResult['requireJsModules'] as $moduleName => $callbacks) {
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
				$jsonResult['scriptCall'][] = '/*RequireJS-Module-' . $inlineCodeKey . '*/' . LF . $javaScriptCode;
			}
		}
		return $jsonResult;
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
		return [
			'data' => $message,
			'scriptCall' => [
				'alert("' . $message . '");'
			],
		];
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

}
