<?php

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

namespace TYPO3\CMS\Lowlevel\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Log\LogDataTrait;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Lists all sys_log entries from the last 24 hours by default
 * This is the most basic and can be useful for nightly check test reports.
 */
class ListSysLogCommand extends Command
{
    use LogDataTrait;

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this->setHelp('Prints a list of recent sys_log entries.' . LF . 'If you want to get more detailed information, use the --verbose option.');
    }

    /**
     * Executes the command for showing sys_log entries
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());
        $showDetails = $output->getVerbosity() > OutputInterface::VERBOSITY_NORMAL;

        $tableHeaders = [
            'Log ID',
            'Date & Time',
            'User ID',
            'Message',
        ];
        if ($showDetails) {
            $tableHeaders[] = 'Details';
        }

        // Initialize result array
        $content = [];

        // Select DB relations from reference table
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)->getQueryBuilderForTable('sys_log');
        $rowIterator = $queryBuilder
            ->select('*')
            ->from('sys_log')
            ->where(
                $queryBuilder->expr()->gt(
                    'tstamp',
                    $queryBuilder->createNamedParameter($GLOBALS['EXEC_TIME'] - 24 * 3600, \PDO::PARAM_INT)
                )
            )
            ->orderBy('tstamp', 'DESC')
            ->executeQuery();

        while ($row = $rowIterator->fetchAssociative()) {
            $logData = $this->unserializeLogData($row['log_data'] ?? '');
            $text = $this->formatLogDetails($row['details'] ?? '', $logData);
            $userInformation = $row['userid'];
            if (!empty($logData['originalUser'] ?? null)) {
                $userInformation .= ' via ' . $logData['originalUser'];
            }

            $result = [
                $row['uid'],
                BackendUtility::datetime($row['tstamp']),
                $userInformation,
                $text,
            ];

            if ($showDetails) {
                $result[] = $this->arrayToLogString($row, [
                    'uid',
                    'userid',
                    'action',
                    'recuid',
                    'tablename',
                    'recpid',
                    'error',
                    'tstamp',
                    'type',
                    'details_nr',
                    'IP',
                    'event_pid',
                    'NEWid',
                    'workspace',
                ]);
            }
            $content[] = $result;
        }
        $io->table($tableHeaders, $content);
        return 0;
    }

    /**
     * Converts a one dimensional array to a one line string which can be used for logging or debugging output
     * Example: "loginType: FE; refInfo: Array; HTTP_HOST: www.example.org; REMOTE_ADDR: 192.168.1.5; REMOTE_HOST:; security_level:; showHiddenRecords: 0;"
     *
     * @param array $arr Data array which should be outputted
     * @param array $valueList List of keys which should be listed in the output string.
     * @return string Output string with key names and their value as string
     */
    protected function arrayToLogString(array $arr, array $valueList): string
    {
        $str = '';
        foreach ($arr as $key => $value) {
            if (in_array($key, $valueList, true)) {
                $str .= (string)$key . trim(': ' . GeneralUtility::fixed_lgd_cs(str_replace(LF, '|', (string)$value), 20)) . LF;
            }
        }
        return $str;
    }
}
