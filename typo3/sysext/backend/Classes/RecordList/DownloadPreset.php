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

namespace TYPO3\CMS\Backend\RecordList;

use TYPO3\CMS\Core\Utility\GeneralUtility;

final class DownloadPreset
{
    public function __construct(
        private readonly string $label,
        private readonly array $columns,
        private string $identifier = '',
    ) {
        $this->identifier = $identifier ?: md5($label . implode($columns));
    }

    public function getIdentifier(): string
    {
        return $this->identifier;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function getColumns(): array
    {
        return $this->columns;
    }

    public static function create(array $preset): self
    {
        $label = $preset['label'] ?? '';

        if (is_array($preset['columns'] ?? null)) {
            $columns = $preset['columns'];
        } else {
            $columns = GeneralUtility::trimExplode(',', $preset['columns'] ?? '', true);
        }

        // Presets with empty columns or empty label are ignored
        if ($columns === [] || $label === '') {
            throw new \InvalidArgumentException('Invalid download preset.', 1718195273);
        }

        return new self(
            $label,
            $columns,
            $preset['identifier'] ?? '',
        );
    }
}
