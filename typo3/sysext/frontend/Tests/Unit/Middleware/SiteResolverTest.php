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
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\JsonResponse;
use TYPO3\CMS\Core\Http\NullResponse;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\SiteMatcher;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Middleware\SiteResolver;
use TYPO3\TestingFramework\Core\AccessibleObjectInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteResolverTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /**
     * @var SiteFinder|AccessibleObjectInterface
     */
    protected $siteFinder;

    /**
     * @var RequestHandlerInterface
     */
    protected $siteFoundRequestHandler;

    /**
     * @var string
     */
    protected $originalLocale;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->originalLocale = setlocale(LC_COLLATE, 0);
        $this->siteFinder = $this->getAccessibleMock(SiteFinder::class, ['dummy'], [], '', false);

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

        $cacheManagerProphecy = $this->prophesize(CacheManager::class);
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManagerProphecy->reveal());
    }

    protected function tearDown(): void
    {
        // restore locale to original setting
        setlocale(LC_COLLATE, $this->originalLocale);
        setlocale(LC_MONETARY, $this->originalLocale);
        setlocale(LC_TIME, $this->originalLocale);
        parent::tearDown();
    }

    /**
     * Expect a URL handed in, as a request. This URL does not have a GET parameter "id"
     * Then the site handling gets triggered, and the URL is taken to resolve a site.
     *
     * This case tests against a site with no domain or scheme, and successfully finds it.
     *
     * @test
     */
    public function detectASingleSiteWhenProperRequestIsGiven()
    {
        $incomingUrl = 'https://a-random-domain.com/mysite/';
        $siteIdentifier = 'full-site';
        $this->siteFinder->_set('sites', [
            $siteIdentifier => new Site($siteIdentifier, 13, [
                'base' => '/mysite/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ])
        ]);

        $subject = new SiteResolver(new SiteMatcher($this->siteFinder));

        $request = new ServerRequest($incomingUrl, 'GET');
        $response = $subject->process($request, $this->siteFoundRequestHandler);

        if ($response instanceof NullResponse) {
            self::fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            self::assertEquals($siteIdentifier, $result['site']);
            self::assertEquals(0, $result['language-id']);
            self::assertEquals('/mysite/', $result['language-base']);
        }
    }

    /**
     * Scenario with two sites
     * Site 1: /
     * Site 2: /mysubsite/
     *
     * The result should be that site 2 is resolved by the router when calling
     *
     * www.random-result.com/mysubsite/you-know-why/
     *
     * @test
     */
    public function detectSubsiteInsideNestedUrlStructure()
    {
        $incomingUrl = 'https://www.random-result.com/mysubsite/you-know-why/';
        $this->siteFinder->_set('sites', [
            'outside-site' => new Site('outside-site', 13, [
                'base' => '/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
            'sub-site' => new Site('sub-site', 15, [
                'base' => '/mysubsite/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
        ]);

        $subject = new SiteResolver(new SiteMatcher($this->siteFinder));

        $request = new ServerRequest($incomingUrl, 'GET');
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        if ($response instanceof NullResponse) {
            self::fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            self::assertEquals('sub-site', $result['site']);
            self::assertEquals(0, $result['language-id']);
            self::assertEquals('/mysubsite/', $result['language-base']);
        }
    }

    public function detectSubSubsiteInsideNestedUrlStructureDataProvider()
    {
        return [
            'matches second site' => [
                'https://www.random-result.com/mysubsite/you-know-why/',
                'sub-site',
                14,
                '/mysubsite/'
            ],
            'matches third site' => [
                'https://www.random-result.com/mysubsite/micro-site/oh-yes-you-do/',
                'subsub-site',
                15,
                '/mysubsite/micro-site/'
            ],
            'matches a subsite in first site' => [
                'https://www.random-result.com/products/pampers/',
                'outside-site',
                13,
                '/'
            ],
        ];
    }

    /**
     * Scenario with three sites
     * Site 1: /
     * Site 2: /mysubsite/
     * Site 3: /mysubsite/micro-site/
     *
     * The result should be that site 2 is resolved by the router when calling
     *
     * www.random-result.com/mysubsite/you-know-why/
     *
     * and site 3 when calling
     * www.random-result.com/mysubsite/micro-site/oh-yes-you-do/
     *
     * @test
     * @dataProvider detectSubSubsiteInsideNestedUrlStructureDataProvider
     */
    public function detectSubSubsiteInsideNestedUrlStructure($incomingUrl, $expectedSiteIdentifier, $expectedRootPageId, $expectedBase)
    {
        $this->siteFinder->_set('sites', [
            'outside-site' => new Site('outside-site', 13, [
                'base' => '/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
            'sub-site' => new Site('sub-site', 14, [
                'base' => '/mysubsite/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
            'subsub-site' => new Site('subsub-site', 15, [
                'base' => '/mysubsite/micro-site/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'fr_FR.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
        ]);

        $subject = new SiteResolver(new SiteMatcher($this->siteFinder));

        $request = new ServerRequest($incomingUrl, 'GET');
        $response = $subject->process($request, $this->siteFoundRequestHandler);

        if ($response instanceof NullResponse) {
            self::fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            self::assertEquals($expectedSiteIdentifier, $result['site']);
            self::assertEquals($expectedRootPageId, $result['rootpage']);
            self::assertEquals($expectedBase, $result['language-base']);
        }
    }

    public function detectProperLanguageByIncomingUrlDataProvider()
    {
        return [
            'matches second site' => [
                'https://www.random-result.com/mysubsite/you-know-why/',
                'sub-site',
                14,
                2,
                '/mysubsite/'
            ],
            'matches second site in other language' => [
                'https://www.random-result.com/mysubsite/it/you-know-why/',
                'sub-site',
                14,
                2,
                '/mysubsite/'
            ],
            'matches third site' => [
                'https://www.random-result.com/mysubsite/micro-site/ru/oh-yes-you-do/',
                'subsub-site',
                15,
                13,
                '/mysubsite/micro-site/ru/'
            ],
            'matches a subpage in first site' => [
                'https://www.random-result.com/en/products/pampers/',
                'outside-site',
                13,
                0,
                '/en/'
            ],
            'matches a subpage with translation in first site' => [
                'https://www.random-result.com/fr/products/pampers/',
                'outside-site',
                13,
                1,
                '/fr/'
            ],
        ];
    }

    /**
     * Scenario with three one site and three languages
     * Site 1: /
     *     Language 0: /en/
     *     Language 1: /fr/
     * Site 2: /mysubsite/
     *     Language: 2: /
     * Site 3: /mysubsite/micro-site/
     *     Language: 13: /ru/
     *
     * @test
     * @dataProvider detectProperLanguageByIncomingUrlDataProvider
     */
    public function detectProperLanguageByIncomingUrl($incomingUrl, $expectedSiteIdentifier, $expectedRootPageId, $expectedLanguageId, $expectedBase)
    {
        $this->siteFinder->_set('sites', [
            'outside-site' => new Site('outside-site', 13, [
                'base' => '/',
                'languages' => [
                    0 => [
                        'languageId' => 0,
                        'locale' => 'en_US.UTF-8',
                        'base' => '/en/'
                    ],
                    1 => [
                        'languageId' => 1,
                        'locale' => 'fr_CA.UTF-8',
                        'base' => '/fr/'
                    ]
                ]
            ]),
            'sub-site' => new Site('sub-site', 14, [
                'base' => '/mysubsite/',
                'languages' => [
                    2 => [
                        'languageId' => 2,
                        'locale' => 'it_IT.UTF-8',
                        'base' => '/'
                    ]
                ]
            ]),
            'subsub-site' => new Site('subsub-site', 15, [
                'base' => '/mysubsite/micro-site/',
                'languages' => [
                    13 => [
                        'languageId' => 13,
                        'locale' => 'ru_RU.UTF-8',
                        'base' => '/ru/'
                    ]
                ]
            ]),
        ]);

        $subject = new SiteResolver(new SiteMatcher($this->siteFinder));

        $request = new ServerRequest($incomingUrl, 'GET');
        $response = $subject->process($request, $this->siteFoundRequestHandler);

        if ($response instanceof NullResponse) {
            self::fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            self::assertEquals($expectedSiteIdentifier, $result['site']);
            self::assertEquals($expectedRootPageId, $result['rootpage']);
            self::assertEquals($expectedLanguageId, $result['language-id']);
            self::assertEquals($expectedBase, $result['language-base']);
        }
    }
}
