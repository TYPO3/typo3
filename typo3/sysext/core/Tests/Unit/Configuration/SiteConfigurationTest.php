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

use Prophecy\Argument;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Cache\Frontend\PhpFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\Uri;
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
        $coreCacheProphecy = $this->prophesize(PhpFrontend::class);
        $coreCacheProphecy->require(Argument::any())->willReturn(false);
        $coreCacheProphecy->set(Argument::any(), Argument::any())->willReturn(null);
        $coreCacheProphecy->remove(Argument::any(), Argument::any())->willReturn(null);
        $cacheManager->getCache('core')->willReturn($coreCacheProphecy->reveal());
        GeneralUtility::setSingletonInstance(CacheManager::class, $cacheManager->reveal());

        $this->siteConfiguration = new SiteConfiguration($this->fixturePath);
    }

    /**
     * @test
     */
    public function resolveAllExistingSitesReturnsEmptyArrayForNoSiteConfigsFound(): void
    {
        self::assertEmpty($this->siteConfiguration->resolveAllExistingSites());
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
        self::assertCount(1, $sites);
        $currentSite = current($sites);
        self::assertSame(42, $currentSite->getRootPageId());
        self::assertEquals(new Uri('https://example.com'), $currentSite->getBase());
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
}
