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
 * Value object mirroring the "version" schema of the TER REST API.
 *
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class TerExtensionVersion
{
    /**
     * @param array<string, string> $dependencies map of extension key => version constraint
     * @param array<string, string> $conflicts map of extension key => version constraint
     */
    public function __construct(
        public string $number,
        public string $title = '',
        public string $description = '',
        public string $uploadComment = '',
        public string $state = '',
        public string $category = '',
        public array $dependencies = [],
        public array $conflicts = [],
        public int $uploadDate = 0,
        public int $reviewState = 0,
        public ?TerAuthor $author = null,
        public string $artifactSha256 = '',
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!is_array($data) || !isset($data['number'])) {
            return null;
        }
        return new self(
            number: (string)$data['number'],
            title: (string)($data['title'] ?? ''),
            description: (string)($data['description'] ?? ''),
            uploadComment: (string)($data['upload_comment'] ?? ''),
            state: (string)($data['state'] ?? ''),
            category: (string)($data['category'] ?? ''),
            dependencies: self::normalizeConstraints($data['dependencies'] ?? null),
            conflicts: self::normalizeConstraints($data['conflicts'] ?? null),
            uploadDate: (int)($data['upload_date'] ?? 0),
            reviewState: (int)($data['review_state'] ?? 0),
            author: TerAuthor::fromArray(is_array($data['author'] ?? null) ? $data['author'] : null),
            artifactSha256: (string)($data['artifact_sha256'] ?? ''),
        );
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
            $normalized[(string)$key] = (string)$constraint;
        }
        return $normalized;
    }
}
