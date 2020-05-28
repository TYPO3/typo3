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

namespace TYPO3\CMS\Seo\Tests\Functional\Canonical;

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Domain\Repository\PageRepository;
use TYPO3\CMS\Core\Routing\PageArguments;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\CMS\Seo\Canonical\CanonicalGenerator;

/**
 * Test case
 */
class CanonicalGeneratorTest extends AbstractTestCase
{
    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core', 'frontend', 'seo'
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:seo/Tests/Functional/Fixtures/pages-canonical.xml');
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/')
        );
        $_SERVER['HTTP_HOST'] = 'localhost';
        $_SERVER['REQUEST_URI'] = '/';
        GeneralUtility::flushInternalRuntimeCaches();
    }

    protected function initTypoScriptFrontendController(int $uid): TypoScriptFrontendController
    {
        $site = GeneralUtility::makeInstance(SiteFinder::class)->getSiteByIdentifier('website-local');
        $typoScriptFrontendController = new TypoScriptFrontendController(
            GeneralUtility::makeInstance(Context::class),
            $site,
            $site->getDefaultLanguage(),
            new PageArguments($uid, '0', []),
            GeneralUtility::makeInstance(FrontendUserAuthentication::class)
        );
        $typoScriptFrontendController->cObj = new ContentObjectRenderer();
        $typoScriptFrontendController->cObj->setLogger(new NullLogger());
        $typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $typoScriptFrontendController->getPageAndRootlineWithDomain(1);
        return $typoScriptFrontendController;
    }

    public function generateDataProvider(): array
    {
        return [
            'uid: 1 with canonical_link' => [1, '<link rel="canonical" href="http://localhost/"/>' . chr(10)],
            'uid: 2 with canonical_link' => [2, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . chr(10)],
            'uid: 3 with canonical_link AND content_from_pid = 2' => [3, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . chr(10)],
            'uid: 4 without canonical_link AND content_from_pid = 2' => [4, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . chr(10)],
            'uid: 5 without canonical_link AND without content_from_pid set' => [5, '<link rel="canonical" href="http://localhost/dummy-1-2-5"/>' . chr(10)],
            'uid: 6 without canonical_link AND content_from_pid = 7 (but target page is deleted)' => [6, '<link rel="canonical" href="http://localhost/dummy-1-2-6"/>' . chr(10)],
            'uid: 8 without canonical_link AND content_from_pid = 9 (but target page is hidden)' => [8, '<link rel="canonical" href="http://localhost/dummy-1-2-8"/>' . chr(10)],
            'uid: 10 no index' => [10, ''],
        ];
    }

    /**
     * @test
     * @dataProvider generateDataProvider
     * @param int $uid
     * @param string $expectedCanonicalUrl
     */
    public function generate(int $uid, string $expectedCanonicalUrl): void
    {
        $typoScriptFrontendController = $this->initTypoScriptFrontendController($uid);
        self::assertSame($expectedCanonicalUrl, (new CanonicalGenerator($typoScriptFrontendController))->generate());
    }
}
