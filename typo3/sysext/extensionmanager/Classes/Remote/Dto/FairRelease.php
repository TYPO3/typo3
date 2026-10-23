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
 * One release of a FAIR metadata document: the version, when it was
 * published, what it requires and suggests, and the "package" artifact
 * with its checksum.
 *
 * @see https://github.com/fairpm/fair-protocol/blob/main/specification.md#release-document
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class FairRelease
{
    private const SHA256_PREFIX = 'sha256:';

    /**
     * @param array<string, string> $requires requirement key ("env:typo3", "did:web:…") to version constraint
     * @param array<string, string> $suggests same shape as $requires
     */
    public function __construct(
        public string $version,
        public int $date = 0,
        public array $requires = [],
        public array $suggests = [],
        public string $artifactUrl = '',
        public string $artifactChecksum = '',
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!is_array($data) || !is_string($data['version'] ?? null) || $data['version'] === '') {
            return null;
        }
        // The "package" artifact may be given as one object or as a list of them.
        $package = $data['artifacts']['package'] ?? null;
        if (is_array($package) && isset($package[0]) && is_array($package[0])) {
            $package = $package[0];
        }
        $date = 0;
        if (is_string($data['date'] ?? null) && $data['date'] !== '') {
            $date = (int)(strtotime($data['date']) ?: 0);
        }
        return new self(
            $data['version'],
            $date,
            self::normalizeConstraints($data['requires'] ?? null),
            self::normalizeConstraints($data['suggests'] ?? null),
            is_array($package) ? (string)($package['url'] ?? '') : '',
            is_array($package) ? (string)($package['checksum'] ?? '') : '',
        );
    }

    /**
     * The bare hash when the checksum is a sha256 one, which is what
     * downloading verifies against; an empty string for any other algorithm.
     */
    public function getArtifactSha256(): string
    {
        if (!str_starts_with($this->artifactChecksum, self::SHA256_PREFIX)) {
            return '';
        }
        return substr($this->artifactChecksum, strlen(self::SHA256_PREFIX));
    }

    /**
     * @return array<string, string>
     */
    private static function normalizeConstraints(mixed $constraints): array
    {
        if (!is_array($constraints)) {
            return [];
        }
        $normalized = [];
        foreach ($constraints as $key => $constraint) {
            $normalized[(string)$key] = is_scalar($constraint) ? (string)$constraint : '';
        }
        return $normalized;
    }
}
