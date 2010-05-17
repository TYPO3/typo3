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
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */

require_once(PATH_t3lib."class.t3lib_extobjbase.php");

class tx_tstemplateobjbrowser extends t3lib_extobjbase {
	function modMenu()	{
		global $LANG;

		$modMenu = array (
			"ts_browser_type" => array(
				"setup" => "Setup",
				"const" => "Constants"
			),
			"ts_browser_toplevel_setup" => array(
				"0" => "ALL"
			),
			"ts_browser_toplevel_const" => array(
				"0" => "ALL"
			),
			"ts_browser_const" => array(
				"0" => "Plain substitution (default)",
				"subst" => "Substituted constants in green",
				"const" => "UN-substituted constants in green"
			),
			"ts_browser_regexsearch" => "",
			"ts_browser_fixedLgd" => "1",
			"ts_browser_linkObjects" => "1",
			'ts_browser_alphaSort' => '1',
		);

		foreach(array('setup','const') as $bType)	{
			$addKey = t3lib_div::_GET('addKey');
			if (is_array($addKey))	{		// If any plus-signs were clicked, it's registred.
				reset($addKey);
				if (current($addKey))	{
					$this->pObj->MOD_SETTINGS['ts_browser_TLKeys_'.$bType][key($addKey)] = key($addKey);
				} else {
					unset($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_'.$bType][key($addKey)]);
				}
				$GLOBALS['BE_USER']->pushModuleData($this->pObj->MCONF['name'],$this->pObj->MOD_SETTINGS);
			}

			if (count($this->pObj->MOD_SETTINGS['ts_browser_TLKeys_'.$bType]))	{
				$modMenu['ts_browser_toplevel_'.$bType]['-']='---';
				$modMenu['ts_browser_toplevel_'.$bType] = $modMenu['ts_browser_toplevel_'.$bType] + $this->pObj->MOD_SETTINGS['ts_browser_TLKeys_'.$bType];
			}
		}

		return $modMenu;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$propertyArray: ...
	 * @param	[type]		$parentType: ...
	 * @param	[type]		$parentValue: ...
	 * @return	[type]		...
	 */
	function verify_TSobjects($propertyArray,$parentType,$parentValue)	{
		$TSobjTable = array(
			"PAGE" => array(
				"prop" => array (
					"typeNum" => "int",
					"1,2,3" => "COBJ",
					"bodyTag" => "string"
				)
			),
			"TEXT" => array(
				"prop" => array (
					"value" => "string"
				)
			),
			"HTML" => array(
				"prop" => array (
					"value" => "stdWrap"
				)
			),
			"stdWrap" => array(
				"prop" => array (
					"field" => "string",
					"current" => "boolean"
				)
			),
		);
		$TSobjDataTypes = array(
			"COBJ" => "TEXT,CONTENT",
			"PAGE" => "PAGE",
			"stdWrap" => ""
		);

		if ($parentType)	{
			if (isset($TSobjDataTypes[$parentType]) && (!$TSobjDataTypes[$parentType] || t3lib_div::inlist($TSobjDataTypes[$parentType],$parentValue)))	{
				$ObjectKind = $parentValue;
			} else {
				$ObjectKind = ""; 	// Object kind is "" if it should be known.
			}
		} else {
			$ObjectKind = $parentValue;	// If parentType is not given, then it can be anything. Free.
		}

		if ($ObjectKind && is_array($TSobjTable[$ObjectKind]))	{
			$result=array();
			if (is_array($propertyArray))		{
				reset($propertyArray);
				while(list($key,$val)=each($propertyArray))	{
					if (t3lib_div::testInt($key))	{	// If num-arrays
						$result[$key]=$TSobjTable[$ObjectKind]["prop"]["1,2,3"];
					} else {	// standard
						$result[$key]=$TSobjTable[$ObjectKind]["prop"][$key];
					}
				}
			}
			return $result;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$pageId: ...
	 * @param	[type]		$template_uid: ...
	 * @return	[type]		...
	 */
	function initialize_editor($pageId,$template_uid=0)	{
			// Initializes the module. Done in this function because we may need to re-initialize if data is submitted!
		global $tmpl,$tplRow,$theConstants;

		$tmpl = t3lib_div::makeInstance("t3lib_tsparser_ext");	// Defined global here!
		$tmpl->tt_track = 0;	// Do not log time-performance information
		$tmpl->init();

				// Gets the rootLine
		$sys_page = t3lib_div::makeInstance("t3lib_pageSelect");
		$rootLine = $sys_page->getRootLine($pageId);
		$tmpl->runThroughTemplates($rootLine,$template_uid);	// This generates the constants/config + hierarchy info for the template.

		$tplRow = $tmpl->ext_getFirstTemplate($pageId,$template_uid);	// Get the row of the first VISIBLE template of the page. whereclause like the frontend.
		if (is_array($tplRow))	{	// IF there was a template...
			return 1;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function main()	{
		global $SOBE,$BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;
		global $tmpl,$tplRow,$theConstants;

		$POST = t3lib_div::_POST();

		// **************************
		// Checking for more than one template an if, set a menu...
		// **************************
		$manyTemplatesMenu = $this->pObj->templateMenu();
		$template_uid = 0;
		if ($manyTemplatesMenu)	{
			$template_uid = $this->pObj->MOD_SETTINGS["templatesOnPage"];
		}





		// **************************
		// Main
		// **************************

		// BUGBUG: Should we check if the uset may at all read and write template-records???
		$bType= $this->pObj->MOD_SETTINGS["ts_browser_type"];
		$existTemplate = $this->initialize_editor($this->pObj->id,$template_uid);		// initialize
		if ($existTemplate)	{
			$theOutput.=$this->pObj->doc->divider(5);
			$theOutput.=$this->pObj->doc->section("Current template:",'<img src="'.$BACK_PATH.t3lib_iconWorks::getIcon("sys_template",$tplRow).'" width=18 height=16 align=top><b>'.$this->pObj->linkWrapTemplateTitle($tplRow["title"], ($bType=="setup"?"config":"constants")).'</b>'.htmlspecialchars(trim($tplRow["sitetitle"])?' - ('.$tplRow["sitetitle"].')':''),0,0);
			if ($manyTemplatesMenu)	{
				$theOutput.=$this->pObj->doc->section("",$manyTemplatesMenu);
				$theOutput.=$this->pObj->doc->divider(5);
			}

			if ($POST["add_property"] || $POST["update_value"] || $POST["clear_object"])	{
					// add property
				$line="";
		//		debug($POST);
				if (is_array($POST["data"]))	{
					$name = key($POST["data"]);
					if ($POST['data'][$name]['name']!=='') {
							// Workaround for this special case: User adds a key and submits by pressing the return key. The form however will use "add_property" which is the name of the first submit button in this form.
						unset($POST['update_value']);
						$POST['add_property'] = 'Add';
					}
					if ($POST["add_property"])	{
						$property = trim($POST["data"][$name]["name"]);
						if (ereg_replace("[^a-zA-Z0-9_\.]*","",$property)!=$property)	{
							$theOutput.=$this->pObj->doc->spacer(10);
							$theOutput.=$this->pObj->doc->section($GLOBALS["TBE_TEMPLATE"]->rfw("BAD PROPERTY!"),'You must enter a property with characters a-z, A-Z and 0-9, no spaces!<BR>Nothing was updated!',0,0,0,1);
						} else {
							$pline= $name.".".$property." = ".trim($POST["data"][$name]["propertyValue"]);
							$theOutput.=$this->pObj->doc->spacer(10);
							$theOutput.=$this->pObj->doc->section($GLOBALS["TBE_TEMPLATE"]->rfw("PROPERTY ADDED"),htmlspecialchars($pline),0,0,0,1);
							$line.=chr(10).$pline;
						}
					}
					elseif ($POST['update_value']) {
						$pline= $name." = ".trim($POST["data"][$name]["value"]);
						$theOutput.=$this->pObj->doc->spacer(10);
						$theOutput.=$this->pObj->doc->section($GLOBALS["TBE_TEMPLATE"]->rfw("VALUE UPDATED"),htmlspecialchars($pline),0,0,0,1);
						$line.=chr(10).$pline;
					}
					elseif ($POST['clear_object']) {
						if ($POST["data"][$name]["clearValue"])	{
							$pline= $name." >";
							$theOutput.=$this->pObj->doc->spacer(10);
							$theOutput.=$this->pObj->doc->section($GLOBALS["TBE_TEMPLATE"]->rfw("Object cleared"),htmlspecialchars($pline),0,0,0,1);
							$line.=chr(10).$pline;
						}
					}
				}
				if ($line)	{
					require_once (PATH_t3lib."class.t3lib_tcemain.php");
					$saveId = $tplRow['_ORIG_uid'] ? $tplRow['_ORIG_uid'] : $tplRow['uid'];
						// Set the data to be saved
					$recData=array();
					$field =$bType=="setup"?"config":"constants";
					$recData["sys_template"][$saveId][$field] = $tplRow[$field].$line;
						// Create new  tce-object
					$tce = t3lib_div::makeInstance("t3lib_TCEmain");
					$tce->stripslashes_values=0;
						// Initialize
					$tce->start($recData,Array());
						// Saved the stuff
					$tce->process_datamap();
						// Clear the cache (note: currently only admin-users can clear the cache in tce_main.php)
					$tce->clear_cacheCmd("all");

						// re-read the template ...
					$this->initialize_editor($this->pObj->id,$template_uid);
				}
			}
		}

		$tsbr = t3lib_div::_GET('tsbr');
		$update=0;
		if (is_array($tsbr))	{		// If any plus-signs were clicked, it's registred.
			$this->pObj->MOD_SETTINGS["tsbrowser_depthKeys_".$bType] = $tmpl->ext_depthKeys($tsbr, $this->pObj->MOD_SETTINGS["tsbrowser_depthKeys_".$bType]);
			$update=1;
		}

		if ($POST["Submit"])	{		// If any POST-vars are send, update the condition array
			$this->pObj->MOD_SETTINGS["tsbrowser_conditions"] = $POST["conditions"];
			$update=1;
		}
		if ($update)	{ $GLOBALS["BE_USER"]->pushModuleData($this->pObj->MCONF["name"],$this->pObj->MOD_SETTINGS); }


		$tmpl->matchAlternative = $this->pObj->MOD_SETTINGS['tsbrowser_conditions'];
		$tmpl->matchAlternative[] = 'dummydummydummydummydummydummydummydummydummydummydummy';	// This is just here to make sure that at least one element is in the array so that the tsparser actually uses this array to match.

		$tmpl->constantMode = $this->pObj->MOD_SETTINGS["ts_browser_fixedLgd"] ? "" : $this->pObj->MOD_SETTINGS["ts_browser_const"];
		if ($this->pObj->sObj && $tmpl->constantMode)	{$tmpl->constantMode = "untouched";}

		$tmpl->regexMode = $this->pObj->MOD_SETTINGS["ts_browser_regexsearch"];
		$tmpl->fixedLgd=$this->pObj->MOD_SETTINGS["ts_browser_fixedLgd"];
#		$tmpl->linkObjects=$this->pObj->MOD_SETTINGS["ts_browser_linkObjects"];
		$tmpl->linkObjects = TRUE;
		$tmpl->ext_regLinenumbers = TRUE;
		$tmpl->bType=$bType;
		$tmpl->resourceCheck=1;
		$tmpl->uplPath = PATH_site.$tmpl->uplPath;
		$tmpl->removeFromGetFilePath = PATH_site;
		//debug($tmpl->uplPath);

		if ($this->pObj->MOD_SETTINGS["ts_browser_type"]=="const")	{
			$tmpl->ext_constants_BRP=intval(t3lib_div::_GP("breakPointLN"));
		} else {
			$tmpl->ext_config_BRP=intval(t3lib_div::_GP("breakPointLN"));
		}
		$tmpl->generateConfig();

		if ($bType=="setup")	{
			$theSetup = $tmpl->setup;
		} else {
			$theSetup = $tmpl->setup_constants;
		}

			// EDIT A VALUE:
		if ($this->pObj->sObj)	{
			list($theSetup,$theSetupValue) = $tmpl->ext_getSetup($theSetup, ($this->pObj->sObj?$this->pObj->sObj:""));
			$theOutput.=$this->pObj->doc->divider(5);
			if ($existTemplate)	{
					// Value
				$out = '';
				$out.= htmlspecialchars($this->pObj->sObj).' =<br />';
				$out.='<input type="Text" name="data['.htmlspecialchars($this->pObj->sObj).'][value]" value="'.htmlspecialchars($theSetupValue).'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(40).'>';
				$out.='<input type="Submit" name="update_value" value="Update">';
				$theOutput.=$this->pObj->doc->section("Edit object/property value:",$out,0,0);

					// Property
				if (t3lib_extMgm::isLoaded("tsconfig_help"))	{
					$url=$BACK_PATH."wizard_tsconfig.php?mode=tsref&onlyProperty=1";
					$params=array();
					$params["formName"]="editForm";
					$params["itemName"]="data[".htmlspecialchars($this->pObj->sObj)."][name]";
					$params["itemValue"]="data[".htmlspecialchars($this->pObj->sObj)."][propertyValue]";
					$TSicon='<a href="#" onClick="vHWin=window.open(\''.$url.t3lib_div::implodeArrayForUrl("",array("P"=>$params)).'\',\'popUp'.$md5ID.'\',\'height=500,width=780,status=0,menubar=0,scrollbars=1\');vHWin.focus();return false;"><img src="'.$BACK_PATH.'gfx/wizard_tsconfig_s.gif" width="22" height="16" border="0" class="absmiddle" hspace=2 title="TSref reference"></a>';
				} else $TSicon="";
				$out="";
				$out="<nobr>".htmlspecialchars($this->pObj->sObj).".";
				$out.='<input type="Text" name="data['.htmlspecialchars($this->pObj->sObj).'][name]"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).'>'.$TSicon.' = </nobr><BR>';
				$out.='<input type="Text" name="data['.htmlspecialchars($this->pObj->sObj).'][propertyValue]"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(40).'>';
				$out.='<input type="Submit" name="add_property" value="Add">';



				$theOutput.=$this->pObj->doc->spacer(20);
				$theOutput.=$this->pObj->doc->section("Add object property:",$out,0,0);

					// clear
				$out="";
				$out=htmlspecialchars($this->pObj->sObj)." <b>CLEAR?</b> &nbsp;&nbsp;";
				$out.='<input type="Checkbox" name="data['.htmlspecialchars($this->pObj->sObj).'][clearValue]" value="1">';
				$out.='<input type="Submit" name="clear_object" value="Clear">';
				$theOutput.=$this->pObj->doc->spacer(20);
				$theOutput.=$this->pObj->doc->section("Clear object:",$out,0,0);

				$theOutput.=$this->pObj->doc->spacer(10);
			} else {
				$theOutput.=$this->pObj->doc->section("EDIT:",$GLOBALS["TBE_TEMPLATE"]->rfw("You cannot edit properties and values, if there's no current template."),0,0,0,1);
			}
				// Links:
			$out='';
			if (!$this->pObj->MOD_SETTINGS['ts_browser_TLKeys_'.$bType][$this->pObj->sObj])	{
				if (count($theSetup))	{
					$out = '<a href="index.php?id='.$this->pObj->id.'&addKey['.rawurlencode($this->pObj->sObj).']=1&SET[ts_browser_toplevel_'.$bType.']='.rawurlencode($this->pObj->sObj).'"><b>Add key</b></a> "'.htmlspecialchars($this->pObj->sObj).'" to Object List (OL)';
				}
			} else {
				$out = '<a href="index.php?id='.$this->pObj->id.'&addKey['.rawurlencode($this->pObj->sObj).']=0&SET[ts_browser_toplevel_'.$bType.']=0"><b>Remove key</b></a> "'.htmlspecialchars($this->pObj->sObj).'" from Object List (OL)';
			}
			if ($out)	{
				$theOutput.=$this->pObj->doc->divider(5);
				$theOutput.=$this->pObj->doc->section("",$out);
			}

				// back
			$out = "< Back";
			$out = '<a href="index.php?id='.$this->pObj->id.'"><b>'.$out.'</b></a>';
			$theOutput.=$this->pObj->doc->divider(5);
			$theOutput.=$this->pObj->doc->section("",$out);

		} else {
			$tmpl->tsbrowser_depthKeys=$this->pObj->MOD_SETTINGS["tsbrowser_depthKeys_".$bType];
		//	debug($tmpl->tsbrowser_depthKeys);

			if (t3lib_div::_POST('search') && t3lib_div::_POST('search_field'))	{		// If any POST-vars are send, update the condition array
				$tmpl->tsbrowser_depthKeys = $tmpl->ext_getSearchKeys($theSetup, '', t3lib_div::_POST('search_field'), array());
		//		debug($tmpl->tsbrowser_depthKeys);
		//		debug($tmpl->tsbrowser_searchKeys);
			}



			// Expanding menu
		/*	if (is_array($theSetup))	{
				reset($theSetup);
				while(list($tkey,$tval)=each($theSetup))	{
					if (substr($tkey,-1)==".")	{
						$tkey=substr($tkey,0,-1);
					}
					if ($theSetup[$tkey."."] && $tkey!="types" && $tkey!="TSConstantEditor")	{
						$this->pObj->MOD_MENU["ts_browser_toplevel_".$bType][$tkey]=$tkey;
					}
				}
			}*/

			$theOutput.=$this->pObj->doc->spacer(5);
			$theOutput.=$this->pObj->doc->section('Object tree:','',0,1);

			$menu = 'Browse: '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[ts_browser_type]',$bType,$this->pObj->MOD_MENU['ts_browser_type']);
			$menu.= '&nbsp;&nbsp;OL: '.t3lib_BEfunc::getFuncMenu($this->pObj->id,'SET[ts_browser_toplevel_'.$bType.']',$this->pObj->MOD_SETTINGS['ts_browser_toplevel_'.$bType],$this->pObj->MOD_MENU['ts_browser_toplevel_'.$bType]);
			$theOutput.=$this->pObj->doc->section('','<NOBR>'.$menu.'</NOBR>');


			$theKey=$this->pObj->MOD_SETTINGS["ts_browser_toplevel_".$bType];
			if (!$theKey || !str_replace("-","",$theKey))	{$theKey="";}
			list($theSetup,$theSetupValue) = $tmpl->ext_getSetup($theSetup, ($this->pObj->MOD_SETTINGS['ts_browser_toplevel_'.$bType]?$this->pObj->MOD_SETTINGS['ts_browser_toplevel_'.$bType]:''));
			$tree = $tmpl->ext_getObjTree($theSetup, $theKey, '', '', $theSetupValue, $this->pObj->MOD_SETTINGS['ts_browser_alphaSort']);
			$tree = $tmpl->substituteCMarkers($tree);



				// Parser Errors:
			$pEkey = ($bType=="setup"?"config":"constants");
			if (count($tmpl->parserErrors[$pEkey]))	{
				reset($tmpl->parserErrors[$pEkey]);
				$errMsg=array();
				while(list(,$inf)=each($tmpl->parserErrors[$pEkey]))	{
					$errMsg[]=($inf[1]).": &nbsp; &nbsp;".$inf[0];
				}
				$theOutput.=$this->pObj->doc->spacer(10);
				$theOutput.=$this->pObj->doc->section($GLOBALS["TBE_TEMPLATE"]->rfw("Errors and warnings"),implode($errMsg,"<br>"),0,1,0,1);
			}



			if (isset($this->pObj->MOD_SETTINGS["ts_browser_TLKeys_".$bType][$theKey]))	{
				$remove='<td width="1%" nowrap><a href="index.php?id='.$this->pObj->id.'&addKey['.$theKey.']=0&SET[ts_browser_toplevel_'.$bType.']=0"><b>Remove key from OL</b></a></td>';
			} else {
				$remove='';
			}
			$label = $theKey ? $theKey : ($bType=="setup"?"SETUP ROOT":"CONSTANTS ROOT");
			$theOutput.=$this->pObj->doc->spacer(15);
			$theOutput.=$this->pObj->doc->sectionEnd();
			$theOutput.='<table border=0 cellpadding=1 cellspacing=0 id="typo3-objectBrowser">
					<tr>
						<td><img src=clear.gif width=4 height=1></td>
						<td class="bgColor2">
							<table border=0 cellpadding=0 cellspacing=0 class="bgColor5" width="100%"><tr><td nowrap width="99%"><b>'.$label.'</b></td>'.$remove.'</tr></table>
						</td>
					</tr>
					<tr>
						<td><img src=clear.gif width=4 height=1></td>
						<td class="bgColor2">
							<table border=0 cellpadding=0 cellspacing=0 class="bgColor4" width="100%"><tr><td nowrap>'.$tree.'</td></tr></table><img src=clear.gif width=465 height=1></td>
					</tr>
				</table>
			';


				// Conditions:
			if (is_array($tmpl->sections))	{
				$theOutput.=$this->pObj->doc->divider(15);

				$out="";
				reset($tmpl->sections);
				while(list($key,$val)=each($tmpl->sections))	{
					$out.='<tr><td><input type="Checkbox" name="conditions['.$key.']" id="check'.$key.'" value="'.htmlspecialchars($val).'"'.($this->pObj->MOD_SETTINGS["tsbrowser_conditions"][$key]?" checked":"").'></td><td nowrap><label for="check'.$key.'">'.$tmpl->substituteCMarkers(htmlspecialchars($val)).'</label>&nbsp;&nbsp;</td></tr>';
				}
				$theOutput.='
					<table border=0 cellpadding=1 cellspacing=0>
						<tr>
							<td><img src=clear.gif width=4 height=1></td>
							<td class="bgColor2">
								<table border=0 cellpadding=0 cellspacing=0 class="bgColor4">'.$out.'
								<tr>
									<td>&nbsp;</td>
									<td><img src=clear.gif height=10 width=240><BR><input type="Submit" name="Submit" value="Set conditions"><BR></td>
								</tr>
								</table>
							</td>
						</tr>
					</table>

				';
			}

				// Search:
			$theOutput.='<br>

				<table border=0 cellpadding=1 cellspacing=0>
					<tr>
						<td><img src=clear.gif width=4 height=1></td>
						<td class="bgColor2">
							<table border=0 cellpadding=0 cellspacing=0 class="bgColor4">
							<tr>
								<td>&nbsp;Enter search phrase:&nbsp;&nbsp;<input type="Text" name="search_field" value="'.htmlspecialchars($POST["search_field"]).'"'.$GLOBALS["TBE_TEMPLATE"]->formWidth(20).'></td>
								<td><input type="Submit" name="search" value="Search"></td>
							</tr>
							<tr>
								<td>&nbsp;<label for="checkTs_browser_regexsearch">Use ereg(), not stristr():</label>&nbsp;&nbsp;'.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_browser_regexsearch]",$this->pObj->MOD_SETTINGS["ts_browser_regexsearch"],'','','id="checkTs_browser_regexsearch"').'</td>
								<td>&nbsp;</td>
							</tr>
							</table>
						</td>
					</tr>
				</table>
			<br>
			';

				// Menu in the bottom:
			$menu = '<label for="checkTs_browser_fixedLgd">Crop lines:</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_browser_fixedLgd]",$this->pObj->MOD_SETTINGS["ts_browser_fixedLgd"],'','','id="checkTs_browser_fixedLgd"');
			#$menu.= "&nbsp;&nbsp;Enable object links".t3lib_BEfunc::getFuncCheck($this->pObj->id,"SET[ts_browser_linkObjects]",$this->pObj->MOD_SETTINGS["ts_browser_linkObjects"]);
			$menu .= '<br /><label for="checkTs_browser_alphaSort">Sort alphabetically:</label> '.t3lib_BEfunc::getFuncCheck($this->pObj->id,'SET[ts_browser_alphaSort]',$this->pObj->MOD_SETTINGS['ts_browser_alphaSort'],'','','id="checkTs_browser_alphaSort"');
			if ($bType=="setup" && !$this->pObj->MOD_SETTINGS["ts_browser_fixedLgd"])	{
				$menu.= "<br />Constants display: ".t3lib_BEfunc::getFuncMenu($this->pObj->id,"SET[ts_browser_const]",$this->pObj->MOD_SETTINGS["ts_browser_const"],$this->pObj->MOD_MENU["ts_browser_const"]);
			}
			$theOutput.=$this->pObj->doc->section("",'<NOBR>'.$menu.'</NOBR>');

			$theOutput.=$this->pObj->doc->spacer(10);
			$theOutput.=$this->pObj->doc->section("Cache",'Click here to <a href="index.php?id='.$this->pObj->id.'&clear_all_cache=1"><strong>clear all cache</strong></a>',0,1);

				// Ending section:
			$theOutput.=$this->pObj->doc->sectionEnd();
		}
		return $theOutput;
	}
}

if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_objbrowser/class.tx_tstemplateobjbrowser.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["ext/tstemplate_objbrowser/class.tx_tstemplateobjbrowser.php"]);
}

?>
