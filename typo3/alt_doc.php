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
 * Main form rendering script
 * By sending certain parameters to this script you can bring up a form
 * which allows the user to edit the content of one or more database records.
 *
 * $Id$
 * Revised for TYPO3 3.6 November/2003 by Kasper Skaarhoj
 * XHTML compliant
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   93: class SC_alt_doc 
 *  164:     function preInit()	
 *  216:     function doProcessData()	
 *  228:     function processData()	
 *  345:     function init()	
 *  424:     function main()	
 *  481:     function printContent()	
 *
 *              SECTION: Sub-content functions, rendering specific parts of the module content.
 *  516:     function makeEditForm()	
 *  680:     function makeButtonPanel()	
 *  759:     function makeDocSel()	
 *  798:     function makeCmenu()	
 *  816:     function compileForm($panel,$docSel,$cMenu,$editForm)	
 *  875:     function functionMenus()	
 *  906:     function shortCutLink()	
 *  937:     function tceformMessages()	
 *
 *              SECTION: Other functions
 *  975:     function editRegularContentFromId()	
 * 1002:     function compileStoreDat()	
 * 1015:     function getNewIconMode($table,$key='saveDocNew')	
 * 1028:     function closeDocument($code=0)	
 * 1060:     function setDocument($currentDocFromHandlerMD5='',$retUrl='alt_doc_nodoc.php')	
 *
 * TOTAL FUNCTIONS: 19
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 
require ('init.php');
require ('template.php');
include ('sysext/lang/locallang_alt_doc.php');
require_once (PATH_t3lib.'class.t3lib_tceforms.php');


t3lib_BEfunc::lockRecords();




/**
 * Script Class: Drawing the editing form for editing records in TYPO3.
 * Notice: It does NOT use tce_db.php to submit data to, rather it handles submissions itself
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_doc {

		// Internal, static: GPvars:
	var $editconf;			// GPvar "edit": Is an array looking approx like [tablename][list-of-ids]=command, eg. "&edit[pages][123]=edit". See t3lib_BEfunc::editOnClick(). Value can be seen modified internally.
	var $columnsOnly;		// Commalist of fieldnames to edit. The point is IF you specify this list, only those fields will be rendered in the form. Otherwise all (available) fields in the record is shown according to the types configuration in $TCA
	var $defVals;			// Default values for fields (array with tablenames, fields etc. as keys). Can be seen modified internally.
	var $overrideVals;		// Array of values to force being set (as hidden fields). Will be set as $this->defVals IF defVals does not exist.
	var $returnUrl;			// If set, this value will be set in $this->retUrl (which is used quite many places as the return URL). If not set, "dummy.php" will be set in $this->retUrl
	var $closeDoc;			// Close-document command. Not really sure of all options...
	var $doSave;			// Quite simply, if this variable is set, then the processing of incoming data will be performed - as if a save-button is pressed. Used in the forms as a hidden field which can be set through JavaScript if the form is somehow submitted by JavaScript).

	var $data;				// GPvar (for processing only) : The data array from which the data comes...
	var $mirror;			// GPvar (for processing only) : ?
	var $cacheCmd;			// GPvar (for processing only) : Clear-cache cmd.
	var $redirect;			// GPvar (for processing only) : Redirect (not used???)
	var $disableRTE;		// GPvar (for processing only) : If set, the rich text editor is disabled in the forms. 
	var $returnNewPageId;	// GPvar (for processing only) : Boolean: If set, then the GET var "&id=" will be added to the retUrl string so that the NEW id of something is returned to the script calling the form.
	var $vC;				// GPvar (for processing only) : Verification code, internal stuff.
	
	var $popViewId;			// GPvar (module) : ID for displaying the page in the frontend (used for SAVE/VIEW operations)
	var $viewUrl;			// GPvar (module) : Alternative URL for viewing the frontend pages.
	var $editRegularContentFromId;		// If this is pointing to a page id it will automatically load all content elements (NORMAL column/default language) from that page into the form!
	var $recTitle;				// Alternative title for the document handler.
	var $disHelp;				// Disable help... ?
	var $noView;				// If set, then no SAVE/VIEW button is printed 
	var $returnEditConf;		// If set, the $this->editconf array is returned to the calling script (used by wizard_add.php for instance)

	
		// Internal, static:
	var $doc;				// Document template object
	var $content;			// Content accumulation

	var $retUrl;			// Return URL script, processed. This contains the script (if any) that we should RETURN TO from the alt_doc.php script IF we press the close button. Thus this variable is normally passed along from the calling script so we can properly return if needed.
	var $R_URL_parts;		// Contains the parts of the REQUEST_URI (current url). By parts we mean the result of resolving REQUEST_URI (current url) by the parse_url() function. The result is an array where eg. "path" is the script path and "query" is the parameters...
	var $R_URL_getvars;		// Contains the current GET vars array; More specifically this array is the foundation for creating the R_URI internal var (which becomes the "url of this script" to which we submit the forms etc.)
	var $R_URI;				// Set to the URL of this script including variables which is needed to re-display the form. See main()

	var $storeTitle;		// Is loaded with the "title" of the currently "open document" - this is used in the Document Selector box. (see makeDocSel())
	var $storeArray;		// Contains an array with key/value pairs of GET parameters needed to reach the current document displayed - used in the Document Selector box. (see compileStoreDat())
	var $storeUrl;			// Contains storeArray, but imploded into a GET parameter string (see compileStoreDat())
	var $storeUrlMd5;		// Hashed value of storeURL (see compileStoreDat())

	var $docDat;			// Module session data
	var $docHandler;		// An array of the "open documents" - keys are md5 hashes (see $storeUrlMd5) identifying the various documents on the GET parameter list needed to open it. The values are arrays with 0,1,2 keys with information about the document (see compileStoreDat()). The docHandler variable is stored in the $docDat session data, key "0".


		// Internal: Related to the form rendering:
	var $elementsData;		// Array of the elements to create edit forms for.
	var $firstEl;			// Pointer to the first element in $elementsData
	var $errorC;			// Counter, used to count the number of errors (when users do not have edit permissions)
	var $newC;				// Counter, used to count the number of new record forms displayed
	var $viewId;			// Is set the the pid value of the last shown record - thus indicating which page to show when clicking the SAVE/VIEW button
	var $modTSconfig;		// Module TSconfig, loaded from main() based on the page id value of viewId
	var $tceforms;			// Contains the instance of TCEforms class.
	var $generalPathOfForm;	// Contains the root-line path of the currently edited record(s) - for display.

	
		// Internal, dynamic:	
	var $dontStoreDocumentRef;	// Used internally to disable the storage of the document reference (eg. new records)
	
	
	
	
	
					

	/**
	 * First initialization.
	 *
	 * @return	void
	 */
	function preInit()	{
		global $BE_USER;
		
			// Setting GPvars:
		$this->editconf = t3lib_div::_GP('edit');
		$this->defVals = t3lib_div::_GP('defVals');
		$this->overrideVals = t3lib_div::_GP('overrideVals');
		$this->columnsOnly = t3lib_div::_GP('columnsOnly');
		$this->returnUrl = t3lib_div::_GP('returnUrl');
		$this->closeDoc = t3lib_div::_GP('closeDoc');
		$this->doSave = t3lib_div::_GP('doSave');
		$this->returnEditConf = t3lib_div::_GP('returnEditConf');
		
			// Setting override values as default if defVals does not exist.
		if (!is_array($this->defVals) && is_array($this->overrideVals))	{
			$this->defVals = $this->overrideVals;
		}
		
			// Setting return URL
		$this->retUrl = $this->returnUrl ? $this->returnUrl : 'dummy.php';
		
			// Make R_URL (request url) based on input GETvars:
		$this->R_URL_parts = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));
		$this->R_URL_getvars = t3lib_div::_GET();
		
			// MAKE url for storing
		$this->compileStoreDat();
		
			// Initialize more variables.
		$this->dontStoreDocumentRef=0;
		$this->storeTitle='';
		
			// Get session data for the module:
		$this->docDat = $BE_USER->getModuleData('alt_doc.php','ses');
		$this->docHandler = $this->docDat[0];

			// If a request for closing the document has been sent, act accordingly:
		if ($this->closeDoc>0)	{
			$this->closeDocument($this->closeDoc);
		}

			// If NO vars are sent to the script, try to read first document:
		if (is_array($this->R_URL_getvars) && count($this->R_URL_getvars)<2 && !is_array($this->editconf))	{	// Added !is_array($this->editconf) because editConf must not be set either. Anyways I can't figure out when this situation here will apply...
			$this->setDocument($this->docDat[1]);
		}
	}

	/**
	 * Detects, if a save command has been triggered.
	 *
	 * @return	boolean		True, then save the document (data submitted)
	 */
	function doProcessData()	{
		global $HTTP_POST_VARS;
		
		$out = $this->doSave || isset($HTTP_POST_VARS['_savedok_x']) || isset($HTTP_POST_VARS['_saveandclosedok_x']) || isset($HTTP_POST_VARS['_savedokview_x']) || isset($HTTP_POST_VARS['_savedoknew_x']);
		return $out;
	}

	/**
	 * Do processing of data, submitting it to TCEmain.
	 *
	 * @return	void
	 */
	function processData()	{
		global $BE_USER,$HTTP_POST_VARS,$TYPO3_CONF_VARS;

			// GPvars specifically for processing:
		$this->data = t3lib_div::_GP('data');
		$this->mirror = t3lib_div::_GP('mirror');
		$this->cacheCmd = t3lib_div::_GP('cacheCmd');
		$this->redirect = t3lib_div::_GP('redirect');
		$this->disableRTE = t3lib_div::_GP('_disableRTE');
		$this->returnNewPageId = t3lib_div::_GP('returnNewPageId');
		$this->vC = t3lib_div::_GP('vC');

			// See tce_db.php for relevate options here:
			// Only options related to $this->data submission are included here.
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
		$tce->stripslashes_values=0;
	
			// Setting default values specific for the user:
		$TCAdefaultOverride = $BE_USER->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
	
			// Setting internal vars:
		if ($BE_USER->uc['neverHideAtCopy'])	{	$tce->neverHideAtCopy = 1;	}
		$tce->debug=0;
		$tce->disableRTE = $this->disableRTE;

			// Loading TCEmain with data:
		$tce->start($this->data,array());
		if (is_array($this->mirror))	{	$tce->setMirror($this->mirror);	}
		
			// If pages are being edited, we set an instruction about updating the page tree after this operation.
		if (isset($this->data['pages']))	{
			t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
		}
		

			// Checking referer / executing
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost!=$refInfo['host'] && $this->vC!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$tce->log('',0,0,0,1,"Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",1,array($refInfo['host'],$httpHost));
			debug('Error: Referer host did not match with server host.');
		} else {

				// Perform the saving operation with TCEmain:
			$tce->process_uploads($GLOBALS['HTTP_POST_FILES']);
			$tce->process_datamap();
			
				// If there was saved any new items, load them:
			if (count($tce->substNEWwithIDs_table))	{

					// Resetting editconf:
				$this->editconf = array();	
				
					// Traverse all new records and forge the content of ->editconf so we can continue to EDIT these records!
				foreach($tce->substNEWwithIDs_table as $nKey => $nTable)	{
					$this->editconf[$nTable][$tce->substNEWwithIDs[$nKey]]='edit';
					if ($nTable=='pages' && $this->retUrl!='dummy.php' && $this->returnNewPageId)	{
						$this->retUrl.='&id='.$tce->substNEWwithIDs[$nKey];
					}
				}
				
					// Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
				$this->R_URL_getvars['edit']=$this->editconf;
					
					// Unsetting default values since we don't need them anymore.
				unset($this->R_URL_getvars['defVals']);
				
					// Re-compile the store* values since editconf changed...
				$this->compileStoreDat();
			}
				
				// If a document is saved and a new one is created right after.
			if (isset($HTTP_POST_VARS['_savedoknew_x']) && is_array($this->editconf))	{

					// Finding the current table:
				reset($this->editconf);
				$nTable=key($this->editconf);

					// Finding the first id, getting the records pid+uid				
				reset($this->editconf[$nTable]);
				$nUid=key($this->editconf[$nTable]);
				$nRec = t3lib_BEfunc::getRecord($nTable,$nUid,'pid,uid');
	
					// Setting a blank editconf array for a new record:
				$this->editconf=array();
				if ($this->getNewIconMode($nTable)=='top')	{
					$this->editconf[$nTable][$nRec['pid']]='new';	
				} else {
					$this->editconf[$nTable][-$nRec['uid']]='new';	
				}
				
					// Finally, set the editconf array in the "getvars" so they will be passed along in URLs as needed.
				$this->R_URL_getvars['edit']=$this->editconf;
				
					// Re-compile the store* values since editconf changed...
				$this->compileStoreDat();
			}
	
			$tce->printLogErrorMessages(
				isset($HTTP_POST_VARS['_saveandclosedok_x']) ? 
				$this->retUrl : 
				$this->R_URL_parts['path'].'?'.t3lib_div::implodeArrayForUrl('',$this->R_URL_getvars)	// popView will not be invoked here, because the information from the submit button for save/view will be lost .... But does it matter if there is an error anyways?
			);
		}
		if (isset($HTTP_POST_VARS['_saveandclosedok_x']) || $this->closeDoc<0)	{	//  || count($tce->substNEWwithIDs)... If any new items has been save, the document is CLOSED because if not, we just get that element re-listed as new. And we don't want that!
			$this->closeDocument(abs($this->closeDoc));
		}
	}

	/**
	 * Initialize the normal module operation
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$HTTP_POST_VARS;

			// Setting more GPvars:
		$this->popViewId = t3lib_div::_GP('popViewId');
		$this->viewUrl = t3lib_div::_GP('viewUrl');		
		$this->editRegularContentFromId = t3lib_div::_GP('editRegularContentFromId');
		$this->recTitle = t3lib_div::_GP('recTitle');
		$this->disHelp = t3lib_div::_GP('disHelp');
		$this->noView = t3lib_div::_GP('noView');

			// Set other internal variables:		
		$this->R_URL_getvars['returnUrl']=$this->retUrl;
		$this->R_URI = $this->R_URL_parts['path'].'?'.t3lib_div::implodeArrayForUrl('',$this->R_URL_getvars);
	
			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'showPalettes' => '',
			'showDescriptions' => '',
			'disableRTE' => ''
		);

			// Setting virtual document name		
		$this->MCONF['name']='xMOD_alt_doc.php';

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::_GP('SET'), $this->MCONF['name']);

			// Create an instance of the document template object
		$this->doc = t3lib_div::makeInstance('bigDoc');
		$this->doc->bodyTagMargins['x']=5;
		$this->doc->bodyTagMargins['y']=5;
		$this->doc->backPath = $BACK_PATH;
		$this->doc->docType = 'xhtml_trans';

		$this->doc->form='<form action="'.htmlspecialchars($this->R_URI).'" method="post" enctype="'.$GLOBALS['TYPO3_CONF_VARS']['SYS']['form_enctype'].'" name="editform" onsubmit="return TBE_EDITOR_checkSubmit(1);">';

		$this->doc->JScode = $this->doc->wrapScriptTags('
			function jumpToUrl(URL,formEl)	{	//
				if (!TBE_EDITOR_isFormChanged())	{
					document.location = URL;
				} else if (formEl && formEl.type=="checkbox") {
					formEl.checked = formEl.checked ? 0 : 1;
				}
			}
		
				// Object: TS:
			function typoSetup	()	{	//
				this.uniqueID = "";
			}
			var TS = new typoSetup();
		
				// Info view:
			function launchView(table,uid,bP)	{	//
				var backPath= bP ? bP : "";
				var thePreviewWindow="";
				thePreviewWindow = window.open(backPath+"show_item.php?table="+escape(table)+"&uid="+escape(uid),"ShowItem"+TS.uniqueID,"height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");	
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function deleteRecord(table,id,url)	{	//
				if (confirm('.$LANG->JScharCode($LANG->getLL('deleteWarning')).'))	{	
					document.location = "tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC='.$BE_USER->veriCode().'&prErr=1&uPT=1";
				}
				return false;
			}
		'.(isset($HTTP_POST_VARS['_savedokview_x']) && $this->popViewId ? t3lib_BEfunc::viewOnClick($this->popViewId,'',t3lib_BEfunc::BEgetRootLine($this->popViewId),'',$this->viewUrl) : '')
		);
	}

	/**
	 * Main module operation
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG;

			// Starting content accumulation:
		$this->content='';
		$this->content.=$this->doc->startPage('TYPO3 Edit Document');
		
			// Begin edit:
		if (is_array($this->editconf))	{
		
				// Initialize TCEforms (rendering the forms)
			$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			$this->tceforms->initDefaultBEMode();
			$this->tceforms->doSaveFieldName='doSave';
			$this->tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
			$this->tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];

				// Setting external variables:
			if ($BE_USER->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$this->tceforms->edit_showFieldHelp='text';

			if ($this->editRegularContentFromId)	{
				$this->editRegularContentFromId();
			}
			
				// Creating the editing form, wrap it with buttons, document selector etc.
			$editForm = $this->makeEditForm();
			if ($editForm)	{
				reset($this->elementsData);
				$this->firstEl = current($this->elementsData);
		
				if ($this->viewId)	{
						// Module configuration:
					$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->viewId,'mod.xMOD_alt_doc');
				} else $this->modTSconfig=array();
		
				$panel = $this->makeButtonPanel();
				$docSel = $this->makeDocSel();
				$cMenu = $this->makeCmenu();
		
				$formContent = $this->compileForm($panel,$docSel,$cMenu,$editForm);

				$this->content.=$this->tceforms->printNeededJSFunctions_top().
								$formContent.
								$this->tceforms->printNeededJSFunctions();
				$this->content.=$this->functionMenus();
				$this->content.=$this->shortCutLink();
				
				$this->tceformMessages();
			}
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{

		echo $this->content.$this->doc->endPage();
	}











	








	/***************************
	 *
	 * Sub-content functions, rendering specific parts of the module content.
	 *
	 ***************************/
	 
	/**
	 * Creates the editing form with TCEforms, based on the input from GPvars.
	 *
	 * @return	string		HTML form elements wrapped in tables
	 */
	function makeEditForm()	{
		global $BE_USER,$LANG,$TCA;

			// Initialize variables:
		$this->elementsData=array();
		$this->errorC=0;
		$this->newC=0;
		$thePrevUid='';
		$editForm='';

			// Traverse the GPvar edit array
		foreach($this->editconf as $table => $conf)	{	// Tables:
			if (is_array($conf) && $TCA[$table] && $BE_USER->check('tables_modify',$table))	{
			
					// Traverse the keys/comments of each table (keys can be a commalist of uids)
				foreach($conf as $cKey => $cmd)	{
					if ($cmd=='edit' || $cmd=='new')	{		
					
							// Get the ids:
						$ids = t3lib_div::trimExplode(',',$cKey,1);
						
							// Traverse the ids:
						foreach($ids as $theUid)	{
						
								// Checking if the user has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
								// First, resetting flags.
							$hasAccess = 1;
							$deleteAccess=0;
							$this->viewId=0;
							
								// If the command is to create a NEW record...:
							if ($cmd=='new')	{
								if (intval($theUid))	{		// NOTICE: the id values in this case points to the page uid onto which the record should be create OR (if the id is negativ) to a record from the same table AFTER which to create the record.
								
										// Find parent page on which the new record reside
									if ($theUid<0)	{	// Less than zero - find parent page
										$calcPRec=t3lib_BEfunc::getRecord($table,abs($theUid));
										$calcPRec=t3lib_BEfunc::getRecord('pages',$calcPRec['pid']);
									} else {	// always a page
										$calcPRec=t3lib_BEfunc::getRecord('pages',abs($theUid));
									}
									
										// Now, calculate whether the user has access to creating new records on this position:
									if (is_array($calcPRec))	{
										$CALC_PERMS = $BE_USER->calcPerms($calcPRec);	// Permissions for the parent page
										if ($table=='pages')	{	// If pages:
											$hasAccess = $CALC_PERMS&8 ? 1 : 0;
											$this->viewId = $calcPRec['pid'];
										} else {
											$hasAccess = $CALC_PERMS&16 ? 1 : 0;
											$this->viewId = $calcPRec['uid'];
										}
									}
								}
								$this->dontStoreDocumentRef=1;		// Don't save this document title in the document selector if the document is new.
							} else {	// Edit:
								$calcPRec=t3lib_BEfunc::getRecord($table,$theUid);
								if (is_array($calcPRec))	{
									if ($table=='pages')	{	// If pages:
										$CALC_PERMS = $BE_USER->calcPerms($calcPRec);
										$hasAccess = $CALC_PERMS&2 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&4 ? 1 : 0;
										$this->viewId = $calcPRec['uid'];
									} else {
										$CALC_PERMS = $BE_USER->calcPerms(t3lib_BEfunc::getRecord('pages',$calcPRec['pid']));	// Fetching pid-record first.
										$hasAccess = $CALC_PERMS&16 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&16 ? 1 : 0;
										$this->viewId = $calcPRec['pid'];
									}
								} else $hasAccess=0;
							}
							
							// AT THIS POINT we have checked the access status of the editing/creation of records and we can now proceed with creating the form elements:
							
							if ($hasAccess)	{
								$prevPageID = is_object($trData)?$trData->prevPageID:'';
								$trData = t3lib_div::makeInstance('t3lib_transferData');
								$trData->defVals = $this->defVals;
								$trData->lockRecords=1;
								$trData->disableRTE = $this->MOD_SETTINGS['disableRTE'];
								$trData->prevPageID = $prevPageID;
								$trData->fetchRecord($table,$theUid,$cmd=='new'?'new':'');	// 'new'
								reset($trData->regTableItems_data);
								$rec = current($trData->regTableItems_data);
								$rec['uid'] = $cmd=='new'?uniqid('NEW'):$theUid;
								$this->elementsData[]=array(
									'table' => $table,
									'uid' => $rec['uid'],
									'cmd' => $cmd,
									'deleteAccess' => $deleteAccess
								);
								if ($cmd=='new')	{
									$rec['pid'] = $theUid=='prev'?$thePrevUid:$theUid;
								}
								
									// Now, render the form:
								if (is_array($rec))	{
								
										// Setting visual path / title of form:
									$this->generalPathOfForm = $this->tceforms->getRecordPath($table,$rec);
									if (!$this->storeTitle)	{
										$this->storeTitle = $this->recTitle ? htmlspecialchars($this->recTitle) : t3lib_BEfunc::getRecordTitle($table,$rec,1);
									}

										// Setting variables in TCEforms object:
									$this->tceforms->hiddenFieldList = '';
									$this->tceforms->globalShowHelp = $this->disHelp?0:1;
									if (is_array($this->overrideVals[$table]))	{
										$this->tceforms->hiddenFieldListArr=array_keys($this->overrideVals[$table]);
									}

										// Create form for the record (either specific list of fields or the whole record):
									$panel='';
									if ($this->columnsOnly)	{
										$panel.=$this->tceforms->getListedFields($table,$rec,$this->columnsOnly);
									} else {
										$panel.=$this->tceforms->getMainFields($table,$rec);
									}
									$panel=$this->tceforms->wrapTotal($panel,$rec,$table);
		
										// Setting the pid value for new records:
									if ($cmd=='new')	{
										$panel.='<input type="hidden" name="data['.$table.']['.$rec['uid'].'][pid]" value="'.$rec['pid'].'" />';
										$this->newC++;
									}
									
										// Display "is-locked" message:
									if ($lockInfo=t3lib_BEfunc::isRecordLocked($table,$rec['uid']))	{
										$lockIcon='
										
											<!--
											 	Warning box:
											-->
											<table border="0" cellpadding="0" cellspacing="0" class="warningbox">
												<tr>
													<td><img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/recordlock_warning3.gif','width="17" height="12"').' alt="" /></td>
													<td>'.htmlspecialchars($lockInfo['msg']).'</td>
												</tr>
											</table>
										';
									} else $lockIcon='';
	
										// Combine it all:
									$editForm.=$lockIcon.$panel;
								}
								
								$thePrevUid = $rec['uid'];
							} else {
								$this->errorC++;
								$editForm.=$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.noEditPermission',1).'<br /><br />';
							}
						}
					}
				}
			}
		}
		return $editForm;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	string		HTML code, comprised of images linked to various actions.
	 */
	function makeButtonPanel()	{
		global $TCA,$LANG;

		$panel='';
		
			// Render SAVE type buttons:
			// The action of each button is decided by its name attribute. (See doProcessData())
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'])	{
				
				// SAVE button:
			$panel.= '<input type="image" class="c-inputButton" name="_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDoc',1).'" />';
				
				// SAVE / VIEW button:
			if ($this->viewId && !$this->noView && t3lib_extMgm::isLoaded('cms')) {
				$panel.= '<input type="image" class="c-inputButton" name="_savedokview"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedokshow.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveDocShow',1).'" />';
			}
				
				// SAVE / NEW button:
			if (count($this->elementsData)==1 && $this->getNewIconMode($this->firstEl['table'])) {
				$panel.= '<input type="image" class="c-inputButton" name="_savedoknew"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedoknew.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveNewDoc',1).'" />';
			}
			
				// SAVE / CLOSE
			$panel.= '<input type="image" class="c-inputButton" name="_saveandclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc',1).'" />';
		}
			
			// CLOSE button:
		$panel.= '<a href="#" onclick="document.editform.closeDoc.value=1; document.editform.submit(); return false;">'.
				'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:rm.closeDoc',1).'" alt="" />'.
				'</a>';

			// DELETE + UNDO buttons:
		if (!$this->errorC && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'] && count($this->elementsData)==1)	{
			if ($this->firstEl['cmd']!='new' && t3lib_div::testInt($this->firstEl['uid']))	{

					// Delete:
				if ($this->firstEl['deleteAccess'] && !$TCA[$this->firstEl['table']]['ctrl']['readOnly'] && !$this->getNewIconMode($this->firstEl['table'],'disableDelete')) {
					$aOnClick = 'return deleteRecord(\''.$this->firstEl['table'].'\',\''.$this->firstEl['uid'].'\',unescape(\''.rawurlencode($this->retUrl).'\'));';
					$panel.= '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/deletedok.gif','width="21" height="16"').' class="c-inputButton" title="'.$LANG->getLL('deleteItem',1).'" alt="" />'.
							'</a>';
				}
			
					// Undo:
				$undoButton = 0;
				$undoRes = $GLOBALS['TYPO3_DB']->exec_SELECTquery('tstamp', 'sys_history', 'tablename="'.$GLOBALS['TYPO3_DB']->quoteStr($this->firstEl['table'], 'sys_history').'" AND recuid="'.intval($this->firstEl['uid']).'"', '', 'tstamp DESC', '1');
				if ($undoButtonR = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($undoRes))	{
					$undoButton = 1;
				}
				if ($undoButton) {
					$aOnClick = 'document.location=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$panel.= '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/undo.gif','width="21" height="16"').' class="c-inputButton" title="'.htmlspecialchars(sprintf($LANG->getLL('undoLastChange'),t3lib_BEfunc::calcAge(time()-$undoButtonR['tstamp'],$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears')))).'" alt="" />'.
							'</a>';
				}
				if ($this->getNewIconMode($this->firstEl['table'],'showHistory'))	{
					$aOnClick = 'document.location=\'show_rechis.php?element='.rawurlencode($this->firstEl['table'].':'.$this->firstEl['uid']).'&returnUrl='.rawurlencode($this->R_URI).'\'; return false;';
					$panel.= '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/history2.gif','width="13" height="12"').' class="c-inputButton" alt="" />'.
							'</a>';
				}
				
					// If only SOME fields are shown in the form, this will link the user to the FULL form:
				if ($this->columnsOnly)	{
					$panel.= '<a href="'.htmlspecialchars($this->R_URI.'&columnsOnly=').'">'.
							'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/edit2.gif','width="11" height="12"').' class="c-inputButton" title="'.$LANG->getLL('editWholeRecord',1).'" alt="" />'.
							'</a>';
				}
			}
		}
		return $panel;
	}

	/**
	 * Create the selector box form element which allows to select between open documents.
	 * Can be disabled through Page TSconfig.
	 *
	 * @return	string		HTML <select> element  (if applicable)
	 */
	function makeDocSel()	{
		global $BE_USER,$LANG;

			// Render the selector ONLY if it has not been disabled:
		if (!$this->modTSconfig['properties']['disableDocSelector'])	{

				// Checking if the currently open document is stored in the list of "open documents" - if not, then add it:
			if ((strcmp($this->docDat[1],$this->storeUrlMd5)||!isset($this->docHandler[$this->storeUrlMd5])) && !$this->dontStoreDocumentRef)	{
				$this->docHandler[$this->storeUrlMd5]=array($this->storeTitle,$this->storeArray,$this->storeUrl);
				$BE_USER->pushModuleData('alt_doc.php',array($this->docHandler,$this->storeUrlMd5));
			}

				// Now, create the document selector box:
			$docSel='';
			if (is_array($this->docHandler))	{
				$opt = array();
				$opt[] = '<option>[ '.$LANG->getLL('openDocs',1).': ]</option>';

					// Traverse the list of open documents:
				foreach($this->docHandler as $md5k => $setupArr)	{
					$theValue = 'alt_doc.php?'.$setupArr[2].'&returnUrl='.rawurlencode($this->retUrl);
					$opt[]='<option value="'.htmlspecialchars($theValue).'"'.(!strcmp($md5k,$this->storeUrlMd5)?' selected="selected"':'').'>'.htmlspecialchars(strip_tags(t3lib_div::htmlspecialchars_decode($setupArr[0]))).'</option>';
				}

					// Compile the selector box finally:
				$onChange = 'if(this.options[this.selectedIndex].value && !TBE_EDITOR_isFormChanged()){document.location=(this.options[this.selectedIndex].value);}';
				$docSel='<select name="_docSelector" onchange="'.htmlspecialchars($onChange).'">'.implode('',$opt).'</select>';
			}
		} else $docSel='';
		return $docSel;
	}

	/**
	 * Create the selector box form element which allows to select a clear-cache operation.
	 * Can be disabled through Page TSconfig.
	 *
	 * @return	string		HTML <select> element (if applicable)
	 * @see template::clearCacheMenu()
	 */
	function makeCmenu()	{

			// Generate the menu if NOT disabled:
		if (!$this->modTSconfig['properties']['disableCacheSelector'])	{
			$cMenu = $this->doc->clearCacheMenu(intval($this->viewId),!$this->modTSconfig['properties']['disableDocSelector']);
		} else $cMenu ='';
		return $cMenu;
	}

	/**
	 * Put together the various elements (buttons, selectors, form) into a table
	 *
	 * @param	string		The button panel HTML
	 * @param	string		Document selector HTML
	 * @param	string		Clear-cache menu HTML
	 * @param	string		HTML form.
	 * @return	string		Composite HTML
	 */
	function compileForm($panel,$docSel,$cMenu,$editForm)	{
		global $LANG;
		
			
		$formContent='';
		$formContent.='

			<!--
			 	Header of the editing page.
				Contains the buttons for saving/closing, the document selector and menu selector.
				Shows the path of the editing operation as well.
			-->
			<table border="0" cellpadding="0" cellspacing="1" width="470" id="typo3-altdoc-header">
				<tr>
					<td nowrap="nowrap" valign="top">'.$panel.'</td>
					<td nowrap="nowrap" valign="top" align="right">'.$docSel.$cMenu.'</td>
				</tr>
				<tr>
					<td colspan="2">'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.path',1).': '.htmlspecialchars($this->generalPathOfForm).'</td>
				</tr>
			</table>
			<img src="clear.gif" width="1" height="4" alt="" /><br />
			
			


			<!--
			 	EDITING FORM:
			-->

			'.$editForm.'



			<!--
			 	Saving buttons (same as in top)
			-->
			
			'.$panel.
			'<input type="hidden" name="returnUrl" value="'.htmlspecialchars($this->retUrl).'" />
			<input type="hidden" name="viewUrl" value="'.htmlspecialchars($this->viewUrl).'" />';
		
		if ($this->returnNewPageId)	{
			$formContent.='<input type="hidden" name="returnNewPageId" value="1" />';
		}
		$formContent.='<input type="hidden" name="popViewId" value="'.htmlspecialchars($this->viewId).'" />';
		$formContent.='<input type="hidden" name="closeDoc" value="0" />';
		$formContent.='<input type="hidden" name="doSave" value="0" />';
		$formContent.='<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'" />';
		$formContent.='<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'" />';

		return $formContent;
	}

	/**
	 * Create the checkbox buttons in the bottom of the pages.
	 *
	 * @return	string		HTML for function menus.
	 */
	function functionMenus()	{
		global $BE_USER,$LANG;

		$funcMenus = '';
		
			// Show palettes:
		$funcMenus.= '<br /><br />'.t3lib_BEfunc::getFuncCheck('','SET[showPalettes]',$this->MOD_SETTINGS['showPalettes'],'alt_doc.php',t3lib_div::implodeArrayForUrl('',array_merge($this->R_URL_getvars,array('SET'=>'')))).$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showPalettes',1);

			// Show descriptions/help texts:
		if ($BE_USER->uc['edit_showFieldHelp']!='text') {
			$funcMenus.= '<br />'.t3lib_BEfunc::getFuncCheck('','SET[showDescriptions]',$this->MOD_SETTINGS['showDescriptions'],'alt_doc.php',t3lib_div::implodeArrayForUrl('',array_merge($this->R_URL_getvars,array('SET'=>'')))).$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.showDescriptions',1);
		}
			
			// Show disable RTE checkbox:
		if ($BE_USER->isRTE())	{
			$funcMenus.= '<br />'.t3lib_BEfunc::getFuncCheck('','SET[disableRTE]',$this->MOD_SETTINGS['disableRTE'],'alt_doc.php',t3lib_div::implodeArrayForUrl('',array_merge($this->R_URL_getvars,array('SET'=>'')))).$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.disableRTE',1);
		}
		
		return '
								
				<!--
				 	Function menus (checkboxes for selecting options):
				-->
				'.$funcMenus;
	}

	/**
	 * Create shortcut and open-in-window link in the bottom of the page
	 *
	 * @return	string
	 */
	function shortCutLink()	{
		global $BE_USER,$LANG;
		
			// ShortCut
		if ($this->returnUrl!='close.html')	{
			$content.='<br /><br />';

				// Shortcut:
			if ($BE_USER->mayMakeShortcut())	{
				$content.=$this->doc->makeShortcutIcon('returnUrl,edit,defVals,overrideVals,columnsOnly,popViewId,returnNewPageId,editRegularContentFromId,disHelp,noView',implode(',',array_keys($this->MOD_MENU)),$this->MCONF['name'],1);
			}

				// Open in new window:
			$aOnClick = 'vHWin=window.open(\''.t3lib_div::linkThisScript(array('returnUrl'=>'close.html')).'\',\''.md5($this->R_URI).'\',\''.($BE_USER->uc['edit_wideDocument']?'width=670,height=500':'width=600,height=400').',status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;';
			$content.='<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.
					'<img'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/open_in_new_window.gif','width="19" height="14"').' title="'.$LANG->sL('LLL:EXT:lang/locallang_core.php:labels.openInNewWindow',1).'" alt="" />'.
					'</a>';
		}
		return '
								
				<!--
				 	Shortcut link:
				-->
				'.$content;
	}

	/**
	 * Reads comment messages from TCEforms and prints them in a HTML comment in the buttom of the page.
	 *
	 * @return	void
	 */
	function tceformMessages()	{
		if (count($this->tceforms->commentMessages))	{
			$this->content.='

<!-- TCEFORM messages
'.htmlspecialchars(implode(chr(10),$this->tceforms->commentMessages)).'
-->

';
		}
	}
















	/***************************
	 *
	 * Other functions
	 *
	 ***************************/
	 
	/**
	 * Function, which populates the internal editconf array with editing commands for all tt_content elements from the normal column in normal language from the page pointed to by $this->editRegularContentFromId
	 *
	 * @return	void
	 */
	function editRegularContentFromId()	{
		if (t3lib_extMgm::isLoaded('cms'))	{
			$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
						'uid', 
						'tt_content', 
						'pid='.intval($this->editRegularContentFromId).
							t3lib_BEfunc::deleteClause('tt_content').
							' AND colPos=0 AND sys_language_uid=0',
						'', 
						'sorting'
					);
			if ($GLOBALS['TYPO3_DB']->sql_num_rows($res))	{
				$ecUids=array();
				while($ecRec = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$ecUids[]=$ecRec['uid'];
				}
				$this->editconf['tt_content'][implode(',',$ecUids)]='edit';
			}
		}
	}

	/**
	 * Populates the variables $this->storeArray, $this->storeUrl, $this->storeUrlMd5
	 *
	 * @return	void
	 * @see makeDocSel()
	 */
	function compileStoreDat()	{
		$this->storeArray = t3lib_div::compileSelectedGetVarsFromArray('edit,defVals,overrideVals,columnsOnly,disHelp,noView,editRegularContentFromId',$this->R_URL_getvars);
		$this->storeUrl = t3lib_div::implodeArrayForUrl('',$this->storeArray);
		$this->storeUrlMd5 = md5($this->storeUrl);
	}

	/**
	 * Function used to look for configuration of buttons in the form: Fx. disabling buttons or showing them at various positions.
	 *
	 * @param	string		The table for which the configuration may be specific
	 * @param	string		The option for look for. Default is checking if the saveDocNew button should be displayed.
	 * @return	string		Return value fetched from USER TSconfig
	 */
	function getNewIconMode($table,$key='saveDocNew')	{
		global $BE_USER;
		$TSconfig = $BE_USER->getTSConfig('options.'.$key);
		$output = trim(isset($TSconfig['properties'][$table]) ? $TSconfig['properties'][$table] : $TSconfig['value']);
		return $output;
	}

	/**
	 * Handling the closing of a document
	 *
	 * @param	integer		Close code: 0/1 will redirect to $this->retUrl, 3 will clear the docHandler (thus closing all documents) and otehr values will call setDocument with ->retUrl
	 * @return	void
	 */
	function closeDocument($code=0)	{
		global $BE_USER;
		
			// If current document is found in docHandler, then unset it, possibly unset it ALL and finally, write it to the session data:
		if (isset($this->docHandler[$this->storeUrlMd5]))	{
			unset($this->docHandler[$this->storeUrlMd5]);
			if ($code=='3')	$this->docHandler=array();
			$BE_USER->pushModuleData('alt_doc.php',array($this->docHandler,$this->docDat[1]));
		}
		
			// If ->returnEditConf is set, then add the current content of editconf to the ->retUrl variable: (used by other scripts, like wizard_add, to know which records was created or so...)
		if ($this->returnEditConf && $this->retUrl!='dummy.php')	{
			$this->retUrl.='&returnEditConf='.rawurlencode(serialize($this->editconf));
		}

			// If code is NOT set OR set to 1, then make a header location redirect to $this->retUrl
		if (!$code || $code==1)	{
			Header('Location: '.t3lib_div::locationHeaderUrl($this->retUrl));
			exit;
		} else {
			$this->setDocument('',$this->retUrl);
		}
	}

	/**
	 * Redirects to the document pointed to by $currentDocFromHandlerMD5 OR $retUrl (depending on some internal calculations).
	 * Most likely you will get a header-location redirect from this function.
	 *
	 * @param	string		Pointer to the document in the docHandler array
	 * @param	string		Alternative/Default retUrl
	 * @return	void
	 */
	function setDocument($currentDocFromHandlerMD5='',$retUrl='alt_doc_nodoc.php')	{
		if (!t3lib_extMgm::isLoaded('cms') && !strcmp($retUrl,'alt_doc_nodoc.php'))	return;
		
		if (!$this->modTSconfig['properties']['disableDocSelector'] && is_array($this->docHandler) && count($this->docHandler))	{
			if (isset($this->docHandler[$currentDocFromHandlerMD5]))	{
				$setupArr=$this->docHandler[$currentDocFromHandlerMD5];
			} else {
				reset($this->docHandler);
				$setupArr=current($this->docHandler);
			}
			if ($setupArr[2])	{
				$sParts = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));
				$retUrl = $sParts['path'].'?'.$setupArr[2].'&returnUrl='.rawurlencode($retUrl);
			}
		}
		Header('Location: '.t3lib_div::locationHeaderUrl($retUrl));
		exit;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/alt_doc.php']);
}
















// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_doc');

// Preprocessing, storing data if submitted to
$SOBE->preInit();
if ($SOBE->doProcessData())	{		// Checks, if a save button has been clicked (or the doSave variable is sent)
	require_once (PATH_t3lib.'class.t3lib_tcemain.php');
	$SOBE->processData();
}

require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
require_once (PATH_t3lib.'class.t3lib_transferdata.php');


// Main:
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
