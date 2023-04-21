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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

use TYPO3\CMS\Core\Cache\Frontend\FrontendInterface;

/**
 * Representation of Content-Security-Policy hash source value
 * see https://www.w3.org/TR/CSP3/#grammardef-hash-source
 */
final class HashValue implements \Stringable, SourceValueInterface
{
    public readonly string $value;

    public static function create(string $value, HashType $type = HashType::sha256): self
    {
        return new self($value, $type);
    }

    /**
     * @param string $value hash value (binary, hex or base64 encoded)
     * @param HashType $type
     */
    public function __construct(string $value, public readonly HashType $type = HashType::sha256)
    {
        $length = strlen($value);
        if ($length === $this->type->length()) {
            $value = base64_encode($value);
        } elseif ($length === $this->type->length() * 2 && ctype_xdigit($value)) {
            $value = base64_encode(hex2bin($value));
        } elseif (strlen(base64_decode($value) ?: '') !== $this->type->length()) {
            throw new \LogicException('Invalid base64 encoded value', 1678620881);
        }
        $this->value = $value;
    }

    public function __toString(): string
    {
        return sprintf("'%s-%s'", $this->type->value, $this->value);
    }

    public static function knows(string $value): bool
    {
        return preg_match(self::createParsingPattern(), $value) === 1;
    }

    public static function parse(string $value): self
    {
        if (preg_match(self::createParsingPattern(), $value, $matches) !== 1) {
            throw new \LogicException(sprintf('Parsing "%s" is not known', $value), 1678621397);
        }
        return new self($matches['value'], HashType::from($matches['type']));
    }

    private static function createParsingPattern(): string
    {
        $types = array_map(static fn (HashType $type): string => $type->value, HashType::cases());
        return sprintf("/^'(?P<type>%s)-(?P<value>.+)'$/", implode('|', $types));
    }

    public function compile(?FrontendInterface $cache = null): ?string
    {
        return (string)$this;
    }

    public function serialize(): ?string
    {
        return (string)$this;
    }
}
