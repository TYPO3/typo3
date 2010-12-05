<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Revised for TYPO3 3.6 June/2003 by Kasper Skårhøj
 * XHTML compliant
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   88: class TSpagegen
 *   95:     function pagegenInit()
 *  271:     function getIncFiles()
 *  304:     function JSeventFunctions()
 *  338:     function renderContent()
 *  365:     function renderContentWithHeader($pageContent)
 *
 *              SECTION: Helper functions
 *  827:     function inline2TempFile($str,$ext)
 *
 *
 *  881: class FE_loadDBGroup extends t3lib_loadDBGroup
 *
 * TOTAL FUNCTIONS: 6
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */



















/**
 * Class for starting TypoScript page generation
 *
 * The class is not instantiated as an objects but called directly with the "::" operator.
 * eg: TSpagegen::pagegenInit()
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 */
class TSpagegen {

	/**
	 * Setting some vars in TSFE, primarily based on TypoScript config settings.
	 *
	 * @return	void
	 */
	public static function pagegenInit() {
		if ($GLOBALS['TSFE']->page['content_from_pid']>0)	{
			$temp_copy_TSFE = clone($GLOBALS['TSFE']);	// make REAL copy of TSFE object - not reference!
			$temp_copy_TSFE->id = $GLOBALS['TSFE']->page['content_from_pid'];	// Set ->id to the content_from_pid value - we are going to evaluate this pid as was it a given id for a page-display!
			$temp_copy_TSFE->getPageAndRootlineWithDomain($GLOBALS['TSFE']->config['config']['content_from_pid_allowOutsideDomain']?0:$GLOBALS['TSFE']->domainStartPage);
			$GLOBALS['TSFE']->contentPid = intval($temp_copy_TSFE->id);
			unset($temp_copy_TSFE);
		}
		if ($GLOBALS['TSFE']->config['config']['MP_defaults'])	{
			$temp_parts = t3lib_div::trimExplode('|',$GLOBALS['TSFE']->config['config']['MP_defaults'],1);
			foreach ($temp_parts as $temp_p) {
				list($temp_idP,$temp_MPp) = explode(':',$temp_p,2);
				$temp_ids=t3lib_div::intExplode(',',$temp_idP);
				foreach ($temp_ids as $temp_id) {
					$GLOBALS['TSFE']->MP_defaults[$temp_id]=$temp_MPp;
				}
			}
		}

			// Global vars...
		$GLOBALS['TSFE']->indexedDocTitle = $GLOBALS['TSFE']->page['title'];
		$GLOBALS['TSFE']->debug = ''.$GLOBALS['TSFE']->config['config']['debug'];

			// Base url:
		if ($GLOBALS['TSFE']->config['config']['baseURL'])	{
			if ($GLOBALS['TSFE']->config['config']['baseURL']==='1')	{
					// Deprecated property, going to be dropped in TYPO3 4.7.
				$error = 'Unsupported TypoScript property was found in this template: "config.baseURL="1"

This setting has been deprecated in TYPO 3.8.1 due to security concerns.
You need to change this value to the URL of your website root, otherwise TYPO3 will not work!

See <a href="http://wiki.typo3.org/index.php/TYPO3_3.8.1" target="_blank">wiki.typo3.org/index.php/TYPO3_3.8.1</a> for more information.';
				throw new RuntimeException(nl2br($error));
			} else {
				$GLOBALS['TSFE']->baseUrl = $GLOBALS['TSFE']->config['config']['baseURL'];
			}
			$GLOBALS['TSFE']->anchorPrefix = substr(t3lib_div::getIndpEnv('TYPO3_REQUEST_URL'),strlen(t3lib_div::getIndpEnv('TYPO3_SITE_URL')));
		}

			// Internal and External target defaults
		$GLOBALS['TSFE']->intTarget = ''.$GLOBALS['TSFE']->config['config']['intTarget'];
		$GLOBALS['TSFE']->extTarget = ''.$GLOBALS['TSFE']->config['config']['extTarget'];
		$GLOBALS['TSFE']->fileTarget = ''.$GLOBALS['TSFE']->config['config']['fileTarget'];
		if ($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses'] === 'ascii') {
			$GLOBALS['TSFE']->spamProtectEmailAddresses = 'ascii';
		} else {
			$GLOBALS['TSFE']->spamProtectEmailAddresses = t3lib_div::intInRange($GLOBALS['TSFE']->config['config']['spamProtectEmailAddresses'],-10,10,0);
		}

		$GLOBALS['TSFE']->absRefPrefix = ($GLOBALS['TSFE']->config['config']['absRefPrefix'] ? trim($GLOBALS['TSFE']->config['config']['absRefPrefix']) : '');

		if ($GLOBALS['TSFE']->type && $GLOBALS['TSFE']->config['config']['frameReloadIfNotInFrameset'])	{
			$tdlLD = $GLOBALS['TSFE']->tmpl->linkData($GLOBALS['TSFE']->page,'_top',$GLOBALS['TSFE']->no_cache,'');
			$GLOBALS['TSFE']->JSCode = 'if(!parent.'.trim($GLOBALS['TSFE']->sPre).' && !parent.view_frame) top.location.href="'.$GLOBALS['TSFE']->baseUrlWrap($tdlLD['totalURL']).'"';
		}
		$GLOBALS['TSFE']->compensateFieldWidth = ''.$GLOBALS['TSFE']->config['config']['compensateFieldWidth'];
		$GLOBALS['TSFE']->lockFilePath = ''.$GLOBALS['TSFE']->config['config']['lockFilePath'];
		$GLOBALS['TSFE']->lockFilePath = $GLOBALS['TSFE']->lockFilePath ? $GLOBALS['TSFE']->lockFilePath : $GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'];
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'] = isset($GLOBALS['TSFE']->config['config']['noScaleUp']) ? ''.$GLOBALS['TSFE']->config['config']['noScaleUp'] : $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];
		$GLOBALS['TSFE']->TYPO3_CONF_VARS['GFX']['im_noScaleUp'] = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noScaleUp'];

		$GLOBALS['TSFE']->ATagParams = trim($GLOBALS['TSFE']->config['config']['ATagParams']) ? ' '.trim($GLOBALS['TSFE']->config['config']['ATagParams']) : '';
		if ($GLOBALS['TSFE']->config['config']['setJS_mouseOver'])	$GLOBALS['TSFE']->setJS('mouseOver');
		if ($GLOBALS['TSFE']->config['config']['setJS_openPic'])	$GLOBALS['TSFE']->setJS('openPic');

		$GLOBALS['TSFE']->sWordRegEx='';
		$GLOBALS['TSFE']->sWordList = t3lib_div::_GP('sword_list');
		if (is_array($GLOBALS['TSFE']->sWordList))	{
			$space = (!empty($GLOBALS['TSFE']->config['config']['sword_standAlone'])) ? '[[:space:]]' : '';

			foreach ($GLOBALS['TSFE']->sWordList as $val) {
				if (strlen(trim($val)) > 0) {
						$GLOBALS['TSFE']->sWordRegEx.= $space.quotemeta($val).$space.'|';
				}
			}
			$GLOBALS['TSFE']->sWordRegEx = preg_replace('/\|$/','',$GLOBALS['TSFE']->sWordRegEx);
		}

			// linkVars
		$linkVars = (string)$GLOBALS['TSFE']->config['config']['linkVars'];
		if ($linkVars)	{
			$linkVarArr = explode(',',$linkVars);

			$GLOBALS['TSFE']->linkVars='';
			$GET = t3lib_div::_GET();

			foreach ($linkVarArr as $val)	{
				$val = trim($val);

				if (preg_match('/^(.*)\((.+)\)$/',$val,$match))	{
					$val = trim($match[1]);
					$test = trim($match[2]);
				} else unset($test);

				if ($val && isset($GET[$val]))	{
					if (!is_array($GET[$val]))	{
						$tmpVal = rawurlencode($GET[$val]);

						if ($test && !TSpagegen::isAllowedLinkVarValue($tmpVal,$test))	{
							continue;	// Error: This value was not allowed for this key
						}

						$value = '&'.$val.'='.$tmpVal;
					} else {
						if ($test && strcmp('array',$test))	{
							continue;	// Error: This key must not be an array!
						}
						$value = t3lib_div::implodeArrayForUrl($val,$GET[$val]);
					}
				} else continue;

				$GLOBALS['TSFE']->linkVars.= $value;
			}
			unset($GET);
		} else {
			$GLOBALS['TSFE']->linkVars='';
		}

			// Setting XHTML-doctype from doctype
		if (!$GLOBALS['TSFE']->config['config']['xhtmlDoctype'])	{
			$GLOBALS['TSFE']->config['config']['xhtmlDoctype'] = $GLOBALS['TSFE']->config['config']['doctype'];
		}

		if ($GLOBALS['TSFE']->config['config']['xhtmlDoctype'])	{
			$GLOBALS['TSFE']->xhtmlDoctype = $GLOBALS['TSFE']->config['config']['xhtmlDoctype'];

				// Checking XHTML-docytpe
			switch((string)$GLOBALS['TSFE']->config['config']['xhtmlDoctype'])	{
				case 'xhtml_trans':
				case 'xhtml_strict':
				case 'xhtml_frames':
					$GLOBALS['TSFE']->xhtmlVersion = 100;
				break;
				case 'xhtml_basic':
					$GLOBALS['TSFE']->xhtmlVersion = 105;
				break;
				case 'xhtml_11':
				case 'xhtml+rdfa_10':
					$GLOBALS['TSFE']->xhtmlVersion = 110;
				break;
				case 'xhtml_2':
					$GLOBALS['TSFE']->xhtmlVersion = 200;
				break;
				default:
					$GLOBALS['TSFE']->getPageRenderer()->setRenderXhtml(FALSE);
					$GLOBALS['TSFE']->xhtmlDoctype = '';
					$GLOBALS['TSFE']->xhtmlVersion = 0;
			}
		} else {
			$GLOBALS['TSFE']->getPageRenderer()->setRenderXhtml(FALSE);
		}
	}

	/**
	 * Returns an array with files to include. These files are the ones set up in TypoScript config.
	 *
	 * @return	array		Files to include. Paths are relative to PATH_site.
	 */
	public static function getIncFiles() {
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
			foreach ($incLibs as $theLib) {
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
	public static function JSeventFunctions()	{
		$functions = array();
		$setEvents = array();
		$setBody = array();

		foreach ($GLOBALS['TSFE']->JSeventFuncCalls as $event => $handlers)	{
			if (count($handlers))	{
				$functions[] = '	function T3_'.$event.'Wrapper(e)	{	'.implode('   ',$handlers).'	}';
				$setEvents[] = '	document.'.$event.'=T3_'.$event.'Wrapper;';
				if ($event == 'onload')	{
					$setBody[]='onload="T3_onloadWrapper();"';	// dubiuos double setting breaks on some browser - do we need it?
				}
			}
		}

		return array(count($functions)? implode(LF, $functions) . LF . implode(LF, $setEvents) : '', $setBody);
	}

	/**
	 * Rendering the page content
	 *
	 * @return	void
	 */
	public static function renderContent() {
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
	public static function renderContentWithHeader($pageContent) {
			// get instance of t3lib_PageRenderer
		/** @var $pageRenderer t3lib_PageRenderer */
		$pageRenderer = $GLOBALS['TSFE']->getPageRenderer();

		$pageRenderer->backPath = TYPO3_mainDir;

		if ($GLOBALS['TSFE']->config['config']['moveJsFromHeaderToFooter']) {
			$pageRenderer->enableMoveJsFromHeaderToFooter();
		}

		if ($GLOBALS['TSFE']->config['config']['pageRendererTemplateFile']) {
			$file = $GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->config['config']['pageRendererTemplateFile']);
			if ($file) {
				$pageRenderer->setTemplateFile($file);
			}
		}

		$headerComment = $GLOBALS['TSFE']->config['config']['headerComment'];
		if (trim($headerComment)) {
			$pageRenderer->addInlineComment(TAB . str_replace(LF, LF . TAB, trim($headerComment)) . LF);
		}

			// Setting charset:
		$theCharset = $GLOBALS['TSFE']->metaCharset;

			// Reset the content variables:
		$GLOBALS['TSFE']->content = '';
		$htmlTagAttributes = array ();
		$htmlLang = $GLOBALS['TSFE']->config['config']['htmlTag_langKey'] ? $GLOBALS['TSFE']->config['config']['htmlTag_langKey'] : 'en';

			// Set content direction: (More info: http://www.tau.ac.il/~danon/Hebrew/HTML_and_Hebrew.html)
		if ($GLOBALS['TSFE']->config['config']['htmlTag_dir']) {
			$htmlTagAttributes['dir'] = htmlspecialchars($GLOBALS['TSFE']->config['config']['htmlTag_dir']);
		}

			// Setting document type:
		$docTypeParts = array ();
		// Part 1: XML prologue
		switch ((string) $GLOBALS['TSFE']->config['config']['xmlprologue']) {
			case 'none' :
				break;
			case 'xml_10' :
				$docTypeParts[] = '<?xml version="1.0" encoding="' . $theCharset . '"?>';
				break;
			case 'xml_11' :
				$docTypeParts[] = '<?xml version="1.1" encoding="' . $theCharset . '"?>';
				break;
			case '' :
				if ($GLOBALS['TSFE']->xhtmlVersion)
					$docTypeParts[] = '<?xml version="1.0" encoding="' . $theCharset . '"?>';
				break;
			default :
				$docTypeParts[] = $GLOBALS['TSFE']->config['config']['xmlprologue'];
		}
		// Part 2: DTD
		$doctype = $GLOBALS['TSFE']->config['config']['doctype'];
		if ($doctype) {
			switch ($doctype) {
				case 'xhtml_trans' :
					$docTypeParts[] = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">';
					break;
				case 'xhtml_strict' :
					$docTypeParts[] = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">';
					break;
				case 'xhtml_frames' :
					$docTypeParts[] = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.0 Frameset//EN"
     "http://www.w3.org/TR/xhtml1/DTD/xhtml1-frameset.dtd">';
					break;
				case 'xhtml_basic' :
					$docTypeParts[] = '<!DOCTYPE html
    PUBLIC "-//W3C//DTD XHTML Basic 1.0//EN"
    "http://www.w3.org/TR/xhtml-basic/xhtml-basic10.dtd">';
					break;
				case 'xhtml_11' :
					$docTypeParts[] = '<!DOCTYPE html
     PUBLIC "-//W3C//DTD XHTML 1.1//EN"
     "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
					break;
				case 'xhtml_2' :
					$docTypeParts[] = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML 2.0//EN"
	"http://www.w3.org/TR/xhtml2/DTD/xhtml2.dtd">';
					break;
				case 'xhtml+rdfa_10' :
					$docTypeParts[] = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD XHTML+RDFa 1.0//EN"
	"http://www.w3.org/MarkUp/DTD/xhtml-rdfa-1.dtd">';
					break;
				case 'html_5' :
					$docTypeParts[] = '<!DOCTYPE html>';
					break;
				case 'none' :
					break;
				default :
					$docTypeParts[] = $doctype;
			}
		} else {
			$docTypeParts[] = '<!DOCTYPE html
	PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">';
		}

		if ($GLOBALS['TSFE']->xhtmlVersion) {
			$htmlTagAttributes['xml:lang'] = $htmlLang;
		}
		if ($GLOBALS['TSFE']->xhtmlVersion < 110 || $doctype === 'html_5') {
			$htmlTagAttributes['lang'] = $htmlLang;
		}
		if ($GLOBALS['TSFE']->xhtmlVersion || $doctype === 'html_5') {
			$htmlTagAttributes['xmlns'] = 'http://www.w3.org/1999/xhtml'; // We add this to HTML5 to achieve a slightly better backwards compatibility
			if (is_array($GLOBALS['TSFE']->config['config']['namespaces.'])) {
				foreach ($GLOBALS['TSFE']->config['config']['namespaces.'] as $prefix => $uri) {
					$htmlTagAttributes['xmlns:' . htmlspecialchars($prefix)] = $uri; // $uri gets htmlspecialchared later
				}
			}
		}

			// Swap XML and doctype order around (for MSIE / Opera standards compliance)
		if ($GLOBALS['TSFE']->config['config']['doctypeSwitch']) {
			$docTypeParts = array_reverse($docTypeParts);
		}

			// Adding doctype parts:
		if (count($docTypeParts)) {
			$pageRenderer->setXmlPrologAndDocType(implode(LF, $docTypeParts));
		}

			// Begin header section:
		if (strcmp($GLOBALS['TSFE']->config['config']['htmlTag_setParams'], 'none')) {
			$_attr = $GLOBALS['TSFE']->config['config']['htmlTag_setParams'] ? $GLOBALS['TSFE']->config['config']['htmlTag_setParams'] : t3lib_div::implodeAttributes($htmlTagAttributes);
		} else {
			$_attr = '';
		}
		$pageRenderer->setHtmlTag('<html' . ($_attr ? ' ' . $_attr : '') . '>');

			// Head tag:
		$headTag = $GLOBALS['TSFE']->pSetup['headTag'] ? $GLOBALS['TSFE']->pSetup['headTag'] : '<head>';
		$pageRenderer->setHeadTag($headTag);

			// Setting charset meta tag:
		$pageRenderer->setCharSet($theCharset);

		$pageRenderer->addInlineComment('	This website is powered by TYPO3 - inspiring people to share!
	TYPO3 is a free open source Content Management Framework initially created by Kasper Skaarhoj and licensed under GNU/GPL.
	TYPO3 is copyright ' . TYPO3_copyright_year . ' of Kasper Skaarhoj. Extensions are copyright of their respective owners.
	Information and contribution at http://typo3.com/ and http://typo3.org/
');

		if ($GLOBALS['TSFE']->baseUrl) {
			$pageRenderer->setBaseUrl($GLOBALS['TSFE']->baseUrl);
		}

		if ($GLOBALS['TSFE']->pSetup['shortcutIcon']) {
			$favIcon = $GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['shortcutIcon']);
			$iconMimeType = '';
			if (function_exists('finfo_open')) {
				if (($finfo = @finfo_open(FILEINFO_MIME))) {
					$iconMimeType = ' type="' . finfo_file($finfo, PATH_site . $favIcon) . '"';
					finfo_close($finfo);
					$pageRenderer->setIconMimeType($iconMimeType);
				}
			}
			$pageRenderer->setFavIcon(t3lib_div::getIndpEnv('TYPO3_SITE_URL') . $favIcon);

		}

			// Including CSS files
		if (is_array($GLOBALS['TSFE']->tmpl->setup['plugin.'])) {
			$temp_styleLines = array ();
			foreach ($GLOBALS['TSFE']->tmpl->setup['plugin.'] as $key => $iCSScode) {
				if (is_array($iCSScode) && $iCSScode['_CSS_DEFAULT_STYLE']) {
					$temp_styleLines[] = '/* default styles for extension "' . substr($key, 0, - 1) . '" */' . LF . $iCSScode['_CSS_DEFAULT_STYLE'];
				}
			}
			if (count($temp_styleLines)) {
				if ($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile']) {
					$pageRenderer->addCssFile(TSpagegen::inline2TempFile(implode(LF, $temp_styleLines), 'css'));
				} else {
					$pageRenderer->addCssInlineBlock('TSFEinlineStyle', implode(LF, $temp_styleLines));
				}
			}
		}

		if ($GLOBALS['TSFE']->pSetup['stylesheet']) {
			$ss = $GLOBALS['TSFE']->tmpl->getFileName($GLOBALS['TSFE']->pSetup['stylesheet']);
			if ($ss) {
				$pageRenderer->addCssFile($ss);
			}
		}

		/**********************************************************************/
		/* includeCSS
		/* config.includeCSS {
		/*
		/* }
		/**********************************************************************/

		if (is_array($GLOBALS['TSFE']->pSetup['includeCSS.'])) {
			foreach ($GLOBALS['TSFE']->pSetup['includeCSS.'] as $key => $CSSfile) {
				if (!is_array($CSSfile)) {
					$ss = $GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['external'] ? $CSSfile : $GLOBALS['TSFE']->tmpl->getFileName($CSSfile);
					if ($ss) {
						if ($GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['import']) {
							if (! $GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['external'] && substr($ss, 0, 1) != '/') { // To fix MSIE 6 that cannot handle these as relative paths (according to Ben v Ende)
								$ss = t3lib_div::dirname(t3lib_div::getIndpEnv('SCRIPT_NAME')) . '/' . $ss;
							}
							$pageRenderer->addCssInlineBlock(
								'import_' . $key,
								'@import url("' . htmlspecialchars($ss) . '") ' . htmlspecialchars($GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['media']) . ';',
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['compress'] ? TRUE : FALSE,
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
								''
							);
						} else {
							$pageRenderer->addCssFile(
								$ss,
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['alternate'] ? 'alternate stylesheet' : 'stylesheet',
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['media'] ? $GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['media'] : 'all',
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['title'] ? $GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['title'] : '',
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['compress'] ? TRUE : FALSE,
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
								$GLOBALS['TSFE']->pSetup['includeCSS.'][$key . '.']['allWrap']);

						}
					}
				}
			}
		}

			// Stylesheets
		$style = '';
		if ($GLOBALS['TSFE']->pSetup['insertClassesFromRTE']) {
			$pageTSConfig = $GLOBALS['TSFE']->getPagesTSconfig();
			$RTEclasses = $pageTSConfig['RTE.']['classes.'];
			if (is_array($RTEclasses)) {
				foreach ($RTEclasses as $RTEclassName => $RTEvalueArray) {
					if ($RTEvalueArray['value']) {
						$style .= '
.' . substr($RTEclassName, 0, - 1) . ' {' . $RTEvalueArray['value'] . '}';
					}
				}
			}

			if ($GLOBALS['TSFE']->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs'] && is_array($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.'])) {
				$mSOa_tList = t3lib_div::trimExplode(',', strtoupper($GLOBALS['TSFE']->pSetup['insertClassesFromRTE.']['add_mainStyleOverrideDefs']), 1);
				foreach ($pageTSConfig['RTE.']['default.']['mainStyleOverride_add.'] as $mSOa_key => $mSOa_value) {
					if (! is_array($mSOa_value) && (in_array('*', $mSOa_tList) || in_array($mSOa_key, $mSOa_tList))) {
						$style .= '
' . $mSOa_key . ' {' . $mSOa_value . '}';
					}
				}
			}
		}

			// Setting body tag margins in CSS:
		if (isset($GLOBALS['TSFE']->pSetup['bodyTagMargins']) && $GLOBALS['TSFE']->pSetup['bodyTagMargins.']['useCSS']) {
			$margins = intval($GLOBALS['TSFE']->pSetup['bodyTagMargins']);
			$style .= '
	BODY {margin: ' . $margins . 'px ' . $margins . 'px ' . $margins . 'px ' . $margins . 'px;}';
		}

		if ($GLOBALS['TSFE']->pSetup['noLinkUnderline']) {
			$GLOBALS['TSFE']->logDeprecatedTyposcript('config.noLinkUnderline');
			$style .= '
	A:link {text-decoration: none}
	A:visited {text-decoration: none}
	A:active {text-decoration: none}';
		}
		if (trim($GLOBALS['TSFE']->pSetup['hover'])) {
			$GLOBALS['TSFE']->logDeprecatedTyposcript('config.hover');
			$style .= '
	A:hover {color: ' . trim($GLOBALS['TSFE']->pSetup['hover']) . ';}';
		}
		if (trim($GLOBALS['TSFE']->pSetup['hoverStyle'])) {
			$GLOBALS['TSFE']->logDeprecatedTyposcript('config.hoverStyle');
			$style .= '
	A:hover {' . trim($GLOBALS['TSFE']->pSetup['hoverStyle']) . '}';
		}
		if ($GLOBALS['TSFE']->pSetup['smallFormFields']) {
			$GLOBALS['TSFE']->logDeprecatedTyposcript('config.smallFormFields');
			$style .= '
	SELECT {  font-family: Verdana, Arial, Helvetica; font-size: 10px }
	TEXTAREA  {  font-family: Verdana, Arial, Helvetica; font-size: 10px}
	INPUT   {  font-family: Verdana, Arial, Helvetica; font-size: 10px }';
		}
		if ($GLOBALS['TSFE']->pSetup['adminPanelStyles']) {
			$style .= '

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
			// CSS_inlineStyle from TS
		$style .= trim($GLOBALS['TSFE']->pSetup['CSS_inlineStyle']);
		$style .= $GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['cssInline.'], 'cssInline.');

		if (trim($style)) {
			if ($GLOBALS['TSFE']->config['config']['inlineStyle2TempFile']) {
				$pageRenderer->addCssFile(TSpagegen::inline2TempFile($style, 'css'));
			} else {
				$pageRenderer->addCssInlineBlock('additionalTSFEInlineStyle', $style);
			}
		}

			// Javascript Libraries
		if (is_array($GLOBALS['TSFE']->pSetup['javascriptLibs.'])) {
			if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['SVG']) {
				$pageRenderer->loadSvg();
				if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['SVG.']['debug']) {
					$pageRenderer->enableSvgDebug();
				}
				if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['SVG.']['forceFlash']) {
					$pageRenderer->svgForceFlash();
				}
			}

			if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['Prototype']) {
				$pageRenderer->loadPrototype();
			}
			if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['Scriptaculous']) {
				$modules = $GLOBALS['TSFE']->pSetup['javascriptLibs.']['Scriptaculous.']['modules'] ? $GLOBALS['TSFE']->pSetup['javascriptLibs.']['Scriptaculous.']['modules'] : '';
				$pageRenderer->loadScriptaculous($modules);
			}
			if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtCore']) {
				$pageRenderer->loadExtCore();
				if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtCore.']['debug']) {
					$pageRenderer->enableExtCoreDebug();
				}
			}
			if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs']) {
				$css = $GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['css'] ? TRUE : FALSE;
				$theme = $GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['theme'] ? TRUE : FALSE;
				$adapter = $GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['adapter'] ? $GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['adapter'] : '';
				$pageRenderer->loadExtJs($css, $theme, $adapter);
				if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['debug']) {
					$pageRenderer->enableExtJsDebug();
				}
				if ($GLOBALS['TSFE']->pSetup['javascriptLibs.']['ExtJs.']['quickTips']) {
					$pageRenderer->enableExtJSQuickTips();
				}
			}
		}

			// JavaScript library files
		if (is_array($GLOBALS['TSFE']->pSetup['includeJSlibs.'])) {
			foreach ($GLOBALS['TSFE']->pSetup['includeJSlibs.'] as $key => $JSfile) {
				if (!is_array($JSfile)) {
					$ss = $GLOBALS['TSFE']->pSetup['includeJSlibs.'][$key . '.']['external'] ? $JSfile : $GLOBALS['TSFE']->tmpl->getFileName($JSfile);
					if ($ss) {
						$type = $GLOBALS['TSFE']->pSetup['includeJSlibs.'][$key . '.']['type'];
						if (! $type) {
							$type = 'text/javascript';
						}
						$pageRenderer->addJsLibrary(
							$key,
							$ss,
							$type,
							$GLOBALS['TSFE']->pSetup['includeJSlibs.'][$key . '.']['compress'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSlibs.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSlibs.'][$key . '.']['allWrap']
						);
					}
				}
			}
		}

		if (is_array($GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'])) {
			foreach ($GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'] as $key => $JSfile) {
				if (!is_array($JSfile)) {
					$ss = $GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'][$key . '.']['external'] ? $JSfile : $GLOBALS['TSFE']->tmpl->getFileName($JSfile);
					if ($ss) {
						$type = $GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'][$key . '.']['type'];
						if (! $type) {
							$type = 'text/javascript';
						}
						$pageRenderer->addJsFooterLibrary(
							$key,
							$ss,
							$type,
							$GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'][$key . '.']['compress'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSFooterlibs.'][$key . '.']['allWrap']
						);
					}
				}
			}
		}

			// JavaScript files
		if (is_array($GLOBALS['TSFE']->pSetup['includeJS.'])) {
			foreach ($GLOBALS['TSFE']->pSetup['includeJS.'] as $key => $JSfile) {
				if (!is_array($JSfile)) {
					$ss = $GLOBALS['TSFE']->pSetup['includeJS.'][$key . '.']['external'] ? $JSfile : $GLOBALS['TSFE']->tmpl->getFileName($JSfile);
					if ($ss) {
						$type = $GLOBALS['TSFE']->pSetup['includeJS.'][$key . '.']['type'];
						if (! $type) {
							$type = 'text/javascript';
						}
						$pageRenderer->addJsFile(
							$ss,
							$type,
							$GLOBALS['TSFE']->pSetup['includeJS.'][$key . '.']['compress'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJS.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJS.'][$key . '.']['allWrap']
						);
					}
				}
			}
		}

		if (is_array($GLOBALS['TSFE']->pSetup['includeJSFooter.'])) {
			foreach ($GLOBALS['TSFE']->pSetup['includeJSFooter.'] as $key => $JSfile) {
				if (!is_array($JSfile)) {
					$ss = $GLOBALS['TSFE']->pSetup['includeJSFooter.'][$key . '.']['external'] ? $JSfile : $GLOBALS['TSFE']->tmpl->getFileName($JSfile);
					if ($ss) {
						$type = $GLOBALS['TSFE']->pSetup['includeJSFooter.'][$key . '.']['type'];
						if (! $type) {
							$type = 'text/javascript';
						}
						$pageRenderer->addJsFooterFile(
							$ss,
							$type,
							$GLOBALS['TSFE']->pSetup['includeJSFooter.'][$key . '.']['compress'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSFooter.'][$key . '.']['forceOnTop'] ? TRUE : FALSE,
							$GLOBALS['TSFE']->pSetup['includeJSFooter.'][$key . '.']['allWrap']
						);
					}
				}
			}
		}

			// Headerdata
		if (is_array($GLOBALS['TSFE']->pSetup['headerData.'])) {
			$pageRenderer->addHeaderData($GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['headerData.'], 'headerData.'));
		}

			// Footerdata
		if (is_array($GLOBALS['TSFE']->pSetup['footerData.'])) {
			$pageRenderer->addFooterData($GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['footerData.'], 'footerData.'));
		}

			// Title
		$titleTagContent = $GLOBALS['TSFE']->tmpl->printTitle($GLOBALS['TSFE']->altPageTitle ? $GLOBALS['TSFE']->altPageTitle : $GLOBALS['TSFE']->page['title'], $GLOBALS['TSFE']->config['config']['noPageTitle'], $GLOBALS['TSFE']->config['config']['pageTitleFirst']);
		if ($GLOBALS['TSFE']->config['config']['titleTagFunction']) {
			$titleTagContent = $GLOBALS['TSFE']->cObj->callUserFunction($GLOBALS['TSFE']->config['config']['titleTagFunction'], array (), $titleTagContent);
		}

		if (strlen($titleTagContent) && intval($GLOBALS['TSFE']->config['config']['noPageTitle']) !== 2) {
			$pageRenderer->setTitle($titleTagContent);
		}

			// add ending slash only to documents rendered as xhtml
		$endingSlash = $GLOBALS['TSFE']->xhtmlVersion ? ' /' : '';

		$pageRenderer->addMetaTag('<meta name="generator" content="TYPO3 ' . TYPO3_branch . ' CMS"' . $endingSlash . '>');

		$conf = $GLOBALS['TSFE']->pSetup['meta.'];
		if (is_array($conf)) {
			foreach ($conf as $theKey => $theValue) {
				if (! strstr($theKey, '.') || ! isset($conf[substr($theKey, 0, - 1)])) { // Only if 1) the property is set but not the value itself, 2) the value and/or any property
					if (strstr($theKey, '.')) {
						$theKey = substr($theKey, 0, - 1);
					}
					$val = $GLOBALS['TSFE']->cObj->stdWrap($conf[$theKey], $conf[$theKey . '.']);
					$key = $theKey;
					if (trim($val)) {
						$a = 'name';
						if (strtolower($key) == 'refresh') {
							$a = 'http-equiv';
						}
						$pageRenderer->addMetaTag('<meta ' . $a . '="' . $key . '" content="' . htmlspecialchars(trim($val)) . '"' . $endingSlash . '>');
					}
				}
			}
		}

		unset($GLOBALS['TSFE']->additionalHeaderData['JSCode']);
		unset($GLOBALS['TSFE']->additionalHeaderData['JSImgCode']);

		if (is_array($GLOBALS['TSFE']->config['INTincScript'])) {
			// Storing the JSCode and JSImgCode vars...
			$GLOBALS['TSFE']->additionalHeaderData['JSCode'] = $GLOBALS['TSFE']->JSCode;
			$GLOBALS['TSFE']->additionalHeaderData['JSImgCode'] = $GLOBALS['TSFE']->JSImgCode;
			$GLOBALS['TSFE']->config['INTincScript_ext']['divKey'] = $GLOBALS['TSFE']->uniqueHash();
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalHeaderData'] = $GLOBALS['TSFE']->additionalHeaderData; // Storing the header-data array
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalJavaScript'] = $GLOBALS['TSFE']->additionalJavaScript; // Storing the JS-data array
			$GLOBALS['TSFE']->config['INTincScript_ext']['additionalCSS'] = $GLOBALS['TSFE']->additionalCSS; // Storing the Style-data array


			$GLOBALS['TSFE']->additionalHeaderData = array ('<!--HD_' . $GLOBALS['TSFE']->config['INTincScript_ext']['divKey'] . '-->'); // Clearing the array
			$GLOBALS['TSFE']->divSection .= '<!--TDS_' . $GLOBALS['TSFE']->config['INTincScript_ext']['divKey'] . '-->';
		} else {
			$GLOBALS['TSFE']->INTincScript_loadJSCode();
		}
		$JSef = TSpagegen::JSeventFunctions();

			// Adding default Java Script:
		$scriptJsCode = '
		var browserName = navigator.appName;
		var browserVer = parseInt(navigator.appVersion);
		var version = "";
		var msie4 = (browserName == "Microsoft Internet Explorer" && browserVer >= 4);
		if ((browserName == "Netscape" && browserVer >= 3) || msie4 || browserName=="Konqueror" || browserName=="Opera") {version = "n3";} else {version = "n2";}
			// Blurring links:
		function blurLink(theObject)	{	//
			if (msie4)	{theObject.blur();}
		}
		' . $JSef[0];

		if ($GLOBALS['TSFE']->spamProtectEmailAddresses && $GLOBALS['TSFE']->spamProtectEmailAddresses !== 'ascii') {
			$scriptJsCode .= '
			// decrypt helper function
		function decryptCharcode(n,start,end,offset)	{
			n = n + offset;
			if (offset > 0 && n > end)	{
				n = start + (n - end - 1);
			} else if (offset < 0 && n < start)	{
				n = end - (start - n - 1);
			}
			return String.fromCharCode(n);
		}
			// decrypt string
		function decryptString(enc,offset)	{
			var dec = "";
			var len = enc.length;
			for(var i=0; i < len; i++)	{
				var n = enc.charCodeAt(i);
				if (n >= 0x2B && n <= 0x3A)	{
					dec += decryptCharcode(n,0x2B,0x3A,offset);	// 0-9 . , - + / :
				} else if (n >= 0x40 && n <= 0x5A)	{
					dec += decryptCharcode(n,0x40,0x5A,offset);	// A-Z @
				} else if (n >= 0x61 && n <= 0x7A)	{
					dec += decryptCharcode(n,0x61,0x7A,offset);	// a-z
				} else {
					dec += enc.charAt(i);
				}
			}
			return dec;
		}
			// decrypt spam-protected emails
		function linkTo_UnCryptMailto(s)	{
			location.href = decryptString(s,' . ($GLOBALS['TSFE']->spamProtectEmailAddresses * - 1) . ');
		}
		';
		}

			//add inline JS
		$inlineJS = '';

			// defined in php
		if (is_array($GLOBALS['TSFE']->inlineJS)) {
			foreach ($GLOBALS['TSFE']->inlineJS as $key => $val) {
				if (! is_array($val)) {
					$inlineJS .= LF . $val . LF;
				}
			}
		}

			// defined in TS with page.inlineJS
			// Javascript inline code
		$inline = $GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['jsInline.'], 'jsInline.');
		if ($inline) {
			$inlineJS .= LF . $inline . LF;
		}

			// Javascript inline code for Footer
		$inlineFooterJs = $GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['jsFooterInline.'], 'jsFooterInline.');

			// Should minify?
		if ($GLOBALS['TSFE']->config['config']['minifyJS']) {
			$pageRenderer->enableCompressJavascript();
			$minifyErrorScript = $minifyErrorInline = '';
			$scriptJsCode = t3lib_div::minifyJavaScript($scriptJsCode, $minifyErrorScript);
			if ($minifyErrorScript) {
				$GLOBALS['TT']->setTSlogMessage($minifyErrorScript, 3);
			}
			if ($inlineJS) {
				$inlineJS = t3lib_div::minifyJavaScript($inlineJS, $minifyErrorInline);
				if ($minifyErrorInline) {
					$GLOBALS['TT']->setTSlogMessage($minifyErrorInline, 3);
				}
			}
			if ($inlineFooterJs) {
				$inlineFooterJs = t3lib_div::minifyJavaScript($inlineFooterJs, $minifyErrorInline);
				if ($minifyErrorInline) {
					$GLOBALS['TT']->setTSlogMessage($minifyErrorInline, 3);
				}
			}

		}

		if (! $GLOBALS['TSFE']->config['config']['removeDefaultJS']) {
				// inlude default and inlineJS
			if ($scriptJsCode) {
				$pageRenderer->addJsInlineCode('_scriptCode', $scriptJsCode, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
			if ($inlineJS) {
				$pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
			if ($inlineFooterJs) {
				$pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
		} elseif ($GLOBALS['TSFE']->config['config']['removeDefaultJS'] === 'external') {
			/*
			 This keeps inlineJS from *_INT Objects from being moved to external files.
			 At this point in frontend rendering *_INT Objects only have placeholders instead
			 of actual content so moving these placeholders to external files would
			 	a) break the JS file (syntax errors due to the placeholders)
			 	b) the needed JS would never get included to the page
			 Therefore inlineJS from *_INT Objects must not be moved to external files but
			 kept internal.
			*/
			$inlineJSint = '';
			self::stripIntObjectPlaceholder($inlineJS, $inlineJSint);
			if ($inlineJSint) {
				$pageRenderer->addJsInlineCode('TS_inlineJSint', $inlineJSint, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
			$pageRenderer->addJsFile(TSpagegen::inline2TempFile($scriptJsCode . $inlineJS, 'js'), 'text/javascript', $GLOBALS['TSFE']->config['config']['minifyJS']);

			if ($inlineFooterJs) {
				$inlineFooterJSint = '';
				self::stripIntObjectPlaceholder($inlineFooterJs, $inlineFooterJSint);
				if ($inlineFooterJSint) {
					$pageRenderer->addJsFooterInlineCode('TS_inlineFooterJSint', $inlineFooterJSint, $GLOBALS['TSFE']->config['config']['minifyJS']);
				}
				$pageRenderer->addJsFooterFile(TSpagegen::inline2TempFile($inlineFooterJs, 'js'), 'text/javascript', $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
		} else {
				// include only inlineJS
			if ($inlineJS) {
				$pageRenderer->addJsInlineCode('TS_inlineJS', $inlineJS, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
			if ($inlineFooterJs) {
				$pageRenderer->addJsFooterInlineCode('TS_inlineFooter', $inlineFooterJs, $GLOBALS['TSFE']->config['config']['minifyJS']);
			}
		}

			// ExtJS specific code
		if (is_array($GLOBALS['TSFE']->pSetup['inlineLanguageLabel.'])) {
			$pageRenderer->addInlineLanguageLabelArray($GLOBALS['TSFE']->pSetup['inlineLanguageLabel.']);
		}

		if (is_array($GLOBALS['TSFE']->pSetup['inlineSettings.'])) {
			$pageRenderer->addInlineSettingArray('TS', $GLOBALS['TSFE']->pSetup['inlineSettings.']);
		}

		if (is_array($GLOBALS['TSFE']->pSetup['extOnReady.'])) {
			$pageRenderer->addExtOnReadyCode($GLOBALS['TSFE']->cObj->cObjGet($GLOBALS['TSFE']->pSetup['extOnReady.'], 'extOnReady.'));
		}

			// compression and concatenate settings
		if ($GLOBALS['TSFE']->config['config']['minifyCSS']) {
			$pageRenderer->enableCompressCss();
		}
		if ($GLOBALS['TSFE']->config['config']['minifyJS']) {
			$pageRenderer->enableCompressJavascript();
		}
		if ($GLOBALS['TSFE']->config['config']['concatenateJsAndCss']) {
			$pageRenderer->enableConcatenateFiles();
		}

			// add header data block
		if ($GLOBALS['TSFE']->additionalHeaderData) {
			$pageRenderer->addHeaderData(implode(LF, $GLOBALS['TSFE']->additionalHeaderData));
		}

			// add footer data block
		if ($GLOBALS['TSFE']->additionalFooterData) {
			$pageRenderer->addFooterData(implode(LF, $GLOBALS['TSFE']->additionalFooterData));
		}

		// Header complete, now add content


		if ($GLOBALS['TSFE']->pSetup['frameSet.']) {
			$fs = t3lib_div::makeInstance('tslib_frameset');
			$pageRenderer->addBodyContent($fs->make($GLOBALS['TSFE']->pSetup['frameSet.']));
			$pageRenderer->addBodyContent(LF . '<noframes>' . LF);
		}

			// Bodytag:
		$defBT = $GLOBALS['TSFE']->pSetup['bodyTagCObject'] ? $GLOBALS['TSFE']->cObj->cObjGetSingle($GLOBALS['TSFE']->pSetup['bodyTagCObject'], $GLOBALS['TSFE']->pSetup['bodyTagCObject.'], 'bodyTagCObject') : '';
		if (! $defBT)
			$defBT = $GLOBALS['TSFE']->defaultBodyTag;
		$bodyTag = $GLOBALS['TSFE']->pSetup['bodyTag'] ? $GLOBALS['TSFE']->pSetup['bodyTag'] : $defBT;
		if ($bgImg = $GLOBALS['TSFE']->cObj->getImgResource($GLOBALS['TSFE']->pSetup['bgImg'], $GLOBALS['TSFE']->pSetup['bgImg.'])) {
			$bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' background="' . $GLOBALS["TSFE"]->absRefPrefix . $bgImg[3] . '">';
		}

		if (isset($GLOBALS['TSFE']->pSetup['bodyTagMargins'])) {
			$margins = intval($GLOBALS['TSFE']->pSetup['bodyTagMargins']);
			if ($GLOBALS['TSFE']->pSetup['bodyTagMargins.']['useCSS']) {
				// Setting margins in CSS, see above
			} else {
				$bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' leftmargin="' . $margins . '" topmargin="' . $margins . '" marginwidth="' . $margins . '" marginheight="' . $margins . '">';
			}
		}

		if (trim($GLOBALS['TSFE']->pSetup['bodyTagAdd'])) {
			$bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim($GLOBALS['TSFE']->pSetup['bodyTagAdd']) . '>';
		}

		if (count($JSef[1])) { // Event functions:
			$bodyTag = preg_replace('/>$/', '', trim($bodyTag)) . ' ' . trim(implode(' ', $JSef[1])) . '>';
		}
		$pageRenderer->addBodyContent(LF . $bodyTag);

			// Div-sections
		if ($GLOBALS['TSFE']->divSection) {
			$pageRenderer->addBodyContent(LF . $GLOBALS['TSFE']->divSection);
		}

			// Page content
		$pageRenderer->addBodyContent(LF . $pageContent);

			// Render complete page
		$GLOBALS['TSFE']->content = $pageRenderer->render();

			// Ending page
		if ($GLOBALS['TSFE']->pSetup['frameSet.']) {
			$GLOBALS['TSFE']->content .= LF . '</noframes>';
		}

	}













	/*************************
	 *
	 * Helper functions
	 * Remember: Calls internally must still be done on the non-instantiated class: TSpagegen::inline2TempFile()
	 *
	 *************************/

	/**
	 * Searches for placeholder created from *_INT cObjects, removes them from
	 * $searchString and merges them to $intObjects
	 *
	 * @param	string		$searchString: the String which should be cleaned from int-object markers
	 * @param	string		$intObjects: the String the found int-placeholders are moved to (for further processing)
	 */
	protected static function stripIntObjectPlaceholder(&$searchString, &$intObjects) {
		$tempArray = array();
		preg_match_all('/\<\!--INT_SCRIPT.[a-z0-9]*--\>/', $searchString, $tempArray);
		$searchString = preg_replace('/\<\!--INT_SCRIPT.[a-z0-9]*--\>/', '', $searchString);
		$intObjects = implode('', $tempArray[0]);
	}

	/**
	 * Writes string to a temporary file named after the md5-hash of the string
	 *
	 * @param	string		CSS styles / JavaScript to write to file.
	 * @param	string		Extension: "css" or "js"
	 * @return	string		<script> or <link> tag for the file.
	 */
	public static function inline2TempFile($str, $ext) {

			// Create filename / tags:
		$script = '';
		switch ($ext) {
			case 'js' :
				$script = 'typo3temp/javascript_' . substr(md5($str), 0, 10) . '.js';
				$output = $GLOBALS['TSFE']->absRefPrefix . $script;
			break;
			case 'css' :
				$script = 'typo3temp/stylesheet_' . substr(md5($str), 0, 10) . '.css';
				$output = $GLOBALS['TSFE']->absRefPrefix . $script;
			break;
		}

			// Write file:
		if ($script) {
			if (! @is_file(PATH_site . $script)) {
				t3lib_div::writeFile(PATH_site . $script, $str);
			}
		}

		return $output;
	}

	/**
	 * Checks if the value defined in "config.linkVars" contains an allowed value. Otherwise, return false which means the value will not be added to any links.
	 *
	 * @param	string		The string in which to find $needle
	 * @param	string		The string to find in $haystack
	 * @return	boolean		Returns true if $needle matches or is found in $haystack
	 */
	public static function isAllowedLinkVarValue($haystack,$needle) {
		$OK = false;

		if ($needle=='int' || $needle=='integer')	{	// Integer

			if (t3lib_div::testInt($haystack))	{
				$OK = true;
			}

		} elseif (preg_match('/^\/.+\/[imsxeADSUXu]*$/', $needle))	{	// Regular expression, only "//" is allowed as delimiter

			if (@preg_match($needle, $haystack))	{
				$OK = true;
			}

		} elseif (strstr($needle,'-'))	{	// Range

			if (t3lib_div::testInt($haystack))	{
				$range = explode('-',$needle);
				if ($range[0] <= $haystack && $range[1] >= $haystack)	{
					$OK = true;
				}
			}

		} elseif (strstr($needle,'|'))	{	// List

			$haystack = str_replace(' ','',$haystack);	// Trim the input
			if (strstr('|'.$needle.'|', '|'.$haystack.'|'))	{
				$OK = true;
			}

		} elseif (!strcmp($needle,$haystack))	{	// String comparison
			$OK = true;
		}

		return $OK;
	}
}

if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['tslib/class.tslib_pagegen.php']);
}



/**
 * Class for fetching record relations for the frontend.
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage tslib
 * @see tslib_cObj::RECORDS()
 */
class FE_loadDBGroup extends t3lib_loadDBGroup {
	var $fromTC = 0;	// Means that everything is returned instead of only uid and label-field
}

?>
