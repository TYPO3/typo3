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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy\Reporting;

use Symfony\Component\Uid\UuidV4;
use TYPO3\CMS\Core\Security\ContentSecurityPolicy\Scope;

/**
 * @internal
 */
class Report implements \JsonSerializable
{
    public readonly UuidV4 $uuid;
    public readonly \DateTimeImmutable $created;
    public readonly \DateTimeImmutable $changed;

    public static function fromArray(array $array): static
    {
        $meta = json_decode($array['meta'] ?? '', true, 16, JSON_THROW_ON_ERROR);
        $details = json_decode($array['details'] ?? '', true, 16, JSON_THROW_ON_ERROR);
        return new static(
            Scope::from($array['scope'] ?? ''),
            ReportStatus::from($array['status'] ?? 0),
            $array['request_time'] ?? 0,
            $meta ?: [],
            new ReportDetails($details ?: []),
            $array['summary'] ?? '',
            UuidV4::fromString($array['uuid'] ?? ''),
            new \DateTimeImmutable('@' . ($array['created'] ?? '0')),
            new \DateTimeImmutable('@' . ($array['changed'] ?? '0'))
        );
    }

    final public function __construct(
        public readonly Scope $scope,
        public readonly ReportStatus $status,
        public readonly int $requestTime,
        public readonly array $meta,
        public readonly ReportDetails $details,
        public readonly string $summary = '',
        UuidV4 $uuid = null,
        \DateTimeImmutable $created = null,
        \DateTimeImmutable $changed = null,
    ) {
        $this->uuid = $uuid ?? new UuidV4();
        $this->created = $created ?? new \DateTimeImmutable();
        $this->changed = $changed ?? $this->created;
    }

    public function jsonSerialize(): array
    {
        return [
            'uuid' => $this->uuid,
            'status' => $this->status->value,
            'created' => $this->created->format(\DateTimeInterface::ATOM),
            'changed' => $this->changed->format(\DateTimeInterface::ATOM),
            'scope' => $this->scope,
            'request_time' => $this->requestTime,
            'meta' => $this->meta,
            'details' => $this->details,
            'summary' => $this->summary,
        ];
    }

    public function toArray(): array
    {
        return [
            'uuid' => (string)$this->uuid,
            'status' => $this->status->value,
            'created' => $this->created->getTimestamp(),
            'changed' => $this->changed->getTimestamp(),
            'scope' => (string)$this->scope,
            'request_time' => $this->requestTime,
            'meta' => json_encode($this->meta),
            'details' => json_encode($this->details->getArrayCopy()),
            'summary' => $this->summary,
        ];
    }
}
