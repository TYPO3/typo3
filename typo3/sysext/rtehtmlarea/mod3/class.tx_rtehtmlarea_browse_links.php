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
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 */

require_once (PATH_typo3.'class.browse_links.php');


/**
 * Class which generates the page tree
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_pageTree extends rtePageTree {

	/**
	 * Create the page navigation tree in HTML
	 *
	 * @param	array		Tree array
	 * @return	string		HTML output.
	 */
	function printTree($treeArr='')	{
		global $BACK_PATH;
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);
		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass = ($c+1)%2 ? 'bgColor' : 'bgColor-10';
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act']=='page' && $GLOBALS['SOBE']->browser->curUrlInfo['pageid']==$v['row']['uid'] && $GLOBALS['SOBE']->browser->curUrlInfo['pageid'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}

			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&editorNo='.$GLOBALS['SOBE']->browser->editorNo.'&contentTypo3Language='.$GLOBALS['SOBE']->browser->contentTypo3Language.'&contentTypo3Charset='.$GLOBALS['SOBE']->browser->contentTypo3Charset.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandPage='.$v['row']['uid'].'\');';
			$cEbullet = $this->ext_isLinkable($v['row']['doktype'],$v['row']['uid']) ?
						'<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' :
						'';
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap"'.($v['row']['_CSSCLASS'] ? ' class="'.$v['row']['_CSSCLASS'].'"' : '').'>'.
					$v['HTML'].
					$this->wrapTitle($this->getTitleStr($v['row'],$titleLen),$v['row'],$this->ext_pArrPages).
					'</td>'.
					$arrCol.
					'<td>'.$cEbullet.'</td>
				</tr>';
		}
		$out='


			<!--
				Navigation Page Tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				'.$out.'
			</table>';
		return $out;
	}
}

/**
 * Base extension class which generates the folder tree.
 * Used directly by the RTE.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_folderTree extends rteFolderTree {

	/**
	 * Wrapping the title in a link, if applicable.
	 *
	 * @param	string		Title, ready for output.
	 * @param	array		The "record"
	 * @return	string		Wrapping title string.
	 */
	function wrapTitle($title,$v)	{
		$title = htmlspecialchars($title);
		
		if ($this->ext_isLinkable($v))	{
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&editorNo='.$GLOBALS['SOBE']->browser->editorNo.'&contentTypo3Language='.$GLOBALS['SOBE']->browser->contentTypo3Language.'&contentTypo3Charset='.$GLOBALS['SOBE']->browser->contentTypo3Charset.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['path']).'\');';
			return '<a href="#" onclick="'.htmlspecialchars($aOnClick).'">'.$title.'</a>';
		} else {
			return '<span class="typo3-dimmed">'.$title.'</span>';
		}
	}

	/**
	 * Create the folder navigation tree in HTML
	 *
	 * @param	mixed		Input tree array. If not array, then $this->tree is used.
	 * @return	string		HTML output of the tree.
	 */
	function printTree($treeArr='')	{
		global $BACK_PATH;
		$titleLen=intval($GLOBALS['BE_USER']->uc['titleLen']);

		if (!is_array($treeArr))	$treeArr=$this->tree;

		$out='';
		$c=0;

			// Preparing the current-path string (if found in the listing we will see a red blinking arrow).
		if (!$GLOBALS['SOBE']->browser->curUrlInfo['value'])	{
			$cmpPath='';
		} else if (substr(trim($GLOBALS['SOBE']->browser->curUrlInfo['info']),-1)!='/')	{
			$cmpPath=PATH_site.dirname($GLOBALS['SOBE']->browser->curUrlInfo['info']).'/';
		} else {
			$cmpPath=PATH_site.$GLOBALS['SOBE']->browser->curUrlInfo['info'];
		}

			// Traverse rows for the tree and print them into table rows:
		foreach($treeArr as $k => $v)	{
			$c++;
			$bgColorClass=($c+1)%2 ? 'bgColor' : 'bgColor-10';

				// Creating blinking arrow, if applicable:
			if ($GLOBALS['SOBE']->browser->curUrlInfo['act']=='file' && $cmpPath==$v['row']['path'])	{
				$arrCol='<td><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/blinkarrow_right.gif','width="5" height="9"').' class="c-blinkArrowR" alt="" /></td>';
				$bgColorClass='bgColor4';
			} else {
				$arrCol='<td></td>';
			}
				// Create arrow-bullet for file listing (if folder path is linkable):
			$aOnClick = 'return jumpToUrl(\''.$this->thisScript.'?act='.$GLOBALS['SOBE']->browser->act.'&editorNo='.$GLOBALS['SOBE']->browser->editorNo.'&contentTypo3Language='.$GLOBALS['SOBE']->browser->contentTypo3Language.'&contentTypo3Charset='.$GLOBALS['SOBE']->browser->contentTypo3Charset.'&mode='.$GLOBALS['SOBE']->browser->mode.'&expandFolder='.rawurlencode($v['row']['path']).'\');';
			$cEbullet = $this->ext_isLinkable($v['row']) ? '<a href="#" onclick="'.htmlspecialchars($aOnClick).'"><img'.t3lib_iconWorks::skinImg($BACK_PATH,'gfx/ol/arrowbullet.gif','width="18" height="16"').' alt="" /></a>' : '';

				// Put table row with folder together:
			$out.='
				<tr class="'.$bgColorClass.'">
					<td nowrap="nowrap">'.$v['HTML'].$this->wrapTitle(t3lib_div::fixed_lgd_cs($v['row']['title'],$titleLen),$v['row']).'</td>
					'.$arrCol.'
					<td>'.$cEbullet.'</td>
				</tr>';
		}

		$out='

			<!--
				Folder tree:
			-->
			<table border="0" cellpadding="0" cellspacing="0" id="typo3-tree">
				'.$out.'
			</table>';
		return $out;
	}

}

/**
 * Script class for the Element Browser window.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class tx_rtehtmlarea_browse_links extends browse_links {

	var $editorNo;
	var $contentTypo3Language;
	var $contentTypo3Charset;
	public $additionalAttributes = array();
	public $buttonConfig = array();
	public $RTEProperties = array();

	public $anchorTypes = array( 'page', 'url', 'file', 'mail', 'spec');
	public $classesAnchorDefault = array();
	public $classesAnchorDefaultTitle = array();
	public $classesAnchorClassTitle = array();
	public $classesAnchorDefaultTarget = array();
	public $classesAnchorJSOptions = array();

	public $allowedItems;

	/**
	 * Constructor:
	 * Initializes a lot of variables, setting JavaScript functions in header etc.
	 *
	 * @return	void
	 */
	function init()	{

		$this->initVariables();
		$this->initConfiguration();

			// Creating backend template object:
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
			// Loading the Prototype library and browse_links.js
		$this->doc->getPageRenderer()->loadPrototype();
		$this->doc->loadJavascriptLib('js/browse_links.js');
			// Adding context menu code
		$this->doc->getContextMenuCode();
			// Init fileProcessor
		$this->fileProcessor = t3lib_div::makeInstance('t3lib_basicFileFunctions');
		$this->fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);

			// Initializing hooking browsers
		$this->initHookObjects('ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php');

			// CurrentUrl - the current link url must be passed around if it exists
		$this->curUrlArray = t3lib_div::_GP('curUrl');
		if ($this->curUrlArray['all'])	{
			$this->curUrlArray = t3lib_div::get_tag_attributes($this->curUrlArray['all']);
			$this->curUrlArray['href'] = htmlspecialchars_decode($this->curUrlArray['href']);
		}
			// Note: parseCurUrl will invoke the hooks
		$this->curUrlInfo = $this->parseCurUrl($this->curUrlArray['href'],$this->siteURL);
		if (isset($this->curUrlArray['external']) && $this->curUrlInfo['act'] != 'mail') {
			$this->curUrlInfo['act'] = 'url';
			$this->curUrlInfo['info'] = $this->curUrlArray['href'];
		}
			// Determine nature of current url:
		$this->act = t3lib_div::_GP('act');
		if (!$this->act)	{
			$this->act=$this->curUrlInfo['act'];
		}
			// Setting intial values for link attributes
		$this->initLinkAttributes();

			// Add attributes to body tag. Note: getBodyTagAdditions will invoke the hooks
		$this->doc->bodyTagAdditions = $this->getBodyTagAdditions();
			// Adding RTE JS code
		$this->doc->JScodeArray['rtehtmlarea'] = $this->getJSCode();
	}

	/**
	 * Initialize class variables
	 *
	 * @return	void
	 */
	public function initVariables() {

			// Process bparams
		$this->bparams = t3lib_div::_GP('bparams');
		$pArr = explode('|', $this->bparams);
		$pRteArr = explode(':', $pArr[1]);
		$this->editorNo = $pRteArr[0];
		$this->contentTypo3Language = $pRteArr[1];
		$this->contentTypo3Charset = $pRteArr[2];
		$this->RTEtsConfigParams = $pArr[2];
		if (!$this->editorNo) {
			$this->editorNo = t3lib_div::_GP('editorNo');
			$this->contentTypo3Language = t3lib_div::_GP('contentTypo3Language');
			$this->contentTypo3Charset = t3lib_div::_GP('contentTypo3Charset');
			$this->RTEtsConfigParams = t3lib_div::_GP('RTEtsConfigParams');
		}
		$this->pointer = t3lib_div::_GP('pointer');
		$this->expandPage = t3lib_div::_GP('expandPage');
		$this->expandFolder = t3lib_div::_GP('expandFolder');
		$this->P = t3lib_div::_GP('P');
		$this->PM = t3lib_div::_GP('PM');
		$pArr[1] = implode(':', array($this->editorNo, $this->contentTypo3Language, $this->contentTypo3Charset));
		$pArr[2] = $this->RTEtsConfigParams;
		$this->bparams = implode('|', $pArr);

			// Find "mode"
		$this->mode = t3lib_div::_GP('mode');
		if (!$this->mode)	{
			$this->mode = 'rte';
		}
			// Current site url
		$this->siteURL = t3lib_div::getIndpEnv('TYPO3_SITE_URL');

			// the script to link to
		$this->thisScript = t3lib_div::getIndpEnv('SCRIPT_NAME');
	}

	/**
	 * Initializes the configuration variables
	 *
	 * @return	void
	 */
	 public function initConfiguration() {
		$this->thisConfig = $this->getRTEConfig();
		$this->buttonConfig = $this->getButtonConfig('link');
	 }

	/**
	 * Get the RTE configuration from Page TSConfig
	 *
	 * @return	array		RTE configuration array
	 */
	protected function getRTEConfig()	{
		global $BE_USER;

		$RTEtsConfigParts = explode(':', $this->RTEtsConfigParams);
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$this->RTEProperties = $RTEsetup['properties'];
		return t3lib_BEfunc::RTEsetup($this->RTEProperties, $RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
	}

	/**
	 * Get the configuration of the button
	 *
	 * @param	string		$buttonName: the name of the button
	 * @return	array		the configuration array of the image button
	 */
	protected function getButtonConfig($buttonName)	{
		return ((is_array($this->thisConfig['buttons.']) && is_array($this->thisConfig['buttons.'][$buttonName.'.'])) ? $this->thisConfig['buttons.'][$buttonName.'.'] : array());
	}

	/**
	 * Initialize hook objects implementing interface t3lib_browseLinksHook
	 * @param	string		$hookKey: the hook key
	 * @return	void
	 */
	protected function initHookObjects($hookKey) {
		global $TYPO3_CONF_VARS;
		if (is_array($TYPO3_CONF_VARS['SC_OPTIONS'][$hookKey]['browseLinksHook'])) {
			foreach ($TYPO3_CONF_VARS['SC_OPTIONS'][$hookKey]['browseLinksHook'] as $classData) {
				$processObject = t3lib_div::getUserObj($classData);
				if(!($processObject instanceof t3lib_browseLinksHook)) {
					throw new UnexpectedValueException('$processObject must implement interface t3lib_browseLinksHook', 1195115652);
				}
				$parameters = array();
				$processObject->init($this, $parameters);
				$this->hookObjects[] = $processObject;
			}
		}
	}

	/**
	 * Initialize the current or default values of the link attributes
	 *
	 * @return	void
	 */
	protected function initLinkAttributes() {

			// Initializing the title value
		$this->setTitle = $GLOBALS['LANG']->csConvObj->conv($this->curUrlArray['title'], 'utf-8', $GLOBALS['LANG']->charSet);

			// Processing the classes configuration
		$classSelected = array();
		if ($this->thisConfig['classesAnchor'] || $this->thisConfig['classesLinks']) {
			$this->setClass = $this->curUrlArray['class'];
			if ($this->thisConfig['classesAnchor']) {
				$classesAnchorArray = t3lib_div::trimExplode(',',$this->thisConfig['classesAnchor'], 1);
			} else {
				$classesAnchorArray = t3lib_div::trimExplode(',',$this->thisConfig['classesLinks'], 1);
			}
				// Collecting allowed classes and configured default values
			$classesAnchor = array();
			$classesAnchor['all'] = array();
			$titleReadOnly = $this->buttonConfig['properties.']['title.']['readOnly'] || $this->buttonConfig[$this->act.'.']['properties.']['title.']['readOnly'];
			if (is_array($this->RTEProperties['classesAnchor.'])) {
				foreach ($this->RTEProperties['classesAnchor.'] as $label => $conf) {
					if (in_array($conf['class'], $classesAnchorArray)) {
						$classesAnchor['all'][] = $conf['class'];
						if (in_array($conf['type'], $this->anchorTypes)) {
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
						if ($titleReadOnly && $conf['titleText']) {
							$this->classesAnchorClassTitle[$conf['class']] = $this->classesAnchorDefaultTitle[$conf['type']] = $this->getLLContent(trim($conf['titleText']));
						}
					}
				}
			}
				// Constructing the class selector options
			foreach ($this->anchorTypes as $anchorType) {
				foreach ($classesAnchorArray as $class) {
					if (!in_array($class, $classesAnchor['all']) || (in_array($class, $classesAnchor['all']) && is_array($classesAnchor[$anchorType]) && in_array($class, $classesAnchor[$anchorType]))) {
						$selected = '';
						if ($this->setClass == $class || (!$this->setClass && $this->classesAnchorDefault[$anchorType] == $class)) {
							$selected = 'selected="selected"';
							$classSelected[$anchorType] = true;
						}
						$classLabel = (is_array($this->RTEProperties['classes.']) && is_array($this->RTEProperties['classes.'][$class.'.']) && $this->RTEProperties['classes.'][$class.'.']['name']) ? $this->getPageConfigLabel($this->RTEProperties['classes.'][$class.'.']['name'], 0) : $class;
						$classStyle = (is_array($this->RTEProperties['classes.']) && is_array($this->RTEProperties['classes.'][$class.'.']) && $this->RTEProperties['classes.'][$class.'.']['value']) ? $this->RTEProperties['classes.'][$class.'.']['value'] : '';
						$this->classesAnchorJSOptions[$anchorType] .= '<option ' . $selected . ' value="' .$class . '"' . ($classStyle?' style="'.$classStyle.'"':'') . '>' . $classLabel . '</option>';
					}
				}
				if ($this->classesAnchorJSOptions[$anchorType] && !($this->buttonConfig['properties.']['class.']['required'] || $this->buttonConfig[$this->act.'.']['properties.']['class.']['required'])) {
					$selected = '';
					if (!$this->setClass && !$this->classesAnchorDefault[$anchorType])  $selected = 'selected="selected"';
					$this->classesAnchorJSOptions[$anchorType] =  '<option ' . $selected . ' value=""></option>' . $this->classesAnchorJSOptions[$anchorType];
				}
			}
		}
			// Initializing the target value
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
			// Initializing additional attributes
		if ($GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes']) {
			$addAttributes = t3lib_div::trimExplode(',', $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['rtehtmlarea']['plugins']['TYPO3Link']['additionalAttributes'], 1);
			foreach ($addAttributes as $attribute) {
				$this->additionalAttributes[$attribute] = isset($this->curUrlArray[$attribute]) ? $this->curUrlArray[$attribute] : '';
			}
		}
	}

	/**
	 * Provide the additional parameters to be included in the template body tag
	 *
	 * @return	string		the body tag additions
	 */
	public function getBodyTagAdditions() {
		$bodyTagAdditions = array();
			// call hook for extra additions
		foreach ($this->hookObjects as $hookObject) {
			if (method_exists($hookObject, 'addBodyTagAdditions')) {
				$bodyTagAdditions = $hookObject->addBodyTagAdditions($bodyTagAdditions);
			}
		}
		return t3lib_div::implodeAttributes($bodyTagAdditions, TRUE);
	}

	/**
	 * Generate JS code to be used on the link insert/modify dialogue
	 *
	 * @return	string		the generated JS code
	 */
	function getJSCode()	{
		global $BACK_PATH;
			// BEGIN accumulation of header JavaScript:
		$JScode = '';
		$JScode.= '
			var plugin = window.parent.RTEarea["' . $this->editorNo . '"].editor.getPlugin("TYPO3Link");
			var HTMLArea = window.parent.HTMLArea;
			var add_href="'.($this->curUrlArray['href']?'&curUrl[href]='.rawurlencode($this->curUrlArray['href']):'').'";
			var add_target="'.($this->setTarget?'&curUrl[target]='.rawurlencode($this->setTarget):'').'";
			var add_class="'.($this->setClass?'&curUrl[class]='.rawurlencode($this->setClass):'').'";
			var add_title="'.($this->setTitle?'&curUrl[title]='.rawurlencode($this->setTitle):'').'";
			var add_params="'.($this->bparams?'&bparams='.rawurlencode($this->bparams):'').'";
			var additionalValues = ' . (count($this->additionalAttributes) ? json_encode($this->additionalAttributes) : '{}') . ';';

			// Attributes setting functions
		$JScode.= '
			var cur_href="'.($this->curUrlArray['href'] ? ($this->curUrlInfo['query'] ? substr($this->curUrlArray['href'], 0, -strlen($this->curUrlInfo['query'])) :$this->curUrlArray['href']):'').'";
			var cur_target="'.($this->setTarget?$this->setTarget:'').'";
			var cur_class="'.($this->setClass?$this->setClass:'').'";
			var cur_title="'.($this->setTitle?$this->setTitle:'').'";

			function browse_links_setTarget(value)	{
				cur_target=value;
				add_target="&curUrl[target]="+encodeURIComponent(value);
			}
			function browse_links_setClass(value)	{
				cur_class=value;
				add_class="&curUrl[class]="+encodeURIComponent(value);
			}
			function browse_links_setTitle(value)	{
				cur_title=value;
				add_title="&curUrl[title]="+encodeURIComponent(value);
			}
			function browse_links_setHref(value)	{
				cur_href=value;
				add_href="&curUrl[href]="+value;
			}
			function browse_links_setAdditionalValue(name, value) {
				additionalValues[name] = value;
			}
		';
			// Link setting functions
		$JScode.='
			function link_typo3Page(id,anchor) {
				var parameters = (document.ltargetform.query_parameters && document.ltargetform.query_parameters.value) ? (document.ltargetform.query_parameters.value.charAt(0) == "&" ? "" : "&") + document.ltargetform.query_parameters.value : "";
				var theLink = \'' . $this->siteURL . '?id=\' + id + parameters + (anchor ? anchor : "");
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_folder(folder) {
				var theLink = \''.$this->siteURL.'\'+folder;
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_spec(theLink) {
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				plugin.createLink(theLink,cur_target,cur_class,cur_title,additionalValues);
				return false;
			}
			function link_current()	{
				var parameters = (document.ltargetform.query_parameters && document.ltargetform.query_parameters.value) ? (document.ltargetform.query_parameters.value.charAt(0) == "&" ? "" : "&") + document.ltargetform.query_parameters.value : "";
				if (document.ltargetform.anchor_title) browse_links_setTitle(document.ltargetform.anchor_title.value);
				if (document.ltargetform.anchor_class) browse_links_setClass(document.ltargetform.anchor_class.value);
				if (document.ltargetform.ltarget) browse_links_setTarget(document.ltargetform.ltarget.value);
				if (document.ltargetform.lrel) browse_links_setAdditionalValue("rel", document.ltargetform.lrel.value);
				if (cur_href!="http://" && cur_href!="mailto:")	{
					plugin.createLink(cur_href + parameters,cur_target,cur_class,cur_title,additionalValues);
				}
				return false;
			}
		';
			// General "jumpToUrl" and launchView functions:
		$JScode.='
			function jumpToUrl(URL,anchor) {
				var add_editorNo = URL.indexOf("editorNo=")==-1 ? "&editorNo='.$this->editorNo.'" : "";
				var add_contentTypo3Language = URL.indexOf("contentTypo3Language=")==-1 ? "&contentTypo3Language='.$this->contentTypo3Language.'" : "";
				var add_contentTypo3Charset = URL.indexOf("contentTypo3Charset=")==-1 ? "&contentTypo3Charset='.$this->contentTypo3Charset.'" : "";
				var add_act = URL.indexOf("act=")==-1 ? "&act='.$this->act.'" : "";
				var add_mode = URL.indexOf("mode=")==-1 ? "&mode='.$this->mode.'" : "";
				var add_additionalValues = "";
				if (plugin.pageTSConfiguration && plugin.pageTSConfiguration.additionalAttributes) {
					var additionalAttributes = plugin.pageTSConfiguration.additionalAttributes.split(",");
					for (var i = additionalAttributes.length; --i >= 0;) {
						if (additionalValues[additionalAttributes[i]] != "") {
							add_additionalValues += "&curUrl[" + additionalAttributes[i] + "]=" + encodeURIComponent(additionalValues[additionalAttributes[i]]);
						}
					}
				}
				var theLocation = URL+add_act+add_editorNo+add_contentTypo3Language+add_contentTypo3Charset+add_mode+add_href+add_target+add_class+add_title+add_additionalValues+add_params+(anchor?anchor:"");
				window.location.href = theLocation;
				return false;
			}
			function launchView(url) {
				var thePreviewWindow="";
				thePreviewWindow = window.open("' . $GLOBALS['BACK_PATH'] . 'show_item.php?table="+url,"ShowItem","height=300,width=410,status=0,menubar=0,resizable=0,location=0,directories=0,scrollbars=1,toolbar=0");
				if (thePreviewWindow && thePreviewWindow.focus)	{
					thePreviewWindow.focus();
				}
			}
		';
		return $JScode;
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

			// Initializing the action value, possibly removing blinded values etc:
		$this->allowedItems = explode(',','page,file,url,mail,spec');

			// Calling hook for extra options
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
			$menuDef['file']['label'] = $LANG->getLL('file',1);
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
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="browse_links_setTarget(\'\');browse_links_setHref(\'mailto:\'+document.lurlform.lemail.value); return link_current();" /></td>
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
									'<input type="submit" value="'.$LANG->getLL('setLink',1).'" onclick="if (/^[A-Za-z0-9_+]{1,8}:/.test(document.lurlform.lurl.value)) { browse_links_setHref(document.lurlform.lurl.value); } else { browse_links_setHref(\'http://\'+document.lurlform.lurl.value); }; browse_links_setAdditionalValue(\'external\', \'1\'); return link_current();" /></td>
							</tr>
						</table>
					</form>';
				$content.=$extUrl;
				$content.=$this->addAttributesForm();
			break;
			case 'file':
				$content.=$this->addAttributesForm();

				$foldertree = t3lib_div::makeInstance('tx_rtehtmlarea_folderTree');
				$tree=$foldertree->getBrowsableTree();

				if (!$this->curUrlInfo['value'] || $this->curUrlInfo['act']!='file')	{
					$cmpPath='';
				} elseif (substr(trim($this->curUrlInfo['info']),-1)!='/')	{
					$cmpPath=PATH_site.dirname($this->curUrlInfo['info']).'/';
					if (!isset($this->expandFolder)) $this->expandFolder = $cmpPath;
				} else {
					$cmpPath=PATH_site.$this->curUrlInfo['info'];
				}

				list(,,$specUid) = explode('_',$this->PM);
				$files = $this->expandFolder($foldertree->specUIDmap[$specUid]);

					// Create upload/create folder forms, if a path is given:
				if ($BE_USER->getTSConfigVal('options.uploadFieldsInTopOfEB')) {
					$path=$this->expandFolder;
					if (!$path || !@is_dir($path))	{
						$path = $this->fileProcessor->findTempFolder().'/';	// The closest TEMP-path is found
					}
					if ($path!='/' && @is_dir($path)) {
						$uploadForm=$this->uploadForm($path);
						$createFolder=$this->createFolder($path);
					} else {
						$createFolder='';
						$uploadForm='';
					}
					$content.=$uploadForm;
					if ($BE_USER->isAdmin() || $BE_USER->getTSConfigVal('options.createFoldersInEB')) {
						$content.=$createFolder;
					}
				}



				$content.= '
			<!--
			Wrapper table for folder tree / file list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkFiles">
						<tr>
							<td class="c-wCell" valign="top">'.$this->barheader($LANG->getLL('folderTree').':').$tree.'</td>
							<td class="c-wCell" valign="top">'.$files.'</td>
						</tr>
					</table>
					';
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
							if (isset($v[$k2i.'.']['target']))	$onClickEvent.="browse_links_setTarget('".$v[$k2i.'.']['target']."');";
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
				$pagetree->ext_showPageId = $GLOBALS['BE_USER']->getTSConfigVal('options.pageTree.showPageIdWithTitle');
				$pagetree->addField('nav_title');
				$tree=$pagetree->getBrowsableTree();
				$cElements = $this->expandPage();


				// Outputting Temporary DB mount notice:
				if (intval($GLOBALS['BE_USER']->getSessionData('pageTree_temporaryMountPoint')))	{
					$link = '<a href="' . htmlspecialchars(t3lib_div::linkThisScript(array('setTempDBmount' => 0))) . '">' .
										$LANG->sl('LLL:EXT:lang/locallang_core.xml:labels.temporaryDBmount', 1) .
									'</a>';
					$flashMessage = t3lib_div::makeInstance(
						't3lib_FlashMessage',
						$link,
						'',
						t3lib_FlashMessage::INFO
					);
					$dbmount = $flashMessage->render();
				}

				$content .= '
			<!--
				Wrapper table for page tree / record list:
			-->
					<table border="0" cellpadding="0" cellspacing="0" id="typo3-linkPages">
						<tr>
							<td class="c-wCell" valign="top">' . $this->barheader($LANG->getLL('pageTree') . ':') . $dbmount . $tree . '</td>
							<td class="c-wCell" valign="top">' . $cElements . '</td>
						</tr>
					</table>
					';
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
		$content = $this->doc->insertStylesAndJS($content);
		return $content;
	}

	function addAttributesForm() {
		$ltargetForm = '';
			// Add page id, target, class selector box, title and parameters fields:
		$lpageId = $this->addPageIdSelector();
		$queryParameters = $this->addQueryParametersSelector();
		$ltarget = $this->addTargetSelector();
		$lclass = $this->addClassSelector();
		$ltitle = $this->addTitleSelector();
		$rel = $this->addRelField();
		if ($lpageId || $queryParameters || $ltarget || $lclass || $ltitle || $rel) {
			$ltargetForm = $this->wrapInForm($lpageId.$queryParameters.$ltarget.$lclass.$ltitle.$rel);
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
		if ($this->act == $this->curUrlInfo['act'] && $this->act != 'mail' && $this->curUrlArray['href']) {
			$form .='
						<tr>
							<td>
							</td>
							<td colspan="3">
								<input type="submit" value="'.$LANG->getLL('update',1).'" onclick="' . (($this->act == 'url') ? 'browse_links_setAdditionalValue(\'external\', \'1\'); ' : '') .'return link_current();" />
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

	function addRelField() {
		return (($this->act == 'page' || $this->act == 'url' || $this->act == 'file') && $this->buttonConfig && is_array($this->buttonConfig['relAttribute.']) && $this->buttonConfig['relAttribute.']['enabled'])?'
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('linkRelationship',1).':</td>
							<td colspan="3">
								<input type="text" name="lrel" value="' . $this->additionalAttributes['rel']. '"  ' . $this->doc->formWidth(30) . ' />
							</td>
						</tr>':'';
	}

	function addQueryParametersSelector() {
		global $LANG;

		return ($this->act == 'page' && $this->buttonConfig && is_array($this->buttonConfig['queryParametersSelector.']) && $this->buttonConfig['queryParametersSelector.']['enabled'])?'
						<tr>
							<td>'.$LANG->getLL('query_parameters',1).':</td>
							<td colspan="3">
								<input type="text" name="query_parameters" value="' . ($this->curUrlInfo['query']?$this->curUrlInfo['query']:'') . '" ' . $this->doc->formWidth(30) . ' />
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
						<td><input type="text" name="ltarget" onchange="browse_links_setTarget(this.value);" value="'.htmlspecialchars($this->setTarget?$this->setTarget:(($this->setClass || !$this->classesAnchorDefault[$this->act])?'':$this->classesAnchorDefaultTarget[$this->act])).'"'.$this->doc->formWidth(10).' /></td>';
			$ltarget .= '
						<td colspan="2">';
			if (!$targetSelectorConfig['disabled']) {
				$ltarget .= '
							<select name="ltarget_type" onchange="browse_links_setTarget(this.options[this.selectedIndex].value);document.ltargetform.ltarget.value=this.options[this.selectedIndex].value;this.selectedIndex=0;">
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
					browse_links_setTarget(document.ltargetform.ltarget.value);
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

	/**
	 * Return html code for the class selector
	 *
	 * @return	string		the html code to be added to the form
	 */
	public function addClassSelector() {
		$selectClass = '';
		if ($this->classesAnchorJSOptions[$this->act]) {
			$selectClass ='
						<tr>
							<td>'.$GLOBALS['LANG']->getLL('anchor_class',1).':</td>
							<td colspan="3">
								<select name="anchor_class" onchange="'.$this->getClassOnChangeJS().'">
									' . $this->classesAnchorJSOptions[$this->act] . '
								</select>
							</td>
						</tr>';
		}
		return $selectClass;
	}

	/**
	 * Return JS code for the class selector onChange event
	 *
	 * @return	string	class selector onChange JS code
	 */
	 public function getClassOnChangeJS() {
		 return '
					if (document.ltargetform.anchor_class) {
						document.ltargetform.anchor_class.value = document.ltargetform.anchor_class.options[document.ltargetform.anchor_class.selectedIndex].value;
						if (document.ltargetform.anchor_class.value && HTMLArea.classesAnchorSetup) {
							for (var i = HTMLArea.classesAnchorSetup.length; --i >= 0;) {
								var anchorClass = HTMLArea.classesAnchorSetup[i];
								if (anchorClass[\'name\'] == document.ltargetform.anchor_class.value) {
									if (anchorClass[\'titleText\'] && document.ltargetform.anchor_title) {
										document.ltargetform.anchor_title.value = anchorClass[\'titleText\'];
										document.getElementById(\'rtehtmlarea-browse-links-title-readonly\').innerHTML = anchorClass[\'titleText\'];
										browse_links_setTitle(anchorClass[\'titleText\']);
									}
									if (anchorClass[\'target\']) {
										if (document.ltargetform.ltarget) {
											document.ltargetform.ltarget.value = anchorClass[\'target\'];
										}
										browse_links_setTarget(anchorClass[\'target\']);
									} else if (document.ltargetform.ltarget && document.getElementById(\'ltargetrow\').style.display == \'none\') {
											// Reset target to default if field is not displayed and class has no configured target
										document.ltargetform.ltarget.value = \''. ($this->thisConfig['defaultLinkTarget']?$this->thisConfig['defaultLinkTarget']:'') .'\';
										browse_links_setTarget(document.ltargetform.ltarget.value);
									}
									break;
								}
							}
						}
						browse_links_setClass(document.ltargetform.anchor_class.value);
					}
								';
	 }

	function addTitleSelector() {
		$title = ($this->setTitle ? $this->setTitle : (($this->setClass || !$this->classesAnchorDefault[$this->act]) ? '' : $this->classesAnchorDefaultTitle[$this->act]));
		$readOnly = $this->buttonConfig['properties.']['title.']['readOnly'] || $this->buttonConfig[$this->act.'.']['properties.']['title.']['readOnly'];
		if ($readOnly) {
			$title = $this->setClass ? $this->classesAnchorClassTitle[$this->setClass] : $this->classesAnchorDefaultTitle[$this->act];
		}
		return '
						<tr>
							<td><label for="rtehtmlarea-browse-links-anchor_title" id="rtehtmlarea-browse-links-title-label">' . $GLOBALS['LANG']->getLL('anchor_title',1) . ':</label></td>
							<td colspan="3">
								<span id="rtehtmlarea-browse-links-title-input" style="display: ' . ($readOnly ? 'none' : 'inline') . ';">
									<input type="text" id="rtehtmlarea-browse-links-anchor_title" name="anchor_title" value="' . $title . '" ' . $this->doc->formWidth(30) . ' />
								</span>
								<span id="rtehtmlarea-browse-links-title-readonly" style="display: ' . ($readOnly ? 'inline' : 'none') . ';">' . $title . '</span>
							</td>
						</tr>';
	}

	/**
	 * Localize a string using the language of the content element rather than the language of the BE interface
	 *
	 * @param	string		string: the label to be localized
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

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod3/class.tx_rtehtmlarea_browse_links.php']);
}

?>