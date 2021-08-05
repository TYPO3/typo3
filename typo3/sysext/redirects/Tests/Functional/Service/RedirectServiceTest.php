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
                'type' => LinkService::TYPE_PAGE
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
}
