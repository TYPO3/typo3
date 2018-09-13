<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Seo\Tests\Functional\Canonical;

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

use Psr\Log\NullLogger;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\CMS\Frontend\Page\PageRepository;
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
    }

    protected function initTypoScriptFrontendController(int $uid): TypoScriptFrontendController
    {
        $typoScriptFrontendController = new TypoScriptFrontendController(null, $uid, 0);
        $typoScriptFrontendController->cObj = new ContentObjectRenderer();
        $typoScriptFrontendController->cObj->setLogger(new NullLogger());
        $typoScriptFrontendController->sys_page = GeneralUtility::makeInstance(PageRepository::class);
        $typoScriptFrontendController->tmpl = GeneralUtility::makeInstance(TemplateService::class);
        $typoScriptFrontendController->getPageAndRootlineWithDomain(1);
        $GLOBALS['TSFE'] = $typoScriptFrontendController;
        return $typoScriptFrontendController;
    }

    public function generateDataProvider(): array
    {
        return [
            'uid: 1 with canonical_link' => [1, '<link rel="canonical" href="http://localhost/"/>' . LF],
            'uid: 2 with canonical_link' => [2, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . LF],
            'uid: 3 with canonical_link AND content_from_pid = 2' => [3, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . LF],
            'uid: 4 without canonical_link AND content_from_pid = 2' => [4, '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . LF],
            'uid: 5 without canonical_link AND without content_from_pid set' => [5, '<link rel="canonical" href="http://localhost/?id=5"/>' . LF],
            'uid: 6 without canonical_link AND content_from_pid = 7 (but target page is deleted)' => [6, '<link rel="canonical" href="http://localhost/?id=6"/>' . LF],
            'uid: 8 without canonical_link AND content_from_pid = 9 (but target page is hidden)' => [8, '<link rel="canonical" href="http://localhost/?id=8"/>' . LF],
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
        $this->assertSame($expectedCanonicalUrl, (new CanonicalGenerator($typoScriptFrontendController))->generate());
    }
}
