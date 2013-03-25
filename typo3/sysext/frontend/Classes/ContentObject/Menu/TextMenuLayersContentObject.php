<?php
namespace TYPO3\CMS\Frontend\ContentObject\Menu;

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
 * Contains the TMENU_LAYERS menu object
 * Class for the creation of text based DHTML menus
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 */
class TextMenuLayersContentObject extends \TYPO3\CMS\Frontend\ContentObject\Menu\TextMenuContentObject {

	// FULL DUPLICATE FROM gmenu_layers BEGIN:
	/**
	 * @todo Define visibility
	 */
	public $GMENU_fixKey = 'layers';

	/**
	 * @todo Define visibility
	 */
	public $divLayers = array();

	/**
	 * @todo Define visibility
	 */
	public $WMx = 0;

	/**
	 * @todo Define visibility
	 */
	public $WMy = 0;

	/**
	 * @todo Define visibility
	 */
	public $WMxyArray = array();

	/**
	 * @todo Define visibility
	 */
	public $WMextraScript = '';

	/**
	 * @todo Define visibility
	 */
	public $WMlastKey = '';

	/**
	 * @todo Define visibility
	 */
	public $WMrestoreScript = '';

	/**
	 * @todo Define visibility
	 */
	public $WMresetSubMenus = '';

	/**
	 * @todo Define visibility
	 */
	public $WMactiveHasSubMenu = '';

	/**
	 * @todo Define visibility
	 */
	public $WMactiveKey = '';

	/**
	 * @todo Define visibility
	 */
	public $WMtheSubMenu;

	/**
	 * @todo Define visibility
	 */
	public $WMisSub;

	/**
	 * @todo Define visibility
	 */
	public $WMhideCode;

	/**
	 * @todo Define visibility
	 */
	public $WMonlyOnLoad = 0;

	/**
	 * @todo Define visibility
	 */
	public $WMbordersWithin = array();

	/**
	 * @todo Define visibility
	 */
	public $WMsubIds = array();

	/**
	 * @todo Define visibility
	 */
	public $WMtempStore = array();

	/**
	 * @todo Define visibility
	 */
	public $WMlockPosition_addAccumulated = array();

	/**
	 * @todo Define visibility
	 */
	public $VMmouseoverActions = array();

	/**
	 * @todo Define visibility
	 */
	public $VMmouseoutActions = array();

	/**
	 * Creating unique menu id string plus other initialization of internal variables (all prefixed "WM")
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function extProc_init() {
		$this->WMid = trim($this->mconf['layer_menu_id']) ? trim($this->mconf['layer_menu_id']) . 'x' : substr(md5('gl' . serialize($this->mconf)), 0, 6);
		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'][] = $this->WMid;
		$this->WMtempStore = $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'];
		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] = array();
		// Save:
		$this->WMonlyOnLoad = $this->mconf['displayActiveOnLoad'] && !$this->mconf['displayActiveOnLoad.']['onlyOnLoad'];
		$this->WMbordersWithin = \TYPO3\CMS\Core\Utility\GeneralUtility::intExplode(',', $this->mconf['bordersWithin'] . ',0,0,0,0,0');
	}

	/**
	 * Processing of mouse-over features depending on whether "freezeMouseover" property is set.
	 *
	 * @param integer $key Pointer to $this->menuArr[$key] where the current menu element record is found OR $this->result['RO'][$key] where the configuration for that elements RO version is found! Here it is used with the ->WMid to make unique names
	 * @return void
	 * @todo Define visibility
	 */
	public function extProc_RO($key) {
		if ($this->mconf['freezeMouseover']) {
			$this->VMmouseoverActions[$this->WMid . $key] = 'case "Menu' . $this->WMid . $key . '":' . $this->I['linkHREF']['onMouseover'] . '; break;';
			$this->VMmouseoutActions[$this->WMid . $key] = 'case "Menu' . $this->WMid . $key . '":' . $this->I['linkHREF']['onMouseout'] . '; break;';
			$this->I['linkHREF']['onMouseover'] = 'GL' . $this->WMid . '_over(\'Menu' . $this->WMid . $key . '\');';
			$this->I['linkHREF']['onMouseout'] = '';
		}
	}

	/**
	 * Processing before the links are created.
	 * This means primarily creating some javaScript code for the management.
	 *
	 * @param integer $key Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @todo Define visibility
	 */
	public function extProc_beforeLinking($key) {
		if ($this->I['uid']) {
			array_push($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId'], $this->WMid);
			$this->WMtheSubMenu = $this->subMenu($this->I['uid'], $this->WMsubmenuObjSuffixes[$key]['sOSuffix']);
			array_pop($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId']);
			$this->WMisSub = trim($this->WMtheSubMenu) ? 1 : 0;
			if ($this->mconf['lockPosition_addSelf']) {
				$this->WMy += (strcmp($this->mconf['setFixedHeight'], '') ? $this->mconf['setFixedHeight'] : $this->I['val']['output_h']) + intval($this->mconf['lockPosition_adjust']);
				$this->WMx += (strcmp($this->mconf['setFixedWidth'], '') ? $this->mconf['setFixedWidth'] : $this->I['val']['output_w']) + intval($this->mconf['lockPosition_adjust']);
			}
			// orig: && $this->WMisSub, changed 210901
			if ($this->isActive($this->I['uid'], $this->getMPvar($key)) && $this->mconf['displayActiveOnLoad']) {
				$this->WMactiveHasSubMenu = $this->WMisSub;
				$this->WMactiveKey = 'Menu' . $this->WMid . $key;
				$this->WMrestoreVars = trim('
GLV_restoreMenu["' . $this->WMid . '"] = "' . $this->WMactiveKey . '";
				');
				$this->WMrestoreScript = '	GL_doTop("' . $this->WMid . '",GLV_restoreMenu["' . $this->WMid . '"]);' . ($this->mconf['freezeMouseover'] ? '
	GL' . $this->WMid . '_over(GLV_restoreMenu["' . $this->WMid . '"]);
' : '');
			}
			if ($this->WMisSub) {
				$event = 'GL_stopMove(\'' . $this->WMid . '\');';
				$this->I['linkHREF']['onMouseover'] = 'GL_doTop(\'' . $this->WMid . '\', \'Menu' . $this->WMid . $key . '\');' . $this->I['linkHREF']['onMouseover'];
				// IESelectFix - Activates IFRAME layer below menu
				if ($this->mconf['ieSelectFix']) {
					$this->I['linkHREF']['onMouseover'] = $this->I['linkHREF']['onMouseover'] . 'GL_iframer(\'' . $this->WMid . '\',\'Menu' . $this->WMid . $key . '\',true);';
				}
				// Added 120802; This means that everytime leaving a menuitem the layer
				// should be shut down (and if the layer is hit in the meantime it is
				// not though). This should happen only for items that are auto-hidden
				// when not over and possibly only when a hide-timer is set. Problem is
				// if the hide-timer is not set and we leave the main element, then the
				// layer will be hidden unless we reach the layer before the timeout will
				// happen and the menu hidden.
				if (\TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->mconf['hideMenuWhenNotOver'], 0, 600) && $this->mconf['hideMenuTimer']) {
					$event .= 'GL_resetAll("' . $this->WMid . '");';
				}
				$this->I['linkHREF']['onMouseout'] .= $event;
			} else {
				$this->I['linkHREF']['onMouseover'] = 'GL_hideAll("' . $this->WMid . '");' . $this->I['linkHREF']['onMouseover'];
				// IESelectFix - Hides IFRAME layer below menu
				if ($this->mconf['ieSelectFix']) {
					$this->I['linkHREF']['onMouseover'] = $this->I['linkHREF']['onMouseover'] . 'GL_iframer(\'' . $this->WMid . '\',\'\',false);';
				}
				$event = 'GL_resetAll("' . $this->WMid . '");';
				$this->I['linkHREF']['onMouseout'] .= $event;
			}
			$this->WMxyArray[] = 'GLV_menuXY["' . $this->WMid . '"]["Menu' . $this->WMid . $key . '"] = new Array(' . $this->WMx . ',' . $this->WMy . ',"itemID' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5(($this->I['uid'] . '-' . $this->WMid)) . '","anchorID' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5(($this->I['uid'] . '-' . $this->WMid)) . '");';
		}
	}

	/**
	 * Processing after linking, basically setting the <div>-layers for the menu
	 * items. Also some more JavaScript code is made.
	 *
	 * @param integer $key Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return void
	 * @todo Define visibility
	 */
	public function extProc_afterLinking($key) {
		if ($this->I['uid']) {
			if (!$this->I['spacer'] && $this->WMisSub) {
				$exStyle = $this->mconf['layerStyle'] ? $this->mconf['layerStyle'] : 'position:absolute;visibility:hidden';
				if (trim($exStyle)) {
					$exStyle = ' ' . $exStyle;
				}
				$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['layerCounter']++;
				$zIndex = 10000 - $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['layerCounter'];
				$divStart = '<div id="Menu' . $this->WMid . $key . '" style="z-index:' . $zIndex . ';' . $exStyle . '">';
				$divStop = '</div>';
				$this->divLayers[] = $divStart . $this->WMtheSubMenu . $divStop;
				$this->WMhideCode .= '
	GL_getObjCss("Menu' . $this->WMid . $key . '").visibility = "hidden";';
				$this->WMlastKey = 'Menu' . $this->WMid . $key;
			}
			if (!$this->mconf['lockPosition_addSelf']) {
				$this->WMy += (strcmp($this->mconf['setFixedHeight'], '') ? $this->mconf['setFixedHeight'] : $this->I['val']['output_h']) + intval($this->mconf['lockPosition_adjust']);
				$this->WMx += (strcmp($this->mconf['setFixedWidth'], '') ? $this->mconf['setFixedWidth'] : $this->I['val']['output_w']) + intval($this->mconf['lockPosition_adjust']);
			}
		}
		$this->WMresult .= $this->I['theItem'];
	}

	/**
	 * Wrapping the item in a <div> section if 'relativeToTriggerItem' was set
	 *
	 * @param string $item The current content of the menu item, $this->I['theItem'], passed along.
	 * @param integer $key Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return string The modified version of $item, going back into $this->I['theItem']
	 * @todo Define visibility
	 */
	public function extProc_beforeAllWrap($item, $key) {
		if ($this->mconf['relativeToTriggerItem']) {
			$item = '<div id="anchorID' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5(($this->I['uid'] . '-' . $this->WMid)) . '" style="position:absolute;visibility:hidden;"></div><div id="itemID' . \TYPO3\CMS\Core\Utility\GeneralUtility::shortmd5(($this->I['uid'] . '-' . $this->WMid)) . '" style="width:100%; height:100%;">' . $item . '</div>';
		}
		return $item;
	}

	/**
	 * Returns TRUE if different from ''  OR if intval()!=0
	 *
	 * @param string $in Value to evaluate
	 * @return boolean TRUE if $in is different from ''  OR if intval()!=0
	 * @todo Define visibility
	 */
	public function isSetIntval($in) {
		return $this->mconf['blankStrEqFalse'] ? strcmp($in, '') : intval($in);
	}

	/**
	 * Putting things together, in particular the JavaScript code needed for the DHTML menu.
	 *
	 * @return mixed Returns the value of a call to the parent function, parent::extProc_finish();
	 * @todo Define visibility
	 */
	public function extProc_finish() {
		$dirL = $this->mconf['directionLeft'] ? '-GL_getObj(id).width' : '';
		$dirU = $this->mconf['directionUp'] ? '-GL_getObj(id).height' : '';
		$parentLayerId = end($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMparentId']);
		$DoTop = array();
		$GLV_menuOn = array();
		$relCode = array();
		$relFlag = 0;
		if ($this->mconf['relativeToParentLayer'] && $parentLayerId) {
			$relCode['X'] .= 'GLV_curLayerX["' . $parentLayerId . '"]+';
			$relCode['Y'] .= 'GLV_curLayerY["' . $parentLayerId . '"]+';
			if ($this->mconf['relativeToParentLayer.']['addWidth']) {
				$relCode['X'] .= 'GLV_curLayerWidth["' . $parentLayerId . '"]+';
			}
			if ($this->mconf['relativeToParentLayer.']['addHeight']) {
				$relCode['Y'] .= 'GLV_curLayerHeight["' . $parentLayerId . '"]+';
			}
		}
		if ($this->mconf['relativeToTriggerItem']) {
			$DoTop[] = '
		var parentObject = GL_getObj(GLV_menuXY[WMid][id][2]);
		var TI_width = parentObject.width;
		var TI_height = parentObject.height;
		var anchorObj = GL_getObj(GLV_menuXY[WMid][id][3]);
		var TI_x = anchorObj.x;
		var TI_y = anchorObj.y;
			';
			$relCode['X'] .= 'TI_x+';
			$relCode['Y'] .= 'TI_y+';
			if ($this->mconf['relativeToTriggerItem.']['addWidth']) {
				$relCode['X'] .= 'TI_width+';
			}
			if ($this->mconf['relativeToTriggerItem.']['addHeight']) {
				$relCode['Y'] .= 'TI_height+';
			}
			$relFlag = 1;
		}
		if ($relFlag) {
			$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].left = (' . $relCode['X'] . intval($this->mconf['leftOffset']) . $dirL . ')+"px";';
			$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].top =  (' . $relCode['Y'] . intval($this->mconf['topOffset']) . $dirU . ')+"px";';
		} else {
			// X position (y is fixed)
			if (!strcmp($this->mconf['lockPosition'], 'x')) {
				$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].left = (' . $relCode['X'] . 'GLV_menuXY["' . $this->WMid . '"][id][0]-(' . intval($this->mconf['xPosOffset']) . ')' . $dirL . ')+"px";';
				if ($this->isSetIntval($this->mconf['topOffset'])) {
					$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].top = (' . $relCode['Y'] . intval($this->mconf['topOffset']) . $dirU . ')+"px";';
				}
			} elseif ($this->isSetIntval($this->mconf['xPosOffset'])) {
				$GLV_menuOn[] = ($DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].left = (GLV_x-(' . intval($this->mconf['xPosOffset']) . ')' . $dirL . ')+"px";');
				if ($this->isSetIntval($this->mconf['topOffset'])) {
					$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].top = (' . $relCode['Y'] . intval($this->mconf['topOffset']) . $dirU . ')+"px";';
				}
			}
			// Y position	(x is fixed)
			if (!strcmp($this->mconf['lockPosition'], 'y')) {
				$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].top = (' . $relCode['Y'] . 'GLV_menuXY["' . $this->WMid . '"][id][1]-(' . intval($this->mconf['yPosOffset']) . ')' . $dirU . ')+"px";';
				if ($this->isSetIntval($this->mconf['leftOffset'])) {
					$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].left = (' . $relCode['X'] . intval($this->mconf['leftOffset']) . $dirL . ')+"px";';
				}
			} elseif ($this->isSetIntval($this->mconf['yPosOffset'])) {
				$GLV_menuOn[] = ($DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].top = (GLV_y-(' . intval($this->mconf['yPosOffset']) . ')' . $dirU . ')+"px";');
				if ($this->isSetIntval($this->mconf['leftOffset'])) {
					$DoTop[] = 'GLV_menuOn["' . $this->WMid . '"].left = (' . $relCode['X'] . intval($this->mconf['leftOffset']) . $dirL . ')+"px";';
				}
			}
		}
		// BordersWithIn:
		$DoTop[] = $this->extCalcBorderWithin('left', $this->WMbordersWithin[0]);
		$DoTop[] = $this->extCalcBorderWithin('top', $this->WMbordersWithin[1]);
		$DoTop[] = $this->extCalcBorderWithin('right', $this->WMbordersWithin[2]);
		$DoTop[] = $this->extCalcBorderWithin('bottom', $this->WMbordersWithin[3]);
		$DoTop[] = $this->extCalcBorderWithin('left', $this->WMbordersWithin[4]);
		$DoTop[] = $this->extCalcBorderWithin('top', $this->WMbordersWithin[5]);
		if ($this->mconf['freezeMouseover'] && !$this->mconf['freezeMouseover.']['alwaysKeep']) {
			$this->WMhideCode .= '
GL' . $this->WMid . '_out("");';
		}
		$TEST = '';
		if (count($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'])) {
			foreach ($GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] as $mIdStr) {
				$this->WMhideCode .= '
GL_hideAll("' . $mIdStr . '");';
				$this->WMrestoreScript .= '
GL_restoreMenu("' . $mIdStr . '");';
				$this->WMresetSubMenus .= '
if (!GLV_doReset["' . $mIdStr . '"] && GLV_currentLayer["' . $mIdStr . '"]!=null)	resetSubMenu=0;';
			}
		}
		// IESelectFix - Adds IFRAME tag to HTML, Hides IFRAME layer below menu
		if ($this->mconf['ieSelectFix']) {
			$this->WMhideCode .= '
	GL_iframer(\'' . $this->WMid . '\',\'\',false);';
			$this->divLayers['iframe'] = '<iframe id="Iframe' . $this->WMid . '" scrolling="no" frameborder="0" style="position:absolute; top:0px; left:0px; background-color:transparent; layer-background-color:transparent; display:none;"></iframe>';
		}
		$GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid'] = array_merge($this->WMtempStore, $GLOBALS['TSFE']->applicationData['GMENU_LAYERS']['WMid']);
		$GLOBALS['TSFE']->additionalHeaderData['gmenu_layer_shared'] = '<script type="text/javascript" src="' . \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::siteRelPath('statictemplates') . 'media/scripts/jsfunc.layermenu.js"></script>';
		$GLOBALS['TSFE']->additionalJavaScript['JSCode'] .= '

GLV_curLayerWidth["' . $this->WMid . '"]=0;
GLV_curLayerHeight["' . $this->WMid . '"]=0;
GLV_curLayerX["' . $this->WMid . '"]=0;
GLV_curLayerY["' . $this->WMid . '"]=0;
GLV_menuOn["' . $this->WMid . '"] = null;
GLV_gap["' . $this->WMid . '"]=' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->mconf['hideMenuWhenNotOver'], 0, 600) . ';
GLV_currentLayer["' . $this->WMid . '"] = null;
GLV_currentROitem["' . $this->WMid . '"] = null;
GLV_hasBeenOver["' . $this->WMid . '"]=0;
GLV_doReset["' . $this->WMid . '"]=false;
GLV_lastKey["' . $this->WMid . '"] = "' . $this->WMlastKey . '";
GLV_onlyOnLoad["' . $this->WMid . '"] = ' . ($this->WMonlyOnLoad ? 1 : 0) . ';
GLV_dontHideOnMouseUp["' . $this->WMid . '"] = ' . ($this->mconf['dontHideOnMouseUp'] ? 1 : 0) . ';
GLV_dontFollowMouse["' . $this->WMid . '"] = ' . ($this->mconf['dontFollowMouse'] ? 1 : 0) . ';
GLV_date = new Date();
GLV_timeout["' . $this->WMid . '"] = GLV_date.getTime();
GLV_timeoutRef["' . $this->WMid . '"] = ' . \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->mconf['hideMenuTimer'], 0, 20000) . ';
GLV_menuXY["' . $this->WMid . '"] = new Array();
' . implode(LF, $this->WMxyArray) . '
' . $this->WMrestoreVars;
		if ($this->mconf['freezeMouseover']) {
			$GLOBALS['TSFE']->additionalJavaScript['JSCode'] .= '
// Alternative rollover/out functions for use with GMENU_LAYER
function GL' . $this->WMid . '_over(mitm_id) {
	GL' . $this->WMid . '_out("");	// removes any old roll over state of an item. Needed for alwaysKeep and Opera browsers.
	switch(mitm_id) {
' . implode(LF, $this->VMmouseoverActions) . '
	}
	GLV_currentROitem["' . $this->WMid . '"]=mitm_id;
}
function GL' . $this->WMid . '_out(mitm_id) {
	if (!mitm_id)	mitm_id=GLV_currentROitem["' . $this->WMid . '"];
	switch(mitm_id) {
' . implode(LF, $this->VMmouseoutActions) . '
	}
}
';
		}
		$GLOBALS['TSFE']->additionalJavaScript['JSCode'] .= '
function GL' . $this->WMid . '_getMouse(e) {
	if (GLV_menuOn["' . $this->WMid . '"]!=null && !GLV_dontFollowMouse["' . $this->WMid . '"]){
' . implode(LF, $GLV_menuOn) . '
	}
	GL_mouseMoveEvaluate("' . $this->WMid . '");
}
function GL' . $this->WMid . '_hideCode() {
' . $this->WMhideCode . '
}
function GL' . $this->WMid . '_doTop(WMid,id) {
' . trim(implode(LF, $DoTop)) . '
}
function GL' . $this->WMid . '_restoreMenu() {
' . $this->WMrestoreScript . '
}
function GL' . $this->WMid . '_resetSubMenus() {
	var resetSubMenu=1;
' . $this->WMresetSubMenus . '
	return resetSubMenu;
}

GLV_timeout_pointers[GLV_timeout_count] = "' . $this->WMid . '";
GLV_timeout_count++;

';
		$GLOBALS['TSFE']->JSeventFuncCalls['onload']['GL_initLayers()'] = 'GL_initLayers();';
		$GLOBALS['TSFE']->JSeventFuncCalls['onload'][$this->WMid] = 'GL_restoreMenu("' . $this->WMid . '");';
		// Should be called BEFORE any of the 'local' getMouse functions!
		// is put inside in a try catch block to avoid JS errors in IE
		$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']['GL_getMouse(e)'] = 'try{GL_getMouse(e);}catch(ex){};';
		$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove'][$this->WMid] = 'try{GL' . $this->WMid . '_getMouse(e);}catch(ex){};';
		$GLOBALS['TSFE']->JSeventFuncCalls['onmouseup'][$this->WMid] = 'GL_mouseUp(\'' . $this->WMid . '\',e);';
		$GLOBALS['TSFE']->divSection .= implode($this->divLayers, LF) . LF;
		return parent::extProc_finish();
	}

	/**
	 * Creates a JavaScript line which corrects the position of the layer based on
	 * the constraints in TypoScript property 'bordersWithin'
	 *
	 * @param string $kind Direction to test.
	 * @param integer $integer The boundary limit in the direction set by $kind. If set then a value is returned, otherwise blank.
	 * @return string JavaScript string for correction of the layer position (if $integer is TRUE)
	 * @see extProc_finish(), extProc_init()
	 * @todo Define visibility
	 */
	public function extCalcBorderWithin($kind, $integer) {
		if ($integer) {
			switch ($kind) {
			case 'right':

			case 'bottom':
				$add = '';
				if ($kind == 'right') {
					$add = 'GL_getObj(id).width';
					$key = 'left';
				}
				if ($kind == 'bottom') {
					$add = 'GL_getObj(id).height';
					$key = 'top';
				}
				$str = 'if (parseInt(GLV_menuOn["' . $this->WMid . '"].' . $key . ')+' . $add . '>' . $integer . ') GLV_menuOn["' . $this->WMid . '"].' . $key . '=' . $integer . '-' . $add . ';';
				break;
			default:
				$str = 'if (parseInt(GLV_menuOn["' . $this->WMid . '"].' . $kind . ')<' . $integer . ') GLV_menuOn["' . $this->WMid . '"].' . $kind . '=' . $integer . ';';
				break;
			}
		}
		return $str;
	}

}


?>