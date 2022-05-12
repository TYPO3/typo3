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

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Configuration\FlexForm\FlexFormTools;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;

/**
 * Checks if TCA records with a FlexForm includes values that don't match the connected FlexForm value
 */
class CleanFlexFormsCommand extends Command
{
    /**
     * @var ConnectionPool
     */
    private $connectionPool;

    public function __construct(ConnectionPool $connectionPool)
    {
        $this->connectionPool = $connectionPool;
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setHelp('Traverse page tree and find and clean/update records with dirty FlexForm values. If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'pid',
                'p',
                InputOption::VALUE_REQUIRED,
                'Setting start page in page tree. Default is the page tree root, 0 (zero)'
            )
            ->addOption(
                'depth',
                'd',
                InputOption::VALUE_REQUIRED,
                'Setting traversal depth. 0 (zero) will only analyze start page (see --pid), 1 will traverse one level of subpages etc.'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the records will not be updated, but only show the output which records would have been updated.'
            );
    }

    /**
     * Executes the command to find and update records with FlexForms where the values do not match the datastructures
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $startingPoint = 0;
        if ($input->hasOption('pid') && MathUtility::canBeInterpretedAsInteger($input->getOption('pid'))) {
            $startingPoint = MathUtility::forceIntegerInRange((int)$input->getOption('pid'), 0);
        }

        $depth = 1000;
        if ($input->hasOption('depth') && MathUtility::canBeInterpretedAsInteger($input->getOption('depth'))) {
            $depth = MathUtility::forceIntegerInRange((int)$input->getOption('depth'), 0);
        }

        if ($io->isVerbose()) {
            $io->section('Searching the database now for records with FlexForms that need to be updated.');
        }

        // Type unsafe comparison and explicit boolean setting on purpose
        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        // Find all records that should be updated
        $recordsToUpdate = $this->findAllDirtyFlexformsInPage($startingPoint, $depth);

        if (!$io->isQuiet()) {
            $io->note('Found ' . count($recordsToUpdate) . ' records with wrong FlexForms information.');
        }

        if (!empty($recordsToUpdate)) {
            $io->section('Cleanup process starting now.' . ($dryRun ? ' (Not deleting now, just a dry run)' : ''));

            // Clean up the records now
            $this->cleanFlexFormRecords($recordsToUpdate, $dryRun, $io);

            $io->success('All done!');
        } else {
            $io->success('Nothing to do - You\'re all set!');
        }
        return 0;
    }

    /**
     * Recursive traversal of page tree
     *
     * @param int $pageId Page root id
     * @param int $depth Depth
     * @param array $dirtyFlexFormFields the list of all previously found flexform fields
     * @return array
     */
    protected function findAllDirtyFlexformsInPage(int $pageId, int $depth, array $dirtyFlexFormFields = []): array
    {
        if ($pageId > 0) {
            $dirtyFlexFormFields = $this->compareAllFlexFormsInRecord('pages', $pageId, $dirtyFlexFormFields);
        }

        // Traverse tables of records that belongs to this page
        foreach ($GLOBALS['TCA'] as $tableName => $tableConfiguration) {
            if ($tableName !== 'pages') {
                // Select all records belonging to page:
                $queryBuilder = $this->connectionPool
                    ->getQueryBuilderForTable($tableName);

                $queryBuilder->getRestrictions()
                    ->removeAll();

                $result = $queryBuilder
                    ->select('uid')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
                    )
                    ->executeQuery();

                while ($rowSub = $result->fetchAssociative()) {
                    // Traverse flexforms
                    $dirtyFlexFormFields = $this->compareAllFlexFormsInRecord($tableName, $rowSub['uid'], $dirtyFlexFormFields);
                    // Add any versions of those records
                    $versions = BackendUtility::selectVersionsOfRecord(
                        $tableName,
                        $rowSub['uid'],
                        'uid,t3ver_wsid',
                        null,
                        true
                    );
                    if (is_array($versions)) {
                        foreach ($versions as $verRec) {
                            if (!($verRec['_CURRENT_VERSION'] ?? false)) {
                                // Traverse flexforms
                                $dirtyFlexFormFields = $this->compareAllFlexFormsInRecord($tableName, $verRec['uid'], $dirtyFlexFormFields);
                            }
                        }
                    }
                }
            }
        }

        // Find subpages
        if ($depth > 0) {
            $depth--;
            $queryBuilder = $this->connectionPool
                ->getQueryBuilderForTable('pages');

            $queryBuilder->getRestrictions()
                ->removeAll();

            $result = $queryBuilder
                ->select('uid')
                ->from('pages')
                ->where(
                    $queryBuilder->expr()->eq('pid', $queryBuilder->createNamedParameter($pageId, \PDO::PARAM_INT))
                )
                ->orderBy('sorting')
                ->executeQuery();

            while ($row = $result->fetchAssociative()) {
                $dirtyFlexFormFields = $this->findAllDirtyFlexformsInPage($row['uid'], $depth, $dirtyFlexFormFields);
            }
        }
        // Add any versions of pages
        if ($pageId > 0) {
            $versions = BackendUtility::selectVersionsOfRecord('pages', $pageId, 'uid,t3ver_oid,t3ver_wsid', null, true);
            if (is_array($versions)) {
                foreach ($versions as $verRec) {
                    if (!($verRec['_CURRENT_VERSION'] ?? false)) {
                        $dirtyFlexFormFields = $this->findAllDirtyFlexformsInPage($verRec['uid'], $depth, $dirtyFlexFormFields);
                    }
                }
            }
        }
        return $dirtyFlexFormFields;
    }

    /**
     * Check a specific record on all TCA columns if they are FlexForms and if the FlexForm values
     * don't match to the newly defined ones.
     *
     * @param string $tableName Table name
     * @param int $uid UID of record in processing
     * @param array $dirtyFlexFormFields the existing FlexForm fields
     * @return array the updated list of dirty FlexForm fields
     */
    protected function compareAllFlexFormsInRecord(string $tableName, int $uid, array $dirtyFlexFormFields = []): array
    {
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);
        foreach ($GLOBALS['TCA'][$tableName]['columns'] as $columnName => $columnConfiguration) {
            if ($columnConfiguration['config']['type'] === 'flex') {
                $queryBuilder = $this->connectionPool
                    ->getQueryBuilderForTable($tableName);
                $queryBuilder->getRestrictions()->removeAll();

                $fullRecord = $queryBuilder->select('*')
                    ->from($tableName)
                    ->where(
                        $queryBuilder->expr()->eq('uid', $queryBuilder->createNamedParameter($uid, \PDO::PARAM_INT))
                    )
                    ->executeQuery()
                    ->fetchAssociative();

                if ($fullRecord[$columnName]) {
                    // Clean XML and check against the record fetched from the database
                    $newXML = $flexObj->cleanFlexFormXML($tableName, $columnName, $fullRecord);
                    if (!hash_equals(md5($fullRecord[$columnName]), md5($newXML))) {
                        $dirtyFlexFormFields[$tableName . ':' . $uid . ':' . $columnName] = $fullRecord;
                    }
                }
            }
        }
        return $dirtyFlexFormFields;
    }

    /**
     * Actually cleans the database record fields with a new FlexForm as chosen currently for this record
     *
     * @param array $records
     * @param bool $dryRun
     * @param SymfonyStyle $io
     */
    protected function cleanFlexFormRecords(array $records, bool $dryRun, SymfonyStyle $io)
    {
        $flexObj = GeneralUtility::makeInstance(FlexFormTools::class);

        // Set up the data handler instance
        $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
        $dataHandler->dontProcessTransformations = true;
        $dataHandler->bypassWorkspaceRestrictions = true;
        // Setting this option allows to also update deleted records (or records on deleted pages) within DataHandler
        $dataHandler->bypassAccessCheckForRecords = true;

        // Loop through all tables and their records
        foreach ($records as $recordIdentifier => $fullRecord) {
            [$table, $uid, $field] = explode(':', $recordIdentifier);
            if ($io->isVerbose()) {
                $io->writeln('Cleaning FlexForm XML in "' . $recordIdentifier . '"');
            }
            if (!$dryRun) {
                // Clean XML now
                $data = [];
                if ($fullRecord[$field]) {
                    $data[$table][$uid][$field] = $flexObj->cleanFlexFormXML($table, $field, $fullRecord);
                } else {
                    $io->note('The field "' . $field . '" in record "' . $table . ':' . $uid . '" was not found.');
                    continue;
                }
                $dataHandler->start($data, []);
                $dataHandler->process_datamap();
                // Return errors if any:
                if (!empty($dataHandler->errorLog)) {
                    $errorMessage = array_merge(['DataHandler reported an error'], $dataHandler->errorLog);
                    $io->error($errorMessage);
                } elseif (!$io->isQuiet()) {
                    $io->writeln('Updated FlexForm in record "' . $table . ':' . $uid . '".');
                }
            }
        }
    }
}
