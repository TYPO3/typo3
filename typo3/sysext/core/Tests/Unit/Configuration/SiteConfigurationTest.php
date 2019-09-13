<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Core\Tests\Unit\Configuration;

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

use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\PackageInterface;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class SiteConfigurationTest extends UnitTestCase
{
    protected $resetSingletonInstances = true;

    /**
     * @var \TYPO3\CMS\Core\Configuration\SiteConfiguration
     */
    protected $siteConfiguration;

    /**
     * @var string
     * store temporarily used files here
     * will be removed after each test
     */
    protected $fixturePath;
    protected $packageManager;

    protected function setUp(): void
    {
        parent::setUp();
        $basePath = Environment::getVarPath() . '/tests/unit';
        $this->fixturePath = $basePath . '/fixture/config/sites';
        if (!file_exists($this->fixturePath)) {
            GeneralUtility::mkdir_deep($this->fixturePath);
        }
        $this->testFilesToDelete[] = $basePath;
        $cacheManager = $this->prophesize(CacheManager::class);
        $cacheManager->getCache('core')->willReturn($this->prophesize(FrontendInterface::class)->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());
        $this->packageManager = $this->prophesize(PackageManager::class);
        $this->packageManager->getActivePackages()->willReturn([]);
        GeneralUtility::setSingletonInstance(PackageManager::class, $this->packageManager->reveal());

        $this->siteConfiguration = new SiteConfiguration($this->fixturePath);
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReturnsEmptyArrayForNoSiteConfigsFound(): void
    {
        $this->assertEmpty($this->siteConfiguration->resolveAllExistingSites());
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReadsConfiguration(): void
    {
        $configuration = [
            'rootPageId' => 42,
            'base' => 'https://example.com',
        ];
        $yamlFileContents = Yaml::dump($configuration, 99, 2);
        GeneralUtility::mkdir($this->fixturePath . '/home');
        GeneralUtility::writeFile($this->fixturePath . '/home/config.yaml', $yamlFileContents);
        $sites = $this->siteConfiguration->resolveAllExistingSites();
        $this->assertCount(1, $sites);
        $currentSite = current($sites);
        $this->assertSame(42, $currentSite->getRootPageId());
        $this->assertEquals(new Uri('https://example.com'), $currentSite->getBase());
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReadsSettings(): void
    {
        $configuration = [
            'rootPageId' => 42,
            'base' => 'https://example.com',
        ];
        $settings = [
            'somevendor' => [
                'someextension' => 'foo',
            ],
            'othervendor' => [
                'otherextension' => 'bar',
            ],
        ];
        GeneralUtility::mkdir($this->fixturePath . '/home');
        // make sure some config exists else no settings will be loaded
        GeneralUtility::writeFile($this->fixturePath . '/home/config.yaml', Yaml::dump($configuration, 99, 2));
        GeneralUtility::writeFile($this->fixturePath . '/home/settings.yaml', Yaml::dump($settings, 99, 2));
        $sites = $this->siteConfiguration->resolveAllExistingSites();
        $currentSite = current($sites);
        $this->assertSame($settings, $currentSite->getSettings());
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReadsSettingsIncludingDefaultsFromExtensions(): void
    {
        $configuration = [
            'rootPageId' => 42,
            'base' => 'https://example.com',
        ];
        $settings = [
            'somevendor' => [
                'someextension' => 'foo',
            ],
            'othervendor' => [
                'otherextension' => 'bar',
            ],
        ];
        GeneralUtility::mkdir($this->fixturePath . '/home');
        // make sure some config exists else no settings will be loaded
        GeneralUtility::writeFile($this->fixturePath . '/home/config.yaml', Yaml::dump($configuration, 99, 2));
        GeneralUtility::writeFile($this->fixturePath . '/home/settings.yaml', Yaml::dump($settings, 99, 2));
        $package = $this->createFakeExtensionWithSiteSettings();

        $this->packageManager->getActivePackages()->willReturn([
            $package['extensionkey'] => $package['package'],
        ]);
        $sites = $this->siteConfiguration->resolveAllExistingSites();
        $currentSite = current($sites);
        $this->assertSame([
            'MyVendor' => [
                'MyExtension' => [
                    'page' => [
                        'id' => '95',
                    ],
                    'storagePid' => '21',
                ],
            ],
            'somevendor' => [
                'someextension' => 'foo',
            ],
            'othervendor' => [
                'otherextension' => 'bar',
            ],
        ], $currentSite->getSettings());
    }

    /**
     * @test
     */
    public function writeOnlyWritesModifiedKeys(): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config1.yaml';
        $expected = __DIR__ . '/Fixtures/SiteConfigs/config1_expected.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);

        // load with resolved imports as the module does
        $configuration = GeneralUtility::makeInstance(YamlFileLoader::class)
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

        $this->siteConfiguration->write($identifier, $configuration);

        // expect modified base but intact imports
        self::assertFileEquals($expected, $siteConfig);
    }

    /**
     * @test
     */
    public function writingOfNestedStructuresPreservesOrder(): void
    {
        $identifier = 'testsite';
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $identifier);
        $configFixture = __DIR__ . '/Fixtures/SiteConfigs/config2.yaml';
        $expected = __DIR__ . '/Fixtures/SiteConfigs/config2_expected.yaml';
        $siteConfig = $this->fixturePath . '/' . $identifier . '/config.yaml';
        copy($configFixture, $siteConfig);

        // load with resolved imports as the module does
        $configuration = GeneralUtility::makeInstance(YamlFileLoader::class)
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
            'typo3Language' => 'default',
            'locale' => 'en_US.utf8',
            'iso-639-1' => 'en',
            'hreflang' => '',
            'direction' => '',
            'flag' => 'en',
            'navigationTitle' => 'English',
        ];
        array_unshift($configuration['languages'], $languageConfig);
        $this->siteConfiguration->write($identifier, $configuration);

        // expect modified base but intact imports
        self::assertFileEquals($expected, $siteConfig);
    }

    public function writeSettingsDataProvider(): array
    {
        return [
            'no modifications' => [
                'settings_1.yaml',
                // use default value
                ['MyVendor.MyExtension.storagePid' => '21'],
            ],
            'changed storage' => [
                'settings_2.yaml',
                ['MyVendor.MyExtension.storagePid' => '22'],
            ],
            'one additional setting without default and changed storage' => [
                'settings_3.yaml',
                ['MyVendor.MyExtension.somethingElse' => '22', 'MyVendor.MyExtension.storagePid' => '22'],
            ],
        ];
    }

    /**
     * @test
     * @dataProvider writeSettingsDataProvider
     * @param string $expectedFile
     * @param $settings
     */
    public function writeSettingsWritesOnlyModifiedAndNonDefaultKeys(string $expectedFile, $settings): void
    {
        $expected = __DIR__ . '/Fixtures/SiteSettings/' . $expectedFile;
        $configSite = ['rootPageId' => 15, 'base' => 'something'];
        $settingsSite = [
            'banana' => [
                'bread' => [
                    'is' => 'tasty',
                ],
            ],
        ];
        $this->prepareSiteConfig($settingsSite, $configSite, 'site_1');
        $package = $this->createFakeExtensionWithSiteSettings();

        $this->packageManager->getActivePackages()->willReturn([
            $package['extensionkey'] => $package['package'],
        ]);

        $this->siteConfiguration->writeSettings('site_1', $settings);
        self::assertFileEquals($expected, $this->fixturePath . '/site_1/settings.yaml');
    }

    /**
     * All settings provided by extensions with interface definitions will be removed
     * if they aren't in the array to be written or are reset to their default values
     *
     * @test
     */
    public function writeSettingsRemovesSettingsProvidedByExtensionsIfNonOrDefaultValueGiven(): void
    {
        $expected = __DIR__ . '/Fixtures/SiteSettings/settings_removal.yaml';
        $configSite = ['rootPageId' => 15, 'base' => 'something'];
        $settingsSite = [
            // should be kept as it's not coming from an extension but custom configuration
            'banana' => [
                'bread' => [
                    'is' => 'tasty',
                ],
            ],
            'MyVendor' => [
                'MyExtension' => [
                    // should be removed as it's not in the to be written array but has a definition by an extension
                    'page' => [
                        'id' => '96',
                    ],
                    // should be removed as it is reset to it's default value
                    'storagePid' => '22',
                ],
            ],
        ];
        $this->prepareSiteConfig($settingsSite, $configSite, 'site_1');
        $package = $this->createFakeExtensionWithSiteSettings();

        $this->packageManager->getActivePackages()->willReturn([
            $package['extensionkey'] => $package['package'],
        ]);

        $this->siteConfiguration->writeSettings('site_1', ['MyVendor.MyExtension.storagePid' => '21']);
        self::assertFileEquals($expected, $this->fixturePath . '/site_1/settings.yaml');
    }

    /**
     * @param array $settings
     * @param array $config
     * @param string $configDirPath
     */
    protected function prepareSiteConfig(array $settings, array $config, string $configDirPath): void
    {
        $yamlFileContents = Yaml::dump($settings, 99, 2);
        GeneralUtility::mkdir_deep($this->fixturePath . '/' . $configDirPath);
        GeneralUtility::writeFile($this->fixturePath . '/' . $configDirPath . '/config.yaml', Yaml::dump($config));
        GeneralUtility::writeFile($this->fixturePath . '/' . $configDirPath . '/settings.yaml', $yamlFileContents);
    }

    protected function createFakeExtensionWithSiteSettings(string $vendor = 'MyVendor', string $packageName = 'MyExtension', $line1 = 'storagePid = 21', $line2 = ''): array
    {
        $extensionKey = uniqid('test');
        $packagePath = $this->fixturePath . '/ext/' . $extensionKey . '/';
        $package = $this->prophesize(PackageInterface::class);
        $package->getPackageKey()->willReturn($extensionKey);
        $package->getPackagePath()->willReturn($packagePath);
        $defaultExtensionSettingsPath = $packagePath . '/Configuration/Site';
        GeneralUtility::mkdir_deep($defaultExtensionSettingsPath);
        $settingData = $vendor . ' {' . PHP_EOL;
        $settingData .= '   ' . $packageName . ' {' . PHP_EOL;
        $settingData .= '       page {' . PHP_EOL;
        $settingData .= '           # cat=testing/siteSettings; type=int; label=Page ID' . PHP_EOL;
        $settingData .= '           id = 95' . PHP_EOL;
        $settingData .= '       }' . PHP_EOL;
        $settingData .= '       # cat=testing/siteSettings; type=int; label=StoragePid:where to store records' . PHP_EOL;
        $settingData .= '       ' . $line1 . '' . PHP_EOL;
        $settingData .= '       ' . $line2 . '' . PHP_EOL;
        $settingData .= '   }' . PHP_EOL;
        $settingData .= '}';
        file_put_contents($defaultExtensionSettingsPath . '/settings.typoscript', $settingData);
        return ['extensionkey' => $extensionKey, 'package' => $package->reveal()];
    }
}
