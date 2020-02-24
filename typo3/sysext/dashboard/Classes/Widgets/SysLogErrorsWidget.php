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

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\ExtensionManagementUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This widget will show the number of system log errors in a bar chart
 */
class SysLogErrorsWidget extends AbstractBarChartWidget
{
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.title';
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.description';
    protected $buttonText = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.buttonText';
    protected $width = 4;
    protected $height = 4;
    protected $data = [];
    protected $labels = [];

    protected function initializeView(): void
    {
        if (ExtensionManagementUtility::isLoaded('belog')) {
            $uriBuilder = GeneralUtility::makeInstance(UriBuilder::class);
            $this->buttonLink = (string)$uriBuilder->buildUriFromRoute(
                'system_BelogLog',
                ['tx_belog_system_beloglog[constraint][action]' => -1]
            );
        }

        parent::initializeView();
    }
    /**
     * @inheritDoc
     */
    protected function prepareChartData(): void
    {
        $this->calculateDataForLastDays(31);

        $this->chartData = [
            'labels' => $this->labels,
            'datasets' => [
                [
                    'label' => $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.sysLogErrors.chart.dataSet.0'),
                    'backgroundColor' => $this->chartColors[0],
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

    protected function calculateDataForLastDays(int $days): void
    {
        $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';

        for ($daysBefore=$days; $daysBefore>=0; $daysBefore--) {
            $this->labels[] = date($format, strtotime('-' . $daysBefore . ' day'));
            $startPeriod = strtotime('-' . $daysBefore . ' day 0:00:00');
            $endPeriod =  strtotime('-' . $daysBefore . ' day 23:59:59');

            $this->data[] = $this->getNumberOfErrorsInPeriod($startPeriod, $endPeriod);
        }
    }
}
