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

namespace TYPO3\CMS\Frontend\Tests\Unit\Http;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Information\Typo3Information;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\Type\DocType;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Core\TypoScript\TypoScriptService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\RequestHandler;
use TYPO3\CMS\Frontend\Resource\FilePathSanitizer;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * @todo: It looks as if these unit tests should be turned into functional tests
 */
final class RequestHandlerTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function generateHtmlTagIncludesAllPossibilitiesDataProvider(): array
    {
        return [
            'no original values' => [
                [],
                [],
                '<html>',
            ],
            'no additional values' => [
                ['dir' => 'left'],
                [],
                '<html dir="left">',
            ],
            'no additional values #2' => [
                ['dir' => 'left', 'xmlns:dir' => 'left'],
                [],
                '<html dir="left" xmlns:dir="left">',
            ],
            'disable all attributes' => [
                ['dir' => 'left', 'xmlns:dir' => 'left'],
                ['htmlTag_setParams' => 'none'],
                '<html>',
            ],
            'only add setParams' => [
                ['dir' => 'left', 'xmlns:dir' => 'left'],
                ['htmlTag_setParams' => 'amp'],
                '<html amp>',
            ],
            'attributes property trumps htmlTag_setParams' => [
                ['dir' => 'left', 'xmlns:dir' => 'left'],
                ['htmlTag.' => ['attributes.' => ['amp' => '']], 'htmlTag_setParams' => 'none'],
                '<html dir="left" xmlns:dir="left" amp>',
            ],
            'attributes property with mixed values' => [
                ['dir' => 'left', 'xmlns:dir' => 'left'],
                ['htmlTag.' => ['attributes.' => ['amp' => '', 'no-js' => 'true', 'additional-enabled' => 0]]],
                '<html dir="left" xmlns:dir="left" amp no-js="true" additional-enabled="0">',
            ],
            'attributes property overrides default settings' => [
                ['dir' => 'left'],
                ['htmlTag.' => ['attributes.' => ['amp' => '', 'dir' => 'right']]],
                '<html amp dir="right">',
            ],
        ];
    }

    /**
     * Does not test stdWrap functionality.
     */
    #[DataProvider('generateHtmlTagIncludesAllPossibilitiesDataProvider')]
    #[Test]
    public function generateHtmlTagIncludesAllPossibilities(array $htmlTagAttributes, array $configuration, string $expectedResult): void
    {
        $subject = $this->getAccessibleMock(RequestHandler::class, null, [], '', false);
        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $contentObjectRendererMock->expects($this->never())->method('stdWrap');
        $result = $subject->_call('generateHtmlTag', $htmlTagAttributes, $configuration, $contentObjectRendererMock);
        self::assertEquals($expectedResult, $result);
    }

    #[Test]
    public function generateMetaTagExpectExceptionOnBogusTags(): void
    {
        $stdWrapResult = '10';

        $expectedTags = [
            'type' => 'http-equiv-new',
            'name' => 'refresh',
            'content' => '10',
        ];

        $siteLanguage = $this->createSiteWithLanguage()->getLanguageById(3);
        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $contentObjectRendererMock->expects($this->atLeastOnce())->method('cObjGet')->with(self::anything())->willReturn('');
        $contentObjectRendererMock->expects($this->once())->method('stdWrap')->with(self::anything())->willReturn($stdWrapResult);
        $frontendControllerMock = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $frontendControllerMock->expects($this->once())->method('generatePageTitle')->willReturn('');
        $frontendControllerMock->expects($this->once())->method('INTincScript_loadJSCode');
        $frontendController = $frontendControllerMock;
        $frontendController->cObj = $contentObjectRendererMock;
        $typo3InformationMock = $this->getMockBuilder(Typo3Information::class)->disableOriginalConstructor()->getMock();
        $typo3InformationMock->expects($this->once())->method('getInlineHeaderComment')->willReturn('dummy');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationMock);

        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $pageRendererMock->method('getDocType')->willReturn(DocType::html5);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setPageArray([
            'meta.' => [
                'refresh' => '10',
                'refresh.' => [
                    'attribute' => 'http-equiv-new',
                ],
            ],
        ]);
        $frontendTypoScript->setConfigArray([]);
        $request = (new ServerRequest())
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('routing', new PageArguments(1, '0', []))
            ->withAttribute('language', $siteLanguage);
        $subject = $this->getAccessibleMock(
            RequestHandler::class,
            ['getPageRenderer'],
            [
                new NoopEventDispatcher(),
                new ListenerProvider(new Container()),
                new TimeTracker(false),
                new FilePathSanitizer(),
                new TypoScriptService(),
                new Context(),
            ],
        );
        $subject->method('getPageRenderer')->willReturn($pageRendererMock);
        $subject->_call('processHtmlBasedRenderingSettings', $frontendController, $request);
        $pageRendererMock->expects($this->never())->method('setMetaTag')->with($expectedTags['type'], $expectedTags['name'], $expectedTags['content'])->willThrowException(new \InvalidArgumentException('', 1666309039));
    }

    public static function generateMetaTagHtmlGeneratesCorrectTagsDataProvider(): array
    {
        return [
            'simple meta' => [
                [
                    'author' => 'Markus Klein',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'author',
                    'content' => 'Markus Klein',
                ],
            ],
            'httpEquivalent meta' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['httpEquivalent' => 1],
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1',
                ],
            ],
            'httpEquivalent meta xhtml new notation' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['attribute' => 'http-equiv'],
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1',
                ],
            ],
            'refresh meta' => [
                [
                    'refresh' => '10',
                ],
                '',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10',
                ],
            ],
            'refresh meta new notation' => [
                [
                    'refresh' => '10',
                    'refresh.' => ['attribute' => 'http-equiv'],
                ],
                '10',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10',
                ],
            ],
            'meta with dot' => [
                [
                    'DC.author' => 'Markus Klein',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'DC.author',
                    'content' => 'Markus Klein',
                ],
            ],
            'meta with colon' => [
                [
                    'OG:title' => 'Magic Tests',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'OG:title',
                    'content' => 'Magic Tests',
                ],
            ],
            'different attribute name' => [
                [
                    'og:site_title' => 'My TYPO3 site',
                    'og:site_title.' => ['attribute' => 'property'],
                ],
                'My TYPO3 site',
                [
                    'type' => 'property',
                    'name' => 'og:site_title',
                    'content' => 'My TYPO3 site',
                ],
            ],
            'meta with 0 value' => [
                [
                    'custom:key' => '0',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'custom:key',
                    'content' => '0',
                ],
            ],
        ];
    }

    #[DataProvider('generateMetaTagHtmlGeneratesCorrectTagsDataProvider')]
    #[Test]
    public function generateMetaTagHtmlGeneratesCorrectTags(array $typoScript, string $stdWrapResult, array $expectedTags): void
    {
        $siteLanguage = $this->createSiteWithLanguage()->getLanguageById(3);
        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $contentObjectRendererMock->expects($this->atLeastOnce())->method('cObjGet')->with(self::anything())->willReturn('');
        $contentObjectRendererMock->method('stdWrap')->with(self::anything())->willReturn($stdWrapResult);
        $frontendControllerMock = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $frontendControllerMock->expects($this->once())->method('generatePageTitle')->willReturn('');
        $frontendControllerMock->expects($this->once())->method('INTincScript_loadJSCode');
        $frontendController = $frontendControllerMock;
        $frontendController->cObj = $contentObjectRendererMock;
        $typo3InformationMock = $this->getMockBuilder(Typo3Information::class)->disableOriginalConstructor()->getMock();
        $typo3InformationMock->expects($this->once())->method('getInlineHeaderComment')->willReturn('dummy');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationMock);

        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $pageRendererMock->method('getDocType')->willReturn(DocType::html5);
        $pageRendererMock->expects($this->once())->method('setMetaTag')->with($expectedTags['type'], $expectedTags['name'], $expectedTags['content'], [], false);
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setPageArray([
            'meta.' => $typoScript,
        ]);
        $frontendTypoScript->setConfigArray([]);
        $request = (new ServerRequest())
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('routing', new PageArguments(1, '0', []))
            ->withAttribute('language', $siteLanguage);
        $subject = $this->getAccessibleMock(
            RequestHandler::class,
            ['getPageRenderer'],
            [
                new NoopEventDispatcher(),
                new ListenerProvider(new Container()),
                new TimeTracker(false),
                new FilePathSanitizer(),
                new TypoScriptService(),
                new Context(),
            ],
        );
        $subject->method('getPageRenderer')->willReturn($pageRendererMock);
        $subject->_call('processHtmlBasedRenderingSettings', $frontendController, $request);
    }

    #[Test]
    public function generateMetaTagHtmlGenerateNoTagWithEmptyContent(): void
    {
        $stdWrapResult = '';

        $siteLanguage = $this->createSiteWithLanguage()->getLanguageById(3);
        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $contentObjectRendererMock->expects($this->atLeastOnce())->method('cObjGet')->with(self::anything())->willReturn('');
        $contentObjectRendererMock->method('stdWrap')->with(self::anything())->willReturn($stdWrapResult);
        $frontendControllerMock = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $frontendControllerMock->method('generatePageTitle')->willReturn('');
        $frontendControllerMock->expects($this->once())->method('INTincScript_loadJSCode');
        $frontendController = $frontendControllerMock;
        $frontendController->cObj = $contentObjectRendererMock;
        $typo3InformationMock = $this->getMockBuilder(Typo3Information::class)->disableOriginalConstructor()->getMock();
        $typo3InformationMock->expects($this->once())->method('getInlineHeaderComment')->willReturn('dummy');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationMock);

        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $pageRendererMock->method('getDocType')->willReturn(DocType::html5);
        $pageRendererMock->expects($this->never())->method('setMetaTag');
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setPageArray([
            'meta.' => [
                'custom:key' => '',
            ],
        ]);
        $frontendTypoScript->setConfigArray([]);
        $request = (new ServerRequest())
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('routing', new PageArguments(1, '0', []))
            ->withAttribute('language', $siteLanguage);
        $subject = $this->getAccessibleMock(
            RequestHandler::class,
            ['getPageRenderer'],
            [
                new NoopEventDispatcher(),
                new ListenerProvider(new Container()),
                new TimeTracker(false),
                new FilePathSanitizer(),
                new TypoScriptService(),
                new Context(),
            ],
        );
        $subject->method('getPageRenderer')->willReturn($pageRendererMock);
        $subject->_call('processHtmlBasedRenderingSettings', $frontendController, $request);
    }

    public static function generateMultipleMetaTagsDataProvider(): array
    {
        return [
            'multi value attribute name' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => 'de_DE',
                        ],
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL',
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE',
                    ],
                ],
            ],
            'multi value attribute name (empty values are skipped)' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => '',
                            30 => 'de_DE',
                        ],
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL',
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE',
                    ],
                ],
            ],
        ];
    }

    #[DataProvider('generateMultipleMetaTagsDataProvider')]
    #[Test]
    public function generateMultipleMetaTags(array $typoScript, string $stdWrapResult, array $expectedTags): void
    {
        $siteLanguage = $this->createSiteWithLanguage()->getLanguageById(3);
        $contentObjectRendererMock = $this->getMockBuilder(ContentObjectRenderer::class)->disableOriginalConstructor()->getMock();
        $contentObjectRendererMock->expects($this->atLeastOnce())->method('cObjGet')->with(self::anything())->wilLReturn('');
        $contentObjectRendererMock->method('stdWrap')->with(self::anything())->willReturn($stdWrapResult);
        $frontendControllerMock = $this->getMockBuilder(TypoScriptFrontendController::class)->disableOriginalConstructor()->getMock();
        $frontendControllerMock->expects($this->once())->method('generatePageTitle')->willReturn('');
        $frontendControllerMock->expects($this->once())->method('INTincScript_loadJSCode');
        $frontendController = $frontendControllerMock;
        $frontendController->cObj = $contentObjectRendererMock;
        $typo3InformationMock = $this->getMockBuilder(Typo3Information::class)->disableOriginalConstructor()->getMock();
        $typo3InformationMock->expects($this->once())->method('getInlineHeaderComment')->willReturn('This website is...');
        GeneralUtility::addInstance(Typo3Information::class, $typo3InformationMock);

        $pageRendererMock = $this->getMockBuilder(PageRenderer::class)->disableOriginalConstructor()->getMock();
        $pageRendererMock->method('getDocType')->willReturn(DocType::html5);
        $series = [
            [$expectedTags[0]['type'], $expectedTags[0]['name'], $expectedTags[0]['content'], [], false],
            [$expectedTags[1]['type'], $expectedTags[1]['name'], $expectedTags[1]['content'], [], false],
        ];
        $pageRendererMock
            ->expects($this->exactly(2))
            ->method('setMetaTag')
            ->willReturnCallback(function (string $type, string $name, string $content, array $subProperties, bool $replace) use (&$series): void {
                $expectedArgs = array_shift($series);
                self::assertSame($expectedArgs[0], $type);
                self::assertSame($expectedArgs[1], $name);
                self::assertSame($expectedArgs[2], $content);
                self::assertSame($expectedArgs[3], $subProperties);
                self::assertSame($expectedArgs[4], $replace);
            });
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setPageArray([
            'meta.' => $typoScript,
        ]);
        $frontendTypoScript->setConfigArray([]);
        $request = (new ServerRequest())
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('routing', new PageArguments(1, '0', []))
            ->withAttribute('language', $siteLanguage);
        $subject = $this->getAccessibleMock(
            RequestHandler::class,
            ['getPageRenderer'],
            [
                new NoopEventDispatcher(),
                new ListenerProvider(new Container()),
                new TimeTracker(false),
                new FilePathSanitizer(),
                new TypoScriptService(),
                new Context(),
            ],
        );
        $subject->method('getPageRenderer')->willReturn($pageRendererMock);
        $subject->_call('processHtmlBasedRenderingSettings', $frontendController, $request);
    }

    /**
     * Test if the method is called, and the globals are still the same after calling the method
     */
    #[Test]
    public function resetGlobalsToCurrentRequestDoesNotModifyAnything(): void
    {
        $getVars = ['outside' => '1'];
        $postVars = ['world' => 'yo'];
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'https://www.example.com/my/path/';
        $_GET = $getVars;
        $_POST = $postVars;
        $request = ServerRequestFactory::fromGlobals();

        $subject = $this->getAccessibleMock(RequestHandler::class, null, [], '', false);
        $subject->_call('resetGlobalsToCurrentRequest', $request);
        self::assertEquals($_GET, $getVars);
        self::assertEquals($_POST, $postVars);
    }

    /**
     * Test if the method is called, and the globals are still the same after calling the method
     */
    #[Test]
    public function resetGlobalsToCurrentRequestWithModifiedRequestOverridesGlobals(): void
    {
        $getVars = ['typical' => '1'];
        $postVars = ['mixtape' => 'wheels'];
        $modifiedGetVars = ['typical' => 1, 'dont-stop' => 'the-music'];
        $modifiedPostVars = ['mixtape' => 'wheels', 'tx_blogexample_pi1' => ['uid' => 13]];
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'https://www.example.com/my/path/';
        $_GET = $getVars;
        $_POST = $postVars;
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withQueryParams($modifiedGetVars);
        $request = $request->withParsedBody($modifiedPostVars);

        $subject = $this->getAccessibleMock(RequestHandler::class, null, [], '', false);
        $subject->_call('resetGlobalsToCurrentRequest', $request);
        self::assertEquals($_GET, $modifiedGetVars);
        self::assertEquals($_POST, $modifiedPostVars);
    }

    private function createSiteWithLanguage(): Site
    {
        return new Site('test', 1, [
            'identifier' => 'test',
            'rootPageId' => 1,
            'base' => '/',
            'languages' => [
                [
                    'base' => '/',
                    'languageId' => 3,
                    'locale' => 'fr_FR',
                ],
            ],
        ]);
    }
}
