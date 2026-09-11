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
 * Result of a paginated extension listing / search against the TER REST API.
 *
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class TerExtensionListResult
{
    /**
     * @param TerExtension[] $extensions the extensions on the requested page
     */
    public function __construct(
        public int $totalResults,
        public array $extensions = [],
    ) {}

    public static function fromArray(array $data): self
    {
        $extensions = [];
        foreach ((is_array($data['extensions'] ?? null) ? $data['extensions'] : []) as $extensionData) {
            $extension = TerExtension::fromArray(is_array($extensionData) ? $extensionData : null);
            if ($extension !== null) {
                $extensions[] = $extension;
            }
        }
        return new self(
            totalResults: (int)($data['results'] ?? count($extensions)),
            extensions: $extensions,
        );
    }
}
