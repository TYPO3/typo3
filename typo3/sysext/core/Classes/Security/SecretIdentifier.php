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

namespace TYPO3\CMS\Core\Security;

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Model used to identify a secret, without actually containing the secret value.
 *
 * @internal
 */
class SecretIdentifier implements \JsonSerializable
{
    public static function fromJson(string $json): self
    {
        return self::fromArray(
            (array)json_decode($json, true, 8, JSON_THROW_ON_ERROR)
        );
    }

    public static function fromArray(array $payload): self
    {
        $type = $payload['type'] ?? null;
        $name = $payload['name'] ?? null;
        if (!is_string($type) || !is_string($name)) {
            throw new \LogicException('Properties "type" and "name" must be of type string', 1664215980);
        }
        return GeneralUtility::makeInstance(self::class, $type, $name);
    }

    public function __construct(public readonly string $type, public readonly string $name)
    {
    }

    /**
     * @return array{type: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return [
            'type' => $this->type,
            'name' => $this->name,
        ];
    }
}
