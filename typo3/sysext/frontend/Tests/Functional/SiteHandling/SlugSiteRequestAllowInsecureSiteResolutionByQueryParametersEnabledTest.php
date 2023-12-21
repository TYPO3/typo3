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

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

final class SlugSiteRequestAllowInsecureSiteResolutionByQueryParametersEnabledTest extends AbstractTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'devIPmask' => '123.123.123.123',
            'encryptionKey' => '4408d27a916d51e624b69af3554f516dbab61037a9f7b9fd6f81b4d3bedeccb6',
            'features' => [
                'security.frontend.allowInsecureSiteResolutionByQueryParameters' => true,
            ],
        ],
        'FE' => [
            'cacheHash' => [
                'requireCacheHashPresenceParameters' => ['value', 'testing[value]', 'tx_testing_link[value]'],
                'excludedParameters' => ['L', 'tx_testing_link[excludedValue]'],
                'enforceValidation' => true,
            ],
            'debug' => false,
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty($writer->getErrors());
            $this->setUpFrontendRootPage(
                1000,
                [
                    'EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                    'EXT:frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
                ],
                [
                    'title' => 'ACME Root',
                ]
            );
            $this->setUpFrontendRootPage(
                3000,
                [
                    'EXT:core/Tests/Functional/Fixtures/Frontend/JsonRenderer.typoscript',
                    'EXT:frontend/Tests/Functional/SiteHandling/Fixtures/JsonRenderer.typoscript',
                ],
                [
                    'title' => 'ACME Archive',
                ]
            );
        });
    }

    public static function siteWithPageIdRequestsAreCorrectlyHandledDataProvider(): \Generator
    {
        yield 'valid same-site request is redirected' => ['https://website.local/?id=1000&L=0', 307];
        yield 'valid same-site request is processed' => ['https://website.local/?id=1100&L=0', 200];
        // This case is allowed due to security.frontend.allowInsecureSiteResolutionByQueryParameters, should otherwise be 404
        yield 'invalid off-site request with unknown domain is denied' => ['https://otherdomain.website.local/?id=3000&L=0', 200];
        yield 'invalid off-site request with unknown domain and without L parameter is denied' => ['https://otherdomain.website.local/?id=3000', 404];
        yield 'invalid cross-site request without L parameter is denied' => ['https://website.local/?id=3000', 404];
        // This case is allowed due to security.frontend.allowInsecureSiteResolutionByQueryParameters, should otherwise be 404
        yield 'invalid cross-site request *not* denied' => ['https://website.local/?id=3000&L=0', 200];
    }

    /**
     * @test
     * @dataProvider siteWithPageIdRequestsAreCorrectlyHandledDataProvider
     */
    public function siteWithPageIdRequestsAreCorrectlyHandled(string $uri, int $expectation): void
    {
        $this->writeSiteConfiguration(
            'website-local',
            $this->buildSiteConfiguration(1000, 'https://website.local/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );
        $this->writeSiteConfiguration(
            'archive-acme-com',
            $this->buildSiteConfiguration(3000, 'https://archive.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ],
            $this->buildErrorHandlingConfiguration('Fluid', [404])
        );

        $response = $this->executeFrontendSubRequest(new InternalRequest($uri));
        self::assertSame($expectation, $response->getStatusCode());
    }
}
