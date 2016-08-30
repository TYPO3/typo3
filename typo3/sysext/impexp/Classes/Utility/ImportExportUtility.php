<?php
namespace TYPO3\CMS\Impexp\Utility;

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

use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;
use TYPO3\CMS\Impexp\Import;

/**
 * Utility for import / export
 * Can be used for API access for simple importing of files
 *
 */
class ImportExportUtility
{
    /**
     * Import a T3D file directly
     *
     * @param string $file The full absolute path to the file
     * @param int $pid The pid under which the t3d file should be imported
     * @throws \ErrorException
     * @throws \InvalidArgumentException
     * @return int
     */
    public function importT3DFile($file, $pid)
    {
        if (!is_string($file)) {
            throw new \InvalidArgumentException('Input parameter $file has to be of type string', 1377625645);
        }
        if (!is_int($pid)) {
            throw new \InvalidArgumentException('Input parameter $int has to be of type integer', 1377625646);
        }
        /** @var $import Import */
        $import = GeneralUtility::makeInstance(Import::class);
        $import->init(0, 'import');

        $this->emitAfterImportExportInitialisationSignal($import);

        $importResponse = 0;
        if ($file && @is_file($file)) {
            if ($import->loadFile($file, 1)) {
                // Import to root page:
                $import->importData($pid);
                // Get id of first created page:
                $newPages = $import->import_mapId['pages'];
                $importResponse = (int)reset($newPages);
            }
        }

        // Check for errors during the import process:
        $errors = $import->printErrorLog();
        if ($errors !== '') {
            /** @var \TYPO3\CMS\Core\Log\Logger $logger */
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning($errors);

            if (!$importResponse) {
                throw new \ErrorException('No page records imported', 1377625537);
            }
        }
        return $importResponse;
    }

    /**
     * Get the SignalSlot dispatcher
     *
     * @return Dispatcher
     */
    protected function getSignalSlotDispatcher()
    {
        return GeneralUtility::makeInstance(Dispatcher::class);
    }

    /**
     * Emits a signal after initialization
     *
     * @param Import $import
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotException
     * @throws \TYPO3\CMS\Extbase\SignalSlot\Exception\InvalidSlotReturnException
     */
    protected function emitAfterImportExportInitialisationSignal(Import $import)
    {
        $this->getSignalSlotDispatcher()->dispatch(__CLASS__, 'afterImportExportInitialisation', [$import]);
    }
}
