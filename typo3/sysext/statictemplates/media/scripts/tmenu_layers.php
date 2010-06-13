<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the TMENU_LAYERS extension class, tslib_tmenu_layers
 *
 * $Id: tmenu_layers.php 5165 2009-03-09 18:28:59Z ohader $
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   84: class tslib_tmenu_layers extends tslib_tmenu
 *  116:     function extProc_init()
 *  134:     function extProc_RO($key)
 *  150:     function extProc_beforeLinking($key)
 *  205:     function extProc_afterLinking($key)
 *  240:     function extProc_beforeAllWrap($item,$key)
 *  253:     function isSetIntval($in)
 *  262:     function extProc_finish ()
 *  444:     function extCalcBorderWithin($kind,$integer)
 *
 * TOTAL FUNCTIONS: 8
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



















/**
 * Class extending tslib_tmenu for the creation of text based DHTML menus
 * NOTICE: The contents of this class is copied EXACTLY AS IS from gmenu_layers class! See notes in class (for BEGIN/END) and also 'diff.xmenu_layers.txt'
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=385&cHash=648519dd66
 * @see diff.xmenu_layers.txt
 */
class tslib_tmenu_layers extends tslib_tmenu {

// FULL DUPLICATE FROM gmenu_layers BEGIN:

	var $GMENU_fixKey='layers';
	var $divLayers=Array();

	var $WMx=0;
	var $WMy=0;
	var $WMxyArray=array();
	var $WMextraScript='';
	var $WMlastKey='';
	var $WMrestoreScript='';
	var $WMresetSubMenus='';
	var $WMactiveHasSubMenu='';
	var $WMactiveKey='';
	var $WMtheSubMenu;
	var $WMisSub;
	var $WMhideCode;
	var $WMonlyOnLoad=0;
	var $WMbordersWithin=array();
	var $WMsubIds=array();
	var $WMtempStore=array();
	var $WMlockPosition_addAccumulated=array();
	var $VMmouseoverActions=array();
	var $VMmouseoutActions=array();

	/**
	 * Creating unique menu id string plus other initialization of internal variables (all prefixed "WM")
	 *
	 * @return	void
	 */
	function extProc_init()	{
		$this->WMid = trim($this->mconf['layer_menu_id'])?trim($this->mconf['layer_menu_id']).'x':substr(md5(microtime()),0,6);	// NO '_' (underscore) in the ID!!! NN4 breaks!

		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'][]=$this->WMid;
		$this->WMtempStore = $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'];
		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']=array();

			// Save:
		$this->WMonlyOnLoad = ($this->mconf['displayActiveOnLoad'] && !$this->mconf['displayActiveOnLoad.']['onlyOnLoad']);
		$this->WMbordersWithin = t3lib_div::intExplode(',',$this->mconf['bordersWithin'].',0,0,0,0,0');
	}

	/**
	 * Processing of mouse-over features depending on whether "freezeMouseover" property is set.
	 *
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found OR $this->result['RO'][$key] where the configuration for that elements RO version is found! Here it is used with the ->WMid to make unique names
	 * @return	void
	 */
	function extProc_RO($key)	{
		if ($this->mconf['freezeMouseover'])	{
			$this->VMmouseoverActions[$this->WMid.$key]='case "Menu'.$this->WMid.$key.'":'.$this->I['linkHREF']['onMouseover'].'; break;';
			$this->VMmouseoutActions[$this->WMid.$key]='case "Menu'.$this->WMid.$key.'":'.$this->I['linkHREF']['onMouseout'].'; break;';
			$this->I['linkHREF']['onMouseover']='GL'.$this->WMid.'_over(\'Menu'.$this->WMid.$key.'\');';
			$this->I['linkHREF']['onMouseout']='';
		}
	}

	/**
	 * Processing before the links are created.
	 * This means primarily creating some javaScript code for the management.
	 *
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void
	 */
	function extProc_beforeLinking($key)	{
		if ($this->I['uid'])	{

			array_push($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId'],$this->WMid);
			$this->WMtheSubMenu = $this->subMenu($this->I['uid'], $this->WMsubmenuObjSuffixes[$key]['sOSuffix']);
			array_pop($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId']);
			$this->WMisSub = trim($this->WMtheSubMenu) ? 1 : 0;

			if ($this->mconf['lockPosition_addSelf'])		{
				$this->WMy+=(strcmp($this->mconf['setFixedHeight'],'')?$this->mconf['setFixedHeight']:$this->I['val']['output_h'])+intval($this->mconf['lockPosition_adjust']);
				$this->WMx+=(strcmp($this->mconf['setFixedWidth'],'')?$this->mconf['setFixedWidth']:$this->I['val']['output_w'])+intval($this->mconf['lockPosition_adjust']);
			}

			if ($this->isActive($this->I['uid'], $this->getMPvar($key)) && $this->mconf['displayActiveOnLoad'])	{	// orig: && $this->WMisSub, changed 210901
				$this->WMactiveHasSubMenu = $this->WMisSub;
				$this->WMactiveKey = 'Menu'.$this->WMid.$key;


				$this->WMrestoreVars=trim('
GLV_restoreMenu["'.$this->WMid.'"] = "'.$this->WMactiveKey.'";
				');
				$this->WMrestoreScript='	GL_doTop("'.$this->WMid.'",GLV_restoreMenu["'.$this->WMid.'"]);'.($this->mconf['freezeMouseover']?'
	GL'.$this->WMid.'_over(GLV_restoreMenu["'.$this->WMid.'"]);
':'');
			}

			if ($this->WMisSub)	{
				$event="GL_stopMove('".$this->WMid."');";
				$this->I['linkHREF']['onMouseover']='GL_doTop(\''.$this->WMid.'\', \'Menu'.$this->WMid.$key.'\');'.$this->I['linkHREF']['onMouseover'];
					// IESelectFix - Activates IFRAME layer below menu
				if ($this->mconf['ieSelectFix']) $this->I['linkHREF']['onMouseover']=$this->I['linkHREF']['onMouseover'].'GL_iframer(\''.$this->WMid.'\',\'Menu'.$this->WMid.$key.'\',true);';
					// Added 120802; This means that everytime leaving a menuitem the layer should be shut down (and if the layer is hit in the meantime it is not though).
					// This should happen only for items that are auto-hidden when not over and possibly only when a hide-timer is set. Problem is if the hide-timer is not set and we leave the main element, then the layer will be hidden unless we reach the layer before the timeout will happen and the menu hidden.
				if (t3lib_div::intInRange($this->mconf['hideMenuWhenNotOver'],0,600) && $this->mconf['hideMenuTimer'])	{
					$event.='GL_resetAll("'.$this->WMid.'");';
				}
				$this->I['linkHREF']['onMouseout'].=$event;
			} else {
				$this->I['linkHREF']['onMouseover'] = 'GL_hideAll("'.$this->WMid.'");'.$this->I['linkHREF']['onMouseover'];
					// IESelectFix - Hides IFRAME layer below menu
				if ($this->mconf['ieSelectFix']) $this->I['linkHREF']['onMouseover'] = $this->I['linkHREF']['onMouseover'].'GL_iframer(\''.$this->WMid.'\',\'\',false);';
				$event='GL_resetAll("'.$this->WMid.'");';
				$this->I['linkHREF']['onMouseout'].=$event;
			}

			$this->WMxyArray[] = 'GLV_menuXY["'.$this->WMid.'"]["Menu'.$this->WMid.$key.'"] = new Array('.$this->WMx.','.$this->WMy.',"itemID'.t3lib_div::shortmd5($this->I['uid'].'-'.$this->WMid).'","anchorID'.t3lib_div::shortmd5($this->I['uid'].'-'.$this->WMid).'");';
		}
	}

	/**
	 * Processing after linking, basically setting the <div>-layers for the menu items. Also some more JavaScript code is made.
	 *
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void
	 */
	function extProc_afterLinking($key)	{
		if ($this->I['uid'])	{
			if (!$this->I['spacer'] && $this->WMisSub)	{
				$exStyle=$this->mconf['layerStyle'] ? $this->mconf['layerStyle'] : 'position:absolute;visibility:hidden';
				if (trim($exStyle))	{
					$exStyle=' '.$exStyle;
				}
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['layerCounter']++;
				$zIndex = 10000-$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['layerCounter'];
#				$zIndex = (($key+2)*$this->menuNumber*100);
				$divStart = '<div id="Menu'.$this->WMid.$key.'" style="z-index:'.$zIndex.';'.$exStyle.'">';
				$divStop = '</div>';

				$this->divLayers[]= $divStart.$this->WMtheSubMenu.$divStop;

				$this->WMhideCode.='
	GL_getObjCss("Menu'.$this->WMid.$key.'").visibility = "hidden";';
				$this->WMlastKey = 'Menu'.$this->WMid.$key;
			}

			if (!$this->mconf['lockPosition_addSelf'])		{
				$this->WMy+=(strcmp($this->mconf['setFixedHeight'],'')?$this->mconf['setFixedHeight']:$this->I['val']['output_h'])+intval($this->mconf['lockPosition_adjust']);
				$this->WMx+=(strcmp($this->mconf['setFixedWidth'],'')?$this->mconf['setFixedWidth']:$this->I['val']['output_w'])+intval($this->mconf['lockPosition_adjust']);
			}
		}
		$this->WMresult.=$this->I['theItem'];
	}

	/**
	 * Wrapping the item in a <div> section if 'relativeToTriggerItem' was set
	 *
	 * @param	string		The current content of the menu item, $this->I['theItem'], passed along.
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	string		The modified version of $item, going back into $this->I['theItem']
	 */
	function extProc_beforeAllWrap($item,$key)	{
		if ($this->mconf['relativeToTriggerItem'])	{
			$item = '<div id="anchorID'.t3lib_div::shortmd5($this->I['uid'].'-'.$this->WMid).'" style="position:absolute;visibility:hidden;"></div><div id="itemID'.t3lib_div::shortmd5($this->I['uid'].'-'.$this->WMid).'" style="width:100%; height:100%;">'.$item.'</div>';
		}
		return $item;
	}

	/**
	 * Returns true if different from ''  OR if intval()!=0
	 *
	 * @param	string		Value to evaluate
	 * @return	boolean		true if $in is different from ''  OR if intval()!=0
	 */
	function isSetIntval($in)	{
		return $this->mconf['blankStrEqFalse'] ? strcmp($in,'') : intval($in);
	}

	/**
	 * Putting things together, in particular the JavaScript code needed for the DHTML menu.
	 *
	 * @return	mixed		Returns the value of a call to the parent function, parent::extProc_finish();
	 */
	function extProc_finish ()	{
		$dirL = $this->mconf['directionLeft'] ? '-GL_getObj(id).width' : '';
		$dirU = $this->mconf['directionUp'] ? '-GL_getObj(id).height' : '';

		$parentLayerId = end($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId']);

		$DoTop=array();
		$GLV_menuOn=array();
		$relCode=array();
		$relFlag=0;
		if ($this->mconf['relativeToParentLayer'] && $parentLayerId)	{
			$relCode['X'].='GLV_curLayerX["'.$parentLayerId.'"]+';
			$relCode['Y'].='GLV_curLayerY["'.$parentLayerId.'"]+';
			if ($this->mconf['relativeToParentLayer.']['addWidth'])		{	$relCode['X'].='GLV_curLayerWidth["'.$parentLayerId.'"]+';	}
			if ($this->mconf['relativeToParentLayer.']['addHeight'])	{	$relCode['Y'].='GLV_curLayerHeight["'.$parentLayerId.'"]+';	}
		}
		if ($this->mconf['relativeToTriggerItem'])	{
			$DoTop[]='
		var parentObject = GL_getObj(GLV_menuXY[WMid][id][2]);
		var TI_width = parentObject.width;
		var TI_height = parentObject.height;
		var anchorObj = GL_getObj(GLV_menuXY[WMid][id][3]);
		var TI_x = anchorObj.x;
		var TI_y = anchorObj.y;
			';
			$relCode['X'].='TI_x+';
			$relCode['Y'].='TI_y+';

			if ($this->mconf['relativeToTriggerItem.']['addWidth'])	{	$relCode['X'].='TI_width+';	}
			if ($this->mconf['relativeToTriggerItem.']['addHeight'])	{	$relCode['Y'].='TI_height+';	}
			$relFlag=1;
		}
		if ($relFlag)	{
			$DoTop[]='GLV_menuOn["'.$this->WMid.'"].left = ('.$relCode['X'].intval($this->mconf['leftOffset']).$dirL.')+"px";';
			$DoTop[]='GLV_menuOn["'.$this->WMid.'"].top =  ('.$relCode['Y'].intval($this->mconf['topOffset']).$dirU.')+"px";';
		} else {
				// X position (y is fixed)
			if (!strcmp($this->mconf['lockPosition'],'x'))	{
				$DoTop[]='GLV_menuOn["'.$this->WMid.'"].left = ('.$relCode['X'].'GLV_menuXY["'.$this->WMid.'"][id][0]-('.intval($this->mconf['xPosOffset']).')'.$dirL.')+"px";';
				if ($this->isSetIntval($this->mconf['topOffset']))	{
					$DoTop[]='GLV_menuOn["'.$this->WMid.'"].top = ('.$relCode['Y'].intval($this->mconf['topOffset']).$dirU.')+"px";';
				}
			} elseif ($this->isSetIntval($this->mconf['xPosOffset'])) {
				$GLV_menuOn[]=$DoTop[]='GLV_menuOn["'.$this->WMid.'"].left = (GLV_x-('.intval($this->mconf['xPosOffset']).')'.$dirL.')+"px";';
				if ($this->isSetIntval($this->mconf['topOffset']))	{
					$DoTop[]='GLV_menuOn["'.$this->WMid.'"].top = ('.$relCode['Y'].intval($this->mconf['topOffset']).$dirU.')+"px";';
				}
			}
				// Y position	(x is fixed)
			if (!strcmp($this->mconf['lockPosition'],'y'))	{
				$DoTop[]='GLV_menuOn["'.$this->WMid.'"].top = ('.$relCode['Y'].'GLV_menuXY["'.$this->WMid.'"][id][1]-('.intval($this->mconf['yPosOffset']).')'.$dirU.')+"px";';
				if ($this->isSetIntval($this->mconf['leftOffset']))	{
					$DoTop[]='GLV_menuOn["'.$this->WMid.'"].left = ('.$relCode['X'].intval($this->mconf['leftOffset']).$dirL.')+"px";';
				}
			} elseif ($this->isSetIntval($this->mconf['yPosOffset']))	{
				$GLV_menuOn[]=$DoTop[]='GLV_menuOn["'.$this->WMid.'"].top = (GLV_y-('.intval($this->mconf['yPosOffset']).')'.$dirU.')+"px";';
				if ($this->isSetIntval($this->mconf['leftOffset']))	{
					$DoTop[]='GLV_menuOn["'.$this->WMid.'"].left = ('.$relCode['X'].intval($this->mconf['leftOffset']).$dirL.')+"px";';
				}
			}
		}

			// BordersWithIn:
		$DoTop[]=$this->extCalcBorderWithin('left',$this->WMbordersWithin[0]);
		$DoTop[]=$this->extCalcBorderWithin('top',$this->WMbordersWithin[1]);
		$DoTop[]=$this->extCalcBorderWithin('right',$this->WMbordersWithin[2]);
		$DoTop[]=$this->extCalcBorderWithin('bottom',$this->WMbordersWithin[3]);
		$DoTop[]=$this->extCalcBorderWithin('left',$this->WMbordersWithin[4]);
		$DoTop[]=$this->extCalcBorderWithin('top',$this->WMbordersWithin[5]);


		if ($this->mconf['freezeMouseover'] && !$this->mconf['freezeMouseover.']['alwaysKeep'])	{
			$this->WMhideCode.='
GL'.$this->WMid.'_out("");';
		}

		$TEST='';
		if (count($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']))	{
			foreach ($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] as $mIdStr) {
				$this->WMhideCode.='
GL_hideAll("'.$mIdStr.'");';
				$this->WMrestoreScript.='
GL_restoreMenu("'.$mIdStr.'");';
				$this->WMresetSubMenus.='
if (!GLV_doReset["'.$mIdStr.'"] && GLV_currentLayer["'.$mIdStr.'"]!=null)	resetSubMenu=0;';
			}
		}
			// IESelectFix - Adds IFRAME tag to HTML, Hides IFRAME layer below menu
		if ($this->mconf['ieSelectFix']) {
			$this->WMhideCode.= '
	GL_iframer(\''.$this->WMid.'\',\'\',false);';
			$this->divLayers['iframe'] = '<iframe id="Iframe'.$this->WMid.'" scrolling="no" frameborder="0" style="position:absolute; top:0px; left:0px; background-color:transparent; layer-background-color:transparent; display:none;"></iframe>';
		}
		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']=array_merge($this->WMtempStore,$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']);
		$GLOBALS['TSFE']->additionalHeaderData['gmenu_layer_shared']='<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('cms').'tslib/media/scripts/jsfunc.layermenu.js"></script>';
		$GLOBALS['TSFE']->JSCode.= '

GLV_curLayerWidth["'.$this->WMid.'"]=0;
GLV_curLayerHeight["'.$this->WMid.'"]=0;
GLV_curLayerX["'.$this->WMid.'"]=0;
GLV_curLayerY["'.$this->WMid.'"]=0;
GLV_menuOn["'.$this->WMid.'"] = null;
GLV_gap["'.$this->WMid.'"]='.t3lib_div::intInRange($this->mconf['hideMenuWhenNotOver'],0,600).';
GLV_currentLayer["'.$this->WMid.'"] = null;
GLV_currentROitem["'.$this->WMid.'"] = null;
GLV_hasBeenOver["'.$this->WMid.'"]=0;
GLV_doReset["'.$this->WMid.'"]=false;
GLV_lastKey["'.$this->WMid.'"] = "'.$this->WMlastKey.'";
GLV_onlyOnLoad["'.$this->WMid.'"] = '.($this->WMonlyOnLoad?1:0).';
GLV_dontHideOnMouseUp["'.$this->WMid.'"] = '.($this->mconf['dontHideOnMouseUp']?1:0).';
GLV_dontFollowMouse["'.$this->WMid.'"] = '.($this->mconf['dontFollowMouse']?1:0).';
GLV_date = new Date();
GLV_timeout["'.$this->WMid.'"] = GLV_date.getTime();
GLV_timeoutRef["'.$this->WMid.'"] = '.t3lib_div::intInRange($this->mconf['hideMenuTimer'],0,20000).';
GLV_menuXY["'.$this->WMid.'"] = new Array();
'.implode(LF,$this->WMxyArray).'
'.$this->WMrestoreVars;

		if ($this->mconf['freezeMouseover'])	{
			$GLOBALS['TSFE']->JSCode.= '
// Alternative rollover/out functions for use with GMENU_LAYER
function GL'.$this->WMid.'_over(mitm_id)	{
	GL'.$this->WMid.'_out("");	// removes any old roll over state of an item. Needed for alwaysKeep and Opera browsers.
	switch(mitm_id)	{
'.implode(LF,$this->VMmouseoverActions).'
	}
	GLV_currentROitem["'.$this->WMid.'"]=mitm_id;
}
function GL'.$this->WMid.'_out(mitm_id)	{
	if (!mitm_id)	mitm_id=GLV_currentROitem["'.$this->WMid.'"];
	switch(mitm_id)	{
'.implode(LF,$this->VMmouseoutActions).'
	}
}
';
		}
		$GLOBALS["TSFE"]->JSCode.= '
function GL'.$this->WMid.'_getMouse(e) {
	if (GLV_menuOn["'.$this->WMid.'"]!=null && !GLV_dontFollowMouse["'.$this->WMid.'"]){
'.implode(LF,$GLV_menuOn).'
	}
	GL_mouseMoveEvaluate("'.$this->WMid.'");
}
function GL'.$this->WMid.'_hideCode() {
'.$this->WMhideCode.'
}
function GL'.$this->WMid.'_doTop(WMid,id) {
'.trim(implode(LF,$DoTop)).'
}
function GL'.$this->WMid.'_restoreMenu() {
'.$this->WMrestoreScript.'
}
function GL'.$this->WMid.'_resetSubMenus() {
	var resetSubMenu=1;
'.$this->WMresetSubMenus.'
	return resetSubMenu;
}

GLV_timeout_pointers[GLV_timeout_count] = "'.$this->WMid.'";
GLV_timeout_count++;

';
		$GLOBALS['TSFE']->JSeventFuncCalls['onload']['GL_initLayers()']= 'GL_initLayers();';
		$GLOBALS['TSFE']->JSeventFuncCalls['onload'][$this->WMid]=	'GL_restoreMenu("'.$this->WMid.'");';
		$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']['GL_getMouse(e)']= 'GL_getMouse(e);';	// Should be called BEFORE any of the 'local' getMouse functions!
		$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove'][$this->WMid]= 'GL'.$this->WMid.'_getMouse(e);';
		$GLOBALS['TSFE']->JSeventFuncCalls['onmouseup'][$this->WMid]= 'GL_mouseUp(\''.$this->WMid.'\',e);';

		$GLOBALS['TSFE']->divSection.=implode($this->divLayers,LF).LF;

		return parent::extProc_finish();
	}

	/**
	 * Creates a JavaScript line which corrects the position of the layer based on the constraints in TypoScript property 'bordersWithin'
	 *
	 * @param	string		Direction to test.
	 * @param	integer		The boundary limit in the direction set by $kind. If set then a value is returned, otherwise blank.
	 * @return	string		JavaScript string for correction of the layer position (if $integer is true)
	 * @see extProc_finish(), extProc_init()
	 */
	function extCalcBorderWithin($kind,$integer)	{
		if ($integer)	{
			switch($kind)	{
				case 'right':
				case 'bottom':
					$add='';
					if ($kind=='right')		{$add='GL_getObj(id).width'; $key = 'left';}
					if ($kind=='bottom')	{$add='GL_getObj(id).height'; $key = 'top';}
					$str = 'if (parseInt(GLV_menuOn["'.$this->WMid.'"].'.$key.')+'.$add.'>'.$integer.') GLV_menuOn["'.$this->WMid.'"].'.$key.'='.$integer.'-'.$add.';';
				break;
				default:
					$str = 'if (parseInt(GLV_menuOn["'.$this->WMid.'"].'.$kind.')<'.$integer.') GLV_menuOn["'.$this->WMid.'"].'.$kind.'='.$integer.';';
				break;
			}
		}
		return $str;
	}
}

// FULL DUPLICATE FROM gmenu_layers END:


$GLOBALS['TSFE']->tmpl->menuclasses.=',tmenu_layers';

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/tmenu_layers.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/tmenu_layers.php']);
}

?>