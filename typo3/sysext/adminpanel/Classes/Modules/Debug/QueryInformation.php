<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Modules\Debug;

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

use TYPO3\CMS\Adminpanel\Log\DoctrineSqlLogger;
use TYPO3\CMS\Adminpanel\Modules\AbstractSubModule;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Admin Panel Query Information module for showing SQL Queries
 */
class QueryInformation extends AbstractSubModule
{
    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     *
     * @return string
     */
    public function getIdentifier(): string
    {
        return 'debug_queryinformation';
    }

    /**
     * Sub-Module label
     *
     * @return string
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.queryInformation.label'
        );
    }

    /**
     * @return string Returns content of admin panel
     * @throws \UnexpectedValueException
     * @throws \InvalidArgumentException
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getContent(): string
    {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename(
            'typo3/sysext/adminpanel/Resources/Private/Templates/Modules/Debug/QueryInformation.html'
        );
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $logger = $connection->getConfiguration()->getSQLLogger();
        $this->getLanguageService()->includeLLFile('EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf');
        if ($logger instanceof DoctrineSqlLogger) {
            $queries = $logger->getQueries();
            $this->queryCount = \count($queries);
            $groupedQueries = $this->groupQueries($queries);
            $totalTime = array_sum(array_column($queries, 'executionMS')) * 1000;
            $view->assign('queries', $groupedQueries ?? [])->assign('totalTime', $totalTime);
        }
        return $view->render();
    }

    /**
     * @param array $queries
     * @return array
     */
    protected function groupQueries(array $queries): array
    {
        $groupedQueries = [];
        foreach ($queries as $query) {
            $identifier = sha1($query['sql']) . sha1(implode(',', $query['backtrace']));
            if (is_array($query['params'])) {
                foreach ($query['params'] as $k => $param) {
                    if (is_array($param)) {
                        $query['params'][$k] = implode(',', $param);
                    }
                }
            }
            if (isset($groupedQueries[$identifier])) {
                $groupedQueries[$identifier]['count']++;
                $groupedQueries[$identifier]['time'] += ($query['executionMS'] * 1000);
                $groupedQueries[$identifier]['queries'][] = $query;
            } else {
                $groupedQueries[$identifier] = [
                    'sql' => $query['sql'],
                    'time' => $query['executionMS'] * 1000,
                    'count' => 1,
                    'queries' => [
                        $query,
                    ],
                ];
            }
        }
        uasort(
            $groupedQueries,
            function ($a, $b) {
                return $b['time'] <=> $a['time'];
            }
        );
        return $groupedQueries;
    }
}
