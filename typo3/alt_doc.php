<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class SC_alt_doc 
 *  123:     function preInit()	
 *  166:     function doProcessData()	
 *  187:     function processData()	
 *  289:     function init()	
 *  363:     function main()	
 *  415:     function printContent()	
 *  427:     function editRegularContentFromId()	
 *  449:     function makeEditForm()	
 *  588:     function makeButtonPanel()	
 *  635:     function makeDocSel()	
 *  664:     function makeCmenu()	
 *  682:     function compileForm($panel,$docSel,$cMenu,$editForm)	
 *  712:     function compileStoreDat()	
 *  724:     function functionMenus()	
 *  741:     function shortCutLink()	
 *  759:     function tceformMessages()	
 *
 *              SECTION: OTHER FUNCTIONS:
 *  791:     function getNewIconMode($table,$key="saveDocNew")	
 *  804:     function closeDocument($code=0)	
 *  831:     function setDocument($currentDocFromHandlerMD5="",$retUrl="alt_doc_nodoc.php")	
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
t3lib_div::setGPvars('defVals,overrideVals,columnsOnly',1);




/**
 * Script Class: Drawing the editing form for editing records in TYPO3.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_doc {
	var $viewId;
	var $generalPathOfForm;
	var $tceforms;
	var $content;
	var $editconf;
	var $defVals;
	var $overrideVals;
	var $columnsOnly;
	var $retUrl;
	var $R_URL_parts;
	var $R_URL_getvars;
	var $storeArray;
	var $storeUrl;
	var $storeUrlMd5;
	var $dontStoreDocumentRef;
	var $storeTitle;
	var $JSrefreshCode;
	var $docDat;
	var $docHandler;
	var $data;
	var $mirror;
	var $cacheCmd;
	var $redirect;
	var $R_URI;
	var $modTSconfig;
	var $elementsData;
	var $errorC;
	var $newC;
	var $firstEl;
	var $doc;	

	/**
	 * @return	[type]		...
	 */
	function preInit()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		
		$this->editconf = t3lib_div::GPvar('edit');

		$this->defVals=$GLOBALS['defVals'];
		$this->overrideVals=$GLOBALS['overrideVals'];
		$this->columnsOnly=$GLOBALS['columnsOnly'];
		
		if (!is_array($this->defVals) && is_array($this->overrideVals))	{
			$this->defVals = $this->overrideVals;	// Setting override values as default if defVals does not exist.
		}
		$this->retUrl = t3lib_div::GPvar('returnUrl')?t3lib_div::GPvar('returnUrl'):'dummy.php';
		
		// Make R_URL (request url)
		$this->R_URL_parts = parse_url(t3lib_div::getIndpEnv('REQUEST_URI'));
		$this->R_URL_getvars = $HTTP_GET_VARS;
		
		
		// MAKE url for storing
		$this->compileStoreDat();
		
		$this->dontStoreDocumentRef=0;
		$this->storeTitle='';
		$this->JSrefreshCode='';
		
		$this->docDat = $BE_USER->getModuleData('alt_doc.php','ses');
		$this->docHandler = $this->docDat[0];
		
		if (t3lib_div::GPvar('closeDoc')>0)	{
			$this->closeDocument(t3lib_div::GPvar('closeDoc'));
		}
			// If NO vars are send to the script, try to read first document:
		if (is_array($HTTP_GET_VARS) && count($HTTP_GET_VARS)<2 && !is_array($this->editconf))	{	// Added !is_array($this->editconf) because editConf must not be set either. Anyways I can't figure out when this situation here will apply...
			$this->setDocument($this->docDat[1]);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function doProcessData()	{
		global $HTTP_POST_VARS;
#debug(array($HTTP_POST_VARS,$GLOBALS['HTTP_GET_VARS']));
/*		debug(array(
			'_savedok_x' => isset($HTTP_POST_VARS['_savedok_x']),
			'_saveandclosedok_x' => isset($HTTP_POST_VARS['_saveandclosedok_x']),
			'_savedokview_x' => isset($HTTP_POST_VARS['_savedokview_x']),
			'_savedoknew_x' => isset($HTTP_POST_VARS['_savedoknew_x']),
			'doSave' => t3lib_div::GPvar('doSave'),
		));
	*/	
		
		$out = t3lib_div::GPvar('doSave') || isset($HTTP_POST_VARS['_savedok_x']) || isset($HTTP_POST_VARS['_saveandclosedok_x']) || isset($HTTP_POST_VARS['_savedokview_x']) || isset($HTTP_POST_VARS['_savedoknew_x']);
		return $out;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function processData()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		t3lib_div::setGPvars('data,mirror,cacheCmd,redirect');
		
		$this->data=$GLOBALS['data'];
		$this->mirror=$GLOBALS['mirror'];
		$this->cacheCmd=$GLOBALS['cacheCmd'];
		$this->redirect=$GLOBALS['redirect'];

		
			// See tce_db.php for relevate options here:
			// Only options related to $this->data submission are included here.
		$tce = t3lib_div::makeInstance('t3lib_TCEmain');
	
		if ($BE_USER->uc['neverHideAtCopy'])	{
			$tce->neverHideAtCopy = 1;
		}
	
		$TCAdefaultOverride = $BE_USER->getTSConfigProp('TCAdefaults');
		if (is_array($TCAdefaultOverride))	{
			$tce->setDefaultsFromUserTS($TCAdefaultOverride);
		}
	
		$tce->debug=0;
		$tce->disableRTE=t3lib_div::GPvar('_disableRTE');
		$tce->start($this->data,array());
		if (is_array($this->mirror))	{$tce->setMirror($this->mirror);}
		
		if (isset($this->data['pages']))	{
			t3lib_BEfunc::getSetUpdateSignal('updatePageTree');
		}
		
		// ***************************
		// Checking referer / executing
		// ***************************
		$refInfo=parse_url(t3lib_div::getIndpEnv('HTTP_REFERER'));
		$httpHost = t3lib_div::getIndpEnv('TYPO3_HOST_ONLY');
		if ($httpHost!=$refInfo['host'] && t3lib_div::GPvar('vC')!=$BE_USER->veriCode() && !$TYPO3_CONF_VARS['SYS']['doNotCheckReferer'])	{
			$tce->log('',0,0,0,1,"Referer host '%s' and server host '%s' did not match and veriCode was not valid either!",1,array($refInfo["host"],$httpHost));
			debug("Error: Referer host did not match with server host.");
		} else {
			$tce->process_uploads($GLOBALS["HTTP_POST_FILES"]);
			$tce->process_datamap();
			
			// If there was saved any new items, load them:
			if (count($tce->substNEWwithIDs_table))	{
				$this->editconf = array();
				reset($tce->substNEWwithIDs_table);
				while(list($nKey,$nTable)=each($tce->substNEWwithIDs_table))	{
					$this->editconf[$nTable][$tce->substNEWwithIDs[$nKey]]="edit";
					if ($nTable=="pages" && $this->retUrl!="dummy.php" && t3lib_div::GPvar("returnNewPageId"))	{
						$this->retUrl.="&id=".$tce->substNEWwithIDs[$nKey];
					}
				}
				$this->R_URL_getvars["edit"]=$this->editconf;
				$HTTP_GET_VARS["edit"]=$this->editconf;
				unset($HTTP_GET_VARS["defVals"]);
				$this->compileStoreDat();
			}
			if (isset($HTTP_POST_VARS["_savedoknew_x"]) && is_array($this->editconf))	{
				
				reset($this->editconf);
				$nTable=key($this->editconf);
				reset($this->editconf[$nTable]);
				$nUid=key($this->editconf[$nTable]);
				$nRec = t3lib_BEfunc::getRecord($nTable,$nUid,"pid,uid");
	
				$this->editconf=array();
				if ($this->getNewIconMode($nTable)=="top")	{
					$this->editconf[$nTable][$nRec["pid"]]="new";	
				} else {
					$this->editconf[$nTable][-$nRec["uid"]]="new";	
				}
			}
			
			if($tce->debug) {
				echo "<b>GET-vars:<br></b>";
				t3lib_div::print_array($HTTP_GET_VARS);
				
				echo "<BR><b>POST-vars:<br></b>";
				t3lib_div::print_array($HTTP_POST_VARS);
				
				echo "<BR><b>Cookies:</b><br>";
				t3lib_div::print_array($GLOBALS["HTTP_COOKIE_VARS"]);
			}
	
			$tce->printLogErrorMessages(
				isset($HTTP_POST_VARS["_saveandclosedok_x"]) ? 
				$this->retUrl : 
				$this->R_URL_parts["path"]."?".t3lib_div::implodeArrayForUrl("",$this->R_URL_getvars)	// popView will not be invoked here, because the information from the submit button for save/view will be lost .... But does it matter if there is an error anyways?
			);
		}
		if (isset($HTTP_POST_VARS["_saveandclosedok_x"]) || t3lib_div::GPvar("closeDoc")<0)	{	//  || count($tce->substNEWwithIDs)... If any new items has been save, the document is CLOSED because if not, we just get that element re-listed as new. And we don't want that!
			$this->closeDocument(abs(t3lib_div::GPvar("closeDoc")));
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->R_URL_getvars["returnUrl"]=$this->retUrl;
		$this->R_URI = $this->R_URL_parts["path"]."?".t3lib_div::implodeArrayForUrl("",$this->R_URL_getvars);
	
			// MENU-ITEMS:
			// If array, then it's a selector box menu
			// If empty string it's just a variable, that'll be saved. 
			// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			"showPalettes" => "",
			"showDescriptions" => "",
			"disableRTE" => ""
		);
		
		$this->MCONF["name"]="xMOD_alt_doc.php";

			// CLEANSE SETTINGS
		$this->MOD_SETTINGS = t3lib_BEfunc::getModuleData($this->MOD_MENU, t3lib_div::GPvar("SET"), $this->MCONF["name"]);

		
		// ***************************
		// Main:
		// ***************************
		$this->doc = t3lib_div::makeInstance("bigDoc");
		$this->doc->bodyTagMargins["x"]=5;
		$this->doc->bodyTagMargins["y"]=5;
		$this->doc->backPath = $BACK_PATH;
#		$debugThing="alert();";
		//$debugThing = "alert('In form: '+document.editform['data[tt_content][83][bodytext]'].value);";
		$this->doc->form='<form action="'.$this->R_URI.'" method="post" enctype="'.$GLOBALS["TYPO3_CONF_VARS"]["SYS"]["form_enctype"].'" name="editform" onSubmit="'.$debugThing.'return TBE_EDITOR_checkSubmit(1);" autocomplete="off">';
		$this->doc->JScode = '
<script language="javascript" type="text/javascript">
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
		if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL("deleteWarning")).'))	{	
			document.location = "tce_db.php?cmd["+table+"]["+id+"][delete]=1&redirect="+escape(url)+"&vC='.$BE_USER->veriCode().'&prErr=1&uPT=1";
		}
		return false;
	}
	
	'.(isset($HTTP_POST_VARS["_savedokview_x"])&&t3lib_div::GPVar("popViewId") ? t3lib_BEfunc::viewOnClick(t3lib_div::GPVar("popViewId"),"",t3lib_BEfunc::BEgetRootLine(t3lib_div::GPVar("popViewId")),"",t3lib_div::GPvar("viewUrl")) : '').'
</script>
		'.$this->JSrefreshCode;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->doc->startPage("TYPO3 Edit Document");
		
		
		// Begin edit:
		if (is_array($this->editconf))	{
			$this->tceforms = t3lib_div::makeInstance('t3lib_TCEforms');
			$this->tceforms->initDefaultBEMode();
			$this->tceforms->doSaveFieldName='doSave';
			$this->tceforms->palettesCollapsed = !$this->MOD_SETTINGS['showPalettes'];
			$this->tceforms->disableRTE = $this->MOD_SETTINGS['disableRTE'];

			if ($BE_USER->uc['edit_showFieldHelp']!='text' && $this->MOD_SETTINGS['showDescriptions'])	$this->tceforms->edit_showFieldHelp='text';

			if (t3lib_div::GPvar('editRegularContentFromId'))	{
				$this->editRegularContentFromId();
			}
			

			$editForm = $this->makeEditForm();
			if ($editForm)	{
				reset($this->elementsData);
				$this->firstEl = current($this->elementsData);
		
				if ($this->viewId)	{
					// Module configuration:
					$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->viewId,"mod.xMOD_alt_doc");
				} else $this->modTSconfig=array();
		
				$panel = $this->makeButtonPanel();
				$docSel = $this->makeDocSel();		
				$cMenu = $this->makeCmenu();
		
				$formContent = $this->compileForm($panel,$docSel,$cMenu,$editForm);

				$this->content.=$this->tceforms->printNeededJSFunctions_top().$formContent.$this->tceforms->printNeededJSFunctions();
				$this->content.=$this->functionMenus();
				$this->content.=$this->shortCutLink();
				
				$this->tceformMessages();
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		global $SOBE;

		//debug(array($this->content));
		echo $this->content.$this->doc->endPage();
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function editRegularContentFromId()	{
		if (t3lib_extMgm::isLoaded("cms"))	{
			$query="SELECT uid FROM tt_content WHERE pid=".intval(t3lib_div::GPvar("editRegularContentFromId")).
				t3lib_BEfunc::deleteClause("tt_content").
				" AND colPos=0 AND sys_language_uid=0".
				" ORDER BY sorting";
			$res = mysql(TYPO3_db,$query);
			if (mysql_num_rows($res))	{
				$ecUids=array();
				while($ecRec=mysql_fetch_assoc($res))	{
					$ecUids[]=$ecRec["uid"];
				}
				$this->editconf["tt_content"][implode(",",$ecUids)]="edit";
			}
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function makeEditForm()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;

		$this->elementsData=array();
		$this->errorC=0;
		$this->newC=0;
		$thePrevUid="";
		$editForm="";

		reset($this->editconf);
		while(list($table,$conf)=each($this->editconf))	{
			if (is_array($conf) && $TCA[$table] && $BE_USER->check("tables_modify",$table))	{
				reset($conf);
				while(list($cKey,$cmd)=each($conf))	{
					if ($cmd=="edit" || $cmd=="new")	{
						$ids = t3lib_div::trimExplode(",",$cKey,1);
						reset($ids);
						while(list(,$theUid)=each($ids))	{
							// Has permissions? (Only working as a precaution, because the final permission check is always down in TCE. But it's good to notify the user on beforehand...)
							$hasAccess = 1;
							$deleteAccess=0;
							$this->viewId=0;
							if ($cmd=="new")	{	// 
								if (intval($theUid))	{
									if ($theUid<0)	{	// Less than zero - find parent page
										$calcPRec=t3lib_BEfunc::getRecord($table,abs($theUid));
										$calcPRec=t3lib_BEfunc::getRecord("pages",$calcPRec["pid"]);
									} else {	// always a page
										$calcPRec=t3lib_BEfunc::getRecord("pages",abs($theUid));
									}
									if (is_array($calcPRec))	{
										$CALC_PERMS = $BE_USER->calcPerms($calcPRec);	// Permissions for the parent page
										if ($table=="pages")	{	// If pages:
											$hasAccess = $CALC_PERMS&8 ? 1 : 0;
											$this->viewId = $calcPRec["pid"];
										} else {
											$hasAccess = $CALC_PERMS&16 ? 1 : 0;
											$this->viewId = $calcPRec["uid"];
										}
									}
								}
								$this->dontStoreDocumentRef=1;
							} else {	// Edit:
								$calcPRec=t3lib_BEfunc::getRecord($table,$theUid);
								if (is_array($calcPRec))	{
									if ($table=="pages")	{	// If pages:
										$CALC_PERMS = $BE_USER->calcPerms($calcPRec);
										$hasAccess = $CALC_PERMS&2 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&4 ? 1 : 0;
										$this->viewId = $calcPRec["uid"];
									} else {
										$CALC_PERMS = $BE_USER->calcPerms(t3lib_BEfunc::getRecord("pages",$calcPRec["pid"]));	// Fetching pid-record first.
										$hasAccess = $CALC_PERMS&16 ? 1 : 0;
										$deleteAccess = $CALC_PERMS&16 ? 1 : 0;
										$this->viewId = $calcPRec["pid"];
									}
								} else $hasAccess=0;
							}
	//						debug($this->viewId);
							
							if ($hasAccess)	{
								$prevPageID = is_object($trData)?$trData->prevPageID:"";
								$trData = t3lib_div::makeInstance("t3lib_transferData");
								$trData->defVals = $this->defVals;
								$trData->lockRecords=1;
								$trData->disableRTE = $this->MOD_SETTINGS["disableRTE"];
								$trData->prevPageID = $prevPageID;
								$trData->fetchRecord($table,$theUid,$cmd=="new"?"new":"");	// "new"
								reset($trData->regTableItems_data);
								$rec = current($trData->regTableItems_data);
								$rec["uid"] = $cmd=="new"?uniqid("NEW"):$theUid;
								$this->elementsData[]=array(
									"table" => $table,
									"uid" => $rec["uid"],
									"cmd" => $cmd,
									"deleteAccess" => $deleteAccess
								);
								if ($cmd=="new")	{
									$rec["pid"] = $theUid=="prev"?$thePrevUid:$theUid;
								}
								
									// Now, render the form:
									
								if (is_array($rec))	{
									$this->generalPathOfForm = $this->tceforms->getRecordPath($table,$rec);
	
									if (!$this->storeTitle)	$this->storeTitle=t3lib_div::GPvar("recTitle")?t3lib_div::GPvar("recTitle"):t3lib_BEfunc::getRecordTitle($table,$rec,1);
									$panel="";
		//							debug($rec);
									$this->tceforms->hiddenFieldList = "";
									$this->tceforms->globalShowHelp = t3lib_div::GPvar("disHelp")?0:1;
									if (is_array($this->overrideVals[$table]))	{
										$this->tceforms->hiddenFieldListArr=array_keys($this->overrideVals[$table]);
									}
									if ($this->columnsOnly)	{
										$panel.=$this->tceforms->getListedFields($table,$rec,$this->columnsOnly);
									} else {
										$panel.=$this->tceforms->getMainFields($table,$rec);
									}
									$panel=$this->tceforms->wrapTotal($panel,$rec,$table);
	//								debug($this->tceforms->hiddenFieldAccum);
		
									if ($cmd=="new")	{
										$panel.='<input type="hidden" name="data['.$table.']['.$rec["uid"].'][pid]" value="'.$rec["pid"].'">';
										$this->newC++;
									}
									
									if ($lockInfo=t3lib_BEfunc::isRecordLocked($table,$rec["uid"]))	{
										$lockIcon='<BR><table align="center" border=0 cellpadding=4 cellspacing=0 bgcolor="yellow" style="border:solid 2px black;"><tr><td>
											<img src="gfx/recordlock_warning3.gif" width="17" height="12" vspace=2 hspace=10 border="0" align=top></td><td><strong>'.htmlspecialchars($lockInfo["msg"]).'</strong>
										</td></tr></table><BR><BR>
											';
									} else $lockIcon="";
	
									$editForm.=$lockIcon.$panel;
								}
								
			//					debug(($theUid=="prev"?$thePrevUid:$theUid));
			//					debug($theUid);
			//					debug($thePrevUid,1);
								$thePrevUid = $rec["uid"];
			//					debug($thePrevUid);
							} else {
								$this->errorC++;
								$editForm.=$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.noEditPermission")."<BR><BR>";
							}
						}
					}
				}
			}
		}
		return $editForm;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function makeButtonPanel()	{
		global $TCA,$LANG;
		$panel="";
		if (!$this->errorC && !$TCA[$this->firstEl["table"]]["ctrl"]["readOnly"])	{
				// if (!$this->newC) ...
			$panel.= '<input type="image" border=0 name="_savedok" src="gfx/savedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDoc",1)).'>';
			if ($this->viewId && !t3lib_div::GPvar("noView") && t3lib_extMgm::isLoaded("cms")) $panel.= '<input type="image" border=0 name="_savedokview" src="gfx/savedokshow.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveDocShow",1)).'>';
			if (count($this->elementsData)==1 && $this->getNewIconMode($this->firstEl["table"])) $panel.= '<input type="image" border=0 name="_savedoknew" src="gfx/savedoknew.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveNewDoc",1)).'>';
			$panel.= '<input type="image" border=0 name="_saveandclosedok" src="gfx/saveandclosedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.saveCloseDoc",1)).'>';
		}
		$panel.= '<a href="#" onClick="document.editform.closeDoc.value=1; document.editform.submit(); return false;"><img border=0 src="gfx/closedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:rm.closeDoc"),1).'></a>';

		if (!$this->errorC && !$TCA[$this->firstEl["table"]]["ctrl"]["readOnly"] && count($this->elementsData)==1)	{
			if ($this->firstEl["cmd"]!="new" && t3lib_div::testInt($this->firstEl["uid"]))	{
					// Delete:
				if ($this->firstEl["deleteAccess"] && !$TCA[$this->firstEl["table"]]["ctrl"]["readOnly"] && !$this->getNewIconMode($this->firstEl["table"],"disableDelete")) {
					$panel.= '<a href="#" onClick="return deleteRecord(\''.$this->firstEl["table"].'\',\''.$this->firstEl["uid"].'\',unescape(\''.rawurlencode($this->retUrl).'\'));"><img border=0 src="gfx/deletedok.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib($LANG->getLL("deleteItem"),1).' align=top></a>';
				}
			
					// Undo:
				$undoButton=0;
				$undoQuery="SELECT tstamp FROM sys_history WHERE tablename='".$this->firstEl["table"]."' AND recuid='".$this->firstEl["uid"]."' ORDER BY tstamp DESC LIMIT 1";
				$undoRes = mysql(TYPO3_db,$undoQuery);
				if ($undoButtonR = mysql_fetch_assoc($undoRes))	{
					$undoButton=1;
				}
				if ($undoButton) {
					$panel.= '<a href="#" onClick="document.location=\'show_rechis.php?element='.rawurlencode($this->firstEl["table"].':'.$this->firstEl["uid"]).'&revert=ALL_FIELDS&sumUp=-1&returnUrl='.rawurlencode($this->R_URI).'\'; return false;"><img border=0 src="gfx/undo.gif" hspace=2 width="21" height="16"'.t3lib_BEfunc::titleAttrib(htmlspecialchars(sprintf($LANG->getLL("undoLastChange"),t3lib_BEfunc::calcAge(time()-$undoButtonR["tstamp"],$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.minutesHoursDaysYears"))))).' align=top></a>';
				}
				if ($this->getNewIconMode($this->firstEl["table"],"showHistory"))	{
					$panel.= '<a href="#" onClick="document.location=\'show_rechis.php?element='.rawurlencode($this->firstEl["table"].':'.$this->firstEl["uid"]).'&returnUrl='.rawurlencode($this->R_URI).'\'; return false;"><img border=0 src="gfx/history2.gif" hspace=2 vspace=2 width="13" height="12"'.t3lib_BEfunc::titleAttrib("").' align=top></a>';
				}
				
					// columnsOnly to all:
				if ($this->columnsOnly)	{
					$panel.= '<a href="'.$this->R_URI.'&columnsOnly="><img src="gfx/edit2.gif" width="11" height="12" hspace=5 border="0"'.t3lib_BEfunc::titleAttrib(htmlspecialchars($LANG->getLL("editWholeRecord"))).'></a>';
				}
			}
		}
		return $panel;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function makeDocSel()	{
		global $BE_USER,$LANG;
		// DOC-handler
		if (!$this->modTSconfig["properties"]["disableDocSelector"])	{
			if ((strcmp($this->docDat[1],$this->storeUrlMd5)||!isset($this->docHandler[$this->storeUrlMd5])) && !$this->dontStoreDocumentRef)	{
//debug("stored");
//debug($this->storeUrl);
				$this->docHandler[$this->storeUrlMd5]=array($this->storeTitle,$this->storeArray,$this->storeUrl);
				$BE_USER->pushModuleData("alt_doc.php",array($this->docHandler,$this->storeUrlMd5));
			}
			$docSel="";
			if (is_array($this->docHandler))	{
				reset($this->docHandler);
				$opt=array();
				$opt[]='<option>[ '.$LANG->getLL("openDocs").': ]</option>';
				while(list($md5k,$setupArr)=each($this->docHandler))	{
					$opt[]='<option value="alt_doc.php?'.htmlspecialchars($setupArr[2]."&returnUrl=".rawurlencode($this->retUrl)).'"'.(!strcmp($md5k,$this->storeUrlMd5)?" SELECTED":"").'>'.$setupArr[0].'</option>';
				}
				$docSel='<select name="_docSelector" onChange="if(this.options[this.selectedIndex].value && !TBE_EDITOR_isFormChanged()){document.location=(this.options[this.selectedIndex].value);}">'.implode("",$opt).'</select>';
			}
		} else $docSel="";
		return $docSel;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function makeCmenu()	{
		global $SOBE;
		
		if (!$this->modTSconfig["properties"]["disableCacheSelector"])	{	//$this->viewId && 
			$cMenu = $this->doc->clearCacheMenu(intval($this->viewId),!$this->modTSconfig["properties"]["disableDocSelector"]);
		} else $cMenu ="";
		return $cMenu;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$panel: ...
	 * @param	[type]		$docSel: ...
	 * @param	[type]		$cMenu: ...
	 * @param	[type]		$editForm: ...
	 * @return	[type]		...
	 */
	function compileForm($panel,$docSel,$cMenu,$editForm)	{
		global $LANG;
		
		$formContent="";
		$formContent.='<table border=0 cellpadding=0 cellspacing=1 width=470>
			<tr><td nowrap valign=top>'.$panel.'</td><td nowrap valign=top align=right>'.$docSel.$cMenu.'</td></tr>
			<tr><td colspan=2>'.$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.path").': '.htmlspecialchars($this->generalPathOfForm).'</td></tr>
			</table><img src=clear.gif width=1 height=4><BR>'.
			$editForm.
			$panel.
			'<input type="hidden" name="returnUrl" value="'.htmlspecialchars($this->retUrl).'">
			<input type="hidden" name="viewUrl" value="'.htmlspecialchars(t3lib_div::GPvar("viewUrl")).'">';
		
		if (t3lib_div::GPvar("returnNewPageId"))	{
			$formContent.='<input type="hidden" name="returnNewPageId" value="1">';
		}
		$formContent.='<input type="hidden" name="popViewId" value="'.$this->viewId.'">';
		$formContent.='<input type="hidden" name="closeDoc" value="0">';
		$formContent.='<input type="hidden" name="doSave" value="0">';
		$formContent.='<input type="hidden" name="_serialNumber" value="'.md5(microtime()).'">';
		$formContent.='<input type="hidden" name="_disableRTE" value="'.$this->tceforms->disableRTE.'">';

		return $formContent;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function compileStoreDat()	{
		global $HTTP_GET_VARS;
		$this->storeArray = t3lib_div::compileSelectedGetVarsFromArray("edit,defVals,overrideVals,columnsOnly,disHelp,noView,editRegularContentFromId",$HTTP_GET_VARS);
		$this->storeUrl = t3lib_div::implodeArrayForUrl("",$this->storeArray);
		$this->storeUrlMd5 = md5($this->storeUrl);
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function functionMenus()	{
		global $BE_USER,$LANG;

			// show palettes
		$funcMenus = "";
		$funcMenus.= "<BR><BR>".t3lib_BEfunc::getFuncCheck("","SET[showPalettes]",$this->MOD_SETTINGS["showPalettes"],"alt_doc.php",t3lib_div::implodeArrayForUrl("",array_merge($this->R_URL_getvars,array("SET"=>"")))).$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.showPalettes");			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...

		if ($BE_USER->uc["edit_showFieldHelp"]!="text") $funcMenus.= "<BR>".t3lib_BEfunc::getFuncCheck("","SET[showDescriptions]",$this->MOD_SETTINGS["showDescriptions"],"alt_doc.php",t3lib_div::implodeArrayForUrl("",array_merge($this->R_URL_getvars,array("SET"=>"")))).$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.showDescriptions");			// Candidate for t3lib_div::array_merge() if integer-keys will some day make trouble...
		if ($BE_USER->isRTE())	$funcMenus.= "<BR>".t3lib_BEfunc::getFuncCheck("","SET[disableRTE]",$this->MOD_SETTINGS["disableRTE"],"alt_doc.php",t3lib_div::implodeArrayForUrl("",array_merge($this->R_URL_getvars,array("SET"=>"")))).$LANG->sL("LLL:EXT:lang/locallang_core.php:labels.disableRTE");
		return $funcMenus;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function shortCutLink()	{
		global $SOBE,$BE_USER,$LANG;
			// ShortCut
		if (t3lib_div::GPvar("returnUrl")!="close.html")	{
			$content.="<BR><BR>";
			if ($BE_USER->mayMakeShortcut())	{
				$content.=$this->doc->makeShortcutIcon("returnUrl,edit,defVals,overrideVals,columnsOnly,popViewId,returnNewPageId,editRegularContentFromId,disHelp,noView",implode(",",array_keys($this->MOD_MENU)),$this->MCONF["name"],1);
			}
			$content.='<a href="#" onClick="vHWin=window.open(\''.t3lib_div::linkThisScript(array("returnUrl"=>"close.html")).'\',\''.md5($this->R_URI).'\',\''.($BE_USER->uc["edit_wideDocument"]?"width=670,height=500":"width=600,height=400").',status=0,menubar=0,scrollbars=1,resizable=1\');vHWin.focus();return false;"><img src="gfx/open_in_new_window.gif" width="19" height="14" hspace=4 border="0"'.t3lib_BEfunc::titleAttrib($LANG->sL("LLL:EXT:lang/locallang_core.php:labels.openInNewWindow")).'></a>';
		}
		return $content;
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function tceformMessages()	{
		if (count($this->tceforms->commentMessages))	{
		$this->content.='

<!-- TCEFORM messages
'.implode(chr(10),$this->tceforms->commentMessages).'
-->

';
		}
	}

	








	/***************************
	 *
	 * OTHER FUNCTIONS:	
	 *
	 ***************************/
	 
	/**
	 * @param	[type]		$table: ...
	 * @param	[type]		$key: ...
	 * @return	[type]		...
	 */
	function getNewIconMode($table,$key="saveDocNew")	{
		global $BE_USER;
		$TSconfig = $BE_USER->getTSConfig("options.".$key);
		$output = trim(isset($TSconfig["properties"][$table]) ? $TSconfig["properties"][$table] : $TSconfig["value"]);
		return $output;
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$code: ...
	 * @return	[type]		...
	 */
	function closeDocument($code=0)	{
		global $BE_USER;
		if (isset($this->docHandler[$this->storeUrlMd5]))	{
			unset($this->docHandler[$this->storeUrlMd5]);
			if ($code=="3")	$this->docHandler=array();
			$BE_USER->pushModuleData("alt_doc.php",array($this->docHandler,$this->docDat[1]));
		}
		
		if (t3lib_div::GPvar("returnEditConf") && $this->retUrl!="dummy.php")	{
			$this->retUrl.="&returnEditConf=".rawurlencode(serialize($this->editconf));
		}
	
		if (!$code || $code==1)	{
			Header("Location: ".t3lib_div::locationHeaderUrl($this->retUrl));
			exit;
		} else {
			$this->setDocument("",$this->retUrl);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @param	[type]		$currentDocFromHandlerMD5: ...
	 * @param	[type]		$retUrl: ...
	 * @return	[type]		...
	 */
	function setDocument($currentDocFromHandlerMD5="",$retUrl="alt_doc_nodoc.php")	{
		if (!t3lib_extMgm::isLoaded("cms") && !strcmp($retUrl,"alt_doc_nodoc.php"))	return;
		
		if (!$this->modTSconfig["properties"]["disableDocSelector"] && is_array($this->docHandler) && count($this->docHandler))	{
			if (isset($this->docHandler[$currentDocFromHandlerMD5]))	{
				$setupArr=$this->docHandler[$currentDocFromHandlerMD5];
			} else {
				reset($this->docHandler);
				$setupArr=current($this->docHandler);
			}
			if ($setupArr[2])	{
				$sParts = parse_url(t3lib_div::getIndpEnv("REQUEST_URI"));
				$retUrl = $sParts["path"].'?'.$setupArr[2]."&returnUrl=".rawurlencode($retUrl);
			}
		}
		Header("Location: ".t3lib_div::locationHeaderUrl($retUrl));
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
if ($SOBE->doProcessData())	{
	require_once (PATH_t3lib.'class.t3lib_tcemain.php');
	$SOBE->processData();
} else {
	require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');
	$BACK_PATH='';
}
require_once (PATH_t3lib.'class.t3lib_transferdata.php');


// Main:
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>