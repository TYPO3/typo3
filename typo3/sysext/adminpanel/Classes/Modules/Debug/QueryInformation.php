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

namespace TYPO3\CMS\Adminpanel\Modules\Debug;

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Log\DoctrineSqlLoggingMiddleware;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\DataProviderInterface;
use TYPO3\CMS\Adminpanel\ModuleApi\ModuleData;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Fluid\View\StandaloneView;

/**
 * Admin Panel Query Information module for showing SQL Queries
 *
 * @internal
 */
class QueryInformation extends AbstractSubModule implements DataProviderInterface
{
    /**
     * Identifier for this Sub-module,
     * for example "preview" or "cache"
     */
    public function getIdentifier(): string
    {
        return 'debug_queryinformation';
    }

    /**
     * Sub-Module label
     */
    public function getLabel(): string
    {
        return $this->getLanguageService()->sL(
            'LLL:EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf:submodule.queryInformation.label'
        );
    }

    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);

        $loggingMiddleware = null;
        foreach ($connection->getConfiguration()->getMiddlewares() as $middleware) {
            if ($middleware instanceof DoctrineSqlLoggingMiddleware) {
                $loggingMiddleware = $middleware;
                break;
            }
        }

        $data = [];
        if ($loggingMiddleware !== null) {
            $queries = $loggingMiddleware->getQueries();
            $data['totalQueries'] = count($queries);
            $data['queries'] = $this->groupQueries($queries);
            $data['totalTime'] = array_sum(array_column($queries, 'executionMS')) * 1000;
        }
        return new ModuleData($data);
    }

    public function getContent(ModuleData $data): string
    {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename(
            'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/QueryInformation.html'
        );
        $view->assignMultiple($data->getArrayCopy());
        $view->assign('languageKey', $this->getBackendUser()->user['lang'] ?? null);
        return $view->render();
    }

    protected function groupQueries(array $queries): array
    {
        $groupedQueries = [];
        foreach ($queries as $query) {
            $backtraceString = json_encode($query['backtrace']);
            if ($backtraceString === false) {
                // skip entry if it can't be encoded
                continue;
            }
            $identifier = sha1($query['sql']) . sha1($backtraceString);
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
            static function (array $a, array $b): int {
                return $b['time'] <=> $a['time'];
            }
        );
        return $groupedQueries;
    }
}
