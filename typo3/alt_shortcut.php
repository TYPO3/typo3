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
 * Shortcut frame
 * Appears in the bottom frame of the backend frameset.
 * Provides links to registered shortcuts
 * If the 'cms' extension is loaded you will also have a field for entering page id/alias which will be found/edited
 *
 * $Id$
 * Revised for TYPO3 3.6 2/2003 by Kasper Skårhøj
 * XHTML compliant output
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   86: class SC_alt_shortcut
 *  125:     function preinit()
 *  152:     function preprocess()
 *  234:     function init()
 *  275:     function main()
 *  452:     function editLoadedFunc()
 *  532:     function editPageIdFunc()
 *  586:     function printContent()
 *
 *              SECTION: WORKSPACE FUNCTIONS:
 *  611:     function workspaceSelector()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  686:     function mIconFilename($Ifilename,$backPath)
 *  702:     function getIcon($modName)
 *  726:     function itemLabel($inlabel,$modName,$M_modName='')
 *  748:     function getLinkedPageId($url)
 *
 * TOTAL FUNCTIONS: 12
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


require('init.php');
require('template.php');
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');






/**
 * Script Class for the shortcut frame, bottom frame of the backend frameset
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_alt_shortcut {

		// Internal, static: GPvar
	var $modName;
	var $M_modName;
	var $URL;
	var $editSC;
	var $deleteCategory;
	var $editName;
	var $editGroup;
	var $whichItem;

		// Internal, static:
	/**
	 * Object for backend modules, load modules-object
	 *
	 * @var t3lib_loadModules
	 */
	var $loadModules;
	protected $isAjaxCall;

	/**
	 * Document template object
	 *
	 * @var template
	 */
	var $doc;

		// Internal, dynamic:
	var $content;			// Accumulation of output HTML (string)
	var $lines;				// Accumulation of table cells (array)

	var $editLoaded;		// Flag for defining whether we are editing
	var $editError;			// Can contain edit error message
	var $editPath;			// Set to the record path of the record being edited.
	var $editSC_rec;		// Holds the shortcut record when editing
	var $theEditRec;		// Page record to be edited
	var $editPage;			// Page alias or id to be edited
	var $selOpt;			// Select options.
	var $searchFor;			// Text to search for...
	var $groupLabels=array();	// Labels of all groups. If value is 1, the system will try to find a label in the locallang array.

	var $alternativeTableUid = array();	// Array with key 0/1 being table/uid of record to edit. Internally set.



	/**
	 * Pre-initialization - setting input variables for storing shortcuts etc.
	 *
	 * @return	void
	 */
	function preinit()	{
		global $TBE_MODULES;

			// Setting GPvars:
		$this->isAjaxCall             = (boolean) t3lib_div::_GP('ajax');
		$this->modName                = t3lib_div::_GP('modName');
		$this->M_modName              = t3lib_div::_GP('motherModName');
		$this->URL                    = t3lib_div::_GP('URL');
		$this->editSC                 = t3lib_div::_GP('editShortcut');

		$this->deleteCategory         = t3lib_div::_GP('deleteCategory');
		$this->editPage               = t3lib_div::_GP('editPage');
		$this->changeWorkspace        = t3lib_div::_GP('changeWorkspace');
		$this->changeWorkspacePreview = t3lib_div::_GP('changeWorkspacePreview');
		$this->editName               = t3lib_div::_GP('editName');
		$this->editGroup              = t3lib_div::_GP('editGroup');
		$this->whichItem              = t3lib_div::_GP('whichItem');

			// Creating modules object
		$this->loadModules = t3lib_div::makeInstance('t3lib_loadModules');
		$this->loadModules->load($TBE_MODULES);
	}

	/**
	 * Adding shortcuts, editing shortcuts etc.
	 *
	 * @return	void
	 */
	function preprocess()	{
		global $BE_USER;
		$description = '';	// Default description
		$url = urldecode($this->URL);
		$queryParts = parse_url($url);

			// Lookup the title of this page and use it as default description
		$page_id = $this->getLinkedPageId($url);
		if (t3lib_div::testInt($page_id))	{
			if (preg_match('/\&edit\[(.*)\]\[(.*)\]=edit/',$url,$matches))	{
					// Edit record
				$description = '';	// TODO: Set something useful
			} else {
					// Page listing
				$pageRow = t3lib_BEfunc::getRecord('pages',$page_id);
				if (count($pageRow))	{
						// If $page_id is an integer, set the description to the title of that page
					$description = $pageRow['title'];
				}
			}
		} else {
			if (preg_match('/\/$/', $page_id))	{
					// If $page_id is a string and ends with a slash, assume it is a fileadmin reference and set the description to the basename of that path
				$description = basename($page_id);
			}
		}


			// Adding a shortcut being set from another frame,
			// but only if it's a relative URL (i.e. scheme part is not defined)
		if ($this->modName && $this->URL && empty($queryParts['scheme'])) {
			$fields_values = array(
				'userid' => $BE_USER->user['uid'],
				'module_name' => $this->modName.'|'.$this->M_modName,
				'url' => $this->URL,
				'description' => $description,
				'sorting' => $GLOBALS['EXEC_TIME'],
			);
			$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_be_shortcuts', $fields_values);
		}

			// Selection-clause for users - so users can deleted only their own shortcuts (except admins)
		$addUSERWhere = (!$BE_USER->isAdmin()?' AND userid='.intval($BE_USER->user['uid']):'');

			// Deleting shortcuts:
		if (strcmp($this->deleteCategory,''))	{
			if (t3lib_div::testInt($this->deleteCategory))	{
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'sc_group='.intval($this->deleteCategory).$addUSERWhere);
			}
		}

			// If other changes in post-vars:
		if (is_array($_POST))	{
				// Saving:
			if (isset($_POST['_savedok_x']) || isset($_POST['_saveclosedok_x']))	{
				$fields_values = array(
					'description' => $this->editName,
					'sc_group' => intval($this->editGroup)
				);
				if ($fields_values['sc_group']<0 && !$BE_USER->isAdmin())	{
					$fields_values['sc_group']=0;
				}

				$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_be_shortcuts', 'uid='.intval($this->whichItem).$addUSERWhere, $fields_values);
			}
				// If save without close, keep the session going...
			if (isset($_POST['_savedok_x']))	{
				$this->editSC=$this->whichItem;
			}
				// Deleting a single shortcut ?
			if (isset($_POST['_deletedok_x']))	{
				$GLOBALS['TYPO3_DB']->exec_DELETEquery('sys_be_shortcuts', 'uid='.intval($this->whichItem).$addUSERWhere);

				if (!$this->editSC)	$this->editSC=-1;	// Just to have the checkbox set...
			}
		}

	}

	/**
	 * Initialize (page output)
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH;

		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->form='<form action="alt_shortcut.php" name="shForm" method="post">';
		$this->doc->divClass='typo3-shortcut';
		$this->doc->JScode.=$this->doc->wrapScriptTags('
			function jump(url,modName,mainModName)	{	//
					// Clear information about which entry in nav. tree that might have been highlighted.
				top.fsMod.navFrameHighlightedID = new Array();
				if (top.content && top.content.nav_frame && top.content.nav_frame.refresh_nav)	{
					top.content.nav_frame.refresh_nav();
				}

				top.nextLoadModuleUrl = url;
				top.goToModule(modName);
			}
			function editSh(uid)	{	//
				window.location.href="alt_shortcut.php?editShortcut="+uid;
			}
			function submitEditPage(id)	{	//
				window.location.href="alt_shortcut.php?editPage="+top.rawurlencodeAndRemoveSiteUrl(id);
			}
			function changeWorkspace(workspaceId)	{	//
				window.location.href="alt_shortcut.php?changeWorkspace="+top.rawurlencodeAndRemoveSiteUrl(workspaceId);
			}
			function changeWorkspacePreview(newstate)	{	//
				window.location.href="alt_shortcut.php?changeWorkspacePreview="+newstate;
			}
			function refreshShortcuts() {
				window.location.href = document.URL;
			}

			');
		$this->content.=$this->doc->startPage('Shortcut frame');
	}

	/**
	 * Main function, creating content in the frame
	 *
	 * @return	void
	 */
	function main()	{
		global $BE_USER,$LANG,$TCA;

			// By default, 5 groups are set
		$this->groupLabels=array(
			1 => 1,
			2 => 1,
			3 => 1,
			4 => 1,
			5 => 1,
		);

			// "Shortcuts" have been renamed to "Bookmarks"
			// @deprecated remove shortcuts code in TYPO3 4.7
		$shortCutGroups = $BE_USER->getTSConfigProp('options.shortcutGroups');
		if ($shortCutGroups !== NULL) {
			t3lib_div::deprecationLog('options.shortcutGroups - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.bookmarkGroups instead');
		}
		$bookmarkGroups = $BE_USER->getTSConfigProp('options.bookmarkGroups');
		if ($bookmarkGroups !== NULL) {
			$shortCutGroups = $bookmarkGroups;
		}
		if (is_array($shortCutGroups) && count($shortCutGroups)) {
			foreach ($shortCutGroups as $k=>$v)	{
				if (strcmp('',$v) && strcmp('0',$v))	{
					$this->groupLabels[$k] = (string)$v;
				} elseif ($BE_USER->isAdmin())	{
					unset($this->groupLabels[$k]);
				}
			}
		}

			// List of global groups that will be loaded. All global groups have negative IDs.
		$globalGroups = -100;	// Group -100 is kind of superglobal and can't be changed.
		if (count($this->groupLabels))	{
			$globalGroups .= ','.implode(',',array_keys($this->groupLabels));
			$globalGroups = str_replace(',',',-',$globalGroups);	// Ugly hack to make the UIDs negative - is there any better solution?
		}

			// Fetching shortcuts to display for this user:
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('*', 'sys_be_shortcuts', '((userid='.$BE_USER->user['uid'].' AND sc_group>=0) OR sc_group IN ('.$globalGroups.'))', '', 'sc_group,sorting');

			// Init vars:
		$this->lines=array();
		$this->linesPre=array();
		$this->editSC_rec='';
		$this->selOpt=array();
		$formerGr='';

			// Traverse shortcuts
		while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
			$mParts = explode('|',$row['module_name']);
			$row['module_name']=$mParts[0];
			$row['M_module_name']=$mParts[1];
			$mParts = explode('_',$row['M_module_name']?$row['M_module_name']:$row['module_name']);
			$qParts = parse_url($row['url']);

			if (!$BE_USER->isAdmin())	{
					// Check for module access
				if (!isset($LANG->moduleLabels['tabs_images'][implode('_',$mParts).'_tab']))	{	// Nice hack to check if the user has access to this module - otherwise the translation label would not have been loaded :-)
					continue;
				}

				$page_id = $this->getLinkedPageId($row['url']);
				if (t3lib_div::testInt($page_id))	{
						// Check for webmount access
					if (!$GLOBALS['BE_USER']->isInWebMount($page_id)) continue;

						// Check for record access
					$pageRow = t3lib_BEfunc::getRecord('pages',$page_id);
					if (!$GLOBALS['BE_USER']->doesUserHaveAccess($pageRow,$perms=1)) continue;
				}
			}

			if ($this->editSC && $row['uid']==$this->editSC)	{
				$this->editSC_rec=$row;
			}

			$sc_group = $row['sc_group'];
			if ($sc_group && strcmp($formerGr,$sc_group))	{
				if ($sc_group!=-100)	{
					if ($this->groupLabels[abs($sc_group)] && strcmp('1',$this->groupLabels[abs($sc_group)]))	{
						$label = $this->groupLabels[abs($sc_group)];
					} else {
						$label = $LANG->getLL('shortcut_group_'.abs($sc_group),1);
						if (!$label)	$label = $LANG->getLL('shortcut_group',1).' '.abs($sc_group);	// Fallback label
					}

					if ($sc_group>=0)	{
						$onC = 'if (confirm('.$GLOBALS['LANG']->JScharCode($LANG->getLL('bookmark_delAllInCat')).')){window.location.href=\'alt_shortcut.php?deleteCategory='.$sc_group.'\';}return false;';
						$this->linesPre[]='<td>&nbsp;</td><td class="bgColor5"><a href="#" onclick="'.htmlspecialchars($onC).'" title="'.$LANG->getLL('bookmark_delAllInCat',1).'">'.$label.'</a></td>';
					} else {
						$label = $LANG->getLL('bookmark_global',1).': '.($label ? $label : abs($sc_group));	// Fallback label
						$this->lines[]='<td>&nbsp;</td><td class="bgColor5">'.$label.'</td>';
					}
					unset($label);
				}
			}

			$bgColorClass = $row['uid']==$this->editSC ? 'bgColor5' : ($row['sc_group']<0 ? 'bgColor6' : 'bgColor4');

			if ($row['description']&&($row['uid']!=$this->editSC))	{
				$label = $row['description'];
			} else {
				$label = t3lib_div::fixed_lgd_cs(rawurldecode($qParts['query']),150);
			}
			$titleA = $this->itemLabel($label,$row['module_name'],$row['M_module_name']);

			$editSH = ($row['sc_group']>=0 || $BE_USER->isAdmin()) ? 'editSh('.intval($row['uid']).');' : "alert('".$LANG->getLL('bookmark_onlyAdmin')."')";
			$jumpSC = 'jump(unescape(\''.rawurlencode($row['url']).'\'),\''.implode('_',$mParts).'\',\''.$mParts[0].'\');';
			$onC = 'if (document.shForm.editShortcut_check && document.shForm.editShortcut_check.checked){'.$editSH.'}else{'.$jumpSC.'}return false;';
			if ($sc_group>=0)	{	// user defined groups show up first
				$this->linesPre[]='<td class="'.$bgColorClass.'"><a href="#" onclick="'.htmlspecialchars($onC).'"><img src="'.$this->getIcon($row['module_name']).'" title="'.htmlspecialchars($titleA).'" alt="" /></a></td>';
			} else {
				$this->lines[]='<td class="'.$bgColorClass.'"><a href="#" onclick="'.htmlspecialchars($onC).'"><img src="'.$this->getIcon($row['module_name']).'" title="'.htmlspecialchars($titleA).'" alt="" /></a></td>';
			}
			if (trim($row['description']))	{
				$kkey = strtolower(substr($row['description'],0,20)).'_'.$row['uid'];
				$this->selOpt[$kkey]='<option value="'.htmlspecialchars($jumpSC).'">'.htmlspecialchars(t3lib_div::fixed_lgd_cs($row['description'],50)).'</option>';
			}
			$formerGr=$row['sc_group'];
		}
		ksort($this->selOpt);
		array_unshift($this->selOpt,'<option>['.$LANG->getLL('bookmark_selSC',1).']</option>');

		$this->editLoadedFunc();
		$this->editPageIdFunc();

		if (!$this->editLoaded && t3lib_extMgm::isLoaded('cms'))	{
				$editIdCode = '<td nowrap="nowrap">'.$LANG->getLL('bookmark_editID',1).': <input type="text" value="'.($this->editError?htmlspecialchars($this->editPage):'').'" name="editPage"'.$this->doc->formWidth(15).' onchange="submitEditPage(this.value);" />'.
					($this->editError?'&nbsp;<strong><span class="typo3-red">'.htmlspecialchars($this->editError).'</span></strong>':'').
					(is_array($this->theEditRec)?'&nbsp;<strong>'.$LANG->getLL('bookmark_loadEdit',1).' \''.t3lib_BEfunc::getRecordTitle('pages',$this->theEditRec,TRUE).'\'</strong> ('.htmlspecialchars($this->editPath).')':'').
					($this->searchFor?'&nbsp;'.$LANG->getLL('bookmark_searchFor',1).' <strong>\''.htmlspecialchars($this->searchFor).'\'</strong>':'').
					'</td>';
		} else $editIdCode = '';

			// Adding CSH:
		$editIdCode.= '<td>&nbsp;'.t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'bookmarks', $GLOBALS['BACK_PATH'],'',TRUE).'</td>';

			// Compile it all:
		$this->content.='

			<table border="0" cellpadding="0" cellspacing="0" width="99%">
				<tr>
					<td>
						<!--
							Shortcut Display Table:
						-->
						<table border="0" cellpadding="0" cellspacing="2" id="typo3-shortcuts">
							<tr>
							';
								// "Shortcuts" have been renamed to "Bookmarks"
								// @deprecated remove shortcuts code in TYPO3 4.7
							$useShortcuts = $GLOBALS['BE_USER']->getTSConfigVal('options.enableShortcuts');
							$useBookmarks = $GLOBALS['BE_USER']->getTSConfigVal('options.enableBookmarks');
							if ($useShortcuts || $useBookmarks) {
								$this->content .= implode('
								', $this->lines);

								if ($useShortcuts) {
									t3lib_div::deprecationLog('options.enableShortcuts - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.enableBookmarks instead');
								}
							}
							$this->content .= $editIdCode . '
							</tr>
						</table>
					</td>
					<td align="right">';
		if ($this->hasWorkspaceAccess()) {
			$this->content .= $this->workspaceSelector() .
								t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'workspaceSelector', $GLOBALS['BACK_PATH'],'',TRUE);
		}
		$this->content .= '
					</td>
				</tr>
			</table>
			';

			// Launch Edit page:
		if ($this->theEditRec['uid'])	{
			$this->content.=$this->doc->wrapScriptTags('top.loadEditId('.$this->theEditRec['uid'].');');

		}

			// Load alternative table/uid into editing form.
		if (count($this->alternativeTableUid)==2 && isset($TCA[$this->alternativeTableUid[0]]) && t3lib_div::testInt($this->alternativeTableUid[1]))	{
			$JSaction = t3lib_BEfunc::editOnClick('&edit['.$this->alternativeTableUid[0].']['.$this->alternativeTableUid[1].']=edit','','dummy.php');
			$this->content.=$this->doc->wrapScriptTags('function editArbitraryElement() { top.content.'.$JSaction.'; } editArbitraryElement();');
		}

			// Load search for something.
		if ($this->searchFor)	{
			$urlParameters = array();
			$urlParameters['id'] = intval($GLOBALS['WEBMOUNTS'][0]);
			$urlParameters['search_field'] = $this->searchFor;
			$urlParameters['search_levels'] = 4;
			$this->content .= $this->doc->wrapScriptTags('jump(unescape("' .
				rawurlencode(t3lib_BEfunc::getModuleUrl('web_list', $urlParameters, '')) .
			'"), "web_list", "web");');
		}
	}

	/**
	 * Creates lines for the editing form.
	 *
	 * @return	void
	 */
	function editLoadedFunc()	{
		global $BE_USER,$LANG;

		$this->editLoaded=0;
		if (is_array($this->editSC_rec) && ($this->editSC_rec['sc_group']>=0 || $BE_USER->isAdmin()))	{	// sc_group numbers below 0 requires admin to edit those. sc_group numbers above zero must always be owned by the user himself.
			$this->editLoaded=1;

			$opt=array();
			$opt[]='<option value="0"></option>';

			foreach($this->groupLabels as $k=>$v)	{
				if ($v && strcmp('1',$v))	{
					$label = $v;
				} else {
					$label = $LANG->getLL('bookmark_group_'.$k,1);
					if (!$label)	$label = $LANG->getLL('bookmark_group',1).' '.$k;	// Fallback label
				}
				$opt[]='<option value="'.$k.'"'.(!strcmp($this->editSC_rec['sc_group'],$k)?' selected="selected"':'').'>'.$label.'</option>';
			}

			if ($BE_USER->isAdmin())	{
				foreach($this->groupLabels as $k=>$v)	{
					if ($v && strcmp('1',$v))	{
						$label = $v;
					} else {
						$label = $LANG->getLL('bookmark_group_'.$k,1);
						if (!$label)	$label = $LANG->getLL('bookmark_group',1).' '.$k;	// Fallback label
					}
					$label = $LANG->getLL('bookmark_global',1).': '.$label;	// Add a prefix for global groups

					$opt[]='<option value="-'.$k.'"'.(!strcmp($this->editSC_rec['sc_group'],'-'.$k)?' selected="selected"':'').'>'.$label.'</option>';
				}
				$opt[]='<option value="-100"'.(!strcmp($this->editSC_rec['sc_group'],'-100')?' selected="selected"':'').'>'.$LANG->getLL('bookmark_global',1).': '.$LANG->getLL('bookmark_all',1).'</option>';
			}

				// border="0" hspace="2" width="21" height="16" - not XHTML compliant in <input type="image" ...>
			$manageForm='

				<!--
					Shortcut Editing Form:
				-->
				<table border="0" cellpadding="0" cellspacing="0" id="typo3-shortcuts-editing">
					<tr>
						<td>&nbsp;&nbsp;</td>
						<td><input type="image" class="c-inputButton" name="_savedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/savedok.gif','').' title="'.$LANG->getLL('shortcut_save',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_saveclosedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/saveandclosedok.gif','').' title="'.$LANG->getLL('bookmark_saveClose',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_closedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/closedok.gif','').' title="'.$LANG->getLL('bookmark_close',1).'" /></td>
						<td><input type="image" class="c-inputButton" name="_deletedok"'.t3lib_iconWorks::skinImg($this->doc->backPath,'gfx/deletedok.gif','').' title="'.$LANG->getLL('bookmark_delete',1).'" /></td>
						<td><input name="editName" type="text" value="'.htmlspecialchars($this->editSC_rec['description']).'"'.$this->doc->formWidth(15).' /></td>
						<td><select name="editGroup">'.implode('',$opt).'</select></td>
					</tr>
				</table>
				<input type="hidden" name="whichItem" value="'.$this->editSC_rec['uid'].'" />

				';
		} else $manageForm='';

		if (!$this->editLoaded && count($this->selOpt)>1)	{
			$this->lines[]='<td>&nbsp;</td>';
			$this->lines[]='<td><select name="_selSC" onchange="eval(this.options[this.selectedIndex].value);this.selectedIndex=0;">'.implode('',$this->selOpt).'</select></td>';
		}

			// $this->linesPre contains elements with sc_group>=0
		$this->lines = array_merge($this->linesPre,$this->lines);

		if (count($this->lines)) {
				// "Shortcuts" have been renamed to "Bookmarks"
				// @deprecated remove shortcuts code in TYPO3 4.7
			$createShortcuts = !$BE_USER->getTSConfigVal('options.mayNotCreateEditShortcuts');
			$createBookmarks = !$BE_USER->getTSConfigVal('options.mayNotCreateEditBookmarks');

			if ($createShortcuts || $createBookmarks) {
				$this->lines=array_merge(array('<td><input type="checkbox" id="editShortcut_check" name="editShortcut_check" value="1"'.($this->editSC?' checked="checked"':'').' /> <label for="editShortcut_check">'.$LANG->getLL('bookmark_edit',1).'</label>&nbsp;</td>'),$this->lines);
				$this->lines[]='<td>'.$manageForm.'</td>';

				if ($createShortcuts) {
					t3lib_div::deprecationLog('options.mayNotCreateEditShortcuts - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.mayNotCreateEditBookmarks instead');
				}
			}
			$this->lines[]='<td><img src="clear.gif" width="10" height="1" alt="" /></td>';
		}
	}

	/**
	 * If "editPage" value is sent to script and it points to an accessible page, the internal var $this->theEditRec is set to the page record which should be loaded.
	 * Returns void
	 *
	 * @return	void
	 */
	function editPageIdFunc()	{
		global $BE_USER,$LANG;

		if (!t3lib_extMgm::isLoaded('cms'))	return;

			// EDIT page:
		$this->editPage = trim($LANG->csConvObj->conv_case($LANG->charSet,$this->editPage,'toLower'));
		$this->editError = '';
		$this->theEditRec = '';
		$this->searchFor = '';
		if ($this->editPage)	{

				// First, test alternative value consisting of [table]:[uid] and if not found, proceed with traditional page ID resolve:
			$this->alternativeTableUid = explode(':',$this->editPage);
			if (!(count($this->alternativeTableUid)==2 && $BE_USER->isAdmin()))	{	// We restrict it to admins only just because I'm not really sure if alt_doc.php properly checks permissions of passed records for editing. If alt_doc.php does that, then we can remove this.

				$where = ' AND ('.$BE_USER->getPagePermsClause(2).' OR '.$BE_USER->getPagePermsClause(16).')';
				if (t3lib_div::testInt($this->editPage))	{
					$this->theEditRec = t3lib_BEfunc::getRecordWSOL('pages',$this->editPage,'*',$where);
				} else {
					$records = t3lib_BEfunc::getRecordsByField('pages','alias',$this->editPage,$where);
					if (is_array($records))	{
						reset($records);
						$this->theEditRec = current($records);
						t3lib_BEfunc::workspaceOL('pages',$this->theEditRec);
					}
				}
				if (!is_array($this->theEditRec))	{
					unset($this->theEditRec);
					$this->searchFor = $this->editPage;
				} elseif (!$BE_USER->isInWebMount($this->theEditRec['uid'])) {
					unset($this->theEditRec);
					$this->editError=$LANG->getLL('bookmark_notEditable');
				} else {

						// Visual path set:
					$perms_clause = $BE_USER->getPagePermsClause(1);
					$this->editPath = t3lib_BEfunc::getRecordPath($this->theEditRec['pid'], $perms_clause, 30);

						// "Shortcuts" have been renamed to "Bookmarks"
						// @deprecated remove shortcuts code in TYPO3 4.7
					$shortcutSetPageTree = !$BE_USER->getTSConfigVal('options.shortcut_onEditId_dontSetPageTree');
					$bookmarkSetPageTree = !$BE_USER->getTSConfigVal('options.bookmark_onEditId_dontSetPageTree');

					if ($shortcutSetPageTree && $bookmarkSetPageTree) {
						$shortcutKeepExpanded = $BE_USER->getTSConfigVal('options.shortcut_onEditId_keepExistingExpanded');
						$bookmarkKeepExpanded = $BE_USER->getTSConfigVal('options.bookmark_onEditId_keepExistingExpanded');
						$keepNotExpanded = (!$shortcutKeepExpanded || !$bookmarkKeepExpanded);

							// Expanding page tree:
						t3lib_BEfunc::openPageTree($this->theEditRec['pid'], $keepNotExpanded);

						if ($shortcutSetPageTree) {
							t3lib_div::deprecationLog('options.shortcut_onEditId_dontSetPageTree - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.bookmark_onEditId_dontSetPageTree instead');
						}
						if ($shortcutKeepExpanded) {
							t3lib_div::deprecationLog('options.shortcut_onEditId_keepExistingExpanded - since TYPO3 4.5, will be removed in TYPO3 4.7 - use options.bookmark_onEditId_keepExistingExpanded instead');
						}
					}
				}
			}
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{
		$content = '';

		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);

		if($this->editPage && $this->isAjaxCall) {
			$data = array();

				// edit page
			if($this->theEditRec['uid']) {
				$data['type']       = 'page';
				$data['editRecord'] = $this->theEditRec['uid'];
			}

				// edit alternative table/uid
			if(count($this->alternativeTableUid) == 2
			&& isset($GLOBALS['TCA'][$this->alternativeTableUid[0]])
			&& t3lib_div::testInt($this->alternativeTableUid[1])) {
				$data['type']             = 'alternative';
				$data['alternativeTable'] = $this->alternativeTableUid[0];
				$data['alternativeUid']   = $this->alternativeTableUid[1];
			}

				// search for something else
			if($this->searchFor) {
				$data['type']            = 'search';
				$data['firstMountPoint'] = intval($GLOBALS['WEBMOUNTS'][0]);
				$data['searchFor']       = $this->searchFor;
			}

			$content = json_encode($data);

			header('Content-type: application/json; charset=utf-8');
			header('X-JSON: '.$content);
		} else {
			$content = $this->content;
		}

		echo $content;
	}









	/***************************
	 *
	 * WORKSPACE FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Create selector for workspaces and change workspace if command is given to do that.
	 *
	 * @return	string		HTML
	 */
	function workspaceSelector()	{
		global $TYPO3_DB,$BE_USER,$LANG;

			// Changing workspace and if so, reloading entire backend:
		if (strlen($this->changeWorkspace))	{
			$BE_USER->setWorkspace($this->changeWorkspace);
			return $this->doc->wrapScriptTags('top.location.href="'. t3lib_BEfunc::getBackendScript() . '";');
		}
			// Changing workspace and if so, reloading entire backend:
		if (strlen($this->changeWorkspacePreview))	{
			$BE_USER->setWorkspacePreview($this->changeWorkspacePreview);
		}

			// Create options array:
		$options = array();
		if ($BE_USER->checkWorkspace(array('uid' => 0)))	{
			$options[0] = '['.$LANG->getLL('bookmark_onlineWS').']';
		}
		if ($BE_USER->checkWorkspace(array('uid' => -1)))	{
			$options[-1] = '['.$LANG->getLL('bookmark_offlineWS').']';
		}

			// Add custom workspaces (selecting all, filtering by BE_USER check):
		$workspaces = $TYPO3_DB->exec_SELECTgetRows('uid,title,adminusers,members,reviewers','sys_workspace','pid=0'.t3lib_BEfunc::deleteClause('sys_workspace'),'','title');
		if (count($workspaces))	{
			foreach ($workspaces as $rec)	{
				if ($BE_USER->checkWorkspace($rec))	{
					$options[$rec['uid']] = $rec['uid'].': '.$rec['title'];
				}
			}
		}

			// Build selector box:
		if (count($options))	{
			foreach($options as $value => $label)	{
				$selected = ((int)$BE_USER->workspace===$value ? ' selected="selected"' : '');
				$options[$value] = '<option value="'.htmlspecialchars($value).'"'.$selected.'>'.htmlspecialchars($label).'</option>';
			}
		} else {
			$options[] = '<option value="-99">'.$LANG->getLL('bookmark_noWSfound',1).'</option>';
		}

		$selector = '';
			// Preview:
		if ($BE_USER->workspace!==0)	{
			$selector.= '<label for="workspacePreview">Frontend Preview:</label> <input type="checkbox" name="workspacePreview" id="workspacePreview" onclick="changeWorkspacePreview('.($BE_USER->user['workspace_preview'] ? 0 : 1).')"; '.($BE_USER->user['workspace_preview'] ? 'checked="checked"' : '').'/>&nbsp;';
		}

		$selector.= '<a href="mod/user/ws/index.php" target="content">'.
					t3lib_iconWorks::getSpriteIconForRecord('sys_workspace', array()).
					'</a>';
		if (count($options) > 1) {
			$selector .= '<select name="_workspaceSelector" onchange="changeWorkspace(this.options[this.selectedIndex].value);">'.implode('',$options).'</select>';
		}

		return $selector;
	}







	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/

	/**
	 * Returns relative filename for icon.
	 *
	 * @param	string		Absolute filename of the icon
	 * @param	string		Backpath string to prepend the icon after made relative
	 * @return	void
	 */
	function mIconFilename($Ifilename,$backPath)	{
			// Change icon of fileadmin references - otherwise it doesn't differ with Web->List
		$Ifilename = str_replace ('mod/file/list/list.gif', 'mod/file/file.gif', $Ifilename);

		if (t3lib_div::isAbsPath($Ifilename))	{
			$Ifilename = '../'.substr($Ifilename,strlen(PATH_site));
		}
		return $backPath.$Ifilename;
	}

	/**
	 * Returns icon for shortcut display
	 *
	 * @param	string		Backend module name
	 * @return	string		Icon file name
	 */
	function getIcon($modName)	{
		global $LANG;
		if ($LANG->moduleLabels['tabs_images'][$modName.'_tab'])	{
			$icon = $this->mIconFilename($LANG->moduleLabels['tabs_images'][$modName.'_tab'],'');
		} elseif ($modName=='xMOD_alt_doc.php') {
			$icon = 'gfx/edit2.gif';
		} elseif ($modName=='xMOD_file_edit.php') {
			$icon = 'gfx/edit_file.gif';
		} elseif ($modName=='xMOD_wizard_rte.php') {
			$icon = 'gfx/edit_rtewiz.gif';
		} else {
			$icon = 'gfx/dummy_module.gif';
		}
		return $icon;
	}

	/**
	 * Returns title-label for icon
	 *
	 * @param	string		In-label
	 * @param	string		Backend module name (key)
	 * @param	string		Backend module label (user defined?)
	 * @return	string		Label for the shortcut item
	 */
	function itemLabel($inlabel,$modName,$M_modName='')	{
		global $LANG;
		if (substr($modName,0,5)=='xMOD_')	{
			$label=substr($modName,5);
		} else {
			$split = explode('_',$modName);
			$label = $LANG->moduleLabels['tabs'][$split[0].'_tab'];
			if (count($split)>1)	{
				$label.='>'.$LANG->moduleLabels['tabs'][$modName.'_tab'];
			}
		}
		if ($M_modName)	$label.=' ('.$M_modName.')';
		$label.=': '.$inlabel;
		return $label;
	}

	/**
	 * Return the ID of the page in the URL if found.
	 *
	 * @param	string		The URL of the current shortcut link
	 * @return	string		If a page ID was found, it is returned. Otherwise: 0
	 */
	function getLinkedPageId($url)	{
		return preg_replace('/.*[\?&]id=([^&]+).*/', '$1', $url);
	}

	/**
	 * Checks if user has access to Workspace module.
	 *
	 * @return	boolean		Returns true if user has access to workspace module.
	 */
	function hasWorkspaceAccess() {
		$MCONF = array();
		include('mod/user/ws/conf.php');
		return $GLOBALS['BE_USER']->modAccess(array('name' => 'user', 'access' => 'user,group'), false) && $GLOBALS['BE_USER']->modAccess($MCONF, false);
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_shortcut.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/alt_shortcut.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_alt_shortcut');
$SOBE->preinit();
$SOBE->preprocess();
$SOBE->init();
$SOBE->main();
$SOBE->printContent();

?>
