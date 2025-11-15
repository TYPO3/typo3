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

namespace TYPO3\CMS\Form\Domain\DTO;

/**
 * FormMetadata - Lightweight DTO for form listings
 * Contains only metadata, not the full form definition
 *
 * @internal
 */
final readonly class FormMetadata
{
    public function __construct(
        public string $identifier,
        public string $type,
        public string $name,
        public string $prototypeName,
        public ?string $persistenceIdentifier = null,
        public bool $invalid = false,
        public bool $readOnly = false,
        public bool $removable = true,
        public ?string $storageType = null,
        public bool $duplicateIdentifier = false,
        public ?int $fileUid = null,
        public int $referenceCount = 0,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            identifier: $data['identifier'] ?? '',
            type: $data['type'] ?? 'Form',
            name: $data['label'] ?? $data['identifier'] ?? '',
            prototypeName: $data['prototypeName'] ?? 'standard',
            persistenceIdentifier: $data['persistenceIdentifier'] ?? null,
            invalid: $data['invalid'] ?? false,
            readOnly: $data['readOnly'] ?? false,
            removable: $data['removable'] ?? true,
            storageType: $data['storageType'] ?? null,
            duplicateIdentifier: $data['duplicateIdentifier'] ?? false,
            fileUid: $data['fileUid'] ?? null,
            referenceCount: $data['referenceCount'] ?? 0,
        );
    }

    public static function createInvalid(
        string $persistenceIdentifier,
        string $errorMessage
    ): self {
        return new self(
            identifier: $persistenceIdentifier,
            type: 'Form',
            name: $errorMessage,
            prototypeName: 'standard',
            persistenceIdentifier: $persistenceIdentifier,
            invalid: true,
        );
    }

    public static function createFromYaml(
        array $yamlData,
        string $persistenceIdentifier,
        ?int $fileUid = null
    ): self {
        return self::fromArray($yamlData)
            ->withPersistenceIdentifier($persistenceIdentifier)
            ->withFileUid($fileUid);
    }

    public function toArray(): array
    {
        return [
            'identifier' => $this->identifier,
            'type' => $this->type,
            'label' => $this->name,
            'name' => $this->name,
            'prototypeName' => $this->prototypeName,
            'persistenceIdentifier' => $this->persistenceIdentifier ?? $this->identifier,
            'invalid' => $this->invalid,
            'readOnly' => $this->readOnly,
            'removable' => $this->removable,
            'storageType' => $this->storageType,
            'location' => $this->storageType,
            'duplicateIdentifier' => $this->duplicateIdentifier,
            'fileUid' => $this->fileUid,
            'referenceCount' => $this->referenceCount,
        ];
    }

    private function with(array $changes): self
    {
        return new self(
            identifier: $changes['identifier'] ?? $this->identifier,
            type: $changes['type'] ?? $this->type,
            name: $changes['name'] ?? $this->name,
            prototypeName: $changes['prototypeName'] ?? $this->prototypeName,
            persistenceIdentifier: $changes['persistenceIdentifier'] ?? $this->persistenceIdentifier,
            invalid: $changes['invalid'] ?? $this->invalid,
            readOnly: $changes['readOnly'] ?? $this->readOnly,
            removable: $changes['removable'] ?? $this->removable,
            storageType: $changes['storageType'] ?? $this->storageType,
            duplicateIdentifier: $changes['duplicateIdentifier'] ?? $this->duplicateIdentifier,
            fileUid: $changes['fileUid'] ?? $this->fileUid,
            referenceCount: $changes['referenceCount'] ?? $this->referenceCount,
        );
    }

    public function withPersistenceIdentifier(string $persistenceIdentifier): self
    {
        return $this->with(['persistenceIdentifier' => $persistenceIdentifier]);
    }

    public function withStorageType(string $storageType): self
    {
        return $this->with(['storageType' => $storageType]);
    }

    public function withDuplicateIdentifier(bool $duplicateIdentifier): self
    {
        return $this->with(['duplicateIdentifier' => $duplicateIdentifier]);
    }

    public function withReadOnly(bool $readOnly): self
    {
        return $this->with(['readOnly' => $readOnly]);
    }

    public function withRemovable(bool $removable): self
    {
        return $this->with(['removable' => $removable]);
    }

    public function withFileUid(?int $fileUid): self
    {
        return $this->with(['fileUid' => $fileUid]);
    }

    public function withReferenceCount(int $referenceCount): self
    {
        return $this->with(['referenceCount' => $referenceCount]);
    }

    public function withInvalid(bool $invalid): self
    {
        return $this->with(['invalid' => $invalid]);
    }
}
