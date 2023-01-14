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

/**
 * @internal not part of public API, as this needs to be streamlined and proven
 */
class Page implements \ArrayAccess
{
    use PropertyTrait;

    protected array $specialPropertyNames = [
        '_language',
        '_LOCALIZED_UID',
        '_MP_PARAM',
        '_ORIG_uid',
        '_ORIG_pid',
        '_SHORTCUT_ORIGINAL_PAGE_UID',
        '_PAGES_OVERLAY',
        '_PAGES_OVERLAY_UID',
        '_PAGES_OVERLAY_LANGUAGE',
        '_PAGES_OVERLAY_REQUESTEDLANGUAGE',
        '_TRANSLATION_SOURCE',
    ];

    protected array $specialProperties = [];

    public function __construct(array $properties)
    {
        foreach ($properties as $propertyName => $propertyValue) {
            if (in_array($propertyName, $this->specialPropertyNames)) {
                if ($propertyName === '_TRANSLATION_SOURCE' && !$propertyValue instanceof Page) {
                    $this->specialProperties[$propertyName] = new Page($propertyValue);
                } else {
                    $this->specialProperties[$propertyName] = $propertyValue;
                }
            } else {
                $this->properties[$propertyName] = $propertyValue;
            }
        }
    }

    public function getLanguageId(): int
    {
        return $this->specialProperties['_language'] ?? $this->specialProperties['_PAGES_OVERLAY_LANGUAGE'] ?? $this->properties['sys_language_uid'];
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
        return $this->specialProperties['_PAGES_OVERLAY_REQUESTEDLANGUAGE'] ?? null;
    }

    public function toArray(bool $includeSpecialProperties = false): array
    {
        if ($includeSpecialProperties) {
            return $this->properties + $this->specialProperties;
        }
        return $this->properties;
    }
}
