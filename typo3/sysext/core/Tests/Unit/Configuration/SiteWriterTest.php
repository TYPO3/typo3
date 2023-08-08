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

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteWriterTest extends UnitTestCase
{
    /**
     * store temporarily used files here
     * will be removed after each test
     */
    private string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $basePath = Environment::getVarPath() . '/tests/unit';
        $this->fixturePath = $basePath . '/fixture/config/sites';
        if (!file_exists($this->fixturePath)) {
            GeneralUtility::mkdir_deep($this->fixturePath);
        }
        $this->testFilesToDelete[] = $basePath;
    }

    #[Test]
    public function writeOnlyWritesModifiedKeys(): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config1.yaml';
        $expected = __DIR__ . '/Fixtures/SiteConfigs/config1_expected.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);

        // load with resolved imports as the module does
        $configuration = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load(
                GeneralUtility::fixWindowsFilePath($siteConfig),
                YamlFileLoader::PROCESS_IMPORTS
            );
        // modify something on base level
        $configuration['base'] = 'https://example.net/';
        // modify something nested
        $configuration['languages'][0]['title'] = 'English';
        // delete values
        unset($configuration['someOtherValue'], $configuration['languages'][1]);

        $subject = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
        $subject->write($identifier, $configuration, true);

        // expect modified base but intact imports
        self::assertFileEquals($expected, $siteConfig);
    }

    #[Test]
    public function writingOfNestedStructuresPreservesOrder(): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config2.yaml';
        $expected = __DIR__ . '/Fixtures/SiteConfigs/config2_expected.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);

        // load with resolved imports as the module does
        $configuration = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load(
                GeneralUtility::fixWindowsFilePath($siteConfig),
                YamlFileLoader::PROCESS_IMPORTS
            );
        // add new language
        $languageConfig = [
            'title' => 'English',
            'enabled' => true,
            'languageId' => '0',
            'base' => '/en',
            'locale' => 'en_US.utf8',
            'flag' => 'en',
            'navigationTitle' => 'English',
        ];
        array_unshift($configuration['languages'], $languageConfig);
        $subject = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
        $subject->write($identifier, $configuration, true);

        // expect modified base but intact imports
        self::assertFileEquals($expected, $siteConfig);
    }

    public static function writingPlaceholdersIsHandledDataProvider(): \Generator
    {
        yield 'unchanged' => [
            ['customProperty' => 'Using %env("existing")% variable'],
        ];
        yield 'removed placeholder variable' => [
            ['customProperty' => 'Not using any variable'],
        ];
        yield 'changed raw text only' => [
            ['customProperty' => 'Using %env("existing")% variable from system environment'],
        ];
    }

    #[DataProvider('writingPlaceholdersIsHandledDataProvider')]
    #[Test]
    #[DoesNotPerformAssertions]
    public function writingPlaceholdersIsHandled(array $changes): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config2.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);
        // load with resolved imports as the module does
        $configuration = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load(
                GeneralUtility::fixWindowsFilePath($siteConfig),
                YamlFileLoader::PROCESS_IMPORTS
            );
        $configuration = array_merge($configuration, $changes);
        $subject = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
        $subject->write($identifier, $configuration, true);
    }

    #[Test]
    public function writingPlaceholdersThrowsWithInvalidData(): void
    {
        $this->expectException(SiteConfigurationWriteException::class);
        $this->expectExceptionCode(1670361271);
        $changes = [
            'customProperty' => 'Using %env("existing")% and %env("secret")% variable',
        ];
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config2.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);
        // load with resolved imports as the module does
        $configuration = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load(
                GeneralUtility::fixWindowsFilePath($siteConfig),
                YamlFileLoader::PROCESS_IMPORTS
            );
        $configuration = array_merge($configuration, $changes);
        $subject = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
        $subject->write($identifier, $configuration, true);
    }

    #[Test]
    public function writeAllowsPlaceholdersInImportedFiles(): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config3.yaml';
        $expected = __DIR__ . '/Fixtures/SiteConfigs/config3_expected.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);

        // load with resolved imports as the module does
        $configuration = (new YamlFileLoader($this->createMock(LoggerInterface::class)))
            ->load(
                GeneralUtility::fixWindowsFilePath($siteConfig),
                YamlFileLoader::PROCESS_IMPORTS
            );

        // Simulate env placeholder
        putenv('base=https://example.com/');

        $subject = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
        $subject->write($identifier, $configuration, true);

        self::assertFileEquals($expected, $siteConfig);

        // Reset env placeholder
        putenv('base');
    }
}
