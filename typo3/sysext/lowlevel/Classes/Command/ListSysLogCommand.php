<?php
namespace TYPO3\CMS\Lowlevel\Command;

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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Lists all sys_log entries from the last 24 hours by default
 * This is the most basic and can be useful for nightly check test reports.
 */
class ListSysLogCommand extends Command
{

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this->setDescription('Show entries from the sys_log database table of the last 24 hours.');
        $this->setHelp('Prints a list of recent sys_log entries.' . LF . 'If you want to get more detailed information, use the --verbose option.');
    }

    /**
     * Executes the command for showing sys_log entries
     *
     * @param InputInterface $input
     * @param OutputInterface $output
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
            'Message'
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
            ->execute();

        while ($row = $rowIterator->fetch()) {
            $logData = unserialize($row['log_data']);
            $userInformation = $row['userid'];
            if (!empty($logData['originalUser'])) {
                $userInformation .= ' via ' . $logData['originalUser'];
            }

            $result = [
                $row['uid'],
                BackendUtility::datetime($row['tstamp']),
                $userInformation,
                sprintf($row['details'], $logData[0], $logData[1], $logData[2], $logData[3], $logData[4], $logData[5])
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
                    'workspace'
                ]);
            }
            $content[] = $result;
        }
        $io->table($tableHeaders, $content);
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
