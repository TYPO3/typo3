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

use TYPO3\CMS\Dashboard\Widgets\Interfaces\AdditionalCssInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\EventDataInterface;
use TYPO3\CMS\Dashboard\Widgets\Interfaces\RequireJsModuleInterface;

/**
 * The AbstractChartWidget class is the basic widget class for all chart widgets.
 * It is possible to extend this class for custom widgets. EXT:dashboard also provides
 * more special chart types for widgets (bar chart and doughnut chart).
 */
abstract class AbstractChartWidget extends AbstractWidget implements AdditionalCssInterface, RequireJsModuleInterface, EventDataInterface
{
    /**
     * The type of chart you want to show. The types of charts that are available can be found in the
     * chart.js documentation.
     *
     * @link https://www.chartjs.org/docs/latest/charts/
     *
     * Currently the following chart types are implemented:
     * @see AbstractBarChartWidget
     * @see AbstractDoughnutChartWidget
     *
     * @var string
     */
    protected $chartType = '';

    /**
     * This property should contain the data for the graph. The data and options you have depend on the type
     * of chart. More information can be found in the documentation of the specific type.
     *
     * @link https://www.chartjs.org/docs/latest/charts/bar.html#data-structure
     * @link https://www.chartjs.org/docs/latest/charts/doughnut.html#data-structure
     *
     * @var array
     */
    protected $chartData = [];

    /**
     * This property should contain the options to configure your graph. The options available are based on the type
     * of chart. The implementations of the charts will contain the default options for that type of graph.
     *
     * @see AbstractBarChartWidget::$chartOptions
     * @see AbstractDoughnutChartWidget::$chartOptions
     *
     * @var array
     */
    protected $chartOptions = [];

    /**
     * This property can be used to pass data to the JavaScript that will handle the content rendering. For
     * charts, the only property necessary for charts is the graphConfig element. This will be set in the
     * getEventData method of this class. Setting this property manually is therefore not needed.
     *
     * @see getEventData
     *
     * @var array
     */
    protected $eventData = [];

    /**
     * The default colors that will be used for the graphs.
     *
     * @var string[]
     */
    protected $chartColors = ['#ff8700', '#a4276a', '#1a568f', '#4c7e3a', '#69bbb5'];

    /**
     * If you want to show a button below the graph, you need to set the title and the link to the button.
     * This text can be a fixed string or can contain a translatable string.
     *
     * @var string
     */
    protected $buttonText = '';

    /**
     * The link of the button. Besides the link, also the buttonText should be set before the buttons will be
     * rendered.
     *
     * @var string
     */
    protected $buttonLink = '';

    /**
     * By default the link of the button will be opened in the current frame. By setting the target, you can specify
     * where the link should be opened.
     *
     * @var string
     */
    protected $buttonTarget = '';

    /**
     * This CSS class is used so the JavaScript can identify the widgets containing graphs. Overriding this
     * property could lead to the widget not working.
     *
     * @internal
     *
     * @var string
     */
    protected $additionalClasses = 'dashboard-item--chart';

    /**
     * @inheritDoc
     */
    protected $iconIdentifier = 'content-widget-chart';

    /**
     * @inheritDoc
     */
    protected $templateName = 'ChartWidget';

    /**
     * This method is used to define the data that will be shown in the graph. This method should be implemented
     * and should set the data in the chartData property.
     *
     * @see chartData
     */
    abstract protected function prepareChartData(): void;

    protected function initializeView(): void
    {
        parent::initializeView();

        if ($this->buttonLink && $this->buttonText) {
            $this->view->assign(
                'button',
                [
                    'text' => $this->getLanguageService()->sL($this->buttonText) ?: $this->buttonText,
                    'link' => $this->buttonLink,
                    'target' => $this->buttonTarget
                ]
            );
        }
    }

    public function getEventData(): array
    {
        $this->prepareChartData();
        $this->eventData['graphConfig'] = [
            'type' => $this->chartType,
            'data' => $this->chartData,
            'options' => $this->chartOptions
        ];
        return $this->eventData;
    }

    public function getCssFiles(): array
    {
        return ['EXT:dashboard/Resources/Public/Css/Contrib/chart.css'];
    }

    public function getRequireJsModules(): array
    {
        return [
            'TYPO3/CMS/Dashboard/Contrib/chartjs',
            'TYPO3/CMS/Dashboard/ChartInitializer',
        ];
    }
}
