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
 * Value object mirroring the "author" schema of the TER REST API.
 *
 * @internal This class is not part of the Public TYPO3 API.
 */
final readonly class TerAuthor
{
    public function __construct(
        public string $name = '',
        public string $email = '',
    ) {}

    public static function fromArray(?array $data): ?self
    {
        if (!is_array($data) || $data === []) {
            return null;
        }
        return new self(
            name: (string)($data['name'] ?? ''),
            email: (string)($data['email'] ?? ''),
        );
    }
}
