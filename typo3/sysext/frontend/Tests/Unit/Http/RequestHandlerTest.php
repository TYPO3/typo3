<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Frontend\Tests\Unit\Http;

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

use Prophecy\Argument;
use TYPO3\CMS\Core\Http\ServerRequestFactory;
use TYPO3\CMS\Core\Page\PageRenderer;
use TYPO3\CMS\Core\TimeTracker\TimeTracker;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Http\RequestHandler;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class RequestHandlerTest extends UnitTestCase
{
    /**
     * @return array
     */
    public function generateMetaTagHtmlGeneratesCorrectTagsDataProvider()
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
                    'content' => 'Markus Klein'
                ]
            ],
            'httpEquivalent meta' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['httpEquivalent' => 1]
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1'
                ]
            ],
            'httpEquivalent meta xhtml new notation' => [
                [
                    'X-UA-Compatible' => 'IE=edge,chrome=1',
                    'X-UA-Compatible.' => ['attribute' => 'http-equiv']
                ],
                'IE=edge,chrome=1',
                [
                    'type' => 'http-equiv',
                    'name' => 'X-UA-Compatible',
                    'content' => 'IE=edge,chrome=1'
                ]
            ],
            'refresh meta' => [
                [
                    'refresh' => '10',
                ],
                '',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10'
                ]
            ],
            'refresh meta new notation' => [
                [
                    'refresh' => '10',
                    'refresh.' => ['attribute' => 'http-equiv']
                ],
                '10',
                [
                    'type' => 'http-equiv',
                    'name' => 'refresh',
                    'content' => '10'
                ]
            ],
            'meta with dot' => [
                [
                    'DC.author' => 'Markus Klein',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'DC.author',
                    'content' => 'Markus Klein'
                ]
            ],
            'meta with colon' => [
                [
                    'OG:title' => 'Magic Tests',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'OG:title',
                    'content' => 'Magic Tests'
                ]
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
                    'content' => 'My TYPO3 site'
                ]
            ],
            'meta with 0 value' => [
                [
                    'custom:key' => '0',
                ],
                '',
                [
                    'type' => 'name',
                    'name' => 'custom:key',
                    'content' => '0'
                ]
            ],
        ];
    }

    /**
     * @test
     */
    public function generateMetaTagExpectExceptionOnBogusTags()
    {
        $stdWrapResult = '10';

        $typoScript = [
            'refresh' => '10',
            'refresh.' => ['attribute' => 'http-equiv-new']
        ];

        $expectedTags = [
            'type' => 'http-equiv-new',
            'name' => 'refresh',
            'content' => '10'
        ];

        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        $subject = $this->getAccessibleMock(RequestHandler::class, ['getPageRenderer'], [], '', false);
        $subject->expects($this->any())->method('getPageRenderer')->willReturn($pageRendererProphecy->reveal());
        $subject->_set('timeTracker', new TimeTracker(false));
        $subject->_call('generatePageContentWithHeader', $tsfe->reveal(), null);

        $pageRendererProphecy->setMetaTag($expectedTags['type'], $expectedTags['name'], $expectedTags['content'])->willThrow(\InvalidArgumentException::class);
    }

    /**
     * @test
     * @dataProvider generateMetaTagHtmlGeneratesCorrectTagsDataProvider
     *
     * @param array $typoScript
     * @param string $stdWrapResult
     * @param array $expectedTags
     */
    public function generateMetaTagHtmlGeneratesCorrectTags(array $typoScript, string $stdWrapResult, array $expectedTags)
    {
        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        $subject = $this->getAccessibleMock(RequestHandler::class, ['getPageRenderer'], [], '', false);
        $subject->expects($this->any())->method('getPageRenderer')->willReturn($pageRendererProphecy->reveal());
        $subject->_set('timeTracker', new TimeTracker(false));
        $subject->_call('generatePageContentWithHeader', $tsfe->reveal(), null);

        $pageRendererProphecy->setMetaTag($expectedTags['type'], $expectedTags['name'], $expectedTags['content'], [], false)->shouldHaveBeenCalled();
    }

    /**
     * @test
     */
    public function generateMetaTagHtmlGenerateNoTagWithEmptyContent()
    {
        $stdWrapResult = '';

        $typoScript = [
            'custom:key' => '',
        ];

        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];

        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        $subject = $this->getAccessibleMock(RequestHandler::class, ['getPageRenderer'], [], '', false);
        $subject->expects($this->any())->method('getPageRenderer')->willReturn($pageRendererProphecy->reveal());
        $subject->_set('timeTracker', new TimeTracker(false));
        $subject->_call('generatePageContentWithHeader', $tsfe->reveal(), null);

        $pageRendererProphecy->setMetaTag(null, null, null)->shouldNotBeCalled();
    }

    public function generateMultipleMetaTagsDataProvider()
    {
        return [
            'multi value attribute name' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => 'de_DE',
                        ]
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL'
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE'
                    ]
                ]
            ],
            'multi value attribute name (empty values are skipped)' => [
                [
                    'og:locale:alternate.' => [
                        'attribute' => 'property',
                        'value' => [
                            10 => 'nl_NL',
                            20 => '',
                            30 => 'de_DE',
                        ]
                    ],
                ],
                '',
                [
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'nl_NL'
                    ],
                    [
                        'type' => 'property',
                        'name' => 'og:locale:alternate',
                        'content' => 'de_DE'
                    ]
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider generateMultipleMetaTagsDataProvider
     *
     * @param array $typoScript
     * @param string $stdWrapResult
     * @param array $expectedTags
     */
    public function generateMultipleMetaTags(array $typoScript, string $stdWrapResult, array $expectedTags)
    {
        $cObj = $this->prophesize(ContentObjectRenderer::class);
        $cObj->cObjGet(Argument::cetera())->shouldBeCalled();
        $cObj->stdWrap(Argument::cetera())->willReturn($stdWrapResult);
        $tmpl = $this->prophesize(TemplateService::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $tsfe->generatePageTitle()->willReturn('');
        $tsfe->INTincScript_loadJSCode()->shouldBeCalled();
        $tsfe->cObj = $cObj->reveal();
        $tsfe->tmpl = $tmpl->reveal();
        $tsfe->config = [
            'config' => [],
        ];
        $tsfe->page = [
            'title' => ''
        ];
        $tsfe->pSetup = [
            'meta.' => $typoScript
        ];
        $pageRendererProphecy = $this->prophesize(PageRenderer::class);
        $subject = $this->getAccessibleMock(RequestHandler::class, ['getPageRenderer'], [], '', false);
        $subject->expects($this->any())->method('getPageRenderer')->willReturn($pageRendererProphecy->reveal());
        $subject->_set('timeTracker', new TimeTracker(false));
        $subject->_call('generatePageContentWithHeader', $tsfe->reveal(), null);

        $pageRendererProphecy->setMetaTag($expectedTags[0]['type'], $expectedTags[0]['name'], $expectedTags[0]['content'], [], false)->shouldHaveBeenCalled();
        $pageRendererProphecy->setMetaTag($expectedTags[1]['type'], $expectedTags[1]['name'], $expectedTags[1]['content'], [], false)->shouldHaveBeenCalled();
    }

    /**
     * Test if the method is called, and the object is still the same.
     *
     * @test
     */
    public function addModifiedGlobalsToIncomingRequestFindsSameObject()
    {
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'https://www.example.com/my/path/';
        $_GET = ['foo' => '1'];
        $_POST = ['bar' => 'yo'];
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('_originalGetParameters', $_GET);
        $request = $request->withAttribute('_originalPostParameters', $_POST);

        $subject = $this->getAccessibleMock(RequestHandler::class, ['dummy'], [], '', false);
        $resultRequest = $subject->_call('addModifiedGlobalsToIncomingRequest', $request);
        $this->assertSame($request, $resultRequest);
    }

    /**
     * @return array
     */
    public function addModifiedGlobalsToIncomingRequestDataProvider()
    {
        return [
            'No parameters have been modified via hook or middleware' => [
                ['batman' => '1'],
                ['no_cache' => 1],
                // Enriched within PSR-7 query params + parsed body
                [],
                [],
                // modified GET / POST parameters
                [],
                [],
                // expected merged results
                ['batman' => '1'],
                ['no_cache' => 1],
            ],
            'No parameters have been modified via hook' => [
                ['batman' => '1'],
                [],
                // Enriched within PSR-7 query params + parsed body
                ['ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['no_cache' => '1'],
                // modified GET / POST parameters
                [],
                [],
                // expected merged results
                ['batman' => '1', 'ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['no_cache' => 1],
            ],
            'Hooks and Middlewares modified' => [
                ['batman' => '1'],
                [],
                // Enriched within PSR-7 query params + parsed body
                ['ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['no_cache' => '1'],
                // modified GET / POST parameters
                ['batman' => '1', 'add_via_hook' => 'yes'],
                ['submitForm' => 'download now'],
                // expected merged results
                ['batman' => '1', 'add_via_hook' => 'yes', 'ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['submitForm' => 'download now', 'no_cache' => 1],
            ],
            'Hooks and Middlewares modified with middleware overruling hooks' => [
                ['batman' => '1'],
                [],
                // Enriched within PSR-7 query params + parsed body
                ['ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['no_cache' => '1'],
                // modified GET / POST parameters
                ['batman' => '0', 'add_via_hook' => 'yes'],
                ['submitForm' => 'download now', 'no_cache' => 0],
                // expected merged results
                ['batman' => '1', 'add_via_hook' => 'yes', 'ARD' => 'TV', 'Oscars' => 'Cinema'],
                ['submitForm' => 'download now', 'no_cache' => 1],
            ],
            'Hooks and Middlewares modified with middleware overruling hooks with nested parameters' => [
                ['batman' => '1'],
                [['tx_siteexample_pi2' => ['uid' => 13]]],
                // Enriched within PSR-7 query params + parsed body
                ['ARD' => 'TV', 'Oscars' => 'Cinema', ['tx_blogexample_pi1' => ['uid' => 123]]],
                ['no_cache' => '1', ['tx_siteexample_pi2' => ['name' => 'empty-tail']]],
                // modified GET / POST parameters
                ['batman' => '0', 'add_via_hook' => 'yes', ['tx_blogexample_pi1' => ['uid' => 234]]],
                ['submitForm' => 'download now', 'no_cache' => 0],
                // expected merged results
                ['batman' => '1', 'add_via_hook' => 'yes', 'ARD' => 'TV', 'Oscars' => 'Cinema', ['tx_blogexample_pi1' => ['uid' => 123]]],
                ['submitForm' => 'download now', 'no_cache' => '1', ['tx_siteexample_pi2' => ['uid' => 13, 'name' => 'empty-tail']]],
            ],
        ];
    }

    /**
     * Test if the method modifies GET and POST to the expected result, when enriching an object.
     *
     * @param array $initialGetParams
     * @param array $initialPostParams
     * @param array $addedQueryParams
     * @param array $addedParsedBody
     * @param array $modifiedGetParams
     * @param array $modifiedPostParams
     * @param array $expectedQueryParams
     * @param array $expectedParsedBody
     * @dataProvider addModifiedGlobalsToIncomingRequestDataProvider
     * @test
     */
    public function addModifiedGlobalsToIncomingRequestModifiesObject(
        $initialGetParams,
        $initialPostParams,
        $addedQueryParams,
        $addedParsedBody,
        $modifiedGetParams,
        $modifiedPostParams,
        $expectedQueryParams,
        $expectedParsedBody
    ) {
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'https://www.example.com/my/path/';
        $_GET = $initialGetParams;
        $_POST = $initialPostParams;
        $request = ServerRequestFactory::fromGlobals();
        $request = $request->withAttribute('_originalGetParameters', $initialGetParams);
        $request = $request->withAttribute('_originalPostParameters', $initialPostParams);

        // Now enriching the request object with other GET / POST parameters
        $queryParams = $request->getQueryParams();
        $queryParams = array_replace_recursive($queryParams, $addedQueryParams);
        $request = $request->withQueryParams($queryParams);
        $parsedBody = $request->getParsedBody() ?? [];
        $parsedBody = array_replace_recursive($parsedBody, $addedParsedBody);
        $request = $request->withParsedBody($parsedBody);

        // Now overriding GET and POST parameters
        $_GET = $modifiedGetParams;
        $_POST = $modifiedPostParams;

        $subject = $this->getAccessibleMock(RequestHandler::class, ['dummy'], [], '', false);
        $subject->_set('timeTracker', new TimeTracker(false));
        $resultRequest = $subject->_call('addModifiedGlobalsToIncomingRequest', $request);
        $this->assertEquals($expectedQueryParams, $resultRequest->getQueryParams());
        $this->assertEquals($expectedParsedBody, $resultRequest->getParsedBody());
    }

    /**
     * Test if the method is called, and the globals are still the same after calling the method
     *
     * @test
     */
    public function resetGlobalsToCurrentRequestDoesNotModifyAnything()
    {
        $getVars = ['outside' => '1'];
        $postVars = ['world' => 'yo'];
        GeneralUtility::flushInternalRuntimeCaches();
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SERVER['HTTP_HOST'] = 'https://www.example.com/my/path/';
        $_GET = $getVars;
        $_POST = $postVars;
        $request = ServerRequestFactory::fromGlobals();

        $subject = $this->getAccessibleMock(RequestHandler::class, ['dummy'], [], '', false);
        $subject->_call('resetGlobalsToCurrentRequest', $request);
        $this->assertEquals($_GET, $getVars);
        $this->assertEquals($_POST, $postVars);
    }

    /**
     * Test if the method is called, and the globals are still the same after calling the method
     *
     * @test
     */
    public function resetGlobalsToCurrentRequestWithModifiedRequestOverridesGlobals()
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

        $subject = $this->getAccessibleMock(RequestHandler::class, ['dummy'], [], '', false);
        $subject->_call('resetGlobalsToCurrentRequest', $request);
        $this->assertEquals($_GET, $modifiedGetVars);
        $this->assertEquals($_POST, $modifiedPostVars);
    }
}
