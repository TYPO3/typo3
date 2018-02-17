<?php
declare(strict_types = 1);
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
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use TYPO3\CMS\Core\Core\Bootstrap;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\ReferenceIndex;
use TYPO3\CMS\Core\Utility\File\BasicFileUtility;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Looking up all occurencies of RTEmagic images in the database and check existence of parent and
 * copy files on the file system plus report possibly lost files of this type
 */
class RteImagesCommand extends Command
{

    /**
     * Configure the command by defining the name, options and arguments
     */
    public function configure()
    {
        $this
            ->setDescription('Looking up all occurrences of RTEmagic images in the database and check existence of parent and copy files on the file system plus report possibly lost RTE files.')
            ->setHelp('
Assumptions:
- a perfect integrity of the reference index table (always update the reference index table before using this tool!)
- that all RTEmagic image files in the database are registered with the soft reference parser "images"
- images found in deleted records are included (means that you might find lost RTEmagic images after flushing deleted records)

The assumptions are not requirements by the TYPO3 API but reflects the de facto implementation of most TYPO3 installations.
However, many custom fields using an RTE will probably not have the "images" soft reference parser registered and so the index will be incomplete and not listing all RTEmagic image files.
The consequence of this limitation is that you should be careful if you wish to delete lost RTEmagic images - they could be referenced from a field not parsed by the "images" soft reference parser!

Automatic Repair of Errors:
- Will search for double-usages of RTEmagic images and make copies as required.
- Lost files can be deleted automatically, but it is recommended to delete them manually if you do not recognize them as used somewhere the system does not know about.

Manual repair suggestions:
- Missing files: Re-insert missing files or edit record where the reference is found.

If the option "--dry-run" is not set, the files are then deleted automatically.
Warning: First, make sure those files are not used somewhere TYPO3 does not know about! See the assumptions above.

If you want to get more detailed information, use the --verbose option.')
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'If this option is set, the files will not actually be deleted, but just the output which files would be deleted are shown'
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
     * - find files within uploads/* which are not connected to the reference index
     * - remove these files if --dry-run is not set
     *
     * @param InputInterface $input
     * @param OutputInterface $output
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // Make sure the _cli_ user is loaded
        Bootstrap::initializeBackendAuthentication();

        $io = new SymfonyStyle($input, $output);
        $io->title($this->getDescription());

        $dryRun = $input->hasOption('dry-run') && $input->getOption('dry-run') != false ? true : false;

        $this->updateReferenceIndex($input, $io);

        // Find the RTE files
        $allRteImagesInUse = $this->findAllReferencedRteImagesWithOriginals();

        if (count($allRteImagesInUse)) {
            $allRteImagesWithOriginals = [];
            $multipleReferenced = [];
            $missingFiles = [];
            $lostFiles = [];

            // Searching for duplicates, and missing files (also missing originals)
            foreach ($allRteImagesInUse as $fileName => $fileInfo) {
                $allRteImagesWithOriginals[$fileName]++;
                $allRteImagesWithOriginals[$fileInfo['original']]++;
                if ($fileInfo['count'] > 1 && $fileInfo['exists'] && $fileInfo['original_exists']) {
                    $multipleReferenced[$fileName] = $fileInfo['softReferences'];
                }
                // Missing files:
                if (!$fileInfo['exists']) {
                    $missingFiles[$fileName] = $fileInfo['softReferences'];
                }
                if (!$fileInfo['original_exists']) {
                    $missingFiles[$fileInfo['original']] = $fileInfo['softReferences'];
                }
            }

            // Now, ask for RTEmagic files inside uploads/ folder:
            $magicFiles = $this->findAllRteFilesInDirectory();
            foreach ($magicFiles as $fileName) {
                if (!isset($allRteImagesWithOriginals[$fileName])) {
                    $lostFiles[$fileName] = $fileName;
                }
            }
            ksort($missingFiles);
            ksort($multipleReferenced);

            // Output info about missing files
            if (!$io->isQuiet()) {
                $io->note('Found ' . count($missingFiles) . ' RTE images that are referenced, but missing.');
                if ($io->isVerbose()) {
                    $io->listing($missingFiles);
                }
            }

            // Duplicate RTEmagic image files
            // These files are RTEmagic images found used in multiple records! RTEmagic images should be used by only
            // one record at a time. A large amount of such images probably stems from previous versions of TYPO3 (before 4.2)
            // which did not support making copies automatically of RTEmagic images in case of new copies / versions.
            $this->copyMultipleReferencedRteImages($multipleReferenced, $dryRun, $io);

            // Delete lost files
            // Lost RTEmagic files from uploads/
            // These files you might be able to delete but only if _all_ RTEmagic images are found by the soft reference parser.
            // If you are using the RTE in third-party extensions it is likely that the soft reference parser is not applied
            // correctly to their RTE and thus these "lost" files actually represent valid RTEmagic images,
            // just not registered. Lost files can be auto-fixed but only if you specifically
            // set "lostFiles" as parameter to the --AUTOFIX option.
            if (count($lostFiles)) {
                ksort($lostFiles);
                $this->deleteLostFiles($lostFiles, $dryRun, $io);
                $io->success('Deleted ' . count($lostFiles) . ' lost files.');
            }
        } else {
            $io->success('Nothing to do, your system does not have any RTE images.');
        }
    }

    /**
     * Function to update the reference index
     * - if the option --update-refindex is set, do it
     * - otherwise, if in interactive mode (not having -n set), ask the user
     * - otherwise assume everything is fine
     *
     * @param InputInterface $input holds information about entered parameters
     * @param SymfonyStyle $io necessary for outputting information
     */
    protected function updateReferenceIndex(InputInterface $input, SymfonyStyle $io)
    {
        // Check for reference index to update
        $io->note('Finding RTE images used in TYPO3 requires a clean reference index (sys_refindex)');
        $updateReferenceIndex = false;
        if ($input->hasOption('update-refindex') && $input->getOption('update-refindex')) {
            $updateReferenceIndex = true;
        } elseif ($input->isInteractive()) {
            $updateReferenceIndex = $io->confirm('Should the reference index be updated right now?', false);
        }

        // Update the reference index
        if ($updateReferenceIndex) {
            $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
            $referenceIndex->updateIndex(false, !$io->isQuiet());
        } else {
            $io->writeln('Reference index is assumed to be up to date, continuing.');
        }
    }

    /**
     * Find lost files in uploads/ folder
     *
     * @return array an array of files (relative to PATH_site) that are not connected
     */
    protected function findAllReferencedRteImagesWithOriginals(): array
    {
        $allRteImagesInUse = [];

        // Select all RTEmagic files in the reference table (only from soft references of course)
        $queryBuilder = GeneralUtility::makeInstance(ConnectionPool::class)
            ->getQueryBuilderForTable('sys_refindex');

        $result = $queryBuilder
            ->select('*')
            ->from('sys_refindex')
            ->where(
                $queryBuilder->expr()->eq(
                    'ref_table',
                    $queryBuilder->createNamedParameter('_FILE', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->like(
                    'ref_string',
                    $queryBuilder->createNamedParameter('%/RTEmagic%', \PDO::PARAM_STR)
                ),
                $queryBuilder->expr()->eq(
                    'softref_key',
                    $queryBuilder->createNamedParameter('images', \PDO::PARAM_STR)
                )
            )
            ->execute();

        // Traverse the files and put into a large table:
        while ($rec = $result->fetch()) {
            $file = $rec['ref_string'];
            $filename = basename($file);
            if (strpos($filename, 'RTEmagicC_') === 0) {
                // First time the file is referenced => build index
                if (!is_array($allRteImagesInUse[$file])) {
                    $original = 'RTEmagicP_' . preg_replace('/\\.[[:alnum:]]+$/', '', substr($filename, 10));
                    $original = substr($file, 0, -strlen($filename)) . $original;
                    $allRteImagesInUse[$file] = [
                        'exists' => @is_file(PATH_site . $file),
                        'original' => $original,
                        'original_exists' => @is_file(PATH_site . $original),
                        'count' => 0,
                        'softReferences' => []
                    ];
                }
                $allRteImagesInUse[$file]['count']++;
                $allRteImagesInUse[$file]['softReferences'][$rec['hash']] = $this->formatReferenceIndexEntryToString($rec);
            }
        }

        ksort($allRteImagesInUse);
        return $allRteImagesInUse;
    }

    /**
     * Find all RTE files in uploads/ folder
     *
     * @param string $folder the name of the folder to start from
     * @return array an array of files (relative to PATH_site) that are not connected
     */
    protected function findAllRteFilesInDirectory($folder = 'uploads/'): array
    {
        $filesFound = [];

        // Get all files
        $files = [];
        $files = GeneralUtility::getAllFilesAndFoldersInPath($files, PATH_site . $folder);
        $files = GeneralUtility::removePrefixPathFromList($files, PATH_site);

        // Traverse files
        foreach ($files as $key => $value) {
            // If the file is a RTEmagic-image name
            if (preg_match('/^RTEmagic[P|C]_/', basename($value))) {
                $filesFound[] = $value;
                continue;
            }
        }

        return $filesFound;
    }

    /**
     * Removes given files from the uploads/ folder
     *
     * @param array $lostFiles Contains the lost files found
     * @param bool $dryRun if set, the files are just displayed, but not deleted
     * @param SymfonyStyle $io the IO object for output
     */
    protected function deleteLostFiles(array $lostFiles, bool $dryRun, SymfonyStyle $io)
    {
        foreach ($lostFiles as $lostFile) {
            $absoluteFileName = GeneralUtility::getFileAbsFileName($lostFile);
            if ($io->isVeryVerbose()) {
                $io->writeln('Deleting file "' . $absoluteFileName . '"');
            }
            if (!$dryRun) {
                if ($absoluteFileName && @is_file($absoluteFileName)) {
                    unlink($absoluteFileName);
                    if (!$io->isQuiet()) {
                        $io->writeln('Permanently deleted file "' . $absoluteFileName . '".');
                    }
                } else {
                    $io->error('File "' . $absoluteFileName . '" was not found!');
                }
            }
        }
    }

    /**
     * Duplicate RTEmagic image files which are used on several records. RTEmagic images should be used by only
     * one record at a time. A large amount of such images probably stems from previous versions of TYPO3 (before 4.2)
     * which did not support making copies automatically of RTEmagic images in case of new copies / versions.
     *
     * @param array $multipleReferencedImages
     * @param bool $dryRun
     * @param SymfonyStyle $io
     */
    protected function copyMultipleReferencedRteImages(array $multipleReferencedImages, bool $dryRun, SymfonyStyle $io)
    {
        $fileProcObj = GeneralUtility::makeInstance(BasicFileUtility::class);
        foreach ($multipleReferencedImages as $fileName => $fileInfo) {
            // Traverse all records using the file
            $c = 0;
            foreach ($fileInfo['usedIn'] as $hash => $recordID) {
                if ($c === 0) {
                    $io->writeln('Keeping file ' . $fileName . ' for record ' . $recordID);
                } else {
                    $io->writeln('Copying file ' . basename($fileName) . ' for record ' . $recordID);
                    // Get directory prefix for file and set the original name
                    $dirPrefix = dirname($fileName) . '/';
                    $rteOrigName = basename($fileInfo['original']);
                    // If filename looks like an RTE file, and the directory is in "uploads/", then process as a RTE file!
                    if ($rteOrigName && strpos($dirPrefix, 'uploads/') === 0 && @is_dir((PATH_site . $dirPrefix))) {
                        // From the "original" RTE filename, produce a new "original" destination filename which is unused.
                        $origDestName = $fileProcObj->getUniqueName($rteOrigName, PATH_site . $dirPrefix);
                        // Create copy file name
                        $pI = pathinfo($fileName);
                        $copyDestName = dirname($origDestName) . '/RTEmagicC_' . substr(basename($origDestName), 10) . '.' . $pI['extension'];
                        if (!@is_file($copyDestName) && !@is_file($origDestName) && $origDestName === GeneralUtility::getFileAbsFileName($origDestName) && $copyDestName === GeneralUtility::getFileAbsFileName($copyDestName)) {
                            $io->writeln('Copying file ' . basename($fileName) . ' for record ' . $recordID . ' to ' . basename($copyDestName));
                            if (!$dryRun) {
                                // Making copies
                                GeneralUtility::upload_copy_move(PATH_site . $fileInfo['original'], $origDestName);
                                GeneralUtility::upload_copy_move(PATH_site . $fileName, $copyDestName);
                                clearstatcache();
                                if (@is_file($copyDestName)) {
                                    $referenceIndex = GeneralUtility::makeInstance(ReferenceIndex::class);
                                    $error = $referenceIndex->setReferenceValue($hash, PathUtility::stripPathSitePrefix($copyDestName));
                                    if ($error) {
                                        $io->error('ReferenceIndex::setReferenceValue() reported "' . $error . '"');
                                    }
                                } else {
                                    $io->error('File "' . $copyDestName . '" could not be created.');
                                }
                            }
                        } else {
                            $io->error('Could not construct new unique names for file.');
                        }
                    } else {
                        $io->error('Maybe directory of file was not within "uploads/"?');
                    }
                }
                $c++;
            }
        }
    }

    /**
     * Formats a sys_refindex entry to something readable
     *
     * @param array $record
     * @return string
     */
    protected function formatReferenceIndexEntryToString(array $record): string
    {
        return $record['tablename']
            . ':' . $record['recuid']
            . ':' . $record['field']
            . ($record['flexpointer'] ? ':' . $record['flexpointer'] : '')
            . ($record['softref_key'] ? ':' . $record['softref_key'] . ' (Soft Reference) ' : '')
            . ($record['deleted'] ? ' (DELETED)' : '');
    }
}
