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

namespace TYPO3\CMS\Core\Domain;

use TYPO3\CMS\Core\Domain\Exception\RecordPropertyNotFoundException;
use TYPO3\CMS\Core\Domain\Record\ComputedProperties;

/**
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class Page implements RecordInterface, \ArrayAccess
{
    use PropertyTrait;

    protected array $specialPropertyNames = [
        '_language',
        '_LOCALIZED_UID',
        '_REQUESTED_OVERLAY_LANGUAGE',
        '_MP_PARAM',
        '_ORIG_uid',
        '_ORIG_pid',
        '_SHORTCUT_ORIGINAL_PAGE_UID',
        '_TRANSLATION_SOURCE',
    ];

    protected array $specialProperties = [];
    protected RawRecord $rawRecord;
    protected ComputedProperties $computedProperties;

    public function __construct(array $properties)
    {
        $translationSource = null;
        $localizedUid = null;
        $requestedOverlayLanguageId = null;

        foreach ($properties as $propertyName => $propertyValue) {
            if (in_array($propertyName, $this->specialPropertyNames)) {
                if ($propertyName === '_TRANSLATION_SOURCE' && !$propertyValue instanceof Page) {
                    $translationSource = new Page($propertyValue);
                    $this->specialProperties[$propertyName] = $translationSource;
                } elseif ($propertyName === '_TRANSLATION_SOURCE') {
                    $translationSource = $propertyValue;
                    $this->specialProperties[$propertyName] = $propertyValue;
                } elseif ($propertyName === '_LOCALIZED_UID') {
                    $localizedUid = $propertyValue;
                    $this->specialProperties[$propertyName] = $propertyValue;
                } elseif ($propertyName === '_REQUESTED_OVERLAY_LANGUAGE') {
                    $requestedOverlayLanguageId = $propertyValue;
                    $this->specialProperties[$propertyName] = $propertyValue;
                } else {
                    $this->specialProperties[$propertyName] = $propertyValue;
                }
            } else {
                $this->properties[$propertyName] = $propertyValue;
            }
        }

        // Create computed properties from special properties
        $this->computedProperties = new ComputedProperties(
            versionedUid: null,
            localizedUid: $localizedUid,
            requestedOverlayLanguageId: $requestedOverlayLanguageId,
            translationSource: $translationSource
        );

        // Determine record type for pages (based on doktype)
        $recordType = isset($this->properties['doktype']) ? (string)$this->properties['doktype'] : null;
        $fullType = $recordType !== null ? 'pages.' . $recordType : 'pages';

        // Create RawRecord
        $this->rawRecord = new RawRecord(
            uid: (int)($this->properties['uid'] ?? 0),
            pid: (int)($this->properties['pid'] ?? 0),
            properties: $this->properties,
            computedProperties: $this->computedProperties,
            fullType: $fullType
        );
    }

    // RecordInterface methods
    public function getUid(): int
    {
        return $this->rawRecord->getUid();
    }

    public function getPid(): int
    {
        return $this->rawRecord->getPid();
    }

    public function getFullType(): string
    {
        return $this->rawRecord->getFullType();
    }

    public function getRecordType(): ?string
    {
        return $this->rawRecord->getRecordType();
    }

    public function getMainType(): string
    {
        return $this->rawRecord->getMainType();
    }

    public function getRawRecord(): RawRecord
    {
        return $this->rawRecord;
    }

    public function getComputedProperties(): ComputedProperties
    {
        return $this->computedProperties;
    }

    public function has(string $id): bool
    {
        if (array_key_exists($id, $this->properties)) {
            return true;
        }

        if (in_array($id, ['uid', 'pid'], true)) {
            return true;
        }

        if (array_key_exists($id, $this->specialProperties)) {
            return true;
        }

        return false;
    }

    public function get(string $id): mixed
    {
        if (array_key_exists($id, $this->properties)) {
            return $this->properties[$id];
        }

        if ($id === 'uid') {
            return $this->rawRecord->getUid();
        }

        if ($id === 'pid') {
            return $this->rawRecord->getPid();
        }

        if (array_key_exists($id, $this->specialProperties)) {
            return $this->specialProperties[$id];
        }

        throw new RecordPropertyNotFoundException('Record property "' . $id . '" is not available.', 1725892141);
    }

    // Page-specific methods
    public function getLanguageId(): int
    {
        return $this->specialProperties['_language'] ?? $this->properties['sys_language_uid'];
    }

    public function getPageId(): int
    {
        $pageId = isset($this->properties['l10n_parent']) && $this->properties['l10n_parent'] > 0 ? $this->properties['l10n_parent'] : $this->properties['uid'];
        return (int)$pageId;
    }

    public function getTranslationSource(): ?Page
    {
        return $this->specialProperties['_TRANSLATION_SOURCE'] ?? null;
    }

    public function getRequestedLanguage(): ?int
    {
        return $this->specialProperties['_REQUESTED_OVERLAY_LANGUAGE'] ?? null;
    }

    public function toArray(bool $includeSpecialProperties = false): array
    {
        if ($includeSpecialProperties) {
            return $this->properties + $this->specialProperties;
        }
        return $this->properties;
    }
}
