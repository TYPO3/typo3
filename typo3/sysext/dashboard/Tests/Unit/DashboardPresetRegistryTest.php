<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Dashboard\Tests\Unit;

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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\DashboardPreset;
use TYPO3\CMS\Dashboard\DashboardPresetRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class DashboardPresetRegistryTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

    /** @var DashboardPresetRegistry  */
    protected $subject;

    public function setUp(): void
    {
        $this->subject = GeneralUtility::makeInstance(
            DashboardPresetRegistry::class
        );
    }

    /**
     * @test
     */
    public function withoutRegisteredPresetsOnlyFallbackPresetsIsReturned(): void
    {
        $presets = $this->subject->getDashboardPresets();
        self::assertCount(1, $presets);

        self::assertEquals('dashboardPreset-fallback', array_key_first($presets));
        self::assertInstanceOf(DashboardPreset::class, reset($presets));
    }

    /**
     * @test
     */
    public function getWidgetsMethodReturnsDashboardPresetsObjects(): void
    {
        $dashboardPreset1 = new DashboardPreset('identifier1', 'title1', 'description1');
        $dashboardPreset2 = new DashboardPreset('identifier2', 'title2', 'description2');

        $this->subject->registerDashboardPreset($dashboardPreset1);
        $this->subject->registerDashboardPreset($dashboardPreset2);

        foreach ($this->subject->getDashboardPresets() as $identifier => $dashboardPresetObject) {
            self::assertInstanceOf(DashboardPreset::class, $dashboardPresetObject);
        }
    }

    /**
     * @test
     */
    public function dashboardPresetsGetRegistered(): void
    {
        // If no dashboard preset is registered, it will return a fallback preset
        self::assertCount(1, $this->subject->getDashboardPresets());

        // Register a first dashboard template. No fallback will be added anymore
        $dashboardPreset = new DashboardPreset('identifier1', 'title1', 'description1');
        $this->subject->registerDashboardPreset($dashboardPreset);

        // Check if 1 dashboard template is found
        self::assertCount(1, $this->subject->getDashboardPresets());

        // Register same dashboard template again
        $this->subject->registerDashboardPreset($dashboardPreset);
        self::assertCount(1, $this->subject->getDashboardPresets());

        // Register new dashboard template and check if it is registered successfully
        $dashboardPreset2 = new DashboardPreset('identifier2', 'title2', 'description2');
        $this->subject->registerDashboardPreset($dashboardPreset2);

        $dashboardPresets = $this->subject->getDashboardPresets();
        self::assertCount(2, $dashboardPresets);

        // Check if the identifiers are correctly registered
        self::assertEquals(['identifier1', 'identifier2'], array_keys($dashboardPresets));
    }
}
