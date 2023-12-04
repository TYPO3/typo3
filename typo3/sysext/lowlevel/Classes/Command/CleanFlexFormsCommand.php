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
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\Restriction\DeletedRestriction;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Checks and clean up TCA records with a FlexForm which include values that don't match the connected FlexForm data structure.
 */
#[AsCommand('cleanup:flexforms', 'Clean up database FlexForm fields that do not match the chosen data structure.')]
class CleanFlexFormsCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly FlexFormTools $flexFormTools,
    ) {
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setHelp('Clean up records with dirty FlexForm values not reflected in current data structure.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not be updated, but only show the output which records would have been updated.'
            );
    }

    /**
     * Executes the command to find and update records with FlexForms where the values do not match the datastructures
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $isDryRun = $input->hasOption('dry-run') && $input->getOption('dry-run');

        $numberOfAffectedRecords = 0;
        $numberOfAffectedTables = 0;
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if (!is_array($tableConfiguration['columns'] ?? false)) {
                continue;
            }
            $flexFieldsInTable = [];
            foreach ($tableConfiguration['columns'] as $columnName => $columnDefinition) {
                if (($columnDefinition['config']['type'] ?? '') !== 'flex') {
                    continue;
                }
                $flexFieldsInTable[] = $columnName;
            }
            if (empty($flexFieldsInTable)) {
                continue;
            }
            $tableHadUpdate = false;
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable($tableName);
            // We do *not* consider soft-deleted records, they are stalled and "don't exist anymore"
            // from Backend point of view, this command follows this view.
            $queryBuilder->getRestrictions()->removeAll()->add(GeneralUtility::makeInstance(DeletedRestriction::class));
            $orWheres = [];
            foreach ($flexFieldsInTable as $flexFieldInTable) {
                $orWheres[] = $queryBuilder->expr()->neq($flexFieldInTable, $queryBuilder->createNamedParameter(''));
            }
            $result = $queryBuilder
                ->select('*')
                ->from($tableName)
                ->where($queryBuilder->expr()->or(...$orWheres))
                ->orderBy('uid')
                ->executeQuery();
            while ($record = $result->fetchAssociative()) {
                $recordHadUpdate = false;
                foreach ($flexFieldsInTable as $flexFieldInTable) {
                    if ((string)$record[$flexFieldInTable] === '') {
                        // Don't handle empty or null value
                        continue;
                    }
                    // Clean XML and check against current record value.
                    $cleanXml = $this->flexFormTools->cleanFlexFormXML($tableName, $flexFieldInTable, $record);
                    if ($cleanXml !== $record[$flexFieldInTable]) {
                        $recordHadUpdate = true;
                        $tableHadUpdate = true;
                        if (!$isDryRun) {
                            $this->connectionPool->getConnectionForTable($tableName)->update(
                                $tableName,
                                [$flexFieldInTable => $cleanXml],
                                ['uid' => $record['uid']],
                                [$flexFieldInTable => Connection::PARAM_STR]
                            );
                        }
                        $io->writeln(
                            $isDryRun
                            ? 'Found dirty FlexForm XML in record "' . $tableName . ':' . $record['uid'] . '", field "' . $flexFieldInTable . '".'
                            : 'Updated FlexForm XML in record "' . $tableName . ':' . $record['uid'] . '", field "' . $flexFieldInTable . '".'
                        );
                    }
                }
                if ($recordHadUpdate) {
                    $numberOfAffectedRecords++;
                }
            }
            if ($tableHadUpdate) {
                $numberOfAffectedTables++;
            }
        }

        if ($numberOfAffectedRecords) {
            $io->success(
                $isDryRun
                ? 'Found ' . $numberOfAffectedRecords . ' dirty records in ' . $numberOfAffectedTables . ' tables.'
                : 'Updated ' . $numberOfAffectedRecords . ' dirty records in ' . $numberOfAffectedTables . ' tables.'
            );
        } else {
            $io->success('No dirty FlexForm fields found.');
        }

        return Command::SUCCESS;
    }
}
