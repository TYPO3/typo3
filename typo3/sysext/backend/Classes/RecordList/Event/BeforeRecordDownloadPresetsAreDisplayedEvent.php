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
use TYPO3\CMS\Backend\RecordList\DownloadPreset;

/**
 * Event to manipulate the available list of download presets.
 *
 * Array $presets contains a list of DownloadPreset objects
 * with their methods: `getIdentifier()`, `getLabel()` and `getColumns()`.
 *
 * The event is always coupled to a specific database table.
 */
final class BeforeRecordDownloadPresetsAreDisplayedEvent
{
    /** @var DownloadPreset[]  */
    private array $presets;

    /**
     * @param string $table - Name of the originating database table
     * @param array<string|int, array{columns: string|string[]|null, label: string|null}> $presets - Contains list of sub-arrays with keys "label" (string, name of the preset) and "columns" (string, comma-separated list of columns included in the preset)
     * @param ServerRequestInterface $request - Request-context of the action that displays the preset
     * @param int $id - Page ID where the records are stored
     */
    public function __construct(
        private readonly string $table,
        array $presets,
        private readonly ServerRequestInterface $request,
        private readonly int $id,
    ) {
        $this->setPresets($presets);
    }

    /**
     * @return DownloadPreset[]
     */
    public function getPresets(): array
    {
        return $this->presets;
    }

    public function setPresets(array $presets): void
    {
        $this->presets = [];
        foreach ($presets as $preset) {
            if (is_array($preset)) {
                try {
                    $preset = DownloadPreset::create($preset);
                } catch (\InvalidArgumentException) {
                    continue;
                }
            }
            if ($preset instanceof DownloadPreset) {
                $this->presets[$preset->getIdentifier()] = $preset;
            }
        }
    }

    public function getDatabaseTable(): string
    {
        return $this->table;
    }

    public function getRequest(): ServerRequestInterface
    {
        return $this->request;
    }

    public function getId(): int
    {
        return $this->id;
    }
}
