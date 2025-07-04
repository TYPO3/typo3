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

namespace TYPO3\CMS\Core\Tests\Functional\Site\Entity;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\SetError;
use TYPO3\CMS\Core\Site\Set\SetRegistry;
use TYPO3\CMS\Core\Site\SiteSettingsFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SiteSettingsTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_site_settings',
    ];

    #[Test]
    public function retrieveArraySetting(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-set-1']);

        // Available because of definition from settings.definitions.yaml
        self::assertTrue($settings->has('foo.bar.baz'));
        self::assertSame(['a', 'b'], $settings->get('foo.bar.baz'));
        // Available because of legacy array-flattening
        self::assertTrue($settings->has('foo.bar.baz.0'));
        self::assertSame('a', $settings->get('foo.bar.baz.0'));
        self::assertTrue($settings->has('foo.bar.baz.1'));
        self::assertSame('b', $settings->get('foo.bar.baz.1'));
        self::assertSame(['foo.bar.baz'], $settings->getIdentifiers());
    }

    #[Test]
    public function noAccessUndefinedArray(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-set-1']);

        // Not available because there is no settings key named `foo.bar`
        self::assertFalse($settings->has('foo.bar'));
        self::assertNull($settings->get('foo.bar'));
    }

    #[Test]
    public function excpetionIsThrownForInvalidSettingsDefault(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-set-with-invalid-settings-default']);

        self::assertTrue($settings->isEmpty());
        self::assertFalse($settings->has('foo.bar.baz'));
        self::assertSame([], $settings->getIdentifiers());

        $invalidSets = $this->get(SetRegistry::class)->getInvalidSets();
        self::assertSame(SetError::invalidSettingsDefinitions, $invalidSets['typo3tests/site-settings-set-with-invalid-settings-default']['error']);
        self::assertStringContainsString('Invalid default value', $invalidSets['typo3tests/site-settings-set-with-invalid-settings-default']['context']);
    }

    #[Test]
    public function exceptionIsThrownForInvalidSettingsType(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-set-with-invalid-settings-type']);

        self::assertTrue($settings->isEmpty());
        self::assertFalse($settings->has('foo.bar.baz'));
        self::assertSame([], $settings->getIdentifiers());

        $invalidSets = $this->get(SetRegistry::class)->getInvalidSets();
        self::assertSame(SetError::invalidSettingsDefinitions, $invalidSets['typo3tests/site-settings-set-with-invalid-settings-type']['error']);
        self::assertStringContainsString('Invalid settings type', $invalidSets['typo3tests/site-settings-set-with-invalid-settings-type']['context']);
    }

    #[Test]
    public function retrieveSettingsFromSettingsMap(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-map']);

        // Available because of definition from settings.definitions.yaml
        self::assertSame('baz', $settings->get('foo.bar'));
        self::assertTrue($settings->has('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsMapWithMapOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo.bar' => 'override'], ['typo3tests/site-settings-map']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsMapWithTreeOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo' => ['bar' => 'override']], ['typo3tests/site-settings-map']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsMapWithMapAndTreeOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo.bar' => 'override2', 'foo' => ['bar' => 'override']], ['typo3tests/site-settings-map']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override2', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsTree(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings([], ['typo3tests/site-settings-legacy-tree']);

        // Available because of definition from settings.definitions.yaml
        self::assertSame('baz', $settings->get('foo.bar'));
        self::assertTrue($settings->has('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsTreeWithMapOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo.bar' => 'override'], ['typo3tests/site-settings-legacy-tree']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsTreeWithTreeOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo' => ['bar' => 'override']], ['typo3tests/site-settings-legacy-tree']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }

    #[Test]
    public function retrieveSettingsFromSettingsTreeWithMapAndTreeOverride(): void
    {
        $siteSettingsFactory = $this->get(SiteSettingsFactory::class);
        $settings = $siteSettingsFactory->composeSettings(['foo.bar' => 'override2', 'foo' => ['bar' => 'override']], ['typo3tests/site-settings-legacy-tree']);

        self::assertTrue($settings->has('foo.bar'));
        self::assertSame('override2', $settings->get('foo.bar'));
        self::assertSame(['foo.bar'], $settings->getIdentifiers());
    }
}
