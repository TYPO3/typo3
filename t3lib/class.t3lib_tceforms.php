<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Revised for TYPO3 3.6 August/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  191: class t3lib_TCEforms
 *  294:     function t3lib_TCEforms()
 *  328:     function initDefaultBEmode()
 *
 *              SECTION: Rendering the forms, fields etc
 *  373:     function getSoloField($table,$row,$theFieldToReturn)
 *  412:     function getMainFields($table,$row,$depth=0)
 *  579:     function getListedFields($table,$row,$list)
 *  620:     function getPaletteFields($table,$row,$palette,$header='',$itemList='',$collapsedHeader='')
 *  696:     function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)
 *  830:     function getSingleField_SW($table,$field,$row,&$PA)
 *
 *              SECTION: Rendering of each TCEform field type
 *  903:     function getSingleField_typeInput($table,$field,$row,&$PA)
 *  955:     function getSingleField_typeText($table,$field,$row,&$PA)
 * 1054:     function getSingleField_typeCheck($table,$field,$row,&$PA)
 * 1113:     function getSingleField_typeRadio($table,$field,$row,&$PA)
 * 1143:     function getSingleField_typeSelect($table,$field,$row,&$PA)
 * 1217:     function getSingleField_typeSelect_single($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1326:     function getSingleField_typeSelect_checkbox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1438:     function getSingleField_typeSelect_singlebox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1540:     function getSingleField_typeSelect_multiple($table,$field,$row,&$PA,$config,$selItems,$nMV_label)
 * 1630:     function getSingleField_typeGroup($table,$field,$row,&$PA)
 * 1787:     function getSingleField_typeNone($table,$field,$row,&$PA)
 * 1803:     function getSingleField_typeNone_render($config,$itemValue)
 * 1862:     function getSingleField_typeFlex($table,$field,$row,&$PA)
 * 1986:     function getSingleField_typeFlex_langMenu($languages,$elName,$selectedLanguage,$multi=1)
 * 2005:     function getSingleField_typeFlex_sheetMenu($sArr,$elName,$sheetKey)
 * 2035:     function getSingleField_typeFlex_draw($dataStruct,$editData,$cmdData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$tRows=array())
 * 2187:     function getSingleField_typeUnknown($table,$field,$row,&$PA)
 * 2202:     function getSingleField_typeUser($table,$field,$row,&$PA)
 *
 *              SECTION: "Configuration" fetching/processing functions
 * 2236:     function getRTypeNum($table,$row)
 * 2262:     function rearrange($fields)
 * 2288:     function getExcludeElements($table,$row,$typeNum)
 * 2336:     function getFieldsToAdd($table,$row,$typeNum)
 * 2361:     function mergeFieldsWithAddedFields($fields,$fieldsToAdd)
 * 2393:     function setTSconfig($table,$row,$field='')
 * 2415:     function getSpecConfForField($table,$row,$field)
 * 2436:     function getSpecConfFromString($extraString, $defaultExtras)
 *
 *              SECTION: Display of localized content etc.
 * 2464:     function registerDefaultLanguageData($table,$rec)
 * 2496:     function renderDefaultLanguageContent($table,$field,$row,$item)
 * 2519:     function renderDefaultLanguageDiff($table,$field,$row,$item)
 *
 *              SECTION: Form element helper functions
 * 2575:     function dbFileIcons($fName,$mode,$allowed,$itemArray,$selector='',$params=array(),$onFocus='')
 * 2708:     function getClipboardElements($allowed,$mode)
 * 2757:     function getClickMenu($str,$table,$uid='')
 * 2778:     function renderWizards($itemKinds,$wizConf,$table,$row,$field,&$PA,$itemName,$specConf,$RTE=0)
 * 2982:     function getIcon($icon)
 * 3009:     function optionTagStyle($iconString)
 * 3025:     function extractValuesOnlyFromValueLabelList($itemFormElValue)
 * 3047:     function wrapOpenPalette($header,$table,$row,$palette,$retFunc=0)
 * 3071:     function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='')
 * 3085:     function elName($itemName)
 * 3096:     function noTitle($str,$wrapParts=array())
 * 3105:     function blur()
 * 3114:     function thisReturnUrl()
 * 3127:     function getSingleHiddenField($table,$field,$row)
 * 3149:     function formWidth($size=48,$textarea=0)
 * 3176:     function formWidthText($size=48,$wrap='')
 * 3192:     function formElStyle($type)
 * 3203:     function formElClass($type)
 * 3214:     function formElStyleClassValue($type, $class=FALSE)
 * 3236:     function insertDefStyle($type)
 * 3255:     function getDynTabMenu($parts, $idString)
 *
 *              SECTION: Item-array manipulation functions (check/select/radio)
 * 3294:     function initItemArray($fieldValue)
 * 3312:     function addItems($items,$iArray)
 * 3334:     function procItems($items,$iArray,$config,$table,$row,$field)
 * 3358:     function addSelectOptionsToItemArray($items,$fieldValue,$TSconfig,$field)
 * 3580:     function addSelectOptionsToItemArray_makeModuleData($value)
 * 3602:     function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)
 *
 *              SECTION: Template functions
 * 3682:     function setNewBEDesign()
 * 3737:     function intoTemplate($inArr,$altTemplate='')
 * 3761:     function addUserTemplateMarkers($marker,$table,$field,$row,&$PA)
 * 3772:     function wrapLabels($str)
 * 3785:     function wrapTotal($c,$rec,$table)
 * 3798:     function replaceTableWrap($arr,$rec,$table)
 * 3835:     function wrapBorder(&$out_array,&$out_pointer)
 * 3857:     function rplColorScheme($inTemplate)
 * 3877:     function getDivider()
 * 3887:     function printPalette($palArr)
 * 3938:     function helpTextIcon($table,$field,$force=0)
 * 3958:     function helpText($table,$field)
 * 3979:     function setColorScheme($scheme)
 * 4003:     function resetSchemes()
 * 4014:     function storeSchemes()
 * 4026:     function restoreSchemes()
 *
 *              SECTION: JavaScript related functions
 * 4056:     function JStop()
 * 4107:     function JSbottom($formname='forms[0]')
 * 4420:     function dbFileCon($formObj='document.forms[0]')
 * 4528:     function printNeededJSFunctions()
 * 4555:     function printNeededJSFunctions_top()
 *
 *              SECTION: Various helper functions
 * 4603:     function getDefaultRecord($table,$pid=0)
 * 4642:     function getRecordPath($table,$rec)
 * 4656:     function readPerms()
 * 4670:     function sL($str)
 * 4683:     function getLL($str)
 * 4701:     function isPalettesCollapsed($table,$palette)
 * 4716:     function isDisplayCondition($displayCond,$row)
 * 4797:     function getTSCpid($table,$uid,$pid)
 * 4811:     function doLoadTableDescr($table)
 * 4823:     function getAvailableLanguages($onlyIsoCoded=1,$setDefault=1)
 *
 *
 * 4865: class t3lib_TCEforms_FE extends t3lib_TCEforms
 * 4873:     function wrapLabels($str)
 * 4883:     function printPalette($palArr)
 * 4908:     function setFancyDesign()
 *
 * TOTAL FUNCTIONS: 98
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */













require_once(PATH_t3lib.'class.t3lib_diff.php');



/**
 * 'TCEforms' - Class for creating the backend editing forms.
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @coauthor	Rene Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms	{

		// variables not commented yet.... (do so...)
	var $palFieldArr = array();
	var $disableWizards = 0;
	var $isPalettedoc = 0;
	var $paletteMargin = 1;
	var $defStyle = ''; // 'font-family:Verdana;font-size:10px;';
	var $cachedTSconfig = array();
	var $cachedTSconfig_fieldLevel = array();
	var $transformedRow = array();
	var $extJSCODE = '';
	var $printNeededJS = array();
	var $hiddenFieldAccum=array();
	var $TBE_EDITOR_fieldChanged_func='';
	var $loadMD5_JS=1;
	var $prevBorderStyle='[nothing here...]';	// Something unique...
	var $allowUpload=0; 				// If set direct upload fields will be shown
	var $titleLen=15; 					// $BE_USER->uc['titleLen'] but what is default??
	var $defaultLanguageData = array();	// Array where records in the default language is stored. (processed by transferdata)
	var $defaultLanguageData_diff = array();	// Array where records in the default language is stored (raw without any processing. used for making diff)


		// EXTERNAL, static
	var $backPath='';					// Set this to the 'backPath' pointing back to the typo3 admin directory from the script where this form is displayed.
	var $returnUrl='';					// Alternative return URL path (default is t3lib_div::linkThisScript())
	var $doSaveFieldName='';			// Can be set to point to a field name in the form which will be set to '1' when the form is submitted with a *save* button. This way the recipient script can determine that the form was submitted for save and not "close" for example.
	var $palettesCollapsed=0;			// Can be set true/false to whether palettes (secondary options) are in the topframe or in form. True means they are NOT IN-form. So a collapsed palette is one, which is shown in the top frame, not in the page.
	var $disableRTE=0;					// If set, the RTE is disabled (from form display, eg. by checkbox in the bottom of the page!)
	var $globalShowHelp=1;				// If false, then all CSH will be disabled, regardless of settings in $this->edit_showFieldHelp
	var $localizationMode='';		// If true, the forms are rendering only localization relevant fields of the records.
	var $fieldOrder='';					// Overrule the field order set in TCA[types][showitem], eg for tt_content this value, 'bodytext,image', would make first the 'bodytext' field, then the 'image' field (if set for display)... and then the rest in the old order.
	var $doPrintPalette=1;				// If set to false, palettes will NEVER be rendered.
	var $clipObj=FALSE;					// Set to initialized clipboard object; Then the element browser will offer a link to paste in records from clipboard.
	var $enableClickMenu=FALSE;			// Enable click menu on reference icons.
	var $enableTabMenu = FALSE;			// Enable Tab Menus. If set to true, the JavaScript content from template::getDynTabMenuJScode() must be included in the document.
	var $renderReadonly = FALSE; 		// When enabled all fields are rendered non-editable.

	var $form_rowsToStylewidth = 9.58;	// Form field width compensation: Factor from NN4 form field widths to style-aware browsers (like NN6+ and MSIE, with the $CLIENT[FORMSTYLE] value set)
	var $form_largeComp = 1.33;			// Form field width compensation: Compensation for large documents, doc-tab (editing)
	var $charsPerRow=40;				// The number of chars expected per row when the height of a text area field is automatically calculated based on the number of characters found in the field content.
	var $maxTextareaWidth=48;			// The maximum abstract value for textareas
	var $maxInputWidth=48;				// The maximum abstract value for input fields
	var $defaultMultipleSelectorStyle='width:250px;';	// Default style for the selector boxes used for multiple items in "select" and "group" types.


		// INTERNAL, static
	var $prependFormFieldNames = 'data';		// The string to prepend formfield names with.
	var $prependFormFieldNames_file = 'data_files';		// The string to prepend FILE form field names with.
	var $formName = 'editform';					// The name attribute of the form.



		// INTERNAL, dynamic
	var $perms_clause='';						// Set by readPerms()  (caching)
	var $perms_clause_set=0;					// Set by readPerms()  (caching-flag)
	var $edit_showFieldHelp='';					// Used to indicate the mode of CSH (Context Sensitive Help), whether it should be icons-only ('icon'), full description ('text') or not at all (blank).
	var $docLarge=0;							// If set, the forms will be rendered a little wider, more precisely with a factor of $this->form_largeComp.
	var $clientInfo=array();					// Loaded with info about the browser when class is instantiated.
	var $RTEenabled=0;							// True, if RTE is possible for the current user (based on result from BE_USER->isRTE())
	var $RTEenabled_notReasons='';				// If $this->RTEenabled was false, you can find the reasons listed in this array which is filled with reasons why the RTE could not be loaded)
	var $RTEcounter = 0;						// Counter that is incremented before an RTE is created. Can be used for unique ids etc.

	var $colorScheme;							// Contains current color scheme
	var $classScheme;							// Contains current class scheme
	var $defColorScheme;						// Contains the default color scheme
	var $defClassScheme;						// Contains the default class scheme
	var $fieldStyle;							// Contains field style values
	var $borderStyle;							// Contains border style values.

	var $commentMessages=array();				// An accumulation of messages from the class.

		// INTERNAL, templates
	var $totalWrap='<hr />|<hr />';				// Total wrapping for the table rows.
	var $fieldTemplate='<b>###FIELD_NAME###</b><br />###FIELD_ITEM###<hr />';	// Field template
	var $sectionWrap='';						// Wrapping template code for a section
	var $palFieldTemplateHeader='';				// Template for palette headers
	var $palFieldTemplate='';					// Template for palettes

		// INTERNAL, working memory
	var $excludeElements='';					// Set to the fields NOT to display, if any.
	var $palettesRendered=array();				// During rendering of forms this will keep track of which palettes has already been rendered (so they are not rendered twice by mistake)
	var $hiddenFieldListArr = array();			// This array of fields will be set as hidden-fields instead of rendered normally! For instance palette fields edited in the top frame are set as hidden fields since the main form has to submit the values. The top frame actually just sets the value in the main form!
	var $requiredFields=array();				// Used to register input-field names, which are required. (Done during rendering of the fields). This information is then used later when the JavaScript is made.
	var $requiredElements=array();				// Used to register the min and max number of elements for selectorboxes where that apply (in the "group" type for instance)
	var $renderDepth=0;							// Keeps track of the rendering depth of nested records.
	var $savedSchemes=array();					// Color scheme buffer.

		// Internal, registers for user defined functions etc.
	var $additionalCode_pre = array();			// Additional HTML code, printed before the form.
	var $additionalJS_pre = array();			// Additional JavaScript, printed before the form
	var $additionalJS_post = array();			// Additional JavaScript printed after the form
	var $additionalJS_submit = array();			// Additional JavaScript executed on submit; If you set "OK" variable it will raise an error about RTEs not being loaded and offer to block further submission.







	/**
	 * Constructor function, setting internal variables, loading the styles used.
	 *
	 * @return	void
	 */
	function t3lib_TCEforms()	{
		global $CLIENT;

		$this->clientInfo = t3lib_div::clientInfo();

		$this->RTEenabled = $GLOBALS['BE_USER']->isRTE();
		if (!$this->RTEenabled)	{
			$this->RTEenabled_notReasons = implode(chr(10),$GLOBALS['BE_USER']->RTE_errors);
			$this->commentMessages[] = 'RTE NOT ENABLED IN SYSTEM due to:'.chr(10).$this->RTEenabled_notReasons;
		}

			// Default color+class scheme
		$this->defColorScheme = array(
			$GLOBALS['SOBE']->doc->bgColor,	// Background for the field AND palette
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor,-20),	// Background for the field header
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor,-10),	// Background for the palette field header
			'black',	// Field header font color
			'#666666'	// Palette field header font color
		);
		$this->defColorScheme = array();

			// Override / Setting defaults from TBE_STYLES array
		$this->resetSchemes();

			// Setting the current colorScheme to default.
		$this->defColorScheme = $this->colorScheme;
		$this->defClassScheme = $this->classScheme;
	}

	/**
	 * Initialize various internal variables.
	 *
	 * @return	void
	 */
	function initDefaultBEmode()	{
		global $BE_USER;
		$this->prependFormFieldNames = 'data';
		$this->formName = 'editform';
		$this->setNewBEDesign();
		$this->docLarge = $BE_USER->uc['edit_wideDocument'] ? 1 : 0;
		$this->edit_showFieldHelp = $BE_USER->uc['edit_showFieldHelp'];

		$this->edit_docModuleUpload = $BE_USER->uc['edit_docModuleUpload'];
		$this->titleLen = $BE_USER->uc['titleLen'];
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
	function getSoloField($table,$row,$theFieldToReturn)	{
		global $TCA;

		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			$typeNum = $this->getRTypeNum($table,$row);
			if ($TCA[$table]['types'][$typeNum])	{
				$itemList = $TCA[$table]['types'][$typeNum]['showitem'];
				if ($itemList)	{
					$fields = t3lib_div::trimExplode(',',$itemList,1);
					$excludeElements = $this->excludeElements = $this->getExcludeElements($table,$row,$typeNum);

					reset($fields);
					while(list(,$fieldInfo)=each($fields))	{
						$parts = explode(';',$fieldInfo);

						$theField = trim($parts[0]);
						if (!in_array($theField,$excludeElements) && !strcmp($theField,$theFieldToReturn))	{
							if ($TCA[$table]['columns'][$theField])	{
								$sField = $this->getSingleField($table,$theField,$row,$parts[1],1,$parts[3],$parts[2]);
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
	function getMainFields($table,$row,$depth=0)	{
		global $TCA, $TYPO3_CONF_VARS;

		$this->renderDepth=$depth;

			// Init vars:
		$out_array = array(array());
		$out_array_meta = array(array(
			'title' => $this->getLL('l_generalTab')
		));

		$out_pointer=0;
		$out_sheet=0;
		$this->palettesRendered=array();
		$this->palettesRendered[$this->renderDepth][$table]=array();

			// First prepare user defined objects (if any) for hooks which extend this function:
		$hookObjectsArr = array();
		if (is_array ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass']))	{
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS']['t3lib/class.t3lib_tceforms.php']['getMainFieldsClass'] as $classRef)	{
				$hookObjectsArr[] = &t3lib_div::getUserObj($classRef);
			}
		}

			// Hook: getMainFields_preProcess (requested by Thomas Hempel for use with the "dynaflex" extension)
		foreach ($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_preProcess'))	{
				$hookObj->getMainFields_preProcess($table,$row,$this);
			}
		}

		if ($TCA[$table])	{

				// Load the full TCA for the table.
			t3lib_div::loadTCA($table);

				// Load the description content for the table.
			if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table))	{
				$GLOBALS['LANG']->loadSingleTableDescription($table);
			}
				// Get the current "type" value for the record.
			$typeNum = $this->getRTypeNum($table,$row);

				// Find the list of fields to display:
			if ($TCA[$table]['types'][$typeNum])	{
				$itemList = $TCA[$table]['types'][$typeNum]['showitem'];
				if ($itemList)	{	// If such a list existed...

						// Explode the field list and possibly rearrange the order of the fields, if configured for
					$fields = t3lib_div::trimExplode(',',$itemList,1);
					if ($this->fieldOrder)	{
						$fields = $this->rearrange($fields);
					}

						// Get excluded fields, added fiels and put it together:
					$excludeElements = $this->excludeElements = $this->getExcludeElements($table,$row,$typeNum);
					$fields = $this->mergeFieldsWithAddedFields($fields,$this->getFieldsToAdd($table,$row,$typeNum));

						// Traverse the fields to render:
					$cc=0;
					foreach($fields as $fieldInfo)	{
							// Exploding subparts of the field configuration:
						$parts = explode(';',$fieldInfo);

							// Getting the style information out:
						$color_style_parts = t3lib_div::trimExplode('-',$parts[4]);
						if (strcmp($color_style_parts[0],''))	{
							$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][intval($color_style_parts[0])]);
						}
						if (strcmp($color_style_parts[1],''))	{
							$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][intval($color_style_parts[1])];
							if (!isset($this->fieldStyle))	$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
						}
						if (strcmp($color_style_parts[2],''))	{
							$this->wrapBorder($out_array[$out_sheet],$out_pointer);
							$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][intval($color_style_parts[2])];
							if (!isset($this->borderStyle))	$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
						}

							// Render the field:
						$theField = $parts[0];
						if (!in_array($theField,$excludeElements))	{
							if ($TCA[$table]['columns'][$theField])	{
								$sFieldPal='';

								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]]))	{
									$sFieldPal=$this->getPaletteFields($table,$row,$parts[2]);
									$this->palettesRendered[$this->renderDepth][$table][$parts[2]] = 1;
								}
								$sField = $this->getSingleField($table,$theField,$row,$parts[1],0,$parts[3],$parts[2]);
								if ($sField)	$sField.=$sFieldPal;

								$out_array[$out_sheet][$out_pointer].= $sField;
							} elseif ($theField=='--div--')	{
								if ($cc>0)	{
									$out_array[$out_sheet][$out_pointer].=$this->getDivider();

									if ($this->enableTabMenu && $TCA[$table]['ctrl']['dividers2tabs'])	{
										$this->wrapBorder($out_array[$out_sheet],$out_pointer);
										$out_sheet++;
										$out_array[$out_sheet] = array();
										$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
									}
								} else {	// Setting alternative title for "General" tab if "--div--" is the very first element.
									$out_array_meta[$out_sheet]['title'] = $this->sL($parts[1]);
								}
							} elseif($theField=='--palette--')	{
								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]]))	{
										// render a 'header' if not collapsed
									if ($TCA[$table]['palettes'][$parts[2]]['canNotCollapse'] AND $parts[1]) {
										$out_array[$out_sheet][$out_pointer].=$this->getPaletteFields($table,$row,$parts[2],$this->sL($parts[1]));
									} else {
										$out_array[$out_sheet][$out_pointer].=$this->getPaletteFields($table,$row,$parts[2],'','',$this->sL($parts[1]));
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
		foreach ($hookObjectsArr as $hookObj)	{
			if (method_exists($hookObj,'getMainFields_postProcess'))	{
				$hookObj->getMainFields_postProcess($table,$row,$this);
			}
		}

			// Wrapping a border around it all:
		$this->wrapBorder($out_array[$out_sheet],$out_pointer);

			// Resetting styles:
		$this->resetSchemes();

			// Rendering Main palettes, if any
		$mParr = t3lib_div::trimExplode(',',$TCA[$table]['ctrl']['mainpalette']);
		$i = 0;
		if (count($mParr))	{
			foreach ($mParr as $mP)	{
				if (!isset($this->palettesRendered[$this->renderDepth][$table][$mP]))	{
					$temp_palettesCollapsed=$this->palettesCollapsed;
					$this->palettesCollapsed=0;
					$label = ($i==0?$this->getLL('l_generalOptions'):$this->getLL('l_generalOptions_more'));
					$out_array[$out_sheet][$out_pointer].=$this->getPaletteFields($table,$row,$mP,$label);
					$this->palettesCollapsed=$temp_palettesCollapsed;
					$this->palettesRendered[$this->renderDepth][$table][$mP] = 1;
				}
				$this->wrapBorder($out_array[$out_sheet],$out_pointer);
				$i++;
				if ($this->renderDepth)	{
					$this->renderDepth--;
				}
			}
		}


			// Return the imploded $out_array:
		if ($out_sheet>0)	{	// There were --div-- dividers around...

				// Create parts array for the tab menu:
			$parts = array();
			foreach($out_array as $idx => $sheetContent)	{
				$parts[] = array(
					'label' => $out_array_meta[$idx]['title'],
					'content' => '<table border="0" cellspacing="0" cellpadding="0" width="100%">'.
							implode('',$sheetContent).
						'</table>'
				);
			}

			return '
				<tr>
					<td colspan="2">
					'.$this->getDynTabMenu($parts, 'TCEforms:'.$table.':'.$row['uid']).'
					</td>
				</tr>';
		} else {	// Only one, so just implode:
			return implode('',$out_array[$out_sheet]);
		}
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
	function getListedFields($table,$row,$list)	{
		global $TCA;

		t3lib_div::loadTCA($table);
		if ($this->edit_showFieldHelp || $this->doLoadTableDescr($table))	{
			$GLOBALS['LANG']->loadSingleTableDescription($table);
		}

		$out='';
		$types_fieldConfig=t3lib_BEfunc::getTCAtypes($table,$row,1);

		$editFieldList=array_unique(t3lib_div::trimExplode(',',$list,1));
		foreach($editFieldList as $theFieldC)	{
			list($theField,$palFields) = split('\[|\]',$theFieldC);
			$theField = trim($theField);
			$palFields = trim($palFields);
			if ($TCA[$table]['columns'][$theField])	{
				$parts = t3lib_div::trimExplode(';',$types_fieldConfig[$theField]['origString']);
				$sField= $this->getSingleField($table,$theField,$row,$parts[1],0,$parts[3],0);	// Don't sent palette pointer - there are no options anyways for a field-list.
				$out.= $sField;
			} elseif($theField=='--div--')	{
				$out.=$this->getDivider();
			}
			if ($palFields)	{
				$out.=$this->getPaletteFields($table,$row,'','',implode(',',t3lib_div::trimExplode('|',$palFields,1)));
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
	function getPaletteFields($table,$row,$palette,$header='',$itemList='',$collapsedHeader='')	{
		global $TCA;
		if (!$this->doPrintPalette)	return '';

		$out='';
		$palParts=array();
		t3lib_div::loadTCA($table);

			// Getting excludeElements, if any.
		if (!is_array($this->excludeElements))	{
			$this->excludeElements = $this->getExcludeElements($table,$row,$this->getRTypeNum($table,$row));
		}

			// Render the palette TCEform elements.
		if ($TCA[$table] && (is_array($TCA[$table]['palettes'][$palette]) || $itemList))	{
			$itemList = $itemList?$itemList:$TCA[$table]['palettes'][$palette]['showitem'];
			if ($itemList)	{
				$fields = t3lib_div::trimExplode(',',$itemList,1);
				reset($fields);
				while(list(,$fieldInfo)=each($fields))	{
					$parts = t3lib_div::trimExplode(';',$fieldInfo);
					$theField = $parts[0];

					if (!in_array($theField,$this->excludeElements) && $TCA[$table]['columns'][$theField])	{
						$this->palFieldArr[$palette][] = $theField;
						if ($this->isPalettesCollapsed($table,$palette))	{
							$this->hiddenFieldListArr[] = $theField;
						}

						$part=$this->getSingleField($table,$theField,$row,$parts[1],1,'',$parts[2]);
						if (is_array($part))	{
							$palParts[]=$part;
						}
					}
				}
			}
		}
			// Put palette together if there are fields in it:
		if (count($palParts))	{
			if ($header)	{
				$out.=	$this->intoTemplate(array(
								'HEADER' => htmlspecialchars($header)
							),
							$this->palFieldTemplateHeader
						);
			}
			$out.=	$this->intoTemplate(array(
							'PALETTE' => $this->printPalette($palParts)
						),
						$this->palFieldTemplate
					);
		}
			// If a palette is collapsed (not shown in form, but in top frame instead) AND a collapse header string is given, then make that string a link to activate the palette.
		if ($this->isPalettesCollapsed($table,$palette) && $collapsedHeader)	{
			$pC=	$this->intoTemplate(array(
							'PALETTE' => $this->wrapOpenPalette('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/options.gif','width="18" height="16"').' border="0" title="'.htmlspecialchars($this->getLL('l_moreOptions')).'" align="top" alt="" /><strong>'.$collapsedHeader.'</strong>',$table,$row,$palette),
						),
						$this->palFieldTemplate
					);
			$out.=$pC;
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
	function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)	{
		global $TCA,$BE_USER;

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
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];	// Using "form_type" locally in this script

			// Now, check if this field is configured and editable (according to excludefields + other configuration)
		if (	is_array($PA['fieldConf']) &&
				(!$PA['fieldConf']['exclude'] || $BE_USER->check('non_exclude_fields',$table.':'.$field)) &&
				$PA['fieldConf']['config']['form_type']!='passthrough' &&
				($this->RTEenabled || !$PA['fieldConf']['config']['showIfRTE']) &&
				(!$PA['fieldConf']['displayCond'] || $this->isDisplayCondition($PA['fieldConf']['displayCond'],$row)) &&
				(!$TCA[$table]['ctrl']['languageField'] || strcmp($PA['fieldConf']['l10n_mode'],'exclude') || $row[$TCA[$table]['ctrl']['languageField']]<=0) &&
				(!$TCA[$table]['ctrl']['languageField'] || !$this->localizationMode || $this->localizationMode===$PA['fieldConf']['l10n_cat'])
			)	{

				// Fetching the TSconfig for the current table/field. This includes the $row which means that
			$PA['fieldTSConfig'] = $this->setTSconfig($table,$row,$field);

				// If the field is NOT disabled from TSconfig (which it could have been) then render it
			if (!$PA['fieldTSConfig']['disabled'])	{

					// Init variables:
				$PA['itemFormElName']=$this->prependFormFieldNames.'['.$table.']['.$row['uid'].']['.$field.']';		// Form field name
				$PA['itemFormElName_file']=$this->prependFormFieldNames_file.'['.$table.']['.$row['uid'].']['.$field.']';	// Form field name, in case of file uploads
				$PA['itemFormElValue']=$row[$field];		// The value to show in the form field.

					// Create a JavaScript code line which will ask the user to save/update the form due to changing the element. This is used for eg. "type" fields and others configured with "requestUpdate"
				if (
					($TCA[$table]['ctrl']['type'] && !strcmp($field,$TCA[$table]['ctrl']['type'])) ||
					($TCA[$table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$table]['ctrl']['requestUpdate'],$field))) {
					if($GLOBALS['BE_USER']->jsConfirmation(1))	{
						$alertMsgOnChange = 'if (confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					} else {
						$alertMsgOnChange = 'if (TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
					}
				} else {
					$alertMsgOnChange = '';
				}

					// Render as a hidden field?
				if (in_array($field,$this->hiddenFieldListArr))	{
					$this->hiddenFieldAccum[]='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
				} else {	// Render as a normal field:

						// If the field is NOT a palette field, then we might create an icon which links to a palette for the field, if one exists.
					if (!$PA['palette'])	{
						if ($PA['pal'] && $this->isPalettesCollapsed($table,$PA['pal']))	{
							list($thePalIcon,$palJSfunc) = $this->wrapOpenPalette('<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/options.gif','width="18" height="16"').' border="0" title="'.htmlspecialchars($this->getLL('l_moreOptions')).'" alt="" />',$table,$row,$PA['pal'],1);
						} else {
							$thePalIcon = '';
							$palJSfunc = '';
						}
					}
						// onFocus attribute to add to the field:
					$PA['onFocus'] = ($palJSfunc && !$BE_USER->uc['dontShowPalettesOnFocusInAB']) ? ' onfocus="'.htmlspecialchars($palJSfunc).'"' : '';

						// Find item
					$item='';
					$PA['label'] = $PA['altName'] ? $PA['altName'] : $PA['fieldConf']['label'];
					$PA['label'] = $this->sL($PA['label']);
						// JavaScript code for event handlers:
					$PA['fieldChangeFunc']=array();
					$PA['fieldChangeFunc']['TBE_EDITOR_fieldChanged'] = "TBE_EDITOR_fieldChanged('".$table."','".$row['uid']."','".$field."','".$PA['itemFormElName']."');";
					$PA['fieldChangeFunc']['alert']=$alertMsgOnChange;

						// Based on the type of the item, call a render function:
					$item = $this->getSingleField_SW($table,$field,$row,$PA);

						// Add language + diff
					$item = $this->renderDefaultLanguageContent($table,$field,$row,$item);
					$item = $this->renderDefaultLanguageDiff($table,$field,$row,$item);

						// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$PA['label'] = t3lib_div::deHSCentities(htmlspecialchars($PA['label']));
					if (t3lib_div::testInt($row['uid']) && $PA['fieldTSConfig']['linkTitleToSelf'])	{
						$lTTS_url = $this->backPath.'alt_doc.php?edit['.$table.']['.$row['uid'].']=edit&columnsOnly='.$field.
									($PA['fieldTSConfig']['linkTitleToSelf.']['returnUrl']?'&returnUrl='.rawurlencode($this->thisReturnUrl()):'');
						$PA['label'] = '<a href="'.htmlspecialchars($lTTS_url).'">'.$PA['label'].'</a>';
					}

						// Create output value:
					if ($PA['fieldConf']['config']['form_type']=='user' && $PA['fieldConf']['config']['noTableWrapping'])	{
						$out = $item;
					} elseif ($PA['palette'])	{
							// Array:
						$out=array(
							'NAME'=>$PA['label'],
							'ID'=>$row['uid'],
							'FIELD'=>$field,
							'TABLE'=>$table,
							'ITEM'=>$item,
							'HELP_ICON' => $this->helpTextIcon($table,$field,1)
						);
						$out = $this->addUserTemplateMarkers($out,$table,$field,$row,$PA);
					} else {
							// String:
						$out=array(
							'NAME'=>$PA['label'],
							'ITEM'=>$item,
							'TABLE'=>$table,
							'ID'=>$row['uid'],
							'HELP_ICON'=>$this->helpTextIcon($table,$field),
							'HELP_TEXT'=>$this->helpText($table,$field),
							'PAL_LINK_ICON'=>$thePalIcon,
							'FIELD'=>$field
						);
						$out = $this->addUserTemplateMarkers($out,$table,$field,$row,$PA);
							// String:
						$out=$this->intoTemplate($out);
					}
				}
			} else $this->commentMessages[]=$this->prependFormFieldNames.'['.$table.']['.$row['uid'].']['.$field.']: Disabled by TSconfig';
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
	function getSingleField_SW($table,$field,$row,&$PA)	{
		$PA['fieldConf']['config']['form_type'] = $PA['fieldConf']['config']['form_type'] ? $PA['fieldConf']['config']['form_type'] : $PA['fieldConf']['config']['type'];	// Using "form_type" locally in this script

		switch($PA['fieldConf']['config']['form_type'])	{
			case 'input':
				$item = $this->getSingleField_typeInput($table,$field,$row,$PA);
			break;
			case 'text':
				$item = $this->getSingleField_typeText($table,$field,$row,$PA);
			break;
			case 'check':
				$item = $this->getSingleField_typeCheck($table,$field,$row,$PA);
			break;
			case 'radio':
				$item = $this->getSingleField_typeRadio($table,$field,$row,$PA);
			break;
			case 'select':
				$item = $this->getSingleField_typeSelect($table,$field,$row,$PA);
			break;
			case 'group':
				$item = $this->getSingleField_typeGroup($table,$field,$row,$PA);
			break;
			case 'none':
				$item = $this->getSingleField_typeNone($table,$field,$row,$PA);
			break;
			case 'user':
				$item = $this->getSingleField_typeUser($table,$field,$row,$PA);
			break;
			case 'flex':
				$item = $this->getSingleField_typeFlex($table,$field,$row,$PA);
			break;
			default:
				$item = $this->getSingleField_typeUnknown($table,$field,$row,$PA);
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
	function getSingleField_typeInput($table,$field,$row,&$PA)	{
		// typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue)
		// typo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off)

		$config = $PA['fieldConf']['config'];

#		$specConf = $this->getSpecConfForField($table,$row,$field);
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);
		$size = t3lib_div::intInRange($config['size']?$config['size']:30,5,$this->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'],1);


		if($this->renderReadonly || $config['readonly'])  {
			$itemFormElValue = $PA['itemFormElValue'];
			if (in_array('date',$evalList))	{
				$config['format'] = 'date';
			} elseif (in_array('date',$evalList))	{
				$config['format'] = 'date';
			} elseif (in_array('datetime',$evalList))	{
				$config['format'] = 'datetime';
			} elseif (in_array('time',$evalList))	{
				$config['format'] = 'time';
			}
			if (in_array('password',$evalList))	{
				$itemFormElValue = $itemFormElValue ? '*********' : '';
			}
			return $this->getSingleField_typeNone_render($config, $itemFormElValue);
		}

		if (in_array('required',$evalList))	{
			$this->requiredFields[$table.'_'.$row['uid'].'_'.$field]=$PA['itemFormElName'];
		}

		$paramsList = "'".$PA['itemFormElName']."','".implode(',',$evalList)."','".trim($config['is_in'])."',".(isset($config['checkbox'])?1:0).",'".$config['checkbox']."'";
		if (isset($config['checkbox']))	{
				// Setting default "click-checkbox" values for eval types "date" and "datetime":
			$thisMidnight = mktime(0,0,0);
			$checkSetValue = in_array('date',$evalList) ? $thisMidnight : '';
			$checkSetValue = in_array('datetime',$evalList) ? time() : $checkSetValue;

			$cOnClick = 'typo3FormFieldGet('.$paramsList.',1,\''.$checkSetValue.'\');'.implode('',$PA['fieldChangeFunc']);
			$item.='<input type="checkbox" name="'.$PA['itemFormElName'].'_cb" onclick="'.htmlspecialchars($cOnClick).'" />';
		}

		$PA['fieldChangeFunc'] = array_merge(array('typo3FormFieldGet'=>'typo3FormFieldGet('.$paramsList.');'), $PA['fieldChangeFunc']);
		$mLgd = ($config['max']?$config['max']:256);
		$iOnChange = implode('',$PA['fieldChangeFunc']);
		$item.='<input type="text" name="'.$PA['itemFormElName'].'_hr" value=""'.$this->formWidth($size).' maxlength="'.$mLgd.'" onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].' />';	// This is the EDITABLE form field.
		$item.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';			// This is the ACTUAL form field - values from the EDITABLE field must be transferred to this field which is the one that is written to the database.
		$this->extJSCODE.='typo3FormFieldSet('.$paramsList.');';

			// going through all custom evaluations configured for this field
		foreach ($evalList as $evalData) {
			if (substr($evalData, 0, 3) == 'tx_')	{
				$evalObj = t3lib_div::getUserObj($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['tce']['formevals'][$evalData].':&'.$evalData);
				if(is_object($evalObj) && method_exists($evalObj, 'returnFieldJS'))	{
					$this->extJSCODE .= "\n\nfunction ".$evalData."(value) {\n".$evalObj->returnFieldJS()."\n}\n";
				}
			}
		}

			// Creating an alternative item without the JavaScript handlers.
		$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'_hr" value="" />';
		$altItem.= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';

			// Wrap a wizard around the item?
		$item= $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'].'_hr',$specConf);

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
	function getSingleField_typeText($table,$field,$row,&$PA)	{

			// Init config:
		$config = $PA['fieldConf']['config'];

		if($this->renderReadonly || $config['readonly'])  {
			return $this->getSingleField_typeNone_render($config, $PA['itemFormElValue']);
		}

			// Setting columns number:
		$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);

			// Setting number of rows:
		$origRows = $rows = t3lib_div::intInRange($config['rows'] ? $config['rows'] : 5, 1, 20);
		if (strlen($PA['itemFormElValue']) > $this->charsPerRow*2)	{
			$cols = $this->maxTextareaWidth;
			$rows = t3lib_div::intInRange(round(strlen($PA['itemFormElValue'])/$this->charsPerRow), count(explode(chr(10),$PA['itemFormElValue'])), 20);
			if ($rows<$origRows)	$rows = $origRows;
		}

			// Init RTE vars:
		$RTEwasLoaded = 0;				// Set true, if the RTE is loaded; If not a normal textarea is shown.
		$RTEwouldHaveBeenLoaded = 0;	// Set true, if the RTE would have been loaded if it wasn't for the disable-RTE flag in the bottom of the page...

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. Traditionally, this is where RTE configuration has been found.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Setting up the altItem form field, which is a hidden field containing the value
		$altItem = '<input type="hidden" name="'.htmlspecialchars($PA['itemFormElName']).'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';

			// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
		if ($this->RTEenabled) {
			$p = t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']]))	{	// If the field is configured for RTE and if any flag-field is not set to disable it.
				list($tscPID,$thePidValue) = $this->getTSCpid($table,$row['uid'],$row['pid']);

					// If the pid-value is not negative (that is, a pid could NOT be fetched)
				if ($thePidValue >= 0)	{
					$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($tscPID));
					$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);
					$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$table,$field,$RTEtypeVal);

					if (!$thisConfig['disabled'])	{
						if (!$this->disableRTE)	{
							$this->RTEcounter++;

								// Find alternative relative path for RTE images/links:
							$eFile = t3lib_parsehtml_proc::evalWriteFile($specConf['static_write'], $row);
							$RTErelPath = is_array($eFile) ? dirname($eFile['relEditFile']) : '';

								// Get RTE object, draw form and set flag:
							$RTEobj = &t3lib_BEfunc::RTEgetObj();
							$item = $RTEobj->drawRTE($this,$table,$field,$row,$PA,$specConf,$thisConfig,$RTEtypeVal,$RTErelPath,$thePidValue);

								// Wizard:
							$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,1);

							$RTEwasLoaded = 1;
						} else {
							$RTEwouldHaveBeenLoaded = 1;
							$this->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
						}
					} else $this->commentMessages[] = $PA['itemFormElName'].': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
				} else $this->commentMessages[] = $PA['itemFormElName'].': PID value could NOT be fetched. Rare error, normally with new records.';
			} else {
				if (!isset($specConf['richtext']))	$this->commentMessages[] = $PA['itemFormElName'].': RTE was not configured for this field in TCA-types';
				if (!(!$p['flag'] || !$row[$p['flag']]))	 $this->commentMessages[] = $PA['itemFormElName'].': Field-flag ('.$PA['flag'].') has been set to disable RTE!';
			}
		}

			// Display ordinary field if RTE was not loaded.
		if (!$RTEwasLoaded) {
			if ($specConf['rte_only'])	{	// Show message, if no RTE (field can only be edited with RTE!)
				$item = '<p><em>'.htmlspecialchars($this->getLL('l_noRTEfound')).'</em></p>';
			} else {
				if ($specConf['nowrap'])	{
					$wrap = 'off';
				} else {
					$wrap = ($config['wrap'] ? $config['wrap'] : 'virtual');
				}

				$classes = array();
				if ($specConf['fixed-font'])	{ $classes[] = 'fixed-font'; }
				if ($specConf['enable-tab'])	{ $classes[] = 'enable-tab'; }

				if (count($classes))	{
					$class = ' class="'.implode(' ',$classes).'"';
				} else $class='';

				$iOnChange = implode('',$PA['fieldChangeFunc']);
				$item.= '
							<textarea name="'.$PA['itemFormElName'].'"'.$this->formWidthText($cols,$wrap).$class.' rows="'.$rows.'" wrap="'.$wrap.'" onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].'>'.
							t3lib_div::formatForTextarea($PA['itemFormElValue']).
							'</textarea>';
				$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,$RTEwouldHaveBeenLoaded);
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
	function getSingleField_typeCheck($table,$field,$row,&$PA)	{
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traversing the array of items:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

		if (!count($selItems))	{
			$selItems[]=array('','');
		}
		$thisValue = intval($PA['itemFormElValue']);

		$cols = intval($config['cols']);
		if ($cols > 1)	{
			$item.= '<table border="0" cellspacing="0" cellpadding="0" class="typo3-TCEforms-checkboxArray">';
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				if(!($c%$cols))	{ $item.='<tr>'; }
				$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$item.= '<td nowrap="nowrap">'.
						'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$disabled.' />'.
						$this->wrapLabels(htmlspecialchars($p[0]).'&nbsp;').
						'</td>';
				if(($c%$cols)+1==$cols)	{$item.='</tr>';}
			}
			if ($c%$cols)	{
				$rest=$cols-($c%$cols);
				for ($c=0;$c<$rest;$c++) {
					$item.= '<td></td>';
				}
				if ($c>0)	{ $item.= '</tr>'; }
			}
			$item.= '</table>';
		} else {
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$item.= ($c>0?'<br />':'').
						'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$PA['onFocus'].$disabled.' />'.
						htmlspecialchars($p[0]);
			}
		}
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($thisValue).'" />';
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
	function getSingleField_typeRadio($table,$field,$row,&$PA)	{
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Get items for the array:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			// Traverse the items, making the form elements:
		for ($c=0;$c<count($selItems);$c++) {
			$p = $selItems[$c];
			$rOnClick = implode('',$PA['fieldChangeFunc']);
			$rChecked = (!strcmp($p[1],$PA['itemFormElValue'])?' checked="checked"':'');
			$item.= '<input type="radio"'.$this->insertDefStyle('radio').' name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($p[1]).'" onclick="'.htmlspecialchars($rOnClick).'"'.$rChecked.$PA['onFocus'].$disabled.' />'.
					htmlspecialchars($p[0]).
					'<br />';
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
	function getSingleField_typeSelect($table,$field,$row,&$PA)	{
		global $TCA;

			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Getting the selector box items from the system
		$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($PA['fieldConf']),$PA['fieldConf'],$this->setTSconfig($table,$row),$field);
		$selItems = $this->addItems($selItems,$PA['fieldTSConfig']['addItems.']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			// Possibly remove some items:
		$removeItems = t3lib_div::trimExplode(',',$PA['fieldTSConfig']['removeItems'],1);
		foreach($selItems as $tk => $p)	{

				// Checking languages and authMode:
			$languageDeny = $TCA[$table]['ctrl']['languageField'] && !strcmp($TCA[$table]['ctrl']['languageField'], $field) && !$GLOBALS['BE_USER']->checkLanguageAccess($p[1]);
			$authModeDeny = $config['form_type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table,$field,$p[1],$config['authMode']);
			if (in_array($p[1],$removeItems) || $languageDeny || $authModeDeny)	{
				unset($selItems[$tk]);
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
				$selItems[$tk][0]=$this->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]);
			}

				// Removing doktypes with no access:
			if ($table.'.'.$field == 'pages.doktype')	{
				if (!($GLOBALS['BE_USER']->isAdmin() || t3lib_div::inList($GLOBALS['BE_USER']->groupData['pagetypes_select'],$p[1])))	{
					unset($selItems[$tk]);
				}
			}
		}

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ '.$this->getLL('l_noMatchingValue').' ]';

			// Prepare some values:
		$maxitems = intval($config['maxitems']);

			// If a SINGLE selector box...
		if ($maxitems<=1)	{
			$item = $this->getSingleField_typeSelect_single($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} elseif (!strcmp($config['renderMode'],'checkbox'))	{	// Checkbox renderMode
			$item = $this->getSingleField_typeSelect_checkbox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} elseif (!strcmp($config['renderMode'],'singlebox'))	{	// Single selector box renderMode
			$item = $this->getSingleField_typeSelect_singlebox($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		} else {	// Traditional multiple selector box:
			$item = $this->getSingleField_typeSelect_multiple($table,$field,$row,$PA,$config,$selItems,$nMV_label);
		}

			// Wizards:
		if (!$disabled) {
			$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
			$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);
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
	function getSingleField_typeSelect_single($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{

			// Initialization:
		$c = 0;
		$sI = 0;
		$noMatchingValue = 1;
		$opt = array();
		$selicons = array();
		$onlySelectedIconShown = 0;
		$size = intval($config['size']);

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
			$onlySelectedIconShown = 1;
		}

			// Icon configuration:
		if ($config['suppress_icons']=='IF_VALUE_FALSE')	{
			$suppressIcons = !$PA['itemFormElValue'] ? 1 : 0;
		} elseif ($config['suppress_icons']=='ONLY_SELECTED')	{
			$suppressIcons=0;
			$onlySelectedIconShown=1;
		} elseif ($config['suppress_icons']) 	{
			$suppressIcons = 1;
		} else $suppressIcons = 0;

			// Traverse the Array of selector box items:
		foreach($selItems as $p)	{
			$sM = (!strcmp($PA['itemFormElValue'],$p[1])?' selected="selected"':'');
			if ($sM)	{
				$sI = $c;
				$noMatchingValue = 0;
			}

				// Getting style attribute value (for icons):
			if ($config['iconsInOptionTags'])	{
				$styleAttrValue = $this->optionTagStyle($p[2]);
			}

				// Compiling the <option> tag:
			$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
						$sM.
						($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
						(!strcmp($p[1],'--div--') ? ' class="c-divider"' : '').
						'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>';

				// If there is an icon for the selector box (rendered in table under)...:
			if ($p[2] && !$suppressIcons && (!$onlySelectedIconShown || $sM))	{
				list($selIconFile,$selIconInfo)=$this->getIcon($p[2]);
				$iOnClick = $this->elName($PA['itemFormElName']).'.selectedIndex='.$c.'; '.implode('',$PA['fieldChangeFunc']).$this->blur().'return false;';
				$selicons[]=array(
					(!$onlySelectedIconShown ? '<a href="#" onclick="'.htmlspecialchars($iOnClick).'">' : '').
					'<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" title="'.htmlspecialchars($p[0]).'" alt="'.htmlspecialchars($p[0]).'" />'.
					(!$onlySelectedIconShown ? '</a>' : ''),
					$c,$sM);
			}
			$c++;
		}

			// No-matching-value:
		if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			$nMV_label = @sprintf($nMV_label, $PA['itemFormElValue']);
			$opt[]= '<option value="'.htmlspecialchars($PA['itemFormElValue']).'" selected="selected">'.htmlspecialchars($nMV_label).'</option>';
		}

			// Create item form fields:
		$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex='.$sI.';} '.implode('',$PA['fieldChangeFunc']);
		if(!$disabled) {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_selIconVal" value="'.htmlspecialchars($sI).'" />';	// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
		}
		$item.= '<select name="'.$PA['itemFormElName'].'"'.
					$this->insertDefStyle('select').
					($size?' size="'.$size.'"':'').
					' onchange="'.htmlspecialchars($sOnChange).'"'.
					$PA['onFocus'].$disabled.'>';
		$item.= implode('',$opt);
		$item.= '</select>';

			// Create icon table:
		if (count($selicons))	{
			$item.='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-selectIcons">';
			$selicon_cols = intval($config['selicon_cols']);
			if (!$selicon_cols)	$selicon_cols=count($selicons);
			$sR = ceil(count($selicons)/$selicon_cols);
			$selicons = array_pad($selicons,$sR*$selicon_cols,'');
			for($sa=0;$sa<$sR;$sa++)	{
				$item.='<tr>';
				for($sb=0;$sb<$selicon_cols;$sb++)	{
					$sk=($sa*$selicon_cols+$sb);
					$imgN = 'selIcon_'.$table.'_'.$row['uid'].'_'.$field.'_'.$selicons[$sk][1];
					$imgS = ($selicons[$sk][2]?$this->backPath.'gfx/content_selected.gif':'clear.gif');
					$item.='<td><img name="'.htmlspecialchars($imgN).'" src="'.$imgS.'" width="7" height="10" alt="" /></td>';
					$item.='<td>'.$selicons[$sk][0].'</td>';
				}
				$item.='</tr>';
			}
			$item.='</table>';
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
	function getSingleField_typeSelect_checkbox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$tRows = array();
		$c=0;
		if (!$disabled) {
			$sOnChange = implode('',$PA['fieldChangeFunc']);
			$setAll = array();	// Used to accumulate the JS needed to restore the original selection.
			foreach($selItems as $p)	{
					// Non-selectable element:
				if (!strcmp($p[1],'--div--'))	{
					if (count($setAll))	{
							$tRows[] = '
								<tr>
									<td colspan="2">'.
									'<a href="#" onclick="'.htmlspecialchars(implode('',$setAll).' return false;').'">'.
									htmlspecialchars($this->getLL('l_setAllCheckboxes')).
									'</a></td>
								</tr>';
							$setAll = array();
					}

					$tRows[] = '
						<tr class="c-header">
							<td colspan="2">'.htmlspecialchars($p[0]).'</td>
						</tr>';
				} else {
						// Selected or not by default:
					$sM = '';
					if (isset($itemArray[$p[1]]))	{
						$sM = ' checked="checked"';
						unset($itemArray[$p[1]]);
					}

						// Icon:
					$selIconFile = '';
					if ($p[2])	{
						list($selIconFile,$selIconInfo) = $this->getIcon($p[2]);
					}

						// Compile row:
					$onClickCell = $this->elName($PA['itemFormElName'].'['.$c.']').'.checked=!'.$this->elName($PA['itemFormElName'].'['.$c.']').'.checked;';
					$onClick = 'this.attributes.getNamedItem("class").nodeValue = '.$this->elName($PA['itemFormElName'].'['.$c.']').'.checked ? "c-selectedItem" : "";';
					$setAll[] = $this->elName($PA['itemFormElName'].'['.$c.']').'.checked=1;';
					$tRows[] = '
						<tr class="'.($sM ? 'c-selectedItem' : '').'" onclick="'.htmlspecialchars($onClick).'" style="cursor: pointer;">
							<td><input type="checkbox" name="'.htmlspecialchars($PA['itemFormElName'].'['.$c.']').'" value="'.htmlspecialchars($p[1]).'"'.$sM.' onclick="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].' /></td>
							<td class="c-labelCell" onclick="'.htmlspecialchars($onClickCell).'">'.
								($selIconFile ? '<img src="'.$selIconFile.'" '.$selIconInfo[3].' vspace="2" border="0" class="absmiddle" style="margin-right: 4px;" alt="" />' : '').
								t3lib_div::deHSCentities(htmlspecialchars($p[0])).
								(strcmp($p[3],'') ? '<br/><p class="c-descr">'.nl2br(trim(htmlspecialchars($p[3]))).'</p>' : '').
								'</td>
						</tr>';
					$c++;
				}
			}

				// Remaining checkboxes will get their set-all link:
			if (count($setAll))	{
					$tRows[] = '
						<tr>
							<td colspan="2">'.
							'<a href="#" onclick="'.htmlspecialchars(implode('',$setAll).' return false;').'">'.
							htmlspecialchars($this->getLL('l_setAllCheckboxes')).
							'</a></td>
						</tr>';
			}
		}

			// Remaining values (invalid):
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <checkboxes> tag:
				array_unshift($tRows,'
						<tr class="c-invalidItem">
							<td><input type="checkbox" name="'.htmlspecialchars($PA['itemFormElName'].'['.$c.']').'" value="'.htmlspecialchars($theNoMatchValue).'" checked="checked" onclick="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].$disabled.' /></td>
							<td class="c-labelCell">'.
								t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))).
								'</td>
						</tr>');
				$c++;
			}
		}

			// Add an empty hidden field which will send a blank value if all items are unselected.
		$item.='<input type="hidden" name="'.htmlspecialchars($PA['itemFormElName']).'" value="" />';

			// Implode rows in table:
		$item.= '
			<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-select-checkbox">'.
				implode('',$tRows).'
			</table>
			';

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
	function getSingleField_typeSelect_singlebox($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{

			// Get values in an array (and make unique, which is fine because there can be no duplicates anyway):
		$itemArray = array_flip($this->extractValuesOnlyFromValueLabelList($PA['itemFormElValue']));

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Traverse the Array of selector box items:
		$opt = array();
		$restoreCmd = array();	// Used to accumulate the JS needed to restore the original selection.
		$c = 0;
		foreach($selItems as $p)	{
				// Selected or not by default:
			$sM = '';
			if (isset($itemArray[$p[1]]))	{
				$sM = ' selected="selected"';
				$restoreCmd[] = $this->elName($PA['itemFormElName'].'[]').'.options['.$c.'].selected=1;';
				unset($itemArray[$p[1]]);
			}

				// Non-selectable element:
			$nonSel = '';
			if (!strcmp($p[1],'--div--'))	{
				$nonSel = ' onclick="this.selected=0;" class="c-divider"';
			}

				// Icon style for option tag:
			if ($config['iconsInOptionTags']) {
				$styleAttrValue = $this->optionTagStyle($p[2]);
			}

				// Compile <option> tag:
			$opt[] = '<option value="'.htmlspecialchars($p[1]).'"'.
						$sM.
						$nonSel.
						($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
						'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>';
			$c++;
		}

			// Remaining values:
		if (count($itemArray) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
			foreach($itemArray as $theNoMatchValue => $temp)	{
					// Compile <option> tag:
				array_unshift($opt,'<option value="'.htmlspecialchars($theNoMatchValue).'" selected="selected">'.t3lib_div::deHSCentities(htmlspecialchars(@sprintf($nMV_label, $theNoMatchValue))).'</option>');
			}
		}

			// Compile selector box:
		$sOnChange = implode('',$PA['fieldChangeFunc']);
		$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"';
		$size = intval($config['size']);
		$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($selItems)+1,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
		$selectBox = '<select name="'.$PA['itemFormElName'].'[]"'.
						$this->insertDefStyle('select').
						($size ? ' size="'.$size.'"' : '').
						' multiple="multiple" onchange="'.htmlspecialchars($sOnChange).'"'.
						$PA['onFocus'].
						$selector_itemListStyle.
						$disabled.'>
						'.
					implode('
						',$opt).'
					</select>';

			// Add an empty hidden field which will send a blank value if all items are unselected.
		if (!$disabled) {
			$item.='<input type="hidden" name="'.htmlspecialchars($PA['itemFormElName']).'" value="" />';
		}

			// Put it all into a table:
		$item.= '
			<table border="0" cellspacing="0" cellpadding="0" width="1" class="typo3-TCEforms-select-singlebox">
				<tr>
					<td>
					'.$selectBox.'
					<br/>
					<em>'.
						htmlspecialchars($this->getLL('l_holdDownCTRL')).
						'</em>
					</td>
					<td valign="top">
					<a href="#" onclick="'.htmlspecialchars($this->elName($PA['itemFormElName'].'[]').'.selectedIndex=-1;'.implode('',$restoreCmd).' return false;').'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/history.gif','width="13" height="12"').' title="'.htmlspecialchars($this->getLL('l_revertSelection')).'" alt="" />'.
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
	function getSingleField_typeSelect_multiple($table,$field,$row,&$PA,$config,$selItems,$nMV_label)	{

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Setting this hidden field (as a flag that JavaScript can read out)
		if (!$disabled) {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'" />';
		}

			// Set max and min items:
		$maxitems = t3lib_div::intInRange($config['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($config['minitems'],0);

			// Register the required number of elements:
		$this->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);

			// Get "removeItems":
		$removeItems = t3lib_div::trimExplode(',',$PA['fieldTSConfig']['removeItems'],1);

			// Perform modification of the selected items array:
		$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|',$tv,2);
			$evalValue = rawurldecode($tvP[0]);
			$isRemoved = in_array($evalValue,$removeItems)  || ($config['form_type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode($table,$field,$evalValue,$config['authMode']));
			if ($isRemoved && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
				$tvP[1] = rawurlencode(@sprintf($nMV_label, $evalValue));
			} elseif (isset($PA['fieldTSConfig']['altLabels.'][$evalValue])) {
				$tvP[1] = rawurlencode($this->sL($PA['fieldTSConfig']['altLabels.'][$evalValue]));
			}
			$itemArray[$tk] = implode('|',$tvP);
		}
		$itemsToSelect = '';

		if(!$disabled) {
				// Create option tags:
			$opt = array();
			$styleAttrValue = '';
			foreach($selItems as $p)	{
				if ($config['iconsInOptionTags'])	{
					$styleAttrValue = $this->optionTagStyle($p[2]);
				}
				$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.
								($styleAttrValue ? ' style="'.htmlspecialchars($styleAttrValue).'"' : '').
								'>'.htmlspecialchars($p[0]).'</option>';
			}

				// Put together the selector box:
			$selector_itemListStyle = isset($config['itemListStyle']) ? ' style="'.htmlspecialchars($config['itemListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"';
			$size = intval($config['size']);
			$size = $config['autoSizeMax'] ? t3lib_div::intInRange(count($itemArray)+1,t3lib_div::intInRange($size,1),$config['autoSizeMax']) : $size;
			$sOnChange = 'setFormValueFromBrowseWin(\''.$PA['itemFormElName'].'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); '.implode('',$PA['fieldChangeFunc']);
			$itemsToSelect = '
				<select name="'.$PA['itemFormElName'].'_sel"'.
							$this->insertDefStyle('select').
							($size ? ' size="'.$size.'"' : '').
							' onchange="'.htmlspecialchars($sOnChange).'"'.
							$PA['onFocus'].
							$selector_itemListStyle.'>
					'.implode('
					',$opt).'
				</select>';
		}

			// Pass to "dbFileIcons" function:
		$params = array(
			'size' => $size,
			'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
			'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
			'dontShowMoveIcons' => ($maxitems<=1),
			'maxitems' => $maxitems,
			'info' => '',
			'headers' => array(
				'selector' => $this->getLL('l_selected').':<br />',
				'items' => $this->getLL('l_items').':<br />'
			),
			'noBrowser' => 1,
			'thumbnails' => $itemsToSelect,
			'readonly' => $disabled
		);
		$item.= $this->dbFileIcons($PA['itemFormElName'],'','',$itemArray,'',$params,$PA['onFocus']);

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
	function getSingleField_typeGroup($table,$field,$row,&$PA)	{
			// Init:
		$config = $PA['fieldConf']['config'];
		$internal_type = $config['internal_type'];
		$show_thumbs = $config['show_thumbs'];
		$size = intval($config['size']);
		$maxitems = t3lib_div::intInRange($config['maxitems'],0);
		if (!$maxitems)	$maxitems=100000;
		$minitems = t3lib_div::intInRange($config['minitems'],0);
		$allowed = $config['allowed'];
		$disallowed = $config['disallowed'];

		$disabled = '';
		if($this->renderReadonly || $config['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

		$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'"'.$disabled.' />';
		$this->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);
		$info='';

			// "Extra" configuration; Returns configuration for the field based on settings found in the "types" fieldlist. See http://typo3.org/documentation/document-library/doc_core_api/Wizards_Configuratio/.
		$specConf = $this->getSpecConfFromString($PA['extra'], $PA['fieldConf']['defaultExtras']);

			// Acting according to either "file" or "db" type:
		switch((string)$config['internal_type'])	{
			case 'file':	// If the element is of the internal type "file":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!count($tempFT))	{$info.='*';}
				foreach($tempFT as $ext)	{
					if ($ext)	{
						$info.=strtoupper($ext).' ';
					}
				}
					// Creating string, showing disallowed types:
				$tempFT_dis = t3lib_div::trimExplode(',',$disallowed,1);
				if (count($tempFT_dis))	{$info.='<br />';}
				foreach($tempFT_dis as $ext)	{
					if ($ext)	{
						$info.='-'.strtoupper($ext).' ';
					}
				}

					// Making the array of file items:
				$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);

					// Showing thumbnails:
				$thumbsnail = '';
				if ($show_thumbs)	{
					$imgs = array();
					foreach($itemArray as $imgRead)	{
						$imgP = explode('|',$imgRead);

						$rowCopy = array();
						$rowCopy[$field] = $imgP[0];

							// Icon + clickmenu:
						$absFilePath = t3lib_div::getFileAbsFileName($config['uploadfolder'].'/'.$imgP[0]);

						$fI = pathinfo($imgP[0]);
						$fileIcon = t3lib_BEfunc::getFileIcon(strtolower($fI['extension']));
						$fileIcon = '<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/fileicons/'.$fileIcon,'width="18" height="16"').' class="absmiddle" title="'.htmlspecialchars($fI['basename'].($absFilePath && @is_file($absFilePath) ? ' ('.t3lib_div::formatSize(filesize($absFilePath)).'bytes)' : ' - FILE NOT FOUND!')).'" alt="" />';

						$imgs[] = '<span class="nobr">'.t3lib_BEfunc::thumbCode($rowCopy,$table,$field,$this->backPath,'thumbs.php',$config['uploadfolder'],0,' align="middle"').
									($absFilePath ? $this->getClickMenu($fileIcon, $absFilePath) : $fileIcon).
									$imgP[0].
									'</span>';
					}
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readonly' => $disabled
				);
				$item.= $this->dbFileIcons($PA['itemFormElName'],'file',implode(',',$tempFT),$itemArray,'',$params,$PA['onFocus']);

				if(!$disabled) {
						// Adding the upload field:
					if ($this->edit_docModuleUpload)	$item.='<input type="file" name="'.$PA['itemFormElName_file'].'"'.$this->formWidth().' size="60" />';
				}
			break;
			case 'db':	// If the element is of the internal type "db":

					// Creating string showing allowed types:
				$tempFT = t3lib_div::trimExplode(',',$allowed,1);
				if (!strcmp(trim($tempFT[0]),'*'))	{
					$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
							htmlspecialchars($this->getLL('l_allTables')).
							'</span><br />';
				} else {
					while(list(,$theT)=each($tempFT))	{
						if ($theT)	{
							$info.='<span class="nobr">&nbsp;&nbsp;&nbsp;&nbsp;'.
									t3lib_iconWorks::getIconImage($theT,array(),$this->backPath,'align="top"').
									htmlspecialchars($this->sL($GLOBALS['TCA'][$theT]['ctrl']['title'])).
									'</span><br />';
						}
					}
				}

				$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
				$itemArray = array();
				$imgs = array();

					// Thumbnails:
				$temp_itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
				foreach($temp_itemArray as $dbRead)	{
					$recordParts = explode('|',$dbRead);
					list($this_table,$this_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
					$itemArray[] = array('table'=>$this_table, 'id'=>$this_uid);
					if (!$disabled && $show_thumbs)	{
						$rr = t3lib_BEfunc::getRecordWSOL($this_table,$this_uid);
						$imgs[] = '<span class="nobr">'.
								$this->getClickMenu(t3lib_iconWorks::getIconImage($this_table,$rr,$this->backPath,'align="top" title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'],$perms_clause,15)).' [UID: '.$rr['uid'].']"'),$this_table, $this_uid).
								'&nbsp;'.
								htmlspecialchars(t3lib_div::fixed_lgd_cs($this->noTitle($rr[$GLOBALS['TCA'][$this_table]['ctrl']['label']],array('<em>','</em>')),$this->titleLen)).' <span class="typo3-dimmed"><em>['.$rr['uid'].']</em></span>'.
								'</span>';
					}
				}
				$thumbsnail='';
				if (!$disabled && $show_thumbs)	{
					$thumbsnail = implode('<br />',$imgs);
				}

					// Creating the element:
				$params = array(
					'size' => $size,
					'dontShowMoveIcons' => ($maxitems<=1),
					'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
					'maxitems' => $maxitems,
					'style' => isset($config['selectedListStyle']) ? ' style="'.htmlspecialchars($config['selectedListStyle']).'"' : ' style="'.$this->defaultMultipleSelectorStyle.'"',
					'info' => $info,
					'thumbnails' => $thumbsnail,
					'readonly' => $disabled
				);
				$item.= $this->dbFileIcons($PA['itemFormElName'],'db',implode(',',$tempFT),$itemArray,'',$params,$PA['onFocus']);

			break;
		}

			// Wizards:
		$altItem = '<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
		if (!$disabled) {
			$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);
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
	function getSingleField_typeNone($table,$field,$row,&$PA)	{
			// Init:
		$config = $PA['fieldConf']['config'];
		$itemValue = $PA['itemFormElValue'];

		return $this->getSingleField_typeNone_render($config,$itemValue);
	}

	/**
	 * HTML rendering of a value which is not editable.
	 *
	 * @param	array		Configuration for the display
	 * @param	string		The value to display
	 * @return	string		The HTML code for the display
	 * @see getSingleField_typeNone();
	 */
	function getSingleField_typeNone_render($config,$itemValue)	{

				// is colorScheme[0] the right value?
		$divStyle = 'border:solid 1px '.t3lib_div::modifyHTMLColorAll($this->colorScheme[0],-30).';'.$this->defStyle.$this->formElStyle('none').' background-color: '.$this->colorScheme[0].'; padding-left:1px;color:#555;';

		if ($config['format'])	{
			$itemValue = $this->formatValue($config, $itemValue);
		}

		$rows = intval($config['rows']);
		if ($rows > 1) {
			if(!$config['pass_content']) {
				$itemValue = nl2br(htmlspecialchars($itemValue));
			}
				// like textarea
			$cols = t3lib_div::intInRange($config['cols'] ? $config['cols'] : 30, 5, $this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = $rows = t3lib_div::intInRange($rows, 1, 20);
				if (strlen($itemValue)>$this->charsPerRow*2)	{
					$cols = $this->maxTextareaWidth;
					$rows = t3lib_div::intInRange(round(strlen($itemValue)/$this->charsPerRow),count(explode(chr(10),$itemValue)),20);
					if ($rows<$origRows)	$rows=$origRows;
				}
			}

			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);
				// hardcoded: 12 is the height of the font
			$height=$rows*12;

			$item='
				<div style="'.htmlspecialchars($divStyle.' overflow:auto; height:'.$height.'px; width:'.$width.'px;').'" class="'.htmlspecialchars($this->formElClass('none')).'">'.
				$itemValue.
				'</div>';
		} else {
			if(!$config['pass_content']) {
				$itemValue = htmlspecialchars($itemValue);
			}

			$cols = $config['cols']?$config['cols']:($config['size']?$config['size']:$this->maxInputWidth);
			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);

				// overflow:auto crashes mozilla here. Title tag is usefull when text is longer than the div box (overflow:hidden).
			$item = '
				<div style="'.htmlspecialchars($divStyle.' overflow:hidden; width:'.$width.'px;').'" class="'.htmlspecialchars($this->formElClass('none')).'" title="'.$itemValue.'">'.
				'<span class="nobr">'.(strcmp($itemValue,'')?$itemValue:'&nbsp;').'</span>'.
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
	function getSingleField_typeFlex($table,$field,$row,&$PA)	{

			// Data Structure:
		$dataStructArray = t3lib_BEfunc::getFlexFormDS($PA['fieldConf']['config'],$row,$table);
#debug($dataStructArray);

			// Get data structure:
		if (is_array($dataStructArray))	{
#debug(array(str_replace(' ',chr(160),$PA['itemFormElValue'])));

				// Get data:
			$xmlData = $PA['itemFormElValue'];
			$xmlHeaderAttributes = t3lib_div::xmlGetHeaderAttribs($xmlData);
			$storeInCharset = strtolower($xmlHeaderAttributes['encoding']);
			if ($storeInCharset)	{
				$currentCharset=$GLOBALS['LANG']->charSet;
				$xmlData = $GLOBALS['LANG']->csConvObj->conv($xmlData,$storeInCharset,$currentCharset,1);
			}
			$editData=t3lib_div::xml2array($xmlData);
			if (!is_array($editData))	{	// Must be XML parsing error...
#debug(array($editData,$xmlData));
				$editData=array();
			}

				// Find the data structure if sheets are found:
			$sheet = $editData['meta']['currentSheetId'] ? $editData['meta']['currentSheetId'] : 'sDEF';	// Sheet to display
#			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'[meta][currentSheetId]" value="'.$sheet.'">';

				// Create sheet menu:
			if (is_array($dataStructArray['sheets']))	{
				#$item.=$this->getSingleField_typeFlex_sheetMenu($dataStructArray['sheets'], $PA['itemFormElName'].'[meta][currentSheetId]', $sheet).'<br />';
			}
#debug($editData);

				// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

			$editData['meta']['currentLangId']=array();
			$languages = $this->getAvailableLanguages();

			foreach($languages as $lInfo)	{
				if ($GLOBALS['BE_USER']->checkLanguageAccess($lInfo['uid']))	{
					$editData['meta']['currentLangId'][] = 	$lInfo['ISOcode'];
				}
			}
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId']))	{
				$editData['meta']['currentLangId']=array('DEF');
			}

			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);


//			if (!$langDisabled && count($languages) > 1)	{
//				$item.=$this->getSingleField_typeFlex_langMenu($languages, $PA['itemFormElName'].'[meta][currentLangId]', $editData['meta']['currentLangId']).'<br />';
//			}

			$PA['_noEditDEF'] = FALSE;
			if ($langChildren || $langDisabled)	{
				$rotateLang = array('DEF');
			} else {
				if (!in_array('DEF',$editData['meta']['currentLangId']))	{
					array_unshift($editData['meta']['currentLangId'],'DEF');
					$PA['_noEditDEF'] = TRUE;
				}
				$rotateLang = $editData['meta']['currentLangId'];
			}

				// Tabs sheets
			if (is_array($dataStructArray['sheets']))	{
				$tabsToTraverse = array_keys($dataStructArray['sheets']);
			} else {
				$tabsToTraverse = array($sheet);
			}

			foreach($rotateLang as $lKey)	{
				if (!$langChildren && !$langDisabled)	{
					$item.= '<b>'.$lKey.':</b>';
				}

				$tabParts = array();
				foreach($tabsToTraverse as $sheet)	{
					$sheetCfg = $dataStructArray['sheets'][$sheet];
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheet);

						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el']))		{
						$cmdData = t3lib_div::_GP('flexFormsCmdData');
						$lang = 'l'.$lKey;	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_lang'] = $lang;

							// Render flexform:
						$tRows = $this->getSingleField_typeFlex_draw(
									$dataStruct['ROOT']['el'],
									$editData['data'][$sheet][$lang],
									$cmdData['data'][$sheet][$lang],
									$table,
									$field,
									$row,
									$PA,
									'[data]['.$sheet.']['.$lang.']'
								);
						$sheetContent= '<table border="0" cellpadding="1" cellspacing="1" class="typo3-TCEforms-flexForm">'.implode('',$tRows).'</table>';

			#			$item = '<div style=" position:absolute;">'.$item.'</div>';
						//visibility:hidden;
					} else $sheetContent='Data Structure ERROR: No ROOT element found for sheet "'.$sheet.'".';

						// Add to tab:
					$tabParts[] = array(
						'label' => ($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sheet),
						'description' => ($sheetCfg['ROOT']['TCEforms']['sheetDescription'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetDescription']) : ''),
						'linkTitle' => ($sheetCfg['ROOT']['TCEforms']['sheetShortDescr'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetShortDescr']) : ''),
						'content' => $sheetContent
					);
				}

				if (is_array($dataStructArray['sheets']))	{
					$item.= $this->getDynTabMenu($tabParts,'TCEFORMS:flexform:'.$PA['itemFormElName']);
				} else {
					$item.= $sheetContent;
				}
			}
		} else $item='Data Structure ERROR: '.$dataStructArray;

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
	function getSingleField_typeFlex_langMenu($languages,$elName,$selectedLanguage,$multi=1)	{
		$opt=array();
		foreach($languages as $lArr)	{
			$opt[]='<option value="'.htmlspecialchars($lArr['ISOcode']).'"'.(in_array($lArr['ISOcode'],$selectedLanguage)?' selected="selected"':'').'>'.htmlspecialchars($lArr['title']).'</option>';
		}

		$output = '<select name="'.$elName.'[]"'.($multi ? ' multiple="multiple" size="'.count($languages).'"' : '').'>'.implode('',$opt).'</select>';

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
	function getSingleField_typeFlex_sheetMenu($sArr,$elName,$sheetKey)	{

		$tCells =array();
		$pct = round(100/count($sArr));
		foreach($sArr as $sKey => $sheetCfg)	{
			if ($GLOBALS['BE_USER']->jsConfirmation(1))	{
				$onClick = 'if (confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){'.$this->elName($elName).".value='".$sKey."'; TBE_EDITOR_submitForm()};";
			} else {
				$onClick = 'if(TBE_EDITOR_checkSubmit(-1)){ '.$this->elName($elName).".value='".$sKey."'; TBE_EDITOR_submitForm();}";
			}


			$tCells[]='<td width="'.$pct.'%" style="'.($sKey==$sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;').' cursor: hand;" onclick="'.htmlspecialchars($onClick).'" align="center">'.
					($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey).
					'</td>';
		}

		return '<table border="0" cellpadding="0" cellspacing="2" class="typo3-TCEforms-flexForm-sheetMenu"><tr>'.implode('',$tCells).'</tr></table>';
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$dataStruct: ...
	 * @param	[type]		$editData: ...
	 * @param	[type]		$cmdData: ...
	 * @param	[type]		$table: ...
	 * @param	[type]		$field: ...
	 * @param	[type]		$row: ...
	 * @param	[type]		$PA: ...
	 * @param	[type]		$formPrefix: ...
	 * @param	[type]		$level: ...
	 * @param	[type]		$tRows: ...
	 * @return	[type]		...
	 */
	function getSingleField_typeFlex_draw($dataStruct,$editData,$cmdData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$tRows=array())	{

			// Data Structure array must be ... and array of course...
		if (is_array($dataStruct))	{
			foreach($dataStruct as $key => $value)	{
				if (is_array($value))	{	// The value of each entry must be an array.

						// ********************
						// Making the row:
						// ********************
					$rowCells=array();

						// Icon:
					$rowCells['title'] = '<img src="clear.gif" width="'.($level*16).'" height="1" alt="" /><strong>'.htmlspecialchars(t3lib_div::fixed_lgd_cs($this->sL($value['tx_templavoila']['title']),30)).'</strong>';;

					$rowCells['formEl']='';
					if ($value['type']=='array')	{
						if ($value['section'])	{
								// Render "NEW [container]" selectorbox:
							if (is_array($value['el']))	{
								$opt=array();
								$opt[]='<option value=""></option>';
								foreach($value['el'] as $kk => $vv)	{
									$opt[]='<option value="'.$kk.'">'.htmlspecialchars('NEW "'.$value['el'][$kk]['tx_templavoila']['title'].'"').'</option>';
								}
								$rowCells['formEl']='<select name="flexFormsCmdData'.$formPrefix.'['.$key.'][value]">'.implode('',$opt).'</select>';
							}

								// Put row together
							$tRows[]='<tr class="bgColor2">
								<td nowrap="nowrap" valign="top">'.$rowCells['title'].'</td>
								<td>'.$rowCells['formEl'].'</td>
							</tr>';

							$cc=0;
							if (is_array($editData[$key]['el']))	{
								foreach($editData[$key]['el'] as $k3 => $v3)	{
									$cc=$k3;
									$theType = key($v3);
									$theDat = $v3[$theType];
									$newSectionEl = $value['el'][$theType];
									if (is_array($newSectionEl))	{
										$tRows = $this->getSingleField_typeFlex_draw(
											array($theType => $newSectionEl),
											array($theType => $theDat),
											$cmdData[$key]['el'][$cc],
											$table,
											$field,
											$row,
											$PA,
											$formPrefix.'['.$key.'][el]['.$cc.']',
											$level+1,
											$tRows
										);
									}
								}
							}



								// New form?
							if ($cmdData[$key]['value'])	{
								$newSectionEl = $value['el'][$cmdData[$key]['value']];
								if (is_array($newSectionEl))	{
									$tRows = $this->getSingleField_typeFlex_draw(
										array($cmdData[$key]['value'] => $newSectionEl),
										array(),
										array(),
										$table,
										$field,
										$row,
										$PA,
										$formPrefix.'['.$key.'][el]['.($cc+1).']',
										$level+1,
										$tRows
									);
								}
							}
						} else {
							$idTagPrefix = uniqid('id',true); // ID attributes are used for the move and delete checkboxes for referencing to them in the label tag (<label for="the form field id">) that's rendered around the icons

								// Put row together
							$tRows[]='<tr class="bgColor2">
								<td nowrap="nowrap" valign="top">'.
								'<input name="_DELETE_FLEX_FORM'.$PA['itemFormElName'].$formPrefix.'" id="'.$idTagPrefix.'-del" type="checkbox" value="1" /><label for="'.$idTagPrefix.'-del"><img src="'.$this->backPath.'gfx/garbage.gif" border="0" alt="" /></label>'.
								'<input name="_MOVEUP_FLEX_FORM'.$PA['itemFormElName'].$formPrefix.'" id="'.$idTagPrefix.'-mvup" type="checkbox" value="1" /><label for="'.$idTagPrefix.'-mvup"><img src="'.$this->backPath.'gfx/button_up.gif" border="0" alt="" /></label>'.
								'<input name="_MOVEDOWN_FLEX_FORM'.$PA['itemFormElName'].$formPrefix.'" id="'.$idTagPrefix.'-mvdown" type="checkbox" value="1" /><label for="'.$idTagPrefix.'-mvdown"><img src="'.$this->backPath.'gfx/button_down.gif" border="0" alt="" /></label>'.
								$rowCells['title'].'</td>
								<td>'.$rowCells['formEl'].'</td>
							</tr>';

							$tRows = $this->getSingleField_typeFlex_draw(
								$value['el'],
								$editData[$key]['el'],
								$cmdData[$key]['el'],
								$table,
								$field,
								$row,
								$PA,
								$formPrefix.'['.$key.'][el]',
								$level+1,
								$tRows
							);
						}

					} elseif (is_array($value['TCEforms']['config'])) {	// Rendering a single form element:

						if (is_array($PA['_valLang']))	{
							$rotateLang = $PA['_valLang'];
						} else {
							$rotateLang = array($PA['_valLang']);
						}

						foreach($rotateLang as $vDEFkey)	{
							$vDEFkey = 'v'.$vDEFkey;

							if (!$value['TCEforms']['displayCond'] || $this->isDisplayCondition($value['TCEforms']['displayCond'],$editData,$vDEFkey)) {
								$fakePA=array();
								$fakePA['fieldConf']=array(
									'label' => $this->sL($value['TCEforms']['label']),
									'config' => $value['TCEforms']['config'],
									'defaultExtras' => $value['TCEforms']['defaultExtras']
								);
								if ($PA['_noEditDEF'] && $PA['_lang']==='lDEF') {
									$fakePA['fieldConf']['config'] = array(
										'type' => 'none',
										'rows' => 2
									);
								}

								if (
									($GLOBALS['TCA'][$table]['ctrl']['type'] && !strcmp($key,$GLOBALS['TCA'][$table]['ctrl']['type'])) ||
									($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'] && t3lib_div::inList($GLOBALS['TCA'][$table]['ctrl']['requestUpdate'],$key))) {
									if ($GLOBALS['BE_USER']->jsConfirmation(1))	{
										$alertMsgOnChange = 'if (confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm() };';
									} else {
										$alertMsgOnChange = 'if(TBE_EDITOR_checkSubmit(-1)){ TBE_EDITOR_submitForm();}';
									}
								} else {
									$alertMsgOnChange = '';
								}

								$fakePA['fieldChangeFunc']=$PA['fieldChangeFunc'];
								if (strlen($alertMsgOnChange)) {
									$fakePA['fieldChangeFunc']['alert']=$alertMsgOnChange;
								}
								$fakePA['onFocus']=$PA['onFocus'];
								$fakePA['label']=$PA['label'];

								$fakePA['itemFormElName']=$PA['itemFormElName'].$formPrefix.'['.$key.']['.$vDEFkey.']';
								$fakePA['itemFormElName_file']=$PA['itemFormElName_file'].$formPrefix.'['.$key.']['.$vDEFkey.']';

								if(isset($editData[$key][$vDEFkey])) {
									$fakePA['itemFormElValue']=$editData[$key][$vDEFkey];
								} else {
									$fakePA['itemFormElValue']=$fakePA['fieldConf']['config']['default'];
								}

								$rowCells['formEl']= $this->getSingleField_SW($table,$field,$row,$fakePA);
								$rowCells['title']= htmlspecialchars($fakePA['fieldConf']['label']);

								if (!in_array('DEF',$rotateLang))	{
									$defInfo = '<div class="typo3-TCEforms-originalLanguageValue">'.nl2br(htmlspecialchars($editData[$key]['vDEF'])).'&nbsp;</div>';
								} else {
									$defInfo = '';
								}

									// Put row together
								$tRows[]='<tr>
									<td nowrap="nowrap" valign="top" class="bgColor5">'.$rowCells['title'].($vDEFkey=='vDEF' ? '' : ' ('.$vDEFkey.')').'</td>
									<td class="bgColor4">'.$rowCells['formEl'].$defInfo.'</td>
								</tr>';
							}
						}
					}
				}
			}
		}

		return $tRows;
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
	function getSingleField_typeUnknown($table,$field,$row,&$PA)	{
		$item='Unknown type: '.$PA['fieldConf']['config']['form_type'].'<br />';

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
	function getSingleField_typeUser($table,$field,$row,&$PA)	{
		$PA['table']=$table;
		$PA['field']=$field;
		$PA['row']=$row;

		$PA['pObj']=&$this;

		return t3lib_div::callUserFunction($PA['fieldConf']['config']['userFunc'],$PA,$this);
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
	function formatValue ($config, $itemValue)	{
		$format = trim($config['format']);
		switch($format)	{
			case 'date':
				$option = trim($config['format.']['option']);
				if ($option)	{
					if ($config['format.']['strftime'])	{
						$value = strftime($option,$itemValue);
					} else {
						$value = date($option,$itemValue);
					}
				} else {
					$value = date('d-m-Y',$itemValue);
				}
				if ($config['format.']['appendAge'])	{
					$value .= ' ('.t3lib_BEfunc::calcAge((time()-$itemValue), $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')).')';
				}
				$itemValue = $value;
				break;
			case 'datetime':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i d-m-Y',$itemValue);
				break;
			case 'time':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i',$itemValue);
				break;
			case 'timesec':	// compatibility with "eval" (type "input")
				$itemValue = date('H:i:s',$itemValue);
				break;
			case 'year':	// compatibility with "eval" (type "input")
				$itemValue = date('Y',$itemValue);
				break;
			case 'int':
				$baseArr = array('dec'=>'d','hex'=>'x','HEX'=>'X','oct'=>'o','bin'=>'b');
				$base = trim($config['format.']['base']);
				$format = $baseArr[$base] ? $baseArr[$base] : 'd';
				$itemValue = sprintf('%'.$format,$itemValue);
				break;
			case 'float':
				$precision = t3lib_div::intInRange($config['format.']['precision'],1,10,2);
				$itemValue = sprintf('%.'.$precision.'f',$itemValue);
				break;
			case 'number':
				$format = trim($config['format.']['option']);
				$itemValue = sprintf('%'.$format,$itemValue);
				break;
			case 'md5':
				$itemValue = md5($itemValue);
				break;
			case 'filesize':
				$value = t3lib_div::formatSize(intval($itemValue));
				if ($config['format.']['appendByteSize'])	{
					$value .= ' ('.$itemValue.')';
				}
				$itemValue = $value;
				break;
			case 'user':
				$func = trim($config['format.']['userFunc']);
				if ($func)	{
					$params = array(
						'value' => $itemValue,
						'args' => $config['format.']['userFunc'],
						'config' => $config,
						'pObj' => &$this
					);
					$itemValue = t3lib_div::callUserFunction($func,$params,$this);
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
	function getRTypeNum($table,$row)	{
		global $TCA;
			// If there is a "type" field configured...
		if ($TCA[$table]['ctrl']['type'])	{
			$typeFieldName = $TCA[$table]['ctrl']['type'];
			$typeNum=$row[$typeFieldName];	// Get value of the row from the record which contains the type value.
			if (!strcmp($typeNum,''))	$typeNum=0;			// If that value is an empty string, set it to "0" (zero)
		} else {
			$typeNum = 0;	// If no "type" field, then set to "0" (zero)
		}

		$typeNum = (string)$typeNum;		// Force to string. Necessary for eg '-1' to be recognized as a type value.
		if (!$TCA[$table]['types'][$typeNum])	{	// However, if the type "0" is not found in the "types" array, then default to "1" (for historical reasons)
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
	function rearrange($fields)	{
		$fO = array_flip(t3lib_div::trimExplode(',',$this->fieldOrder,1));
		reset($fields);
		$newFields=array();
		while(list($cc,$content)=each($fields))	{
			$cP = t3lib_div::trimExplode(';',$content);
			if (isset($fO[$cP[0]]))	{
				$newFields[$fO[$cP[0]]] = $content;
				unset($fields[$cc]);
			}
		}
		ksort($newFields);
		$fields=array_merge($newFields,$fields);		// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
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
	function getExcludeElements($table,$row,$typeNum)	{
		global $TCA;

			// Init:
		$excludeElements=array();

			// If a subtype field is defined for the type
		if ($TCA[$table]['types'][$typeNum]['subtype_value_field'])	{
			$sTfield = $TCA[$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($TCA[$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]]))	{
				$excludeElements=t3lib_div::trimExplode(',',$TCA[$table]['types'][$typeNum]['subtypes_excludelist'][$row[$sTfield]],1);
			}
		}

			// If a bitmask-value field has been configured, then find possible fields to exclude based on that:
		if ($TCA[$table]['types'][$typeNum]['bitmask_value_field'])	{
			$sTfield = $TCA[$table]['types'][$typeNum]['bitmask_value_field'];
			$sTValue = t3lib_div::intInRange($row[$sTfield],0);
			if (is_array($TCA[$table]['types'][$typeNum]['bitmask_excludelist_bits']))	{
				reset($TCA[$table]['types'][$typeNum]['bitmask_excludelist_bits']);
				while(list($bitKey,$eList)=each($TCA[$table]['types'][$typeNum]['bitmask_excludelist_bits']))	{
					$bit=substr($bitKey,1);
					if (t3lib_div::testInt($bit))	{
						$bit = t3lib_div::intInRange($bit,0,30);
						if (
								(substr($bitKey,0,1)=='-' && !($sTValue&pow(2,$bit))) ||
								(substr($bitKey,0,1)=='+' && ($sTValue&pow(2,$bit)))
							)	{
							$excludeElements = array_merge($excludeElements,t3lib_div::trimExplode(',',$eList,1));
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
	function getFieldsToAdd($table,$row,$typeNum)	{
		global $TCA;

			// Init:
		$addElements=array();

			// If a subtype field is defined for the type
		if ($TCA[$table]['types'][$typeNum]['subtype_value_field'])	{
			$sTfield = $TCA[$table]['types'][$typeNum]['subtype_value_field'];
			if (trim($TCA[$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]]))	{
				$addElements=t3lib_div::trimExplode(',',$TCA[$table]['types'][$typeNum]['subtypes_addlist'][$row[$sTfield]],1);
			}
		}
			// Return the return
		return array($addElements,$sTfield);
	}

	/**
	 * Merges the current [types][showitem] array with the array of fields to add for the current subtype field of the "type" value.
	 *
	 * @param	array		A [types][showitem] list of fields, exploded by ","
	 * @param	array		The output from getFieldsToAdd()
	 * @return	array		Return the modified $fields array.
	 * @see getMainFields(),getFieldsToAdd()
	 */
	function mergeFieldsWithAddedFields($fields,$fieldsToAdd)	{
		if (count($fieldsToAdd[0]))	{
			reset($fields);
			$c=0;
			while(list(,$fieldInfo)=each($fields))	{
				$parts = explode(';',$fieldInfo);
				if (!strcmp(trim($parts[0]),$fieldsToAdd[1]))	{
					array_splice(
						$fields,
						$c+1,
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
	function setTSconfig($table,$row,$field='')	{
		$mainKey = $table.':'.$row['uid'];
		if (!isset($this->cachedTSconfig[$mainKey]))	{
			$this->cachedTSconfig[$mainKey]=t3lib_BEfunc::getTCEFORM_TSconfig($table,$row);
		}
		if ($field)	{
			return $this->cachedTSconfig[$mainKey][$field];
		} else {
			return $this->cachedTSconfig[$mainKey];
		}
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
	function getSpecConfForField($table,$row,$field)	{
			// Finds the current "types" configuration for the table/row:
		$types_fieldConfig = t3lib_BEfunc::getTCAtypes($table,$row);

			// If this is an array, then traverse it:
		if (is_array($types_fieldConfig))	{
			foreach($types_fieldConfig as $vconf)	{
					// If the input field name matches one found in the 'types' list, then return the 'special' configuration.
				if ($vconf['field']==$field)	return $vconf['spec'];
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
	function getSpecConfFromString($extraString, $defaultExtras)    {
		return t3lib_BEfunc::getSpecConfParts($extraString, $defaultExtras);
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
	function registerDefaultLanguageData($table,$rec)	{
		global $TCA;

			// Add default language:
		if ($TCA[$table]['ctrl']['languageField']
				&& $rec[$TCA[$table]['ctrl']['languageField']] > 0
				&& $TCA[$table]['ctrl']['transOrigPointerField']
				&& intval($rec[$TCA[$table]['ctrl']['transOrigPointerField']]) > 0)	{

			$lookUpTable = $TCA[$table]['ctrl']['transOrigPointerTable'] ? $TCA[$table]['ctrl']['transOrigPointerTable'] : $table;

				// Get data formatted:
			$this->defaultLanguageData[$table.':'.$rec['uid']] = t3lib_BEfunc::getRecordWSOL($lookUpTable, intval($rec[$TCA[$table]['ctrl']['transOrigPointerField']]));

				// Get data for diff:
			if ($TCA[$table]['ctrl']['transOrigDiffSourceField'])	{
				$this->defaultLanguageData_diff[$table.':'.$rec['uid']] = unserialize($rec[$TCA[$table]['ctrl']['transOrigDiffSourceField']]);
			}
		}
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
	function renderDefaultLanguageContent($table,$field,$row,$item)	{
		if (is_array($this->defaultLanguageData[$table.':'.$row['uid']]))	{
			$dLVal = t3lib_BEfunc::getProcessedValue($table,$field,$this->defaultLanguageData[$table.':'.$row['uid']][$field],0,1);

			if (strcmp($dLVal,''))	{
				$item.='<div class="typo3-TCEforms-originalLanguageValue">'.nl2br(htmlspecialchars($dLVal)).'&nbsp;</div>';
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
	function renderDefaultLanguageDiff($table,$field,$row,$item)	{
		if (is_array($this->defaultLanguageData_diff[$table.':'.$row['uid']]))	{

				// Initialize:
			$dLVal = array(
				'old' => $this->defaultLanguageData_diff[$table.':'.$row['uid']],
				'new' => $this->defaultLanguageData[$table.':'.$row['uid']],
			);

			if (isset($dLVal['old'][$field]))	{	// There must be diff-data:
			 	if (strcmp($dLVal['old'][$field],$dLVal['new'][$field]))	{

						// Create diff-result:
					$t3lib_diff_Obj = t3lib_div::makeInstance('t3lib_diff');
					$diffres = $t3lib_diff_Obj->makeDiffDisplay(
						t3lib_BEfunc::getProcessedValue($table,$field,$dLVal['old'][$field],0,1),
						t3lib_BEfunc::getProcessedValue($table,$field,$dLVal['new'][$field],0,1)
					);

					$item.='<div class="typo3-TCEforms-diffBox">'.
						'<div class="typo3-TCEforms-diffBox-header">'.htmlspecialchars($this->getLL('l_changeInOrig')).':</div>'.
						$diffres.
					'</div>';
				}
			}
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
	 * @return	string		The form fields for the selection.
	 */
	function dbFileIcons($fName,$mode,$allowed,$itemArray,$selector='',$params=array(),$onFocus='')	{


		$disabled = '';
		if($this->renderReadonly || $params['readonly'])  {
			$disabled = ' disabled="disabled"';
		}

			// Sets a flag which means some JavaScript is included on the page to support this element.
		$this->printNeededJS['dbFileIcons']=1;

			// INIT
		$uidList=array();
		$opt=array();
		$itemArrayC=0;

			// Creating <option> elements:
		if (is_array($itemArray))	{
			$itemArrayC=count($itemArray);
			reset($itemArray);
			switch($mode)	{
				case 'db':
					while(list(,$pp)=each($itemArray))	{
						$pRec = t3lib_BEfunc::getRecordWSOL($pp['table'],$pp['id']);
						if (is_array($pRec))	{
							$pTitle = t3lib_div::fixed_lgd_cs($this->noTitle($pRec[$GLOBALS['TCA'][$pp['table']]['ctrl']['label']]),$this->titleLen);
							$pUid = $pp['table'].'_'.$pp['id'];
							$uidList[]=$pUid;
							$opt[]='<option value="'.htmlspecialchars($pUid).'">'.htmlspecialchars($pTitle).'</option>';
						}
					}
				break;
				case 'file':
					while(list(,$pp)=each($itemArray))	{
						$pParts = explode('|',$pp);
						$uidList[]=$pUid=$pTitle = $pParts[0];
						$opt[]='<option value="'.htmlspecialchars(rawurldecode($pParts[0])).'">'.htmlspecialchars(rawurldecode($pParts[0])).'</option>';
					}
				break;
				default:
					while(list(,$pp)=each($itemArray))	{
						$pParts = explode('|',$pp, 2);
						$uidList[]=$pUid=$pParts[0];
						$pTitle = $pParts[1];
						$opt[]='<option value="'.htmlspecialchars(rawurldecode($pUid)).'">'.htmlspecialchars(rawurldecode($pTitle)).'</option>';
					}
				break;
			}
		}

			// Create selector box of the options
		$sSize = $params['autoSizeMax'] ? t3lib_div::intInRange($itemArrayC+1,t3lib_div::intInRange($params['size'],1),$params['autoSizeMax']) : $params['size'];
		if (!$selector)	{
			$selector = '<select size="'.$sSize.'"'.$this->insertDefStyle('group').' multiple="multiple" name="'.$fName.'_list" '.$onFocus.$params['style'].$disabled.'>'.implode('',$opt).'</select>';
		}


		$icons = array(
			'L' => array(),
			'R' => array(),
		);
		if (!$params['readonly']) {
			if (!$params['noBrowser'])	{
				$aOnClick='setFormValueOpenBrowser(\''.$mode.'\',\''.($fName.'|||'.$allowed.'|').'\'); return false;';
				$icons['R'][]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/insert3.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_browse_'.($mode=='file'?'file':'db'))).' />'.
						'</a>';
			}
			if (!$params['dontShowMoveIcons'])	{
				if ($sSize>=5)	{
					$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Top\'); return false;">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_totop.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_to_top')).' />'.
							'</a>';
				}
				$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Up\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/up.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_up')).' />'.
						'</a>';
				$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Down\'); return false;">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/down.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_down')).' />'.
						'</a>';
				if ($sSize>=5)	{
					$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Bottom\'); return false;">'.
							'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_tobottom.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_to_bottom')).' />'.
							'</a>';
				}
			}

			$clipElements = $this->getClipboardElements($allowed,$mode);
			if (count($clipElements))	{
				$aOnClick = '';
	#			$counter = 0;
				foreach($clipElements as $elValue)	{
					if ($mode=='file')	{
						$itemTitle = 'unescape(\''.rawurlencode(basename($elValue)).'\')';
					} else {	// 'db' mode assumed
						list($itemTable,$itemUid) = explode('|', $elValue);
						$itemTitle = $GLOBALS['LANG']->JScharCode(t3lib_BEfunc::getRecordTitle($itemTable, t3lib_BEfunc::getRecordWSOL($itemTable,$itemUid)));
						$elValue = $itemTable.'_'.$itemUid;
					}
					$aOnClick.= 'setFormValueFromBrowseWin(\''.$fName.'\',unescape(\''.rawurlencode(str_replace('%20',' ',$elValue)).'\'),'.$itemTitle.');';

	#				$counter++;
	#				if ($params['maxitems'] && $counter >= $params['maxitems'])	{	break;	}	// Makes sure that no more than the max items are inserted... for convenience.
				}
				$aOnClick.= 'return false;';
				$icons['R'][]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
						'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/insert5.png','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib(sprintf($this->getLL('l_clipInsert_'.($mode=='file'?'file':'db')),count($clipElements))).' />'.
						'</a>';
			}
			$icons['L'][]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Remove\'); return false;">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_clear.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_remove_selected')).' />'.
					'</a>';
		}

		$str='<table border="0" cellpadding="0" cellspacing="0" width="1">
			'.($params['headers']?'
				<tr>
					<td>'.$this->wrapLabels($params['headers']['selector']).'</td>
					<td></td>
					<td></td>
					<td></td>
					<td>'.($params['thumbnails'] ? $this->wrapLabels($params['headers']['items']) : '').'</td>
				</tr>':'').
			'
			<tr>
				<td valign="top">'.
					$selector.'<br />'.
					$this->wrapLabels($params['info']).
				'</td>
				<td valign="top">'.
					implode('<br />',$icons['L']).'</td>
				<td valign="top">'.
					implode('<br />',$icons['R']).'</td>
				<td><img src="clear.gif" width="5" height="1" alt="" /></td>
				<td valign="top">'.
					$this->wrapLabels($params['thumbnails']).
				'</td>
			</tr>
		</table>';

			// Creating the hidden field which contains the actual value as a comma list.
		$str.='<input type="hidden" name="'.$fName.'" value="'.htmlspecialchars(implode(',',$uidList)).'" />';

		return $str;
	}

	/**
	 * Returns array of elements from clipboard to insert into GROUP element box.
	 *
	 * @param	string		Allowed elements, Eg "pages,tt_content", "gif,jpg,jpeg,png"
	 * @param	string		Mode of relations: "db" or "file"
	 * @return	array		Array of elements in values (keys are insignificant), if none found, empty array.
	 */
	function getClipboardElements($allowed,$mode)	{

		$output = array();

		if (is_object($this->clipObj))	{
			switch($mode)	{
				case 'file':
					$elFromTable = $this->clipObj->elFromTable('_FILE');
					$allowedExts = t3lib_div::trimExplode(',', $allowed, 1);

					if ($allowedExts)	{	// If there are a set of allowed extensions, filter the content:
						foreach($elFromTable as $elValue)	{
							$pI = pathinfo($elValue);
							$ext = strtolower($pI['extension']);
							if (in_array($ext, $allowedExts))	{
								$output[] = $elValue;
							}
						}
					} else {	// If all is allowed, insert all: (This does NOT respect any disallowed extensions, but those will be filtered away by the backend TCEmain)
						$output = $elFromTable;
					}
				break;
				case 'db':
					$allowedTables = t3lib_div::trimExplode(',', $allowed, 1);
					if (!strcmp(trim($allowedTables[0]),'*'))	{	// All tables allowed for relation:
						$output = $this->clipObj->elFromTable('');
					} else {	// Only some tables, filter them:
						foreach($allowedTables as $tablename)	{
							$elFromTable = $this->clipObj->elFromTable($tablename);
							$output = array_merge($output,$elFromTable);
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
	function getClickMenu($str,$table,$uid='')	{
		if ($this->enableClickMenu)	{
			$onClick = $GLOBALS['SOBE']->doc->wrapClickMenuOnIcon($str,$table,$uid,1,'','+copy,info,edit,view', TRUE);
			return '<a href="#" onclick="'.htmlspecialchars($onClick).'">'.$str.'</a>';
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
	function renderWizards($itemKinds,$wizConf,$table,$row,$field,&$PA,$itemName,$specConf,$RTE=0)	{

			// Init:
		$fieldChangeFunc = $PA['fieldChangeFunc'];
		$item = $itemKinds[0];
		$outArr = array();
		$colorBoxLinks = array();
		$fName = '['.$table.']['.$row['uid'].']['.$field.']';
		$md5ID = 'ID'.t3lib_div::shortmd5($itemName);
		$listFlag = '_list';

			// Manipulate the field name (to be the true form field name) and remove a suffix-value if the item is a selector box with renderMode "singlebox":
		if ($PA['fieldConf']['config']['form_type']=='select')	{
			if ($PA['fieldConf']['config']['maxitems']<=1)	{	// Single select situation:
				$listFlag = '';
			} elseif ($PA['fieldConf']['config']['renderMode']=='singlebox')	{
				$itemName.='[]';
				$listFlag = '';
			}
		}

			// traverse wizards:
		if (is_array($wizConf) && !$this->disableWizards)	{
			foreach($wizConf as $wid => $wConf)	{
				if (substr($wid,0,1)!='_'
						&& (!$wConf['enableByTypeConfig'] || @in_array($wid,$specConf['wizards']['parameters']))
						&& ($RTE || !$wConf['RTEonly'])
					)	{

						// Title / icon:
					$iTitle = htmlspecialchars($this->sL($wConf['title']));
					if ($wConf['icon'])	{
						$iDat = $this->getIcon($wConf['icon']);
						$icon = '<img src="'.$iDat[0].'" '.$iDat[1][3].' border="0"'.t3lib_BEfunc::titleAltAttrib($iTitle).' />';
					} else {
						$icon = $iTitle;
					}

						//
					switch((string)$wConf['type'])	{
						case 'userFunc':
						case 'script':
						case 'popup':
						case 'colorbox':
							if (!$wConf['notNewRecords'] || t3lib_div::testInt($row['uid']))	{

									// Setting &P array contents:
								$params = array();
								$params['params'] = $wConf['params'];
								$params['exampleImg'] = $wConf['exampleImg'];
								$params['table'] = $table;
								$params['uid'] = $row['uid'];
								$params['pid'] = $row['pid'];
								$params['field'] = $field;
								$params['md5ID'] = $md5ID;
								$params['returnUrl'] = $this->thisReturnUrl();

									// Resolving script filename and setting URL.
								if (!strcmp(substr($wConf['script'],0,4), 'EXT:')) {
									$wScript = t3lib_div::getFileAbsFileName($wConf['script']);
									if ($wScript)	{
										$wScript = '../'.substr($wScript,strlen(PATH_site));
									} else break;
								} else {
									$wScript = $wConf['script'];
								}
								$url = $this->backPath.$wScript.(strstr($wScript,'?') ? '' : '?');

									// If there is no script and the type is "colorbox", break right away:
								if ((string)$wConf['type']=='colorbox' && !$wConf['script'])	{ break; }

									// If "script" type, create the links around the icon:
								if ((string)$wConf['type']=='script')	{
									$aUrl = $url.t3lib_div::implodeArrayForUrl('',array('P'=>$params));
									$outArr[]='<a href="'.htmlspecialchars($aUrl).'" onclick="'.$this->blur().'return !TBE_EDITOR_isFormChanged();">'.
										$icon.
										'</a>';
								} else {

										// ... else types "popup", "colorbox" and "userFunc" will need additional parameters:
									$params['formName'] = $this->formName;
									$params['itemName'] = $itemName;
									$params['fieldChangeFunc'] = $fieldChangeFunc;

									switch((string)$wConf['type'])	{
										case 'popup':
										case 'colorbox':
												// Current form value is passed as P[currentValue]!
											$addJS = $wConf['popup_onlyOpenIfSelected']?'if (!TBE_EDITOR_curSelected(\''.$itemName.$listFlag.'\')){alert('.$GLOBALS['LANG']->JScharCode($this->getLL('m_noSelItemForEdit')).'); return false;}':'';
											$curSelectedValues='+\'&P[currentSelectedValues]=\'+TBE_EDITOR_curSelected(\''.$itemName.$listFlag.'\')';
											$aOnClick=	$this->blur().
														$addJS.
														'vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl('',array('P'=>$params)).'\'+\'&P[currentValue]=\'+TBE_EDITOR_rawurlencode('.$this->elName($itemName).'.value,200)'.$curSelectedValues.',\'popUp'.$md5ID.'\',\''.$wConf['JSopenParams'].'\');'.
														'vHWin.focus();return false;';
												// Setting "colorBoxLinks" - user LATER to wrap around the color box as well:
											$colorBoxLinks = Array('<a href="#" onclick="'.htmlspecialchars($aOnClick).'">','</a>');
											if ((string)$wConf['type']=='popup')	{
												$outArr[] = $colorBoxLinks[0].$icon.$colorBoxLinks[1];
											}
										break;
										case 'userFunc':
											$params['item'] = &$item;	// Reference set!
											$params['icon'] = $icon;
											$params['iTitle'] = $iTitle;
											$params['wConf'] = $wConf;
											$params['row'] = $row;
											$outArr[] = t3lib_div::callUserFunction($wConf['userFunc'],$params,$this);
										break;
									}
								}

									// Hide the real form element?
								if (is_array($wConf['hideParent']) || $wConf['hideParent'])	{
									$item = $itemKinds[1];	// Setting the item to a hidden-field.
									if (is_array($wConf['hideParent']))	{
										$item.= $this->getSingleField_typeNone_render($wConf['hideParent'], $PA['itemFormElValue']);
									}
								}
							}
						break;
						case 'select':
							$fieldValue = array('config' => $wConf);
							$TSconfig = $this->setTSconfig($table, $row);
							$TSconfig[$field] = $TSconfig[$field]['wizards.'][$wid.'.'];
							$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($fieldValue), $fieldValue, $TSconfig, $field);

							$opt = array();
							$opt[] = '<option>'.$iTitle.'</option>';
							foreach($selItems as $p)	{
								$opt[] = '<option value="'.htmlspecialchars($p[1]).'">'.htmlspecialchars($p[0]).'</option>';
							}
							if ($wConf['mode']=='append')	{
								$assignValue = $this->elName($itemName).'.value=\'\'+this.options[this.selectedIndex].value+'.$this->elName($itemName).'.value';
							} elseif ($wConf['mode']=='prepend')	{
								$assignValue = $this->elName($itemName).'.value+=\'\'+this.options[this.selectedIndex].value';
							} else {
								$assignValue = $this->elName($itemName).'.value=this.options[this.selectedIndex].value';
							}
							$sOnChange = $assignValue.';this.selectedIndex=0;'.implode('',$fieldChangeFunc);
							$outArr[] = '<select name="_WIZARD'.$fName.'" onchange="'.htmlspecialchars($sOnChange).'">'.implode('',$opt).'</select>';
						break;
					}

						// Color wizard colorbox:
					if ((string)$wConf['type']=='colorbox')	{
						$dim = t3lib_div::intExplode('x',$wConf['dim']);
						$dX = t3lib_div::intInRange($dim[0],1,200,20);
						$dY = t3lib_div::intInRange($dim[1],1,200,20);
						$color = $row[$field] ? ' bgcolor="'.htmlspecialchars($row[$field]).'"' : '';
						$outArr[] = '<table border="0" cellpadding="0" cellspacing="0" id="'.$md5ID.'"'.$color.' style="'.htmlspecialchars($wConf['tableStyle']).'">
									<tr>
										<td>'.
											$colorBoxLinks[0].
											'<img src="clear.gif" width="'.$dX.'" height="'.$dY.'"'.t3lib_BEfunc::titleAltAttrib(trim($iTitle.' '.$row[$field])).' border="0" />'.
											$colorBoxLinks[1].
											'</td>
									</tr>
								</table>';
					}
				}
			}

				// For each rendered wizard, put them together around the item.
			if (count($outArr))	{
				if ($wizConf['_HIDDENFIELD'])	$item = $itemKinds[1];

				$outStr = '';
				$vAlign = $wizConf['_VALIGN'] ? ' valign="'.$wizConf['_VALIGN'].'"' : '';
				if (count($outArr)>1 || $wizConf['_PADDING'])	{
					$dist = intval($wizConf['_DISTANCE']);
					if ($wizConf['_VERTICAL'])	{
						$dist = $dist ? '<tr><td><img src="clear.gif" width="1" height="'.$dist.'" alt="" /></td></tr>' : '';
						$outStr = '<tr><td>'.implode('</td></tr>'.$dist.'<tr><td>',$outArr).'</td></tr>';
					} else {
						$dist = $dist ? '<td><img src="clear.gif" height="1" width="'.$dist.'" alt="" /></td>' : '';
						$outStr = '<tr><td'.$vAlign.'>'.implode('</td>'.$dist.'<td'.$vAlign.'>',$outArr).'</td></tr>';
					}
					$outStr = '<table border="0" cellpadding="'.intval($wizConf['_PADDING']).'" cellspacing="0">'.$outStr.'</table>';
				} else {
					$outStr = implode('',$outArr);
				}

				if (!strcmp($wizConf['_POSITION'],'left'))	{
					$outStr = '<tr><td'.$vAlign.'>'.$outStr.'</td><td'.$vAlign.'>'.$item.'</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'],'top'))	{
					$outStr = '<tr><td>'.$outStr.'</td></tr><tr><td>'.$item.'</td></tr>';
				} elseif (!strcmp($wizConf['_POSITION'],'bottom'))	{
					$outStr = '<tr><td>'.$item.'</td></tr><tr><td>'.$outStr.'</td></tr>';
				} else {
					$outStr = '<tr><td'.$vAlign.'>'.$item.'</td><td'.$vAlign.'>'.$outStr.'</td></tr>';
				}

				$item = '<table border="0" cellpadding="0" cellspacing="0">'.$outStr.'</table>';
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
	function getIcon($icon)	{
		if (substr($icon,0,4)=='EXT:')	{
			$file = t3lib_div::getFileAbsFileName($icon);
			if ($file)	{
				$file = substr($file,strlen(PATH_site));
				$selIconFile = $this->backPath.'../'.$file;
				$selIconInfo = @getimagesize(PATH_site.$file);
			}
		} elseif (substr($icon,0,3)=='../')	{
			$selIconFile = $this->backPath.t3lib_div::resolveBackPath($icon);
			$selIconInfo = @getimagesize(PATH_site.t3lib_div::resolveBackPath(substr($icon,3)));
		} elseif (substr($icon,0,4)=='ext/' || substr($icon,0,7)=='sysext/') {
			$selIconFile = $this->backPath.$icon;
			$selIconInfo = @getimagesize(PATH_typo3.$icon);
		} else {
			$selIconFile = $this->backPath.'gfx/'.$icon;
			$selIconInfo = @getimagesize(PATH_t3lib.'gfx/'.$icon);
		}
		return array($selIconFile,$selIconInfo);
	}

	/**
	 * Creates style attribute content for option tags in a selector box, primarily setting it up to show the icon of an element as background image (works in mozilla)
	 *
	 * @param	string		Icon string for option item
	 * @return	string		Style attribute content, if any
	 */
	function optionTagStyle($iconString)	{
		if ($iconString)	{
			list($selIconFile,$selIconInfo) = $this->getIcon($iconString);
			$padTop = t3lib_div::intInRange(($selIconInfo[1]-12)/2,0);
			$styleAttr = 'background-image: url('.$selIconFile.'); background-repeat: no-repeat; height: '.t3lib_div::intInRange(($selIconInfo[1]+2)-$padTop,0).'px; padding-top: '.$padTop.'px; padding-left: '.($selIconInfo[0]+4).'px;';
			return $styleAttr;
		}
	}

	/**
	 * Extracting values from a value/label list (as made by transferData class)
	 *
	 * @param	string		Value string where values are comma separated, intermixed with labels and rawurlencoded (this is what is delivered to TCEforms normally!)
	 * @param	array		Values in an array
	 * @return	array		Input string exploded with comma and for each value only the label part is set in the array. Keys are numeric
	 */
	function extractValuesOnlyFromValueLabelList($itemFormElValue)	{
			// Get values of selected items:
		$itemArray = t3lib_div::trimExplode(',',$itemFormElValue,1);
		foreach($itemArray as $tk => $tv) {
			$tvP = explode('|',$tv,2);
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
	 * @param	array		The record array
	 * @param	integer		The palette pointer.
	 * @param	boolean		Determines the output type of the function.
	 * @return	mixed		If $retFunc is set, then returns an array with icon code and palette JavaScript function. Otherwise just the icon code.
	 */
	function wrapOpenPalette($header,$table,$row,$palette,$retFunc=0)	{
		$fieldL=array();
		if (!is_array($this->palFieldArr[$palette]))	{$this->palFieldArr[$palette]=array();}
		$palFieldN = is_array($this->palFieldArr[$palette]) ? count($this->palFieldArr[$palette]) : 0;
		$palJSFunc = 'TBE_EDITOR_palUrl(\''.($table.':'.$row['uid'].':'.$palette).'\',\''.implode(',',$this->palFieldArr[$palette]).'\','.$palFieldN.',\''.$table.'\',\''.$row['uid'].'\',1);';

		$aOnClick = $this->blur().substr($palJSFunc,0,-3).'0);return false;';

		$iconCode = '<a href="#" onclick="'.htmlspecialchars($aOnClick).'" title="'.htmlspecialchars($table).'">'.
					$header.
					'</a>';
		return $retFunc ? array($iconCode,$palJSFunc) : $iconCode;
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
	function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='')	{
		$onClick = $this->elName($itemName).'.value=this.checked?('.$this->elName($itemName).'.value|'.pow(2,$c).'):('.$this->elName($itemName).'.value&'.(pow(2,$iCount)-1-pow(2,$c)).');'.
					$addFunc;
		$str = ' onclick="'.htmlspecialchars($onClick).'"'.
				(($thisValue&pow(2,$c))?' checked="checked"':'');
		return $str;
	}

	/**
	 * Returns element reference for form element name
	 *
	 * @param	string		Form element name
	 * @return	string		Form element reference (JS)
	 */
	function elName($itemName)	{
		return 'document.'.$this->formName."['".$itemName."']";
	}

	/**
	 * Returns the "No title" string if the input $str is empty.
	 *
	 * @param	string		The string which - if empty - will become the no-title string.
	 * @param	array		Array with wrappin parts for the no-title output (in keys [0]/[1])
	 * @return	string
	 */
	function noTitle($str,$wrapParts=array())	{
		return strcmp($str,'') ? $str : $wrapParts[0].'['.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.no_title').']'.$wrapParts[1];
	}

	/**
	 * Returns 'this.blur();' string, if supported.
	 *
	 * @return	string		If the current browser supports styles, the string 'this.blur();' is returned.
	 */
	function blur()	{
		return $GLOBALS['CLIENT']['FORMSTYLE'] ? 'this.blur();':'';
	}

	/**
	 * Returns the "returnUrl" of the form. Can be set externally or will be taken from "t3lib_div::linkThisScript()"
	 *
	 * @return	string		Return URL of current script
	 */
	function thisReturnUrl()	{
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
	function getSingleHiddenField($table,$field,$row)	{
		global $TCA;
		$out='';
		t3lib_div::loadTCA($table);
		if ($TCA[$table]['columns'][$field])	{

			$uid=$row['uid'];
			$itemName=$this->prependFormFieldNames.'['.$table.']['.$uid.']['.$field.']';
			$itemValue=$row[$field];
			$item.='<input type="hidden" name="'.$itemName.'" value="'.htmlspecialchars($itemValue).'" />';
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
	function formWidth($size=48,$textarea=0) {
			// Input or text-field attribute (size or cols)
		if ($this->docLarge)	$size = round($size*$this->form_largeComp);
		$wAttrib = $textarea?'cols':'size';
		if (!$GLOBALS['CLIENT']['FORMSTYLE'])	{	// If not setting the width by style-attribute
			$retVal = ' '.$wAttrib.'="'.$size.'"';
		} else {	// Setting width by style-attribute. 'cols' MUST be avoided with NN6+
			$pixels = ceil($size*$this->form_rowsToStylewidth);
			$theStyle = 'width:'.$pixels.'px;'.$this->defStyle.$this->formElStyle($textarea?'text':'input');
			$retVal = ' style="'.htmlspecialchars($theStyle).'"';

			$class = $this->formElClass($textarea?'text':'input');
			if ($class)	{
				$retVal.= ' class="'.htmlspecialchars($class).'"';
			}
		}
		return $retVal;
	}

	/**
	 * Returns parameters to set with for a textarea field
	 *
	 * @param	integer		The abstract width (1-48)
	 * @param	string		Empty or "off" (text wrapping in the field or not)
	 * @return	string		The "cols" attribute string (or style from formWidth())
	 * @see formWidth()
	 */
	function formWidthText($size=48,$wrap='') {
		$wTags = $this->formWidth($size,1);
			// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap))!='off' && $GLOBALS['CLIENT']['BROWSER']=='net' && $GLOBALS['CLIENT']['VERSION']>=5)	{
			$wTags.=' cols="'.$size.'"';
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
	function formElStyle($type)	{
		return $this->formElStyleClassValue($type);
	}

	/**
	 * Get class attribute value for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 * @see formElStyleClassValue()
	 */
	function formElClass($type)	{
		return $this->formElStyleClassValue($type, TRUE);
	}

	/**
	 * Get style CSS values for the current field type.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @param	boolean		If set, will return value only if prefixed with CLASS, otherwise must not be prefixed "CLASS"
	 * @return	string		CSS attributes
	 */
	function formElStyleClassValue($type, $class=FALSE)	{
			// Get value according to field:
		if (isset($this->fieldStyle[$type]))	{
			$style = trim($this->fieldStyle[$type]);
		} else {
			$style = trim($this->fieldStyle['all']);
		}

			// Check class prefixed:
		if (substr($style,0,6)=='CLASS:')	{
			return $class ? trim(substr($style,6)) : '';
		} else {
			return !$class ? $style : '';
		}
	}

	/**
	 * Return default "style" / "class" attribute line.
	 *
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 */
	function insertDefStyle($type)	{
		$out = '';

		$style = trim($this->defStyle.$this->formElStyle($type));
		$out.= $style?' style="'.htmlspecialchars($style).'"':'';

		$class = $this->formElClass($type);
		$out.= $class?' class="'.htmlspecialchars($class).'"':'';

		return $out;
	}

	/**
	 * Create dynamic tab menu
	 *
	 * @param	array		Parts for the tab menu, fed to template::getDynTabMenu()
	 * @param	string		ID string for the tab menu
	 * @return	string		HTML for the menu
	 */
	function getDynTabMenu($parts, $idString) {
		if (is_object($GLOBALS['TBE_TEMPLATE']))	{
			return $GLOBALS['TBE_TEMPLATE']->getDynTabMenu($parts, $idString);
		} else {
			$output = '';
			foreach($parts as $singlePad)	{
				$output.='
				<h3>'.htmlspecialchars($singlePad['label']).'</h3>
				'.($singlePad['description'] ? '<p class="c-descr">'.nl2br(htmlspecialchars($singlePad['description'])).'</p>' : '').'
				'.$singlePad['content'];
			}

			return '<div class="typo3-dyntabmenu-divs">'.$output.'</div>';
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
	function initItemArray($fieldValue)	{
		$items = array();
		if (is_array($fieldValue['config']['items']))	{
			reset ($fieldValue['config']['items']);
			while (list($itemName,$itemValue) = each($fieldValue['config']['items']))	{
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
	function addItems($items,$iArray)	{
		global $TCA;
		if (is_array($iArray))	{
			reset($iArray);
			while(list($value,$label)=each($iArray))	{
				$items[]=array($this->sl($label),$value);
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
	function procItems($items,$iArray,$config,$table,$row,$field)	{
		global $TCA;

		$params=array();
		$params['items'] = &$items;
		$params['config'] = $config;
		$params['TSconfig'] = $iArray;
		$params['table'] = $table;
		$params['row'] = $row;
		$params['field'] = $field;

		t3lib_div::callUserFunction($config['itemsProcFunc'],$params,$this);
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
	function addSelectOptionsToItemArray($items,$fieldValue,$TSconfig,$field)	{
		global $TCA;

			// Values from foreign tables:
		if ($fieldValue['config']['foreign_table'])	{
			$items = $this->foreignTable($items,$fieldValue,$TSconfig,$field);
			if ($fieldValue['config']['neg_foreign_table'])	{
				$items = $this->foreignTable($items,$fieldValue,$TSconfig,$field,1);
			}
		}

			// Values from a file folder:
		if ($fieldValue['config']['fileFolder'])	{
			$fileFolder = t3lib_div::getFileAbsFileName($fieldValue['config']['fileFolder']);
			if (@is_dir($fileFolder))	{

					// Configurations:
				$extList = $fieldValue['config']['fileFolder_extList'];
				$recursivityLevels = isset($fieldValue['config']['fileFolder_recursions']) ? t3lib_div::intInRange($fieldValue['config']['fileFolder_recursions'],0,99) : 99;

					// Get files:
				$fileFolder = ereg_replace('\/$','',$fileFolder).'/';
				$fileArr = t3lib_div::getAllFilesAndFoldersInPath(array(),$fileFolder,$extList,0,$recursivityLevels);
				$fileArr = t3lib_div::removePrefixPathFromList($fileArr, $fileFolder);

				foreach($fileArr as $fileRef)	{
					$fI = pathinfo($fileRef);
					$icon = t3lib_div::inList('gif,png,jpeg,jpg', strtolower($fI['extension'])) ? '../'.substr($fileFolder,strlen(PATH_site)).$fileRef : '';
					$items[] = array(
						$fileRef,
						$fileRef,
						$icon
					);
				}
			}
		}

			// If 'special' is configured:
		if ($fieldValue['config']['special'])	{
			switch ($fieldValue['config']['special'])	{
				case 'tables':
					$temp_tc = array_keys($TCA);
					$descr = '';

					foreach($temp_tc as $theTableNames)	{
						if (!$TCA[$theTableNames]['ctrl']['adminOnly'])	{

								// Icon:
							$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon($theTableNames, array()),'',1);

								// Add description texts:
							if ($this->edit_showFieldHelp)	{
								$GLOBALS['LANG']->loadSingleTableDescription($theTableNames);
								$fDat = $GLOBALS['TCA_DESCR'][$theTableNames]['columns'][''];
								$descr = $fDat['description'];
							}

								// Item configuration:
							$items[] = array(
								$this->sL($TCA[$theTableNames]['ctrl']['title']),
								$theTableNames,
								$icon,
								$descr
							);
						}
					}
				break;
				case 'pagetypes':
					$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];

					foreach($theTypes as $theTypeArrays)	{
							// Icon:
						$icon = $theTypeArrays[1]!='--div--' ? '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon('pages', array('doktype' => $theTypeArrays[1])),'',1) : '';

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

					foreach($theTypes as $theTypeArrays)	{
						list($theTable, $theField) = explode(':', $theTypeArrays[1]);

							// Add description texts:
						if ($this->edit_showFieldHelp)	{
							$GLOBALS['LANG']->loadSingleTableDescription($theTable);
							$fDat = $GLOBALS['TCA_DESCR'][$theTable]['columns'][$theField];
							$descr = $fDat['description'];
						}

							// Item configuration:
						$items[] = array(
							ereg_replace(':$','',$theTypeArrays[0]),
							$theTypeArrays[1],
							'',
							$descr
						);
					}
				break;
				case 'explicitValues':
					$theTypes = t3lib_BEfunc::getExplicitAuthFieldValues();

							// Icons:
					$icons = array(
						'ALLOW' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_ok2.gif','',1),
						'DENY' => '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,'gfx/icon_fatalerror.gif','',1),
					);

						// Traverse types:
					foreach($theTypes as $tableFieldKey => $theTypeArrays)	{

						if (is_array($theTypeArrays['items']))	{
								// Add header:
							$items[] = array(
								$theTypeArrays['tableFieldLabel'],
								'--div--',
							);

								// Traverse options for this field:
							foreach($theTypeArrays['items'] as $itemValue => $itemContent)	{
									// Add item to be selected:
								$items[] = array(
									'['.$itemContent[2].'] '.$itemContent[1],
									$tableFieldKey.':'.ereg_replace('[:|,]','',$itemValue).':'.$itemContent[0],
									$icons[$itemContent[0]]
								);
							}
						}
					}
				break;
				case 'languages':
					$items = array_merge($items,t3lib_BEfunc::getSystemLanguages());
				break;
				case 'custom':
						// Initialize:
					$customOptions = $GLOBALS['TYPO3_CONF_VARS']['BE']['customPermOptions'];
					if (is_array($customOptions))	{
						foreach($customOptions as $coKey => $coValue) {
							if (is_array($coValue['items']))	{
									// Add header:
								$items[] = array(
									$GLOBALS['LANG']->sl($coValue['header']),
									'--div--',
								);

									// Traverse items:
								foreach($coValue['items'] as $itemKey => $itemCfg)	{
										// Icon:
									if ($itemCfg[1])	{
										list($icon) = $this->getIcon($itemCfg[1]);
										if ($icon)	$icon = '../'.TYPO3_mainDir.$icon;
									} else $icon = '';

										// Add item to be selected:
									$items[] = array(
										$GLOBALS['LANG']->sl($itemCfg[0]),
										$coKey.':'.ereg_replace('[:|,]','',$itemKey),
										$icon,
										$GLOBALS['LANG']->sl($itemCfg[2]),
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

					$modList = $fieldValue['config']['special']=='modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList))	{
						$descr = '';

						foreach($modList as $theMod)	{

								// Icon:
							$icon = $GLOBALS['LANG']->moduleLabels['tabs_images'][$theMod.'_tab'];
							if ($icon)	{
								$icon = '../'.substr($icon,strlen(PATH_site));
							}

								// Description texts:
							if ($this->edit_showFieldHelp)	{
								$descr = $GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tablabel'].
											chr(10).
											$GLOBALS['LANG']->moduleLabels['labels'][$theMod.'_tabdescr'];
							}

								// Item configuration:
							$items[] = array(
								$this->addSelectOptionsToItemArray_makeModuleData($theMod),
								$theMod,
								$icon,
								$descr
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
	function addSelectOptionsToItemArray_makeModuleData($value)	{
		$label = '';
			// Add label for main module:
		$pp = explode('_',$value);
		if (count($pp)>1)	$label.=$GLOBALS['LANG']->moduleLabels['tabs'][$pp[0].'_tab'].'>';
			// Add modules own label now:
		$label.= $GLOBALS['LANG']->moduleLabels['tabs'][$value.'_tab'];

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
	function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)	{
		global $TCA;

			// Init:
		$pF=$pFFlag?'neg_':'';
		$f_table = $fieldValue['config'][$pF.'foreign_table'];
		$uidPre = $pFFlag?'-':'';

			// Get query:
		$res = t3lib_BEfunc::exec_foreign_table_where_query($fieldValue,$field,$TSconfig,$pF);

			// Perform lookup
		if ($GLOBALS['TYPO3_DB']->sql_error())	{
			echo($GLOBALS['TYPO3_DB']->sql_error()."\n\nThis may indicate a table defined in tables.php is not existing in the database!");
			return array();
		}

			// Get label prefix.
		$lPrefix = $this->sL($fieldValue['config'][$pF.'foreign_table_prefix']);

			// Get icon field + path if any:
		$iField = $TCA[$f_table]['ctrl']['selicon_field'];
		$iPath = trim($TCA[$f_table]['ctrl']['selicon_field_path']);

			// Traverse the selected rows to add them:
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			t3lib_BEfunc::workspaceOL($f_table, $row);
				// Prepare the icon if available:
			if ($iField && $iPath && $row[$iField])	{
				$iParts = t3lib_div::trimExplode(',',$row[$iField],1);
				$icon = '../'.$iPath.'/'.trim($iParts[0]);
			} elseif (t3lib_div::inList('singlebox,checkbox',$fieldValue['config']['renderMode'])) {
				$icon = '../'.TYPO3_mainDir.t3lib_iconWorks::skinImg($this->backPath,t3lib_iconWorks::getIcon($f_table, $row),'',1);
			} else $icon = '';

				// Add the item:
			$items[] = array(
				t3lib_div::fixed_lgd_cs($lPrefix.strip_tags(t3lib_BEfunc::getRecordTitle($f_table,$row)),$this->titleLen),
				$uidPre.$row['uid'],
				$icon
			);
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
	function setNewBEDesign()	{

			// Wrapping all table rows for a particular record being edited:
		$this->totalWrap='
		<table border="0" cellspacing="0" cellpadding="0" width="'.($this->docLarge ? 440+150 : 440).'" class="typo3-TCEforms">'.
			'<tr class="bgColor2">
				<td>&nbsp;</td>
				<td>###RECORD_ICON### <span class="typo3-TCEforms-recHeader">###TABLE_TITLE###</span> ###ID_NEW_INDICATOR### - ###RECORD_LABEL###</td>
			</tr>'.
			'|'.
			'<tr>
				<td>&nbsp;</td>
				<td><img src="clear.gif" width="'.($this->docLarge ? 440+150 : 440).'" height="1" alt="" /></td>
			</tr>
		</table>';

			// Wrapping a single field:
		$this->fieldTemplate='
			<tr ###BGCOLOR_HEAD######CLASSATTR_2###>
				<td>###FIELD_HELP_ICON###</td>
				<td width="99%"><span style="color:###FONTCOLOR_HEAD###;"###CLASSATTR_4###><b>###FIELD_NAME###</b></span>###FIELD_HELP_TEXT###</td>
			</tr>
			<tr ###BGCOLOR######CLASSATTR_1###>
				<td nowrap="nowrap"><img name="req_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="10" height="10" alt="" /><img name="cm_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="7" height="10" alt="" /></td>
				<td valign="top">###FIELD_ITEM######FIELD_PAL_LINK_ICON###</td>
			</tr>';

		$this->palFieldTemplate='
			<tr ###BGCOLOR######CLASSATTR_1###>
				<td>&nbsp;</td>
				<td nowrap="nowrap" valign="top">###FIELD_PALETTE###</td>
			</tr>';
		$this->palFieldTemplateHeader='
			<tr ###BGCOLOR_HEAD######CLASSATTR_2###>
				<td>&nbsp;</td>
				<td nowrap="nowrap" valign="top"><strong>###FIELD_HEADER###</strong></td>
			</tr>';

		$this->sectionWrap='
			<tr>
				<td colspan="2"><img src="clear.gif" width="1" height="###SPACE_BEFORE###" alt="" /></td>
			</tr>
			<tr>
				<td colspan="2"><table ###TABLE_ATTRIBS###>###CONTENT###</table></td>
			</tr>
			';
	}

	/**
	 * This inserts the content of $inArr into the field-template
	 *
	 * @param	array		Array with key/value pairs to insert in the template.
	 * @param	string		Alternative template to use instead of the default.
	 * @return	string
	 */
	function intoTemplate($inArr,$altTemplate='')	{
				// Put into template_
		$fieldTemplateParts = explode('###FIELD_',$this->rplColorScheme($altTemplate?$altTemplate:$this->fieldTemplate));
		reset($fieldTemplateParts);
		$out=current($fieldTemplateParts);
		while(list(,$part)=each($fieldTemplateParts))	{
			list($key,$val)=explode('###',$part,2);
			$out.=$inArr[$key];
			$out.=$val;
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
	function addUserTemplateMarkers($marker,$table,$field,$row,&$PA)	{
		return $marker;
	}

	/**
	 * Wrapping labels
	 * Currently not implemented - just returns input value.
	 *
	 * @param	string		Input string.
	 * @return	string		Output string.
	 */
	function wrapLabels($str)	{
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
	function wrapTotal($c,$rec,$table)	{
		$parts = $this->replaceTableWrap(explode('|',$this->totalWrap,2),$rec,$table);
		return $parts[0].$c.$parts[1].implode('',$this->hiddenFieldAccum);
	}

	/**
	 * This replaces markers in the total wrap
	 *
	 * @param	array		An array of template parts containing some markers.
	 * @param	array		The record
	 * @param	string		The table name
	 * @return	string
	 */
	function replaceTableWrap($arr,$rec,$table)	{
		global $TCA;
		reset($arr);
		while(list($k,$v)=each($arr))	{

				// Make "new"-label
			if (strstr($rec['uid'],'NEW'))	{
				$newLabel = ' <span class="typo3-TCEforms-newToken">'.
							$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new',1).
							'</span>';

				$truePid = t3lib_BEfunc::getTSconfig_pidValue($table,$rec['uid'],$rec['pid']);
				$prec = t3lib_BEfunc::getRecordWSOL('pages',$truePid,'title');
				$rLabel = '<em>[PID: '.$truePid.'] '.htmlspecialchars(trim(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle('pages',$prec),40))).'</em>';
			} else {
				$newLabel = ' <span class="typo3-TCEforms-recUid">['.$rec['uid'].']</span>';
				$rLabel  = htmlspecialchars(trim(t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($table,$rec),40)));
			}

				// Make substitutions:
			$arr[$k] = str_replace('###ID_NEW_INDICATOR###', $newLabel, $arr[$k]);
			$arr[$k] = str_replace('###RECORD_LABEL###',$rLabel,$arr[$k]);
			$arr[$k] = str_replace('###TABLE_TITLE###',htmlspecialchars($this->sL($TCA[$table]['ctrl']['title'])),$arr[$k]);

			$titleA=t3lib_BEfunc::titleAltAttrib($this->getRecordPath($table,$rec));
			$arr[$k]=str_replace('###RECORD_ICON###',t3lib_iconWorks::getIconImage($table,$rec,$this->backPath,'class="absmiddle"'.$titleA),$arr[$k]);
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
	function wrapBorder(&$out_array,&$out_pointer)	{
		if ($this->sectionWrap && $out_array[$out_pointer])	{
			$tableAttribs='';
			$tableAttribs.= $this->borderStyle[0] ? ' style="'.htmlspecialchars($this->borderStyle[0]).'"':'';
			$tableAttribs.= $this->borderStyle[2] ? ' background="'.htmlspecialchars($this->backPath.$this->borderStyle[2]).'"':'';
			$tableAttribs.= $this->borderStyle[3] ? ' class="'.htmlspecialchars($this->borderStyle[3]).'"':'';
			if ($tableAttribs)	{
				$tableAttribs='border="0" cellspacing="0" cellpadding="0" width="100%"'.$tableAttribs;
				$out_array[$out_pointer] = str_replace('###CONTENT###',$out_array[$out_pointer],
					str_replace('###TABLE_ATTRIBS###',$tableAttribs,
						str_replace('###SPACE_BEFORE###',intval($this->borderStyle[1]),$this->sectionWrap)));
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
	function rplColorScheme($inTemplate)	{
			// Colors:
		$inTemplate = str_replace('###BGCOLOR###',$this->colorScheme[0]?' bgcolor="'.$this->colorScheme[0].'"':'',$inTemplate);
		$inTemplate = str_replace('###BGCOLOR_HEAD###',$this->colorScheme[1]?' bgcolor="'.$this->colorScheme[1].'"':'',$inTemplate);
		$inTemplate = str_replace('###FONTCOLOR_HEAD###',$this->colorScheme[3],$inTemplate);

			// Classes:
		$inTemplate = str_replace('###CLASSATTR_1###',$this->classScheme[0]?' class="'.$this->classScheme[0].'"':'',$inTemplate);
		$inTemplate = str_replace('###CLASSATTR_2###',$this->classScheme[1]?' class="'.$this->classScheme[1].'"':'',$inTemplate);
		$inTemplate = str_replace('###CLASSATTR_4###',$this->classScheme[3]?' class="'.$this->classScheme[3].'"':'',$inTemplate);

		return $inTemplate;
	}

	/**
	 * Returns divider.
	 * Currently not implemented and returns only blank value.
	 *
	 * @return	string
	 */
	function getDivider()	{
		//return "<hr />";
	}

	/**
	 * Creates HTML output for a palette
	 *
	 * @param	array		The palette array to print
	 * @return	string		HTML output
	 */
	function printPalette($palArr)	{

			// Init color/class attributes:
		$ccAttr2 = $this->colorScheme[2] ? ' bgcolor="'.$this->colorScheme[2].'"' : '';
		$ccAttr2.= $this->classScheme[2] ? ' class="'.$this->classScheme[2].'"' : '';
		$ccAttr4 = $this->colorScheme[4] ? ' style="color:'.$this->colorScheme[4].'"' : '';
		$ccAttr4.= $this->classScheme[4] ? ' class="'.$this->classScheme[4].'"' : '';

			// Traverse palette fields and render them into table rows:
		foreach($palArr as $content)	{
			$hRow[]='<td'.$ccAttr2.'>&nbsp;</td>
					<td nowrap="nowrap"'.$ccAttr2.'>'.
						'<span'.$ccAttr4.'>'.
							$content['NAME'].
						'</span>'.
					'</td>';
			$iRow[]='<td valign="top">'.
						'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="10" height="10" vspace="4" alt="" />'.
						'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="7" height="10" vspace="4" alt="" />'.
					'</td>
					<td nowrap="nowrap" valign="top">'.
						$content['ITEM'].
						$content['HELP_ICON'].
					'</td>';
		}

			// Final wrapping into the table:
		$out='<table border="0" cellpadding="0" cellspacing="0" class="typo3-TCEforms-palette">
			<tr>
				<td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>'.
					implode('
				',$hRow).'
			</tr>
			<tr>
				<td></td>'.
					implode('
				',$iRow).'
			</tr>
		</table>';

		return $out;
	}

	/**
	 * Returns help-text ICON if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @param	boolean		Force the return of the help-text icon.
	 * @return	string		HTML, <a>-tag with
	 */
	function helpTextIcon($table,$field,$force=0)	{
		if ($this->globalShowHelp && $GLOBALS['TCA_DESCR'][$table]['columns'][$field] && (($this->edit_showFieldHelp=='icon'&&!$this->doLoadTableDescr($table)) || $force))	{
			$aOnClick = 'vHWin=window.open(\''.$this->backPath.'view_help.php?tfID='.($table.'.'.$field).'\',\'viewFieldHelp\',\'height=400,width=600,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.
					'</a>';
		} else {
				// Detects fields with no CSH and outputs dummy line to insert into CSH locallang file:
			#debug(array("'".$field.".description' => '[FILL IN] ".$table."->".$field."',"),$table);
			return '&nbsp;';
		}
	}

	/**
	 * Returns help text DESCRIPTION, if configured for.
	 *
	 * @param	string		The table name
	 * @param	string		The field name
	 * @return	string
	 */
	function helpText($table,$field)	{
		if ($this->globalShowHelp && $GLOBALS['TCA_DESCR'][$table]['columns'][$field] && ($this->edit_showFieldHelp=='text' || $this->doLoadTableDescr($table)))	{
			$fDat = $GLOBALS['TCA_DESCR'][$table]['columns'][$field];
			return '<table border="0" cellpadding="2" cellspacing="0" width="90%"><tr><td valign="top" width="14">'.
					$this->helpTextIcon(
						$table,
						$field,
						$fDat['details']||$fDat['syntax']||$fDat['image_descr']||$fDat['image']||$fDat['seeAlso']
					).
					'</td><td valign="top"><span class="typo3-TCEforms-helpText">'.
					$GLOBALS['LANG']->hscAndCharConv(strip_tags($fDat['description']),1).
					'</span></td></tr></table>';
		}
	}

	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defColorScheme plus input string.
	 *
	 * @param	string		A color scheme string.
	 * @return	void
	 */
	function setColorScheme($scheme)	{
		$this->colorScheme = $this->defColorScheme;
		$this->classScheme = $this->defClassScheme;

		$parts = t3lib_div::trimExplode(',',$scheme);
		foreach($parts as $key => $col)	{
				// Split for color|class:
			list($color,$class) = t3lib_div::trimExplode('|',$col);

				// Handle color values:
			if ($color)	$this->colorScheme[$key] = $color;
			if ($color=='-')	$this->colorScheme[$key] = '';

				// Handle class values:
			if ($class)	$this->classScheme[$key] = $class;
			if ($class=='-')	$this->classScheme[$key] = '';
		}
	}

	/**
	 * Reset color schemes.
	 *
	 * @return	void
	 */
	function resetSchemes()	{
		$this->setColorScheme($GLOBALS['TBE_STYLES']['colorschemes'][0]);
		$this->fieldStyle = $GLOBALS['TBE_STYLES']['styleschemes'][0];
		$this->borderStyle = $GLOBALS['TBE_STYLES']['borderschemes'][0];
	}

	/**
	 * Store current color scheme
	 *
	 * @return	void
	 */
	function storeSchemes()	{
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
	function restoreSchemes()	{
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
	function JStop()	{

		$out = '';

			// Additional top HTML:
		if (count($this->additionalCode_pre))	{
			$out.= implode('

				<!-- NEXT: -->
			',$this->additionalCode_pre);
		}

			// Additional top JavaScript
		if (count($this->additionalJS_pre))	{
			$out.='


		<!--
			JavaScript in top of page (before form):
		-->

		<script type="text/javascript">
			/*<![CDATA[*/

			'.implode('

				// NEXT:
			',$this->additionalJS_pre).'

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
	 * 		Example use:
	 *
	 * 		$msg.='Distribution time (hh:mm dd-mm-yy):<br /><input type="text" name="send_mail_datetime_hr" onchange="typo3FormFieldGet(\'send_mail_datetime\', \'datetime\', \'\', 0,0);"'.$GLOBALS['TBE_TEMPLATE']->formWidth(20).' /><input type="hidden" value="'.time().'" name="send_mail_datetime" /><br />';
	 * 		$this->extJSCODE.='typo3FormFieldSet("send_mail_datetime", "datetime", "", 0,0);';
	 *
	 * 		... and then include the result of this function after the form
	 *
	 * @param	string		The identification of the form on the page.
	 * @return	string		A <script></script> section with JavaScript.
	 */
	function JSbottom($formname='forms[0]')	{

				// required
			$reqLines=array();
			$reqLinesCheck=array();
			$reqLinesSet=array();
			reset($this->requiredFields);
			while(list($itemImgName,$itemName)=each($this->requiredFields))	{
				$reqLines[]="					TBE_REQUIRED['".$itemName."']=1;";
				$reqLinesCheck[]="					if (!document.".$formname."['".$itemName."'].value)	{OK=0;}";
				$reqLinesSet[]="					if (!document.".$formname."['".$itemName."'].value)	{TBE_EDITOR_setImage('req_".$itemImgName."','TBE_EDITOR_req');}";
			}

			$reqRange=array();
			$reqRangeCheck=array();
			$reqRangeSet=array();
			reset($this->requiredElements);
			while(list($itemName,$range)=each($this->requiredElements))	{
				$reqRange[]="					TBE_RANGE['".$itemName."']=1;";
				$reqRange[]="					TBE_RANGE_lower['".$itemName."']=".$range[0].";";
				$reqRange[]="					TBE_RANGE_upper['".$itemName."']=".$range[1].";";
				$reqRangeCheck[]="					if (!TBE_EDITOR_checkRange(document.".$formname."['".$itemName."_list'],".$range[0].",".$range[1]."))	{OK=0;}";
				$reqRangeSet[]="					if (!TBE_EDITOR_checkRange(document.".$formname."['".$itemName."_list'],".$range[0].",".$range[1]."))	{TBE_EDITOR_setImage('req_".$range['imgName']."','TBE_EDITOR_req');}";
			}

			$this->TBE_EDITOR_fieldChanged_func='TBE_EDITOR_fieldChanged_fName(fName,formObj[fName+"_list"]);';

			if ($this->loadMD5_JS)	{
			$out.='
			<script type="text/javascript" src="'.$this->backPath.'md5.js"></script>';
			}
			$out.='
			<script type="text/javascript" src="'.$this->backPath.'../t3lib/jsfunc.evalfield.js"></script>
			<script type="text/javascript">
				/*<![CDATA[*/

				var TBE_EDITOR_req=new Image(); 	TBE_EDITOR_req.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/required_h.gif','',1).'";
				var TBE_EDITOR_cm=new Image(); 		TBE_EDITOR_cm.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/content_client.gif','',1).'";
				var TBE_EDITOR_sel=new Image(); 	TBE_EDITOR_sel.src = "'.t3lib_iconWorks::skinImg($this->backPath,'gfx/content_selected.gif','',1).'";
				var TBE_EDITOR_clear=new Image(); 	TBE_EDITOR_clear.src = "'.$this->backPath.'clear.gif";
				var TBE_REQUIRED=new Array();
'.implode(chr(10),$reqLines).'

				var TBE_RANGE=new Array();
				var TBE_RANGE_lower=new Array();
				var TBE_RANGE_upper=new Array();
'.implode(chr(10),$reqRange).'

				// $this->additionalJS_post:
'.implode(chr(10),$this->additionalJS_post).'

				var TBE_EDITOR_loadTime = 0;
				var TBE_EDITOR_isChanged = 0;

				function TBE_EDITOR_loginRefreshed()	{	//
					var date = new Date();
					TBE_EDITOR_loadTime = Math.floor(date.getTime()/1000);
					if (top.busy && top.busy.loginRefreshed)	{top.busy.loginRefreshed();}
				}
				function TBE_EDITOR_checkLoginTimeout()	{	//
					var date = new Date();
					var theTime = Math.floor(date.getTime()/1000);
					if (theTime > TBE_EDITOR_loadTime+'.intval($GLOBALS['BE_USER']->auth_timeout_field).'-10)	{
						return true;
					}
				}
				function TBE_EDITOR_setHiddenContent(RTEcontent,theField)	{	//
					document.'.$formname.'[theField].value = RTEcontent;
					alert(document.'.$formname.'[theField].value);
				}
				function TBE_EDITOR_fieldChanged_fName(fName,el)	{	//
					var idx='.(2+substr_count($this->prependFormFieldNames,'[')).';
					var table = TBE_EDITOR_split(fName, "[", idx);
					var uid = TBE_EDITOR_split(fName, "[", idx+1);
					var field = TBE_EDITOR_split(fName, "[", idx+2);

					table = table.substr(0,table.length-1);
					uid = uid.substr(0,uid.length-1);
					field = field.substr(0,field.length-1);
					TBE_EDITOR_fieldChanged(table,uid,field,el);
				}
				function TBE_EDITOR_fieldChanged(table,uid,field,el)	{	//
					var theField = "'.$this->prependFormFieldNames.'["+table+"]["+uid+"]["+field+"]";
					TBE_EDITOR_isChanged = 1;

						// Set change image:
					var imgObjName = "cm_"+table+"_"+uid+"_"+field;
					TBE_EDITOR_setImage(imgObjName,"TBE_EDITOR_cm");

						// Set change image
					if (document.'.$formname.'[theField] && document.'.$formname.'[theField].type=="select-one" && document.'.$formname.'[theField+"_selIconVal"])	{
						var imgObjName = "selIcon_"+table+"_"+uid+"_"+field+"_";
						TBE_EDITOR_setImage(imgObjName+document.'.$formname.'[theField+"_selIconVal"].value,"TBE_EDITOR_clear");
						document.'.$formname.'[theField+"_selIconVal"].value = document.'.$formname.'[theField].selectedIndex;
						TBE_EDITOR_setImage(imgObjName+document.'.$formname.'[theField+"_selIconVal"].value,"TBE_EDITOR_sel");
					}

						// Set required flag:
					var imgReqObjName = "req_"+table+"_"+uid+"_"+field;
					if (TBE_REQUIRED[theField] && document.'.$formname.'[theField])	{
						if (document.'.$formname.'[theField].value)	{
							TBE_EDITOR_setImage(imgReqObjName,"TBE_EDITOR_clear");
						} else {
							TBE_EDITOR_setImage(imgReqObjName,"TBE_EDITOR_req");
						}
					}
					if (TBE_RANGE[theField] && document.'.$formname.'[theField])	{
						if (TBE_EDITOR_checkRange(document.'.$formname.'[theField+"_list"],TBE_RANGE_lower[theField],TBE_RANGE_upper[theField]))	{
							TBE_EDITOR_setImage(imgReqObjName,"TBE_EDITOR_clear");
						} else {
							TBE_EDITOR_setImage(imgReqObjName,"TBE_EDITOR_req");
						}
					}
					'.(!$this->isPalettedoc?'':'
					TBE_EDITOR_setOriginalFormFieldValue(theField);
					').'
				}
				'.($this->isPalettedoc?'
				function TBE_EDITOR_setOriginalFormFieldValue(theField)	{	//
					if ('.$this->isPalettedoc.' && '.$this->isPalettedoc.'.document.'.$formname.' && '.$this->isPalettedoc.'.document.'.$formname.'[theField]) {
						'.$this->isPalettedoc.'.document.'.$formname.'[theField].value = document.'.$formname.'[theField].value;
					}
				}
				':'').'
				function TBE_EDITOR_isFormChanged(noAlert)	{	//
					if (TBE_EDITOR_isChanged && !noAlert && confirm('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsChanged')).'))	{
						return 0;
					}
					return TBE_EDITOR_isChanged;
				}
				function TBE_EDITOR_checkAndDoSubmit(sendAlert)	{	//
					if (TBE_EDITOR_checkSubmit(sendAlert))	{
						TBE_EDITOR_submitForm();
					}
				}

				/**
				 * Checks if the form can be submitted according to any possible restrains like required values, item numbers etc.
				 * Returns true if the form can be submitted, otherwise false (and might issue an alert message, if "sendAlert" is 1)
				 * If "sendAlert" is false, no error message will be shown upon false return value (if "1" then it will).
				 * If "sendAlert" is "-1" then the function will ALWAYS return true regardless of constraints (except if login has expired) - this is used in the case where a form field change requests a form update and where it is accepted that constraints are not observed (form layout might change so other fields are shown...)
				 */
				function TBE_EDITOR_checkSubmit(sendAlert)	{	//
					if (TBE_EDITOR_checkLoginTimeout() && confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_refresh_login')).'))	{
						vHWin=window.open(\''.$this->backPath.'login_frameset.php?\',\'relogin\',\'height=300,width=400,status=0,menubar=0\');
						vHWin.focus();
						return false;
					}
					var OK=1;

					// $this->additionalJS_post:
'.implode(chr(10),$this->additionalJS_submit).'

					if(!OK)	{
						if (!confirm(unescape("SYSTEM ERROR: One or more Rich Text Editors on the page could not be contacted. This IS an error, although it should not be regular.\nYou can save the form now by pressing OK, but you will loose the Rich Text Editor content if you do.\n\nPlease report the error to your administrator if it persists.")))	{
							return false;
						} else {
							OK = 1;
						}
					}

'.implode(chr(10),$reqLinesCheck).'
'.implode(chr(10),$reqRangeCheck).'

					if (OK || sendAlert==-1)	{
						return true;
					} else {
						if(sendAlert)	alert('.$GLOBALS['LANG']->JScharCode($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.fieldsMissing')).');
						return false;
					}
				}
				function TBE_EDITOR_checkRange(el,lower,upper)	{	//
					if (el && el.length>=lower && el.length<=upper) {
						return true;
					} else {
						return false;
					}
				}
				function TBE_EDITOR_initRequired()	{	//
'.implode(chr(10),$reqLinesSet).'
'.implode(chr(10),$reqRangeSet).'
				}
				function TBE_EDITOR_setImage(name,imgName)	{	//
					if (document[name]) {document[name].src = eval(imgName+".src");}
				}
				function TBE_EDITOR_submitForm()	{	//
					'.($this->doSaveFieldName?'document.'.$this->formName."['".$this->doSaveFieldName."'].value=1;":'').'
					document.'.$this->formName.'.submit();
				}
				function typoSetup	()	{	//
					this.passwordDummy = "********";
					this.decimalSign = ".";
				}
				var TS = new typoSetup();
				var evalFunc = new evalFunc();
				evalFunc.USmode = '.($GLOBALS['TYPO3_CONF_VARS']['SYS']['USdateFormat']?'1':'0').';

				function typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue)	{	//
					if (document.'.$formname.'[theField])	{
						var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
						var theValue = document.'.$formname.'[theField].value;
						if (checkbox && theValue==checkboxValue)	{
							document.'.$formname.'[theField+"_hr"].value="";
							if (document.'.$formname.'[theField+"_cb"])	document.'.$formname.'[theField+"_cb"].checked = "";
						} else {
							document.'.$formname.'[theField+"_hr"].value = evalFunc.outputObjValue(theFObj, theValue);
							if (document.'.$formname.'[theField+"_cb"])	document.'.$formname.'[theField+"_cb"].checked = "on";
						}
					}
				}
				function typo3FormFieldGet(theField, evallist, is_in, checkbox, checkboxValue, checkbox_off, checkSetValue)	{	//
					if (document.'.$formname.'[theField])	{
						var theFObj = new evalFunc_dummy (evallist,is_in, checkbox, checkboxValue);
						if (checkbox_off)	{
							if (document.'.$formname.'[theField+"_cb"].checked)	{
								document.'.$formname.'[theField].value=checkSetValue;
							} else {
								document.'.$formname.'[theField].value=checkboxValue;
							}
						}else{
							document.'.$formname.'[theField].value = evalFunc.evalObjValue(theFObj, document.'.$formname.'[theField+"_hr"].value);
						}
						typo3FormFieldSet(theField, evallist, is_in, checkbox, checkboxValue);
					}
				}
				function TBE_EDITOR_split(theStr1, delim, index) {		//
					var theStr = ""+theStr1;
					var lengthOfDelim = delim.length;
					sPos = -lengthOfDelim;
					if (index<1) {index=1;}
					for (var a=1; a<index; a++)	{
						sPos = theStr.indexOf(delim, sPos+lengthOfDelim);
						if (sPos==-1)	{return null;}
					}
					ePos = theStr.indexOf(delim, sPos+lengthOfDelim);
					if(ePos == -1)	{ePos = theStr.length;}
					return (theStr.substring(sPos+lengthOfDelim,ePos));
				}
				function TBE_EDITOR_palUrl(inData,fieldList,fieldNum,table,uid,isOnFocus) {		//
					var url = "'.$this->backPath.'alt_palette.php?inData="+inData+"&formName='.rawurlencode($this->formName).'"+"&prependFormFieldNames='.rawurlencode($this->prependFormFieldNames).'";
					var field = "";
					var theField="";
					for (var a=0; a<fieldNum;a++)	{
						field = TBE_EDITOR_split(fieldList, ",", a+1);
						theField = "'.$this->prependFormFieldNames.'["+table+"]["+uid+"]["+field+"]";
						if (document.'.$formname.'[theField])		url+="&rec["+field+"]="+TBE_EDITOR_rawurlencode(document.'.$formname.'[theField].value);
					}
					if (top.topmenuFrame)	{
						top.topmenuFrame.location.href = url+"&backRef="+(top.content.list_frame ? (top.content.list_frame.view_frame ? "top.content.list_frame.view_frame" : "top.content.list_frame") : "top.content");
					} else if (!isOnFocus) {
						var vHWin=window.open(url,"palette","height=300,width=200,status=0,menubar=0,scrollbars=1");
						vHWin.focus();
					}
				}
				function TBE_EDITOR_curSelected(theField)	{	//
					var fObjSel = document.'.$formname.'[theField];
					var retVal="";
					if (fObjSel)	{
						if (fObjSel.type=="select-multiple" || fObjSel.type=="select-one")	{
							var l=fObjSel.length;
							for (a=0;a<l;a++)	{
								if (fObjSel.options[a].selected==1)	{
									retVal+=fObjSel.options[a].value+",";
								}
							}
						}
					}
					return retVal;
				}
				function TBE_EDITOR_rawurlencode(str,maxlen)	{	//
					var output = str;
					if (maxlen)	output = output.substr(0,200);
					output = escape(output);
					output = TBE_EDITOR_str_replace("*","%2A", output);
					output = TBE_EDITOR_str_replace("+","%2B", output);
					output = TBE_EDITOR_str_replace("/","%2F", output);
					output = TBE_EDITOR_str_replace("@","%40", output);
					return output;
				}
				function TBE_EDITOR_str_replace(match,replace,string)	{	//
					var input = ""+string;
					var matchStr = ""+match;
					if (!matchStr)	{return string;}
					var output = "";
					var pointer=0;
					var pos = input.indexOf(matchStr);
					while (pos!=-1)	{
						output+=""+input.substr(pointer, pos-pointer)+replace;
						pointer=pos+matchStr.length;
						pos = input.indexOf(match,pos+1);
					}
					output+=""+input.substr(pointer);
					return output;
				}
				/*]]>*/
			</script>
			<script type="text/javascript">
				/*<![CDATA[*/

				'.$this->extJSCODE.'

				TBE_EDITOR_initRequired();
				TBE_EDITOR_loginRefreshed();
				/*]]>*/
			</script>';
			return $out;
	}

	/**
	 * Used to connect the db/file browser with this document and the formfields on it!
	 *
	 * @param	string		Form object reference (including "document.")
	 * @return	string		JavaScript functions/code (NOT contained in a <script>-element)
	 */
	function dbFileCon($formObj='document.forms[0]')	{
		$str='

			// ***************
			// Used to connect the db/file browser with this document and the formfields on it!
			// ***************

			var browserWin="";

			function setFormValueOpenBrowser(mode,params) {	//
				var url = "'.$this->backPath.'browser.php?mode="+mode+"&bparams="+params;

				browserWin = window.open(url,"Typo3WinBrowser","height=350,width="+(mode=="db"?650:600)+",status=0,menubar=0,resizable=1,scrollbars=1");
				browserWin.focus();
			}
			function setFormValueFromBrowseWin(fName,value,label)	{	//
				var formObj = setFormValue_getFObj(fName)
				if (formObj && value!="--div--")	{
					fObj = formObj[fName+"_list"];
						// Inserting element
					var l=fObj.length;
					var setOK=1;
					if (!formObj[fName+"_mul"] || formObj[fName+"_mul"].value==0)	{
						for (a=0;a<l;a++)	{
							if (fObj.options[a].value==value)	{
								setOK=0;
							}
						}
					}
					if (setOK)	{
						fObj.length++;
						fObj.options[l].value=value;
						fObj.options[l].text=unescape(label);

							// Traversing list and set the hidden-field
						setHiddenFromList(fObj,formObj[fName]);
						'.$this->TBE_EDITOR_fieldChanged_func.'
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
				var formObj = setFormValue_getFObj(fName)
				if (formObj)	{
					var localArray_V = new Array();
					var localArray_L = new Array();
					var localArray_S = new Array();
					var fObjSel = formObj[fName+"_list"];
					var l=fObjSel.length;
					var c=0;
					if (type=="Remove" || type=="Top" || type=="Bottom")	{
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

					'.$this->TBE_EDITOR_fieldChanged_func.'
				}
			}
			function setFormValue_getFObj(fName)	{	//
				var formObj = '.$formObj.';
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
	function printNeededJSFunctions()	{
			// JS evaluation:
		$out = $this->JSbottom($this->formName);
			//
		if ($this->printNeededJS['dbFileIcons'])	{
			$out.= '



			<!--
			 	JavaScript after the form has been drawn:
			-->

			<script type="text/javascript">
				/*<![CDATA[*/
			'.$this->dbFileCon('document.'.$this->formName).'
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
	function printNeededJSFunctions_top()	{
			// JS evaluation:
		$out = $this->JStop($this->formName);
		return $out;
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
	function getDefaultRecord($table,$pid=0)	{
		global $TCA;
		if ($TCA[$table])	{
			t3lib_div::loadTCA($table);
			$row = array();

			if ($pid<0 && $TCA[$table]['ctrl']['useColumnsForDefaultValues'])	{
					// Fetches the previous record:
				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', $table, 'uid='.abs($pid).t3lib_BEfunc::deleteClause($table));
				if ($drow = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
						// Gets the list of fields to copy from the previous record.
					$fArr = explode(',',$TCA[$table]['ctrl']['useColumnsForDefaultValues']);
					foreach($fArr as $theF)	{
						if ($TCA[$table]['columns'][$theF])	{
							$row[$theF] = $drow[$theF];
						}
					}
				}
				$GLOBALS['TYPO3_DB']->sql_free_result($res);
			}

			foreach($TCA[$table]['columns'] as $field => $info)	{
				if (isset($info['config']['default']))	{
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
	function getRecordPath($table,$rec)	{
		t3lib_BEfunc::fixVersioningPid($table,$rec);
		list($tscPID,$thePidValue)=$this->getTSCpid($table,$rec['uid'],$rec['pid']);
		if ($thePidValue>=0)	{
			return t3lib_BEfunc::getRecordPath($tscPID,$this->readPerms(),15);
		}
	}

	/**
	 * Returns the select-page read-access SQL clause.
	 * Returns cached string, so you can call this function as much as you like without performance loss.
	 *
	 * @return	string
	 */
	function readPerms()	{
		if (!$this->perms_clause_set)	{
			$this->perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
			$this->perms_clause_set=1;
		}
		return $this->perms_clause;
	}

	/**
	 * Fetches language label for key
	 *
	 * @param	string		Language label reference, eg. 'LLL:EXT:lang/locallang_core.php:labels.blablabla'
	 * @return	string		The value of the label, fetched for the current backend language.
	 */
	function sL($str)	{
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
	function getLL($str)	{
		$content = '';

		switch(substr($str,0,2))	{
			case 'l_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.'.substr($str,2));
			break;
			case 'm_':
				$content = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.substr($str,2));
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
	function isPalettesCollapsed($table,$palette)	{
		global $TCA;

		if ($TCA[$table]['ctrl']['canNotCollapse']) return 0;
		if (is_array($TCA[$table]['palettes'][$palette]) && $TCA[$table]['palettes'][$palette]['canNotCollapse'])	return 0;
		return $this->palettesCollapsed;
	}

	/**
	 * Returns true, if the evaluation of the required-field code is OK.
	 *
	 * @param	string		The required-field code
	 * @param	array		The record to evaluate
	 * @return	boolean
	 */
	function isDisplayCondition($displayCond,$row,$ffValueKey='')	{
		$output = FALSE;

		$parts = explode(':',$displayCond);
		switch((string)$parts[0])	{	// Type of condition:
			case 'FIELD':
				$theFieldValue = $ffValueKey ? $row[$parts[1]][$ffValueKey] : $row[$parts[1]];

				switch((string)$parts[2])	{
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $theFieldValue ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
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
						$cmpParts = explode('-',$parts[3]);
						$output = $theFieldValue >= $cmpParts[0] && $theFieldValue <= $cmpParts[1];
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case 'IN':
					case '!IN':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
					case '=':
					case '!=':
						$output = t3lib_div::inList($parts[3],$theFieldValue);
						if ($parts[2]{0}=='!')	$output = !$output;
					break;
				}
			break;
			case 'EXT':
				switch((string)$parts[2])	{
					case 'LOADED':
						if (strtolower($parts[3])=='true')	{
							$output = t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !t3lib_extMgm::isLoaded($parts[1]) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'REC':
				switch((string)$parts[1])	{
					case 'NEW':
						if (strtolower($parts[2])=='true')	{
							$output = !(intval($row['uid']) > 0) ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = (intval($row['uid']) > 0) ? TRUE : FALSE;
						}
					break;
				}
			break;
			case 'HIDE_L10N_SIBLINGS':
				if ($ffValueKey==='vDEF')	{
					$output = TRUE;
				} elseif ($parts[1]==='except_admin' && $GLOBALS['BE_USER']->isAdmin())	{
					$output = TRUE;
				}
			break;
			case 'VERSION':
				switch((string)$parts[1])	{
					case 'IS':
						if (strtolower($parts[2])=='true')	{
							$output = intval($row['pid'])==-1 ? TRUE : FALSE;
						} elseif (strtolower($parts[2])=='false') {
							$output = !(intval($row['pid'])==-1) ? TRUE : FALSE;
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
	function getTSCpid($table,$uid,$pid)	{
		$key = $table.':'.$uid.':'.$pid;
		if (!isset($this->cache_getTSCpid[$key]))	{
			$this->cache_getTSCpid[$key] = t3lib_BEfunc::getTSCpid($table,$uid,$pid);
		}
		return $this->cache_getTSCpid[$key];
	}

	/**
	 * Returns true if descriptions should be loaded always
	 *
	 * @param	string		Table for which to check
	 * @return	boolean
	 */
	function doLoadTableDescr($table)	{
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
	function getAvailableLanguages($onlyIsoCoded=1,$setDefault=1)	{
		$isL = t3lib_extMgm::isLoaded('static_info_tables');

			// Find all language records in the system:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('static_lang_isocode,title,uid', 'sys_language', 'pid=0 AND hidden=0'.t3lib_BEfunc::deleteClause('sys_language'), '', 'title');

			// Traverse them:
		$output=array();
		if ($setDefault)	{
			$output[0]=array(
				'uid' => 0,
				'title' => 'Default language',
				'ISOcode' => 'DEF'
			);
		}
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$output[$row['uid']]=$row;

			if ($isL && $row['static_lang_isocode'])	{
				$rr = t3lib_BEfunc::getRecord('static_languages',$row['static_lang_isocode'],'lg_iso_2');
				if ($rr['lg_iso_2'])	$output[$row['uid']]['ISOcode']=$rr['lg_iso_2'];
			}

			if ($onlyIsoCoded && !$output[$row['uid']]['ISOcode'])	unset($output[$row['uid']]);
		}
		return $output;
	}
}









/**
 * Extension class for the rendering of TCEforms in the frontend
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
class t3lib_TCEforms_FE extends t3lib_TCEforms {

	/**
	 * Function for wrapping labels.
	 *
	 * @param	string		The string to wrap
	 * @return	string
	 */
	function wrapLabels($str)	{
		return '<font face="verdana" size="1" color="black">'.$str.'</font>';
	}

	/**
	 * Prints the palette in the frontend editing (forms-on-page?)
	 *
	 * @param	array		The palette array to print
	 * @return	string		HTML output
	 */
	function printPalette($palArr)	{
		$out='';
		reset($palArr);
		$bgColor=' bgcolor="#D6DAD0"';
		while(list(,$content)=each($palArr))	{
			$hRow[]='<td'.$bgColor.'><font face="verdana" size="1">&nbsp;</font></td><td nowrap="nowrap"'.$bgColor.'><font color="#666666" face="verdana" size="1">'.$content['NAME'].'</font></td>';
			$iRow[]='<td valign="top">'.
						'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="10" height="10" alt="" />'.
						'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="7" height="10" alt="" />'.
						'</td><td nowrap="nowrap" valign="top">'.$content['ITEM'].$content['HELP_ICON'].'</td>';
		}
		$out='<table border="0" cellpadding="0" cellspacing="0">
			<tr><td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>'.implode('',$hRow).'</tr>
			<tr><td></td>'.implode('',$iRow).'</tr>
		</table>';

		return $out;
	}

	/**
	 * Sets the fancy front-end design of the editor.
	 * Frontend
	 *
	 * @return	void
	 */
	function setFancyDesign()	{
		$this->fieldTemplate='
	<tr>
		<td nowrap="nowrap" bgcolor="#F6F2E6">###FIELD_HELP_ICON###<font face="verdana" size="1" color="black"><b>###FIELD_NAME###</b></font>###FIELD_HELP_TEXT###</td>
	</tr>
	<tr>
		<td nowrap="nowrap" bgcolor="#ABBBB4"><img name="req_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="10" height="10" alt="" /><img name="cm_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="7" height="10" alt="" /><font face="verdana" size="1" color="black">###FIELD_ITEM###</font>###FIELD_PAL_LINK_ICON###</td>
	</tr>	';

		$this->totalWrap='<table border="0" cellpadding="1" cellspacing="0" bgcolor="black"><tr><td><table border="0" cellpadding="2" cellspacing="0">|</table></td></tr></table>';

		$this->palFieldTemplate='
	<tr>
		<td nowrap="nowrap" bgcolor="#ABBBB4"><font face="verdana" size="1" color="black">###FIELD_PALETTE###</font></td>
	</tr>	';
		$this->palFieldTemplateHeader='
	<tr>
		<td nowrap="nowrap" bgcolor="#F6F2E6"><font face="verdana" size="1" color="black"><b>###FIELD_HEADER###</b></font></td>
	</tr>	';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php']);
}
?>
