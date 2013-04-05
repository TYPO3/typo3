<?php
namespace TYPO3\CMS\Core\Tests\Unit\Page;

/***************************************************************
 * Copyright notice
 *
 * (c) 2009-2013 Steffen Kamper (info@sk-typo3.de)
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Testcase for \TYPO3\CMS\Core\Page\PageRenderer
 *
 * @author Steffen Kamper (info@sk-typo3.de)
 */
class PageRendererTest extends \TYPO3\CMS\Core\Tests\UnitTestCase {

	/**
	 * @var \TYPO3\CMS\Core\Page\PageRenderer
	 */
	private $fixture;

	public function setUp() {
		$this->fixture = new \TYPO3\CMS\Core\Page\PageRenderer();
		$this->fixture->setCharSet($GLOBALS['LANG']->charSet);
	}

	public function tearDown() {
		unset($this->fixture);
	}

	//////////////////////////////////////
	// Tests for the basic functionality
	//////////////////////////////////////
	/**
	 * @test
	 */
	public function fixtureCanBeCreated() {
		$this->assertTrue($this->fixture instanceof \TYPO3\CMS\Core\Page\PageRenderer);
	}

	//////////////////////
	// test functions
	//////////////////////
	/**
	 * test set xml prolog and doctype
	 */
	public function testSetXmlPrologAndDocType() {
		$expectedReturnValue = '<?xml version="1.0" encoding="utf-8" ?>';
		$this->fixture->setXmlPrologAndDocType('<?xml version="1.0" encoding="utf-8" ?>');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test set title
	 */
	public function testSetTitle() {
		$expectedReturnValue = '<title>This is the title</title>';
		$this->fixture->setTitle('This is the title');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test set charset
	 */
	public function testSetCharset() {
		$expectedReturnValue = '<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />';
		$this->fixture->setCharset('utf-8');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test set favicon
	 */
	public function testSetFavIcon() {
		$expectedReturnValue1 = '<link rel="shortcut icon" href="http://google.com/favicon.ico" />';
		$expectedReturnValue2 = '<link rel="icon" href="http://google.com/favicon.ico" />';
		$this->fixture->setFavIcon('http://google.com/favicon.ico');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue1, $out);
		$this->assertContains($expectedReturnValue2, $out);
	}

	/**
	 * test set baseUrl
	 */
	public function testSetBaseUrl() {
		$expectedReturnValue = '<base href="http://ggogle.com/" />';
		$this->fixture->setBaseUrl('http://ggogle.com/');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add meta tag
	 */
	public function testAddMetaTag() {
		$expectedReturnValue = '<meta name="author" content="Anna Lyse">';
		$this->fixture->addMetaTag('<meta name="author" content="Anna Lyse">');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add inline comment
	 */
	public function testAddInlineComment() {
		$expectedReturnValue = 'this is an inline comment written by unit test';
		$this->fixture->addInlineComment('this is an inline comment written by unit test');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add header data
	 */
	public function testAddHeaderData() {
		$expectedReturnValue = '<tag method="private" name="test" />';
		$this->fixture->addHeaderData('<tag method="private" name="test" />');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add footer data
	 */
	public function testAddFooterData() {
		$expectedReturnValue = '<tag method="private" name="test" />';
		$this->fixture->addFooterData('<tag method="private" name="test" />');
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add JS library file
	 */
	public function testAddJsLibrary() {
		$expectedRegExp = '#<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->addJsLibrary('test', 'fileadmin/test.js');
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test add JS footer library file
	 */
	public function testAddJsFooterLibrary() {
		$expectedRegExp = '#<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->addJsFooterLibrary('test', 'fileadmin/test.js');
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test add JS file
	 */
	public function testAddJsFile() {
		$expectedRegExp = '#<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->addJsFile('fileadmin/test.js');
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test add JS file for footer
	 */
	public function testAddJsFooterFile() {
		$expectedRegExp = '#<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->addJsFooterFile('fileadmin/test.js');
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test add JS inline
	 */
	public function testAddJsInlineCode() {
		$expectedReturnValue = 'var x = "testvar"';
		$this->fixture->addJsInlineCode('test', 'var x = "testvar"');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add JS inline for footer
	 */
	public function testAddJsFooterInlineCode() {
		$expectedReturnValue = 'var x = "testvar"';
		$this->fixture->addJsFooterInlineCode('test', 'var x = "testvar"');
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add JS handler
	 */
	public function testAddExtOnReadyCode() {
		$expectedReturnValue1 = 'Ext.onReady(function() {';
		$expectedReturnValue2 = 'var x = "testvar";';
		$this->fixture->loadExtJS();
		$this->fixture->addExtOnReadyCode('var x = "testvar";');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue1, $out);
		$this->assertContains($expectedReturnValue2, $out);
	}

	/**
	 * test add CSS file
	 */
	public function testAddCssFile() {
		$expectedReturnValue = '<link rel="stylesheet" type="text/css" href="fileadmin/test.css" media="all" />';
		$this->fixture->addCssFile('fileadmin/test.css');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add CSS inline
	 */
	public function testAddCssInlineBlock() {
		$expectedReturnValue = 'body {margin:20px;}';
		$this->fixture->addCssInlineBlock('general', 'body {margin:20px;}');
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add CSS inline and force on top
	 */
	public function testAddCssInlineBlockForceOnTop() {
		$expectedReturnValue = '/*general1*/' . LF . 'h1 {margin:20px;}' . LF . '/*general*/' . LF . 'body {margin:20px;}';
		$this->fixture->addCssInlineBlock('general', 'body {margin:20px;}');
		$this->fixture->addCssInlineBlock('general1', 'h1 {margin:20px;}', NULL, TRUE);
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test load prototype
	 */
	public function testLoadPrototype() {
		$expectedRegExp = '#<script src="contrib/prototype/prototype\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->loadPrototype();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test load Scriptaculous
	 */
	public function testLoadScriptaculous() {
		$this->fixture->loadScriptaculous('slider,controls');
		$out = $this->fixture->render();
		$this->assertContains('<script src="contrib/scriptaculous/scriptaculous.js" type="text/javascript"></script>', $out);
		$this->assertContains('<script src="contrib/scriptaculous/effects.js" type="text/javascript"></script>', $out);
		$this->assertContains('<script src="contrib/scriptaculous/controls.js" type="text/javascript"></script>', $out);
		$this->assertContains('<script src="contrib/scriptaculous/slider.js" type="text/javascript"></script>', $out);
	}

	/**
	 * Tests whether scriptaculous is loaded correctly when compression is enabled.
	 *
	 * @test
	 */
	public function isScriptaculousLoadedCompressedIfConfiguredAndClientIsCapable() {
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '5';
		$this->fixture->loadScriptaculous('slider,controls');
		$this->fixture->enableCompressJavascript();
		$out = $this->fixture->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
	}

	/**
	 * Tests whether scriptaculous is correctly loaded, but without compression
	 * if the browser did not send the appropriate headers.
	 *
	 * @test
	 */
	public function isScriptaculousNotLoadedCompressedIfClientCannotHandleCompression() {
		$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '5';
		$this->fixture->loadScriptaculous('slider,controls');
		$this->fixture->enableCompressJavascript();
		$out = $this->fixture->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
	}

	/**
	 * Tests whether scriptaculous is correctly loaded, but without compression
	 * if no compression is configured.
	 *
	 * @test
	 */
	public function isScriptaculousNotLoadedCompressedIfCompressionIsNotConfigured() {
		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '';
		$this->fixture->loadScriptaculous('slider,controls');
		$this->fixture->enableCompressJavascript();
		$out = $this->fixture->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
	}

	/**
	 * test load jQuery
	 *
	 * @test
	 */
	public function loadJqueryLoadsTheLatestJqueryMinifiedVersionInNoConflictMode() {
		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);';
		$this->fixture->loadJquery();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
	}

	/**
	 * test load jQuery
	 *
	 * @test
	 */
	public function loadJqueryRespectsGivenNamespace() {
		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.MyNameSpace = jQuery.noConflict(true);';
		$this->fixture->loadJquery(NULL, NULL, 'MyNameSpace');
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
	}

	/**
	 * test load jQuery
	 *
	 * @test
	 */
	public function loadJqueryWithDefaultNoConflictModeDoesNotSetNamespace() {
		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'jQuery.noConflict();';
		$this->fixture->loadJquery(NULL, NULL, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT);
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
		$this->assertNotContains('var TYPO3 = TYPO3 || {}; TYPO3.', $out);
	}

	/**
	 * test load jQuery
	 *
	 * @test
	 */
	public function loadJqueryWithNamespaceNoneDoesNotIncludeNoConflictHandling() {
		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->loadJquery(NULL, NULL, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_NONE);
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertNotContains('jQuery.noConflict', $out);
	}

	/**
	 * test load jQuery
	 *
	 * @test
	 */
	public function loadJqueryLoadsTheLatestJqueryVersionInNoConflictModeUncompressedInDebugMode() {
		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);';
		$this->fixture->loadJquery();
		$this->fixture->enableDebugMode();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @test
	 */
	public function includingNotAvailableLocalJqueryVersionThrowsException() {
		$this->fixture->loadJquery('1.3.34');
		$this->fixture->render();
	}

	/**
	 * @expectedException \UnexpectedValueException
	 * @test
	 */
	public function includingJqueryWithNonAlphnumericNamespaceThrowsException() {
		$this->fixture->loadJquery(NULL, NULL, '12sd.12fsd');
		$this->fixture->render();
	}

	/**
	 * @return array
	 */
	public function loadJqueryFromSourceDataProvider() {
		$specificVersion = '1.6.3';
		return array(
			'google with no version number' => array(NULL, 'google', '#<script src="//ajax.googleapis.com/ajax/libs/jquery/' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '/jquery.js" type="text/javascript"></script>#'),
			'google with version number' => array($specificVersion, 'google', '#<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $specificVersion . '/jquery.js" type="text/javascript"></script>#'),
			'msn with no version number' => array(NULL, 'msn', '#<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '.js" type="text/javascript"></script>#'),
			'msn with version number' => array($specificVersion, 'msn', '#<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-' . $specificVersion . '.js" type="text/javascript"></script>#'),
			'jquery with no version number' => array(NULL, 'jquery', '#<script src="http://code.jquery.com/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '.js" type="text/javascript"></script>#'),
			'jquery with version number' => array($specificVersion, 'jquery', '#<script src="http://code.jquery.com/jquery-' . $specificVersion . '.js" type="text/javascript"></script>#'),
			'jquery with custom URL' => array($specificVersion, 'http://my.cool.cdn/foo/jquery.js', '#<script src="http://my.cool.cdn/foo/jquery.js" type="text/javascript"></script>#')
		);
	}

	/**
	 * Tests whether jQuery is correctly loaded, from the respective CDNs
	 *
	 * @dataProvider loadJqueryFromSourceDataProvider
	 * @test
	 */
	public function isJqueryLoadedFromSourceUncompressedIfDebugModeIsEnabled($version, $source, $regex) {
		$this->fixture->loadJquery($version, $source);
		$this->fixture->enableDebugMode();
		$out = $this->fixture->render();
		$this->assertRegExp($regex, $out);
	}

	/**
	 * @return array
	 */
	public function loadJqueryMinifiedFromSourceDataProvider() {
		$specificVersion = '1.6.3';
		return array(
			'google with no version number' => array(NULL, 'google', '#<script src="//ajax.googleapis.com/ajax/libs/jquery/' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '/jquery.min.js" type="text/javascript"></script>#'),
			'google with version number' => array($specificVersion, 'google', '#<script src="//ajax.googleapis.com/ajax/libs/jquery/' . $specificVersion . '/jquery.min.js" type="text/javascript"></script>#'),
			'msn with no version number' => array(NULL, 'msn', '#<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '.min.js" type="text/javascript"></script>#'),
			'msn with version number' => array($specificVersion, 'msn', '#<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-' . $specificVersion . '.min.js" type="text/javascript"></script>#'),
			'jquery with no version number' => array(NULL, 'jquery', '#<script src="http://code.jquery.com/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '.min.js" type="text/javascript"></script>#'),
			'jquery with version number' => array($specificVersion, 'jquery', '#<script src="http://code.jquery.com/jquery-' . $specificVersion . '.min.js" type="text/javascript"></script>#')
		);
	}

	/**
	 * Tests whether jQuery is correctly loaded, from the respective CDNs
	 *
	 * @dataProvider loadJqueryMinifiedFromSourceDataProvider
	 * @test
	 */
	public function isJqueryLoadedMinifiedFromSourceByDefault($version, $cdn, $regex) {
		$this->fixture->loadJquery($version, $cdn);
		$out = $this->fixture->render();
		$this->assertRegExp($regex, $out);
	}

	/**
	 * test load ExtJS
	 */
	public function testLoadExtJS() {
		$expectedRegExp = '#<script src="contrib/extjs/adapter/jquery/ext-jquery-adapter\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>' . LF . '<script src="contrib/extjs/ext-all\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#m';
		$this->fixture->loadExtJS(TRUE, TRUE, 'jquery');
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test load ExtCore
	 */
	public function testLoadExtCore() {
		$expectedRegExp = '#<script src="contrib/extjs/ext-core\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->loadExtCore();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test enable ExtJsDebug
	 */
	public function testEnableExtJsDebug() {
		$expectedRegExp = '#<script src="contrib/extjs/ext-all-debug\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->loadExtJS(TRUE, TRUE, 'jquery');
		$this->fixture->enableExtJsDebug();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test enable ExtCoreDebug
	 */
	public function testEnableExtCoreDebug() {
		$expectedRegExp = '#<script src="contrib/extjs/ext-core-debug\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$this->fixture->loadExtCore();
		$this->fixture->enableExtCoreDebug();
		$out = $this->fixture->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * test inline language label
	 */
	public function testAddInlineLanguageLabel() {
		$expectedReturnValue = 'TYPO3.lang = {"myKey":"myValue"}';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineLanguageLabel('myKey', 'myValue');
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test inline language label as array
	 */
	public function testAddInlineLanguageLabelArray() {
		$expectedReturnValue = 'TYPO3.lang = {"myKey1":"myValue1","myKey2":"myValue2"}';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineLanguageLabelArray(array('myKey1' => 'myValue1', 'myKey2' => 'myValue2'));
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test inline language label as array get merged
	 */
	public function testAddInlineLanguageLabelArrayMerged() {
		$expectedReturnValue = 'TYPO3.lang = {"myKey1":"myValue1","myKey2":"myValue2"}';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineLanguageLabelArray(array('myKey1' => 'myValue1'));
		$this->fixture->addInlineLanguageLabelArray(array('myKey2' => 'myValue2'));
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test inline setting
	 */
	public function testAddInlineSetting() {
		$expectedReturnValue = 'TYPO3.settings = {"myApp":{"myKey":"myValue"}};';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineSetting('myApp', 'myKey', 'myValue');
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test inline settings with array
	 */
	public function testAddInlineSettingArray() {
		$expectedReturnValue = 'TYPO3.settings = {"myApp":{"myKey1":"myValue1","myKey2":"myValue2"}};';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineSettingArray('myApp', array('myKey1' => 'myValue1', 'myKey2' => 'myValue2'));
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test inline settings with array get merged
	 */
	public function testAddInlineSettingArrayMerged() {
		$expectedReturnValue = 'TYPO3.settings = {"myApp":{"myKey1":"myValue1","myKey2":"myValue2"}};';
		$this->fixture->loadExtJS();
		$this->fixture->addInlineSettingArray('myApp', array('myKey1' => 'myValue1'));
		$this->fixture->addInlineSettingArray('myApp', array('myKey2' => 'myValue2'));
		$this->fixture->enableMoveJsFromHeaderToFooter();
		$out = $this->fixture->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * test add body content
	 */
	public function testAddBodyContent() {
		$expectedReturnValue = 'ABCDE';
		$this->fixture->addBodyContent('A');
		$this->fixture->addBodyContent('B');
		$this->fixture->addBodyContent('C');
		$this->fixture->addBodyContent('D');
		$this->fixture->addBodyContent('E');
		$out = $this->fixture->getBodyContent();
		$this->assertEquals($expectedReturnValue, $out);
	}

	/**
	 * test set body content
	 */
	public function testSetBodyContent() {
		$expectedReturnValue = 'ABCDE';
		$this->fixture->setBodyContent('ABCDE');
		$out = $this->fixture->getBodyContent();
		$this->assertEquals($expectedReturnValue, $out);
		$out = $this->fixture->render();
		$this->assertContains($expectedReturnValue, $out);
	}

	/**
	 * Tests the addInlineLanguageLabelFile() method.
	 *
	 * @test
	 * @expectedException \RuntimeException
	 */
	public function areInlineLanguageLabelsNotProcessable() {
		$this->fixture->setLanguage(NULL);
		$this->fixture->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf');
		$out = $this->fixture->render();
	}

	/**
	 * Tests the addInlineLanguageLabelFile() method.
	 *
	 * @test
	 */
	public function areInlineLanguageLabelsPassed() {
		$this->fixture->setLanguage($GLOBALS['LANG']->lang);
		$this->fixture->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf');
		$out = $this->fixture->render();
		$this->assertContains('labels.beUser', $out);
		$this->assertContains('labels.feUser', $out);
	}

	/**
	 * Tests the addInlineLanguageLabelFile() method.
	 *
	 * @test
	 */
	public function areInlineLanguageLabelsEmptyOnNonExistingFile() {
		$this->fixture->addInlineLanguageLabelFile('');
		$inlineLanguageLabelFiles = $this->fixture->getInlineLanguageLabelFiles();
		$this->assertEquals(array(), $inlineLanguageLabelFiles);
	}

	/**
	 * Tests the addInlineLanguageLabelFile() method.
	 *
	 * @test
	 */
	public function areInlineLanguageLabelsSelected() {
		$this->fixture->setLanguage($GLOBALS['LANG']->lang);
		$this->fixture->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf', 'labels.');
		$out = $this->fixture->render();
		$this->assertContains('labels.beUser', $out);
		$this->assertContains('labels.feUser', $out);
	}

	/**
	 * Tests the addInlineLanguageLabelFile() method.
	 *
	 * @test
	 */
	public function areInlineLanguageLabelsSelectedAndStripped() {
		$this->fixture->setLanguage($GLOBALS['LANG']->lang);
		$this->fixture->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf', 'labels.', 'lock');
		$out = $this->fixture->render();
		$this->assertContains('edRecord', $out);
		$this->assertContains('edRecord_content', $out);
		$this->assertContains('edRecordUser', $out);
	}

}

?>