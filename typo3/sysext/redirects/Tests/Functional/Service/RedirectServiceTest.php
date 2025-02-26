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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\NullLogger;
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\LinkHandling\LinkService;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScriptFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Page\PageInformationFactory;
use TYPO3\CMS\Redirects\Event\BeforeRedirectMatchDomainEvent;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\CMS\Redirects\Service\RedirectService;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class RedirectServiceTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'Deutsch', 'locale' => 'de_DE.UTF8'],
        'FR' => ['id' => 1, 'title' => 'French', 'locale' => 'fr_FR.UTF8'],
    ];

    protected array $coreExtensionsToLoad = ['redirects'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/redirects/Tests/Functional/Fixtures/Extensions/test_bolt',
    ];

    protected array $configurationToUseInTestInstance = [
        'FE' => [
            'cacheHash' => [
                'excludedParameters' => ['L', 'pk_campaign', 'pk_kwd', 'utm_source', 'utm_medium', 'utm_campaign', 'utm_term', 'utm_content', 'gclid', 'fbclid', 'msclkid'],
                // @todo this should be tested explicitly - enabled and disabled
                'enforceValidation' => false,
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/be_users.csv');
        $this->setUpBackendUser(1);
    }

    protected function tearDown(): void
    {
        // Ensure to remove all SiteConfiguration written by test methods to have a clean instance.
        GeneralUtility::rmdir(Environment::getConfigPath() . '/sites', true);
        parent::tearDown();
    }

    #[Test]
    public function linkForRedirectToAccessRestrictedPageIsBuild(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectToAccessRestrictedPages.csv');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );

        $this->setUpFrontendRootPage(1, ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']);

        $logger = new NullLogger();
        $frontendUserAuthentication = new FrontendUserAuthentication();
        $frontendUserAuthentication->setLogger($logger);

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $uri = new Uri('https://acme.com/redirect-to-access-restricted-site');
        $request = $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest($uri))
            ->withAttribute('site', $siteFinder->getSiteByRootPageId(1))
            ->withAttribute('frontend.user', $frontendUserAuthentication)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        $linkServiceMock = $this->getMockBuilder(LinkService::class)->disableOriginalConstructor()->getMock();
        $linkServiceMock->method('resolve')->with('t3://page?uid=2')->willReturn(
            [
                'pageuid' => 2,
                'type' => LinkService::TYPE_PAGE,
            ]
        );

        /** @var PhpFrontend $typoScriptCache */
        $typoScriptCache = $this->get(CacheManager::class)->getCache('typoscript');
        $redirectService = new RedirectService(
            new RedirectCacheService(),
            $linkServiceMock,
            $siteFinder,
            new NoopEventDispatcher(),
            $this->get(PageInformationFactory::class),
            $this->get(FrontendTypoScriptFactory::class),
            $typoScriptCache,
            $this->get(LogManager::class)->getLogger('Testing'),
        );

        // Assert correct redirect is matched
        $redirectMatch = $redirectService->matchRedirect($uri->getHost(), $uri->getPath(), $uri->getQuery());
        self::assertEquals(1, $redirectMatch['uid']);
        self::assertEquals('t3://page?uid=2', $redirectMatch['target']);

        // Ensure we deal with an unauthorized request!
        self::assertFalse($this->get(Context::class)->getPropertyFromAspect('frontend.user', 'isLoggedIn'));

        // Assert link to access restricted page is build
        $targetUrl = $redirectService->getTargetUrl($redirectMatch, $request);
        self::assertEquals(new Uri('https://acme.com/access-restricted'), $targetUrl);
    }

    public static function redirectsDataProvider(): array
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

    #[DataProvider('redirectsDataProvider')]
    #[Test]
    public function checkReponseCodeOnRedirect($url, $statusCode, $targetUrl, $redirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectToPages.csv');

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );

        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url)
        );
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertEquals('TYPO3 Redirect ' . $redirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($targetUrl, $response->getHeader('location')[0]);
    }

    public static function checkRegExpRedirectsDataProvider(): array
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
                '//acme.com/page2',
                11,
            ],
            'regexp capture group with relative target - keep query params' => [
                'https://acme.com/relative-target-keep-page2?param1=value1',
                301,
                '//acme.com/page2?param1=value1',
                12,
            ],
            'regexp capture group with relative target - respect query param' => [
                'https://acme.com/respect-relative-target-page2?param1=subpage',
                301,
                '//acme.com/page2/subpage',
                13,
            ],
            'regexp capture group with relative target - respect query param and keep them' => [
                'https://acme.com/respect-keep-relative-target-page2?param1=subpage',
                301,
                '//acme.com/page2/subpage?param1=subpage',
                14,
            ],
            // test for https://forge.typo3.org/issues/89799#note-14
            'regexp relative target redirect with unsafe regexp and without ending $' => [
                'https://acme.com/other-relative-target-with-unsafe-capture-group-new',
                301,
                '//acme.com/offer-new',
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

    #[DataProvider('checkRegExpRedirectsDataProvider')]
    #[Test]
    public function checkRegExpRedirects(string $url, int $expectedStatusCode, string $expectedRedirectUri, int $expectedRedirectUid)
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_regexp.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
    }

    /**
     * @see https://forge.typo3.org/issues/101739
     */
    #[Test]
    public function regexpWithNoParamRegexpAndRespectingGetParameteresIssuesNotFoundStatusIfParamsAreGivenInUrl(): void
    {
        $url = 'https://acme.com/regexp-respect-get-parameter?param1=value1';
        $expectedStatusCode = 404;
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_regexp.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertSame($expectedStatusCode, $response->getStatusCode());
    }

    /**
     * @see https://forge.typo3.org/issues/101739
     */
    #[Test]
    public function regexpWithNoParamRegexpAndRespectingGetParameteresRedirectsIfNoParamsAreGiven(): void
    {
        $url = 'https://acme.com/regexp-respect-get-parameter';
        $expectedStatusCode = 301;
        $expectedRedirectUid = 17;
        $expectedRedirectUri = 'https://anotherdomain.com/regexp-respect-get-parameter';
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_regexp.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertSame($expectedStatusCode, $response->getStatusCode());
        self::assertSame('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertSame($expectedRedirectUri, $response->getHeader('location')[0]);
    }

    public static function samePathWithSameDomainT3TargetDataProvider(): array
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

    #[DataProvider('samePathWithSameDomainT3TargetDataProvider')]
    #[Test]
    public function samePathWithSameDomainT3Target(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathWithSameDomainT3Target.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }

    public static function samePathWithSameDomainAndRelativeTargetDataProvider(): array
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
                '//acme.com/flat-samehost-1',
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
                '//acme.com/flat-samehost-3',
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
                '//acme.com/regexp-samehost-1',
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
                '//acme.com/regexp-samehost-3',
                7,
            ],
        ];
    }

    #[DataProvider('samePathWithSameDomainAndRelativeTargetDataProvider')]
    #[Test]
    public function samePathWithSameDomainAndRelativeTarget(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathWithSameDomainAndRelativeTarget.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }

    public static function samePathRedirectsWithExternalTargetDataProvider(): array
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

    #[DataProvider('samePathRedirectsWithExternalTargetDataProvider')]
    #[Test]
    public function samePathRedirectsWithExternalTarget(string $url, string $baseUri, int $expectedStatusCode, ?string $expectedRedirectUri, ?int $expectedRedirectUid): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_samePathRedirectsWithExternalTarget.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, $baseUri)
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url),
            null,
            false
        );
        self::assertEquals($expectedStatusCode, $response->getStatusCode());
        if ($expectedRedirectUri) {
            self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
            self::assertEquals($expectedRedirectUri, $response->getHeader('location')[0]);
        }
    }

    #[Test]
    public function beforeRedirectMatchDomainEventIsTriggered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/BeforeRedirectMatchDomainEventIsTriggered.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );

        $frontendUserAuthentication = new FrontendUserAuthentication();

        $siteFinder = GeneralUtility::makeInstance(SiteFinder::class);
        $uri = new Uri('https://acme.com/non-existing-page');
        $GLOBALS['TYPO3_REQUEST'] = (new ServerRequest($uri))
            ->withAttribute('site', $siteFinder->getSiteByRootPageId(1))
            ->withAttribute('frontend.user', $frontendUserAuthentication)
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE);

        /** @var BeforeRedirectMatchDomainEvent[] $dispatchedEvents */
        $dispatchedEvents = [];
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'before-redirect-match-domain-event-is-triggered',
            static function (BeforeRedirectMatchDomainEvent $event) use (&$dispatchedEvents): void {
                $dispatchedEvents[] = $event;
                if ($event->getMatchDomainName() === '*') {
                    $event->setMatchedRedirect(['wildcard-manual-matched' => $event->getPath()]);
                }
            }
        );

        $eventListener = $container->get(ListenerProvider::class);
        $eventListener->addListener(BeforeRedirectMatchDomainEvent::class, 'before-redirect-match-domain-event-is-triggered');

        /** @var PhpFrontend $typoScriptCache */
        $typoScriptCache = $this->get(CacheManager::class)->getCache('typoscript');
        $redirectService = new RedirectService(
            new RedirectCacheService(),
            new LinkService(),
            $siteFinder,
            $this->get(EventDispatcherInterface::class),
            $this->get(PageInformationFactory::class),
            $this->get(FrontendTypoScriptFactory::class),
            $typoScriptCache,
            $this->get(LogManager::class)->getLogger('Testing'),
        );

        $redirectMatch = $redirectService->matchRedirect($uri->getHost(), $uri->getPath(), $uri->getQuery());

        self::assertCount(2, $dispatchedEvents);
        self::assertNull($dispatchedEvents[0]->getMatchedRedirect());
        self::assertEquals(['wildcard-manual-matched' => $uri->getPath()], $dispatchedEvents[1]->getMatchedRedirect());
        self::assertSame(['wildcard-manual-matched' => $uri->getPath()], $redirectMatch);
    }

    public static function regExpRedirectsWithArgumentMatchesWithSimilarRegExpWithoutQueryParamInRecordDataProvider(): \Generator
    {
        // Regression test for https://forge.typo3.org/issues/101191
        yield '#1 Non-query argument regex redirect not respecting get arguments before query-argument regex does not match before query-argument regex' => [
            'importDataSet' => __DIR__ . '/Fixtures/RegExp/case1.csv',
            'url' => 'https://acme.com/foo/lightbar.html?type=101',
            'statusCode' => 301,
            'redirectUid' => 2,
            'targetUrl' => 'https://acme.com/page3?type=101',
        ];

        yield '#2 Non-query argument regex redirect respecting get arguments before query-argument regex does not match before query-argument regex' => [
            'importDataSet' => __DIR__ . '/Fixtures/RegExp/case2.csv',
            'url' => 'https://acme.com/foo/lightbar.html?type=101',
            'statusCode' => 301,
            'redirectUid' => 2,
            'targetUrl' => 'https://acme.com/page3?type=101',
        ];

        // Redirect respecting query arguments but has a too open regexp provided and matching takes precedence over
        // a later redirect with a "better" match. This is a configuration error and therefore the correct way to handle
        // this case. For example missing trailing `$` or leaving the `respect_query_parameters` option unchecked would
        // mitigate this.
        yield '#3 To open non-query argument regex redirect respecting get arguments before query-argument regex proceeds query-argument regex' => [
            'importDataSet' => __DIR__ . '/Fixtures/RegExp/case3.csv',
            'url' => 'https://acme.com/foo/lightbar.html?type=101',
            'statusCode' => 301,
            'redirectUid' => 1,
            'targetUrl' => 'https://acme.com/page2',
        ];
    }

    #[DataProvider('regExpRedirectsWithArgumentMatchesWithSimilarRegExpWithoutQueryParamInRecordDataProvider')]
    #[Test]
    public function regExpRedirectsWithArgumentMatchesWithSimilarRegExpWithoutQueryParamInRecord(
        string $importDataSet,
        string $url,
        int $statusCode,
        int $redirectUid,
        string $targetUrl
    ): void {
        $this->importCSVDataSet($importDataSet);
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        $this->setUpFrontendRootPage(
            1,
            ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']
        );

        $response = $this->executeFrontendSubRequest(
            new InternalRequest($url)
        );
        self::assertEquals($statusCode, $response->getStatusCode());
        self::assertEquals('TYPO3 Redirect ' . $redirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($targetUrl, $response->getHeader('location')[0]);
    }

    public static function sourceHostNotNotContainedInAnySiteConfigRedirectIsRedirectedDataProvider(): \Generator
    {
        yield 'non-configured source_host with site rootpage target using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-pid1'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => false,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 1,
            'expectedRedirectLocationUri' => 'https://acme.com/',
        ];
        yield 'non-configured source_host with site sub-page target using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-pid2'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => false,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 2,
            'expectedRedirectLocationUri' => 'https://acme.com/page2',
        ];
        // Regression test for https://forge.typo3.org/issues/103395
        yield 'non-configured source_host with site root target without typoscript using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-pid1'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => true,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 1,
            'expectedRedirectLocationUri' => 'https://acme.com/',
        ];
        // Not configured source host and matched redirect to external page
        yield 'non-configured source_host without tailing slash with external target without TypoScript using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-external'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => true,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 3,
            'expectedRedirectLocationUri' => 'https://external.domain.tld/',
        ];
        yield 'non-configured source_host with tailing slash with external target without TypoScript using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-external/'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => true,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 3,
            'expectedRedirectLocationUri' => 'https://external.domain.tld/',
        ];
        yield 'non-configured source_host without tailing slash with external target with using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-external'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => false,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 3,
            'expectedRedirectLocationUri' => 'https://external.domain.tld/',
        ];
        yield 'non-configured source_host with tailing slash with external target with TypoScript using T3 LinkHandler syntax' => [
            'request' => new InternalRequest('https://non-configured.domain.tld/redirect-to-external/'),
            'rootPageTypoScriptFiles' => ['setup' => ['EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript']],
            'useTestBolt' => false,
            'expectedRedirectStatusCode' => 301,
            'expectedRedirectUid' => 3,
            'expectedRedirectLocationUri' => 'https://external.domain.tld/',
        ];
    }

    /**
     * @param array{constants?: string[], setup?: string[]} $rootPageTypoScriptFiles
     */
    #[DataProvider('sourceHostNotNotContainedInAnySiteConfigRedirectIsRedirectedDataProvider')]
    #[Test]
    public function sourceHostNotNotContainedInAnySiteConfigRedirectIsRedirected(
        InternalRequest $request,
        array $rootPageTypoScriptFiles,
        bool $useTestBolt,
        int $expectedRedirectStatusCode,
        int $expectedRedirectUid,
        string $expectedRedirectLocationUri,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SourceHostWithoutSourceConfigRedirect.csv');
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/')
        );
        if ($useTestBolt === true) {
            $constants = '';
            foreach ($rootPageTypoScriptFiles['constants'] ?? [] as $typoScriptFile) {
                $constants .= '@import \'' . $typoScriptFile . '\'' . LF;
            }
            $setup = '';
            foreach ($rootPageTypoScriptFiles['setup'] ?? [] as $typoScriptFile) {
                $setup .= '@import \'' . $typoScriptFile . '\'' . LF;
            }
            $this->mergeSiteConfiguration('acme-com', [
                'test_bolt_enabled' => true,
                'test_bolt_constants' => $constants,
                'test_bolt_setup' => $setup,
            ]);
            $connection = $this->getConnectionPool()->getConnectionForTable('pages');
            $connection->update(
                'pages',
                ['is_siteroot' => 1],
                ['uid' => 1]
            );
        } else {
            $this->setUpFrontendRootPage(1, $rootPageTypoScriptFiles);
        }

        $response = $this->executeFrontendSubRequest($request);
        self::assertEquals($expectedRedirectStatusCode, $response->getStatusCode());
        self::assertEquals('TYPO3 Redirect ' . $expectedRedirectUid, $response->getHeader('X-Redirect-By')[0]);
        self::assertEquals($expectedRedirectLocationUri, $response->getHeader('location')[0]);
    }

    public static function redirectToTargetPageInOtherSiteRouteWorksAsExpectedDataProvider(): \Generator
    {
        // domain based language distinction
        yield 'site1 matched default language redirects to site2 target on default language for wildcard redirect' => [
            'baseSiteOne' => 'https://site1.acme.com/',
            'baseSiteOneDefaultLanguage' => 'https://site1.acme.com/',
            'baseSiteOneFirstLanguage' => 'https://site1.acme.de/',
            'baseSiteTwo' => 'https://site2.acme.com/',
            'baseSiteTwoDefaultLanguage' => 'https://site2.acme.com/',
            'baseSiteTwoFirstLanguage' => 'https://site2.acme.de/',
            'baseSiteTwoFirstLanguageIdentifier' => 'DE',
            'uri' => 'https://site1.acme.com/wildcard-source-host/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://site2.acme.com/page-1',
            'expectedMatchedRedirectRecordUid' => 1,
        ];
        // @todo This should be investigated. Do we really want to support this ? Same languageId is not garuanteed to
        //       be the same language. On the other hand, reusing the same languageId in multiple SiteConfigurations
        //       is used in some setup, but should this not be considered as an invalid setup/conflict ?
        yield 'site1 matched first language redirects to site2 target on first language for wildcard redirect when langaugeId is the same' => [
            'baseSiteOne' => 'https://site1.acme.com/',
            'baseSiteOneDefaultLanguage' => 'https://site1.acme.com/',
            'baseSiteOneFirstLanguage' => 'https://site1.acme.de/',
            'baseSiteTwo' => 'https://site2.acme.com/',
            'baseSiteTwoDefaultLanguage' => 'https://site2.acme.com/',
            'baseSiteTwoFirstLanguage' => 'https://site2.acme.fr/',
            'baseSiteTwoFirstLanguageIdentifier' => 'FR',
            'uri' => 'https://site1.acme.de/wildcard-source-host/',
            'expectedRedirectStatusCode' => 302,
            // @todo Consider if expected redirect uri should not be:
            //       'https://site2.acme.fr/page-1'
            'expectedRedirectUri' => 'https://site2.acme.fr/page-1-translated',
            'expectedMatchedRedirectRecordUid' => 1,
        ];
    }

    #[DataProvider('redirectToTargetPageInOtherSiteRouteWorksAsExpectedDataProvider')]
    #[Test]
    public function redirectToTargetPageInOtherSiteRouteWorksAsExpected(
        string $baseSiteOne,
        string $baseSiteOneDefaultLanguage,
        string $baseSiteOneFirstLanguage,
        string $baseSiteTwo,
        string $baseSiteTwoDefaultLanguage,
        string $baseSiteTwoFirstLanguage,
        string $baseSiteTwoFirstLanguageIdentifier,
        string $uri,
        int $expectedRedirectStatusCode,
        string $expectedRedirectUri,
        int $expectedMatchedRedirectRecordUid,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_targetPageInDifferentSiteRootWithTranslations.csv');
        $this->writeSiteConfiguration(
            'acme-one',
            $this->buildSiteConfiguration(1, $baseSiteOne),
            [
                $this->buildDefaultLanguageConfiguration('EN', $baseSiteOneDefaultLanguage),
                $this->buildLanguageConfiguration('DE', $baseSiteOneFirstLanguage),
            ]
        );
        $this->writeSiteConfiguration(
            'acme-two',
            $this->buildSiteConfiguration(3, $baseSiteTwo),
            [
                $this->buildDefaultLanguageConfiguration('EN', $baseSiteTwoDefaultLanguage),
                $this->buildLanguageConfiguration($baseSiteTwoFirstLanguageIdentifier, $baseSiteTwoFirstLanguage),
            ]
        );
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => [],
                'setup' => [
                    'EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript',
                ],
            ]
        );
        $this->setUpFrontendRootPage(
            3,
            [
                'constants' => [],
                'setup' => [
                    'EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript',
                ],
            ]
        );
        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            new InternalRequestContext(),
            false,
        );
        self::assertSame($expectedRedirectStatusCode, $response->getStatusCode());
        self::assertSame($expectedRedirectUri, ($response->getHeader('location')[0] ?? ''));
        self::assertSame('TYPO3 Redirect ' . $expectedMatchedRedirectRecordUid, ($response->getHeader('X-Redirect-By')[0] ?? ''));
    }

    public static function redirectRespectingMatchedSiteLanguageWhenTargetPageHasSameSiteRootDataProvider(): \Generator
    {
        // domain based language distinction
        yield 'wildcard source domain redirects to target on the matched site language (default language)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.de/',
            'uri' => 'https://www.acme.com/wildcard-source-host/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.com/page-1',
            'expectedMatchedRedirectRecordUid' => 1,
        ];
        yield 'wildcard source domain redirects to target on the matched site language (language 1)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.de/',
            'uri' => 'https://www.acme.de/wildcard-source-host/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.de/page-1-translated',
            'expectedMatchedRedirectRecordUid' => 1,
        ];
        yield 'default language source domain redirects to target on the matched site language (default language)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.de/',
            'uri' => 'https://www.acme.com/default-language-source-host/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.com/page-2',
            'expectedMatchedRedirectRecordUid' => 2,
        ];
        yield 'first language source domain redirects to target on the matched site language (first language)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.de/',
            'uri' => 'https://www.acme.de/first-language-source-host/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.de/page-3-translated',
            'expectedMatchedRedirectRecordUid' => 3,
        ];
        // language prefixed path language distinction
        yield 'wildcard source domain with language prefixed path redirects to target on the matched site language (default language)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.com/de/',
            'uri' => 'https://www.acme.com/language-prefixed-path/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.com/page-1',
            'expectedMatchedRedirectRecordUid' => 4,
        ];
        yield 'wildcard source domain with language prefixed path redirects to target on the matched site language (language 1)' => [
            'baseSite' => 'https://www.acme.com/',
            'baseSiteDefaultLanguage' => 'https://www.acme.com/',
            'baseSiteFirstLanguage' => 'https://www.acme.com/de/',
            'uri' => 'https://www.acme.com/de/language-prefixed-path/',
            'expectedRedirectStatusCode' => 302,
            'expectedRedirectUri' => 'https://www.acme.com/de/page-1-translated',
            'expectedMatchedRedirectRecordUid' => 5,
        ];
    }

    #[DataProvider('redirectRespectingMatchedSiteLanguageWhenTargetPageHasSameSiteRootDataProvider')]
    #[Test]
    public function redirectRespectingMatchedSiteLanguageWhenTargetPageHasSameSiteRootReturnsExpectedRedirect(
        string $baseSite,
        string $baseSiteDefaultLanguage,
        string $baseSiteFirstLanguage,
        string $uri,
        int $expectedRedirectStatusCode,
        string $expectedRedirectUri,
        int $expectedMatchedRedirectRecordUid,
    ): void {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectService_targetPageInSameSiteRootWithTranslations.csv');
        $this->writeSiteConfiguration(
            'acme',
            $this->buildSiteConfiguration(1, $baseSite),
            [
                $this->buildDefaultLanguageConfiguration('EN', $baseSiteDefaultLanguage),
                $this->buildLanguageConfiguration('DE', $baseSiteFirstLanguage),
            ]
        );
        $this->setUpFrontendRootPage(
            1,
            [
                'constants' => [],
                'setup' => [
                    'EXT:redirects/Tests/Functional/Service/Fixtures/Redirects.typoscript',
                ],
            ]
        );
        $response = $this->executeFrontendSubRequest(
            new InternalRequest($uri),
            new InternalRequestContext(),
            false,
        );
        self::assertSame($expectedRedirectStatusCode, $response->getStatusCode());
        self::assertSame($expectedRedirectUri, ($response->getHeader('location')[0] ?? ''));
        self::assertSame('TYPO3 Redirect ' . $expectedMatchedRedirectRecordUid, ($response->getHeader('X-Redirect-By')[0] ?? ''));
    }
}
