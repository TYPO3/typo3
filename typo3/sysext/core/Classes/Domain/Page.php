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
use TYPO3\CMS\Core\Domain\Record\SystemProperties;

/**
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class Page extends Record implements \ArrayAccess
{
    protected array $specialPropertyNames = [
        '_language',
        '_LOCALIZED_UID',
        '_REQUESTED_OVERLAY_LANGUAGE',
        '_MP_PARAM',
        '_ORIG_uid',
        '_ORIG_pid',
        '_SHORTCUT_ORIGINAL_PAGE_UID',
        '_MOUNT_POINT_ORIGINAL_PAGE_PID',
        '_TRANSLATION_SOURCE',
    ];

    protected array $specialProperties = [];

    /**
     * @param RawRecord|array $rawRecordOrProperties RawRecord when created via RecordFactory, array for legacy usage
     */
    public function __construct(RawRecord|array $rawRecordOrProperties, array $properties = [], ?SystemProperties $systemProperties = null)
    {
        if ($rawRecordOrProperties instanceof RawRecord) {
            parent::__construct($rawRecordOrProperties, $properties, $systemProperties);
            $this->extractSpecialPropertiesFromComputed($rawRecordOrProperties);
        } else {
            $this->initFromArray($rawRecordOrProperties);
        }
    }

    public function has(string $id): bool
    {
        if (parent::has($id)) {
            return true;
        }
        return array_key_exists($id, $this->specialProperties);
    }

    public function get(string $id): mixed
    {
        if (parent::has($id)) {
            return parent::get($id);
        }
        if (array_key_exists($id, $this->specialProperties)) {
            return $this->specialProperties[$id];
        }
        throw new RecordPropertyNotFoundException('Record property "' . $id . '" is not available.', 1725892141);
    }

    public function getLanguageId(): int
    {
        if ($this->systemProperties?->getLanguage() !== null) {
            return $this->systemProperties->getLanguage()->getLanguageId();
        }
        return (int)($this->specialProperties['_language'] ?? $this->properties['sys_language_uid'] ?? 0);
    }

    public function getPageId(): int
    {
        if ($this->systemProperties?->getLanguage() !== null) {
            $translationParent = $this->systemProperties->getLanguage()->getTranslationParent();
            return $translationParent > 0 ? $translationParent : $this->getUid();
        }
        $pageId = isset($this->properties['l10n_parent']) && $this->properties['l10n_parent'] > 0 ? $this->properties['l10n_parent'] : $this->getUid();
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

    public function toArray(bool $includeSystemProperties = false): array
    {
        if ($includeSystemProperties) {
            // When including system properties, return the full raw record overlaid
            // with resolved properties and special properties for backward compatibility.
            $result = $this->rawRecord->toArray();
            foreach ($this->properties as $key => $property) {
                if ($property instanceof RecordPropertyClosure) {
                    $this->properties[$key] = $property->instantiate();
                }
                $result[$key] = $this->properties[$key];
            }
            $result += ['_system' => $this->systemProperties?->toArray() ?? []];
            $result += $this->specialProperties;
            return $result;
        }
        return parent::toArray();
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->has((string)$offset);
    }

    public function offsetGet(mixed $offset): mixed
    {
        return $this->has((string)$offset) ? $this->get((string)$offset) : null;
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        $this->properties[$offset] = $value;
    }

    public function offsetUnset(mixed $offset): void
    {
        unset($this->properties[$offset]);
    }

    private function extractSpecialPropertiesFromComputed(RawRecord $rawRecord): void
    {
        $computedProperties = $rawRecord->getComputedProperties();
        if ($computedProperties->getLocalizedUid() !== null) {
            $this->specialProperties['_LOCALIZED_UID'] = $computedProperties->getLocalizedUid();
        }
        if ($computedProperties->getRequestedOverlayLanguageId() !== null) {
            $this->specialProperties['_REQUESTED_OVERLAY_LANGUAGE'] = $computedProperties->getRequestedOverlayLanguageId();
        }
        if ($computedProperties->getTranslationSource() !== null) {
            $this->specialProperties['_TRANSLATION_SOURCE'] = $computedProperties->getTranslationSource();
        }
        if ($computedProperties->getVersionedUid() !== null) {
            $this->specialProperties['_ORIG_uid'] = $computedProperties->getVersionedUid();
        }
        // Extract remaining special properties from the raw record
        $rawProperties = $rawRecord->toArray();
        foreach ($this->specialPropertyNames as $name) {
            if (isset($rawProperties[$name]) && !isset($this->specialProperties[$name])) {
                $this->specialProperties[$name] = $rawProperties[$name];
            }
        }
    }

    private function initFromArray(array $properties): void
    {
        $regularProperties = [];
        $translationSource = null;
        $localizedUid = null;
        $versionedUid = null;
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
                } elseif ($propertyName === '_ORIG_uid') {
                    $versionedUid = $propertyValue;
                    $this->specialProperties[$propertyName] = $propertyValue;
                } elseif ($propertyName === '_REQUESTED_OVERLAY_LANGUAGE') {
                    $requestedOverlayLanguageId = $propertyValue;
                    $this->specialProperties[$propertyName] = $propertyValue;
                } else {
                    $this->specialProperties[$propertyName] = $propertyValue;
                }
            } else {
                $regularProperties[$propertyName] = $propertyValue;
            }
        }

        $computedProperties = new ComputedProperties(
            versionedUid: $versionedUid,
            localizedUid: $localizedUid,
            requestedOverlayLanguageId: $requestedOverlayLanguageId,
            translationSource: $translationSource
        );

        $recordType = isset($regularProperties['doktype']) ? (string)$regularProperties['doktype'] : null;
        $fullType = $recordType !== null ? 'pages.' . $recordType : 'pages';

        $rawRecord = new RawRecord(
            uid: (int)($regularProperties['uid'] ?? 0),
            pid: (int)($regularProperties['pid'] ?? 0),
            properties: $regularProperties,
            computedProperties: $computedProperties,
            fullType: $fullType
        );

        parent::__construct($rawRecord, $regularProperties, null);
    }
}
