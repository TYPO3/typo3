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
 * Libraries for pagegen.php
 * The script "pagegen.php" is included by "index_ts.php" when a page is not cached but needs to be rendered.
 *
 * $Id$
 * Revised for TYPO3 3.6 June/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   85: class TSpagegen 
 *   92:     function pagegenInit()	
 *  215:     function getIncFiles()	
 *  248:     function JSeventFunctions()	
 *  282:     function renderContent()	
 *  309:     function renderContentWithHeader($pageContent)	
 *
 *
 *  610: class FE_loadDBGroup extends t3lib_loadDBGroup	
 *
 * TOTAL FUNCTIONS: 5
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

 

















/**
 * Class for starting TypoScript page generation
 * 
 * The class is not instantiated as an objects but called directly with the "::" operator.
 * eg: TSpagegen::pagegenInit()
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class TSpagegen {

	/**
	 * Setting some vars in TSFE, primarily based on TypoScript config settings.
	 * 
	 * @return	void		
	 */
	function pagegenInit()	{
		if ($GLOBALS['TSFE']->page['content_from_pid']>0)	{
			$temp_copy_TSFE = $GLOBALS['TSFE'];	// make REAL copy of TSFE object - not reference!
			$temp_copy_TSFE->id = $GLOBALS['TSFE']->page['content_from_pid'];	// Set ->id to the content_from_pid value - we are going to evaluate this pid as was it a given id for a page-display!
			$temp_copy_TSFE->getPageAndRootlineWithDomain($GLOBALS['TSFE']->config['config']['content_from_pid_allowOutsideDomain']?0:$GLOBALS['TSFE']->domainStartPage);
			$GLOBALS['TSFE']->contentPid = intval($temp_copy_TSFE->id);
			unset($temp_copy_TSFE);
		}
		if ($GLOBALS['TSFE']->config['config']['MP_defaults'])	{
			$temp_parts = t3lib_div::trimExplode('|',$GLOBALS['TSFE']->config['config']['MP_defaults'],1);
			reset($temp_parts);
			while(list(,$temp_p)=each($temp_parts))	{
				list($temp_idP,$temp_MPp) = explode(':',$temp_p,2);
				$temp_ids=t3lib_div::intExplode(',',$temp_idP);
				reset($temp_ids);
				while(list(,$temp_id)=each($temp_ids))	{
					$GLOBALS['TSFE']->MP_defaults[$temp_id]=$temp_MPp;
				}
			}
		}
		if ($GLOBALS['TSFE']->config['config']['simulateStaticDocuments_pEnc_onlyP'])	{
			$temp_parts = t3lib_div::trimExplode(',',$GLOBALS['TSFE']->config['config']['simulateStaticDocuments_pEnc_onlyP'],1);
			foreach ($temp_parts as $temp_p)	{
				$GLOBALS['TSFE']->pEncAllowedParamNames[$temp_p]=1;
			}
		}
		

			// Global vars...
		$GLOBALS['TSFE']->indexedDocTitle = $GLOBALS['TSFE']->page['title'];
		$GLOBALS['TSFE']->debug = ''.$GLOBALS['TSFE']->config['config']['debug'];

			// Internal and External target defaults
		$GLOBALS['TSFE']->intTarget = ''.$GLOBALS['TSFE']->config['config']['intTarget'];
		$GLOBALS['TSFE']->extTarget = ''.$GLOBALS['TSFE']->config['config']['extTarget'];
		$GLOBALS['TSFE']->spamProtectEmailAddresses = t3lib_div::intInRange($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses'],-5,5,0);
		if ($GLOBALS['TSFE']->spamProtectEmailAddresses)	{
			$GLOBALS['TSFE']->additionalJavaScript['UnCryptMailto()']='
  // JS function for uncrypting spam-protected emails:
function UnCryptMailto(s) {	//
	var n=0;
	var r="";
	for(var i=0; i < s.length; i++) { 
		n=s.charCodeAt(i); 
		if (n>=8364) {n = 128;}
		r += String.fromCharCode(n-('.$GLOBALS['TSFE']->spamProtectEmailAddresses.')); 
	}
	return r;
}
  // JS function for uncrypting spam-protected emails:
function linkTo_UnCryptMailto(s)	{	//
	location.href=UnCryptMailto(s);
}
		';
		}

		
		$GLOBALS['TSFE']->absRefPrefix = trim(''.$GLOBALS['TSFE']->config['config']['absRefPrefix']);
		if ((!strcmp($GLOBALS['TSFE']->config['config']['simulateStaticDocuments'],'PATH_INFO') || $GLOBALS['TSFE']->absRefPrefix_force) 
				&& !$GLOBALS['TSFE']->absRefPrefix)	{
			$GLOBALS['TSFE']->absRefPrefix=t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/'; 
		}		// Force absRefPrefix to this value is PATH_INFO is used.
		
		if ($GLOBALS['TSFE']->type && $GLOBALS['TSFE']->config['config']['frameReloadIfNotInFrameset'])	{
			$tdlLD = $GLOBALS['TSFE']->tmpl->linkData($GLOBALS['TSFE']->page,'_top',$GLOBALS['TSFE']->no_cache,'');
			$GLOBALS['TSFE']->JSCode = 'if(!parent.'.trim($GLOBALS['TSFE']->sPre).' && !parent.view_frame) top.document.location="'.$tdlLD['totalURL'].'"';
		}		
		$GLOBALS['TSFE']->compensateFieldWidth = ''.$GLOBALS['TSFE']->config['config']['compensateFieldWidth'];  
		$GLOBALS['TSFE']->lockFilePath = ''.$GLOBALS['TSFE']->config['config']['lockFilePath'];  
		$GLOBALS['TSFE']->lockFilePath = $GLOBALS['TSFE']->lockFilePath ? $GLOBALS['TSFE']->lockFilePath : 'fileadmin/';
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'] = isset($GLOBALS['TSFE']->config['config']['noScaleUp']) ? ''.$GLOBALS['TSFE']->config['config']['noScaleUp'] : $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
		$GLOBALS['TSFE']->TYPO3_CONF_VARS['GFX']['im_noScaleUp'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
		
		$GLOBALS['TSFE']->ATagParams = trim($GLOBALS['TSFE']->config['config']['ATagParams']) ? ' '.trim($GLOBALS['TSFE']->config['config']['ATagParams']) : '';
		if ($GLOBALS['TSFE']->config['config']['setJS_mouseOver'])	$GLOBALS['TSFE']->setJS('mouseOver');
		if ($GLOBALS['TSFE']->config['config']['setJS_openPic'])	$GLOBALS['TSFE']->setJS('openPic');
		
		$GLOBALS['TSFE']->sWordRegEx='';
		$GLOBALS['TSFE']->sWordList = t3lib_div::GPvar('sword_list');
		if (is_array($GLOBALS['TSFE']->sWordList))	{
			$standAlone = trim(''.$GLOBALS['TSFE']->config['config']['sword_standAlone']);
			$noMixedCase = trim(''.$GLOBALS['TSFE']->config['config']['sword_noMixedCase']);
		
			$space = ($standAlone) ? '[[:space:]]' : '';
			reset($GLOBALS['TSFE']->sWordList);
			while (list($key,$val) = each($GLOBALS['TSFE']->sWordList))	{
				if (trim($val)) {
					if (!$noMixedCase) {
						$GLOBALS['TSFE']->sWordRegEx.= $space.sql_regcase(quotemeta($val)).$space.'|';
					} else {
						$GLOBALS['TSFE']->sWordRegEx.= $space.quotemeta($val).$space.'|';
					}
				}
			}
			$GLOBALS['TSFE']->sWordRegEx = ereg_replace('\|$','',$GLOBALS['TSFE']->sWordRegEx);
		}
		
			// linkVars
		$GLOBALS['TSFE']->linkVars = ''.$GLOBALS['TSFE']->config['config']['linkVars'];
		if ($GLOBALS['TSFE']->linkVars)	{
			$linkVarArr = explode(',',$GLOBALS['TSFE']->linkVars);
			reset($linkVarArr);
			$GLOBALS['TSFE']->linkVars='';
			while(list(,$val)=each($linkVarArr))	{
				$val=trim($val);
				if ($val && isset($GLOBALS['HTTP_GET_VARS'][$val]))	{
					if (!is_array($GLOBALS['HTTP_GET_VARS'][$val]))	{
						$GLOBALS['TSFE']->linkVars.='&'.$val.'='.rawurlencode($GLOBALS['HTTP_GET_VARS'][$val]);
					} else {
						$GLOBALS['TSFE']->linkVars.=t3lib_div::implodeArrayForUrl($val,$GLOBALS['HTTP_GET_VARS'][$val]);
					}
				}
			}
		} else {
			$GLOBALS['TSFE']->linkVars='';
		}
	}

	/**
	 * Returns an array with files to include. These files are the ones set up in TypoScript config.
	 * 
	 * @return	array		Files to include. Paths are relative to PATH_site.
	 */
	function getIncFiles()	{
		$incFilesArray = array();
			// Get files from config.includeLibrary
		$includeLibrary = trim(''.$GLOBALS['TSFE']->config['config']['includeLibrary']);
		if ($includeLibrary)	{
			$incFile=$GLOBALS['TSFE']->tmpl->getFileName($includeLibrary);
			if ($incFile)	{
				$incFilesArray[] = $incFile;
			}
		}
		
		if (is_array($GLOBALS['TSFE']->pSetup['includeLibs.']))	{$incLibs=$GLOBALS['TSFE']->pSetup['includeLibs.'];} else {$incLibs=array();}
		if (is_array($GLOBALS['TSFE']->tmpl->setup['includeLibs.']))	{$incLibs+=$GLOBALS['TSFE']->tmpl->setup['includeLibs.'];}	// toplevel 'includeLibs' is added to the PAGE.includeLibs. In that way, PAGE-libs get first priority, because if the key already exist, it's not altered. (Due to investigation by me)
		if (count($incLibs))	{
			reset($incLibs);
			while(list(,$theLib)=each($incLibs))	{
				if (!is_array($theLib) && $incFile=$GLOBALS['TSFE']->tmpl->getFileName($theLib))	{
					$incFilesArray[] = $incFile;
				}
			}
		}
			// Include HTML mail library?
		if ($GLOBALS['TSFE']->config['config']['incT3Lib_htmlmail'])	{
			$incFilesArray[] = 't3lib/class.t3lib_htmlmail.php';
		}
		return $incFilesArray;
	}
	
	/**
	 * Processing JavaScript handlers
	 * 
	 * @return	array		Array with a) a JavaScript section with event handlers and variables set and b) an array with attributes for the body tag.
	 */
	function JSeventFunctions()	{
		$functions=array();
		$setEvents=array();
		$setBody=array();

		if (is_array($GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']) && count($GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']))	{
			$functions[]='	function T3_onmousemoveWrapper(e)	{	'.implode('   ',$GLOBALS['TSFE']->JSeventFuncCalls['onmousemove']).'	}';
			$setEvents[]='	document.onmousemove=T3_onmousemoveWrapper;';
		}
		if (is_array($GLOBALS['TSFE']->JSeventFuncCalls['onmouseup']) && count($GLOBALS['TSFE']->JSeventFuncCalls['onmouseup']))	{
			$functions[]='	function T3_onmouseupWrapper(e)	{	'.implode('   ',$GLOBALS['TSFE']->JSeventFuncCalls['onmouseup']).'	}';
			$setEvents[]='	document.onmouseup=T3_onmouseupWrapper;';
		}
		if (is_array($GLOBALS['TSFE']->JSeventFuncCalls['onload']) && count($GLOBALS['TSFE']->JSeventFuncCalls['onload']))	{
			$functions[]='	function T3_onloadWrapper()	{	'.implode('   ',$GLOBALS['TSFE']->JSeventFuncCalls['onload']).'	}';
			$setEvents[]='	document.onload=T3_onloadWrapper;';
			$setBody[]='onload="T3_onloadWrapper();"';
		}

		return Array(count($functions)?'
<script type="text/javascript">
	/*<![CDATA[*/
'.implode(chr(10),$functions).'
'.implode(chr(10),$setEvents).'
	/*]]>*/
</script>
			':'',$setBody);
	}

	/**
	 * Rendering the page content
	 * 
	 * @return	void		
	 */
	function renderContent()	{
		// PAGE CONTENT
		$GLOBALS['TT']->incStackPointer();
		$GLOBALS['TT']->push($GLOBALS['TSFE']->sPre, 'PAGE');
			$pageContent = $GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup);

			if ($GLOBALS['TSFE']->pSetup['wrap'])	{$pageContent = $GLOBALS['TSFE']->cObj->wrap($pageContent, $GLOBALS['TSFE']->pSetup['wrap']);}
			if ($GLOBALS['TSFE']->pSetup['stdWrap.'])	{$pageContent = $GLOBALS['TSFE']->cObj->stdWrap($pageContent, $GLOBALS['TSFE']->pSetup['stdWrap.']);}
		
			// PAGE HEADER (after content - maybe JS is inserted!
		
			// if 'disableAllHeaderCode' is set, all the header-code is discarded!
		if ($GLOBALS['TSFE']->config['config']['disableAllHeaderCode'])	{
			$GLOBALS['TSFE']->content=$pageContent;
		} else {			
			TSpagegen::renderContentWithHeader($pageContent);
		}
		$GLOBALS['TT']->pull($GLOBALS['TT']->LR?$GLOBALS['TSFE']->content:'');
		$GLOBALS['TT']->decStackPointer();
	}
	
	/**
	 * Rendering normal HTML-page with header by wrapping the generated content ($pageContent) in body-tags and setting the header accordingly.
	 * 
	 * @param	string		The page content which TypoScript objects has generated
	 * @return	void		
	 */
	function renderContentWithHeader($pageContent)	{
		$customContent = $GLOBALS['TSFE']->config['config']['headerComment'];
		if (trim($customContent))	{
			$customContent = chr(10).$customContent;
		} else $customContent='';

			// Setting charset:		
		$theCharset = ($GLOBALS['TSFE']->config['config']['metaCharset'] ? $GLOBALS['TSFE']->config['config']['metaCharset'] : 'iso-8859-1');
		
			// Reset the content variables:
		$GLOBALS['TSFE']->content='';

			// Setting document type:
		switch((string)$GLOBALS['TSFE']->config['config']['doctype'])	{
			case 'xhtml_trans':
				$GLOBALS['TSFE']->content.='<?xml version="1.0" encoding="'.$theCharset.'"?>
<!DOCTYPE html 
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
			break;
			default:
				$GLOBALS['TSFE']->content.='<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
			break;
		}
		
		$GLOBALS['TSFE']->content.='
<html>
<head>
<!-- '.($customContent?$customContent.chr(10):'').'
	This website is brought to you by TYPO3 - get.content.right
	TYPO3 is a free open source Content Management Framework
	created by Kasper Skaarhoej and licensed under GNU/GPL.
	Information and contribution at http://www.typo3.com
-->
';
		
		
		if ($GLOBALS['TSFE']->pSetup['shortcutIcon']) {
			$ss=$path.$GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['shortcutIcon']);
			$GLOBALS['TSFE']->content.='
<link rel="SHORTCUT ICON" href="'.$ss.'" />';
		}
		
		




		/** CSS STYLESHEET handling: */
		if (is_array($GLOBALS['TSFE']->tmpl->setup['plugin.'])) {
			$temp_styleLines=array();
			reset($GLOBALS['TSFE']->tmpl->setup['plugin.']);
			while(list($k2,$iCSScode)=each($GLOBALS['TSFE']->tmpl->setup['plugin.']))	{
				if (is_array($iCSScode) && $iCSScode['_CSS_DEFAULT_STYLE'])	{
					$temp_styleLines[]='/* default styles for extension "'.substr($k2,0,-1).'" */'.chr(10).$iCSScode['_CSS_DEFAULT_STYLE'];
				}
			}
			if (count($temp_styleLines))	{
				$GLOBALS['TSFE']->content.='
<style type="text/css">
	/*<![CDATA[*/
<!--
'.implode(chr(10),$temp_styleLines).'
-->
	/*]]>*/
</style>';
			}
		}

		if ($GLOBALS['TSFE']->pSetup['stylesheet']) {
			$ss=$GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['stylesheet']);
			if ($ss) {
				$GLOBALS['TSFE']->content.='
	<link rel="stylesheet" href="'.htmlspecialchars($ss).'" />';
			}
		}
		if (is_array($GLOBALS['TSFE']->pSetup['includeCSS.'])) {
			reset($GLOBALS['TSFE']->pSetup['includeCSS.']);
			while(list($k2,$iCSSfile)=each($GLOBALS['TSFE']->pSetup['includeCSS.']))	{
				if (!is_array($iCSSfile))	{
					$ss=$GLOBALS['TSFE']->tmpl->getFileName($iCSSfile);
					if ($ss) {
						$GLOBALS['TSFE']->content.='
	<link rel="stylesheet" href="'.htmlspecialchars($ss).'"'.
			($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['title'] ? ' title="'.htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['title']).'"' : '').
			($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['media'] ? ' media="'.htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['media']).'"' : '').
			' />';
					}
				}
			}
		}

		// Stylesheets
		$style='';
		$style.=trim($GLOBALS['TSFE']->pSetup['CSS_inlineStyle']).chr(10);
		
		if ($GLOBALS['TSFE']->pSetup['insertClassesFromRTE'])	{
			$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
			$RTEclasses = $pageTSConfig['RTE.']['classes.'];
			if (is_array($RTEclasses))	{
				reset($RTEclasses);
				while(list($RTEclassName,$RTEvalueArray)=each($RTEclasses))	{
					if ($RTEvalueArray['value'])	{
						$style.='
.'.substr($RTEclassName,0,-1).' {'.$RTEvalueArray['value'].'}';
					}
				}
			}

			if ($GLOBALS['TSFE']->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs'] && is_array($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.']))	{
				$mSOa_tList = t3lib_div::trimExplode(',',strtoupper($GLOBALS['TSFE']->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs']),1);
				reset($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.']);
				while(list($mSOa_key,$mSOa_value)=each($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.']))	{
					if (!is_array($mSOa_value) && (in_array('*',$mSOa_tList)||in_array($mSOa_key,$mSOa_tList)))	{
						$style.='
'.$mSOa_key.' {'.$mSOa_value.'}';
					}
				}
			}
		}
		
		if ($GLOBALS['TSFE']->pSetup['noLinkUnderline'])	{
			$style.='
A:link {text-decoration: none}
A:visited {text-decoration: none}
A:active {text-decoration: none}';
		}
		if (trim($GLOBALS['TSFE']->pSetup['hover']))	{
			$style.='
A:hover {color: '.trim($GLOBALS['TSFE']->pSetup['hover']).';}';
		}
		if (trim($GLOBALS['TSFE']->pSetup['hoverStyle']))	{
			$style.='
A:hover {'.trim($GLOBALS['TSFE']->pSetup['hoverStyle']).'}';
		}
		if ($GLOBALS['TSFE']->pSetup['smallFormFields'])	{
			$style.='
SELECT {  font-family: Verdana, Arial, Helvetica; font-size: 10px }
TEXTAREA  {  font-family: Verdana, Arial, Helvetica; font-size: 10px} 
INPUT   {  font-family: Verdana, Arial, Helvetica; font-size: 10px }';
		}
		
		if (trim($style))	{
		$GLOBALS['TSFE']->content.='
<style type="text/css">
	/*<![CDATA[*/
<!--'.$style.'
-->
	/*]]>*/
</style>';
		}
	




		// Headerdata
		if (is_array($GLOBALS['TSFE']->pSetup['headerData.']))	{
			$GLOBALS['TSFE']->content.= chr(10).$GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['headerData.'],'headerData.');
		}

			// <title></title> :
		$titleTagContent = $GLOBALS['TSFE']->tmpl->printTitle(
			$GLOBALS['TSFE']->altPageTitle?$GLOBALS['TSFE']->altPageTitle:$GLOBALS['TSFE']->page['title'],
			$GLOBALS['TSFE']->config['config']['noPageTitle'],
			$GLOBALS['TSFE']->config['config']['pageTitleFirst']
		);
		if ($GLOBALS['TSFE']->config['config']['titleTagFunction'])	{
			$titleTagContent = $GLOBALS['TSFE']->cObj->callUserFunction($GLOBALS['TSFE']->config['config']['titleTagFunction'], array(), $titleTagContent);
		}
		
		$GLOBALS['TSFE']->content.='
<title>'.htmlspecialchars($titleTagContent).'</title>';
		$GLOBALS['TSFE']->content.='
<meta http-equiv="Content-Type" content="text/html; charset='.$theCharset.'" />';
		$GLOBALS['TSFE']->content.='
<meta name="generator" content="TYPO3 3.6 CMS" />';
		
		$conf=$GLOBALS['TSFE']->pSetup['meta.'];
		if (is_array($conf))	{
			reset($conf);
			while(list($theKey,$theValue)=each($conf))	{
				if (!strstr($theKey,'.') || !isset($conf[substr($theKey,0,-1)]))	{		// Only if 1) the property is set but not the value itself, 2) the value and/or any property
					if (strstr($theKey,'.'))	{
						$theKey = substr($theKey,0,-1);
					}
					$val = $GLOBALS['TSFE']->cObj->stdWrap($conf[$theKey],$conf[$theKey.'.']);
					$key = $theKey;
					if (trim($val))	{
						$a='name';
						if (strtolower($key)=='refresh')	{$a='http-equiv';}
						$GLOBALS['TSFE']->content.= '
<meta '.$a.'="'.$key.'" content="'.htmlspecialchars(trim($val)).'" />';
					}
				}
			}
		}
	
		unset($GLOBALS['TSFE']->additionalHeaderData['JSCode']);
		unset($GLOBALS['TSFE']->additionalHeaderData['JSImgCode']);
	
		if (is_array($GLOBALS['TSFE']->config['INTincScript']))	{
				// Storing the JSCode and JSImgCode vars...
			$GLOBALS['TSFE']->additionalHeaderData['JSCode'] = $GLOBALS['TSFE']->JSCode;	
			$GLOBALS['TSFE']->additionalHeaderData['JSImgCode'] = $GLOBALS['TSFE']->JSImgCode;
			$GLOBALS['TSFE']->config['INTincScript_ext']['divKey']= $GLOBALS['TSFE']->uniqueHash();
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalHeaderData']	= $GLOBALS['TSFE']->additionalHeaderData;	// Storing the header-data array
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalJavaScript']	= $GLOBALS['TSFE']->additionalJavaScript;	// Storing the JS-data array
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalCSS']	= $GLOBALS['TSFE']->additionalCSS;	// Storing the Style-data array

			$GLOBALS['TSFE']->additionalHeaderData=array('<!--HD_'.$GLOBALS['TSFE']->config['INTincScript_ext']['divKey'].'-->');	// Clearing the array
			$GLOBALS['TSFE']->divSection.='<!--TDS_'.$GLOBALS['TSFE']->config['INTincScript_ext']['divKey'].'-->';
		} else {
			$GLOBALS['TSFE']->INTincScript_loadJSCode();
		}
		$JSef = TSpagegen::JSeventFunctions();

		$GLOBALS['TSFE']->content.='
<script type="text/javascript">
	/*<![CDATA[*/
<!--
	browserName = navigator.appName;
	browserVer = parseInt(navigator.appVersion);
	var msie4 = (browserName == "Microsoft Internet Explorer" && browserVer >= 4);
	if ((browserName == "Netscape" && browserVer >= 3) || msie4 || browserName=="Konqueror") {version = "n3";} else {version = "n2";}
		// Blurring links:
	function blurLink(theObject)	{	//
		if (msie4)	{theObject.blur();}
	}
// -->
	/*]]>*/
</script>
'.implode($GLOBALS['TSFE']->additionalHeaderData,chr(10)).'
'.$JSef[0].'
</head>';
		if ($GLOBALS['TSFE']->pSetup['frameSet.'])	{
			$fs = t3lib_div::makeInstance('tslib_frameset');
			$GLOBALS['TSFE']->content.=$fs->make($GLOBALS['TSFE']->pSetup['frameSet.']);
			$GLOBALS['TSFE']->content.= chr(10).'<noframes>'.chr(10);
		}
		
		// Bodytag:
		$defBT = $GLOBALS['TSFE']->pSetup['bodyTagCObject'] ? $GLOBALS['TSFE']->cObj->cObjGetSingle($GLOBALS['TSFE']->pSetup['bodyTagCObject'],$GLOBALS['TSFE']->pSetup['bodyTagCObject.'],'bodyTagCObject') : '';
		if (!$defBT)	$defBT = '<body bgcolor="#FFFFFF">';
		$bodyTag = $GLOBALS['TSFE']->pSetup['bodyTag'] ? $GLOBALS['TSFE']->pSetup['bodyTag'] : $defBT;
		if ($bgImg=$GLOBALS['TSFE']->cObj->getImgResource($GLOBALS['TSFE']->pSetup['bgImg'],$GLOBALS['TSFE']->pSetup['bgImg.']))	{
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' background="'.$GLOBALS["TSFE"]->absRefPrefix.$bgImg[3].'">';
		}
		if (isset($GLOBALS['TSFE']->pSetup['bodyTagMargins']))	{
			$margins = $GLOBALS['TSFE']->pSetup['bodyTagMargins'];
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' leftmargin="'.$margins.'" topmargin="'.$margins.'" marginwidth="'.$margins.'" marginheight="'.$margins.'">';
		}
		if (trim($GLOBALS['TSFE']->pSetup['bodyTagAdd']))	{
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' '.trim($GLOBALS['TSFE']->pSetup['bodyTagAdd']).'>';
		}
		if (count($JSef[1]))	{	// Event functions:
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' '.trim(implode(' ',$JSef[1])).'>';
		}
		

		// Div-sections			
		$GLOBALS['TSFE']->content.= chr(10).$bodyTag;
		if ($GLOBALS['TSFE']->divSection)	{
			$GLOBALS['TSFE']->content.=	chr(10).$GLOBALS['TSFE']->divSection;
		}
		
		// Page content
		$GLOBALS['TSFE']->content.=chr(10).$pageContent;
		
		// Ending page
		$GLOBALS['TSFE']->content.= chr(10).'</body>';
		if ($GLOBALS['TSFE']->pSetup['frameSet.'])	{
			$GLOBALS['TSFE']->content.= chr(10).'</noframes>';
		}
		$GLOBALS['TSFE']->content.=chr(10).'</html>';
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php']);
}



// ********************************************************
// Includes the search-class if $sword and $scols are set.
// ********************************************************
if (t3lib_div::GPvar('sword') && t3lib_div::GPvar('scols'))	{
	require_once(PATH_tslib.'class.tslib_search.php');
}

// ************
// LoadDBGroup
// ************
require_once (PATH_t3lib.'class.t3lib_loaddbgroup.php');

/**
 * Class for fetching record relations for the frontend.
 * 
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @see tslib_cObj::RECORDS()
 */
class FE_loadDBGroup extends t3lib_loadDBGroup	{
	var $fromTC = 0;		// Means the not only uid and label-field is returned, but everything
}

// **********************************
// includes stuff for graphical work
// **********************************
require_once(PATH_t3lib.'class.t3lib_stdgraphic.php');
require_once(PATH_tslib.'class.tslib_gifbuilder.php');

// *************************
// includes menu-management
// *************************
require_once(PATH_tslib.'class.tslib_menu.php');

// *************************
// Global content object...
// *************************
require_once(PATH_tslib.'class.tslib_content.php');

?>