<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

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

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\RouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Middleware\PageResolver;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageResolverTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var TypoScriptFrontendController|AccessibleObjectInterface
     */
    protected $controller;

    /**
     * @var RequestHandlerInterface
     */
    protected $responseOutputHandler;

    /**
     * @var PageResolver|AccessibleObjectInterface
     */
    protected $subject;

    protected function setUp(): void
    {
        $this->controller = $this->getAccessibleMock(TypoScriptFrontendController::class, ['getSiteScript', 'makeCacheHash', 'determineId', 'isBackendUserLoggedIn'], [], '', false);

        // A request handler which expects a site with some more details are found.
        $this->responseOutputHandler = new class implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var RouteResult $routeResult */
                $routeResult = $request->getAttribute('routing', false);
                if ($routeResult) {
                    return new JsonResponse(
                        [
                            'site' => $routeResult->getSite()->getIdentifier(),
                            'language-id' => $routeResult->getLanguage()->getLanguageId(),
                            'tail' => $routeResult->getTail(),
                            'page' => $routeResult['page']
                        ]
                    );
                }
                return new NullResponse();
            }
        };
    }

    /**
     * @test
     */
    public function properSiteConfigurationLoadsPageRouter()
    {
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom';
        $pageRecord = ['uid' => 13, 'l10n_parent' => 0, 'slug' => '/mr-magpie/bloom'];
        $site = new Site('lotus-flower', 13, [
            'base' => '/lotus-flower/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
            ]
        ]);
        $language = $site->getDefaultLanguage();

        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);
        $request = $request->withAttribute('routing', new RouteResult($request->getUri(), $site, $language, 'mr-magpie/bloom'));

        $expectedRouteResult = new RouteResult($request->getUri(), $site, $language, '', ['page' => $pageRecord]);
        $pageRouterMock = $this->getMockBuilder(PageRouter::class)->disableOriginalConstructor()->setMethods(['matchRoute'])->getMock();
        $pageRouterMock->expects($this->once())->method('matchRoute')->willReturn($expectedRouteResult);

        $subject = $this->getAccessibleMock(PageResolver::class, ['getPageRouter'], [$this->controller], '', true);
        $subject->expects($this->any())->method('getPageRouter')->willReturn($pageRouterMock);
        $response = $subject->process($request, $this->responseOutputHandler);
        $result = $response->getBody()->getContents();
        $result = json_decode($result, true);
        $this->assertEquals('lotus-flower', $result['site']);
        $this->assertEquals($pageRecord, $result['page']);
    }

    /**
     * Ensures that a request with a trailing slash will be redirect to one without a trailing slash because the
     * page slug does have a trailing slash.
     * @test
     */
    public function properSiteConfigurationLoadsPageRouterWithRedirect()
    {
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom/';
        $pageRecord = ['uid' => 13, 'l10n_parent' => 0, 'slug' => '/mr-magpie/bloom'];
        $site = new Site('lotus-flower', 13, [
            'base' => '/lotus-flower/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
            ]
        ]);
        $language = $site->getDefaultLanguage();

        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);
        $request = $request->withAttribute('routing', new RouteResult($request->getUri(), $site, $language, 'mr-magpie/bloom/'));

        $expectedRouteResult = new RouteResult($request->getUri(), $site, $language, '/', ['page' => $pageRecord]);
        $pageRouterMock = $this->getMockBuilder(PageRouter::class)->disableOriginalConstructor()->setMethods(['matchRoute'])->getMock();
        $pageRouterMock->expects($this->once())->method('matchRoute')->willReturn($expectedRouteResult);

        $subject = $this->getAccessibleMock(PageResolver::class, ['getPageRouter'], [$this->controller], '', true);
        $subject->expects($this->any())->method('getPageRouter')->willReturn($pageRouterMock);
        $response = $subject->process($request, $this->responseOutputHandler);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertEquals('https://king.com/lotus-flower/en/mr-magpie/bloom', $response->getHeader('Location')[0]);
    }

    /**
     * Ensures that a request without a trailing slash will be redirect to one with a trailing slash because the
     * page slug does not have a trailing slash.
     * @test
     */
    public function properSiteConfigurationLoadsPageRouterWithRedirectWithoutTrailingSlash()
    {
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom';
        $pageRecord = ['uid' => 13, 'l10n_parent' => 0, 'slug' => '/mr-magpie/bloom/'];
        $site = new Site('lotus-flower', 13, [
            'base' => '/lotus-flower/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
            ]
        ]);
        $language = $site->getDefaultLanguage();

        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);
        $request = $request->withAttribute('routing', new RouteResult($request->getUri(), $site, $language, 'mr-magpie/bloom/'));

        $expectedRouteResult = new RouteResult($request->getUri(), $site, $language, '', ['page' => $pageRecord]);
        $pageRouterMock = $this->getMockBuilder(PageRouter::class)->disableOriginalConstructor()->setMethods(['matchRoute'])->getMock();
        $pageRouterMock->expects($this->once())->method('matchRoute')->willReturn($expectedRouteResult);

        $subject = $this->getAccessibleMock(PageResolver::class, ['getPageRouter'], [$this->controller], '', true);
        $subject->expects($this->any())->method('getPageRouter')->willReturn($pageRouterMock);
        $response = $subject->process($request, $this->responseOutputHandler);
        $this->assertEquals(307, $response->getStatusCode());
        $this->assertEquals('https://king.com/lotus-flower/en/mr-magpie/bloom/', $response->getHeader('Location')[0]);
    }
}
