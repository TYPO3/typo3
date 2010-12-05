<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
*  (c) 2005-2010 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Displays the page/file tree for browsing database records or files.
 * Used from TCEFORMS an other elements
 * In other words: This is the ELEMENT BROWSER!
 *
 * Adapted for htmlArea RTE by Stanislas Rolland
 *
 * TYPO3 SVN ID: $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */

require_once(t3lib_extMgm::extPath('dam').'class.tx_dam_browse_media.php');

/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_dam_browse_links extends tx_dam_browse_media {

		// Internal, static:
	var $setTarget;			// Target (RTE specific)
	var $setClass;			// Class (RTE specific)
	var $setTitle;			// Title (RTE specific)

	var $contentTypo3Language;
	var $contentTypo3Charset;

	var $editorNo;
	var $buttonConfig = array();

	protected $classesAnchorDefault = array();
	protected $classesAnchorDefaultTitle = array();
	protected $classesAnchorDefaultTarget = array();
	protected $classesAnchorJSOptions = array();
	public $allowedItems;


	/**
	 * Check if this object should be rendered.
	 *
	 * @param	string		$type Type: "file", ...
	 * @param	object		$pObj Parent object.
	 * @return	boolean
	 * @see SC_browse_links::main()
	 */
	function isValid($type, $pObj) {
		$isValid = false;

		$pArr = explode('|', t3lib_div::_GP('bparams'));

		if ($type=='rte' && $pObj->button == 'link') {
			$isValid = true;
		}

		return $isValid;
	}

	/**
	 * Rendering
	 * Called in SC_browse_links::main() when isValid() returns true;
	 *
	 * @param	string		$type Type: "file", ...
	 * @param	object		$pObj Parent object.
	 * @return	string		Rendered content
	 * @see SC_browse_links::main()
	 */
	function render($type, $pObj) {
		global $LANG, $BE_USER, $BACK_PATH;

		$this->pObj = $pObj;

			// init class browse_links
		$this->init();

		switch((string)$this->mode)	{
			case 'rte':
				$content = $this->main_rte();
			break;
			default:
				$content = '';
			break;
		}

		return $content;
	}

	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$BACK_PATH,$LANG,$TYPO3_CONF_VARS;

			// Main GPvars:
		$this->siteUrl = t3lib_div::getIndpEnv('TYPO3_SITE_URL');
		$this->act = t3lib_div::_GP('act');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->pointer = t3lib_div::_GP('pointer');
		$this->P = t3lib_div::_GP('P');
		$this->PM = t3lib_div::_GP('PM');

			// Find RTE parameters
		$this->bparams = t3lib_div::_GP('bparams');
		$this->contentTypo3Language = t3lib_div::_GP('contentTypo3Language');
		$this->contentTypo3Charset = t3lib_div::_GP('contentTypo3Charset');
		$this->editorNo = t3lib_div::_GP('editorNo');
		$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $this->editorNo ? $this->editorNo : $pRteArr[0];
		$this->contentTypo3Language = $this->contentTypo3Language ? $this->contentTypo3Language : $pRteArr[1];
		$this->contentTypo3Charset = $this->contentTypo3Charset ? $this->contentTypo3Charset : $pRteArr[2];
		$this->RTEtsConfigParams = $this->RTEtsConfigParams ? $this->RTEtsConfigParams : $pArr[2];

			// Find "mode"
		$this->mode=t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode='rte';
		}

			// init fileProcessor
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

			// init hook objects:
		$this->hookObjects = array();
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'])) {
			foreach($TYPO3_CONF_VARS['SC_OPTIONS']['typo3/class.browse_links.php']['browseLinksHook'] as $classData) {
				$processObject = t3lib_div::getUserObj($classData);

				if(!($processObject instanceof t3lib_browseLinksHook)) {
					throw new UnexpectedValueException('$processObject must implement interface t3lib_browseLinksHook', 1195115652);
				}

				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}

			// Site URL
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');	// Current site url

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');

			// CurrentUrl - the current link url must be passed around if it exists
		if ($this->mode=='wizard')	{
			$currentLinkParts = t3lib_div::trimExplode(' ',$this->P['currentValue']);
			$this->curUrlArray = array(
				'target' => $currentLinkParts[1]
			);
			$this->curUrlInfo=$this->parseCurUrl($this->siteURL.'?id='.$currentLinkParts[0],$this->siteURL);
		} else {
			$this->curUrlArray = t3lib_div::_GP('curUrl');
			if ($this->curUrlArray['all'])	{
				$this->curUrlArray=t3lib_div::get_tag_attributes($this->curUrlArray['all']);
			}
			$this->curUrlInfo=$this->parseCurUrl($this->curUrlArray['href'],$this->siteURL);
		}

			// Determine nature of current url:
		$this->act=t3lib_div::_GP('act');
		if (!$this->act)	{
			$this->act=$this->curUrlInfo['act'];
		}

			// Initializing the titlevalue
		$this->setTitle = $LANG->csConvObj->conv($this->curUrlArray['title'], 'utf-8', $LANG->charSet);

			// Rich Text Editor specific configuration:
		$addPassOnParams='';
		$classSelected = array();
		if ((string)$this->mode=='rte')	{
			$RTEtsConfigParts = explode(':',$this->RTEtsConfigParams);
			$addPassOnParams .= '&RTEtsConfigParams='.rawurlencode($this->RTEtsConfigParams);
			$addPassOnParams .= ($this->contentTypo3Language ? '&contentTypo3Language=' . rawurlencode($this->contentTypo3Language) : '');
			$addPassOnParams .= ($this->contentTypo3Charset ? '&contentTypo3Charset=' . rawurlencode($this->contentTypo3Charset) : '');
			$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
			$this->thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
			if (is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.']['link.'])) {
				$this->buttonConfig = $this->thisConfig['buttons.']['link.'];
			}
			if ($this->thisConfig['classesAnchor'] || $this->thisConfig['classesLinks']) {
				$this->setClass = $this->curUrlArray['class'];
				if ($this->thisConfig['classesAnchor']) {
					$classesAnchorArray = t3lib_div::trimExplode(',',$this->thisConfig['classesAnchor'], 1);
				} else {
					$classesAnchorArray = t3lib_div::trimExplode(',',$this->thisConfig['classesLinks'], 1);
				}
				$anchorTypes = array( 'page', 'url', 'file', 'mail', 'spec');
				$classesAnchor = array();
				$classesAnchor['all'] = array();
				if (is_array($RTEsetup['properties']['classesAnchor.'])) {
					foreach ($RTEsetup['properties']['classesAnchor.'] as $label => $conf) {
						if (in_array($conf['class'], $classesAnchorArray)) {
							$classesAnchor['all'][] = $conf['class'];
							if (in_array($conf['type'], $anchorTypes)) {
								$classesAnchor[$conf['type']][] = $conf['class'];
								if (is_array($this->thisConfig['classesAnchor.']) && is_array($this->thisConfig['classesAnchor.']['default.']) && $this->thisConfig['classesAnchor.']['default.'][$conf['type']] == $conf['class']) {
									$this->classesAnchorDefault[$conf['type']] = $conf['class'];
									if ($conf['titleText']) {
										$this->classesAnchorDefaultTitle[$conf['type']] = $this->getLLContent(trim($conf['titleText']));
									}
									if ($conf['target']) {
										$this->classesAnchorDefaultTarget[$conf['type']] = trim($conf['target']);
									}
								}
							}
						}
					}
				}
				foreach ($anchorTypes as $anchorType) {
					foreach ($classesAnchorArray as $class) {
						if (!in_array($class, $classesAnchor['all']) || (in_array($class, $classesAnchor['all']) && is_array($classesAnchor[$anchorType]) && in_array($class, $classesAnchor[$anchorType]))) {
							$selected = '';
							if ($this->setClass == $class || (!$this->setClass && $this->classesAnchorDefault[$anchorType] == $class)) {
								$selected = 'selected="selected"';
								$classSelected[$anchorType] = true;
							}
							$classLabel = (is_array($RTEsetup['properties']['classes.']) && is_array($RTEsetup['properties']['classes.'][$class.'.']) && $RTEsetup['properties']['classes.'][$class.'.']['name']) ? $this->getPageConfigLabel($RTEsetup['properties']['classes.'][$class.'.']['name'], 0) : $class;
							$classStyle = (is_array($RTEsetup['properties']['classes.']) && is_array($RTEsetup['properties']['classes.'][$class.'.']) && $RTEsetup['properties']['classes.'][$class.'.']['value']) ? $RTEsetup['properties']['classes.'][$class.'.']['value'] : '';
							$this->classesAnchorJSOptions[$anchorType] .= '<option ' . $selected . ' value="' .$class . '"' . ($classStyle?' style="'.$classStyle.'"':'') . '>' . $classLabel . '</option>';
						}
					}
					if ($this->classesAnchorJSOptions[$anchorType]) {
						$selected = '';
						if (!$this->setClass && !$this->classesAnchorDefault[$anchorType])  $selected = 'selected="selected"';
						$this->classesAnchorJSOptions[$anchorType] =  '<option ' . $selected . ' value=""></option>' . $this->classesAnchorJSOptions[$anchorType];
					}
				}
			}
		}

			// Initializing the target value (RTE)
			// Unset the target if it is set to a value different than default and if no class is selected and the target field is not displayed
			// In other words, do not forward the target if we changed tab and the target field is not displayed
		$this->setTarget = (isset($this->curUrlArray['target'])
				&& !(
					($this->curUrlArray['target'] != $this->thisConfig['defaultLinkTarget'])
					&& !$classSelected[$this->act]
					&& is_array($this->buttonConfig['targetSelector.']) && $this->buttonConfig['targetSelector.']['disabled'] && is_array($this->buttonConfig['popupSelector.']) && $this->buttonConfig['popupSelector.']['disabled'])
				) ? $this->curUrlArray['target'] : '';
		if ($this->thisConfig['defaultLinkTarget'] && !isset($this->curUrlArray['target']))	{
			$this->setTarget=$this->thisConfig['defaultLinkTarget'];
		}

			// init the DAM object
		$this->initDAM();
		$this->getModSettings();
		$this->processParams();

			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
	}

	function reinitParams() {
		if ($this->editorNo) {
			$pArr = explode('|', $this->bparams);
			$pArr[1] = implode(':', array($this->editorNo, $this->contentTypo3Language, $this->contentTypo3Charset));
			$pArr[2] = $this->RTEtsConfigParams;
			$this->bparams = implode('|', $pArr);
		}
		parent::reinitParams();
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function getJSCode()	{
		global $LANG,$BACK_PATH,$TYPO3_CONF_VARS;

			// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode.= '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Link");
			var HTMLArea = window.parent.HTMLArea;
				// This JavaScript is primarily for RTE/Link. jumpToUrl is used in the other cases as well...
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_class="'.($this->setClass?'&curUrl[class]='.rawurlencode($this->setClass):'').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";

			var cur_href="'.($this->curUrlArray['href']?$this->curUrlArray['href']:'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_class="'.($this->setClass?$this->setClass:'').'";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";

			function setTarget(value)	{
				cur_target=value;
				add_target="&curUrl[target]="+encodeURIComponent(value);
			}
			function setClass(value)	{
				cur_class=value;
				add_class="&curUrl[class]="+encodeURIComponent(value);
			}
			function setTitle(value)	{
				cur_title=value;
				add_title="&curUrl[title]="+encodeURIComponent(value);
			}
			function setValue(value)	{
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}';

				// Functions used, if the link selector is in RTE mode:
			$JScode.='
				function link_typo3Page(id,anchor)	{
					var theLink = \''.$this->siteURL.'?id=\'+id+(anchor?anchor:"");
					if (document.ltargetform.anchor_title) setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_folder(folder)	{	//
					var theLink = \''.$this->siteURL.'\'+folder;
					if (document.ltargetform.anchor_title) setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_spec(theLink)	{	//
					if (document.ltargetform.anchor_title) setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) setTarget(document.ltargetform.ltarget.value);
					plugin.createLink(theLink,cur_target,cur_class,cur_title);
					return false;
				}
				function link_current()	{	//
					if (document.ltargetform.anchor_title) setTitle(document.ltargetform.anchor_title.value);
					if (document.ltargetform.anchor_class) setClass(document.ltargetform.anchor_class.value);
					if (document.ltargetform.ltarget) setTarget(document.ltargetform.ltarget.value);
					if (cur_href!="http://" && cur_href!="mailto:")	{
						plugin.createLink(cur_href,cur_target,cur_class,cur_title);
					}
					return false;
				}
			';

			// General "jumpToUrl" function:
		$JScode.='
			function jumpToUrl(URL,anchor)	{	//
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo='.$this->editorNo.'" : "";
				var add_contentTypo3Language = URL.indexOf("contentTypo3Language=")==-1 ? "&contentTypo3Language='.$this->contentTypo3Language.'" : "";
				var add_contentTypo3Charset = URL.indexOf("contentTypo3Charset=")==-1 ? "&contentTypo3Charset='.$this->contentTypo3Charset.'" : "";
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$this->mode.'" : "";
				var theLocation = URL+add_act+add_editorNo+add_contentTypo3Language+add_contentTypo3Charset+add_mode+add_href+add_target+add_class+add_title+add_params'.($addPassOnParams?'+"'.$addPassOnParams.'"':'').'+(anchor?anchor:"");
				window.location.href = theLocation;
				return false
			}
		';

			// This is JavaScript especially for the TBE Element Browser!
		$pArr = explode('|',$this->bparams);
		$formFieldName = 'data['.$pArr[0].']['.$pArr[1].']['.$pArr[2].']';
		$JScode.='
			var elRef="";
			var targetDoc="";

			function launchView(url)	{	//
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . $BACK_PATH . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
			function setReferences()	{	//
				if (parent.window.opener
				&& parent.window.opener.content
				&& parent.window.opener.content.document.editform
				&& parent.window.opener.content.document.editform["'.$formFieldName.'"]
						) {
					targetDoc = parent.window.opener.content.document;
					elRef = targetDoc.editform["'.$formFieldName.'"];
					return true;
				} else {
					return false;
				}
			}
			function insertElement(table, uid, type, filename, fp, filetype, imagefile, action, close)	{	//
				link_folder(fp.substring('.strlen(PATH_site).'));
				return false;
			}
			function addElement(elName,elValue,altElValue,close)	{	//
				if (parent.window.opener && parent.window.opener.setFormValueFromBrowseWin)	{
					parent.window.opener.setFormValueFromBrowseWin("'.$pArr[0].'",altElValue?altElValue:elValue,elName);
					if (close)	{
						parent.window.opener.focus();
						parent.close();
					}
				} else {
					alert("Error - reference to main window is not set properly!");
					parent.close();
				}
			}
		';

			// Finally, add the accumulated JavaScript to the template object:
		$this->doc->JScodeArray['rtehtmlarea'] = $JScode;
	}

	/**
	 * Return true or false whether thumbs should be displayed or not
	 *
	 * @return	boolean
	 */
	function displayThumbs() {
		global $BE_USER;
		return parent::displayThumbs() && !$BE_USER->getTSConfigVal('options.noThumbsInRTEimageSelect') && ($this->act != 'dragdrop');
	}

	/**
	 * Create HTML checkbox to enable/disable thumbnail display
	 *
	 * @return	string HTML code
	 */
	function addDisplayOptions() {
		global $BE_USER;

			// Getting flag for showing/not showing thumbnails:
		$noThumbs = $BE_USER->getTSConfigVal('options.noThumbsInEB') || ($this->mode == 'rte' && $BE_USER->getTSConfigVal('options.noThumbsInRTEimageSelect')) || ($this->act == 'dragdrop');
		if ($noThumbs)	{
			$thumbNailCheckbox = '';
		} else {

			$thumbNailCheckbox = t3lib_BEfunc::getFuncCheck('', 'SET[displayThumbs]',$this->displayThumbs(), $this->thisScript, t3lib_div::implodeArrayForUrl('',$this->addParams));
			$description = $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_mod_file_list.xml:displayThumbs',1);
			$id = 'l'.uniqid('tx_dam_scbase');
			$idAttr = ' id="'.$id.'"';
			$thumbNailCheckbox = str_replace('<input', '<input'.$idAttr, $thumbNailCheckbox);
			$thumbNailCheckbox .= ' <label for="'.$id.'">'.$description.'</label>';
			$this->damSC->addOption('html', 'thumbnailCheckbox', $thumbNailCheckbox);
		}
		$this->damSC->addOption('funcCheck', 'extendedInfo', $GLOBALS['LANG']->getLL('displayExtendedInfo',1));
	}

	/******************************************************************
	 *
	 * Main functions
	 *
	 ******************************************************************/
	/**
	 * Rich Text Editor (RTE) link selector (MAIN function)
	 * Generates the link selector for the Rich Text Editor.
	 * Can also be used to select links for the TCEforms (see $wiz)
	 *
	 * @param	boolean		If set, the "remove link" is not shown in the menu: Used for the "Select link" wizard which is used by the TCEforms
	 * @return	string		Modified content variable.
	 */
	function main_rte($wiz=0)	{
		global $LANG, $BE_USER, $BACK_PATH;

			// Starting content:
		$content=$this->doc->startPage($LANG->getLL('Insert/Modify Link',1));
		$this->reinitParams();

			// Initializing the action value, possibly removing blinded values etc:
		$this->allowedItems = explode(',','page,file,url,mail,spec,upload');

			// Remove upload tab if filemount is readonly
		if ($this->isReadOnlyFolder(tx_dam::path_makeAbsolute($this->damSC->path))) {
			$this->allowedItems = array_diff($this->allowedItems, array('upload'));
		}
			//call hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$this->allowedItems = $hookObject->addAllowedItems($this->allowedItems);
		}

		if (is_array($this->buttonConfig['options.']) && $this->buttonConfig['options.']['removeItems']) {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->buttonConfig['options.']['removeItems'],1));
		} else {
			$this->allowedItems = array_diff($this->allowedItems,t3lib_div::trimExplode(',',$this->thisConfig['blindLinkOptions'],1));
		}
		reset($this->allowedItems);
		if (!in_array($this->act,$this->allowedItems)) {
			$this->act = current($this->allowedItems);
		}

			// Making menu in top:
		$menuDef = array();
		if (!$wiz && $this->curUrlArray['href'])	{
			$menuDef['removeLink']['isActive'] = $this->act=='removeLink';
			$menuDef['removeLink']['label'] = $LANG->getLL('removeLink',1);
			$menuDef['removeLink']['url'] = '#';
			$menuDef['removeLink']['addParams'] = 'onclick="plugin.unLink();return false;"';
		}
		if (in_array('page',$this->allowedItems)) {
			$menuDef['page']['isActive'] = $this->act=='page';
			$menuDef['page']['label'] = $LANG->getLL('page',1);
			$menuDef['page']['url'] = '#';
			$menuDef['page']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=page&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('file',$this->allowedItems)){
			$menuDef['file']['isActive'] = $this->act=='file';
			$menuDef['file']['label'] =  $LANG->sL('LLL:EXT:dam/mod_main/locallang_mod.xml:mlang_tabs_tab',1);
			$menuDef['file']['url'] = '#';
			$menuDef['file']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=file&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('url',$this->allowedItems)) {
			$menuDef['url']['isActive'] = $this->act=='url';
			$menuDef['url']['label'] = $LANG->getLL('extUrl',1);
			$menuDef['url']['url'] = '#';
			$menuDef['url']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=url&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('mail',$this->allowedItems)) {
			$menuDef['mail']['isActive'] = $this->act=='mail';
			$menuDef['mail']['label'] = $LANG->getLL('email',1);
			$menuDef['mail']['url'] = '#';
			$menuDef['mail']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=mail&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (is_array($this->thisConfig['userLinks.']) && in_array('spec',$this->allowedItems)) {
			$menuDef['spec']['isActive'] = $this->act=='spec';
			$menuDef['spec']['label'] = $LANG->getLL('special',1);
			$menuDef['spec']['url'] = '#';
			$menuDef['spec']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=spec&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}
		if (in_array('upload', $this->allowedItems)) {
			$menuDef['upload']['isActive'] = ($this->act === 'upload');
			$menuDef['upload']['label'] = $LANG->getLL('tx_dam_file_upload.title',1);
			$menuDef['upload']['url'] = '#';
			$menuDef['upload']['addParams'] = 'onclick="jumpToUrl(\''.htmlspecialchars('?act=upload&mode='.$this->mode.'&bparams='.$this->bparams).'\');return false;"';
		}

			// call hook for extra options
		foreach($this->hookObjects as $hookObject) {
			$menuDef = $hookObject->modifyMenuDefinition($menuDef);
		}

		$content .= $this->doc->getTabMenuRaw($menuDef);

			// Adding the menu and header to the top of page:
		$content.=$this->printCurrentUrl($this->curUrlInfo['info']).'<br />';

			// Depending on the current action we will create the actual module content for selecting a link:
		switch($this->act)	{
			case 'mail':
				$extUrl='
			<!--
				Enter mail address:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkMail">
							<tr>
								<td>'.$LANG->getLL('emailAddress',1).':</td>
								<td><input type="text" name="lemail"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='mail'?$this->curUrlInfo['info']:'').'" /> '.
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="setTarget(\'\');setValue(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
				$content.=$this->addAttributesForm();
			break;
			case 'url':
				$extUrl='
			<!--
				Enter External URL:
			-->
					<form action="" name="lurlform" id="lurlform">
						<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkURL">
							<tr>
								<td>URL:</td>
								<td><input type="text" name="lurl"'.$this->doc->formWidth(20).' value="'.htmlspecialchars($this->curUrlInfo['act']=='url'?$this->curUrlInfo['info']:'http://').'" /> '.
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="if (/^[A-Za-z0-9_+]{1,8}:/i.test(document.lurlform.lurl.value)) { setValue(document.lurlform.lurl.value); } else { setValue(\'http://\'+document.lurlform.lurl.value); }; return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
				$content.=$this->addAttributesForm();
			break;
			case 'file':
				$this->addDisplayOptions();
				$content.=$this->addAttributesForm();
				$content.= $this->dam_select($this->allowedFileTypes, $this->disallowedFileTypes);
				$content.= $this->damSC->getOptions();
				break;
			case 'spec':
				if (is_array($this->thisConfig['userLinks.']))	{
					$subcats=array();
					$v=$this->thisConfig['userLinks.'];
					foreach ($v as $k2 => $dummyValue) {
						$k2i = intval($k2);
						if (substr($k2,-1)=='.' && is_array($v[$k2i.'.']))	{

								// Title:
							$title = trim($v[$k2i]);
							if (!$title)	{
								$title=$v[$k2i.'.']['url'];
							} else {
								$title=$LANG->sL($title);
							}
								// Description:
							$description=$v[$k2i.'.']['description'] ? $LANG->sL($v[$k2i.'.']['description'],1).'<br />' : '';

								// URL + onclick event:
							$onClickEvent='';
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="setTarget('".$v[$k2i.'.']['target']."');";
							$v[$k2i.'.']['url'] = str_replace('###_URL###',$this->siteURL,$v[$k2i.'.']['url']);
							if (substr($v[$k2i.'.']['url'],0,7)=="http://" || substr($v[$k2i.'.']['url'],0,7)=='mailto:')	{
								$onClickEvent.="cur_href=unescape('".rawurlencode($v[$k2i.'.']['url'])."');link_current();";
							} else {
								$onClickEvent.="link_spec(unescape('".$this->siteURL.rawurlencode($v[$k2i.'.']['url'])."'));";
							}

								// Link:
							$A=array('<a href="#" onclick="'.htmlspecialchars($onClickEvent).'return false;">','</a>');

								// Adding link to menu of user defined links:
							$subcats[$k2i]='
								<tr>
									<td class="bgColor4">'.$A[0].'<strong>'.htmlspecialchars($title).($this->curUrlInfo['info']==$v[$k2i.'.']['url']?'<img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" />':'').'</strong><br />'.$description.$A[1].'</td>
								</tr>';
						}
					}

						// Sort by keys:
					ksort($subcats);

						// Add menu to content:
					$content.= '
			<!--
				Special userdefined menu:
			-->
						<table border="0" cellpadding="1" cellspacing="1" id="typo3-linkSpecial">
							<tr>
								<td class="bgColor5" class="c-wCell" valign="top"><strong>'.$LANG->getLL('special',1).'</strong></td>
							</tr>
							'.implode('',$subcats).'
						</table>
						';
				}
			break;
			case 'page':
				$content.=$this->addAttributesForm();

				$pagetree = t3lib_div::makeInstance('tx_rtehtmlarea_pageTree');
				$pagetree->ext_showNavTitle = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showNavTitle');
				$pagetree->addField('nav_title');
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();
				$content.= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($LANG->getLL('pageTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$cElements.'</td>
						</tr>
					</table>
					';
			break;
			case 'upload':
				$content.= $this->dam_upload($this->allowedFileTypes, $this->disallowedFileTypes);
				$content.= $this->damSC->getOptions();
				$content.='<br /><br />';
				if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB'))	{
					$content.= $this->createFolder(tx_dam::path_makeAbsolute($this->damSC->path));
				}
			break;
			default:
					// call hook
				foreach($this->hookObjects as $hookObject) {
					$content .= $hookObject->getTab($this->act);
				}

			break;
		}

			// End page, return content:
		$content.= $this->doc->endPage();
		$this->getJSCode();
		$content = $this->damSC->doc->insertStylesAndJS($content);
		return $content;
	}

	function addAttributesForm() {
		$ltargetForm = '';
			// Add page id, target, class selector box and title field:
		$lpageId = $this->addPageIdSelector();
		$ltarget = $this->addTargetSelector();
		$lclass = $this->addClassSelector();
		$ltitle = $this->addTitleSelector();
		if ($lpageId || $ltarget || $lclass || $ltitle) {
			$ltargetForm = $this->wrapInForm($lpageId.$ltarget.$lclass.$ltitle);
		}
		return $ltargetForm;
	}

	function wrapInForm($string) {
		global $LANG;

		$form = '
			<!--
				Selecting target for link:
			-->
				<form action="" name="ltargetform" id="ltargetform">
					<table border="0" cellpadding="2" cellspacing="1" id="typo3-linkTarget">'. $string;
		if ((($this->act == 'page' && $this->curUrlInfo['act']=='page') || ($this->act == 'file' && $this->curUrlInfo['act']=='file') || ($this->act == 'url' && $this->curUrlInfo['act']!='page')) && $this->curUrlArray['href']) {
			$form .='
						<tr>
							<td>
							</td>
							<td colspan="3">
								<input type="submit" value="'.$LANG->getLL('update',1).'" onclick="return link_current();" />
							</td>
						</tr>';
		}
		$form .= '
					</table>
				</form>';
		return $form;
	}

	function addPageIdSelector() {
		global $LANG;

		return ($this->act == 'page' && $this->buttonConfig && is_array($this->buttonConfig['pageIdSelector.']) && $this->buttonConfig['pageIdSelector.']['enabled'])?'
						<tr>
							<td>'.$LANG->getLL('page_id',1).':</td>
							<td colspan="3">
								<input type="text" size="6" name="luid" />&nbsp;<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="return link_typo3Page(document.ltargetform.luid.value);" />
							</td>
						</tr>':'';
	}

	function addTargetSelector() {
		global $LANG;

		$targetSelectorConfig = array();
		$popupSelectorConfig = array();
		if (is_array($this->buttonConfig['targetSelector.'])) {
			$targetSelectorConfig = $this->buttonConfig['targetSelector.'];
		}
		if (is_array($this->buttonConfig['popupSelector.'])) {
			$popupSelectorConfig = $this->buttonConfig['popupSelector.'];
		}

		$ltarget = '';
		if ($this->act != 'mail')	{
			$ltarget .= '
					<tr id="ltargetrow"'. (($targetSelectorConfig['disabled'] && $popupSelectorConfig['disabled']) ? ' style="display: none;"' : '') . '>
						<td>'.$LANG->getLL('target',1).':</td>
						<td><input type="text" name="ltarget" onchange="setTarget(this.value);" value="'.htmlspecialchars($this->setTarget?$this->setTarget:(($this->setClass || !$this->classesAnchorDefault[$this->act])?'':$this->classesAnchorDefaultTarget[$this->act])).'"'.$this->doc->formWidth(10).' /></td>';
			$ltarget .= '
						<td colspan="2">';
			if (!$targetSelectorConfig['disabled']) {
				$ltarget .= '
							<select name="ltarget_type" onchange="setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
								<option></option>
								<option value="_top">'.$LANG->getLL('top',1).'</option>
								<option value="_blank">'.$LANG->getLL('newWindow',1).'</option>
							</select>';
			}
			$ltarget .= '
						</td>
					</tr>';
			if (!$popupSelectorConfig['disabled']) {

				$selectJS = 'if (document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value>0 && document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value>0)	{
					document.ltargetform.ltarget.value = document.ltargetform.popup_width.options[document.ltargetform.popup_width.selectedIndex].value+\'x\'+document.ltargetform.popup_height.options[document.ltargetform.popup_height.selectedIndex].value;
					setTarget(document.ltargetform.ltarget.value);
					document.ltargetform.popup_width.selectedIndex=0;
					document.ltargetform.popup_height.selectedIndex=0;
				}';

				$ltarget.='
						<tr>
							<td>'.$LANG->getLL('target_popUpWindow',1).':</td>
							<td colspan="3">
								<select name="popup_width" onchange="'.$selectJS.'">
									<option value="0">'.$LANG->getLL('target_popUpWindow_width',1).'</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
									<option value="700">700</option>
									<option value="800">800</option>
								</select>
								x
								<select name="popup_height" onchange="'.$selectJS.'">
									<option value="0">'.$LANG->getLL('target_popUpWindow_height',1).'</option>
									<option value="200">200</option>
									<option value="300">300</option>
									<option value="400">400</option>
									<option value="500">500</option>
									<option value="600">600</option>
								</select>
							</td>
						</tr>';
			}
		}
		return $ltarget;
	}

	function addClassSelector() {
		global $LANG;

		$selectClass = '';
		if ($this->classesAnchorJSOptions[$this->act]) {
			$selectClassJS = '
					if (document.ltargetform.anchor_class) {
						document.ltargetform.anchor_class.value = document.ltargetform.anchor_class.options[document.ltargetform.anchor_class.selectedIndex].value;
						if (document.ltargetform.anchor_class.value && HTMLArea.classesAnchorSetup) {
							for (var i = HTMLArea.classesAnchorSetup.length; --i >= 0;) {
								var anchorClass = HTMLArea.classesAnchorSetup[i];
								if (anchorClass[\'name\'] == document.ltargetform.anchor_class.value) {
									if (anchorClass[\'titleText\'] && document.ltargetform.anchor_title) {
										document.ltargetform.anchor_title.value = anchorClass[\'titleText\'];
										setTitle(anchorClass[\'titleText\']);
									}
									if (anchorClass[\'target\']) {
										if (document.ltargetform.ltarget) {
											document.ltargetform.ltarget.value = anchorClass[\'target\'];
										}
										setTarget(anchorClass[\'target\']);
									} else if (document.ltargetform.ltarget && document.getElementById(\'ltargetrow\').style.display == \'none\') {
											// Reset target to default if field is not displayed and class has no configured target
										document.ltargetform.ltarget.value = \''. ($this->thisConfig['defaultLinkTarget']?$this->thisConfig['defaultLinkTarget']:'') .'\';
										setTarget(document.ltargetform.ltarget.value);
									}
									break;
								}
							}
						}
						setClass(document.ltargetform.anchor_class.value);
					}
				';
			$selectClass ='
						<tr>
							<td>'.$LANG->getLL('anchor_class',1).':</td>
							<td colspan="3">
								<select name="anchor_class" onchange="'.$selectClassJS.'">
									' . $this->classesAnchorJSOptions[$this->act] . '
								</select>
							</td>
						</tr>';
		}
		return $selectClass;
	}

	function addTitleSelector() {
		global $LANG;

		return '
						<tr>
							<td>'.$LANG->getLL('anchor_title',1).':</td>
							<td colspan="3">
								<input type="text" name="anchor_title" value="' . ($this->setTitle?$this->setTitle:(($this->setClass || !$this->classesAnchorDefault[$this->act])?'':$this->classesAnchorDefaultTitle[$this->act])) . '" ' . $this->doc->formWidth(30) . ' />
							</td>
						</tr>';
	}

	/**
	 * Localize a string using the language of the content element rather than the language of the BE interface
	 *
	 * @param	string		$string: the label to be localized
	 * @return	string		Localized string.
	 */
	public function getLLContent($string) {
		global $LANG;

		$BE_lang = $LANG->lang;
		$BE_origCharSet = $LANG->origCharSet;
		$BE_charSet = $LANG->charSet;

		$LANG->lang = $this->contentTypo3Language;
		$LANG->origCharSet = $LANG->csConvObj->charSetArray[$this->contentTypo3Language];
		$LANG->origCharSet = $LANG->origCharSet ? $LANG->origCharSet : 'iso-8859-1';
		$LANG->charSet = $this->contentTypo3Charset;
		$LLString = $LANG->sL($string);

		$LANG->lang = $BE_lang;
		$LANG->origCharSet = $BE_origCharSet;
		$LANG->charSet = $BE_charSet;
		return $LLString;
	}

	/**
	 * Localize a label obtained from Page TSConfig
	 *
	 * @param	string		string: the label to be localized
	 * @return	string		Localized string.
	 */
	public function getPageConfigLabel($string,$JScharCode=1) {
		global $LANG;
		if (strcmp(substr($string,0,4),'LLL:')) {
			$label = $string;
		} else {
			$label = $LANG->sL(trim($string));
		}
		$label = str_replace('"', '\"', str_replace('\\\'', '\'', $label));
		$label = $JScharCode ? $LANG->JScharCode($label): $label;
		return $label;
	}

}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_dam_browse_links.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_dam_browse_links.php']);
}

?>