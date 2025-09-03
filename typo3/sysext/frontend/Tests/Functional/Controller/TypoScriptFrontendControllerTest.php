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

namespace TYPO3\CMS\Frontend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\Internal\TypoScriptInstruction;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class TypoScriptFrontendControllerTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8'],
    ];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        // Test headerAndFooterMarkersAreReplacedDuringIntProcessing() relies on persisted page cache:
                        // It calls FE rendering twice to verify USER_INT stuff is called for page-cache-exists-but-has-INT.
                        // testing-framework usually sets these to NullBackend which would defeat this case.
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    #[Test]
    public function headerAndFooterMarkersAreReplacedDuringIntProcessing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageWithUserInt.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        // Call page first time to trigger page cache with result
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
        $body = (string)$response->getBody();
        self::assertStringContainsString('userIntContent', $body);
        self::assertStringContainsString('headerDataFromUserInt', $body);
        self::assertStringContainsString('footerDataFromUserInt', $body);

        // Call page second time to see if it works with page cache and user_int is still executed.
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
        $body = (string)$response->getBody();
        self::assertStringContainsString('userIntContent', $body);
        self::assertStringContainsString('headerDataFromUserInt', $body);
        self::assertStringContainsString('footerDataFromUserInt', $body);
    }

    #[Test]
    public function jsIncludesWithUserIntIsRendered(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(2);
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest(
            (new InternalRequest('https://website.local/en/'))
                ->withPageId(2)
                ->withInstructions([
                    (new TypoScriptInstruction())
                        ->withTypoScript([
                            'page' => 'PAGE',
                            'page.' => [
                                'jsInline.' => [
                                    '10' => 'COA_INT',
                                    '10.' => [
                                        '10' => 'TEXT',
                                        '10.' => [
                                            'value' => 'alert(yes);',
                                        ],
                                    ],
                                ],
                            ],
                        ]),
                ]),
        );

        $body = (string)$response->getBody();
        self::assertStringContainsString('/*TS_inlineJSint*/
alert(yes);', $body);
    }

    #[Test]
    public function localizationReturnsUnchangedStringIfNotLocallangLabel(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithoutLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
        $body = (string)$response->getBody();
        self::assertStringContainsString('notprefixedWithLLL', $body);
    }

    #[Test]
    public function localizationReturnsLocalizedStringWithLocallangLabel(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageWithUserObjectUsingSlWithLLL.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );

        $response = $this->executeFrontendSubRequest((new InternalRequest('https://website.local/en/'))->withPageId(88));
        $body = (string)$response->getBody();
        self::assertStringContainsString('Pagetree Overview', $body);
    }

    #[Test]
    public function applicationConsidersTrueConditionVerdict(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageHttpsConditionHelloWorld.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );
        $request = (new InternalRequest('https://website.local/en/'))->withPageId(2);
        $response = $this->executeFrontendSubRequest($request);
        self::assertStringContainsString('https-condition-on', (string)$response->getBody());
    }

    #[Test]
    public function applicationConsidersFalseConditionVerdictToElseBranch(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageHttpsConditionHelloWorld.typoscript']
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'http://website.local/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/en/')]
        );
        $request = (new InternalRequest('http://website.local/en/'))->withPageId(2);
        $response = $this->executeFrontendSubRequest($request);
        self::assertStringContainsString('https-condition-off', (string)$response->getBody());
    }

    #[Test]
    public function localizedPageCacheTagsAreAddedOnLocalizedPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/LiveDefaultPages.csv');
        $this->setUpFrontendRootPage(
            2,
            ['EXT:frontend/Tests/Functional/Controller/Fixtures/PageHttpsConditionHelloWorld.typoscript'],
        );
        $this->writeSiteConfiguration(
            'test',
            $this->buildSiteConfiguration(2, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/en/'),
                $this->buildLanguageConfiguration('DE', '/de/'),
            ],
        );

        $this->executeFrontendSubRequest((new InternalRequest('https://website.local/de/'))->withPageId(90));

        $cacheBackend = $this->get(CacheManager::class)->getCache('pages')->getBackend();

        self::assertInstanceOf(Typo3DatabaseBackend::class, $cacheBackend);
        self::assertCount(1, $cacheBackend->findIdentifiersByTag('pageId_88'));
        self::assertCount(1, $cacheBackend->findIdentifiersByTag('pageId_90'));
    }
}
