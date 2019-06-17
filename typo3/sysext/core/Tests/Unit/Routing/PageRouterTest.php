<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Routing;

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
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Routing\PageRouter;
use TYPO3\CMS\Core\Routing\PageSlugCandidateProvider;
use TYPO3\CMS\Core\Routing\RouteNotFoundException;
use TYPO3\CMS\Core\Routing\SiteRouteResult;
use TYPO3\CMS\Core\Site\Entity\Site;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PageRouterTest extends UnitTestCase
{
    /**
     * @var bool
     */
    protected $resetSingletonInstances = true;

    /**
     * @test
     */
    public function matchRequestThrowsExceptionIfNoPreviousResultGiven()
    {
        $this->expectException(RouteNotFoundException::class);
        $this->expectExceptionCode(1555303496);
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom';
        $request = new ServerRequest($incomingUrl, 'GET');
        $subject = new PageRouter(new Site('lotus-flower', 13, []));
        $subject->matchRequest($request, null);
    }

    /**
     * @test
     */
    public function properSiteConfigurationFindsRoute()
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

        $pageSlugCandidateProvider = $this->prophesize(PageSlugCandidateProvider::class);
        $pageSlugCandidateProvider->getCandidatesForPath('/mr-magpie/bloom', $language)->willReturn([$pageRecord]);

        $request = new ServerRequest($incomingUrl, 'GET');
        $previousResult = new SiteRouteResult($request->getUri(), $site, $language, '/mr-magpie/bloom');
        $subject = $this->getAccessibleMock(PageRouter::class, ['getSlugCandidateProvider'], [$site, []]);
        $subject->expects($this->once())->method('getSlugCandidateProvider')->willReturn($pageSlugCandidateProvider->reveal());
        $routeResult = $subject->matchRequest($request, $previousResult);

        $expectedRouteResult = new PageArguments(13, '0', [], [], []);
        $this->assertEquals($expectedRouteResult, $routeResult);
    }

    /**
     * Let's see if the slug is "/blabla" and the base does not have a trailing slash ("/en")
     * @test
     */
    public function properSiteConfigurationWithoutTrailingSlashFindsRoute()
    {
        $incomingUrl = 'https://king.com/lotus-flower/en/mr-magpie/bloom';
        $pageRecord = ['uid' => 13, 'l10n_parent' => 0, 'slug' => '/mr-magpie/bloom'];
        $site = new Site('lotus-flower', 13, [
            'base' => '/lotus-flower/',
            'languages' => [
                0 => [
                    'languageId' => 0,
                    'locale' => 'en_US.UTF-8',
                    'base' => '/en'
                ],
            ]
        ]);
        $language = $site->getDefaultLanguage();
        $pageSlugCandidateProvider = $this->prophesize(PageSlugCandidateProvider::class);
        $pageSlugCandidateProvider->getCandidatesForPath(Argument::cetera())->willReturn([$pageRecord]);

        $request = new ServerRequest($incomingUrl, 'GET');
        $previousResult = new SiteRouteResult($request->getUri(), $site, $language, '/mr-magpie/bloom/');
        $subject = $this->getAccessibleMock(PageRouter::class, ['getSlugCandidateProvider'], [$site, []]);
        $subject->expects($this->once())->method('getSlugCandidateProvider')->willReturn($pageSlugCandidateProvider->reveal());
        $routeResult = $subject->matchRequest($request, $previousResult);

        $expectedRouteResult = new PageArguments((int)$pageRecord['uid'], '0', []);
        self::assertEquals($expectedRouteResult, $routeResult);
    }
}
