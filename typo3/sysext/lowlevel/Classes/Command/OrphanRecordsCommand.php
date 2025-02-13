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

namespace TYPO3\CMS\Lowlevel\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;

#[AsCommand('cleanup:orphanrecords', 'Find and delete records that have lost their connection with the page tree')]
class OrphanRecordsCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly TcaSchemaFactory $schemaFactory,
    ) {
        parent::__construct();
    }

    public function configure(): void
    {
        $this
            ->setHelp(
                'TYPO3 "pages" database table is a tree that represents a hierarchical structure with a set of connected nodes by their "uid" und "pid" values.'
                . "\n" . 'All TCA records must be connected to a valid "pid".'
                . "\n"
                . "\n" . 'This command finds and deletes all "pages" rows that do not have a proper tree connection to "pid" "0".'
                . "\n" . 'It also finds and deletes TCA record rows having a "pid" set to invalid "pages" "uid"s.'
                . "\n"
                . "\n" . 'The command can be called using "typo3 cleanup:orphanrecords -v --dry-run" to *find* affected records allowing manual inspection.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not be deleted. The command outputs a list of broken records'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $isVerbose = $io->isVerbose();
        $isDryRun = $input->hasOption('dry-run') && (bool)$input->getOption('dry-run') !== false;

        // Get list of valid pages uids
        $pageStatus = $this->getConnectedAndOrphanPages();
        $connectedPageUids = $pageStatus['connectedPageUids'];

        // Loop all TCA tables
        $tcaTableNames = $this->schemaFactory->all()->getNames();
        sort($tcaTableNames);
        $orphanRecords = [];
        foreach ($tcaTableNames as $tableName) {
            if ($tableName === 'pages') {
                continue;
            }
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            $queryBuilder->getRestrictions()->removeAll();
            $tableResult = $queryBuilder->select('uid', 'pid')->from($tableName)->orderBy('uid')->executeQuery();
            while ($tableRow = $tableResult->fetchAssociative()) {
                if (!array_key_exists((int)$tableRow['pid'], $connectedPageUids)) {
                    $orphanRecords[$tableName][] = $tableRow;
                }
            }
        }

        // We have a list of potential orphan records now. Refresh the list of valid
        // pages in case the pages list changed during this run meanwhile.
        $pageStatus = $this->getConnectedAndOrphanPages();
        $connectedPageUids = $pageStatus['connectedPageUids'];
        $orphanPageUids = $pageStatus['orphanPageUids'];

        // Delete or output list of orphan pages
        $removedPages = 0;
        foreach ($orphanPageUids as $pageUid) {
            $removedPages++;
            if ($isDryRun) {
                if ($isVerbose) {
                    $io->warning('Found orphan pages:' . $pageUid);
                }
            } else {
                $this->connectionPool->getConnectionForTable('pages')->delete(
                    'pages',
                    ['uid' => (int)$pageUid],
                    [Connection::PARAM_INT]
                );
                if ($isVerbose) {
                    $io->warning('Removed orphan pages:' . $pageUid);
                }
            }
        }

        // Delete or output list of orphan records
        $removedRecords = 0;
        foreach ($orphanRecords as $tableName => $tableRows) {
            foreach ($tableRows as $tableRow) {
                if (!array_key_exists((int)$tableRow['pid'], $connectedPageUids)) {
                    $removedRecords++;
                    if ($isDryRun) {
                        if ($isVerbose) {
                            $io->warning('Found orphan ' . $tableName . ':' . $tableRow['uid']);
                        }
                    } else {
                        $this->connectionPool->getConnectionForTable($tableName)->delete(
                            $tableName,
                            ['uid' => (int)$tableRow['uid']],
                            [Connection::PARAM_INT]
                        );
                        if ($isVerbose) {
                            $io->warning('Removed orphan ' . $tableName . ':' . $tableRow['uid']);
                        }
                    }
                }
            }
        }

        // Output summary
        $numberOfRemovedRecords = $removedPages + $removedRecords;
        if ($numberOfRemovedRecords > 0) {
            if ($isDryRun) {
                $io->warning('Found ' . $numberOfRemovedRecords . ' orphan records without proper page tree connection.');
            } else {
                $io->success('Removed ' . $numberOfRemovedRecords . ' orphan records without proper page tree connection.');
            }
        } else {
            $io->success('No orphan records found.');
        }

        return Command::SUCCESS;
    }

    /**
     * A straight solution that gets a list of *all* pages and returns a list
     * of page uids that *are* connected in a tree and a list of "remaining"
     * page uids that are not.
     * Note this finds "loops" as invalid: If a chain of pages is connected
     * to each other and none has a pid that leads to "0", they are found
     * as "orphan".
     */
    private function getConnectedAndOrphanPages(): array
    {
        $connectedPageUids = [];
        // uid 0 is "valid"
        $connectedPageUids[0] = true;
        $unknownPageUidPidPairs = [];
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('pages');
        $queryBuilder->getRestrictions()->removeAll();
        $pagesResult = $queryBuilder->select('uid', 'pid')->from('pages')->orderBy('uid')->executeQuery();
        while ($pageRow = $pagesResult->fetchAssociative()) {
            if ((int)$pageRow['pid'] === 0) {
                // Pages with pid 0 are good and sorted out already.
                $connectedPageUids[(int)$pageRow['uid']] = true;
            } else {
                $unknownPageUidPidPairs[(int)$pageRow['uid']] = (int)$pageRow['pid'];
            }
        }
        $unknownUidPidPairsCount = count($unknownPageUidPidPairs);
        if ($unknownUidPidPairsCount > 0) {
            while (true) {
                // If there are currently "unknown status" rows, have a loop that reduces the
                // "unknown" list until it does not change anymore or is empty: Each run looks
                // if "pid" is in "connected" list and adds itself as valid "uid" if so.
                foreach ($unknownPageUidPidPairs as $uid => $pid) {
                    if (array_key_exists($pid, $connectedPageUids)) {
                        $connectedPageUids[$uid] = true;
                        unset($unknownPageUidPidPairs[$uid]);
                    }
                }
                $unknownUidPidPairsCountAfter = count($unknownPageUidPidPairs);
                if ($unknownUidPidPairsCountAfter === 0 || $unknownUidPidPairsCountAfter === $unknownUidPidPairsCount) {
                    break;
                }
                $unknownUidPidPairsCount = $unknownUidPidPairsCountAfter;
            }
        }
        $orphanPageUids = array_keys($unknownPageUidPidPairs);
        return [
            'connectedPageUids' => $connectedPageUids,
            'orphanPageUids' => $orphanPageUids,
        ];
    }
}
