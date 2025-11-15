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

namespace TYPO3\CMS\Form\Domain\ValueObject;

/**
 * Form persistence identifier value object
 *
 * Represents a unique identifier for a form definition in storage.
 * Examples:
 * - Extension path: "EXT:my_extension/Configuration/Forms/contact.form.yaml"
 * - File mount: "1:/forms/contact.form.yaml"
 *
 * @internal
 */
final readonly class FormIdentifier
{
    public function __construct(
        public string $identifier,
    ) {
        if (empty($identifier)) {
            throw new \InvalidArgumentException('Identifier cannot be empty', 1764502814);
        }
    }

    public static function fromString(string $string): self
    {
        return new self($string);
    }

    public function toString(): string
    {
        return $this->identifier;
    }

    public function __toString(): string
    {
        return $this->toString();
    }
}
