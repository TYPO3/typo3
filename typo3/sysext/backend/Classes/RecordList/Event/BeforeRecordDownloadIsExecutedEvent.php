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

namespace TYPO3\CMS\Backend\RecordList\Event;

use Psr\Http\Message\ServerRequestInterface;

/**
 * Listeners to this event are able to manipulate the download of records, usually triggered via Web > List.
 */
final class BeforeRecordDownloadIsExecutedEvent
{
    /**
     * @param array $headerRow - Array of downloaded header metadata
     * @param array $records - Array of the actual data
     * @param ServerRequestInterface $request - PSR request context (for the actual download request)
     * @param string $table - Name of the originating database table
     * @param string $format - Format of the exported data (JSON/CSV)
     * @param string $filename - Name of the exported file for download
     * @param int $id - Page uid from where records are fetched
     * @param array $modTSconfig - Currently applied TS config when exporting
     * @param array $columnsToRender - Array of selected columns that were fetched
     * @param bool $hideTranslations - Hide translations?
     */
    public function __construct(
        private array $headerRow,
        private array $records,
        private readonly ServerRequestInterface $request,
        private readonly string $table,
        private readonly string $format,
        private readonly string $filename,
        private readonly int $id,
        private readonly array $modTSconfig,
        private readonly array $columnsToRender,
        private readonly bool $hideTranslations,
    ) {}

    public function getHeaderRow(): array
    {
        return $this->headerRow;
    }

    public function setHeaderRow(array $headerRow): void
    {
        $this->headerRow = $headerRow;
    }

    public function getRecords(): array
    {
        return $this->records;
    }

    public function setRecords(array $records): void
    {
        $this->records = $records;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getTable(): string
    {
        return $this->table;
    }

    public function getFormat(): string
    {
        return $this->format;
    }

    public function getFilename(): string
    {
        return $this->filename;
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function getModTSconfig(): array
    {
        return $this->modTSconfig;
    }

    public function getColumnsToRender(): array
    {
        return $this->columnsToRender;
    }

    public function isHideTranslations(): bool
    {
        return $this->hideTranslations;
    }
}
