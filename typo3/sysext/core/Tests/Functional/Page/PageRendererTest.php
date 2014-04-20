<?php
namespace TYPO3\CMS\Core\Tests\Functional\Page;

/***************************************************************
 * Copyright notice
 *
 * (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 * Test case
 */
class PageRendererTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase {

	/**
	 * @test
	 */
	public function pageRendererRendersInsertsMainContentStringsInOutput() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();
		$subject->setCharSet('utf-8');
		$subject->setLanguage('default');

		$prologueString = $expectedPrologueString = '<?xml version="1.0" encoding="utf-8" ?>';
		$subject->setXmlPrologAndDocType($prologueString);

		$title = uniqid('aTitle-');
		$subject->setTitle($title);
		$expectedTitleString = '<title>' . $title . '</title>';

		$charset = 'utf-8';
		$subject->setCharSet($charset);
		$expectedCharsetString = '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />';

		$favouriteIcon = 'http://google.com/favicon.ico';
		$subject->setFavIcon($favouriteIcon);
		$expectedFavouriteIconPartOne = '<link rel="shortcut icon" href="' . $favouriteIcon . '" />';
		$expectedFavouriteIconPartTwo = '<link rel="icon" href="' . $favouriteIcon . '" />';

		$baseUrl = 'http://google.com/';
		$subject->setBaseUrl($baseUrl);
		$expectedBaseUrlString = '<base href="' . $baseUrl . '" />';

		$metaTag = $expectedMetaTagString = '<meta name="author" content="Anna Lyse">';
		$subject->addMetaTag($metaTag);

		$inlineComment = uniqid('comment');
		$subject->addInlineComment($inlineComment);
		$expectedInlineCommentString = '<!-- ' . LF . $inlineComment . '-->';

		$headerData = $expectedHeaderData = '<tag method="private" name="test" />';
		$subject->addHeaderData($headerData);

		$subject->addJsLibrary('test', 'fileadmin/test.js', 'text/javascript', FALSE, FALSE, 'wrapBeforeXwrapAfter', FALSE, 'X');
		$expectedJsLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

		$subject->addJsFile('fileadmin/test.js', 'text/javascript', FALSE, FALSE, 'wrapBeforeXwrapAfter', FALSE, 'X');
		$expectedJsFileRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

		$jsInlineCode = $expectedJsInlineCodeString = 'var x = "' . uniqid('jsInline-') . '"';
		$subject->addJsInlineCode(uniqid(), $jsInlineCode);

		$extOnReadyCode = $expectedExtOnReadyCodePartOne = uniqid('extOnReady-');
		$expectedExtOnReadyCodePartTwo = 'Ext.onReady(function() {';
		$subject->loadExtJS();
		$subject->addExtOnReadyCode($extOnReadyCode);

		$cssFile = uniqid('cssFile-');
		$expectedCssFileString = 'wrapBefore<link rel="stylesheet" type="text/css" href="' . $cssFile . '" media="print" />wrapAfter';
		$subject->addCssFile($cssFile, 'stylesheet', 'print', '', TRUE, FALSE, 'wrapBeforeXwrapAfter', FALSE, 'X');

		$expectedCssInlineBlockOnTopString = '/*general3*/' . LF . 'h1 {margin:20px;}' . LF . '/*general2*/' . LF . 'body {margin:20px;}';
		$subject->addCssInlineBlock('general2', 'body {margin:20px;}');
		$subject->addCssInlineBlock('general3', 'h1 {margin:20px;}', NULL, TRUE);

		$expectedLoadPrototypeRegExp = '#<script src="contrib/prototype/prototype\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$subject->loadPrototype();

		$subject->loadScriptaculous('slider,controls');
		$expectedScriptaculousMain = '<script src="contrib/scriptaculous/scriptaculous.js" type="text/javascript"></script>';
		$expectedScriptaculousEffects = '<script src="contrib/scriptaculous/effects.js" type="text/javascript"></script>';
		$expectedScriptaculousControls = '<script src="contrib/scriptaculous/controls.js" type="text/javascript"></script>';
		$expectedScriptaculousSlider  = '<script src="contrib/scriptaculous/slider.js" type="text/javascript"></script>';

		$subject->loadJquery();
		$expectedJqueryRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedJqueryStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);';

		$subject->loadExtJS(TRUE, TRUE, 'jquery');
		$expectedExtJsRegExp = '#<script src="contrib/extjs/adapter/jquery/ext-jquery-adapter\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>' . LF . '<script src="contrib/extjs/ext-all\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#m';

		$expectedBodyContent = uniqid('ABCDE-');
		$subject->setBodyContent($expectedBodyContent);

		$renderedString = $subject->render();

		$this->assertContains($expectedPrologueString, $renderedString);
		$this->assertContains($expectedTitleString, $renderedString);
		$this->assertContains($expectedCharsetString, $renderedString);
		$this->assertContains($expectedFavouriteIconPartOne, $renderedString);
		$this->assertContains($expectedFavouriteIconPartTwo, $renderedString);
		$this->assertContains($expectedBaseUrlString, $renderedString);
		$this->assertContains($expectedMetaTagString, $renderedString);
		$this->assertContains($expectedInlineCommentString, $renderedString);
		$this->assertContains($expectedHeaderData, $renderedString);
		$this->assertRegExp($expectedJsLibraryRegExp, $renderedString);
		$this->assertRegExp($expectedJsFileRegExp, $renderedString);
		$this->assertContains($expectedJsInlineCodeString, $renderedString);
		$this->assertContains($expectedExtOnReadyCodePartOne, $renderedString);
		$this->assertContains($expectedExtOnReadyCodePartTwo, $renderedString);
		$this->assertContains($expectedCssFileString, $renderedString);
		$this->assertContains($expectedCssInlineBlockOnTopString, $renderedString);
		$this->assertRegExp($expectedLoadPrototypeRegExp, $renderedString);
		$this->assertContains($expectedScriptaculousMain, $renderedString);
		$this->assertContains($expectedScriptaculousEffects, $renderedString);
		$this->assertContains($expectedScriptaculousControls, $renderedString);
		$this->assertContains($expectedScriptaculousSlider, $renderedString);
		$this->assertRegExp($expectedJqueryRegExp, $renderedString);
		$this->assertContains($expectedJqueryStatement, $renderedString);
		$this->assertRegExp($expectedExtJsRegExp, $renderedString);
		$this->assertContains($expectedBodyContent, $renderedString);
	}

	/**
	 * @test
	 */
	public function pageRendererRendersFooterValues() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();
		$subject->setCharSet('utf-8');
		$subject->setLanguage('default');

		$subject->enableMoveJsFromHeaderToFooter();

		$footerData = $expectedFooterData = '<tag method="private" name="test" />';
		$subject->addFooterData($footerData);

		$expectedJsFooterLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
		$subject->addJsFooterLibrary('test', 'fileadmin/test.js', 'text/javascript', FALSE, FALSE, 'wrapBeforeXwrapAfter', FALSE, 'X');

		$expectedJsFooterRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
		$subject->addJsFooterFile('fileadmin/test.js', 'text/javascript', FALSE, FALSE, 'wrapBeforeXwrapAfter', FALSE, 'X');

		$jsFooterInlineCode = $expectedJsFooterInlineCodeString = 'var x = "' . uniqid('jsFooterInline-') . '"';
		$subject->addJsFooterInlineCode(uniqid(), $jsFooterInlineCode);

		// Bunch of label tests
		$subject->loadExtJS();
		$subject->addInlineLanguageLabel('myKey', 'myValue');
		$subject->addInlineLanguageLabelArray(array(
			'myKeyArray1' => 'myValueArray1',
			'myKeyArray2' => 'myValueArray2'
		));
		$subject->addInlineLanguageLabelArray(array(
			'myKeyArray3' => 'myValueArray3'
		));
		$expectedInlineLabelReturnValue = 'TYPO3.lang = {"myKey":"myValue","myKeyArray1":"myValueArray1","myKeyArray2":"myValueArray2","myKeyArray3":"myValueArray3"';

		$subject->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf');
		$expectedLanguageLabel1 = 'labels.beUser';
		$expectedLanguageLabel2 = 'labels.feUser';

		// Bunch of inline settings test
		$subject->addInlineSetting('myApp', 'myKey', 'myValue');
		$subject->addInlineSettingArray('myApp', array(
			'myKey1' => 'myValue1',
			'myKey2' => 'myValue2'
		));
		$subject->addInlineSettingArray('myApp', array(
			'myKey3' => 'myValue3'
		));
		$expectedInlineSettingsReturnValue = 'TYPO3.settings = {"myApp":{"myKey":"myValue","myKey1":"myValue1","myKey2":"myValue2","myKey3":"myValue3"}';

		$renderedString = $subject->render(\TYPO3\CMS\Core\Page\PageRenderer::PART_FOOTER);

		$this->assertContains($expectedFooterData, $renderedString);
		$this->assertRegExp($expectedJsFooterLibraryRegExp, $renderedString);
		$this->assertRegExp($expectedJsFooterRegExp, $renderedString);
		$this->assertContains($expectedJsFooterInlineCodeString, $renderedString);
		$this->assertContains($expectedInlineLabelReturnValue, $renderedString);
		$this->assertContains($expectedLanguageLabel1, $renderedString);
		$this->assertContains($expectedLanguageLabel2, $renderedString);
		$this->assertContains($expectedInlineSettingsReturnValue, $renderedString);
	}

	/**
	 * @test
	 */
	public function isScriptaculousLoadedCompressedIfConfiguredAndClientIsCapable() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '5';
		$subject->loadScriptaculous('slider,controls');
		$subject->enableCompressJavascript();
		$out = $subject->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js.gzip" type="text/javascript"></script>#', $out);
	}

	/**
	 * @test
	 */
	public function isScriptaculousNotLoadedCompressedIfClientCannotHandleCompression() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$_SERVER['HTTP_ACCEPT_ENCODING'] = '';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '5';
		$subject->loadScriptaculous('slider,controls');
		$subject->enableCompressJavascript();
		$out = $subject->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
	}

	/**
	 * @test
	 */
	public function isScriptaculousNotLoadedCompressedIfCompressionIsNotConfigured() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$_SERVER['HTTP_ACCEPT_ENCODING'] = 'gzip,deflate';
		$GLOBALS['TYPO3_CONF_VARS']['BE']['compressionLevel'] = '';
		$subject->loadScriptaculous('slider,controls');
		$subject->enableCompressJavascript();
		$out = $subject->render();
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/scriptaculous-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/effects-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/controls-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
		$this->assertRegExp('#<script src="[^"]*/typo3temp/compressor/slider-[a-f0-9]+.js" type="text/javascript"></script>#', $out);
	}

	/**
	 * @test
	 */
	public function loadJqueryRespectsGivenNamespace() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.MyNameSpace = jQuery.noConflict(true);';
		$subject->loadJquery(NULL, NULL, 'MyNameSpace');
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
	}

	/**
	 * @test
	 */
	public function loadJqueryWithDefaultNoConflictModeDoesNotSetNamespace() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'jQuery.noConflict();';
		$subject->loadJquery(NULL, NULL, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT);
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
		$this->assertNotContains('var TYPO3 = TYPO3 || {}; TYPO3.', $out);
	}

	/**
	 * @test
	 */
	public function loadJqueryWithNamespaceNoneDoesNotIncludeNoConflictHandling() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$subject->loadJquery(NULL, NULL, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_NONE);
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertNotContains('jQuery.noConflict', $out);
	}

	/**
	 * @test
	 */
	public function loadJqueryLoadsTheLatestJqueryVersionInNoConflictModeUncompressedInDebugMode() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);';
		$subject->loadJquery();
		$subject->enableDebugMode();
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
		$this->assertContains($expectedStatement, $out);
	}

	/**
	 * @return array
	 */
	public function loadJqueryFromSourceDataProvider() {
		return array(
			'google with version number' => array(
				'1.6.3',
				'google',
				'#<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.js" type="text/javascript"></script>#'
			),
			'msn with version number' => array(
				'1.6.3',
				'msn',
				'#<script src="//ajax.aspnetcdn.com/ajax/jQuery/jquery-1.6.3.js" type="text/javascript"></script>#'
			),
			'jquery with version number' => array(
				'1.6.3',
				'jquery',
				'#<script src="http://code.jquery.com/jquery-1.6.3.js" type="text/javascript"></script>#'
			),
			'jquery with custom URL' => array(
				'1.6.3',
				'http://my.cool.cdn/foo/jquery.js',
				'#<script src="http://my.cool.cdn/foo/jquery.js" type="text/javascript"></script>#'
			),
		);
	}

	/**
	 * @dataProvider loadJqueryFromSourceDataProvider
	 * @test
	 */
	public function isJqueryLoadedFromSourceUncompressedIfDebugModeIsEnabled($version, $source, $regex) {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$subject->loadJquery($version, $source);
		$subject->enableDebugMode();
		$out = $subject->render();
		$this->assertRegExp($regex, $out);
	}

	/**
	 * @test
	 */
	public function isJqueryLoadedMinifiedFromGoogleByDefault() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegex = '#<script src="//ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.min.js" type="text/javascript"></script>#';
		$subject->loadJquery('1.6.3', 'google');
		$out = $subject->render();
		$this->assertRegExp($expectedRegex, $out);
	}

	/**
	 * @test
	 */
	public function loadExtJsInDebugLoadsDebugExtJs() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/extjs/ext-all-debug\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$subject->loadExtJS(TRUE, TRUE, 'jquery');
		$subject->enableExtJsDebug();
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * @test
	 */
	public function loadExtCoreLoadsExtCore() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/extjs/ext-core\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$subject->loadExtCore();
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
	}

	/**
	 * @test
	 */
	public function loadExtCoreInDebugLoadsDebugExtCore() {
		$subject = new \TYPO3\CMS\Core\Page\PageRenderer();

		$expectedRegExp = '#<script src="contrib/extjs/ext-core-debug\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
		$subject->loadExtCore();
		$subject->enableExtCoreDebug();
		$out = $subject->render();
		$this->assertRegExp($expectedRegExp, $out);
	}
}
