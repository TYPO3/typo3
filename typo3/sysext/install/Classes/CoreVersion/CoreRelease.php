<?php

declare(strict_types = 1);

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

    protected $version;
    protected $date;
    protected $type;
    protected $checksum;
    protected $isElts;

    public function __construct(string $version, \DateTimeInterface $date, string $type, string $checksum, bool $isElts = false)
    {
        $this->version = $version;
        $this->date = $date;
        $this->type = $type;
        $this->checksum = $checksum;
        $this->isElts = $isElts;
    }

    public static function fromApiResponse(array $response): self
    {
        return new self($response['version'], new \DateTimeImmutable($response['date']), $response['type'], $response['tar_package']['sha1sum'], $response['elts'] ?? false);
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
