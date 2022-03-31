.. include:: /Includes.rst.txt

.. highlight:: php

.. _graph-widget-implementation:

======================
Implement graph widget
======================

.. php:namespace:: TYPO3\CMS\Dashboard\Widgets

First of all a new data provider is required, which will provide the data for the chart.
Next the data will be provided to the widget instance, which will be rendered with RequireJS modules and Css.

To make the dashboard aware of this workflow, some interfaces come together:

* :php:class:`EventDataInterface`

* :php:class:`AdditionalCssInterface`

* :php:class:`RequireJsModuleInterface`

Also the existing template file :file:`Widget/ChartWidget` is used, which provides necessary HTML to render the chart.
The provided ``eventData`` will be rendered as a chart and therefore has to match the expected structure.

An example would be :file:`Classes/Widgets/BarChartWidget.php`::

   class BarChartWidget implements WidgetInterface, EventDataInterface, AdditionalCssInterface, RequireJsModuleInterface
   {
       /**
        * @var ChartDataProviderInterface
        */
       private $dataProvider;

       public function __construct(
           // …
           ChartDataProviderInterface $dataProvider,
           // …
       ) {
           // …
           $this->dataProvider = $dataProvider;
           // …
       }

       public function renderWidgetContent(): string
       {
           $this->view->setTemplate('Widget/ChartWidget');
           $this->view->assignMultiple([
               // …
               'configuration' => $this->configuration,
               // …
           ]);
           return $this->view->render();
       }

       public function getEventData(): array
       {
           return [
               'graphConfig' => [
                   'type' => 'bar',
                   'options' => [
                       // …
                   ],
                   'data' => $this->dataProvider->getChartData(),
               ],
           ];
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

Together with :file:`Services.yaml`:

.. code-block:: yaml

   services:
     dashboard.widget.sysLogErrors:
       class: 'TYPO3\CMS\Dashboard\Widgets\BarChartWidget'
       arguments:
         # …
         $dataProvider: '@TYPO3\CMS\Dashboard\Widgets\Provider\SysLogErrorsDataProvider'
         # …
       tags:
         - name: dashboard.widget

The configuration adds necessary CSS classes, as well as the ``dataProvider`` to use.
The provider implements :php:class:`ChartDataProviderInterface` and could look like the following.

:file:`Classes/Widgets/Provider/SysLogErrorsDataProvider`::

   class SysLogErrorsDataProvider implements ChartDataProviderInterface
   {
       /**
        * Number of days to gather information for.
        *
        * @var int
        */
       protected $days = 31;

       /**
        * @var array
        */
       protected $labels = [];

       /**
        * @var array
        */
       protected $data = [];

       public function __construct(int $days = 31)
       {
           $this->days = $days;
       }

       public function getChartData(): array
       {
           $this->calculateDataForLastDays();
           return [
               'labels' => $this->labels,
               'datasets' => [
                   [
                       'label' => $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.chart.dataSet.0'),
                       'backgroundColor' => WidgetApi::getDefaultChartColors()[0],
                       'border' => 0,
                       'data' => $this->data
                   ]
               ]
           ];
       }

       protected function getNumberOfErrorsInPeriod(int $start, int $end): int
       {
           $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
           return (int)$queryBuilder
               ->count('*')
               ->from('sys_log')
               ->where(
                   $queryBuilder->expr()->eq(
                       'type',
                       $queryBuilder->createNamedParameter(SystemLogType::ERROR, Connection::PARAM_INT)
                   ),
                   $queryBuilder->expr()->gte(
                       'tstamp',
                       $queryBuilder->createNamedParameter($start, Connection::PARAM_INT)
                   ),
                   $queryBuilder->expr()->lte(
                       'tstamp',
                       $queryBuilder->createNamedParameter($end, Connection::PARAM_INT)
                   )
               )
               ->execute()
               ->fetchColumn();
       }

       protected function calculateDataForLastDays(): void
       {
           $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';
           for ($daysBefore = $this->days; $daysBefore >= 0; $daysBefore--) {
               $this->labels[] = date($format, strtotime('-' . $daysBefore . ' day'));
               $startPeriod = strtotime('-' . $daysBefore . ' day 0:00:00');
               $endPeriod =  strtotime('-' . $daysBefore . ' day 23:59:59');
               $this->data[] = $this->getNumberOfErrorsInPeriod($startPeriod, $endPeriod);
           }
       }

       protected function getLanguageService(): LanguageService
       {
           return $GLOBALS['LANG'];
       }
   }
