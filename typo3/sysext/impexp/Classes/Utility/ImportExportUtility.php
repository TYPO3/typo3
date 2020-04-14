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

namespace TYPO3\CMS\Impexp\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Event\BeforeImportEvent;
use TYPO3\CMS\Impexp\Import;

/**
 * Utility for import / export
 * Can be used for API access for simple importing of files
 * @internal
 */
class ImportExportUtility
{
    /**
     * @var Import|null
     */
    protected $import;

    /**
     * @var EventDispatcherInterface
     */
    protected $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

    /**
     * @return Import|null
     */
    public function getImport(): ?Import
    {
        return $this->import;
    }

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
        $this->import = GeneralUtility::makeInstance(Import::class);
        $this->import->init();

        $this->eventDispatcher->dispatch(new BeforeImportEvent($this->import));

        $importResponse = 0;
        if ($file && @is_file($file)) {
            if ($this->import->loadFile($file, 1)) {
                // Import to root page:
                $this->import->importData($pid);
                // Get id of first created page:
                $newPages = $this->import->import_mapId['pages'];
                $importResponse = (int)reset($newPages);
            }
        }

        // Check for errors during the import process:
        $errors = $this->import->printErrorLog();
        if ($errors !== '') {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning($errors);

            if (!$importResponse) {
                throw new \ErrorException('No page records imported', 1377625537);
            }
        }
        return $importResponse;
    }
}
