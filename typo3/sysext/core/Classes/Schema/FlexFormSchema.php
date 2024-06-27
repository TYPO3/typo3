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

namespace TYPO3\CMS\Core\Schema;

use TYPO3\CMS\Core\Schema\Field\FieldTypeInterface;
use TYPO3\CMS\Core\Schema\Struct\FlexSheet;

/**
 * @internal This is an experimental implementation and might change until TYPO3 v13 LTS
 */
final readonly class FlexFormSchema implements SchemaInterface
{
    public function __construct(
        protected string $structIdentifier,
        /** @var FlexSheet[] */
        protected array $sheets
    ) {}

    public function getSheets(): array
    {
        return $this->sheets;
    }

    public function getName(): string
    {
        return $this->structIdentifier;
    }

    public function getField(string $fieldName, string $sheetName = 'sDEF'): ?FieldTypeInterface
    {
        if (!isset($this->sheets[$sheetName])) {
            return null;
        }
        if ($this->sheets[$sheetName]->hasField($sheetName . '/' . $fieldName)) {
            return $this->sheets[$sheetName]->getField($sheetName . '/' . $fieldName);
        }
        // Look for any kind of field that has the same name, regardless of the sheet name
        foreach ($this->sheets as $sheetName => $sheet) {
            if ($sheet->hasField($sheetName . '/' . $fieldName)) {
                return $sheet->getField($sheetName . '/' . $fieldName);
            }
        }
        return null;
    }

    public static function __set_state(array $state): self
    {
        return new self(...$state);
    }
}
