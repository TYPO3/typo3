<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 *  216:     function getIncFiles()
 *  249:     function JSeventFunctions()
 *  283:     function renderContent()
 *  310:     function renderContentWithHeader($pageContent)
 *
 *
 *  674: class FE_loadDBGroup extends t3lib_loadDBGroup
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
		$GLOBALS['TSFE']->sWordList = t3lib_div::_GP('sword_list');
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
			$GLOBALS['TSFE']->linkVars='';
			reset($linkVarArr);
			while(list(,$val)=each($linkVarArr))	{
				$val=trim($val);
				$GET = t3lib_div::_GET();
				if ($val && isset($GET[$val]))	{
					if (!is_array($GET[$val]))	{
						$GLOBALS['TSFE']->linkVars.='&'.$val.'='.rawurlencode($GET[$val]);
					} else {
						$GLOBALS['TSFE']->linkVars.=t3lib_div::implodeArrayForUrl($val,$GET[$val]);
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
			$GLOBALS['TSFE']->content = $pageContent;
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
		$theCharset = $GLOBALS['TSFE']->metaCharset;

			// Reset the content variables:
		$GLOBALS['TSFE']->content='';
		$htmlTagAttributes = array();
		$htmlLang = $GLOBALS['TSFE']->config['config']['htmlTag_langKey'] ? $GLOBALS['TSFE']->config['config']['htmlTag_langKey'] : 'en';

			// Set content direction: (More info: http://www.tau.ac.il/~danon/Hebrew/HTML_and_Hebrew.html)
		if ($GLOBALS['TSFE']->config['config']['htmlTag_dir'])	{
			$htmlTagAttributes['dir'] = htmlspecialchars($GLOBALS['TSFE']->config['config']['htmlTag_dir']);
		}

			// Setting document type:
		$docTypeParts = array();
		$XMLprologue = $GLOBALS['TSFE']->config['config']['xmlprologue'] != 'none';
		if ($GLOBALS['TSFE']->config['config']['doctype'])	{

				// Setting doctypes:
			switch((string)$GLOBALS['TSFE']->config['config']['doctype'])	{
				case 'xhtml_trans':
		 			if ($XMLprologue) $docTypeParts[]='<?xml version="1.0" encoding="'.$theCharset.'"?>';
					$docTypeParts[]='<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
				break;
				case 'xhtml_strict':
		 			if ($XMLprologue) $docTypeParts[]='<?xml version="1.0" encoding="'.$theCharset.'"?>';
					$docTypeParts[]='<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
				break;
				case 'xhtml_frames':
		 			if ($XMLprologue) $docTypeParts[]='<?xml version="1.0" encoding="'.$theCharset.'"?>';
					$docTypeParts[]='<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
				break;
				case 'xhtml_11':
		 			if ($XMLprologue) $docTypeParts[]='<?xml version="1.1" encoding="'.$theCharset.'"?>';
					$docTypeParts[]='<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.1//EN"
     "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
				break;
				case 'xhtml_2':
		 			if ($XMLprologue) $docTypeParts[]='<?xml version="2.0" encoding="'.$theCharset.'"?>';
					$docTypeParts[]='<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 2.0//EN"
	"http://www.w3.org/TR/xhtml2/DTD/xhtml2.dtd">';
				break;
				case 'none':
				break;
				default:
					$docTypeParts[] = $GLOBALS['TSFE']->config['config']['doctype'];
				break;
			}
				// Setting <html> tag attributes:
			switch((string)$GLOBALS['TSFE']->config['config']['doctype'])	{
				case 'xhtml_trans':
				case 'xhtml_strict':
				case 'xhtml_frames':
	 				$htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
					$htmlTagAttributes['xml:lang'] = $htmlLang;
					$htmlTagAttributes['lang'] = $htmlLang;
				break;
				case 'xhtml_11':
				case 'xhtml_2':
	 				$htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml';
					$htmlTagAttributes['xml:lang'] = $htmlLang;
				break;
			}
		} else {
			$docTypeParts[]='<!DOCTYPE html
	PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
		}

			// Swap XML and doctype order around (for MSIE / Opera standards compliance)
		if ($GLOBALS['TSFE']->config['config']['doctypeSwitch'])	{
			$docTypeParts = array_reverse($docTypeParts);
		}

			// Adding doctype parts:
		$GLOBALS['TSFE']->content.= count($docTypeParts) ? implode(chr(10),$docTypeParts).chr(10) : '';

			// Begin header section:
		if (strcmp($GLOBALS['TSFE']->config['config']['htmlTag_setParams'],'none'))	{
			$_attr = $GLOBALS['TSFE']->config['config']['htmlTag_setParams'] ? $GLOBALS['TSFE']->config['config']['htmlTag_setParams'] : t3lib_div::implodeParams($htmlTagAttributes);
		} else {
			$_attr = '';
		}
		$GLOBALS['TSFE']->content.='<html'.($_attr ? ' '.$_attr : '').'>
<head>
<!-- '.($customContent?$customContent.chr(10):'').'
	This website is brought to you by TYPO3 - get.content.right
	TYPO3 is a free open source Content Management Framework created by Kasper Skaarhoj and licensed under GNU/GPL.
	TYPO3 is copyright 1998-2004 of Kasper Skaarhoj. Extensions are copyright of their respective owners.
	Information and contribution at http://www.typo3.com
-->
';


		if ($GLOBALS['TSFE']->config['config']['baseURL']) {
			$ss = intval($GLOBALS['TSFE']->config['config']['baseURL']) ? t3lib_div::getIndpEnv('TYPO3_SITE_URL') : $GLOBALS['TSFE']->config['config']['baseURL'];
			$GLOBALS['TSFE']->content.='
	<base href="'.htmlspecialchars($ss).'" />';
		}

		if ($GLOBALS['TSFE']->pSetup['shortcutIcon']) {
			$ss=$path.$GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['shortcutIcon']);
			$GLOBALS['TSFE']->content.='
	<link rel="SHORTCUT ICON" href="'.htmlspecialchars($ss).'" />';
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
				if ($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile'])	{
					$GLOBALS['TSFE']->content.=TSpagegen::inline2TempFile(implode(chr(10),$temp_styleLines),'css');
				} else {
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
		}

		if ($GLOBALS['TSFE']->pSetup['stylesheet']) {
			$ss=$GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['stylesheet']);
			if ($ss) {
				$GLOBALS['TSFE']->content.='
	<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($ss).'" />';
			}
		}
		if (is_array($GLOBALS['TSFE']->pSetup['includeCSS.'])) {
			reset($GLOBALS['TSFE']->pSetup['includeCSS.']);
			while(list($k2,$iCSSfile)=each($GLOBALS['TSFE']->pSetup['includeCSS.']))	{
				if (!is_array($iCSSfile))	{
					$ss = $GLOBALS['TSFE']->tmpl->getFileName($iCSSfile);
					if ($ss) {
						if ($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['import'])	{
							if (substr($ss,0,1)!='/')	{	// To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
								$ss = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')).'/'.$ss;
							}
							$GLOBALS['TSFE']->content.='
	<style type="text/css">
	<!--
	@import url("'.htmlspecialchars($ss).'") '.htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['media']).';
	-->
	</style>
							';
						} else {
							$GLOBALS['TSFE']->content.='
	<link rel="'.($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['alternate'] ? 'alternate stylesheet' : 'stylesheet').'" type="text/css" href="'.htmlspecialchars($ss).'"'.
			($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['title'] ? ' title="'.htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['title']).'"' : '').
			($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['media'] ? ' media="'.htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$k2.'.']['media']).'"' : '').
			' />';
						}
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

			// Setting body tag margins in CSS:
		if (isset($GLOBALS['TSFE']->pSetup['bodyTagMargins']) && $GLOBALS['TSFE']->pSetup['bodyTagMargins.']['useCSS'])	{
			$margins = intval($GLOBALS['TSFE']->pSetup['bodyTagMargins']);
			$style.='
	BODY {margin: '.$margins.'px '.$margins.'px '.$margins.'px '.$margins.'px;}';
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
		if ($GLOBALS['TSFE']->pSetup['adminPanelStyles'])	{
			$style.='

	/* Default styles for the Admin Panel */
	TABLE.typo3-adminPanel { border: 1px solid black; background-color: #F6F2E6; }
	TABLE.typo3-adminPanel TR.typo3-adminPanel-hRow TD { background-color: #9BA1A8; }
	TABLE.typo3-adminPanel TR.typo3-adminPanel-itemHRow TD { background-color: #ABBBB4; }
	TABLE.typo3-adminPanel TABLE, TABLE.typo3-adminPanel TD { border: 0px; }
	TABLE.typo3-adminPanel TD FONT { font-family: verdana; font-size: 10px; color: black; }
	TABLE.typo3-adminPanel TD A FONT { font-family: verdana; font-size: 10px; color: black; }
	TABLE.typo3-editPanel { border: 1px solid black; background-color: #F6F2E6; }
	TABLE.typo3-editPanel TD { border: 0px; }
			';
		}

		if (trim($style))	{
			if ($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile'])	{
				$GLOBALS['TSFE']->content.=TSpagegen::inline2TempFile($style, 'css');
			} else {
				$GLOBALS['TSFE']->content.='
	<style type="text/css">
		/*<![CDATA[*/
	<!--'.$style.'
	-->
		/*]]>*/
	</style>';
			}
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

			// Adding default Java Script:
		$_scriptCode = '
		browserName = navigator.appName;
		browserVer = parseInt(navigator.appVersion);
		var msie4 = (browserName == "Microsoft Internet Explorer" && browserVer >= 4);
		if ((browserName == "Netscape" && browserVer >= 3) || msie4 || browserName=="Konqueror") {version = "n3";} else {version = "n2";}
			// Blurring links:
		function blurLink(theObject)	{	//
			if (msie4)	{theObject.blur();}
		}
		';
		if (!$GLOBALS['TSFE']->config['config']['removeDefaultJS']) {
				// NOTICE: The following code must be kept synchronized with "tslib/default.js"!!!
			$GLOBALS['TSFE']->content.='
	<script type="text/javascript">
		/*<![CDATA[*/
	<!--'.$_scriptCode.'
	// -->
		/*]]>*/
	</script>';
		} elseif ($GLOBALS['TSFE']->config['config']['removeDefaultJS']==='external')	{
			$GLOBALS['TSFE']->content.=TSpagegen::inline2TempFile($_scriptCode, 'js');
		}

		$GLOBALS['TSFE']->content.=chr(10).implode($GLOBALS['TSFE']->additionalHeaderData,chr(10)).'
'.$JSef[0].'
</head>';
		if ($GLOBALS['TSFE']->pSetup['frameSet.'])	{
			$fs = t3lib_div::makeInstance('tslib_frameset');
			$GLOBALS['TSFE']->content.=$fs->make($GLOBALS['TSFE']->pSetup['frameSet.']);
			$GLOBALS['TSFE']->content.= chr(10).'<noframes>'.chr(10);
		}

			// Bodytag:
		$defBT = $GLOBALS['TSFE']->pSetup['bodyTagCObject'] ? $GLOBALS['TSFE']->cObj->cObjGetSingle($GLOBALS['TSFE']->pSetup['bodyTagCObject'],$GLOBALS['TSFE']->pSetup['bodyTagCObject.'],'bodyTagCObject') : '';
		if (!$defBT)	$defBT = $GLOBALS['TSFE']->defaultBodyTag;
		$bodyTag = $GLOBALS['TSFE']->pSetup['bodyTag'] ? $GLOBALS['TSFE']->pSetup['bodyTag'] : $defBT;
		if ($bgImg=$GLOBALS['TSFE']->cObj->getImgResource($GLOBALS['TSFE']->pSetup['bgImg'],$GLOBALS['TSFE']->pSetup['bgImg.']))	{
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' background="'.$GLOBALS["TSFE"]->absRefPrefix.$bgImg[3].'">';
		}

		if (isset($GLOBALS['TSFE']->pSetup['bodyTagMargins']))	{
			$margins = intval($GLOBALS['TSFE']->pSetup['bodyTagMargins']);
			if ($GLOBALS['TSFE']->pSetup['bodyTagMargins.']['useCSS'])	{
				// Setting margins in CSS, see above
			} else {
				$bodyTag = ereg_replace('>$','',trim($bodyTag)).' leftmargin="'.$margins.'" topmargin="'.$margins.'" marginwidth="'.$margins.'" marginheight="'.$margins.'">';
			}
		}

		if (trim($GLOBALS['TSFE']->pSetup['bodyTagAdd']))	{
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' '.trim($GLOBALS['TSFE']->pSetup['bodyTagAdd']).'>';
		}

		if (count($JSef[1]))	{	// Event functions:
			$bodyTag = ereg_replace('>$','',trim($bodyTag)).' '.trim(implode(' ',$JSef[1])).'>';
		}
		$GLOBALS['TSFE']->content.= chr(10).$bodyTag;


		// Div-sections
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













	/*************************
	 *
	 * Helper functions
	 * Remember: Calls internally must still be done on the non-instantiated class: TSpagegen::inline2TempFile()
	 *
	 *************************/

	/**
	 * Writes string to a temporary file named after the md5-hash of the string
	 *
	 * @param	string		CSS styles / JavaScript to write to file.
	 * @param	string		Extension: "css" or "js"
	 * @return	string		<script> or <link> tag for the file.
	 */
	function inline2TempFile($str,$ext)	{

			// Create filename / tags:
		$script = '';
		switch($ext)	{
			case 'js':
				$script = 'typo3temp/javascript_'.substr(md5($str),0,10).'.js';
				$output = '
	<script type="text/javascript" src="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.$script).'"></script>';
			break;
			case 'css':
				$script = 'typo3temp/stylesheet_'.substr(md5($str),0,10).'.css';
				$output = '
	<link rel="stylesheet" type="text/css" href="'.htmlspecialchars($GLOBALS['TSFE']->absRefPrefix.$script).'" />';
			break;
		}

			// Write file:
		if ($script)	{
			if (!@is_file(PATH_site.$script))	{
				t3lib_div::writeFile(PATH_site.$script,$str);
			}
		}

		return $output;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php']);
}



// ********************************************************
// Includes the search-class if $sword and $scols are set.
// ********************************************************
if (t3lib_div::_GP('sword') && t3lib_div::_GP('scols'))	{
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
