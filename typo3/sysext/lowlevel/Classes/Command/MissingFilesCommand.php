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
use TYPO3\CMS\Backend\Command\ProgressListener\ReferenceIndexProgressListener;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Resource\Index\FileIndexRepository;
use TYPO3\CMS\Core\Resource\Index\Indexer;
use TYPO3\CMS\Core\Resource\Index\MetaDataRepository;
use TYPO3\CMS\Core\Resource\ProcessedFileRepository;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Resource\ResourceStorage;
use TYPO3\CMS\Core\Resource\StorageRepository;

/**
 * Removes the database records of indexed files that have been flagged as
 * missing by the storage indexer and that are no longer referenced by any
 * record, the same way FileDeletionAspect does for files deleted via FAL.
 */
#[AsCommand('cleanup:missingfiles', 'Removes database records of indexed files that are flagged as missing and no longer referenced')]
final class MissingFilesCommand extends Command
{
    public function __construct(
        private readonly ConnectionPool $connectionPool,
        private readonly ReferenceIndex $referenceIndex,
        private readonly StorageRepository $storageRepository,
        private readonly ResourceFactory $resourceFactory,
        private readonly FileIndexRepository $fileIndexRepository,
        private readonly MetaDataRepository $metaDataRepository,
        private readonly ProcessedFileRepository $processedFileRepository,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->setHelp('
Files that are deleted outside of TYPO3 (e.g. via SSH or FTP) keep their database records in
sys_file and sys_file_metadata. The storage indexer flags those records as "missing" and the
reports module lists them, but nothing removes them. This command removes the records of
missing files that are not referenced anymore.

Assumptions:
- a perfect integrity of the reference index table (sys_refindex), as it decides whether a
  file is still referenced. Always update the reference index before using this command!
- an up to date storage index (sys_file.missing). Run the "File Abstraction Layer: Update
  storage index" scheduler task or pass --update-storage before using this command!

Missing files that are still referenced are never removed. Either restore the file, or remove
the references and run the command again.

If the option "--dry-run" is not set, the records are removed the same way as for a file
deleted via TYPO3: the sys_file and sys_file_metadata records, their category relations,
their processed files (records and files in the processing folder) and left-over deleted
file references are removed, and the reference index entries are updated. Use the --verbose
option to list the affected files.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, no records are removed, only the records that would be removed are shown'
            )
            ->addOption(
                'update-refindex',
                null,
                InputOption::VALUE_NONE,
                'Setting this option automatically updates the reference index and does not ask on command line. Alternatively, use -n to avoid the interactive mode'
            )
            ->addOption(
                'update-storage',
                null,
                InputOption::VALUE_REQUIRED | InputOption::VALUE_IS_ARRAY,
                'Update the storage index of the storage with the given uid first, so that files deleted outside of TYPO3 are flagged as missing. Can be given multiple times, "all" updates all storages'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $dryRun = (bool)$input->getOption('dry-run');

        $this->updateReferenceIndex($input, $io);
        if (!$this->updateStorageIndex($input, $io)) {
            return Command::INVALID;
        }

        $missingFiles = $this->findMissingFiles();
        if ($missingFiles === []) {
            $io->success('No files flagged as missing found.');
            return Command::SUCCESS;
        }

        $referencedFileUids = $this->findReferencedFileUids(array_keys($missingFiles));
        $referencedFiles = array_intersect_key($missingFiles, array_flip($referencedFileUids));
        $unreferencedFiles = array_diff_key($missingFiles, $referencedFiles);

        if ($referencedFiles !== []) {
            $io->note(sprintf(
                '%d missing file(s) are still referenced and are kept. Restore the files or remove the references.',
                count($referencedFiles)
            ));
            if ($io->isVerbose()) {
                $io->listing($this->formatFiles($referencedFiles));
            }
        }

        if ($unreferencedFiles === []) {
            $io->success('All missing files are still referenced, nothing to remove.');
            return Command::SUCCESS;
        }

        if ($io->isVerbose()) {
            $io->listing($this->formatFiles($unreferencedFiles));
        }

        if ($dryRun) {
            $io->success(sprintf('%d missing file record(s) without references would be removed.', count($unreferencedFiles)));
            return Command::SUCCESS;
        }

        foreach ($unreferencedFiles as $fileRow) {
            $this->removeFile($fileRow);
        }
        $io->success(sprintf('Removed %d missing file record(s) without references.', count($unreferencedFiles)));

        return Command::SUCCESS;
    }

    /**
     * - if the option --update-refindex is set, update the reference index
     * - otherwise, if in interactive mode (not having -n set), ask the user
     * - otherwise assume the reference index is up to date
     */
    private function updateReferenceIndex(InputInterface $input, SymfonyStyle $io): void
    {
        $io->note('Finding missing files without references requires a clean reference index (sys_refindex)');
        if ($input->getOption('update-refindex')) {
            $updateReferenceIndex = true;
        } elseif ($input->isInteractive()) {
            $updateReferenceIndex = $io->confirm('Should the reference index be updated right now?', false);
        } else {
            $updateReferenceIndex = false;
        }

        if ($updateReferenceIndex) {
            $progressListener = new ReferenceIndexProgressListener();
            $progressListener->initialize($io);
            $this->referenceIndex->updateIndex(false, $progressListener);
        } else {
            $io->writeln('Reference index is assumed to be up to date, continuing.');
        }
    }

    /**
     * - if the option --update-storage is set, update the index of the given storages
     * - otherwise, if in interactive mode (not having -n set), ask the user to update all storages
     * - otherwise assume the storage index is up to date
     *
     * @return bool false if a given storage uid does not exist
     */
    private function updateStorageIndex(InputInterface $input, SymfonyStyle $io): bool
    {
        $io->note('Finding missing files requires an up to date storage index (sys_file.missing)');
        $requestedStorages = $input->getOption('update-storage');
        if ($requestedStorages === [] && $input->isInteractive()) {
            if ($io->confirm('Should the index of all file storages be updated right now?', false)) {
                $requestedStorages = ['all'];
            }
        }
        if ($requestedStorages === []) {
            $io->writeln('The storage index is assumed to be up to date, continuing.');
            return true;
        }

        $storages = [];
        if (in_array('all', $requestedStorages, true)) {
            $storages = $this->storageRepository->findAll();
        } else {
            foreach ($requestedStorages as $storageUid) {
                $storage = $this->storageRepository->findByUid((int)$storageUid);
                if ($storage === null) {
                    $io->error(sprintf('File storage with uid "%s" does not exist.', $storageUid));
                    return false;
                }
                $storages[$storage->getUid()] = $storage;
            }
        }

        foreach ($storages as $storage) {
            $this->indexStorage($storage);
            $io->writeln(sprintf('Updated the index of storage "%s" (uid %d).', $storage->getName(), $storage->getUid()));
        }
        return true;
    }

    private function indexStorage(ResourceStorage $storage): void
    {
        $currentEvaluatePermissionsValue = $storage->getEvaluatePermissions();
        $storage->setEvaluatePermissions(false);
        new Indexer($storage)->processChangesInStorages();
        $storage->setEvaluatePermissions($currentEvaluatePermissionsValue);
    }

    /**
     * @return array<int, array<string, mixed>> sys_file rows flagged as missing, keyed by uid
     */
    private function findMissingFiles(): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file');
        $result = $queryBuilder
            ->select('*')
            ->from('sys_file')
            ->where(
                $queryBuilder->expr()->eq('missing', $queryBuilder->createNamedParameter(1, Connection::PARAM_INT))
            )
            ->orderBy('uid')
            ->executeQuery();
        $missingFiles = [];
        while ($row = $result->fetchAssociative()) {
            $missingFiles[(int)$row['uid']] = $row;
        }
        return $missingFiles;
    }

    /**
     * Looks up which of the given files are referenced in sys_refindex. Relations
     * from sys_file_metadata do not count, as metadata belongs to the file itself.
     *
     * @param int[] $fileUids
     * @return int[]
     */
    private function findReferencedFileUids(array $fileUids): array
    {
        $referencedFileUids = [];
        foreach (array_chunk($fileUids, 1000) as $fileUidChunk) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_refindex');
            $result = $queryBuilder
                ->selectLiteral('DISTINCT ' . $queryBuilder->quoteIdentifier('ref_uid'))
                ->from('sys_refindex')
                ->where(
                    $queryBuilder->expr()->eq('ref_table', $queryBuilder->createNamedParameter('sys_file')),
                    $queryBuilder->expr()->in('ref_uid', $queryBuilder->createNamedParameter($fileUidChunk, Connection::PARAM_INT_ARRAY)),
                    $queryBuilder->expr()->neq('tablename', $queryBuilder->createNamedParameter('sys_file_metadata'))
                )
                ->executeQuery();
            foreach ($result->fetchFirstColumn() as $referencedFileUid) {
                $referencedFileUids[] = (int)$referencedFileUid;
            }
        }
        return $referencedFileUids;
    }

    /**
     * Removes everything FileDeletionAspect removes for a file deleted via FAL:
     * processed files, category relations, metadata, left-over (deleted) file
     * references and finally the index record itself.
     *
     * @param array<string, mixed> $fileRow
     */
    private function removeFile(array $fileRow): void
    {
        $fileUid = (int)$fileRow['uid'];
        $file = $this->resourceFactory->getFileObject($fileUid, $fileRow);
        foreach ($this->processedFileRepository->findAllByOriginalFile($file) as $processedFile) {
            if ($processedFile->exists()) {
                $processedFile->delete(true);
            }
            $this->processedFileRepository->remove($processedFile);
        }

        $metaDataUids = $this->findMetaDataUids($fileUid);
        if ($metaDataUids !== []) {
            $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_category_record_mm');
            $queryBuilder
                ->delete('sys_category_record_mm')
                ->where(
                    $queryBuilder->expr()->eq('tablenames', $queryBuilder->createNamedParameter('sys_file_metadata')),
                    $queryBuilder->expr()->in('uid_foreign', $queryBuilder->createNamedParameter($metaDataUids, Connection::PARAM_INT_ARRAY))
                )
                ->executeStatement();
        }
        $this->metaDataRepository->removeByFileUid($fileUid);
        foreach ($metaDataUids as $metaDataUid) {
            $this->referenceIndex->updateRefIndexTable('sys_file_metadata', $metaDataUid);
        }

        $this->connectionPool->getConnectionForTable('sys_file_reference')->delete(
            'sys_file_reference',
            ['uid_local' => $fileUid]
        );
        $this->fileIndexRepository->remove($fileUid);
    }

    /**
     * @return int[]
     */
    private function findMetaDataUids(int $fileUid): array
    {
        $queryBuilder = $this->connectionPool->getQueryBuilderForTable('sys_file_metadata');
        $queryBuilder->getRestrictions()->removeAll();
        return array_map(
            intval(...),
            $queryBuilder
                ->select('uid')
                ->from('sys_file_metadata')
                ->where(
                    $queryBuilder->expr()->eq('file', $queryBuilder->createNamedParameter($fileUid, Connection::PARAM_INT))
                )
                ->executeQuery()
                ->fetchFirstColumn()
        );
    }

    /**
     * @param array<int, array<string, mixed>> $files
     * @return string[]
     */
    private function formatFiles(array $files): array
    {
        return array_map(
            static fn(array $file): string => sprintf('sys_file:%d (%d:%s)', $file['uid'], $file['storage'], $file['identifier']),
            array_values($files)
        );
    }
}
