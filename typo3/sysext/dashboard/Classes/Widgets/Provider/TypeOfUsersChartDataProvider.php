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
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Dashboard\WidgetApi;
use TYPO3\CMS\Dashboard\Widgets\ChartDataProviderInterface;

class TypeOfUsersChartDataProvider implements ChartDataProviderInterface
{
    /**
     * @var LanguageService
     */
    private $languageService;

    public function __construct(LanguageService $languageService)
    {
        $this->languageService = $languageService;
    }

    /**
     * @inheritDoc
     */
    public function getChartData(): array
    {
        $adminUsers = $this->getNumberOfUsers(true);
        $normalUsers = $this->getNumberOfUsers(false);

        return [
            'labels' => [
                $this->languageService ->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.normalUsers'),
                $this->languageService ->sL('LLL:EXT:dashboard/Resources/Private/Language/locallang.xlf:widgets.typeOfUsers.adminUsers')
            ],
            'datasets' => [
                [
                    'backgroundColor' => WidgetApi::getDefaultChartColors(),
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
