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

use TYPO3\CMS\Backend\Form\FormDataCompiler;
use TYPO3\CMS\Backend\Form\FormDataGroup\OnTheFly;
use TYPO3\CMS\Backend\Form\FormDataProvider\TcaSelectItems;
use TYPO3\CMS\Backend\Form\NodeFactory;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Imaging\Icon;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Core\Resource\Folder;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Backend\Form\Utility\FormEngineUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Backend\Form\InlineStackProcessor;
use TYPO3\CMS\Backend\Form\InlineRelatedRecordResolver;
use TYPO3\CMS\Backend\Form\Exception\AccessDeniedException;

/**
 * Inline element entry container.
 *
 * This container is the entry step to rendering an inline element. It is created by SingleFieldContainer.
 *
 * The code creates the main structure for the single inline elements, initializes
 * the inlineData array, that is manipulated and also returned back in its manipulated state.
 * The "control" stuff of inline elements is rendered here, for example the "create new" button.
 *
 * For each existing inline relation an InlineRecordContainer is called for further processing.
 */
class InlineControlContainer extends AbstractContainer {

	/**
	 * Inline data array used in JS, returned as JSON object to frontend
	 *
	 * @var array
	 */
	protected $inlineData = array();

	/**
	 * @var InlineStackProcessor
	 */
	protected $inlineStackProcessor;

	/**
	 * @var IconFactory
	 */
	protected $iconFactory;

	/**
	 * Container objects give $nodeFactory down to other containers.
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
	 */
	public function render() {
		$languageService = $this->getLanguageService();

		$this->inlineData = $this->data['inlineData'];

		/** @var InlineStackProcessor $inlineStackProcessor */
		$inlineStackProcessor = GeneralUtility::makeInstance(InlineStackProcessor::class);
		$this->inlineStackProcessor = $inlineStackProcessor;
		$inlineStackProcessor->initializeByGivenStructure($this->data['inlineStructure']);

		$table = $this->data['tableName'];
		$row = $this->data['databaseRow'];
		$field = $this->data['fieldName'];
		$parameterArray = $this->data['parameterArray'];

		$resultArray = $this->initializeResultArray();
		$html = '';

		// An inline field must have a foreign_table, if not, stop all further inline actions for this field
		if (
			!$parameterArray['fieldConf']['config']['foreign_table']
			|| !is_array($GLOBALS['TCA'][$parameterArray['fieldConf']['config']['foreign_table']])
		) {
			return $resultArray;
		}

		$config = FormEngineUtility::mergeInlineConfiguration($parameterArray['fieldConf']['config']);
		$foreign_table = $config['foreign_table'];

		$language = 0;
		if (BackendUtility::isTableLocalizable($table)) {
			$language = (int)$row[$GLOBALS['TCA'][$table]['ctrl']['languageField']];
		}
		$minItems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		$maxItems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxItems) {
			$maxItems = 100000;
		}

		// Add the current inline job to the structure stack
		$newStructureItem = array(
			'table' => $table,
			'uid' => $row['uid'],
			'field' => $field,
			'config' => $config,
			'localizationMode' => BackendUtility::getInlineLocalizationMode($table, $config),
		);
		// Extract FlexForm parts (if any) from element name, e.g. array('vDEF', 'lDEF', 'FlexField', 'vDEF')
		if (!empty($parameterArray['itemFormElName'])) {
			$flexFormParts = FormEngineUtility::extractFlexFormParts($parameterArray['itemFormElName']);
			if ($flexFormParts !== NULL) {
				$newStructureItem['flexform'] = $flexFormParts;
			}
		}
		$inlineStackProcessor->pushStableStructureItem($newStructureItem);

		// e.g. data[<table>][<uid>][<field>]
		$nameForm = $inlineStackProcessor->getCurrentStructureFormPrefix();
		// e.g. data-<pid>-<table1>-<uid1>-<field1>-<table2>-<uid2>-<field2>
		$nameObject = $inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);

		// Get the records related to this inline record
		/** @var InlineRelatedRecordResolver $inlineRelatedRecordResolver */
		$inlineRelatedRecordResolver = GeneralUtility::makeInstance(InlineRelatedRecordResolver::class);
		$relatedRecords = $inlineRelatedRecordResolver->getRelatedRecords($table, $field, $row, $parameterArray, $config, $this->data['inlineFirstPid']);

		// Set the first and last record to the config array
		$relatedRecordsUids = array_keys($relatedRecords['records']);
		$config['inline']['first'] = reset($relatedRecordsUids);
		$config['inline']['last'] = end($relatedRecordsUids);

		$top = $inlineStackProcessor->getStructureLevel(0);

		$this->inlineData['config'][$nameObject] = array(
			'table' => $foreign_table,
			'md5' => md5($nameObject)
		);
		$this->inlineData['config'][$nameObject . '-' . $foreign_table] = array(
			'min' => $minItems,
			'max' => $maxItems,
			'sortable' => $config['appearance']['useSortable'],
			'top' => array(
				'table' => $top['table'],
				'uid' => $top['uid']
			),
			'context' => array(
				'config' => $config,
				'hmac' => GeneralUtility::hmac(serialize($config)),
			),
		);
		$this->inlineData['nested'][$nameObject] = $this->data['tabAndInlineStack'];

		// If relations are required to be unique, get the uids that have already been used on the foreign side of the relation
		if ($config['foreign_unique']) {
			// If uniqueness *and* selector are set, they should point to the same field - so, get the configuration of one:
			$selConfig = FormEngineUtility::getInlinePossibleRecordsSelectorConfig($config, $config['foreign_unique']);
			// Get the used unique ids:
			$uniqueIds = $this->getUniqueIds($relatedRecords['records'], $config, $selConfig['type'] == 'groupdb');
			$possibleRecords = $this->getPossibleRecords($table, $field, $row, $config, 'foreign_unique');
			$uniqueMax = $config['appearance']['useCombination'] || $possibleRecords === FALSE ? -1 : count($possibleRecords);
			$this->inlineData['unique'][$nameObject . '-' . $foreign_table] = array(
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

		$resultArray['inlineData'] = $this->inlineData;

		// Render the localization links
		$localizationLinks = '';
		if ($language > 0 && $row[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] > 0 && MathUtility::canBeInterpretedAsInteger($row['uid'])) {
			// Add the "Localize all records" link before all child records:
			if (isset($config['appearance']['showAllLocalizationLink']) && $config['appearance']['showAllLocalizationLink']) {
				$localizationLinks .= ' ' . $this->getLevelInteractionLink('localize', $nameObject . '-' . $foreign_table, $config);
			}
			// Add the "Synchronize with default language" link before all child records:
			if (isset($config['appearance']['showSynchronizationLink']) && $config['appearance']['showSynchronizationLink']) {
				$localizationLinks .= ' ' . $this->getLevelInteractionLink('synchronize', $nameObject . '-' . $foreign_table, $config);
			}
		}

		// Define how to show the "Create new record" link - if there are more than maxitems, hide it
		if ($relatedRecords['count'] >= $maxItems || $uniqueMax > 0 && $relatedRecords['count'] >= $uniqueMax) {
			$config['inline']['inlineNewButtonStyle'] = 'display: none;';
			$config['inline']['inlineNewRelationButtonStyle'] = 'display: none;';
		}

		// Render the level links (create new record):
		$levelLinks = $this->getLevelInteractionLink('newRecord', $nameObject . '-' . $foreign_table, $config);

		// Wrap all inline fields of a record with a <div> (like a container)
		$html .= '<div class="form-group" id="' . $nameObject . '">';
		// Add the level links before all child records:
		if ($config['appearance']['levelLinksPosition'] === 'both' || $config['appearance']['levelLinksPosition'] === 'top') {
			$html .= '<div class="form-group t3js-formengine-validation-marker">' . $levelLinks . $localizationLinks . '</div>';
		}
		// If it's required to select from possible child records (reusable children), add a selector box
		if ($config['foreign_selector'] && $config['appearance']['showPossibleRecordsSelector'] !== FALSE) {
			// If not already set by the foreign_unique, set the possibleRecords here and the uniqueIds to an empty array
			if (!$config['foreign_unique']) {
				$possibleRecords = $this->getPossibleRecords($table, $field, $row, $config);
				$uniqueIds = array();
			}
			$selectorBox = $this->renderPossibleRecordsSelector($possibleRecords, $config, $uniqueIds);
			$html .= $selectorBox . $localizationLinks;
		}
		$title = $languageService->sL($parameterArray['fieldConf']['label']);
		$html .= '<div class="panel-group panel-hover" data-title="' . htmlspecialchars($title) . '" id="' . $nameObject . '_records">';

		$relationList = array();
		if (!empty($relatedRecords['records'])) {
			foreach ($relatedRecords['records'] as $rec) {
				$options = $this->data;
				$options['inlineRelatedRecordToRender'] = $rec;
				$options['inlineRelatedRecordConfig'] = $config;
				$options['inlineData'] = $this->inlineData;
				$options['inlineStructure'] = $inlineStackProcessor->getStructure();
				$options['renderType'] = 'inlineRecordContainer';
				try {
					// This container may raise an access denied exception, to not kill further processing,
					// just a simple "empty" return is created here to ignore this field.
					$childArray = $this->nodeFactory->create($options)->render();
				} catch (AccessDeniedException $e) {
					$childArray = $this->initializeResultArray();
				}
				$html .= $childArray['html'];
				$childArray['html'] = '';
				$resultArray = $this->mergeChildReturnIntoExistingResult($resultArray, $childArray);
				if (!isset($rec['__virtual']) || !$rec['__virtual']) {
					$relationList[] = $rec['uid'];
				}
			}
		}
		$html .= '</div>';
		// Add the level links after all child records:
		if ($config['appearance']['levelLinksPosition'] ===  'both' || $config['appearance']['levelLinksPosition'] === 'bottom') {
			$html .= $levelLinks . $localizationLinks;
		}
		if (is_array($config['customControls'])) {
			$html .= '<div id="' . $nameObject . '_customControls">';
			foreach ($config['customControls'] as $customControlConfig) {
				$parameters = array(
					'table' => $table,
					'field' => $field,
					'row' => $row,
					'nameObject' => $nameObject,
					'nameForm' => $nameForm,
					'config' => $config
				);
				$html .= GeneralUtility::callUserFunction($customControlConfig, $parameters, $this);
			}
			$html .= '</div>';
		}
		// Add Drag&Drop functions for sorting to FormEngine::$additionalJS_post
		if (count($relationList) > 1 && $config['appearance']['useSortable']) {
			$resultArray['additionalJavaScriptPost'][] = 'inline.createDragAndDropSorting("' . $nameObject . '_records' . '");';
		}
		// Publish the uids of the child records in the given order to the browser
		$html .= '<input type="hidden" name="' . $nameForm . '" value="' . implode(',', $relationList) . '" ' . $this->getValidationDataAsDataAttribute(array('type' => 'inline', 'minitems' => $minItems, 'maxitems' => $maxItems)) . ' class="inlineRecord" />';
		// Close the wrap for all inline fields (container)
		$html .= '</div>';

		$resultArray['html'] = $html;
		return $resultArray;
	}

	/**
	 * Gets the uids of a select/selector that should be unique and have already been used.
	 *
	 * @param array $records All inline records on this level
	 * @param array $conf The TCA field configuration of the inline field to be rendered
	 * @param bool $splitValue For usage with group/db, values come like "tx_table_123|Title%20abc", but we need "tx_table" and "123
	 * @return array The uids, that have been used already and should be used unique
	 */
	protected function getUniqueIds($records, $conf = array(), $splitValue = FALSE) {
		$uniqueIds = array();
		if (isset($conf['foreign_unique']) && $conf['foreign_unique'] && !empty($records)) {
			foreach ($records as $rec) {
				// Skip virtual records (e.g. shown in localization mode):
				if (!isset($rec['__virtual']) || !$rec['__virtual']) {
					$value = $rec[$conf['foreign_unique']];
					// Split the value and extract the table and uid:
					if ($splitValue) {
						$valueParts = GeneralUtility::trimExplode('|', $value);
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
	 * Get possible records.
	 * Copied from FormEngine and modified.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $conf An array with additional configuration options.
	 * @param string $checkForConfField For which field in the foreign_table the possible records should be fetched
	 * @return mixed Array of possible record items; FALSE if type is "group/db", then everything could be "possible
	 */
	protected function getPossibleRecords($table, $field, $row, $conf, $checkForConfField = 'foreign_selector') {
		// Field configuration from TCA:
		$foreign_check = $conf[$checkForConfField];
		$foreignConfig = FormEngineUtility::getInlinePossibleRecordsSelectorConfig($conf, $foreign_check);
		$PA = $foreignConfig['PA'];
		if ($foreignConfig['type'] == 'select') {
			$pageTsConfig['TCEFORM.']['dummyTable.']['dummyField.'] = $PA['fieldTSConfig'];
			$selectDataInput = [
				'tableName' => 'dummyTable',
				'command' => 'edit',
				'pageTsConfigMerged' => $pageTsConfig,
				'vanillaTableTca' => [
					'ctrl' => [],
					'columns' => [
						'dummyField' => $PA['fieldConf'],
					],
				],
				'processedTca' => [
					'ctrl' => [],
					'columns' => [
						'dummyField' => $PA['fieldConf'],
					],
				],
			];

			/** @var OnTheFly $formDataGroup */
			$formDataGroup = GeneralUtility::makeInstance(OnTheFly::class);
			$formDataGroup->setProviderList([ TcaSelectItems::class ]);
			/** @var FormDataCompiler $formDataCompiler */
			$formDataCompiler = GeneralUtility::makeInstance(FormDataCompiler::class, $formDataGroup);
			$compilerResult = $formDataCompiler->compile($selectDataInput);
			$selItems = $compilerResult['processedTca']['columns']['dummyField']['config']['items'];
		} else {
			$selItems = FALSE;
		}
		return $selItems;
	}

	/**
	 * Makes a flat array from the $possibleRecords array.
	 * The key of the flat array is the value of the record,
	 * the value of the flat array is the label of the record.
	 *
	 * @param array $possibleRecords The possibleRecords array (for select fields)
	 * @return mixed A flat array with key=uid, value=label; if $possibleRecords isn't an array, FALSE is returned.
	 */
	protected function getPossibleRecordsFlat($possibleRecords) {
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
	 * Creates the HTML code of a general link to be used on a level of inline children.
	 * The possible keys for the parameter $type are 'newRecord', 'localize' and 'synchronize'.
	 *
	 * @param string $type The link type, values are 'newRecord', 'localize' and 'synchronize'.
	 * @param string $objectPrefix The "path" to the child record to create (e.g. 'data-parentPageId-partenTable-parentUid-parentField-childTable]')
	 * @param array $conf TCA configuration of the parent(!) field
	 * @return string The HTML code of the new link, wrapped in a div
	 */
	protected function getLevelInteractionLink($type, $objectPrefix, $conf = array()) {
		$languageService = $this->getLanguageService();
		$nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
		$attributes = array();
		switch ($type) {
			case 'newRecord':
				$title = $languageService->sL('LLL:EXT:lang/locallang_core.xlf:cm.createnew', TRUE);
				$icon = 'actions-document-new';
				$className = 'typo3-newRecordLink';
				$attributes['class'] = 'btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
				$attributes['onclick'] = 'return inline.createNewRecord(' . GeneralUtility::quoteJSvalue($objectPrefix) . ')';
				if (!empty($conf['inline']['inlineNewButtonStyle'])) {
					$attributes['style'] = $conf['inline']['inlineNewButtonStyle'];
				}
				if (!empty($conf['appearance']['newRecordLinkAddTitle'])) {
					$title = sprintf(
						$languageService->sL('LLL:EXT:lang/locallang_core.xlf:cm.createnew.link', TRUE),
						$languageService->sL($GLOBALS['TCA'][$conf['foreign_table']]['ctrl']['title'], TRUE)
					);
				} elseif (isset($conf['appearance']['newRecordLinkTitle']) && $conf['appearance']['newRecordLinkTitle'] !== '') {
					$title = $languageService->sL($conf['appearance']['newRecordLinkTitle'], TRUE);
				}
				break;
			case 'localize':
				$title = $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:localizeAllRecords', TRUE);
				$icon = 'actions-document-localize';
				$className = 'typo3-localizationLink';
				$attributes['class'] = 'btn btn-default';
				$attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($objectPrefix) . ', \'localize\')';
				break;
			case 'synchronize':
				$title = $languageService->sL('LLL:EXT:lang/locallang_misc.xlf:synchronizeWithOriginalLanguage', TRUE);
				$icon = 'actions-document-synchronize';
				$className = 'typo3-synchronizationLink';
				$attributes['class'] = 'btn btn-default inlineNewButton ' . $this->inlineData['config'][$nameObject]['md5'];
				$attributes['onclick'] = 'return inline.synchronizeLocalizeRecords(' . GeneralUtility::quoteJSvalue($objectPrefix) . ', \'synchronize\')';
				break;
			default:
				$title = '';
				$icon = '';
				$className = '';
		}
		// Create the link:
		$icon = $icon ? $this->iconFactory->getIcon($icon, Icon::SIZE_SMALL) : '';
		$link = $this->wrapWithAnchor($icon . $title, '#', $attributes);
		return '<div' . ($className ? ' class="' . $className . '"' : '') . 'title="' . $title . '">' . $link . '</div>';
	}

	/**
	 * Wraps a text with an anchor and returns the HTML representation.
	 *
	 * @param string $text The text to be wrapped by an anchor
	 * @param string $link  The link to be used in the anchor
	 * @param array $attributes Array of attributes to be used in the anchor
	 * @return string The wrapped text as HTML representation
	 */
	protected function wrapWithAnchor($text, $link, $attributes = array()) {
		$link = trim($link);
		$result = '<a href="' . ($link ?: '#') . '"';
		foreach ($attributes as $key => $value) {
			$result .= ' ' . $key . '="' . htmlspecialchars(trim($value)) . '"';
		}
		$result .= '>' . $text . '</a>';
		return $result;
	}

	/**
	 * Get a selector as used for the select type, to select from all available
	 * records and to create a relation to the embedding record (e.g. like MM).
	 *
	 * @param array $selItems Array of all possible records
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param array $uniqueIds The uids that have already been used and should be unique
	 * @return string A HTML <select> box with all possible records
	 */
	protected function renderPossibleRecordsSelector($selItems, $conf, $uniqueIds = array()) {
		$foreign_selector = $conf['foreign_selector'];
		$selConfig = FormEngineUtility::getInlinePossibleRecordsSelectorConfig($conf, $foreign_selector);
		$item  = '';
		if ($selConfig['type'] === 'select') {
			$item = $this->renderPossibleRecordsSelectorTypeSelect($selItems, $conf, $selConfig['PA'], $uniqueIds);
		} elseif ($selConfig['type'] === 'groupdb') {
			$item = $this->renderPossibleRecordsSelectorTypeGroupDB($conf, $selConfig['PA']);
		}
		return $item;
	}

	/**
	 * Generate a link that opens an element browser in a new window.
	 * For group/db there is no way to use a "selector" like a <select>|</select>-box.
	 *
	 * @param array $conf TCA configuration of the parent(!) field
	 * @param array $PA An array with additional configuration options
	 * @return string A HTML link that opens an element browser in a new window
	 */
	protected function renderPossibleRecordsSelectorTypeGroupDB($conf, &$PA) {
		$backendUser = $this->getBackendUserAuthentication();

		$config = $PA['fieldConf']['config'];
		ArrayUtility::mergeRecursiveWithOverrule($config, $conf);
		$foreign_table = $config['foreign_table'];
		$allowed = $config['allowed'];
		$objectPrefix = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']) . '-' . $foreign_table;
		$nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);
		$mode = 'db';
		$showUpload = FALSE;
		if (!empty($config['appearance']['createNewRelationLinkTitle'])) {
			$createNewRelationText = $this->getLanguageService()->sL($config['appearance']['createNewRelationLinkTitle'], TRUE);
		} else {
			$createNewRelationText = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.createNewRelation', TRUE);
		}
		if (is_array($config['appearance'])) {
			if (isset($config['appearance']['elementBrowserType'])) {
				$mode = $config['appearance']['elementBrowserType'];
			}
			if ($mode === 'file') {
				$showUpload = TRUE;
			}
			if (isset($config['appearance']['fileUploadAllowed'])) {
				$showUpload = (bool)$config['appearance']['fileUploadAllowed'];
			}
			if (isset($config['appearance']['elementBrowserAllowed'])) {
				$allowed = $config['appearance']['elementBrowserAllowed'];
			}
		}
		$browserParams = '|||' . $allowed . '|' . $objectPrefix . '|inline.checkUniqueElement||inline.importElement';
		$onClick = 'setFormValueOpenBrowser(' . GeneralUtility::quoteJSvalue($mode) . ', ' . GeneralUtility::quoteJSvalue($browserParams) . '); return false;';

		$buttonStyle = '';
		if (isset($config['inline']['inlineNewRelationButtonStyle'])) {
			$buttonStyle = ' style="' . $config['inline']['inlineNewRelationButtonStyle'] . '"';
		}

		$item = '
			<a href="#" class="btn btn-default inlineNewRelationButton ' . $this->inlineData['config'][$nameObject]['md5'] . '"
				' . $buttonStyle . ' onclick="' . htmlspecialchars($onClick) . '" title="' . $createNewRelationText . '">
				' . $this->iconFactory->getIcon('actions-insert-record', Icon::SIZE_SMALL) . '
				' . $createNewRelationText . '
			</a>';

		$isDirectFileUploadEnabled = (bool)$this->getBackendUserAuthentication()->uc['edit_docModuleUpload'];
		if ($showUpload && $isDirectFileUploadEnabled) {
			$folder = $backendUser->getDefaultUploadFolder();
			if (
				$folder instanceof Folder
				&& $folder->checkActionPermission('add')
			) {
				$maxFileSize = GeneralUtility::getMaxUploadFileSize() * 1024;
				$item .= ' <a href="#" class="btn btn-default t3js-drag-uploader inlineNewFileUploadButton ' . $this->inlineData['config'][$nameObject]['md5'] . '"
					' . $buttonStyle . '
					data-dropzone-target="#' . htmlspecialchars($this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid'])) . '"
					data-insert-dropzone-before="1"
					data-file-irre-object="' . htmlspecialchars($objectPrefix) . '"
					data-file-allowed="' . htmlspecialchars($allowed) . '"
					data-target-folder="' . htmlspecialchars($folder->getCombinedIdentifier()) . '"
					data-max-file-size="' . htmlspecialchars($maxFileSize) . '"
					><span class="t3-icon t3-icon-actions t3-icon-actions-edit t3-icon-edit-upload">&nbsp;</span>';
				$item .= $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:file_upload.select-and-submit', TRUE);
				$item .= '</a>';
			}
		}

		$item = '<div class="form-control-wrap">' . $item . '</div>';
		$allowedList = '';
		$allowedArray = GeneralUtility::trimExplode(',', $allowed, TRUE);
		$allowedLabel = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.allowedFileExtensions', TRUE);
		foreach ($allowedArray as $allowedItem) {
			$allowedList .= '<span class="label label-success">' . strtoupper($allowedItem) . '</span> ';
		}
		if (!empty($allowedList)) {
			$item .= '<div class="help-block">' . $allowedLabel . '<br>' . $allowedList . '</div>';
		}
		$item = '<div class="form-group t3js-formengine-validation-marker">' . $item . '</div>';
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
	 */
	protected function renderPossibleRecordsSelectorTypeSelect($selItems, $conf, &$PA, $uniqueIds = array()) {
		$foreign_table = $conf['foreign_table'];
		$foreign_selector = $conf['foreign_selector'];
		$PA = array();
		$PA['fieldConf'] = $GLOBALS['TCA'][$foreign_table]['columns'][$foreign_selector];
		$PA['fieldTSConfig'] = FormEngineUtility::getTSconfigForTableRow($foreign_table, array(), $foreign_selector);
		$config = $PA['fieldConf']['config'];
		$item = '';
		// @todo $disabled is not present - should be read from config?
		$disabled = FALSE;
		if (!$disabled) {
			$nameObject = $this->inlineStackProcessor->getCurrentStructureDomObjectIdPrefix($this->data['inlineFirstPid']);;
			// Create option tags:
			$opt = array();
			foreach ($selItems as $p) {
				if (!in_array($p[1], $uniqueIds)) {
					$opt[] = '<option value="' . htmlspecialchars($p[1]) . '">' . htmlspecialchars($p[0]) . '</option>';
				}
			}
			// Put together the selector box:
			$itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : '';
			$size = (int)$conf['size'];
			$size = $conf['autoSizeMax'] ? MathUtility::forceIntegerInRange(count($selItems) + 1, MathUtility::forceIntegerInRange($size, 1), $conf['autoSizeMax']) : $size;
			$onChange = 'return inline.importNewRecord(' . GeneralUtility::quoteJSvalue($nameObject . '-' . $conf['foreign_table']) . ')';
			$item = '
				<select id="' . $nameObject . '-' . $conf['foreign_table'] . '_selector" class="form-control"' . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($onChange) . '"' . $PA['onFocus'] . $itemListStyle . ($conf['foreign_unique'] ? ' isunique="isunique"' : '') . '>
					' . implode('', $opt) . '
				</select>';

			if ($size <= 1) {
				// Add a "Create new relation" link for adding new relations
				// This is necessary, if the size of the selector is "1" or if
				// there is only one record item in the select-box, that is selected by default
				// The selector-box creates a new relation on using an onChange event (see some line above)
				if (!empty($conf['appearance']['createNewRelationLinkTitle'])) {
					$createNewRelationText = $this->getLanguageService()->sL($conf['appearance']['createNewRelationLinkTitle'], TRUE);
				} else {
					$createNewRelationText = $this->getLanguageService()->sL('LLL:EXT:lang/locallang_core.xlf:cm.createNewRelation', TRUE);
				}
				$item .= '
				<span class="input-group-btn">
					<a href="#" class="btn btn-default" onclick="' . htmlspecialchars($onChange) . '" . title="' . $createNewRelationText .'">
						' . $this->iconFactory->getIcon('actions-document-new', Icon::SIZE_SMALL) . $createNewRelationText . '
					</a>
				</span>';
			} else {
				$item .= '
				<span class="input-group-btn btn"></span>';
			}

			// Wrap the selector and add a spacer to the bottom

			$item = '<div class="input-group form-group t3js-formengine-validation-marker ' . $this->inlineData['config'][$nameObject]['md5'] . '">' . $item . '</div>';
		}
		return $item;
	}

	/**
	 * @return BackendUserAuthentication
	 */
	protected function getBackendUserAuthentication() {
		return $GLOBALS['BE_USER'];
	}

	/**
	 * @return LanguageService
	 */
	protected function getLanguageService() {
		return $GLOBALS['LANG'];
	}

}
