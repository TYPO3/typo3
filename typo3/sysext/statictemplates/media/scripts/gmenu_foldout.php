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
 * Contains the GMENU_FOLDOUT extension class, tslib_gmenu_foldout
 *
 * $Id: gmenu_foldout.php 5165 2009-03-09 18:28:59Z ohader $
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
 *   80: class tslib_gmenu_foldout extends tslib_gmenu
 *   96:     function extProc_init()
 *  117:     function extProc_beforeLinking($key)
 *  134:     function extProc_afterLinking($key)
 *  160:     function extProc_finish()
 *
 * TOTAL FUNCTIONS: 4
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */





















/**
 * Class extension tslib_gmenu for the creation of DHTML foldout menus
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @link http://typo3.org/doc.0.html?&tx_extrepmgm_pi1[extUid]=270&tx_extrepmgm_pi1[tocEl]=386&cHash=f69ab462e2
 */
class tslib_gmenu_foldout extends tslib_gmenu {
	var $GMENU_fixKey='foldout';

	var $WMarrowNO;
	var $WMarrowACT;
	var $WMimagesFlag;
	var $WMimageHTML;
	var $WMsubmenu;
	var $WMtableWrap;
	var $WM_activeOnLoad='';

	/**
	 * Initializing, setting internal variables (prefixed WM)
	 *
	 * @return	void
	 */
	function extProc_init()	{
		$this->WMarrowNO='';
		$this->WMarrowACT='';
		$this->WMimagesFlag=0;
		$this->WMimageHTML ='';
		if (($this->mconf['arrowNO']||$this->mconf['arrowNO.']) && ($this->mconf['arrowACT']||$this->mconf['arrowACT.']))	{
			$this->WMarrowNO = $GLOBALS['TSFE']->cObj->getImgResource($this->mconf['arrowNO'],$this->mconf['arrowNO.']);
			$this->WMarrowACT = $GLOBALS['TSFE']->cObj->getImgResource($this->mconf['arrowACT'],$this->mconf['arrowACT.']);
			if (is_array($this->WMarrowACT) && is_array($this->WMarrowNO))	{
				$this->WMimagesFlag=1;
			}
		}
	}

	/**
	 * Processing before the links are created.
	 * Basically this is setting an onclick handler for clicking the menu item.
	 *
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void
	 */
	function extProc_beforeLinking($key)	{
		$this->I['addATagParams']='';
		$this->WMsubmenu = $this->subMenu($this->I['uid'], $this->WMsubmenuObjSuffixes[$key]['sOSuffix']);
		if (trim($this->WMsubmenu))	{
			$this->I['addATagParams']=' onclick="GF_menu('.$key.');'.($this->mconf['dontLinkIfSubmenu'] ? ' return false;' : '').'"';
			if ($this->isActive($this->I['uid'], $this->getMPvar($key)) && $this->mconf['displayActiveOnLoad'])	{	// orig: && $this->WMisSub, changed 210901
				$this->WM_activeOnLoad='GF_menu('.$key.');';
			}
		}
	}

	/**
	 * Processing after linking, basically setting the <div>-layers for the menu items and possibly wrapping in table, adding bullet images.
	 *
	 * @param	integer		Pointer to $this->menuArr[$key] where the current menu element record is found
	 * @return	void
	 */
	function extProc_afterLinking($key)	{
		$this->WMtableWrap = $this->mconf['dontWrapInTable'] ? '' : '<table cellspacing="0" cellpadding="0" width="100%" border="0"><tr><td>|</td></tr></table>';

		if ($this->WMimagesFlag)	{
			$this->WMimageHTML='<img src="'.$GLOBALS['TSFE']->absRefPrefix.$this->WMarrowNO[3].'" width="'.$this->WMarrowNO[0].'" height="'.$this->WMarrowNO[1].'" border="0" name="imgA'.$key.'"'.($this->mconf['arrowImgParams']?' '.$this->mconf['arrowImgParams']:'').' alt="" />';
		} else {$this->WMimageHTML="";}

		if (strstr($this->I['theItem'], '###ARROW_IMAGE###'))	{
			$this->I['theItem'] = str_replace('###ARROW_IMAGE###', $this->WMimageHTML, $this->I['theItem']);
		} else {
			$this->I['theItem'] = $this->WMimageHTML.$this->I['theItem'];
		}

		$this->WMresult.= '
<div class="clTop" id="divTop'.($key+1).'">'.$this->tmpl->wrap($this->I['theItem'], $this->WMtableWrap).'
<div class="clSub" id="divSub'.($key+1).'">
		'.$this->WMsubmenu.'
</div>
</div>';		// Originally a <br /> between the div-tags, but it seemed to break stuff.
	}

	/**
	 * Putting things together, in particular the JavaScript code needed for the DHTML menu.
	 *
	 * @return	string		Empty string! (Since $GLOBALS['TSFE']->divSection is set with the <div>-sections used in the menu)
	 */
	function extProc_finish()	{
		$bHeight = t3lib_div::intInRange(($this->mconf['bottomHeight']?$this->mconf['bottomHeight']:100) ,0,3000);
		$bottomContent = $this->mconf['bottomContent'] ? $GLOBALS['TSFE']->cObj->cObjGetSingle($this->mconf['bottomContent'],$this->mconf['bottomContent.'], '/GMENU_FOLDOUT/.bottomContent') : '';
		$adjustTopHeights = intval($this->mconf['adjustItemsH']);
		$adjustSubHeights = intval($this->mconf['adjustSubItemsH']);
		$mWidth = t3lib_div::intInRange(($this->mconf['menuWidth']?$this->mconf['menuWidth']:170) ,0,3000);
		$mHeight = t3lib_div::intInRange(($this->mconf['menuHeight']?$this->mconf['menuHeight']:400) ,0,3000);
		$insertmColor= $this->mconf['menuBackColor'] ? 'BACKGROUND-COLOR: '.$this->mconf['menuBackColor'].'; layer-background-color: '.$this->mconf['menuBackColor'] : '';
		$insertBottomColor= $this->mconf['bottomBackColor'] ? 'BACKGROUND-COLOR: '.$this->mconf['bottomBackColor'].'; layer-background-color: '.$this->mconf['bottomBackColor'] : '';
		$menuOffset = t3lib_div::intExplode(',',$this->mconf['menuOffset'].',');
		$subOffset = t3lib_div::intExplode(',',$this->mconf['subMenuOffset'].',');


		$GLOBALS['TSFE']->additionalHeaderData['gmenu_layer_shared']='<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('cms').'tslib/media/scripts/jsfunc.layermenu.js"></script>';
		$GLOBALS['TSFE']->additionalHeaderData['gmenu_foldout']='<script type="text/javascript" src="'.t3lib_extMgm::siteRelPath('cms').'tslib/media/scripts/jsfunc.foldout.js"></script>';

		$GLOBALS["TSFE"]->additionalHeaderData[].= '
<style type="text/css">
	/*<![CDATA[*/
#divCont {
	Z-INDEX: 1; LEFT: '.$menuOffset[0].'px; VISIBILITY: hidden; WIDTH: '.$mWidth.'px; POSITION: absolute; TOP: '.$menuOffset[1].'px; HEIGHT: '.$mHeight.'px
}
.clTop {
	Z-INDEX: 1; WIDTH: '.$mWidth.'px; POSITION: absolute; '.$insertmColor.'
}
.clSub {
	Z-INDEX: 1; LEFT: '.$subOffset[0].'px; WIDTH: '.$mWidth.'px; POSITION: absolute; TOP: '.$subOffset[1].'px
}
.bottomLayer {
	Z-INDEX: 1; WIDTH: '.$mWidth.'px; CLIP: rect(0px '.$mWidth.'px '.$bHeight.'px 0px); POSITION: absolute; HEIGHT: '.$bHeight.'px; '.$insertBottomColor.'
}
	/*]]>*/
</style>
<script type="text/javascript">
/*<![CDATA[*/
<!--
GFV_foldNumber='.$this->WMmenuItems.';          //How many toplinks do you have?
GFV_foldTimer='.t3lib_div::intInRange(($this->mconf['foldTimer']?$this->mconf['foldTimer']:40) ,1,3000).';          //The timeout in the animation, these are milliseconds.
GFV_foldSpeed='.t3lib_div::intInRange($this->mconf['foldSpeed'],1,100).';           //How many steps in an animation?
GFV_stayFolded='.($this->mconf['stayFolded'] ? 'true' : 'false').';      //Stay open when you click a new toplink?
GFV_foldImg='.$this->WMimagesFlag.';             //Do you want images (if not set to 0 and remove the images from the body)?
GFV_currentFold=null;
GFV_foldStep1=null;
GFV_foldStep2=null;
GFV_step=0;
GFV_active=false;	 //Don\'t change this one.
GFV_adjustTopHeights = '.$adjustTopHeights.';
GFV_adjustSubHeights = '.$adjustSubHeights.';
if (bw.opera)	{
	GFV_scrX= innerWidth;
	GFV_scrY= innerHeight;
}

//This is the default image.
//Remember to change the actual images in the page as well, but remember to keep the name of the image.
var GFV_unImg=new Image();
GFV_unImg.src="'.$GLOBALS['TSFE']->absRefPrefix.$this->WMarrowNO[3].'";

var GFV_exImg=new Image();          //Making an image variable...
GFV_exImg.src="'.$GLOBALS['TSFE']->absRefPrefix.$this->WMarrowACT[3].'";   //...this is the source of the image that it changes to when the menu expands.

//-->
/*]]>*/
</script>
';

		$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']['GF_resizeForOpera()']= 'GF_resizeForOpera();';
		$GLOBALS['TSFE']->JSeventFuncCalls['onload']['GMENU_FOLDOUT']= 'if(bw.bw) {GF_initFoldout();'.$this->WM_activeOnLoad.'}';

		$GLOBALS['TSFE']->divSection.= '
<div id="divCont"><!-- These are the contents of the foldoutmenu. -->
		'.$this->tmpl->wrap($this->WMresult,$this->mconf['wrap']).'
<div class="bottomLayer" id="divTop'.($this->WMmenuItems+1).'">
	<div class="clSub" id="divSub'.($this->WMmenuItems+1).'"><!-- This is a cover layer, it should always be the last one, and does NOT count in your number of toplinks! --><!-- So if this one is divTop7, the GFV_foldNumber variable should be set to 6 --><!-- This layer covers up the last sub, so if the last sub gets too big, increase this layers size in the stylesheet. --><!-- There are tables with width="100%" around the toplinks, to force NS4 to use the real width specified for the toplinks in the stylesheet. -->
	</div>'.$this->tmpl->wrap($bottomContent, $this->WMtableWrap).'
</div>
</div><!-- Here ends the foldoutmenu. -->
		';
		return '';
	}
}

$GLOBALS['TSFE']->tmpl->menuclasses.=',gmenu_foldout';


if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/gmenu_foldout.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['media/scripts/gmenu_foldout.php']);
}

?>