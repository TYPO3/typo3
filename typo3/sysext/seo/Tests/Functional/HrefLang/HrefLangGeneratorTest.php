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

namespace TYPO3\CMS\Seo\Tests\Functional\HrefLang;

use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerFactory;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\Scenario\DataHandlerWriter;
use TYPO3\TestingFramework\Core\Functional\Framework\Frontend\InternalRequest;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class HrefLangGeneratorTest extends FunctionalTestCase
{
    use SiteBasedTestTrait;

    /**
     * @var string[]
     */
    protected $coreExtensionsToLoad = [
        'core',
        'frontend',
        'seo'
    ];

    /**
     * @var array
     */
    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8', 'iso' => 'en', 'hrefLang' => 'en-US', 'direction' => ''],
        'DE' => ['id' => 1, 'title' => 'German', 'locale' => 'de_DE.UTF8', 'iso' => 'de', 'hrefLang' => 'de-DE', 'direction' => ''],
    ];

    public static function setUpBeforeClass(): void
    {
        parent::setUpBeforeClass();
        static::initializeDatabaseSnapshot();
    }

    public static function tearDownAfterClass(): void
    {
        static::destroyDatabaseSnapshot();
        parent::tearDownAfterClass();
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'acme-com',
            $this->buildSiteConfiguration(1000, 'https://acme.com/'),
            [
                $this->buildDefaultLanguageConfiguration('EN', '/'),
                $this->buildLanguageConfiguration('DE', '/de'),
            ]
        );

        $this->setUpDatabaseWithYamlPayload(__DIR__ . '/../Fixtures/HrefLangScenario.yml');
    }

    /**
     * @param string $url
     * @param array $expected
     *
     * @test
     * @dataProvider checkHrefLangOutputDataProvider
     */
    public function checkHrefLangOutput($url, $expectedTags, $notExpectedTags): void
    {
        $this->setUpFrontendRootPage(
            1000,
            ['typo3/sysext/seo/Tests/Functional/Fixtures/HrefLang.typoscript']
        );

        $response = $this->executeFrontendRequest(
            new InternalRequest($url)
        );
        $stream = $response->getBody();
        $stream->rewind();
        $content = $stream->getContents();

        foreach ($expectedTags as $expectedTag) {
            self::assertStringContainsString($expectedTag, $content);
        }

        foreach ($notExpectedTags as $notExpectedTag) {
            self::assertStringNotContainsString($notExpectedTag, $content);
        }
    }

    /**
     * @return array
     */
    public function checkHrefLangOutputDataProvider(): array
    {
        return [
            'No translation available, so no hreflang tags expected' => [
                'https://acme.com/',
                [],
                [
                    '<link rel="alternate" hreflang='
                ]
            ],
            'English page, with German translation' => [
                'https://acme.com/hello',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                []
            ],
            'German page, with English translation and English default' => [
                'https://acme.com/de/willkommen',
                [
                    '<link rel="alternate" hreflang="en-US" href="https://acme.com/hello"/>',
                    '<link rel="alternate" hreflang="de-DE" href="https://acme.com/de/willkommen"/>',
                    '<link rel="alternate" hreflang="x-default" href="https://acme.com/hello"/>',
                ],
                []
            ],
        ];
    }

    /**
     * @param string $pathToYamlFile
     * @throws \Doctrine\DBAL\DBALException
     */
    protected function setUpDatabaseWithYamlPayload(string $pathToYamlFile): void
    {
        $this->withDatabaseSnapshot(function () use ($pathToYamlFile) {
            $backendUser = $this->setUpBackendUserFromFixture(1);
            Bootstrap::initializeLanguageObject();

            $factory = DataHandlerFactory::fromYamlFile($pathToYamlFile);
            $writer = DataHandlerWriter::withBackendUser($backendUser);
            $writer->invokeFactory($factory);
            static::failIfArrayIsNotEmpty(
                $writer->getErrors()
            );
        });
    }

    /**
     * @param array $items
     */
    protected static function failIfArrayIsNotEmpty(array $items): void
    {
        if (empty($items)) {
            return;
        }

        self::fail(
            'Array was not empty as expected, but contained these items:' . LF
            . '* ' . implode(LF . '* ', $items)
        );
    }
}
