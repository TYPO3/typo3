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
 * A FAIR metadata document: one package with all of its releases, as TER
 * serves it at /fair/v1/packages/{extensionKey}.
 *
 * @see https://github.com/fairpm/fair-protocol/blob/main/specification.md#metadata-document
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class FairPackage
{
    /**
     * @param FairRelease[] $releases newest first
     */
    public function __construct(
        public string $id,
        public string $extensionKey,
        public string $composerName = '',
        public string $name = '',
        public string $description = '',
        public string $documentation = '',
        public string $authorName = '',
        public string $authorEmail = '',
        public array $releases = [],
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!is_array($data) || !is_string($data['id'] ?? null) || $data['id'] === '') {
            return null;
        }
        // TER names the extension key explicitly; the slug and the last DID
        // segment carry it as well.
        $extensionKey = (string)($data['extension_key'] ?? $data['slug'] ?? substr((string)strrchr($data['id'], ':'), 1));
        if ($extensionKey === '') {
            return null;
        }
        $releases = [];
        foreach ((is_array($data['releases'] ?? null) ? $data['releases'] : []) as $releaseData) {
            $release = FairRelease::fromArray(is_array($releaseData) ? $releaseData : null);
            if ($release !== null) {
                $releases[] = $release;
            }
        }
        usort($releases, static fn(FairRelease $a, FairRelease $b): int => version_compare($b->version, $a->version));
        $author = is_array($data['authors'] ?? null) ? ($data['authors'][0] ?? null) : null;
        return new self(
            $data['id'],
            $extensionKey,
            (string)($data['package_name'] ?? ''),
            (string)($data['name'] ?? ''),
            (string)($data['description'] ?? ''),
            (string)($data['documentation'] ?? ''),
            is_array($author) ? (string)($author['name'] ?? '') : '',
            is_array($author) ? (string)($author['email'] ?? '') : '',
            $releases,
        );
    }

    public function getRelease(string $version): ?FairRelease
    {
        foreach ($this->releases as $release) {
            if ($release->version === $version) {
                return $release;
            }
        }
        return null;
    }

    public function getNewestRelease(): ?FairRelease
    {
        return $this->releases[0] ?? null;
    }
}
