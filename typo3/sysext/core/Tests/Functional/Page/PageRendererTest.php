<?php

declare(strict_types=1);

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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\StreamFactoryInterface;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Imaging\IconRegistry;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Localization\Locale;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\CMS\Core\Page\AssetCollector;
use TYPO3\CMS\Core\Page\AssetRenderer;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Resource\RelativeCssPathFixer;
use TYPO3\CMS\Core\Resource\ResourceCompressor;
use TYPO3\CMS\Core\Service\MarkerBasedTemplateService;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageRendererTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected function createPageRenderer(): PageRenderer
    {
        $container = $this->getContainer();
        return new PageRenderer(
            $container->get('cache.assets'),
            $container->get(MarkerBasedTemplateService::class),
            $container->get(MetaTagManagerRegistry::class),
            $container->get(AssetRenderer::class),
            $container->get(AssetCollector::class),
            new ResourceCompressor(),
            new RelativeCssPathFixer(),
            $container->get(LanguageServiceFactory::class),
            $container->get(ResponseFactoryInterface::class),
            $container->get(StreamFactoryInterface::class),
            $container->get(IconRegistry::class),
        );
    }

    #[Test]
    public function pageRendererRendersInsertsMainContentStringsInOutput(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $subject = $this->createPageRenderer();
        $subject->setLanguage(new Locale());

        $prologueString = $expectedPrologueString = '<?xml version="1.0" encoding="utf-8" ?>';
        $subject->setXmlPrologAndDocType($prologueString);

        $title = StringUtility::getUniqueId('aTitle-');
        $subject->setTitle($title);
        $expectedTitleString = '<title>' . $title . '</title>';

        $expectedCharsetString = '<meta charset="utf-8">';

        $favouriteIcon = 'http://example.com/favicon.ico';
        $subject->setFavIcon($favouriteIcon);
        $expectedFavouriteIconPartOne = '<link rel="icon" href="' . $favouriteIcon . '">';

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

        $subject->loadJavaScriptModule('@typo3/core/ajax/ajax-request.js');
        $expectedJavaScriptModuleScriptRegExp = '#<script type="module" async="async" src="[^"]*typo3/sysext/core/Resources/Public/JavaScript/ajax/ajax-request\.js\?bust=[^"]*"></script>#';

        $subject->addJsLibrary(
            'test',
            '/fileadmin/test.js',
            'text/javascript',
            false,
            false,
            'wrapBeforeXwrapAfter',
            false,
            'X'
        );
        $expectedJsLibraryRegExp = '#wrapBefore<script src="/fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('/fileadmin/test.js', 'text/javascript', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileRegExp = '#wrapBefore<script src="/fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';

        $subject->addJsFile('/fileadmin/test-plain.js', '', false, false, 'wrapBeforeXwrapAfter', false, 'X');
        $expectedJsFileWithoutTypeRegExp = '#wrapBefore<script src="/fileadmin/test-plain\\.(js|\\d+\\.js|js\\?\\d+)"></script>wrapAfter#';

        $jsInlineCode = $expectedJsInlineCodeString = 'var x = "' . StringUtility::getUniqueId('jsInline-') . '"';
        $subject->addJsInlineCode(StringUtility::getUniqueId(), $jsInlineCode);

        $cssFile = StringUtility::getUniqueId('/cssFile-');
        $expectedCssFileString = 'wrapBefore<link rel="stylesheet" href="' . $cssFile . '" media="print">wrapAfter';
        $subject->addCssFile($cssFile, 'stylesheet', 'print', '', true, false, 'wrapBeforeXwrapAfter', false, 'X');

        $expectedCssInlineBlockOnTopString = '/*general3*/' . LF . 'h1 {margin:20px;}' . LF . '/*general2*/' . LF . 'body {margin:20px;}';
        $subject->addCssInlineBlock('general2', 'body {margin:20px;}');
        $subject->addCssInlineBlock('general3', 'h1 {margin:20px;}', false, true);

        $expectedBodyContent = StringUtility::getUniqueId('ABCDE-');
        $subject->setBodyContent($expectedBodyContent);

        $state = serialize($subject->getState());
        $renderedString = $subject->render();

        self::assertStringContainsString($expectedPrologueString, $renderedString);
        self::assertStringContainsString($expectedTitleString, $renderedString);
        self::assertStringContainsString($expectedCharsetString, $renderedString);
        self::assertStringContainsString($expectedFavouriteIconPartOne, $renderedString);
        self::assertStringContainsString($expectedInlineCommentString, $renderedString);
        self::assertStringContainsString($expectedHeaderData, $renderedString);
        self::assertMatchesRegularExpression($expectedJavaScriptModuleScriptRegExp, $renderedString);
        self::assertMatchesRegularExpression($expectedJsLibraryRegExp, $renderedString);
        self::assertMatchesRegularExpression($expectedJsFileRegExp, $renderedString);
        self::assertMatchesRegularExpression($expectedJsFileWithoutTypeRegExp, $renderedString);
        self::assertStringContainsString($expectedJsInlineCodeString, $renderedString);
        self::assertStringContainsString($expectedCssFileString, $renderedString);
        self::assertStringContainsString($expectedCssInlineBlockOnTopString, $renderedString);
        self::assertStringContainsString($expectedBodyContent, $renderedString);
        self::assertStringContainsString('<meta property="og:type" content="foobar">', $renderedString);
        self::assertStringContainsString('<meta name="author" content="foobar">', $renderedString);
        self::assertStringContainsString('<meta http-equiv="refresh" content="5">', $renderedString);
        self::assertStringContainsString('<meta name="dc.author" content="&lt;evil tag&gt;">', $renderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar">', $renderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar" />', $renderedString);
        self::assertStringContainsString('<meta name="generator" content="TYPO3 CMS">', $renderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image1.jpg">', $renderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image2.jpg">', $renderedString);

        $stateBasedSubject = $this->createPageRenderer();
        $stateBasedSubject->updateState(unserialize($state, ['allowed_classes' => [Locale::class]]));
        $stateBasedRenderedString = $stateBasedSubject->render();
        self::assertStringContainsString($expectedPrologueString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedTitleString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedCharsetString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedFavouriteIconPartOne, $stateBasedRenderedString);
        self::assertStringContainsString($expectedInlineCommentString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedHeaderData, $stateBasedRenderedString);
        self::assertMatchesRegularExpression($expectedJavaScriptModuleScriptRegExp, $stateBasedRenderedString);
        self::assertMatchesRegularExpression($expectedJsLibraryRegExp, $stateBasedRenderedString);
        self::assertMatchesRegularExpression($expectedJsFileRegExp, $stateBasedRenderedString);
        self::assertMatchesRegularExpression($expectedJsFileWithoutTypeRegExp, $stateBasedRenderedString);
        self::assertStringContainsString($expectedJsInlineCodeString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedCssFileString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedCssInlineBlockOnTopString, $stateBasedRenderedString);
        self::assertStringContainsString($expectedBodyContent, $stateBasedRenderedString);
        self::assertStringContainsString('<meta property="og:type" content="foobar">', $stateBasedRenderedString);
        self::assertStringContainsString('<meta name="author" content="foobar">', $stateBasedRenderedString);
        self::assertStringContainsString('<meta http-equiv="refresh" content="5">', $stateBasedRenderedString);
        self::assertStringContainsString('<meta name="dc.author" content="&lt;evil tag&gt;">', $stateBasedRenderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar">', $stateBasedRenderedString);
        self::assertStringNotContainsString('<meta name="randomtag" content="foobar" />', $stateBasedRenderedString);
        self::assertStringContainsString('<meta name="generator" content="TYPO3 CMS">', $stateBasedRenderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image1.jpg">', $stateBasedRenderedString);
        self::assertStringContainsString('<meta property="og:image" content="/path/to/image2.jpg">', $stateBasedRenderedString);
    }

    public static function pageRendererRendersFooterValuesDataProvider(): array
    {
        return [
            'frontend' => [SystemEnvironmentBuilder::REQUESTTYPE_FE],
            'backend' => [SystemEnvironmentBuilder::REQUESTTYPE_BE],
        ];
    }

    #[DataProvider('pageRendererRendersFooterValuesDataProvider')]
    #[Test]
    public function pageRendererRendersFooterValues(int $requestType): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', $requestType);
        $subject = $this->createPageRenderer();
        $subject->setLanguage(new Locale());

        $subject->enableMoveJsFromHeaderToFooter();

        $footerData = $expectedFooterData = '<tag method="private" name="test" />';
        $subject->addFooterData($footerData);

        $expectedJsFooterLibraryRegExp = '#wrapBefore<script src="/fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
        $subject->addJsFooterLibrary(
            'test',
            '/fileadmin/test.js',
            'text/javascript',
            false,
            false,
            'wrapBeforeXwrapAfter',
            false,
            'X'
        );

        $expectedJsFooterRegExp = '#wrapBefore<script src="/fileadmin/test\\.(js|\\d+\\.js|js\\?\\d+)" type="text/javascript"></script>wrapAfter#';
        $subject->addJsFooterFile(
            '/fileadmin/test.js',
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
            'myKeyArray2' => 'myValueArray2',
        ]);
        $subject->addInlineLanguageLabelArray([
            'myKeyArray3' => 'myValueArray3',
        ]);
        $expectedInlineLabelReturnValue = '"lang":{"myKey":"myValue","myKeyArray1":"myValueArray1","myKeyArray2":"myValueArray2","myKeyArray3":"myValueArray3",';

        $subject->addInlineLanguageLabelFile('EXT:core/Resources/Private/Language/locallang_core.xlf');
        $expectedLanguageLabel1 = 'labels.beUser';
        $expectedLanguageLabel2 = 'labels.feUser';

        // Bunch of inline settings test
        $subject->addInlineSetting('myApp', 'myKey', 'myValue');
        $subject->addInlineSettingArray('myApp', [
            'myKey1' => 'myValue1',
            'myKey2' => 'myValue2',
        ]);
        $subject->addInlineSettingArray('myApp', [
            'myKey3' => 'myValue3',
        ]);
        $expectedInlineSettingsReturnValue = '"settings":{"myApp":{"myKey":"myValue","myKey1":"myValue1","myKey2":"myValue2","myKey3":"myValue3"}';

        if ($requestType === SystemEnvironmentBuilder::REQUESTTYPE_FE) {
            $expectedInlineAssignmentsPrefix = 'var TYPO3 = Object.assign(TYPO3 || {}, Object.fromEntries(Object.entries({"settings":';
        } else {
            $expectedInlineAssignmentsPrefix = '<script>Object.assign(globalThis, {"TYPO3":{"settings":{';
        }

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedFooterData, $renderedString);
        self::assertMatchesRegularExpression($expectedJsFooterLibraryRegExp, $renderedString);
        self::assertMatchesRegularExpression($expectedJsFooterRegExp, $renderedString);
        self::assertStringContainsString($expectedJsFooterInlineCodeString, $renderedString);
        self::assertStringContainsString($expectedLanguageLabel1, $renderedString);
        self::assertStringContainsString($expectedLanguageLabel2, $renderedString);
        self::assertStringMatchesFormat('%a' . $expectedInlineAssignmentsPrefix . '%a', $renderedString);
        self::assertStringContainsString($expectedInlineLabelReturnValue, $renderedString);
        self::assertStringContainsString($expectedInlineSettingsReturnValue, $renderedString);
    }

    #[Test]
    public function pageRendererRendersNomoduleJavascript(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $subject = $this->createPageRenderer();
        $subject->setLanguage(new Locale());

        $subject->addJsFooterLibrary(
            'test',
            '/fileadmin/test.js',
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
        $expectedJsFooterLibrary = '<script src="/fileadmin/test.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsLibrary(
            'test2',
            '/fileadmin/test2.js',
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
        $expectedJsLibrary = '<script src="/fileadmin/test2.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsFile(
            '/fileadmin/test3.js',
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
        $expectedJsFile = '<script src="/fileadmin/test3.js" type="text/javascript" nomodule="nomodule"></script>';

        $subject->addJsFooterFile(
            '/fileadmin/test4.js',
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
        $expectedJsFooter = '<script src="/fileadmin/test4.js" type="text/javascript" nomodule="nomodule"></script>';

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedJsFooterLibrary, $renderedString);
        self::assertStringContainsString($expectedJsLibrary, $renderedString);
        self::assertStringContainsString($expectedJsFile, $renderedString);
        self::assertStringContainsString($expectedJsFooter, $renderedString);
    }

    #[Test]
    public function pageRendererRendersDataAttributeInScriptTags(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $subject = $this->createPageRenderer();
        $subject->setLanguage(new Locale());

        $subject->addJsFooterLibrary(
            'test',
            '/fileadmin/test.js',
            tagAttributes: [
                'type' => 'text/javascript',
                'data-foo' => 'JsFooterLibrary',
                'data-bar' => 'baz',
            ]
        );
        $expectedJsFooterLibrary = '<script src="/fileadmin/test.js" type="text/javascript" data-foo="JsFooterLibrary" data-bar="baz"></script>';

        $subject->addJsLibrary(
            'test2',
            '/fileadmin/test2.js',
            'text/javascript',
            tagAttributes: [
                'data-foo' => 'JsLibrary',
                'data-bar' => 'baz',
            ]
        );
        $expectedJsLibrary = '<script src="/fileadmin/test2.js" type="text/javascript" data-foo="JsLibrary" data-bar="baz"></script>';

        $subject->addJsFile(
            '/fileadmin/test3.js',
            'text/javascript',
            tagAttributes: [
                'data-foo' => 'JsFile',
                'data-bar' => 'baz',
            ]
        );
        $expectedJsFile = '<script src="/fileadmin/test3.js" type="text/javascript" data-foo="JsFile" data-bar="baz"></script>';

        $subject->addJsFooterFile(
            '/fileadmin/test4.js',
            'text/javascript',
            tagAttributes: [
                'data-foo' => 'JsFooterFile',
                'data-bar' => 'baz',
            ]
        );
        $expectedJsFooter = '<script src="/fileadmin/test4.js" type="text/javascript" data-foo="JsFooterFile" data-bar="baz"></script>';

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedJsFooterLibrary, $renderedString);
        self::assertStringContainsString($expectedJsLibrary, $renderedString);
        self::assertStringContainsString($expectedJsFile, $renderedString);
        self::assertStringContainsString($expectedJsFooter, $renderedString);
    }

    #[Test]
    public function pageRendererRendersDataAttributeInCssTags(): void
    {
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest('https://www.example.com/'))
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_BE);
        $subject = $this->createPageRenderer();
        $subject->setLanguage(new Locale());

        $subject->addCssFile(
            '/fileadmin/test.css',
            tagAttributes: [
                'data-foo' => 'CssFile',
                'data-bar' => 'baz',
            ]
        );
        $expectedCssFile = '<link rel="stylesheet" href="/fileadmin/test.css" media="all" data-foo="CssFile" data-bar="baz">';

        $subject->addCssLibrary(
            '/fileadmin/test.css',
            tagAttributes: [
                'data-foo' => 'CssLibrary',
                'data-bar' => 'baz',
            ]
        );
        $expectedCssLibrary = '<link rel="stylesheet" href="/fileadmin/test.css" media="all" data-foo="CssLibrary" data-bar="baz">';

        $renderedString = $subject->render();

        self::assertStringContainsString($expectedCssFile, $renderedString);
        self::assertStringContainsString($expectedCssLibrary, $renderedString);
    }
}
