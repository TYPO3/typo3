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

use Doctrine\DBAL\Exception\TableNotFoundException;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Backend\Command\ProgressListener\ReferenceIndexProgressListener;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Finds references and soft-references to
 * - records which are marked as deleted (e.g. still in the system as reminder)
 * - offline versions (references should never point to offline versions)
 * - non-existing records (records which have been deleted not via DataHandler)
 *
 * The later (non-soft-reference variants) can be automatically fixed by simply removing
 * the references from the refindex.
 *
 * @todo: The entire logic smells fishy and needs a major overhaul.
 */
#[AsCommand('cleanup:missingrelations', 'Find all record references pointing to a non-existing record')]
class MissingRelationsCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ReferenceIndex $referenceIndex,
        private readonly TcaSchemaFactory $tcaSchemaFactory,
    ) {
        parent::__construct();
    }

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setHelp('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- all database references to check are integers greater than zero
- does not check if a referenced record is inside an offline branch, another workspace etc. which could make the reference useless in reality or otherwise question integrity
Records may be missing for these reasons (except software bugs):
- someone deleted the record which is technically not an error although it might be a mistake that someone did so.
- after flushing published versions and/or deleted-flagged records a number of new missing references might appear; those were pointing to records just flushed.

An automatic repair is only possible for managed references are (not for soft references), for
offline versions records and non-existing records. If you just want to list them, use the --dry-run option.
The references in this case are removed.

If the option "--dry-run" is not set, all managed files (TCA/FlexForm attachments) will silently remove the references
to non-existing and offline version records.
All soft references with relations to non-existing records, offline versions and deleted records
require manual fix if you consider it an error.

Manual repair suggestions:
- For soft references you should investigate each case and edit the content accordingly.
- References to deleted records can theoretically be removed since a deleted record cannot be selected and hence
your website should not be affected by removal of the reference. On the other hand it does not hurt to ignore it
for now. To have this automatically fixed you must first flush the deleted records after which remaining
references will appear as pointing to Non Existing Records and can now be removed with the automatic fix.

If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the references will not be removed, but just the output which references would be deleted are shown'
            )
            ->addOption(
                'update-refindex',
                null,
                InputOption::VALUE_NONE,
                'Setting this option automatically updates the reference index and does not ask on command line. Alternatively, use -n to avoid the interactive mode'
            );
    }

    /**
     * Executes the command to
     * - optionally update the reference index (to have clean data)
     * - find data in sys_refindex (softrefs and regular references) where the reference points to a non-existing record or offline version
     * - remove these files if --dry-run is not set (not possible for refindexes)
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $dryRun = $input->hasOption('dry-run') && (bool)$input->getOption('dry-run') !== false;

        // Update the reference index
        $this->updateReferenceIndex($input, $io);

        $results = $this->findRelationsToNonExistingRecords();

        // Display soft references to non-existing records
        if ($io->isVerbose() && count($results['nonExistingRecordsInSoftReferenceRelations'])) {
            $io->note([
                'Found ' . count($results['nonExistingRecordsInSoftReferenceRelations']) . ' non-existing records that are still being soft-referenced in the following locations.',
                'These relations cannot be removed automatically and need manual repair.',
            ]);
            $io->listing($results['nonExistingRecordsInSoftReferenceRelations']);
        }

        // Display soft references to offline version records
        // These records are offline versions having a pid=-1 and references should never occur directly to their uids.
        if ($io->isVerbose() && count($results['offlineVersionRecordsInSoftReferenceRelations'])) {
            $io->note([
                'Found ' . count($results['offlineVersionRecordsInSoftReferenceRelations']) . ' soft-references pointing to offline versions, which should never be referenced directly.',
                'These relations cannot be removed automatically and need manual repair.',
            ]);
            $io->listing($results['offlineVersionRecordsInSoftReferenceRelations']);
        }

        // Display references to deleted records
        // These records are deleted with a flag but references are still pointing at them.
        // Keeping the references is useful if you undelete the referenced records later, otherwise the references
        // are lost completely when the deleted records are flushed at some point. Notice that if those records listed
        // are themselves deleted (marked with "DELETED") it is not a problem.
        if ($io->isVerbose() && count($results['deletedRecords'])) {
            $io->note([
                'Found ' . count($results['deletedRecords']) . ' references pointing to deleted records.',
                'Keeping the references is useful if you undelete the referenced records later, otherwise the references' .
                'are lost completely when the deleted records are flushed at some point. Notice that if those records listed' .
                'are themselves deleted (marked with "DELETED") it is not a problem.',
            ]);
            $io->listing($results['deletedRecords']);
        }

        // soft references which link to deleted records
        if ($io->isVerbose() && count($results['deletedRecordsInSoftReferenceRelations'])) {
            $io->note([
                'Found ' . count($results['deletedRecordsInSoftReferenceRelations']) . ' soft references pointing  to deleted records.',
                'Keeping the references is useful if you undelete the referenced records later, otherwise the references' .
                'are lost completely when the deleted records are flushed at some point. Notice that if those records listed' .
                'are themselves deleted (marked with "DELETED") it is not a problem.',
            ]);
            $io->listing($results['deletedRecordsInSoftReferenceRelations']);
        }

        // Find missing references
        if (count($results['offlineVersionRecords']) || count($results['nonExistingRecords'])) {
            $io->note([
                'Found ' . count($results['nonExistingRecords']) . ' references to non-existing records ' .
                'and ' . count($results['offlineVersionRecords']) . ' references directly linked to offline versions.',
            ]);

            $this->removeReferencesToMissingRecords(
                $results['offlineVersionRecords'],
                $results['nonExistingRecords'],
                $dryRun,
                $io
            );
            $io->success('All references were updated accordingly.');
        } else {
            $io->success('Nothing to do, no missing relations found. Everything is in place.');
        }
        return Command::SUCCESS;
    }

    /**
     * Function to update the reference index
     * - if the option --update-refindex is set, do it
     * - otherwise, if in interactive mode (not having -n set), ask the user
     * - otherwise assume everything is fine
     */
    protected function updateReferenceIndex(InputInterface $input, SymfonyStyle $io): void
    {
        // Check for reference index to update
        $io->note('Finding missing records referenced by TYPO3 requires a clean reference index (sys_refindex)');
        if ($input->hasOption('update-refindex') && $input->getOption('update-refindex')) {
            $updateReferenceIndex = true;
        } elseif ($input->isInteractive()) {
            $updateReferenceIndex = $io->confirm('Should the reference index be updated right now?', false);
        } else {
            $updateReferenceIndex = false;
        }

        // Update the reference index
        if ($updateReferenceIndex) {
            $progressListener = GeneralUtility::makeInstance(ReferenceIndexProgressListener::class);
            $progressListener->initialize($io);
            $this->referenceIndex->updateIndex(false, $progressListener);
        } else {
            $io->writeln('Reference index is assumed to be up to date, continuing.');
        }
    }

    /**
     * Find relations pointing to non-existing records (in managed references or soft-references)
     */
    protected function findRelationsToNonExistingRecords(): array
    {
        $deletedRecords = [];
        $deletedRecordsInSoftReferenceRelations = [];
        $nonExistingRecords = [];
        $nonExistingRecordsInSoftReferenceRelations = [];
        $offlineVersionRecords = [];
        $offlineVersionRecordsInSoftReferenceRelations = [];

        // Select DB relations from reference table
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $rowIterator = $queryBuilder
            ->select('ref_uid', 'ref_table', 'softref_key', 'hash', 'tablename', 'recuid', 'field', 'flexpointer')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->gt('ref_uid', $queryBuilder->createNamedParameter(0, Connection::PARAM_INT))
            )
            ->executeQuery();

        $existingRecords = [];
        while ($rec = $rowIterator->fetchAssociative()) {
            $isSoftReference = !empty($rec['softref_key']);
            $idx = $rec['ref_table'] . ':' . $rec['ref_uid'];
            $schema = $this->tcaSchemaFactory->get($rec['ref_table']);
            // Get referenced record:
            if (!isset($existingRecords[$idx])) {
                $queryBuilder = $this->connectionPool
                    ->getQueryBuilderForTable($rec['ref_table']);
                $queryBuilder->getRestrictions()->removeAll();

                $selectFields = ['uid', 'pid'];
                if ($schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                    $selectFields[] = $schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName();
                }
                if ($schema->isWorkspaceAware()) {
                    $selectFields[] = 't3ver_oid';
                    $selectFields[] = 't3ver_wsid';
                }

                try {
                    $existingRecords[$idx] = $queryBuilder
                        ->select(...$selectFields)
                        ->from($rec['ref_table'])
                        ->where(
                            $queryBuilder->expr()->eq(
                                'uid',
                                $queryBuilder->createNamedParameter($rec['ref_uid'], Connection::PARAM_INT)
                            )
                        )
                        ->executeQuery()
                        ->fetchAssociative();
                } catch (TableNotFoundException $tableNotFoundException) {
                    // noop
                }
            }
            // Compile info string for location of reference:
            $infoString = $this->formatReferenceIndexEntryToString($rec);
            // Handle missing file:
            if ($existingRecords[$idx]['uid'] ?? false) {
                // Record exists, but is a reference to an offline version
                if ((int)($existingRecords[$idx]['t3ver_oid'] ?? 0) > 0) {
                    if ($isSoftReference) {
                        $offlineVersionRecordsInSoftReferenceRelations[] = $infoString;
                    } else {
                        $offlineVersionRecords[$idx][$rec['hash']] = $infoString;
                    }
                    // reference to a deleted record
                } elseif ($schema->hasCapability(TcaSchemaCapability::SoftDelete)
                    && $existingRecords[$idx][$schema->getCapability(TcaSchemaCapability::SoftDelete)->getFieldName()]) {
                    if ($isSoftReference) {
                        $deletedRecordsInSoftReferenceRelations[] = $infoString;
                    } else {
                        $deletedRecords[] = $infoString;
                    }
                }
            } else {
                if ($isSoftReference) {
                    $nonExistingRecordsInSoftReferenceRelations[] = $infoString;
                } else {
                    $nonExistingRecords[$idx][$rec['hash']] = $infoString;
                }
            }
        }

        return [
            // Non-existing records to which there are references (managed)
            // These references can safely be removed since there is no record found in the database at all.
            'nonExistingRecords' => ArrayUtility::sortByKeyRecursive($nonExistingRecords),
            // Non-existing records to which there are references (softref)
            'nonExistingRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($nonExistingRecordsInSoftReferenceRelations),
            // Offline version records (managed)
            // These records are offline versions having a pid=-1 and references should never occur directly to their uids.
            'offlineVersionRecords' => ArrayUtility::sortByKeyRecursive($offlineVersionRecords),
            // Offline version records (softref)
            'offlineVersionRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($offlineVersionRecordsInSoftReferenceRelations),
            // Deleted-flagged records (managed)
            // These records are deleted with a flag but references are still pointing at them.
            // Keeping the references is useful if you undelete the referenced records later, otherwise the references
            // are lost completely when the deleted records are flushed at some point. Notice that if those records listed
            // are themselves deleted (marked with "DELETED") it is not a problem.
            'deletedRecords' => ArrayUtility::sortByKeyRecursive($deletedRecords),
            // Deleted-flagged records (softref)
            'deletedRecordsInSoftReferenceRelations' => ArrayUtility::sortByKeyRecursive($deletedRecordsInSoftReferenceRelations),
        ];
    }

    /**
     * Removes all references to non-existing records or offline versions
     *
     * @param array $offlineVersionRecords Contains the records of offline versions of sys_refindex which need to be removed
     * @param array $nonExistingRecords Contains the records non-existing records of sys_refindex which need to be removed
     * @param bool $dryRun if set, the references are just displayed, but not removed
     * @param SymfonyStyle $io the IO object for output
     */
    protected function removeReferencesToMissingRecords(
        array $offlineVersionRecords,
        array $nonExistingRecords,
        bool $dryRun,
        SymfonyStyle $io
    ): void {
        // Remove references to offline records
        foreach ($offlineVersionRecords as $references) {
            if ($io->isVeryVerbose()) {
                $io->writeln('Removing references in offline versions which there are references pointing towards.');
            }
            foreach ($references as $hash => $recordReference) {
                $this->removeReference($hash, $recordReference, $dryRun, $io);
            }
        }

        // Remove references to non-existing records
        foreach ($nonExistingRecords as $references) {
            if ($io->isVeryVerbose()) {
                $io->writeln('Removing references to non-existing records.');
            }
            foreach ($references as $hash => $recordReference) {
                $this->removeReference($hash, $recordReference, $dryRun, $io);
            }
        }
    }

    /**
     * Remove a reference to a missing record
     */
    protected function removeReference(string $hash, string $recordReference, bool $dryRun, SymfonyStyle $io): void
    {
        $io->writeln('Removing reference in record "' . $recordReference . '" (Hash: ' . $hash . ')');
        if ($dryRun) {
            return;
        }
        $error = $this->setReferenceValue($hash);
        if ($error) {
            $io->error('ReferenceIndex::setReferenceValue() reported "' . $error . '"');
        }
    }

    /**
     * Formats a sys_refindex entry to something readable
     */
    protected function formatReferenceIndexEntryToString(array $record): string
    {
        return $record['tablename']
            . ':' . $record['recuid']
            . ':' . $record['field']
            . ($record['flexpointer'] ? ':' . $record['flexpointer'] : '')
            . ($record['softref_key'] ? ':' . $record['softref_key'] . ' (Soft Reference) ' : '');
    }

    /**
     * Setting the value of a reference or removing it completely.
     * Usage: For lowlevel clean up operations!
     * WARNING: With this you can set values that are not allowed in the database since it will bypass all checks for validity!
     * Hence it is targeted at clean-up operations. Please use DataHandler in the usual ways if you wish to manipulate references.
     * Since this interface allows updates to soft reference values (which DataHandler does not directly) you may like to use it
     * for that as an exception to the warning above.
     * Notice; If you want to remove multiple references from the same field, you MUST start with the one having the highest
     * sorting number. If you don't the removal of a reference with a lower number will recreate an index in which the remaining
     * references in that field has new hash-keys due to new sorting numbers - and you will get errors for the remaining operations
     * which cannot find the hash you feed it!
     * To ensure proper working only admin-BE_USERS in live workspace should use this function
     *
     * @internal
     */
    protected function setReferenceValue(string $hash): string|bool
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
        $queryBuilder->getRestrictions()->removeAll();

        // Get current index from Database
        $referenceRecord = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq('hash', $queryBuilder->createNamedParameter($hash))
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        // Check if reference existed.
        if (!is_array($referenceRecord)) {
            return 'ERROR: No reference record with hash="' . $hash . '" was found!';
        }
        if (!$this->tcaSchemaFactory->has($referenceRecord['tablename'])) {
            return 'ERROR: Table "' . $referenceRecord['tablename'] . '" was not in TCA!';
        }

        // Get that record from database
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable($referenceRecord['tablename']);
        $queryBuilder->getRestrictions()->removeAll();
        $record = $queryBuilder
            ->select('*')
            ->from($referenceRecord['tablename'])
            ->where(
                $queryBuilder->expr()->eq(
                    'uid',
                    $queryBuilder->createNamedParameter($referenceRecord['recuid'], Connection::PARAM_INT)
                )
            )
            ->setMaxResults(1)
            ->executeQuery()
            ->fetchAssociative();

        if (is_array($record)) {
            // Get relation for single field from record
            $recordRelations = $this->referenceIndex->getRelations((string)$referenceRecord['tablename'], $record, 0);
            $fieldRelation = $recordRelations[$referenceRecord['field']] ?? null;
            if ($fieldRelation) {
                // Initialize data array that is to be sent to DataHandler afterwards:
                $dataArray = [];
                // Based on type
                switch ((string)($fieldRelation['type'] ?? '')) {
                    case 'db':
                        $error = $this->setReferenceValue_dbRels($referenceRecord, $fieldRelation['itemArray'], $dataArray);
                        if ($error) {
                            return $error;
                        }
                        break;
                    case 'flex':
                        // DB references in FlexForms
                        if (is_array($fieldRelation['flexFormRels']['db'][$referenceRecord['flexpointer']])) {
                            $error = $this->setReferenceValue_dbRels($referenceRecord, $fieldRelation['flexFormRels']['db'][$referenceRecord['flexpointer']], $dataArray, $referenceRecord['flexpointer']);
                            if ($error) {
                                return $error;
                            }
                        }
                        // Soft references in FlexForms
                        if ($referenceRecord['softref_key'] && is_array($fieldRelation['flexFormRels']['softrefs'][$referenceRecord['flexpointer']]['keys'][$referenceRecord['softref_key']])) {
                            $error = $this->setReferenceValue_softreferences($referenceRecord, $fieldRelation['flexFormRels']['softrefs'][$referenceRecord['flexpointer']], $dataArray, $referenceRecord['flexpointer']);
                            if ($error) {
                                return $error;
                            }
                        }
                        break;
                }
                // Soft references in the field:
                if ($referenceRecord['softref_key'] && is_array($fieldRelation['softrefs']['keys'][$referenceRecord['softref_key']])) {
                    $error = $this->setReferenceValue_softreferences($referenceRecord, $fieldRelation['softrefs'], $dataArray);
                    if ($error) {
                        return $error;
                    }
                }
                // Data Array, now ready to be sent to DataHandler, execute CMD array:
                $dataHandler = GeneralUtility::makeInstance(DataHandler::class);
                $dataHandler->dontProcessTransformations = true;
                $dataHandler->bypassWorkspaceRestrictions = true;
                // Otherwise this may lead to permission issues if user is not admin
                $dataHandler->bypassAccessCheckForRecords = true;
                // Check has been done previously that there is a backend user which is Admin and also in live workspace
                $dataHandler->start($dataArray, []);
                $dataHandler->process_datamap();
                // Return errors if any:
                if (!empty($dataHandler->errorLog)) {
                    return LF . 'DataHandler:' . implode(LF . 'DataHandler:', $dataHandler->errorLog);
                }
            }
        }
        return false;
    }

    /**
     * Setting a value for a reference for a DB field:
     *
     * @param array $itemArray Array of references from that field
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     */
    protected function setReferenceValue_dbRels(array $refRec, array $itemArray, array &$dataArray, string $flexPointer = ''): string|bool
    {
        $sorting = $refRec['sorting'] ?? null;
        if ((int)($itemArray[$sorting]['id'] ?? 0) === (int)$refRec['ref_uid']
            && (string)($itemArray[$sorting]['table'] ?? '') === (string)$refRec['ref_table']
        ) {
            // Setting or removing value:
            // Remove value:
            unset($itemArray[$sorting]);
            // Traverse and compile new list of records:
            $saveValue = [];
            foreach ($itemArray as $pair) {
                $saveValue[] = $pair['table'] . '_' . $pair['id'];
            }
            // Set in data array:
            if ($flexPointer) {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = ArrayUtility::setValueByPath(
                    [],
                    substr($flexPointer, 0, -1),
                    implode(',', $saveValue)
                );
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = implode(',', $saveValue);
            }
        } else {
            return 'ERROR: table:id pair "' . $refRec['ref_table'] . ':' . $refRec['ref_uid']
                . '" did not match that of the record ("' . ($itemArray[$sorting]['table'] ?? '') . ':' . ($itemArray[$sorting]['id'] ?? '') . '")'
                . ' in sorting index "' . $sorting . '"';
        }
        return false;
    }

    /**
     * Setting a value for a soft reference token
     *
     * @param array $softref Array of soft reference occurrences
     * @param array $dataArray Data array in which the new value is set (passed by reference)
     * @param string $flexPointer Flexform pointer, if in a flex form field.
     */
    protected function setReferenceValue_softreferences(array $refRec, array $softref, array &$dataArray, string $flexPointer = ''): string|bool
    {
        if (!is_array($softref['keys'][$refRec['softref_key']][$refRec['softref_id']])) {
            return 'ERROR: Soft reference parser key "' . $refRec['softref_key'] . '" or the index "' . $refRec['softref_id'] . '" was not found.';
        }
        // Set new value:
        $softref['keys'][$refRec['softref_key']][$refRec['softref_id']]['subst']['tokenValue'] = '';
        // Traverse softreferences and replace in tokenized content to rebuild it with new value inside:
        foreach ($softref['keys'] as $sfIndexes) {
            foreach ($sfIndexes as $data) {
                $softref['tokenizedContent'] = str_replace('{softref:' . $data['subst']['tokenID'] . '}', $data['subst']['tokenValue'], $softref['tokenizedContent']);
            }
        }
        // Set in data array:
        if (!str_contains($softref['tokenizedContent'], '{softref:')) {
            if ($flexPointer) {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']]['data'] = ArrayUtility::setValueByPath(
                    [],
                    substr($flexPointer, 0, -1),
                    $softref['tokenizedContent']
                );
            } else {
                $dataArray[$refRec['tablename']][$refRec['recuid']][$refRec['field']] = $softref['tokenizedContent'];
            }
        } else {
            return 'ERROR: After substituting all found soft references there were still soft reference tokens in the text. (theoretically this does not have to be an error if the string "{softref:" happens to be in the field for another reason.)';
        }
        return false;
    }
}
