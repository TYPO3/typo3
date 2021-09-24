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

namespace TYPO3\CMS\Dashboard;

use TYPO3\CMS\Core\SingletonInterface;

/**
 * @internal
 */
class DashboardPresetRegistry implements SingletonInterface
{
    /**
     * @var DashboardPreset[]
     */
    private $dashboardPresets = [];

    /**
     * @return array
     */
    public function getDashboardPresets(): array
    {
        if (empty($this->dashboardPresets)) {
            $fallbackDashboardPreset = new DashboardPreset(
                'dashboardPreset-fallback',
                'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:dashboard.default',
                '',
                'content-dashboard',
                [],
                false
            );

            return [
                'dashboardPreset-fallback' => $fallbackDashboardPreset,
            ];
        }

        return $this->dashboardPresets;
    }

    /**
     * @param DashboardPreset $dashboardPreset
     */
    public function registerDashboardPreset(DashboardPreset $dashboardPreset): void
    {
        $this->dashboardPresets[$dashboardPreset->getIdentifier()] = $dashboardPreset;
    }
}
