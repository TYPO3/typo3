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

namespace TYPO3Tests\DatasetImport\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\Framework\DataHandling\DataSet;
use TYPO3\TestingFramework\Core\Testbase;

/**
 * CLI command for setting up TYPO3 via CLI
 */
class DatasetImportCommand extends Command
{
    protected function configure(): void
    {
        $this->addArgument('path', InputArgument::REQUIRED, 'Path to CSV dataset to import');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if (!class_exists(DataSet::class)) {
            $io = new SymfonyStyle($input, $output);
            $io->getErrorStyle()->error('Missing typo3/testing-framework dependency.');
            return Command::FAILURE;
        }
        $path = $input->getArgument('path');
        if (str_ends_with($path, '.xml')) {
            $testbase = new Testbase();
            $testbase->importXmlDatabaseFixture($path);
        } else {
            $this->importCSVDataSet($path);
        }
        return Command::SUCCESS;
    }

    /**
     * Import data from a CSV file to database.
     * Single file can contain data from multiple tables.
     *
     * @param string $path Absolute path to the CSV file containing the data set to load
     * @see TYPO3\TestingFramework\Core\Acceptance\Extension::importCSVDataSet
     */
    private function importCSVDataSet(string $path): void
    {
        $dataSet = DataSet::read($path, true);
        foreach ($dataSet->getTableNames() as $tableName) {
            $connection = GeneralUtility::makeInstance(ConnectionPool::class)->getConnectionForTable($tableName);
            foreach ($dataSet->getElements($tableName) as $element) {
                // Some DBMS like postgresql are picky about inserting blob types with correct cast, setting
                // types correctly (like Connection::PARAM_LOB) allows doctrine to create valid SQL
                $types = [];
                $tableDetails = $connection->createSchemaManager()->listTableDetails($tableName);
                foreach ($element as $columnName => $columnValue) {
                    $types[] = $tableDetails->getColumn($columnName)->getType()->getBindingType();
                }
                // Insert the row
                $connection->insert($tableName, $element, $types);
            }
            Testbase::resetTableSequences($connection, $tableName);
        }
    }
}
