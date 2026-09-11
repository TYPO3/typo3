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

namespace TYPO3\CMS\Extensionmanager\Remote\Dto;

/**
 * Value object mirroring the top-level "extension" schema of the TER REST API.
 *
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class TerExtension
{
    public function __construct(
        public string $key,
        public string $owner = '',
        public int $downloads = 0,
        public string $composerName = '',
        public string $documentationLink = '',
        public ?TerExtensionVersion $currentVersion = null,
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!is_array($data) || !isset($data['key'])) {
            return null;
        }
        $meta = is_array($data['meta'] ?? null) ? $data['meta'] : [];
        return new self(
            key: (string)$data['key'],
            owner: (string)($data['owner'] ?? ''),
            downloads: (int)($data['downloads'] ?? 0),
            composerName: (string)($meta['composer_name'] ?? ''),
            documentationLink: (string)($meta['external_manual'] ?? ''),
            currentVersion: TerExtensionVersion::fromArray(is_array($data['current_version'] ?? null) ? $data['current_version'] : null),
        );
    }
}
