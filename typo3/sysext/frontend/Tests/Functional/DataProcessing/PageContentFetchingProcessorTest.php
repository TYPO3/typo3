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

namespace TYPO3\CMS\Frontend\Tests\Functional\DataProcessing;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PageContentFetchingProcessorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en-US'],
    ];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_classic_content',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
            ]
        );

        $this->withDatabaseSnapshot(function () {
            $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
            $backendUser = $this->setUpBackendUser(1);
            $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
            $scenarioFile = __DIR__ . '/Fixtures/ContentScenario.yaml';
            $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            self::failIfArrayIsNotEmpty($writer->getErrors());
            $connection = $this->get(ConnectionPool::class)->getConnectionForTable('pages');

            $pageLayoutFileContents[] = file_get_contents(__DIR__ . '/Fixtures/PageLayouts/Default.tsconfig');
            $pageLayoutFileContents[] = file_get_contents(__DIR__ . '/Fixtures/PageLayouts/Home.tsconfig');
            $pageLayoutFileContents[] = file_get_contents(__DIR__ . '/Fixtures/PageLayouts/Productdetail.tsconfig');

            $connection->update(
                'pages',
                ['TSconfig' => implode(chr(10), $pageLayoutFileContents)],
                ['uid' => 1000]
            );
            $this->setUpFrontendRootPage(1000, ['EXT:frontend/Tests/Functional/DataProcessing/Fixtures/PageContentProcessor/setup.typoscript'], ['title' => 'ACME Guitars']);
        });
    }

    #[Test]
    public function homeLayoutIsRendered(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/'))->withPageId(1000));
        $body = (string)$response->getBody();
        self::assertStringContainsString('Welcome to ACME guitars', $body);
        self::assertStringContainsString('Carousel Items will show up: 2', $body);
        self::assertStringContainsString('Meet us at Guitar Brussels in 2035', $body);
        self::assertStringContainsString('Great to see you here', $body);
        self::assertStringContainsString('If you read this you are at the end.', $body);
    }

    #[Test]
    public function productDetailLayoutIsRendered(): void
    {
        $response = $this->executeFrontendSubRequest((new InternalRequest('https://acme.com/'))->withPageId(1110));
        $body = (string)$response->getBody();
        self::assertStringContainsString('Hero is our flagship', $body);
        self::assertStringContainsString('Get a hero for yourself', $body);
        self::assertStringContainsString('Flash Info for all products', $body);
        self::assertStringContainsString('If you read this you are at the end.', $body);
    }
}
