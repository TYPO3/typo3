<?php
namespace TYPO3\CMS\Backend\Form;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Backend\Form\Element\InlineElement;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Backend\Utility\IconUtility;
use TYPO3\CMS\Core\Html\HtmlParser;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * 'TCEforms' - Class for creating the backend editing forms.
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor René Fritz <r.fritz@colorcube.de>
 */
class FormEngine {

	// variables not commented yet.... (do so...)
	/**
	 * @todo Define visibility
	 */
	public $palFieldArr = array();

	/**
	 * @todo Define visibility
	 */
	public $disableWizards = 0;

	/**
	 * @todo Define visibility
	 */
	public $isPalettedoc = 0;

	/**
	 * @todo Define visibility
	 */
	public $paletteMargin = 1;

	/**
	 * @todo Define visibility
	 */
	public $defStyle = '';

	/**
	 * @todo Define visibility
	 */
	public $cachedTSconfig = array();

	/**
	 * @todo Define visibility
	 */
	public $cachedTSconfig_fieldLevel = array();

	/**
	 * @todo Define visibility
	 */
	public $cachedLanguageFlag = array();

	/**
	 * @todo Define visibility
	 */
	public $cachedAdditionalPreviewLanguages = NULL;

	/**
	 * Cache for the real PID of a record. The array key consists for a combinded string "<table>:<uid>:<pid>".
	 * The value is an array with two values: first is the real PID of a record, second is the PID value for TSconfig.
	 *
	 * @var array
	 */
	protected $cache_getTSCpid;

	/**
	 * @todo Define visibility
	 */
	public $transformedRow = array();

	/**
	 * @todo Define visibility
	 */
	public $extJSCODE = '';

	/**
	 * @todo Define visibility
	 */
	public $printNeededJS = array();

	/**
	 * @todo Define visibility
	 */
	public $hiddenFieldAccum = array();

	/**
	 * @todo Define visibility
	 */
	public $TBE_EDITOR_fieldChanged_func = '';

	/**
	 * @todo Define visibility
	 */
	public $loadMD5_JS = 1;

	// Something unique...
	/**
	 * @todo Define visibility
	 */
	public $prevBorderStyle = '[nothing here...]';

	// If set direct upload fields will be shown
	/**
	 * @todo Define visibility
	 */
	public $allowUpload = 0;

	// Array where records in the default language is stored. (processed by transferdata)
	/**
	 * @todo Define visibility
	 */
	public $defaultLanguageData = array();

	// Array where records in the default language is stored (raw without any processing. used for making diff)
	/**
	 * @todo Define visibility
	 */
	public $defaultLanguageData_diff = array();

	/**
	 * @todo Define visibility
	 */
	public $additionalPreviewLanguageData = array();

	// EXTERNAL, static
	// Set this to the 'backPath' pointing back to the typo3 admin directory from the script where this form is displayed.
	/**
	 * @todo Define visibility
	 */
	public $backPath = '';

	// Alternative return URL path (default is \TYPO3\CMS\Core\Utility\GeneralUtility::linkThisScript())
	/**
	 * @todo Define visibility
	 */
	public $returnUrl = '';

	// Can be set to point to a field name in the form which will be set to '1' when the form is submitted with a *save* button. This way the recipient script can determine that the form was submitted for save and not "close" for example.
	/**
	 * @todo Define visibility
	 */
	public $doSaveFieldName = '';

	// Can be set TRUE/FALSE to whether palettes (secondary options) are in the topframe or in form. TRUE means they are NOT IN-form. So a collapsed palette is one, which is shown in the top frame, not in the page.
	/**
	 * @todo Define visibility
	 */
	public $palettesCollapsed = 0;

	// If set, the RTE is disabled (from form display, eg. by checkbox in the bottom of the page!)
	/**
	 * @todo Define visibility
	 */
	public $disableRTE = 0;

	// If FALSE, then all CSH will be disabled, regardless of settings in $this->edit_showFieldHelp
	/**
	 * @todo Define visibility
	 */
	public $globalShowHelp = 1;

	// If TRUE, the forms are rendering only localization relevant fields of the records.
	/**
	 * @todo Define visibility
	 */
	public $localizationMode = '';

	// Overrule the field order set in TCA[types][showitem], eg for tt_content this value, 'bodytext,image', would make first the 'bodytext' field, then the 'image' field (if set for display)... and then the rest in the old order.
	/**
	 * @todo Define visibility
	 */
	public $fieldOrder = '';

	// If set to FALSE, palettes will NEVER be rendered.
	/**
	 * @todo Define visibility
	 */
	public $doPrintPalette = 1;

	/**
	 * Set to initialized clipboard object; Then the element browser will offer a link to paste in records from clipboard.
	 *
	 * @var \TYPO3\CMS\Backend\Clipboard\Clipboard
	 * @todo Define visibility
	 */
	public $clipObj = FALSE;

	// Enable click menu on reference icons.
	/**
	 * @todo Define visibility
	 */
	public $enableClickMenu = FALSE;

	// Enable Tab Menus.
	/**
	 * @todo Define visibility
	 */
	public $enableTabMenu = FALSE;

	// When enabled all fields are rendered non-editable.
	/**
	 * @todo Define visibility
	 */
	public $renderReadonly = FALSE;

	// Form field width compensation: Factor of "size=12" to "style="width: 12*9.58px" for form field widths of style-aware browsers
	/**
	 * @todo Define visibility
	 */
	public $form_rowsToStylewidth = 9.58;

	/**
	 * Value that gets added for style="width: ...px" for textareas compared to input fields.
	 *
	 * @var integer
	 */
	protected $form_additionalTextareaStyleWidth = 23;

	// Form field width compensation: Compensation for large documents, doc-tab (editing)
	/**
	 * @todo Define visibility
	 */
	public $form_largeComp = 1.33;

	// The number of chars expected per row when the height of a text area field is automatically calculated based on the number of characters found in the field content.
	/**
	 * @todo Define visibility
	 */
	public $charsPerRow = 40;

	// The maximum abstract value for textareas
	/**
	 * @todo Define visibility
	 */
	public $maxTextareaWidth = 48;

	// The maximum abstract value for input fields
	/**
	 * @todo Define visibility
	 */
	public $maxInputWidth = 48;

	// Default style for the selector boxes used for multiple items in "select" and "group" types.
	/**
	 * @todo Define visibility
	 */
	public $defaultMultipleSelectorStyle = 'width:310px;';

	// INTERNAL, static
	// The string to prepend formfield names with.
	/**
	 * @todo Define visibility
	 */
	public $prependFormFieldNames = 'data';

	// The string to prepend commands for tcemain::process_cmdmap with.
	/**
	 * @todo Define visibility
	 */
	public $prependCmdFieldNames = 'cmd';

	// The string to prepend FILE form field names with.
	/**
	 * @todo Define visibility
	 */
	public $prependFormFieldNames_file = 'data_files';

	/**
	 * The string to prepend form field names that are active (not NULL).
	 *
	 * @var string
	 */
	protected $prependFormFieldNamesActive = 'control[active]';

	// The name attribute of the form.
	/**
	 * @todo Define visibility
	 */
	public $formName = 'editform';

	// Whitelist that allows TCA field configuration to be overridden by TSconfig, @see overrideFieldConf()
	/**
	 * @todo Define visibility
	 */
	public $allowOverrideMatrix = array();

	// INTERNAL, dynamic
	// Set by readPerms()  (caching)
	/**
	 * @todo Define visibility
	 */
	public $perms_clause = '';

	// Set by readPerms()  (caching-flag)
	/**
	 * @todo Define visibility
	 */
	public $perms_clause_set = 0;

	// Used to indicate the mode of CSH (Context Sensitive Help), whether it should be icons-only ('icon') or not at all (blank).
	/**
	 * @todo Define visibility
	 */
	public $edit_showFieldHelp = '';

	/**
	 * @var bool
	 */
	public $edit_docModuleUpload = FALSE;

	// Loaded with info about the browser when class is instantiated.
	/**
	 * @todo Define visibility
	 */
	public $clientInfo = array();

	// TRUE, if RTE is possible for the current user (based on result from BE_USER->isRTE())
	/**
	 * @todo Define visibility
	 */
	public $RTEenabled = 0;

	// If $this->RTEenabled was FALSE, you can find the reasons listed in this array which is filled with reasons why the RTE could not be loaded)
	/**
	 * @todo Define visibility
	 */
	public $RTEenabled_notReasons = '';

	// Counter that is incremented before an RTE is created. Can be used for unique ids etc.
	/**
	 * @todo Define visibility
	 */
	public $RTEcounter = 0;

	// Contains current color scheme
	/**
	 * @todo Define visibility
	 */
	public $colorScheme;

	// Contains current class scheme
	/**
	 * @todo Define visibility
	 */
	public $classScheme;

	// Contains the default color scheme
	/**
	 * @todo Define visibility
	 */
	public $defColorScheme;

	// Contains the default class scheme
	/**
	 * @todo Define visibility
	 */
	public $defClassScheme;

	// Contains field style values
	/**
	 * @todo Define visibility
	 */
	public $fieldStyle;

	// Contains border style values.
	/**
	 * @todo Define visibility
	 */
	public $borderStyle;

	// An accumulation of messages from the class.
	/**
	 * @todo Define visibility
	 */
	public $commentMessages = array();

	// INTERNAL, templates
	// Total wrapping for the table rows.
	/**
	 * @todo Define visibility
	 */
	public $totalWrap = '<hr />|<hr />';

	// Field template
	/**
	 * @todo Define visibility
	 */
	public $fieldTemplate = '<strong>###FIELD_NAME###</strong><br />###FIELD_ITEM###<hr />';

	/**
	 * Template subpart for palette fields.
	 *
	 * @var string
	 */
	protected $paletteFieldTemplate;

	// Wrapping template code for a section
	/**
	 * @todo Define visibility
	 */
	public $sectionWrap = '';

	// Template for palette headers
	/**
	 * @todo Define visibility
	 */
	public $palFieldTemplateHeader = '';

	// Template for palettes
	/**
	 * @todo Define visibility
	 */
	public $palFieldTemplate = '';

	// INTERNAL, working memory
	// Set to the fields NOT to display, if any.
	/**
	 * @todo Define visibility
	 */
	public $excludeElements = '';

	// During rendering of forms this will keep track of which palettes has already been rendered (so they are not rendered twice by mistake)
	/**
	 * @todo Define visibility
	 */
	public $palettesRendered = array();

	// This array of fields will be set as hidden-fields instead of rendered normally! For instance palette fields edited in the top frame are set as hidden fields since the main form has to submit the values. The top frame actually just sets the value in the main form!
	/**
	 * @todo Define visibility
	 */
	public $hiddenFieldListArr = array();

	// Used to register input-field names, which are required. (Done during rendering of the fields). This information is then used later when the JavaScript is made.
	/**
	 * @todo Define visibility
	 */
	public $requiredFields = array();

	// Used to register input-field names, which are required an have additional requirements (e.g. like a date/time must be positive integer). The information of this array is merged with $this->requiredFields later.
	/**
	 * @todo Define visibility
	 */
	public $requiredAdditional = array();

	// Used to register the min and max number of elements for selectorboxes where that apply (in the "group" type for instance)
	/**
	 * @todo Define visibility
	 */
	public $requiredElements = array();

	// Used to determine where $requiredFields or $requiredElements are nested (in Tabs or IRRE)
	/**
	 * @todo Define visibility
	 */
	public $requiredNested = array();

	// Keeps track of the rendering depth of nested records.
	/**
	 * @todo Define visibility
	 */
	public $renderDepth = 0;

	// Color scheme buffer.
	/**
	 * @todo Define visibility
	 */
	public $savedSchemes = array();

	// holds the path an element is nested in (e.g. required for RTEhtmlarea)
	/**
	 * @todo Define visibility
	 */
	public $dynNestedStack = array();

	// Internal, registers for user defined functions etc.
	// Additional HTML code, printed before the form.
	/**
	 * @todo Define visibility
	 */
	public $additionalCode_pre = array();

	// Additional JavaScript, printed before the form
	/**
	 * @todo Define visibility
	 */
	public $additionalJS_pre = array();

	// Additional JavaScript printed after the form
	/**
	 * @todo Define visibility
	 */
	public $additionalJS_post = array();

	// Additional JavaScript executed on submit; If you set "OK" variable it will raise an error about RTEs not being loaded and offer to block further submission.
	/**
	 * @todo Define visibility
	 */
	public $additionalJS_submit = array();

	// Additional JavaScript executed when section element is deleted. This is necessary, for example, to correctly clean up HTMLArea RTE (bug #8232)
	/**
	 * @todo Define visibility
	 */
	public $additionalJS_delete = array();

	/**
	 * @var \TYPO3\CMS\Backend\Form\Element\InlineElement
	 * @todo Define visibility
	 */
	public $inline;

	// Array containing hook class instances called once for a form
	/**
	 * @todo Define visibility
	 */
	public $hookObjectsMainFields = array();

	// Array containing hook class instances called for each field
	/**
	 * @todo Define visibility
	 */
	public $hookObjectsSingleField = array();

	// Rows gettings inserted into the alt_doc headers (when called from alt_doc.php)
	/**
	 * @todo Define visibility
	 */
	public $extraFormHeaders = array();

	public $templateFile = '';

	/**
	 * @var integer
	 */
	protected $multiSelectFilterCount = 0;

	/**
	 * @var \TYPO3\CMS\Backend\Form\Element\SuggestElement
	 */
	protected $suggest;

	// Form templates, relative to typo3 directory
	/**
	 * Constructor function, setting internal variables, loading the styles used.
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function __construct() {
		$this->clientInfo = GeneralUtility::clientInfo();
		$this->RTEenabled = $GLOBALS['BE_USER']->isRTE();
		if (!$this->RTEenabled) {
			$this->RTEenabled_notReasons = implode(LF, $GLOBALS['BE_USER']->RTE_errors);
			$this->commentMessages[] = 'RTE NOT ENABLED IN SYSTEM due to:' . LF . $this->RTEenabled_notReasons;
		}
		// Default color+class scheme
		$this->defColorScheme = array(
			$GLOBALS['SOBE']->doc->bgColor,
			// Background for the field AND palette
			GeneralUtility::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor, -20),
			// Background for the field header
			GeneralUtility::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor, -10),
			// Background for the palette field header
			'black',
			// Field header font color
			'#666666'
		);
		$this->defColorScheme = array();
		// Override / Setting defaults from TBE_STYLES array
		$this->resetSchemes();
		// Setting the current colorScheme to default.
		$this->defColorScheme = $this->colorScheme;
		$this->defClassScheme = $this->classScheme;
		// Define whitelist that allows TCA field configuration to be overridden by TSconfig, @see overrideFieldConf():
		$this->allowOverrideMatrix = array(
			'input' => array('size', 'max', 'readOnly'),
			'text' => array('cols', 'rows', 'wrap', 'readOnly'),
			'check' => array('cols', 'showIfRTE', 'readOnly'),
			'select' => array('size', 'autoSizeMax', 'maxitems', 'minitems', 'readOnly', 'treeConfig'),
			'group' => array('size', 'autoSizeMax', 'max_size', 'show_thumbs', 'maxitems', 'minitems', 'disable_controls', 'readOnly'),
			'inline' => array('appearance', 'behaviour', 'foreign_label', 'foreign_selector', 'foreign_unique', 'maxitems', 'minitems', 'size', 'autoSizeMax', 'symmetric_label', 'readOnly')
		);
		// Create instance of InlineElement only if this a non-IRRE-AJAX call:
		if (!isset($GLOBALS['ajaxID']) || strpos($GLOBALS['ajaxID'], 'TYPO3\\CMS\\Backend\\Form\\Element\\InlineElement::') !== 0) {
			$this->inline = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\Element\\InlineElement');
		}
		// Create instance of \TYPO3\CMS\Backend\Form\Element\SuggestElement only if this a non-Suggest-AJAX call:
		if (!isset($GLOBALS['ajaxID']) || strpos($GLOBALS['ajaxID'], 'TYPO3\\CMS\\Backend\\Form\\Element\\SuggestElement::') !== 0) {
			$this->suggest = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\Element\\SuggestElement');
		}
		// Prepare user defined objects (if any) for hooks which extend this function:
		$this->hookObjectsMainFields = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef) {
				$this->hookObjectsMainFields[] = GeneralUtility::getUserObj($classRef);
			}
		}
		$this->hookObjectsSingleField = array();
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'] as $classRef) {
				$this->hookObjectsSingleField[] = GeneralUtility::getUserObj($classRef);
			}
		}
		$this->templateFile = 'sysext/backend/Resources/Private/Templates/FormEngine.html';
	}

	/**
	 * Initialize various internal variables.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function initDefaultBEmode() {
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		$this->setNewBEDesign();
		$this->edit_showFieldHelp = $GLOBALS['BE_USER']->uc['edit_showFieldHelp'];
		$this->edit_docModuleUpload = (bool)$GLOBALS['BE_USER']->uc['edit_docModuleUpload'];
		$this->inline->init($this);
		$this->suggest->init($this);
	}

	/*******************************************************
	 *
	 * Rendering the forms, fields etc
	 *
	 *******************************************************/
	/**
	 * Will return the TCEform element for just a single field from a record.
	 * The field must be listed in the currently displayed fields (as found in [types][showitem]) for the record.
	 * This also means that the $table/$row supplied must be complete so the list of fields to show can be found correctly
	 *
	 * @param string $table The table name
	 * @param array $row The record from the table for which to render a field.
	 * @param string $theFieldToReturn The field name to return the TCEform element for.
	 * @return string HTML output
	 * @see getMainFields()
	 * @todo Define visibility
	 */
	public function getSoloField($table, $row, $theFieldToReturn) {
		if ($GLOBALS['TCA'][$table]) {
			$typeNum = $this->getRTypeNum($table, $row);
			if ($GLOBALS['TCA'][$table]['types'][$typeNum]) {
				$itemList = $GLOBALS['TCA'][$table]['types'][$typeNum]['showitem'];
				if ($itemList) {
					$fields = GeneralUtility::trimExplode(',', $itemList, TRUE);
					$excludeElements = ($this->excludeElements = $this->getExcludeElements($table, $row, $typeNum));
					foreach ($fields as $fieldInfo) {
						$parts = explode(';', $fieldInfo);
						$theField = trim($parts[0]);
						if (!in_array($theField, $excludeElements) && (string)$theField === (string)$theFieldToReturn) {
							if ($GLOBALS['TCA'][$table]['columns'][$theField]) {
								$sField = $this->getSingleField($table, $theField, $row, $parts[1], 1, $parts[3], $parts[2]);
								return $sField['ITEM'];
							}
						}
					}
				}
			}
		}
	}

	/**
	 * Based on the $table and $row of content, this displays the complete TCEform for the record.
	 * The input-$row is required to be preprocessed if necessary by eg.
	 * the \TYPO3\CMS\Backend\Form\DataPreprocessor class. For instance the RTE content
	 * should be transformed through this class first.
	 *
	 * @param string $table The table name
	 * @param array $row The record from the table for which to render a field.
	 * @param integer $depth Depth level
	 * @param array $overruleTypesArray Overrule types array. Can be used to override the showitem etc. configuration for the TCA types of the table. Can contain all settings which are possible in the TCA 'types' section. See e.g. $TCA['tt_content']['types'].
	 * @return string HTML output
	 * @see getSoloField()
	 * @todo Define visibility
	 */
	public function getMainFields($table, array $row, $depth = 0, array $overruleTypesArray = array()) {
		$this->renderDepth = $depth;
		// Init vars:
		$out_array = array(array());
		$out_array_meta = array(
			array(
				'title' => $this->getLL('l_generalTab')
			)
		);
		$out_pointer = 0;
		$out_sheet = 0;
		$this->palettesRendered = array();
		$this->palettesRendered[$this->renderDepth][$table] = array();
		// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		foreach ($this->hookObjectsMainFields as $hookObj) {
			if (method_exists($hookObj, 'getMainFields_preProcess')) {
				$hookObj->getMainFields_preProcess($table, $row, $this);
			}
		}
		if ($GLOBALS['TCA'][$table]) {
			// Get dividers2tabs setting from TCA of the current table:
			$dividers2tabs = &$GLOBALS['TCA'][$table]['ctrl']['dividers2tabs'];
			// Load the description content for the table.
			if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table)) {
				$GLOBALS['LANG']->loadSingleTableDescription($table);
			}
			// Get the current "type" value for the record.
			$typeNum = $this->getRTypeNum($table, $row);
			// Find the list of fields to display:
			if ($GLOBALS['TCA'][$table]['types'][$typeNum]) {
				$itemList = $GLOBALS['TCA'][$table]['types'][$typeNum]['showitem'];
				if (is_array($overruleTypesArray) && isset($overruleTypesArray[$typeNum]['showitem'])) {
					$itemList = $overruleTypesArray[$typeNum]['showitem'];
				}
				// If such a list existed...
				if ($itemList) {
					// Explode the field list and possibly rearrange the order of the fields, if configured for
					$fields = GeneralUtility::trimExplode(',', $itemList, TRUE);
					if ($this->fieldOrder) {
						$fields = $this->rearrange($fields);
					}
					// Get excluded fields, added fiels and put it together:
					$excludeElements = ($this->excludeElements = $this->getExcludeElements($table, $row, $typeNum));
					$fields = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd($table, $row, $typeNum), $table);
					// If TCEforms will render a tab menu in the next step, push the name to the tab stack:
					$tabIdentString = '';
					$tabIdentStringMD5 = '';
					if (strstr($itemList, '--div--') !== FALSE && $this->enableTabMenu && $dividers2tabs) {
						$tabIdentString = 'TCEforms:' . $table . ':' . $row['uid'];
						$tabIdentStringMD5 = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId($tabIdentString);
						// Remember that were currently working on the general tab:
						if (isset($fields[0]) && strpos($fields[0], '--div--') !== 0) {
							$this->pushToDynNestedStack('tab', $tabIdentStringMD5 . '-1');
						}
					}
					// Traverse the fields to render:
					$cc = 0;
					foreach ($fields as $fieldInfo) {
						// Exploding subparts of the field configuration:
						$parts = explode(';', $fieldInfo);
						// Getting the style information out:
						$color_style_parts = GeneralUtility::trimExplode('-', $parts[4]);
						if ($color_style_parts[0] !== '') {
							$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][(int)$color_style_parts[0]]);
						}
						if ($color_style_parts[1] !== '') {
							$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][(int)$color_style_parts[1]];
							if (!isset($this->fieldStyle)) {
								$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
							}
						}
						if ($color_style_parts[2] !== '') {
							$this->wrapBorder($out_array[$out_sheet], $out_pointer);
							$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][(int)$color_style_parts[2]];
							if (!isset($this->borderStyle)) {
								$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
							}
						}
						// Render the field:
						$theField = $parts[0];
						if (!in_array($theField, $excludeElements)) {
							if ($GLOBALS['TCA'][$table]['columns'][$theField]) {
								$sFieldPal = '';
								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]])) {
									$sFieldPal = $this->getPaletteFields($table, $row, $parts[2]);
									$this->palettesRendered[$this->renderDepth][$table][$parts[2]] = 1;
								}
								$sField = $this->getSingleField($table, $theField, $row, $parts[1], 0, $parts[3], $parts[2]);
								if ($sField) {
									$sField .= $sFieldPal;
								}
								$out_array[$out_sheet][$out_pointer] .= $sField;
							} elseif ($theField == '--div--') {
								if ($cc > 0) {
									$out_array[$out_sheet][$out_pointer] .= $this->getDivider();
									if ($this->enableTabMenu && $dividers2tabs) {
										$this->wrapBorder($out_array[$out_sheet], $out_pointer);
										// Remove last tab entry from the dynNestedStack:
										$out_sheet++;
										// Remove the previous sheet from stack (if any):
										$this->popFromDynNestedStack('tab', $tabIdentStringMD5 . '-' . $out_sheet);
										// Remember on which sheet we're currently working:
										$this->pushToDynNestedStack('tab', $tabIdentStringMD5 . '-' . ($out_sheet + 1));
										$out_array[$out_sheet] = array();
										$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
										// Register newline for Tab
										$out_array_meta[$out_sheet]['newline'] = $parts[2] == 'newline';
									}
								} else {
									// Setting alternative title for "General" tab if "--div--" is the very first element.
									$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
									// Only add the first tab to the dynNestedStack if there are more tabs:
									if ($tabIdentString && strpos($itemList, '--div--', strlen($fieldInfo))) {
										$this->pushToDynNestedStack('tab', $tabIdentStringMD5 . '-1');
									}
								}
							} elseif ($theField == '--palette--') {
								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]])) {
									// Render a 'header' if not collapsed
									if ($GLOBALS['TCA'][$table]['palettes'][$parts[2]]['canNotCollapse'] && $parts[1]) {
										$out_array[$out_sheet][$out_pointer] .= $this->getPaletteFields($table, $row, $parts[2], $this->sL($parts[1]));
									} else {
										$out_array[$out_sheet][$out_pointer] .= $this->getPaletteFields($table, $row, $parts[2], '', '', $this->sL($parts[1]));
									}
									$this->palettesRendered[$this->renderDepth][$table][$parts[2]] = 1;
								}
							}
						}
						$cc++;
					}
				}
			}
		}
		// Hook: getMainFields_postProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		foreach ($this->hookObjectsMainFields as $hookObj) {
			if (method_exists($hookObj, 'getMainFields_postProcess')) {
				$hookObj->getMainFields_postProcess($table, $row, $this);
			}
		}
		// Wrapping a border around it all:
		$this->wrapBorder($out_array[$out_sheet], $out_pointer);
		// Resetting styles:
		$this->resetSchemes();
		// Rendering Main palettes, if any
		$mParr = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['ctrl']['mainpalette']);
		$i = 0;
		if (count($mParr)) {
			foreach ($mParr as $mP) {
				if (!isset($this->palettesRendered[$this->renderDepth][$table][$mP])) {
					$temp_palettesCollapsed = $this->palettesCollapsed;
					$this->palettesCollapsed = 0;
					$label = $i == 0 ? $this->getLL('l_generalOptions') : $this->getLL('l_generalOptions_more');
					$out_array[$out_sheet][$out_pointer] .= $this->getPaletteFields($table, $row, $mP, $label);
					$this->palettesCollapsed = $temp_palettesCollapsed;
					$this->palettesRendered[$this->renderDepth][$table][$mP] = 1;
				}
				$this->wrapBorder($out_array[$out_sheet], $out_pointer);
				$i++;
				if ($this->renderDepth) {
					$this->renderDepth--;
				}
			}
		}
		// Return the imploded $out_array:
		// There were --div-- dividers around...
		if ($out_sheet > 0) {
			// Create parts array for the tab menu:
			$parts = array();
			foreach ($out_array as $idx => $sheetContent) {
				$content = implode('', $sheetContent);
				if ($content) {
					// Wrap content (row) with table-tag, otherwise tab/sheet will be disabled (see getdynTabMenu() )
					$content = '<table border="0" cellspacing="0" cellpadding="0" width="100%">' . $content . '</table>';
				}
				$parts[$idx] = array(
					'label' => $out_array_meta[$idx]['title'],
					'content' => $content,
					'newline' => $out_array_meta[$idx]['newline']
				);
			}
			if (count($parts) > 1) {
				// Unset the current level of tab menus:
				$this->popFromDynNestedStack('tab', $tabIdentStringMD5 . '-' . ($out_sheet + 1));
				$dividersToTabsBehaviour = isset($GLOBALS['TCA'][$table]['ctrl']['dividers2tabs']) ? $GLOBALS['TCA'][$table]['ctrl']['dividers2tabs'] : 1;
				$output = $this->getDynTabMenu($parts, $tabIdentString, $dividersToTabsBehaviour);
			} else {
				// If there is only one tab/part there is no need to wrap it into the dynTab code
				$output = isset($parts[0]) ? trim($parts[0]['content']) : '';
			}
			$output = '
				<tr>
					<td colspan="2">
					' . $output . '
					</td>
				</tr>';
		} else {
			// Only one tab, so just implode and wrap the background image (= tab container) around:
			$output = implode('', $out_array[$out_sheet]);
			if ($this->inline->inlineCount == 0) {
				$output = '<div class="typo3-dyntabmenu-divs">' . $output . '</div>';
			}
		}

		return $output;
	}

	/**
	 * Will return the TCEform elements for a pre-defined list of fields.
	 * Notice that this will STILL use the configuration found in the list [types][showitem] for those fields which are found there. So ideally the list of fields given as argument to this function should also be in the current [types][showitem] list of the record.
	 * Used for displaying forms for the frontend edit icons for instance.
	 *
	 * @param string $table The table name
	 * @param array $row The record array.
	 * @param string $list Commalist of fields from the table. These will be shown in the specified order in a form.
	 * @return string TCEform elements in a string.
	 * @todo Define visibility
	 */
	public function getListedFields($table, $row, $list) {
		if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table)) {
			$GLOBALS['LANG']->loadSingleTableDescription($table);
		}
		$out = '';
		$types_fieldConfig = BackendUtility::getTCAtypes($table, $row, 1);
		$editFieldList = array_unique(GeneralUtility::trimExplode(',', $list, TRUE));
		foreach ($editFieldList as $theFieldC) {
			list($theField, $palFields) = preg_split('/\\[|\\]/', $theFieldC);
			$theField = trim($theField);
			$palFields = trim($palFields);
			if ($GLOBALS['TCA'][$table]['columns'][$theField]) {
				$parts = GeneralUtility::trimExplode(';', $types_fieldConfig[$theField]['origString']);
				// Don't sent palette pointer - there are no options anyways for a field-list.
				$sField = $this->getSingleField($table, $theField, $row, $parts[1], 0, $parts[3], 0);
				$out .= $sField;
			} elseif ($theField == '--div--') {
				$out .= $this->getDivider();
			}
			if ($palFields) {
				$out .= $this->getPaletteFields($table, $row, '', '', implode(',', GeneralUtility::trimExplode('|', $palFields, TRUE)));
			}
		}
		return $out;
	}

	/**
	 * Creates a palette (collection of secondary options).
	 *
	 * @param string $table The table name
	 * @param array $row The row array
	 * @param string $palette The palette number/pointer
	 * @param string $header Header string for the palette (used when in-form). If not set, no header item is made.
	 * @param string $itemList Optional alternative list of fields for the palette
	 * @param string $collapsedHeader Optional Link text for activating a palette (when palettes does not have another form element to belong to).
	 * @return string HTML code.
	 * @todo Define visibility
	 */
	public function getPaletteFields($table, $row, $palette, $header = '', $itemList = '', $collapsedHeader = NULL) {
		if (!$this->doPrintPalette) {
			return '';
		}
		$out = '';
		$parts = $this->loadPaletteElements($table, $row, $palette, $itemList);
		// Put palette together if there are fields in it:
		if (count($parts)) {
			$realFields = 0;
			foreach ($parts as $part) {
				if ($part['NAME'] !== '--linebreak--') {
					$realFields++;
				}
			}
			if ($realFields > 0) {
				if ($header) {
					$out .= $this->intoTemplate(array('HEADER' => htmlspecialchars($header)), $this->palFieldTemplateHeader);
				}
				$collapsed = $this->isPalettesCollapsed($table, $palette);
				// Check if the palette is a hidden palette
				$isHiddenPalette = !empty($GLOBALS['TCA'][$table]['palettes'][$palette]['isHiddenPalette']);
				$thePalIcon = '';
				if ($collapsed && $collapsedHeader !== NULL && !$isHiddenPalette) {
					list($thePalIcon, ) = $this->wrapOpenPalette(IconUtility::getSpriteIcon('actions-system-options-view', array('title' => htmlspecialchars($this->getLL('l_moreOptions')))), $table, $row, $palette, 1);
					$thePalIcon = '<span style="margin-left: 20px;">' . $thePalIcon . $collapsedHeader . '</span>';
				}
				$paletteHtml = $this->wrapPaletteField($this->printPalette($parts), $table, $row, $palette, $collapsed);
				$out .= $this->intoTemplate(array('PALETTE' => $thePalIcon . $paletteHtml), $this->palFieldTemplate);
			}
		}
		return $out;
	}

	/**
	 * Returns the form HTML code for a database table field.
	 *
	 * @param string $table The table name
	 * @param string $field The field name
	 * @param array $row The record to edit from the database table.
	 * @param string $altName Alternative field name label to show.
	 * @param boolean $palette Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param string $extra The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param integer $pal The palette pointer.
	 * @return mixed String (normal) or array (palettes)
	 * @todo Define visibility
	 */
	public function getSingleField($table, $field, $row, $altName = '', $palette = 0, $extra = '', $pal = 0) {
		// Hook: getSingleField_preProcess
		foreach ($this->hookObjectsSingleField as $hookObj) {
			if (method_exists($hookObj, 'getSingleField_preProcess')) {
				$hookObj->getSingleField_preProcess($table, $field, $row, $altName, $palette, $extra, $pal, $this);
			}
		}
		$out = '';
		$PA = array();
		$PA['altName'] = $altName;
		$PA['palette'] = $palette;
		$PA['extra'] = $extra;
		$PA['pal'] = $pal;
		// Get the TCA configuration for the current field:
		$PA['fieldConf'] = $GLOBALS['TCA'][$table]['columns'][$field];
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ?: $PA['fieldConf']['config']['type'];

		// Using "form_type" locally in this script
		$skipThisField = $this->inline->skipField($table, $field, $row, $PA['fieldConf']['config']);

		// Evaluate display condition
		$displayConditionResult = TRUE;
		if (is_array($PA['fieldConf']) && $PA['fieldConf']['displayCond'] && is_array($row)) {
			/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
			$elementConditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\ElementConditionMatcher');
			$displayConditionResult = $elementConditionMatcher->match($PA['fieldConf']['displayCond'], $row);
		}
		// Check if this field is configured and editable (according to excludefields + other configuration)
		if (
			is_array($PA['fieldConf'])
			&& !$skipThisField
			&& (!$PA['fieldConf']['exclude'] || $GLOBALS['BE_USER']->check('non_exclude_fields', $table . ':' . $field))
			&& $PA['fieldConf']['config']['form_type'] != 'passthrough'
			&& ($this->RTEenabled || !$PA['fieldConf']['config']['showIfRTE'])
			&& $displayConditionResult
			&& (!$GLOBALS['TCA'][$table]['ctrl']['languageField'] || $PA['fieldConf']['l10n_display'] || ($PA['fieldConf']['l10n_mode'] !== 'exclude') || $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] <= 0)
			&& (!$GLOBALS['TCA'][$table]['ctrl']['languageField'] || !$this->localizationMode || $this->localizationMode === $PA['fieldConf']['l10n_cat'])
		) {
			// Fetching the TSconfig for the current table/field. This includes the $row which means that
			$PA['fieldTSConfig'] = $this->setTSconfig($table, $row, $field);
			// If the field is NOT disabled from TSconfig (which it could have been) then render it
			if (!$PA['fieldTSConfig']['disabled']) {
				// Override fieldConf by fieldTSconfig:
				$PA['fieldConf']['config'] = $this->overrideFieldConf($PA['fieldConf']['config'], $PA['fieldTSConfig']);
				// Init variables:
				$PA['itemFormElName'] = $this->prependFormFieldNames . '[' . $table . '][' . $row['uid'] . '][' . $field . ']';
				// Form field name, in case of file uploads
				$PA['itemFormElName_file'] = $this->prependFormFieldNames_file . '[' . $table . '][' . $row['uid'] . '][' . $field . ']';
				// Form field name, to activate elements
				// If the "eval" list contains "null", elements can be deactivated which results in storing NULL to database
				$PA['itemFormElNameActive'] = $this->prependFormFieldNamesActive . '[' . $table . '][' . $row['uid'] . '][' . $field . ']';
				// The value to show in the form field.
				$PA['itemFormElValue'] = $row[$field];
				$PA['itemFormElID'] = $this->prependFormFieldNames . '_' . $table . '_' . $row['uid'] . '_' . $field;
				// Set field to read-only if configured for translated records to show default language content as readonly
				if ($PA['fieldConf']['l10n_display'] && GeneralUtility::inList($PA['fieldConf']['l10n_display'], 'defaultAsReadonly') && $row[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0) {
					$PA['fieldConf']['config']['readOnly'] = TRUE;
					$PA['itemFormElValue'] = $this->defaultLanguageData[$table . ':' . $row['uid']][$field];
				}
				if (strpos($GLOBALS['TCA'][$table]['ctrl']['type'], ':') === FALSE) {
					$typeField = $GLOBALS['TCA'][$table]['ctrl']['type'];
				} else {
					$typeField = substr($GLOBALS['TCA'][$table]['ctrl']['type'], 0, strpos($GLOBALS['TCA'][$table]['ctrl']['type'], ':'));
				}
				// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (
					!empty($GLOBALS['TCA'][$table]['ctrl']['type'])
					&& $field === $typeField
					|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'])
					&& GeneralUtility::inList(str_replace(' ', '', $GLOBALS['TCA'][$table]['ctrl']['requestUpdate']), $field)
				) {
					if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
						$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
					}
				} else {
					$alertMsgOnChange = '';
				}
				// Render as a hidden field?
				if (in_array($field, $this->hiddenFieldListArr)) {
					$this->hiddenFieldAccum[] = '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
				} else {
					// Render as a normal field:
					// If the field is NOT a palette field, then we might create an icon which links to a palette for the field, if one exists.
					if (!$PA['palette']) {
						$paletteFields = $this->loadPaletteElements($table, $row, $PA['pal']);
						if ($PA['pal'] && $this->isPalettesCollapsed($table, $PA['pal']) && count($paletteFields)) {
							list($thePalIcon, $palJSfunc) = $this->wrapOpenPalette(IconUtility::getSpriteIcon('actions-system-options-view', array('title' => htmlspecialchars($this->getLL('l_moreOptions')))), $table, $row, $PA['pal'], 1);
						} else {
							$thePalIcon = '';
							$palJSfunc = '';
						}
					}
					// onFocus attribute to add to the field:
					$PA['onFocus'] = $palJSfunc && !$GLOBALS['BE_USER']->uc['dontShowPalettesOnFocusInAB'] ? ' onfocus="' . htmlspecialchars($palJSfunc) . '"' : '';
					// Find item
					$item = '';
					$PA['label'] = $PA['altName'] ?: $PA['fieldConf']['label'];
					$PA['label'] = $PA['fieldTSConfig']['label'] ?: $PA['label'];
					$PA['label'] = $PA['fieldTSConfig']['label.'][$GLOBALS['LANG']->lang] ?: $PA['label'];
					$PA['label'] = $this->sL($PA['label']);
					// JavaScript code for event handlers:
					$PA['fieldChangeFunc'] = array();
					$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'TBE_EDITOR.fieldChanged(\'' . $table . '\',\'' . $row['uid'] . '\',\'' . $field . '\',\'' . $PA['itemFormElName'] . '\');';
					$PA['fieldChangeFunc']['alert'] = $alertMsgOnChange;
					// If this is the child of an inline type and it is the field creating the label
					if ($this->inline->isInlineChildAndLabelField($table, $field)) {
						$inlineObjectId = implode(InlineElement::Structure_Separator, array(
							$this->inline->inlineNames['object'],
							$table,
							$row['uid']
						));
						$PA['fieldChangeFunc']['inline'] = 'inline.handleChangedField(\'' . $PA['itemFormElName'] . '\',\'' . $inlineObjectId . '\');';
					}
					// Based on the type of the item, call a render function:
					$item = $this->getSingleField_SW($table, $field, $row, $PA);
					// Add language + diff
					if ($PA['fieldConf']['l10n_display'] && (GeneralUtility::inList($PA['fieldConf']['l10n_display'], 'hideDiff') || GeneralUtility::inList($PA['fieldConf']['l10n_display'], 'defaultAsReadonly'))) {
						$renderLanguageDiff = FALSE;
					} else {
						$renderLanguageDiff = TRUE;
					}
					if ($renderLanguageDiff) {
						$item = $this->renderDefaultLanguageContent($table, $field, $row, $item);
						$item = $this->renderDefaultLanguageDiff($table, $field, $row, $item);
					}
					// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$label = htmlspecialchars($PA['label'], ENT_COMPAT, 'UTF-8', FALSE);
					if (MathUtility::canBeInterpretedAsInteger($row['uid']) && $PA['fieldTSConfig']['linkTitleToSelf'] && !GeneralUtility::_GP('columnsOnly')) {
						$lTTS_url = $this->backPath . 'alt_doc.php?edit[' . $table . '][' . $row['uid'] . ']=edit&columnsOnly=' . $field . '&returnUrl=' . rawurlencode($this->thisReturnUrl());
						$label = '<a href="' . htmlspecialchars($lTTS_url) . '">' . $label . '</a>';
					}

					if (isset($PA['fieldConf']['config']['mode']) && $PA['fieldConf']['config']['mode'] == 'useOrOverridePlaceholder') {
						$placeholder = $this->getPlaceholderValue($table, $field, $PA['fieldConf']['config'], $row);
						$onChange = 'typo3form.fieldTogglePlaceholder(' . GeneralUtility::quoteJSvalue($PA['itemFormElName']) . ', !this.checked)';
						$checked = $PA['itemFormElValue'] === NULL ? '' : ' checked="checked"';

						$this->additionalJS_post[] = 'typo3form.fieldTogglePlaceholder('
							. GeneralUtility::quoteJSvalue($PA['itemFormElName']) . ', ' . ($checked ? 'false' : 'true') . ');';

						$item = '<div class="t3-form-field-placeholder-override">'
						. '<span class="t3-tceforms-placeholder-override-checkbox">' .
							'<input type="hidden" name="' . htmlspecialchars($PA['itemFormElNameActive']) . '" value="0" />' .
							'<input type="checkbox" name="' . htmlspecialchars($PA['itemFormElNameActive']) . '" value="1" id="tce-forms-textfield-use-override-' . $field . '-' . $row['uid'] . '" onchange="' . htmlspecialchars($onChange) . '"' . $checked . ' />' .
							'<label for="tce-forms-textfield-use-override-' . $field . '-' . $row['uid'] . '">' .
							sprintf($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.placeholder.override'),
							BackendUtility::getRecordTitlePrep($placeholder, 20)) . '</label>' .
						'</span>'
						. '<div class="t3-form-placeholder-placeholder">' . $this->getSingleField_typeNone_render(
							$PA['fieldConf']['config'], GeneralUtility::fixed_lgd_cs($placeholder, 30)
						) . '</div>'
						. '<div class="t3-form-placeholder-formfield">' . $item . '</div>'
						. '</div>';
					}

					// Wrap the label with help text
					$PA['label'] = ($label = BackendUtility::wrapInHelp($table, $field, $label));
					// Create output value:
					if ($PA['fieldConf']['config']['form_type'] == 'user' && $PA['fieldConf']['config']['noTableWrapping']) {
						$out = $item;
					} elseif ($PA['palette']) {
						// Array:
						$out = array(
							'NAME' => $label,
							'ID' => $row['uid'],
							'FIELD' => $field,
							'TABLE' => $table,
							'ITEM' => $item,
							'ITEM_DISABLED' => ($this->isDisabledNullValueField($table, $field, $row, $PA) ? ' disabled' : ''),
							'ITEM_NULLVALUE' => $this->renderNullValueWidget($table, $field, $row, $PA),
						);
						$out = $this->addUserTemplateMarkers($out, $table, $field, $row, $PA);
					} else {
						// String:
						$out = array(
							'NAME' => $label,
							'ITEM' => $item,
							'TABLE' => $table,
							'ID' => $row['uid'],
							'PAL_LINK_ICON' => $thePalIcon,
							'FIELD' => $field,
							'ITEM_DISABLED' => ($this->isDisabledNullValueField($table, $field, $row, $PA) ? ' disabled' : ''),
							'ITEM_NULLVALUE' => $this->renderNullValueWidget($table, $field, $row, $PA),
						);
						$out = $this->addUserTemplateMarkers($out, $table, $field, $row, $PA);
						// String:
						$out = $this->intoTemplate($out);
					}
				}
			} else {
				$this->commentMessages[] = $this->prependFormFieldNames . '[' . $table . '][' . $row['uid'] . '][' . $field . ']: Disabled by TSconfig';
			}
		}
		// Hook: getSingleField_postProcess
		foreach ($this->hookObjectsSingleField as $hookObj) {
			if (method_exists($hookObj, 'getSingleField_postProcess')) {
				$hookObj->getSingleField_postProcess($table, $field, $row, $out, $PA, $this);
			}
		}
		// Return value (string or array)
		return $out;
	}

	/**
	 * Rendering a single item for the form
	 *
	 * @param string $table Table name of record
	 * @param string $field Fieldname to render
	 * @param array $row The record
	 * @param array $PA Parameters array containing a lot of stuff. Value by Reference!
	 * @return string Returns the item as HTML code to insert
	 * @access private
	 * @see getSingleField(), getSingleField_typeFlex_draw()
	 * @todo Define visibility
	 */
	public function getSingleField_SW($table, $field, $row, &$PA) {
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ?: $PA['fieldConf']['config']['type'];
		// Using "form_type" locally in this script
		// Hook: getSingleField_beforeRender
		foreach ($this->hookObjectsSingleField as $hookObject) {
			if (method_exists($hookObject, 'getSingleField_beforeRender')) {
				$hookObject->getSingleField_beforeRender($table, $field, $row, $PA);
			}
		}
		switch ($PA['fieldConf']['config']['form_type']) {
			case 'input':
				$item = $this->getSingleField_typeInput($table, $field, $row, $PA);
				break;
			case 'text':
				$item = $this->getSingleField_typeText($table, $field, $row, $PA);
				break;
			case 'check':
				$item = $this->getSingleField_typeCheck($table, $field, $row, $PA);
				break;
			case 'radio':
				$item = $this->getSingleField_typeRadio($table, $field, $row, $PA);
				break;
			case 'select':
				$item = $this->getSingleField_typeSelect($table, $field, $row, $PA);
				break;
			case 'group':
				$item = $this->getSingleField_typeGroup($table, $field, $row, $PA);
				break;
			case 'inline':
				$item = $this->inline->getSingleField_typeInline($table, $field, $row, $PA);
				break;
			case 'none':
				$item = $this->getSingleField_typeNone($table, $field, $row, $PA);
				break;
			case 'user':
				$item = $this->getSingleField_typeUser($table, $field, $row, $PA);
				break;
			case 'flex':
				$item = $this->getSingleField_typeFlex($table, $field, $row, $PA);
				break;
			default:
				$item = $this->getSingleField_typeUnknown($table, $field, $row, $PA);
		}
		return $item;
	}

	/**********************************************************
	 *
	 * Rendering of each TCEform field type
	 *
	 ************************************************************/
	/**
	 * Generation of TCEform elements of the type "input"
	 * This will render a single-line input form field, possibly with various control/validation features
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeInput($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$size = MathUtility::forceIntegerInRange($config['size'] ? $config['size'] : 30, 5, $this->maxInputWidth);
		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		$classAndStyleAttributes = $this->formWidthAsArray($size);
		$fieldAppendix = '';
		$item = '';
		$cssClasses = array($classAndStyleAttributes['class']);
		$cssStyle = $classAndStyleAttributes['style'];
		if (!isset($config['checkbox'])) {
			$config['checkbox'] = '0';
			$checkboxIsset = FALSE;
		} else {
			$checkboxIsset = TRUE;
		}
		if (in_array('date', $evalList) || in_array('datetime', $evalList)) {
			if (in_array('datetime', $evalList)) {
				$class = 'datetime';
			} else {
				$class = 'date';
			}
			$dateRange = '';
			if (isset($config['range']['lower'])) {
				$dateRange .= ' lower-' . (int)$config['range']['lower'];
			}
			if (isset($config['range']['upper'])) {
				$dateRange .= ' upper-' . (int)$config['range']['upper'];
			}
			$inputId = uniqid('tceforms-' . $class . 'field-');
			$cssClasses[] = 'tceforms-textfield tceforms-' . $class . 'field' . $dateRange;
			$fieldAppendix = IconUtility::getSpriteIcon('actions-edit-pick-date', array(
				'style' => 'cursor:pointer;',
				'id' => 'picker-' . $inputId
			));
		} elseif (in_array('timesec', $evalList)) {
			$inputId = uniqid('tceforms-timesecfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-timesecfield';
		} elseif (in_array('year', $evalList)) {
			$inputId = uniqid('tceforms-yearfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-yearfield';
		} elseif (in_array('time', $evalList)) {
			$inputId = uniqid('tceforms-timefield-');
			$cssClasses[] = 'tceforms-textfield tceforms-timefield';
		} elseif (in_array('int', $evalList)) {
			$inputId = uniqid('tceforms-intfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-intfield';
		} elseif (in_array('double2', $evalList)) {
			$inputId = uniqid('tceforms-double2field-');
			$cssClasses[] = 'tceforms-textfield tceforms-double2field';
		} else {
			$inputId = uniqid('tceforms-textfield-');
			$cssClasses[] = 'tceforms-textfield';
			if ($checkboxIsset === FALSE) {
				$config['checkbox'] = '';
			}
		}
		if (isset($config['wizards']['link'])) {
			$inputId = uniqid('tceforms-linkfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-linkfield';
		} elseif (isset($config['wizards']['color'])) {
			$inputId = uniqid('tceforms-colorfield-');
			$cssClasses[] = 'tceforms-textfield tceforms-colorfield';
		}
		if ($this->renderReadonly || $config['readOnly']) {
			$itemFormElValue = $PA['itemFormElValue'];
			if (in_array('date', $evalList)) {
				$config['format'] = 'date';
			} elseif (in_array('datetime', $evalList)) {
				$config['format'] = 'datetime';
			} elseif (in_array('time', $evalList)) {
				$config['format'] = 'time';
			}
			if (in_array('password', $evalList)) {
				$itemFormElValue = $itemFormElValue ? '*********' : '';
			}
			return $this->getSingleField_typeNone_render($config, $itemFormElValue);
		}
		foreach ($evalList as $func) {
			switch ($func) {
				case 'required':
					$this->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
					// Mark this field for date/time disposal:
					if (array_intersect($evalList, array('date', 'datetime', 'time'))) {
						$this->requiredAdditional[$PA['itemFormElName']]['isPositiveNumber'] = TRUE;
					}
					break;
				default:
					// Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval()
					$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
					if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
						$_params = array(
							'value' => $PA['itemFormElValue']
						);
						$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
					}
			}
		}
		$paramsList = '\'' . $PA['itemFormElName'] . '\',\'' . implode(',', $evalList) . '\',\'' . trim($config['is_in']) . '\',' . (isset($config['checkbox']) ? 1 : 0) . ',\'' . $config['checkbox'] . '\'';
		if (in_array('date', $evalList) || in_array('datetime', $evalList)) {
			$item .= '<span class="t3-tceforms-input-wrapper-datetime" onmouseOver="if (document.getElementById(\'' . $inputId . '\').value) {this.className=\'t3-tceforms-input-wrapper-datetime-hover\';} else {this.className=\'t3-tceforms-input-wrapper-datetime\';};" onmouseOut="this.className=\'t3-tceforms-input-wrapper-datetime\';">';
			// Add server timezone offset to UTC to our stored date
			if ($PA['itemFormElValue'] > 0) {
				$PA['itemFormElValue'] += date('Z', $PA['itemFormElValue']);
			}
		} else {
			$item .= '<span class="t3-tceforms-input-wrapper" onmouseOver="if (document.getElementById(\'' . $inputId . '\').value) {this.className=\'t3-tceforms-input-wrapper-hover\';} else {this.className=\'t3-tceforms-input-wrapper\';};" onmouseOut="this.className=\'t3-tceforms-input-wrapper\';">';
		}
		$PA['fieldChangeFunc'] = array_merge(array('typo3form.fieldGet' => 'typo3form.fieldGet(' . $paramsList . ');'), $PA['fieldChangeFunc']);
		// Old function "checkbox" now the option to set the date / remove the date
		if (isset($config['checkbox'])) {
			$item .= IconUtility::getSpriteIcon('actions-input-clear', array('tag' => 'a', 'class' => 't3-tceforms-input-clearer', 'onclick' => 'document.getElementById(\'' . $inputId . '\').value=\'\';document.getElementById(\'' . $inputId . '\').focus();' . implode('', $PA['fieldChangeFunc'])));
		}
		$mLgd = $config['max'] ?: 256;
		$iOnChange = implode('', $PA['fieldChangeFunc']);
		$cssClasses[] = 'hasDefaultValue';
		$item .= '<input type="text" ' . $this->getPlaceholderAttribute($table, $field, $config, $row) . 'id="' . $inputId . '" ' . 'class="' . implode(' ', $cssClasses) . '" ' . 'name="' . $PA['itemFormElName'] . '_hr" ' . 'value=""' . 'style="' . $cssStyle . '" ' . 'maxlength="' . $mLgd . '" ' . 'onchange="' . htmlspecialchars($iOnChange) . '"' . $PA['onFocus'] . ' />';
		// This is the EDITABLE form field.
		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
		// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$item .= $fieldAppendix . '</span><div style="clear:both;"></div>';
		$this->extJSCODE .= 'typo3form.fieldSet(' . $paramsList . ');';
		// Going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData] . ':&' . $evalData);
			if (is_object($evalObj) && method_exists($evalObj, 'returnFieldJS')) {
				$this->extJSCODE .= '
TBE_EDITOR.customEvalFunctions[\'' . $evalData . '\'] = function(value) {
' . $evalObj->returnFieldJS() . '
}
';
			}
		}
		// Creating an alternative item without the JavaScript handlers.
		$altItem = '<input type="hidden" name="' . $PA['itemFormElName'] . '_hr" value="" />';
		$altItem .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
		// Wrap a wizard around the item?
		$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'] . '_hr', $specConf);

		return $item;
	}

	/**
	 * Renders a view widget to handle and activate NULL values.
	 * The widget is enabled by using 'null' in the 'eval' TCA definition.
	 *
	 * @param string $table Name of the table
	 * @param string $field Name of the field
	 * @param array $row Accordant data of the record row
	 * @param array $PA Parameters array with rendering instructions
	 * @return string Widget (if any).
	 */
	protected function renderNullValueWidget($table, $field, array $row, array $PA) {
		$widget = '';

		$config = $PA['fieldConf']['config'];
		if (!empty($config['eval']) && GeneralUtility::inList($config['eval'], 'null') && (empty($config['mode']) || $config['mode'] !== 'useOrOverridePlaceholder')) {
			$isNull = ($PA['itemFormElValue'] === NULL);

			$checked = ($isNull ? '' : ' checked="checked"');
			$onChange = htmlspecialchars(
				'typo3form.fieldSetNull(\'' . $PA['itemFormElName'] . '\', !this.checked)'
			);

			$widget = '<span class="t3-tceforms-widget-null-wrapper">' .
				'<input type="hidden" name="' . $PA['itemFormElNameActive'] . '" value="0" />' .
				'<input type="checkbox" name="' . $PA['itemFormElNameActive'] . '" value="1" onchange="' . $onChange . '"' . $checked . ' />' .
			'</span>';
		}

		return $widget;
	}

	/**
	 * Determines whether the current field value is considered as NULL value.
	 * Using NULL values is enabled by using 'null' in the 'eval' TCA definition.
	 *
	 * @param string $table Name of the table
	 * @param string $field Name of the field
	 * @param array $row Accordant data
	 * @param array $PA Parameters array with rendering instructions
	 * @return boolean
	 */
	protected function isDisabledNullValueField($table, $field, array $row, array $PA) {
		$result = FALSE;

		$config = $PA['fieldConf']['config'];
		if ($PA['itemFormElValue'] === NULL && !empty($config['eval'])
			&& GeneralUtility::inList($config['eval'], 'null')
			&& (empty($config['mode']) || $config['mode'] !== 'useOrOverridePlaceholder')) {

			$result = TRUE;
		}

		return $result;
	}

	/**
	 * Generation of TCEform elements of the type "text"
	 * This will render a <textarea> OR RTE area form field, possibly with various control/validation features
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeText($table, $field, $row, &$PA) {
		// Init config:
		$config = $PA['fieldConf']['config'];
		$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
		if ($this->renderReadonly || $config['readOnly']) {
			return $this->getSingleField_typeNone_render($config, $PA['itemFormElValue']);
		}
		// Setting columns number:
		$cols = MathUtility::forceIntegerInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
		// Setting number of rows:
		$origRows = ($rows = MathUtility::forceIntegerInRange($config['rows'] ? $config['rows'] : 5, 1, 20));
		if (strlen($PA['itemFormElValue']) > $this->charsPerRow * 2) {
			$cols = $this->maxTextareaWidth;
			$rows = MathUtility::forceIntegerInRange(round(strlen($PA['itemFormElValue']) / $this->charsPerRow), count(explode(LF, $PA['itemFormElValue'])), 20);
			if ($rows < $origRows) {
				$rows = $origRows;
			}
		}
		if (in_array('required', $evalList)) {
			$this->requiredFields[$table . '_' . $row['uid'] . '_' . $field] = $PA['itemFormElName'];
		}
		// Init RTE vars:
		// Set TRUE, if the RTE is loaded; If not a normal textarea is shown.
		$RTEwasLoaded = 0;
		// Set TRUE, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...
		$RTEwouldHaveBeenLoaded = 0;
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
		// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($this->RTEenabled) {
			$p = BackendUtility::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			// If the field is configured for RTE and if any flag-field is not set to disable it.
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) {
				BackendUtility::fixVersioningPid($table, $row);
				list($tscPID, $thePidValue) = $this->getTSCpid($table, $row['uid'], $row['pid']);
				// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($thePidValue >= 0) {
					$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', BackendUtility::getPagesTSconfig($tscPID));
					$RTEtypeVal = BackendUtility::getTCAtypeValue($table, $row);
					$thisConfig = BackendUtility::RTEsetup($RTEsetup['properties'], $table, $field, $RTEtypeVal);
					if (!$thisConfig['disabled']) {
						if (!$this->disableRTE) {
							$this->RTEcounter++;
							// Find alternative relative path for RTE images/links:
							$eFile = \TYPO3\CMS\Core\Html\RteHtmlParser::evalWriteFile($specConf['static_write'], $row);
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';
							// Get RTE object, draw form and set flag:
							$RTEobj = BackendUtility::RTEgetObj();
							$item = $RTEobj->drawRTE($this, $table, $field, $row, $PA, $specConf, $thisConfig, $RTEtypeVal, $RTErelPath, $thePidValue);
							// Wizard:
							$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf, 1);
							$RTEwasLoaded = 1;
						} else {
							$RTEwouldHaveBeenLoaded = 1;
							$this->commentMessages[] = $PA['itemFormElName'] . ': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
						}
					} else {
						$this->commentMessages[] = $PA['itemFormElName'] . ': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
					}
				} else {
					$this->commentMessages[] = $PA['itemFormElName'] . ': PID value could NOT be fetched. Rare error, normally with new records.';
				}
			} else {
				if (!isset($specConf['richtext'])) {
					$this->commentMessages[] = $PA['itemFormElName'] . ': RTE was not configured for this field in TCA-types';
				}
				if (!(!$p['flag'] || !$row[$p['flag']])) {
					$this->commentMessages[] = $PA['itemFormElName'] . ': Field-flag (' . $PA['flag'] . ') has been set to disable RTE!';
				}
			}
		}
		// Display ordinary field if RTE was not loaded.
		if (!$RTEwasLoaded) {
			// Show message, if no RTE (field can only be edited with RTE!)
			if ($specConf['rte_only']) {
				$item = '<p><em>' . htmlspecialchars($this->getLL('l_noRTEfound')) . '</em></p>';
			} else {
				if ($specConf['nowrap']) {
					$wrap = 'off';
				} else {
					$wrap = $config['wrap'] ?: 'virtual';
				}
				$classes = array();
				if ($specConf['fixed-font']) {
					$classes[] = 'fixed-font';
				}
				if ($specConf['enable-tab']) {
					$classes[] = 'enable-tab';
				}
				$formWidthText = $this->formWidthText($cols, $wrap);
				// Extract class attributes from $formWidthText (otherwise it would be added twice to the output)
				$res = array();
				if (preg_match('/ class="(.+?)"/', $formWidthText, $res)) {
					$formWidthText = str_replace(' class="' . $res[1] . '"', '', $formWidthText);
					$classes = array_merge($classes, explode(' ', $res[1]));
				}
				if (count($classes)) {
					$class = ' class="tceforms-textarea ' . implode(' ', $classes) . '"';
				} else {
					$class = 'tceforms-textarea';
				}
				$evalList = GeneralUtility::trimExplode(',', $config['eval'], TRUE);
				foreach ($evalList as $func) {
					switch ($func) {
						case 'required':
							$this->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
							break;
						default:
							// Pair hook to the one in \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_input_Eval() and \TYPO3\CMS\Core\DataHandling\DataHandler::checkValue_text_Eval()
							$evalObj = GeneralUtility::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
							if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
								$_params = array(
									'value' => $PA['itemFormElValue']
								);
								$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
							}
					}
				}
				$iOnChange = implode('', $PA['fieldChangeFunc']);
				$item .= '
							<textarea ' . 'id="' . uniqid('tceforms-textarea-') . '" ' . 'name="' . $PA['itemFormElName'] . '"' . $formWidthText . $class . ' ' . 'rows="' . $rows . '" ' . 'wrap="' . $wrap . '" ' . 'onchange="' . htmlspecialchars($iOnChange) . '"' . $this->getPlaceholderAttribute($table, $field, $config, $row) . $PA['onFocus'] . '>' . GeneralUtility::formatForTextarea($PA['itemFormElValue']) . '</textarea>';
				$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf, $RTEwouldHaveBeenLoaded);
			}
		}
		// Return field HTML:
		return $item;
	}

	/**
	 * Generation of TCEform elements of the type "check"
	 * This will render a check-box OR an array of checkboxes
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeCheck($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traversing the array of items:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) {
			$selItems = $this->procItems($selItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
		}
		if (!count($selItems)) {
			$selItems[] = array('', '');
		}
		$thisValue = (int)$PA['itemFormElValue'];
		$cols = (int)$config['cols'];
		$selItemsCount = count($selItems);
		if ($cols > 1) {
			$item .= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c = 0; $c < $selItemsCount; $c++) {
				$p = $selItems[$c];
				if (!($c % $cols)) {
					$item .= '<tr>';
				}
				$cBP = $this->checkBoxParams($PA['itemFormElName'], $thisValue, $c, count($selItems), implode('', $PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'] . '_' . $c;
				$cBID = $PA['itemFormElID'] . '_' . $c;
				$item .= '<td nowrap="nowrap">' . '<input type="checkbox"' . $this->insertDefStyle('check') . ' value="1" name="' . $cBName . '"' . $cBP . $disabled . ' id="' . $cBID . '" />' . $this->wrapLabels(('<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>&nbsp;')) . '</td>';
				if ($c % $cols + 1 == $cols) {
					$item .= '</tr>';
				}
			}
			if ($c % $cols) {
				$rest = $cols - $c % $cols;
				for ($c = 0; $c < $rest; $c++) {
					$item .= '<td></td>';
				}
				if ($c > 0) {
					$item .= '</tr>';
				}
			}
			$item .= '</table>';
		} else {
			for ($c = 0; $c < $selItemsCount; $c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($PA['itemFormElName'], $thisValue, $c, count($selItems), implode('', $PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'] . '_' . $c;
				$cBID = $PA['itemFormElID'] . '_' . $c;
				$item .= ($c > 0 ? '<br />' : '') . '<input type="checkbox"' . $this->insertDefStyle('check') . ' value="1" name="' . $cBName . '"' . $cBP . $PA['onFocus'] . $disabled . ' id="' . $cBID . '" />' . $this->wrapLabels(('<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>'));
			}
		}
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($thisValue) . '" />';
		}
		return $item;
	}

	/**
	 * Generation of TCEform elements of the type "radio"
	 * This will render a series of radio buttons.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeRadio($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Get items for the array:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) {
			$selItems = $this->procItems($selItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
		}
		// Traverse the items, making the form elements:
		$selItemsCount = count($selItems);
		for ($c = 0; $c < $selItemsCount; $c++) {
			$p = $selItems[$c];
			$rID = $PA['itemFormElID'] . '_' . $c;
			$rOnClick = implode('', $PA['fieldChangeFunc']);
			$rChecked = (string)$p[1] === (string)$PA['itemFormElValue'] ? ' checked="checked"' : '';
			$item .= '<input type="radio"' . $this->insertDefStyle('radio') . ' name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($p[1]) . '" onclick="' . htmlspecialchars($rOnClick) . '"' . $rChecked . $PA['onFocus'] . $disabled . ' id="' . $rID . '" />
					<label for="' . $rID . '">' . htmlspecialchars($p[0]) . '</label>
					<br />';
		}
		return $item;
	}

	/**
	 * Generation of TCEform elements of the type "select"
	 * This will render a selector box element, or possibly a special construction with two selector boxes. That depends on configuration.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeSelect($table, $field, $row, &$PA) {
		// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$selItems = $this->getSelectItems($table, $field, $row, $PA);

		// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ ' . $this->getLL('l_noMatchingValue') . ' ]';
		// Prepare some values:
		$maxitems = (int)$config['maxitems'];
		// If a SINGLE selector box...
		if ($maxitems <= 1 && $config['renderMode'] !== 'tree') {
			$item = $this->getSingleField_typeSelect_single($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'checkbox') {
			// Checkbox renderMode
			$item = $this->getSingleField_typeSelect_checkbox($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'singlebox') {
			// Single selector box renderMode
			$item = $this->getSingleField_typeSelect_singlebox($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif ($config['renderMode'] === 'tree') {
			// Tree renderMode
			$treeClass = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\Element\\TreeElement', $this);
			$item = $treeClass->renderField($table, $field, $row, $PA, $config, $selItems, $nMV_label);
			// Register the required number of elements
			$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
			$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		} else {
			// Traditional multiple selector box:
			$item = $this->getSingleField_typeSelect_multiple($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		}
		// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
			$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf);
		}
		return $item;
	}

	/**
	 * Collects the items for a select field by reading the configured
	 * select items from the configuration and / or by collecting them
	 * from a foreign table.
	 *
	 * @param string $table The table name of the record
	 * @param string $fieldName The select field name
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return array
	 */
	public function getSelectItems($table, $fieldName, array $row, array $PA) {
		$config = $PA['fieldConf']['config'];

		// Getting the selector box items from the system
		$selectItems = $this->addSelectOptionsToItemArray(
			$this->initItemArray($PA['fieldConf']),
			$PA['fieldConf'],
			$this->setTSconfig($table, $row),
			$fieldName
		);

		// Possibly filter some items:
		$selectItems = GeneralUtility::keepItemsInArray(
			$selectItems,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				return $value[1];
			}
		);

		// Possibly add some items:
		$selectItems = $this->addItems($selectItems, $PA['fieldTSConfig']['addItems.']);

		// Process items by a user function:
		if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
			$selectItems = $this->procItems($selectItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $fieldName);
		}

		// Possibly remove some items:
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		foreach ($selectItems as $selectItemIndex => $selectItem) {

			// Checking languages and authMode:
			$languageDeny = FALSE;
			if (
				!empty($GLOBALS['TCA'][$table]['ctrl']['languageField'])
				&& $GLOBALS['TCA'][$table]['ctrl']['languageField'] === $fieldName
				&& !$GLOBALS['BE_USER']->checkLanguageAccess($selectItem[1])
			) {
				$languageDeny = TRUE;
			}

			$authModeDeny = FALSE;
			if (
				($config['form_type'] === 'select')
				&& $config['authMode']
				&& !$GLOBALS['BE_USER']->checkAuthMode($table, $fieldName, $selectItem[1], $config['authMode'])
			) {
				$authModeDeny = TRUE;
			}

			if (in_array($selectItem[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($selectItems[$selectItemIndex]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$selectItem[1]])) {
				$selectItems[$selectItemIndex][0] = htmlspecialchars($this->sL($PA['fieldTSConfig']['altLabels.'][$selectItem[1]]));
			}

			// Removing doktypes with no access:
			if (($table === 'pages' || $table === 'pages_language_overlay') && $fieldName === 'doktype') {
				if (!($GLOBALS['BE_USER']->isAdmin() || GeneralUtility::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'], $selectItem[1]))) {
					unset($selectItems[$selectItemIndex]);
				}
			}
		}

		return $selectItems;
	}

	/**
	 * Creates a single-selector box
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 * @todo Define visibility
	 */
	public function getSingleField_typeSelect_single($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		// check against inline uniqueness
		$inlineParent = $this->inline->getStructureLevel(-1);
		if (is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->inline->inlineData['unique'][$this->inline->inlineNames['object'] . InlineElement::Structure_Separator . $table]['used'];
				$PA['fieldChangeFunc']['inlineUnique'] = 'inline.updateUnique(this,\'' . $this->inline->inlineNames['object'] . InlineElement::Structure_Separator . $table . '\',\'' . $this->inline->inlineNames['form'] . '\',\'' . $row['uid'] . '\');';
			}
			// hide uid of parent record for symmetric relations
			if ($inlineParent['config']['foreign_table'] == $table && ($inlineParent['config']['foreign_field'] == $field || $inlineParent['config']['symmetric_field'] == $field)) {
				$uniqueIds[] = $inlineParent['uid'];
			}
		}
		// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = (int)$config['size'];
		// Style set on <select/>
		$selectedStyle = '';
		$item = '';
		$disabled = '';
		// TODO: icon is used but never assigned
		$onChangeIcon = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
		}
		// Register as required if minitems is greater than zero
		if (($minItems = MathUtility::forceIntegerInRange($config['minitems'], 0)) > 0) {
			$this->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
		}

		// Icon configuration:
		if ($config['suppress_icons'] == 'IF_VALUE_FALSE') {
			$suppressIcons = !$PA['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons'] == 'ONLY_SELECTED') {
			$suppressIcons = 0;
			$onlySelectedIconShown = 1;
		} elseif ($config['suppress_icons']) {
			$suppressIcons = 1;
		} else {
			$suppressIcons = 0;
		}
		// Traverse the Array of selector box items:
		$optGroupStart = array();
		$optGroupOpen = FALSE;
		$classesForSelectTag = array();
		foreach ($selItems as $p) {
			$sM = (string)$PA['itemFormElValue'] === (string)$p[1] ? ' selected="selected"' : '';
			if ($sM) {
				$sI = $c;
				$noMatchingValue = 0;
			}
			// Getting style attribute value (for icons):
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->optionTagStyle($p[2]);
				if ($sM) {
					list($selectIconFile, $selectIconInfo) = $this->getIcon($p[2]);
					if (!empty($selectIconInfo)) {
						$selectedStyle = ' style="background-image:url(' . $selectIconFile . ');"';
						$classesForSelectTag[] = 'typo3-TCEforms-select-selectedItemWithBackgroundImage';
					}
				}
			}
			// Compiling the <option> tag:
			if (!($p[1] != $PA['itemFormElValue'] && is_array($uniqueIds) && in_array($p[1], $uniqueIds))) {
				if ($p[1] === '--div--') {
					$optGroupStart[0] = $p[0];
					if ($config['iconsInOptionTags']) {
						$optGroupStart[1] = $this->optgroupTagStyle($p[2]);
					} else {
						$optGroupStart[1] = $styleAttrValue;
					}
				} else {
					if (count($optGroupStart)) {
						// Closing last optgroup before next one starts
						if ($optGroupOpen) {
							$opt[] = '</optgroup>' . LF;
						}
						$opt[] = '<optgroup label="' . htmlspecialchars($optGroupStart[0], ENT_COMPAT, 'UTF-8', FALSE) . '"' . ($optGroupStart[1] ? ' style="' . htmlspecialchars($optGroupStart[1]) . '"' : '') . ' class="c-divider">' . LF;
						$optGroupOpen = TRUE;
						$c--;
						$optGroupStart = array();
					}
					$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . $sM . ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . '>' . htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE) . '</option>' . LF;
				}
			}
			// If there is an icon for the selector box (rendered in selicon-table below)...:
			// if there is an icon ($p[2]), icons should be shown, and, if only selected are visible, is it selected
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM)) {
				list($selIconFile, $selIconInfo) = $this->getIcon($p[2]);
				if (!empty($selIconInfo)) {
					$iOnClick = $this->elName($PA['itemFormElName']) . '.selectedIndex=' . $c . '; ' . $this->elName($PA['itemFormElName']) . '.style.backgroundImage=' . $this->elName($PA['itemFormElName']) . '.options[' . $c . '].style.backgroundImage; ' . implode('', $PA['fieldChangeFunc']) . 'this.blur(); return false;';
				} else {
					$iOnClick = $this->elName($PA['itemFormElName']) . '.selectedIndex=' . $c . '; ' . $this->elName($PA['itemFormElName']) . '.className=' . $this->elName($PA['itemFormElName']) . '.options[' . $c . '].className; ' . implode('', $PA['fieldChangeFunc']) . 'this.blur(); return false;';
				}
				$selicons[] = array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="' . htmlspecialchars($iOnClick) . '">' : '') . $this->getIconHtml($p[2], htmlspecialchars($p[0]), htmlspecialchars($p[0])) . (!$onlySelectedIconShown ? '</a>' : ''),
					$c,
					$sM
				);
			}
			$c++;
		}
		// Closing optgroup if open
		if ($optGroupOpen) {
			$opt[] = '</optgroup>';
			$optGroupOpen = FALSE;
		}
		// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);
			$opt[] = '<option value="' . htmlspecialchars($PA['itemFormElValue']) . '" selected="selected">' . htmlspecialchars($nMV_label) . '</option>';
		}
		// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=' . $sI . ';} ' . implode('', $PA['fieldChangeFunc']);
		if (!$disabled) {
			// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_selIconVal" value="' . htmlspecialchars($sI) . '" />';
		}
		if ($config['iconsInOptionTags']) {
			$classesForSelectTag[] = 'icon-select';
		}
		$item .= '<select' . $selectedStyle . ' id="' . uniqid('tceforms-select-') . '" name="' . $PA['itemFormElName'] . '"' . $this->insertDefStyle('select', implode(' ', $classesForSelectTag)) . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars(($onChangeIcon . $sOnChange)) . '"' . $PA['onFocus'] . $disabled . '>';
		$item .= implode('', $opt);
		$item .= '</select>';
		// Create icon table:
		if (count($selicons) && !$config['noIconsBelowSelect']) {
			$item .= '<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = (int)$config['selicon_cols'];
			if (!$selicon_cols) {
				$selicon_cols = count($selicons);
			}
			$sR = ceil(count($selicons) / $selicon_cols);
			$selicons = array_pad($selicons, $sR * $selicon_cols, '');
			for ($sa = 0; $sa < $sR; $sa++) {
				$item .= '<tr>';
				for ($sb = 0; $sb < $selicon_cols; $sb++) {
					$sk = $sa * $selicon_cols + $sb;
					$imgN = 'selIcon_' . $table . '_' . $row['uid'] . '_' . $field . '_' . $selicons[$sk][1];
					$imgS = $selicons[$sk][2] ? $this->backPath . 'gfx/content_selected.gif' : 'clear.gif';
					$item .= '<td><img name="' . htmlspecialchars($imgN) . '" src="' . $imgS . '" width="7" height="10" alt="" /></td>';
					$item .= '<td>' . $selicons[$sk][0] . '</td>';
				}
				$item .= '</tr>';
			}
			$item .= '</table>';
		}
		return $item;
	}

	/**
	 * Creates a checkbox list (renderMode = "checkbox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 * @todo Define visibility
	 */
	public function getSingleField_typeSelect_checkbox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		if (empty($selItems)) {
			return '';
		}
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traverse the Array of selector box items:
		$tRows = array();
		$c = 0;
		if (!$disabled) {
			$sOnChange = implode('', $PA['fieldChangeFunc']);
			// Used to accumulate the JS needed to restore the original selection.
			$setAll = array();
			$unSetAll = array();
			foreach ($selItems as $p) {
				// Non-selectable element:
				if ($p[1] === '--div--') {
					$selIcon = '';
					if (isset($p[2]) && $p[2] != 'empty-emtpy') {
						$selIcon = $this->getIconHtml($p[2]);
					}
					$tRows[] = '
						<tr class="c-header">
							<td colspan="3">' . $selIcon . htmlspecialchars($p[0]) . '</td>
						</tr>';
				} else {
					// Selected or not by default:
					$sM = '';
					if (isset($itemArray[$p[1]])) {
						$sM = ' checked="checked"';
						unset($itemArray[$p[1]]);
					}
					// Icon:
					if ($p[2]) {
						$selIcon = $p[2];
					} else {
						$selIcon = IconUtility::getSpriteIcon('empty-empty');
					}
					// Compile row:
					$rowId = uniqid('select_checkbox_row_');
					$onClickCell = $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=!' . $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = ' . $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked ? "c-selectedItem" : "c-unselectedItem";';
					$setAll[] = $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=1;';
					$setAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');$(\'' . $rowId . '\').addClassName(\'c-selectedItem\');';
					$unSetAll[] = $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=0;';
					$unSetAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').addClassName(\'c-unselectedItem\');';
					$restoreCmd[] = $this->elName(($PA['itemFormElName'] . '[' . $c . ']')) . '.checked=' . ($sM ? 1 : 0) . ';' . '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');' . '$(\'' . $rowId . '\').addClassName(\'c-' . ($sM ? '' : 'un') . 'selectedItem\');';
					// Check if some help text is available
					// Since TYPO3 4.5 help text is expected to be an associative array
					// with two key, "title" and "description"
					// For the sake of backwards compatibility, we test if the help text
					// is a string and use it as a description (this could happen if items
					// are modified with an itemProcFunc)
					$hasHelp = FALSE;
					$help = '';
					$helpArray = array();
					if (is_array($p[3]) && count($p[3]) > 0 || !empty($p[3])) {
						$hasHelp = TRUE;
						if (is_array($p[3])) {
							$helpArray = $p[3];
						} else {
							$helpArray['description'] = $p[3];
						}
					}
					$label = htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE);
					if ($hasHelp) {
						$help = BackendUtility::wrapInHelp('', '', '', $helpArray);
					}
					$tRows[] = '
						<tr id="' . $rowId . '" class="' . ($sM ? 'c-selectedItem' : 'c-unselectedItem') . '" onclick="' . htmlspecialchars($onClick) . '" style="cursor: pointer;">
							<td class="c-checkbox"><input type="checkbox"' . $this->insertDefStyle('check') . ' name="' . htmlspecialchars(($PA['itemFormElName'] . '[' . $c . ']')) . '" value="' . htmlspecialchars($p[1]) . '"' . $sM . ' onclick="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . ' /></td>
							<td class="c-labelCell" onclick="' . htmlspecialchars($onClickCell) . '">' . $this->getIconHtml($selIcon) . $label . '</td>
								<td class="c-descr" onclick="' . htmlspecialchars($onClickCell) . '">' . (empty($help) ? '' : $help) . '</td>
						</tr>';
					$c++;
				}
			}
		}
		// Remaining values (invalid):
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <checkboxes> tag:
				array_unshift($tRows, '
						<tr class="c-invalidItem">
							<td class="c-checkbox"><input type="checkbox"' . $this->insertDefStyle('check') . ' name="' . htmlspecialchars(($PA['itemFormElName'] . '[' . $c . ']')) . '" value="' . htmlspecialchars($theNoMatchValue) . '" checked="checked" onclick="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . $disabled . ' /></td>
							<td class="c-labelCell">' . htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</td><td>&nbsp;</td>
						</tr>');
				$c++;
			}
		}
		// Add an empty hidden field which will send a blank value if all items are unselected.
		$item .= '<input type="hidden" class="select-checkbox" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';
		// Remaining checkboxes will get their set-all link:
		if (count($setAll)) {
			$tableHead = '<thead>
					<tr class="c-header-checkbox-controls t3-row-header">
						<td class="c-checkbox">
						<input type="checkbox" class="checkbox" onclick="if (checked) {' . htmlspecialchars((implode('', $setAll) . '} else {' . implode('', $unSetAll) . '}')) . '">
						</td>
						<td colspan="2">
						</td>
					</tr></thead>';
		}
		// Implode rows in table:
		$item .= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">' . $tableHead . '<tbody>' . implode('', $tRows) . '</tbody>
			</table>
			';
		// Add revert icon
		if (is_array($restoreCmd)) {
			$item .= '<a href="#" onclick="' . implode('', $restoreCmd) . ' return false;' . '">' . IconUtility::getSpriteIcon('actions-edit-undo', array('title' => htmlspecialchars($this->getLL('l_revertSelection')))) . '</a>';
		}
		return $item;
	}

	/**
	 * Creates a selectorbox list (renderMode = "singlebox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 * @todo Define visibility
	 */
	public function getSingleField_typeSelect_singlebox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Traverse the Array of selector box items:
		$opt = array();
		// Used to accumulate the JS needed to restore the original selection.
		$restoreCmd = array();
		$c = 0;
		foreach ($selItems as $p) {
			// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]])) {
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->elName(($PA['itemFormElName'] . '[]')) . '.options[' . $c . '].selected=1;';
				unset($itemArray[$p[1]]);
			}
			// Non-selectable element:
			$nonSel = '';
			if ((string) $p[1] === '--div--') {
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}
			// Icon style for option tag:
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->optionTagStyle($p[2]);
			}
			// Compile <option> tag:
			$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . $sM . $nonSel . ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . '>' . htmlspecialchars($p[0], ENT_COMPAT, 'UTF-8', FALSE) . '</option>';
			$c++;
		}
		// Remaining values:
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
				// Compile <option> tag:
				array_unshift($opt, '<option value="' . htmlspecialchars($theNoMatchValue) . '" selected="selected">' . htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue), ENT_COMPAT, 'UTF-8', FALSE) . '</option>');
			}
		}
		// Compile selector box:
		$sOnChange = implode('', $PA['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"';
		$size = (int)$config['size'];
		$cssPrefix = $size === 1 ? 'tceforms-select' : 'tceforms-multiselect';
		$size = $config['autoSizeMax'] ? MathUtility::forceIntegerInRange(count($selItems) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax']) : $size;
		$selectBox = '<select id="' . uniqid($cssPrefix) . '" name="' . $PA['itemFormElName'] . '[]"' . $this->insertDefStyle('select', $cssPrefix) . ($size ? ' size="' . $size . '"' : '') . ' multiple="multiple" onchange="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . $selector_itemListStyle . $disabled . '>
						' . implode('
						', $opt) . '
					</select>';
		// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';
		}
		// Put it all into a table:
		$item .= '
			<table border="0" cellspacing="0" cellpadding="0" width="1" class="typo3-TCEforms-select-singlebox">
				<tr>
					<td>
					' . $selectBox . '
					<br/>
					<em>' . htmlspecialchars($this->getLL('l_holdDownCTRL')) . '</em>
					</td>
					<td valign="top">
						<a href="#" onclick="' . htmlspecialchars(($this->elName(($PA['itemFormElName'] . '[]')) . '.selectedIndex=-1;' . implode('', $restoreCmd) . ' return false;')) . '" title="' . htmlspecialchars($this->getLL('l_revertSelection')) . '">' . IconUtility::getSpriteIcon('actions-edit-undo') . '</a>
					</td>
				</tr>
			</table>
				';
		return $item;
	}

	/**
	 * Creates a multiple-selector box (two boxes, side-by-side)
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param string $table See getSingleField_typeSelect()
	 * @param string $field See getSingleField_typeSelect()
	 * @param array $row See getSingleField_typeSelect()
	 * @param array $PA See getSingleField_typeSelect()
	 * @param array $config (Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param array $selItems Items available for selection
	 * @param string $nMV_label Label for no-matching-value
	 * @return string The HTML code for the item
	 * @see getSingleField_typeSelect()
	 * @todo Define visibility
	 */
	public function getSingleField_typeSelect_multiple($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '" />';
		}
		// Set max and min items:
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		// Register the required number of elements:
		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		// Get "removeItems":
		$removeItems = GeneralUtility::trimExplode(',', $PA['fieldTSConfig']['removeItems'], TRUE);
		// Get the array with selected items:
		$itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);

		// Possibly filter some items:
		$itemArray = GeneralUtility::keepItemsInArray(
			$itemArray,
			$PA['fieldTSConfig']['keepItems'],
			function ($value) {
				$parts = explode('|', $value, 2);
				return rawurldecode($parts[0]);
			}
		);

		// Perform modification of the selected items array:
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$evalValue = $tvP[0];
			$isRemoved = in_array($evalValue, $removeItems) || $config['form_type'] == 'select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table, $field, $evalValue, $config['authMode']);
			if ($isRemoved && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$evalValue])) {
				$tvP[1] = rawurlencode($this->sL($PA['fieldTSConfig']['altLabels.'][$evalValue]));
			}
			if ($tvP[1] == '') {
				// Case: flexform, default values supplied, no label provided (bug #9795)
				foreach ($selItems as $selItem) {
					if ($selItem[1] == $tvP[0]) {
						$tvP[1] = html_entity_decode($selItem[0]);
						break;
					}
				}
			}
			$itemArray[$tk] = implode('|', $tvP);
		}
		$itemsToSelect = '';
		$filterTextfield = '';
		$filterSelectbox = '';
		if (!$disabled) {
			// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach ($selItems as $p) {
				if ($config['iconsInOptionTags']) {
					$styleAttrValue = $this->optionTagStyle($p[2]);
				}
				$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' . ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') . ' title="' . $p[0] . '">' . $p[0] . '</option>';
			}
			// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"';
			$size = (int)$config['size'];
			$size = $config['autoSizeMax'] ? MathUtility::forceIntegerInRange(count($itemArray) + 1, MathUtility::forceIntegerInRange($size, 1), $config['autoSizeMax']) : $size;
			$sOnChange = implode('', $PA['fieldChangeFunc']);

			$multiSelectId = uniqid('tceforms-multiselect-');
			$itemsToSelect = '
				<select data-relatedfieldname="' . htmlspecialchars($PA['itemFormElName']) . '" data-exclusivevalues="' . htmlspecialchars($config['exclusiveKeys']) . '" id="' . $multiSelectId . '" name="' . $PA['itemFormElName'] . '_sel"' . $this->insertDefStyle('select', 'tceforms-multiselect tceforms-itemstoselect t3-form-select-itemstoselect') . ($size ? ' size="' . $size . '"' : '') . ' onchange="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . $selector_itemListStyle . '>
					' . implode('
					', $opt) . '
				</select>';

			if ($config['enableMultiSelectFilterTextfield'] || $config['multiSelectFilterItems']) {
				$this->multiSelectFilterCount++;
				$jsSelectBoxFilterName = str_replace(' ', '', ucwords(str_replace('-', ' ', GeneralUtility::strtolower($multiSelectId))));
				$this->additionalJS_post[] = '
					var '. $jsSelectBoxFilterName . ' = new TCEForms.SelectBoxFilter("' . $multiSelectId . '");
				';
			}

			if ($config['enableMultiSelectFilterTextfield']) {
				// add input field for filter
				$filterTextfield = '<input class="typo3-TCEforms-suggest-search typo3-TCEforms-multiselect-filter" id="' . $multiSelectId . '_filtertextfield" value="" style="width: 104px;" />';
			}

			if (isset($config['multiSelectFilterItems']) && is_array($config['multiSelectFilterItems']) && count($config['multiSelectFilterItems']) > 1) {
				$filterDropDownOptions = array();
				foreach ($config['multiSelectFilterItems'] as $optionElement) {
					$filterDropDownOptions[] = '<option value="' . htmlspecialchars($this->sL($optionElement[0])) . '">' . htmlspecialchars((isset($optionElement[1]) && $optionElement[1] != '') ? $this->sL($optionElement[1]) : $this->sL($optionElement[0])) . '</option>';
				}
				$filterSelectbox = '
					<select id="' . $multiSelectId . '_filterdropdown">
						' . implode('
						', $filterDropDownOptions) . '
					</select>';
			}
		}
		// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
			'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
			'dontShowMoveIcons' => $maxitems <= 1,
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->getLL('l_selected') . ':<br />',
				'items' => $this->getLL('l_items') . ': ' . $filterSelectbox . $filterTextfield . '<br />'
			),
			'noBrowser' => 1,
			'thumbnails' => $itemsToSelect,
			'readOnly' => $disabled
		);
		$item .= $this->dbFileIcons($PA['itemFormElName'], '', '', $itemArray, '', $params, $PA['onFocus']);
		return $item;
	}

	/**
	 * Generation of TCEform elements of the type "group"
	 * This will render a selectorbox into which elements from either the file system or database can be inserted. Relations.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeGroup($table, $field, $row, &$PA) {
		// Init:
		$config = $PA['fieldConf']['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = isset($config['size']) ? (int)$config['size'] : 5;
		$maxitems = MathUtility::forceIntegerInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = MathUtility::forceIntegerInRange($config['minitems'], 0);
		$allowed = trim($config['allowed']);
		$disallowed = trim($config['disallowed']);
		$item = '';
		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';
		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		$info = '';
		// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$PA['itemFormElID_file'] = $PA['itemFormElID'] . '_files';
		// whether the list and delete controls should be disabled
		$noList = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'list');
		$noDelete = isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'delete');
		// if maxitems==1 then automatically replace the current item (in list and file selector)
		if ($maxitems === 1) {
			$this->additionalJS_post[] = 'TBE_EDITOR.clearBeforeSettingFormValueFromBrowseWin[\'' . $PA['itemFormElName'] . '\'] = {
					itemFormElID_file: \'' . $PA['itemFormElID_file'] . '\'
				}';
			$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(\'' . $PA['itemFormElName'] . '\', \'Remove\'); ' . $PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		} elseif ($noList) {
			// If the list controls have been removed and the maximum number is reached, remove the first entry to avoid "write once" field
			$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = 'setFormValueManipulate(\'' . $PA['itemFormElName'] . '\', \'RemoveFirstIfFull\', \'' . $maxitems . '\'); ' . $PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'];
		}
		// Acting according to either "file" or "db" type:
		switch ((string) $config['internal_type']) {
		case 'file_reference':
			$config['uploadfolder'] = '';
			// Fall through
		case 'file':
			// Creating string showing allowed types:
			$tempFT = GeneralUtility::trimExplode(',', $allowed, TRUE);
			if (!count($tempFT)) {
				$info .= '*';
			}
			foreach ($tempFT as $ext) {
				if ($ext) {
					$info .= strtoupper($ext) . ' ';
				}
			}
			// Creating string, showing disallowed types:
			$tempFT_dis = GeneralUtility::trimExplode(',', $disallowed, TRUE);
			if (count($tempFT_dis)) {
				$info .= '<br />';
			}
			foreach ($tempFT_dis as $ext) {
				if ($ext) {
					$info .= '-' . strtoupper($ext) . ' ';
				}
			}
			// Making the array of file items:
			$itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);
			$fileFactory = \TYPO3\CMS\Core\Resource\ResourceFactory::getInstance();
			// Correct the filename for the FAL items
			foreach ($itemArray as &$fileItem) {
				list($fileUid, $fileLabel) = explode('|', $fileItem);
				if (MathUtility::canBeInterpretedAsInteger($fileUid)) {
					$fileObject = $fileFactory->getFileObject($fileUid);
					$fileLabel = $fileObject->getName();
				}
				$fileItem = $fileUid . '|' . $fileLabel;
			}
			// Showing thumbnails:
			$thumbsnail = '';
			if ($show_thumbs) {
				$imgs = array();
				foreach ($itemArray as $imgRead) {
					$imgP = explode('|', $imgRead);
					$imgPath = rawurldecode($imgP[0]);
					// FAL icon production
					if (MathUtility::canBeInterpretedAsInteger($imgP[0])) {
						$fileObject = $fileFactory->getFileObject($imgP[0]);

						if ($fileObject->isMissing()) {
							$flashMessage = \TYPO3\CMS\Core\Resource\Utility\BackendUtility::getFlashMessageForMissingFile($fileObject);
							$imgs[] = $flashMessage->render();
						} elseif (GeneralUtility::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'], $fileObject->getExtension())) {
							$imageUrl = $fileObject->process(\TYPO3\CMS\Core\Resource\ProcessedFile::CONTEXT_IMAGEPREVIEW, array())->getPublicUrl(TRUE);
							$imgTag = '<img src="' . $imageUrl . '" alt="' . htmlspecialchars($fileObject->getName()) . '" />';
							$imgs[] = '<span class="nobr">' . $imgTag . htmlspecialchars($fileObject->getName()) . '</span>';
						} else {
							// Icon
							$imgTag = IconUtility::getSpriteIconForResource($fileObject, array('title' => $fileObject->getName()));
							$imgs[] = '<span class="nobr">' . $imgTag . htmlspecialchars($fileObject->getName()) . '</span>';
						}
					} else {
						$rowCopy = array();
						$rowCopy[$field] = $imgPath;
						$thumbnailCode = '';
						try {
							$thumbnailCode = BackendUtility::thumbCode(
								$rowCopy, $table, $field, $this->backPath, 'thumbs.php',
								$config['uploadfolder'], 0, ' align="middle"'
							);
							$thumbnailCode = '<span class="nobr">' . $thumbnailCode . $imgPath . '</span>';

						} catch (\Exception $exception) {
							/** @var $flashMessage FlashMessage */
							$message = $exception->getMessage();
							$flashMessage = GeneralUtility::makeInstance(
								'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
								htmlspecialchars($message), '', FlashMessage::ERROR, TRUE
							);
							$class = 'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService';
							/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
							$flashMessageService = GeneralUtility::makeInstance($class);
							$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
							$defaultFlashMessageQueue->enqueue($flashMessage);

							$logMessage = $message . ' (' . $table . ':' . $row['uid'] . ')';
							GeneralUtility::sysLog($logMessage, 'core', GeneralUtility::SYSLOG_SEVERITY_WARNING);
						}

						$imgs[] = $thumbnailCode;
					}
				}
				$thumbsnail = implode('<br />', $imgs);
			}
			// Creating the element:
			$params = array(
				'size' => $size,
				'dontShowMoveIcons' => $maxitems <= 1,
				'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
				'maxitems' => $maxitems,
				'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
				'info' => $info,
				'thumbnails' => $thumbsnail,
				'readOnly' => $disabled,
				'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
				'noList' => $noList,
				'noDelete' => $noDelete
			);
			$item .= $this->dbFileIcons($PA['itemFormElName'], 'file', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus'], '', '', '', $config);
			if (!$disabled && !(isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'upload'))) {
				// Adding the upload field:
				if ($this->edit_docModuleUpload && $config['uploadfolder']) {
					// Insert the multiple attribute to enable HTML5 multiple file upload
					$multipleAttribute = '';
					$multipleFilenameSuffix = '';
					if (isset($config['maxitems']) && $config['maxitems'] > 1) {
						$multipleAttribute = ' multiple="multiple"';
						$multipleFilenameSuffix = '[]';
					}
					$item .= '<div id="' . $PA['itemFormElID_file'] . '"><input type="file"' . $multipleAttribute . ' name="' . $PA['itemFormElName_file'] . $multipleFilenameSuffix . '" size="35" onchange="' . implode('', $PA['fieldChangeFunc']) . '" /></div>';
				}
			}
			break;
		case 'folder':
			// If the element is of the internal type "folder":
			// Array of folder items:
			$itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);
			// Creating the element:
			$params = array(
				'size' => $size,
				'dontShowMoveIcons' => $maxitems <= 1,
				'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
				'maxitems' => $maxitems,
				'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
				'info' => $info,
				'readOnly' => $disabled,
				'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
				'noList' => $noList
			);
			$item .= $this->dbFileIcons($PA['itemFormElName'], 'folder', '', $itemArray, '', $params, $PA['onFocus']);
			break;
		case 'db':
			// If the element is of the internal type "db":
			// Creating string showing allowed types:
			$tempFT = GeneralUtility::trimExplode(',', $allowed, TRUE);
			if (trim($tempFT[0]) === '*') {
				$onlySingleTableAllowed = FALSE;
				$info .= '<span class="nobr">' . htmlspecialchars($this->getLL('l_allTables')) . '</span><br />';
			} elseif ($tempFT) {
				$onlySingleTableAllowed = count($tempFT) == 1;
				foreach ($tempFT as $theT) {
					$aOnClick = 'setFormValueOpenBrowser(\'db\', \'' . ($PA['itemFormElName'] . '|||' . $theT) . '\'); return false;';
					$info .= '<span class="nobr">
									<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . IconUtility::getSpriteIconForRecord($theT, array()) . htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])) . '</a></span><br />';
				}
			}
			$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$itemArray = array();
			$imgs = array();
			// Thumbnails:
			$temp_itemArray = GeneralUtility::trimExplode(',', $PA['itemFormElValue'], TRUE);
			foreach ($temp_itemArray as $dbRead) {
				$recordParts = explode('|', $dbRead);
				list($this_table, $this_uid) = BackendUtility::splitTable_Uid($recordParts[0]);
				// For the case that no table was found and only a single table is defined to be allowed, use that one:
				if (!$this_table && $onlySingleTableAllowed) {
					$this_table = $allowed;
				}
				$itemArray[] = array('table' => $this_table, 'id' => $this_uid);
				if (!$disabled && $show_thumbs) {
					$rr = BackendUtility::getRecordWSOL($this_table, $this_uid);
					$imgs[] = '<span class="nobr">' . $this->getClickMenu(IconUtility::getSpriteIconForRecord($this_table, $rr, array(
						'style' => 'vertical-align:top',
						'title' => htmlspecialchars((BackendUtility::getRecordPath($rr['pid'], $perms_clause, 15) . ' [UID: ' . $rr['uid'] . ']'))
					)), $this_table, $this_uid) . '&nbsp;' . BackendUtility::getRecordTitle($this_table, $rr, TRUE) . ' <span class="typo3-dimmed"><em>[' . $rr['uid'] . ']</em></span>' . '</span>';
				}
			}
			$thumbsnail = '';
			if (!$disabled && $show_thumbs) {
				$thumbsnail = implode('<br />', $imgs);
			}
			// Creating the element:
			$params = array(
				'size' => $size,
				'dontShowMoveIcons' => $maxitems <= 1,
				'autoSizeMax' => MathUtility::forceIntegerInRange($config['autoSizeMax'], 0),
				'maxitems' => $maxitems,
				'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
				'info' => $info,
				'thumbnails' => $thumbsnail,
				'readOnly' => $disabled,
				'noBrowser' => $noList || isset($config['disable_controls']) && GeneralUtility::inList($config['disable_controls'], 'browser'),
				'noList' => $noList
			);
			$item .= $this->dbFileIcons($PA['itemFormElName'], 'db', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus'], $table, $field, $row['uid'], $config);
			break;
		}
		// Wizards:
		$altItem = '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';
		if (!$disabled) {
			$item = $this->renderWizards(array($item, $altItem), $config['wizards'], $table, $row, $field, $PA, $PA['itemFormElName'], $specConf);
		}
		return $item;
	}

	/**
	 * Generation of TCEform elements of the type "none"
	 * This will render a non-editable display of the content of the field.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeNone($table, $field, $row, &$PA) {
		// Init:
		$config = $PA['fieldConf']['config'];
		$itemValue = $PA['itemFormElValue'];
		return $this->getSingleField_typeNone_render($config, $itemValue);
	}

	/**
	 * HTML rendering of a value which is not editable.
	 *
	 * @param array $config Configuration for the display
	 * @param string $itemValue The value to display
	 * @return string The HTML code for the display
	 * @see getSingleField_typeNone();
	 * @todo Define visibility
	 */
	public function getSingleField_typeNone_render($config, $itemValue) {
		if ($config['format']) {
			$itemValue = $this->formatValue($config, $itemValue);
		}
		$rows = (int)$config['rows'];
		if ($rows > 1) {
			if (!$config['pass_content']) {
				$itemValue = nl2br(htmlspecialchars($itemValue));
			}
			// Like textarea
			$cols = MathUtility::forceIntegerInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = ($rows = MathUtility::forceIntegerInRange($rows, 1, 20));
				if (strlen($itemValue) > $this->charsPerRow * 2) {
					$cols = $this->maxTextareaWidth;
					$rows = MathUtility::forceIntegerInRange(round(strlen($itemValue) / $this->charsPerRow), count(explode(LF, $itemValue)), 20);
					if ($rows < $origRows) {
						$rows = $origRows;
					}
				}
			}

			$cols = round($cols * $this->form_largeComp);
			$width = ceil($cols * $this->form_rowsToStylewidth);
			// Hardcoded: 12 is the height of the font
			$height = $rows * 12;
			$item = '
				<div style="overflow:auto; height:' . $height . 'px; width:' . $width . 'px;" class="t3-tceforms-fieldReadOnly">' . $itemValue . IconUtility::getSpriteIcon('status-status-readonly') . '</div>';
		} else {
			if (!$config['pass_content']) {
				$itemValue = htmlspecialchars($itemValue);
			}
			$cols = $config['cols'] ? $config['cols'] : ($config['size'] ? $config['size'] : $this->maxInputWidth);
			$cols = round($cols * $this->form_largeComp);
			$width = ceil($cols * $this->form_rowsToStylewidth);
			// Overflow:auto crashes mozilla here. Title tag is useful when text is longer than the div box (overflow:hidden).
			$item = '
				<div style="overflow:hidden; width:' . $width . 'px;" class="t3-tceforms-fieldReadOnly" title="' . $itemValue . '">' . '<span class="nobr">' . ((string)$itemValue !== '' ? $itemValue : '&nbsp;') . '</span>' . IconUtility::getSpriteIcon('status-status-readonly') . '</div>';
		}
		return $item;
	}

	/**
	 * Handler for Flex Forms
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeFlex($table, $field, $row, &$PA) {
		// Data Structure:
		$dataStructArray = BackendUtility::getFlexFormDS($PA['fieldConf']['config'], $row, $table, $field);
		$item = '';
		// Manipulate Flexform DS via TSConfig and group access lists
		if (is_array($dataStructArray)) {
			$flexFormHelper = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FlexFormsHelper');
			$dataStructArray = $flexFormHelper->modifyFlexFormDS($dataStructArray, $table, $field, $row, $PA['fieldConf']);
			unset($flexFormHelper);
		}
		// Get data structure:
		if (is_array($dataStructArray)) {
			// Get data:
			$xmlData = $PA['itemFormElValue'];
			$xmlHeaderAttributes = GeneralUtility::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset) {
				$currentCharset = $GLOBALS['LANG']->charSet;
				$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
			}
			$editData = GeneralUtility::xml2array($xmlData);
			// Must be XML parsing error...
			if (!is_array($editData)) {
				$editData = array();
			} elseif (!isset($editData['meta']) || !is_array($editData['meta'])) {
				$editData['meta'] = array();
			}
			// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF';
			// Sheet to display
			// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;
			$editData['meta']['currentLangId'] = array();
			// Look up page overlays:
			$checkPageLanguageOverlay = $GLOBALS['BE_USER']->getTSConfigVal('options.checkPageLanguageOverlay') ? TRUE : FALSE;
			if ($checkPageLanguageOverlay) {
				$pageOverlays = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('*', 'pages_language_overlay', 'pid=' . (int)$row['pid'] . BackendUtility::deleteClause('pages_language_overlay') . BackendUtility::versioningPlaceholderClause('pages_language_overlay'), '', '', '', 'sys_language_uid');
			}
			$languages = $this->getAvailableLanguages();
			foreach ($languages as $lInfo) {
				if ($GLOBALS['BE_USER']->checkLanguageAccess($lInfo['uid']) && (!$checkPageLanguageOverlay || $lInfo['uid'] <= 0 || is_array($pageOverlays[$lInfo['uid']]))) {
					$editData['meta']['currentLangId'][] = $lInfo['ISOcode'];
				}
			}
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId'])) {
				$editData['meta']['currentLangId'] = array('DEF');
			}
			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
			$PA['_noEditDEF'] = FALSE;
			if ($langChildren || $langDisabled) {
				$rotateLang = array('DEF');
			} else {
				if (!in_array('DEF', $editData['meta']['currentLangId'])) {
					array_unshift($editData['meta']['currentLangId'], 'DEF');
					$PA['_noEditDEF'] = TRUE;
				}
				$rotateLang = $editData['meta']['currentLangId'];
			}
			// Tabs sheets
			if (is_array($dataStructArray['sheets'])) {
				$tabsToTraverse = array_keys($dataStructArray['sheets']);
			} else {
				$tabsToTraverse = array($sheet);
			}

			/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
			$elementConditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\ElementConditionMatcher');

			foreach ($rotateLang as $lKey) {
				if (!$langChildren && !$langDisabled) {
					$item .= '<strong>' . $this->getLanguageIcon($table, $row, ('v' . $lKey)) . $lKey . ':</strong>';
				}
				// Default language, other options are "lUK" or whatever country code (independant of system!!!)
				$lang = 'l' . $lKey;
				$tabParts = array();
				foreach ($tabsToTraverse as $sheet) {
					list($dataStruct, $sheet) = GeneralUtility::resolveSheetDefInDS($dataStructArray, $sheet);
					// If sheet has displayCond
					if ($dataStruct['ROOT']['TCEforms']['displayCond']) {
						$splittedCondition = GeneralUtility::trimExplode(':', $dataStruct['ROOT']['TCEforms']['displayCond']);
						$skipCondition = FALSE;
						$fakeRow = array();
						switch ($splittedCondition[0]) {
							case 'FIELD':
								list($sheetName, $fieldName) = GeneralUtility::trimExplode('.', $splittedCondition[1]);
								$fieldValue = $editData['data'][$sheetName][$lang][$fieldName];
								$splittedCondition[1] = $fieldName;
								$dataStruct['ROOT']['TCEforms']['displayCond'] = join(':', $splittedCondition);
								$fakeRow = array($fieldName => $fieldValue);
								break;
							case 'HIDE_FOR_NON_ADMINS':

							case 'VERSION':

							case 'HIDE_L10N_SIBLINGS':

							case 'EXT':
								break;
							case 'REC':
								$fakeRow = array('uid' => $row['uid']);
								break;
							default:
								$skipCondition = TRUE;
						}
						$displayConditionResult = TRUE;
						if ($dataStruct['ROOT']['TCEforms']['displayCond']) {
							$displayConditionResult = $elementConditionMatcher->match($dataStruct['ROOT']['TCEforms']['displayCond'], $fakeRow, 'vDEF');
						}
						// If sheets displayCond leads to false
						if (!$skipCondition && !$displayConditionResult) {
							// Don't create this sheet
							continue;
						}
					}
					// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
						// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';
						$PA['_lang'] = $lang;
						// Assemble key for loading the correct CSH file
						$dsPointerFields = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$field]['config']['ds_pointerField'], TRUE);
						$PA['_cshKey'] = $table . '.' . $field;
						foreach ($dsPointerFields as $key) {
							$PA['_cshKey'] .= '.' . $row[$key];
						}
						// Push the sheet level tab to DynNestedStack
						if (is_array($dataStructArray['sheets'])) {
							$tabIdentString = $GLOBALS['TBE_TEMPLATE']->getDynTabMenuId('TCEFORMS:flexform:' . $PA['itemFormElName'] . $PA['_lang']);
							$this->pushToDynNestedStack('tab', $tabIdentString . '-' . (count($tabParts) + 1));
						}
						// Render flexform:
						$tRows = $this->getSingleField_typeFlex_draw($dataStruct['ROOT']['el'], $editData['data'][$sheet][$lang], $table, $field, $row, $PA, '[data][' . $sheet . '][' . $lang . ']');
						$sheetContent = '<div class="typo3-TCEforms-flexForm">' . $tRows . '</div>';
						// Pop the sheet level tab from DynNestedStack
						if (is_array($dataStructArray['sheets'])) {
							$this->popFromDynNestedStack('tab', $tabIdentString . '-' . (count($tabParts) + 1));
						}
					} else {
						$sheetContent = 'Data Structure ERROR: No ROOT element found for sheet "' . $sheet . '".';
					}
					// Add to tab:
					$tabParts[] = array(
						'label' => $dataStruct['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetTitle']) : $sheet,
						'description' => $dataStruct['ROOT']['TCEforms']['sheetDescription'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetDescription']) : '',
						'linkTitle' => $dataStruct['ROOT']['TCEforms']['sheetShortDescr'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetShortDescr']) : '',
						'content' => $sheetContent
					);
				}
				if (is_array($dataStructArray['sheets'])) {
					$dividersToTabsBehaviour = isset($GLOBALS['TCA'][$table]['ctrl']['dividers2tabs']) ? $GLOBALS['TCA'][$table]['ctrl']['dividers2tabs'] : 1;
					$item .= $this->getDynTabMenu($tabParts, 'TCEFORMS:flexform:' . $PA['itemFormElName'] . $PA['_lang'], $dividersToTabsBehaviour);
				} else {
					$item .= $sheetContent;
				}
			}
		} else {
			$item = 'Data Structure ERROR: ' . $dataStructArray;
		}
		return $item;
	}

	/**
	 * Creates the language menu for FlexForms:
	 *
	 * @param array $languages
	 * @param string $elName
	 * @param array $selectedLanguage
	 * @param boolean $multi
	 * @return string HTML for menu
	 * @todo Define visibility
	 */
	public function getSingleField_typeFlex_langMenu($languages, $elName, $selectedLanguage, $multi = 1) {
		$opt = array();
		foreach ($languages as $lArr) {
			$opt[] = '<option value="' . htmlspecialchars($lArr['ISOcode']) . '"' . (in_array($lArr['ISOcode'], $selectedLanguage) ? ' selected="selected"' : '') . '>' . htmlspecialchars($lArr['title']) . '</option>';
		}
		$output = '<select id="' . uniqid('tceforms-multiselect-') . ' class="tceforms-select tceforms-multiselect tceforms-flexlangmenu" name="' . $elName . '[]"' . ($multi ? ' multiple="multiple" size="' . count($languages) . '"' : '') . '>' . implode('', $opt) . '</select>';
		return $output;
	}

	/**
	 * Creates the menu for selection of the sheets:
	 *
	 * @param array $sArr Sheet array for which to render the menu
	 * @param string $elName Form element name of the field containing the sheet pointer
	 * @param string $sheetKey Current sheet key
	 * @return string HTML for menu
	 * @todo Define visibility
	 */
	public function getSingleField_typeFlex_sheetMenu($sArr, $elName, $sheetKey) {
		$tCells = array();
		$pct = round(100 / count($sArr));
		foreach ($sArr as $sKey => $sheetCfg) {
			if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
				$onClick = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){' . $this->elName($elName) . '.value=\'' . $sKey . '\'; TBE_EDITOR.submitForm()};';
			} else {
				$onClick = 'if(TBE_EDITOR.checkSubmit(-1)){ ' . $this->elName($elName) . '.value=\'' . $sKey . '\'; TBE_EDITOR.submitForm();}';
			}
			$tCells[] = '<td width="' . $pct . '%" style="' . ($sKey == $sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;') . ' cursor: hand;" onclick="' . htmlspecialchars($onClick) . '" align="center">' . ($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey) . '</td>';
		}
		return '<table border="0" cellpadding="0" cellspacing="2" class="typo3-TCEforms-flexForm-sheetMenu"><tr>' . implode('', $tCells) . '</tr></table>';
	}

	/**
	 * Recursive rendering of flexforms
	 *
	 * @param array $dataStruct (part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param array $editData (part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param string $table Table name, eg. tt_content
	 * @param string $field Field name, eg. tx_templavoila_flex
	 * @param array $row The particular record from $table in which the field $field is found
	 * @param array $PA Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param string $formPrefix Form field prefix, eg. "[data][sDEF][lDEF][...][...]
	 * @param integer $level Indicates nesting level for the function call
	 * @param string $level Prefix for ID-values
	 * @param boolean $toggleClosed Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return string HTMl code for form.
	 * @todo Define visibility
	 */
	public function getSingleField_typeFlex_draw($dataStruct, $editData, $table, $field, $row, &$PA, $formPrefix = '', $level = 0, $idPrefix = 'ID', $toggleClosed = FALSE) {
		$output = '';
		$mayRestructureFlexforms = $GLOBALS['BE_USER']->checkLanguageAccess(0);
		// Data Structure array must be ... and array of course...
		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) {
				// Traversing fields in structure:
				if (is_array($value)) {
					// The value of each entry must be an array.
					// ********************
					// Making the row:
					// ********************
					// Title of field:
					// <title>LLL:EXT:cms/locallang_ttc.xml:media.sources</title>
					$theTitle = $value['title'];

					// If there is a title, check for LLL label
					if (strlen($theTitle) > 0) {
						$theTitle = htmlspecialchars(GeneralUtility::fixed_lgd_cs($this->sL($theTitle), 30));
					}
					// If it's a "section" or "container":
					if ($value['type'] == 'array') {
						// Creating IDs for form fields:
						// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form because this relies on simple string substitution of the first parts of the id values.
						// This is a suffix used for forms on this level
						$thisId = GeneralUtility::shortMd5(uniqid('id', TRUE));
						// $idPrefix is the prefix for elements on lower levels in the hierarchy and we combine this with the thisId value to form a new ID on this level.
						$idTagPrefix = $idPrefix . '-' . $thisId;
						// If it's a "section" containing other elements:
						if ($value['section']) {
							// Load script.aculo.us if flexform sections can be moved by drag'n'drop:
							$GLOBALS['SOBE']->doc->getPageRenderer()->loadScriptaculous();
							// Render header of section:
							$output .= '<div class="t3-form-field-label-flexsection"><strong>' . $theTitle . '</strong></div>';
							// Render elements in data array for section:
							$tRows = array();
							$cc = 0;
							if (is_array($editData[$key]['el'])) {
								foreach ($editData[$key]['el'] as $k3 => $v3) {
									$cc = $k3;
									if (is_array($v3)) {
										$theType = key($v3);
										$theDat = $v3[$theType];
										$newSectionEl = $value['el'][$theType];
										if (is_array($newSectionEl)) {
											$tRows[] = $this->getSingleField_typeFlex_draw(array($theType => $newSectionEl), array($theType => $theDat), $table, $field, $row, $PA, $formPrefix . '[' . $key . '][el][' . $cc . ']', $level + 1, $idTagPrefix, $v3['_TOGGLE']);
										}
									}
								}
							}
							// Now, we generate "templates" for new elements that could be added to this section by traversing all possible types of content inside the section:
							// We have to handle the fact that requiredElements and such may be set during this rendering process and therefore we save and reset the state of some internal variables - little crude, but works...
							// Preserving internal variables we don't want to change:
							$TEMP_requiredElements = $this->requiredElements;
							// Traversing possible types of new content in the section:
							$newElementsLinks = array();
							foreach ($value['el'] as $nnKey => $nCfg) {
								$additionalJS_post_saved = $this->additionalJS_post;
								$this->additionalJS_post = array();
								$additionalJS_submit_saved = $this->additionalJS_submit;
								$this->additionalJS_submit = array();
								$newElementTemplate = $this->getSingleField_typeFlex_draw(array($nnKey => $nCfg), array(), $table, $field, $row, $PA, $formPrefix . '[' . $key . '][el][' . $idTagPrefix . '-form]', $level + 1, $idTagPrefix);
								// Makes a "Add new" link:
								$var = uniqid('idvar');
								$replace = 'replace(/' . $idTagPrefix . '-/g,"' . $idTagPrefix . '-"+' . $var . '+"-")';
								$replace .= '.replace(/(tceforms-(datetime|date)field-)/g,"$1" + (new Date()).getTime())';
								$onClickInsert = 'var ' . $var . ' = "' . 'idx"+(new Date()).getTime();';
								// Do not replace $isTagPrefix in setActionStatus() because it needs section id!
								$onClickInsert .= 'new Insertion.Bottom($("' . $idTagPrefix . '"), ' . json_encode($newElementTemplate) . '.' . $replace . '); setActionStatus("' . $idTagPrefix . '");';
								$onClickInsert .= 'eval(unescape("' . rawurlencode(implode(';', $this->additionalJS_post)) . '").' . $replace . ');';
								$onClickInsert .= 'TBE_EDITOR.addActionChecks("submit", unescape("' . rawurlencode(implode(';', $this->additionalJS_submit)) . '").' . $replace . ');';
								$onClickInsert .= 'TYPO3.TCEFORMS.update();';
								$onClickInsert .= 'return false;';
								// Kasper's comment (kept for history):
								// Maybe there is a better way to do this than store the HTML for the new element
								// in rawurlencoded format - maybe it even breaks with certain charsets?
								// But for now this works...
								$this->additionalJS_post = $additionalJS_post_saved;
								$this->additionalJS_submit = $additionalJS_submit_saved;
								$title = '';
								if (isset($nCfg['title'])) {
									$title = $this->sL($nCfg['title']);
								} elseif (isset($nCfg['tx_templavoila']['title'])) {
									/* @deprecated since 4.7 will be removed two versions after 6.2 */
									GeneralUtility::deprecationLog(
										'Flexform data for table ' . $table . ', field ' . $field
										. 'contains the <tx_templavoila><title>... construct deprecated since TYPO3 4.7. '
										. 'The <tx_templavoila> element has to be removed now. Support will be removed two versions after 6.2.'
									);
									$title = $this->sL($nCfg['tx_templavoila']['title']);
								}
								$newElementsLinks[] = '<a href="#" onclick="' . htmlspecialchars($onClickInsert) . '">'
									. IconUtility::getSpriteIcon('actions-document-new')
									. htmlspecialchars(GeneralUtility::fixed_lgd_cs($title, 30)) . '</a>';
							}
							// Reverting internal variables we don't want to change:
							$this->requiredElements = $TEMP_requiredElements;
							// Adding the sections:
							$toggleAll = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.toggleall', TRUE);
							$output .= '
							<div class="t3-form-field-toggle-flexsection">
								<a href="#" onclick="' . htmlspecialchars(('flexFormToggleSubs("' . $idTagPrefix . '"); return false;')) . '">' . IconUtility::getSpriteIcon('actions-move-right', array('title' => $toggleAll)) . $toggleAll . '
								</a>
							</div>

							<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsection">' . implode('', $tRows) . '</div>';
							$output .= $mayRestructureFlexforms ? '<div class="t3-form-field-add-flexsection"><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.addnew', TRUE) . ':</strong> ' . implode(' | ', $newElementsLinks) . '</div>' : '';
						} else {
							// It is a container
							$toggleIcon_open = IconUtility::getSpriteIcon('actions-move-down');
							$toggleIcon_close = IconUtility::getSpriteIcon('actions-move-right');
							// Create on-click actions.
							$onClickRemove = 'if (confirm("Are you sure?")){/*###REMOVE###*/;$("' . $idTagPrefix . '").hide();setActionStatus("' . $idPrefix . '");} return false;';
							$onClickToggle = 'flexFormToggle("' . $idTagPrefix . '"); return false;';
							$onMove = 'flexFormSortable("' . $idPrefix . '")';
							// Notice: Creating "new" elements after others seemed to be too difficult to do and since moving new elements created in the bottom is now so easy with drag'n'drop I didn't see the need.
							// Putting together header of a section. Sections can be removed, copied, opened/closed, moved up and down:
							// I didn't know how to make something right-aligned without a table, so I put it in a table. can be made into <div>'s if someone like to.
							// Notice: The fact that I make a "Sortable.create" right onmousedown is that if we initialize this when rendering the form in PHP new and copied elements will not be possible to move as a sortable. But this way a new sortable is initialized everytime someone tries to move and it will always work.
							$ctrlHeader = '
								<table class="t3-form-field-header-flexsection" onmousedown="' . ($mayRestructureFlexforms ? htmlspecialchars($onMove) : '') . '">
								<tr>
									<td>
										<a href="#" onclick="' . htmlspecialchars($onClickToggle) . '" id="' . $idTagPrefix . '-toggle">
											' . ($toggleClosed ? $toggleIcon_close : $toggleIcon_open) . '
										</a>
										<strong>' . $theTitle . '</strong> <em><span id="' . $idTagPrefix . '-preview"></span></em>
									</td>
									<td align="right">' . ($mayRestructureFlexforms ? IconUtility::getSpriteIcon('actions-move-move', array('title' => 'Drag to Move')) : '') . ($mayRestructureFlexforms ? '<a href="#" onclick="' . htmlspecialchars($onClickRemove) . '">' . IconUtility::getSpriteIcon('actions-edit-delete', array('title' => 'Delete')) : '') . '</td>
									</tr>
								</table>';
							$s = GeneralUtility::revExplode('[]', $formPrefix, 2);
							$actionFieldName = '_ACTION_FLEX_FORM' . $PA['itemFormElName'] . $s[0] . '][_ACTION][' . $s[1];
							// Push the container to DynNestedStack as it may be toggled
							$this->pushToDynNestedStack('flex', $idTagPrefix);
							// Putting together the container:
							$this->additionalJS_delete = array();
							$output .= '
								<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsections">
									<input id="' . $idTagPrefix . '-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value=""/>

									' . $ctrlHeader . '
									<div class="t3-form-field-record-flexsection" id="' . $idTagPrefix . '-content"' . ($toggleClosed ? ' style="display:none;"' : '') . '>' . $this->getSingleField_typeFlex_draw($value['el'], $editData[$key]['el'], $table, $field, $row, $PA, ($formPrefix . '[' . $key . '][el]'), ($level + 1), $idTagPrefix) . '
									</div>
									<input id="' . $idTagPrefix . '-toggleClosed" type="hidden" name="' . htmlspecialchars(('data[' . $table . '][' . $row['uid'] . '][' . $field . ']' . $formPrefix . '[_TOGGLE]')) . '" value="' . ($toggleClosed ? 1 : 0) . '" />
								</div>';
							$output = str_replace('/*###REMOVE###*/', GeneralUtility::slashJS(htmlspecialchars(implode('', $this->additionalJS_delete))), $output);
							// NOTICE: We are saving the toggle-state directly in the flexForm XML and "unauthorized" according to the data structure. It means that flexform XML will report unclean and a cleaning operation will remove the recorded togglestates. This is not a fatal problem. Ideally we should save the toggle states in meta-data but it is much harder to do that. And this implementation was easy to make and with no really harmful impact.
							// Pop the container from DynNestedStack
							$this->popFromDynNestedStack('flex', $idTagPrefix);
						}
					} elseif (is_array($value['TCEforms']['config'])) {
						// Rendering a single form element:
						if (is_array($PA['_valLang'])) {
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}
						$conditionData = is_array($editData) ? $editData : array();
						// Add current $row to data processed by \TYPO3\CMS\Backend\Form\ElementConditionMatcher
						$conditionData['parentRec'] = $row;
						$tRows = array();

						/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
						$elementConditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\ElementConditionMatcher');

						foreach ($rotateLang as $vDEFkey) {
							$vDEFkey = 'v' . $vDEFkey;
							$displayConditionResult = TRUE;
							if ($value['TCEforms']['displayCond']) {
								$displayConditionResult = $elementConditionMatcher->match($value['TCEforms']['displayCond'], $conditionData, $vDEFkey);
							}
							if ($displayConditionResult) {
								$fakePA = array();
								$fakePA['fieldConf'] = array(
									'label' => $this->sL(trim($value['TCEforms']['label'])),
									'config' => $value['TCEforms']['config'],
									'defaultExtras' => $value['TCEforms']['defaultExtras'],
									'onChange' => $value['TCEforms']['onChange']
								);
								if ($PA['_noEditDEF'] && $PA['_lang'] === 'lDEF') {
									$fakePA['fieldConf']['config'] = array(
										'type' => 'none',
										'rows' => 2
									);
								}
								if ($fakePA['fieldConf']['onChange'] === 'reload'
									|| !empty($GLOBALS['TCA'][$table]['ctrl']['type']) && (string)$key === $GLOBALS['TCA'][$table]['ctrl']['type']
									|| !empty($GLOBALS['TCA'][$table]['ctrl']['requestUpdate']) && GeneralUtility::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $key)
								) {
									if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
										$alertMsgOnChange = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm() };';
									} else {
										$alertMsgOnChange = 'if(TBE_EDITOR.checkSubmit(-1)){ TBE_EDITOR.submitForm();}';
									}
								} else {
									$alertMsgOnChange = '';
								}
								$fakePA['fieldChangeFunc'] = $PA['fieldChangeFunc'];
								if (strlen($alertMsgOnChange)) {
									$fakePA['fieldChangeFunc']['alert'] = $alertMsgOnChange;
								}
								$fakePA['onFocus'] = $PA['onFocus'];
								$fakePA['label'] = $PA['label'];
								$fakePA['itemFormElName'] = $PA['itemFormElName'] . $formPrefix . '[' . $key . '][' . $vDEFkey . ']';
								$fakePA['itemFormElName_file'] = $PA['itemFormElName_file'] . $formPrefix . '[' . $key . '][' . $vDEFkey . ']';
								$fakePA['itemFormElID'] = $fakePA['itemFormElName'];
								if (isset($editData[$key][$vDEFkey])) {
									$fakePA['itemFormElValue'] = $editData[$key][$vDEFkey];
								} else {
									$fakePA['itemFormElValue'] = $fakePA['fieldConf']['config']['default'];
								}
								$theFormEl = $this->getSingleField_SW($table, $field, $row, $fakePA);
								$theTitle = htmlspecialchars($fakePA['fieldConf']['label']);
								if (!in_array('DEF', $rotateLang)) {
									$defInfo = '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 0) . $this->previewFieldValue($editData[$key]['vDEF'], $fakePA['fieldConf'], $field) . '&nbsp;</div>';
								} else {
									$defInfo = '';
								}
								if (!$PA['_noEditDEF']) {
									$prLang = $this->getAdditionalPreviewLanguages();
									foreach ($prLang as $prL) {
										$defInfo .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, ('v' . $prL['ISOcode'])) . $this->previewFieldValue($editData[$key][('v' . $prL['ISOcode'])], $fakePA['fieldConf'], $field) . '&nbsp;</div>';
									}
								}
								$languageIcon = '';
								if ($vDEFkey != 'vDEF') {
									$languageIcon = $this->getLanguageIcon($table, $row, $vDEFkey);
								}
								// Put row together
								// possible linebreaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace
								$processedTitle = str_replace('\\n', '<br />', $theTitle);
								$tRows[] = '<div class="t3-form-field-container t3-form-field-container-flex">' . '<div class="t3-form-field-label t3-form-field-label-flex">' . $languageIcon . BackendUtility::wrapInHelp($PA['_cshKey'], $key, $processedTitle) . '</div>
									<div class="t3-form-field t3-form-field-flex">' . $theFormEl . $defInfo . $this->renderVDEFDiff($editData[$key], $vDEFkey) . '</div>
								</div>';
							}
						}
						if (count($tRows)) {
							$output .= implode('', $tRows);
						}
					}
				}
			}
		}
		return $output;
	}

	/**
	 * Handler for unknown types.
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeUnknown($table, $field, $row, &$PA) {
		$item = 'Unknown type: ' . $PA['fieldConf']['config']['form_type'] . '<br />';
		return $item;
	}

	/**
	 * User defined field type
	 *
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return string The HTML code for the TCEform field
	 * @todo Define visibility
	 */
	public function getSingleField_typeUser($table, $field, $row, &$PA) {
		$PA['table'] = $table;
		$PA['field'] = $field;
		$PA['row'] = $row;
		$PA['parameters'] = isset($PA['fieldConf']['config']['parameters']) ? $PA['fieldConf']['config']['parameters'] : array();
		$PA['pObj'] = &$this;
		return GeneralUtility::callUserFunction($PA['fieldConf']['config']['userFunc'], $PA, $this);
	}

	/************************************************************
	 *
	 * Field content processing
	 *
	 ************************************************************/
	/**
	 * Format field content of various types if $config['format'] is set to date, filesize, ..., user
	 * This is primarily for the field type none but can be used for user field types for example
	 *
	 * @param array $config Configuration for the display
	 * @param string $itemValue The value to display
	 * @return string Formatted Field content
	 * @todo Define visibility
	 */
	public function formatValue($config, $itemValue) {
		$format = trim($config['format']);
		switch ($format) {
			case 'date':
				if ($itemValue) {
					$option = trim($config['format.']['option']);
					if ($option) {
						if ($config['format.']['strftime']) {
							$value = strftime($option, $itemValue);
						} else {
							$value = date($option, $itemValue);
						}
					} else {
						$value = date('d-m-Y', $itemValue);
					}
				} else {
					$value = '';
				}
				if ($config['format.']['appendAge']) {
					$value .= ' (' . BackendUtility::calcAge(($GLOBALS['EXEC_TIME'] - $itemValue), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.minutesHoursDaysYears')) . ')';
				}
				$itemValue = $value;
				break;
			case 'datetime':
				// compatibility with "eval" (type "input")
				$itemValue = date('H:i d-m-Y', $itemValue);
				break;
			case 'time':
				// compatibility with "eval" (type "input")
				$itemValue = date('H:i', $itemValue);
				break;
			case 'timesec':
				// compatibility with "eval" (type "input")
				$itemValue = date('H:i:s', $itemValue);
				break;
			case 'year':
				// compatibility with "eval" (type "input")
				$itemValue = date('Y', $itemValue);
				break;
			case 'int':
				$baseArr = array('dec' => 'd', 'hex' => 'x', 'HEX' => 'X', 'oct' => 'o', 'bin' => 'b');
				$base = trim($config['format.']['base']);
				$format = $baseArr[$base] ? $baseArr[$base] : 'd';
				$itemValue = sprintf('%' . $format, $itemValue);
				break;
			case 'float':
				$precision = MathUtility::forceIntegerInRange($config['format.']['precision'], 1, 10, 2);
				$itemValue = sprintf('%.' . $precision . 'f', $itemValue);
				break;
			case 'number':
				$format = trim($config['format.']['option']);
				$itemValue = sprintf('%' . $format, $itemValue);
				break;
			case 'md5':
				$itemValue = md5($itemValue);
				break;
			case 'filesize':
				$value = GeneralUtility::formatSize((int)$itemValue);
				if ($config['format.']['appendByteSize']) {
					$value .= ' (' . $itemValue . ')';
				}
				$itemValue = $value;
				break;
			case 'user':
				$func = trim($config['format.']['userFunc']);
				if ($func) {
					$params = array(
						'value' => $itemValue,
						'args' => $config['format.']['userFunc'],
						'config' => $config,
						'pObj' => &$this
					);
					$itemValue = GeneralUtility::callUserFunction($func, $params, $this);
				}
				break;
			default:
				// Do nothing
		}
		return $itemValue;
	}

	/************************************************************
	 *
	 * "Configuration" fetching/processing functions
	 *
	 ************************************************************/
	/**
	 * Calculate and return the current "types" pointer value for a record
	 *
	 * @param string $table The table name. MUST be in $GLOBALS['TCA']
	 * @param array $row The row from the table, should contain at least the "type" field, if applicable.
	 * @return string Return the "type" value for this record, ready to pick a "types" configuration from the $GLOBALS['TCA'] array.
	 * @todo Define visibility
	 */
	public function getRTypeNum($table, $row) {
		$typeNum = 0;
		$field = $GLOBALS['TCA'][$table]['ctrl']['type'];
		if ($field) {
			if (strpos($field, ':') !== FALSE) {
				list($pointerField, $foreignTypeField) = explode(':', $field);
				$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$pointerField]['config'];
				$relationType = $fieldConfig['type'];
				if ($relationType === 'select') {
					$foreignUid = $row[$pointerField];
					$foreignTable = $fieldConfig['foreign_table'];
				} elseif ($relationType === 'group') {
					$values = $this->extractValuesOnlyFromValueLabelList($row[$pointerField]);
					list(, $foreignUid) = GeneralUtility::revExplode('_', $values[0], 2);
					$allowedTables = explode(',', $fieldConfig['allowed']);
					// Always take the first configured table.
					$foreignTable = $allowedTables[0];
				} else {
					throw new \RuntimeException('TCA Foreign field pointer fields are only allowed to be used with group or select field types.', 1325861239);
				}
				if ($foreignUid) {
					$foreignRow = BackendUtility::getRecord($foreignTable, $foreignUid, $foreignTypeField);
					$this->registerDefaultLanguageData($foreignTable, $foreignRow);
					if ($foreignRow[$foreignTypeField]) {
						$foreignTypeFieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
						$typeNum = $this->getLanguageOverlayRawValue($foreignTable, $foreignRow, $foreignTypeField, $foreignTypeFieldConfig);
					}
				}
			} else {
				$typeFieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
				$typeNum = $this->getLanguageOverlayRawValue($table, $row, $field, $typeFieldConfig);
			}
		}
		if (empty($typeNum)) {
			// If that value is an empty string, set it to "0" (zero)
			$typeNum = 0;
		}
		// If current typeNum doesn't exist, set it to 0 (or to 1 for historical reasons, if 0 doesn't exist)
		if (!$GLOBALS['TCA'][$table]['types'][$typeNum]) {
			$typeNum = $GLOBALS['TCA'][$table]['types']['0'] ? 0 : 1;
		}
		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		$typeNum = (string) $typeNum;
		return $typeNum;
	}

	/**
	 * Used to adhoc-rearrange the field order normally set in the [types][showitem] list
	 *
	 * @param array $fields A [types][showitem] list of fields, exploded by ",
	 * @return array Returns rearranged version (keys are changed around as well.)
	 * @see getMainFields()
	 * @todo Define visibility
	 */
	public function rearrange($fields) {
		$fO = array_flip(GeneralUtility::trimExplode(',', $this->fieldOrder, TRUE));
		$newFields = array();
		foreach ($fields as $cc => $content) {
			$cP = GeneralUtility::trimExplode(';', $content);
			if (isset($fO[$cP[0]])) {
				$newFields[$fO[$cP[0]]] = $content;
				unset($fields[$cc]);
			}
		}
		ksort($newFields);
		// Candidate for GeneralUtility::array_merge() if integer-keys will some day make trouble...
		$fields = array_merge($newFields, $fields);
		return $fields;
	}

	/**
	 * Producing an array of field names NOT to display in the form, based on settings from subtype_value_field, bitmask_excludelist_bits etc.
	 * Notice, this list is in NO way related to the "excludeField" flag
	 *
	 * @param string $table Table name, MUST be in $GLOBALS['TCA']
	 * @param array $row A record from table.
	 * @param string $typeNum A "type" pointer value, probably the one calculated based on the record array.
	 * @return array Array with fieldnames as values. The fieldnames are those which should NOT be displayed "anyways
	 * @see getMainFields()
	 * @todo Define visibility
	 */
	public function getExcludeElements($table, $row, $typeNum) {
		// Init:
		$excludeElements = array();
		// If a subtype field is defined for the type
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field']) {
			$sTfield = $GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]])) {
				$excludeElements = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]], TRUE);
			}
		}
		// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_value_field']) {
			$sTfield = $GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_value_field'];
			$sTValue = MathUtility::forceIntegerInRange($row[$sTfield], 0);
			if (is_array($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_excludelist_bits'])) {
				foreach ($GLOBALS['TCA'][$table]['types'][$typeNum]['bitmask_excludelist_bits'] as $bitKey => $eList) {
					$bit = substr($bitKey, 1);
					if (MathUtility::canBeInterpretedAsInteger($bit)) {
						$bit = MathUtility::forceIntegerInRange($bit, 0, 30);
						if ($bitKey[0] === '-' && !($sTValue & pow(2, $bit)) || $bitKey[0] === '+' && $sTValue & pow(2, $bit)) {
							$excludeElements = array_merge($excludeElements, GeneralUtility::trimExplode(',', $eList, TRUE));
						}
					}
				}
			}
		}
		// Return the array of elements:
		return $excludeElements;
	}

	/**
	 * Finds possible field to add to the form, based on subtype fields.
	 *
	 * @param string $table Table name, MUST be in $GLOBALS['TCA']
	 * @param array $row A record from table.
	 * @param string $typeNum A "type" pointer value, probably the one calculated based on the record array.
	 * @return array An array containing two values: 1) Another array containing fieldnames to add and 2) the subtype value field.
	 * @see getMainFields()
	 * @todo Define visibility
	 */
	public function getFieldsToAdd($table, $row, $typeNum) {
		// Init:
		$addElements = array();
		// If a subtype field is defined for the type
		if ($GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field']) {
			$sTfield = $GLOBALS['TCA'][$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]])) {
				$addElements = GeneralUtility::trimExplode(',', $GLOBALS['TCA'][$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]], TRUE);
			}
		}
		// Return the return
		return array($addElements, $sTfield);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param array $fields A [types][showitem] list of fields, exploded by ",
	 * @param array $fieldsToAdd The output from getFieldsToAdd()
	 * @param string $table The table name, if we want to consider it's palettes when positioning the new elements
	 * @return array Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 * @todo Define visibility
	 */
	public function mergeFieldsWithAddedFields($fields, $fieldsToAdd, $table = '') {
		if (count($fieldsToAdd[0])) {
			$c = 0;
			$found = FALSE;
			foreach ($fields as $fieldInfo) {
				list($fieldName, $label, $paletteName) = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $fieldInfo);
				if ($fieldName === $fieldsToAdd[1]) {
					$found = TRUE;
				} elseif ($fieldName === '--palette--' && $paletteName && $table !== '') {
					// Look inside the palette
					if (is_array($GLOBALS['TCA'][$table]['palettes'][$paletteName])) {
						$itemList = $GLOBALS['TCA'][$table]['palettes'][$paletteName]['showitem'];
						if ($itemList) {
							$paletteFields = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $itemList, TRUE);
							foreach ($paletteFields as $info) {
								$fieldParts = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(';', $info);
								$theField = $fieldParts[0];
								if ($theField === $fieldsToAdd[1]) {
									$found = TRUE;
									break 1;
								}
							}
						}
					}
				}
				if ($found) {
					array_splice($fields, $c + 1, 0, $fieldsToAdd[0]);
					break;
				}
				$c++;
			}
		}
		return $fields;
	}

	/**
	 * Returns TSconfig for table/row
	 * Multiple requests to this function will return cached content so there is no performance loss in calling this many times since the information is looked up only once.
	 *
	 * @param string $table The table name
	 * @param array $row The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param string $field Optionally you can specify the field name as well. In that case the TSconfig for the field is returned.
	 * @return mixed The TSconfig values (probably in an array)
	 * @see BackendUtility::getTCEFORM_TSconfig()
	 * @todo Define visibility
	 */
	public function setTSconfig($table, $row, $field = '') {
		$mainKey = $table . ':' . $row['uid'];
		if (!isset($this->cachedTSconfig[$mainKey])) {
			$this->cachedTSconfig[$mainKey] = BackendUtility::getTCEFORM_TSconfig($table, $row);
		}
		if ($field) {
			return $this->cachedTSconfig[$mainKey][$field];
		} else {
			return $this->cachedTSconfig[$mainKey];
		}
	}

	/**
	 * Overrides the TCA field configuration by TSconfig settings.
	 *
	 * Example TSconfig: TCEform.<table>.<field>.config.appearance.useSortable = 1
	 * This overrides the setting in $GLOBALS['TCA'][<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param array $fieldConfig $GLOBALS['TCA'] field configuration
	 * @param array $TSconfig TSconfig
	 * @return array Changed TCA field configuration
	 * @todo Define visibility
	 */
	public function overrideFieldConf($fieldConfig, $TSconfig) {
		if (is_array($TSconfig)) {
			$TSconfig = GeneralUtility::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
				// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], TRUE)) {
						unset($TSconfig['config'][$key]);
					}
				}
				// Override $GLOBALS['TCA'] field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					\TYPO3\CMS\Core\Utility\ArrayUtility::mergeRecursiveWithOverrule($fieldConfig, $TSconfig['config']);
				}
			}
		}
		return $fieldConfig;
	}

	/**
	 * Returns the "special" configuration (from the "types" "showitem" list) for a fieldname based on input table/record
	 * (Not used anywhere...?)
	 *
	 * @param string $table The table name
	 * @param array $row The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param string $field Specify the field name.
	 * @return array
	 * @see getSpecConfFromString(), BackendUtility::getTCAtypes()
	 * @todo Define visibility
	 */
	public function getSpecConfForField($table, $row, $field) {
		// Finds the current "types" configuration for the table/row:
		$types_fieldConfig = BackendUtility::getTCAtypes($table, $row);
		// If this is an array, then traverse it:
		if (is_array($types_fieldConfig)) {
			foreach ($types_fieldConfig as $vconf) {
				// If the input field name matches one found in the 'types' list, then return the 'special' configuration.
				if ($vconf['field'] == $field) {
					return $vconf['spec'];
				}
			}
		}
	}

	/**
	 * Returns the "special" configuration of an "extra" string (non-parsed)
	 *
	 * @param string $extraString The "Part 4" of the fields configuration in "types" "showitem" lists.
	 * @param string $defaultExtras The ['defaultExtras'] value from field configuration
	 * @return array An array with the special options in.
	 * @see getSpecConfForField(), BackendUtility::getSpecConfParts()
	 * @todo Define visibility
	 */
	public function getSpecConfFromString($extraString, $defaultExtras) {
		return BackendUtility::getSpecConfParts($extraString, $defaultExtras);
	}

	/**
	 * Loads the elements of a palette (collection of secondary options) in an array.
	 *
	 * @param string $table The table name
	 * @param array $row The row array
	 * @param string $palette The palette number/pointer
	 * @param string $itemList Optional alternative list of fields for the palette
	 * @return array The palette elements
	 */
	public function loadPaletteElements($table, $row, $palette, $itemList = '') {
		$parts = array();
		// Getting excludeElements, if any.
		if (!is_array($this->excludeElements)) {
			$this->excludeElements = $this->getExcludeElements($table, $row, $this->getRTypeNum($table, $row));
		}
		// Load the palette TCEform elements
		if ($GLOBALS['TCA'][$table] && (is_array($GLOBALS['TCA'][$table]['palettes'][$palette]) || $itemList)) {
			$itemList = $itemList ? $itemList : $GLOBALS['TCA'][$table]['palettes'][$palette]['showitem'];
			if ($itemList) {
				$fields = GeneralUtility::trimExplode(',', $itemList, TRUE);
				foreach ($fields as $info) {
					$fieldParts = GeneralUtility::trimExplode(';', $info);
					$theField = $fieldParts[0];
					if ($theField === '--linebreak--') {
						$parts[]['NAME'] = '--linebreak--';
					} elseif (!in_array($theField, $this->excludeElements) && $GLOBALS['TCA'][$table]['columns'][$theField]) {
						$this->palFieldArr[$palette][] = $theField;
						$elem = $this->getSingleField($table, $theField, $row, $fieldParts[1], 1, '', $fieldParts[2]);
						if (is_array($elem)) {
							$parts[] = $elem;
						}
					}
				}
			}
		}
		return $parts;
	}

	/************************************************************
	 *
	 * Display of localized content etc.
	 *
	 ************************************************************/
	/**
	 * Will register data from original language records if the current record is a translation of another.
	 * The original data is shown with the edited record in the form. The information also includes possibly diff-views of what changed in the original record.
	 * Function called from outside (see alt_doc.php + quick edit) before rendering a form for a record
	 *
	 * @param string $table Table name of the record being edited
	 * @param array $rec Record array of the record being edited
	 * @return void
	 * @todo Define visibility
	 */
	public function registerDefaultLanguageData($table, $rec) {
		// Add default language:
		if ($GLOBALS['TCA'][$table]['ctrl']['languageField'] && $rec[$GLOBALS['TCA'][$table]['ctrl']['languageField']] > 0 && $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField'] && (int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']] > 0) {
			$lookUpTable = $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'] ? $GLOBALS['TCA'][$table]['ctrl']['transOrigPointerTable'] : $table;
			// Get data formatted:
			$this->defaultLanguageData[$table . ':' . $rec['uid']] = BackendUtility::getRecordWSOL($lookUpTable, (int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']]);
			// Get data for diff:
			if ($GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']) {
				$this->defaultLanguageData_diff[$table . ':' . $rec['uid']] = unserialize($rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigDiffSourceField']]);
			}
			// If there are additional preview languages, load information for them also:
			$prLang = $this->getAdditionalPreviewLanguages();
			foreach ($prLang as $prL) {
				/** @var $t8Tools \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider */
				$t8Tools = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider');
				$tInfo = $t8Tools->translationInfo($lookUpTable, (int)$rec[$GLOBALS['TCA'][$table]['ctrl']['transOrigPointerField']], $prL['uid']);
				if (is_array($tInfo['translations']) && is_array($tInfo['translations'][$prL['uid']])) {
					$this->additionalPreviewLanguageData[$table . ':' . $rec['uid']][$prL['uid']] = BackendUtility::getRecordWSOL($table, (int)$tInfo['translations'][$prL['uid']]['uid']);
				}
			}
		}
	}

	/**
	 * Creates language-overlay for a field value
	 * This means the requested field value will be overridden with the data from the default language.
	 * Can be used to render read only fields for example.
	 *
	 * @param string $table Table name of the record being edited
	 * @param string $row Field name represented by $item
	 * @param array $field Record array of the record being edited in current language
	 * @param array $fieldConf Content of $PA['fieldConf']
	 * @return string Unprocessed field value merged with default language data if needed
	 * @todo Define visibility
	 */
	public function getLanguageOverlayRawValue($table, $row, $field, $fieldConf) {
		$value = $row[$field];
		if (is_array($this->defaultLanguageData[$table . ':' . $row['uid']])) {
			if ($fieldConf['l10n_mode'] == 'exclude' || $fieldConf['l10n_mode'] == 'mergeIfNotBlank' && trim($this->defaultLanguageData[$table . ':' . $row['uid']][$field]) !== '') {
				$value = $this->defaultLanguageData[$table . ':' . $row['uid']][$field];
			}
		}
		return $value;
	}

	/**
	 * Renders the display of default language record content around current field.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData, depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param string $table Table name of the record being edited
	 * @param string $field Field name represented by $item
	 * @param array $row Record array of the record being edited
	 * @param string $item HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 * @todo Define visibility
	 */
	public function renderDefaultLanguageContent($table, $field, $row, $item) {
		if (is_array($this->defaultLanguageData[$table . ':' . $row['uid']])) {
			$defaultLanguageValue = BackendUtility::getProcessedValue($table, $field, $this->defaultLanguageData[$table . ':' . $row['uid']][$field], 0, 1);
			$fieldConfig = $GLOBALS['TCA'][$table]['columns'][$field];
			// Don't show content if it's for IRRE child records:
			if ($fieldConfig['config']['type'] != 'inline') {
				if ($defaultLanguageValue !== '') {
					$item .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 0) . $this->getMergeBehaviourIcon($fieldConfig['l10n_mode']) . $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $field) . '&nbsp;</div>';
				}
				$previewLanguages = $this->getAdditionalPreviewLanguages();
				foreach ($previewLanguages as $previewLanguage) {
					$defaultLanguageValue = BackendUtility::getProcessedValue($table, $field, $this->additionalPreviewLanguageData[$table . ':' . $row['uid']][$previewLanguage['uid']][$field], 0, 1);
					if ($defaultLanguageValue !== '') {
						$item .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, ('v' . $previewLanguage['ISOcode'])) . $this->getMergeBehaviourIcon($fieldConfig['l10n_mode']) . $this->previewFieldValue($defaultLanguageValue, $fieldConfig, $field) . '&nbsp;</div>';
					}
				}
			}
		}
		return $item;
	}

	/**
	 * Renders the diff-view of default language record content compared with what the record was originally translated from.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData, depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param string $table Table name of the record being edited
	 * @param string $field Field name represented by $item
	 * @param array $row Record array of the record being edited
	 * @param string  $item HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 * @todo Define visibility
	 */
	public function renderDefaultLanguageDiff($table, $field, $row, $item) {
		if (is_array($this->defaultLanguageData_diff[$table . ':' . $row['uid']])) {
			// Initialize:
			$dLVal = array(
				'old' => $this->defaultLanguageData_diff[$table . ':' . $row['uid']],
				'new' => $this->defaultLanguageData[$table . ':' . $row['uid']]
			);
			// There must be diff-data:
			if (isset($dLVal['old'][$field])) {
				if ((string)$dLVal['old'][$field] !== (string)$dLVal['new'][$field]) {
					// Create diff-result:
					$t3lib_diff_Obj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\DiffUtility');
					$diffres = $t3lib_diff_Obj->makeDiffDisplay(BackendUtility::getProcessedValue($table, $field, $dLVal['old'][$field], 0, 1), BackendUtility::getProcessedValue($table, $field, $dLVal['new'][$field], 0, 1));
					$item .= '<div class="typo3-TCEforms-diffBox">' . '<div class="typo3-TCEforms-diffBox-header">' . htmlspecialchars($this->getLL('l_changeInOrig')) . ':</div>' . $diffres . '</div>';
				}
			}
		}
		return $item;
	}

	/**
	 * Renders the diff-view of vDEF fields in flexforms
	 *
	 * @param array $vArray Record array of the record being edited
	 * @param string $vDEFkey HTML of the form field. This is what we add the content to.
	 * @return string Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 * @todo Define visibility
	 */
	public function renderVDEFDiff($vArray, $vDEFkey) {
		$item = NULL;
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && isset($vArray[$vDEFkey . '.vDEFbase'])
			&& (string)$vArray[$vDEFkey . '.vDEFbase'] !== (string)$vArray['vDEF']
		) {
			// Create diff-result:
			$t3lib_diff_Obj = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\DiffUtility');
			$diffres = $t3lib_diff_Obj->makeDiffDisplay($vArray[$vDEFkey . '.vDEFbase'], $vArray['vDEF']);
			$item = '<div class="typo3-TCEforms-diffBox">' . '<div class="typo3-TCEforms-diffBox-header">' . htmlspecialchars($this->getLL('l_changeInOrig')) . ':</div>' . $diffres . '</div>';
		}
		return $item;
	}

	/************************************************************
	 *
	 * Form element helper functions
	 *
	 ************************************************************/
	/**
	 * Prints the selector box form-field for the db/file/select elements (multiple)
	 *
	 * @param string $fName Form element name
	 * @param string $mode Mode "db", "file" (internal_type for the "group" type) OR blank (then for the "select" type)
	 * @param string $allowed Commalist of "allowed
	 * @param array $itemArray The array of items. For "select" and "group"/"file" this is just a set of value. For "db" its an array of arrays with table/uid pairs.
	 * @param string $selector Alternative selector box.
	 * @param array $params An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails
	 * @param string $onFocus On focus attribute string
	 * @param string $table (optional) Table name processing for
	 * @param string $field (optional) Field of table name processing for
	 * @param string $uid (optional) uid of table record processing for
	 * @param array $config (optional) The TCA field config
	 * @return string The form fields for the selection.
	 * @todo Define visibility
	 */
	public function dbFileIcons($fName, $mode, $allowed, $itemArray, $selector = '', $params = array(), $onFocus = '', $table = '', $field = '', $uid = '', $config = array()) {
		$title = '';
		$disabled = '';
		if ($this->renderReadonly || $params['readOnly']) {
			$disabled = ' disabled="disabled"';
		}
		// Sets a flag which means some JavaScript is included on the page to support this element.
		$this->printNeededJS['dbFileIcons'] = 1;
		// INIT
		$uidList = array();
		$opt = array();
		$itemArrayC = 0;
		// Creating <option> elements:
		if (is_array($itemArray)) {
			$itemArrayC = count($itemArray);
			switch ($mode) {
				case 'db':
					foreach ($itemArray as $pp) {
						$pRec = BackendUtility::getRecordWSOL($pp['table'], $pp['id']);
						if (is_array($pRec)) {
							$pTitle = BackendUtility::getRecordTitle($pp['table'], $pRec, FALSE, TRUE);
							$pUid = $pp['table'] . '_' . $pp['id'];
							$uidList[] = $pUid;
							$title = htmlspecialchars($pTitle);
							$opt[] = '<option value="' . htmlspecialchars($pUid) . '" title="' . $title . '">' . $title . '</option>';
						}
					}
					break;
				case 'file_reference':

				case 'file':
					foreach ($itemArray as $item) {
						$itemParts = explode('|', $item);
						$uidList[] = ($pUid = ($pTitle = $itemParts[0]));
						$title = htmlspecialchars(rawurldecode($itemParts[1]));
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($itemParts[0])) . '" title="' . $title . '">' . $title . '</option>';
					}
					break;
				case 'folder':
					foreach ($itemArray as $pp) {
						$pParts = explode('|', $pp);
						$uidList[] = ($pUid = ($pTitle = $pParts[0]));
						$title = htmlspecialchars(rawurldecode($pParts[0]));
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($pParts[0])) . '" title="' . $title . '">' . $title . '</option>';
					}
					break;
				default:
					foreach ($itemArray as $pp) {
						$pParts = explode('|', $pp, 2);
						$uidList[] = ($pUid = $pParts[0]);
						$pTitle = $pParts[1];
						$title = htmlspecialchars(rawurldecode($pTitle));
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($pUid)) . '" title="' . $title . '">' . $title . '</option>';
					}
			}
		}
		// Create selector box of the options
		$sSize = $params['autoSizeMax'] ? MathUtility::forceIntegerInRange($itemArrayC + 1, MathUtility::forceIntegerInRange($params['size'], 1), $params['autoSizeMax']) : $params['size'];
		if (!$selector) {
			$isMultiple = $params['maxitems'] != 1 && $params['size'] != 1;
			$selector = '<select id="' . uniqid('tceforms-multiselect-') . '" ' . ($params['noList'] ? 'style="display: none"' : 'size="' . $sSize . '"' . $this->insertDefStyle('group', 'tceforms-multiselect')) . ($isMultiple ? ' multiple="multiple"' : '') . ' name="' . $fName . '_list" ' . $onFocus . $params['style'] . $disabled . '>' . implode('', $opt) . '</select>';
		}
		$icons = array(
			'L' => array(),
			'R' => array()
		);
		if (!$params['readOnly'] && !$params['noList']) {
			if (!$params['noBrowser']) {
				// Check against inline uniqueness
				$inlineParent = $this->inline->getStructureLevel(-1);
				if (is_array($inlineParent) && $inlineParent['uid']) {
					if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
						$objectPrefix = $this->inline->inlineNames['object'] . InlineElement::Structure_Separator . $table;
						$aOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
						$rOnClickInline = 'inline.revertUnique(\'' . $objectPrefix . '\',null,\'' . $uid . '\');';
					}
				}
				if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserType'])) {
					$elementBrowserType = $config['appearance']['elementBrowserType'];
				} else {
					$elementBrowserType = $mode;
				}
				if (is_array($config['appearance']) && isset($config['appearance']['elementBrowserAllowed'])) {
					$elementBrowserAllowed = $config['appearance']['elementBrowserAllowed'];
				} else {
					$elementBrowserAllowed = $allowed;
				}
				$aOnClick = 'setFormValueOpenBrowser(\'' . $elementBrowserType . '\',\'' . ($fName . '|||' . $elementBrowserAllowed . '|' . $aOnClickInline) . '\'); return false;';
				$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . IconUtility::getSpriteIcon('actions-insert-record', array('title' => htmlspecialchars($this->getLL(('l_browse_' . ($mode == 'db' ? 'db' : 'file')))))) . '</a>';
			}
			if (!$params['dontShowMoveIcons']) {
				if ($sSize >= 5) {
					$icons['L'][] = IconUtility::getSpriteIcon('actions-move-to-top', array(
						'data-fieldname' => $fName,
						'class' => 't3-btn t3-btn-moveoption-top',
						'title' => htmlspecialchars($this->getLL('l_move_to_top'))
					));
				}
				$icons['L'][] = IconUtility::getSpriteIcon('actions-move-up', array(
					'data-fieldname' => $fName,
					'class' => 't3-btn t3-btn-moveoption-up',
					'title' => htmlspecialchars($this->getLL('l_move_up'))
				));
				$icons['L'][] = IconUtility::getSpriteIcon('actions-move-down', array(
					'data-fieldname' => $fName,
					'class' => 't3-btn t3-btn-moveoption-down',
					'title' => htmlspecialchars($this->getLL('l_move_down'))
				));
				if ($sSize >= 5) {
					$icons['L'][] = IconUtility::getSpriteIcon('actions-move-to-bottom', array(
						'data-fieldname' => $fName,
						'class' => 't3-btn t3-btn-moveoption-bottom',
						'title' => htmlspecialchars($this->getLL('l_move_to_bottom'))
					));
				}
			}
			$clipElements = $this->getClipboardElements($allowed, $mode);
			if (count($clipElements)) {
				$aOnClick = '';
				foreach ($clipElements as $elValue) {
					if ($mode == 'db') {
						list($itemTable, $itemUid) = explode('|', $elValue);
						$itemTitle = GeneralUtility::quoteJSvalue(BackendUtility::getRecordTitle($itemTable, BackendUtility::getRecordWSOL($itemTable, $itemUid)));
						$elValue = $itemTable . '_' . $itemUid;
					} else {
						// 'file', 'file_reference' and 'folder' mode
						$itemTitle = 'unescape(\'' . rawurlencode(basename($elValue)) . '\')';
					}
					$aOnClick .= 'setFormValueFromBrowseWin(\'' . $fName . '\',unescape(\'' . rawurlencode(str_replace('%20', ' ', $elValue)) . '\'),' . $itemTitle . ',' . $itemTitle . ');';
				}
				$aOnClick .= 'return false;';
				$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' . IconUtility::getSpriteIcon('actions-document-paste-into', array('title' => htmlspecialchars(sprintf($this->getLL(('l_clipInsert_' . ($mode == 'db' ? 'db' : 'file'))), count($clipElements))))) . '</a>';
			}
		}
		if (!$params['readOnly'] && !$params['noDelete']) {
			$icons['L'][] = IconUtility::getSpriteIcon('actions-selection-delete', array(
				'onclick' => $rOnClickInline,
				'data-fieldname' => $fName,
				'class' => 't3-btn t3-btn-removeoption',
				'title' => htmlspecialchars($this->getLL('l_remove_selected'))

			));
		}
		$imagesOnly = FALSE;
		if ($params['thumbnails'] && $params['info']) {
			// In case we have thumbnails, check if only images are allowed.
			// In this case, render them below the field, instead of to the right
			$allowedExtensionList = GeneralUtility::trimExplode(' ', strtolower($params['info']), TRUE);
			$imageExtensionList = GeneralUtility::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), TRUE);
			$imagesOnly = TRUE;
			foreach ($allowedExtensionList as $allowedExtension) {
				if (!GeneralUtility::inArray($imageExtensionList, $allowedExtension)) {
					$imagesOnly = FALSE;
					break;
				}
			}
		}
		if ($imagesOnly) {
			$rightbox = '';
			$thumbnails = '<div class="imagethumbs">' . $this->wrapLabels($params['thumbnails']) . '</div>';
		} else {
			$rightbox = $this->wrapLabels($params['thumbnails']);
			$thumbnails = '';
		}
		// Hook: dbFileIcons_postProcess (requested by FAL-team for use with the "fal" extension)
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'] as $classRef) {
				$hookObject = GeneralUtility::getUserObj($classRef);
				if (!$hookObject instanceof \TYPO3\CMS\Backend\Form\DatabaseFileIconsHookInterface) {
					throw new \UnexpectedValueException('$hookObject must implement interface TYPO3\\CMS\\Backend\\Form\\DatabaseFileIconsHookInterface', 1290167704);
				}
				$additionalParams = array(
					'mode' => $mode,
					'allowed' => $allowed,
					'itemArray' => $itemArray,
					'onFocus' => $onFocus,
					'table' => $table,
					'field' => $field,
					'uid' => $uid,
					'config' => $GLOBALS['TCA'][$table]['columns'][$field]
				);
				$hookObject->dbFileIcons_postProcess($params, $selector, $thumbnails, $icons, $rightbox, $fName, $uidList, $additionalParams, $this);
			}
		}
		$str = '<table border="0" cellpadding="0" cellspacing="0" width="1">
			' . ($params['headers'] ? '
				<tr>
					<td>' . $this->wrapLabels($params['headers']['selector']) . '</td>
					<td></td>
					<td></td>
					<td>' . ($params['thumbnails'] ? $this->wrapLabels($params['headers']['items']) : '') . '</td>
				</tr>' : '') . '
			<tr>
				<td valign="top">' . $selector . $thumbnails;
		if (!$params['noList'] && $params['info'] !== '') {
			$str .= '<span class="filetypes">' . $this->wrapLabels($params['info']) . '</span>';
		}
		$str .= '</td>
					<td valign="top" class="icons">' . implode('<br />', $icons['L']) . '</td>
					<td valign="top" class="icons">' . implode('<br />', $icons['R']) . '</td>
					<td valign="top">' . $rightbox . '</td>
			</tr>
		</table>';
		// Creating the hidden field which contains the actual value as a comma list.
		$str .= '<input type="hidden" name="' . $fName . '" value="' . htmlspecialchars(implode(',', $uidList)) . '" />';
		return $str;
	}

	/**
	 * Returns array of elements from clipboard to insert into GROUP element box.
	 *
	 * @param string $allowed Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png
	 * @param string $mode Mode of relations: "db" or "file
	 * @return array Array of elements in values (keys are insignificant), if none found, empty array.
	 * @todo Define visibility
	 */
	public function getClipboardElements($allowed, $mode) {
		$output = array();
		if (is_object($this->clipObj)) {
			switch ($mode) {
				case 'file_reference':

				case 'file':
					$elFromTable = $this->clipObj->elFromTable('_FILE');
					$allowedExts = GeneralUtility::trimExplode(',', $allowed, TRUE);
					// If there are a set of allowed extensions, filter the content:
					if ($allowedExts) {
						foreach ($elFromTable as $elValue) {
							$pI = pathinfo($elValue);
							$ext = strtolower($pI['extension']);
							if (in_array($ext, $allowedExts)) {
								$output[] = $elValue;
							}
						}
					} else {
						// If all is allowed, insert all: (This does NOT respect any disallowed extensions, but those will be filtered away by the backend TCEmain)
						$output = $elFromTable;
					}
					break;
				case 'db':
					$allowedTables = GeneralUtility::trimExplode(',', $allowed, TRUE);
					// All tables allowed for relation:
					if (trim($allowedTables[0]) === '*') {
						$output = $this->clipObj->elFromTable('');
					} else {
						// Only some tables, filter them:
						foreach ($allowedTables as $tablename) {
							$elFromTable = $this->clipObj->elFromTable($tablename);
							$output = array_merge($output, $elFromTable);
						}
					}
					$output = array_keys($output);
					break;
			}
		}
		return $output;
	}

	/**
	 * Wraps the icon of a relation item (database record or file) in a link opening the context menu for the item.
	 * Icons will be wrapped only if $this->enableClickMenu is set. This must be done only if a global SOBE object exists and if the necessary JavaScript for displaying the context menus has been added to the page properties.
	 *
	 * @param string $str The icon HTML to wrap
	 * @param string $table Table name (eg. "pages" or "tt_content") OR the absolute path to the file
	 * @param integer $uid The uid of the record OR if file, just blank value.
	 * @return string HTML
	 * @todo Define visibility
	 */
	public function getClickMenu($str, $table, $uid = '') {
		if ($this->enableClickMenu) {
			$onClick = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($str, $table, $uid, 1, '', '+copy,info,edit,view', TRUE);
			return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $str . '</a>';
		}
	}

	/**
	 * Rendering wizards for form fields.
	 *
	 * @param array $itemKinds Array with the real item in the first value, and an alternative item in the second value.
	 * @param array $wizConf The "wizard" key from the config array for the field (from TCA)
	 * @param string $table Table name
	 * @param array $row The record array
	 * @param string $field The field name
	 * @param array $PA Additional configuration array. (passed by reference!)
	 * @param string $itemName The field name
	 * @param array $specConf Special configuration if available.
	 * @param boolean $RTE Whether the RTE could have been loaded.
	 * @return string The new item value.
	 * @todo Define visibility
	 */
	public function renderWizards($itemKinds, $wizConf, $table, $row, $field, &$PA, $itemName, $specConf, $RTE = 0) {
		// Init:
		$fieldChangeFunc = $PA['fieldChangeFunc'];
		$item = $itemKinds[0];
		$outArr = array();
		$colorBoxLinks = array();
		$fName = '[' . $table . '][' . $row['uid'] . '][' . $field . ']';
		$md5ID = 'ID' . GeneralUtility::shortmd5($itemName);
		$listFlag = '_list';
		$fieldConfig = $PA['fieldConf']['config'];
		$prefixOfFormElName = 'data[' . $table . '][' . $row['uid'] . '][' . $field . ']';
		if (GeneralUtility::isFirstPartOfStr($PA['itemFormElName'], $prefixOfFormElName)) {
			$flexFormPath = str_replace('][', '/', substr($PA['itemFormElName'], strlen($prefixOfFormElName) + 1, -1));
		}
		// Manipulate the field name (to be the TRUE form field name) and remove a suffix-value if the item is a selector box with renderMode "singlebox":
		if ($PA['fieldConf']['config']['form_type'] == 'select') {
			// Single select situation:
			if ($PA['fieldConf']['config']['maxitems'] <= 1) {
				$listFlag = '';
			} elseif ($PA['fieldConf']['config']['renderMode'] == 'singlebox') {
				$itemName .= '[]';
				$listFlag = '';
			}
		}
		// Traverse wizards:
		if (is_array($wizConf) && !$this->disableWizards) {
			$parametersOfWizards = &$specConf['wizards']['parameters'];
			foreach ($wizConf as $wid => $wConf) {
				if ($wid[0] !== '_' && (!$wConf['enableByTypeConfig'] || is_array($parametersOfWizards) && in_array($wid, $parametersOfWizards)) && ($RTE || !$wConf['RTEonly'])) {
					// Title / icon:
					$iTitle = htmlspecialchars($this->sL($wConf['title']));
					if ($wConf['icon']) {
						$icon = $this->getIconHtml($wConf['icon'], $iTitle, $iTitle);
					} else {
						$icon = $iTitle;
					}
					switch ((string) $wConf['type']) {
						case 'userFunc':

						case 'script':

						case 'popup':

						case 'colorbox':

						case 'slider':
							if (!$wConf['notNewRecords'] || MathUtility::canBeInterpretedAsInteger($row['uid'])) {
								// Setting &P array contents:
								$params = array();
								// Including the full fieldConfig from TCA may produce too long an URL
								if ($wid != 'RTE') {
									$params['fieldConfig'] = $fieldConfig;
								}
								$params['params'] = $wConf['params'];
								$params['exampleImg'] = $wConf['exampleImg'];
								$params['table'] = $table;
								$params['uid'] = $row['uid'];
								$params['pid'] = $row['pid'];
								$params['field'] = $field;
								$params['flexFormPath'] = $flexFormPath;
								$params['md5ID'] = $md5ID;
								$params['returnUrl'] = $this->thisReturnUrl();

								$wScript = '';
								// Resolving script filename and setting URL.
								if (isset($wConf['module']['name'])) {
									$urlParameters = array();
									if (isset($wConf['module']['urlParameters']) && is_array($wConf['module']['urlParameters'])) {
										$urlParameters = $wConf['module']['urlParameters'];
									}
									$wScript = BackendUtility::getModuleUrl($wConf['module']['name'], $urlParameters);
								} elseif (isset($wConf['script'])) {
									GeneralUtility::deprecationLog(
										'The way registering a wizard in TCA has changed in 6.2. '
										. 'Please set module[name]=module_name instead of using script=path/to/sctipt.php in your TCA. '
										. 'The possibility to register wizards this way will be removed in 2 versions.'
									);
									if (substr($wConf['script'], 0, 4) === 'EXT:') {
										$wScript = GeneralUtility::getFileAbsFileName($wConf['script']);
										if ($wScript) {
											$wScript = '../' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($wScript);
										} else {
											// Illeagal configuration, fail silently
											break;
										}
									} else {
										// Compatibility layer
										// @deprecated since 6.2, will be removed 2 versions later
										$parsedWizardUrl = parse_url($wConf['script']);
										if (in_array($parsedWizardUrl['path'], array(
													'wizard_add.php',
													'wizard_colorpicker.php',
													'wizard_edit.php',
													'wizard_forms.php',
													'wizard_list.php',
													'wizard_rte.php',
													'wizard_table.php',
													'browse_links.php',
													'sysext/cms/layout/wizard_backend_layout.php'
												))
										) {
											$urlParameters = array();
											if (isset($parsedWizardUrl['query'])) {
												 parse_str($parsedWizardUrl['query'], $urlParameters);
											}
											$moduleName = str_replace(
												array('.php', 'browse_links', 'sysext/cms/layout/wizard_backend_layout'),
												array('', 'wizard_element_browser', 'wizard_backend_layout'),
												$parsedWizardUrl['path']
											);
											$wScript = BackendUtility::getModuleUrl($moduleName, $urlParameters);
											unset($moduleName, $urlParameters, $parsedWizardUrl);
										} else {
											$wScript = $wConf['script'];
										}
									}
								} elseif (in_array($wConf['type'], array('script', 'colorbox', 'popup'), TRUE)) {
									// Illegal configuration, fail silently
									break;
								}

								$url = $this->backPath . $wScript . (strstr($wScript, '?') ? '' : '?');
								// If "script" type, create the links around the icon:
								if ((string) $wConf['type'] === 'script') {
									$aUrl = $url . GeneralUtility::implodeArrayForUrl('', array('P' => $params));
									$outArr[] = '<a href="' . htmlspecialchars($aUrl) . '" onclick="this.blur(); return !TBE_EDITOR.isFormChanged();">' . $icon . '</a>';
								} else {
									// ... else types "popup", "colorbox" and "userFunc" will need additional parameters:
									$params['formName'] = $this->formName;
									$params['itemName'] = $itemName;
									$params['hmac'] = GeneralUtility::hmac($params['formName'] . $params['itemName'], 'wizard_js');
									$params['fieldChangeFunc'] = $fieldChangeFunc;
									$params['fieldChangeFuncHash'] = GeneralUtility::hmac(serialize($fieldChangeFunc));
									switch ((string) $wConf['type']) {
										case 'popup':
										case 'colorbox':
											// Current form value is passed as P[currentValue]!
											$addJS = $wConf['popup_onlyOpenIfSelected'] ? 'if (!TBE_EDITOR.curSelected(\'' . $itemName . $listFlag . '\')){alert(' . GeneralUtility::quoteJSvalue($this->getLL('m_noSelItemForEdit')) . '); return false;}' : '';
											$curSelectedValues = '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(\'' . $itemName . $listFlag . '\')';
											$aOnClick = 'this.blur();' . $addJS . 'vHWin=window.open(\'' . $url . GeneralUtility::implodeArrayForUrl('', array('P' => $params)) . '\'+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(' . $this->elName($itemName) . '.value,200)' . $curSelectedValues . ',\'popUp' . $md5ID . '\',\'' . $wConf['JSopenParams'] . '\');' . 'vHWin.focus();return false;';
											// Setting "colorBoxLinks" - user LATER to wrap around the color box as well:
											$colorBoxLinks = array('<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">', '</a>');
											if ((string) $wConf['type'] == 'popup') {
												$outArr[] = $colorBoxLinks[0] . $icon . $colorBoxLinks[1];
											}
											break;
										case 'userFunc':
											// Reference set!
											$params['item'] = &$item;
											$params['icon'] = $icon;
											$params['iTitle'] = $iTitle;
											$params['wConf'] = $wConf;
											$params['row'] = $row;
											$outArr[] = GeneralUtility::callUserFunction($wConf['userFunc'], $params, $this);
											break;
										case 'slider':
											// Reference set!
											$params['item'] = &$item;
											$params['icon'] = $icon;
											$params['iTitle'] = $iTitle;
											$params['wConf'] = $wConf;
											$params['row'] = $row;
											$wizard = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\Element\\ValueSlider');
											$outArr[] = call_user_func_array(array(&$wizard, 'renderWizard'), array(&$params, &$this));
											break;
									}
								}
								// Hide the real form element?
								if (is_array($wConf['hideParent']) || $wConf['hideParent']) {
									// Setting the item to a hidden-field.
									$item = $itemKinds[1];
									if (is_array($wConf['hideParent'])) {
										$item .= $this->getSingleField_typeNone_render($wConf['hideParent'], $PA['itemFormElValue']);
									}
								}
							}
							break;
						case 'select':
							$fieldValue = array('config' => $wConf);
							$TSconfig = $this->setTSconfig($table, $row);
							$TSconfig[$field] = $TSconfig[$field]['wizards.'][$wid . '.'];
							$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($fieldValue), $fieldValue, $TSconfig, $field);
							// Process items by a user function:
							if (!empty($wConf['itemsProcFunc'])) {
								$funcConfig = !empty($wConf['itemsProcFunc.']) ? $wConf['itemsProcFunc.'] : array();
								$selItems = $this->procItems($selItems, $funcConfig, $wConf, $table, $row, $field);
							}
							$opt = array();
							$opt[] = '<option>' . $iTitle . '</option>';
							foreach ($selItems as $p) {
								$opt[] = '<option value="' . htmlspecialchars($p[1]) . '">' . htmlspecialchars($p[0]) . '</option>';
							}
							if ($wConf['mode'] == 'append') {
								$assignValue = $this->elName($itemName) . '.value=\'\'+this.options[this.selectedIndex].value+' . $this->elName($itemName) . '.value';
							} elseif ($wConf['mode'] == 'prepend') {
								$assignValue = $this->elName($itemName) . '.value+=\'\'+this.options[this.selectedIndex].value';
							} else {
								$assignValue = $this->elName($itemName) . '.value=this.options[this.selectedIndex].value';
							}
							$sOnChange = $assignValue . ';this.blur();this.selectedIndex=0;' . implode('', $fieldChangeFunc);
							$outArr[] = '<select id="' . uniqid('tceforms-select-') . '" class="tceforms-select tceforms-wizardselect" name="_WIZARD' . $fName . '" onchange="' . htmlspecialchars($sOnChange) . '">' . implode('', $opt) . '</select>';
							break;
						case 'suggest':
							if (isset($PA['fieldTSConfig']['suggest.']['default.']['hide']) && (bool) $PA['fieldTSConfig']['suggest.']['default.']['hide'] == TRUE) {
								break;
							}
							$outArr[] = $this->suggest->renderSuggestSelector($PA['itemFormElName'], $table, $field, $row, $PA);
							break;
					}
					// Color wizard colorbox:
					if ((string) $wConf['type'] === 'colorbox') {
						$dim = GeneralUtility::intExplode('x', $wConf['dim']);
						$dX = MathUtility::forceIntegerInRange($dim[0], 1, 200, 20);
						$dY = MathUtility::forceIntegerInRange($dim[1], 1, 200, 20);
						$color = $PA['itemFormElValue'] ? ' bgcolor="' . htmlspecialchars($PA['itemFormElValue']) . '"' : '';
						$outArr[] = '<table border="0" cellpadding="0" cellspacing="0" id="' . $md5ID . '"' . $color . ' style="' . htmlspecialchars($wConf['tableStyle']) . '">
									<tr>
										<td>' . $colorBoxLinks[0] . '<img ' . IconUtility::skinImg($this->backPath, ($color === '' ? 'gfx/colorpicker_empty.png' : 'gfx/colorpicker.png'), ('width="' . $dX . '" height="' . $dY . '"' . BackendUtility::titleAltAttrib(trim(($iTitle . ' ' . $PA['itemFormElValue']))) . ' border="0"')) . '>' . $colorBoxLinks[1] . '</td>
									</tr>
								</table>';
					}
				}
			}
			// For each rendered wizard, put them together around the item.
			if (count($outArr)) {
				if ($wizConf['_HIDDENFIELD']) {
					$item = $itemKinds[1];
				}
				$outStr = '';
				$vAlign = $wizConf['_VALIGN'] ? ' style="vertical-align:' . $wizConf['_VALIGN'] . '"' : '';
				if (count($outArr) > 1 || $wizConf['_PADDING']) {
					$dist = (int)$wizConf['_DISTANCE'];
					if ($wizConf['_VERTICAL']) {
						$dist = $dist ? '<tr><td><img src="clear.gif" width="1" height="' . $dist . '" alt="" /></td></tr>' : '';
						$outStr = '<tr><td>' . implode(('</td></tr>' . $dist . '<tr><td>'), $outArr) . '</td></tr>';
					} else {
						$dist = $dist ? '<td><img src="clear.gif" height="1" width="' . $dist . '" alt="" /></td>' : '';
						$outStr = '<tr><td' . $vAlign . '>' . implode(('</td>' . $dist . '<td' . $vAlign . '>'), $outArr) . '</td></tr>';
					}
					$outStr = '<table border="0" cellpadding="' . (int)$wizConf['_PADDING'] . '" cellspacing="' . (int)$wizConf['_PADDING'] . '">' . $outStr . '</table>';
				} else {
					$outStr = implode('', $outArr);
				}
				if ($wizConf['_POSITION'] === 'left') {
					$outStr = '<tr><td' . $vAlign . '>' . $outStr . '</td><td' . $vAlign . '>' . $item . '</td></tr>';
				} elseif ($wizConf['_POSITION'] === 'top') {
					$outStr = '<tr><td>' . $outStr . '</td></tr><tr><td>' . $item . '</td></tr>';
				} elseif ($wizConf['_POSITION'] === 'bottom') {
					$outStr = '<tr><td>' . $item . '</td></tr><tr><td>' . $outStr . '</td></tr>';
				} else {
					$outStr = '<tr><td' . $vAlign . '>' . $item . '</td><td' . $vAlign . '>' . $outStr . '</td></tr>';
				}
				$item = '<table border="0" cellpadding="0" cellspacing="0">' . $outStr . '</table>';
			}
		}
		return $item;
	}

	/**
	 * Get icon (for example for selector boxes)
	 *
	 * @param string $icon Icon reference
	 * @return array Array with two values; the icon file reference (relative to PATH_typo3 minus backPath), the icon file information array (getimagesize())
	 * @todo Define visibility
	 */
	public function getIcon($icon) {
		if (substr($icon, 0, 4) == 'EXT:') {
			$file = GeneralUtility::getFileAbsFileName($icon);
			if ($file) {
				$file = \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($file);
				$selIconFile = $this->backPath . '../' . $file;
				$selIconInfo = @getimagesize((PATH_site . $file));
			}
		} elseif (substr($icon, 0, 3) == '../') {
			$selIconFile = $this->backPath . GeneralUtility::resolveBackPath($icon);
			$selIconInfo = @getimagesize((PATH_site . GeneralUtility::resolveBackPath(substr($icon, 3))));
		} elseif (substr($icon, 0, 4) == 'ext/' || substr($icon, 0, 7) == 'sysext/') {
			$selIconFile = $this->backPath . $icon;
			$selIconInfo = @getimagesize((PATH_typo3 . $icon));
		} else {
			$selIconFile = IconUtility::skinImg($this->backPath, 'gfx/' . $icon, '', 1);
			$iconPath = substr($selIconFile, strlen($this->backPath));
			$selIconInfo = @getimagesize((PATH_typo3 . $iconPath));
		}
		if ($selIconInfo === FALSE) {
			// Unset to empty string if icon is not available
			$selIconFile = '';
		}
		return array($selIconFile, $selIconInfo);
	}

	/**
	 * Renders the $icon, supports a filename for skinImg or sprite-icon-name
	 *
	 * @param string $icon The icon passed, could be a file-reference or a sprite Icon name
	 * @param string $alt Alt attribute of the icon returned
	 * @param string $title Title attribute of the icon return
	 * @return A tag representing to show the asked icon
	 */
	protected function getIconHtml($icon, $alt = '', $title = '') {
		$iconArray = $this->getIcon($icon);
		if (is_file(GeneralUtility::resolveBackPath(PATH_typo3 . PATH_typo3_mod . $iconArray[0]))) {
			return '<img src="' . $iconArray[0] . '" alt="' . $alt . '" ' . ($title ? 'title="' . $title . '"' : '') . ' />';
		} else {
			return IconUtility::getSpriteIcon($icon, array('alt' => $alt, 'title' => $title));
		}
	}

	/**
	 * Creates style attribute content for option tags in a selector box, primarily setting it up to show the icon of an element as background image (works in mozilla)
	 *
	 * @param string $iconString Icon string for option item
	 * @return string Style attribute content, if any
	 * @todo Define visibility
	 */
	public function optionTagStyle($iconString) {
		if ($iconString) {
			list($selIconFile, $selIconInfo) = $this->getIcon($iconString);
			if (empty($selIconFile)) {
				// Skip background style if image is unavailable
				return '';
			}
			$padLeft = $selIconInfo[0] + 4;
			if ($padLeft >= 18 && $padLeft <= 24) {
				// In order to get the same padding for all option tags even if icon sizes differ a little, set it to 22 if it was between 18 and 24 pixels
				$padLeft = 22;
			}
			$padTop = MathUtility::forceIntegerInRange(($selIconInfo[1] - 12) / 2, 0);
			$styleAttr = 'background: #fff url(' . $selIconFile . ') 0% 50% no-repeat; height: ' . MathUtility::forceIntegerInRange(($selIconInfo[1] + 2 - $padTop), 0) . 'px; padding-top: ' . $padTop . 'px; padding-left: ' . $padLeft . 'px;';
			return $styleAttr;
		}
	}

	/**
	 * Creates style attribute content for optgroup tags in a selector box, primarily setting it up to show the icon of an element as background image (works in mozilla).
	 *
	 * @param string $iconString Icon string for option item
	 * @return string Style attribute content, if any
	 * @todo Define visibility
	 */
	public function optgroupTagStyle($iconString) {
		if ($iconString) {
			list($selIconFile, $selIconInfo) = $this->getIcon($iconString);
			if (empty($selIconFile)) {
				// Skip background style if image is unavailable
				return '';
			}
			$padLeft = $selIconInfo[0] + 4;
			if ($padLeft >= 18 && $padLeft <= 24) {
				// In order to get the same padding for all option tags even if icon sizes differ a little,
				// set it to 22, if it was between 18 and 24 pixels.
				$padLeft = 22;
			}
			$padTop = MathUtility::forceIntegerInRange(($selIconInfo[1] - 12) / 2, 0);
			return 'background: #ffffff url(' . $selIconFile . ') 0 0 no-repeat; padding-top: ' . $padTop . 'px; padding-left: ' . $padLeft . 'px;';
		}
	}

	/**
	 * Extracting values from a value/label list (as made by transferData class)
	 *
	 * @param array $itemFormElValue Values in an array
	 * @return array Input string exploded with comma and for each value only the label part is set in the array. Keys are numeric
	 * @todo Define visibility
	 */
	public function extractValuesOnlyFromValueLabelList($itemFormElValue) {
		// Get values of selected items:
		$itemArray = GeneralUtility::trimExplode(',', $itemFormElValue, TRUE);
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$tvP[0] = rawurldecode($tvP[0]);
			$itemArray[$tk] = $tvP[0];
		}
		return $itemArray;
	}

	/**
	 * Wraps a string with a link to the palette.
	 *
	 * @param string $header The string to wrap in an A-tag
	 * @param string $table The table name for which to open the palette.
	 * @param array $row The palette pointer.
	 * @param integer $palette The record array
	 * @param mixed $retFunc Not used
	 * @return array
	 * @todo Define visibility
	 */
	public function wrapOpenPalette($header, $table, $row, $palette, $retFunc) {
		$id = 'TCEFORMS_' . $table . '_' . $palette . '_' . $row['uid'];
		$res = '<a href="#" onclick="TBE_EDITOR.toggle_display_states(\'' . $id . '\',\'block\',\'none\'); return false;" >' . $header . '</a>';
		return array($res, '');
	}

	/**
	 * Add the id and the style property to the field palette
	 *
	 * @param string $code Palette Code
	 * @param string $table The table name for which to open the palette.
	 * @param string $row Palette ID
	 * @param string $palette The record array
	 * @param boolean $collapsed TRUE if collapsed
	 * @return boolean Is collapsed
	 * @todo Define visibility
	 */
	public function wrapPaletteField($code, $table, $row, $palette, $collapsed) {
		$display = $collapsed ? 'none' : 'block';
		$id = 'TCEFORMS_' . $table . '_' . $palette . '_' . $row['uid'];
		$code = '<div id="' . $id . '" style="display:' . $display . ';" >' . $code . '</div>';
		return $code;
	}

	/**
	 * Creates checkbox parameters
	 *
	 * @param string $itemName Form element name
	 * @param integer $thisValue The value of the checkbox (representing checkboxes with the bits)
	 * @param integer $c Checkbox # (0-9?)
	 * @param integer $iCount Total number of checkboxes in the array.
	 * @param string $addFunc Additional JavaScript for the onclick handler.
	 * @return string The onclick attribute + possibly the checked-option set.
	 * @todo Define visibility
	 */
	public function checkBoxParams($itemName, $thisValue, $c, $iCount, $addFunc = '') {
		$onClick = $this->elName($itemName) . '.value=this.checked?(' . $this->elName($itemName) . '.value|' . pow(2, $c) . '):(' . $this->elName($itemName) . '.value&' . (pow(2, $iCount) - 1 - pow(2, $c)) . ');' . $addFunc;
		$str = ' onclick="' . htmlspecialchars($onClick) . '"' . ($thisValue & pow(2, $c) ? ' checked="checked"' : '');
		return $str;
	}

	/**
	 * Returns element reference for form element name
	 *
	 * @param string $itemName Form element name
	 * @return string Form element reference (JS)
	 * @todo Define visibility
	 */
	public function elName($itemName) {
		return 'document.' . $this->formName . '[\'' . $itemName . '\']';
	}

	/**
	 * Returns the "returnUrl" of the form. Can be set externally or will be taken from "GeneralUtility::linkThisScript()"
	 *
	 * @return string Return URL of current script
	 * @todo Define visibility
	 */
	public function thisReturnUrl() {
		return $this->returnUrl ? $this->returnUrl : GeneralUtility::linkThisScript();
	}

	/**
	 * Returns the form field for a single HIDDEN field.
	 * (Not used anywhere...?)
	 *
	 * @param string $table Table name
	 * @param string $field Field name
	 * @param array $row The row
	 * @return string The hidden-field <input> tag.
	 * @todo Define visibility
	 */
	public function getSingleHiddenField($table, $field, $row) {
		$item = '';
		if ($GLOBALS['TCA'][$table]['columns'][$field]) {
			$uid = $row['uid'];
			$itemName = $this->prependFormFieldNames . '[' . $table . '][' . $uid . '][' . $field . ']';
			$itemValue = $row[$field];
			$item = '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars($itemValue) . '" />';
		}
		return $item;
	}

	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param integer $size The abstract size value (1-48)
	 * @param boolean $textarea If this is for a text area.
	 * @return string Either a "style" attribute string or "cols"/"size" attribute string.
	 * @todo Define visibility
	 */
	public function formWidth($size = 48, $textarea = 0) {
		$widthAndStyleAttributes = '';
		$fieldWidthAndStyle = $this->formWidthAsArray($size, $textarea);
		// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
		$widthAndStyleAttributes = ' style="' . htmlspecialchars($fieldWidthAndStyle['style']) . '"';
		if ($fieldWidthAndStyle['class']) {
			$widthAndStyleAttributes .= ' class="' . htmlspecialchars($fieldWidthAndStyle['class']) . '"';
		}
		return $widthAndStyleAttributes;
	}

	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param integer $size The abstract size value (1-48)
	 * @param boolean $textarea If set, calculates sizes for a text area.
	 * @return array An array containing style, class, and width attributes.
	 */
	protected function formWidthAsArray($size = 48, $textarea = FALSE) {
		$fieldWidthAndStyle = array('style' => '', 'class' => '', 'width' => '');
		$size = round($size * $this->form_largeComp);

		// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
		$widthInPixels = ceil($size * $this->form_rowsToStylewidth);

		if ($textarea) {
			$widthInPixels += $this->form_additionalTextareaStyleWidth;
		}

		$fieldWidthAndStyle['style'] = 'width: ' . $widthInPixels . 'px; ' . $this->defStyle . $this->formElStyle(($textarea ? 'text' : 'input'));
		$fieldWidthAndStyle['class'] = $this->formElClass($textarea ? 'text' : 'input');
		return $fieldWidthAndStyle;
	}

	/**
	 * Returns parameters to set with for a textarea field
	 *
	 * @param integer $size The abstract width (1-48)
	 * @param string $wrap Empty or "off" (text wrapping in the field or not)
	 * @return string The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 * @todo Define visibility
	 */
	public function formWidthText($size = 48, $wrap = '') {
		$wTags = $this->formWidth($size, TRUE);
		// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap)) != 'off' && $GLOBALS['CLIENT']['BROWSER'] == 'net' && $GLOBALS['CLIENT']['VERSION'] >= 5) {
			$wTags .= ' cols="' . $size . '"';
		}
		return $wTags;
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param string $type Field type (eg. "check", "radio", "select")
	 * @return string CSS attributes
	 * @see formElStyleClassValue()
	 * @todo Define visibility
	 */
	public function formElStyle($type) {
		return $this->formElStyleClassValue($type);
	}

	/**
	 * Get class attribute value for the current field type.
	 *
	 * @param string $type Field type (eg. "check", "radio", "select")
	 * @return string CSS attributes
	 * @see formElStyleClassValue()
	 * @todo Define visibility
	 */
	public function formElClass($type) {
		return $this->formElStyleClassValue($type, TRUE);
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param string $type Field type (eg. "check", "radio", "select")
	 * @param boolean $class If set, will return value only if prefixed with CLASS, otherwise must not be prefixed "CLASS
	 * @return string CSS attributes
	 * @todo Define visibility
	 */
	public function formElStyleClassValue($type, $class = FALSE) {
		// Get value according to field:
		if (isset($this->fieldStyle[$type])) {
			$style = trim($this->fieldStyle[$type]);
		} else {
			$style = trim($this->fieldStyle['all']);
		}
		// Check class prefixed:
		if (substr($style, 0, 6) == 'CLASS:') {
			$out = $class ? trim(substr($style, 6)) : '';
		} else {
			$out = !$class ? $style : '';
		}
		return $out;
	}

	/**
	 * Return default "style" / "class" attribute line.
	 *
	 * @param string $type Field type (eg. "check", "radio", "select")
	 * @param string $additionalClass Additional class(es) to be added
	 * @return string CSS attributes
	 * @todo Define visibility
	 */
	public function insertDefStyle($type, $additionalClass = '') {
		$out = '';
		$style = trim($this->defStyle . $this->formElStyle($type));
		$out .= $style ? ' style="' . htmlspecialchars($style) . '"' : '';
		$class = $this->formElClass($type);
		$classAttributeValue = join(' ', array_filter(array($class, $additionalClass)));
		$out .= $classAttributeValue ? ' class="' . htmlspecialchars($classAttributeValue) . '"' : '';
		return $out;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param array $parts Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param string $idString ID string for the tab menu
	 * @param integer $dividersToTabsBehaviour If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return string HTML for the menu
	 * @todo Define visibility
	 */
	public function getDynTabMenu($parts, $idString, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			$GLOBALS['TBE_TEMPLATE']->backPath = $this->backPath;
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString, 0, FALSE, 1, FALSE, 1, $dividersToTabsBehaviour);
		} else {
			$output = '';
			foreach ($parts as $singlePad) {
				$output .= '
				<h3>' . htmlspecialchars($singlePad['label']) . '</h3>
				' . ($singlePad['description'] ? '<p class="c-descr">' . nl2br(htmlspecialchars($singlePad['description'])) . '</p>' : '') . '
				' . $singlePad['content'];
			}
			return '<div class="typo3-dyntabmenu-divs">' . $output . '</div>';
		}
	}

	/************************************************************
	 *
	 * Item-array manipulation functions (check/select/radio)
	 *
	 ************************************************************/
	/**
	 * Initialize item array (for checkbox, selectorbox, radio buttons)
	 * Will resolve the label value.
	 *
	 * @param array $fieldValue The "columns" array for the field (from TCA)
	 * @return array An array of arrays with three elements; label, value, icon
	 * @todo Define visibility
	 */
	public function initItemArray($fieldValue) {
		$items = array();
		if (is_array($fieldValue['config']['items'])) {
			foreach ($fieldValue['config']['items'] as $itemValue) {
				$items[] = array($this->sL($itemValue[0]), $itemValue[1], $itemValue[2]);
			}
		}
		return $items;
	}

	/**
	 * Merges items into an item-array
	 *
	 * @param array $items The existing item array
	 * @param array $iArray An array of items to add. NOTICE: The keys are mapped to values, and the values and mapped to be labels. No possibility of adding an icon.
	 * @return array The updated $item array
	 * @todo Define visibility
	 */
	public function addItems($items, $iArray) {
		if (is_array($iArray)) {
			foreach ($iArray as $value => $label) {
				$items[] = array($this->sl($label), $value);
			}
		}
		return $items;
	}

	/**
	 * Perform user processing of the items arrays of checkboxes, selectorboxes and radio buttons.
	 *
	 * @param array $items The array of items (label,value,icon)
	 * @param array $iArray The "itemsProcFunc." from fieldTSconfig of the field.
	 * @param array $config The config array for the field.
	 * @param string $table Table name
	 * @param array $row Record row
	 * @param string $field Field name
	 * @return array The modified $items array
	 * @todo Define visibility
	 */
	public function procItems($items, $iArray, $config, $table, $row, $field) {
		$params = array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;
		// The itemsProcFunc method may throw an exception.
		// If it does display an error message and return items unchanged.
		try {
			GeneralUtility::callUserFunction($config['itemsProcFunc'], $params, $this);
		} catch (\Exception $exception) {
			$fieldLabel = $field;
			if (isset($GLOBALS['TCA'][$table]['columns'][$field]['label'])) {
				$fieldLabel = $this->sL($GLOBALS['TCA'][$table]['columns'][$field]['label']);
			}
			$message = sprintf(
				$this->sL('LLL:EXT:lang/locallang_core.xlf:error.items_proc_func_error'),
				$fieldLabel,
				$exception->getMessage()
			);
			/** @var $flashMessage FlashMessage */
			$flashMessage = GeneralUtility::makeInstance(
				'TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
				htmlspecialchars($message),
				'',
				FlashMessage::ERROR,
				TRUE
			);
			$class = 'TYPO3\\CMS\\Core\\Messaging\\FlashMessageService';
			/** @var $flashMessageService \TYPO3\CMS\Core\Messaging\FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance($class);
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
		}
		return $items;
	}

	/**
	 * Add selector box items of more exotic kinds.
	 *
	 * @param array $items The array of items (label,value,icon)
	 * @param array $fieldValue The "columns" array for the field (from TCA)
	 * @param array $TSconfig TSconfig for the table/row
	 * @param string $field The fieldname
	 * @return array The $items array modified.
	 * @todo Define visibility
	 */
	public function addSelectOptionsToItemArray($items, $fieldValue, $TSconfig, $field) {
		// Values from foreign tables:
		if ($fieldValue['config']['foreign_table']) {
			$items = $this->foreignTable($items, $fieldValue, $TSconfig, $field);
			if ($fieldValue['config']['neg_foreign_table']) {
				$items = $this->foreignTable($items, $fieldValue, $TSconfig, $field, 1);
			}
		}
		// Values from a file folder:
		if ($fieldValue['config']['fileFolder']) {
			$fileFolder = GeneralUtility::getFileAbsFileName($fieldValue['config']['fileFolder']);
			if (@is_dir($fileFolder)) {
				// Configurations:
				$extList = $fieldValue['config']['fileFolder_extList'];
				$recursivityLevels = isset($fieldValue['config']['fileFolder_recursions']) ? MathUtility::forceIntegerInRange($fieldValue['config']['fileFolder_recursions'], 0, 99) : 99;
				// Get files:
				$fileFolder = rtrim($fileFolder, '/') . '/';
				$fileArr = GeneralUtility::getAllFilesAndFoldersInPath(array(), $fileFolder, $extList, 0, $recursivityLevels);
				$fileArr = GeneralUtility::removePrefixPathFromList($fileArr, $fileFolder);
				foreach ($fileArr as $fileRef) {
					$fI = pathinfo($fileRef);
					$icon = GeneralUtility::inList('gif,png,jpeg,jpg', strtolower($fI['extension'])) ? '../' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($fileFolder) . $fileRef : '';
					$items[] = array(
						$fileRef,
						$fileRef,
						$icon
					);
				}
			}
		}
		// If 'special' is configured:
		if ($fieldValue['config']['special']) {
			switch ($fieldValue['config']['special']) {
				case 'tables':
					$temp_tc = array_keys($GLOBALS['TCA']);
					foreach ($temp_tc as $theTableNames) {
						if (!$GLOBALS['TCA'][$theTableNames]['ctrl']['adminOnly']) {
							// Icon:
							$icon = IconUtility::mapRecordTypeToSpriteIconName($theTableNames, array());
							// Add help text
							$helpText = array();
							$GLOBALS['LANG']->loadSingleTableDescription($theTableNames);
							$helpTextArray = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
							if (!empty($helpTextArray['description'])) {
								$helpText['description'] = $helpTextArray['description'];
							}
							// Item configuration:
							$items[] = array(
								$this->sL($GLOBALS['TCA'][$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$helpText
							);
						}
					}
					break;
				case 'pagetypes':
					$theTypes = $GLOBALS['TCA']['pages']['columns']['doktype']['config']['items'];
					foreach ($theTypes as $theTypeArrays) {
						// Icon:
						$icon = 'empty-emtpy';
						if ($theTypeArrays[1] != '--div--') {
							$icon = IconUtility::mapRecordTypeToSpriteIconName('pages', array('doktype' => $theTypeArrays[1]));
						}
						// Item configuration:
						$items[] = array(
							$this->sL($theTypeArrays[0]),
							$theTypeArrays[1],
							$icon
						);
					}
					break;
				case 'exclude':
					$theTypes = BackendUtility::getExcludeFields();
					foreach ($theTypes as $theTypeArrays) {
						list($theTable, $theFullField) = explode(':', $theTypeArrays[1]);
						// If the field comes from a FlexForm, the syntax is more complex
						$theFieldParts = explode(';', $theFullField);
						$theField = array_pop($theFieldParts);
						// Add header if not yet set for table:
						if (!array_key_exists($theTable, $items)) {
							$icon = IconUtility::mapRecordTypeToSpriteIconName($theTable, array());
							$items[$theTable] = array(
								$this->sL($GLOBALS['TCA'][$theTable]['ctrl']['title']),
								'--div--',
								$icon
							);
						}
						// Add help text
						$helpText = array();
						$GLOBALS['LANG']->loadSingleTableDescription($theTable);
						$helpTextArray = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theFullField];
						if (!empty($helpTextArray['description'])) {
							$helpText['description'] = $helpTextArray['description'];
						}
						// Item configuration:
						$items[] = array(
							rtrim($GLOBALS['LANG']->sl($GLOBALS['TCA'][$theTable]['columns'][$theField]['label']), ':') . ' (' . $theField . ')',
							$theTypeArrays[1],
							'empty-empty',
							$helpText
						);
					}
					break;
				case 'explicitValues':
					$theTypes = BackendUtility::getExplicitAuthFieldValues();
					// Icons:
					$icons = array(
						'ALLOW' => 'status-status-permission-granted',
						'DENY' => 'status-status-permission-denied'
					);
					// Traverse types:
					foreach ($theTypes as $tableFieldKey => $theTypeArrays) {
						if (is_array($theTypeArrays['items'])) {
							// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--'
							);
							// Traverse options for this field:
							foreach ($theTypeArrays['items'] as $itemValue => $itemContent) {
								// Add item to be selected:
								$items[] = array(
									'[' . $itemContent[2] . '] ' . $itemContent[1],
									$tableFieldKey . ':' . preg_replace('/[:|,]/', '', $itemValue) . ':' . $itemContent[0],
									$icons[$itemContent[0]]
								);
							}
						}
					}
					break;
				case 'languages':
					$items = array_merge($items, BackendUtility::getSystemLanguages());
					break;
				case 'custom':
					// Initialize:
					$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
					if (is_array($customOptions)) {
						foreach ($customOptions as $coKey => $coValue) {
							if (is_array($coValue['items'])) {
								// Add header:
								$items[] = array(
									$GLOBALS['LANG']->sl($coValue['header']),
									'--div--'
								);
								// Traverse items:
								foreach ($coValue['items'] as $itemKey => $itemCfg) {
									// Icon:
									if ($itemCfg[1]) {
										list($icon) = $this->getIcon($itemCfg[1]);
									} else {
										$icon = 'empty-empty';
									}
									// Add help text
									$helpText = array();
									if (!empty($itemCfg[2])) {
										$helpText['description'] = $GLOBALS['LANG']->sl($itemCfg[2]);
									}
									// Add item to be selected:
									$items[] = array(
										$GLOBALS['LANG']->sl($itemCfg[0]),
										$coKey . ':' . preg_replace('/[:|,]/', '', $itemKey),
										$icon,
										$helpText
									);
								}
							}
						}
					}
					break;
				case 'modListGroup':

				case 'modListUser':
					$loadModules = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Module\\ModuleLoader');
					$loadModules->load($GLOBALS['TBE_MODULES']);
					$modList = $fieldValue['config']['special'] == 'modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList)) {
						foreach ($modList as $theMod) {
							// Icon:
							$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$theMod . '_tab'];
							if ($icon) {
								$icon = '../' . \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($icon);
							}
							// Add help text
							$helpText = array(
								'title' => $GLOBALS['LANG']->moduleLabels['labels'][$theMod . '_tablabel'],
								'description' => $GLOBALS['LANG']->moduleLabels['labels'][$theMod . '_tabdescr']
							);
							// Item configuration:
							$items[] = array(
								$this->addSelectOptionsToItemArray_makeModuleData($theMod),
								$theMod,
								$icon,
								$helpText
							);
						}
					}
					break;
			}
		}
		// Return the items:
		return $items;
	}

	/**
	 * Creates value/label pair for a backend module (main and sub)
	 *
	 * @param string $value The module key
	 * @return string The rawurlencoded 2-part string to transfer to interface
	 * @access private
	 * @see addSelectOptionsToItemArray()
	 * @todo Define visibility
	 */
	public function addSelectOptionsToItemArray_makeModuleData($value) {
		$label = '';
		// Add label for main module:
		$pp = explode('_', $value);
		if (count($pp) > 1) {
			$label .= $GLOBALS['LANG']->moduleLabels['tabs'][($pp[0] . '_tab')] . '>';
		}
		// Add modules own label now:
		$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$value . '_tab'];
		return $label;
	}

	/**
	 * Adds records from a foreign table (for selector boxes)
	 *
	 * @param array $items The array of items (label,value,icon)
	 * @param array $fieldValue The 'columns' array for the field (from TCA)
	 * @param array $TSconfig TSconfig for the table/row
	 * @param string $field The fieldname
	 * @param boolean $pFFlag If set, then we are fetching the 'neg_' foreign tables.
	 * @return array The $items array modified.
	 * @see addSelectOptionsToItemArray(), BackendUtility::exec_foreign_table_where_query()
	 * @todo Define visibility
	 */
	public function foreignTable($items, $fieldValue, $TSconfig, $field, $pFFlag = 0) {
		// Init:
		$pF = $pFFlag ? 'neg_' : '';
		$f_table = $fieldValue['config'][$pF . 'foreign_table'];
		$uidPre = $pFFlag ? '-' : '';
		// Exec query:
		$res = BackendUtility::exec_foreign_table_where_query($fieldValue, $field, $TSconfig, $pF);
		// Perform error test
		if ($GLOBALS['TYPO3_DB']->sql_error()) {
			$msg = htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error());
			$msg .= '<br />' . LF;
			$msg .= $this->sL('LLL:EXT:lang/locallang_core.xlf:error.database_schema_mismatch');
			$msgTitle = $this->sL('LLL:EXT:lang/locallang_core.xlf:error.database_schema_mismatch_title');
			/** @var $flashMessage FlashMessage */
			$flashMessage = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', $msg, $msgTitle, FlashMessage::ERROR, TRUE);
			/** @var $flashMessageService FlashMessageService */
			$flashMessageService = GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessageService');
			/** @var $defaultFlashMessageQueue FlashMessageQueue */
			$defaultFlashMessageQueue = $flashMessageService->getMessageQueueByIdentifier();
			$defaultFlashMessageQueue->enqueue($flashMessage);
			return array();
		}
		// Get label prefix.
		$lPrefix = $this->sL($fieldValue['config'][$pF . 'foreign_table_prefix']);
		// Get icon field + path if any:
		$iField = $GLOBALS['TCA'][$f_table]['ctrl']['selicon_field'];
		$iPath = trim($GLOBALS['TCA'][$f_table]['ctrl']['selicon_field_path']);
		// Traverse the selected rows to add them:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			BackendUtility::workspaceOL($f_table, $row);
			if (is_array($row)) {
				// Prepare the icon if available:
				if ($iField && $iPath && $row[$iField]) {
					$iParts = GeneralUtility::trimExplode(',', $row[$iField], TRUE);
					$icon = '../' . $iPath . '/' . trim($iParts[0]);
				} elseif (GeneralUtility::inList('singlebox,checkbox', $fieldValue['config']['renderMode'])) {
					$icon = IconUtility::mapRecordTypeToSpriteIconName($f_table, $row);
				} else {
					$icon = '';
				}
				// Add the item:
				$items[] = array(
					$lPrefix . htmlspecialchars(BackendUtility::getRecordTitle($f_table, $row)),
					$uidPre . $row['uid'],
					$icon
				);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $items;
	}

	/********************************************
	 *
	 * Template functions
	 *
	 ********************************************/
	/**
	 * Sets the design to the backend design.
	 * Backend
	 *
	 * @return 	void
	 * @todo Define visibility
	 */
	public function setNewBEDesign() {
		$template = GeneralUtility::getUrl(PATH_typo3 . $this->templateFile);
		// Wrapping all table rows for a particular record being edited:
		$this->totalWrap = HtmlParser::getSubpart($template, '###TOTALWRAP###');
		// Wrapping a single field:
		$this->fieldTemplate = HtmlParser::getSubpart($template, '###FIELDTEMPLATE###');
		$this->paletteFieldTemplate = HtmlParser::getSubpart($template, '###PALETTEFIELDTEMPLATE###');
		$this->palFieldTemplate = HtmlParser::getSubpart($template, '###PALETTE_FIELDTEMPLATE###');
		$this->palFieldTemplateHeader = HtmlParser::getSubpart($template, '###PALETTE_FIELDTEMPLATE_HEADER###');
		$this->sectionWrap = HtmlParser::getSubpart($template, '###SECTION_WRAP###');
	}

	/**
	 * This inserts the content of $inArr into the field-template
	 *
	 * @param array $inArr Array with key/value pairs to insert in the template.
	 * @param string $altTemplate Alternative template to use instead of the default.
	 * @return string
	 * @todo Define visibility
	 */
	public function intoTemplate($inArr, $altTemplate = '') {
		// Put into template_
		$fieldTemplateParts = explode('###FIELD_', $this->rplColorScheme($altTemplate ? $altTemplate : $this->fieldTemplate));
		$out = current($fieldTemplateParts);
		foreach ($fieldTemplateParts as $part) {
			list($key, $val) = explode('###', $part, 2);
			$out .= $inArr[$key];
			$out .= $val;
		}
		return $out;
	}

	/**
	 * Overwrite this function in own extended class to add own markers for output
	 *
	 * @param array $marker Array with key/value pairs to insert in the template.
	 * @param string $table The table name of the record
	 * @param string $field The field name which this element is supposed to edit
	 * @param array $row The record data array where the value(s) for the field can be found
	 * @param array $PA An array with additional configuration options.
	 * @return array Marker array for template output
	 * @see function intoTemplate()
	 * @todo Define visibility
	 */
	public function addUserTemplateMarkers($marker, $table, $field, $row, &$PA) {
		return $marker;
	}

	/**
	 * Wrapping labels
	 * Currently not implemented - just returns input value.
	 *
	 * @param string $str Input string.
	 * @return string Output string.
	 * @todo Define visibility
	 */
	public function wrapLabels($str) {
		return $str;
	}

	/**
	 * Wraps all the table rows into a single table.
	 * Used externally from scripts like alt_doc.php and db_layout.php (which uses TCEforms...)
	 *
	 * @param string $c Code to output between table-parts; table rows
	 * @param array $rec The record
	 * @param string $table The table name
	 * @return string
	 * @todo Define visibility
	 */
	public function wrapTotal($c, $rec, $table) {
		$parts = $this->replaceTableWrap(explode('|', $this->totalWrap, 2), $rec, $table);
		return $parts[0] . $c . $parts[1] . implode('', $this->hiddenFieldAccum);
	}

	/**
	 * Generates a token and returns an input field with it
	 *
	 * @param string $formName Context of the token
	 * @param string $tokenName The name of the token GET/POST variable
	 * @return string A complete input field
	 */
	static public function getHiddenTokenField($formName = 'securityToken', $tokenName = 'formToken') {
		$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
		return '<input type="hidden" name="' . $tokenName . '" value="' . $formprotection->generateToken($formName) . '" />';
	}

	/**
	 * This replaces markers in the total wrap
	 *
	 * @param array $arr An array of template parts containing some markers.
	 * @param array $rec The record
	 * @param string $table The table name
	 * @return string
	 * @todo Define visibility
	 */
	public function replaceTableWrap($arr, $rec, $table) {
		// Make "new"-label
		if (strstr($rec['uid'], 'NEW')) {
			$newLabel = ' <span class="typo3-TCEforms-newToken">' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.new', TRUE) . '</span>';
			// BackendUtility::fixVersioningPid Should not be used here because NEW records are not offline workspace versions...
			$truePid = BackendUtility::getTSconfig_pidValue($table, $rec['uid'], $rec['pid']);
			$prec = BackendUtility::getRecordWSOL('pages', $truePid, 'title');
			$pageTitle = BackendUtility::getRecordTitle('pages', $prec, TRUE, FALSE);
			$rLabel = '<em>[PID: ' . $truePid . '] ' . $pageTitle . '</em>';
			// Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecord', TRUE);
				if ($rec['pid'] == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.createNewRecordRootLevel', TRUE);
				}
				$pageTitle = sprintf($label, $tableTitle, $pageTitle);
			}
		} else {
			$newLabel = ' <span class="typo3-TCEforms-recUid">[' . $rec['uid'] . ']</span>';
			$rLabel = BackendUtility::getRecordTitle($table, $rec, TRUE, FALSE);
			$prec = BackendUtility::getRecordWSOL('pages', $rec['pid'], 'uid,title');
			// Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.editPage', TRUE);
				// Just take the record title and prepend an edit label.
				$pageTitle = sprintf($label, $tableTitle, $rLabel);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecord', TRUE);
				$pageTitle = BackendUtility::getRecordTitle('pages', $prec, TRUE, FALSE);
				if ($rLabel === BackendUtility::getNoRecordTitle(TRUE)) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordNoTitle', TRUE);
				}
				if ($rec['pid'] == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.editRecordRootLevel', TRUE);
				}
				if ($rLabel !== BackendUtility::getNoRecordTitle(TRUE)) {
					// Just take the record title and prepend an edit label.
					$pageTitle = sprintf($label, $tableTitle, $rLabel, $pageTitle);
				} else {
					// Leave out the record title since it is not set.
					$pageTitle = sprintf($label, $tableTitle, $pageTitle);
				}
			}
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
					htmlspecialchars($this->sL($GLOBALS['TCA'][$table]['ctrl']['title'])),
					IconUtility::getSpriteIconForRecord($table, $rec, array('title' => $this->getRecordPath($table, $rec)))
				),
				$arr[$k]
			);
		}
		return $arr;
	}

	/**
	 * Wraps an element in the $out_array with the template row for a "section" ($this->sectionWrap)
	 *
	 * @param array $out_array The array with form elements stored in (passed by reference and changed!)
	 * @param integer $out_pointer The pointer to the entry in the $out_array  (passed by reference and incremented!)
	 * @return void
	 * @todo Define visibility
	 */
	public function wrapBorder(&$out_array, &$out_pointer) {
		if ($this->sectionWrap && $out_array[$out_pointer]) {
			$tableAttribs = '';
			$tableAttribs .= $this->borderStyle[0] ? ' style="' . htmlspecialchars($this->borderStyle[0]) . '"' : '';
			$tableAttribs .= $this->borderStyle[2] ? ' background="' . htmlspecialchars(($this->backPath . $this->borderStyle[2])) . '"' : '';
			$tableAttribs .= $this->borderStyle[3] ? ' class="' . htmlspecialchars($this->borderStyle[3]) . '"' : '';
			if ($tableAttribs) {
				$tableAttribs = 'border="0" cellspacing="0" cellpadding="0" width="100%"' . $tableAttribs;
				$out_array[$out_pointer] = str_replace('###CONTENT###', $out_array[$out_pointer], str_replace('###TABLE_ATTRIBS###', $tableAttribs, $this->sectionWrap));
			}
			$out_pointer++;
		}
	}

	/**
	 * Replaces colorscheme markers in the template string
	 *
	 * @param string $inTemplate Template string with markers to be substituted.
	 * @return string
	 * @todo Define visibility
	 */
	public function rplColorScheme($inTemplate) {
		return str_replace(
			array(
				// Colors:
				'###BGCOLOR###',
				'###BGCOLOR_HEAD###',
				'###FONTCOLOR_HEAD###',
				// Classes:
				'###CLASSATTR_1###',
				'###CLASSATTR_2###',
				'###CLASSATTR_4###'
			),
			array(
				// Colors:
				$this->colorScheme[0] ? ' bgcolor="' . $this->colorScheme[0] . '"' : '',
				$this->colorScheme[1] ? ' bgcolor="' . $this->colorScheme[1] . '"' : '',
				$this->colorScheme[3],
				// Classes:
				$this->classScheme[0] ? ' class="' . $this->classScheme[0] . '"' : '',
				$this->classScheme[1] ? ' class="' . $this->classScheme[1] . '"' : '',
				$this->classScheme[3] ? ' class="' . $this->classScheme[3] . '"' : ''
			),
			$inTemplate
		);
	}

	/**
	 * Returns divider.
	 * Currently not implemented and returns only blank value.
	 *
	 * @return string Empty string
	 * @todo Define visibility
	 */
	public function getDivider() {
		return '';
	}

	/**
	 * Creates HTML output for a palette
	 *
	 * @param array $palArr The palette array to print
	 * @return string HTML output
	 * @todo Define visibility
	 */
	public function printPalette($palArr) {
		$fieldAttributes = ($labelAttributes = '');
		// Init color/class attributes:
		if ($this->colorScheme[2]) {
			$labelAttributes .= ' bgcolor="' . $this->colorScheme[2] . '"';
		}
		if ($this->classScheme[2]) {
			$labelAttributes .= ' class="t3-form-palette-field-label ' . $this->classScheme[2] . '"';
		} else {
			$labelAttributes .= ' class="t3-form-palette-field-label"';
		}
		if ($this->colorScheme[4]) {
			$fieldAttributes .= ' style="color: ' . $this->colorScheme[4] . '"';
		}
		if ($this->classScheme[4]) {
			$fieldAttributes .= ' class="t3-form-palette-field ' . $this->classScheme[4] . '"';
		}
		$row = 0;
		$hRow = ($iRow = array());
		$lastLineWasLinebreak = FALSE;
		// Traverse palette fields and render them into containers:
		foreach ($palArr as $content) {
			if ($content['NAME'] === '--linebreak--') {
				if (!$lastLineWasLinebreak) {
					$row++;
					$lastLineWasLinebreak = TRUE;
				}
			} else {
				$lastLineWasLinebreak = FALSE;

				$paletteMarkers = array(
					'###CONTENT_TABLE###' => $content['TABLE'],
					'###CONTENT_ID###' => $content['ID'],
					'###CONTENT_FIELD###' => $content['FIELD'],
					'###CONTENT_NAME###' => $content['NAME'],
					'###CONTENT_ITEM###' => $content['ITEM'],
					'###CONTENT_ITEM_NULLVALUE###' => $content['ITEM_NULLVALUE'],
					'###CONTENT_ITEM_DISABLED###' => $content['ITEM_DISABLED'],
					'###ATTRIBUTES_LABEL###' => $labelAttributes,
					'###ATTRIBUTES_FIELD###' => $fieldAttributes,
				);
				$iRow[$row][] = HtmlParser::substituteMarkerArray(
					$this->paletteFieldTemplate,
					$paletteMarkers,
					FALSE,
					TRUE
				);
			}
		}
		// Final wrapping into the fieldset:
		$out = '<fieldset class="t3-form-palette-fieldset">';
		for ($i = 0; $i <= $row; $i++) {
			if (isset($iRow[$i])) {
				$out .= implode('', $iRow[$i]);
				$out .= $i < $row ? '<br />' : '';
			}
		}
		$out .= '</fieldset>';
		return $out;
	}

	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defColorScheme plus input string.
	 *
	 * @param string $scheme A color scheme string.
	 * @return void
	 * @todo Define visibility
	 */
	public function setColorScheme($scheme) {
		$this->colorScheme = $this->defColorScheme;
		$this->classScheme = $this->defClassScheme;
		$parts = GeneralUtility::trimExplode(',', $scheme);
		foreach ($parts as $key => $col) {
			// Split for color|class:
			list($color, $class) = GeneralUtility::trimExplode('|', $col);
			// Handle color values:
			if ($color) {
				$this->colorScheme[$key] = $color;
			}
			if ($color == '-') {
				$this->colorScheme[$key] = '';
			}
			// Handle class values:
			if ($class) {
				$this->classScheme[$key] = $class;
			}
			if ($class == '-') {
				$this->classScheme[$key] = '';
			}
		}
	}

	/**
	 * Reset color schemes.
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function resetSchemes() {
		$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
		$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
		$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
	}

	/**
	 * Store current color scheme
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function storeSchemes() {
		$this->savedSchemes['classScheme'] = $this->classScheme;
		$this->savedSchemes['colorScheme'] = $this->colorScheme;
		$this->savedSchemes['fieldStyle'] = $this->fieldStyle;
		$this->savedSchemes['borderStyle'] = $this->borderStyle;
	}

	/**
	 * Restore the saved color scheme
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function restoreSchemes() {
		$this->classScheme = $this->savedSchemes['classScheme'];
		$this->colorScheme = $this->savedSchemes['colorScheme'];
		$this->fieldStyle = $this->savedSchemes['fieldStyle'];
		$this->borderStyle = $this->savedSchemes['borderStyle'];
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
	 * @todo Define visibility
	 */
	public function JStop() {
		$out = '';
		// Additional top HTML:
		if (count($this->additionalCode_pre)) {
			$out .= implode('

				<!-- NEXT: -->
			', $this->additionalCode_pre);
		}
		// Additional top JavaScript
		if (count($this->additionalJS_pre)) {
			$out .= '


		<!--
			JavaScript in top of page (before form):
		-->

		<script type="text/javascript">
			/*<![CDATA[*/

			' . implode('

					// NEXT:
			', $this->additionalJS_pre) . '

			/*]]>*/
		</script>
			';
		}
		// Return result:
		return $out;
	}

	/**
	 * JavaScript code used for input-field evaluation.
	 *
	 * Example use:
	 *
	 * $msg .= 'Distribution time (hh:mm dd-mm-yy):<br /><input type="text" name="send_mail_datetime_hr" onchange="typo3form.fieldGet(\'send_mail_datetime\', \'datetime\', \'\', 0,0);"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /><input type="hidden" value="' . $GLOBALS['EXEC_TIME'] . '" name="send_mail_datetime" /><br />';
	 * $this->extJSCODE.='typo3form.fieldSet("send_mail_datetime", "datetime", "", 0,0);';
	 *
	 * ... and then include the result of this function after the form
	 *
	 * @param string $formname The identification of the form on the page.
	 * @param boolean $update Just extend/update existing settings, e.g. for AJAX call
	 * @return string A section with JavaScript - if $update is FALSE, embedded in <script></script>
	 * @todo Define visibility
	 */
	public function JSbottom($formname = 'forms[0]', $update = FALSE) {
		$jsFile = array();
		$elements = array();
		$out = '';
		// Required:
		foreach ($this->requiredFields as $itemImgName => $itemName) {
			$match = array();
			if (preg_match('/^(.+)\\[((\\w|\\d|_)+)\\]$/', $itemName, $match)) {
				$record = $match[1];
				$field = $match[2];
				$elements[$record][$field]['required'] = 1;
				$elements[$record][$field]['requiredImg'] = $itemImgName;
				if (isset($this->requiredAdditional[$itemName]) && is_array($this->requiredAdditional[$itemName])) {
					$elements[$record][$field]['additional'] = $this->requiredAdditional[$itemName];
				}
			}
		}
		// Range:
		foreach ($this->requiredElements as $itemName => $range) {
			if (preg_match('/^(.+)\\[((\\w|\\d|_)+)\\]$/', $itemName, $match)) {
				$record = $match[1];
				$field = $match[2];
				$elements[$record][$field]['range'] = array($range[0], $range[1]);
				$elements[$record][$field]['rangeImg'] = $range['imgName'];
			}
		}
		$this->TBE_EDITOR_fieldChanged_func = 'TBE_EDITOR.fieldChanged_fName(fName,formObj[fName+"_list"]);';
		if (!$update) {
			if ($this->loadMD5_JS) {
				$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/md5.js');
			}
			$pageRenderer = $this->getPageRenderer();
			// load the main module for FormEngine with all important JS functions
			$pageRenderer->loadRequireJsModule('TYPO3/CMS/Backend/FormEngine');
			$pageRenderer->loadPrototype();
			$pageRenderer->loadJquery();
			$pageRenderer->loadExtJS();
			// Make textareas resizable and flexible
			if (!($GLOBALS['BE_USER']->uc['resizeTextareas'] == '0' && $GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0')) {
				$pageRenderer->addCssFile($this->backPath . 'js/extjs/ux/resize.css');
				$this->loadJavascriptLib('js/extjs/ux/ext.resizable.js');
			}
			$resizableSettings = array(
				'textareaMaxHeight' => $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] > 0 ? $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] : '600',
				'textareaFlexible' => !$GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0',
				'textareaResize' => !$GLOBALS['BE_USER']->uc['resizeTextareas'] == '0'
			);
			$pageRenderer->addInlineSettingArray('', $resizableSettings);
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.evalfield.js');
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tbe_editor.js');
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.placeholder.js');
			// Needed for tceform manipulation (date picker)
			$typo3Settings = array(
				'datePickerUSmode' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 1 : 0,
				'dateFormat' => array('j-n-Y', 'G:i j-n-Y'),
				'dateFormatUS' => array('n-j-Y', 'G:i n-j-Y')
			);
			$pageRenderer->addInlineSettingArray('', $typo3Settings);
			$this->loadJavascriptLib('js/extjs/ux/Ext.ux.DateTimePicker.js');
			$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/tceforms.js');
			// If IRRE fields were processed, add the JavaScript functions:
			if ($this->inline->inlineCount) {
				$pageRenderer->loadScriptaculous();
				// We want to load jQuery-ui inside our js. Enable this using requirejs.
				$pageRenderer->loadRequireJs();
				$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.inline.js');
				$out .= '
				inline.setPrependFormFieldNames("' . $this->inline->prependNaming . '");
				inline.setNoTitleString("' . addslashes(BackendUtility::getNoRecordTitle(TRUE)) . '");
				';
				// Always include JS functions for Suggest fields as we don't know what will come
				$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tceforms_suggest.js');
				$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tceforms_selectboxfilter.js');
			} else {
				// If Suggest fields were processed, add the JS functions
				if ($this->suggest->suggestCount > 0) {
					$pageRenderer->loadScriptaculous();
					$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tceforms_suggest.js');
				}
				if ($this->multiSelectFilterCount > 0) {
					$pageRenderer->loadScriptaculous();
					$this->loadJavascriptLib('sysext/backend/Resources/Public/JavaScript/jsfunc.tceforms_selectboxfilter.js');
				}
			}
			// Toggle icons:
			$toggleIcon_open = IconUtility::getSpriteIcon('actions-move-down', array('title' => 'Open'));
			$toggleIcon_close = IconUtility::getSpriteIcon('actions-move-right', array('title' => 'Close'));
			$out .= '
			function getOuterHTML(idTagPrefix) {	// Function getting the outerHTML of an element with id
				var str=($(idTagPrefix).inspect()+$(idTagPrefix).innerHTML+"</"+$(idTagPrefix).tagName.toLowerCase()+">");
				return str;
			}
			function flexFormToggle(id) {	// Toggling flexform elements on/off:
				Element.toggle(""+id+"-content");

				if (Element.visible(id+"-content")) {
					$(id+"-toggle").update(\'' . $toggleIcon_open . '\');
					$(id+"-toggleClosed").value = 0;
				} else {
					$(id+"-toggle").update(\'' . $toggleIcon_close . '\');
					$(id+"-toggleClosed").value = 1;
				}

				var previewContent = "";
				var children = $(id+"-content").getElementsByTagName("input");
				for (var i = 0, length = children.length; i < length; i++) {
					if (children[i].type=="text" && children[i].value)	previewContent+= (previewContent?" / ":"")+children[i].value;
				}
				if (previewContent.length>80) {
					previewContent = previewContent.substring(0,67)+"...";
				}
				$(id+"-preview").update(previewContent);
			}
			function flexFormToggleSubs(id) {	// Toggling sub flexform elements on/off:
				var descendants = $(id).immediateDescendants();
				var isOpen=0;
				var isClosed=0;
					// Traverse and find how many are open or closed:
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id) {
						if (Element.visible(descendants[i].id+"-content"))	{isOpen++;} else {isClosed++;}
					}
				}

					// Traverse and toggle
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id) {
						if (isOpen!=0 && isClosed!=0) {
							if (Element.visible(descendants[i].id+"-content"))	{flexFormToggle(descendants[i].id);}
						} else {
							flexFormToggle(descendants[i].id);
						}
					}
				}
			}
			function flexFormSortable(id) {	// Create sortables for flexform sections
				Position.includeScrollOffsets = true;
 				Sortable.create(id, {tag:\'div\',constraint: false, onChange:function(){
					setActionStatus(id);
				} });
			}
			function setActionStatus(id) {	// Updates the "action"-status for a section. This is used to move and delete elements.
				var descendants = $(id).immediateDescendants();

					// Traverse and find how many are open or closed:
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id) {
						$(descendants[i].id+"-action").value = descendants[i].visible() ? i : "DELETE";
					}
				}
			}

			TBE_EDITOR.images.req.src = "' . IconUtility::skinImg($this->backPath, 'gfx/required_h.gif', '', 1) . '";
			TBE_EDITOR.images.cm.src = "' . IconUtility::skinImg($this->backPath, 'gfx/content_client.gif', '', 1) . '";
			TBE_EDITOR.images.sel.src = "' . IconUtility::skinImg($this->backPath, 'gfx/content_selected.gif', '', 1) . '";
			TBE_EDITOR.images.clear.src = "' . $this->backPath . 'clear.gif";

			TBE_EDITOR.auth_timeout_field = ' . (int)$GLOBALS['BE_USER']->auth_timeout_field . ';
			TBE_EDITOR.formname = "' . $formname . '";
			TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
			TBE_EDITOR.backPath = "' . addslashes($this->backPath) . '";
			TBE_EDITOR.prependFormFieldNames = "' . $this->prependFormFieldNames . '";
			TBE_EDITOR.prependFormFieldNamesUENC = "' . rawurlencode($this->prependFormFieldNames) . '";
			TBE_EDITOR.prependFormFieldNamesCnt = ' . substr_count($this->prependFormFieldNames, '[') . ';
			TBE_EDITOR.isPalettedoc = ' . ($this->isPalettedoc ? addslashes($this->isPalettedoc) : 'null') . ';
			TBE_EDITOR.doSaveFieldName = "' . ($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '') . '";
			TBE_EDITOR.labels.fieldsChanged = ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsChanged')) . ';
			TBE_EDITOR.labels.fieldsMissing = ' . GeneralUtility::quoteJSvalue($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.fieldsMissing')) . ';
			TBE_EDITOR.labels.refresh_login = ' . GeneralUtility::quoteJSvalue($this->getLL('m_refresh_login')) . ';
			TBE_EDITOR.labels.onChangeAlert = ' . GeneralUtility::quoteJSvalue($this->getLL('m_onChangeAlert')) . ';
			evalFunc.USmode = ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '1' : '0') . ';
			TBE_EDITOR.backend_interface = "' . $GLOBALS['BE_USER']->uc['interfaceSetup'] . '";

			TBE_EDITOR.customEvalFunctions = {};

			';
		}
		// Add JS required for inline fields
		if (count($this->inline->inlineData)) {
			$out .= '
			inline.addToDataArray(' . json_encode($this->inline->inlineData) . ');
			';
		}
		// Registered nested elements for tabs or inline levels:
		if (count($this->requiredNested)) {
			$out .= '
			TBE_EDITOR.addNested(' . json_encode($this->requiredNested) . ');
			';
		}
		// Elements which are required or have a range definition:
		if (count($elements)) {
			$out .= '
			TBE_EDITOR.addElements(' . json_encode($elements) . ');
			TBE_EDITOR.initRequired();
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
		$out .= '
			TBE_EDITOR.loginRefreshed();
		';
		// Regular direct output:
		if (!$update) {
			$spacer = LF . TAB;
			$out = $spacer . implode($spacer, $jsFile) . GeneralUtility::wrapJS($out);
		}
		return $out;
	}

	/**
	 * Used to connect the db/file browser with this document and the formfields on it!
	 *
	 * @param string $formObj Form object reference (including "document.")
	 * @return string JavaScript functions/code (NOT contained in a <script>-element)
	 * @deprecated since TYPO3 6.2, remove two versions later. This is now done in an external file, see printNeededJSfunctions
	 */
	public function dbFileCon($formObj = 'document.forms[0]') {
		GeneralUtility::logDeprecatedFunction();
	}

	/**
	 * Prints necessary JavaScript for TCEforms (after the form HTML).
	 * currently this is used to transform page-specific options in the TYPO3.Settings array for JS
	 * so the JS module can access these values
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printNeededJSFunctions() {

		/** @var $pageRenderer \TYPO3\CMS\Core\Page\PageRenderer */
		$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();

		// set variables to be accessible for JS
		$pageRenderer->addInlineSetting('FormEngine', 'formName', $this->formName);
		$pageRenderer->addInlineSetting('FormEngine', 'backPath', $this->backPath);

		// Integrate JS functions for the element browser if such fields or IRRE fields were processed
		if ($this->printNeededJS['dbFileIcons'] || $this->inline->inlineCount > 0 || $this->suggest->suggestCount > 0) {
			$pageRenderer->addInlineSetting('FormEngine', 'legacyFieldChangedCb', 'function() { ' . $this->TBE_EDITOR_fieldChanged_func . ' };');
		}

		$out = $this->JSbottom($this->formName);
		return $out;
	}

	/**
	 * Returns necessary JavaScript for the top
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printNeededJSFunctions_top() {
		// JS evaluation:
		$out = $this->JStop($this->formName);
		return $out;
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied.
	 * This method acts as wrapper for $GLOBALS['SOBE']->doc->loadJavascriptLib($lib).
	 *
	 * @param string $lib Library name. Call it with the full path like "contrib/prototype/prototype.js" to load it
	 * @return void
	 */
	public function loadJavascriptLib($lib) {
		$GLOBALS['SOBE']->doc->loadJavascriptLib($lib);
	}

	/**
	 * Wrapper for access to the current page renderer object
	 *
	 * @return \TYPO3\CMS\Core\Page\PageRenderer
	 */
	protected function getPageRenderer() {
		return $GLOBALS['SOBE']->doc->getPageRenderer();
	}

	/********************************************
	 *
	 * Various helper functions
	 *
	 ********************************************/
	/**
	 * Gets default record. Maybe not used anymore. FE-editor?
	 *
	 * @param string $table Database Tablename
	 * @param integer $pid PID value (positive / negative)
	 * @return array "default" row.
	 * @todo Define visibility
	 */
	public function getDefaultRecord($table, $pid = 0) {
		if ($GLOBALS['TCA'][$table]) {
			$row = array();
			if ($pid < 0 && $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']) {
				// Fetches the previous record:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . abs($pid) . BackendUtility::deleteClause($table));
				if ($drow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
					// Gets the list of fields to copy from the previous record.
					$fArr = explode(',', $GLOBALS['TCA'][$table]['ctrl']['useColumnsForDefaultValues']);
					foreach ($fArr as $theF) {
						if ($GLOBALS['TCA'][$table]['columns'][$theF]) {
							$row[$theF] = $drow[$theF];
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}
			foreach ($GLOBALS['TCA'][$table]['columns'] as $field => $info) {
				if (isset($info['config']['default'])) {
					$row[$field] = $info['config']['default'];
				}
			}
			return $row;
		}
	}

	/**
	 * Returns TRUE if the given $row is new (i.e. has not been saved to the database)
	 *
	 * @param string $table
	 * @param array $row
	 * @return bool
	 */
	protected function isNewRecord($table, $row) {
		return !MathUtility::canBeInterpretedAsInteger($row['uid']) && GeneralUtility::isFirstPartOfStr($row['uid'], 'NEW');
	}

	/**
	 * Return record path (visually formatted, using BackendUtility::getRecordPath() )
	 *
	 * @param string $table Table name
	 * @param array $rec Record array
	 * @return string The record path.
	 * @see BackendUtility::getRecordPath()
	 * @todo Define visibility
	 */
	public function getRecordPath($table, $rec) {
		BackendUtility::fixVersioningPid($table, $rec);
		list($tscPID, $thePidValue) = $this->getTSCpid($table, $rec['uid'], $rec['pid']);
		if ($thePidValue >= 0) {
			return BackendUtility::getRecordPath($tscPID, $this->readPerms(), 15);
		}
	}

	/**
	 * Returns the select-page read-access SQL clause.
	 * Returns cached string, so you can call this function as much as you like without performance loss.
	 *
	 * @return string
	 * @todo Define visibility
	 */
	public function readPerms() {
		if (!$this->perms_clause_set) {
			$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$this->perms_clause_set = 1;
		}
		return $this->perms_clause;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param string $str Language label reference, eg. 'LLL:EXT:lang/locallang_core.xlf:labels.blablabla'
	 * @return string The value of the label, fetched for the current backend language.
	 * @todo Define visibility
	 */
	public function sL($str) {
		return $GLOBALS['LANG']->sL($str);
	}

	/**
	 * Returns language label from locallang_core.xlf
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.xlf
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.xlf
	 *
	 * @param string $str The label key
	 * @return string The value of the label, fetched for the current backend language.
	 * @todo Define visibility
	 */
	public function getLL($str) {
		$content = '';
		switch (substr($str, 0, 2)) {
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:labels.' . substr($str, 2));
				break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:mess.' . substr($str, 2));
				break;
		}
		return $content;
	}

	/**
	 * Returns TRUE, if the palette, $palette, is collapsed (not shown, but found in top-frame) for the table.
	 *
	 * @param string $table The table name
	 * @param integer $palette The palette pointer/number
	 * @return boolean
	 * @todo Define visibility
	 */
	public function isPalettesCollapsed($table, $palette) {
		if (is_array($GLOBALS['TCA'][$table]['palettes'][$palette]) && $GLOBALS['TCA'][$table]['palettes'][$palette]['isHiddenPalette']) {
			return TRUE;
		}
		if ($GLOBALS['TCA'][$table]['ctrl']['canNotCollapse']) {
			return FALSE;
		}
		if (is_array($GLOBALS['TCA'][$table]['palettes'][$palette]) && $GLOBALS['TCA'][$table]['palettes'][$palette]['canNotCollapse']) {
			return FALSE;
		}
		return $this->palettesCollapsed;
	}

	/**
	 * Returns TRUE, if the evaluation of the required-field code is OK.
	 *
	 * @param string $displayCond The required-field code
	 * @param array $row The record to evaluate
	 * @param string $ffValueKey FlexForm value key, eg. vDEF
	 * @return boolean
	 * @todo Define visibility
	 * @deprecated since TYPO3 6.1, will be removed 2 versions later - Use \TYPO3\CMS\Backend\Form\ElementConditionMatcher instead
	 */
	public function isDisplayCondition($displayCond, $row, $ffValueKey = '') {
		GeneralUtility::logDeprecatedFunction();
		/** @var $elementConditionMatcher \TYPO3\CMS\Backend\Form\ElementConditionMatcher */
		$elementConditionMatcher = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\ElementConditionMatcher');
		return $elementConditionMatcher->match($displayCond, $row, $ffValueKey);
	}

	/**
	 * Return TSCpid (cached)
	 * Using BackendUtility::getTSCpid()
	 *
	 * @param string $table Tablename
	 * @param string $uid UID value
	 * @param string $pid PID value
	 * @return array Array of two integers; first is the real PID of a record, second is the PID value for TSconfig.
	 * @see BackendUtility::getTSCpid()
	 * @todo Define visibility
	 */
	public function getTSCpid($table, $uid, $pid) {
		$key = $table . ':' . $uid . ':' . $pid;
		if (!isset($this->cache_getTSCpid[$key])) {
			$this->cache_getTSCpid[$key] = BackendUtility::getTSCpid($table, $uid, $pid);
		}
		return $this->cache_getTSCpid[$key];
	}

	/**
	 * Returns TRUE if descriptions should be loaded always
	 *
	 * @param string $table Table for which to check
	 * @return boolean
	 * @todo Define visibility
	 */
	public function doLoadTableDescr($table) {
		return $GLOBALS['TCA'][$table]['interface']['always_description'];
	}

	/**
	 * Returns an array of available languages (to use for FlexForms)
	 *
	 * @param boolean $onlyIsoCoded If set, only languages which are paired with a static_info_table / static_language record will be returned.
	 * @param boolean $setDefault If set, an array entry for a default language is set.
	 * @return array
	 * @todo Define visibility
	 */
	public function getAvailableLanguages($onlyIsoCoded = 1, $setDefault = 1) {
		$isL = ExtensionManagementUtility::isLoaded('static_info_tables');
		// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0 AND hidden=0' . BackendUtility::deleteClause('sys_language'), '', 'title');
		// Traverse them:
		$output = array();
		if ($setDefault) {
			$output[0] = array(
				'uid' => 0,
				'title' => 'Default language',
				'ISOcode' => 'DEF'
			);
		}
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$output[$row['uid']] = $row;
			if ($isL && $row['static_lang_isocode']) {
				$rr = BackendUtility::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
				if ($rr['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $rr['lg_iso_2'];
				}
			}
			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}
		$GLOBALS['TYPO3_DB']->sql_free_result($res);
		return $output;
	}

	/**
	 * Initializes language icons etc.
	 *
	 * @param string $table Table name
	 * @param array $row Record
	 * @param string $sys_language_uid Sys language uid OR ISO language code prefixed with "v", eg. "vDA
	 * @return void
	 * @todo Define visibility
	 */
	public function getLanguageIcon($table, $row, $sys_language_uid) {
		$mainKey = $table . ':' . $row['uid'];
		if (!isset($this->cachedLanguageFlag[$mainKey])) {
			BackendUtility::fixVersioningPid($table, $row);
			list($tscPID, $thePidValue) = $this->getTSCpid($table, $row['uid'], $row['pid']);
			/** @var $t8Tools \TYPO3\CMS\Backend\Configuration\TranslationConfigurationProvider */
			$t8Tools = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Configuration\\TranslationConfigurationProvider');
			$this->cachedLanguageFlag[$mainKey] = $t8Tools->getSystemLanguages($tscPID, $this->backPath);
		}
		// Convert sys_language_uid to sys_language_uid if input was in fact a string (ISO code expected then)
		if (!MathUtility::canBeInterpretedAsInteger($sys_language_uid)) {
			foreach ($this->cachedLanguageFlag[$mainKey] as $rUid => $cD) {
				if ('v' . $cD['ISOcode'] === $sys_language_uid) {
					$sys_language_uid = $rUid;
				}
			}
		}
		$out = '';
		if ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']) {
			$out .= IconUtility::getSpriteIcon($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']);
			$out .= '&nbsp;';
		} elseif ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title']) {
			$out .= '[' . $this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title'] . ']';
			$out .= '&nbsp;';
		}
		return $out;
	}

	/**
	 * Renders an icon to indicate the way the translation and the original is merged (if this is relevant).
	 *
	 * If a field is defined as 'mergeIfNotBlank' this is useful information for an editor. He/she can leave the field blank and
	 * the original value will be used. Without this hint editors are likely to copy the contents even if it is not necessary.
	 *
	 * @param string $l10nMode Localization mode from TCA
	 * @return string
	 */
	protected function getMergeBehaviourIcon($l10nMode) {
		$icon = '';
		if ($l10nMode === 'mergeIfNotBlank') {
			$icon = IconUtility::getSpriteIcon('actions-edit-merge-localization', array('title' => $this->sL('LLL:EXT:lang/locallang_misc.xlf:localizeMergeIfNotBlank')));
		}
		return $icon;
	}

	/**
	 * Rendering preview output of a field value which is not shown as a form field but just outputted.
	 *
	 * @param string $value The value to output
	 * @param array $config Configuration for field.
	 * @param string $field Name of field.
	 * @return string HTML formatted output
	 * @todo Define visibility
	 */
	public function previewFieldValue($value, $config, $field = '') {
		if ($config['config']['type'] === 'group' && ($config['config']['internal_type'] === 'file' || $config['config']['internal_type'] === 'file_reference')) {
			// Ignore uploadfolder if internal_type is file_reference
			if ($config['config']['internal_type'] === 'file_reference') {
				$config['config']['uploadfolder'] = '';
			}
			$show_thumbs = TRUE;
			$table = 'tt_content';
			// Making the array of file items:
			$itemArray = GeneralUtility::trimExplode(',', $value, TRUE);
			// Showing thumbnails:
			$thumbsnail = '';
			if ($show_thumbs) {
				$imgs = array();
				foreach ($itemArray as $imgRead) {
					$imgP = explode('|', $imgRead);
					$imgPath = rawurldecode($imgP[0]);
					$rowCopy = array();
					$rowCopy[$field] = $imgPath;
					// Icon + clickmenu:
					$absFilePath = GeneralUtility::getFileAbsFileName($config['config']['uploadfolder'] ? $config['config']['uploadfolder'] . '/' . $imgPath : $imgPath);
					$fileInformation = pathinfo($imgPath);
					$fileIcon = IconUtility::getSpriteIconForFile($imgPath, array('title' => htmlspecialchars($fileInformation['basename'] . ($absFilePath && @is_file($absFilePath) ? ' (' . GeneralUtility::formatSize(filesize($absFilePath)) . 'bytes)' : ' - FILE NOT FOUND!'))));
					$imgs[] = '<span class="nobr">' . BackendUtility::thumbCode($rowCopy, $table, $field, $this->backPath, 'thumbs.php', $config['config']['uploadfolder'], 0, ' align="middle"') . ($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon) . $imgPath . '</span>';
				}
				$thumbsnail = implode('<br />', $imgs);
			}
			return $thumbsnail;
		} else {
			return nl2br(htmlspecialchars($value));
		}
	}

	/**
	 * Generates and return information about which languages the current user should see in preview, configured by options.additionalPreviewLanguages
	 *
	 * @return array Array of additional languages to preview
	 * @todo Define visibility
	 */
	public function getAdditionalPreviewLanguages() {
		if (!isset($this->cachedAdditionalPreviewLanguages)) {
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages')) {
				$uids = GeneralUtility::intExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'));
				foreach ($uids as $uid) {
					if ($sys_language_rec = BackendUtility::getRecord('sys_language', $uid)) {
						$this->cachedAdditionalPreviewLanguages[$uid] = array('uid' => $uid);
						if ($sys_language_rec['static_lang_isocode'] && ExtensionManagementUtility::isLoaded('static_info_tables')) {
							$staticLangRow = BackendUtility::getRecord('static_languages', $sys_language_rec['static_lang_isocode'], 'lg_iso_2');
							if ($staticLangRow['lg_iso_2']) {
								$this->cachedAdditionalPreviewLanguages[$uid]['uid'] = $uid;
								$this->cachedAdditionalPreviewLanguages[$uid]['ISOcode'] = $staticLangRow['lg_iso_2'];
							}
						}
					}
				}
			} else {
				// None:
				$this->cachedAdditionalPreviewLanguages = array();
			}
		}
		return $this->cachedAdditionalPreviewLanguages;
	}

	/**
	 * Push a new element to the dynNestedStack. Thus, every object know, if it's
	 * nested in a tab or IRRE level and in which order this was processed.
	 *
	 * @param string $type Type of the level, e.g. "tab" or "inline
	 * @param string $ident Identifier of the level
	 * @return void
	 * @todo Define visibility
	 */
	public function pushToDynNestedStack($type, $ident) {
		$this->dynNestedStack[] = array($type, $ident);
	}

	/**
	 * Remove an element from the dynNestedStack. If $type and $ident
	 * are set, the last element will only be removed, if it matches
	 * what is expected to be removed.
	 *
	 * @param string $type Type of the level, e.g. "tab" or "inline
	 * @param string $ident Identifier of the level
	 * @return void
	 * @todo Define visibility
	 */
	public function popFromDynNestedStack($type = NULL, $ident = NULL) {
		if ($type != NULL && $ident != NULL) {
			$last = end($this->dynNestedStack);
			if ($type == $last[0] && $ident == $last[1]) {
				array_pop($this->dynNestedStack);
			}
		} else {
			array_pop($this->dynNestedStack);
		}
	}

	/**
	 * Get the dynNestedStack as associative array.
	 * The result is e.g. ['tab','DTM-ABCD-1'], ['inline','data[13][table][uid][field]'], ['tab','DTM-DEFG-2'], ...
	 *
	 * @param boolean $json Return a JSON string instead of an array - default: FALSE
	 * @param boolean $skipFirst Skip the first element in the dynNestedStack - default: FALSE
	 * @return mixed Returns an associative array by default. If $json is TRUE, it will be returned as JSON string.
	 * @todo Define visibility
	 */
	public function getDynNestedStack($json = FALSE, $skipFirst = FALSE) {
		$result = $this->dynNestedStack;
		if ($skipFirst) {
			array_shift($result);
		}
		return $json ? json_encode($result) : $result;
	}

	/**
	 * Takes care of registering properties in requiredFields and requiredElements.
	 * The current hierarchy of IRRE and/or Tabs is stored. Thus, it is possible to determine,
	 * which required field/element was filled incorrectly and show it, even if the Tab or IRRE
	 * level is hidden.
	 *
	 * @param string $type Type of requirement ('field' or 'range')
	 * @param string $name The name of the form field
	 * @param mixed $value For type 'field' string, for type 'range' array
	 * @return void
	 */
	protected function registerRequiredProperty($type, $name, $value) {
		if ($type == 'field' && is_string($value)) {
			$this->requiredFields[$name] = $value;
			// requiredFields have name/value swapped! For backward compatibility we keep this:
			$itemName = $value;
		} elseif ($type == 'range' && is_array($value)) {
			$this->requiredElements[$name] = $value;
			$itemName = $name;
		}
		// Set the situation of nesting for the current field:
		$this->registerNestedElement($itemName);
	}

	/**
	 * Sets the current situation of nested tabs and inline levels for a given element.
	 *
	 * @param string $itemName The element the nesting should be stored for
	 * @param boolean $setLevel Set the reverse level lookup - default: TRUE
	 * @return void
	 */
	protected function registerNestedElement($itemName, $setLevel = TRUE) {
		$dynNestedStack = $this->getDynNestedStack();
		if (count($dynNestedStack) && preg_match('/^(.+\\])\\[(\\w+)\\]$/', $itemName, $match)) {
			array_shift($match);
			$this->requiredNested[$itemName] = array(
				'parts' => $match,
				'level' => $dynNestedStack
			);
		}
	}

	/**
	 * Return the placeholder attribute for an input field.
	 *
	 * @param string $table
	 * @param string $field
	 * @param array $config
	 * @param array $row
	 * @return string
	 */
	protected function getPlaceholderAttribute($table, $field, array $config, array $row) {
		if (!isset($config['mode']) || $config['mode'] !== 'useOrOverridePlaceholder') {
			return '';
		}

		$value = $this->getPlaceholderValue($table, $field, $config, $row);

		// Cleanup the string and support 'LLL:'
		$value = htmlspecialchars(trim($this->sL($value)));
		return empty($value) ? '' : ' placeholder="' . $value . '" ';
	}

	/**
	 * Determine and get the value for the placeholder for an input field.
	 *
	 * @param string $table
	 * @param string $field
	 * @param array $config
	 * @param array $row
	 * @return mixed
	 */
	protected function getPlaceholderValue($table, $field, array $config, array $row) {
		$value = trim($config['placeholder']);
		if (!$value) {
			return '';
		}
		// Check if we have a reference to another field value from the current record
		if (substr($value, 0, 6) === '__row|') {
			/** @var \TYPO3\CMS\Backend\Form\FormDataTraverser $traverser */
			$traverseFields = GeneralUtility::trimExplode('|', substr($value, 6));
			$traverser = GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Form\\FormDataTraverser', $this);
			$value = $traverser->getTraversedFieldValue($traverseFields, $table, $row);
		}

		return $value;
	}

	/**
	 * Insert additional style sheet link
	 *
	 * @param string $key Some key identifying the style sheet
	 * @param string $href Uri to the style sheet file
	 * @param string $title Value for the title attribute of the link element
	 * @param string $relation Value for the rel attribute of the link element
	 * @return void
	 */
	public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet') {
		$GLOBALS['SOBE']->doc->addStyleSheet($key, $href, $title, $relation);
	}
}
