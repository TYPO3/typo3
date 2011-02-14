<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Contains TYPO3 Core Form generator - AKA "TCEforms"
 *
 * $Id$
 * Revised for TYPO3 3.6 August/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  196: class t3lib_TCEforms
 *  302:	 function t3lib_TCEforms()
 *  338:	 function initDefaultBEmode()
 *
 *			  SECTION: Rendering the forms, fields etc
 *  385:	 function getSoloField($table,$row,$theFieldToReturn)
 *  424:	 function getMainFields($table,$row,$depth=0)
 *  618:	 function getListedFields($table,$row,$list)
 *  660:	 function getPaletteFields($table,$row,$palette,$header='',$itemList='',$collapsedHeader='')
 *  737:	 function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)
 *  900:	 function getSingleField_SW($table,$field,$row,&$PA)
 *
 *			  SECTION: Rendering of each TCEform field type
 *  976:	 function getSingleField_typeInput($table,$field,$row,&$PA)
 * 1057:	 function getSingleField_typeText($table,$field,$row,&$PA)
 * 1178:	 function getSingleField_typeCheck($table,$field,$row,&$PA)
 * 1244:	 function getSingleField_typeRadio($table,$field,$row,&$PA)
 * 1279:	 function getSingleField_typeSelect($table,$field,$row,&$PA)
 * 1359:	 function getSingleField_typeSelect_single($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1490:	 function getSingleField_typeSelect_checkbox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1609:	 function getSingleField_typeSelect_singlebox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1719:	 function getSingleField_typeSelect_multiple($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1823:	 function getSingleField_typeGroup($table,$field,$row,&$PA)
 * 1992:	 function getSingleField_typeNone($table,$field,$row,&$PA)
 * 2008:	 function getSingleField_typeNone_render($config,$itemValue)
 * 2070:	 function getSingleField_typeFlex($table,$field,$row,&$PA)
 * 2205:	 function getSingleField_typeFlex_langMenu($languages,$elName,$selectedLanguage,$multi=1)
 * 2224:	 function getSingleField_typeFlex_sheetMenu($sArr,$elName,$sheetKey)
 * 2259:	 function getSingleField_typeFlex_draw($dataStruct,$editData,$cmdData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$tRows=array())
 * 2452:	 function getSingleField_typeUnknown($table,$field,$row,&$PA)
 * 2467:	 function getSingleField_typeUser($table,$field,$row,&$PA)
 *
 *			  SECTION: Field content processing
 * 2496:	 function formatValue ($config, $itemValue)
 *
 *			  SECTION: "Configuration" fetching/processing functions
 * 2588:	 function getRTypeNum($table,$row)
 * 2614:	 function rearrange($fields)
 * 2640:	 function getExcludeElements($table,$row,$typeNum)
 * 2688:	 function getFieldsToAdd($table,$row,$typeNum)
 * 2713:	 function mergeFieldsWithAddedFields($fields,$fieldsToAdd)
 * 2745:	 function setTSconfig($table,$row,$field='')
 * 2767:	 function getSpecConfForField($table,$row,$field)
 * 2788:	 function getSpecConfFromString($extraString, $defaultExtras)
 * 3007:	 function loadPaletteElements($table, $row, $palette, $itemList='')
 *
 *			  SECTION: Display of localized content etc.
 * 2816:	 function registerDefaultLanguageData($table,$rec)
 * 2848:	 function getLanguageOverlayRawValue($table, $row, $field, $fieldConf)
 * 2876:	 function renderDefaultLanguageContent($table,$field,$row,$item)
 * 2899:	 function renderDefaultLanguageDiff($table,$field,$row,$item)
 *
 *			  SECTION: Form element helper functions
 * 2955:	 function dbFileIcons($fName,$mode,$allowed,$itemArray,$selector='',$params=array(),$onFocus='')
 * 3108:	 function getClipboardElements($allowed,$mode)
 * 3157:	 function getClickMenu($str,$table,$uid='')
 * 3178:	 function renderWizards($itemKinds,$wizConf,$table,$row,$field,&$PA,$itemName,$specConf,$RTE=0)
 * 3382:	 function getIcon($icon)
 * 3409:	 function optionTagStyle($iconString)
 * 3425:	 function extractValuesOnlyFromValueLabelList($itemFormElValue)
 * 3447:	 function wrapOpenPalette($header,$table,$row,$palette,$retFunc=0)
 * 3471:	 function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='')
 * 3485:	 function elName($itemName)
 * 3496:	 function noTitle($str,$wrapParts=array())
 * 3505:	 function blur()
 * 3514:	 function thisReturnUrl()
 * 3527:	 function getSingleHiddenField($table,$field,$row)
 * 3549:	 function formWidth($size=48,$textarea=0)
 * 3576:	 function formWidthText($size=48,$wrap='')
 * 3592:	 function formElStyle($type)
 * 3603:	 function formElClass($type)
 * 3614:	 function formElStyleClassValue($type, $class=FALSE)
 * 3638:	 function insertDefStyle($type)
 * 3657:	 function getDynTabMenu($parts, $idString)
 *
 *			  SECTION: Item-array manipulation functions (check/select/radio)
 * 3696:	 function initItemArray($fieldValue)
 * 3714:	 function addItems($items,$iArray)
 * 3736:	 function procItems($items,$iArray,$config,$table,$row,$field)
 * 3760:	 function addSelectOptionsToItemArray($items,$fieldValue,$TSconfig,$field)
 * 3980:	 function addSelectOptionsToItemArray_makeModuleData($value)
 * 4002:	 function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)
 *
 *			  SECTION: Template functions
 * 4083:	 function setNewBEDesign()
 * 4138:	 function intoTemplate($inArr,$altTemplate='')
 * 4162:	 function addUserTemplateMarkers($marker,$table,$field,$row,&$PA)
 * 4173:	 function wrapLabels($str)
 * 4186:	 function wrapTotal($c,$rec,$table)
 * 4199:	 function replaceTableWrap($arr,$rec,$table)
 * 4236:	 function wrapBorder(&$out_array,&$out_pointer)
 * 4258:	 function rplColorScheme($inTemplate)
 * 4278:	 function getDivider()
 * 4288:	 function printPalette($palArr)
 * 4339:	 function helpTextIcon($table,$field,$force=0)
 * 4359:	 function helpText($table,$field)
 * 4380:	 function setColorScheme($scheme)
 * 4404:	 function resetSchemes()
 * 4415:	 function storeSchemes()
 * 4427:	 function restoreSchemes()
 *
 *			  SECTION: JavaScript related functions
 * 4457:	 function JStop()
 * 4508:	 function JSbottom($formname='forms[0]')
 * 4835:	 function dbFileCon($formObj='document.forms[0]')
 * 5053:	 function printNeededJSFunctions()
 * 5080:	 function printNeededJSFunctions_top()
 *
 *			  SECTION: Various helper functions
 * 5128:	 function getDefaultRecord($table,$pid=0)
 * 5167:	 function getRecordPath($table,$rec)
 * 5181:	 function readPerms()
 * 5195:	 function sL($str)
 * 5208:	 function getLL($str)
 * 5229:	 function isPalettesCollapsed($table,$palette)
 * 5245:	 function isDisplayCondition($displayCond,$row,$ffValueKey='')
 * 5349:	 function getTSCpid($table,$uid,$pid)
 * 5363:	 function doLoadTableDescr($table)
 * 5375:	 function getAvailableLanguages($onlyIsoCoded=1,$setDefault=1)
 *
 *
 * 5417: class t3lib_TCEforms_FE extends t3lib_TCEforms
 * 5425:	 function wrapLabels($str)
 * 5435:	 function printPalette($palArr)
 * 5460:	 function setFancyDesign()
 *
 * TOTAL FUNCTIONS: 100
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


/**
 * 'TCEforms' - Class for creating the backend editing forms.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @coauthor	René Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms {

		// variables not commented yet.... (do so...)
	var $palFieldArr = array();
	var $disableWizards = 0;
	var $isPalettedoc = 0;
	var $paletteMargin = 1;
	var $defStyle = ''; // 'font-family:Verdana;font-size:10px;';
	var $cachedTSconfig = array();
	var $cachedTSconfig_fieldLevel = array();
	var $cachedLanguageFlag = array();
	var $cachedAdditionalPreviewLanguages = NULL;
	var $transformedRow = array();
	var $extJSCODE = '';
	var $printNeededJS = array();
	var $hiddenFieldAccum = array();
	var $TBE_EDITOR_fieldChanged_func = '';
	var $loadMD5_JS = 1;
	var $prevBorderStyle = '[nothing here...]'; // Something unique...
	var $allowUpload = 0; // If set direct upload fields will be shown
	var $titleLen = 15; // @deprecated since TYPO3 4.1: $BE_USER->uc['titleLen'] but what is default??
	var $defaultLanguageData = array(); // Array where records in the default language is stored. (processed by transferdata)
	var $defaultLanguageData_diff = array(); // Array where records in the default language is stored (raw without any processing. used for making diff)
	var $additionalPreviewLanguageData = array();


		// EXTERNAL, static
	var $backPath = ''; // Set this to the 'backPath' pointing back to the typo3 admin directory from the script where this form is displayed.
	var $returnUrl = ''; // Alternative return URL path (default is t3lib_div::linkThisScript())
	var $doSaveFieldName = ''; // Can be set to point to a field name in the form which will be set to '1' when the form is submitted with a *save* button. This way the recipient script can determine that the form was submitted for save and not "close" for example.
	var $palettesCollapsed = 0; // Can be set true/false to whether palettes (secondary options) are in the topframe or in form. True means they are NOT IN-form. So a collapsed palette is one, which is shown in the top frame, not in the page.
	var $disableRTE = 0; // If set, the RTE is disabled (from form display, eg. by checkbox in the bottom of the page!)
	var $globalShowHelp = 1; // If false, then all CSH will be disabled, regardless of settings in $this->edit_showFieldHelp
	var $localizationMode = ''; // If true, the forms are rendering only localization relevant fields of the records.
	var $fieldOrder = ''; // Overrule the field order set in TCA[types][showitem], eg for tt_content this value, 'bodytext,image', would make first the 'bodytext' field, then the 'image' field (if set for display)... and then the rest in the old order.
	var $doPrintPalette = 1; // If set to false, palettes will NEVER be rendered.

	/**
	 * Set to initialized clipboard object; Then the element browser will offer a link to paste in records from clipboard.
	 *
	 * @var t3lib_clipboard
	 */
	var $clipObj = FALSE;
	var $enableClickMenu = FALSE; // Enable click menu on reference icons.
	var $enableTabMenu = FALSE; // Enable Tab Menus.
	var $renderReadonly = FALSE; // When enabled all fields are rendered non-editable.

	var $form_rowsToStylewidth = 9.58; // Form field width compensation: Factor from NN4 form field widths to style-aware browsers (like NN6+ and MSIE, with the $CLIENT[FORMSTYLE] value set)
	var $form_largeComp = 1.33; // Form field width compensation: Compensation for large documents, doc-tab (editing)
	var $charsPerRow = 40; // The number of chars expected per row when the height of a text area field is automatically calculated based on the number of characters found in the field content.
	var $maxTextareaWidth = 48; // The maximum abstract value for textareas
	var $maxInputWidth = 48; // The maximum abstract value for input fields
	var $defaultMultipleSelectorStyle = 'width:250px;'; // Default style for the selector boxes used for multiple items in "select" and "group" types.


		// INTERNAL, static
	var $prependFormFieldNames = 'data'; // The string to prepend formfield names with.
	var $prependCmdFieldNames = 'cmd'; // The string to prepend commands for tcemain::process_cmdmap with.
	var $prependFormFieldNames_file = 'data_files'; // The string to prepend FILE form field names with.
	var $formName = 'editform'; // The name attribute of the form.
	var $allowOverrideMatrix = array(); // Whitelist that allows TCA field configuration to be overridden by TSconfig, @see overrideFieldConf()


		// INTERNAL, dynamic
	var $perms_clause = ''; // Set by readPerms()  (caching)
	var $perms_clause_set = 0; // Set by readPerms()  (caching-flag)
	var $edit_showFieldHelp = ''; // Used to indicate the mode of CSH (Context Sensitive Help), whether it should be icons-only ('icon'), full description ('text') or not at all (blank).
	var $docLarge = 0; // If set, the forms will be rendered a little wider, more precisely with a factor of $this->form_largeComp.
	var $clientInfo = array(); // Loaded with info about the browser when class is instantiated.
	var $RTEenabled = 0; // True, if RTE is possible for the current user (based on result from BE_USER->isRTE())
	var $RTEenabled_notReasons = ''; // If $this->RTEenabled was false, you can find the reasons listed in this array which is filled with reasons why the RTE could not be loaded)
	var $RTEcounter = 0; // Counter that is incremented before an RTE is created. Can be used for unique ids etc.

	var $colorScheme; // Contains current color scheme
	var $classScheme; // Contains current class scheme
	var $defColorScheme; // Contains the default color scheme
	var $defClassScheme; // Contains the default class scheme
	var $fieldStyle; // Contains field style values
	var $borderStyle; // Contains border style values.

	var $commentMessages = array(); // An accumulation of messages from the class.

		// INTERNAL, templates
	var $totalWrap = '<hr />|<hr />'; // Total wrapping for the table rows.
	var $fieldTemplate = '<strong>###FIELD_NAME###</strong><br />###FIELD_ITEM###<hr />'; // Field template
	var $sectionWrap = ''; // Wrapping template code for a section
	var $palFieldTemplateHeader = ''; // Template for palette headers
	var $palFieldTemplate = ''; // Template for palettes

		// INTERNAL, working memory
	var $excludeElements = ''; // Set to the fields NOT to display, if any.
	var $palettesRendered = array(); // During rendering of forms this will keep track of which palettes has already been rendered (so they are not rendered twice by mistake)
	var $hiddenFieldListArr = array(); // This array of fields will be set as hidden-fields instead of rendered normally! For instance palette fields edited in the top frame are set as hidden fields since the main form has to submit the values. The top frame actually just sets the value in the main form!
	var $requiredFields = array(); // Used to register input-field names, which are required. (Done during rendering of the fields). This information is then used later when the JavaScript is made.
	var $requiredAdditional = array(); // Used to register input-field names, which are required an have additional requirements (e.g. like a date/time must be positive integer). The information of this array is merged with $this->requiredFields later.
	var $requiredElements = array(); // Used to register the min and max number of elements for selectorboxes where that apply (in the "group" type for instance)
	var $requiredNested = array(); // Used to determine where $requiredFields or $requiredElements are nested (in Tabs or IRRE)
	var $renderDepth = 0; // Keeps track of the rendering depth of nested records.
	var $savedSchemes = array(); // Color scheme buffer.
	var $dynNestedStack = array(); // holds the path an element is nested in (e.g. required for RTEhtmlarea)

		// Internal, registers for user defined functions etc.
	var $additionalCode_pre = array(); // Additional HTML code, printed before the form.
	var $additionalJS_pre = array(); // Additional JavaScript, printed before the form
	var $additionalJS_post = array(); // Additional JavaScript printed after the form
	var $additionalJS_submit = array(); // Additional JavaScript executed on submit; If you set "OK" variable it will raise an error about RTEs not being loaded and offer to block further submission.
	var $additionalJS_delete = array(); // Additional JavaScript executed when section element is deleted. This is neceessary, for example, to correctly clean up HTMLArea RTE (bug #8232)

	/**
	 * Instance of t3lib_tceforms_inline
	 *
	 * @var t3lib_TCEforms_inline
	 */
	var $inline;
	var $hookObjectsMainFields = array(); // Array containing hook class instances called once for a form
	var $hookObjectsSingleField = array(); // Array containing hook class instances called for each field
	var $extraFormHeaders = array(); // Rows gettings inserted into the alt_doc headers (when called from alt_doc.php)

	public $templateFile = ''; // Form templates, relative to typo3 directory


	/**
	 * Constructor function, setting internal variables, loading the styles used.
	 *
	 * @return	void
	 */
	function t3lib_TCEforms() {
		global $CLIENT, $TYPO3_CONF_VARS;

		$this->clientInfo = t3lib_div::clientInfo();

		$this->RTEenabled = $GLOBALS['BE_USER']->isRTE();
		if (!$this->RTEenabled) {
			$this->RTEenabled_notReasons = implode(LF, $GLOBALS['BE_USER']->RTE_errors);
			$this->commentMessages[] = 'RTE NOT ENABLED IN SYSTEM due to:' . LF . $this->RTEenabled_notReasons;
		}

			// Default color+class scheme
		$this->defColorScheme = array(
			$GLOBALS['SOBE']->doc->bgColor, // Background for the field AND palette
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor, -20), // Background for the field header
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor, -10), // Background for the palette field header
			'black', // Field header font color
			'#666666' // Palette field header font color
		);
		$this->defColorScheme = array();

			// Override / Setting defaults from TBE_STYLES array
		$this->resetSchemes();

			// Setting the current colorScheme to default.
		$this->defColorScheme = $this->colorScheme;
		$this->defClassScheme = $this->classScheme;

			// Define whitelist that allows TCA field configuration to be overridden by TSconfig, @see overrideFieldConf():
		$this->allowOverrideMatrix = array(
			'input' => array('size', 'max'),
			'text' => array('cols', 'rows', 'wrap'),
			'check' => array('cols', 'showIfRTE'),
			'select' => array('size', 'autoSizeMax', 'maxitems', 'minitems'),
			'group' => array('size', 'autoSizeMax', 'max_size', 'show_thumbs', 'maxitems', 'minitems', 'disable_controls'),
			'inline' => array('appearance', 'behaviour', 'foreign_label', 'foreign_selector', 'foreign_unique', 'maxitems', 'minitems', 'size', 'autoSizeMax', 'symmetric_label'),
		);

			// Create instance of t3lib_TCEforms_inline only if this a non-IRRE-AJAX call:
		if (!isset($GLOBALS['ajaxID']) || strpos($GLOBALS['ajaxID'], 't3lib_TCEforms_inline::') !== 0) {
			$this->inline = t3lib_div::makeInstance('t3lib_TCEforms_inline');
		}
			// Create instance of t3lib_TCEforms_suggest only if this a non-Suggest-AJAX call:
		if (!isset($GLOBALS['ajaxID']) || strpos($GLOBALS['ajaxID'], 't3lib_TCEforms_suggest::') !== 0) {
			$this->suggest = t3lib_div::makeInstance('t3lib_TCEforms_suggest');
		}

			// Prepare user defined objects (if any) for hooks which extend this function:
		$this->hookObjectsMainFields = array();
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef) {
				$this->hookObjectsMainFields[] = t3lib_div::getUserObj($classRef);
			}
		}
		$this->hookObjectsSingleField = array();
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getSingleFieldClass'] as $classRef) {
				$this->hookObjectsSingleField[] = t3lib_div::getUserObj($classRef);
			}
		}

		$this->templateFile = 'templates/tceforms.html';

	}

	/**
	 * Initialize various internal variables.
	 *
	 * @return	void
	 */
	function initDefaultBEmode() {
		global $BE_USER;
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		$this->setNewBEDesign();
		$this->docLarge = $BE_USER->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $BE_USER->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $BE_USER->uc['edit_docModuleUpload'];
		$this->titleLen = $BE_USER->uc['titleLen']; // @deprecated since TYPO3 4.1

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
	 * @param	string		The table name
	 * @param	array		The record from the table for which to render a field.
	 * @param	string		The field name to return the TCEform element for.
	 * @return	string		HTML output
	 * @see getMainFields()
	 */
	function getSoloField($table, $row, $theFieldToReturn) {
		global $TCA;

		if ($TCA[$table]) {
			t3lib_div::loadTCA($table);
			$typeNum = $this->getRTypeNum($table, $row);
			if ($TCA[$table]['types'][$typeNum]) {
				$itemList = $TCA[$table]['types'][$typeNum]['showitem'];
				if ($itemList) {
					$fields = t3lib_div::trimExplode(',', $itemList, 1);
					$excludeElements = $this->excludeElements = $this->getExcludeElements($table, $row, $typeNum);

					foreach ($fields as $fieldInfo) {
						$parts = explode(';', $fieldInfo);

						$theField = trim($parts[0]);
						if (!in_array($theField, $excludeElements) && !strcmp($theField, $theFieldToReturn)) {
							if ($TCA[$table]['columns'][$theField]) {
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
	 * The input-$row is required to be preprocessed if necessary by eg. the t3lib_transferdata class. For instance the RTE content should be transformed through this class first.
	 *
	 * @param	string		The table name
	 * @param	array		The record from the table for which to render a field.
	 * @param	integer		Depth level
	 * @return	string		HTML output
	 * @see getSoloField()
	 */
	function getMainFields($table, $row, $depth = 0) {
		global $TCA, $TYPO3_CONF_VARS;

		$this->renderDepth = $depth;

			// Init vars:
		$out_array = array(array());
		$out_array_meta = array(array(
									'title' => $this->getLL('l_generalTab')
								));

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

		if ($TCA[$table]) {

				// Load the full TCA for the table.
			t3lib_div::loadTCA($table);

				// Get dividers2tabs setting from TCA of the current table:
			$dividers2tabs =& $TCA[$table]['ctrl']['dividers2tabs'];

				// Load the description content for the table.
			if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table)) {
				$GLOBALS['LANG']->loadSingleTableDescription($table);
			}
				// Get the current "type" value for the record.
			$typeNum = $this->getRTypeNum($table, $row);

				// Find the list of fields to display:
			if ($TCA[$table]['types'][$typeNum]) {
				$itemList = $TCA[$table]['types'][$typeNum]['showitem'];
				if ($itemList) { // If such a list existed...
						// Explode the field list and possibly rearrange the order of the fields, if configured for
					$fields = t3lib_div::trimExplode(',', $itemList, 1);
					if ($this->fieldOrder) {
						$fields = $this->rearrange($fields);
					}

						// Get excluded fields, added fiels and put it together:
					$excludeElements = $this->excludeElements = $this->getExcludeElements($table, $row, $typeNum);
					$fields = $this->mergeFieldsWithAddedFields($fields, $this->getFieldsToAdd($table, $row, $typeNum));

						// If TCEforms will render a tab menu in the next step, push the name to the tab stack:
					$tabIdentString = '';
					$tabIdentStringMD5 = '';
					if (strstr($itemList, '--div--') !== false && $this->enableTabMenu && $dividers2tabs) {
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
						$color_style_parts = t3lib_div::trimExplode('-', $parts[4]);
						if (strcmp($color_style_parts[0], '')) {
							$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
						}
						if (strcmp($color_style_parts[1], '')) {
							$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
							if (!isset($this->fieldStyle)) {
								$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
							}
						}
						if (strcmp($color_style_parts[2], '')) {
							$this->wrapBorder($out_array[$out_sheet], $out_pointer);
							$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
							if (!isset($this->borderStyle)) {
								$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
							}
						}

							// Render the field:
						$theField = $parts[0];
						if (!in_array($theField, $excludeElements)) {
							if ($TCA[$table]['columns'][$theField]) {
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
										$this->popFromDynNestedStack('tab', $tabIdentStringMD5 . '-' . ($out_sheet));
											// Remember on which sheet we're currently working:
										$this->pushToDynNestedStack('tab', $tabIdentStringMD5 . '-' . ($out_sheet + 1));
										$out_array[$out_sheet] = array();
										$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
											// Register newline for Tab
										$out_array_meta[$out_sheet]['newline'] = ($parts[2] == "newline");
									}
								} else { // Setting alternative title for "General" tab if "--div--" is the very first element.
									$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
										// Only add the first tab to the dynNestedStack if there are more tabs:
									if ($tabIdentString && strpos($itemList, '--div--', strlen($fieldInfo))) {
										$this->pushToDynNestedStack('tab', $tabIdentStringMD5 . '-1');
									}
								}
							} elseif ($theField == '--palette--') {
								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]])) {
										// render a 'header' if not collapsed
									if ($TCA[$table]['palettes'][$parts[2]]['canNotCollapse'] AND $parts[1]) {
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
		$mParr = t3lib_div::trimExplode(',', $TCA[$table]['ctrl']['mainpalette']);
		$i = 0;
		if (count($mParr)) {
			foreach ($mParr as $mP) {
				if (!isset($this->palettesRendered[$this->renderDepth][$table][$mP])) {
					$temp_palettesCollapsed = $this->palettesCollapsed;
					$this->palettesCollapsed = 0;
					$label = ($i == 0 ? $this->getLL('l_generalOptions') : $this->getLL('l_generalOptions_more'));
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
		if ($out_sheet > 0) { // There were --div-- dividers around...

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
					'newline' => $out_array_meta[$idx]['newline'], // Newline for this tab/sheet
				);
			}

			if (count($parts) > 1) {
					// Unset the current level of tab menus:
				$this->popFromDynNestedStack('tab', $tabIdentStringMD5 . '-' . ($out_sheet + 1));
				$dividersToTabsBehaviour = (isset($TCA[$table]['ctrl']['dividers2tabs']) ? $TCA[$table]['ctrl']['dividers2tabs'] : 1);
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
				// Only one, so just implode:
			$output = implode('', $out_array[$out_sheet]);
		}

		return $output;
	}

	/**
	 * Will return the TCEform elements for a pre-defined list of fields.
	 * Notice that this will STILL use the configuration found in the list [types][showitem] for those fields which are found there. So ideally the list of fields given as argument to this function should also be in the current [types][showitem] list of the record.
	 * Used for displaying forms for the frontend edit icons for instance.
	 *
	 * @param	string		The table name
	 * @param	array		The record array.
	 * @param	string		Commalist of fields from the table. These will be shown in the specified order in a form.
	 * @return	string		TCEform elements in a string.
	 */
	function getListedFields($table, $row, $list) {
		global $TCA;

		t3lib_div::loadTCA($table);
		if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table)) {
			$GLOBALS['LANG']->loadSingleTableDescription($table);
		}

		$out = '';
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table, $row, 1);

		$editFieldList = array_unique(t3lib_div::trimExplode(',', $list, 1));
		foreach ($editFieldList as $theFieldC) {
			list($theField, $palFields) = preg_split('/\[|\]/', $theFieldC);
			$theField = trim($theField);
			$palFields = trim($palFields);
			if ($TCA[$table]['columns'][$theField]) {
				$parts = t3lib_div::trimExplode(';', $types_fieldConfig[$theField]['origString']);
				$sField = $this->getSingleField($table, $theField, $row, $parts[1], 0, $parts[3], 0); // Don't sent palette pointer - there are no options anyways for a field-list.
				$out .= $sField;
			} elseif ($theField == '--div--') {
				$out .= $this->getDivider();
			}
			if ($palFields) {
				$out .= $this->getPaletteFields($table, $row, '', '', implode(',', t3lib_div::trimExplode('|', $palFields, 1)));
			}
		}

		return $out;
	}

	/**
	 * Creates a palette (collection of secondary options).
	 *
	 * @param	string		The table name
	 * @param	array		The row array
	 * @param	string		The palette number/pointer
	 * @param	string		Header string for the palette (used when in-form). If not set, no header item is made.
	 * @param	string		Optional alternative list of fields for the palette
	 * @param	string		Optional Link text for activating a palette (when palettes does not have another form element to belong to).
	 * @return	string		HTML code.
	 */
	function getPaletteFields($table, $row, $palette, $header = '', $itemList = '', $collapsedHeader = NULL) {
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
					$out .= $this->intoTemplate(
						array('HEADER' => htmlspecialchars($header)),
						$this->palFieldTemplateHeader
					);
				}

				$collapsed = $this->isPalettesCollapsed($table, $palette);

				$thePalIcon = '';
				if ($collapsed && $collapsedHeader !== NULL) {
					list($thePalIcon,) = $this->wrapOpenPalette(
						t3lib_iconWorks::getSpriteIcon(
							'actions-system-options-view',
							array('title' => htmlspecialchars($this->getLL('l_moreOptions')))
						), $table, $row, $palette, 1);
					$thePalIcon = '<span style="margin-left: 20px;">' . $thePalIcon . $collapsedHeader . '</span>';
				}

				$paletteHtml = $this->wrapPaletteField($this->printPalette($parts), $table, $row, $palette, $collapsed);

				$out .= $this->intoTemplate(
					array('PALETTE' => $thePalIcon . $paletteHtml),
					$this->palFieldTemplate
				);
			}
		}
		return $out;
	}

	/**
	 * Returns the form HTML code for a database table field.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	array		The record to edit from the database table.
	 * @param	string		Alternative field name label to show.
	 * @param	boolean		Set this if the field is on a palette (in top frame), otherwise not. (if set, field will render as a hidden field).
	 * @param	string		The "extra" options from "Part 4" of the field configurations found in the "types" "showitem" list. Typically parsed by $this->getSpecConfFromString() in order to get the options as an associative array.
	 * @param	integer		The palette pointer.
	 * @return	mixed		String (normal) or array (palettes)
	 */
	function getSingleField($table, $field, $row, $altName = '', $palette = 0, $extra = '', $pal = 0) {
		global $TCA, $BE_USER;

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

			// Make sure to load full $TCA array for the table:
		t3lib_div::loadTCA($table);

			// Get the TCA configuration for the current field:
		$PA['fieldConf'] = $TCA[$table]['columns'][$field];
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type']; // Using "form_type" locally in this script

		$skipThisField = $this->inline->skipField($table, $field, $row, $PA['fieldConf']['config']);

			// Now, check if this field is configured and editable (according to excludefields + other configuration)
		if (is_array($PA['fieldConf']) &&
			!$skipThisField &&
			(!$PA['fieldConf']['exclude'] || $BE_USER->check('non_exclude_fields', $table . ':' . $field)) &&
			$PA['fieldConf']['config']['form_type'] != 'passthrough' &&
			($this->RTEenabled || !$PA['fieldConf']['config']['showIfRTE']) &&
			(!$PA['fieldConf']['displayCond'] || $this->isDisplayCondition($PA['fieldConf']['displayCond'], $row)) &&
			(!$TCA[$table]['ctrl']['languageField'] || $PA['fieldConf']['l10n_display'] || strcmp($PA['fieldConf']['l10n_mode'], 'exclude') || $row[$TCA[$table]['ctrl']['languageField']] <= 0) &&
			(!$TCA[$table]['ctrl']['languageField'] || !$this->localizationMode || $this->localizationMode === $PA['fieldConf']['l10n_cat'])
		) {


				// Fetching the TSconfig for the current table/field. This includes the $row which means that
			$PA['fieldTSConfig'] = $this->setTSconfig($table, $row, $field);

				// If the field is NOT disabled from TSconfig (which it could have been) then render it
			if (!$PA['fieldTSConfig']['disabled']) {
					// Override fieldConf by fieldTSconfig:
				$PA['fieldConf']['config'] = $this->overrideFieldConf($PA['fieldConf']['config'], $PA['fieldTSConfig']);

					// Init variables:
				$PA['itemFormElName'] = $this->prependFormFieldNames . '[' . $table . '][' . $row['uid'] . '][' . $field . ']'; // Form field name
				$PA['itemFormElName_file'] = $this->prependFormFieldNames_file . '[' . $table . '][' . $row['uid'] . '][' . $field . ']'; // Form field name, in case of file uploads
				$PA['itemFormElValue'] = $row[$field]; // The value to show in the form field.
				$PA['itemFormElID'] = $this->prependFormFieldNames . '_' . $table . '_' . $row['uid'] . '_' . $field;

					// set field to read-only if configured for translated records to show default language content as readonly
				if ($PA['fieldConf']['l10n_display'] && t3lib_div::inList($PA['fieldConf']['l10n_display'], 'defaultAsReadonly') && $row[$TCA[$table]['ctrl']['languageField']] > 0) {
					$PA['fieldConf']['config']['readOnly'] = true;
					$PA['itemFormElValue'] = $this->defaultLanguageData[$table . ':' . $row['uid']][$field];
				}

					// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (
					($TCA[$table]['ctrl']['type'] && !strcmp($field, $TCA[$table]['ctrl']['type'])) ||
					($TCA[$table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$table]['ctrl']['requestUpdate'], $field))) {
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
				} else { // Render as a normal field:

						// If the field is NOT a palette field, then we might create an icon which links to a palette for the field, if one exists.
					if (!$PA['palette']) {
						$paletteFields = $this->loadPaletteElements($table, $row, $PA['pal']);
						if ($PA['pal'] && $this->isPalettesCollapsed($table, $PA['pal']) && count($paletteFields)) {
							list($thePalIcon, $palJSfunc) = $this->wrapOpenPalette(t3lib_iconWorks::getSpriteIcon('actions-system-options-view', array('title' => htmlspecialchars($this->getLL('l_moreOptions')))), $table, $row, $PA['pal'], 1);
						} else {
							$thePalIcon = '';
							$palJSfunc = '';
						}
					}
						// onFocus attribute to add to the field:
					$PA['onFocus'] = ($palJSfunc && !$BE_USER->uc['dontShowPalettesOnFocusInAB']) ? ' onfocus="' . htmlspecialchars($palJSfunc) . '"' : '';

						// Find item
					$item = '';
					$PA['label'] = ($PA['altName'] ? $PA['altName'] : $PA['fieldConf']['label']);
					$PA['label'] = ($PA['fieldTSConfig']['label'] ? $PA['fieldTSConfig']['label'] : $PA['label']);
					$PA['label'] = ($PA['fieldTSConfig']['label.'][$GLOBALS['LANG']->lang] ? $PA['fieldTSConfig']['label.'][$GLOBALS['LANG']->lang] : $PA['label']);
					$PA['label'] = $this->sL($PA['label']);
						// JavaScript code for event handlers:
					$PA['fieldChangeFunc'] = array();
					$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR.fieldChanged('" . $table . "','" . $row['uid'] . "','" . $field . "','" . $PA['itemFormElName'] . "');";
					$PA['fieldChangeFunc']['alert'] = $alertMsgOnChange;
						// if this is the child of an inline type and it is the field creating the label
					if ($this->inline->isInlineChildAndLabelField($table, $field)) {
						$inlineObjectId = implode(
							t3lib_TCEforms_inline::Structure_Separator,
							array(
								 $this->inline->inlineNames['object'],
								 $table,
								 $row['uid']
							)
						);
						$PA['fieldChangeFunc']['inline'] = "inline.handleChangedField('" . $PA['itemFormElName'] . "','" . $inlineObjectId . "');";
					}

						// Based on the type of the item, call a render function:
					$item = $this->getSingleField_SW($table, $field, $row, $PA);

						// Add language + diff
					if ($PA['fieldConf']['l10n_display'] && (t3lib_div::inList($PA['fieldConf']['l10n_display'], 'hideDiff') || t3lib_div::inList($PA['fieldConf']['l10n_display'], 'defaultAsReadonly'))) {
						$renderLanguageDiff = false;
					} else {
						$renderLanguageDiff = true;
					}

					if ($renderLanguageDiff) {
						$item = $this->renderDefaultLanguageContent($table, $field, $row, $item);
						$item = $this->renderDefaultLanguageDiff($table, $field, $row, $item);
					}

						// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$label = t3lib_div::deHSCentities(htmlspecialchars($PA['label']));
					if (t3lib_div::testInt($row['uid']) && $PA['fieldTSConfig']['linkTitleToSelf'] && !t3lib_div::_GP('columnsOnly')) {
						$lTTS_url = $this->backPath . 'alt_doc.php?edit[' . $table . '][' . $row['uid'] . ']=edit&columnsOnly=' . $field . '&returnUrl=' . rawurlencode($this->thisReturnUrl());
						$label = '<a href="' . htmlspecialchars($lTTS_url) . '">' . $label . '</a>';
					}

						// wrap the label with help text
					$PA['label'] = $label = t3lib_BEfunc::wrapInHelp($table, $field, $label);

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
							'ITEM' => $item
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
							'FIELD' => $field
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
	 * @param	string		Table name of record
	 * @param	string		Fieldname to render
	 * @param	array		The record
	 * @param	array		parameters array containing a lot of stuff. Value by Reference!
	 * @return	string		Returns the item as HTML code to insert
	 * @access private
	 * @see getSingleField(), getSingleField_typeFlex_draw()
	 */
	function getSingleField_SW($table, $field, $row, &$PA) {
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type']; // Using "form_type" locally in this script

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
			break;
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeInput($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];

		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$size = t3lib_div::intInRange($config['size'] ? $config['size'] : 30, 5, $this->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',', $config['eval'], 1);
		$classAndStyleAttributes = $this->formWidthAsArray($size);

		$fieldAppendix = '';
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
				$dateRange .= ' lower-' . intval($config['range']['lower']);
			}
			if (isset($config['range']['upper'])) {
				$dateRange .= ' upper-' . intval($config['range']['upper']);
			}
			$inputId = uniqid('tceforms-' . $class . 'field-');
			$cssClasses[] = 'tceforms-textfield tceforms-' . $class . 'field' . $dateRange;
			$fieldAppendix = t3lib_iconWorks::getSpriteIcon(
				'actions-edit-pick-date',
				array(
					 'style' => 'cursor:pointer;',
					 'id' => 'picker-' . $inputId
				)
			);
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
						$this->requiredAdditional[$PA['itemFormElName']]['isPositiveNumber'] = true;
					}
				break;
				default:
					if (substr($func, 0, 3) == 'tx_') {
							// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval()
						$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
						if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
							$_params = array(
								'value' => $PA['itemFormElValue']
							);
							$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
						}
					}
				break;
			}
		}

		$paramsList = "'" . $PA['itemFormElName'] . "','" . implode(',', $evalList) . "','" . trim($config['is_in']) . "'," . (isset($config['checkbox']) ? 1 : 0) . ",'" . $config['checkbox'] . "'";
		if ((in_array('date', $evalList) || in_array('datetime', $evalList))) {
			$item .= '<span class="t3-tceforms-input-wrapper-datetime" onmouseOver="if (document.getElementById(\'' .
					 $inputId . '\').value) {this.className=\'t3-tceforms-input-wrapper-datetime-hover\';} else {this.className=\'t3-tceforms-input-wrapper-datetime\';};" onmouseOut="this.className=\'t3-tceforms-input-wrapper-datetime\';">';

				// Add server timezone offset to UTC to our stored date
			if ($PA['itemFormElValue'] > 0) {
				$PA['itemFormElValue'] += date('Z', $PA['itemFormElValue']);
			}
		} else {
			$item .= '<span class="t3-tceforms-input-wrapper" onmouseOver="if (document.getElementById(\'' . $inputId .
					 '\').value) {this.className=\'t3-tceforms-input-wrapper-hover\';} else {this.className=\'t3-tceforms-input-wrapper\';};" onmouseOut="this.className=\'t3-tceforms-input-wrapper\';">';
		}

		$PA['fieldChangeFunc'] = array_merge(
			array('typo3form.fieldGet' => 'typo3form.fieldGet(' . $paramsList . ');'),
			$PA['fieldChangeFunc']
		);
			// old function "checkbox" now the option to set the date / remove the date
		if (isset($config['checkbox'])) {
			$item .= t3lib_iconWorks::getSpriteIcon('actions-input-clear', array('tag' => 'a', 'class' => 't3-tceforms-input-clearer', 'onclick' => 'document.getElementById(\'' . $inputId . '\').value=\'\';document.getElementById(\'' . $inputId . '\').focus();' . implode('', $PA['fieldChangeFunc'])));
		}
		$mLgd = ($config['max'] ? $config['max'] : 256);
		$iOnChange = implode('', $PA['fieldChangeFunc']);

		$item .= '<input type="text" id="' . $inputId .
				 '" class="' . implode(' ', $cssClasses) . '" name="' . $PA['itemFormElName'] .
				 '_hr" value="" style="' . $cssStyle . '" maxlength="' . $mLgd . '" onchange="' .
				 htmlspecialchars($iOnChange) . '"' . $PA['onFocus'] . ' />'; // This is the EDITABLE form field.
		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '" value="' .
				 htmlspecialchars($PA['itemFormElValue']) . '" />'; // This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$item .= $fieldAppendix . '</span><div style="clear:both;"></div>';
		$this->extJSCODE .= 'typo3form.fieldSet(' . $paramsList . ');';

			// going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			if (substr($evalData, 0, 3) == 'tx_') {
				$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData] . ':&' . $evalData);
				if (is_object($evalObj) && method_exists($evalObj, 'returnFieldJS')) {
					$this->extJSCODE .= "\n\nfunction " . $evalData . "(value) {\n" . $evalObj->returnFieldJS() . "\n}\n";
				}
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
	 * Generation of TCEform elements of the type "text"
	 * This will render a <textarea> OR RTE area form field, possibly with various control/validation features
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeText($table, $field, $row, &$PA) {

			// Init config:
		$config = $PA['fieldConf']['config'];
		$evalList = t3lib_div::trimExplode(',', $config['eval'], 1);

		if ($this->renderReadonly || $config['readOnly']) {
			return $this->getSingleField_typeNone_render($config, $PA['itemFormElValue']);
		}

			// Setting columns number:
		$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);

			// Setting number of rows:
		$origRows = $rows = t3lib_div::intInRange($config['rows'] ? $config['rows'] : 5, 1, 20);
		if (strlen($PA['itemFormElValue']) > $this->charsPerRow * 2) {
			$cols = $this->maxTextareaWidth;
			$rows = t3lib_div::intInRange(round(strlen($PA['itemFormElValue']) / $this->charsPerRow), count(explode(LF, $PA['itemFormElValue'])), 20);
			if ($rows < $origRows) {
				$rows = $origRows;
			}
		}

		if (in_array('required', $evalList)) {
			$this->requiredFields[$table . '_' . $row['uid'] . '_' . $field] = $PA['itemFormElName'];
		}

			// Init RTE vars:
		$RTEwasLoaded = 0; // Set true, if the RTE is loaded; If not a normal textarea is shown.
		$RTEwouldHaveBeenLoaded = 0; // Set true, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="' . htmlspecialchars($PA['itemFormElValue']) . '" />';

			// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($this->RTEenabled) {
			$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']])) { // If the field is configured for RTE and if any flag-field is not set to disable it.
				t3lib_BEfunc::fixVersioningPid($table, $row);
				list($tscPID, $thePidValue) = $this->getTSCpid($table, $row['uid'], $row['pid']);

					// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($thePidValue >= 0) {
					$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE', t3lib_BEfunc::getPagesTSconfig($tscPID));
					$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table, $row);
					$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'], $table, $field, $RTEtypeVal);

					if (!$thisConfig['disabled']) {
						if (!$this->disableRTE) {
							$this->RTEcounter++;

								// Find alternative relative path for RTE images/links:
							$eFile = t3lib_parsehtml_proc::evalWriteFile($specConf['static_write'], $row);
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';

								// Get RTE object, draw form and set flag:
							$RTEobj = t3lib_BEfunc::RTEgetObj();
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
			if ($specConf['rte_only']) { // Show message, if no RTE (field can only be edited with RTE!)
				$item = '<p><em>' . htmlspecialchars($this->getLL('l_noRTEfound')) . '</em></p>';
			} else {
				if ($specConf['nowrap']) {
					$wrap = 'off';
				} else {
					$wrap = ($config['wrap'] ? $config['wrap'] : 'virtual');
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

				$evalList = t3lib_div::trimExplode(',', $config['eval'], 1);
				foreach ($evalList as $func) {
					switch ($func) {
						case 'required':
							$this->registerRequiredProperty('field', $table . '_' . $row['uid'] . '_' . $field, $PA['itemFormElName']);
						break;
						default:
							if (substr($func, 0, 3) == 'tx_') {
									// Pair hook to the one in t3lib_TCEmain::checkValue_input_Eval() and t3lib_TCEmain::checkValue_text_Eval()
								$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$func] . ':&' . $func);
								if (is_object($evalObj) && method_exists($evalObj, 'deevaluateFieldValue')) {
									$_params = array(
										'value' => $PA['itemFormElValue']
									);
									$PA['itemFormElValue'] = $evalObj->deevaluateFieldValue($_params);
								}
							}
						break;
					}
				}

				$iOnChange = implode('', $PA['fieldChangeFunc']);
				$item .= '
							<textarea id="' . uniqid('tceforms-textarea-') . '" name="' . $PA['itemFormElName'] . '"' . $formWidthText . $class . ' rows="' . $rows . '" wrap="' . $wrap . '" onchange="' . htmlspecialchars($iOnChange) . '"' . $PA['onFocus'] . '>' .
						 t3lib_div::formatForTextarea($PA['itemFormElValue']) .
						 '</textarea>';
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeCheck($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];

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
		$thisValue = intval($PA['itemFormElValue']);

		$cols = intval($config['cols']);
		if ($cols > 1) {
			$item .= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c = 0; $c < count($selItems); $c++) {
				$p = $selItems[$c];
				if (!($c % $cols)) {
					$item .= '<tr>';
				}
				$cBP = $this->checkBoxParams($PA['itemFormElName'], $thisValue, $c, count($selItems), implode('', $PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'] . '_' . $c;
				$cBID = $PA['itemFormElID'] . '_' . $c;
				$item .= '<td nowrap="nowrap">' .
						 '<input type="checkbox"' . $this->insertDefStyle('check') . ' value="1" name="' . $cBName . '"' . $cBP . $disabled . ' id="' . $cBID . '" />' .
						 $this->wrapLabels('<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>&nbsp;') .
						 '</td>';
				if (($c % $cols) + 1 == $cols) {
					$item .= '</tr>';
				}
			}
			if ($c % $cols) {
				$rest = $cols - ($c % $cols);
				for ($c = 0; $c < $rest; $c++) {
					$item .= '<td></td>';
				}
				if ($c > 0) {
					$item .= '</tr>';
				}
			}
			$item .= '</table>';
		} else {
			for ($c = 0; $c < count($selItems); $c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($PA['itemFormElName'], $thisValue, $c, count($selItems), implode('', $PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'] . '_' . $c;
				$cBID = $PA['itemFormElID'] . '_' . $c;
				$item .= ($c > 0 ? '<br />' : '') .
						 '<input type="checkbox"' . $this->insertDefStyle('check') . ' value="1" name="' . $cBName . '"' . $cBP . $PA['onFocus'] . $disabled . ' id="' . $cBID . '" />' .
						 $this->wrapLabels('<label for="' . $cBID . '">' . htmlspecialchars($p[0]) . '</label>');
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeRadio($table, $field, $row, &$PA) {
		$config = $PA['fieldConf']['config'];

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
		for ($c = 0; $c < count($selItems); $c++) {
			$p = $selItems[$c];
			$rID = $PA['itemFormElID'] . '_' . $c;
			$rOnClick = implode('', $PA['fieldChangeFunc']);
			$rChecked = (!strcmp($p[1], $PA['itemFormElValue']) ? ' checked="checked"' : '');
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeSelect($table, $field, $row, &$PA) {
		global $TCA;

			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Getting the selector box items from the system
		$selItems = $this->addSelectOptionsToItemArray(
			$this->initItemArray($PA['fieldConf']),
			$PA['fieldConf'],
			$this->setTSconfig($table, $row),
			$field
		);
			// Possibly filter some items:
		$keepItemsFunc = create_function('$value', 'return $value[1];');
		$selItems = t3lib_div::keepItemsInArray($selItems, $PA['fieldTSConfig']['keepItems'], $keepItemsFunc);
			// Possibly add some items:
		$selItems = $this->addItems($selItems, $PA['fieldTSConfig']['addItems.']);
			// Process items by a user function:
		if (isset($config['itemsProcFunc']) && $config['itemsProcFunc']) {
			$selItems = $this->procItems($selItems, $PA['fieldTSConfig']['itemsProcFunc.'], $config, $table, $row, $field);
		}

			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode(',', $PA['fieldTSConfig']['removeItems'], 1);
		foreach ($selItems as $tk => $p) {

				// Checking languages and authMode:
			$languageDeny = $TCA[$table]['ctrl']['languageField'] && !strcmp($TCA[$table]['ctrl']['languageField'], $field) && !$GLOBALS['BE_USER']->checkLanguageAccess($p[1]);
			$authModeDeny = $config['form_type'] == 'select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table, $field, $p[1], $config['authMode']);
			if (in_array($p[1], $removeItems) || $languageDeny || $authModeDeny) {
				unset($selItems[$tk]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
				$selItems[$tk][0] = $this->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]);
			}

				// Removing doktypes with no access:
			if (($table === 'pages' || $table === 'pages_language_overlay') && $field === 'doktype') {
				if (!($GLOBALS['BE_USER']->isAdmin() || t3lib_div::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'], $p[1]))) {
					unset($selItems[$tk]);
				}
			}
		}

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ ' . $this->getLL('l_noMatchingValue') . ' ]';

			// Prepare some values:
		$maxitems = intval($config['maxitems']);

			// If a SINGLE selector box...
		if ($maxitems <= 1 && $config['renderMode'] !== 'tree') {
			$item = $this->getSingleField_typeSelect_single($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif (!strcmp($config['renderMode'], 'checkbox')) { // Checkbox renderMode
			$item = $this->getSingleField_typeSelect_checkbox($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif (!strcmp($config['renderMode'], 'singlebox')) { // Single selector box renderMode
			$item = $this->getSingleField_typeSelect_singlebox($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} elseif (!strcmp($config['renderMode'], 'tree')) { // Tree renderMode
			$treeClass = t3lib_div::makeInstance('t3lib_TCEforms_Tree', $this);
			$item = $treeClass->renderField($table, $field, $row, $PA, $config, $selItems, $nMV_label);
		} else { // Traditional multiple selector box:
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
	 * Creates a single-selector box
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param	string		See getSingleField_typeSelect()
	 * @param	string		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		(Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param	array		Items available for selection
	 * @param	string		Label for no-matching-value
	 * @return	string		The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	function getSingleField_typeSelect_single($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {
			// check against inline uniqueness
		$inlineParent = $this->inline->getStructureLevel(-1);
		if (is_array($inlineParent) && $inlineParent['uid']) {
			if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
				$uniqueIds = $this->inline->inlineData['unique'][$this->inline->inlineNames['object'] . '[' . $table . ']']['used'];
				$PA['fieldChangeFunc']['inlineUnique'] = "inline.updateUnique(this,'" . $this->inline->inlineNames['object'] . '[' . $table . "]','" . $this->inline->inlineNames['form'] . "','" . $row['uid'] . "');";
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
		$size = intval($config['size']);
		$selectedStyle = ''; // Style set on <select/>

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
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
			$sM = (!strcmp($PA['itemFormElValue'], $p[1]) ? ' selected="selected"' : '');
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
				if (!strcmp($p[1], '--div--')) {
					$optGroupStart[0] = $p[0];
					if ($config['iconsInOptionTags']) {
						$optGroupStart[1] = $this->optgroupTagStyle($p[2]);
					} else {
						$optGroupStart[1] = $styleAttrValue;
					}

				} else {
					if (count($optGroupStart)) {
						if ($optGroupOpen) { // Closing last optgroup before next one starts
							$opt[] = '</optgroup>' . LF;
						}
						$opt[] = '<optgroup label="' . t3lib_div::deHSCentities(htmlspecialchars($optGroupStart[0])) . '"' .
								 ($optGroupStart[1] ? ' style="' . htmlspecialchars($optGroupStart[1]) . '"' : '') .
								 ' class="c-divider">' . LF;
						$optGroupOpen = TRUE;
						$c--;
						$optGroupStart = array();
					}
					$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' .
							 $sM .
							 ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') .
							 '>' . t3lib_div::deHSCentities(($p[0])) . '</option>' . LF;
				}
			}

				// If there is an icon for the selector box (rendered in selicon-table below)...:
				// if there is an icon ($p[2]), icons should be shown, and, if only selected are visible, is it selected
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM)) {
				list($selIconFile, $selIconInfo) = $this->getIcon($p[2]);
				if (!empty($selIconInfo)) {
					$iOnClick = $this->elName($PA['itemFormElName']) . '.selectedIndex=' . $c . '; ' .
						$this->elName($PA['itemFormElName']) . '.style.backgroundImage=' . $this->elName($PA['itemFormElName']) . '.options[' . $c . '].style.backgroundImage; ' .
						implode('', $PA['fieldChangeFunc']) . $this->blur() . 'return false;';
				} else {
					$iOnClick = $this->elName($PA['itemFormElName']) . '.selectedIndex=' . $c . '; ' .
						$this->elName($PA['itemFormElName']) . '.className=' . $this->elName($PA['itemFormElName']) . '.options[' . $c . '].className; ' .
						implode('', $PA['fieldChangeFunc']) . $this->blur() . 'return false;';
				}
				$selicons[] = array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="' . htmlspecialchars($iOnClick) . '">' : '') .
					$this->getIconHtml($p[2], htmlspecialchars($p[0]), htmlspecialchars($p[0])) .
					(!$onlySelectedIconShown ? '</a>' : ''),
					$c, $sM);
			}
			$c++;
		}

		if ($optGroupOpen) { // Closing optgroup if open
			$opt[] = '</optgroup>';
			$optGroupOpen = false;
		}

			// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);
			$opt[] = '<option value="' . htmlspecialchars($PA['itemFormElValue']) . '" selected="selected">' . htmlspecialchars($nMV_label) . '</option>';
		}

			// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex=' . $sI . ';} ' . implode('', $PA['fieldChangeFunc']);
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_selIconVal" value="' . htmlspecialchars($sI) . '" />'; // MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
		}
		if ($config['iconsInOptionTags']) {
			$classesForSelectTag[] = 'icon-select';
		}
		$item .= '<select' . $selectedStyle . ' id="' . uniqid('tceforms-select-') . '" name="' . $PA['itemFormElName'] . '"' .
				 $this->insertDefStyle('select', implode(' ', $classesForSelectTag)) .
				 ($size ? ' size="' . $size . '"' : '') .
				 ' onchange="' . htmlspecialchars($onChangeIcon . $sOnChange) . '"' .
				 $PA['onFocus'] . $disabled . '>';
		$item .= implode('', $opt);
		$item .= '</select>';

			// Create icon table:
		if (count($selicons) && !$config['noIconsBelowSelect']) {
			$item .= '<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = intval($config['selicon_cols']);
			if (!$selicon_cols) {
				$selicon_cols = count($selicons);
			}
			$sR = ceil(count($selicons) / $selicon_cols);
			$selicons = array_pad($selicons, $sR * $selicon_cols, '');
			for ($sa = 0; $sa < $sR; $sa++) {
				$item .= '<tr>';
				for ($sb = 0; $sb < $selicon_cols; $sb++) {
					$sk = ($sa * $selicon_cols + $sb);
					$imgN = 'selIcon_' . $table . '_' . $row['uid'] . '_' . $field . '_' . $selicons[$sk][1];
					$imgS = ($selicons[$sk][2] ? $this->backPath . 'gfx/content_selected.gif' : 'clear.gif');
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
	 * @param	string		See getSingleField_typeSelect()
	 * @param	string		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		(Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param	array		Items available for selection
	 * @param	string		Label for no-matching-value
	 * @return	string		The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	function getSingleField_typeSelect_checkbox(
		$table, $field, $row, &$PA, $config, $selItems, $nMV_label) {

		if (empty($selItems)) {
			return '';
		}

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$tRows = array();
		$c = 0;
		if (!$disabled) {
			$sOnChange = implode('', $PA['fieldChangeFunc']);
			$setAll = array(); // Used to accumulate the JS needed to restore the original selection.
			$unSetAll = array();
			foreach ($selItems as $p) {
					// Non-selectable element:
				if (!strcmp($p[1], '--div--')) {
					$tRows[] = '
						<tr class="c-header">
							<td colspan="3">' . htmlspecialchars($p[0]) . '</td>
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
						$selIcon = t3lib_iconWorks::getSpriteIcon('empty-empty');
 					}

						// Compile row:
					$rowId = uniqid('select_checkbox_row_');
					$onClickCell = $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked=!' . $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = ' . $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked ? "c-selectedItem" : "c-unselectedItem";';
					$setAll[] = $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked=1;';
					$setAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');$(\'' . $rowId . '\').addClassName(\'c-selectedItem\');';
					$unSetAll[] = $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked=0;';
					$unSetAll[] .= '$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').addClassName(\'c-unselectedItem\');';
					$restoreCmd[] = $this->elName($PA['itemFormElName'] . '[' . $c . ']') . '.checked=' . ($sM ? 1 : 0) . ';' .
									'$(\'' . $rowId . '\').removeClassName(\'c-selectedItem\');$(\'' . $rowId . '\').removeClassName(\'c-unselectedItem\');' .
									'$(\'' . $rowId . '\').addClassName(\'c-' . ($sM ? '' : 'un') . 'selectedItem\');';

						// Check if some help text is available
						// Since TYPO3 4.5 help text is expected to be an associative array
						// with two key, "title" and "description"
						// For the sake of backwards compatibility, we test if the help text
						// is a string and use it as a description (this could happen if items
						// are modified with an itemProcFunc)
					$hasHelp = FALSE;
					$help = '';
					$helpArray = array();
					if ((is_array($p[3]) && count($p[3]) > 0) || !empty($p[3])) {
						$hasHelp = TRUE;
						if (is_array($p[3])) {
							$helpArray = $p[3];
						} else {
							$helpArray['description'] = $p[3];
						}
					}

					$label = t3lib_div::deHSCentities(htmlspecialchars($p[0]));
					if ($hasHelp) {
						$help = t3lib_BEfunc::wrapInHelp('', '', '', $helpArray);
					}

					$tRows[] = '
						<tr id="' . $rowId . '" class="' . ($sM ? 'c-selectedItem' : 'c-unselectedItem') . '" onclick="' . htmlspecialchars($onClick) . '" style="cursor: pointer;">
							<td class="c-checkbox"><input type="checkbox"' . $this->insertDefStyle('check') . ' name="' . htmlspecialchars($PA['itemFormElName'] . '[' . $c . ']') . '" value="' . htmlspecialchars($p[1]) . '"' . $sM . ' onclick="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . ' /></td>
							<td class="c-labelCell" onclick="' . htmlspecialchars($onClickCell) . '">' .
							   $this->getIconHtml($selIcon) .
							   $label .
							   '</td>
								<td class="c-descr" onclick="' . htmlspecialchars($onClickCell) . '">' . ((empty($help)) ? '' : $help) . '</td>
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
							<td class="c-checkbox"><input type="checkbox"' . $this->insertDefStyle('check') . ' name="' . htmlspecialchars($PA['itemFormElName'] . '[' . $c . ']') . '" value="' . htmlspecialchars($theNoMatchValue) . '" checked="checked" onclick="' . htmlspecialchars($sOnChange) . '"' . $PA['onFocus'] . $disabled . ' /></td>
							<td class="c-labelCell">' .
									  t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))) .
									  '</td><td>&nbsp;</td>
						</tr>');
				$c++;
			}
		}

			// Add an empty hidden field which will send a blank value if all items are unselected.
		$item .= '<input type="hidden" name="' . htmlspecialchars($PA['itemFormElName']) . '" value="" />';

			// Remaining checkboxes will get their set-all link:
		if (count($setAll)) {
			$tableHead = '<thead>
					<tr class="c-header-checkbox-controls t3-row-header">
						<td class="c-checkbox">
						<input type="checkbox" class="checkbox" onclick="if (checked) {' . htmlspecialchars(implode('', $setAll) . '} else {' .  implode('', $unSetAll) . '}') . '">
						</td>
						<td colspan="2">
						</td>
					</tr></thead>';
		}
			// Implode rows in table:
		$item .= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">' .
				$tableHead .
				'<tbody>' . implode('', $tRows) . '</tbody>
			</table>
			';

			// Add revert icon
		if (is_array($restoreCmd)) {
			$item .= '<a href="#" onclick="' . implode('', $restoreCmd) . ' return false;' . '">' .
					 t3lib_iconWorks::getSpriteIcon('actions-edit-undo', array('title' => htmlspecialchars($this->getLL('l_revertSelection')))) . '</a>';
		}

		return $item;
	}

	/**
	 * Creates a selectorbox list (renderMode = "singlebox")
	 * (Render function for getSingleField_typeSelect())
	 *
	 * @param	string		See getSingleField_typeSelect()
	 * @param	string		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		(Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param	array		Items available for selection
	 * @param	string		Label for no-matching-value
	 * @return	string		The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	function getSingleField_typeSelect_singlebox($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$opt = array();
		$restoreCmd = array(); // Used to accumulate the JS needed to restore the original selection.
		$c = 0;
		foreach ($selItems as $p) {
				// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]])) {
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->elName($PA['itemFormElName'] . '[]') . '.options[' . $c . '].selected=1;';
				unset($itemArray[$p[1]]);
			}

				// Non-selectable element:
			$nonSel = '';
			if (!strcmp($p[1], '--div--')) {
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}

				// Icon style for option tag:
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->optionTagStyle($p[2]);
			}

				// Compile <option> tag:
			$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' .
					 $sM .
					 $nonSel .
					 ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') .
					 '>' . t3lib_div::deHSCentities(htmlspecialchars($p[0])) . '</option>';
			$c++;
		}

			// Remaining values:
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement']) {
			foreach ($itemArray as $theNoMatchValue => $temp) {
					// Compile <option> tag:
				array_unshift($opt, '<option value="' . htmlspecialchars($theNoMatchValue) . '" selected="selected">' . t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))) . '</option>');
			}
		}

			// Compile selector box:
		$sOnChange = implode('', $PA['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"';
		$size = intval($config['size']);
		$cssPrefix = ($size === 1) ? 'tceforms-select' : 'tceforms-multiselect';
		$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($selItems) + 1, t3lib_div::intInRange($size, 1), $config['autoSizeMax']) : $size;
		$selectBox = '<select id="' . uniqid($cssPrefix) . '" name="' . $PA['itemFormElName'] . '[]"' .
					 $this->insertDefStyle('select', $cssPrefix) .
					 ($size ? ' size="' . $size . '"' : '') .
					 ' multiple="multiple" onchange="' . htmlspecialchars($sOnChange) . '"' .
					 $PA['onFocus'] .
					 $selector_itemListStyle .
					 $disabled . '>
						' .
					 implode('
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
					<em>' .
				 htmlspecialchars($this->getLL('l_holdDownCTRL')) .
				 '</em>
					</td>
					<td valign="top">
					  <a href="#" onclick="' . htmlspecialchars($this->elName($PA['itemFormElName'] . '[]') . '.selectedIndex=-1;' . implode('', $restoreCmd) . ' return false;') . '" title="' . htmlspecialchars($this->getLL('l_revertSelection')) . '">' .
				 t3lib_iconWorks::getSpriteIcon('actions-edit-undo') .
				 '</a>
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
	 * @param	string		See getSingleField_typeSelect()
	 * @param	string		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		See getSingleField_typeSelect()
	 * @param	array		(Redundant) content of $PA['fieldConf']['config'] (for convenience)
	 * @param	array		Items available for selection
	 * @param	string		Label for no-matching-value
	 * @return	string		The HTML code for the item
	 * @see getSingleField_typeSelect()
	 */
	function getSingleField_typeSelect_multiple($table, $field, $row, &$PA, $config, $selItems, $nMV_label) {

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

			// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '" />';
		}

			// Set max and min items:
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = t3lib_div::intInRange($config['minitems'], 0);

			// Register the required number of elements:
		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));

			// Get "removeItems":
		$removeItems = t3lib_div::trimExplode(',', $PA['fieldTSConfig']['removeItems'], 1);

			// Get the array with selected items:
		$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

			// Possibly filter some items:
		$keepItemsFunc = create_function('$value', '$parts=explode(\'|\',$value,2); return rawurldecode($parts[0]);');
		$itemArray = t3lib_div::keepItemsInArray($itemArray, $PA['fieldTSConfig']['keepItems'], $keepItemsFunc);

			// Perform modification of the selected items array:
		foreach ($itemArray as $tk => $tv) {
			$tvP = explode('|', $tv, 2);
			$evalValue = $tvP[0];
			$isRemoved = in_array($evalValue, $removeItems) || ($config['form_type'] == 'select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table, $field, $evalValue, $config['authMode']));
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

		if (!$disabled) {
				// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach ($selItems as $p) {
				if ($config['iconsInOptionTags']) {
					$styleAttrValue = $this->optionTagStyle($p[2]);
				}
				$opt[] = '<option value="' . htmlspecialchars($p[1]) . '"' .
						 ($styleAttrValue ? ' style="' . htmlspecialchars($styleAttrValue) . '"' : '') .
						 '>' . $p[0] . '</option>';
			}

				// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="' . htmlspecialchars($config['itemListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"';
			$size = intval($config['size']);
			$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($itemArray) + 1, t3lib_div::intInRange($size, 1), $config['autoSizeMax']) : $size;
			if ($config['exclusiveKeys']) {
				$sOnChange = 'setFormValueFromBrowseWin(\'' . $PA['itemFormElName'] . '\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text,\'' . $config['exclusiveKeys'] . '\'); ';
			} else {
				$sOnChange = 'setFormValueFromBrowseWin(\'' . $PA['itemFormElName'] . '\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); ';
			}
			$sOnChange .= implode('', $PA['fieldChangeFunc']);
			$itemsToSelect = '
				<select id="' . uniqid('tceforms-multiselect-') . '" name="' . $PA['itemFormElName'] . '_sel"' .
							 $this->insertDefStyle('select', 'tceforms-multiselect tceforms-itemstoselect') .
							 ($size ? ' size="' . $size . '"' : '') .
							 ' onchange="' . htmlspecialchars($sOnChange) . '"' .
							 $PA['onFocus'] .
							 $selector_itemListStyle . '>
					' . implode('
					', $opt) . '
				</select>';
		}

			// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
			'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
			'dontShowMoveIcons' => ($maxitems <= 1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->getLL('l_selected') . ':<br />',
				'items' => $this->getLL('l_items') . ':<br />'
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeGroup($table, $field, $row, &$PA) {
			// Init:
		$config = $PA['fieldConf']['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'], 0);
		if (!$maxitems) {
			$maxitems = 100000;
		}
		$minitems = t3lib_div::intInRange($config['minitems'], 0);
		$allowed = trim($config['allowed']);
		$disallowed = trim($config['disallowed']);

		$disabled = '';
		if ($this->renderReadonly || $config['readOnly']) {
			$disabled = ' disabled="disabled"';
		}

		$item .= '<input type="hidden" name="' . $PA['itemFormElName'] . '_mul" value="' . ($config['multiple'] ? 1 : 0) . '"' . $disabled . ' />';
		$this->registerRequiredProperty('range', $PA['itemFormElName'], array($minitems, $maxitems, 'imgName' => $table . '_' . $row['uid'] . '_' . $field));
		$info = '';

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Acting according to either "file" or "db" type:
		switch ((string) $config['internal_type']) {
			case 'file_reference':
				$config['uploadfolder'] = '';
				// Fall through
			case 'file': // If the element is of the internal type "file":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $allowed, 1);
				if (!count($tempFT)) {
					$info .= '*';
				}
				foreach ($tempFT as $ext) {
					if ($ext) {
						$info .= strtoupper($ext) . ' ';
					}
				}
					// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',', $disallowed, 1);
				if (count($tempFT_dis)) {
					$info .= '<br />';
				}
				foreach ($tempFT_dis as $ext) {
					if ($ext) {
						$info .= '-' . strtoupper($ext) . ' ';
					}
				}

					// Making the array of file items:
				$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

					// Showing thumbnails:
				$thumbsnail = '';
				if ($show_thumbs) {
					$imgs = array();
					foreach ($itemArray as $imgRead) {
						$imgP = explode('|', $imgRead);
						$imgPath = rawurldecode($imgP[0]);

						$rowCopy = array();
						$rowCopy[$field] = $imgPath;

						$imgs[] = '<span class="nobr">' . t3lib_BEfunc::thumbCode($rowCopy, $table, $field, $this->backPath, 'thumbs.php', $config['uploadfolder'], 0, ' align="middle"') .
								  $imgPath .
								  '</span>';
					}
					$thumbsnail = implode('<br />', $imgs);
				}

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item .= $this->dbFileIcons($PA['itemFormElName'], 'file', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus']);

				if (!$disabled && !(isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'upload'))) {
						// Adding the upload field:
					if ($this->edit_docModuleUpload && $config['uploadfolder']) {
						$item .= '<input type="file" name="' . $PA['itemFormElName_file'] . '" size="35" onchange="' . implode('', $PA['fieldChangeFunc']) . '" />';
					}
				}
			break;
			case 'folder': // If the element is of the internal type "folder":

					// array of folder items:
				$itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ?
							' style="' . htmlspecialchars($config['selectedListStyle']) . '"'
							: ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);

				$item .= $this->dbFileIcons(
					$PA['itemFormElName'],
					'folder',
					'',
					$itemArray,
					'',
					$params,
					$PA['onFocus']
				);
			break;
			case 'db': // If the element is of the internal type "db":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',', $allowed, TRUE);
				if (!strcmp(trim($tempFT[0]), '*')) {
					$onlySingleTableAllowed = false;
					$info .= '<span class="nobr">' .
							 htmlspecialchars($this->getLL('l_allTables')) .
							 '</span><br />';
				} elseif ($tempFT) {
					$onlySingleTableAllowed = (count($tempFT) == 1);
					foreach ($tempFT as $theT) {
						$info .= '<span class="nobr">' .
								 t3lib_iconWorks::getSpriteIconForRecord($theT, array()) .
								 htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])) .
								 '</span><br />';
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();

					// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',', $PA['itemFormElValue'], 1);
				foreach ($temp_itemArray as $dbRead) {
					$recordParts = explode('|', $dbRead);
					list($this_table, $this_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
						// For the case that no table was found and only a single table is defined to be allowed, use that one:
					if (!$this_table && $onlySingleTableAllowed) {
						$this_table = $allowed;
					}
					$itemArray[] = array('table' => $this_table, 'id' => $this_uid);
					if (!$disabled && $show_thumbs) {
						$rr = t3lib_BEfunc::getRecordWSOL($this_table, $this_uid);
						$imgs[] = '<span class="nobr">' .
								  $this->getClickMenu(
									  t3lib_iconWorks::getSpriteIconForRecord(
										  $this_table,
										  $rr,
										  array(
											   'style' => 'vertical-align:top',
											   'title' => htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'], $perms_clause, 15) . ' [UID: ' . $rr['uid'] . ']')
										  )
									  ),
									  $this_table,
									  $this_uid
								  ) .
								  '&nbsp;' .
								  t3lib_BEfunc::getRecordTitle($this_table, $rr, TRUE) . ' <span class="typo3-dimmed"><em>[' . $rr['uid'] . ']</em></span>' .
								  '</span>';
					}
				}
				$thumbsnail = '';
				if (!$disabled && $show_thumbs) {
					$thumbsnail = implode('<br />', $imgs);
				}

					// Creating the element:
				$noList = isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'list');
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems <= 1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'], 0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="' . htmlspecialchars($config['selectedListStyle']) . '"' : ' style="' . $this->defaultMultipleSelectorStyle . '"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readOnly' => $disabled,
					'noBrowser' => $noList || (isset($config['disable_controls']) && t3lib_div::inList($config['disable_controls'], 'browser')),
					'noList' => $noList,
				);
				$item .= $this->dbFileIcons($PA['itemFormElName'], 'db', implode(',', $tempFT), $itemArray, '', $params, $PA['onFocus'], $table, $field, $row['uid']);

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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeNone($table, $field, $row, &$PA) {
			// Init:
		$config = $PA['fieldConf']['config'];
		$itemValue = $PA['itemFormElValue'];

		return $this->getSingleField_typeNone_render($config, $itemValue);
	}

	/**
	 * HTML rendering of a value which is not editable.
	 *
	 * @param	array		Configuration for the display
	 * @param	string		The value to display
	 * @return	string		The HTML code for the display
	 * @see getSingleField_typeNone();
	 */
	function getSingleField_typeNone_render($config, $itemValue) {

			// is colorScheme[0] the right value?
		$divStyle = 'border:solid 1px ' . t3lib_div::modifyHTMLColorAll($this->colorScheme[0], -30) . ';' . $this->defStyle . $this->formElStyle('none') . ' background-color: ' . $this->colorScheme[0] . '; padding-left:1px;color:#555;';

		if ($config['format']) {
			$itemValue = $this->formatValue($config, $itemValue);
		}

		$rows = intval($config['rows']);
		if ($rows > 1) {
			if (!$config['pass_content']) {
				$itemValue = nl2br(htmlspecialchars($itemValue));
			}
				// like textarea
			$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = $rows = t3lib_div::intInRange($rows, 1, 20);
				if (strlen($itemValue) > $this->charsPerRow * 2) {
					$cols = $this->maxTextareaWidth;
					$rows = t3lib_div::intInRange(round(strlen($itemValue) / $this->charsPerRow), count(explode(LF, $itemValue)), 20);
					if ($rows < $origRows) {
						$rows = $origRows;
					}
				}
			}

			if ($this->docLarge) {
				$cols = round($cols * $this->form_largeComp);
			}
			$width = ceil($cols * $this->form_rowsToStylewidth);
				// hardcoded: 12 is the height of the font
			$height = $rows * 12;

			$item = '
				<div style="' . htmlspecialchars($divStyle . ' overflow:auto; height:' . $height . 'px; width:' . $width . 'px;') . '" class="' . htmlspecialchars($this->formElClass('none')) . '">' .
					$itemValue .
					'</div>';
		} else {
			if (!$config['pass_content']) {
				$itemValue = htmlspecialchars($itemValue);
			}

			$cols = $config['cols'] ? $config['cols'] : ($config['size'] ? $config['size'] : $this->maxInputWidth);
			if ($this->docLarge) {
				$cols = round($cols * $this->form_largeComp);
			}
			$width = ceil($cols * $this->form_rowsToStylewidth);

				// overflow:auto crashes mozilla here. Title tag is usefull when text is longer than the div box (overflow:hidden).
			$item = '
				<div style="' . htmlspecialchars($divStyle . ' overflow:hidden; width:' . $width . 'px;') . '" class="' . htmlspecialchars($this->formElClass('none')) . '" title="' . $itemValue . '">' .
					'<span class="nobr">' . (strcmp($itemValue, '') ? $itemValue : '&nbsp;') . '</span>' .
					'</div>';
		}

		return $item;
	}

	/**
	 * Handler for Flex Forms
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeFlex($table, $field, $row, &$PA) {

			// Data Structure:
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($PA['fieldConf']['config'], $row, $table);

			// Manipulate Flexform DS via TSConfig and group access lists
		if (is_array($dataStructArray)) {
			$flexFormHelper = t3lib_div::makeInstance('t3lib_TCEforms_Flexforms');
			$dataStructArray = $flexFormHelper->modifyFlexFormDS($dataStructArray, $table, $field, $row, $PA['fieldConf']['config']);
			unset($flexFormHelper);
		}

			// Get data structure:
		if (is_array($dataStructArray)) {

				// Get data:
			$xmlData = $PA['itemFormElValue'];
			$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset) {
				$currentCharset = $GLOBALS['LANG']->charSet;
				$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData, $storeInCharset, $currentCharset, 1);
			}
			$editData = t3lib_div::xml2array($xmlData);
			if (!is_array($editData)) { // Must be XML parsing error...
				$editData = array();
			} elseif (!isset($editData['meta']) || !is_array($editData['meta'])) {
				$editData['meta'] = array();
			}

				// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF'; // Sheet to display

				// Create sheet menu:
				//TODO; Why is this commented out?
			//			if (is_array($dataStructArray['sheets']))	{
			//				#$item.=$this->getSingleField_typeFlex_sheetMenu($dataStructArray['sheets'], $PA['itemFormElName'].'[meta][currentSheetId]', $sheet).'<br />';
			//			}

				// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

			$editData['meta']['currentLangId'] = array();

				// Look up page overlays:
			$checkPageLanguageOverlay = $GLOBALS['BE_USER']->getTSConfigVal('options.checkPageLanguageOverlay') ? TRUE : FALSE;
			if ($checkPageLanguageOverlay) {
				$pageOverlays = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows(
					'*',
					'pages_language_overlay',
					'pid=' . intval($row['pid']) .
					t3lib_BEfunc::deleteClause('pages_language_overlay') .
					t3lib_BEfunc::versioningPlaceholderClause('pages_language_overlay'),
					'',
					'',
					'',
					'sys_language_uid'
				);
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

				//TODO: Why is this commented out?
			//			if (!$langDisabled && count($languages) > 1)	{
			//				$item.=$this->getSingleField_typeFlex_langMenu($languages, $PA['itemFormElName'].'[meta][currentLangId]', $editData['meta']['currentLangId']).'<br />';
			//			}

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

			foreach ($rotateLang as $lKey) {
				if (!$langChildren && !$langDisabled) {
					$item .= '<strong>' . $this->getLanguageIcon($table, $row, 'v' . $lKey) . $lKey . ':</strong>';
				}

				$tabParts = array();
				foreach ($tabsToTraverse as $sheet) {
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray, $sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el'])) {
						$lang = 'l' . $lKey; // Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF'; // Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_lang'] = $lang;
							// Assemble key for loading the correct CSH file
						$dsPointerFields = t3lib_div::trimExplode(',', $GLOBALS['TCA'][$table]['columns'][$field]['config']['ds_pointerField'], TRUE);
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
						$tRows = $this->getSingleField_typeFlex_draw(
							$dataStruct['ROOT']['el'],
							$editData['data'][$sheet][$lang],
							$table,
							$field,
							$row,
							$PA,
							'[data][' . $sheet . '][' . $lang . ']'
						);
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
						'label' => ($dataStruct['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetTitle']) : $sheet),
						'description' => ($dataStruct['ROOT']['TCEforms']['sheetDescription'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetDescription']) : ''),
						'linkTitle' => ($dataStruct['ROOT']['TCEforms']['sheetShortDescr'] ? $this->sL($dataStruct['ROOT']['TCEforms']['sheetShortDescr']) : ''),
						'content' => $sheetContent
					);
				}

				if (is_array($dataStructArray['sheets'])) {
					$dividersToTabsBehaviour = (isset($GLOBALS['TCA'][$table]['ctrl']['dividers2tabs']) ? $GLOBALS['TCA'][$table]['ctrl']['dividers2tabs'] : 1);
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
	 * @param	[type]		$languages: ...
	 * @param	[type]		$elName: ...
	 * @param	[type]		$selectedLanguage: ...
	 * @param	[type]		$multi: ...
	 * @return	string		HTML for menu
	 */
	function getSingleField_typeFlex_langMenu($languages, $elName, $selectedLanguage, $multi = 1) {
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
	 * @param	array		Sheet array for which to render the menu
	 * @param	string		Form element name of the field containing the sheet pointer
	 * @param	string		Current sheet key
	 * @return	string		HTML for menu
	 */
	function getSingleField_typeFlex_sheetMenu($sArr, $elName, $sheetKey) {

		$tCells = array();
		$pct = round(100 / count($sArr));
		foreach ($sArr as $sKey => $sheetCfg) {
			if ($GLOBALS['BE_USER']->jsConfirmation(1)) {
				$onClick = 'if (confirm(TBE_EDITOR.labels.onChangeAlert) && TBE_EDITOR.checkSubmit(-1)){' . $this->elName($elName) . ".value='" . $sKey . "'; TBE_EDITOR.submitForm()};";
			} else {
				$onClick = 'if(TBE_EDITOR.checkSubmit(-1)){ ' . $this->elName($elName) . ".value='" . $sKey . "'; TBE_EDITOR.submitForm();}";
			}


			$tCells[] = '<td width="' . $pct . '%" style="' . ($sKey == $sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;') . ' cursor: hand;" onclick="' . htmlspecialchars($onClick) . '" align="center">' .
						($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey) .
						'</td>';
		}

		return '<table border="0" cellpadding="0" cellspacing="2" class="typo3-TCEforms-flexForm-sheetMenu"><tr>' . implode('', $tCells) . '</tr></table>';
	}

	/**
	 * Recursive rendering of flexforms
	 *
	 * @param	array		(part of) Data Structure for which to render. Keys on first level is flex-form fields
	 * @param	array		(part of) Data array of flexform corresponding to the input DS. Keys on first level is flex-form field names
	 * @param	string		Table name, eg. tt_content
	 * @param	string		Field name, eg. tx_templavoila_flex
	 * @param	array		The particular record from $table in which the field $field is found
	 * @param	array		Array of standard information for rendering of a form field in TCEforms, see other rendering functions too
	 * @param	string		Form field prefix, eg. "[data][sDEF][lDEF][...][...]"
	 * @param	integer		Indicates nesting level for the function call
	 * @param	string		Prefix for ID-values
	 * @param	boolean		Defines whether the next flexform level is open or closed. Comes from _TOGGLE pseudo field in FlexForm xml.
	 * @return	string		HTMl code for form.
	 */
	function getSingleField_typeFlex_draw($dataStruct, $editData, $table, $field, $row, &$PA, $formPrefix = '', $level = 0, $idPrefix = 'ID', $toggleClosed = FALSE) {

		$output = '';
		$mayRestructureFlexforms = $GLOBALS['BE_USER']->checkLanguageAccess(0);

			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct)) {
			foreach ($dataStruct as $key => $value) { // Traversing fields in structure:
				if (is_array($value)) { // The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
						// Title of field:
					$theTitle = htmlspecialchars(t3lib_div::fixed_lgd_cs($this->sL($value['tx_templavoila']['title']), 30));

						// If it's a "section" or "container":
					if ($value['type'] == 'array') {

							// Creating IDs for form fields:
							// It's important that the IDs "cascade" - otherwise we can't dynamically expand the flex form because this relies on simple string substitution of the first parts of the id values.
						$thisId = t3lib_div::shortMd5(uniqid('id', TRUE)); // This is a suffix used for forms on this level
						$idTagPrefix = $idPrefix . '-' . $thisId; // $idPrefix is the prefix for elements on lower levels in the hierarchy and we combine this with the thisId value to form a new ID on this level.

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
											$tRows[] = $this->getSingleField_typeFlex_draw(
												array($theType => $newSectionEl),
												array($theType => $theDat),
												$table,
												$field,
												$row,
												$PA,
												$formPrefix . '[' . $key . '][el][' . $cc . ']',
												$level + 1,
												$idTagPrefix,
												$v3['_TOGGLE']
											);
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
								$newElementTemplate = $this->getSingleField_typeFlex_draw(
									array($nnKey => $nCfg),
									array(),
									$table,
									$field,
									$row,
									$PA,
									$formPrefix . '[' . $key . '][el][' . $idTagPrefix . '-form]',
									$level + 1,
									$idTagPrefix
								);

									// Makes a "Add new" link:
								$var = uniqid('idvar');
								$replace = 'replace(/' . $idTagPrefix . '-/g,"' . $idTagPrefix . '-"+' . $var . '+"-")';
								$onClickInsert = 'var ' . $var . ' = "' . 'idx"+(new Date()).getTime();';
									// Do not replace $isTagPrefix in setActionStatus() because it needs section id!
								$onClickInsert .= 'new Insertion.Bottom($("' . $idTagPrefix . '"), unescape("' . rawurlencode($newElementTemplate) . '").' . $replace . '); setActionStatus("' . $idTagPrefix . '");';
								$onClickInsert .= 'eval(unescape("' . rawurlencode(implode(';', $this->additionalJS_post)) . '").' . $replace . ');';
								$onClickInsert .= 'TBE_EDITOR.addActionChecks("submit", unescape("' . rawurlencode(implode(';', $this->additionalJS_submit)) . '").' . $replace . ');';
								$onClickInsert .= 'return false;';
									// Kasper's comment (kept for history): Maybe there is a better way to do this than store the HTML for the new element in rawurlencoded format - maybe it even breaks with certain charsets? But for now this works...
								$this->additionalJS_post = $additionalJS_post_saved;
								$this->additionalJS_submit = $additionalJS_submit_saved;
								$new = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:cm.new', 1);
								$newElementsLinks[] = '<a href="#" onclick="' . htmlspecialchars($onClickInsert) . '">' .
													  t3lib_iconWorks::getSpriteIcon('actions-document-new') .
													  htmlspecialchars(t3lib_div::fixed_lgd_cs($this->sL($nCfg['tx_templavoila']['title']), 30)) . '</a>';
							}

								// Reverting internal variables we don't want to change:
							$this->requiredElements = $TEMP_requiredElements;

								// Adding the sections:
							$toggleAll = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.toggleall', 1);
							$output .= '
							<div class="t3-form-field-toggle-flexsection">
								<a href="#" onclick="' . htmlspecialchars('flexFormToggleSubs("' . $idTagPrefix . '"); return false;') . '">'
									   . t3lib_iconWorks::getSpriteIcon('actions-move-right', array('title' => $toggleAll)) . $toggleAll . '
								</a>
							</div>

							<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsection">' . implode('', $tRows) . '</div>';
							$output .= $mayRestructureFlexforms ? '<div class="t3-form-field-add-flexsection"><strong>' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.addnew', 1) . ':</strong> ' . implode(' | ', $newElementsLinks) . '</div>' : '';
						} else {
								// It is a container

							$toggleIcon_open = t3lib_iconWorks::getSpriteIcon('actions-move-down');
							$toggleIcon_close = t3lib_iconWorks::getSpriteIcon('actions-move-right');

								// Create on-click actions.
								//$onClickCopy = 'new Insertion.After($("'.$idTagPrefix.'"), getOuterHTML("'.$idTagPrefix.'").replace(/'.$idTagPrefix.'-/g,"'.$idTagPrefix.'-copy"+Math.floor(Math.random()*100000+1)+"-")); return false;';	// Copied elements doesn't work (well) in Safari while they do in Firefox and MSIE! UPDATE: It turned out that copying doesn't work for any browser, simply because the data from the copied form never gets submitted to the server for some reason! So I decided to simply disable copying for now. If it's requested by customers we can look to enable it again and fix the issue. There is one un-fixable problem though; Copying an element like this will violate integrity if files are attached inside that element because the file reference doesn't get an absolute path prefixed to it which would be required to have TCEmain generate a new copy of the file.
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
									<td align="right">' .
										  ($mayRestructureFlexforms ? t3lib_iconWorks::getSpriteIcon('actions-move-move', array('title' => 'Drag to Move')) : '') .
										  ($mayRestructureFlexforms ? '<a href="#" onclick="' . htmlspecialchars($onClickRemove) . '">' . t3lib_iconWorks::getSpriteIcon('actions-edit-delete', array('title' => 'Delete')) : '') .
										  '</td>
									</tr>
								</table>';

							$s = t3lib_div::revExplode('[]', $formPrefix, 2);
							$actionFieldName = '_ACTION_FLEX_FORM' . $PA['itemFormElName'] . $s[0] . '][_ACTION][' . $s[1];

								// Push the container to DynNestedStack as it may be toggled
							$this->pushToDynNestedStack('flex', $idTagPrefix);

								// Putting together the container:
							$this->additionalJS_delete = array();
							$output .= '
								<div id="' . $idTagPrefix . '" class="t3-form-field-container-flexsections">
									<input id="' . $idTagPrefix . '-action" type="hidden" name="' . htmlspecialchars($actionFieldName) . '" value=""/>

									' . $ctrlHeader . '
									<div class="t3-form-field-record-flexsection" id="' . $idTagPrefix . '-content"' . ($toggleClosed ? ' style="display:none;"' : '') . '>' .
									   $this->getSingleField_typeFlex_draw(
										   $value['el'],
										   $editData[$key]['el'],
										   $table,
										   $field,
										   $row,
										   $PA,
										   $formPrefix . '[' . $key . '][el]',
										   $level + 1,
										   $idTagPrefix
									   ) . '
									</div>
									<input id="' . $idTagPrefix . '-toggleClosed" type="hidden" name="' . htmlspecialchars('data[' . $table . '][' . $row['uid'] . '][' . $field . ']' . $formPrefix . '[_TOGGLE]') . '" value="' . ($toggleClosed ? 1 : 0) . '" />
								</div>';
							$output = str_replace('/*###REMOVE###*/', t3lib_div::slashJS(htmlspecialchars(implode('', $this->additionalJS_delete))), $output);
								// NOTICE: We are saving the toggle-state directly in the flexForm XML and "unauthorized" according to the data structure. It means that flexform XML will report unclean and a cleaning operation will remove the recorded togglestates. This is not a fatal problem. Ideally we should save the toggle states in meta-data but it is much harder to do that. And this implementation was easy to make and with no really harmful impact.

								// Pop the container from DynNestedStack
							$this->popFromDynNestedStack('flex', $idTagPrefix);
						}

							// If it's a "single form element":
					} elseif (is_array($value['TCEforms']['config'])) { // Rendering a single form element:

						if (is_array($PA['_valLang'])) {
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}

						$conditionData = is_array($editData) ? $editData : array();
							// add current $row to data processed by isDisplayCondition()
						$conditionData['parentRec'] = $row;

						$tRows = array();
						foreach ($rotateLang as $vDEFkey) {
							$vDEFkey = 'v' . $vDEFkey;

							if (!$value['TCEforms']['displayCond'] || $this->isDisplayCondition($value['TCEforms']['displayCond'], $conditionData, $vDEFkey)) {
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

								if (
									$fakePA['fieldConf']['onChange'] == 'reload' ||
									($GLOBALS['TCA'][$table]['ctrl']['type'] && !strcmp($key, $GLOBALS['TCA'][$table]['ctrl']['type'])) ||
									($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] && t3lib_div::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'], $key))) {
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
									$defInfo = '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 0) .
											   $this->previewFieldValue($editData[$key]['vDEF'], $fakePA['fieldConf'], $field) . '&nbsp;</div>';
								} else {
									$defInfo = '';
								}

								if (!$PA['_noEditDEF']) {
									$prLang = $this->getAdditionalPreviewLanguages();
									foreach ($prLang as $prL) {
										$defInfo .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 'v' . $prL['ISOcode']) .
													$this->previewFieldValue($editData[$key]['v' . $prL['ISOcode']], $fakePA['fieldConf'], $field) . '&nbsp;</div>';
									}
								}

								$languageIcon = '';
								if ($vDEFkey != 'vDEF') {
									$languageIcon = $this->getLanguageIcon($table, $row, $vDEFkey);
								}
									// Put row together
									// possible linebreaks in the label through xml: \n => <br/>, usage of nl2br() not possible, so it's done through str_replace
								$processedTitle = str_replace('\n', '<br />', $theTitle);
								$tRows[] = '<div class="t3-form-field-container t3-form-field-container-flex">' .
										   '<div class="t3-form-field-label t3-form-field-label-flex">' .
										   $languageIcon .
										   t3lib_BEfunc::wrapInHelp($PA['_cshKey'], $key, $processedTitle) .
										   '</div>
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
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeUnknown($table, $field, $row, &$PA) {
		$item = 'Unknown type: ' . $PA['fieldConf']['config']['form_type'] . '<br />';

		return $item;
	}

	/**
	 * User defined field type
	 *
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	string		The HTML code for the TCEform field
	 */
	function getSingleField_typeUser($table, $field, $row, &$PA) {
		$PA['table'] = $table;
		$PA['field'] = $field;
		$PA['row'] = $row;

		$PA['pObj'] =& $this;

		return t3lib_div::callUserFunction($PA['fieldConf']['config']['userFunc'], $PA, $this);
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
	 * @param	array		Configuration for the display
	 * @param	string		The value to display
	 * @return	string		Formatted Field content
	 */
	function formatValue($config, $itemValue) {
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
					$value .= ' (' .
							  t3lib_BEfunc::calcAge(($GLOBALS['EXEC_TIME'] - $itemValue), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')) .
							  ')';
				}
				$itemValue = $value;
			break;
			case 'datetime': // compatibility with "eval" (type "input")
				$itemValue = date('H:i d-m-Y', $itemValue);
			break;
			case 'time': // compatibility with "eval" (type "input")
				$itemValue = date('H:i', $itemValue);
			break;
			case 'timesec': // compatibility with "eval" (type "input")
				$itemValue = date('H:i:s', $itemValue);
			break;
			case 'year': // compatibility with "eval" (type "input")
				$itemValue = date('Y', $itemValue);
			break;
			case 'int':
				$baseArr = array('dec' => 'd', 'hex' => 'x', 'HEX' => 'X', 'oct' => 'o', 'bin' => 'b');
				$base = trim($config['format.']['base']);
				$format = $baseArr[$base] ? $baseArr[$base] : 'd';
				$itemValue = sprintf('%' . $format, $itemValue);
			break;
			case 'float':
				$precision = t3lib_div::intInRange($config['format.']['precision'], 1, 10, 2);
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
				$value = t3lib_div::formatSize(intval($itemValue));
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
					$itemValue = t3lib_div::callUserFunction($func, $params, $this);
				}
			break;
			default:
			break;
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
	 * @param	string		The table name. MUST be in $TCA
	 * @param	array		The row from the table, should contain at least the "type" field, if applicable.
	 * @return	string		Return the "type" value for this record, ready to pick a "types" configuration from the $TCA array.
	 */
	function getRTypeNum($table, $row) {
		global $TCA;
			// If there is a "type" field configured...
		if ($TCA[$table]['ctrl']['type']) {
			$typeFieldName = $TCA[$table]['ctrl']['type'];
			$typeFieldConfig = $TCA[$table]['columns'][$typeFieldName];
			$typeNum = $this->getLanguageOverlayRawValue($table, $row, $typeFieldName, $typeFieldConfig);
			if (!strcmp($typeNum, '')) {
				$typeNum = 0;
			} // If that value is an empty string, set it to "0" (zero)
		} else {
			$typeNum = 0; // If no "type" field, then set to "0" (zero)
		}

		$typeNum = (string) $typeNum; // Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$TCA[$table]['types'][$typeNum]) { // However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
			$typeNum = 1;
		}

		return $typeNum;
	}

	/**
	 * Used to adhoc-rearrange the field order normally set in the [types][showitem] list
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @return	array		Returns rearranged version (keys are changed around as well.)
	 * @see getMainFields()
	 */
	function rearrange($fields) {
		$fO = array_flip(t3lib_div::trimExplode(',', $this->fieldOrder, 1));
		$newFields = array();
		foreach ($fields as $cc => $content) {
			$cP = t3lib_div::trimExplode(';', $content);
			if (isset($fO[$cP[0]])) {
				$newFields[$fO[$cP[0]]] = $content;
				unset($fields[$cc]);
			}
		}
		ksort($newFields);
		$fields = array_merge($newFields, $fields); // Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		return $fields;
	}

	/**
	 * Producing an array of field names NOT to display in the form, based on settings from subtype_value_field, bitmask_excludelist_bits etc.
	 * Notice, this list is in NO way related to the "excludeField" flag
	 *
	 * @param	string		Table name, MUST be in $TCA
	 * @param	array		A record from table.
	 * @param	string		A "type" pointer value, probably the one calculated based on the record array.
	 * @return	array		Array with fieldnames as values. The fieldnames are those which should NOT be displayed "anyways"
	 * @see getMainFields()
	 */
	function getExcludeElements($table, $row, $typeNum) {
		global $TCA;

			// Init:
		$excludeElements = array();

			// If a subtype field is defined for the type
		if ($TCA[$table]['types'][$typeNum]['subtype_value_field']) {
			$sTfield = $TCA[$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($TCA[$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]])) {
				$excludeElements = t3lib_div::trimExplode(',', $TCA[$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]], 1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($TCA[$table]['types'][$typeNum]['bitmask_value_field']) {
			$sTfield = $TCA[$table]['types'][$typeNum]['bitmask_value_field'];
			$sTValue = t3lib_div::intInRange($row[$sTfield], 0);
			if (is_array($TCA[$table]['types'][$typeNum]['bitmask_excludelist_bits'])) {
				foreach ($TCA[$table]['types'][$typeNum]['bitmask_excludelist_bits'] as $bitKey => $eList) {
					$bit = substr($bitKey, 1);
					if (t3lib_div::testInt($bit)) {
						$bit = t3lib_div::intInRange($bit, 0, 30);
						if (
							(substr($bitKey, 0, 1) == '-' && !($sTValue & pow(2, $bit))) ||
							(substr($bitKey, 0, 1) == '+' && ($sTValue & pow(2, $bit)))
						) {
							$excludeElements = array_merge($excludeElements, t3lib_div::trimExplode(',', $eList, 1));
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
	 * @param	string		Table name, MUST be in $TCA
	 * @param	array		A record from table.
	 * @param	string		A "type" pointer value, probably the one calculated based on the record array.
	 * @return	array		An array containing two values: 1) Another array containing fieldnames to add and 2) the subtype value field.
	 * @see getMainFields()
	 */
	function getFieldsToAdd($table, $row, $typeNum) {
		global $TCA;

			// Init:
		$addElements = array();

			// If a subtype field is defined for the type
		if ($TCA[$table]['types'][$typeNum]['subtype_value_field']) {
			$sTfield = $TCA[$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($TCA[$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]])) {
				$addElements = t3lib_div::trimExplode(',', $TCA[$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]], 1);
			}
		}
			// Return the return
		return array($addElements, $sTfield);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @param	array		The output from getFieldsToAdd()
	 * @return	array		Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 */
	function mergeFieldsWithAddedFields($fields, $fieldsToAdd) {
		if (count($fieldsToAdd[0])) {
			$c = 0;
			foreach ($fields as $fieldInfo) {
				$parts = explode(';', $fieldInfo);
				if (!strcmp(trim($parts[0]), $fieldsToAdd[1])) {
					array_splice(
						$fields,
						$c + 1,
						0,
						$fieldsToAdd[0]
					);
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
	 * @param	string		The table name
	 * @param	array		The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param	string		Optionally you can specify the field name as well. In that case the TSconfig for the field is returned.
	 * @return	mixed		The TSconfig values (probably in an array)
	 * @see t3lib_BEfunc::getTCEFORM_TSconfig()
	 */
	function setTSconfig($table, $row, $field = '') {
		$mainKey = $table . ':' . $row['uid'];
		if (!isset($this->cachedTSconfig[$mainKey])) {
			$this->cachedTSconfig[$mainKey] = t3lib_BEfunc::getTCEFORM_TSconfig($table, $row);
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
	 * This overrides the setting in $TCA[<table>]['columns'][<field>]['config']['appearance']['useSortable'].
	 *
	 * @param	array		$fieldConfig: TCA field configuration
	 * @param	array		$TSconfig: TSconfig
	 * @return	array		Changed TCA field configuration
	 */
	function overrideFieldConf($fieldConfig, $TSconfig) {
		if (is_array($TSconfig)) {
			$TSconfig = t3lib_div::removeDotsFromTS($TSconfig);
			$type = $fieldConfig['type'];
			if (is_array($TSconfig['config']) && is_array($this->allowOverrideMatrix[$type])) {
					// Check if the keys in TSconfig['config'] are allowed to override TCA field config:
				foreach (array_keys($TSconfig['config']) as $key) {
					if (!in_array($key, $this->allowOverrideMatrix[$type], TRUE)) {
						unset($TSconfig['config'][$key]);
					}
				}
					// Override TCA field config by remaining TSconfig['config']:
				if (count($TSconfig['config'])) {
					$fieldConfig = t3lib_div::array_merge_recursive_overrule($fieldConfig, $TSconfig['config']);
				}
			}
		}

		return $fieldConfig;
	}

	/**
	 * Returns the "special" configuration (from the "types" "showitem" list) for a fieldname based on input table/record
	 * (Not used anywhere...?)
	 *
	 * @param	string		The table name
	 * @param	array		The table row (Should at least contain the "uid" value, even if "NEW..." string. The "pid" field is important as well, and negative values will be intepreted as pointing to a record from the same table.)
	 * @param	string		Specify the field name.
	 * @return	array
	 * @see getSpecConfFromString(), t3lib_BEfunc::getTCAtypes()
	 */
	function getSpecConfForField($table, $row, $field) {
			// Finds the current "types" configuration for the table/row:
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table, $row);

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
	 * @param	string		The "Part 4" of the fields configuration in "types" "showitem" lists.
	 * @param	string		The ['defaultExtras'] value from field configuration
	 * @return	array		An array with the special options in.
	 * @see getSpecConfForField(), t3lib_BEfunc::getSpecConfParts()
	 */
	function getSpecConfFromString($extraString, $defaultExtras) {
		return t3lib_BEfunc::getSpecConfParts($extraString, $defaultExtras);
	}


	/**
	 * Loads the elements of a palette (collection of secondary options) in an array.
	 *
	 * @param	string		The table name
	 * @param	array		The row array
	 * @param	string		The palette number/pointer
	 * @param	string		Optional alternative list of fields for the palette
	 * @return	array		The palette elements
	 */
	public function loadPaletteElements($table, $row, $palette, $itemList = '') {
		global $TCA;

		t3lib_div::loadTCA($table);
		$parts = array();

			// Getting excludeElements, if any.
		if (!is_array($this->excludeElements)) {
			$this->excludeElements = $this->getExcludeElements($table, $row, $this->getRTypeNum($table, $row));
		}

			// Load the palette TCEform elements
		if ($TCA[$table] && (is_array($TCA[$table]['palettes'][$palette]) || $itemList)) {
			$itemList = ($itemList ? $itemList : $TCA[$table]['palettes'][$palette]['showitem']);
			if ($itemList) {
				$fields = t3lib_div::trimExplode(',', $itemList, 1);
				foreach ($fields as $info) {
					$fieldParts = t3lib_div::trimExplode(';', $info);
					$theField = $fieldParts[0];
					if ($theField === '--linebreak--') {
						$parts[]['NAME'] = '--linebreak--';
					} elseif (!in_array($theField, $this->excludeElements) && $TCA[$table]['columns'][$theField]) {
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
	 * @param	string		Table name of the record being edited
	 * @param	array		Record array of the record being edited
	 * @return	void
	 */
	function registerDefaultLanguageData($table, $rec) {
		global $TCA;

			// Add default language:
		if ($TCA[$table]['ctrl']['languageField']
			&& $rec[$TCA[$table]['ctrl']['languageField']] > 0
			&& $TCA[$table]['ctrl']['transOrigPointerField']
			&& intval($rec[$TCA[$table]['ctrl']['transOrigPointerField']]) > 0) {

			$lookUpTable = $TCA[$table]['ctrl']['transOrigPointerTable'] ? $TCA[$table]['ctrl']['transOrigPointerTable'] : $table;

				// Get data formatted:
			$this->defaultLanguageData[$table . ':' . $rec['uid']] = t3lib_BEfunc::getRecordWSOL($lookUpTable, intval($rec[$TCA[$table]['ctrl']['transOrigPointerField']]));

				// Get data for diff:
			if ($TCA[$table]['ctrl']['transOrigDiffSourceField']) {
				$this->defaultLanguageData_diff[$table . ':' . $rec['uid']] = unserialize($rec[$TCA[$table]['ctrl']['transOrigDiffSourceField']]);
			}

				// If there are additional preview languages, load information for them also:
			$prLang = $this->getAdditionalPreviewLanguages();
			foreach ($prLang as $prL) {
				$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
				$tInfo = $t8Tools->translationInfo($lookUpTable, intval($rec[$TCA[$table]['ctrl']['transOrigPointerField']]), $prL['uid']);
				if (is_array($tInfo['translations'][$prL['uid']])) {
					$this->additionalPreviewLanguageData[$table . ':' . $rec['uid']][$prL['uid']] = t3lib_BEfunc::getRecordWSOL($table, intval($tInfo['translations'][$prL['uid']]['uid']));
				}
			}
		}
	}

	/**
	 * Creates language-overlay for a field value
	 * This means the requested field value will be overridden with the data from the default language.
	 * Can be used to render read only fields for example.
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited in current language
	 * @param	array		Content of $PA['fieldConf']
	 * @return	string		Unprocessed field value merged with default language data if needed
	 */
	function getLanguageOverlayRawValue($table, $row, $field, $fieldConf) {
		global $TCA;

		$value = $row[$field];

		if (is_array($this->defaultLanguageData[$table . ':' . $row['uid']])) {

			if ($fieldConf['l10n_mode'] == 'exclude'
				|| ($fieldConf['l10n_mode'] == 'mergeIfNotBlank' && strcmp(trim($this->defaultLanguageData[$table . ':' . $row['uid']][$field]), ''))) {
				$value = $this->defaultLanguageData[$table . ':' . $row['uid']][$field];
			}

		}

		return $value;
	}

	/**
	 * Renders the display of default language record content around current field.
	 * Will render content if any is found in the internal array, $this->defaultLanguageData, depending on registerDefaultLanguageData() being called prior to this.
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	function renderDefaultLanguageContent($table, $field, $row, $item) {
		if (is_array($this->defaultLanguageData[$table . ':' . $row['uid']])) {
			$dLVal = t3lib_BEfunc::getProcessedValue($table, $field, $this->defaultLanguageData[$table . ':' . $row['uid']][$field], 0, 1);
			$fCfg = $GLOBALS['TCA'][$table]['columns'][$field];

				// Don't show content if it's for IRRE child records:
			if ($fCfg['config']['type'] != 'inline') {
				if (strcmp($dLVal, '')) {
					$item .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 0) .
							 $this->previewFieldValue($dLVal, $fCfg, $field) . '&nbsp;</div>';
				}

				$prLang = $this->getAdditionalPreviewLanguages();
				foreach ($prLang as $prL) {
					$dlVal = t3lib_BEfunc::getProcessedValue($table, $field, $this->additionalPreviewLanguageData[$table . ':' . $row['uid']][$prL['uid']][$field], 0, 1);

					if (strcmp($dlVal, '')) {
						$item .= '<div class="typo3-TCEforms-originalLanguageValue">' . $this->getLanguageIcon($table, $row, 'v' . $prL['ISOcode']) .
								 $this->previewFieldValue($dlVal, $fCfg, $field) . '&nbsp;</div>';
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
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	function renderDefaultLanguageDiff($table, $field, $row, $item) {
		if (is_array($this->defaultLanguageData_diff[$table . ':' . $row['uid']])) {

				// Initialize:
			$dLVal = array(
				'old' => $this->defaultLanguageData_diff[$table . ':' . $row['uid']],
				'new' => $this->defaultLanguageData[$table . ':' . $row['uid']],
			);

			if (isset($dLVal['old'][$field])) { // There must be diff-data:
				if (strcmp($dLVal['old'][$field], $dLVal['new'][$field])) {

						// Create diff-result:
					$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');
					$diffres = $t3lib_diff_Obj->makeDiffDisplay(
						t3lib_BEfunc::getProcessedValue($table, $field, $dLVal['old'][$field], 0, 1),
						t3lib_BEfunc::getProcessedValue($table, $field, $dLVal['new'][$field], 0, 1)
					);

					$item .= '<div class="typo3-TCEforms-diffBox">' .
							 '<div class="typo3-TCEforms-diffBox-header">' . htmlspecialchars($this->getLL('l_changeInOrig')) . ':</div>' .
							 $diffres .
							 '</div>';
				}
			}
		}

		return $item;
	}

	/**
	 * Renders the diff-view of vDEF fields in flexforms
	 *
	 * @param	string		Table name of the record being edited
	 * @param	string		Field name represented by $item
	 * @param	array		Record array of the record being edited
	 * @param	string		HTML of the form field. This is what we add the content to.
	 * @return	string		Item string returned again, possibly with the original value added to.
	 * @see getSingleField(), registerDefaultLanguageData()
	 */
	function renderVDEFDiff($vArray, $vDEFkey) {
		if ($GLOBALS['TYPO3_CONF_VARS']['BE']['flexFormXMLincludeDiffBase'] && isset($vArray[$vDEFkey . '.vDEFbase']) && strcmp($vArray[$vDEFkey . '.vDEFbase'], $vArray['vDEF'])) {

				// Create diff-result:
			$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');
			$diffres = $t3lib_diff_Obj->makeDiffDisplay($vArray[$vDEFkey . '.vDEFbase'], $vArray['vDEF']);

			$item .= '<div class="typo3-TCEforms-diffBox">' .
					 '<div class="typo3-TCEforms-diffBox-header">' . htmlspecialchars($this->getLL('l_changeInOrig')) . ':</div>' .
					 $diffres .
					 '</div>';
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
	 * @param	string		Form element name
	 * @param	string		Mode "db", "file" (internal_type for the "group" type) OR blank (then for the "select" type)
	 * @param	string		Commalist of "allowed"
	 * @param	array		The array of items. For "select" and "group"/"file" this is just a set of value. For "db" its an array of arrays with table/uid pairs.
	 * @param	string		Alternative selector box.
	 * @param	array		An array of additional parameters, eg: "size", "info", "headers" (array with "selector" and "items"), "noBrowser", "thumbnails"
	 * @param	string		On focus attribute string
	 * @param	string		$table: (optional) Table name processing for
	 * @param	string		$field: (optional) Field of table name processing for
	 * @param	string		$uid:	(optional) uid of table record processing for
	 * @return	string		The form fields for the selection.
	 */
	function dbFileIcons($fName, $mode, $allowed, $itemArray, $selector = '', $params = array(), $onFocus = '', $table = '', $field = '', $uid = '') {


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
						$pRec = t3lib_BEfunc::getRecordWSOL($pp['table'], $pp['id']);
						if (is_array($pRec)) {
							$pTitle = t3lib_BEfunc::getRecordTitle($pp['table'], $pRec, FALSE, TRUE);
							$pUid = $pp['table'] . '_' . $pp['id'];
							$uidList[] = $pUid;
							$opt[] = '<option value="' . htmlspecialchars($pUid) . '">' . htmlspecialchars($pTitle) . '</option>';
						}
					}
				break;
				case 'file_reference':
				case 'file':
					foreach ($itemArray as $item) {
						$itemParts = explode('|', $item);
						$uidList[] = $pUid = $pTitle = $itemParts[0];
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($itemParts[0])) . '">' . htmlspecialchars(basename(rawurldecode($itemParts[0]))) . '</option>';
					}
				break;
				case 'folder':
					foreach ($itemArray as $pp) {
						$pParts = explode('|', $pp);
						$uidList[] = $pUid = $pTitle = $pParts[0];
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($pParts[0])) . '">' . htmlspecialchars(rawurldecode($pParts[0])) . '</option>';
					}
				break;
				default:
					foreach ($itemArray as $pp) {
						$pParts = explode('|', $pp, 2);
						$uidList[] = $pUid = $pParts[0];
						$pTitle = $pParts[1];
						$opt[] = '<option value="' . htmlspecialchars(rawurldecode($pUid)) . '">' . htmlspecialchars(rawurldecode($pTitle)) . '</option>';
					}
				break;
			}
		}

			// Create selector box of the options
		$sSize = $params['autoSizeMax'] ? t3lib_div::intInRange($itemArrayC + 1, t3lib_div::intInRange($params['size'], 1), $params['autoSizeMax']) : $params['size'];
		if (!$selector) {
			$selector = '<select id="' . uniqid('tceforms-multiselect-') . '" ' . ($params['noList'] ? 'style="display: none"' : 'size="' . $sSize . '"' . $this->insertDefStyle('group', 'tceforms-multiselect')) . ' multiple="multiple" name="' . $fName . '_list" ' . $onFocus . $params['style'] . $disabled . '>' . implode('', $opt) . '</select>';
		}


		$icons = array(
			'L' => array(),
			'R' => array(),
		);
		if (!$params['readOnly'] && !$params['noList']) {
			if (!$params['noBrowser']) {
					// check against inline uniqueness
				$inlineParent = $this->inline->getStructureLevel(-1);
				if (is_array($inlineParent) && $inlineParent['uid']) {
					if ($inlineParent['config']['foreign_table'] == $table && $inlineParent['config']['foreign_unique'] == $field) {
						$objectPrefix = $this->inline->inlineNames['object'] . '[' . $table . ']';
						$aOnClickInline = $objectPrefix . '|inline.checkUniqueElement|inline.setUniqueElement';
						$rOnClickInline = 'inline.revertUnique(\'' . $objectPrefix . '\',null,\'' . $uid . '\');';
					}
				}
				$aOnClick = 'setFormValueOpenBrowser(\'' . $mode . '\',\'' . ($fName . '|||' . $allowed . '|' . $aOnClickInline) . '\'); return false;';
				$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-insert-record', array('title' => htmlspecialchars($this->getLL('l_browse_' . ($mode == 'db' ? 'db' : 'file'))))) .
								'</a>';
			}
			if (!$params['dontShowMoveIcons']) {
				if ($sSize >= 5) {
					$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\'' . $fName . '\',\'Top\'); return false;">' .
									t3lib_iconWorks::getSpriteIcon('actions-move-to-top', array('title' => htmlspecialchars($this->getLL('l_move_to_top')))) .
									'</a>';
				}
				$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\'' . $fName . '\',\'Up\'); return false;">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-up', array('title' => htmlspecialchars($this->getLL('l_move_up')))) .
								'</a>';
				$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\'' . $fName . '\',\'Down\'); return false;">' .
								t3lib_iconWorks::getSpriteIcon('actions-move-down', array('title' => htmlspecialchars($this->getLL('l_move_down')))) .
								'</a>';
				if ($sSize >= 5) {
					$icons['L'][] = '<a href="#" onclick="setFormValueManipulate(\'' . $fName . '\',\'Bottom\'); return false;">' .
									t3lib_iconWorks::getSpriteIcon('actions-move-to-bottom', array('title' => htmlspecialchars($this->getLL('l_move_to_bottom')))) .
									'</a>';
				}
			}

			$clipElements = $this->getClipboardElements($allowed, $mode);
			if (count($clipElements)) {
				$aOnClick = '';
				foreach ($clipElements as $elValue) {
					if ($mode == 'db') {
						list($itemTable, $itemUid) = explode('|', $elValue);
						$itemTitle = $GLOBALS['LANG']->JScharCode(t3lib_BEfunc::getRecordTitle($itemTable, t3lib_BEfunc::getRecordWSOL($itemTable, $itemUid)));
						$elValue = $itemTable . '_' . $itemUid;
					} else {
							// 'file', 'file_reference' and 'folder' mode
						$itemTitle = 'unescape(\'' . rawurlencode(basename($elValue)) . '\')';
					}
					$aOnClick .= 'setFormValueFromBrowseWin(\'' . $fName . '\',unescape(\'' . rawurlencode(str_replace('%20', ' ', $elValue)) . '\'),' . $itemTitle . ');';
				}
				$aOnClick .= 'return false;';
				$icons['R'][] = '<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">' .
								t3lib_iconWorks::getSpriteIcon('actions-document-paste-into', array('title' => htmlspecialchars(sprintf($this->getLL('l_clipInsert_' . ($mode == 'db' ? 'db' : 'file')), count($clipElements))))) .
								'</a>';
			}
			$rOnClick = $rOnClickInline . 'setFormValueManipulate(\'' . $fName . '\',\'Remove\'); return false';
			$icons['L'][] = '<a href="#" onclick="' . htmlspecialchars($rOnClick) . '">' .
							t3lib_iconWorks::getSpriteIcon('actions-selection-delete', array('title' => htmlspecialchars($this->getLL('l_remove_selected')))) .
							'</a>';
		}
		$imagesOnly = FALSE;
		if ($params['thumbnails'] && $params['info']) {
				// In case we have thumbnails, check if only images are allowed.
				// In this case, render them below the field, instead of to the right
			$allowedExtensionList = t3lib_div::trimExplode(' ', strtolower($params['info']), TRUE);
			$imageExtensionList = t3lib_div::trimExplode(',', strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']), TRUE);
			$imagesOnly = TRUE;
			foreach ($allowedExtensionList as $allowedExtension) {
				if (!t3lib_div::inArray($imageExtensionList, $allowedExtension)) {
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
		if (is_array ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'])) {
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['dbFileIcons'] as $classRef) {
				$hookObject = t3lib_div::getUserObj($classRef);

				if (!($hookObject instanceof t3lib_TCEforms_dbFileIconsHook)) {
					throw new UnexpectedValueException(
						'$hookObject must implement interface t3lib_TCEforms_dbFileIconsHook',
						1290167704
					);
				}

				$additionalParams = array(
					'mode' => $mode,
					'allowed' => $allowed,
					'itemArray' => $itemArray,
					'onFocus' => $onFocus,
					'table' => $table,
					'field' => $field,
					'uid' => $uid
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
				</tr>' : '') .
			   '
			<tr>
				<td valign="top">' .
			   $selector .
			   $thumbnails .
			   ($params['noList'] ? '' : '<span class="filetypes">' . $this->wrapLabels($params['info'])) .
			   '</span></td>
				<td valign="top" class="icons">' .
			   implode('<br />', $icons['L']) . '</td>
				<td valign="top" class="icons">' .
			   implode('<br />', $icons['R']) . '</td>
				<td valign="top" class="thumbnails">' .
			   $rightbox .
			   '</td>
			</tr>
		</table>';

			// Creating the hidden field which contains the actual value as a comma list.
		$str .= '<input type="hidden" name="' . $fName . '" value="' . htmlspecialchars(implode(',', $uidList)) . '" />';

		return $str;
	}

	/**
	 * Returns array of elements from clipboard to insert into GROUP element box.
	 *
	 * @param	string		Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png"
	 * @param	string		Mode of relations: "db" or "file"
	 * @return	array		Array of elements in values (keys are insignificant), if none found, empty array.
	 */
	function getClipboardElements($allowed, $mode) {

		$output = array();

		if (is_object($this->clipObj)) {
			switch ($mode) {
				case 'file_reference':
				case 'file':
					$elFromTable = $this->clipObj->elFromTable('_FILE');
					$allowedExts = t3lib_div::trimExplode(',', $allowed, 1);

					if ($allowedExts) { // If there are a set of allowed extensions, filter the content:
						foreach ($elFromTable as $elValue) {
							$pI = pathinfo($elValue);
							$ext = strtolower($pI['extension']);
							if (in_array($ext, $allowedExts)) {
								$output[] = $elValue;
							}
						}
					} else { // If all is allowed, insert all: (This does NOT respect any disallowed extensions, but those will be filtered away by the backend TCEmain)
						$output = $elFromTable;
					}
				break;
				case 'db':
					$allowedTables = t3lib_div::trimExplode(',', $allowed, 1);
					if (!strcmp(trim($allowedTables[0]), '*')) { // All tables allowed for relation:
						$output = $this->clipObj->elFromTable('');
					} else { // Only some tables, filter them:
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
	 * @param	string		The icon HTML to wrap
	 * @param	string		Table name (eg. "pages" or "tt_content") OR the absolute path to the file
	 * @param	integer		The uid of the record OR if file, just blank value.
	 * @return	string		HTML
	 */
	function getClickMenu($str, $table, $uid = '') {
		if ($this->enableClickMenu) {
			$onClick = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($str, $table, $uid, 1, '', '+copy,info,edit,view', TRUE);
			return '<a href="#" onclick="' . htmlspecialchars($onClick) . '">' . $str . '</a>';
		}
	}

	/**
	 * Rendering wizards for form fields.
	 *
	 * @param	array		Array with the real item in the first value, and an alternative item in the second value.
	 * @param	array		The "wizard" key from the config array for the field (from TCA)
	 * @param	string		Table name
	 * @param	array		The record array
	 * @param	string		The field name
	 * @param	array		Additional configuration array. (passed by reference!)
	 * @param	string		The field name
	 * @param	array		Special configuration if available.
	 * @param	boolean		Whether the RTE could have been loaded.
	 * @return	string		The new item value.
	 */
	function renderWizards($itemKinds, $wizConf, $table, $row, $field, &$PA, $itemName, $specConf, $RTE = 0) {

			// Init:
		$fieldChangeFunc = $PA['fieldChangeFunc'];
		$item = $itemKinds[0];
		$outArr = array();
		$colorBoxLinks = array();
		$fName = '[' . $table . '][' . $row['uid'] . '][' . $field . ']';
		$md5ID = 'ID' . t3lib_div::shortmd5($itemName);
		$listFlag = '_list';

		$prefixOfFormElName = 'data[' . $table . '][' . $row['uid'] . '][' . $field . ']';
		if (t3lib_div::isFirstPartOfStr($PA['itemFormElName'], $prefixOfFormElName)) {
			$flexFormPath = str_replace('][', '/', substr($PA['itemFormElName'], strlen($prefixOfFormElName) + 1, -1));
		}

			// Manipulate the field name (to be the true form field name) and remove a suffix-value if the item is a selector box with renderMode "singlebox":
		if ($PA['fieldConf']['config']['form_type'] == 'select') {
			if ($PA['fieldConf']['config']['maxitems'] <= 1) { // Single select situation:
				$listFlag = '';
			} elseif ($PA['fieldConf']['config']['renderMode'] == 'singlebox') {
				$itemName .= '[]';
				$listFlag = '';
			}
		}

			// traverse wizards:
		if (is_array($wizConf) && !$this->disableWizards) {
			$parametersOfWizards =& $specConf['wizards']['parameters'];

			foreach ($wizConf as $wid => $wConf) {
				if (substr($wid, 0, 1) != '_'
					&& (!$wConf['enableByTypeConfig'] || is_array($parametersOfWizards) && in_array($wid, $parametersOfWizards))
					&& ($RTE || !$wConf['RTEonly'])
				) {

						// Title / icon:
					$iTitle = htmlspecialchars($this->sL($wConf['title']));
					if ($wConf['icon']) {
						$icon = $this->getIconHtml($wConf['icon'], $iTitle, $iTitle);
					} else {
						$icon = $iTitle;
					}

						//
					switch ((string) $wConf['type']) {
						case 'userFunc':
						case 'script':
						case 'popup':
						case 'colorbox':
							if (!$wConf['notNewRecords'] || t3lib_div::testInt($row['uid'])) {

									// Setting &P array contents:
								$params = array();
								$params['params'] = $wConf['params'];
								$params['exampleImg'] = $wConf['exampleImg'];
								$params['table'] = $table;
								$params['uid'] = $row['uid'];
								$params['pid'] = $row['pid'];
								$params['field'] = $field;
								$params['flexFormPath'] = $flexFormPath;
								$params['md5ID'] = $md5ID;
								$params['returnUrl'] = $this->thisReturnUrl();

									// Resolving script filename and setting URL.
								if (!strcmp(substr($wConf['script'], 0, 4), 'EXT:')) {
									$wScript = t3lib_div::getFileAbsFileName($wConf['script']);
									if ($wScript) {
										$wScript = '../' . substr($wScript, strlen(PATH_site));
									} else {
										break;
									}
								} else {
									$wScript = $wConf['script'];
								}
								$url = $this->backPath . $wScript . (strstr($wScript, '?') ? '' : '?');

									// If there is no script and the type is "colorbox", break right away:
								if ((string) $wConf['type'] == 'colorbox' && !$wConf['script']) {
									break;
								}

									// If "script" type, create the links around the icon:
								if ((string) $wConf['type'] == 'script') {
									$aUrl = $url . t3lib_div::implodeArrayForUrl('', array('P' => $params));
									$outArr[] = '<a href="' . htmlspecialchars($aUrl) . '" onclick="' . $this->blur() . 'return !TBE_EDITOR.isFormChanged();">' .
												$icon .
												'</a>';
								} else {

										// ... else types "popup", "colorbox" and "userFunc" will need additional parameters:
									$params['formName'] = $this->formName;
									$params['itemName'] = $itemName;
									$params['fieldChangeFunc'] = $fieldChangeFunc;
									$params['fieldChangeFuncHash'] = t3lib_div::hmac(serialize($fieldChangeFunc));

									switch ((string) $wConf['type']) {
										case 'popup':
										case 'colorbox':
												// Current form value is passed as P[currentValue]!
											$addJS = $wConf['popup_onlyOpenIfSelected'] ? 'if (!TBE_EDITOR.curSelected(\'' . $itemName . $listFlag . '\')){alert(' . $GLOBALS['LANG']->JScharCode($this->getLL('m_noSelItemForEdit')) . '); return false;}' : '';
											$curSelectedValues = '+\'&P[currentSelectedValues]=\'+TBE_EDITOR.curSelected(\'' . $itemName . $listFlag . '\')';
											$aOnClick = $this->blur() .
														$addJS .
														'vHWin=window.open(\'' . $url . t3lib_div::implodeArrayForUrl('', array('P' => $params)) . '\'+\'&P[currentValue]=\'+TBE_EDITOR.rawurlencode(' . $this->elName($itemName) . '.value,200)' . $curSelectedValues . ',\'popUp' . $md5ID . '\',\'' . $wConf['JSopenParams'] . '\');' .
														'vHWin.focus();return false;';
												// Setting "colorBoxLinks" - user LATER to wrap around the color box as well:
											$colorBoxLinks = Array('<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">', '</a>');
											if ((string) $wConf['type'] == 'popup') {
												$outArr[] = $colorBoxLinks[0] . $icon . $colorBoxLinks[1];
											}
										break;
										case 'userFunc':
											$params['item'] = &$item; // Reference set!
											$params['icon'] = $icon;
											$params['iTitle'] = $iTitle;
											$params['wConf'] = $wConf;
											$params['row'] = $row;
											$outArr[] = t3lib_div::callUserFunction($wConf['userFunc'], $params, $this);
										break;
									}
								}

									// Hide the real form element?
								if (is_array($wConf['hideParent']) || $wConf['hideParent']) {
									$item = $itemKinds[1]; // Setting the item to a hidden-field.
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
							if (isset($PA['fieldTSConfig']['suggest.']['default.']['hide']) &&
								((bool) $PA['fieldTSConfig']['suggest.']['default.']['hide'] == TRUE)) {
								break;
							}
							$outArr[] = $this->suggest->renderSuggestSelector($PA['itemFormElName'], $table, $field, $row, $PA);
						break;
					}

						// Color wizard colorbox:
					if ((string) $wConf['type'] == 'colorbox') {
						$dim = t3lib_div::intExplode('x', $wConf['dim']);
						$dX = t3lib_div::intInRange($dim[0], 1, 200, 20);
						$dY = t3lib_div::intInRange($dim[1], 1, 200, 20);
						$color = $PA['itemFormElValue'] ? ' bgcolor="' . htmlspecialchars($PA['itemFormElValue']) . '"' : '';
						$outArr[] = '<table border="0" cellpadding="0" cellspacing="0" id="' . $md5ID . '"' . $color . ' style="' . htmlspecialchars($wConf['tableStyle']) . '">
									<tr>
										<td>' .
									$colorBoxLinks[0] . '<img ' .
									t3lib_iconWorks::skinImg($this->backPath,
															 (strlen(trim($color)) == 0 || strcmp(trim($color), '0') == 0) ? 'gfx/colorpicker_empty.png' : 'gfx/colorpicker.png',
															 'width="' . $dX . '" height="' . $dY . '"' . t3lib_BEfunc::titleAltAttrib(trim($iTitle . ' ' . $PA['itemFormElValue'])) . ' border="0"') .
									'>' . $colorBoxLinks[1] .
									'</td>
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
					$dist = intval($wizConf['_DISTANCE']);
					if ($wizConf['_VERTICAL']) {
						$dist = $dist ? '<tr><td><img src="clear.gif" width="1" height="' . $dist . '" alt="" /></td></tr>' : '';
						$outStr = '<tr><td>' . implode('</td></tr>' . $dist . '<tr><td>', $outArr) . '</td></tr>';
					} else {
						$dist = $dist ? '<td><img src="clear.gif" height="1" width="' . $dist . '" alt="" /></td>' : '';
						$outStr = '<tr><td' . $vAlign . '>' . implode('</td>' . $dist . '<td' . $vAlign . '>', $outArr) . '</td></tr>';
					}
					$outStr = '<table border="0" cellpadding="' . intval($wizConf['_PADDING']) . '" cellspacing="' . intval($wizConf['_PADDING']) . '">' . $outStr . '</table>';
				} else {
					$outStr = implode('', $outArr);
				}

				if (!strcmp($wizConf['_POSITION'], 'left')) {
					$outStr = '<tr><td' . $vAlign . '>' . $outStr . '</td><td' . $vAlign . '>' . $item . '</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'], 'top')) {
					$outStr = '<tr><td>' . $outStr . '</td></tr><tr><td>' . $item . '</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'], 'bottom')) {
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
	 * @param	string		Icon reference
	 * @return	array		Array with two values; the icon file reference (relative to PATH_typo3 minus backPath), the icon file information array (getimagesize())
	 */
	function getIcon($icon) {
		if (substr($icon, 0, 4) == 'EXT:') {
			$file = t3lib_div::getFileAbsFileName($icon);
			if ($file) {
				$file = substr($file, strlen(PATH_site));
				$selIconFile = $this->backPath . '../' . $file;
				$selIconInfo = @getimagesize(PATH_site . $file);
			}
		} elseif (substr($icon, 0, 3) == '../') {
			$selIconFile = $this->backPath . t3lib_div::resolveBackPath($icon);
			$selIconInfo = @getimagesize(PATH_site . t3lib_div::resolveBackPath(substr($icon, 3)));
		} elseif (substr($icon, 0, 4) == 'ext/' || substr($icon, 0, 7) == 'sysext/') {
			$selIconFile = $this->backPath . $icon;
			$selIconInfo = @getimagesize(PATH_typo3 . $icon);
		} else {
			$selIconFile = t3lib_iconWorks::skinImg($this->backPath, 'gfx/' . $icon, '', 1);
			$iconPath = substr($selIconFile, strlen($this->backPath));
			$selIconInfo = @getimagesize(PATH_typo3 . $iconPath);
		}
		return array($selIconFile, $selIconInfo);
	}

 	/**
	 * Renders the $icon, supports a filename for skinImg or sprite-icon-name
	 * @param $icon the icon passed, could be a file-reference or a sprite Icon name
	 * @param string $alt alt attribute of the icon returned
	 * @param string $title title attribute of the icon return
	 * @return an tag representing to show the asked icon
	 */
	protected function getIconHtml($icon, $alt = '', $title = '') {
		$iconArray = $this->getIcon($icon);
		if (is_file(t3lib_div::resolveBackPath(PATH_typo3 . PATH_typo3_mod . $iconArray[0]))) {
			return '<img src="' . $iconArray[0] . '" alt="' . $alt . '" ' . ($title ? 'title="' . $title . '"' : '') . ' />';
		} else {
			return t3lib_iconWorks::getSpriteIcon($icon, array('alt'=> $alt, 'title'=> $title));
		}
	}

	/**
	 * Creates style attribute content for option tags in a selector box, primarily setting it up to show the icon of an element as background image (works in mozilla)
	 *
	 * @param	string		Icon string for option item
	 * @return	string		Style attribute content, if any
	 */
	function optionTagStyle($iconString) {
		if ($iconString) {
			list($selIconFile, $selIconInfo) = $this->getIcon($iconString);

			$padLeft = $selIconInfo[0] + 4;

			if ($padLeft >= 18 && $padLeft <= 24) {
				$padLeft = 22; // In order to get the same padding for all option tags even if icon sizes differ a little, set it to 22 if it was between 18 and 24 pixels
			}

			$padTop = t3lib_div::intInRange(($selIconInfo[1] - 12) / 2, 0);
			$styleAttr = 'background: #fff url(' . $selIconFile . ') 0% 50% no-repeat; height: ' . t3lib_div::intInRange(($selIconInfo[1] + 2) - $padTop, 0) . 'px; padding-top: ' . $padTop . 'px; padding-left: ' . $padLeft . 'px;';
			return $styleAttr;
		}
	}

	/**
	 * Creates style attribute content for optgroup tags in a selector box, primarily setting it up to show the icon of an element as background image (works in mozilla).
	 *
	 * @param	string		Icon string for option item
	 * @return	string		Style attribute content, if any
	 */
	function optgroupTagStyle($iconString) {
		if ($iconString) {
			list($selIconFile, $selIconInfo) = $this->getIcon($iconString);

			$padLeft = $selIconInfo[0] + 4;

			if($padLeft >= 18 && $padLeft <= 24) {
					// In order to get the same padding for all option tags even if icon sizes differ a little,
					// set it to 22, if it was between 18 and 24 pixels.
				$padLeft = 22;
			}
			$padTop = t3lib_div::intInRange(($selIconInfo[1] - 12) / 2, 0);

			return 'background: #ffffff url(' . $selIconFile . ') 0 0 no-repeat; padding-top: ' . $padTop . 'px; padding-left: ' . $padLeft . 'px;';
		}
	}

	/**
	 * Extracting values from a value/label list (as made by transferData class)
	 *
	 * @param	string		Value string where values are comma separated, intermixed with labels and rawurlencoded (this is what is delivered to TCEforms normally!)
	 * @param	array		Values in an array
	 * @return	array		Input string exploded with comma and for each value only the label part is set in the array. Keys are numeric
	 */
	function extractValuesOnlyFromValueLabelList($itemFormElValue) {
			// Get values of selected items:
		$itemArray = t3lib_div::trimExplode(',', $itemFormElValue, 1);
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
	 * @param	string		The string to wrap in an A-tag
	 * @param	string		The table name for which to open the palette.
	 * @param	array		The palette pointer.
	 * @param	integer		The record array
	 */
	function wrapOpenPalette($header, $table, $row, $palette, $retFunc) {
		$id = 'TCEFORMS_' . $table . '_' . $palette . '_' . $row['uid'];
		$res = '<a href="#" onclick="TBE_EDITOR.toggle_display_states(\'' . $id . '\',\'block\',\'none\'); return false;" >' . $header . '</a>';
		return array($res, '');
	}

	/**
	 * Add the id and the style property to the field palette
	 *
	 * @param	string		Palette Code
	 * @param	string		The table name for which to open the palette.
	 * @param	string		Palette ID
	 * @param	string		The record array
	 * @return	boolean		is collapsed
	 */
	function wrapPaletteField($code, $table, $row, $palette, $collapsed) {
		$display = $collapsed ? 'none' : 'block';
		$id = 'TCEFORMS_' . $table . '_' . $palette . '_' . $row['uid'];
		$code = '<div id="' . $id . '" style="display:' . $display . ';" >' . $code . '</div>';
		return $code;
	}

	/**
	 * Creates checkbox parameters
	 *
	 * @param	string		Form element name
	 * @param	integer		The value of the checkbox (representing checkboxes with the bits)
	 * @param	integer		Checkbox # (0-9?)
	 * @param	integer		Total number of checkboxes in the array.
	 * @param	string		Additional JavaScript for the onclick handler.
	 * @return	string		The onclick attribute + possibly the checked-option set.
	 */
	function checkBoxParams($itemName, $thisValue, $c, $iCount, $addFunc = '') {
		$onClick = $this->elName($itemName) . '.value=this.checked?(' . $this->elName($itemName) . '.value|' . pow(2, $c) . '):(' . $this->elName($itemName) . '.value&' . (pow(2, $iCount) - 1 - pow(2, $c)) . ');' .
				   $addFunc;
		$str = ' onclick="' . htmlspecialchars($onClick) . '"' .
			   (($thisValue & pow(2, $c)) ? ' checked="checked"' : '');
		return $str;
	}

	/**
	 * Returns element reference for form element name
	 *
	 * @param	string		Form element name
	 * @return	string		Form element reference (JS)
	 */
	function elName($itemName) {
		return 'document.' . $this->formName . "['" . $itemName . "']";
	}

	/**
	 * Returns the "No title" string if the input $str is empty.
	 *
	 * DEPRECATED: Use t3lib_BEfunc::getRecordTitle with the $forceResult flag set.
	 *
	 * @param	string		The string which - if empty - will become the no-title string.
	 * @param	array		Array with wrappin parts for the no-title output (in keys [0]/[1])
	 * @return	string
	 * @deprecated since TYPO3 4.1, this function will be removed in TYPO3 4.6.
	 */
	function noTitle($str, $wrapParts = array()) {
		t3lib_div::logDeprecatedFunction();

		return strcmp($str, '') ? $str : $wrapParts[0] . '[' . $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title') . ']' . $wrapParts[1];
	}

	/**
	 * Returns 'this.blur();' string, if supported.
	 *
	 * @return	string		If the current browser supports styles, the string 'this.blur();' is returned.
	 */
	function blur() {
		return $GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();' : '';
	}

	/**
	 * Returns the "returnUrl" of the form. Can be set externally or will be taken from "t3lib_div::linkThisScript()"
	 *
	 * @return	string		Return URL of current script
	 */
	function thisReturnUrl() {
		return $this->returnUrl ? $this->returnUrl : t3lib_div::linkThisScript();
	}

	/**
	 * Returns the form field for a single HIDDEN field.
	 * (Not used anywhere...?)
	 *
	 * @param	string		Table name
	 * @param	string		Field name
	 * @param	array		The row
	 * @return	string		The hidden-field <input> tag.
	 */
	function getSingleHiddenField($table, $field, $row) {
		global $TCA;
		$out = '';
		t3lib_div::loadTCA($table);
		if ($TCA[$table]['columns'][$field]) {

			$uid = $row['uid'];
			$itemName = $this->prependFormFieldNames . '[' . $table . '][' . $uid . '][' . $field . ']';
			$itemValue = $row[$field];
			$item .= '<input type="hidden" name="' . $itemName . '" value="' . htmlspecialchars($itemValue) . '" />';
			$out = $item;
		}
		return $out;
	}

	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param	integer		The abstract size value (1-48)
	 * @param	boolean		If this is for a text area.
	 * @return	string		Either a "style" attribute string or "cols"/"size" attribute string.
	 */
	function formWidth($size = 48, $textarea = 0) {
		$widthAndStyleAttributes = '';
		$fieldWidthAndStyle = $this->formWidthAsArray($size, $textarea);

		if (!$GLOBALS['CLIENT']['FORMSTYLE']) {
				// If not setting the width by style-attribute
			$widthAndStyleAttributes = ' ' . $fieldWidthAndStyle['width'];
		} else {
				// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$widthAndStyleAttributes = ' style="' . htmlspecialchars($fieldWidthAndStyle['style']) . '"';

			if ($fieldWidthAndStyle['class']) {
				$widthAndStyleAttributes .= ' class="' . htmlspecialchars($fieldWidthAndStyle['class']) . '"';
			}
		}

		return $widthAndStyleAttributes;
	}

	/**
	 * Returns parameters to set the width for a <input>/<textarea>-element
	 *
	 * @param	integer		The abstract size value (1-48)
	 * @param	boolean		If set, calculates sizes for a text area.
	 * @return	array		An array containing style, class, and width attributes.
	 */
	protected function formWidthAsArray($size = 48, $textarea = FALSE) {
		$fieldWidthAndStyle = array('style' => '', 'class' => '', 'width' => '');

		if ($this->docLarge) {
			$size = round($size * $this->form_largeComp);
		}

		$widthAttribute = $textarea ? 'cols' : 'size';
		if (!$GLOBALS['CLIENT']['FORMSTYLE']) {
				// If not setting the width by style-attribute
			$fieldWidthAndStyle['width'] = $widthAttribute . '="' . $size . '"';
		} else {
				// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$widthInPixels = ceil($size * $this->form_rowsToStylewidth);
			$fieldWidthAndStyle['style'] = 'width: ' . $widthInPixels . 'px; '
										   . $this->defStyle
										   . $this->formElStyle($textarea ? 'text' : 'input');

			$fieldWidthAndStyle['class'] = $this->formElClass($textarea ? 'text' : 'input');
		}

		return $fieldWidthAndStyle;
	}

	/**
	 * Returns parameters to set with for a textarea field
	 *
	 * @param	integer		The abstract width (1-48)
	 * @param	string		Empty or "off" (text wrapping in the field or not)
	 * @return	string		The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 */
	function formWidthText($size = 48, $wrap = '') {
		$wTags = $this->formWidth($size, 1);
			// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap)) != 'off' && $GLOBALS['CLIENT']['BROWSER'] == 'net' && $GLOBALS['CLIENT']['VERSION'] >= 5) {
			$wTags .= ' cols="' . $size . '"';
		}
		return $wTags;
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	function formElStyle($type) {
		return $this->formElStyleClassValue($type);
	}

	/**
	 * Get class attribute value for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	function formElClass($type) {
		return $this->formElStyleClassValue($type, TRUE);
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @param	boolean		If set, will return value only if prefixed with CLASS, otherwise must not be prefixed "CLASS"
	 * @return	string		CSS attributes
	 */
	function formElStyleClassValue($type, $class = FALSE) {
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
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @param	string		Additional class(es) to be added
	 * @return	string		CSS attributes
	 */
	function insertDefStyle($type, $additionalClass = '') {
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
	 * @param	array		Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param	string		ID string for the tab menu
	 * @param	integer		If set to '1' empty tabs will be removed, If set to '2' empty tabs will be disabled
	 * @return	string		HTML for the menu
	 */
	function getDynTabMenu($parts, $idString, $dividersToTabsBehaviour = 1) {
		if (is_object($GLOBALS['TBE_TEMPLATE'])) {
			$GLOBALS['TBE_TEMPLATE']->backPath = $this->backPath;
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString, 0, FALSE, 0, 1, FALSE, 1, $dividersToTabsBehaviour);
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
	 * @param	array		The "columns" array for the field (from TCA)
	 * @return	array		An array of arrays with three elements; label, value, icon
	 */
	function initItemArray($fieldValue) {
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
	 * @param	array		The existing item array
	 * @param	array		An array of items to add. NOTICE: The keys are mapped to values, and the values and mapped to be labels. No possibility of adding an icon.
	 * @return	array		The updated $item array
	 */
	function addItems($items, $iArray) {
		global $TCA;
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
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "itemsProcFunc." from fieldTSconfig of the field.
	 * @param	array		The config array for the field.
	 * @param	string		Table name
	 * @param	array		Record row
	 * @param	string		Field name
	 * @return	array		The modified $items array
	 */
	function procItems($items, $iArray, $config, $table, $row, $field) {
		global $TCA;

		$params = array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;

		t3lib_div::callUserFunction($config['itemsProcFunc'], $params, $this);
		return $items;
	}

	/**
	 * Add selector box items of more exotic kinds.
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The "columns" array for the field (from TCA)
	 * @param	array		TSconfig for the table/row
	 * @param	string		The fieldname
	 * @return	array		The $items array modified.
	 */
	function addSelectOptionsToItemArray($items, $fieldValue, $TSconfig, $field) {
		global $TCA;

			// Values from foreign tables:
		if ($fieldValue['config']['foreign_table']) {
			$items = $this->foreignTable($items, $fieldValue, $TSconfig, $field);
			if ($fieldValue['config']['neg_foreign_table']) {
				$items = $this->foreignTable($items, $fieldValue, $TSconfig, $field, 1);
			}
		}

			// Values from a file folder:
		if ($fieldValue['config']['fileFolder']) {
			$fileFolder = t3lib_div::getFileAbsFileName($fieldValue['config']['fileFolder']);
			if (@is_dir($fileFolder)) {

					// Configurations:
				$extList = $fieldValue['config']['fileFolder_extList'];
				$recursivityLevels = isset($fieldValue['config']['fileFolder_recursions']) ? t3lib_div::intInRange($fieldValue['config']['fileFolder_recursions'], 0, 99) : 99;

					// Get files:
				$fileFolder = rtrim($fileFolder, '/') . '/';
				$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(), $fileFolder, $extList, 0, $recursivityLevels);
				$fileArr = t3lib_div::removePrefixPathFromList($fileArr, $fileFolder);

				foreach ($fileArr as $fileRef) {
					$fI = pathinfo($fileRef);
					$icon = t3lib_div::inList('gif,png,jpeg,jpg', strtolower($fI['extension'])) ? '../' . substr($fileFolder, strlen(PATH_site)) . $fileRef : '';
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
					$temp_tc = array_keys($TCA);
					$descr = '';

					foreach ($temp_tc as $theTableNames) {
						if (!$TCA[$theTableNames]['ctrl']['adminOnly']) {

								// Icon:
							$icon = t3lib_iconWorks::mapRecordTypeToSpriteIconName($theTableNames, array());

								// Add help text
							$helpText = array();
							$GLOBALS['LANG']->loadSingleTableDescription($theTableNames);
							$helpTextArray = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
							if (!empty($helpTextArray['description'])) {
								$helpText['description'] = $helpTextArray['description'];
							}

								// Item configuration:
							$items[] = array(
								$this->sL($TCA[$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$helpText
							);
						}
					}
				break;
				case 'pagetypes':
					$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];

					foreach ($theTypes as $theTypeArrays) {
							// Icon:
						$icon = t3lib_iconWorks::mapRecordTypeToSpriteIconName('pages', array('doktype' => $theTypeArrays[1]));

							// Item configuration:
						$items[] = array(
							$this->sL($theTypeArrays[0]),
							$theTypeArrays[1],
							$icon
						);
					}
				break;
				case 'exclude':
					$theTypes = t3lib_BEfunc::getExcludeFields();
					$descr = '';

					foreach ($theTypes as $theTypeArrays) {
						list($theTable, $theField) = explode(':', $theTypeArrays[1]);

							// Add help text
						$helpText = array();
						$GLOBALS['LANG']->loadSingleTableDescription($theTable);
						$helpTextArray = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theField];
						if (!empty($helpTextArray['description'])) {
							$helpText['description'] = $helpTextArray['description'];
						}

							// Item configuration:
						$items[] = array(
							rtrim($theTypeArrays[0], ':'),
							$theTypeArrays[1],
							'empty-empty',
							$helpText
						);
					}
				break;
				case 'explicitValues':
					$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

						// Icons:
					$icons = array(
						'ALLOW' => 'status-status-permission-granted',
						'DENY' => 'status-status-permission-denied',
					);

						// Traverse types:
					foreach ($theTypes as $tableFieldKey => $theTypeArrays) {

						if (is_array($theTypeArrays['items'])) {
								// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--',
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
					$items = array_merge($items, t3lib_BEfunc::getSystemLanguages());
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
									'--div--',
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
										$helpText,
									);
								}
							}
						}
					}
				break;
				case 'modListGroup':
				case 'modListUser':
					$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
					$loadModules->load($GLOBALS['TBE_MODULES']);

					$modList = $fieldValue['config']['special'] == 'modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList)) {
						$descr = '';

						foreach ($modList as $theMod) {

								// Icon:
							$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$theMod . '_tab'];
							if ($icon) {
								$icon = '../' . substr($icon, strlen(PATH_site));
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
	 * @param	string		The module key
	 * @return	string		The rawurlencoded 2-part string to transfer to interface
	 * @access private
	 * @see addSelectOptionsToItemArray()
	 */
	function addSelectOptionsToItemArray_makeModuleData($value) {
		$label = '';
			// Add label for main module:
		$pp = explode('_', $value);
		if (count($pp) > 1) {
			$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$pp[0] . '_tab'] . '>';
		}
			// Add modules own label now:
		$label .= $GLOBALS['LANG']->moduleLabels['tabs'][$value . '_tab'];

		return $label;
	}

	/**
	 * Adds records from a foreign table (for selector boxes)
	 *
	 * @param	array		The array of items (label,value,icon)
	 * @param	array		The 'columns' array for the field (from TCA)
	 * @param	array		TSconfig for the table/row
	 * @param	string		The fieldname
	 * @param	boolean		If set, then we are fetching the 'neg_' foreign tables.
	 * @return	array		The $items array modified.
	 * @see addSelectOptionsToItemArray(), t3lib_BEfunc::exec_foreign_table_where_query()
	 */
	function foreignTable($items, $fieldValue, $TSconfig, $field, $pFFlag = 0) {
		global $TCA;

			// Init:
		$pF = $pFFlag ? 'neg_' : '';
		$f_table = $fieldValue['config'][$pF . 'foreign_table'];
		$uidPre = $pFFlag ? '-' : '';

			// Get query:
		$res = t3lib_BEfunc::exec_foreign_table_where_query($fieldValue, $field, $TSconfig, $pF);

			// Perform lookup
		if ($GLOBALS['TYPO3_DB']->sql_error()) {
			echo($GLOBALS['TYPO3_DB']->sql_error() . "\n\nThis may indicate a table defined in tables.php is not existing in the database!");
			return array();
		}

			// Get label prefix.
		$lPrefix = $this->sL($fieldValue['config'][$pF . 'foreign_table_prefix']);

			// Get icon field + path if any:
		$iField = $TCA[$f_table]['ctrl']['selicon_field'];
		$iPath = trim($TCA[$f_table]['ctrl']['selicon_field_path']);

			// Traverse the selected rows to add them:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			t3lib_BEfunc::workspaceOL($f_table, $row);

			if (is_array($row)) {
					// Prepare the icon if available:
				if ($iField && $iPath && $row[$iField]) {
					$iParts = t3lib_div::trimExplode(',', $row[$iField], 1);
					$icon = '../' . $iPath . '/' . trim($iParts[0]);
				} elseif (t3lib_div::inList('singlebox,checkbox', $fieldValue['config']['renderMode'])) {
					$icon = t3lib_iconWorks::mapRecordTypeToSpriteIconName($f_table, $row);
				} else {
					$icon = 'empty-empty';
				}

					// Add the item:
				$items[] = array(
					$lPrefix . htmlspecialchars(t3lib_BEfunc::getRecordTitle($f_table, $row)),
					$uidPre . $row['uid'],
					$icon
				);
			}
		}
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
	 * @return	void
	 */
	function setNewBEDesign() {
		$template = t3lib_div::getURL(PATH_typo3 . $this->templateFile);

			// Wrapping all table rows for a particular record being edited:
		$this->totalWrap = t3lib_parsehtml::getSubpart($template, '###TOTALWRAP###');

			// Wrapping a single field:
		$this->fieldTemplate = t3lib_parsehtml::getSubpart($template, '###FIELDTEMPLATE###');

		$this->palFieldTemplate = t3lib_parsehtml::getSubpart($template, '###PALETTE_FIELDTEMPLATE###');
		$this->palFieldTemplateHeader = t3lib_parsehtml::getSubpart($template, '###PALETTE_FIELDTEMPLATE_HEADER###');

		$this->sectionWrap = t3lib_parsehtml::getSubpart($template, '###SECTION_WRAP###');
	}

	/**
	 * This inserts the content of $inArr into the field-template
	 *
	 * @param	array		Array with key/value pairs to insert in the template.
	 * @param	string		Alternative template to use instead of the default.
	 * @return	string
	 */
	function intoTemplate($inArr, $altTemplate = '') {
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
	 * @param	array		Array with key/value pairs to insert in the template.
	 * @param	string		The table name of the record
	 * @param	string		The field name which this element is supposed to edit
	 * @param	array		The record data array where the value(s) for the field can be found
	 * @param	array		An array with additional configuration options.
	 * @return	array		marker array for template output
	 * @see function intoTemplate()
	 */
	function addUserTemplateMarkers($marker, $table, $field, $row, &$PA) {
		return $marker;
	}

	/**
	 * Wrapping labels
	 * Currently not implemented - just returns input value.
	 *
	 * @param	string		Input string.
	 * @return	string		Output string.
	 */
	function wrapLabels($str) {
		return $str;
	}

	/**
	 * Wraps all the table rows into a single table.
	 * Used externally from scripts like alt_doc.php and db_layout.php (which uses TCEforms...)
	 *
	 * @param	string		Code to output between table-parts; table rows
	 * @param	array		The record
	 * @param	string		The table name
	 * @return	string
	 */
	function wrapTotal($c, $rec, $table) {
		$parts = $this->replaceTableWrap(explode('|', $this->totalWrap, 2), $rec, $table);
		return $parts[0] . $c . $parts[1] . implode('', $this->hiddenFieldAccum);
	}

	/**
	 * Generates a token and returns an input field with it
	 *
	 * @param string $formName Context of the token
	 * @param string $tokenName The name of the token GET/POST variable
	 * @return string a complete input field
	 */
	public static function getHiddenTokenField($formName = 'securityToken', $tokenName = 'formToken') {
		$formprotection = t3lib_formprotection_Factory::get();
		return '<input type="hidden" name="' .$tokenName . '" value="' . $formprotection->generateToken($formName) . '" />';
	}

	/**
	 * This replaces markers in the total wrap
	 *
	 * @param	array		An array of template parts containing some markers.
	 * @param	array		The record
	 * @param	string		The table name
	 * @return	string
	 */
	function replaceTableWrap($arr, $rec, $table) {
		global $TCA;

			// Make "new"-label
		if (strstr($rec['uid'], 'NEW')) {
			$newLabel = ' <span class="typo3-TCEforms-newToken">' .
						$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new', 1) .
						'</span>';

				// Kasper: Should not be used here because NEW records are not offline workspace versions...
			#t3lib_BEfunc::fixVersioningPid($table,$rec);
			$truePid = t3lib_BEfunc::getTSconfig_pidValue($table, $rec['uid'], $rec['pid']);
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $truePid, 'title');
			$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE);
			$rLabel = '<em>[PID: ' . $truePid . '] ' . $pageTitle . '</em>';

				// Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewPage', TRUE);
				$pageTitle = sprintf($label, $tableTitle);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewRecord', TRUE);

				if ($rec['pid'] == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.createNewRecordRootLevel', TRUE);
				}
				$pageTitle = sprintf($label, $tableTitle, $pageTitle);
			}
		} else {
			$newLabel = ' <span class="typo3-TCEforms-recUid">[' . $rec['uid'] . ']</span>';
			$rLabel = t3lib_BEfunc::getRecordTitle($table, $rec, TRUE, FALSE);
			$prec = t3lib_BEfunc::getRecordWSOL('pages', $rec['pid'], 'uid,title');

				// Fetch translated title of the table
			$tableTitle = $GLOBALS['LANG']->sL($GLOBALS['TCA'][$table]['ctrl']['title']);
			if ($table === 'pages') {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editPage', TRUE);

					// Just take the record title and prepend an edit label.
				$pageTitle = sprintf($label, $tableTitle, $rLabel);
			} else {
				$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecord', TRUE);

				$pageTitle = t3lib_BEfunc::getRecordTitle('pages', $prec, TRUE, FALSE);
				if ($rLabel === t3lib_BEfunc::getNoRecordTitle(TRUE)) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecordNoTitle', TRUE);
				}
				if ($rec['pid'] == 0) {
					$label = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.editRecordRootLevel', TRUE);
				}

				if ($rLabel !== t3lib_BEfunc::getNoRecordTitle(TRUE)) {

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
			$arr[$k] = str_replace('###PAGE_TITLE###', $pageTitle, $arr[$k]);
			$arr[$k] = str_replace('###ID_NEW_INDICATOR###', $newLabel, $arr[$k]);
			$arr[$k] = str_replace('###RECORD_LABEL###', $rLabel, $arr[$k]);
			$arr[$k] = str_replace('###TABLE_TITLE###', htmlspecialchars($this->sL($TCA[$table]['ctrl']['title'])), $arr[$k]);

			$arr[$k] = str_replace('###RECORD_ICON###', t3lib_iconWorks::getSpriteIconForRecord($table, $rec, array('title' => $this->getRecordPath($table, $rec))), $arr[$k]);

		}
		return $arr;
	}

	/**
	 * Wraps an element in the $out_array with the template row for a "section" ($this->sectionWrap)
	 *
	 * @param	array		The array with form elements stored in (passed by reference and changed!)
	 * @param	integer		The pointer to the entry in the $out_array  (passed by reference and incremented!)
	 * @return	void
	 */
	function wrapBorder(&$out_array, &$out_pointer) {
		if ($this->sectionWrap && $out_array[$out_pointer]) {
			$tableAttribs = '';
			$tableAttribs .= $this->borderStyle[0] ? ' style="' . htmlspecialchars($this->borderStyle[0]) . '"' : '';
			$tableAttribs .= $this->borderStyle[2] ? ' background="' . htmlspecialchars($this->backPath . $this->borderStyle[2]) . '"' : '';
			$tableAttribs .= $this->borderStyle[3] ? ' class="' . htmlspecialchars($this->borderStyle[3]) . '"' : '';
			if ($tableAttribs) {
				$tableAttribs = 'border="0" cellspacing="0" cellpadding="0" width="100%"' . $tableAttribs;
				$out_array[$out_pointer] = str_replace('###CONTENT###', $out_array[$out_pointer],
													   str_replace('###TABLE_ATTRIBS###', $tableAttribs, $this->sectionWrap));
			}
			$out_pointer++;
		}
	}

	/**
	 * Replaces colorscheme markers in the template string
	 *
	 * @param	string		Template string with markers to be substituted.
	 * @return	string
	 */
	function rplColorScheme($inTemplate) {
			// Colors:
		$inTemplate = str_replace('###BGCOLOR###', $this->colorScheme[0] ? ' bgcolor="' . $this->colorScheme[0] . '"' : '', $inTemplate);
		$inTemplate = str_replace('###BGCOLOR_HEAD###', $this->colorScheme[1] ? ' bgcolor="' . $this->colorScheme[1] . '"' : '', $inTemplate);
		$inTemplate = str_replace('###FONTCOLOR_HEAD###', $this->colorScheme[3], $inTemplate);

			// Classes:
		$inTemplate = str_replace('###CLASSATTR_1###', $this->classScheme[0] ? ' class="' . $this->classScheme[0] . '"' : '', $inTemplate);
		$inTemplate = str_replace('###CLASSATTR_2###', $this->classScheme[1] ? ' class="' . $this->classScheme[1] . '"' : '', $inTemplate);
		$inTemplate = str_replace('###CLASSATTR_4###', $this->classScheme[3] ? ' class="' . $this->classScheme[3] . '"' : '', $inTemplate);

		return $inTemplate;
	}

	/**
	 * Returns divider.
	 * Currently not implemented and returns only blank value.
	 *
	 * @return	string		Empty string
	 */
	function getDivider() {
		return '';
	}

	/**
	 * Creates HTML output for a palette
	 *
	 * @param	array		The palette array to print
	 * @return	string		HTML output
	 */
	function printPalette($palArr) {
		$fieldAttributes = $labelAttributes = '';

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
		$hRow = $iRow = array();
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
				$fieldIdentifierForJs = $content['TABLE'] . '_' . $content['ID'] . '_' . $content['FIELD'];
				$iRow[$row][] = '<span class="t3-form-palette-field-container">' .
								'<label' . $labelAttributes . '>' .
								$content['NAME'] .
								'</label>' .
								'<span' . $fieldAttributes . '>' .
									'<img name="cm_' . $fieldIdentifierForJs . '" src="clear.gif" class="t3-form-palette-icon-contentchanged" alt="" />' .
									'<img name="req_' . $fieldIdentifierForJs . '" src="clear.gif" class="t3-form-palette-icon-required" alt="" />' .
									$content['ITEM'] .
								'</span>' .
								'</span>';
			}
		}

			// Final wrapping into the fieldset:
		$out = '<fieldset class="t3-form-palette-fieldset">';
		for ($i = 0; $i <= $row; $i++) {
			if (isset($iRow[$i])) {
				$out .= implode('', $iRow[$i]);
				$out .= ($i < $row ? '<br />' : '');
			}

		}
		$out .= '</fieldset>';
		return $out;
	}


	/**
	 * Returns help-text ICON if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	boolean		Force the return of the help-text icon.
	 * @return	string		HTML, <a>-tag with
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function helpTextIcon($table, $field, $force = 0) {
		t3lib_div::logDeprecatedFunction();
		if ($this->globalShowHelp && $GLOBALS['TCA_DESCR'][$table]['columns'][$field] && (($this->edit_showFieldHelp == 'icon' && !$this->doLoadTableDescr($table)) || $force)) {
			return t3lib_BEfunc::helpTextIcon($table, $field, $this->backPath, $force);
		} else {
				// Detects fields with no CSH and outputs dummy line to insert into CSH locallang file:
			return '<span class="nbsp">&nbsp;</span>';
		}
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @return	string
	 * @deprecated since TYPO3 4.5, will be removed in TYPO3 4.7
	 */
	function helpText($table, $field) {
		t3lib_div::logDeprecatedFunction();
		if ($this->globalShowHelp && $GLOBALS['TCA_DESCR'][$table]['columns'][$field] && ($this->edit_showFieldHelp == 'text' || $this->doLoadTableDescr($table))) {
			$fDat = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
			return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">' .
				   $this->helpTextIcon(
					   $table,
					   $field,
					   $fDat['details'] || $fDat['syntax'] || $fDat['image_descr'] || $fDat['image'] || $fDat['seeAlso']
				   ) .
				   '</td><td valign="top"><span class="typo3-TCEforms-helpText">' .
				   $GLOBALS['LANG']->hscAndCharConv(strip_tags($fDat['description']), 1) .
				   '</span></td></tr></table>';
		}
	}

	/**
	 * Returns help-text ICON if configured for.
	 *
	 * @param	string		Field name
	 * @param	string		Field title
	 * @param	string		File name with CSH labels
	 * @return	string		HTML, <a>-tag with
	 * @deprecated since TYPO3 4.5, this function will be removed in TYPO3 4.7. Use t3lib_BEfunc::wrapInHelp() instead.
	 */
	function helpTextIcon_typeFlex($field, $fieldTitle, $cshFile) {
		t3lib_div::logDeprecatedFunction();
		if ($this->globalShowHelp && $cshFile) {
			$value = $GLOBALS['LANG']->sL($cshFile . ':' . $field . '.description');
			if (trim($value)) {
				if (substr($fieldTitle, -1, 1) == ':') {
					$fieldTitle = substr($fieldTitle, 0, strlen($fieldTitle) - 1);
				}

					// Hover popup textbox with alttitle and description
				if ($this->edit_showFieldHelp == 'icon') {
					$arrow = t3lib_iconWorks::getSpriteIcon('actions-view-go-forward');
						// add description text
					$hoverText = '<span class="paragraph">' . nl2br(htmlspecialchars($value)) . $arrow . '</span>';
						// put header before the rest of the text
					$alttitle = $GLOBALS['LANG']->sL($cshFile . ':' . $field . '.alttitle');
					if ($alttitle) {
						$hoverText = '<span class="header">' . $alttitle . '</span><br />' . $hoverText;
					}
					$hoverText = '<span class="typo3-csh-inline">' . $GLOBALS['LANG']->hscAndCharConv($hoverText, FALSE) . '</span>';
				}

					// CSH exists
				$params = base64_encode(serialize(array(
													   'cshFile' => $cshFile,
													   'field' => $field,
													   'title' => $fieldTitle
												  )));
				$aOnClick = 'vHWin=window.open(\'' . $this->backPath . 'view_help.php?ffID=' . $params . '\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
				return '<a href="#" class="typo3-csh-link" onclick="' . htmlspecialchars($aOnClick) . '">' .
					   t3lib_iconWorks::getSpriteIcon('actions-system-help-open') . $hoverText .
					   '</a>';
			}
		}
		return '';
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		Field name
	 * @param	string		CSH file name
	 * @return	string		Description for the field with cion or empty string
	 * @deprecated since TYPO3 4.5, this function will be removed in TYPO3 4.7. Use t3lib_BEfunc::wrapInHelp() instead.
	 */
	function helpText_typeFlex($field, $fieldTitle, $cshFile) {
		t3lib_div::logDeprecatedFunction();
		if ($this->globalShowHelp && $cshFile && $this->edit_showFieldHelp == 'text') {
			$value = $GLOBALS['LANG']->sL($cshFile . ':' . $field . '.description');
			if (trim($value)) {
				return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">' .
					   $this->helpTextIcon_typeFlex(
						   $field,
						   $fieldTitle,
						   $cshFile
					   ) .
					   '</td><td valign="top"><span class="typo3-TCEforms-helpText-flexform">' .
					   $GLOBALS['LANG']->hscAndCharConv(strip_tags($value), 1) .
					   '</span></td></tr></table>';
			}
		}
		return '';
	}


	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defColorScheme plus input string.
	 *
	 * @param	string		A color scheme string.
	 * @return	void
	 */
	function setColorScheme($scheme) {
		$this->colorScheme = $this->defColorScheme;
		$this->classScheme = $this->defClassScheme;

		$parts = t3lib_div::trimExplode(',', $scheme);
		foreach ($parts as $key => $col) {
				// Split for color|class:
			list($color, $class) = t3lib_div::trimExplode('|', $col);

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
	 * @return	void
	 */
	function resetSchemes() {
		$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
		$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
		$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
	}

	/**
	 * Store current color scheme
	 *
	 * @return	void
	 */
	function storeSchemes() {
		$this->savedSchemes['classScheme'] = $this->classScheme;
		$this->savedSchemes['colorScheme'] = $this->colorScheme;
		$this->savedSchemes['fieldStyle'] = $this->fieldStyle;
		$this->savedSchemes['borderStyle'] = $this->borderStyle;
	}

	/**
	 * Restore the saved color scheme
	 *
	 * @return	void
	 */
	function restoreSchemes() {
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
	 * @return	string		A <script></script> section with JavaScript.
	 */
	function JStop() {

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
	 *		 Example use:
	 *
	 *		 $msg .= 'Distribution time (hh:mm dd-mm-yy):<br /><input type="text" name="send_mail_datetime_hr" onchange="typo3form.fieldGet(\'send_mail_datetime\', \'datetime\', \'\', 0,0);"' . $GLOBALS['TBE_TEMPLATE']->formWidth(20) . ' /><input type="hidden" value="' . $GLOBALS['EXEC_TIME'] . '" name="send_mail_datetime" /><br />';
	 *		 $this->extJSCODE.='typo3form.fieldSet("send_mail_datetime", "datetime", "", 0,0);';
	 *
	 *		 ... and then include the result of this function after the form
	 *
	 * @param	string		$formname: The identification of the form on the page.
	 * @param	boolean		$update: Just extend/update existing settings, e.g. for AJAX call
	 * @return	string		A section with JavaScript - if $update is false, embedded in <script></script>
	 */
	function JSbottom($formname = 'forms[0]', $update = FALSE) {
		$jsFile = array();
		$elements = array();

			// required:
		foreach ($this->requiredFields as $itemImgName => $itemName) {
			$match = array();
			if (preg_match('/^(.+)\[((\w|\d|_)+)\]$/', $itemName, $match)) {
				$record = $match[1];
				$field = $match[2];
				$elements[$record][$field]['required'] = 1;
				$elements[$record][$field]['requiredImg'] = $itemImgName;
				if (isset($this->requiredAdditional[$itemName]) && is_array($this->requiredAdditional[$itemName])) {
					$elements[$record][$field]['additional'] = $this->requiredAdditional[$itemName];
				}
			}
		}
			// range:
		foreach ($this->requiredElements as $itemName => $range) {
			if (preg_match('/^(.+)\[((\w|\d|_)+)\]$/', $itemName, $match)) {
				$record = $match[1];
				$field = $match[2];
				$elements[$record][$field]['range'] = array($range[0], $range[1]);
				$elements[$record][$field]['rangeImg'] = $range['imgName'];
			}
		}

		$this->TBE_EDITOR_fieldChanged_func = 'TBE_EDITOR.fieldChanged_fName(fName,formObj[fName+"_list"]);';

		if (!$update) {
			if ($this->loadMD5_JS) {
				$this->loadJavascriptLib('md5.js');
			}

			/** @var $pageRenderer t3lib_PageRenderer */
			$pageRenderer = $GLOBALS['SOBE']->doc->getPageRenderer();
			$pageRenderer->loadPrototype();
			$pageRenderer->loadExtJS();

				// make textareas resizable and flexible
			if (!($GLOBALS['BE_USER']->uc['resizeTextareas'] == '0' && $GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0')) {
				$pageRenderer->addCssFile($this->backPath . '../t3lib/js/extjs/ux/resize.css');
				$this->loadJavascriptLib('../t3lib/js/extjs/ux/ext.resizable.js');
			}
			$resizableSettings = array(
				'textareaMaxHeight' => $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] > 0 ? $GLOBALS['BE_USER']->uc['resizeTextareas_MaxHeight'] : '600',
				'textareaFlexible' => (!$GLOBALS['BE_USER']->uc['resizeTextareas_Flexible'] == '0'),
				'textareaResize' => (!$GLOBALS['BE_USER']->uc['resizeTextareas'] == '0'),
			);
			$pageRenderer->addInlineSettingArray('', $resizableSettings);

			$this->loadJavascriptLib('../t3lib/jsfunc.evalfield.js');
			$this->loadJavascriptLib('jsfunc.tbe_editor.js');

				// needed for tceform manipulation (date picker)
			$typo3Settings = array(
				'datePickerUSmode' => $GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? 1 : 0,
				'dateFormat' => array('j-n-Y', 'G:i j-n-Y'),
				'dateFormatUS' => array('n-j-Y', 'G:i n-j-Y'),
			);
			$pageRenderer->addInlineSettingArray('', $typo3Settings);

			$this->loadJavascriptLib('../t3lib/js/extjs/tceforms.js');

				// if IRRE fields were processed, add the JavaScript functions:
			if ($this->inline->inlineCount) {
				$GLOBALS['SOBE']->doc->getPageRenderer()->loadScriptaculous();
				$this->loadJavascriptLib('../t3lib/jsfunc.inline.js');
				$out .= '
				inline.setPrependFormFieldNames("' . $this->inline->prependNaming . '");
				inline.setNoTitleString("' . addslashes(t3lib_BEfunc::getNoRecordTitle(TRUE)) . '");
				';
			}

				// if Suggest fields were processed, add the JS functions
			if ($this->suggest->suggestCount > 0) {
				$pageRenderer->loadScriptaculous();
				$this->loadJavascriptLib('../t3lib/js/jsfunc.tceforms_suggest.js');
			}

				// Toggle icons:
			$toggleIcon_open = t3lib_iconWorks::getSpriteIcon('actions-move-down', array('title' => 'Open'));
			$toggleIcon_close = t3lib_iconWorks::getSpriteIcon('actions-move-right', array('title' => 'Close'));

			$out .= '
			function getOuterHTML(idTagPrefix)	{	// Function getting the outerHTML of an element with id
				var str=($(idTagPrefix).inspect()+$(idTagPrefix).innerHTML+"</"+$(idTagPrefix).tagName.toLowerCase()+">");
				return str;
			}
			function flexFormToggle(id)	{	// Toggling flexform elements on/off:
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
				if (previewContent.length>80)	{
					previewContent = previewContent.substring(0,67)+"...";
				}
				$(id+"-preview").update(previewContent);
			}
			function flexFormToggleSubs(id)	{	// Toggling sub flexform elements on/off:
				var descendants = $(id).immediateDescendants();
				var isOpen=0;
				var isClosed=0;
					// Traverse and find how many are open or closed:
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id)	{
						if (Element.visible(descendants[i].id+"-content"))	{isOpen++;} else {isClosed++;}
					}
				}

					// Traverse and toggle
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id)	{
						if (isOpen!=0 && isClosed!=0)	{
							if (Element.visible(descendants[i].id+"-content"))	{flexFormToggle(descendants[i].id);}
						} else {
							flexFormToggle(descendants[i].id);
						}
					}
				}
			}
			function flexFormSortable(id)	{	// Create sortables for flexform sections
				Position.includeScrollOffsets = true;
 				Sortable.create(id, {tag:\'div\',constraint: false, onChange:function(){
					setActionStatus(id);
				} });
			}
			function setActionStatus(id)	{	// Updates the "action"-status for a section. This is used to move and delete elements.
				var descendants = $(id).immediateDescendants();

					// Traverse and find how many are open or closed:
				for (var i = 0, length = descendants.length; i < length; i++) {
					if (descendants[i].id)	{
						$(descendants[i].id+"-action").value = descendants[i].visible() ? i : "DELETE";
					}
				}
			}

			TBE_EDITOR.images.req.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/required_h.gif', '', 1) . '";
			TBE_EDITOR.images.cm.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/content_client.gif', '', 1) . '";
			TBE_EDITOR.images.sel.src = "' . t3lib_iconWorks::skinImg($this->backPath, 'gfx/content_selected.gif', '', 1) . '";
			TBE_EDITOR.images.clear.src = "' . $this->backPath . 'clear.gif";

			TBE_EDITOR.auth_timeout_field = ' . intval($GLOBALS['BE_USER']->auth_timeout_field) . ';
			TBE_EDITOR.formname = "' . $formname . '";
			TBE_EDITOR.formnameUENC = "' . rawurlencode($formname) . '";
			TBE_EDITOR.backPath = "' . addslashes($this->backPath) . '";
			TBE_EDITOR.prependFormFieldNames = "' . $this->prependFormFieldNames . '";
			TBE_EDITOR.prependFormFieldNamesUENC = "' . rawurlencode($this->prependFormFieldNames) . '";
			TBE_EDITOR.prependFormFieldNamesCnt = ' . substr_count($this->prependFormFieldNames, '[') . ';
			TBE_EDITOR.isPalettedoc = ' . ($this->isPalettedoc ? addslashes($this->isPalettedoc) : 'null') . ';
			TBE_EDITOR.doSaveFieldName = "' . ($this->doSaveFieldName ? addslashes($this->doSaveFieldName) : '') . '";
			TBE_EDITOR.labels.fieldsChanged = ' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsChanged')) . ';
			TBE_EDITOR.labels.fieldsMissing = ' . $GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsMissing')) . ';
			TBE_EDITOR.labels.refresh_login = ' . $GLOBALS['LANG']->JScharCode($this->getLL('m_refresh_login')) . ';
			TBE_EDITOR.labels.onChangeAlert = ' . $GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')) . ';
			evalFunc.USmode = ' . ($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat'] ? '1' : '0') . ';
			TBE_EDITOR.backend_interface = "' . $GLOBALS['BE_USER']->uc['interfaceSetup'] . '";
			';
		}

			// add JS required for inline fields
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
			// elements which are required or have a range definition:
		if (count($elements)) {
			$out .= '
			TBE_EDITOR.addElements(' . json_encode($elements) . ');
			TBE_EDITOR.initRequired();
			';
		}
			// $this->additionalJS_submit:
		if ($this->additionalJS_submit) {
			$additionalJS_submit = implode('', $this->additionalJS_submit);
			$additionalJS_submit = str_replace(CR, '', $additionalJS_submit);
			$additionalJS_submit = str_replace(LF, '', $additionalJS_submit);
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
			$out = $spacer . implode($spacer, $jsFile) . t3lib_div::wrapJS($out);
		}

		return $out;
	}

	/**
	 * Used to connect the db/file browser with this document and the formfields on it!
	 *
	 * @param	string		Form object reference (including "document.")
	 * @return	string		JavaScript functions/code (NOT contained in a <script>-element)
	 */
	function dbFileCon($formObj = 'document.forms[0]') {
			// @TODO: Export this to an own file, it is more static than dynamic JavaScript -- olly
		$str = '

			// ***************
			// Used to connect the db/file browser with this document and the formfields on it!
			// ***************

			var browserWin="";

			function setFormValueOpenBrowser(mode,params) {	//
				var url = "' . $this->backPath . 'browser.php?mode="+mode+"&bparams="+params;

				browserWin = window.open(url,"Typo3WinBrowser","height=650,width="+(mode=="db"?650:600)+",status=0,menubar=0,resizable=1,scrollbars=1");
				browserWin.focus();
			}
			function setFormValueFromBrowseWin(fName,value,label,exclusiveValues)	{	//
				var formObj = setFormValue_getFObj(fName);
				if (formObj && value !== "--div--") {
					fObj = formObj[fName+"_list"];
					var len = fObj.length;
						// Clear elements if exclusive values are found
					if (exclusiveValues)	{
						var m = new RegExp("(^|,)"+value+"($|,)");
						if (exclusiveValues.match(m))	{
								// the new value is exclusive
							for (a = len - 1; a >= 0; a--) {
								fObj[a] = null;
							}
							len = 0;
						} else if (len == 1)	{
							m = new RegExp("(^|,)"+fObj.options[0].value+"($|,)");
							if (exclusiveValues.match(m))	{
									// the old value is exclusive
								fObj[0] = null;
								len = 0;
							}
						}
					}
						// Inserting element
					var setOK = 1;
					if (!formObj[fName+"_mul"] || formObj[fName+"_mul"].value==0)	{
						for (a=0;a<len;a++)	{
							if (fObj.options[a].value==value)	{
								setOK = 0;
							}
						}
					}
					if (setOK)	{
						fObj.length++;
						fObj.options[len].value = value;
						fObj.options[len].text = unescape(label);

							// Traversing list and set the hidden-field
						setHiddenFromList(fObj,formObj[fName]);
						' . $this->TBE_EDITOR_fieldChanged_func . '
					}
				}
			}
			function setHiddenFromList(fObjSel,fObjHid)	{	//
				l=fObjSel.length;
				fObjHid.value="";
				for (a=0;a<l;a++)	{
					fObjHid.value+=fObjSel.options[a].value+",";
				}
			}
			function setFormValueManipulate(fName,type)	{	//
				var formObj = setFormValue_getFObj(fName);
				if (formObj)	{
					var localArray_V = new Array();
					var localArray_L = new Array();
					var localArray_S = new Array();
					var fObjSel = formObj[fName+"_list"];
					var l=fObjSel.length;
					var c=0;
					if ((type=="Remove" && fObjSel.size > 1) || type=="Top" || type=="Bottom")	{
						if (type=="Top")	{
							for (a=0;a<l;a++)	{
								if (fObjSel.options[a].selected==1)	{
									localArray_V[c]=fObjSel.options[a].value;
									localArray_L[c]=fObjSel.options[a].text;
									localArray_S[c]=1;
									c++;
								}
							}
						}
						for (a=0;a<l;a++)	{
							if (fObjSel.options[a].selected!=1)	{
								localArray_V[c]=fObjSel.options[a].value;
								localArray_L[c]=fObjSel.options[a].text;
								localArray_S[c]=0;
								c++;
							}
						}
						if (type=="Bottom")	{
							for (a=0;a<l;a++)	{
								if (fObjSel.options[a].selected==1)	{
									localArray_V[c]=fObjSel.options[a].value;
									localArray_L[c]=fObjSel.options[a].text;
									localArray_S[c]=1;
									c++;
								}
							}
						}
					}
					if (type=="Down")	{
						var tC = 0;
						var tA = new Array();

						for (a=0;a<l;a++)	{
							if (fObjSel.options[a].selected!=1)	{
									// Add non-selected element:
								localArray_V[c]=fObjSel.options[a].value;
								localArray_L[c]=fObjSel.options[a].text;
								localArray_S[c]=0;
								c++;

									// Transfer any accumulated and reset:
								if (tA.length > 0)	{
									for (aa=0;aa<tA.length;aa++)	{
										localArray_V[c]=fObjSel.options[tA[aa]].value;
										localArray_L[c]=fObjSel.options[tA[aa]].text;
										localArray_S[c]=1;
										c++;
									}

									var tC = 0;
									var tA = new Array();
								}
							} else {
								tA[tC] = a;
								tC++;
							}
						}
							// Transfer any remaining:
						if (tA.length > 0)	{
							for (aa=0;aa<tA.length;aa++)	{
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								c++;
							}
						}
					}
					if (type=="Up")	{
						var tC = 0;
						var tA = new Array();
						var c = l-1;

						for (a=l-1;a>=0;a--)	{
							if (fObjSel.options[a].selected!=1)	{

									// Add non-selected element:
								localArray_V[c]=fObjSel.options[a].value;
								localArray_L[c]=fObjSel.options[a].text;
								localArray_S[c]=0;
								c--;

									// Transfer any accumulated and reset:
								if (tA.length > 0)	{
									for (aa=0;aa<tA.length;aa++)	{
										localArray_V[c]=fObjSel.options[tA[aa]].value;
										localArray_L[c]=fObjSel.options[tA[aa]].text;
										localArray_S[c]=1;
										c--;
									}

									var tC = 0;
									var tA = new Array();
								}
							} else {
								tA[tC] = a;
								tC++;
							}
						}
							// Transfer any remaining:
						if (tA.length > 0)	{
							for (aa=0;aa<tA.length;aa++)	{
								localArray_V[c]=fObjSel.options[tA[aa]].value;
								localArray_L[c]=fObjSel.options[tA[aa]].text;
								localArray_S[c]=1;
								c--;
							}
						}
						c=l;	// Restore length value in "c"
					}

						// Transfer items in temporary storage to list object:
					fObjSel.length = c;
					for (a=0;a<c;a++)	{
						fObjSel.options[a].value = localArray_V[a];
						fObjSel.options[a].text = localArray_L[a];
						fObjSel.options[a].selected = localArray_S[a];
					}
					setHiddenFromList(fObjSel,formObj[fName]);

					' . $this->TBE_EDITOR_fieldChanged_func . '
				}
			}
			function setFormValue_getFObj(fName)	{	//
				var formObj = ' . $formObj . ';
				if (formObj)	{
					if (formObj[fName] && formObj[fName+"_list"] && formObj[fName+"_list"].type=="select-multiple")	{
						return formObj;
					} else {
						alert("Formfields missing:\n fName: "+formObj[fName]+"\n fName_list:"+formObj[fName+"_list"]+"\n type:"+formObj[fName+"_list"].type+"\n fName:"+fName);
					}
				}
				return "";
			}

			// END: dbFileCon parts.
		';
		return $str;
	}

	/**
	 * Prints necessary JavaScript for TCEforms (after the form HTML).
	 *
	 * @return	void
	 */
	function printNeededJSFunctions() {
			// JS evaluation:
		$out = $this->JSbottom($this->formName);


			// Integrate JS functions for the element browser if such fields or IRRE fields were processed:
		if ($this->printNeededJS['dbFileIcons'] || $this->inline->inlineCount) {
			$out .= '



			<!--
			 	JavaScript after the form has been drawn:
			-->

			<script type="text/javascript">
				/*<![CDATA[*/
			' . $this->dbFileCon('document.' . $this->formName) . '
				/*]]>*/
			</script>';
		}
		return $out;
	}

	/**
	 * Returns necessary JavaScript for the top
	 *
	 * @return	void
	 */
	function printNeededJSFunctions_top() {
			// JS evaluation:
		$out = $this->JStop($this->formName);
		return $out;
	}

	/**
	 * Includes a javascript library that exists in the core /typo3/ directory. The
	 * backpath is automatically applied.
	 * This method acts as wrapper for $GLOBALS['SOBE']->doc->loadJavascriptLib($lib).
	 *
	 * @param	string		$lib: Library name. Call it with the full path like "contrib/prototype/prototype.js" to load it
	 * @return	void
	 */
	public function loadJavascriptLib($lib) {
		$GLOBALS['SOBE']->doc->loadJavascriptLib($lib);
	}


	/********************************************
	 *
	 * Various helper functions
	 *
	 ********************************************/


	/**
	 * Gets default record. Maybe not used anymore. FE-editor?
	 *
	 * @param	string		Database Tablename
	 * @param	integer		PID value (positive / negative)
	 * @return	array		"default" row.
	 */
	function getDefaultRecord($table, $pid = 0) {
		global $TCA;
		if ($TCA[$table]) {
			t3lib_div::loadTCA($table);
			$row = array();

			if ($pid < 0 && $TCA[$table]['ctrl']['useColumnsForDefaultValues']) {
					// Fetches the previous record:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid=' . abs($pid) . t3lib_BEfunc::deleteClause($table));
				if ($drow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
						// Gets the list of fields to copy from the previous record.
					$fArr = explode(',', $TCA[$table]['ctrl']['useColumnsForDefaultValues']);
					foreach ($fArr as $theF) {
						if ($TCA[$table]['columns'][$theF]) {
							$row[$theF] = $drow[$theF];
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			foreach ($TCA[$table]['columns'] as $field => $info) {
				if (isset($info['config']['default'])) {
					$row[$field] = $info['config']['default'];
				}
			}

			return $row;
		}
	}

	/**
	 * Return record path (visually formatted, using t3lib_BEfunc::getRecordPath() )
	 *
	 * @param	string		Table name
	 * @param	array		Record array
	 * @return	string		The record path.
	 * @see t3lib_BEfunc::getRecordPath()
	 */
	function getRecordPath($table, $rec) {
		t3lib_BEfunc::fixVersioningPid($table, $rec);
		list($tscPID, $thePidValue) = $this->getTSCpid($table, $rec['uid'], $rec['pid']);
		if ($thePidValue >= 0) {
			return t3lib_BEfunc::getRecordPath($tscPID, $this->readPerms(), 15);
		}
	}

	/**
	 * Returns the select-page read-access SQL clause.
	 * Returns cached string, so you can call this function as much as you like without performance loss.
	 *
	 * @return	string
	 */
	function readPerms() {
		if (!$this->perms_clause_set) {
			$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$this->perms_clause_set = 1;
		}
		return $this->perms_clause;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param	string		Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function sL($str) {
		return $GLOBALS['LANG']->sL($str);
	}

	/**
	 * Returns language label from locallang_core.php
	 * Labels must be prefixed with either "l_" or "m_".
	 * The prefix "l_" maps to the prefix "labels." inside locallang_core.php
	 * The prefix "m_" maps to the prefix "mess." inside locallang_core.php
	 *
	 * @param	string		The label key
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function getLL($str) {
		$content = '';

		switch (substr($str, 0, 2)) {
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.' . substr($str, 2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.' . substr($str, 2));
			break;
		}
		return $content;
	}

	/**
	 * Returns true, if the palette, $palette, is collapsed (not shown, but found in top-frame) for the table.
	 *
	 * @param	string		The table name
	 * @param	integer		The palette pointer/number
	 * @return	boolean
	 */
	function isPalettesCollapsed($table, $palette) {
		global $TCA;

		if ($TCA[$table]['ctrl']['canNotCollapse']) {
			return 0;
		}
		if (is_array($TCA[$table]['palettes'][$palette]) && $TCA[$table]['palettes'][$palette]['canNotCollapse']) {
			return 0;
		}
		return $this->palettesCollapsed;
	}

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @param	string		FlexForm value key, eg. vDEF
	 * @return	boolean
	 */
	function isDisplayCondition($displayCond, $row, $ffValueKey = '') {
		$output = FALSE;

		$parts = explode(':', $displayCond);
		switch ((string) $parts[0]) { // Type of condition:
			case 'FIELD':
				if ($ffValueKey) {
					if (strpos($parts[1], 'parentRec.') !== FALSE) {
						$fParts = explode('.', $parts[1]);
						$theFieldValue = $row['parentRec'][$fParts[1]];
					} else {
						$theFieldValue = $row[$parts[1]][$ffValueKey];
					}
				} else {
					$theFieldValue = $row[$parts[1]];
				}

				switch ((string) $parts[2]) {
					case 'REQ':
						if (strtolower($parts[3]) == 'true') {
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3]) == 'false') {
							$output = !$theFieldValue ? TRUE : FALSE;
						}
					break;
					case '>':
						$output = $theFieldValue > $parts[3];
					break;
					case '<':
						$output = $theFieldValue < $parts[3];
					break;
					case '>=':
						$output = $theFieldValue >= $parts[3];
					break;
					case '<=':
						$output = $theFieldValue <= $parts[3];
					break;
					case '-':
					case '!-':
						$cmpParts = explode('-', $parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0} == '!') {
							$output = !$output;
						}
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3], $theFieldValue);
						if ($parts[2]{0} == '!') {
							$output = !$output;
						}
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3], $theFieldValue);
						if ($parts[2]{0} == '!') {
							$output = !$output;
						}
					break;
				}
			break;
			case 'EXT':
				switch ((string) $parts[2]) {
					case 'LOADED':
						if (strtolower($parts[3]) == 'true') {
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3]) == 'false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch ((string) $parts[1]) {
					case 'NEW':
						if (strtolower($parts[2]) == 'true') {
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2]) == 'false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey === 'vDEF') {
					$output = TRUE;
				} elseif ($parts[1] === 'except_admin' && $GLOBALS['BE_USER']->isAdmin()) {
					$output = TRUE;
				}
			break;
			case 'HIDE_FOR_NON_ADMINS':
				$output = $GLOBALS['BE_USER']->isAdmin() ? TRUE : FALSE;
			break;
			case 'VERSION':
				switch ((string) $parts[1]) {
					case 'IS':
						$isNewRecord = (intval($row['uid']) > 0 ? FALSE : TRUE);

							// detection of version can be done be detecting the workspace of the user
						$isUserInWorkspace = ($GLOBALS['BE_USER']->workspace > 0 ? TRUE : FALSE);
						if (intval($row['pid']) == -1 || intval($row['_ORIG_pid']) == -1) {
							$isRecordDetectedAsVersion = TRUE;
						} else {
							$isRecordDetectedAsVersion = FALSE;
						}

							// New records in a workspace are not handled as a version record
							// if it's no new version, we detect versions like this:
							// -- if user is in workspace: always true
							// -- if editor is in live ws: only true if pid == -1
						$isVersion = ($isUserInWorkspace || $isRecordDetectedAsVersion) && !$isNewRecord;

						if (strtolower($parts[2]) == 'true') {
							$output = $isVersion;
						} else {
							if (strtolower($parts[2]) == 'false') {
								$output = !$isVersion;
							}
						}
					break;
				}
			break;
		}

		return $output;
	}

	/**
	 * Return TSCpid (cached)
	 * Using t3lib_BEfunc::getTSCpid()
	 *
	 * @param	string		Tablename
	 * @param	string		UID value
	 * @param	string		PID value
	 * @return	integer		Returns the REAL pid of the record, if possible. If both $uid and $pid is strings, then pid=-1 is returned as an error indication.
	 * @see t3lib_BEfunc::getTSCpid()
	 */
	function getTSCpid($table, $uid, $pid) {
		$key = $table . ':' . $uid . ':' . $pid;
		if (!isset($this->cache_getTSCpid[$key])) {
			$this->cache_getTSCpid[$key] = t3lib_BEfunc::getTSCpid($table, $uid, $pid);
		}
		return $this->cache_getTSCpid[$key];
	}

	/**
	 * Returns true if descriptions should be loaded always
	 *
	 * @param	string		Table for which to check
	 * @return	boolean
	 */
	function doLoadTableDescr($table) {
		global $TCA;
		return $TCA[$table]['interface']['always_description'];
	}

	/**
	 * Returns an array of available languages (to use for FlexForms)
	 *
	 * @param	boolean		If set, only languages which are paired with a static_info_table / static_language record will be returned.
	 * @param	boolean		If set, an array entry for a default language is set.
	 * @return	array
	 */
	function getAvailableLanguages($onlyIsoCoded = 1, $setDefault = 1) {
		$isL = t3lib_extMgm::isLoaded('static_info_tables');

			// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0 AND hidden=0' . t3lib_BEfunc::deleteClause('sys_language'), '', 'title');

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
				$rr = t3lib_BEfunc::getRecord('static_languages', $row['static_lang_isocode'], 'lg_iso_2');
				if ($rr['lg_iso_2']) {
					$output[$row['uid']]['ISOcode'] = $rr['lg_iso_2'];
				}
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode']) {
				unset($output[$row['uid']]);
			}
		}
		return $output;
	}

	/**
	 * Initializes language icons etc.
	 *
	 * param	string	Table name
	 * param	array	Record
	 * param	string	Sys language uid OR ISO language code prefixed with "v", eg. "vDA"
	 * @return	void
	 */
	function getLanguageIcon($table, $row, $sys_language_uid) {
		global $TCA, $LANG;

		$mainKey = $table . ':' . $row['uid'];

		if (!isset($this->cachedLanguageFlag[$mainKey])) {
			t3lib_BEfunc::fixVersioningPid($table, $row);
			list($tscPID, $thePidValue) = $this->getTSCpid($table, $row['uid'], $row['pid']);

			$t8Tools = t3lib_div::makeInstance('t3lib_transl8tools');
			$this->cachedLanguageFlag[$mainKey] = $t8Tools->getSystemLanguages($tscPID, $this->backPath);
		}

			// Convert sys_language_uid to sys_language_uid if input was in fact a string (ISO code expected then)
		if (!t3lib_div::testInt($sys_language_uid)) {
			foreach ($this->cachedLanguageFlag[$mainKey] as $rUid => $cD) {
				if ('v' . $cD['ISOcode'] === $sys_language_uid) {
					$sys_language_uid = $rUid;
				}
			}
		}

		$out = '';
		if ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']) {
			$out .= t3lib_iconWorks::getSpriteIcon($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['flagIcon']);
			$out .= '&nbsp;';
		} else if ($this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title']) {
			$out .= '[' . $this->cachedLanguageFlag[$mainKey][$sys_language_uid]['title'] . ']';
			$out .= '&nbsp;';
		}
		return $out;
	}

	/**
	 * Rendering preview output of a field value which is not shown as a form field but just outputted.
	 *
	 * @param	string		The value to output
	 * @param	array		Configuration for field.
	 * @param	string		Name of field.
	 * @return	 string		HTML formatted output
	 */
	function previewFieldValue($value, $config, $field = '') {
		if ($config['config']['type'] === 'group' &&
			($config['config']['internal_type'] === 'file' ||
			 $config['config']['internal_type'] === 'file_reference')) {
				// Ignore uploadfolder if internal_type is file_reference
			if ($config['config']['internal_type'] === 'file_reference') {
				$config['config']['uploadfolder'] = '';
			}

			$show_thumbs = TRUE;
			$table = 'tt_content';

				// Making the array of file items:
			$itemArray = t3lib_div::trimExplode(',', $value, 1);

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
					$absFilePath = t3lib_div::getFileAbsFileName($config['config']['uploadfolder'] ? $config['config']['uploadfolder'] . '/' . $imgPath : $imgPath);

					$fileInformation = pathinfo($imgPath);
					$fileIcon = t3lib_iconWorks::getSpriteIconForFile($imgPath,
						array('title' =>
							htmlspecialchars($fileInformation['basename'] .
								($absFilePath && @is_file($absFilePath) ?
								' (' . t3lib_div::formatSize(filesize($absFilePath)) . 'bytes)' :
								' - FILE NOT FOUND!')
							)
						)
					);

					$imgs[] = '<span class="nobr">' . t3lib_BEfunc::thumbCode($rowCopy, $table, $field, $this->backPath, 'thumbs.php', $config['config']['uploadfolder'], 0, ' align="middle"') .
							  ($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon) .
							  $imgPath .
							  '</span>';
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
	 * return array	Array of additional languages to preview
	 */
	function getAdditionalPreviewLanguages() {
		if (!isset($this->cachedAdditionalPreviewLanguages)) {
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages')) {
				$uids = t3lib_div::intExplode(',', $GLOBALS['BE_USER']->getTSConfigVal('options.additionalPreviewLanguages'));
				foreach ($uids as $uid) {
					if ($sys_language_rec = t3lib_BEfunc::getRecord('sys_language', $uid)) {
						$this->cachedAdditionalPreviewLanguages[$uid] = array('uid' => $uid);

						if ($sys_language_rec['static_lang_isocode'] && t3lib_extMgm::isLoaded('static_info_tables')) {
							$staticLangRow = t3lib_BEfunc::getRecord('static_languages', $sys_language_rec['static_lang_isocode'], 'lg_iso_2');
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
	 * @param	string		$type: Type of the level, e.g. "tab" or "inline"
	 * @param	string		$ident: Identifier of the level
	 * @return	void
	 */
	function pushToDynNestedStack($type, $ident) {
		$this->dynNestedStack[] = array($type, $ident);
	}

	/**
	 * Remove an element from the dynNestedStack. If $type and $ident
	 * are set, the last element will only be removed, if it matches
	 * what is expected to be removed.
	 *
	 * @param	string		$type: Type of the level, e.g. "tab" or "inline"
	 * @param	string		$ident: Identifier of the level
	 * @return	void
	 */
	function popFromDynNestedStack($type = NULL, $ident = NULL) {
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
	 * @param	boolean		$json: Return a JSON string instead of an array - default: false
	 * @param	boolean		$skipFirst: Skip the first element in the dynNestedStack - default: false
	 * @return	mixed		Returns an associative array by default. If $json is true, it will be returned as JSON string.
	 */
	function getDynNestedStack($json = FALSE, $skipFirst = FALSE) {
		$result = $this->dynNestedStack;
		if ($skipFirst) {
			array_shift($result);
		}
		return ($json ? json_encode($result) : $result);
	}

	/**
	 * Takes care of registering properties in requiredFields and requiredElements.
	 * The current hierarchy of IRRE and/or Tabs is stored. Thus, it is possible to determine,
	 * which required field/element was filled incorrectly and show it, even if the Tab or IRRE
	 * level is hidden.
	 *
	 * @param	string		$type: Type of requirement ('field' or 'range')
	 * @param	string		$name: The name of the form field
	 * @param	mixed		$value: For type 'field' string, for type 'range' array
	 * @return	void
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
	 * @param	string		$itemName: The element the nesting should be stored for
	 * @param	boolean		$setLevel: Set the reverse level lookup - default: true
	 * @return	void
	 */
	protected function registerNestedElement($itemName, $setLevel = TRUE) {
		$dynNestedStack = $this->getDynNestedStack();
		if (count($dynNestedStack) && preg_match('/^(.+\])\[(\w+)\]$/', $itemName, $match)) {
			array_shift($match);
			$this->requiredNested[$itemName] = array(
				'parts' => $match,
				'level' => $dynNestedStack,
			);
		}
	}

	/**
	 * Insert additional style sheet link
	 *
	 * @param	string		$key: some key identifying the style sheet
	 * @param	string		$href: uri to the style sheet file
	 * @param	string		$title: value for the title attribute of the link element
	 * @return	string		$relation: value for the rel attribute of the link element
	 * @return	void
	 */
	public function addStyleSheet($key, $href, $title = '', $relation = 'stylesheet') {
		$GLOBALS['SOBE']->doc->addStyleSheet($key, $href, $title, $relation);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php']);
}

?>
