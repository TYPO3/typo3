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

namespace TYPO3\CMS\Backend\Form\Event;

/**
 * Listeners to this Event will be able to add custom controls to a TCA type="file" field in FormEngine
 */
final class CustomFileControlsEvent
{
    private array $controls = [];

    public function __construct(
        private array $resultArray,
        private readonly string $tableName,
        private readonly string $fieldName,
        private readonly array $databaseRow,
        private readonly array $fieldConfig,
        private readonly string $formFieldIdentifier,
        private readonly string $formFieldName,
    ) {}

    public function getResultArray(): array
    {
        return $this->resultArray;
    }

    /**
     * WARNING: Modifying the result array should be used with care. It mostly
     * only exists to allow additional $resultArray['javaScriptModules'].
     */
    public function setResultArray(array $resultArray): void
    {
        $this->resultArray = $resultArray;
    }

    public function getControls(): array
    {
        return $this->controls;
    }

    public function setControls(array $controls): void
    {
        $this->controls = $controls;
    }

    public function addControl(string $control, string $identifier = ''): void
    {
        if ($identifier !== '') {
            $this->controls[$identifier] = $control;
        } else {
            $this->controls[] = $control;
        }
    }

    public function removeControl(string $identifier): bool
    {
        if (!isset($this->controls[$identifier])) {
            return false;
        }
        unset($this->controls[$identifier]);
        return true;
    }

    public function getTableName(): string
    {
        return $this->tableName;
    }

    public function getFieldName(): string
    {
        return $this->fieldName;
    }

    public function getDatabaseRow(): array
    {
        return $this->databaseRow;
    }

    public function getFieldConfig(): array
    {
        return $this->fieldConfig;
    }

    public function getFormFieldIdentifier(): string
    {
        return $this->formFieldIdentifier;
    }

    public function getFormFieldName(): string
    {
        return $this->formFieldName;
    }
}
