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

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * This widget will show the type of users (admin / non-admin) in a doughnut chart
 */
class TypeOfUsersWidget extends AbstractDoughnutChartWidget
{
    protected $title = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.title';
    protected $description = 'LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.description';

    /**
     * @inheritDoc
     */
    protected function prepareChartData(): void
    {
        $adminUsers = $this->getNumberOfUsers(true);
        $normalUsers = $this->getNumberOfUsers(false);

        $this->chartData = [
            'labels' => [
                $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.normalUsers'),
                $this->getLanguageService()->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.adminUsers')
            ],
            'datasets' => [
                [
                    'backgroundColor' => [$this->chartColors[0], $this->chartColors[1]],
                    'data' => [$normalUsers, $adminUsers]
                ]
            ],
        ];
    }

    protected function getNumberOfUsers(bool $admin = false): int
    {
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('be_users');
        return (int)$queryBuilder
            ->count('*')
            ->from('be_users')
            ->where(
                $queryBuilder->expr()->eq(
                    'admin',
                    $queryBuilder->createNamedParameter($admin ? 1 : 0, Connection::PARAM_INT)
                )
            )
            ->execute()
            ->fetchColumn();
    }
}
