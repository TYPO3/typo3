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

/**
 * Provides API for widgets.
 */
class WidgetApi
{
    /**
     * Provides default colors to use for charts.
     *
     * @return array Hex codes of default colors.
     */
    public static function getDefaultChartColors(): array
    {
        return [
            '#ff8700',
            '#a4276a',
            '#1a568f',
            '#4c7e3a',
            '#69bbb5',
        ];
    }
}
