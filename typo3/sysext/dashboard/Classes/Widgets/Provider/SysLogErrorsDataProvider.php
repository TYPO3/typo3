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

namespace TYPO3\CMS\Dashboard\Widgets\Provider;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\SysLog\Type as SystemLogType;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

/**
 * Provides chart data for sys log errors.
 */
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

    /**
     * @inheritDoc
     */
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
                    'data' => $this->data,
                ],
            ],
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
            ->executeQuery()
            ->fetchOne();
    }

    protected function calculateDataForLastDays(): void
    {
        $format = $GLOBALS['TYPO3_CONF_VARS']['SYS']['ddmmyy'] ?: 'Y-m-d';

        for ($daysBefore = $this->days; $daysBefore >= 0; $daysBefore--) {
            $this->labels[] = date($format, (int)strtotime('-' . $daysBefore . ' day'));
            $startPeriod = (int)strtotime('-' . $daysBefore . ' day 0:00:00');
            $endPeriod =  (int)strtotime('-' . $daysBefore . ' day 23:59:59');

            $this->data[] = $this->getNumberOfErrorsInPeriod($startPeriod, $endPeriod);
        }
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
