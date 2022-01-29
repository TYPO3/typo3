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

    protected const LANGUAGE_PRESETS = [];

    protected array $coreExtensionsToLoad = ['redirects'];

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
            'regexp capture group with relative target' => [
                'https://acme.com/relative-target-page2',
                301,
                '/page2',
                11,
            ],
            'regexp capture group with relative target - keep query params' => [
                'https://acme.com/relative-target-keep-page2?param1=value1',
                301,
                '/page2?param1=value1',
                12,
            ],
            'regexp capture group with relative target - respect query param' => [
                'https://acme.com/respect-relative-target-page2?param1=subpage',
                301,
                '/page2/subpage',
                13,
            ],
            'regexp capture group with relative target - respect query param and keep them' => [
                'https://acme.com/respect-keep-relative-target-page2?param1=subpage',
                301,
                '/page2/subpage?param1=subpage',
                14,
            ],
            // test for https://forge.typo3.org/issues/89799#note-14
            'regexp relative target redirect with unsafe regexp and without ending $' => [
                'https://acme.com/other-relative-target-with-unsafe-capture-group-new',
                301,
                '/offer-new',
                15,
            ],
            // test for https://forge.typo3.org/issues/89799#note-14
            'regexp redirect with unsafe regexp and without ending $' => [
                'https://acme.com/other-redirect-with-unsafe-capture-group-new',
                301,
                'https://anotherdomain.com/offernew',
                16,
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
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        self::assertIsArray($response->getHeader('X-Redirect-By'));
        self::assertIsArray($response->getHeader('location'));
        self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
    }

    public function samePathWithSameDomainT3TargetDataProvider(): array
    {
        return [
            'flat' => [
                'https://acme.com/flat-samehost-1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'flat - with query parameters' => [
                'https://acme.com/flat-samehost-1?param1=value1&cHash=e0527192caa60a6dac1e30af7cfeaf64',
                'https://acme.com/',
                301,
                'https://acme.com/flat-samehost-1',
                1,
            ],
            'flat keep_query_parameters' => [
                'https://acme.com/flat-samehost-2',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-2?param1=value1&cHash=e0527192caa60a6dac1e30af7cfeaf64',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters' => [
                'https://acme.com/flat-samehost-3',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'flat respect_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                'https://acme.com/flat-samehost-3',
                3,
            ],
            'flat respect_query_parameters and keep_query_parameters' => [
                'https://acme.com/flat-samehost-4',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters and keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-4?param1=value1&cHash=caa2156411affc2d7c8c5169652c6e13',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp' => [
                'https://acme.com/regexp-samehost-1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'regexp - with query parameters' => [
                'https://acme.com/regexp-samehost-1?param1=value1',
                'https://acme.com/',
                301,
                'https://acme.com/regexp-samehost-1',
                5,
            ],
            'regexp keep_query_parameters' => [
                'https://acme.com/regexp-samehost-2',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp keep_query_parameters - with query parameters' => [
                'https://acme.com/regexp-samehost-2?param1=value1&cHash=feced69fa13ce7d3bf0483c21ff03064',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'regexp keep_query_parameters - with query parameters but without cHash' => [
                'https://acme.com/regexp-samehost-2?param1=value1',
                'https://acme.com/',
                301,
                'https://acme.com/regexp-samehost-2?param1=value1&cHash=feced69fa13ce7d3bf0483c21ff03064',
                6,
            ],
            'regexp respect_query_parameters' => [
                'https://acme.com/regexp-samehost-3',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'regexp respect_query_parameters - with query parameters but without cHash' => [
                'https://acme.com/regexp-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                'https://acme.com/regexp-samehost-3',
                7,
            ],
            'same host as external target with query arguments in another order than target should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-3?param1=value1&param2=value2&param3=&cHash=69f1b01feb7ed14b95b85cbc66ee2a3a',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'same host as external target with fragment should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-4',
                'https://acme.com/',
                200,
                null,
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider samePathWithSameDomainT3TargetDataProvider
     */
    public function samePathWithSameDomainT3Target(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathWithSameDomainT3Target.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertIsArray($response->getHeader('X-Redirect-By'));
            self::assertIsArray($response->getHeader('location'));
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }

    public function samePathWithSameDomainAndRelativeTargetDataProvider(): array
    {
        return [
            'flat' => [
                'https://acme.com/flat-samehost-1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'flat - with query parameters' => [
                'https://acme.com/flat-samehost-1?param1=value1&cHash=e0527192caa60a6dac1e30af7cfeaf64',
                'https://acme.com/',
                301,
                '/flat-samehost-1',
                1,
            ],
            'flat keep_query_parameters' => [
                'https://acme.com/flat-samehost-2',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-2?param1=value1&cHash=e0527192caa60a6dac1e30af7cfeaf64',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters' => [
                'https://acme.com/flat-samehost-3',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'flat respect_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                '/flat-samehost-3',
                3,
            ],
            'flat respect_query_parameters and keep_query_parameters' => [
                'https://acme.com/flat-samehost-4',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters and keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-4?param1=value1&cHash=caa2156411affc2d7c8c5169652c6e13',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp' => [
                'https://acme.com/regexp-samehost-1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'regexp - with query parameters' => [
                'https://acme.com/regexp-samehost-1?param1=value1',
                'https://acme.com/',
                301,
                '/regexp-samehost-1',
                5,
            ],
            'regexp keep_query_parameters' => [
                'https://acme.com/regexp-samehost-2',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp keep_query_parameters - with query parameters' => [
                'https://acme.com/regexp-samehost-2?param1=value1&cHash=feced69fa13ce7d3bf0483c21ff03064',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp keep_query_parameters - with query parameters but without cHash' => [
                'https://acme.com/regexp-samehost-2?param1=value1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'regexp respect_query_parameters' => [
                'https://acme.com/regexp-samehost-3',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            // this should redirect and not pass through
            'regexp respect_query_parameters - with query parameters but without cHash' => [
                'https://acme.com/regexp-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                '/regexp-samehost-3',
                7,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider samePathWithSameDomainAndRelativeTargetDataProvider
     */
    public function samePathWithSameDomainAndRelativeTarget(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathWithSameDomainAndRelativeTarget.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertIsArray($response->getHeader('X-Redirect-By'));
            self::assertIsArray($response->getHeader('location'));
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }

    public function samePathRedirectsWithExternalTargetDataProvider(): array
    {
        return [
            'flat' => [
                'https://acme.com/flat-samehost-1',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-1',
                1,
            ],
            'flat - with query parameters' => [
                'https://acme.com/flat-samehost-1?param1=value1&cHash=e0527192caa60a6dac1e30af7cfeaf64',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-1',
                1,
            ],
            'flat keep_query_parameters' => [
                'https://acme.com/flat-samehost-2',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-2',
                2,
            ],
            'flat keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-2?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-2?param1=value1',
                2,
            ],
            // following will not match at all, so it is expected to be resolved with 200
            'flat respect_query_parameters' => [
                'https://acme.com/flat-samehost-3',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-3',
                3,
            ],
            // following will not match at all, so it is expected to be resolved with 200
            'flat respect_query_parameters and keep_query_parameters' => [
                'https://acme.com/flat-samehost-4',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'flat respect_query_parameters and keep_query_parameters - with query parameters' => [
                'https://acme.com/flat-samehost-4?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/flat-samehost-4?param1=value1',
                4,
            ],
            'regexp' => [
                'https://acme.com/regexp-samehost-1',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-1',
                5,
            ],
            'regexp - with query parameters' => [
                'https://acme.com/regexp-samehost-1?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-1',
                5,
            ],
            'regexp keep_query_parameters' => [
                'https://acme.com/regexp-samehost-2',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-2',
                6,
            ],
            'regexp keep_query_parameters - with query parameters' => [
                'https://acme.com/regexp-samehost-2?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-2?param1=value1',
                6,
            ],
            'regexp respect_query_parameters' => [
                'https://acme.com/regexp-samehost-3',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-3',
                7,
            ],
            // this should redirect and not pass through
            'regexp respect_query_parameters - with query parameters but without cHash' => [
                'https://acme.com/regexp-samehost-3?param1=value1',
                'https://acme.com/',
                301,
                'https://external.acme.com/regexp-samehost-3',
                7,
            ],
            'same host as external target with port should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-1',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'same host as external target with userinfo should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-2',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'same host as external target with query arguments in another order than target should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-3?param1=value1&param2=value2&param3=',
                'https://acme.com/',
                200,
                null,
                null,
            ],
            'same host as external target with fragment should pass instead of redirect' => [
                'https://acme.com/sanatize-samehost-4',
                'https://acme.com/',
                200,
                null,
                null,
            ],
        ];
    }

    /**
     * @test
     * @dataProvider samePathRedirectsWithExternalTargetDataProvider
     */
    public function samePathRedirectsWithExternalTarget(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathRedirectsWithExternalTarget.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertIsArray($response->getHeader('X-Redirect-By'));
            self::assertIsArray($response->getHeader('location'));
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }
}
