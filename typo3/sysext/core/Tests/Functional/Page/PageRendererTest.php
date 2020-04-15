<?php

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

namespace TYPO3\CMS\Core\Tests\Functional\Page;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class PageRendererTest extends FunctionalTestCase
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

        $title = StringUtility::getUniqueId('aTitle-');
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

        $inlineComment = StringUtility::getUniqueId('comment');
        $subject->addInlineComment($inlineComment);
        $expectedInlineCommentString = '<!-- ' . LF . $inlineComment . '-->';

        $headerData = $expectedHeaderData = '<tag method="private" name="test" />';
        $subject->addHeaderData($headerData);

        $subject->addJsLibrary(
            'test',
            'fileadmin/test.js',
            'text/javascript',
            false,
            false,
            'wrapBeforeXwrapAfter',
            false,
            'X'
        );
        $expectedJsLibraryRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('fileadmin/test-plain.js', '', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileWithoutTypeRegExp = '#wrapBefore<script src="fileadmin/test-plain\\.(js|\\d+\\.js|js\\?\\d+)"></script>wrapAfter#';

        $jsInlineCode = $expectedJsInlineCodeString = 'var x = "' . StringUtility::getUniqueId('jsInline-') . '"';
        $subject->addJsInlineCode(StringUtility::getUniqueId(), $jsInlineCode);

        $cssFile = StringUtility::getUniqueId('cssFile-');
        $expectedCssFileString = 'wrapBefore<link rel="stylesheet" type="text/css" href="' . $cssFile . '" media="print" />wrapAfter';
        $subject->addCssFile($cssFile, 'stylesheet', 'print', '', true, false, 'wrapBeforeXwrapAfter', false, 'X');

        $expectedCssInlineBlockOnTopString = '/*general3*/' . LF . 'h1 {margin:20px;}' . LF . '/*general2*/' . LF . 'body {margin:20px;}';
        $subject->addCssInlineBlock('general2', 'body {margin:20px;}');
        $subject->addCssInlineBlock('general3', 'h1 {margin:20px;}', null, true);

        $expectedBodyContent = StringUtility::getUniqueId('ABCDE-');
        $subject->setBodyContent($expectedBodyContent);

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedPrologueString, $renderedString);
        self::assertStringContainsString($expectedTitleString, $renderedString);
        self::assertStringContainsString($expectedCharsetString, $renderedString);
        self::assertStringContainsString($expectedFavouriteIconPartOne, $renderedString);
        self::assertStringContainsString($expectedBaseUrlString, $renderedString);
        self::assertStringContainsString($expectedInlineCommentString, $renderedString);
        self::assertStringContainsString($expectedHeaderData, $renderedString);
        self::assertRegExp($expectedJsLibraryRegExp, $renderedString);
        self::assertRegExp($expectedJsFileRegExp, $renderedString);
        self::assertRegExp($expectedJsFileWithoutTypeRegExp, $renderedString);
        self::assertStringContainsString($expectedJsInlineCodeString, $renderedString);
        self::assertStringContainsString($expectedCssFileString, $renderedString);
        self::assertStringContainsString($expectedCssInlineBlockOnTopString, $renderedString);
        self::assertStringContainsString($expectedBodyContent, $renderedString);
        self::assertStringContainsString('<meta property="og:type" content="foobar" />', $renderedString);
        self::assertStringContainsString('<meta name="author" content="foobar" />', $renderedString);
        self::assertStringContainsString('<meta http-equiv="refresh" content="5" />', $renderedString);
        self::assertStringContainsString('<meta name="dc.author" content="&lt;evil tag&gt;" />', $renderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar">', $renderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar" />', $renderedString);
        self::assertStringContainsString('<meta name="generator" content="TYPO3 CMS" />', $renderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image1.jpg" />', $renderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image2.jpg" />', $renderedString);
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
        $subject->addJsFooterLibrary(
            'test',
            'fileadmin/test.js',
            'text/javascript',
            false,
            false,
            'wrapBeforeXwrapAfter',
            false,
            'X'
        );

        $expectedJsFooterRegExp = '#wrapBefore<script src="fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
        $subject->addJsFooterFile(
            'fileadmin/test.js',
            'text/javascript',
            false,
            false,
            'wrapBeforeXwrapAfter',
            false,
            'X'
        );

        $jsFooterInlineCode = $expectedJsFooterInlineCodeString = 'var x = "' . StringUtility::getUniqueId('jsFooterInline-') . '"';
        $subject->addJsFooterInlineCode(StringUtility::getUniqueId(), $jsFooterInlineCode);

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

        self::assertStringContainsString($expectedFooterData, $renderedString);
        self::assertRegExp($expectedJsFooterLibraryRegExp, $renderedString);
        self::assertRegExp($expectedJsFooterRegExp, $renderedString);
        self::assertStringContainsString($expectedJsFooterInlineCodeString, $renderedString);
        self::assertStringContainsString($expectedInlineLabelReturnValue, $renderedString);
        self::assertStringContainsString($expectedLanguageLabel1, $renderedString);
        self::assertStringContainsString($expectedLanguageLabel2, $renderedString);
        self::assertStringContainsString($expectedInlineSettingsReturnValue, $renderedString);
    }

    /**
     * @test
     */
    public function pageRendererRendersNomoduleJavascript()
    {
        $subject = new PageRenderer();
        $subject->setCharSet('utf-8');
        $subject->setLanguage('default');

        $subject->addJsFooterLibrary(
            'test',
            'fileadmin/test.js',
            'text/javascript',
            false,
            false,
            '',
            false,
            '|',
            false,
            '',
            false,
            '',
            true
        );
        $expectedJsFooterLibrary = '<script src="fileadmin/test.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsLibrary(
            'test2',
            'fileadmin/test2.js',
            'text/javascript',
            false,
            false,
            '',
            false,
            '|',
            false,
            '',
            false,
            '',
            true
        );
        $expectedJsLibrary = '<script src="fileadmin/test2.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsFile(
            'fileadmin/test3.js',
            'text/javascript',
            false,
            false,
            '',
            false,
            '|',
            false,
            '',
            false,
            '',
            true
        );
        $expectedJsFile = '<script src="fileadmin/test3.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsFooterFile(
            'fileadmin/test4.js',
            'text/javascript',
            false,
            false,
            '',
            false,
            '|',
            false,
            '',
            false,
            '',
            true
        );
        $expectedJsFooter = '<script src="fileadmin/test4.js" type="text/javascript" nomodule="nomodule"></script>';

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedJsFooterLibrary, $renderedString);
        self::assertStringContainsString($expectedJsLibrary, $renderedString);
        self::assertStringContainsString($expectedJsFile, $renderedString);
        self::assertStringContainsString($expectedJsFooter, $renderedString);
    }

    /**
     * @test
     */
    public function pageRendererMergesRequireJsPackagesOnConsecutiveCalls(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['SYS']['session']['BE'] = [
            'backend'  => DatabaseSessionBackend::class,
            'options' => [
                'table' => 'be_sessions',
            ],
        ];
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $GLOBALS['BE_USER']->id = md5('abc');
        $GLOBALS['BE_USER']->user = ['uid' => 1];
        $GLOBALS['BE_USER']->setLogger(new NullLogger());

        $GLOBALS['LANG'] = LanguageService::createFromUserPreferences($GLOBALS['BE_USER']);

        $subject = new PageRenderer();
        $subject->setCharSet('utf-8');
        $subject->setLanguage('default');

        $packages = [
            [
                'name' => 'foo',
                'location' => '/typo3conf/ext/foo/Resources/Public/JavaScript/Contrib/foo',
                'main' => 'lib/foo'
            ],
            [
                'name' => 'bar',
                'location' => '/typo3conf/ext/bar/Resources/Public/JavaScript/Contrib/bar',
                'main' => 'lib/bar'
            ]
        ];

        foreach ($packages as $package) {
            $subject->addRequireJsConfiguration([
                'packages' => [$package]
            ]);
        }

        $expectedConfiguration = json_encode(['packages' => $packages]);
        // Remove surrounding brackets as the expectation is a substring of a larger JSON string
        $expectedConfiguration = trim($expectedConfiguration, '{}');

        $renderedString = $subject->render();
        self::assertStringContainsString($expectedConfiguration, $renderedString);
    }
}
