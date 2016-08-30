<?php
namespace TYPO3\CMS\Core\Tests\Functional\Page;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

/**
 * Test case
 */
class PageRendererTest extends \TYPO3\CMS\Core\Tests\FunctionalTestCase
{
    /**
     * @test
     */
    public function pageRendererRendersInsertsMainContentStringsInOutput()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();
        $subject->setCharSet('utf-8');
        $subject->setLanguage('default');

        $prologueString = $expectedPrologueString = '<?xml version="1.0" encoding="utf-8" ?>';
        $subject->setXmlPrologAndDocType($prologueString);

        $title = $this->getUniqueId('aTitle-');
        $subject->setTitle($title);
        $expectedTitleString = '<title>' . $title . '</title>';

        $charset = 'utf-8';
        $subject->setCharSet($charset);
        $expectedCharsetString = '<meta http-equiv="Content-Type" content="text/html; charset=' . $charset . '" />';

        $favouriteIcon = 'http://google.com/favicon.ico';
        $subject->setFavIcon($favouriteIcon);
        $expectedFavouriteIconPartOne = '<link rel="shortcut icon" href="' . $favouriteIcon . '" />';

        $baseUrl = 'http://google.com/';
        $subject->setBaseUrl($baseUrl);
        $expectedBaseUrlString = '<base href="' . $baseUrl . '" />';

        $metaTag = $expectedMetaTagString = '<meta name="author" content="Anna Lyse">';
        $subject->addMetaTag($metaTag);

        $inlineComment = $this->getUniqueId('comment');
        $subject->addInlineComment($inlineComment);
        $expectedInlineCommentString = '<!-- ' . LF . $inlineComment . '-->';

        $headerData = $expectedHeaderData = '<tag method="private" name="test" />';
        $subject->addHeaderData($headerData);

        $subject->addJsLibrary('test', 'fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $jsInlineCode = $expectedJsInlineCodeString = 'var x = "' . $this->getUniqueId('jsInline-') . '"';
        $subject->addJsInlineCode($this->getUniqueId(), $jsInlineCode);

        $extOnReadyCode = $expectedExtOnReadyCodePartOne = $this->getUniqueId('extOnReady-');
        $expectedExtOnReadyCodePartTwo = 'Ext.onReady(function() {';
        $subject->loadExtJS();
        $subject->addExtOnReadyCode($extOnReadyCode);

        $cssFile = $this->getUniqueId('cssFile-');
        $expectedCssFileString = 'wrapBefore<link rel="stylesheet" type="text/css" href="' . $cssFile . '" media="print" />wrapAfter';
        $subject->addCssFile($cssFile, 'stylesheet', 'print', '', true, false, 'wrapBeforeXwrapAfter', false, 'X');

        $expectedCssInlineBlockOnTopString = '/*general3*/' . LF . 'h1 {margin:20px;}' . LF . '/*general2*/' . LF . 'body {margin:20px;}';
        $subject->addCssInlineBlock('general2', 'body {margin:20px;}');
        $subject->addCssInlineBlock('general3', 'h1 {margin:20px;}', null, true);

        $subject->loadJquery();
        $expectedJqueryRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
        $expectedJqueryStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.jQuery = jQuery.noConflict(true);';

        $expectedBodyContent = $this->getUniqueId('ABCDE-');
        $subject->setBodyContent($expectedBodyContent);

        $renderedString = $subject->render();

        $this->assertContains($expectedPrologueString, $renderedString);
        $this->assertContains($expectedTitleString, $renderedString);
        $this->assertContains($expectedCharsetString, $renderedString);
        $this->assertContains($expectedFavouriteIconPartOne, $renderedString);
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
        $this->assertRegExp($expectedJqueryRegExp, $renderedString);
        $this->assertContains($expectedJqueryStatement, $renderedString);
        $this->assertContains($expectedBodyContent, $renderedString);
    }

    /**
     * @test
     */
    public function pageRendererRendersFooterValues()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();
        $subject->setCharSet('utf-8');
        $subject->setLanguage('default');

        $subject->enableMoveJsFromHeaderToFooter();

        $footerData = $expectedFooterData = '<tag method="private" name="test" />';
        $subject->addFooterData($footerData);

        $expectedJsFooterLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
        $subject->addJsFooterLibrary('test', 'fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');

        $expectedJsFooterRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
        $subject->addJsFooterFile('fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');

        $jsFooterInlineCode = $expectedJsFooterInlineCodeString = 'var x = "' . $this->getUniqueId('jsFooterInline-') . '"';
        $subject->addJsFooterInlineCode($this->getUniqueId(), $jsFooterInlineCode);

        // Bunch of label tests
        $subject->loadExtJS();
        $subject->addInlineLanguageLabel('myKey', 'myValue');
        $subject->addInlineLanguageLabelArray([
            'myKeyArray1' => 'myValueArray1',
            'myKeyArray2' => 'myValueArray2'
        ]);
        $subject->addInlineLanguageLabelArray([
            'myKeyArray3' => 'myValueArray3'
        ]);
        $expectedInlineLabelReturnValue = 'TYPO3.lang = {"myKey":"myValue","myKeyArray1":"myValueArray1","myKeyArray2":"myValueArray2","myKeyArray3":"myValueArray3"';

        $subject->addInlineLanguageLabelFile('EXT:lang/locallang_core.xlf');
        $expectedLanguageLabel1 = 'labels.beUser';
        $expectedLanguageLabel2 = 'labels.feUser';

        // Bunch of inline settings test
        $subject->addInlineSetting('myApp', 'myKey', 'myValue');
        $subject->addInlineSettingArray('myApp', [
            'myKey1' => 'myValue1',
            'myKey2' => 'myValue2'
        ]);
        $subject->addInlineSettingArray('myApp', [
            'myKey3' => 'myValue3'
        ]);
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
    public function loadJqueryRespectsGivenNamespace()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
        $expectedStatement = 'var TYPO3 = TYPO3 || {}; TYPO3.MyNameSpace = jQuery.noConflict(true);';
        $subject->loadJquery(null, null, 'MyNameSpace');
        $out = $subject->render();
        $this->assertRegExp($expectedRegExp, $out);
        $this->assertContains($expectedStatement, $out);
    }

    /**
     * @test
     */
    public function loadJqueryWithDefaultNoConflictModeDoesNotSetNamespace()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
        $expectedStatement = 'jQuery.noConflict();';
        $subject->loadJquery(null, null, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_DEFAULT_NOCONFLICT);
        $out = $subject->render();
        $this->assertRegExp($expectedRegExp, $out);
        $this->assertContains($expectedStatement, $out);
        $this->assertNotContains('var TYPO3 = TYPO3 || {}; TYPO3.', $out);
    }

    /**
     * @test
     */
    public function loadJqueryWithNamespaceNoneDoesNotIncludeNoConflictHandling()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.min\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
        $subject->loadJquery(null, null, \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_NAMESPACE_NONE);
        $out = $subject->render();
        $this->assertRegExp($expectedRegExp, $out);
        $this->assertNotContains('jQuery.noConflict', $out);
    }

    /**
     * @test
     */
    public function loadJqueryLoadsTheLatestJqueryVersionInNoConflictModeUncompressedInDebugMode()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/jquery/jquery-' . \TYPO3\CMS\Core\Page\PageRenderer::JQUERY_VERSION_LATEST . '\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
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
    public function loadJqueryFromSourceDataProvider()
    {
        return [
            'google with version number' => [
                '1.6.3',
                'google',
                '#<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.js" type="text/javascript"></script>#'
            ],
            'msn with version number' => [
                '1.6.3',
                'msn',
                '#<script src="https://ajax.aspnetcdn.com/ajax/jQuery/jquery-1.6.3.js" type="text/javascript"></script>#'
            ],
            'jquery with version number' => [
                '1.6.3',
                'jquery',
                '#<script src="https://code.jquery.com/jquery-1.6.3.js" type="text/javascript"></script>#'
            ],
            'jquery with custom URL' => [
                '1.6.3',
                'https://my.cool.cdn/foo/jquery.js',
                '#<script src="https://my.cool.cdn/foo/jquery.js" type="text/javascript"></script>#'
            ],
        ];
    }

    /**
     * @dataProvider loadJqueryFromSourceDataProvider
     * @test
     */
    public function isJqueryLoadedFromSourceUncompressedIfDebugModeIsEnabled($version, $source, $regex)
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $subject->loadJquery($version, $source);
        $subject->enableDebugMode();
        $out = $subject->render();
        $this->assertRegExp($regex, $out);
    }

    /**
     * @test
     */
    public function isJqueryLoadedMinifiedFromGoogleByDefault()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegex = '#<script src="https://ajax.googleapis.com/ajax/libs/jquery/1.6.3/jquery.min.js" type="text/javascript"></script>#';
        $subject->loadJquery('1.6.3', 'google');
        $out = $subject->render();
        $this->assertRegExp($expectedRegex, $out);
    }

    /**
     * @test
     */
    public function loadExtJsInDebugLoadsDebugExtJs()
    {
        $subject = new \TYPO3\CMS\Core\Page\PageRenderer();

        $expectedRegExp = '#<script src="sysext/core/Resources/Public/JavaScript/Contrib/extjs/ext-all-debug\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>#';
        $subject->loadExtJS(true, true);
        $subject->enableExtJsDebug();
        $out = $subject->render();
        $this->assertRegExp($expectedRegExp, $out);
    }
}
