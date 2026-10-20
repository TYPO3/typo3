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

namespace TYPO3\CMS\Lowlevel\Tests\Functional\ConfigurationModuleProvider;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Event\CacheWarmupEvent;
use TYPO3\CMS\Core\Configuration\SiteWriter;
use TYPO3\CMS\Core\Settings\SettingDefinition;
use TYPO3\CMS\Core\Site\Set\SetCollector;
use TYPO3\CMS\Core\Site\Set\SetDefinition;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Lowlevel\ConfigurationModuleProvider\ProviderRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteSetsProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['lowlevel'];

    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $this->setUpBackendUser(1);
    }

    #[Test]
    public function setsAreGroupedBySiteInResolvedOrder(): void
    {
        $siteWriter = $this->get(SiteWriter::class);
        $siteWriter->createNewBasicSite('site-a', 1, 'https://a.example.com/', ['typo3tests/set-6', 'typo3tests/set-1']);
        $siteWriter->createNewBasicSite('site-b', 2, 'https://b.example.com/', ['typo3tests/set-1', 'typo3tests/set-6']);
        $siteWriter->createNewBasicSite('site-c', 3, 'https://c.example.com/');

        $configuration = $this->get(ProviderRegistry::class)->getProvider('siteSets')->getConfiguration();
        $sites = $configuration['sites'];

        self::assertSame(['sites', 'siteSets'], array_keys($configuration));
        self::assertSame(['site-a', 'site-b', 'site-c'], array_keys($sites));
        self::assertSame(
            ['typo3tests/set-6', 'typo3tests/set-4', 'typo3tests/set-2', 'typo3tests/set-3', 'typo3tests/set-5', 'typo3tests/set-1'],
            array_keys($sites['site-a'])
        );
        self::assertSame(
            ['typo3tests/set-4', 'typo3tests/set-2', 'typo3tests/set-3', 'typo3tests/set-5', 'typo3tests/set-1', 'typo3tests/set-6'],
            array_keys($sites['site-b'])
        );
        self::assertSame([], $sites['site-c']);
        self::assertSame('Test Set Fixture 1', $sites['site-a']['typo3tests/set-1']['label']);
        self::assertSame(['typo3tests/set-2', 'typo3tests/set-3'], array_keys($sites['site-a']['typo3tests/set-1']['dependencies']));
        self::assertSame(['typo3tests/set-5', 'typo3tests/set-unavailable'], array_keys($sites['site-a']['typo3tests/set-1']['optionalDependencies']));
        self::assertInstanceOf(SettingDefinition::class, $sites['site-a']['typo3tests/set-1']['settingsDefinitions'][0]);
        self::assertSame('foo.bar', $sites['site-a']['typo3tests/set-1']['settingsDefinitions'][0]->key);
        self::assertSame(['foo.baz' => 'bar'], $sites['site-a']['typo3tests/set-6']['settings']);
    }

    #[Test]
    public function allAvailableSetsAreListedInRegistryOrderWithoutSites(): void
    {
        $configuration = $this->get(ProviderRegistry::class)->getProvider('siteSets')->getConfiguration();

        self::assertSame([], $configuration['sites']);
        self::assertSame('Test Set Fixture 1', $configuration['siteSets']['typo3tests/set-1']['label']);
        self::assertSame('Test Set Fixture 6', $configuration['siteSets']['typo3tests/set-6']['label']);
        self::assertSame(['foo.baz' => 'bar'], $configuration['siteSets']['typo3tests/set-6']['settings']);
        self::assertArrayNotHasKey('typo3tests/set-unavailable', $configuration['siteSets']);

        self::assertSame(
            array_keys($this->get(SetRegistry::class)->getAllSets()),
            array_keys($configuration['siteSets'])
        );
    }

    #[Test]
    public function requiredAndOptionalDependenciesIncludeNestedMetadata(): void
    {
        $this->get(SiteWriter::class)->createNewBasicSite('default', 1, 'https://example.com/', ['typo3tests/set-1']);

        $configuration = $this->get(ProviderRegistry::class)->getProvider('siteSets')->getConfiguration();

        foreach ([$configuration['sites']['default']['typo3tests/set-1'], $configuration['siteSets']['typo3tests/set-1']] as $set) {
            self::assertSame(['typo3tests/set-2', 'typo3tests/set-3'], array_keys($set['dependencies']));
            self::assertSame('Test Set Fixture 2', $set['dependencies']['typo3tests/set-2']['label']);
            self::assertSame('Test Set Fixture 4', $set['dependencies']['typo3tests/set-2']['dependencies']['typo3tests/set-4']['label']);
            self::assertSame(['typo3tests/set-5', 'typo3tests/set-unavailable'], array_keys($set['optionalDependencies']));
            self::assertSame('Test Set Fixture 5', $set['optionalDependencies']['typo3tests/set-5']['label']);
            self::assertSame([], $set['optionalDependencies']['typo3tests/set-unavailable']);
            self::assertArrayNotHasKey('dependency', $set);
            self::assertArrayNotHasKey('optionalDependency', $set);
        }
        self::assertSame($configuration['siteSets']['typo3tests/set-1'], $configuration['sites']['default']['typo3tests/set-1']);
    }

    #[Test]
    public function hiddenSetsAreListedWithoutSites(): void
    {
        $collector = $this->get(SetCollector::class);
        $collector->add(new SetDefinition('typo3tests/hidden', 'Hidden set', hidden: true));
        $this->get(SetRegistry::class)->warmupCaches(new CacheWarmupEvent(['system']));

        $configuration = $this->get(ProviderRegistry::class)->getProvider('siteSets')->getConfiguration();

        self::assertTrue($configuration['siteSets']['typo3tests/hidden']['hidden']);
        self::assertSame('Hidden set', $configuration['siteSets']['typo3tests/hidden']['label']);
    }
}
