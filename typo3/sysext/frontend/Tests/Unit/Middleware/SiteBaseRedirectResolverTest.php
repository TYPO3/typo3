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

namespace TYPO3\CMS\Frontend\Tests\Unit\Middleware;

use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Tests\Functional\Fixtures\Frontend\PhpError;
use TYPO3\CMS\Frontend\Middleware\SiteBaseRedirectResolver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteBaseRedirectResolverTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var RequestHandlerInterface
     */
    protected $siteFoundRequestHandler;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        // A request handler which expects a site to be found.
        $this->siteFoundRequestHandler = new class() implements RequestHandlerInterface {
            public function handle(ServerRequestInterface $request): ResponseInterface
            {
                /** @var Site $site */
                /** @var SiteLanguage $language */
                $site = $request->getAttribute('site', false);
                $language = $request->getAttribute('language', false);
                if ($site && $language) {
                    return new JsonResponse(
                        [
                            'site' => $site->getIdentifier(),
                            'language-id' => $language->getLanguageId(),
                            'language-base' => (string)$language->getBase(),
                            'rootpage' => $site->getRootPageId()
                        ]
                    );
                }
                return new NullResponse();
            }
        };
    }

    /**
     * @return array
     */
    public function doRedirectOnMissingOrSuperfluousRequestUrlDataProvider(): array
    {
        $site1 = new Site('outside-site', 13, [
            'base' => 'https://twenty.one/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
                1 => [
                    'languageId' => 1,
                    'locale' => 'fr_CA.UTF-8',
                    'base' => '/fr'
                ]
            ]
        ]);
        $site2 = new Site('sub-site', 14, [
            'base' => 'https://twenty.one/mysubsite/',
            'languages' => [
                2 => [
                    'languageId' => 2,
                    'locale' => 'it_IT.UTF-8',
                    'base' => '/'
                ]
            ]
        ]);

        return [
            'redirect to first language' => [
                'https://twenty.one/',
                'https://twenty.one/en/',
                $site1,
                null,
                ''
            ],
            'redirect to first language adding the slash' => [
                'https://twenty.one/en',
                'https://twenty.one/en/',
                $site1,
                null,
                ''
            ],
            'redirect to second language removing a slash' => [
                'https://twenty.one/fr/',
                'https://twenty.one/fr',
                $site1,
                $site1->getLanguageById(1),
                '/'
            ],
            'redirect to subsite by adding a slash' => [
                'https://twenty.one/mysubsite',
                'https://twenty.one/mysubsite/',
                $site2,
                null,
                ''
            ],
            'redirect to first language and remove nested arguments' => [
                'https://twenty.one/?foo[bar]=foobar&bar=foo',
                'https://twenty.one/en/',
                $site1,
                null,
                ''
            ],
            'redirect to second language removing a slash but keeping the nested arguments' => [
                'https://twenty.one/fr/?foo[bar]=foobar&bar=foo',
                'https://twenty.one/fr?foo%5Bbar%5D=foobar&bar=foo',
                $site1,
                $site1->getLanguageById(1),
                '/'
            ],
        ];
    }

    /**
     * @param string $incomingUrl
     * @param string $expectedRedirectUrl
     * @param Site $site
     * @param SiteLanguage|null $language
     * @param string $tail
     * @dataProvider doRedirectOnMissingOrSuperfluousRequestUrlDataProvider
     * @test
     */
    public function doRedirectOnMissingOrSuperfluousRequestUrl(
        string $incomingUrl,
        string $expectedRedirectUrl,
        Site $site,
        ?SiteLanguage $language,
        string $tail
    ) {
        $routeResult = new SiteRouteResult(new Uri($incomingUrl), $site, $language, $tail);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);
        $request = $request->withAttribute('routing', $routeResult);

        $subject = new SiteBaseRedirectResolver();
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        self::assertEquals(307, $response->getStatusCode());
        self::assertEquals($expectedRedirectUrl, $response->getHeader('Location')[0] ?? '');
    }

    /**
     * @return array
     */
    public function checkIf404IsSiteLanguageIsDisabledInFrontendDataProvider(): array
    {
        return [
            'disabled site language' => ['https://twenty.one/en/pilots/', 404, 0],
            'enabled site language' => ['https://twenty.one/fr/pilots/', 200, 1],
        ];
    }

    /**
     * @param string $url
     * @param int $expectedStatusCode
     * @param int $languageId
     *
     * @test
     * @dataProvider checkIf404IsSiteLanguageIsDisabledInFrontendDataProvider
     */
    public function checkIf404IsSiteLanguageIsDisabledInFrontend(
        string $url,
        int $expectedStatusCode,
        int $languageId
    ) {
        $site = new Site('mixed-site', 13, [
            'base' => '/',
            'errorHandling' => [
                [
                    'errorCode' => 404,
                    'errorHandler' => 'PHP',
                    'errorPhpClassFQCN' => PhpError::class
                ]
            ],
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/',
                    'enabled' => false
                ],
                1 => [
                    'languageId' => 1,
                    'locale' => 'fr_CA.UTF-8',
                    'base' => '/fr/',
                    'enabled' => true
                ]
            ]
        ]);

        // Request to default page
        $request = new ServerRequest($url, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $site->getLanguageById($languageId));
        $subject = new SiteBaseRedirectResolver();
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * @return array
     */
    public function doNotRedirectOnBaseWithoutQueryDataProvider(): array
    {
        $site1 = new Site('outside-site', 13, [
            'base' => 'https://twenty.one/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en/'
                ],
                1 => [
                    'languageId' => 1,
                    'locale' => 'fr_CA.UTF-8',
                    'base' => '/fr'
                ]
            ]
        ]);
        return [
            'no redirect for base' => [
                'https://twenty.one/en/',
                $site1,
                $site1->getLanguageById(0),
                ''
            ],
            'no redirect for base when ID is given' => [
                'https://twenty.one/index.php?id=2',
                $site1,
                $site1->getLanguageById(0),
                ''
            ],
            'no redirect for base and nested arguments' => [
                'https://twenty.one/en/?foo[bar]=foobar&bar=foo',
                $site1,
                $site1->getLanguageById(0),
                ''
            ],
        ];
    }

    /**
     * @param string $incomingUrl
     * @param Site $site
     * @param SiteLanguage|null $language
     * @param string $tail
     * @dataProvider doNotRedirectOnBaseWithoutQueryDataProvider
     * @test
     */
    public function doNotRedirectOnBaseWithoutQuery(
        string $incomingUrl,
        Site $site,
        ?SiteLanguage $language,
        string $tail
    ): void {
        $routeResult = new SiteRouteResult(new Uri($incomingUrl), $site, $language, $tail);
        $request = new ServerRequest($incomingUrl, 'GET');
        $request = $request->withAttribute('site', $site);
        $request = $request->withAttribute('language', $language);
        $request = $request->withAttribute('routing', $routeResult);

        $subject = new SiteBaseRedirectResolver();
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        self::assertEquals(200, $response->getStatusCode());
    }
}
