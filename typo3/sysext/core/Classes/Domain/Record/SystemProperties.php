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

namespace TYPO3\CMS\Core\Domain\Record;

/**
 * Contains all information about system-related properties for the current record.
 *
 * @internal not part of public API, as this needs to be streamlined and proven
 */
readonly class SystemProperties
{
    public function __construct(
        protected ?LanguageInfo $languageInfo,
        protected ?VersionInfo $versionInfo,
        protected ?bool $isDeleted,
        protected ?bool $isDisabled,
        protected ?bool $isLockedForEditing,
        protected ?\DateTimeInterface $createdAt,
        protected ?\DateTimeInterface $lastUpdatedAt,
        protected ?\DateTimeInterface $publishAt,
        protected ?\DateTimeInterface $publishUntil,
        protected ?array $userGroupRestriction,
        protected ?int $sorting,
        protected ?string $description,
    ) {}

    public function getLanguage(): ?LanguageInfo
    {
        return $this->languageInfo;
    }

    public function getVersion(): ?VersionInfo
    {
        return $this->versionInfo;
    }

    public function isDeleted(): ?bool
    {
        return $this->isDeleted;
    }

    public function isDisabled(): ?bool
    {
        return $this->isDisabled;
    }

    public function isLockedForEditing(): ?bool
    {
        return $this->isLockedForEditing;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function getLastUpdatedAt(): ?\DateTimeInterface
    {
        return $this->lastUpdatedAt;
    }

    public function getPublishAt(): ?\DateTimeInterface
    {
        return $this->publishAt;
    }

    public function getPublishUntil(): ?\DateTimeInterface
    {
        return $this->publishUntil;
    }

    public function getUserGroupRestriction(): ?array
    {
        return $this->userGroupRestriction;
    }

    public function getSorting(): ?int
    {
        return $this->sorting;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function toArray(): array
    {
        return [
            'language' => $this->languageInfo,
            'version' => $this->versionInfo,
            'isDeleted' => $this->isDeleted,
            'isDisabled' => $this->isDisabled,
            'isLockedForEditing' => $this->isLockedForEditing,
            'createdAt' => $this->createdAt,
            'lastUpdatedAt' => $this->lastUpdatedAt,
            'publishAt' => $this->publishAt,
            'publishUntil' => $this->publishUntil,
            'userGroupRestriction' => $this->userGroupRestriction,
            'sorting' => $this->sorting,
            'description' => $this->description,
        ];
    }
}
