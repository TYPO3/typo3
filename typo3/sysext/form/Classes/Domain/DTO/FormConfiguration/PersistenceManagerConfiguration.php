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

namespace TYPO3\CMS\Form\Domain\DTO\FormConfiguration;

/**
 * Typed representation of the "persistenceManager" section of the form
 * YAML configuration.
 *
 * @internal
 */
final readonly class PersistenceManagerConfiguration
{
    use ConfigurationValueNormalizationTrait;

    /**
     * @var list<string>
     */
    public const DEFAULT_SORT_BY_KEYS = ['name', 'fileUid'];

    /**
     * @param list<string> $sortByKeys Keys the forms are sorted by in the form manager and plugin select
     * @param list<string> $allowedExtensionPaths EXT: paths that contain forms shipped within extensions
     * @param list<string> $allowedFileMounts File mounts forms may be stored in
     */
    public function __construct(
        public bool $allowSaveToExtensionPaths = false,
        public bool $allowDeleteFromExtensionPaths = false,
        public array $sortByKeys = self::DEFAULT_SORT_BY_KEYS,
        public bool $sortAscending = true,
        public array $allowedExtensionPaths = [],
        public array $allowedFileMounts = [],
    ) {}

    /**
     * Create the DTO from the raw "persistenceManager" configuration array.
     *
     * @param array<string, mixed> $configuration
     */
    public static function fromArray(array $configuration): self
    {
        return new self(
            allowSaveToExtensionPaths: (bool)($configuration['allowSaveToExtensionPaths'] ?? false),
            allowDeleteFromExtensionPaths: (bool)($configuration['allowDeleteFromExtensionPaths'] ?? false),
            sortByKeys: self::normalizeStringList($configuration['sortByKeys'] ?? null, self::DEFAULT_SORT_BY_KEYS),
            sortAscending: (bool)($configuration['sortAscending'] ?? true),
            allowedExtensionPaths: self::normalizeStringList($configuration['allowedExtensionPaths'] ?? null, []),
            allowedFileMounts: self::normalizeStringList($configuration['allowedFileMounts'] ?? null, []),
        );
    }
}
