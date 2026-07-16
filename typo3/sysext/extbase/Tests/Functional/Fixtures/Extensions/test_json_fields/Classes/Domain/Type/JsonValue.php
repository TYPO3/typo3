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

namespace TYPO3Tests\TestJsonFields\Domain\Type;

use TYPO3\CMS\Core\Type\TypeInterface;

/**
 * A custom value type decoding a JSON string from the database into an array and
 * encoding it back on persistence. Extbase creates it with the raw database value
 * and persists its string representation, it is not a relation to another entity.
 */
final class JsonValue implements TypeInterface
{
    private array $data;

    public function __construct(string|array $data = [])
    {
        if (is_string($data)) {
            $data = $data === '' ? [] : json_decode($data, true, 512, JSON_THROW_ON_ERROR);
        }
        $this->data = $data;
    }

    public function toArray(): array
    {
        return $this->data;
    }

    public function __toString(): string
    {
        return json_encode($this->data, JSON_THROW_ON_ERROR);
    }
}
