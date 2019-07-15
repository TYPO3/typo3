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

use TYPO3\CMS\Core\Page\PageRenderer;

/**
 * Test case
 */
class PageRendererTest extends \TYPO3\TestingFramework\Core\Functional\FunctionalTestCase
{
    /**
     * @test
     */
    public function pageRendererRendersInsertsMainContentStringsInOutput()
    {
        $subject = new PageRenderer();
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

        $subject->setMetaTag('property', 'og:type', 'foobar');
        $subject->setMetaTag('name', 'author', 'husel');
        $subject->setMetaTag('name', 'author', 'foobar');
        $subject->setMetaTag('http-equiv', 'refresh', '5');
        $subject->setMetaTag('name', 'DC.Author', '<evil tag>');
        $subject->setMetaTag('property', 'og:image', '/path/to/image1.jpg', [], false);
        $subject->setMetaTag('property', 'og:image', '/path/to/image2.jpg', [], false);

        // Unset meta tag
        $subject->setMetaTag('NaMe', 'randomTag', 'foobar');
        $subject->removeMetaTag('name', 'RanDoMtAg');

        $inlineComment = $this->getUniqueId('comment');
        $subject->addInlineComment($inlineComment);
        $expectedInlineCommentString = '<!-- ' . LF . $inlineComment . '-->';

        $headerData = $expectedHeaderData = '<tag method="private" name="test" />';
        $subject->addHeaderData($headerData);

        $subject->addJsLibrary('test', 'fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('fileadmin/test-plain.js', '', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileWithoutTypeRegExp = '#wrapBefore<script src="fileadmin/test-plain\\.(js|\\d+\\.js|js\\?\\d+)"></script>wrapAfter#';

        $jsInlineCode = $expectedJsInlineCodeString = 'var x = "' . $this->getUniqueId('jsInline-') . '"';
        $subject->addJsInlineCode($this->getUniqueId(), $jsInlineCode);

        $cssFile = $this->getUniqueId('cssFile-');
        $expectedCssFileString = 'wrapBefore<link rel="stylesheet" type="text/css" href="' . $cssFile . '" media="print" />wrapAfter';
        $subject->addCssFile($cssFile, 'stylesheet', 'print', '', true, false, 'wrapBeforeXwrapAfter', false, 'X');

        $expectedCssInlineBlockOnTopString = '/*general3*/' . LF . 'h1 {margin:20px;}' . LF . '/*general2*/' . LF . 'body {margin:20px;}';
        $subject->addCssInlineBlock('general2', 'body {margin:20px;}');
        $subject->addCssInlineBlock('general3', 'h1 {margin:20px;}', null, true);

        $expectedBodyContent = $this->getUniqueId('ABCDE-');
        $subject->setBodyContent($expectedBodyContent);

        $renderedString = $subject->render();

        $this->assertStringContainsString($expectedPrologueString, $renderedString);
        $this->assertStringContainsString($expectedTitleString, $renderedString);
        $this->assertStringContainsString($expectedCharsetString, $renderedString);
        $this->assertStringContainsString($expectedFavouriteIconPartOne, $renderedString);
        $this->assertStringContainsString($expectedBaseUrlString, $renderedString);
        $this->assertStringContainsString($expectedInlineCommentString, $renderedString);
        $this->assertStringContainsString($expectedHeaderData, $renderedString);
        $this->assertRegExp($expectedJsLibraryRegExp, $renderedString);
        $this->assertRegExp($expectedJsFileRegExp, $renderedString);
        $this->assertRegExp($expectedJsFileWithoutTypeRegExp, $renderedString);
        $this->assertStringContainsString($expectedJsInlineCodeString, $renderedString);
        $this->assertStringContainsString($expectedCssFileString, $renderedString);
        $this->assertStringContainsString($expectedCssInlineBlockOnTopString, $renderedString);
        $this->assertStringContainsString($expectedBodyContent, $renderedString);
        $this->assertStringContainsString('<meta property="og:type" content="foobar" />', $renderedString);
        $this->assertStringContainsString('<meta name="author" content="foobar" />', $renderedString);
        $this->assertStringContainsString('<meta http-equiv="refresh" content="5" />', $renderedString);
        $this->assertStringContainsString('<meta name="dc.author" content="&lt;evil tag&gt;" />', $renderedString);
        $this->assertStringNotContainsString('<meta name="randomtag" content="foobar">', $renderedString);
        $this->assertStringNotContainsString('<meta name="randomtag" content="foobar" />', $renderedString);
        $this->assertStringContainsString('<meta name="generator" content="TYPO3 CMS" />', $renderedString);
        $this->assertStringContainsString('<meta property="og:image" content="/path/to/image1.jpg" />', $renderedString);
        $this->assertStringContainsString('<meta property="og:image" content="/path/to/image2.jpg" />', $renderedString);
    }

    /**
     * @test
     */
    public function pageRendererRendersFooterValues()
    {
        $subject = new PageRenderer();
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
        $subject->addInlineLanguageLabel('myKey', 'myValue');
        $subject->addInlineLanguageLabelArray([
            'myKeyArray1' => 'myValueArray1',
            'myKeyArray2' => 'myValueArray2'
        ]);
        $subject->addInlineLanguageLabelArray([
            'myKeyArray3' => 'myValueArray3'
        ]);
        $expectedInlineLabelReturnValue = 'TYPO3.lang = {"myKey":"myValue","myKeyArray1":"myValueArray1","myKeyArray2":"myValueArray2","myKeyArray3":"myValueArray3"';

        $subject->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
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

        $renderedString = $subject->render(PageRenderer::PART_FOOTER);

        $this->assertStringContainsString($expectedFooterData, $renderedString);
        $this->assertRegExp($expectedJsFooterLibraryRegExp, $renderedString);
        $this->assertRegExp($expectedJsFooterRegExp, $renderedString);
        $this->assertStringContainsString($expectedJsFooterInlineCodeString, $renderedString);
        $this->assertStringContainsString($expectedInlineLabelReturnValue, $renderedString);
        $this->assertStringContainsString($expectedLanguageLabel1, $renderedString);
        $this->assertStringContainsString($expectedLanguageLabel2, $renderedString);
        $this->assertStringContainsString($expectedInlineSettingsReturnValue, $renderedString);
    }
}
