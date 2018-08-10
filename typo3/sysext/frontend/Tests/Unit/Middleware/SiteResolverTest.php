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
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Site\SiteFinder;
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

    protected $siteFoundRequestHandler;

    /**
     * Set up
     */
    protected function setUp(): void
    {
        // Make global object available, however it is not actively used
        $GLOBALS['TSFE'] = new \stdClass();
        $this->siteFinder = $this->getAccessibleMock(SiteFinder::class, ['dummy'], [], '', false);

        // A request handler which expects a site to be found.
        $this->siteFoundRequestHandler = new class implements RequestHandlerInterface {
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
                            'language-base' => $language->getBase()
                        ]
                    );
                }
                return new NullResponse();
            }
        };
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

        $request = new ServerRequest($incomingUrl, 'GET');
        $subject = new SiteResolver($this->siteFinder);
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        if ($response instanceof NullResponse) {
            $this->fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            $this->assertEquals($siteIdentifier, $result['site']);
            $this->assertEquals(0, $result['language-id']);
            $this->assertEquals('/mysite/', $result['language-base']);
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
            'sub-site' => new Site('sub-site', 13, [
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

        $request = new ServerRequest($incomingUrl, 'GET');
        $subject = new SiteResolver($this->siteFinder);
        $response = $subject->process($request, $this->siteFoundRequestHandler);
        if ($response instanceof NullResponse) {
            $this->fail('No site configuration found in URL ' . $incomingUrl . '.');
        } else {
            $result = $response->getBody()->getContents();
            $result = json_decode($result, true);
            $this->assertEquals('sub-site', $result['site']);
            $this->assertEquals(0, $result['language-id']);
            $this->assertEquals('/mysubsite/', $result['language-base']);
        }
    }
}
