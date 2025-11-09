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

namespace TYPO3\CMS\Backend\Domain\Model\Language;

use TYPO3\CMS\Core\Site\Entity\SiteLanguage;

/**
 * Represents a single language with its status and UI properties.
 * Contains all information needed to render language menu items.
 *
 * @internal
 */
final readonly class LanguageItem
{
    public function __construct(
        public SiteLanguage $siteLanguage,
        public LanguageStatus $status,
    ) {}

    public function isExisting(): bool
    {
        return $this->status === LanguageStatus::Existing;
    }

    public function isCreatable(): bool
    {
        return $this->status === LanguageStatus::Creatable;
    }

    public function isAvailable(): bool
    {
        return $this->status !== LanguageStatus::Unavailable;
    }

    public function getTitle(): string
    {
        return $this->siteLanguage->getTitle();
    }

    public function getFlagIdentifier(): string
    {
        return $this->siteLanguage->getFlagIdentifier();
    }

    public function getLanguageId(): int
    {
        return $this->siteLanguage->getLanguageId();
    }
}
