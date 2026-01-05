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

namespace TYPO3\CMS\Backend\Domain\Model;

/**
 * Value object representing an open document in the backend.
 *
 * An open document represents a record being edited in the FormEngine,
 * displayed in the "open documents" toolbar.
 *
 * @internal
 */
readonly class OpenDocument implements \JsonSerializable
{
    public function __construct(
        public string $table,
        public string $uid,
        public string $title,
        // Contains an array with key/value pairs of GET parameters needed to reach the
        // current document displayed - used in the 'open documents' toolbar.
        public array $parameters,
        public int $pid,
        public string $returnUrl = '',
    ) {}

    /**
     * Get the identifier for this document (table:uid).
     */
    public function getIdentifier(): string
    {
        return $this->table . ':' . $this->uid;
    }

    /**
     * Create from the legacy array format stored in session.
     *
     * Legacy format: [0 => title, 1 => params, 2 => queryString, 3 => metadata, 4 => returnUrl]
     */
    public static function fromLegacyArray(array $data): self
    {
        $metadata = $data[3] ?? [];
        return new self(
            table: $metadata['table'] ?? '',
            uid: (string)($metadata['uid'] ?? '0'),
            title: $data[0] ?? '',
            parameters: $data[1] ?? [],
            pid: (int)($metadata['pid'] ?? 0),
            returnUrl: $data[4] ?? '',
        );
    }

    public static function fromArray(array $data): self
    {
        return new self(
            table: $data['table'] ?? '',
            uid: (string)($data['uid'] ?? '0'),
            title: $data['title'] ?? '',
            parameters: $data['parameters'] ?? [],
            pid: (int)($data['pid'] ?? 0),
            returnUrl: $data['returnUrl'] ?? '',
        );
    }

    public function toArray(): array
    {
        return [
            'table' => $this->table,
            'uid' => $this->uid,
            'title' => $this->title,
            'parameters' => $this->parameters,
            'pid' => $this->pid,
            'returnUrl' => $this->returnUrl,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
