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

namespace TYPO3\CMS\Redirects\Tests\Functional\Service;

use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RedirectServiceTest extends FunctionalTestCase
{
    use \Prophecy\PhpUnit\ProphecyTrait;
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = ['redirects'];

    protected array $testFilesToDelete = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->setUpBackendUserFromFixture(1);
    }

    protected function tearDown(): void
    {
        foreach ($this->testFilesToDelete as $filename) {
            if (@is_file($filename)) {
                unlink($filename);
            }
        }
        parent::tearDown();
    }

    /**
     * @test
     */
    public function linkForRedirectToAccessRestrictedPageIsBuild(): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/RedirectToAccessRestrictedPages.xml');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );

        $typoscriptFile = Environment::getVarPath() . '/transient/setup.typoscript';
        file_put_contents($typoscriptFile, 'page = PAGE' . PHP_EOL . 'page.typeNum = 0');
        $this->testFilesToDelete[] = $typoscriptFile;
        $this->setUpFrontendRootPage(1, [$typoscriptFile]);

        $logger = $this->prophesize(LoggerInterface::class);
        $frontendUserAuthentication = new FrontendUserAuthentication();
        $frontendUserAuthentication->setLogger($logger->reveal());

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $uri = new Uri('https://acme.com/redirect-to-access-restricted-site');
        $request = $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest($uri))
            ->withAttribute('site', $siteFinder->getSiteByRootPageId(1))
            ->withAttribute('frontend.user', $frontendUserAuthentication)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $linkServiceProphecy = $this->prophesize(LinkService::class);
        $linkServiceProphecy->resolve('t3://page?uid=2')->willReturn(
            [
                'pageuid' => 2,
                'type' => LinkService::TYPE_PAGE,
            ]
        );

        $redirectService = new RedirectService(
            new RedirectCacheService(),
            $linkServiceProphecy->reveal(),
            $siteFinder
        );
        $redirectService->setLogger($logger->reveal());

        // Assert correct redirect is matched
        $redirectMatch = $redirectService->matchRedirect($uri->getHost(), $uri->getPath(), $uri->getQuery());
        self::assertEquals(1, $redirectMatch['uid']);
        self::assertEquals('t3://page?uid=2', $redirectMatch['target']);

        // Ensure we deal with an unauthorized request!
        self::assertFalse(GeneralUtility::makeInstance(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn'));

        // Assert link to access restricted page is build
        $targetUrl = $redirectService->getTargetUrl($redirectMatch, $request);
        self::assertEquals(new Uri('https://acme.com/access-restricted'), $targetUrl);
    }

    public function redirectsDataProvider(): array
    {
        return [
            [
                'https://acme.com/redirect-301',
                301,
                'https://acme.com/',
                1,
            ],
            [
                'https://acme.com/redirect-308',
                308,
                'https://acme.com/page2',
                2,
            ],
            [
                'https://acme.com/redirect-302',
                302,
                'https://www.typo3.org',
                3,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider redirectsDataProvider
     */
    public function checkReponseCodeOnRedirect($url, $statusCode, $targetUrl, $redirectUid): void
    {
        $this->importDataSet(__DIR__ . '/Fixtures/RedirectToPages.xml');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );

        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url)
        );
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertIsArray($response->getHeader('X-Redirect-By'));
        self::assertIsArray($response->getHeader('location'));
        self::assertEquals('TYPO3 Redirect ' . $redirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($targetUrl, $response->getHeader('location')[0]);
    }

    public function checkRegExpRedirectsDataProvider(): array
    {
        return [
            'regexp redirect respecting query parameter but not keeping them' => [
                'https://acme.com/index.php?option=com_content&page=some_page',
                301,
                'https://anotherdomain.com/some_page',
                1,
            ],
            'regexp redirect respecting query parameter and keeping them' => [
                'https://acme.com/index.php?option=com_content2&page=some_page',
                301,
                'https://anotherdomain.com/some_page?option=com_content2&page=some_page',
                2,
            ],
            'regexp redirect not respecting query parameters and not keeping them' => [
                'https://acme.com/some-old-page-others?option=com_content',
                301,
                'https://anotherdomain.com/others',
                3,
            ],
            'regexp redirect not respecting query parameters but keeping them' => [
                'https://acme.com/some-page-others',
                301,
                'https://anotherdomain.com/others',
                4,
            ],
            'regexp redirect not respecting query parameters and not keeping them, with query parameter in request' => [
                'https://acme.com/some-old-page-others?option=com_content',
                301,
                'https://anotherdomain.com/others',
                3,
            ],
            'regexp redirect not respecting query parameters but keeping them, without query parameter in request' => [
                'https://acme.com/some-page-others',
                301,
                'https://anotherdomain.com/others',
                4,
            ],
            // check against unsafe regexp captching group
            'regexp redirect with unsafe captching group, respecting query parameters and not keeping them, with query parameter in request' => [
                'https://acme.com/unsafe-captchinggroup-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others',
                5,
            ],
            // checks against unsafe regexp captching group, but as keeping query parameters this may be undetected,
            // and as such this test acts as counterpart to tests above
            'regexp redirect with unsafe captching group, respecting query parameters but keeping them, with query parameter in request' => [
                'https://acme.com/another-unsafe-captchinggroup-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others?option=com_content',
                6,
            ],
            // check against safe regexp captching group
            'regexp redirect safe captching group, respecting query parameters and not keeping them, with query parameter in request' => [
                'https://acme.com/safe-captchinggroup-not-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others',
                7,
            ],
            // checks against safe regexp captching group
            'regexp redirect safe captching group, respecting query parameters but keeping them, with query parameter in request' => [
                'https://acme.com/another-safe-captchinggroup-not-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others?option=com_content',
                8,
            ],
            // check against more safe regexp captching group - this tests path fallback even with queryparameters in
            // request for non query regexp with $ as end matching in regexp
            'regexp redirect safe captching group, not respecting query parameters and not keeping them, with query parameter in request' => [
                'https://acme.com/more-safe-captchinggroup-not-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others',
                9,
            ],
            'regexp redirect safe captching group, not respecting query parameters but keeping them, with query parameter in request' => [
                'https://acme.com/another-more-safe-captchinggroup-not-matching-queryparameters-others?option=com_content',
                301,
                'https://anotherdomain.com/others?option=com_content',
                10,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider checkRegExpRedirectsDataProvider
     */
    public function checkRegExpRedirects(string $url, int $expectedStatusCode, string $expectedRedirectUri, int $expectedRedirectUid)
    {
        $this->importDataSet(__DIR__ . '/Fixtures/RedirectService_regexp.xml');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url)
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        self::assertIsArray($response->getHeader('X-Redirect-By'));
        self::assertIsArray($response->getHeader('location'));
        self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
    }
}
