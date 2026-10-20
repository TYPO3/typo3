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

namespace TYPO3\CMS\Frontend\Tests\Functional\SiteHandling;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Exception\SiteNotFoundException;
use TYPO3\CMS\Core\Site\SiteFinder;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Test scenarios with a site configuration that cannot be turned into a Site object.
 */
final class InvalidSiteTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/Fixtures/InvalidSiteTestPageImport.csv');
        $this->writeSiteConfiguration(
            'valid',
            $this->buildSiteConfiguration(1, 'https://valid.example.com/'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );
    }

    #[Test]
    public function validSiteIsRenderedWhileAnotherSiteIsInvalid(): void
    {
        $this->writeSiteConfiguration(
            'broken',
            $this->buildSiteConfiguration(2, 'https:///'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://valid.example.com/'));

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('Valid root', (string)$response->getBody());
    }

    #[Test]
    public function pageBelowInvalidNestedSiteIsNotResolvedByParentSite(): void
    {
        $this->writeSiteConfiguration(
            'broken-nested',
            $this->buildSiteConfiguration(3, 'https:///'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest('https://valid.example.com/nested/below'));

        self::assertSame(404, $response->getStatusCode());
    }

    #[Test]
    public function siteOfPageBelowInvalidNestedSiteIsNotFound(): void
    {
        $this->writeSiteConfiguration(
            'broken-nested',
            $this->buildSiteConfiguration(3, 'https:///'),
            [$this->buildDefaultLanguageConfiguration('EN', '/')]
        );

        $this->expectException(SiteNotFoundException::class);
        $this->get(SiteFinder::class)->getSiteByPageId(4);
    }
}
