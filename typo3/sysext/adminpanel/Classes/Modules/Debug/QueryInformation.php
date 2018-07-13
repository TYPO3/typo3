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

use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Log\DoctrineSqlLogger;
use TYPO3\CMS\Adminpanel\ModuleApi\AbstractSubModule;
use TYPO3\CMS\Adminpanel\ModuleApi\ContentProviderInterface;
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
class QueryInformation extends AbstractSubModule implements DataProviderInterface, ContentProviderInterface
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
     * @param ServerRequestInterface $request
     * @return \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData
     * @throws \Doctrine\DBAL\DBALException
     */
    public function getDataToStore(ServerRequestInterface $request): ModuleData
    {
        $connectionPool = GeneralUtility::makeInstance(ConnectionPool::class);
        $connection = $connectionPool->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME);
        $logger = $connection->getConfiguration()->getSQLLogger();
        $data = [];
        if ($logger instanceof DoctrineSqlLogger) {
            $queries = $logger->getQueries();
            $data['queries'] = $this->groupQueries($queries) ?? [];
            $data['totalTime'] = array_sum(array_column($queries, 'executionMS')) * 1000;
        }
        return new ModuleData($data);
    }

    /**
     * @param \TYPO3\CMS\Adminpanel\ModuleApi\ModuleData $data
     * @return string Returns content of admin panel
     */
    public function getContent(ModuleData $data): string
    {
        $view = new StandaloneView();
        $view->setTemplatePathAndFilename(
            'EXT:adminpanel/Resources/Private/Templates/Modules/Debug/QueryInformation.html'
        );
        $this->getLanguageService()->includeLLFile('EXT:adminpanel/Resources/Private/Language/locallang_debug.xlf');
        $view->assignMultiple($data->getArrayCopy());
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
