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

namespace TYPO3\CMS\Scheduler\Task;

use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\IpAnonymizationUtility;

/**
 * Anonymize IP addresses in records
 *
 * This task anonymizes IP addresses in tables older than the given number of days.
 *
 * Available tables must be registered in
 * $GLOBALS['TCA']['tx_scheduler_task']['types'][\TYPO3\CMS\Scheduler\Task\IpAnonymizationTask::class]['taskOptions']['tables']
 *
 * See scheduler_ip_anonymization_task.php of scheduler extension for an example.
 *
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class IpAnonymizationTask extends AbstractTask
{
    /**
     * @var int Number of days
     */
    public $numberOfDays = 180;

    /**
     * @var int mask level see \TYPO3\CMS\Core\Utility\IpAnonymizationUtility::anonymizeIp
     */
    public $mask = 2;

    /**
     * @var string Table to clean up
     */
    public $table = '';

    /**
     * Execute garbage collection, called by scheduler.
     *
     * @throws \RuntimeException If configured table was not cleaned up
     * @return bool TRUE if task run was successful
     */
    public function execute()
    {
        $configuration = $this->getTableConfiguration()[$this->table] ?? [];
        if (empty($configuration)) {
            throw new \RuntimeException(self::class . ' misconfiguration: ' . $this->table . ' does not exist in configuration', 1524502548);
        }
        $this->handleTable($this->table, $configuration);
        return true;
    }

    /**
     * Execute clean up of a specific table
     *
     * @throws \RuntimeException If table configuration is broken
     * @param string $table The table to handle
     * @param array $configuration Clean up configuration
     * @return bool TRUE if cleanup was successful
     */
    protected function handleTable(string $table, array $configuration)
    {
        $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($table);
        $queryBuilder = $connection->createQueryBuilder();
        $queryBuilder->getRestrictions()->removeAll();

        if (empty($configuration['dateField'])) {
            throw new \RuntimeException(self::class . ' misconfiguration: "dateField" must be defined for table ' . $table, 1524502549);
        }
        if (empty($configuration['ipField'])) {
            throw new \RuntimeException(self::class . ' misconfiguration: "ipField" must be defined for table ' . $table, 1524502666);
        }
        $deleteTimestamp = strtotime('-' . $this->numberOfDays . 'days');
        if ($deleteTimestamp === false) {
            throw new \RuntimeException(self::class . ' misconfiguration: number of days could not be calculated for table ' . $table, 1524526354);
        }
        if ($this->mask === 2) {
            $notLikeMaskPattern = '%.0.0';
        } else {
            $notLikeMaskPattern = '%.0';
        }
        try {
            $result = $queryBuilder
                ->select('uid', $configuration['ipField'])
                ->where(
                    $queryBuilder->expr()->lt(
                        $configuration['dateField'],
                        $queryBuilder->createNamedParameter($deleteTimestamp, Connection::PARAM_INT)
                    ),
                    $queryBuilder->expr()->neq(
                        $configuration['ipField'],
                        $queryBuilder->createNamedParameter('')
                    ),
                    $queryBuilder->expr()->isNotNull($configuration['ipField']),
                    $queryBuilder->expr()->notLike(
                        $configuration['ipField'],
                        $queryBuilder->createNamedParameter($notLikeMaskPattern)
                    ),
                    $queryBuilder->expr()->notLike(
                        $configuration['ipField'],
                        $queryBuilder->createNamedParameter('%::')
                    )
                )
                ->from($table)
                ->executeQuery();

            while ($row = $result->fetchAssociative()) {
                $ip = (string)$row[$configuration['ipField']];

                $connection->update(
                    $table,
                    [
                        $configuration['ipField'] => IpAnonymizationUtility::anonymizeIp($ip, (int)$this->mask),
                    ],
                    [
                        'uid' => $row['uid'],
                    ]
                );
            }
        } catch (\Exception $e) {
            throw new \RuntimeException(self::class . ' failed for table ' . $this->table . ' with error: ' . $e->getMessage(), 1524502550);
        }
        return true;
    }

    public function getAdditionalInformation()
    {
        return sprintf($this->getLanguageService()->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.ipAnonymization.additionalInformationTable'), $this->table, $this->numberOfDays);
    }

    public function getTaskParameters(): array
    {
        return [
            'number_of_days' => $this->numberOfDays,
            'ip_mask' => $this->mask,
            'selected_tables' => $this->table,
        ];
    }

    public function setTaskParameters(array $parameters): void
    {
        $this->table = (string)($parameters['table'] ?? $parameters['selected_tables'] ?? '');
        $this->numberOfDays = (int)($parameters['numberOfDays'] ?? $parameters['number_of_days'] ?? 180);
        $this->mask = (int)($parameters['mask'] ?? $parameters['ip_mask'] ?? 2);
    }

    public function getAnonymizableTables(array &$config): void
    {
        foreach ($this->getTableConfiguration() as $tableName => $tableConfiguration) {
            $config['items'][] = [
                'label' => $tableName . (($tableConfiguration['ipField'] ?? false) ? ' [ipField: ' . $tableConfiguration['ipField'] . ']' : '') . (($tableConfiguration['dateField'] ?? false) ? ' [dateField: ' . $tableConfiguration['dateField'] . ']' : ''),
                'value' => $tableName,
            ];
        }
    }

    public function getTableConfiguration(): array
    {
        $tableConfiguration = GeneralUtility::makeInstance(TcaSchemaFactory::class)->get('tx_scheduler_task.' . self::class)->getRawConfiguration()['taskOptions']['tables'] ?? [];

        $tableConfigurationFromConfVars = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['scheduler']['tasks'][self::class]['options']['tables'] ?? [];
        if (!empty($tableConfigurationFromConfVars)) {
            trigger_error('Usage of $GLOBALS[\'TYPO3_CONF_VARS\'][\'SC_OPTIONS\'][\'scheduler\'][\'tasks\'][' . self::class . '][\'options\'][\'tables\'] to define table options is deprecated and will stop working in TYPO3 v15. Use $tca[\'tx_scheduler_task\'][\'types\'][' . self::class . '][\'taskOptions\'][\'tables\'] instead.', E_USER_DEPRECATED);
            if (is_array($tableConfigurationFromConfVars)) {
                $tableConfiguration = array_replace_recursive($tableConfiguration, $tableConfigurationFromConfVars);
            }
        }

        return $tableConfiguration;
    }
}
