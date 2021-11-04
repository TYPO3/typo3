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

use TYPO3\CMS\Core\Page\JavaScriptModuleInstruction;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Concrete Doughnut Chart widget implementation
 *
 * Shows a widget with a doughnut chart. The data for this chart will be provided by the data provider you will set.
 * You can add a button to the widget by defining a button provider.
 *
 * There are no options available for this widget
 *
 * @see ChartDataProviderInterface
 * @see ButtonProviderInterface
 */
class DoughnutChartWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, JavaScriptInterface
{
    /**
     * @var WidgetConfigurationInterface
     */
    private $configuration;

    /**
     * @var ChartDataProviderInterface
     */
    private $dataProvider;

    /**
     * @var StandaloneView
     */
    private $view;

    /**
     * @var ButtonProviderInterface|null
     */
    private $buttonProvider;

    /**
     * @var array
     */
    private $options;

    public function __construct(
        WidgetConfigurationInterface $configuration,
        ChartDataProviderInterface $dataProvider,
        StandaloneView $view,
        $buttonProvider = null,
        array $options = []
    ) {
        $this->configuration = $configuration;
        $this->dataProvider = $dataProvider;
        $this->view = $view;
        $this->options = $options;
        $this->buttonProvider = $buttonProvider;
    }

    public function renderWidgetContent(): string
    {
        $this->view->setTemplate('Widget/ChartWidget');
        $this->view->assignMultiple([
            'button' => $this->buttonProvider,
            'options' => $this->options,
            'configuration' => $this->configuration,
        ]);
        return $this->view->render();
    }

    public function getEventData(): array
    {
        return [
            'graphConfig' => [
                'type' => 'doughnut',
                'options' => [
                    'maintainAspectRatio' => false,
                    'legend' => [
                        'display' => true,
                        'position' => 'bottom',
                    ],
                    'cutoutPercentage' => 60,
                ],
                'data' => $this->dataProvider->getChartData(),
            ],
        ];
    }

    public function getCssFiles(): array
    {
        return ['EXT:dashboard/Resources/Public/Css/Contrib/chart.css'];
    }

    public function getJavaScriptModuleInstructions(): array
    {
        return [
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Dashboard/Contrib/chartjs'),
            JavaScriptModuleInstruction::forRequireJS('TYPO3/CMS/Dashboard/ChartInitializer'),
        ];
    }

    /**
     * @return array
     */
    public function getOptions(): array
    {
        return $this->options;
    }
}
