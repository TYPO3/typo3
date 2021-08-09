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

namespace TYPO3\CMS\Impexp\Utility;

use Psr\EventDispatcher\EventDispatcherInterface;
use TYPO3\CMS\Core\Log\LogManager;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Impexp\Event\BeforeImportEvent;
use TYPO3\CMS\Impexp\Import;

/**
 * Utility for import / export
 * Can be used for API access for simple importing of files.
 *
 * @internal This class is not considered part of the public TYPO3 API.
 */
class ImportExportUtility
{
    protected ?Import $import = null;
    protected EventDispatcherInterface $eventDispatcher;

    public function __construct(EventDispatcherInterface $eventDispatcher)
    {
        $this->eventDispatcher = $eventDispatcher;
    }

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
     * @return int ID of first created page
     */
    public function importT3DFile(string $file, int $pid): int
    {
        $this->import = GeneralUtility::makeInstance(Import::class);
        $this->import->setPid($pid);

        $this->eventDispatcher->dispatch(new BeforeImportEvent($this->import));

        try {
            $this->import->loadFile($file, true);
            $this->import->importData();
        } catch (\Exception $e) {
            $logger = GeneralUtility::makeInstance(LogManager::class)->getLogger(__CLASS__);
            $logger->warning(
                $e->getMessage() . PHP_EOL . implode(PHP_EOL, $this->import->getErrorLog())
            );
        }

        // Get id of first created page:
        $importResponse = 0;
        $importMapId = $this->import->getImportMapId();
        if (isset($importMapId['pages'])) {
            $newPages = $importMapId['pages'];
            $importResponse = (int)reset($newPages);
        }

        // Check for errors during the import process:
        if ($this->import->hasErrors()) {
            if (!$importResponse) {
                throw new \ErrorException('No page records imported', 1377625537);
            }
        }
        return $importResponse;
    }
}
