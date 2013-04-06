<?php
namespace TYPO3\CMS\Core\Page;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2009-2013 Steffen Kamper <info@sk-typo3.de>
 *  (c) 2011-2013 Kai Vogel <kai.vogel@speedprogs.de>
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
 * @author Steffen Kamper <info@sk-typo3.de>
 * @author Kai Vogel <kai.vogel@speedprogs.de>
 */
class PageRenderer implements \TYPO3\CMS\Core\SingletonInterface {

	// Constants for the part to be rendered
	const PART_COMPLETE = 0;
	const PART_HEADER = 1;
	const PART_FOOTER = 2;
	// Available adapters for extJs
	const EXTJS_ADAPTER_JQUERY = 'jquery';
	const EXTJS_ADAPTER_PROTOTYPE = 'prototype';
	const EXTJS_ADAPTER_YUI = 'yui';
	// jQuery Core version that is shipped with TYPO3
	const JQUERY_VERSION_LATEST = '1.9.1';
	// jQuery namespace options
	const JQUERY_NAMESPACE_NONE = 'none';
	const JQUERY_NAMESPACE_DEFAULT = 'jQuery';
	const JQUERY_NAMESPACE_DEFAULT_NOCONFLICT = 'defaultNoConflict';
	/**
	 * @var boolean
	 */
	protected $compressJavascript = FALSE;

	/**
	 * @var boolean
	 */
	protected $compressCss = FALSE;

	/**
	 * @var boolean
	 */
	protected $removeLineBreaksFromTemplate = FALSE;

	/**
	 * @var boolean
	 */
	protected $concatenateFiles = FALSE;

	/**
	 * @var boolean
	 */
	protected $concatenateJavascript = FALSE;

	/**
	 * @var boolean
	 */
	protected $concatenateCss = FALSE;

	/**
	 * @var boolean
	 */
	protected $moveJsFromHeaderToFooter = FALSE;

	/**
	 * @var \TYPO3\CMS\Core\Charset\CharsetConverter
	 */
	protected $csConvObj;

	/**
	 * @var \TYPO3\CMS\Core\Localization\Locales
	 */
	protected $locales;

	/**
	 * The language key
	 * Two character string or 'default'
	 *
	 * @var string
	 */
	protected $lang;

	/**
	 * List of language dependencies for actual language. This is used for local variants of a language
	 * that depend on their "main" language, like Brazilian Portuguese or Canadian French.
	 *
	 * @var array
	 */
	protected $languageDependencies = array();

	/**
	 * @var \TYPO3\CMS\Core\Resource\ResourceCompressor
	 */
	protected $compressor;

	// Arrays containing associative array for the included files
	/**
	 * @var array
	 */
	protected $jsFiles = array();

	/**
	 * @var array
	 */
	protected $jsFooterFiles = array();

	/**
	 * @var array
	 */
	protected $jsLibs = array();

	/**
	 * @var array
	 */
	protected $jsFooterLibs = array();

	/**
	 * @var array
	 */
	protected $cssFiles = array();

	/**
	 * The title of the page
	 *
	 * @var string
	 */
	protected $title;

	/**
	 * Charset for the rendering
	 *
	 * @var string
	 */
	protected $charSet;

	/**
	 * @var string
	 */
	protected $favIcon;

	/**
	 * @var string
	 */
	protected $baseUrl;

	/**
	 * @var boolean
	 */
	protected $renderXhtml = TRUE;

	// Static header blocks
	/**
	 * @var string
	 */
	protected $xmlPrologAndDocType = '';

	/**
	 * @var array
	 */
	protected $metaTags = array();

	/**
	 * @var array
	 */
	protected $inlineComments = array();

	/**
	 * @var array
	 */
	protected $headerData = array();

	/**
	 * @var array
	 */
	protected $footerData = array();

	/**
	 * @var string
	 */
	protected $titleTag = '<title>|</title>';

	/**
	 * @var string
	 */
	protected $metaCharsetTag = '<meta http-equiv="Content-Type" content="text/html; charset=|" />';

	/**
	 * @var string
	 */
	protected $htmlTag = '<html>';

	/**
	 * @var string
	 */
	protected $headTag = '<head>';

	/**
	 * @var string
	 */
	protected $baseUrlTag = '<base href="|" />';

	/**
	 * @var string
	 */
	protected $iconMimeType = '';

	/**
	 * @var string
	 */
	protected $shortcutTag = '<link rel="shortcut icon" href="%1$s"%2$s />
<link rel="icon" href="%1$s"%2$s />';

	// Static inline code blocks
	/**
	 * @var array
	 */
	protected $jsInline = array();

	/**
	 * @var array
	 */
	protected $jsFooterInline = array();

	/**
	 * @var array
	 */
	protected $extOnReadyCode = array();

	/**
	 * @var array
	 */
	protected $cssInline = array();

	/**
	 * @var string
	 */
	protected $bodyContent;

	/**
	 * @var string
	 */
	protected $templateFile;

	/**
	 * @var array
	 */
	protected $jsLibraryNames = array('prototype', 'scriptaculous', 'extjs');

	// Paths to contibuted libraries

	/**
	 * default path to the requireJS library, relative to the typo3/ directory
	 * @var string
	 */
	protected $requireJsPath = 'contrib/requirejs/';

	/**
	 * @var string
	 */
	protected $prototypePath = 'contrib/prototype/';

	/**
	 * @var string
	 */
	protected $scriptaculousPath = 'contrib/scriptaculous/';

	/**
	 * @var string
	 */
	protected $extCorePath = 'contrib/extjs/';

	/**
	 * @var string
	 */
	protected $extJsPath = 'contrib/extjs/';

	/**
	 * @var string
	 */
	protected $svgPath = 'contrib/websvg/';

	/**
	 * The local directory where one can find jQuery versions and plugins
	 *
	 * @var string
	 */
	protected $jQueryPath = 'contrib/jquery/';

	// Internal flags for JS-libraries
	/**
	 * This array holds all jQuery versions that should be included in the
	 * current page.
	 * Each version is described by "source", "version" and "namespace"
	 *
	 * The namespace of every particular version is the key
	 * of that array, because only one version per namespace can exist.
	 *
	 * The type "source" describes where the jQuery core should be included from
	 * currently, TYPO3 supports "local" (make use of jQuery path), "google",
	 * "jquery" and "msn".
	 * Currently there are downsides to "local" and "jquery", as "local" only
	 * supports the latest/shipped jQuery core out of the box, and
	 * "jquery" does not have SSL support.
	 *
	 * @var array
	 */
	protected $jQueryVersions = array();

	/**
	 * Array of jQuery version numbers shipped with the core
	 *
	 * @var array
	 */
	protected $availableLocalJqueryVersions = array(
		'1.8.2',	// jquery version shipped with TYPO3 6.0, still available in the contrib/ directory
		self::JQUERY_VERSION_LATEST
	);

	/**
	 * Array of jQuery CDNs with placeholders
	 *
	 * @var array
	 */
	protected $jQueryCdnUrls = array(
		'google' => '//ajax.googleapis.com/ajax/libs/jquery/%1$s/jquery%2$s.js',
		'msn' => '//ajax.aspnetcdn.com/ajax/jQuery/jquery-%1$s%2$s.js',
		'jquery' => 'http://code.jquery.com/jquery-%1$s%2$s.js'
	);

	/**
	 * if set, the requireJS library is included
	 * @var boolean
	 */
	protected $addRequireJs = FALSE;

	/**
	 * inline configuration for requireJS
	 * @var array
	 */
	protected $requireJsConfig = array();

	/**
	 * @var boolean
	 */
	protected $addPrototype = FALSE;

	/**
	 * @var boolean
	 */
	protected $addScriptaculous = FALSE;

	/**
	 * @var array
	 */
	protected $addScriptaculousModules = array('builder' => FALSE, 'effects' => FALSE, 'dragdrop' => FALSE, 'controls' => FALSE, 'slider' => FALSE);

	/**
	 * @var boolean
	 */
	protected $addExtJS = FALSE;

	/**
	 * @var boolean
	 */
	protected $addExtCore = FALSE;

	/**
	 * @var string
	 */
	protected $extJSadapter = 'ext/ext-base.js';

	/**
	 * @var boolean
	 */
	protected $extDirectCodeAdded = FALSE;

	/**
	 * @var boolean
	 */
	protected $enableExtJsDebug = FALSE;

	/**
	 * @var boolean
	 */
	protected $enableExtCoreDebug = FALSE;

	/**
	 * @var boolean
	 */
	protected $enableJqueryDebug = FALSE;

	/**
	 * @var boolean
	 */
	protected $extJStheme = TRUE;

	/**
	 * @var boolean
	 */
	protected $extJScss = TRUE;

	/**
	 * @var boolean
	 */
	protected $enableExtJSQuickTips = FALSE;

	/**
	 * @var array
	 */
	protected $inlineLanguageLabels = array();

	/**
	 * @var array
	 */
	protected $inlineLanguageLabelFiles = array();

	/**
	 * @var array
	 */
	protected $inlineSettings = array();

	/**
	 * @var array
	 */
	protected $inlineJavascriptWrap = array();

	/**
	 * Saves error messages generated during compression
	 *
	 * @var string
	 */
	protected $compressError = '';

	/**
	 * Is empty string for HTML and ' /' for XHTML rendering
	 *
	 * @var string
	 */
	protected $endingSlash = '';

	/**
	 * SVG library
	 *
	 * @var boolean
	 */
	protected $addSvg = FALSE;

	/**
	 * @var boolean
	 */
	protected $enableSvgDebug = FALSE;

	/**
	 * Used by BE modules
	 *
	 * @var null|string
	 */
	public $backPath;

	/**
	 * @param string $templateFile Declare the used template file. Omit this parameter will use default template
	 * @param string $backPath Relative path to typo3-folder. It varies for BE modules, in FE it will be typo3/
	 */
	public function __construct($templateFile = '', $backPath = NULL) {
		$this->reset();
		$this->csConvObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Charset\\CharsetConverter');
		$this->locales = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Localization\\Locales');
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
	 * Reset all vars to initial values
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
		$this->jQueryVersions = array();
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
	 * @param boolean $enable Enable XHTML
	 * @return void
	 */
	public function setRenderXhtml($enable) {
		$this->renderXhtml = $enable;
	}

	/**
	 * Sets xml prolog and docType
	 *
	 * @param string $xmlPrologAndDocType Complete tags for xml prolog and docType
	 * @return void
	 */
	public function setXmlPrologAndDocType($xmlPrologAndDocType) {
		$this->xmlPrologAndDocType = $xmlPrologAndDocType;
	}

	/**
	 * Sets meta charset
	 *
	 * @param string $charSet Used charset
	 * @return void
	 */
	public function setCharSet($charSet) {
		$this->charSet = $charSet;
	}

	/**
	 * Sets language
	 *
	 * @param string $lang Used language
	 * @return void
	 */
	public function setLanguage($lang) {
		$this->lang = $lang;
		$this->languageDependencies = array();

		// Language is found. Configure it:
		if (in_array($this->lang, $this->locales->getLocales())) {
			$this->languageDependencies[] = $this->lang;
			foreach ($this->locales->getLocaleDependencies($this->lang) as $language) {
				$this->languageDependencies[] = $language;
			}
		}
	}

	/**
	 * Set the meta charset tag
	 *
	 * @param string $metaCharsetTag
	 * @return void
	 */
	public function setMetaCharsetTag($metaCharsetTag) {
		$this->metaCharsetTag = $metaCharsetTag;
	}

	/**
	 * Sets html tag
	 *
	 * @param string $htmlTag Html tag
	 * @return void
	 */
	public function setHtmlTag($htmlTag) {
		$this->htmlTag = $htmlTag;
	}

	/**
	 * Sets HTML head tag
	 *
	 * @param string $headTag HTML head tag
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
	 * Sets HTML base URL
	 *
	 * @param string $baseUrl HTML base URL
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
	 * Sets path to requireJS library (relative to typo3 directory)
	 *
	 * @param string $path Path to requireJS library
	 * @return void
	 */
	public function setRequireJsPath($path) {
		$this->requireJsPath = $path;
	}

	/**
	 * Sets path to prototype library (relative to typo3 directory)
	 *
	 * @param string $path Path to prototype library
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
	 * @return void
	 */
	public function enableMoveJsFromHeaderToFooter() {
		$this->moveJsFromHeaderToFooter = TRUE;
	}

	/**
	 * Disables MoveJsFromHeaderToFooter
	 *
	 * @return void
	 */
	public function disableMoveJsFromHeaderToFooter() {
		$this->moveJsFromHeaderToFooter = FALSE;
	}

	/**
	 * Enables compression of javascript
	 *
	 * @return void
	 */
	public function enableCompressJavascript() {
		$this->compressJavascript = TRUE;
	}

	/**
	 * Disables compression of javascript
	 *
	 * @return void
	 */
	public function disableCompressJavascript() {
		$this->compressJavascript = FALSE;
	}

	/**
	 * Enables compression of css
	 *
	 * @return void
	 */
	public function enableCompressCss() {
		$this->compressCss = TRUE;
	}

	/**
	 * Disables compression of css
	 *
	 * @return void
	 */
	public function disableCompressCss() {
		$this->compressCss = FALSE;
	}

	/**
	 * Enables concatenation of js and css files
	 *
	 * @return void
	 */
	public function enableConcatenateFiles() {
		$this->concatenateFiles = TRUE;
	}

	/**
	 * Disables concatenation of js and css files
	 *
	 * @return void
	 */
	public function disableConcatenateFiles() {
		$this->concatenateFiles = FALSE;
	}

	/**
	 * Enables concatenation of js files
	 *
	 * @return void
	 */
	public function enableConcatenateJavascript() {
		$this->concatenateJavascript = TRUE;
	}

	/**
	 * Disables concatenation of js files
	 *
	 * @return void
	 */
	public function disableConcatenateJavascript() {
		$this->concatenateJavascript = FALSE;
	}

	/**
	 * Enables concatenation of css files
	 *
	 * @return void
	 */
	public function enableConcatenateCss() {
		$this->concatenateCss = TRUE;
	}

	/**
	 * Disables concatenation of css files
	 *
	 * @return void
	 */
	public function disableConcatenateCss() {
		$this->concatenateCss = FALSE;
	}

	/**
	 * Sets removal of all line breaks in template
	 *
	 * @return void
	 */
	public function enableRemoveLineBreaksFromTemplate() {
		$this->removeLineBreaksFromTemplate = TRUE;
	}

	/**
	 * Unsets removal of all line breaks in template
	 *
	 * @return void
	 */
	public function disableRemoveLineBreaksFromTemplate() {
		$this->removeLineBreaksFromTemplate = FALSE;
	}

	/**
	 * Enables Debug Mode
	 * This is a shortcut to switch off all compress/concatenate features to enable easier debug
	 *
	 * @return void
	 */
	public function enableDebugMode() {
		$this->compressJavascript = FALSE;
		$this->compressCss = FALSE;
		$this->concatenateFiles = FALSE;
		$this->removeLineBreaksFromTemplate = FALSE;
		$this->enableExtCoreDebug = TRUE;
		$this->enableExtJsDebug = TRUE;
		$this->enableJqueryDebug = TRUE;
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
	 * @return string $title Title of webpage
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
	 * @return boolean TRUE if XHTML, FALSE if HTML
	 */
	public function getRenderXhtml() {
		return $this->renderXhtml;
	}

	/**
	 * Gets html tag
	 *
	 * @return string $htmlTag Html tag
	 */
	public function getHtmlTag() {
		return $this->htmlTag;
	}

	/**
	 * Get meta charset
	 *
	 * @return string
	 */
	public function getMetaCharsetTag() {
		return $this->metaCharsetTag;
	}

	/**
	 * Gets head tag
	 *
	 * @return string $tag Head tag
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
	 * Gets HTML base URL
	 *
	 * @return string $url
	 */
	public function getBaseUrl() {
		return $this->baseUrl;
	}

	/**
	 * Gets template file
	 *
	 * @return string
	 */
	public function getTemplateFile() {
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
	 * Gets concatenate of js and css files
	 *
	 * @return boolean
	 */
	public function getConcatenateFiles() {
		return $this->concatenateFiles;
	}

	/**
	 * Gets concatenate of js files
	 *
	 * @return boolean
	 */
	public function getConcatenateJavascript() {
		return $this->concatenateJavascript;
	}

	/**
	 * Gets concatenate of css files
	 *
	 * @return boolean
	 */
	public function getConcatenateCss() {
		return $this->concatenateCss;
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
	/*  Public Functions to add Data                     */
	/*                                                   */
	/*                                                   */
	/*****************************************************/
	/**
	 * Adds meta data
	 *
	 * @param string $meta Meta data (complete metatag)
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
	 * @param string $data Free header data for HTML header
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
	 * @param string $data Free header data for HTML header
	 * @return void
	 */
	public function addFooterData($data) {
		if (!in_array($data, $this->footerData)) {
			$this->footerData[] = $data;
		}
	}

	/**
	 * Adds JS Library. JS Library block is rendered on top of the JS files.
	 *
	 * @param string $name Arbitrary identifier
	 * @param string $file File name
	 * @param string $type Content Type
	 * @param boolean $compress Flag if library should be compressed
	 * @param boolean $forceOnTop Flag if added library should be inserted at begin of this block
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @return void
	 */
	public function addJsLibrary($name, $file, $type = 'text/javascript', $compress = FALSE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE) {
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
				'allWrap' => $allWrap,
				'excludeFromConcatenation' => $excludeFromConcatenation
			);
		}
	}

	/**
	 * Adds JS Library to Footer. JS Library block is rendered on top of the Footer JS files.
	 *
	 * @param string $name Arbitrary identifier
	 * @param string $file File name
	 * @param string $type Content Type
	 * @param boolean $compress Flag if library should be compressed
	 * @param boolean $forceOnTop Flag if added library should be inserted at begin of this block
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @return void
	 */
	public function addJsFooterLibrary($name, $file, $type = 'text/javascript', $compress = FALSE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE) {
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
				'allWrap' => $allWrap,
				'excludeFromConcatenation' => $excludeFromConcatenation
			);
		}
	}

	/**
	 * Adds JS file
	 *
	 * @param string $file File name
	 * @param string $type Content Type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @return void
	 */
	public function addJsFile($file, $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE) {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!isset($this->jsFiles[$file])) {
			if (strpos($file, 'ajax.php?') !== FALSE) {
				$compress = FALSE;
			}
			$this->jsFiles[$file] = array(
				'file' => $file,
				'type' => $type,
				'section' => self::PART_HEADER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap,
				'excludeFromConcatenation' => $excludeFromConcatenation
			);
		}
	}

	/**
	 * Adds JS file to footer
	 *
	 * @param string $file File name
	 * @param string $type Content Type
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @return void
	 */
	public function addJsFooterFile($file, $type = 'text/javascript', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE) {
		if (!$type) {
			$type = 'text/javascript';
		}
		if (!isset($this->jsFiles[$file])) {
			if (strpos($file, 'ajax.php?') !== FALSE) {
				$compress = FALSE;
			}
			$this->jsFiles[$file] = array(
				'file' => $file,
				'type' => $type,
				'section' => self::PART_FOOTER,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap,
				'excludeFromConcatenation' => $excludeFromConcatenation
			);
		}
	}

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
	 * @param string $block Javascript code
	 * @param boolean $forceOnTop Position of the javascript code (TRUE for putting it on top, default is FALSE = bottom)
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
	 * @param array $filterNamespaces Limit the output to defined namespaces. If empty, all namespaces are generated
	 * @return void
	 */
	public function addExtDirectCode(array $filterNamespaces = array()) {
		if ($this->extDirectCodeAdded) {
			return;
		}
		$this->extDirectCodeAdded = TRUE;
		if (count($filterNamespaces) === 0) {
			$filterNamespaces = array('TYPO3');
		}
		// For ExtDirect we need flash message support
		$this->addJsFile(\TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($this->backPath . '../t3lib/js/extjs/ux/flashmessages.js'));
		// Add language labels for ExtDirect
		if (TYPO3_MODE === 'FE') {
			$this->addInlineLanguageLabelArray(array(
				'extDirect_timeoutHeader' => $GLOBALS['TSFE']->sL('LLL:EXT:lang/locallang_misc.xlf:extDirect_timeoutHeader'),
				'extDirect_timeoutMessage' => $GLOBALS['TSFE']->sL('LLL:EXT:lang/locallang_misc.xlf:extDirect_timeoutMessage')
			));
		} else {
			$this->addInlineLanguageLabelArray(array(
				'extDirect_timeoutHeader' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:extDirect_timeoutHeader'),
				'extDirect_timeoutMessage' => $GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_misc.xlf:extDirect_timeoutMessage')
			));
		}
		$token = ($api = '');
		if (TYPO3_MODE === 'BE') {
			$formprotection = \TYPO3\CMS\Core\FormProtection\FormProtectionFactory::get();
			$token = $formprotection->generateToken('extDirect');
		}
		/** @var $extDirect \TYPO3\CMS\Core\ExtDirect\ExtDirectApi */
		$extDirect = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\ExtDirect\\ExtDirectApi');
		$api = $extDirect->getApiPhp($filterNamespaces);
		if ($api) {
			$this->addJsInlineCode('TYPO3ExtDirectAPI', $api, FALSE);
		}
		// Note: we need to iterate thru the object, because the addProvider method
		// does this only with multiple arguments
		$this->addExtOnReadyCode('
			(function() {
				TYPO3.ExtDirectToken = "' . $token . '";
				for (var api in Ext.app.ExtDirectAPI) {
					var provider = Ext.Direct.addProvider(Ext.app.ExtDirectAPI[api]);
					provider.on("beforecall", function(provider, transaction, meta) {
						if (transaction.data) {
							transaction.data[transaction.data.length] = TYPO3.ExtDirectToken;
						} else {
							transaction.data = [TYPO3.ExtDirectToken];
						}
					});

					provider.on("call", function(provider, transaction, meta) {
						if (transaction.isForm) {
							transaction.params.securityToken = TYPO3.ExtDirectToken;
						}
					});
				}
			})();

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
				if (event.code === Ext.Direct.exceptions.TRANSPORT && !event.where) {
					TYPO3.Flashmessage.display(
						TYPO3.Severity.error,
						TYPO3.l10n.localize("extDirect_timeoutHeader"),
						TYPO3.l10n.localize("extDirect_timeoutMessage"),
						30
					);
				} else {
					var backtrace = "";
					if (event.code === "parse") {
						extDirectDebug(
							"<p>" + event.xhr.responseText + "<\\/p>",
							event.type,
							"ExtDirect - Exception"
						);
					} else if (event.code === "router") {
						TYPO3.Flashmessage.display(
							TYPO3.Severity.error,
							event.code,
							event.message,
							30
						);
					} else if (event.where) {
						backtrace = "<p style=\\"margin-top: 20px;\\">" +
							"<strong>Backtrace:<\\/strong><br \\/>" +
							event.where.replace(/#/g, "<br \\/>#") +
							"<\\/p>";
						extDirectDebug(
							"<p>" + event.message + "<\\/p>" + backtrace,
							event.method,
							"ExtDirect - Exception"
						);
					}


				}
			});

			Ext.Direct.on("event", function(event, provider) {
				if (typeof event.debug !== "undefined" && event.debug !== "") {
					extDirectDebug(event.debug, event.method, "ExtDirect - Debug");
				}
			});
			', TRUE);
	}

	/**
	 * Adds CSS file
	 *
	 * @param string $file
	 * @param string $rel
	 * @param string $media
	 * @param string $title
	 * @param boolean $compress
	 * @param boolean $forceOnTop
	 * @param string $allWrap
	 * @param boolean $excludeFromConcatenation
	 * @return void
	 */
	public function addCssFile($file, $rel = 'stylesheet', $media = 'all', $title = '', $compress = TRUE, $forceOnTop = FALSE, $allWrap = '', $excludeFromConcatenation = FALSE) {
		if (!isset($this->cssFiles[$file])) {
			$this->cssFiles[$file] = array(
				'file' => $file,
				'rel' => $rel,
				'media' => $media,
				'title' => $title,
				'compress' => $compress,
				'forceOnTop' => $forceOnTop,
				'allWrap' => $allWrap,
				'excludeFromConcatenation' => $excludeFromConcatenation
			);
		}
	}

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

	/**
	 * Call this function if you need to include the jQuery library
	 *
	 * @param null|string $version The jQuery version that should be included, either "latest" or any available version
	 * @param null|string $source The location of the jQuery source, can be "local", "google", "msn", "jquery" or just an URL to your jQuery lib
	 * @param string $namespace The namespace in which the jQuery object of the specific version should be stored.
	 * @return void
	 * @throws \UnexpectedValueException
	 */
	public function loadJquery($version = NULL, $source = NULL, $namespace = self::JQUERY_NAMESPACE_DEFAULT) {
		// Set it to the version that is shipped with the TYPO3 core
		if ($version === NULL || $version === 'latest') {
			$version = self::JQUERY_VERSION_LATEST;
		}
		// Check if the source is set, otherwise set it to "default"
		if ($source === NULL) {
			$source = 'local';
		}
		if ($source === 'local' && !in_array($version, $this->availableLocalJqueryVersions)) {
			throw new \UnexpectedValueException('The requested jQuery version is not available in the local filesystem.', 1341505305);
		}
		if (!preg_match('/^[a-zA-Z0-9]+$/', $namespace)) {
			throw new \UnexpectedValueException('The requested namespace contains non alphanumeric characters.', 1341571604);
		}
		$this->jQueryVersions[$namespace] = array(
			'version' => $version,
			'source' => $source
		);
	}

	/**
	 * Call function if you need the requireJS library
	 * this automatically adds the JavaScript path of all loaded extensions in the requireJS path option
	 * so it resolves names like TYPO3/CMS/MyExtension/MyJsFile to EXT:MyExtension/Resources/Public/JavaScript/MyJsFile.js
	 * when using requireJS
	 *
	 * @return void
	 */
	public function loadRequireJs() {

			// load all paths to map to package names / namespaces
		if (count($this->requireJsConfig) === 0) {
				// first, load all paths for the namespaces
			$this->requireJsConfig['paths'] = array();
			// get all extensions that are loaded
			$loadedExtensions = \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::getLoadedExtensionListArray();
			foreach ($loadedExtensions as $packageName) {
				$fullJsPath = 'EXT:' . $packageName . '/Resources/Public/JavaScript/';
				$fullJsPath = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($fullJsPath);
				$fullJsPath = \TYPO3\CMS\Core\Utility\PathUtility::getRelativePath(PATH_typo3, $fullJsPath);
				$fullJsPath = rtrim($fullJsPath, '/');
				if ($fullJsPath) {
					$this->requireJsConfig['paths']['TYPO3/CMS/' . \TYPO3\CMS\Core\Utility\GeneralUtility::underscoredToUpperCamelCase($packageName)] = $this->backPath . $fullJsPath;
				}
			}

				// check if additional AMD modules need to be loaded if a single AMD module is initialized
			if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules'])) {
				$this->addInlineSettingArray('RequireJS.PostInitializationModules', $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['RequireJS']['postInitializationModules']);
			}
		}

		$this->addRequireJs = TRUE;
	}

	/**
	 * includes a AMD-compatible JS file by resolving the ModuleName, and then requires the file via a requireJS request
	 *
	 * this function only works for AMD-ready JS modules, used like "define('TYPO3/CMS/Backend/FormEngine..."
	 * in the JS file
	 *
	 *	TYPO3/CMS/Backend/FormEngine =>
	 * 		"TYPO3": Vendor Name
	 * 		"CMS": Product Name
	 *		"Backend": Extension Name
	 *		"FormEngine": FileName in the Resources/Public/JavaScript folder
	 *
	 * @param $mainModuleName must be in the form of "TYPO3/CMS/PackageName/ModuleName" e.g. "TYPO3/CMS/Backend/FormEngine"
	 * @return void
	 */
	public function loadRequireJsModule($mainModuleName) {

		// make sure requireJS is initialized
		$this->loadRequireJs();

		// execute the main module
		$this->addJsInlineCode('RequireJS-Module-' . $mainModuleName, 'require(["' . $mainModuleName . '"]);');
	}

	/**
	 * Call function if you need the prototype library
	 *
	 * @return void
	 */
	public function loadPrototype() {
		$this->addPrototype = TRUE;
	}

	/**
	 * Call function if you need the Scriptaculous library
	 *
	 * @param string $modules Add modules you need. use "all" if you need complete modules
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
				$mods = \TYPO3\CMS\Core\Utility\GeneralUtility::trimExplode(',', $modules);
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
	 * @param boolean $css Flag, if set the ext-css will be loaded
	 * @param boolean $theme Flag, if set the ext-theme "grey" will be loaded
	 * @param string $adapter Choose alternative adapter, possible values: yui, prototype, jquery
	 * @return void
	 */
	public function loadExtJS($css = TRUE, $theme = TRUE, $adapter = '') {
		if ($adapter) {
			// Empty $adapter will always load the ext adapter
			switch (\TYPO3\CMS\Core\Utility\GeneralUtility::strtolower(trim($adapter))) {
			case self::EXTJS_ADAPTER_YUI:
				$this->extJSadapter = 'yui/ext-yui-adapter.js';
				break;
			case self::EXTJS_ADAPTER_PROTOTYPE:
				$this->extJSadapter = 'prototype/ext-prototype-adapter.js';
				break;
			case self::EXTJS_ADAPTER_JQUERY:
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
	 */
	public function enableExtJSQuickTips() {
		$this->enableExtJSQuickTips = TRUE;
	}

	/**
	 * Call function if you need the ExtCore library
	 *
	 * @return void
	 */
	public function loadExtCore() {
		$this->addExtCore = TRUE;
	}

	/**
	 * Call function if you need the SVG library
	 *
	 * @return void
	 */
	public function loadSvg() {
		$this->addSvg = TRUE;
	}

	/**
	 * Call this function to load debug version of ExtJS. Use this for development only
	 *
	 * @return void
	 */
	public function enableSvgDebug() {
		$this->enableSvgDebug = TRUE;
	}

	/**
	 * Call this function to force flash usage with SVG library
	 *
	 * @return void
	 */
	public function svgForceFlash() {
		$this->addMetaTag('<meta name="svg.render.forceflash" content="true" />');
	}

	/**
	 * Call this function to load debug version of ExtJS. Use this for development only
	 *
	 * @return void
	 */
	public function enableExtJsDebug() {
		$this->enableExtJsDebug = TRUE;
	}

	/**
	 * Call this function to load debug version of ExtCore. Use this for development only
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
	 * @param string $fileRef Input is a file-reference (see \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName). That file is expected to be a 'locallang.xml' file containing a valid XML TYPO3 language structure.
	 * @param string $selectionPrefix Prefix to select the correct labels (default: '')
	 * @param string $stripFromSelectionName Sub-prefix to be removed from label names in the result (default: '')
	 * @param integer $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
	 * @return void
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
	/*****************************************************/
	/**
	 * Render the section (Header or Footer)
	 *
	 * @param integer $part Section which should be rendered: self::PART_COMPLETE, self::PART_HEADER or self::PART_FOOTER
	 * @return string Content of rendered section
	 */
	public function render($part = self::PART_COMPLETE) {
		$this->prepareRendering();
		list($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs) = $this->renderJavaScriptAndCss();
		$metaTags = implode(LF, $this->metaTags);
		$markerArray = $this->getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags);
		$template = $this->getTemplateForPart($part);
		$this->reset();
		return trim(\TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($template, $markerArray, '###|###'));
	}

	/**
	 * Render the page but not the JavaScript and CSS Files
	 *
	 * @param string $substituteHash The hash that is used for the placehoder markers
	 * @access private
	 * @return string Content of rendered section
	 */
	public function renderPageWithUncachedObjects($substituteHash) {
		$this->prepareRendering();
		$markerArray = $this->getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash);
		$template = $this->getTemplateForPart(self::PART_COMPLETE);
		return trim(\TYPO3\CMS\Core\Html\HtmlParser::substituteMarkerArray($template, $markerArray, '###|###'));
	}

	/**
	 * Renders the JavaScript and CSS files that have been added during processing
	 * of uncached content objects (USER_INT, COA_INT)
	 *
	 * @param string $cachedPageContent
	 * @param string $substituteHash The hash that is used for the placehoder markers
	 * @access private
	 * @return string
	 */
	public function renderJavaScriptAndCssForProcessingOfUncachedContentObjects($cachedPageContent, $substituteHash) {
		$this->prepareRendering();
		list($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs) = $this->renderJavaScriptAndCss();
		$markerArray = array(
			'<!-- ###CSS_INCLUDE' . $substituteHash . '### -->' => $cssFiles,
			'<!-- ###CSS_INLINE' . $substituteHash . '### -->' => $cssInline,
			'<!-- ###JS_INLINE' . $substituteHash . '### -->' => $jsInline,
			'<!-- ###JS_INCLUDE' . $substituteHash . '### -->' => $jsFiles,
			'<!-- ###JS_LIBS' . $substituteHash . '### -->' => $jsLibs,
			'<!-- ###HEADERDATA' . $substituteHash . '### -->' => implode(LF, $this->headerData),
			'<!-- ###FOOTERDATA' . $substituteHash . '### -->' => implode(LF, $this->footerData),
			'<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->' => $jsFooterLibs,
			'<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->' => $jsFooterFiles,
			'<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->' => $jsFooterInline
		);
		foreach ($markerArray as $placeHolder => $content) {
			$cachedPageContent = str_replace($placeHolder, $content, $cachedPageContent);
		}
		$this->reset();
		return $cachedPageContent;
	}

	/**
	 * Remove ending slashes from static header block
	 * if the page is beeing rendered as html (not xhtml)
	 * and define property $this->endingSlash for further use
	 *
	 * @return void
	 */
	protected function prepareRendering() {
		if ($this->getRenderXhtml()) {
			$this->endingSlash = ' /';
		} else {
			$this->metaCharsetTag = str_replace(' />', '>', $this->metaCharsetTag);
			$this->baseUrlTag = str_replace(' />', '>', $this->baseUrlTag);
			$this->shortcutTag = str_replace(' />', '>', $this->shortcutTag);
			$this->endingSlash = '';
		}
	}

	/**
	 * Renders all JavaScript and CSS
	 *
	 * @return array<string>
	 */
	protected function renderJavaScriptAndCss() {
		$this->executePreRenderHook();
		$mainJsLibs = $this->renderMainJavaScriptLibraries();
		if ($this->concatenateFiles || $this->concatenateJavascript || $this->concatenateCss) {
			// Do the file concatenation
			$this->doConcatenate();
		}
		if ($this->compressCss || $this->compressJavascript) {
			// Do the file compression
			$this->doCompress();
		}
		$this->executeRenderPostTransformHook();
		$cssFiles = $this->renderCssFiles();
		$cssInline = $this->renderCssInline();
		list($jsLibs, $jsFooterLibs) = $this->renderAdditionalJavaScriptLibraries();
		list($jsFiles, $jsFooterFiles) = $this->renderJavaScriptFiles();
		list($jsInline, $jsFooterInline) = $this->renderInlineJavaScript();
		$jsLibs = $mainJsLibs . $jsLibs;
		if ($this->moveJsFromHeaderToFooter) {
			$jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
			$jsLibs = '';
			$jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
			$jsFiles = '';
			$jsFooterInline = $jsInline . LF . $jsFooterInline;
			$jsInline = '';
		}
		$this->executePostRenderHook($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs);
		return array($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs);
	}

	/**
	 * Fills the marker array with the given strings and trims each value
	 *
	 * @param $jsLibs string
	 * @param $jsFiles string
	 * @param $jsFooterFiles string
	 * @param $cssFiles string
	 * @param $jsInline string
	 * @param $cssInline string
	 * @param $jsFooterInline string
	 * @param $jsFooterLibs string
	 * @param $metaTags string
	 * @return array Marker array
	 */
	protected function getPreparedMarkerArray($jsLibs, $jsFiles, $jsFooterFiles, $cssFiles, $jsInline, $cssInline, $jsFooterInline, $jsFooterLibs, $metaTags) {
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
			'BODY' => $this->bodyContent
		);
		$markerArray = array_map('trim', $markerArray);
		return $markerArray;
	}

	/**
	 * Fills the marker array with the given strings and trims each value
	 *
	 * @param string $substituteHash The hash that is used for the placehoder markers
	 * @return array Marker array
	 */
	protected function getPreparedMarkerArrayForPageWithUncachedObjects($substituteHash) {
		$markerArray = array(
			'XMLPROLOG_DOCTYPE' => $this->xmlPrologAndDocType,
			'HTMLTAG' => $this->htmlTag,
			'HEADTAG' => $this->headTag,
			'METACHARSET' => $this->charSet ? str_replace('|', htmlspecialchars($this->charSet), $this->metaCharsetTag) : '',
			'INLINECOMMENT' => $this->inlineComments ? LF . LF . '<!-- ' . LF . implode(LF, $this->inlineComments) . '-->' . LF . LF : '',
			'BASEURL' => $this->baseUrl ? str_replace('|', $this->baseUrl, $this->baseUrlTag) : '',
			'SHORTCUT' => $this->favIcon ? sprintf($this->shortcutTag, htmlspecialchars($this->favIcon), $this->iconMimeType) : '',
			'TITLE' => $this->title ? str_replace('|', htmlspecialchars($this->title), $this->titleTag) : '',
			'META' => implode(LF, $this->metaTags),
			'BODY' => $this->bodyContent,
			'CSS_INCLUDE' => '<!-- ###CSS_INCLUDE' . $substituteHash . '### -->',
			'CSS_INLINE' => '<!-- ###CSS_INLINE' . $substituteHash . '### -->',
			'JS_INLINE' => '<!-- ###JS_INLINE' . $substituteHash . '### -->',
			'JS_INCLUDE' => '<!-- ###JS_INCLUDE' . $substituteHash . '### -->',
			'JS_LIBS' => '<!-- ###JS_LIBS' . $substituteHash . '### -->',
			'HEADERDATA' => '<!-- ###HEADERDATA' . $substituteHash . '### -->',
			'FOOTERDATA' => '<!-- ###FOOTERDATA' . $substituteHash . '### -->',
			'JS_LIBS_FOOTER' => '<!-- ###JS_LIBS_FOOTER' . $substituteHash . '### -->',
			'JS_INCLUDE_FOOTER' => '<!-- ###JS_INCLUDE_FOOTER' . $substituteHash . '### -->',
			'JS_INLINE_FOOTER' => '<!-- ###JS_INLINE_FOOTER' . $substituteHash . '### -->'
		);
		$markerArray = array_map('trim', $markerArray);
		return $markerArray;
	}

	/**
	 * Reads the template file and returns the requested part as string
	 *
	 * @param integer $part
	 * @return string
	 */
	protected function getTemplateForPart($part) {
		$templateFile = \TYPO3\CMS\Core\Utility\GeneralUtility::getFileAbsFileName($this->templateFile, TRUE);
		$template = \TYPO3\CMS\Core\Utility\GeneralUtility::getUrl($templateFile);
		if ($this->removeLineBreaksFromTemplate) {
			$template = strtr($template, array(LF => '', CR => ''));
		}
		if ($part != self::PART_COMPLETE) {
			$templatePart = explode('###BODY###', $template);
			$template = $templatePart[$part - 1];
		}
		return $template;
	}

	/**
	 * Helper function for render the main JavaScript libraries,
	 * currently: RequireJS, jQuery, PrototypeJS, Scriptaculous, SVG, ExtJs
	 *
	 * @return string Content with JavaScript libraries
	 */
	protected function renderMainJavaScriptLibraries() {
		$out = '';

		// Include RequireJS
		if ($this->addRequireJs) {
				// load the paths of the requireJS configuration
			$out .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS('var require = ' . json_encode($this->requireJsConfig)) . LF;
				// directly after that, include the require.js file
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->requireJsPath . 'require.js')) . '" type="text/javascript"></script>' . LF;
		}

		if ($this->addSvg) {
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->svgPath . 'svg.js')) . '" data-path="' . $this->backPath . $this->svgPath . '"' . ($this->enableSvgDebug ? ' data-debug="true"' : '') . '></script>' . LF;
		}
		// Include jQuery Core for each namespace, depending on the version and source
		if (!empty($this->jQueryVersions)) {
			foreach ($this->jQueryVersions as $namespace => $jQueryVersion) {
				$out .= $this->renderJqueryScriptTag($jQueryVersion['version'], $jQueryVersion['source'], $namespace);
			}
		}
		if ($this->addPrototype) {
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->prototypePath . 'prototype.js')) . '" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->prototypePath . 'prototype.js']);
		}
		if ($this->addScriptaculous) {
			$mods = array();
			foreach ($this->addScriptaculousModules as $key => $value) {
				if ($this->addScriptaculousModules[$key]) {
					$mods[] = $key;
				}
			}
			// Resolve dependencies
			if (in_array('dragdrop', $mods) || in_array('controls', $mods)) {
				$mods = array_merge(array('effects'), $mods);
			}
			if (count($mods)) {
				foreach ($mods as $module) {
					$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->scriptaculousPath . $module . '.js')) . '" type="text/javascript"></script>' . LF;
					unset($this->jsFiles[$this->backPath . $this->scriptaculousPath . $module . '.js']);
				}
			}
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->scriptaculousPath . 'scriptaculous.js')) . '" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->scriptaculousPath . 'scriptaculous.js']);
		}
		// Include extCore, but only if ExtJS is not included
		if ($this->addExtCore && !$this->addExtJS) {
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->extCorePath . 'ext-core' . ($this->enableExtCoreDebug ? '-debug' : '') . '.js')) . '" type="text/javascript"></script>' . LF;
			unset($this->jsFiles[$this->backPath . $this->extCorePath . 'ext-core' . ($this->enableExtCoreDebug ? '-debug' : '') . '.js']);
		}
		// Include extJS
		if ($this->addExtJS) {
			// Use the base adapter all the time
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->extJsPath . 'adapter/' . ($this->enableExtJsDebug ? str_replace('.js', '-debug.js', $this->extJSadapter) : $this->extJSadapter))) . '" type="text/javascript"></script>' . LF;
			$out .= '<script src="' . $this->processJsFile(($this->backPath . $this->extJsPath . 'ext-all' . ($this->enableExtJsDebug ? '-debug' : '') . '.js')) . '" type="text/javascript"></script>' . LF;
			// Add extJS localization
			// Load standard ISO mapping and modify for use with ExtJS
			$localeMap = $this->locales->getIsoMapping();
			$localeMap[''] = 'en';
			$localeMap['default'] = 'en';
			// Greek
			$localeMap['gr'] = 'el_GR';
			// Norwegian Bokmaal
			$localeMap['no'] = 'no_BO';
			// Swedish
			$localeMap['se'] = 'se_SV';
			$extJsLang = isset($localeMap[$this->lang]) ? $localeMap[$this->lang] : $this->lang;
			// TODO autoconvert file from UTF8 to current BE charset if necessary!!!!
			$extJsLocaleFile = $this->extJsPath . 'locale/ext-lang-' . $extJsLang . '.js';
			if (file_exists(PATH_typo3 . $extJsLocaleFile)) {
				$out .= '<script src="' . $this->processJsFile(($this->backPath . $extJsLocaleFile)) . '" type="text/javascript" charset="utf-8"></script>' . LF;
			}
			// Remove extjs from JScodeLibArray
			unset($this->jsFiles[$this->backPath . $this->extJsPath . 'ext-all.js'], $this->jsFiles[$this->backPath . $this->extJsPath . 'ext-all-debug.js']);
		}
		if (count($this->inlineLanguageLabelFiles)) {
			foreach ($this->inlineLanguageLabelFiles as $languageLabelFile) {
				$this->includeLanguageFileForInline($languageLabelFile['fileRef'], $languageLabelFile['selectionPrefix'], $languageLabelFile['stripFromSelectionName'], $languageLabelFile['$errorMode']);
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
			// Set clear.gif, move it on top, add handler code
			$code = '';
			if (count($this->extOnReadyCode)) {
				foreach ($this->extOnReadyCode as $block) {
					$code .= $block;
				}
			}
			$out .= $this->inlineJavascriptWrap[0] . '
				Ext.ns("TYPO3");
				Ext.BLANK_IMAGE_URL = "' . htmlspecialchars(\TYPO3\CMS\Core\Utility\GeneralUtility::locationHeaderUrl(($this->backPath . 'gfx/clear.gif'))) . '";' . LF . $inlineSettings . 'Ext.onReady(function() {' . ($this->enableExtJSQuickTips ? 'Ext.QuickTips.init();' . LF : '') . $code . ' });' . $this->inlineJavascriptWrap[1];
			unset($this->extOnReadyCode);
			// Include TYPO3.l10n object
			if (TYPO3_MODE === 'BE') {
				$out .= '<script src="' . $this->processJsFile(($this->backPath . 'sysext/lang/res/js/be/typo3lang.js')) . '" type="text/javascript" charset="utf-8"></script>' . LF;
			}
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
			// no extJS loaded, but still inline settings
			if ($inlineSettings !== '') {
				// make sure the global TYPO3 is available
				$inlineSettings = 'var TYPO3 = TYPO3 || {};' . CRLF . $inlineSettings;
				$out .= $this->inlineJavascriptWrap[0] . $inlineSettings . $this->inlineJavascriptWrap[1];
			}
		}
		return $out;
	}

	/**
	 * Renders the HTML script tag for the given jQuery version.
	 *
	 * @param string $version The jQuery version that should be included, either "latest" or any available version
	 * @param string $source The location of the jQuery source, can be "local", "google", "msn" or "jquery
	 * @param string $namespace The namespace in which the jQuery object of the specific version should be stored
	 * @return string
	 */
	protected function renderJqueryScriptTag($version, $source, $namespace) {
		switch (TRUE) {
		case isset($this->jQueryCdnUrls[$source]):
			if ($this->enableJqueryDebug) {
				$minifyPart = '';
			} else {
				$minifyPart = '.min';
			}
			$jQueryFileName = sprintf($this->jQueryCdnUrls[$source], $version, $minifyPart);
			break;
		case $source === 'local':
			$jQueryFileName = $this->backPath . $this->jQueryPath . 'jquery-' . rawurlencode($version);
			if ($this->enableJqueryDebug) {
				$jQueryFileName .= '.js';
			} else {
				$jQueryFileName .= '.min.js';
			}
			break;
		default:
			$jQueryFileName = $source;
		}
		// Include the jQuery Core
		$scriptTag = '<script src="' . htmlspecialchars($jQueryFileName) . '" type="text/javascript"></script>' . LF;
		// Set the noConflict mode to be available via "TYPO3.jQuery" in all installations
		switch ($namespace) {
		case self::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT:
			$scriptTag .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS('jQuery.noConflict();') . LF;
			break;
		case self::JQUERY_NAMESPACE_NONE:
			break;
		case self::JQUERY_NAMESPACE_DEFAULT:

		default:
			$scriptTag .= \TYPO3\CMS\Core\Utility\GeneralUtility::wrapJS('var TYPO3 = TYPO3 || {}; TYPO3.' . $namespace . ' = jQuery.noConflict(true);') . LF;
			break;
		}
		return $scriptTag;
	}

	/**
	 * Render CSS files
	 *
	 * @return string
	 */
	protected function renderCssFiles() {
		$cssFiles = '';
		if (count($this->cssFiles)) {
			foreach ($this->cssFiles as $file => $properties) {
				$file = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($file);
				$file = \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename($file);
				$tag = '<link rel="' . htmlspecialchars($properties['rel']) . '" type="text/css" href="' . htmlspecialchars($file) . '" media="' . htmlspecialchars($properties['media']) . '"' . ($properties['title'] ? ' title="' . htmlspecialchars($properties['title']) . '"' : '') . $this->endingSlash . '>';
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
		return $cssFiles;
	}

	/**
	 * Render inline CSS
	 *
	 * @return string
	 */
	protected function renderCssInline() {
		$cssInline = '';
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
		return $cssInline;
	}

	/**
	 * Render JavaScipt libraries
	 *
	 * @return array<string> jsLibs and jsFooterLibs strings
	 */
	protected function renderAdditionalJavaScriptLibraries() {
		$jsLibs = '';
		$jsFooterLibs = '';
		if (count($this->jsLibs)) {
			foreach ($this->jsLibs as $properties) {
				$properties['file'] = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($properties['file']);
				$properties['file'] = \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename($properties['file']);
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
		if ($this->moveJsFromHeaderToFooter) {
			$jsFooterLibs = $jsLibs . LF . $jsFooterLibs;
			$jsLibs = '';
		}
		return array($jsLibs, $jsFooterLibs);
	}

	/**
	 * Render JavaScript files
	 *
	 * @return array<string> jsFiles and jsFooterFiles strings
	 */
	protected function renderJavaScriptFiles() {
		$jsFiles = '';
		$jsFooterFiles = '';
		if (count($this->jsFiles)) {
			foreach ($this->jsFiles as $file => $properties) {
				$file = \TYPO3\CMS\Core\Utility\GeneralUtility::resolveBackPath($file);
				$file = \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename($file);
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
		if ($this->moveJsFromHeaderToFooter) {
			$jsFooterFiles = $jsFiles . LF . $jsFooterFiles;
			$jsFiles = '';
		}
		return array($jsFiles, $jsFooterFiles);
	}

	/**
	 * Render inline JavaScript
	 *
	 * @return array<string> jsInline and jsFooterInline string
	 */
	protected function renderInlineJavaScript() {
		$jsInline = '';
		$jsFooterInline = '';
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
		if ($this->moveJsFromHeaderToFooter) {
			$jsFooterInline = $jsInline . LF . $jsFooterInline;
			$jsInline = '';
		}
		return array($jsInline, $jsFooterInline);
	}

	/**
	 * Include language file for inline usage
	 *
	 * @param string $fileRef
	 * @param string $selectionPrefix
	 * @param string $stripFromSelectionName
	 * @param integer $errorMode
	 * @return void
	 * @throws \RuntimeException
	 */
	protected function includeLanguageFileForInline($fileRef, $selectionPrefix = '', $stripFromSelectionName = '', $errorMode = 0) {
		if (!isset($this->lang) || !isset($this->charSet)) {
			throw new \RuntimeException('Language and character encoding are not set.', 1284906026);
		}
		$labelsFromFile = array();
		$allLabels = $this->readLLfile($fileRef, $errorMode);
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

	/**
	 * Reads a locallang file.
	 *
	 * @param string $fileRef Reference to a relative filename to include.
	 * @param integer $errorMode Error mode (when file could not be found): 0 - syslog entry, 1 - do nothing, 2 - throw an exception
	 * @return array Returns the $LOCAL_LANG array found in the file. If no array found, returns empty array.
	 */
	protected function readLLfile($fileRef, $errorMode = 0) {
		if ($this->lang !== 'default') {
			$languages = array_reverse($this->languageDependencies);
			// At least we need to have English
			if (empty($languages)) {
				$languages[] = 'default';
			}
		} else {
			$languages = array('default');
		}

		$localLanguage = array();
		foreach ($languages as $language) {
			$tempLL = \TYPO3\CMS\Core\Utility\GeneralUtility::readLLfile($fileRef, $language, $this->charSet, $errorMode);
			$localLanguage['default'] = $tempLL['default'];
			if (!isset($localLanguage[$this->lang])) {
				$localLanguage[$this->lang] = $localLanguage['default'];
			}
			if ($this->lang !== 'default' && isset($tempLL[$language])) {
				// Merge current language labels onto labels from previous language
				// This way we have a labels with fall back applied
				$localLanguage[$this->lang] = \TYPO3\CMS\Core\Utility\GeneralUtility::array_merge_recursive_overrule($localLanguage[$this->lang], $tempLL[$language], FALSE, FALSE);
			}
		}

		return $localLanguage;
	}


	/*****************************************************/
	/*                                                   */
	/*  Tools                                            */
	/*                                                   */
	/*****************************************************/
	/**
	 * Concatenate files into one file
	 * registered handler
	 *
	 * @return void
	 */
	protected function doConcatenate() {
		$this->doConcatenateCss();
		$this->doConcatenateJavaScript();
	}

	/**
	 * Concatenate JavaScript files according to the configuration.
	 *
	 * @return void
	 */
	protected function doConcatenateJavaScript() {
		if ($this->concatenateFiles || $this->concatenateJavascript) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler'])) {
				// use external concatenation routine
				$params = array(
					'jsLibs' => &$this->jsLibs,
					'jsFiles' => &$this->jsFiles,
					'jsFooterFiles' => &$this->jsFooterFiles,
					'headerData' => &$this->headerData,
					'footerData' => &$this->footerData
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsConcatenateHandler'], $params, $this);
			} else {
				$this->jsLibs = $this->getCompressor()->concatenateJsFiles($this->jsLibs);
				$this->jsFiles = $this->getCompressor()->concatenateJsFiles($this->jsFiles);
				$this->jsFooterFiles = $this->getCompressor()->concatenateJsFiles($this->jsFooterFiles);
			}
		}
	}

	/**
	 * Concatenate CSS files according to configuration.
	 *
	 * @return void
	 */
	protected function doConcatenateCss() {
		if ($this->concatenateFiles || $this->concatenateCss) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler'])) {
				// use external concatenation routine
				$params = array(
					'cssFiles' => &$this->cssFiles,
					'headerData' => &$this->headerData,
					'footerData' => &$this->footerData
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssConcatenateHandler'], $params, $this);
			} else {
				$cssOptions = array();
				if (TYPO3_MODE === 'BE') {
					$cssOptions = array('baseDirectories' => $GLOBALS['TBE_TEMPLATE']->getSkinStylesheetDirectories());
				}
				$this->cssFiles = $this->getCompressor()->concatenateCssFiles($this->cssFiles, $cssOptions);
			}
		}
	}

	/**
	 * Compresses inline code
	 *
	 * @return void
	 */
	protected function doCompress() {
		$this->doCompressJavaScript();
		$this->doCompressCss();
	}

	/**
	 * Compresses CSS according to configuration.
	 *
	 * @return void
	 */
	protected function doCompressCss() {
		if ($this->compressCss) {
			// Use external compression routine
			$params = array(
				'cssInline' => &$this->cssInline,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData
			);
			if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler'])) {
				// use external concatenation routine
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['cssCompressHandler'], $params, $this);
			} else {
				$this->cssFiles = $this->getCompressor()->compressCssFiles($this->cssFiles);
			}
		}
	}

	/**
	 * Compresses JavaScript according to configuration.
	 *
	 * @return void
	 */
	protected function doCompressJavaScript() {
		if ($this->compressJavascript) {
			if (!empty($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'])) {
				// Use external compression routine
				$params = array(
					'jsInline' => &$this->jsInline,
					'jsFooterInline' => &$this->jsFooterInline,
					'jsLibs' => &$this->jsLibs,
					'jsFiles' => &$this->jsFiles,
					'jsFooterFiles' => &$this->jsFooterFiles,
					'headerData' => &$this->headerData,
					'footerData' => &$this->footerData
				);
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['jsCompressHandler'], $params, $this);
			} else {
				// Traverse the arrays, compress files
				if (count($this->jsInline)) {
					foreach ($this->jsInline as $name => $properties) {
						if ($properties['compress']) {
							$error = '';
							$this->jsInline[$name]['code'] = \TYPO3\CMS\Core\Utility\GeneralUtility::minifyJavaScript($properties['code'], $error);
							if ($error) {
								$this->compressError .= 'Error with minify JS Inline Block "' . $name . '": ' . $error . LF;
							}
						}
					}
				}
				$this->jsLibs = $this->getCompressor()->compressJsFiles($this->jsLibs);
				$this->jsFiles = $this->getCompressor()->compressJsFiles($this->jsFiles);
				$this->jsFooterFiles = $this->getCompressor()->compressJsFiles($this->jsFooterFiles);
			}
		}
	}

	/**
	 * Returns instance of \TYPO3\CMS\Core\Resource\ResourceCompressor
	 *
	 * @return \TYPO3\CMS\Core\Resource\ResourceCompressor
	 */
	protected function getCompressor() {
		if ($this->compressor === NULL) {
			$this->compressor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceCompressor');
		}
		return $this->compressor;
	}

	/**
	 * Processes a Javascript file dependent on the current context
	 *
	 * Adds the version number for Frontend, compresses the file for Backend
	 *
	 * @param string $filename Filename
	 * @return string New filename
	 */
	protected function processJsFile($filename) {
		switch (TYPO3_MODE) {
		case 'FE':
			if ($this->compressJavascript) {
				$filename = $this->getCompressor()->compressJsFile($filename);
			} else {
				$filename = \TYPO3\CMS\Core\Utility\GeneralUtility::createVersionNumberedFilename($filename);
			}
			break;
		case 'BE':
			if ($this->compressJavascript) {
				$filename = $this->getCompressor()->compressJsFile($filename);
			}
			break;
		}
		return $filename;
	}

	/*****************************************************/
	/*                                                   */
	/*  Hooks                                            */
	/*                                                   */
	/*****************************************************/
	/**
	 * Execute PreRenderHook for possible manuipulation
	 *
	 * @return void
	 */
	protected function executePreRenderHook() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'])) {
			$params = array(
				'jsLibs' => &$this->jsLibs,
				'jsFooterLibs' => &$this->jsFooterLibs,
				'jsFiles' => &$this->jsFiles,
				'jsFooterFiles' => &$this->jsFooterFiles,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
				'jsInline' => &$this->jsInline,
				'jsFooterInline' => &$this->jsFooterInline,
				'cssInline' => &$this->cssInline
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-preProcess'] as $hook) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
	}

	/**
	 * PostTransform for possible manuipulation of concatenated and compressed files
	 *
	 * @return void
	 */
	protected function executeRenderPostTransformHook() {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'])) {
			$params = array(
				'jsLibs' => &$this->jsLibs,
				'jsFooterLibs' => &$this->jsFooterLibs,
				'jsFiles' => &$this->jsFiles,
				'jsFooterFiles' => &$this->jsFooterFiles,
				'cssFiles' => &$this->cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
				'jsInline' => &$this->jsInline,
				'jsFooterInline' => &$this->jsFooterInline,
				'cssInline' => &$this->cssInline
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postTransform'] as $hook) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
	}

	/**
	 * Execute postRenderHook for possible manipulation
	 *
	 * @param $jsLibs string
	 * @param $jsFiles string
	 * @param $jsFooterFiles string
	 * @param $cssFiles string
	 * @param $jsInline string
	 * @param $cssInline string
	 * @param $jsFooterInline string
	 * @param $jsFooterLibs string
	 * @return void
	 */
	protected function executePostRenderHook(&$jsLibs, &$jsFiles, &$jsFooterFiles, &$cssFiles, &$jsInline, &$cssInline, &$jsFooterInline, &$jsFooterLibs) {
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'])) {
			$params = array(
				'jsLibs' => &$jsLibs,
				'jsFiles' => &$jsFiles,
				'jsFooterFiles' => &$jsFooterFiles,
				'cssFiles' => &$cssFiles,
				'headerData' => &$this->headerData,
				'footerData' => &$this->footerData,
				'jsInline' => &$jsInline,
				'cssInline' => &$cssInline,
				'xmlPrologAndDocType' => &$this->xmlPrologAndDocType,
				'htmlTag' => &$this->htmlTag,
				'headTag' => &$this->headTag,
				'charSet' => &$this->charSet,
				'metaCharsetTag' => &$this->metaCharsetTag,
				'shortcutTag' => &$this->shortcutTag,
				'inlineComments' => &$this->inlineComments,
				'baseUrl' => &$this->baseUrl,
				'baseUrlTag' => &$this->baseUrlTag,
				'favIcon' => &$this->favIcon,
				'iconMimeType' => &$this->iconMimeType,
				'titleTag' => &$this->titleTag,
				'title' => &$this->title,
				'metaTags' => &$this->metaTags,
				'jsFooterInline' => &$jsFooterInline,
				'jsFooterLibs' => &$jsFooterLibs,
				'bodyContent' => &$this->bodyContent
			);
			foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_pagerenderer.php']['render-postProcess'] as $hook) {
				\TYPO3\CMS\Core\Utility\GeneralUtility::callUserFunction($hook, $params, $this);
			}
		}
	}

}


?>
