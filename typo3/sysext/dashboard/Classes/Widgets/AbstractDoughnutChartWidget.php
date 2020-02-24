<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Dashboard\Widgets;

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

/**
 * The AbstractDoughnutChartWidget class is the basic widget class for doughnut charts.
 * It is possible to extend this class for custom widgets.
 * In your class you have to store the data to display in $this->chartData.
 * More information can be found in the documentation.
 */
abstract class AbstractDoughnutChartWidget extends AbstractChartWidget
{
    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-chart-pie';

    /**
     * @inheritDoc
     */
    protected $chartType = 'doughnut';

    /**
     * @inheritDoc
     */
    protected $chartOptions = [
        'maintainAspectRatio' => false,
        'legend' => [
            'display' => true,
            'position' => 'bottom'
        ],
        'cutoutPercentage' => 60
    ];

    protected $height = 4;
}
