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
 * New content elements wizard
 * (Part of the 'cms' extension)
 *
 * Revised for TYPO3 3.6 November/2003 by Kasper Skårhøj
 * XHTML compatible.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  101: class ext_posMap extends t3lib_positionMap
 *  111:     function wrapRecordTitle($str,$row)
 *  125:     function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0)
 *
 *
 *  153: class SC_db_new_content_el
 *  176:     function init()
 *  212:     function main()
 *  359:     function printContent()
 *
 *              SECTION: OTHER FUNCTIONS:
 *  388:     function getWizardItems()
 *  398:     function wizardArray()
 *  549:     function removeInvalidElements(&$wizardItems)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');

	// Unset MCONF/MLANG since all we wanted was back path etc. for this particular script.
unset($MCONF);
unset($MLANG);

	// Merging locallang files/arrays:
$LANG->includeLLFile('EXT:lang/locallang_misc.xml');
$LOCAL_LANG_orig = $LOCAL_LANG;
$LANG->includeLLFile('EXT:cms/layout/locallang_db_new_content_el.xml');
$LOCAL_LANG = t3lib_div::array_merge_recursive_overrule($LOCAL_LANG_orig,$LOCAL_LANG);

	// Exits if 'cms' extension is not loaded:
t3lib_extMgm::isLoaded('cms',1);







/**
 * Local position map class
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class ext_posMap extends t3lib_positionMap {
	var $dontPrintPageInsertIcons = 1;

	/**
	 * Wrapping the title of the record - here we just return it.
	 *
	 * @param	string		The title value.
	 * @param	array		The record row.
	 * @return	string		Wrapped title string.
	 */
	function wrapRecordTitle($str,$row)	{
		return $str;
	}

	/**
	 * Create on-click event value.
	 *
	 * @param	array		The record.
	 * @param	string		Column position value.
	 * @param	integer		Move uid
	 * @param	integer		PID value.
	 * @param	integer		System language
	 * @return	string
	 */
	function onClickInsertRecord($row,$vv,$moveUid,$pid,$sys_lang=0) {
		$table='tt_content';

		$location=$this->backPath.'alt_doc.php?edit[tt_content]['.(is_array($row)?-$row['uid']:$pid).']=new&defVals[tt_content][colPos]='.$vv.'&defVals[tt_content][sys_language_uid]='.$sys_lang.'&returnUrl='.rawurlencode($GLOBALS['SOBE']->R_URI);

		return 'window.location.href=\''.$location.'\'+document.editForm.defValues.value; return false;';
	}
}













/**
 * Script Class for the New Content element wizard
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_db_new_content_el {

		// Internal, static (from GPvars):
	var $id;					// Page id
	var $sys_language=0;		// Sys language
	var $R_URI='';				// Return URL.
	var $colPos;				// If set, the content is destined for a specific column.
	var $uid_pid;				//

		// Internal, static:
	var $modTSconfig=array();	// Module TSconfig.

	/**
	 * Internal backend template object
	 *
	 * @var mediumDoc
	 */
	var $doc;

		// Internal, dynamic:
	var $include_once = array();	// Includes a list of files to include between init() and main() - see init()
	var $content;					// Used to accumulate the content of the module.
	var $access;					// Access boolean.
	var $config;					// config of the wizard


	/**
	 * Constructor, initializing internal variables.
	 *
	 * @return	void
	 */
	function init()	{
		global $BACK_PATH,$TBE_MODULES_EXT;

			// Setting class files to include:
		if (is_array($TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']))	{
			$this->include_once = array_merge($this->include_once,$TBE_MODULES_EXT['xMOD_db_new_content_el']['addElClasses']);
		}

			// Setting internal vars:
		$this->id = intval(t3lib_div::_GP('id'));
		$this->sys_language = intval(t3lib_div::_GP('sys_language_uid'));
		$this->R_URI = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('returnUrl'));
		$this->colPos = t3lib_div::_GP('colPos');
		$this->uid_pid = intval(t3lib_div::_GP('uid_pid'));

		$this->MCONF['name'] = 'xMOD_db_new_content_el';
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.wizards.newContentElement');

		$config = t3lib_BEfunc::getPagesTSconfig($this->id);
		$this->config = $config['mod.']['wizards.']['newContentElement.'];

			// Starting the document template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->setModuleTemplate('templates/db_new_content_el.html');
		$this->doc->JScode='';
		$this->doc->form='<form action="" name="editForm"><input type="hidden" name="defValues" value="" />';

			// Setting up the context sensitive menu:
		$this->doc->getContextMenuCode();

			// Getting the current page and receiving access information (used in main())
		$perms_clause = $GLOBALS['BE_USER']->getPagePermsClause(1);
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id,$perms_clause);
		$this->access = is_array($this->pageinfo) ? 1 : 0;
	}

	/**
	 * Creating the module output.
	 *
	 * @return	void
	 */
	function main()	{
		global $LANG,$BACK_PATH;


		if ($this->id && $this->access)	{

				// Init position map object:
			$posMap = t3lib_div::makeInstance('ext_posMap');
			$posMap->cur_sys_language = $this->sys_language;
			$posMap->backPath = $BACK_PATH;

			if ((string)$this->colPos!='')	{	// If a column is pre-set:
				if ($this->uid_pid<0)	{
					$row=array();
					$row['uid']=abs($this->uid_pid);
				} else {
					$row='';
				}
				$this->onClickEvent = $posMap->onClickInsertRecord($row, $this->colPos, '', $this->uid_pid, $this->sys_language);
			} else {
				$this->onClickEvent = '';
			}


			// ***************************
			// Creating content
			// ***************************
				// use a wrapper div
			$this->content .= '<div id="user-setup-wrapper">';
			$this->content.=$this->doc->header($LANG->getLL('newContentElement'));
			$this->content.=$this->doc->spacer(5);

				// Wizard
			$code='';
			$wizardItems = $this->getWizardItems();

				// Wrapper for wizards
			$this->elementWrapper['sectionHeader'] = array('<h3 class="divider">', '</h3>');
			$this->elementWrapper['section'] = array('<table border="0" cellpadding="1" cellspacing="2">', '</table>');
			$this->elementWrapper['wizard'] = array('<tr>', '</tr>');
			$this->elementWrapper['wizardPart'] = array('<td>', '</td>');
				// copy wrapper for tabs
			$this->elementWrapperForTabs = $this->elementWrapper;


				// Hook for manipulating wizardItems, wrapper, onClickEvent etc.
			if(is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'])) {
				foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['cms']['db_new_content_el']['wizardItemsHook'] as $classData) {
					$hookObject = t3lib_div::getUserObj($classData);

					if(!($hookObject instanceof cms_newContentElementWizardsHook)) {
						throw new UnexpectedValueException('$hookObject must implement interface cms_newContentElementWizardItemsHook', 1227834741);
					}

					$hookObject->manipulateWizardItems($wizardItems, $this);
				}
			}

			if ($this->config['renderMode'] == 'tabs' && $this->elementWrapperForTabs != $this->elementWrapper) {
					// restore wrapper for tabs if they are overwritten in hook
				$this->elementWrapper = $this->elementWrapperForTabs;
			}

				// add document inline javascript
			$this->doc->JScode = $this->doc->wrapScriptTags('
				function goToalt_doc()	{	//
					' . $this->onClickEvent . '
				}

				if(top.refreshMenu) {
					top.refreshMenu();
				} else {
					top.TYPO3ModuleMenu.refreshMenu();
				}
			');

				// Traverse items for the wizard.
				// An item is either a header or an item rendered with a radio button and title/description and icon:
			$cc = $key = 0;
			$menuItems = array();
			foreach ($wizardItems as $k => $wInfo)	{
				if ($wInfo['header'])	{
					$menuItems[] = array(
							'label'   => htmlspecialchars($wInfo['header']),
							'content' => $this->elementWrapper['section'][0]
					);
					$key = count($menuItems) - 1;
				} else {
					$content = '';
						// Radio button:
					$oC = "document.editForm.defValues.value=unescape('".rawurlencode($wInfo['params'])."');goToalt_doc();".(!$this->onClickEvent?"window.location.hash='#sel2';":'');
					$content .= $this->elementWrapper['wizardPart'][0] .
						'<input type="radio" name="tempB" value="' . htmlspecialchars($k) . '" onclick="' . htmlspecialchars($oC) . '" />' .
						$this->elementWrapper['wizardPart'][1];

						// Onclick action for icon/title:
					$aOnClick = 'document.getElementsByName(\'tempB\')['.$cc.'].checked=1;'.$oC.'return false;';

						// Icon:
					$iInfo = @getimagesize($wInfo['icon']);
					$content .= $this->elementWrapper['wizardPart'][0] .
						'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '">
						<img' . t3lib_iconWorks::skinImg($this->doc->backPath, $wInfo['icon'], '') . ' alt="" /></a>' .
						$this->elementWrapper['wizardPart'][1];

						// Title + description:
					$content .= $this->elementWrapper['wizardPart'][0] .
						'<a href="#" onclick="' . htmlspecialchars($aOnClick) . '"><strong>' . htmlspecialchars($wInfo['title']) . '</strong><br />' .
						nl2br(htmlspecialchars(trim($wInfo['description']))) . '</a>' .
						$this->elementWrapper['wizardPart'][1];

						// Finally, put it together in a container:
					$menuItems[$key]['content'] .= $this->elementWrapper['wizard'][0] . $content . $this->elementWrapper['wizard'][1];
					$cc++;
				}
			}
				// add closing section-tag
			foreach ($menuItems as $key => $val) {
				$menuItems[$key]['content'] .=  $this->elementWrapper['section'][1];
			}



				// Add the wizard table to the content, wrapped in tabs:
			if ($this->config['renderMode'] == 'tabs') {
				$this->doc->inDocStylesArray[] = '
					.typo3-dyntabmenu-divs { background-color: #fafafa; border: 1px solid #adadad; width: 680px; }
					.typo3-dyntabmenu-divs table { margin: 15px; }
					.typo3-dyntabmenu-divs table td { padding: 3px; }
				';
				$code = $LANG->getLL('sel1', 1) . '<br /><br />' . $this->doc->getDynTabMenu($menuItems, 'new-content-element-wizard', FALSE, FALSE);
			} else {
				$code = $LANG->getLL('sel1',1) . '<br /><br />';
				foreach ($menuItems as $section) {
					$code .= $this->elementWrapper['sectionHeader'][0] . $section['label'] . $this->elementWrapper['sectionHeader'][1] . $section['content'];
				}
			}

			$this->content.= $this->doc->section(!$this->onClickEvent ? $LANG->getLL('1_selectType') : '', $code, 0, 1);



				// If the user must also select a column:
			if (!$this->onClickEvent) {

					// Add anchor "sel2"
				$this->content.= $this->doc->section('','<a name="sel2"></a>');
				$this->content.= $this->doc->spacer(20);

					// Select position
				$code = $LANG->getLL('sel2',1).'<br /><br />';

					// Load SHARED page-TSconfig settings and retrieve column list from there, if applicable:
				$modTSconfig_SHARED = t3lib_BEfunc::getModTSconfig($this->id,'mod.SHARED');
				$colPosList = strcmp(trim($modTSconfig_SHARED['properties']['colPos_list']),'') ? trim($modTSconfig_SHARED['properties']['colPos_list']) : '1,0,2,3';
				$colPosList = implode(',',array_unique(t3lib_div::intExplode(',',$colPosList)));		// Removing duplicates, if any

					// Finally, add the content of the column selector to the content:
				$code.= $posMap->printContentElementColumns($this->id,0,$colPosList,1,$this->R_URI);
				$this->content.= $this->doc->section($LANG->getLL('2_selectPosition'),$code,0,1);
			}

				// Close wrapper div
			$this->content .= '</div>';
		} else {		// In case of no access:
			$this->content = '';
			$this->content.= $this->doc->header($LANG->getLL('newContentElement'));
			$this->content.= $this->doc->spacer(5);
		}

			// Setting up the buttons and markers for docheader
		$docHeaderButtons = $this->getButtons();
		$markers['CSH'] = $docHeaderButtons['csh'];
		$markers['CONTENT'] = $this->content;

			// Build the <body> for the module
		$this->content = $this->doc->startPage($LANG->getLL('newContentElement'));
		$this->content.= $this->doc->moduleBody($this->pageinfo, $docHeaderButtons, $markers);
		$this->content .= $this->doc->sectionEnd();
		$this->content.= $this->doc->endPage();
		$this->content = $this->doc->insertStylesAndJS($this->content);
	}

	/**
	 * Print out the accumulated content:
	 *
	 * @return	void
	 */
	function printContent()	{
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return	array	all available buttons as an assoc. array
	 */
	protected function getButtons()	{
		global $LANG, $BACK_PATH;

		$buttons = array(
			'csh' => '',
			'back' => ''
		);


		if ($this->id && $this->access)	{
				// CSH
			$buttons['csh'] = t3lib_BEfunc::cshItem('xMOD_csh_corebe', 'new_ce', $GLOBALS['BACK_PATH'], '', TRUE);

				// Back
			if ($this->R_URI)	{
				$buttons['back'] = '<a href="' . htmlspecialchars($this->R_URI) . '" class="typo3-goBack" title="' . $LANG->getLL('goBack', TRUE) . '">' .
						t3lib_iconWorks::getSpriteIcon('actions-view-go-back') .
					'</a>';
			}
		}


		return $buttons;
	}









	/***************************
	 *
	 * OTHER FUNCTIONS:
	 *
	 ***************************/


	/**
	 * Returns the content of wizardArray() function...
	 *
	 * @return	array		Returns the content of wizardArray() function...
	 */
	function getWizardItems()	{
		return $this->wizardArray();
	}

	/**
	 * Returns the array of elements in the wizard display.
	 * For the plugin section there is support for adding elements there from a global variable.
	 *
	 * @return	array
	 */
	function wizardArray()	{

		if (is_array($this->config)) {
			$wizards = $this->config['wizardItems.'];
		}
		$appendWizards = $this->wizard_appendWizards($wizards['elements.']);

		$wizardItems = array();

		if (is_array($wizards)) {
			foreach ($wizards as $groupKey => $wizardGroup) {
				$groupKey = preg_replace('/\.$/', '', $groupKey);
				$showItems = t3lib_div::trimExplode(',', $wizardGroup['show'], true);
				$showAll = (strcmp($wizardGroup['show'], '*') ? false : true);
				$groupItems = array();

				if (is_array($appendWizards[$groupKey . '.']['elements.'])) {
					$wizardElements = array_merge((array) $wizardGroup['elements.'], $appendWizards[$groupKey . '.']['elements.']);
				} else {
					$wizardElements = $wizardGroup['elements.'];
				}

				if (is_array($wizardElements)) {
					foreach ($wizardElements as $itemKey => $itemConf) {
						$itemKey = preg_replace('/\.$/', '', $itemKey);
						if ($showAll || in_array($itemKey, $showItems)) {
							$tmpItem = $this->wizard_getItem($groupKey, $itemKey, $itemConf);
							if ($tmpItem) {
								$groupItems[$groupKey . '_' . $itemKey] = $tmpItem;
			}
		}
					}
				}
				if (count($groupItems)) {
					$wizardItems[$groupKey] = $this->wizard_getGroupHeader($groupKey, $wizardGroup);
					$wizardItems = array_merge($wizardItems, $groupItems);
				}
			}
		}

			// Remove elements where preset values are not allowed:
		$this->removeInvalidElements($wizardItems);

		return $wizardItems;
	}

	function wizard_appendWizards($wizardElements) {
		if (!is_array($wizardElements)) {
			$wizardElements = array();
		}
		if (is_array($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'])) {
			foreach ($GLOBALS['TBE_MODULES_EXT']['xMOD_db_new_content_el']['addElClasses'] as $class => $path) {
				require_once($path);
				$modObj = t3lib_div::makeInstance($class);
				$wizardElements = $modObj->proc($wizardElements);
			}
		}
		$returnElements = array();
		foreach ($wizardElements as $key => $wizardItem) {
			preg_match('/^[a-zA-Z0-9]+_/', $key, $group);
			$wizardGroup =  $group[0] ? substr($group[0], 0, -1) . '.' : $key;
			$returnElements[$wizardGroup]['elements.'][substr($key, strlen($wizardGroup)) . '.'] = $wizardItem;
		}
		return $returnElements;
	}


	function wizard_getItem($groupKey, $itemKey, $itemConf) {
		$itemConf['title'] = $GLOBALS['LANG']->sL($itemConf['title']);
		$itemConf['description'] = $GLOBALS['LANG']->sL($itemConf['description']);
		$itemConf['tt_content_defValues'] = $itemConf['tt_content_defValues.'];
		unset($itemConf['tt_content_defValues.']);
		return $itemConf;
	}

	function wizard_getGroupHeader($groupKey, $wizardGroup) {
		return array(
			'header' => $GLOBALS['LANG']->sL($wizardGroup['header'])
		);
	}


	/**
	 * Checks the array for elements which might contain unallowed default values and will unset them!
	 * Looks for the "tt_content_defValues" key in each element and if found it will traverse that array as fieldname / value pairs and check. The values will be added to the "params" key of the array (which should probably be unset or empty by default).
	 *
	 * @param	array		Wizard items, passed by reference
	 * @return	void
	 */
	function removeInvalidElements(&$wizardItems)	{

			// Load full table definition:
		t3lib_div::loadTCA('tt_content');

			// Get TCEFORM from TSconfig of current page
		$row = array('pid' => $this->id);
		$TCEFORM_TSconfig = t3lib_BEfunc::getTCEFORM_TSconfig('tt_content', $row);
		$removeItems = t3lib_div::trimExplode(',', $TCEFORM_TSconfig['CType']['removeItems'], 1);
		$keepItems = t3lib_div::trimExplode(',', $TCEFORM_TSconfig['CType']['keepItems'], 1);

		$headersUsed = Array();
			// Traverse wizard items:
		foreach($wizardItems as $key => $cfg)	{

				// Exploding parameter string, if any (old style)
			if ($wizardItems[$key]['params'])	{
					// Explode GET vars recursively
				$tempGetVars = t3lib_div::explodeUrl2Array($wizardItems[$key]['params'],TRUE);
					// If tt_content values are set, merge them into the tt_content_defValues array, unset them from $tempGetVars and re-implode $tempGetVars into the param string (in case remaining parameters are around).
				if (is_array($tempGetVars['defVals']['tt_content']))	{
					$wizardItems[$key]['tt_content_defValues'] = array_merge(is_array($wizardItems[$key]['tt_content_defValues']) ? $wizardItems[$key]['tt_content_defValues'] : array(), $tempGetVars['defVals']['tt_content']);
					unset($tempGetVars['defVals']['tt_content']);
					$wizardItems[$key]['params'] = t3lib_div::implodeArrayForUrl('',$tempGetVars);
				}
			}

				// If tt_content_defValues are defined...:
			if (is_array($wizardItems[$key]['tt_content_defValues']))	{

					// Traverse field values:
				foreach($wizardItems[$key]['tt_content_defValues'] as $fN => $fV)	{
					if (is_array($GLOBALS['TCA']['tt_content']['columns'][$fN]))	{
							// Get information about if the field value is OK:
						$config = &$GLOBALS['TCA']['tt_content']['columns'][$fN]['config'];
						$authModeDeny = ($config['type']=='select' && $config['authMode'] && !$GLOBALS['BE_USER']->checkAuthMode('tt_content', $fN, $fV, $config['authMode']));
						$isNotInKeepItems = (count($keepItems) && !in_array($fV, $keepItems));

						if ($authModeDeny || ($fN=='CType' && in_array($fV,$removeItems)) || $isNotInKeepItems) {
								// Remove element all together:
							unset($wizardItems[$key]);
							break;
						} else {
								// Add the parameter:
							$wizardItems[$key]['params'].= '&defVals[tt_content]['.$fN.']='.rawurlencode($fV);
							$tmp = explode('_', $key);
							$headersUsed[$tmp[0]] = $tmp[0];
						}
					}
				}
			}
		}
			// remove headers without elements
		foreach ($wizardItems as $key => $cfg)	{
			$tmp = explode('_',$key);
			if ($tmp[0] && !$tmp[1] && !in_array($tmp[0], $headersUsed))	{
				unset($wizardItems[$key]);
			}
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/layout/db_new_content_el.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/cms/layout/db_new_content_el.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_db_new_content_el');
$SOBE->init();

// Include files?
foreach($SOBE->include_once as $INC_FILE)	include_once($INC_FILE);

$SOBE->main();
$SOBE->printContent();

?>
