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

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\CMS\Core\TypoScript\TemplateService;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test case
 */
class CanonicalGeneratorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected $configurationToUseInTestInstance = [
        'SC_OPTIONS' => [
            'Core/TypoScript/TemplateService' => [
                'runThroughTemplatesPostProcessing' => [
                    'FunctionalTest' => \TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Hook\TypoScriptInstructionModifier::class . '->apply',
                ],
            ],
        ],
    ];

    protected $coreExtensionsToLoad = ['seo'];

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $this->writeSiteConfiguration(
            'website-example',
            $this->buildSiteConfiguration(100, 'http://example.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $this->writeSiteConfiguration(
            'dummy',
            $this->buildSiteConfiguration(200, '/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $this->importDataSet('EXT:seo/Tests/Functional/Fixtures/pages-canonical.xml');
        $this->setUpFrontendRootPage(
            1,
            ['typo3/sysext/seo/Tests/Functional/Fixtures/Canonical.typoscript']
        );
    }

    public function generateDataProvider(): array
    {
        return [
            'uid: 1 with canonical_link' => [
                'http://localhost/',
                '<link rel="canonical" href="http://localhost/"/>' . chr(10),
            ],
            'uid: 2 with canonical_link' => [
                'http://localhost/dummy-1-2',
                '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . chr(10),
            ],
            'uid: 3 with canonical_link AND content_from_pid = 2' => [
                'http://localhost/dummy-1-3',
                '<link rel="canonical" href="http://localhost/dummy-1-3"/>' . chr(10),
            ],
            'uid: 4 without canonical_link AND content_from_pid = 2' => [
                'http://localhost/dummy-1-4',
                '<link rel="canonical" href="http://localhost/dummy-1-2"/>' . chr(10),
            ],
            'uid: 5 without canonical_link AND without content_from_pid set' => [
                'http://localhost/dummy-1-2-5',
                '<link rel="canonical" href="http://localhost/dummy-1-2-5"/>' . chr(10),
            ],
            'uid: 8 without canonical_link AND content_from_pid = 9 (but target page is hidden) results in no canonical' => [
                'http://localhost/dummy-1-2-8',
                '',
            ],
            'uid: 10 no index' => [
                'http://localhost/dummy-1-2-10',
                '',
            ],
            'uid: 11 with mount_pid_ol = 0' => [
                'http://localhost/dummy-1-2-11',
                '<link rel="canonical" href="http://localhost/dummy-1-2-11"/>' . chr(10),
            ],
            'uid: 12 with mount_pid_ol = 1' => [
                'http://localhost/dummy-1-2-12',
                '<link rel="canonical" href="http://example.com/"/>' . chr(10),
            ],
            'subpage of page with mount_pid_ol = 0' => [
                'http://localhost/dummy-1-2-11/subpage-of-new-root',
                '<link rel="canonical" href="http://example.com/subpage-of-new-root"/>' . chr(10),
            ],
            'subpage of page with mount_pid_ol = 1' => [
                'http://localhost/dummy-1-2-12/subpage-of-new-root',
                '<link rel="canonical" href="http://example.com/subpage-of-new-root"/>' . chr(10),
            ],
            'mountpoint to page without siteroot' => [
                'http://localhost/dummy-1-2-13',
                '',
            ],
            'subpage of mountpoint without siteroot' => [
                'http://localhost/dummy-1-2-13/imprint',
                '',
            ],
            'uid: 14 typoscript setting config.disableCanonical' => [
                'http://localhost/no-canonical',
                '',
            ],
        ];
    }

    /**
     * @param string $targetUri
     * @param string $expectedCanonicalUrl
     *
     * @test
     * @dataProvider generateDataProvider
     */
    public function generate(string $targetUri, string $expectedCanonicalUrl): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($targetUri))->withInstructions([$this->buildPageTypoScript()])
        );

        if ($expectedCanonicalUrl) {
            self::assertStringContainsString($expectedCanonicalUrl, (string)$response->getBody());
        } else {
            self::assertStringNotContainsString('<link rel="canonical"', (string)$response->getBody());
        }
    }

    private function buildPageTypoScript(): TypoScriptInstruction
    {
        return (new TypoScriptInstruction(TemplateService::class))
            ->withTypoScript([
                'page' => 'PAGE',
                'page.' => [
                    'typeNum' => 0,
                ],
            ]);
    }
}
