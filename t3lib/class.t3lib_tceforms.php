<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  171: class t3lib_TCEforms	
 *  266:     function t3lib_TCEforms()	
 *  303:     function initDefaultBEmode()	
 *
 *              SECTION: Rendering the forms, fields etc
 *  349:     function getSoloField($table,$row,$theFieldToReturn)	
 *  388:     function getMainFields($table,$row,$depth=0)	
 *  515:     function getListedFields($table,$row,$list)	
 *  556:     function getPaletteFields($table,$row,$palette,$header='',$itemList='',$collapsedHeader='')	
 *  632:     function getSingleField($table,$field,$row,$altName='',$palette=0,$extra='',$pal=0)	
 *  760:     function getSingleField_SW($table,$field,$row,&$PA)	
 *
 *              SECTION: Rendering of each TCEform field type
 *  831:     function getSingleField_typeInput($table,$field,$row,&$PA)	
 *  883:     function getSingleField_typeText($table,$field,$row,&$PA)	
 *  952:     function getSingleField_typeCheck($table,$field,$row,&$PA)	
 * 1011:     function getSingleField_typeRadio($table,$field,$row,&$PA)	
 * 1041:     function getSingleField_typeSelect($table,$field,$row,&$PA)	
 * 1199:     function getSingleField_typeGroup($table,$field,$row,&$PA)	
 * 1341:     function getSingleField_typeNone($table,$field,$row,&$PA)	
 * 1395:     function getSingleField_typeFlex($table,$field,$row,&$PA)	
 * 1496:     function getSingleField_typeFlex_langMenu($languages,$elName,$selectedLanguage,$multi=1)	
 * 1515:     function getSingleField_typeFlex_sheetMenu($sArr,$elName,$sheetKey)	
 * 1545:     function getSingleField_typeFlex_draw($dataStruct,$editData,$cmdData,$table,$field,$row,&$PA,$formPrefix='',$level=0,$tRows=array())	
 * 1696:     function getSingleField_typeUnknown($table,$field,$row,&$PA)	
 * 1711:     function getSingleField_typeUser($table,$field,$row,&$PA)	
 *
 *              SECTION: "Configuration" fetching/processing functions
 * 1743:     function getRTypeNum($table,$row)	
 * 1769:     function rearrange($fields)	
 * 1795:     function getExcludeElements($table,$row,$typeNum)	
 * 1843:     function getFieldsToAdd($table,$row,$typeNum)	
 * 1868:     function mergeFieldsWithAddedFields($fields,$fieldsToAdd)	
 * 1900:     function setTSconfig($table,$row,$field='')	
 * 1922:     function getSpecConfForField($table,$row,$field)	
 * 1942:     function getSpecConfFromString($extraString)    
 *
 *              SECTION: Form element helper functions
 * 1974:     function dbFileIcons($fName,$mode,$allowed,$itemArray,$selector='',$params=array(),$onFocus='')	
 * 2083:     function renderWizards($itemKinds,$wizConf,$table,$row,$field,&$PA,$itemName,$specConf,$RTE=0)	
 * 2246:     function getIcon($icon)	
 * 2277:     function wrapOpenPalette($header,$table,$row,$palette,$retFunc=0)	
 * 2301:     function checkBoxParams($itemName,$thisValue,$c,$iCount,$addFunc='')	
 * 2315:     function elName($itemName)	
 * 2326:     function noTitle($str,$wrapParts=array())	
 * 2335:     function blur()	
 * 2348:     function getSingleHiddenField($table,$field,$row)	
 * 2370:     function formWidth($size=48,$textarea=0) 
 * 2392:     function formWidthText($size=48,$wrap='') 
 * 2407:     function formElStyle($type)	
 * 2425:     function insertDefStyle($type)	
 *
 *              SECTION: Item-array manipulation functions (check/select/radio)
 * 2456:     function initItemArray($fieldValue)	
 * 2474:     function addItems($items,$iArray)	
 * 2496:     function procItems($items,$iArray,$config,$table,$row,$field)	
 * 2520:     function addSelectOptionsToItemArray($items,$fieldValue,$TSconfig,$field)	
 * 2598:     function addSelectOptionsToItemArray_makeModuleData($value)	
 * 2620:     function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)	
 *
 *              SECTION: Template functions
 * 2698:     function setFancyDesign()	
 * 2725:     function setNewBEDesign()	
 * 2779:     function intoTemplate($inArr,$altTemplate='')	
 * 2803:     function addUserTemplateMarkers($marker,$table,$field,$row,&$PA)	
 * 2814:     function wrapLabels($str)	
 * 2827:     function wrapTotal($c,$rec,$table)	
 * 2840:     function replaceTableWrap($arr,$rec,$table)	
 * 2862:     function wrapBorder(&$out_array,&$out_pointer)	
 * 2883:     function rplColorScheme($inTemplate)	
 * 2896:     function getDivider()	
 * 2906:     function printPalette($palArr)	
 * 2948:     function helpTextIcon($table,$field,$force=0)	
 * 2964:     function helpText($table,$field)	
 * 2986:     function setColorScheme($scheme)	
 * 3000:     function resetSchemes()	
 * 3011:     function storeSchemes()	
 * 3022:     function restoreSchemes()	
 *
 *              SECTION: JavaScript related functions
 * 3052:     function JStop($formname='forms[0]')	
 * 3091:     function JSbottom($formname='forms[0]')	
 * 3398:     function dbFileCon($formObj='document.forms[0]')	
 * 3506:     function printNeededJSFunctions()	
 * 3533:     function printNeededJSFunctions_top()	
 *
 *              SECTION: Various helper functions
 * 3581:     function getDefaultRecord($table,$pid=0)	
 * 3619:     function getRecordPath($table,$rec)	
 * 3632:     function readPerms()	
 * 3646:     function sL($str)	
 * 3659:     function getLL($str)	
 * 3677:     function isPalettesCollapsed($table,$palette)	
 * 3692:     function isDisplayCondition($displayCond,$row)	
 * 3745:     function getTSCpid($table,$uid,$pid)	
 * 3759:     function doLoadTableDescr($table)	
 * 3771:     function getAvailableLanguages($onlyIsoCoded=1,$setDefault=1)	
 *
 *
 * 3814: class t3lib_TCEforms_FE extends t3lib_TCEforms 
 * 3822:     function wrapLabels($str)	
 * 3832:     function printPalette($palArr)	
 *
 * TOTAL FUNCTIONS: 82
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 













/**
 * 'TCEforms' - Class for creating the backend editing forms.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @coauthor	Rene Fritz <r.fritz@colorcube.de>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_TCEforms	{

		// variables not commented yet.... (do so...)
	var $helpTextFontTag='<font color="#333333">';
	var $RTEpath = 'ext/rte/app/';
	var $tceFormsEditor=1;
	var $palFieldArr=array();
	var $disableWizards=0;
	var $RTEdivStyle='';
	var $isPalettedoc=0;
	var $paletteMargin=1;
	var $defStyle = ''; // 'font-family:Verdana;font-size:10px;';
	var $cachedTSconfig=array();
	var $cachedTSconfig_fieldLevel=array();
	var $transformedRow=array();
	var $extJSCODE='';
	var $RTEwindows=array();
	var $printNeededJS = array();
	var $hiddenFieldAccum=array();
	var $TBE_EDITOR_fieldChanged_func='';
	var $loadMD5_JS=1;
	var $prevBorderStyle='[nothing here...]';	// Something unique...
	var $allowUpload=0; 				// If set direct upload fields will be shown
	var $titleLen=15; 					// $BE_USER->uc['titleLen'] but what is default??


		// EXTERNAL, static
	var $backPath='';					// Set this to the 'backPath' pointing back to the typo3 admin directory from the script where this form is displayed.
	var $doSaveFieldName='';			// Can be set to point to a field name in the form which will be set to '1' when the form is submitted.
	var $palettesCollapsed=0;			// Can be set true/false to whether palettes (secondary options) are in the topframe or in form. True means they are NOT IN-form. So a collapsed palette is one, which is shown in the top frame, not in the page.
	var $disableRTE=0;					// If set, the RTE is disabled.
	var $globalShowHelp=1;				// If false, then all CSH will be disabled, regardless of settings in $this->edit_showFieldHelp
	var $fieldOrder='';					// Overrule the field order set in TCA[types][showitem], eg for tt_content this value, 'bodytext,image', would make first the 'bodytext' field, then the 'image' field (if set for display)... and then the rest in the old order.
	var $doPrintPalette=1;				// If set to false, palettes will NEVER be rendered.

	var $form_rowsToStylewidth = 9.58;	// Form field width compensation: Factor from NN4 form field widths to style-aware browsers (like NN6+ and MSIE, with the $CLIENT[FORMSTYLE] value set)
	var $form_largeComp = 1.33;			// Form field width compensation: Compensation for large documents, doc-tab (editing)
	var $charsPerRow=40;				// The number of chars expected per row when the height of a text area field is automatically calculated based on the number of characters found in the field content.
	var $maxTextareaWidth=48;			// The maximum abstract value for textareas
	var $maxInputWidth=48;				// The maximum abstract value for input fields

	
		// INTERNAL, static
	var $prependFormFieldNames = 'data';		// The string to prepend formfield names with.
	var $prependFormFieldNames_file = 'data_files';		// The string to prepend FILE form field names with.
	var $formName = 'editform';					// The name attribute of the form. 
	var $RTEbgColor= '#F6F2E6';					// The background color passed to the RTE

		

		// INTERNAL, dynamic
	var $perms_clause='';						// Set by readPerms()  (caching)
	var $perms_clause_set=0;					// Set by readPerms()  (caching-flag)
	var $edit_showFieldHelp='';					// Used to indicate the mode of CSH (Context Sensitive Help), whether it should be icons-only ('icon'), full description ('text') or not at all (blank).
	var $docLarge=0;							// If set, the forms will be rendered a little wider, more precisely with a factor of $this->form_largeComp.
	var $clientInfo=array();					// Loaded with info about the browser when class is instantiated.
	var $RTEenabled=0;							// True, if RTE is possible for the current user (based on result from BE_USER->isRTE())
	var $RTEenabled_notReasons='';				// If $this->RTEenabled was false, you can find the reasons listed in this array which is filled with reasons why the RTE could not be loaded)
		
	var $colorScheme;							// Contains current color scheme
	var $defColorScheme;						// Contains the default color scheme
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









	/**
	 * Constructor function, setting internal variables, loading the styles used.
	 * 
	 * @return	void		
	 */
	function t3lib_TCEforms()	{
		global $CLIENT;
		
		$this->clientInfo=t3lib_div::clientInfo();
		$this->RTEenabled = $GLOBALS['BE_USER']->isRTE();
		if (!$this->RTEenabled)	{
			$this->RTEenabled_notReasons=
				(!t3lib_extMgm::isLoaded('rte') ? "- 'rte' extension is not loaded\n":'').
				($CLIENT['BROWSER']!='msie' ? "- Browser is not MSIE\n":'').
				($CLIENT['SYSTEM']!='win' ? "- Client system is not Windows\n":'').
				($CLIENT['VERSION']<5 ? "- Browser version below 5\n":'').
				(!$GLOBALS['BE_USER']->uc['edit_RTE'] ? "- RTE is not enabled for user!\n":'').
				(!$GLOBALS['TYPO3_CONF_VARS']['BE']['RTEenabled'] ? '- RTE is not enabled in $TYPO3_CONF_VARS["BE"]["RTEenabled"]'.chr(10):'');
			$this->commentMessages[]='RTE NOT ENABLED IN SYSTEM due to:'.chr(10).$this->RTEenabled_notReasons;
		}
		
			// Default color scheme
		$this->defColorScheme=array(
			$GLOBALS['SOBE']->doc->bgColor,	// Background for the field AND palette
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor,-20),	// Background for the field header
			t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor,-10),	// Background for the palette field header
			'black',	// Field header font color
			'#666666'	// Palette field header font color
		);

			// Override / Setting defaults from TBE_STYLES array
		$this->resetSchemes();
		
			// Setting the current colorScheme to default.
		$this->defColorScheme=$this->colorScheme;
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
		$this->RTEbgColor = $GLOBALS['SOBE']->doc->bgColor;
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
		global $TCA;

		$this->renderDepth=$depth;

			// Init vars:		
		$out_array=array();
		$out_pointer=0;
		$this->palettesRendered=array();
		$this->palettesRendered[$this->renderDepth][$table]=array();

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
					reset($fields);
					while(list(,$fieldInfo)=each($fields))	{
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
							$this->wrapBorder($out_array,$out_pointer);
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
								
								$out_array[$out_pointer].= $sField;
							} elseif($theField=='--div--')	{
								$out_array[$out_pointer].=$this->getDivider();
							} elseif($theField=='--palette--')	{
								if ($parts[2] && !isset($this->palettesRendered[$this->renderDepth][$table][$parts[2]]))	{
										// render a 'header' if not collapsed
									if ($TCA[$table]['palettes'][$parts[2]]['canNotCollapse'] AND $parts[1]) {
										$out_array[$out_pointer].=$this->getPaletteFields($table,$row,$parts[2],$this->sL($parts[1]));
									} else {
										$out_array[$out_pointer].=$this->getPaletteFields($table,$row,$parts[2],'','',$this->sL($parts[1]));
									}
									$this->palettesRendered[$this->renderDepth][$table][$parts[2]] = 1;
								}
							}
						}
					}
				}
			}
		}
		
			// Wrapping a border around it all:
		$this->wrapBorder($out_array,$out_pointer);

			// Resetting styles:
		$this->resetSchemes();

			// Rendering Main palette, if any
		$mP = $TCA[$table]['ctrl']['mainpalette'];
		if ($mP && !isset($this->palettesRendered[$this->renderDepth][$table][$mP]))	{
			$temp_palettesCollapsed=$this->palettesCollapsed;
			$this->palettesCollapsed=0;
			$out_array[$out_pointer].=$this->getPaletteFields($table,$row,$mP,$this->getLL('l_generalOptions'));
			$this->palettesCollapsed=$temp_palettesCollapsed;
			$this->palettesRendered[$this->renderDepth][$table][$mP] = 1;
		}
		$this->wrapBorder($out_array,$out_pointer);

		if ($this->renderDepth)	{
			$this->renderDepth--;
		}

			// Return the imploded $out_array:
		return implode('',$out_array);
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

		$out='';
		$PA=array();
		$PA['altName']=$altName;
		$PA['palette'] = $palette;
		$PA['extra'] = $extra;
		$PA['pal'] = $pal;
	 
			// Make sure to load full $TCA array for the table:
		t3lib_div::loadTCA($table);

			// Get the TCA configuration for the current field:
		$PA['fieldConf'] = $TCA[$table]['columns'][$field];

			// Now, check if this field is configured and editable (according to excludefields + other configuration)
		if (	is_array($PA['fieldConf']) &&
				(!$PA['fieldConf']['exclude'] || $BE_USER->check('non_exclude_fields',$table.':'.$field)) && 
				$PA['fieldConf']['config']['type']!='passthrough' &&
				($this->RTEenabled || !$PA['fieldConf']['config']['showIfRTE']) && 
				(!$PA['fieldConf']['displayCond'] || $this->isDisplayCondition($PA['fieldConf']['displayCond'],$row))
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
						(($TCA[$table]['ctrl']['type'] && !strcmp($field,$TCA[$table]['ctrl']['type'])) ||
						($TCA[$table]['ctrl']['requestUpdate'] && t3lib_div::inList($TCA[$table]['ctrl']['requestUpdate'],$field)))
						&& !$BE_USER->uc['noOnChangeAlertInTypeFields'])	{
					$alertMsgOnChange='if (confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){TBE_EDITOR_submitForm()};';
				} else {$alertMsgOnChange='';}
				
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

						// If the record has been saved and the "linkTitleToSelf" is set, we make the field name into a link, which will load ONLY this field in alt_doc.php
					$PA['label'] = t3lib_div::deHSCentities(htmlspecialchars($PA['label']));
					if (t3lib_div::testInt($row['uid']) && $PA['fieldTSConfig']['linkTitleToSelf'])	{
						$lTTS_url = $this->backPath.'alt_doc.php?edit['.$table.']['.$row['uid'].']=edit&columnsOnly='.$field.
									($PA['fieldTSConfig']['linkTitleToSelf.']['returnUrl']?'&returnUrl='.rawurlencode(t3lib_div::linkThisScript()):'');
						$PA['label'] = '<a href="'.htmlspecialchars($lTTS_url).'">'.$PA['label'].'</a>';
					}

						// Create output value:
					if ($PA['fieldConf']['config']['type']=='user' && $PA['fieldConf']['config']['noTableWrapping'])	{
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
		switch($PA['fieldConf']['config']['type'])	{
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
		$specConf = $this->getSpecConfFromString($PA['extra']);
		$size = t3lib_div::intInRange($config['size']?$config['size']:30,5,$this->maxInputWidth);
		$evalList = t3lib_div::trimExplode(',',$config['eval'],1);

		if (in_array('required',$evalList))	{
			$this->requiredFields[$table.'_'.$row['uid'].'_'.$field]=$PA['itemFormElName'];
		}

		$paramsList = "'".$PA['itemFormElName']."','".implode(',',$evalList)."','".trim($config['is_in'])."',".(isset($config['checkbox'])?1:0).",'".$config['checkbox']."'";
		if (isset($config['checkbox']))	{
				// Setting default "click-checkbox" values for eval types "date" and "datetime":
			$nextMidNight = mktime(0,0,0)+1*3600*24;
			$checkSetValue = in_array('date',$evalList) ? $nextMidNight : '';
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
			
			// Creating an alternative item without the JavaScript handlers.
		$altItem='<input type="hidden" name="'.$PA['itemFormElName'].'_hr" value="" />';
		$altItem.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
			
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
		$config = $PA['fieldConf']['config'];
		$cols = t3lib_div::intInRange($config['cols']?$config['cols']:30,5,$this->maxTextareaWidth);
		$origRows = $rows = t3lib_div::intInRange($config['rows']?$config['rows']:5,1,20);
		if (strlen($PA['itemFormElValue'])>$this->charsPerRow*2)	{
			$cols = $this->maxTextareaWidth;
			$rows = t3lib_div::intInRange(round(strlen($PA['itemFormElValue'])/$this->charsPerRow),count(explode(chr(10),$PA['itemFormElValue'])),20);
			if ($rows<$origRows)	$rows=$origRows;
		}
		$RTEwasLoaded=0;
		$RTEwouldHaveBeenLoaded=0;

		$specConf = $this->getSpecConfFromString($PA['extra']);
		if ($this->RTEenabled) {	// If RTE is generally enabled (TYPO3_CONF_VARS and user settings)
			$RTEWidth = 460+($this->docLarge ? 150 : 0);
			$p=t3lib_BEfunc::getSpecConfParametersFromArray($specConf['rte_transform']['parameters']);
			if (isset($specConf['richtext']) && (!$p['flag'] || !$row[$p['flag']]))	{	// If the field is configured for RTE and if any flag-field is not set to disable it.

				list($tscPID,$thePidValue)=$this->getTSCpid($table,$row['uid'],$row['pid']);

				if ($thePidValue>=0)	{	// If the pid-value is not negative (that is, a pid could NOT be fetched)
					$RTEsetup = $GLOBALS['BE_USER']->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($tscPID));
					$RTEtypeVal = t3lib_BEfunc::getTCAtypeValue($table,$row);
					$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$table,$field,$RTEtypeVal);
					if (!$thisConfig['disabled'])	{
						if (!$this->disableRTE)	{
							$RTEdivStyle = $this->RTEdivStyle ? $this->RTEdivStyle : 'position:relative; left:0px; top:0px; height:380px; width:'.$RTEWidth.'px;border:solid 0px;';
							$rteURL = $this->backPath.$this->RTEpath.'rte.php?elementId='.$PA['itemFormElName'].'&pid='.$row['pid'].'&typeVal='.rawurlencode($RTEtypeVal).'&bgColor='.rawurlencode($this->colorScheme[0]).'&sC='.rawurlencode($PA['extra']).($this->tceFormsEditor?'&TCEformsEdit=1':'').'&formName='.rawurlencode($this->formName);
							$item.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
							$item.='<div id="cdiv'.count($this->RTEwindows).'" style="'.htmlspecialchars($RTEdivStyle).'">';
							$item.='<iframe src="'.htmlspecialchars($rteURL).'" id="'.$PA['itemFormElName'].'_RTE" style="visibility: visible; position: absolute; left: 0px; top: 0px; height:100%; width:100%;"></iframe>';
							$item.='</div>';

							$altItem='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
							$item=$this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,1);
							$this->RTEwindows[]=$PA['itemFormElName'];
							$RTEwasLoaded=1;
						} else {
							$RTEwouldHaveBeenLoaded=1;
							$this->commentMessages[]=$PA['itemFormElName'].': RTE is disabled by the on-page RTE-flag (probably you can enable it by the check-box in the bottom of this page!)';
						}
					} else $this->commentMessages[]=$PA['itemFormElName'].': RTE is disabled by the Page TSconfig, "RTE"-key (eg. by RTE.default.disabled=0 or such)';
				} else $this->commentMessages[]=$PA['itemFormElName'].': PID value could NOT be fetched. Rare error, normally with new records.';
			} else {
				if (!isset($specConf['richtext']))	$this->commentMessages[]=$PA['itemFormElName'].': RTE was not configured for this field in TCA-types';
				if (!(!$p['flag'] || !$row[$p['flag']]))	 $this->commentMessages[]=$PA['itemFormElName'].': Field-flag ('.$PA['flag'].') has been set to disable RTE!';
			}
		}
		if (!$RTEwasLoaded) {	// Display ordinary field if RTE was not loaded.
			if (strstr($PA['extra'],'nowrap'))	$wrap='off'; else $wrap=($config['wrap']?$config['wrap']:'virtual');
			$iOnChange = implode('',$PA['fieldChangeFunc']);
			$item.='<textarea name="'.$PA['itemFormElName'].'"'.$this->formWidthText($cols,$wrap).' rows="'.$rows.'" wrap="'.$wrap.'" onchange="'.htmlspecialchars($iOnChange).'"'.$PA['onFocus'].'>'.t3lib_div::formatForTextarea($PA['itemFormElValue']).'</textarea>';
			$altItem='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
			$item=$this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf,$RTEwouldHaveBeenLoaded);
		}	

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

			// Traversing the array of items:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

		if (!count($selItems))	{
			$selItems[]=array('','');
		}
		$thisValue = intval($PA['itemFormElValue']);

		$cols = intval($config['cols']);
		if ($cols > 1)	{
			$item.= '<table border="0" cellspacing="0" cellpadding="0">';
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				if(!($c%$cols))	{$item.='<tr>';}
				$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$item.= '<td nowrap="nowrap">'.
						'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.' />'.
						$this->wrapLabels(htmlspecialchars($p[0]).'&nbsp;').
						'</td>';
				if(($c%$cols)+1==$cols)	{$item.='</tr>';}
			}
			if ($c%$cols)	{
				$rest=$cols-($c%$cols);
				for ($c=0;$c<$rest;$c++) {
					$item.= '<td></td>';
				}
				if ($c>0)	{$item.= '</tr>';}
			}
			$item.= '</table>';
		} else {
			for ($c=0;$c<count($selItems);$c++) {
				$p = $selItems[$c];
				$cBP = $this->checkBoxParams($PA['itemFormElName'],$thisValue,$c,count($selItems),implode('',$PA['fieldChangeFunc']));
				$cBName = $PA['itemFormElName'].'_'.$c;
				$item.= ($c>0?'<br />':'').
						'<input type="checkbox"'.$this->insertDefStyle('check').' value="1" name="'.$cBName.'"'.$cBP.$PA['onFocus'].' />'.
						htmlspecialchars($p[0]);
			}
		}
		$item.='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($thisValue).'" />';	

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

			// Get items for the array:
		$selItems = $this->initItemArray($PA['fieldConf']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			// Traverse the items, making the form elements:
		for ($c=0;$c<count($selItems);$c++) {
			$p = $selItems[$c];
			$rOnClick = implode('',$PA['fieldChangeFunc']);
			$rChecked = (!strcmp($p[1],$PA['itemFormElValue'])?' checked="checked"':'');
			$item.= '<input type="radio"'.$this->insertDefStyle('radio').' name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($p[1]).'" onclick="'.htmlspecialchars($rOnClick).'"'.$rChecked.$PA['onFocus'].' />'.
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

			// Field configuration from TCA:
		$config = $PA['fieldConf']['config'];

			// Getting the selector box items from the system
		$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($PA['fieldConf']),$PA['fieldConf'],$this->setTSconfig($table,$row),$field);
		$selItems = $this->addItems($selItems,$PA['fieldTSConfig']['addItems.']);
		if ($config['itemsProcFunc']) $selItems = $this->procItems($selItems,$PA['fieldTSConfig']['itemsProcFunc.'],$config,$table,$row,$field);

			// Possibly remove some items:		
		$removeItems=t3lib_div::trimExplode(',',$PA['fieldTSConfig']['removeItems'],1);
		foreach($selItems as $tk => $p)	{
			if (in_array($p[1],$removeItems))	{
				unset($selItems[$tk]);
			} else if (isset($PA['fieldTSConfig']['altLabels.'][$p[1]])) {
				$selItems[$tk][0]=$this->sL($PA['fieldTSConfig']['altLabels.'][$p[1]]);
			}
		}

			// Creating the label for the "No Matching Value" entry.
		$nMV_label = isset($PA['fieldTSConfig']['noMatchingValue_label']) ? $this->sL($PA['fieldTSConfig']['noMatchingValue_label']) : '[ '.sprintf($this->getLL('l_noMatchingValue'),$PA['itemFormElValue']).' ]';
		
			// Prepare some values:
		$maxitems = intval($config['maxitems']);
		$minitems = intval($config['minitems']);
		$size = intval($config['size']);
		
			// If a SINGLE selector box...
		if ($maxitems<=1)	{
			$c=0;
			$sI=0;
			$noMatchingValue=1;
			$opt=array();
			$selicons=array();
			$onlySelectedIconShown=0;
			
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
					$sI=$c;
					$noMatchingValue=0;
				}
				$opt[]= '<option value="'.htmlspecialchars($p[1]).'"'.$sM.'>'.t3lib_div::deHSCentities(htmlspecialchars($p[0])).'</option>';
					// If there is an icon for the selector box...:
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
			if ($PA['itemFormElValue'] && $noMatchingValue && !$PA['fieldTSConfig']['disableNoMatchingValueElement'] && !$config['disableNoMatchingValueElement'])	{
				$opt[]= '<option value="'.htmlspecialchars($PA['itemFormElValue']).'" selected="selected">'.htmlspecialchars($nMV_label).'</option>';
			}
			$sOnChange = 'if (this.options[this.selectedIndex].value==\'--div--\') {this.selectedIndex='.$sI.';} '.implode('',$PA['fieldChangeFunc']);
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_selIconVal" value="'.htmlspecialchars($sI).'" />';	// MUST be inserted before the selector - else is the value of the hiddenfield here mysteriously submitted...
			$item.= '<select name="'.$PA['itemFormElName'].'"'.$this->insertDefStyle('select').($size?' size="'.$size.'"':'').' onchange="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].'>';
			$item.= implode('',$opt);
			$item.= '</select>';

			if (count($selicons))	{
				$item.='<table border="0" cellpadding="0" cellspacing="0">';
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
		} else {
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'" />';

				// Set max and min items:
			$maxitems = t3lib_div::intInRange($config['maxitems'],0);
			if (!$maxitems)	$maxitems=100000;
			$minitems = t3lib_div::intInRange($config['minitems'],0);
			
				// Register the required number of elements:
			$this->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);

			$sOnChange = 'setFormValueFromBrowseWin(\''.$PA['itemFormElName'].'\',this.options[this.selectedIndex].value,this.options[this.selectedIndex].text); '.implode('',$PA['fieldChangeFunc']);

				// Put together the select form with selected elements:
			$thumbnails='<select style="width:200 px;" name="'.$PA['itemFormElName'].'_sel"'.$this->insertDefStyle('select').($size?' size="'.$size.'"':'').' onchange="'.htmlspecialchars($sOnChange).'"'.$PA['onFocus'].'>';
			foreach($selItems as $p)	{
				$thumbnails.= '<option value="'.htmlspecialchars($p[1]).'">'.htmlspecialchars($p[0]).'</option>';
			}
			$thumbnails.= '</select>';

				// Perform modification of the selected items array:
			$itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
			foreach($itemArray as $tk => $tv) {
				$tvP=explode('|',$tv,2);
				if (in_array($tvP[0],$removeItems) && !$PA['fieldTSConfig']['disableNoMatchingValueElement'])	{
					$tvP[1]=rawurlencode($nMV_label);
				} elseif (isset($PA['fieldTSConfig']['altLabels.'][$tvP[0]])) {
					$tvP[1]=rawurlencode($this->sL($PA['fieldTSConfig']['altLabels.'][$tvP[0]]));
				}
				$itemArray[$tk]=implode('|',$tvP);
			}
			
			$params=array(
				'size' => $size,
				'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
				'dontShowMoveIcons' => ($maxitems<=1),
				'info' => '',
				'headers' => array(
					'selector' => $this->getLL('l_selected').':<br />',
					'items' => $this->getLL('l_items').':<br />'
				),
				'noBrowser' => 1,
				'thumbnails' => $thumbnails
			);
			$item.= $this->dbFileIcons($PA['itemFormElName'],'','',$itemArray,'',$params,$PA['onFocus']);
		}

			// Wizards:
		$altItem='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
		$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);	

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

		$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'_mul" value="'.($config['multiple']?1:0).'" />';
		$this->requiredElements[$PA['itemFormElName']] = array($minitems,$maxitems,'imgName'=>$table.'_'.$row['uid'].'_'.$field);
		$info='';
		
			// If the element is of the internal type "file":
		if ($config['internal_type']=='file')	{

				// Creating string showing allowed types:
			$tempFT = t3lib_div::trimExplode(',',$allowed,1);
			reset($tempFT);
			if (!count($tempFT))	{$info.='*';}
			while(list(,$ext)=each($tempFT))	{
				if ($ext)	{
					$info.=strtoupper($ext).' ';
				}
			}
				// Creating string, showing disallowed types:
			$tempFT_dis = t3lib_div::trimExplode(',',$disallowed,1);
			reset($tempFT_dis);
			if (count($tempFT_dis))	{$info.='<br />';}
			while(list(,$ext)=each($tempFT_dis))	{
				if ($ext)	{
					$info.='-'.strtoupper($ext).' ';
				}
			}
				
				// Making the array of file items:
			$itemArray=t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);

				// Showing thumbnails:
			$thumbsnail='';
			if ($show_thumbs)	{
				reset($itemArray);
				$imgs=array();
				while(list(,$imgRead)=each($itemArray))	{
					$imgP = explode('|',$imgRead);

					$rowCopy=array();
					$rowCopy[$field] = $imgP[0];
					$imgs[]= '<span class="nobr">'.t3lib_BEfunc::thumbCode($rowCopy,$table,$field,$this->backPath,'thumbs.php',$config['uploadfolder'],0,' align="middle"').$imgP[0].'</span>';
				}
				$thumbsnail = implode('<br />',$imgs);
			}

				// Creating the element:
			$params=array(
				'size' => $size,
				'dontShowMoveIcons' => ($maxitems<=1),
				'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
				'info' => $info,
				'thumbnails' => $thumbsnail
			);
			$item.= $this->dbFileIcons($PA['itemFormElName'],'file',implode(',',$tempFT),$itemArray,'',$params,$PA['onFocus']);

				// Adding the upload field:
			if ($this->edit_docModuleUpload)	$item.='<input type="file" name="'.$PA['itemFormElName_file'].'"'.$this->formWidth().' size="60" />';
		}
		
			// If the element is of the internal type "db":
		if ($config['internal_type']=='db')	{

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
			$itemArray=array();
			$imgs=array();

				// Thumbnails:			
			$temp_itemArray = t3lib_div::trimExplode(',',$PA['itemFormElValue'],1);
			foreach($temp_itemArray as $dbRead)	{
				$recordParts = explode('|',$dbRead);
				list($this_table,$this_uid) = t3lib_BEfunc::splitTable_Uid($recordParts[0]);
				$itemArray[] = array('table'=>$this_table, 'id'=>$this_uid);
				if ($show_thumbs)	{
					$rr = t3lib_BEfunc::getRecord($this_table,$this_uid);
					$imgs[]='<span class="nobr">'.
							t3lib_iconWorks::getIconImage($this_table,$rr,$this->backPath,'align="top" title="'.htmlspecialchars(t3lib_BEfunc::getRecordPath($rr['pid'],$perms_clause,15)).'"').
							'&nbsp;'.
							$this->noTitle($rr[$GLOBALS['TCA'][$this_table]['ctrl']['label']],array('<em>','</em>')).
							'</span>';
				}
			}
			$thumbsnail='';
			if ($show_thumbs)	{
				$thumbsnail = implode('<br />',$imgs);
			}

				// Creating the element:
			$params=array(
				'size' => $size,
				'dontShowMoveIcons' => ($maxitems<=1),
				'autoSizeMax' => t3lib_div::intInRange($config['autoSizeMax'],0),
				'info' => $info,
				'thumbnails' => $thumbsnail
			);
			$item.= $this->dbFileIcons($PA['itemFormElName'],'db',implode(',',$tempFT),$itemArray,'',$params,$PA['onFocus']);
		}

			// Wizards:
		$altItem='<input type="hidden" name="'.$PA['itemFormElName'].'" value="'.htmlspecialchars($PA['itemFormElValue']).'" />';
		$item = $this->renderWizards(array($item,$altItem),$config['wizards'],$table,$row,$field,$PA,$PA['itemFormElName'],$specConf);	

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
		
		$divStyle = 'border:solid 1px '.t3lib_div::modifyHTMLColorAll($this->colorScheme[0],-30).';'.$this->defStyle.$this->formElStyle('text').' background-color: '.$this->colorScheme[0].'; overflow:auto;padding-left:1px;color:#555;';
		if ($config['rows']>1) {
			if(!$config['pass_content']) {
				$itemValue=nl2br(htmlspecialchars($itemValue));
			}
				// like textarea
			$cols = t3lib_div::intInRange($config['cols']?$config['cols']:30,5,$this->maxTextareaWidth);
			if (!$config['fixedRows']) {
				$origRows = $rows = t3lib_div::intInRange($config['rows']?$config['rows']:5,1,20);
				if (strlen($itemValue)>$this->charsPerRow*2)	{
					$cols = $this->maxTextareaWidth;
					$rows = t3lib_div::intInRange(round(strlen($itemValue)/$this->charsPerRow),count(explode(chr(10),$itemValue)),20);
					if ($rows<$origRows)	$rows=$origRows;
				}
			} else {
				$rows = intval($config['rows']);
			}

			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);
				// hardcoded: 12 is the height of the font
			$height=$rows*12;
				// is colorScheme[0] the right value?
			$item='<div style="'.htmlspecialchars($divStyle.'height:'.$height.'px;width:'.$width.'px;').'">'.$itemValue.'</div>';
		} else {
			if(!$config['pass_content']) {
				$itemValue=htmlspecialchars($itemValue);
			}

			// how to handle cropping for too long lines?
			#$item=htmlspecialchars($itemValue);
			$cols = $config['cols']?$config['cols']:($config['size']?$config['size']:$this->maxInputWidth);
			if ($this->docLarge)	$cols = round($cols*$this->form_largeComp);
			$width = ceil($cols*$this->form_rowsToStylewidth);
			$item='<div style="'.htmlspecialchars($divStyle.'width:'.$width.'px;').'"><span class="nobr">'.(strcmp($itemValue,'')?$itemValue:'&nbsp;').'</span></div>';
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
			$item.= '<input type="hidden" name="'.$PA['itemFormElName'].'[meta][currentSheetId]" value="'.$sheet.'">';

				// Create sheet menu:
			if (is_array($dataStructArray['sheets']))	{
				$item.=$this->getSingleField_typeFlex_sheetMenu($dataStructArray['sheets'], $PA['itemFormElName'].'[meta][currentSheetId]', $sheet).'<br />';
			}
#debug($editData);
		
				// Create language menu:
			$langChildren = $dataStructArray['meta']['langChildren'] ? 1 : 0;
			$langDisabled = $dataStructArray['meta']['langDisable'] ? 1 : 0;

			$languages = $this->getAvailableLanguages();
			
			if (!is_array($editData['meta']['currentLangId']) || !count($editData['meta']['currentLangId']))	{
				$editData['meta']['currentLangId']=array('DEF');
			}
			$editData['meta']['currentLangId'] = array_unique($editData['meta']['currentLangId']);
			
			if (!$langDisabled && count($languages) > 1)	{
				$item.=$this->getSingleField_typeFlex_langMenu($languages, $PA['itemFormElName'].'[meta][currentLangId]', $editData['meta']['currentLangId']).'<br />';
			}
			
			if ($langChildren || $langDisabled)	{
				$rotateLang = array('DEF');
			} else {
				$rotateLang = $editData['meta']['currentLangId'];
			}

			foreach($rotateLang as $lKey)	{
				if (!$langChildren && !$langDisabled)	{
					$item.= '<b>'.$lKey.':</b>';
				}
	#			foreach($dataStructArray['sheets'] as $sheet => $_blabla)	{
					list ($dataStruct, $sheet) = t3lib_div::resolveSheetDefInDS($dataStructArray,$sheet);
		#debug(array($dataStruct, $sheet));
					
						// Render sheet:
					if (is_array($dataStruct['ROOT']) && is_array($dataStruct['ROOT']['el']))		{
						$cmdData = t3lib_div::GPvar('flexFormsCmdData',1);
						$lang = 'l'.$lKey;	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						$PA['_valLang'] = $langChildren && !$langDisabled ? $editData['meta']['currentLangId'] : 'DEF';	// Default language, other options are "lUK" or whatever country code (independant of system!!!)
						
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
						$item.= '<table border="1" cellpadding="2" cellspacing="0">'.implode('',$tRows).'</table>';
						
			#			$item = '<div style=" position:absolute;">'.$item.'</div>';
						//visibility:hidden;
					} else $item.='Data Structure ERROR: No ROOT element found for sheet "'.$sheet.'".';
	#			}
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
	
		$tCells=array();
		$pct = round(100/count($sArr));
		foreach($sArr as $sKey => $sheetCfg)	{
			$onClick='if (confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_onChangeAlert')).') && TBE_EDITOR_checkSubmit(-1)){'.$this->elName($elName).".value='".$sKey."'; TBE_EDITOR_submitForm()};";

			$tCells[]='<td width="'.$pct.'%" style="'.($sKey==$sheetKey ? 'background-color: #9999cc; font-weight: bold;' : 'background-color: #aaaaaa;').' cursor: hand;" onclick="'.htmlspecialchars($onClick).'" align="center">'.
					($sheetCfg['ROOT']['TCEforms']['sheetTitle'] ? $this->sL($sheetCfg['ROOT']['TCEforms']['sheetTitle']) : $sKey).
					'</td>';
		}
		
		return '<table border="0" cellpadding="0" cellspacing="2" style="padding: 1px 15px 0px 15px; border: 1px solid black;"><tr>'.implode('',$tCells).'</tr></table>';
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
					$bgColor = $this->doc->bgColor4;
						
						// Icon:
					$rowCells['title'] = '<img src="clear.gif" width="'.($level*16).'" height="1" alt="" /><strong>'.htmlspecialchars(t3lib_div::fixed_lgd($this->sL($value['tx_templavoila']['title']),30)).'</strong>';;

					$rowCells['formEl']='';
					if ($value['type']=='array')	{
						if ($value['section'])	{
							if (is_array($value['el']))	{
								$opt=array();
								$opt[]='<option value=""></option>';
								foreach($value['el'] as $kk => $vv)	{
									$opt[]='<option value="'.$kk.'">'.htmlspecialchars('NEW "'.$value['el'][$kk]['tx_templavoila']['title'].'"').'</option>';
								}
								$rowCells['formEl']='<select name="flexFormsCmdData'.$formPrefix.'['.$key.'][value]">'.implode('',$opt).'</select>';
							}

								// Put row together
							$tRows[]='<tr bgcolor="'.$bgColor.'">
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
								// Put row together
							$tRows[]='<tr bgcolor="'.$bgColor.'">
								<td nowrap="nowrap" valign="top">'.
								'<input name="_DELETE_FLEX_FORM'.$PA['itemFormElName'].$formPrefix.'" type="checkbox" value="1" /><img src="'.$this->backPath.'gfx/garbage.gif" border="0" alt="" />'.
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

							$fakePA=array();
							$fakePA['fieldConf']=array(
								'label' => $this->sL($value['TCEforms']['label']), 
								'config' => $value['TCEforms']['config']
							);
							$fakePA['fieldChangeFunc']=$PA['fieldChangeFunc'];
							$fakePA['onFocus']=$PA['onFocus'];
							$fakePA['label']==$PA['label'];
							
							$fakePA['itemFormElName']=$PA['itemFormElName'].$formPrefix.'['.$key.']['.$vDEFkey.']';
							$fakePA['itemFormElName_file']=$PA['itemFormElName_file'].$formPrefix.'['.$key.']['.$vDEFkey.']';
							$fakePA['itemFormElValue']=$editData[$key][$vDEFkey];
	
							$rowCells['formEl']= $this->getSingleField_SW($table,$field,$row,$fakePA);
							$rowCells['title']= htmlspecialchars($fakePA['fieldConf']['label']);
							
								// Put row together
							$tRows[]='<tr bgcolor="'.$bgColor.'">
								<td nowrap="nowrap" valign="top">'.$rowCells['title'].($vDEFkey=='vDEF' ? '' : ' ('.$vDEFkey.')').'</td>
								<td>'.$rowCells['formEl'].'</td>
							</tr>';
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
		$item='Unknown type: '.$PA['fieldConf']['config']['type'].'<br />';

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
	 * @param	string		Optionally you can specify the field name as well. If that case the TSconfig for the field is returned.
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
		$types_fieldConfig=t3lib_BEfunc::getTCAtypes($table,$row);
		
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
	 * @return	array		An array with the special options in.
	 * @see getSpecConfForField(), t3lib_BEfunc::getSpecConfParts()
	 */
	function getSpecConfFromString($extraString)    {
		return t3lib_BEfunc::getSpecConfParts($extraString);
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
						$pRec = t3lib_BEfunc::getRecord($pp['table'],$pp['id']);
						if (is_array($pRec))	{
							$pTitle = t3lib_div::fixed_lgd($this->noTitle($pRec[$GLOBALS['TCA'][$pp['table']]['ctrl']['label']]),$this->titleLen);
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
						$pParts = explode('|',$pp);
						$uidList[]=$pUid=$pParts[0];
						$pTitle = $pParts[1];
						$opt[]='<option value="'.htmlspecialchars(rawurldecode($pUid)).'">'.htmlspecialchars(rawurldecode($pTitle)).'</option>';
					}
				break;
			}
		}
			
			// Create selector box of the options
		if (!$selector)	{
			$sSize = $params['autoSizeMax'] ? t3lib_div::intInRange($itemArrayC+1,t3lib_div::intInRange($params['size'],1),$params['autoSizeMax']) : $params['size'];
			$selector = '<select size="'.$sSize.'"'.$this->insertDefStyle('group').' multiple="multiple" name="'.$fName.'_list" style="width:200px;"'.$onFocus.'>'.implode('',$opt).'</select>';
		}
		
			
		$icons=array();
		if (!$params['noBrowser'])	{
			$aOnClick='setFormValueOpenBrowser(\''.$mode.'\',\''.($fName.'|||'.$allowed.'|').'\'); return false;';
			$icons[]='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/insert3.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_browse_'.($mode=='file'?'file':'db'))).' />'.
					'</a>';
		}
		if (!$params['dontShowMoveIcons'])	{
			$icons[]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Up\'); return false;">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_totop.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_move_to_top')).' />'.
					'</a>';
		}
		$icons[]='<a href="#" onclick="setFormValueManipulate(\''.$fName.'\',\'Remove\'); return false;">'.
				'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/group_clear.gif','width="14" height="14"').' border="0" '.t3lib_BEfunc::titleAltAttrib($this->getLL('l_remove_selected')).' />'.
				'</a>';
		$str='<table border="0" cellpadding="0" cellspacing="0" width="1">
			'.($params['headers']?'
				<tr>
					<td>'.$this->wrapLabels($params['headers']['selector']).'</td>
					<td></td>
					<td></td>
					<td>'.$this->wrapLabels($params['headers']['items']).'</td>
				</tr>':'').
			'
			<tr>
				<td valign="top">'.
					$selector.'<br />'.
					$this->wrapLabels($params['info']).
				'</td>
				<td valign="top">'.
					implode('<br />',$icons).'</td>
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
		$outArr=array();
		$fName='['.$table.']['.$row['uid'].']['.$field.']';
		$md5ID = t3lib_div::shortmd5($itemName);

			// traverse wizards:
		if (is_array($wizConf) && !$this->disableWizards)	{
			reset($wizConf);
			while(list($wid,$wConf)=each($wizConf))	{
				if (substr($wid,0,1)!='_' 
						&& (!$wConf['enableByTypeConfig'] || @in_array($wid,$specConf['wizards']['parameters']))
						&& ($RTE || !$wConf['RTEonly'])
					)	{
				
						// Title / icon:
					$iTitle = htmlspecialchars($this->sL($wConf['title']));
					if ($wConf['icon'])	{
						$iDat = $this->getIcon($wConf['icon']);		// THIS is very ODD!!! Check it....
						$icon = '<img src="'.$iDat[0].'" '.$iDat[1][3].' border="0"'.t3lib_BEfunc::titleAltAttrib($iTitle).' />';
					} else $icon=$iTitle;
					
					$colorBoxLinks=array();
					switch((string)$wConf['type'])	{
						case 'userFunc':
						case 'script':
						case 'popup':
						case 'colorbox':
							if (!$wConf['notNewRecords'] || t3lib_div::testInt($row['uid']))	{
								$params = array();
								$params['params'] = $wConf['params'];
								$params['table'] = $table;
								$params['uid'] = $row['uid'];
								$params['pid'] = $row['pid'];
								$params['field'] = $field;
								$params['md5ID'] = $md5ID;
								$params['returnUrl'] = t3lib_div::linkThisScript();
								$url = $this->backPath.$wConf['script'].(strstr($wConf['script'],'?') ? '' : '?');


								if ((string)$wConf['type']=='colorbox' && !$wConf['script'])	{
									break;
								}
								if ((string)$wConf['type']=='script')	{
									$aUrl = $url.t3lib_div::implodeArrayForUrl('',array('P'=>$params));
									$outArr[]='<a href="'.htmlspecialchars($aUrl).'" onclick="'.$this->blur().'return !TBE_EDITOR_isFormChanged();">'.
												$icon.
												'</a>';
								}

								$params['formName']=$this->formName;
								$params['itemName']=$itemName;
								$params['fieldChangeFunc']=$fieldChangeFunc;
								if ((string)$wConf['type']=='popup' || (string)$wConf['type']=='colorbox')	{
										// Current form value is passed as P[currentValue]!
									$addJS = $wConf['popup_onlyOpenIfSelected']?'if (!TBE_EDITOR_curSelected(\''.$itemName.'_list\')){alert('.$GLOBALS['LANG']->JScharCode($this->getLL('m_noSelItemForEdit')).'); return false;}':'';
									$curSelectedValues='+\'&P[currentSelectedValues]=\'+TBE_EDITOR_curSelected(\''.$itemName.'_list\')';
									$aOnClick=	$this->blur().
												$addJS.
												'vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl('',array('P'=>$params)).'\'+\'&P[currentValue]=\'+TBE_EDITOR_rawurlencode('.$this->elName($itemName).'.value,200)'.$curSelectedValues.',\'popUp'.$md5ID.'\',\''.$wConf['JSopenParams'].'\');'.
												'vHWin.focus();return false;';
									$colorBoxLinks=Array('<a href="#" onclick="'.htmlspecialchars($aOnClick).'">','</a>');
									if ((string)$wConf['type']=='popup')	{
										$outArr[] = $colorBoxLinks[0].$icon.$colorBoxLinks[1];
									}
								} elseif ((string)$wConf['type']=='userFunc')	{
									$params['item']=&$item;
									$params['icon']=$icon;
									$params['iTitle']=$iTitle;
									$params['wConf']=$wConf;
									$params['row']=$row;
									$outArr[]=t3lib_div::callUserFunction($wConf['userFunc'],$params,$this);
								}
							}
						break;
						case 'select':
							$fieldValue=array('config'=>$wConf);
							$TSconfig = $this->setTSconfig($table,$row);
							$TSconfig[$field] = $TSconfig[$field]['wizards.'][$wid.'.'];
							$selItems = $this->addSelectOptionsToItemArray($this->initItemArray($fieldValue),$fieldValue,$TSconfig,$field);
							
							reset($selItems);
							$opt=array();
							$opt[]='<option>'.$iTitle.'</option>';
							while(list(,$p)=each($selItems))	{
								$opt[]='<option value="'.htmlspecialchars($p[1]).'">'.htmlspecialchars($p[0]).'</option>';
							}
							if ($wConf['mode']=='append')	{
								$assignValue = $this->elName($itemName).'.value=\'\'+this.options[this.selectedIndex].value+'.$this->elName($itemName).'.value';
							} elseif ($wConf['mode']=='prepend')	{
								$assignValue = $this->elName($itemName).'.value+=\'\'+this.options[this.selectedIndex].value';
							} else {
								$assignValue = $this->elName($itemName).'.value=this.options[this.selectedIndex].value';
							}
							$sOnChange = $assignValue.';this.selectedIndex=0;'.implode('',$fieldChangeFunc);
							$outArr[]='<select name="_WIZARD'.$fName.'" onchange="'.htmlspecialchars($sOnChange).'">'.implode('',$opt).'</select>';
						break;
					}

						// Color wizard:
					if ((string)$wConf['type']=='colorbox')	{
						$dim = t3lib_div::intExplode('x',$wConf['dim']);
						$dX=t3lib_div::intInRange($dim[0],1,200,20);
						$dY=t3lib_div::intInRange($dim[1],1,200,20);
						$color = $row[$field] ? ' bgcolor="'.htmlspecialchars($row[$field]).'"' : '';
						$outArr[] = '<table border="0" cellpadding="0" cellspacing="0" id="'.$md5ID.'"'.$color.' style="'.htmlspecialchars($wConf['tableStyle']).'">
									<tr>
										<td>'.
											$colorBoxLinks[0].
											'<img src="clear.gif" width="'.$dX.'" height="'.$dY.'"'.t3lib_BEfunc::titleAltAttrib(trim($iTitle.' '.$row[$field])).' border="0" />'.
											$colorBoxLinks[0].
											'</td>
									</tr>
								</table>';
					}
				}
			}
			
				// For each rendered wizard, put them together around the item.
			if (count($outArr))	{
				if ($wizConf['_HIDDENFIELD'])	$item = $itemKinds[1];
			
				$outStr='';
				$vAlign = $wizConf['_VALIGN'] ? ' valign="'.$wizConf['_VALIGN'].'"' : '';
				if (count($outArr)>1 || $wizConf['_PADDING'])	{
					$dist=intval($wizConf['_DISTANCE']);
					if ($wizConf['_VERTICAL'])	{
						$dist=$dist?'<tr><td><img src="clear.gif" width="1" height="'.$dist.'" alt="" /></td></tr>':'';
						$outStr='<tr><td>'.implode('</td></tr>'.$dist.'<tr><td>',$outArr).'</td></tr>';
					} else {
						$dist=$dist?'<td><img src="clear.gif" height="1" width="'.$dist.'" alt="" /></td>':'';
						$outStr='<tr><td'.$vAlign.'>'.implode('</td>'.$dist.'<td'.$vAlign.'>',$outArr).'</td></tr>';
					}
					$outStr='<table border="0" cellpadding="'.intval($wizConf['_PADDING']).'" cellspacing="0">'.$outStr.'</table>';
				} else {
					$outStr=implode('',$outArr);
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

				$item='<table border="0" cellpadding="0" cellspacing="0">'.$outStr.'</table>';
			}
		}
		return $item;
	}

	/**
	 * Get icon (for example for selector boxes)
	 * 
	 * @param	string		Icon reference
	 * @return	array		Array with two values; the icon file reference, the icon file information array (getimagesize())
	 */
	function getIcon($icon)	{
		if (substr($icon,0,4)=='EXT:')	{
			$file = t3lib_div::getFileAbsFileName($icon);
			if ($file)	{
				$file = substr($file,strlen(PATH_site));
				$selIconFile=$this->backPath.'../'.$file;
				$selIconInfo = @getimagesize(PATH_site.$file);
			}
		} elseif (substr($icon,0,3)=='../')	{
			$selIconFile=$this->backPath.$icon;
			$selIconInfo = @getimagesize(PATH_site.substr($icon,3));
		} elseif (substr($icon,0,4)=='ext/' || substr($icon,0,7)=='sysext/') {
			$selIconFile=$icon;
			$selIconInfo = @getimagesize(PATH_typo3.$icon);
		} else {
			$selIconFile='t3lib/gfx/'.$icon;
			$selIconInfo = @getimagesize(PATH_t3lib.'gfx/'.$icon);
		}
		return array($selIconFile,$selIconInfo);
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
	 * Returns parameters to set the width for a <input>-element
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
	 */
	function formElStyle($type)	{
		if ($GLOBALS['CLIENT']['FORMSTYLE'])	{	// If not setting the width by style-attribute
			$style = $this->fieldStyle['all'];
			if (isset($this->fieldStyle[$type]))	{
				$style = $this->fieldStyle[$type];
			}
			if (trim($style))	{
				return $style;
			}
		}
	}
	
	/**
	 * Return default "style" attribute line.
	 * 
	 * @param	string		Field type (eg. "check", "radio", "select")
	 * @return	string		CSS attributes
	 */
	function insertDefStyle($type)	{
		if ($GLOBALS['CLIENT']['FORMSTYLE'])	{	// If not setting the width by style-attribute
			$style = trim($this->defStyle.$this->formElStyle($type));
			return $style?' style="'.htmlspecialchars($style).'"':'';
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

			// If 'special' is configured:
		if ($fieldValue['config']['special'])	{
			switch ($fieldValue['config']['special'])	{
				case 'tables':
					$temp_tc = array_keys($TCA);
					reset($temp_tc);
					while (list(,$theTableNames)=each($temp_tc))	{
						if (!$TCA[$theTableNames]['ctrl']['adminOnly'])	{
							$items[] = array(
								$this->sL($TCA[$theTableNames]['ctrl']['title']),
								$theTableNames
							);
						}
					}
				break;
				case 'pagetypes':
					$theTypes = $TCA['pages']['columns']['doktype']['config']['items'];
					reset($theTypes);
					while (list(,$theTypeArrays)=each($theTypes))	{
						$items[] = array(
							$this->sL($theTypeArrays[0]),
							$theTypeArrays[1]
						);
					}
				break;
				case 'exclude':
					$theTypes = t3lib_BEfunc::getExcludeFields();
					reset($theTypes);
					while (list(,$theTypeArrays)=each($theTypes))	{
						$items[] = array(
							ereg_replace(':$','',$theTypeArrays[0]),
							$theTypeArrays[1]
						);
					}
				break;
				case 'modListGroup':
				case 'modListUser':
					if (!is_object($loadModules))	{
						$loadModules = t3lib_div::makeInstance('t3lib_loadModules');
						$loadModules->load($GLOBALS['TBE_MODULES']);
					}
					$modList = $fieldValue['config']['special']=='modListUser' ? $loadModules->modListUser : $loadModules->modListGroup;
					if (is_array($modList))	{
						reset($modList);
						while (list(,$theMod)=each($modList))	{
							$items[] = array(
								$this->addSelectOptionsToItemArray_makeModuleData($theMod),
								$theMod
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
	 * @see addSelectOptionsToItemArray(), t3lib_BEfunc::foreign_table_where_query()
	 */
	function foreignTable($items,$fieldValue,$TSconfig,$field,$pFFlag=0)	{
		global $TCA;	

			// Init:
		$pF=$pFFlag?'neg_':'';
		$f_table = $fieldValue['config'][$pF.'foreign_table'];
		$uidPre = $pFFlag?'-':'';

			// Get query:
		$query= t3lib_BEfunc::foreign_table_where_query($fieldValue,$field,$TSconfig,$pF);

			// Perform lookup
		$res = @mysql (TYPO3_db, $query);
		if (mysql_error())	{ 
			echo(mysql_error()."\n\nQuery:\n ".$query."\n\nThis may indicate a table defined in tables.php is not existing in the database!");
			return array();
		}

			// Get label prefix.
		$lPrefix = $this->sL($fieldValue['config'][$pF.'foreign_table_prefix']);

			// Get icon field + path if any:
		$iField = $TCA[$f_table]['ctrl']['selicon_field'];
		$iPath = trim($TCA[$f_table]['ctrl']['selicon_field_path']);
		
			// Traverse the selected rows to add them:
		while ($row = mysql_fetch_assoc($res))	{
				// Prepare the icon if available:
			if ($iField && $iPath && $row[$iField])	{
				$iParts = t3lib_div::trimExplode(',',$row[$iField],1);
				$icon = '../'.$iPath.'/'.trim($iParts[0]);
			} else $icon='';
				// Add the item:
			$items[] = array(
				t3lib_div::fixed_lgd($lPrefix.strip_tags(t3lib_BEfunc::getRecordTitle($f_table,$row)),$this->titleLen), 
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

	/**
	 * Sets the design to the backend design.
	 * Backend
	 * 
	 * @return	void		
	 */
	function setNewBEDesign()	{
		$light=0;
		
		$this->totalWrap='
		<table border="0" cellspacing="0" cellpadding="0" width="'.($this->docLarge?440+150:440).'">'.
			'<tr bgcolor="'.t3lib_div::modifyHTMLColorAll($GLOBALS['SOBE']->doc->bgColor2,$light).'">	
				<td>&nbsp;</td>
				<td>###RECORD_ICON### <font color="#333366"><b>###TABLE_TITLE###</b></font> ###ID_NEW_INDICATOR### - ###RECORD_LABEL###</td>
			</tr>'.
			'|'.
			'<tr>
				<td>&nbsp;</td>
				<td><img src="clear.gif" width="'.($this->docLarge?440+150:440).'" height="1" alt="" /></td>
			</tr>
		</table>';

		$this->fieldTemplate='
			<tr ###BGCOLOR_HEAD###>
				<td>###FIELD_HELP_ICON###</td>
				<td width="99%"><font color="###FONTCOLOR_HEAD###"><b>###FIELD_NAME###</b></font>###FIELD_HELP_TEXT###</td>
			</tr>
			<tr ###BGCOLOR###>
				<td nowrap="nowrap"><img name="req_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="10" height="10" alt="" /><img name="cm_###FIELD_TABLE###_###FIELD_ID###_###FIELD_FIELD###" src="clear.gif" width="7" height="10" alt="" /></td>
				<td valign="top">###FIELD_ITEM######FIELD_PAL_LINK_ICON###</td>
			</tr>';

		$this->palFieldTemplate='
			<tr ###BGCOLOR###>
				<td>&nbsp;</td>
				<td nowrap="nowrap" valign="top">###FIELD_PALETTE###</td>
			</tr>';
		$this->palFieldTemplateHeader='
			<tr ###BGCOLOR_HEAD###>
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
			$arr[$k]=str_replace('###ID_NEW_INDICATOR###',(strstr($rec['uid'],'NEW')?' <font color="red"><b>'.$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.new',1).'</b></font>':' ['.$rec['uid'].']'),$arr[$k]);
			$rLabel = trim(t3lib_div::fixed_lgd(t3lib_BEfunc::getRecordTitle($table,$rec),40));
			$arr[$k]=str_replace('###RECORD_LABEL###',htmlspecialchars($rLabel),$arr[$k]);
			$arr[$k]=str_replace('###TABLE_TITLE###',htmlspecialchars($this->sL($TCA[$table]['ctrl']['title'])),$arr[$k]);

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
		$inTemplate = str_replace('###BGCOLOR###',$this->colorScheme[0]?' bgcolor="'.$this->colorScheme[0].'"':'',$inTemplate);
		$inTemplate = str_replace('###BGCOLOR_HEAD###',$this->colorScheme[1]?' bgcolor="'.$this->colorScheme[1].'"':'',$inTemplate);
		$inTemplate = str_replace('###FONTCOLOR_HEAD###',$this->colorScheme[3],$inTemplate);
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
		$out='';
		reset($palArr);
		$bgColor=' bgcolor="'.$this->colorScheme[2].'"';
		while(list(,$content)=each($palArr))	{

			$hRow[]='<td'.$bgColor.'>&nbsp;</td><td nowrap="nowrap"'.$bgColor.'>'.
						'<font color="'.$this->colorScheme[4].'">'.
							$content['NAME'].
						'</font>'.
					'</td>';
			$iRow[]='<td valign="top">'.
						'<img name="req_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="10" height="10" vspace="4" alt="" />'.
						'<img name="cm_'.$content['TABLE'].'_'.$content['ID'].'_'.$content['FIELD'].'" src="clear.gif" width="7" height="10" vspace="4" alt="" />'.
					'</td><td nowrap="nowrap" valign="top">'.
						$content['ITEM'].
						$content['HELP_ICON'].
					'</td>';
		}
			// Final wrapping into the table:
		$out='<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td><img src="clear.gif" width="'.intval($this->paletteMargin).'" height="1" alt="" /></td>'.
					implode('',$hRow).'
			</tr>
			<tr>
				<td></td>'.
				implode('',$iRow).'
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
			$aOnClick = 'vHWin=window.open(\''.$this->backPath.'view_help.php?tfID='.($table.'.'.$field).'\',\'viewFieldHelp\',\'height=300,width=250,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->backPath,'gfx/helpbubble.gif','width="14" height="14"').' hspace="2" border="0" class="absmiddle"'.($GLOBALS['CLIENT']['FORMSTYLE']?' style="cursor:help;"':'').' alt="" />'.
					'</a>';
		} else return '&nbsp;';
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
					'</td><td valign="top">'.
					$this->helpTextFontTag.
					$GLOBALS['LANG']->hscAndCharConv($fDat['description'],0).
					'</font></td></tr></table>';		
		}
	}

	/**
	 * Setting the current color scheme ($this->colorScheme) based on $this->defColorScheme plus input string.
	 * 
	 * @param	string		A color scheme string.
	 * @return	void		
	 */
	function setColorScheme($scheme)	{
		$this->colorScheme=$this->defColorScheme;
		$parts = t3lib_div::trimExplode(',',$scheme);
		while(list($key,$col)=each($parts))	{
			if ($col)	$this->colorScheme[$key]=$col;
			if ($col=='-')	$this->colorScheme[$key]='';
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
		$this->colorScheme=$this->savedSchemes['colorScheme'];
		$this->fieldStyle=$this->savedSchemes['fieldStyle'];
		$this->borderStyle=$this->savedSchemes['borderStyle'];
	}













	/********************************************
	 *
	 * JavaScript related functions
	 *
	 ********************************************/

	/**
	 * JavaScript code used for input-field evaluation.
	 * 
	 * @param	string		The identification of the form on the page.
	 * @return	string		A <script></script> section with JavaScript.
	 */
	function JStop($formname='forms[0]')	{
		if (count($this->RTEwindows))	{
			$out.='


				<!--
				 	JavaScript in top of page (before form):
				-->

				<script type="text/javascript">
					/*<![CDATA[*/
						var TBE_RTE_WINDOWS=new Array();

						function TBE_EDITOR_setRTEref(RTEobj,theField,loadContent)	{	//
							TBE_RTE_WINDOWS[theField]=RTEobj;
							if (loadContent)	{
								RTEobj.setHTML(document.'.$formname.'[theField].value);
							}
						}
					/*]]>*/
				</script>
			';
		}
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
	
			$GLOBALS['JS_INCLUDED_jsfunc_evalfield_js']=1;
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
//	debug($reqRange);

			$RTEwinArr = array();
			$RTEwinArrTransfer = array();
			reset($this->RTEwindows);
			while(list(,$itemName)=each($this->RTEwindows))	{
				$RTEwinArr[]="					TBE_RTE_WINDOWS['".$itemName."']=0;";
				$RTEwinArrTransfer[]="					if(TBE_RTE_WINDOWS['".$itemName."'])	{document.".$formname."['".$itemName."'].value = TBE_RTE_WINDOWS['".$itemName."'].getHTML();}else{OK++;}";
			}			

			$this->TBE_EDITOR_fieldChanged_func='TBE_EDITOR_fieldChanged_fName(fName,formObj[fName+"_list"]);';
			
			if ($this->loadMD5_JS)	{
			$out.='
			<script type="text/javascript" src="'.$this->backPath.'md5.js"></script>';
			}
			$out.='
			<script type="text/javascript" src="'.$this->backPath.'t3lib/jsfunc.evalfield.js"></script>
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

'.implode(chr(10),$RTEwinArr).'				

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
				function TBE_EDITOR_checkSubmit(sendAlert)	{	//
					if (TBE_EDITOR_checkLoginTimeout() && confirm('.$GLOBALS['LANG']->JScharCode($this->getLL('m_refresh_login')).'))	{
						vHWin=window.open(\''.$this->backPath.'login_frameset.php?\',\'relogin\',\'height=300,width=400,status=0,menubar=0\');
						vHWin.focus();
						return false;
					}
					var OK=1;
//alert();
'.implode(chr(10),$RTEwinArrTransfer).'
					if(!OK)	{
						alert(unescape("SYSTEM ERROR: The Rich Text Editors ("+OK+") content could not be read. This IS an error, although it should not be regular.\nThe form is not saved. Try again.o\n\nPlease report the error to your administrator if it persists."));
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
						top.topmenuFrame.document.location = url+"&backRef="+(top.content.list_frame ? (top.content.list_frame.view_frame ? "top.content.list_frame.view_frame" : "top.content.list_frame") : "top.content");
					} else if (!isOnFocus) {
						var vHWin=window.open(url,"palette","height=300,width=200,status=0,menubar=0,scrollbars=1");
						vHWin.focus();
					}
				}
				function TBE_EDITOR_curSelected(theField)	{	//
					var fObjSel = document.'.$formname.'[theField];
					var retVal="";
					if (fObjSel && fObjSel.type=="select-multiple")	{
						var l=fObjSel.length;
						for (a=0;a<l;a++)	{
							if (fObjSel.options[a].selected==1)	{
								retVal+=fObjSel.options[a].value+",";
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
				if (formObj)	{
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
					var fObjSel = formObj[fName+"_list"];
					var l=fObjSel.length;
					var c=0;
					var cS=0;
					if (type=="Remove" || type=="Up")	{
						if (type=="Up")	{
							for (a=0;a<l;a++)	{
								if (fObjSel.options[a].selected==1)	{
									localArray_V[c]=fObjSel.options[a].value;
									localArray_L[c]=fObjSel.options[a].text;
									c++;
									cS++;
								}
							}
						}
						for (a=0;a<l;a++)	{
							if (fObjSel.options[a].selected!=1)	{
								localArray_V[c]=fObjSel.options[a].value;
								localArray_L[c]=fObjSel.options[a].text;
								c++;
							}
						}
					}
					fObjSel.length = c;
					for (a=0;a<c;a++)	{
						fObjSel.options[a].value = localArray_V[a];
						fObjSel.options[a].text = localArray_L[a];
						fObjSel.options[a].selected=(a<cS)?1:0;
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
			$row=array();
			if ($pid<0 && $TCA[$table]['ctrl']['useColumnsForDefaultValues'])	{
					// Fetches the previous record:
				$query = 'SELECT * FROM '.$table.' WHERE uid='.abs($pid).t3lib_BEfunc::deleteClause($table);
				$res = mysql(TYPO3_db,$query);
				echo mysql_error();
				if ($drow = mysql_fetch_assoc($res))	{
						// Gets the list of fields to copy from the previous record.
					$fArr=explode(',',$TCA[$table]['ctrl']['useColumnsForDefaultValues']);
					while(list(,$theF)=each($fArr))	{
						if ($TCA[$table]['columns'][$theF])	{
							$row[$theF]=$drow[$theF];
						}
					}
				}
			}
			reset($TCA[$table]['columns']);
			while(list($field,$info)=each($TCA[$table]['columns']))	{
				if (isset($info['config']['default']))	{
					$row[$field]=$info['config']['default'];
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
		switch(substr($str,0,2))	{
			case 'l_':
				return $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:labels.'.substr($str,2));
			break;
			case 'm_':
				return $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.php:mess.'.substr($str,2));
			break;
		} 
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
	function isDisplayCondition($displayCond,$row)	{
		$output = FALSE;
		
		$parts = explode(':',$displayCond);
		switch((string)$parts[0])	{	// Type of condition:
			case 'FIELD':
				switch((string)$parts[2])	{
					case 'REQ':
						if (strtolower($parts[3])=='true')	{
							$output = $row[$parts[1]] ? TRUE : FALSE;
						} elseif (strtolower($parts[3])=='false') {
							$output = !$row[$parts[1]] ? TRUE : FALSE;
						}
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
		$query = 'SELECT static_lang_isocode,title,uid FROM sys_language WHERE pid=0 AND NOT hidden '.t3lib_BEfunc::deleteClause('sys_language').' ORDER BY title';
		$res = mysql(TYPO3_db,$query);

			// Traverse them:
		$output=array();
		if ($setDefault)	{
			$output[0]=array(
				'uid' => 0,
				'title' => 'Default language',
				'ISOcode' => 'DEF'
			);
		}
		while($row=mysql_fetch_assoc($res))	{
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
 * @author	Kasper Skaarhoj <kasper@typo3.com>
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
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_tceforms.php']);
}
?>
