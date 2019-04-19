<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Tests\Functional\MetaDataHandling;

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

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Functional test for the DataHandler
 */
class PluginsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->testExtensionsToLoad[] = 'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_meta';

        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/core/Tests/Functional/Fixtures/Scenarios/pages_with_plugins_seo_meta.xml');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/')
            ]
        );
    }

    public function ensurePageSetupIsOkDataProvider(): array
    {
        return [
            'page:uid:1' => [1, false],
            'page:uid:2' => [2, true],
            'page:uid:3' => [3, true],
            'page:uid:4' => [4, true],
            'page:uid:5' => [5, true],
        ];
    }

    /**
     * @test
     * @dataProvider ensurePageSetupIsOkDataProvider
     * @param int $pageId
     * @param bool $expectPluginOutput
     */
    public function ensurePageSetupIsOk(int $pageId, bool $expectPluginOutput): void
    {
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_meta/Configuration/TypoScript/page' . $pageId . '.typoscript']);
        $response = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => $pageId,
            ])
        );
        $body = (string)$response->getBody();
        $this->assertStringContainsString('<h1>MetaData-Test</h1>', $body);
        if ($expectPluginOutput) {
            $this->assertStringContainsString('TYPO3\CMS\TestMeta\Controller::setMetaData', $body);
        } else {
            $this->assertStringNotContainsString('TYPO3\CMS\TestMeta\Controller::setMetaData', $body);
        }
    }

    public function ensureMetaDataAreCorrectDataProvider(): array
    {
        return [
            'page:uid:1' => [1, 'Rootpage for Tests', ''],
            'page:uid:2' => [2, 'static title with pageId: 2 and pluginNumber: 20', 'OG title from a controller with pageId: 2 and pluginNumber: 20'],
            'page:uid:3' => [3, 'static title with pageId: 3 and pluginNumber: 20', 'OG title from a controller with pageId: 3 and pluginNumber: 20'],
            'page:uid:4' => [4, 'static title with pageId: 4 and pluginNumber: 20', 'OG title from a controller with pageId: 4 and pluginNumber: 20'],
            'page:uid:5' => [5, 'static title with pageId: 5 and pluginNumber: 10', 'OG title from a controller with pageId: 5 and pluginNumber: 10'],
        ];
    }

    /**
     * This test ensures that the meta data and title of the page are the same
     * even if the pages is delivered cached or uncached.
     *
     * @test
     * @dataProvider ensureMetaDataAreCorrectDataProvider
     * @param int $pageId
     * @param string $expectedTitle
     * @param string $expectedMetaOgTitle
     */
    public function ensureMetaDataAreCorrect(int $pageId, string $expectedTitle, string $expectedMetaOgTitle): void
    {
        $this->setUpFrontendRootPage(1, ['typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_meta/Configuration/TypoScript/page' . $pageId . '.typoscript']);

        // First hit to create a cached version
        $uncachedResponse = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => $pageId,
            ])
        );
        $body = (string)$uncachedResponse->getBody();
        $this->assertStringContainsString('<title>' . $expectedTitle . '</title>', $body);
        if ($expectedMetaOgTitle !== '') {
            $this->assertStringContainsString('<meta name="og:title" content="' . $expectedMetaOgTitle . '" />', $body, 'first hit, not cached');
        }

        // Second hit to check the cached version
        $cachedResponse = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => $pageId,
            ])
        );
        $body = (string)$cachedResponse->getBody();
        $this->assertStringContainsString('<title>' . $expectedTitle . '</title>', $body);
        if ($expectedMetaOgTitle !== '') {
            $this->assertStringContainsString('<meta name="og:title" content="' . $expectedMetaOgTitle . '" />', $body, 'second hit, cached');
        }
    }
}
