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

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;

/**
 * Language = 1 is in free mode
 */
final class LinkGeneratorFreeModeTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', 'https://acme.us/'),
                $this->buildLanguageConfiguration('FR', 'https://acme.fr/', [], 'free'),
                $this->buildLanguageConfiguration('FR-CA', 'https://acme.ca/', ['FR', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'products-acme-com',
            $this->buildSiteConfiguration(1300, 'https://products.acme.com/')
        );
        $this->writeSiteConfiguration(
            'blog-acme-com',
            $this->buildSiteConfiguration(2000, 'https://blog.acme.com/')
        );
        $this->writeSiteConfiguration(
            'john-blog-acme-com',
            $this->buildSiteConfiguration(2110, 'https://blog.acme.com/john/')
        );
        $this->writeSiteConfiguration(
            'jane-blog-acme-com',
            $this->buildSiteConfiguration(2120, 'https://blog.acme.com/jane/')
        );
        $this->writeSiteConfiguration(
            'archive-acme-com',
            $this->buildSiteConfiguration(3000, 'https://archive.acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('FR', 'https://archive.acme.com/fr/', ['EN']),
                $this->buildLanguageConfiguration('FR-CA', 'https://archive.acme.com/ca/', ['FR', 'EN']),
            ]
        );
        $this->writeSiteConfiguration(
            'common-collection',
            $this->buildSiteConfiguration(7000, 'https://common.acme.com/')
        );
        $this->writeSiteConfiguration(
            'usual-collection',
            $this->buildSiteConfiguration(8000, 'https://usual.acme.com/')
        );

        $this->withDatabaseSnapshot(
            function () {
                $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
                $backendUser = $this->setUpBackendUser(1);
                Bootstrap::initializeLanguageObject();
                $scenarioFile = __DIR__ . '/Fixtures/SlugScenario.yaml';
                $factory = DataHandlerFactory::fromYamlFile($scenarioFile);
                $writer = DataHandlerWriter::withBackendUser($backendUser);
                $writer->invokeFactory($factory);
                static::failIfArrayIsNotEmpty($writer->getErrors());
                $this->setUpFrontendRootPage(1000, ['EXT:frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript'], ['title' => 'ACME Root']);
                $this->setUpFrontendRootPage(2000, ['EXT:frontend/Tests/Functional/SiteHandling/Fixtures/LinkGenerator.typoscript'], ['title' => 'ACME Blog']);
            },
            function () {
                $this->setUpBackendUser(1);
                Bootstrap::initializeLanguageObject();
            }
        );
    }

    public static function linkIsGeneratedForLanguageDataProvider(): array
    {
        $instructions = [
            // acme.com -> acme.com (same site)
            ['https://acme.us/', 1100, 1100, 0, '/welcome'],
            ['https://acme.us/', 1100, 1100, 1, 'https://acme.fr/bienvenue'],
            ['https://acme.us/', 1100, 1101, 0, 'https://acme.fr/bienvenue'],
            ['https://acme.us/', 1100, 1102, 0, 'https://acme.ca/bienvenue'],
            // acme.com -> products.acme.com (nested sub-site)
            ['https://acme.us/', 1100, 1300, 0, 'https://products.acme.com/products'],
            ['https://acme.us/', 1100, 1310, 0, 'https://products.acme.com/products/planets'],
            // acme.com -> products.acme.com (nested sub-site, l18n_cfg=1)
            ['https://acme.us/', 1100, 1410, 0, ''],
            ['https://acme.us/', 1100, 1410, 1, 'https://acme.fr/acme-dans-votre-region/groupes'],
            ['https://acme.us/', 1100, 1411, 0, 'https://acme.fr/acme-dans-votre-region/groupes'],
            ['https://acme.us/', 1100, 1412, 0, 'https://acme.ca/acme-dans-votre-quebec/groupes'],
            // acme.com -> archive (outside site)
            ['https://acme.us/', 1100, 3100, 0, 'https://archive.acme.com/statistics'],
            ['https://acme.us/', 1100, 3100, 1, 'https://archive.acme.com/fr/statistics'],
            ['https://acme.us/', 1100, 3101, 0, 'https://archive.acme.com/fr/statistics'],
            ['https://acme.us/', 1100, 3102, 0, 'https://archive.acme.com/ca/statistics'],
            // blog.acme.com -> acme.com (different site)
            ['https://blog.acme.com/', 2100, 1100, 0, 'https://acme.us/welcome'],
            ['https://blog.acme.com/', 2100, 1100, 1, 'https://acme.fr/bienvenue'],
            ['https://blog.acme.com/', 2100, 1101, 0, 'https://acme.fr/bienvenue'],
            ['https://blog.acme.com/', 2100, 1102, 0, 'https://acme.ca/bienvenue'],
            // blog.acme.com -> archive (outside site)
            ['https://blog.acme.com/', 2100, 3100, 0, 'https://archive.acme.com/statistics'],
            ['https://blog.acme.com/', 2100, 3100, 1, 'https://archive.acme.com/fr/statistics'],
            ['https://blog.acme.com/', 2100, 3101, 0, 'https://archive.acme.com/fr/statistics'],
            ['https://blog.acme.com/', 2100, 3102, 0, 'https://archive.acme.com/ca/statistics'],
            // blog.acme.com -> products.acme.com (different sub-site)
            ['https://blog.acme.com/', 2100, 1300, 0, 'https://products.acme.com/products'],
            ['https://blog.acme.com/', 2100, 1310, 0, 'https://products.acme.com/products/planets'],
        ];
        return self::keysFromTemplate($instructions, '%2$d->%3$d (lang:%4$d)');
    }

    /**
     * @test
     * @dataProvider linkIsGeneratedForLanguageDataProvider
     */
    public function linkIsGeneratedForLanguageWithLanguageProperty(string $hostPrefix, int $sourcePageId, int $targetPageId, int $targetLanguageId, string $expectation): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($hostPrefix))
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createTypoLinkUrlInstruction([
                        'parameter' => $targetPageId,
                        'language' => $targetLanguageId,
                    ]),
                ])
        );

        self::assertSame($expectation, (string)$response->getBody());
    }

    public static function languageMenuIsGeneratedDataProvider(): array
    {
        return [
            'ACME Inc (EN)' => [
                'https://acme.us/',
                1100,
                [
                    ['title' => 'English', 'link' => '/welcome', 'active' => 1, 'current' => 0, 'available' => 1],
                    ['title' => 'French', 'link' => 'https://acme.fr/bienvenue', 'active' => 0, 'current' => 0, 'available' => 1],
                    ['title' => 'Franco-Canadian', 'link' => 'https://acme.ca/bienvenue', 'active' => 0, 'current' => 0, 'available' => 1],
                ],
            ],
            'ACME Inc (FR)' => [
                'https://acme.fr/',
                1100,
                [
                    ['title' => 'English', 'link' => 'https://acme.us/welcome', 'active' => 0, 'current' => 0, 'available' => 1],
                    ['title' => 'French', 'link' => '/bienvenue', 'active' => 1, 'current' => 0, 'available' => 1],
                    ['title' => 'Franco-Canadian', 'link' => 'https://acme.ca/bienvenue', 'active' => 0, 'current' => 0, 'available' => 1],
                ],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider languageMenuIsGeneratedDataProvider
     */
    public function languageMenuIsGenerated(string $hostPrefix, int $sourcePageId, array $expectation): void
    {
        $response = $this->executeFrontendSubRequest(
            (new InternalRequest($hostPrefix))
                ->withPageId($sourcePageId)
                ->withInstructions([
                    $this->createLanguageMenuProcessorInstruction([
                        'languages' => 'auto',
                    ]),
                ])
        );

        $json = json_decode((string)$response->getBody(), true);
        $json = $this->filterMenu($json, ['title', 'link', 'available', 'active', 'current']);

        self::assertSame($expectation, $json);
    }
}
