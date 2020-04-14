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

namespace TYPO3\CMS\Seo\Tests\Functional\MetaTag;

use TYPO3\CMS\Frontend\Tests\Functional\SiteHandling\AbstractTestCase;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Functional test for the DataHandler
 */
class MetaTagTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        $this->coreExtensionsToLoad[] = 'seo';

        parent::setUp();
        $this->importDataSet(ORIGINAL_ROOT . 'typo3/sysext/seo/Tests/Functional/Fixtures/Scenarios/pages_with_seo_meta.xml');

        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1, 'http://localhost/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/')
            ]
        );
    }

    public function ensureMetaDataAreCorrectDataProvider(): array
    {
        return [
            'page with twitter_card in page properties' => [
                1,
                [
                    ['type' => 'name' , 'tag' => 'twitter:card', 'content' => 'summary'],
                ]
            ],
            'page with twitter_card in page properties and in typoscript' => [
                2,
                [
                    ['type' => 'name' , 'tag' => 'twitter:card', 'content' => 'summary'],
                ]
            ],
            'page with twitter_card in page properties and in typoscript with replace' => [
                3,
                [
                    ['type' => 'name' , 'tag' => 'twitter:card', 'content' => 'summary_large_image'],
                ]
            ],
        ];
    }

    /**
     * @test
     * @dataProvider ensureMetaDataAreCorrectDataProvider
     * @param int $pageId
     * @param array $expectedMetaTags
     */
    public function ensureMetaDataAreCorrect(int $pageId, array $expectedMetaTags): void
    {
        $this->setUpFrontendRootPage(1, ['typo3/sysext/seo/Tests/Functional/Fixtures/page' . $pageId . '.typoscript']);

        // First hit to create a cached version
        $uncachedResponse = $this->executeFrontendRequest(
            (new InternalRequest('http://localhost/'))->withQueryParameters([
                'id' => $pageId,
            ])
        );
        $body = (string)$uncachedResponse->getBody();

        foreach ($expectedMetaTags as $expectedMetaTag) {
            self::assertStringContainsString('<meta ' . $expectedMetaTag['type'] . '="' . $expectedMetaTag['tag'] . '" content="' . $expectedMetaTag['content'] . '" />', $body);
        }
    }
}
