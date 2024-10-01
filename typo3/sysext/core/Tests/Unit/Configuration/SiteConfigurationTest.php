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

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ServiceLocator;
use Symfony\Component\Yaml\Yaml;
use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Configuration\Loader\YamlFileLoader;
use TYPO3\CMS\Core\Configuration\SiteConfiguration;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\EventDispatcher\NoopEventDispatcher;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Package\Cache\PackageDependentCacheIdentifier;
use TYPO3\CMS\Core\Settings\SettingsTypeRegistry;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SiteConfigurationTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    protected ?SiteConfiguration $siteConfiguration;

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
        $setRegistry = $this->createMock(SetRegistry::class);
        $packageDependentCacheIdentifier = $this->createMock(PackageDependentCacheIdentifier::class);
        $settingsTypeRegistry = new SettingsTypeRegistry($this->createMock(ServiceLocator::class));
        $this->siteConfiguration = new SiteConfiguration(
            $this->fixturePath,
            new SiteSettingsFactory($this->fixturePath, $setRegistry, $settingsTypeRegistry, $this->createMock(YamlFileLoader::class), new NullFrontend('test'), $packageDependentCacheIdentifier),
            $setRegistry,
            new NoopEventDispatcher(),
            new NullFrontend('test'),
            new YamlFileLoader($this->createMock(LoggerInterface::class)),
            new NullFrontend('test')
        );
    }

    #[Test]
    public function resolveAllExistingSitesReturnsEmptyArrayForNoSiteConfigsFound(): void
    {
        self::assertEmpty($this->siteConfiguration->resolveAllExistingSites());
    }

    #[Test]
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
}
