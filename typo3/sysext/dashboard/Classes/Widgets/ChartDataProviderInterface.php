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

namespace TYPO3\CMS\Dashboard\Widgets;

/**
 * Defines API for provider, used for chart widgets.
 */
interface ChartDataProviderInterface
{
    /**
     * This method should provide the data for the graph.
     * The data and options you have depend on the type of chart.
     * More information can be found in the documentation of the specific type.
     *
     * @link https://www.chartjs.org/docs/latest/charts/bar.html#data-structure
     * @link https://www.chartjs.org/docs/latest/charts/doughnut.html#data-structure
     *
     * @return array
     */
    public function getChartData(): array;
}
