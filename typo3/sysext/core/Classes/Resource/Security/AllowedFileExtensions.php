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

namespace TYPO3\CMS\Core\Resource\Security;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The file extensions a file storage or file mount restricts added files to.
 * An empty list given by configuration means no restriction.
 *
 * @internal
 */
final readonly class AllowedFileExtensions
{
    /**
     * @param list<non-empty-string>|null $fileExtensions null if unrestricted
     */
    private function __construct(
        private ?array $fileExtensions,
    ) {}

    /**
     * @param string|list<string> $fileExtensions A list or a comma-separated string
     */
    public static function fromList(string|array $fileExtensions): self
    {
        if (is_string($fileExtensions)) {
            $fileExtensions = GeneralUtility::trimExplode(',', $fileExtensions, true);
        }
        $fileExtensions = array_values(array_unique(array_filter(array_map(
            static fn(string $fileExtension): string => strtolower(trim($fileExtension)),
            $fileExtensions
        ))));
        return new self($fileExtensions === [] ? null : $fileExtensions);
    }

    public static function unrestricted(): self
    {
        return new self(null);
    }

    public function isRestricted(): bool
    {
        return $this->fileExtensions !== null;
    }

    public function allowsFileName(string $fileName): bool
    {
        return $this->allowsFileExtension(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    public function allowsFileExtension(string $fileExtension): bool
    {
        return $this->fileExtensions === null
            || in_array(strtolower($fileExtension), $this->fileExtensions, true);
    }

    /**
     * Permissions of file mounts add up: the result allows what any of both
     * allows, which means no restriction if one of them is unrestricted.
     */
    public function merge(self $other): self
    {
        if ($this->fileExtensions === null || $other->fileExtensions === null) {
            return self::unrestricted();
        }
        return new self(array_values(array_unique([...$this->fileExtensions, ...$other->fileExtensions])));
    }

    /**
     * Restrictions of different levels, such as a storage and its file mounts,
     * narrow each other: the result allows what both allow, which may be nothing.
     */
    public function intersect(self $other): self
    {
        if ($this->fileExtensions === null) {
            return $other;
        }
        if ($other->fileExtensions === null) {
            return $this;
        }
        return new self(array_values(array_intersect($this->fileExtensions, $other->fileExtensions)));
    }

    /**
     * @return list<non-empty-string> empty if unrestricted, or if nothing is allowed
     */
    public function toList(): array
    {
        return $this->fileExtensions ?? [];
    }

    public function toString(): string
    {
        return implode(',', $this->toList());
    }
}
