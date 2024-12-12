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

namespace TYPO3\CMS\Install\CoreVersion;

class CoreRelease
{
    protected const RELEASE_TYPE_REGULAR = 'regular';
    protected const RELEASE_TYPE_SECURITY = 'security';

    public function __construct(
        protected readonly string $version,
        protected readonly \DateTimeInterface $date,
        protected readonly string $type,
        protected readonly string $checksum,
        protected readonly bool $isElts = false
    ) {}

    public static function fromApiResponse(array $response): self
    {
        return new self(
            (string)($response['version'] ?? ''),
            new \DateTimeImmutable((string)($response['date'] ?? '')),
            (string)($response['type'] ?? ''),
            (string)($response['tar_package']['sha1sum'] ?? ''),
            $response['elts'] ?? false
        );
    }

    public function getVersion(): string
    {
        return $this->version;
    }

    public function getDate(): \DateTimeInterface
    {
        return $this->date;
    }

    public function isSecurityUpdate(): bool
    {
        return $this->type === self::RELEASE_TYPE_SECURITY;
    }

    public function getChecksum(): string
    {
        return $this->checksum;
    }

    public function isElts(): bool
    {
        return $this->isElts;
    }
}
