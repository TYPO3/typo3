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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Command\SiteValidateCommand;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteValidateCommandValidSitesTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    #[Test]
    public function validatingAllSitesReportsNumberOfValidSites(): void
    {
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->write('first', ['rootPageId' => 1, 'base' => 'https://first.example.com/']);
        $siteWriter->write('second', ['rootPageId' => 2, 'base' => 'https://second.example.com/']);

        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute([]);

        self::assertSame(Command::SUCCESS, $tester->getStatusCode());
        self::assertStringContainsString('All 2 sites are valid.', $tester->getDisplay());
    }

    public static function validationCacheDataProvider(): iterable
    {
        yield 'cold cache with invalid CLI host' => [false, ''];
        yield 'warm cache with invalid CLI host' => [true, ''];
        yield 'warm cache with valid CLI host' => [true, 'cli.example.com'];
    }

    #[DataProvider('validationCacheDataProvider')]
    #[Test]
    public function validatingSiteDoesNotWriteSharedCache(bool $warmCache, string $cliHost): void
    {
        $this->get(SiteWriter::class)->write('environment-base', [
            'rootPageId' => 6,
            'base' => 'https://%env(TYPO3_TESTING_SITE_VALIDATION_HOST)%/',
        ]);
        $siteConfiguration = $this->get(SiteConfiguration::class);
        $cacheManager = $this->get(CacheManager::class);
        $coreCache = $cacheManager->getCache('core');
        $previousHost = getenv('TYPO3_TESTING_SITE_VALIDATION_HOST');

        try {
            if ($warmCache) {
                putenv('TYPO3_TESTING_SITE_VALIDATION_HOST=web.example.com');
                $sites = $siteConfiguration->getAllExistingSites(false);
                self::assertSame('https://web.example.com/', (string)$sites['environment-base']->getBase());
            }
            $cachedConfiguration = $coreCache->get('sites-configuration');
            putenv('TYPO3_TESTING_SITE_VALIDATION_HOST=' . $cliHost);

            $tester = new CommandTester($this->get(SiteValidateCommand::class));
            $tester->execute(['identifier' => 'environment-base']);

            self::assertSame($cliHost === '' ? Command::FAILURE : Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
            self::assertSame($cachedConfiguration, $coreCache->get('sites-configuration'));
            if ($warmCache) {
                $cacheManager->getCache('runtime')->flush();
                $sites = $siteConfiguration->getAllExistingSites();
                self::assertArrayHasKey('environment-base', $sites);
                self::assertSame('https://web.example.com/', (string)$sites['environment-base']->getBase());
            }
        } finally {
            putenv($previousHost === false ? 'TYPO3_TESTING_SITE_VALIDATION_HOST' : 'TYPO3_TESTING_SITE_VALIDATION_HOST=' . $previousHost);
            GeneralUtility::rmdir($this->instancePath . '/typo3conf/sites/environment-base', true);
        }
    }

    public static function failedImportDataProvider(): iterable
    {
        foreach (['config.yaml', 'settings.yaml', 'csp.yaml'] as $fileName) {
            yield $fileName . ' with missing import' => [$fileName, null, 'import.yaml'];
            yield $fileName . ' with malformed import' => [$fileName, "value: [\n", 'import.yaml'];
            yield $fileName . ' with nested missing import' => [$fileName, "imports:\n  - resource: missing.yaml\nvalue: valid\n", 'missing.yaml'];
        }
    }

    #[DataProvider('failedImportDataProvider')]
    #[Test]
    public function validatingSiteWithFailedImportFails(string $fileName, ?string $importedContent, string $failedResource): void
    {
        $this->get(SiteWriter::class)->write('failed-import', [
            'rootPageId' => 7,
            'base' => 'https://imports.example.com/',
        ]);
        $siteFolder = $this->instancePath . '/typo3conf/sites/failed-import';

        try {
            $filePath = $siteFolder . '/' . $fileName;
            $configuration = is_file($filePath) ? file_get_contents($filePath) : '';
            file_put_contents($filePath, "imports:\n  - resource: import.yaml\n" . $configuration);
            if ($importedContent !== null) {
                file_put_contents($siteFolder . '/import.yaml', $importedContent);
            }

            $tester = new CommandTester($this->get(SiteValidateCommand::class));
            $tester->execute(['identifier' => 'failed-import']);

            self::assertSame(Command::FAILURE, $tester->getStatusCode(), $tester->getDisplay());
            self::assertStringContainsString('Site "failed-import"', $tester->getDisplay());
            self::assertStringContainsString($failedResource, $tester->getDisplay());
            self::assertStringContainsString('One site configuration error was found.', $tester->getDisplay());
        } finally {
            GeneralUtility::rmdir($siteFolder, true);
        }
    }

    #[Test]
    public function validatingSiteWithEmptySettingsImportSucceeds(): void
    {
        $this->get(SiteWriter::class)->write('empty-settings-import', [
            'rootPageId' => 8,
            'base' => 'https://imports.example.com/',
        ]);
        $siteFolder = $this->instancePath . '/typo3conf/sites/empty-settings-import';

        try {
            file_put_contents($siteFolder . '/settings.yaml', "imports:\n  - resource: import.yaml\n");
            file_put_contents($siteFolder . '/import.yaml', '');

            $tester = new CommandTester($this->get(SiteValidateCommand::class));
            $tester->execute(['identifier' => 'empty-settings-import']);

            self::assertSame(Command::SUCCESS, $tester->getStatusCode(), $tester->getDisplay());
        } finally {
            GeneralUtility::rmdir($siteFolder, true);
        }
    }

    #[Test]
    public function validatingSiteRejectsMalformedSettingsAfterSettingsWereCached(): void
    {
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->write('cached-settings', ['rootPageId' => 3, 'base' => 'https://cached.example.com/']);
        $siteWriter->writeSettings('cached-settings', ['example' => 'valid']);
        $siteFolder = $this->instancePath . '/typo3conf/sites/cached-settings';

        try {
            $this->get(SiteConfiguration::class)->getAllExistingSites();
            $tester = new CommandTester($this->get(SiteValidateCommand::class));
            $tester->execute(['identifier' => 'cached-settings']);
            self::assertSame(Command::SUCCESS, $tester->getStatusCode());

            file_put_contents($siteFolder . '/settings.yaml', "example: [\n");

            $tester->execute(['identifier' => 'cached-settings']);

            self::assertSame(Command::FAILURE, $tester->getStatusCode(), $tester->getDisplay());
            self::assertStringContainsString('settings.yaml', $tester->getDisplay());
        } finally {
            GeneralUtility::rmdir($siteFolder, true);
        }
    }

    #[Test]
    public function validatingSiteReportsMalformedBasesOfInactiveBaseVariants(): void
    {
        $this->get(SiteWriter::class)->write('inactive-variants', [
            'rootPageId' => 4,
            'base' => 'https://variants.example.com/',
            'baseVariants' => [
                ['base' => 'https:///', 'condition' => 'false'],
            ],
            'languages' => [
                [
                    'languageId' => 0,
                    'title' => 'English',
                    'locale' => 'en_US.UTF-8',
                    'base' => '/',
                    'baseVariants' => [
                        ['base' => 'https://', 'condition' => 'false'],
                    ],
                ],
            ],
        ]);

        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute(['identifier' => 'inactive-variants']);
        $output = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $tester->getStatusCode(), $output);
        self::assertStringContainsString('Site "inactive-variants"', $output);
        self::assertStringContainsString('baseVariants.0.base', $output);
        self::assertStringContainsString('languages.0.baseVariants.0.base', $output);
        self::assertStringContainsString('2 site configuration errors were found.', $output);
    }

    #[Test]
    public function validatingSiteReportsMalformedBaseOverriddenByActiveBaseVariant(): void
    {
        $this->get(SiteWriter::class)->write('overridden-base', [
            'rootPageId' => 5,
            'base' => 'https:///',
            'baseVariants' => [
                ['base' => 'https://variant.example.com/', 'condition' => 'true'],
            ],
        ]);

        $tester = new CommandTester($this->get(SiteValidateCommand::class));
        $tester->execute([]);
        $output = $tester->getDisplay();

        self::assertSame(Command::FAILURE, $tester->getStatusCode(), $output);
        self::assertStringContainsString('Site "overridden-base"', $output);
        self::assertStringContainsString('The parsedUri "https:///" appears to be malformed', $output);
        self::assertStringContainsString('One site configuration error was found.', $output);
    }
}
