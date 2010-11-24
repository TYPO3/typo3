<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2010 Steffen Kamper (info@sk-typo3.de)
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
 * TYPO3 pageRender class (new in TYPO3 4.3.0)
 * This class render the HTML of a webpage, usable for BE and FE
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage t3lib
 * $Id$
 */
class t3lib_PageRenderer implements t3lib_Singleton {

	protected $compressJavascript = FALSE;
	protected $compressCss = FALSE;
	protected $removeLineBreaksFromTemplate = FALSE;

	protected $concatenateFiles = FALSE;

	protected $moveJsFromHeaderToFooter = FALSE;

	/* @var t3lib_cs Instance of t3lib_cs */
	protected $csConvObj;
	protected $lang;

	/* @var t3lib_Compressor Instance of t3lib_Compressor */
	protected $compressor;

		// static array containing associative array for the included files
	protected static $jsFiles = array();
	protected static $jsFooterFiles = array();
	protected static $jsLibs = array();
	protected static $jsFooterLibs = array();
	protected static $cssFiles = array();

	protected $title;
	protected $charSet;
	protected $favIcon;
	protected $baseUrl;

	protected $renderXhtml = TRUE;

		// static header blocks
	protected $xmlPrologAndDocType = '';
	protected $metaTags = array();
	protected $inlineComments = array();
	protected $headerData = array();
	protected $footerData = array();
	protected $titleTag = '<title>|</title>';
	protected $metaCharsetTag = '<meta http-equiv="Content-Type" content="text/html; charset=|" />';
	protected $htmlTag = '<html>';
	protected $headTag = '<head>';
	protected $baseUrlTag = '<base href="|" />';
	protected $iconMimeType = '';
	protected $shortcutTag = '<link rel="shortcut icon" href="%1$s"%2$s />
<link rel="icon" href="%1$s"%2$s />';

		// static inline code blocks
	protected $jsInline = array();
	protected $jsFooterInline = array();
	protected $extOnReadyCode = array();
	protected $cssInline = array();

	protected $bodyContent;

	protected $templateFile;

	protected $jsLibraryNames = array('prototype', 'scriptaculous', 'extjs');

	const PART_COMPLETE = 0;
	const PART_HEADER = 1;
	const PART_FOOTER = 2;

		// paths to contibuted libraries
	protected $prototypePath = 'contrib/prototype/';
	protected $scriptaculousPath = 'contrib/scriptaculous/';
	protected $extCorePath = 'contrib/extjs/';
	protected $extJsPath = 'contrib/extjs/';
	protected $svgPath = 'contrib/websvg/';


		// internal flags for JS-libraries
	protected $addPrototype = FALSE;
	protected $addScriptaculous = FALSE;
	protected $addScriptaculousModules = array('builder' => FALSE, 'effects' => FALSE, 'dragdrop' => FALSE, 'controls' => FALSE, 'slider' => FALSE);
	protected $addExtJS = FALSE;
	protected $addExtCore = FALSE;
	protected $extJSadapter = 'ext/ext-base.js';


	protected $enableExtJsDebug = FALSE;
	protected $enableExtCoreDebug = FALSE;

		// available adapters for extJs
	const EXTJS_ADAPTER_JQUERY = 'jquery';
	const EXTJS_ADAPTER_PROTOTYPE = 'prototype';
	const EXTJS_ADAPTER_YUI = 'yui';

	protected $extJStheme = TRUE;
	protected $extJScss = TRUE;

	protected $enableExtJSQuickTips = FALSE;

	protected $inlineLanguageLabels = array();
	protected $inlineLanguageLabelFiles = array();
	protected $inlineSettings = array();

	protected $inlineJavascriptWrap = array();

		// saves error messages generated during compression
	protected $compressError = '';

		// SVG library
	protected $addSvg = FALSE;
	protected $enableSvgDebug = FALSE;


		// used by BE modules
	public $backPath;

	/**
	 * Constructor
	 *
	 * @param string $templateFile	declare the used template file. Omit this parameter will use default template
	 * @param string $backPath	relative path to typo3-folder. It varies for BE modules, in FE it will be typo3/
	 * @return void
	 */
	public function __construct($templateFile = '', $backPath = NULL) {

		$this->reset();
		$this->csConvObj = t3lib_div::makeInstance('t3lib_cs');

		if (strlen($templateFile)) {
			$this->templateFile = $templateFile;
		}
		$this->backPath = isset($backPath) ? $backPath : $GLOBALS['BACK_PATH'];

		$this->inlineJavascriptWrap = array(
			'<script type="text/javascript">' . LF . '/*<![CDATA[*/' . LF . '<!-- ' . LF,
			'// -->' . LF . '/*]]>*/' . LF . '</script>' . LF
		);
		$this->inlineCssWrap = array(
			'<style type="text/css">' . LF . '/*<![CDATA[*/' . LF . '<!-- ' . LF,
			'-->' . LF . '/*]]>*/' . LF . '</style>' . LF
		);

	}

	/**
	 * reset all vars to initial values
	 *
	 * @return void
	 */
	protected function reset() {
		$this->templateFile = TYPO3_mainDir . 'templates/template_page_backend.html';
		$this->jsFiles = array();
		$this->jsFooterFiles = array();
		$this->jsInline = array();
		$this->jsFooterInline = array();
		$this->jsLibs = array();
		$this->cssFiles = array();
		$this->cssInline = array();
		$this->metaTags = array();
		$this->inlineComments = array();
		$this->headerData = array();
		$this->footerData = array();
		$this->extOnReadyCode = array();
	}

	/*****************************************************/
	/*                                                   */
	/*  Public Setters                                   */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * Sets the title
	 *
	 * @param string $title	title of webpage
	 * @return void
	 */
	public function setTitle($title) {
		$this->title = $title;
	}


	/**
	 * Enables/disables rendering of XHTML code
	 *
	 * @param boolean $enable	Enable XHTML
	 * @return void
	 */
	public function setRenderXhtml($enable) {
		$this->renderXhtml = $enable;
	}

	/**
	 * Sets xml prolog and docType
	 *
	 * @param string $xmlPrologAndDocType	complete tags for xml prolog and docType
	 * @return void
	 */
	public function setXmlPrologAndDocType($xmlPrologAndDocType) {
		$this->xmlPrologAndDocType = $xmlPrologAndDocType;
	}

	/**
	 * Sets meta charset
	 *
	 * @param string $charSet	used charset
	 * @return void
	 */
	public function setCharSet($charSet) {
		$this->charSet = $charSet;
	}

	/**
	 * Sets language
	 *
	 * @param string $lang	used language
	 * @return void
	 */
	public function setLanguage($lang) {
		$this->lang = $lang;
	}

	/**
	 * Sets html tag
	 *
	 * @param string $htmlTag	html tag
	 * @return void
	 */
	public function setHtmlTag($htmlTag) {
		$this->htmlTag = $htmlTag;
	}

	/**
	 * Sets head tag
	 *
	 * @param string $tag	head tag
	 * @return void
	 */
	public function setHeadTag($headTag) {
		$this->headTag = $headTag;
	}

	/**
	 * Sets favicon
	 *
	 * @param string $favIcon
	 * @return void
	 */
	public function setFavIcon($favIcon) {
		$this->favIcon = $favIcon;
	}

	/**
	 * Sets icon mime type
	 *
	 * @param string $iconMimeType
	 * @return void
	 */
	public function setIconMimeType($iconMimeType) {
		$this->iconMimeType = $iconMimeType;
	}

	/**
	 * Sets base url
	 *
	 * @param string $url
	 * @return void
	 */
	public function setBaseUrl($baseUrl) {
		$this->baseUrl = $baseUrl;
	}

	/**
	 * Sets template file
	 *
	 * @param string $file
	 * @return void
	 */
	public function setTemplateFile($file) {
		$this->templateFile = $file;
	}

	/**
	 * Sets back path
	 *
	 * @param string $backPath
	 * @return void
	 */
	public function setBackPath($backPath) {
		$this->backPath = $backPath;
	}

	/**
	 * Sets Content for Body
	 *
	 * @param string $content
	 * @return void
	 */
	public function setBodyContent($content) {
		$this->bodyContent = $content;
	}

	/**
	 * Sets Path for prototype library (relative to typo3 directory)
	 *
	 * @param string path
	 * @return void
	 */
	public function setPrototypePath($path) {
		$this->prototypePath = $path;
	}

	/**
	 * Sets Path for scriptaculous library (relative to typo3 directory)
	 *
	 * @param string $path
	 * @return void
	 */
	public function setScriptaculousPath($path) {
		$this->scriptaculousPath = $path;
	}

	/**
	 * Sets Path for Ext Core library (relative to typo3 directory)
	 *
	 * @param string $path
	 * @return void
	 */
	public function setExtCorePath($path) {
		$this->extCorePath = $path;
	}

	/**
	 * Sets Path for ExtJs library (relative to typo3 directory)
	 *
	 * @param string $path
	 * @return void
	 */
	public function setExtJsPath($path) {
		$this->extJsPath = $path;
	}

	/**
	 * Sets Path for SVG library (websvg)
	 *
	 * @param string $path
	 * @return void
	 */
	public function setSvgPath($path) {
		$this->svgPath = $path;
	}

	/*****************************************************/
	/*                                                   */
	/*  Public Enablers / Disablers                      */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * Enables MoveJsFromHeaderToFooter
	 *
	 * @param void
	 * @return void
	 */
	public function enableMoveJsFromHeaderToFooter() {
		$this->moveJsFromHeaderToFooter = TRUE;
	}

	/**
	 * Disables MoveJsFromHeaderToFooter
	 *
	 * @param void
	 * @return void
	 */
	public function disableMoveJsFromHeaderToFooter() {
		$this->moveJsFromHeaderToFooter = FALSE;
	}

	/**
	 * Enables compression of javascript
	 *
	 * @param void
	 * @return void
	 */
	public function enableCompressJavascript() {
		$this->compressJavascript = TRUE;
	}

	/**
	 * Disables compression of javascript
	 *
	 * @param void
	 * @return void
	 */
	public function disableCompressJavascript() {
		$this->compressJavascript = FALSE;
	}

	/**
	 * Enables compression of css
	 *
	 * @param void
	 * @return void
	 */
	public function enableCompressCss() {
		$this->compressCss = TRUE;
	}

	/**
	 * Disables compression of css
	 *
	 * @param void
	 * @return void
	 */
	public function disableCompressCss() {
		$this->compressCss = FALSE;
	}

	/**
	 * Enables concatenation of js/css files
	 *
	 * @param void
	 * @return void
	 */
	public function enableConcatenateFiles() {
		$this->concatenateFiles = TRUE;
	}

	/**
	 * Disables concatenation of js/css files
	 *
	 * @param void
	 * @return void
	 */
	public function disableConcatenateFiles() {
		$this->concatenateFiles = FALSE;
	}

	/**
	 * Sets removal of all line breaks in template
	 *
	 * @param void
	 * @return void
	 */
	public function enableRemoveLineBreaksFromTemplate() {
		$this->removeLineBreaksFromTemplate = TRUE;
	}

	/**
	 * Unsets removal of all line breaks in template
	 *
	 * @param void
	 * @return void
	 */
	public function disableRemoveLineBreaksFromTemplate() {
		$this->removeLineBreaksFromTemplate = FALSE;
	}

	/**
	 * Enables Debug Mode
	 * This is a shortcut to switch off all compress/concatenate features to enable easier debug
	 *
	 * @param void
	 * @return void
	 */
	public function enableDebugMode() {
		$this->compressJavascript = FALSE;
		$this->compressCss = FALSE;
		$this->concatenateFiles = FALSE;
		$this->removeLineBreaksFromTemplate = FALSE;
		$this->enableExtCoreDebug = TRUE;
		$this->enableExtJsDebug = TRUE;
		$this->enableSvgDebug = TRUE;
	}

	/*****************************************************/
	/*                                                   */
	/*  Public Getters                                   */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * Gets the title
	 *
	 * @return string $title		title of webpage
	 */
	public function getTitle() {
		return $this->title;
	}

	/**
	 * Gets the charSet
	 *
	 * @return string $charSet
	 */
	public function getCharSet() {
		return $this->charSet;
	}

	/**
	 * Gets the language
	 *
	 * @return string $lang
	 */
	public function getLanguage() {
		return $this->lang;
	}

	/**
	 * Returns rendering mode XHTML or HTML
	 *
	 * @return boolean		TRUE if XHTML, FALSE if HTML
	 */
	public function getRenderXhtml() {
		return $this->renderXhtml;
	}

	/**
	 * Gets html tag
	 *
	 * @return string $htmlTag	html tag
	 */
	public function getHtmlTag() {
		return $this->htmlTag;
	}

	/**
	 * Gets head tag
	 *
	 * @return string $tag	head tag
	 */
	public function getHeadTag() {
		return $this->headTag;
	}

	/**
	 * Gets favicon
	 *
	 * @return string $favIcon
	 */
	public function getFavIcon() {
		return $this->favIcon;
	}

	/**
	 * Gets icon mime type
	 *
	 * @return string $iconMimeType
	 */
	public function getIconMimeType() {
		return $this->iconMimeType;
	}

	/**
	 * Gets base url
	 *
	 * @return string $url
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	/**
	 * Gets template file
	 *
	 * @return string $file
	 */
	public function getTemplateFile($file) {
		return $this->templateFile;
	}

	/**
	 * Gets MoveJsFromHeaderToFooter
	 *
	 * @return boolean
	 */
	public function getMoveJsFromHeaderToFooter() {
		return $this->moveJsFromHeaderToFooter;
	}

	/**
	 * Gets compress of javascript
	 *
	 * @return boolean
	 */
	public function getCompressJavascript() {
		return $this->compressJavascript;
	}

	/**
	 * Gets compress of css
	 *
	 * @return boolean
	 */
	public function getCompressCss() {
		return $this->compressCss;
	}

	/**
	 * Gets concatenate of files
	 *
	 * @return boolean
	 */
	public function getConcatenateFiles() {
		return $this->concatenateFiles;
	}

	/**
	 * Gets remove of empty lines from template
	 *
	 * @return boolean
	 */
	public function getRemoveLineBreaksFromTemplate() {
		return $this->removeLineBreaksFromTemplate;
	}

	/**
	 * Gets content for body
	 *
	 * @return string
	 */
	public function getBodyContent() {
		return $this->bodyContent;
	}

	/**
	 * Gets Path for prototype library (relative to typo3 directory)
	 *
	 * @return string
	 */
	public function getPrototypePath() {
		return $this->prototypePath;
	}

	/**
	 * Gets Path for scriptaculous library (relative to typo3 directory)
	 *
	 * @return string
	 */
	public function getScriptaculousPath() {
		return $this->scriptaculousPath;
	}

	/**
	 * Gets Path for Ext Core library (relative to typo3 directory)
	 *
	 * @return string
	 */
	public function getExtCorePath() {
		return $this->extCorePath;
	}

	/**
	 * Gets Path for ExtJs library (relative to typo3 directory)
	 *
	 * @return string
	 */
	public function getExtJsPath() {
		return $this->extJsPath;
	}

	/**
	 * Gets Path for SVG library (relative to typo3 directory)
	 *
	 * @return string
	 */
	public function getSvgPath() {
		return $this->svgPath;
	}

	/**
	 * Gets the inline language labels.
	 *
	 * @return array The inline language labels
	 */
	public function getInlineLanguageLabels() {
		return $this->inlineLanguageLabels;
	}

	/**
	 * Gets the inline language files
	 *
	 * @return array
	 */
	public function getInlineLanguageLabelFiles() {
		return $this->inlineLanguageLabelFiles;
	}

	/*****************************************************/
	/*                                                   */
	/*  Public Function to add Data                      */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * Adds meta data
	 *
	 * @param string $meta	meta data (complete metatag)
	 * @return void
	 */
	public function addMetaTag($meta) {
		if (!in_array($meta, $this->metaTags)) {
			$this->metaTags[] = $meta;
		}
	}

	/**
	 * Adds inline HTML comment
	 *
	 * @param string $comment
	 * @return void
	 */
	public function addInlineComment($comment) {
		if (!in_array($comment, $this->inlineComments)) {
			$this->inlineComments[] = $comment;
		}
	}

	/**
	 * Adds header data
	 *
	 * @param string $data	 free header data for HTML header
	 * @return void
	 */
	public function addHeaderData($data) {
		if (!in_array($data, $this->headerData)) {
			$this->headerData[] = $data;
		}
	}

	/**
	 * Adds footer data
	 *
	 * @param string $data	 free header data for HTML header
	 * @return void
	 */
	public function addFooterData($data) {
		if (!in_array($data, $this->footerData)) {
			$this->footerData[] = $data;
		}
	}

	/* Javascript Files */

	/**
	 * Adds JS Library. JS Library block is rendered on top of the JS files.
	 *
	 * @param string $name
	 * @param string $file
	 * @param string $type
	 * @param boolean $compress		flag if library should be compressed
	 * @param boolean $forceOnTop	flag if added library should be inserted at begin of this block
	 * @param string $allWrap
	 * @return void
	 */
	public function addJsLibrary($name, $file, $type = 'text/javascript', $compress = FALSE, $forceOnTop = FALSE, $allWrap = '') {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!in_array(strtolower($name), $this->jsLibs)) {
			$this->jsLibs[strtolower($name)] = array(
				'file' => $file,
				'type' => $type,
				'section' => self::PART_HEADER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap
			);
		}

	}

	/**
	 * Adds JS Library to Footer. JS Library block is rendered on top of the Footer JS files.
	 *
	 * @param string $name
	 * @param string $file
	 * @param string $type
	 * @param boolean $compress	flag if library should be compressed
	 * @param boolean $forceOnTop	flag if added library should be inserted at begin of this block
	 * @param string $allWrap
	 * @return void
	 */
	public function addJsFooterLibrary($name, $file, $type = 'text/javascript', $compress = FALSE, $forceOnTop = FALSE, $allWrap = '') {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!in_array(strtolower($name), $this->jsLibs)) {
			$this->jsLibs[strtolower($name)] = array(
				'file' => $file,
				'type' => $type,
				'section' => self::PART_FOOTER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap
			);
		}

	}

	/**
	 * Adds JS file
	 *
	 * @param string $file
	 * @param string $type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @return void
	 */
	public function addJsFile($file, $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '') {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!isset($this->jsFiles[$file])) {
			$this->jsFiles[$file] = array(
				'type' => $type,
				'section' => self::PART_HEADER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap
			);
		}
	}

	/**
	 * Adds JS file to footer
	 *
	 * @param string $file
	 * @param string $type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @return void
	 */
	public function addJsFooterFile($file, $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '') {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!isset($this->jsFiles[$file])) {
			$this->jsFiles[$file] = array(
				'type' => $type,
				'section' => self::PART_FOOTER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap
			);
		}
	}

	/*Javascript Inline Blocks */

	/**
	 * Adds JS inline code
	 *
	 * @param string $name
	 * @param string $block
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @return void
	 */
	public function addJsInlineCode($name, $block, $compress = TRUE, $forceOnTop = FALSE) {
		if (!isset($this->jsInline[$name]) && !empty($block)) {
			$this->jsInline[$name] = array(
				'code' => $block . LF,
				'section' => self::PART_HEADER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop
			);
		}
	}

	/**
	 * Adds JS inline code to footer
	 *
	 * @param string $name
	 * @param string $block
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @return void
	 */
	public function addJsFooterInlineCode($name, $block, $compress = TRUE, $forceOnTop = FALSE) {
		if (!isset($this->jsInline[$name]) && !empty($block)) {
			$this->jsInline[$name] = array(
				'code' => $block . LF,
				'section' => self::PART_FOOTER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop
			);
		}
	}

	/**
	 * Adds Ext.onready code, which will be wrapped in Ext.onReady(function() {...});
	 *
	 * @param string $block javascript code
	 * @param boolean $forceOnTop position of the javascript code (TRUE for putting it on top, default is FALSE = bottom)
	 * @return void
	 */
	public function addExtOnReadyCode($block, $forceOnTop = FALSE) {
		if (!in_array($block, $this->extOnReadyCode)) {
			if ($forceOnTop) {
				array_unshift($this->extOnReadyCode, $block);
			} else {
				$this->extOnReadyCode[] = $block;
			}
		}
	}

	/**
	 * Adds the ExtDirect code
	 *
	 * @return void
	 */
	public function addExtDirectCode() {
			// Note: we need to iterate thru the object, because the addProvider method
			// does this only with multiple arguments
		$this->addExtOnReadyCode(
			'for (var api in Ext.app.ExtDirectAPI) {
				Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
			}

			var extDirectDebug = function(message, header, group) {
				var TYPO3ViewportInstance = null;

				if (top && top.TYPO3 && typeof top.TYPO3.Backend === "object") {
					TYPO3ViewportInstance = top.TYPO3.Backend;
				} else if (typeof TYPO3 === "object" && typeof TYPO3.Backend === "object") {
					TYPO3ViewportInstance = TYPO3.Backend;
				}

				if (TYPO3ViewportInstance !== null) {
					TYPO3ViewportInstance.DebugConsole.addTab(message, header, group);
				} else if (typeof console === "object") {
					console.log(message);
				} else {
					document.write(message);
				}
			};

			Ext.Direct.on("exception", function(event) {
				var backtrace = "";
				if (event.where) {
					backtrace = "<p style=\"margin-top: 20px;\">" +
						"<strong>Backtrace:<\/strong><br \/>" +
						event.where.replace(/#/g, "<br \/>#") +
						"<\/p>";
				}

				extDirectDebug(
					"<p>" + event.message + "<\/p>" + backtrace,
					event.method,
					"ExtDirect - Exception"
				);
			});

			Ext.Direct.on("event", function(event, provider) {
				if (typeof event.debug !== "undefined" && event.debug !== "") {
					extDirectDebug(event.debug, event.method, "ExtDirect - Debug");
				}
			});
			',
			TRUE
		);
	}

	/* CSS Files */

	/**
	 * Adds CSS file
	 *
	 * @param string $file
	 * @param string $rel
	 * @param string $media
	 * @param string $title
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @return void
	 */
	public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '') {
		if (!isset($this->cssFiles[$file])) {
			$this->cssFiles[$file] = array(
				'rel' => $rel,
				'media' => $media,
				'title' => $title,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap
			);
		}
	}

	/*CSS Inline Blocks */

	/**
	 * Adds CSS inline code
	 *
	 * @param string $name
	 * @param string $block
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @return void
	 */
	public function addCssInlineBlock($name, $block, $compress = FALSE, $forceOnTop = FALSE) {
		if (!isset($this->cssInline[$name]) && !empty($block)) {
			$this->cssInline[$name] = array(
				'code' => $block,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop
			);
		}
	}

	/* JS Libraries */

	/**
	 *  call function if you need the prototype library
	 *
	 * @return void
	 */
	public function loadPrototype() {
		$this->addPrototype = TRUE;
	}

	/**
	 * call function if you need the Scriptaculous library
	 *
	 * @param string $modules   add modules you need. use "all" if you need complete modules
	 * @return void
	 */
	public function loadScriptaculous($modules = 'all') {
			// Scriptaculous require prototype, so load prototype too.
		$this->addPrototype = TRUE;
		$this->addScriptaculous = TRUE;
		if ($modules) {
			if ($modules == 'all') {
				foreach ($this->addScriptaculousModules as $key => $value) {
					$this->addScriptaculousModules[$key] = TRUE;
				}
			} else {
				$mods = t3lib_div::trimExplode(',', $modules);
				foreach ($mods as $mod) {
					if (isset($this->addScriptaculousModules[strtolower($mod)])) {
						$this->addScriptaculousModules[strtolower($mod)] = TRUE;
					}
				}
			}
		}
	}

	/**
	 * call this function if you need the extJS library
	 *
	 * @param boolean $css flag, if set the ext-css will be loaded
	 * @param boolean $theme flag, if set the ext-theme "grey" will be loaded
	 * @param string $adapter choose alternative adapter, possible values: yui, prototype, jquery
	 * @return void
	 */
	public function loadExtJS($css = TRUE, $theme = TRUE, $adapter = '') {
		if ($adapter) {
				// empty $adapter will always load the ext adapter
			switch (t3lib_div::strtolower(trim($adapter))) {
				case self::EXTJS_ADAPTER_YUI :
					$this->extJSadapter = 'yui/ext-yui-adapter.js';
				break;
				case self::EXTJS_ADAPTER_PROTOTYPE :
					$this->extJSadapter = 'prototype/ext-prototype-adapter.js';
				break;
				case self::EXTJS_ADAPTER_JQUERY :
					$this->extJSadapter = 'jquery/ext-jquery-adapter.js';
				break;
			}
		}
		$this->addExtJS = TRUE;
		$this->extJStheme = $theme;
		$this->extJScss = $css;

	}

	/**
	 * Enables ExtJs QuickTips
	 * Need extJs loaded
	 *
	 * @return void
	 *
	 */
	public function enableExtJSQuickTips() {
		$this->enableExtJSQuickTips = TRUE;
	}


	/**
	 * call function if you need the ExtCore library
	 *
	 * @return void
	 */
	public function loadExtCore() {
		$this->addExtCore = TRUE;
	}

	/**
	 * call function if you need the SVG library
	 *
	 * @return void
	 */
	public function loadSvg() {
		$this->addSvg = TRUE;
	}

	/**
	 * call this function to load debug version of ExtJS. Use this for development only
	 *
	 */
	public function enableSvgDebug() {
		$this->enableSvgDebug = TRUE;
	}

	/**
	 * call this function to force flash usage with SVG library
	 *
	 */
	public function svgForceFlash() {
		$this->addMetaTag('<meta name="svg.render.forceflash" content="true" />');
	}

	/**
	 * call this function to load debug version of ExtJS. Use this for development only
	 *
	 */
	public function enableExtJsDebug() {
		$this->enableExtJsDebug = TRUE;
	}

	/**
	 * call this function to load debug version of ExtCore. Use this for development only
	 *
	 * @return void
	 */
	public function enableExtCoreDebug() {
		$this->enableExtCoreDebug = TRUE;
	}

	/**
	 * Adds Javascript Inline Label. This will occur in TYPO3.lang - object
	 * The label can be used in scripts with TYPO3.lang.<key>
	 * Need extJs loaded
	 *
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function addInlineLanguageLabel($key, $value) {
		$this->inlineLanguageLabels[$key] = $value;
	}

	/**
	 * Adds Javascript Inline Label Array. This will occur in TYPO3.lang - object
	 * The label can be used in scripts with TYPO3.lang.<key>
	 * Array will be merged with existing array.
	 * Need extJs loaded
	 *
	 * @param array $array
	 * @return void
	 */
	public function addInlineLanguageLabelArray(array $array) {
		$this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $array);
	}

	/**
	 * Gets labels to be used in JavaScript fetched from a locallang file.
	 *
	 * @param	string		Input is a file-reference (see t3lib_div::getFileAbsFileName). That file is expected to be a 'locallang.xml' file containing a valid XML TYPO3 language structure.
	 * @param	string		$selectionPrefix: Prefix to select the correct labels (default: '')
	 * @param	string		$stripFromSelectionName: Sub-prefix to be removed from label names in the result (default: '')
	 * @param	integer		Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
	 * @return	void
	 */
	public function addInlineLanguageLabelFile($fileRef, $selectionPrefix = '', $stripFromSelectionName = '', $errorMode = 0) {
		$index = md5($fileRef . $selectionPrefix . $stripFromSelectionName);
		if ($fileRef && !isset($this->inlineLanguageLabelFiles[$index])) {
			$this->inlineLanguageLabelFiles[$index] = array(
				'fileRef' => $fileRef,
				'selectionPrefix' => $selectionPrefix,
				'stripFromSelectionName' => $stripFromSelectionName,
				'errorMode' => $errorMode
			);
		}
	}


	/**
	 * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
	 * The label can be used in scripts with TYPO3.setting.<key>
	 * Need extJs loaded
	 *
	 * @param string $namespace
	 * @param string $key
	 * @param string $value
	 * @return void
	 */
	public function addInlineSetting($namespace, $key, $value) {
		if ($namespace) {
			if (strpos($namespace, '.')) {
				$parts = explode('.', $namespace);
				$a = &$this->inlineSettings;
				foreach ($parts as $part) {
					$a = &$a[$part];
				}
				$a[$key] = $value;
			} else {
				$this->inlineSettings[$namespace][$key] = $value;
			}
		} else {
			$this->inlineSettings[$key] = $value;
		}
	}

	/**
	 * Adds Javascript Inline Setting. This will occur in TYPO3.settings - object
	 * The label can be used in scripts with TYPO3.setting.<key>
	 * Array will be merged with existing array.
	 * Need extJs loaded
	 *
	 * @param string $namespace
	 * @param array $array
	 * @return void
	 */
	public function addInlineSettingArray($namespace, array $array) {
		if ($namespace) {
			if (strpos($namespace, '.')) {
				$parts = explode('.', $namespace);
				$a = &$this->inlineSettings;
				foreach ($parts as $part) {
					$a = &$a[$part];
				}
				$a = array_merge((array) $a, $array);
			} else {
				$this->inlineSettings[$namespace] = array_merge((array) $this->inlineSettings[$namespace], $array);
			}
		} else {
			$this->inlineSettings = array_merge($this->inlineSettings, $array);
		}
	}

	/**
	 * Adds content to body content
	 *
	 * @param string $content
	 * @return void
	 */
	public function addBodyContent($content) {
		$this->bodyContent .= $content;
	}

	/*****************************************************/
	/*                                                   */
	/*  Render Functions                                 */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * render the section (Header or Footer)
	 *
	 * @param int $part	section which should be rendered: self::PART_COMPLETE, self::PART_HEADER or self::PART_FOOTER
	 * @return string	content of rendered section
	 */
	public function render($part = self::PART_COMPLETE) {

		$jsFiles = '';
		$cssFiles = '';
		$cssInline = '';
		$jsInline = '';
		$jsFooterInline = '';
		$jsFooterLibs = '';
		$jsFooterFiles = '';

			// preRenderHook for possible manuipulation
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'])) {
			$params = array(
				'jsLibs' => &$this->jsLibs,
				'jsFiles' => &$this->jsFiles,
				'jsFooterFiles' => &$this->jsFooterFiles,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
				'jsInline' => &$this->jsInline,
				'cssInline' => &$this->cssInline,
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] as $hook) {
				t3lib_div::callUserFunction($hook, $params, $this);
			}
		}

		$jsLibs = $this->renderJsLibraries();

		if ($this->concatenateFiles) {
				// do the file concatenation
			$this->doConcatenate();
		}
		if ($this->compressCss || $this->compressJavascript) {
				// do the file compression
			$this->doCompress();
		}

		$metaTags = implode(LF, $this->metaTags);

			// remove ending slashes from static header block
			// if the page is beeing rendered as html (not xhtml)
			// and define variable $endingSlash for further use
		if ($this->getRenderXhtml()) {
			$endingSlash = ' /';
		} else {
			$this->metaCharsetTag = str_replace(' />', '>', $this->metaCharsetTag);
			$this->baseUrlTag = str_replace(' />', '>', $this->baseUrlTag);
			$this->shortcutTag = str_replace(' />', '>', $this->shortcutTag);
			$endingSlash = '';
		}

		if (count($this->cssFiles)) {
			foreach ($this->cssFiles as $file => $properties) {
				$file = t3lib_div::resolveBackPath($file);
				$file = t3lib_div::createVersionNumberedFilename($file);
				$tag = '<link rel="' . htmlspecialchars($properties['rel']) . '" type="text/css" href="' .
					   htmlspecialchars($file) . '" media="' . htmlspecialchars($properties['media']) . '"' .
					   ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '') .
					   $endingSlash . '>';
				if ($properties['allWrap'] && strpos($properties['allWrap'], '|') !== FALSE) {
					$tag = str_replace('|', $tag, $properties['allWrap']);
				}
				if ($properties['forceOnTop']) {
					$cssFiles = $tag . LF . $cssFiles;
				} else {
					$cssFiles .= LF . $tag;
				}
			}
		}

		if (count($this->cssInline)) {
			foreach ($this->cssInline as $name => $properties) {
				if ($properties['forceOnTop']) {
					$cssInline = '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF . $cssInline;
				} else {
					$cssInline .= '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF;
				}
			}
			$cssInline = $this->inlineCssWrap[0] . $cssInline . $this->inlineCssWrap[1];
		}

		if (count($this->jsLibs)) {
			foreach ($this->jsLibs as $name => $properties) {
				$properties['file'] = t3lib_div::resolveBackPath($properties['file']);
				$properties['file'] = t3lib_div::createVersionNumberedFilename($properties['file']);
				$tag = '<script src="' . htmlspecialchars($properties['file']) . '" type="' . htmlspecialchars($properties['type']) . '"></script>';
				if ($properties['allWrap'] && strpos($properties['allWrap'], '|') !== FALSE) {
					$tag = str_replace('|', $tag, $properties['allWrap']);
				}
				if ($properties['forceOnTop']) {
					if ($properties['section'] === self::PART_HEADER) {
						$jsLibs = $tag . LF . $jsLibs;
					} else {
						$jsFooterLibs = $tag . LF . $jsFooterLibs;
					}
				} else {
					if ($properties['section'] === self::PART_HEADER) {
						$jsLibs .= LF . $tag;
					} else {
						$jsFooterLibs .= LF . $tag;
					}
				}
			}
		}

		if (count($this->jsFiles)) {
			foreach ($this->jsFiles as $file => $properties) {
				$file = t3lib_div::resolveBackPath($file);
				$file = t3lib_div::createVersionNumberedFilename($file);
				$tag = '<script src="' . htmlspecialchars($file) . '" type="' . htmlspecialchars($properties['type']) . '"></script>';
				if ($properties['allWrap'] && strpos($properties['allWrap'], '|') !== FALSE) {
					$tag = str_replace('|', $tag, $properties['allWrap']);
				}
				if ($properties['forceOnTop']) {
					if ($properties['section'] === self::PART_HEADER) {
						$jsFiles = $tag . LF . $jsFiles;
					} else {
						$jsFooterFiles = $tag . LF . $jsFooterFiles;
					}
				} else {
					if ($properties['section'] === self::PART_HEADER) {
						$jsFiles .= LF . $tag;
					} else {
						$jsFooterFiles .= LF . $tag;
					}
				}
			}
		}

		if (count($this->jsInline)) {
			foreach ($this->jsInline as $name => $properties) {
				if ($properties['forceOnTop']) {
					if ($properties['section'] === self::PART_HEADER) {
						$jsInline = '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF . $jsInline;
					} else {
						$jsFooterInline = '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF . $jsFooterInline;
					}
				} else {
					if ($properties['section'] === self::PART_HEADER) {
						$jsInline .= '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF;
					} else {
						$jsFooterInline .= '/*' . htmlspecialchars($name) . '*/' . LF . $properties['code'] . LF;
					}
				}
			}
		}


		if ($jsInline) {
			$jsInline = $this->inlineJavascriptWrap[0] . $jsInline . $this->inlineJavascriptWrap[1];
		}

		if ($jsFooterInline) {
			$jsFooterInline = $this->inlineJavascriptWrap[0] . $jsFooterInline . $this->inlineJavascriptWrap[1];
		}


			// get template
		$templateFile = t3lib_div::getFileAbsFileName($this->templateFile, TRUE);
		$template = t3lib_div::getURL($templateFile);

		if ($this->removeLineBreaksFromTemplate) {
			$template = strtr($template, array(LF => '', CR => ''));
		}
		if ($part != self::PART_COMPLETE) {
			$templatePart = explode('###BODY###', $template);
			$template = $templatePart[$part - 1];
		}

		if ($this->moveJsFromHeaderToFooter) {
			$jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
			$jsLibs = '';
			$jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
			$jsFiles = '';
			$jsFooterInline = $jsInline . LF . $jsFooterInline;
			$jsInline = '';
		}

		$markerArray = array(
			'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
			'HTMLTAG' => $this->htmlTag,
			'HEADTAG' => $this->headTag,
			'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
			'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
			'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
			'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
			'CSS_INCLUDE' => $cssFiles,
			'CSS_INLINE' => $cssInline,
			'JS_INLINE' => $jsInline,
			'JS_INCLUDE' => $jsFiles,
			'JS_LIBS' => $jsLibs,
			'TITLE' => $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '',
			'META' => $metaTags,
			'HEADERDATA' => $this->headerData ? implode(LF, $this->headerData) : '',
			'FOOTERDATA' => $this->footerData ? implode(LF, $this->footerData) : '',
			'JS_LIBS_FOOTER' => $jsFooterLibs,
			'JS_INCLUDE_FOOTER' => $jsFooterFiles,
			'JS_INLINE_FOOTER' => $jsFooterInline,
			'BODY' => $this->bodyContent,
		);

		$markerArray = array_map('trim', $markerArray);

		$this->reset();
		return trim(t3lib_parsehtml::substituteMarkerArray($template, $markerArray, '###|###'));
	}

	/**
	 * helper function for render the javascript libraries
	 *
	 * @return string	content with javascript libraries
	 */
	protected function renderJsLibraries() {
		$out = '';

		if ($this->addSvg) {
			$out .= '<script src="' . $this->processJsFile($this->backPath . $this->svgPath . 'svg.js') .
					'" data-path="' . $this->backPath . $this->svgPath .
					'"' . ($this->enableSvgDebug ? ' data-debug="true"' : '') . '></script>';
		}

		if ($this->addPrototype) {
			$out .= '<script src="' . $this->processJsFile($this->backPath . $this->prototypePath . 'prototype.js') .
					'" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->prototypePath . 'prototype.js']);
		}

		if ($this->addScriptaculous) {
			$mods = array();
			foreach ($this->addScriptaculousModules as $key => $value) {
				if ($this->addScriptaculousModules[$key]) {
					$mods[] = $key;
				}
			}
				// resolve dependencies
			if (in_array('dragdrop', $mods) || in_array('controls', $mods)) {
				$mods = array_merge(array('effects'), $mods);
			}

			if (count($mods)) {
				foreach ($mods as $module) {
					$out .= '<script src="' . $this->processJsFile($this->backPath .
																   $this->scriptaculousPath . $module . '.js') . '" type="text/javascript"></script>' . LF;
					unset($this->jsFiles[$this->backPath . $this->scriptaculousPath . $module . '.js']);
				}
			}
			$out .= '<script src="' . $this->processJsFile($this->backPath . $this->scriptaculousPath .
														   'scriptaculous.js') . '" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->scriptaculousPath . 'scriptaculous.js']);
		}

			// include extCore
		if ($this->addExtCore) {
			$out .= '<script src="' . $this->processJsFile($this->backPath .
														   $this->extCorePath . 'ext-core' . ($this->enableExtCoreDebug ? '-debug' : '') . '.js') .
					'" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->extCorePath . 'ext-core' . ($this->enableExtCoreDebug ? '-debug' : '') . '.js']);
		}

			// include extJS
		if ($this->addExtJS) {
				// use the base adapter all the time
			$out .= '<script src="' . $this->processJsFile($this->backPath . $this->extJsPath .
														   'adapter/' . ($this->enableExtJsDebug ?
					str_replace('.js', '-debug.js', $this->extJSadapter) : $this->extJSadapter)) .
					'" type="text/javascript"></script>' . LF;
			$out .= '<script src="' . $this->processJsFile($this->backPath . $this->extJsPath .
														   'ext-all' . ($this->enableExtJsDebug ? '-debug' : '') . '.js') .
					'" type="text/javascript"></script>' . LF;

				// add extJS localization
			$localeMap = $this->csConvObj->isoArray; // load standard ISO mapping and modify for use with ExtJS
			$localeMap[''] = 'en';
			$localeMap['default'] = 'en';
			$localeMap['gr'] = 'el_GR'; // Greek
			$localeMap['no'] = 'no_BO'; // Norwegian Bokmaal
			$localeMap['se'] = 'se_SV'; // Swedish


			$extJsLang = isset($localeMap[$this->lang]) ? $localeMap[$this->lang] : $this->lang;
				// TODO autoconvert file from UTF8 to current BE charset if necessary!!!!
			$extJsLocaleFile = $this->extJsPath . 'locale/ext-lang-' . $extJsLang . '.js';
			if (file_exists(PATH_typo3 . $extJsLocaleFile)) {
				$out .= '<script src="' . $this->processJsFile($this->backPath .
															   $extJsLocaleFile) . '" type="text/javascript" charset="utf-8"></script>' . LF;
			}


				// remove extjs from JScodeLibArray
			unset(
			$this->jsFiles[$this->backPath . $this->extJsPath . 'ext-all.js'],
			$this->jsFiles[$this->backPath . $this->extJsPath . 'ext-all-debug.js']
			);
		}

		if (count($this->inlineLanguageLabelFiles)) {
			foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
				$this->includeLanguageFileForInline(
					$languageLabelFile['fileRef'],
					$languageLabelFile['selectionPrefix'],
					$languageLabelFile['stripFromSelectionName'],
					$languageLabelFile['$errorMode']
				);
			}
		}
		unset($this->inlineLanguageLabelFiles);

			// Convert labels/settings back to UTF-8 since json_encode() only works with UTF-8:
		if ($this->getCharSet() !== 'utf-8') {
			if ($this->inlineLanguageLabels) {
				$this->csConvObj->convArray($this->inlineLanguageLabels, $this->getCharSet(), 'utf-8');
			}
			if ($this->inlineSettings) {
				$this->csConvObj->convArray($this->inlineSettings, $this->getCharSet(), 'utf-8');
			}
		}

		$inlineSettings = $this->inlineLanguageLabels ? 'TYPO3.lang = ' . json_encode($this->inlineLanguageLabels) . ';' : '';
		$inlineSettings .= $this->inlineSettings ? 'TYPO3.settings = ' . json_encode($this->inlineSettings) . ';' : '';

		if ($this->addExtCore || $this->addExtJS) {
				// set clear.gif, move it on top, add handler code
			$code = '';
			if (count($this->extOnReadyCode)) {
				foreach ($this->extOnReadyCode as $block) {
					$code .= $block;
				}
			}

			$out .= $this->inlineJavascriptWrap[0] . '
				Ext.ns("TYPO3");
				Ext.BLANK_IMAGE_URL = "' . htmlspecialchars(t3lib_div::locationHeaderUrl($this->backPath . 'gfx/clear.gif')) . '";' . LF .
					$inlineSettings .
					'Ext.onReady(function() {' .
					($this->enableExtJSQuickTips ? 'Ext.QuickTips.init();' . LF : '') . $code .
					' });' . $this->inlineJavascriptWrap[1];
			unset ($this->extOnReadyCode);

			if ($this->extJStheme) {
				if (isset($GLOBALS['TBE_STYLES']['extJS']['theme'])) {
					$this->addCssFile($this->backPath . $GLOBALS['TBE_STYLES']['extJS']['theme'], 'stylesheet', 'all', '', TRUE, TRUE);
				} else {
					$this->addCssFile($this->backPath . $this->extJsPath . 'resources/css/xtheme-blue.css', 'stylesheet', 'all', '', TRUE, TRUE);
				}
			}
			if ($this->extJScss) {
				if (isset($GLOBALS['TBE_STYLES']['extJS']['all'])) {
					$this->addCssFile($this->backPath . $GLOBALS['TBE_STYLES']['extJS']['all'], 'stylesheet', 'all', '', TRUE, TRUE);
				} else {
					$this->addCssFile($this->backPath . $this->extJsPath . 'resources/css/ext-all-notheme.css', 'stylesheet', 'all', '', TRUE, TRUE);
				}
			}
		} else {
			if ($inlineSettings) {
				$out .= $this->inlineJavascriptWrap[0] . $inlineSettings . $this->inlineJavascriptWrap[1];
			}
		}

		return $out;
	}

	protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '', $errorMode = 0) {
		if (!isset($this->lang) || !isset($this->charSet)) {
			throw new RuntimeException('Language and character encoding are not set.', 1284906026);
		}

		$labelsFromFile = array();
		$allLabels = t3lib_div::readLLfile($fileRef, $this->lang, $this->charSet, $errorMode);

			// Regular expression to strip the selection prefix and possibly something from the label name:
		$labelPattern = '#^' . preg_quote($selectionPrefix, '#') . '(' . preg_quote($stripFromSelectionName, '#') . ')?#';

		if ($allLabels !== FALSE) {
				// Merge language specific translations:
			if ($this->lang !== 'default' && isset($allLabels[$this->lang])) {
				$labels = array_merge($allLabels['default'], $allLabels[$this->lang]);
			} else {
				$labels = $allLabels['default'];
			}

				// Iterate through all locallang labels:
			foreach ($labels as $label => $value) {
				if ($selectionPrefix === '') {
					$labelsFromFile[$label] = $value;
				} elseif (strpos($label, $selectionPrefix) === 0) {
					$key = preg_replace($labelPattern, '', $label);
					$labelsFromFile[$label] = $value;
				}
			}

			$this->inlineLanguageLabels = array_merge($this->inlineLanguageLabels, $labelsFromFile);
		}
	}

	/*****************************************************/
	/*                                                   */
	/*  Tools                                            */
	/*                                                   */
	/*                                                   */
	/*****************************************************/

	/**
	 * concatenate files into one file
	 * registered handler
	 *
	 * @return void
	 */
	protected function doConcatenate() {
			// traverse the arrays, concatenate in one file
			// then remove concatenated files from array and add the concatenated file

		if ($this->concatenateFiles) {
			$params = array(
				'jsLibs' => &$this->jsLibs,
				'jsFiles' => &$this->jsFiles,
				'jsFooterFiles' => &$this->jsFooterFiles,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
			);

			if ($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['concatenateHandler']) {
					// use extern concatenate routine
				t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['concatenateHandler'], $params, $this);
			} elseif (TYPO3_MODE === 'BE') {
				$cssOptions = array('baseDirectories' => $GLOBALS['TBE_TEMPLATE']->getSkinStylesheetDirectories());
				$this->cssFiles = $this->getCompressor()->concatenateCssFiles($this->cssFiles, $cssOptions);
			}
		}
	}

	/**
	 * compress inline code
	 *
	 * @return void
	 */
	protected function doCompress() {

		if ($this->compressJavascript && $GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler']) {
				// use extern compress routine
			$params = array(
				'jsInline' => &$this->jsInline,
				'jsFooterInline' => &$this->jsFooterInline,
				'jsLibs' => &$this->jsLibs,
				'jsFiles' => &$this->jsFiles,
				'jsFooterFiles' => &$this->jsFooterFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
			);
			t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'], $params, $this);
		} else {
				// traverse the arrays, compress files

			if ($this->compressJavascript) {
				if (count($this->jsInline)) {
					foreach ($this->jsInline as $name => $properties) {
						if ($properties['compress']) {
							$error = '';
							$this->jsInline[$name]['code'] = t3lib_div::minifyJavaScript($properties['code'], $error);
							if ($error) {
								$this->compressError .= 'Error with minify JS Inline Block "' . $name . '": ' . $error . LF;
							}
						}
					}
				}
				if (TYPO3_MODE === 'BE') {
					$this->jsFiles = $this->getCompressor()->compressJsFiles($this->jsFiles);
					$this->jsFooterFiles = $this->getCompressor()->compressJsFiles($this->jsFooterFiles);
				}
			}
		}
		if ($this->compressCss) {
				// use extern compress routine
			$params = array(
				'cssInline' => &$this->cssInline,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
			);

			if ($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler']) {
					// use extern concatenate routine
				t3lib_div::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler'], $params, $this);
			} elseif (TYPO3_MODE === 'BE') {
				$this->cssFiles = $this->getCompressor()->compressCssFiles($this->cssFiles);
			}
		}
	}

	/**
	 * Returns instance of t3lib_Compressor
	 *
	 * @return	t3lib_Compressor		Instance of t3lib_Compressor
	 */
	protected function getCompressor() {
		if ($this->compressor === NULL) {
			$this->compressor = t3lib_div::makeInstance('t3lib_Compressor');
		}
		return $this->compressor;
	}

	/**
	 * Processes a Javascript file dependent on the current context
	 *
	 * Adds the version number for Frontend, compresses the file for Backend
	 *
	 * @param	string	$filename		Filename
	 * @return	string		new filename
	 */
	protected function processJsFile($filename) {
		switch (TYPO3_MODE) {
			case 'FE':
				$filename = t3lib_div::createVersionNumberedFilename($filename);
			break;
			case 'BE':
				if ($this->compressJavascript) {
					$filename = $this->getCompressor()->compressJsFile($filename);
				}
			break;
		}
		return $filename;
	}

}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagerenderer.php']) {
	include_once ($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['t3lib/class.t3lib_pagerenderer.php']);
}
?>