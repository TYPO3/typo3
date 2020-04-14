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

namespace TYPO3\CMS\Frontend\Tests\Functional\Middleware;

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequestContext;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class BackendUserAuthenticatorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importDataSet('EXT:core/Tests/Functional/Fixtures/pages.xml');
        $this->setUpBackendUserFromFixture(1);
        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );
    }

    /**
     * @test
     */
    public function nonAuthenticatedRequestDoesNotSendHeaders(): void
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest('/'))->withPageId(1),
            (new InternalRequestContext())
        );
        self::assertArrayNotHasKey('Cache-Control', $response->getHeaders());
        self::assertArrayNotHasKey('Pragma', $response->getHeaders());
        self::assertArrayNotHasKey('Expires', $response->getHeaders());
    }

    /**
     * @test
     */
    public function authenticatedRequestIncludesInvalidCacheHeaders(): void
    {
        $response = $this->executeFrontendRequest(
            (new InternalRequest('/'))->withPageId(1),
            (new InternalRequestContext())
                ->withBackendUserId(1)
        );
        self::assertEquals('no-cache, must-revalidate', $response->getHeaders()['Cache-Control'][0]);
        self::assertEquals('no-cache', $response->getHeaders()['Pragma'][0]);
        self::assertEquals(0, $response->getHeaders()['Expires'][0]);
    }
}
