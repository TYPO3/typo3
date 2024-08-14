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
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Exception\SiteConfigurationWriteException;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteWriterTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ?SiteWriter $siteWriter;

    /**
     * store temporarily used files here
     * will be removed after each test
     */
    protected ?string $fixturePath;

    protected function setUp(): void
    {
        parent::setUp();
        $basePath = Environment::getVarPath() . '/tests/unit';
        $this->fixturePath = $basePath . '/fixture/config/sites';
        if (!file_exists($this->fixturePath)) {
            GeneralUtility::mkdir_deep($this->fixturePath);
        }
        $this->testFilesToDelete[] = $basePath;
        $this->siteWriter = new SiteWriter(
            $this->fixturePath,
            new NoopEventDispatcher(),
            new NullFrontend('test'),
            new YamlFileLoader($this->createMock(LoggerInterface::class))
        );
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

        $this->siteWriter->write($identifier, $configuration, true);

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
        $this->siteWriter->write($identifier, $configuration, true);

        // expect modified base but intact imports
        self::assertFileEquals($expected, $siteConfig);
    }

    public static function writingPlaceholdersIsHandledDataProvider(): \Generator
    {
        yield 'unchanged' => [
            ['customProperty' => 'Using %env("existing")% variable'],
            false,
        ];
        yield 'removed placeholder variable' => [
            ['customProperty' => 'Not using any variable'],
            false,
        ];
        yield 'changed raw text only' => [
            ['customProperty' => 'Using %env("existing")% variable from system environment'],
            false,
        ];
        yield 'added new placeholder variable' => [
            ['customProperty' => 'Using %env("existing")% and %env("secret")% variable'],
            true,
        ];
    }

    #[DataProvider('writingPlaceholdersIsHandledDataProvider')]
    #[Test]
    public function writingPlaceholdersIsHandled(array $changes, bool $expectedException): void
    {
        if ($expectedException) {
            $this->expectException(SiteConfigurationWriteException::class);
            $this->expectExceptionCode(1670361271);
        }

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
        $this->siteWriter->write($identifier, $configuration, true);
    }
}
