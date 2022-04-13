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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer\Token;

/**
 * Main implementation of a TokenInterface.
 *
 * @internal: Internal tokenizer structure.
 */
abstract class AbstractToken implements TokenInterface
{
    protected int $line = 0;
    protected int $column = 0;

    public function __construct(
        private readonly TokenType $type,
        protected readonly string $value,
        int $line = 0,
        int $column = 0
    ) {
        // No constructor property promotion for $line and $column: We don't serialize
        // these two and want to still default them to 0 (zero) when unserialized.
        $this->line = $line;
        $this->column = $column;
    }

    public function __toString(): string
    {
        return $this->value;
    }

    /**
     * Do not store line and column when structure is serialized to cache.
     * Not storing $line and $column reduces the cache size by about 1/3 since
     * we're typically storing *a lot* of tokens.
     */
    public function __serialize(): array
    {
        return [
            'type' => $this->type,
            'value' => $this->value,
        ];
    }

    public function getType(): TokenType
    {
        return $this->type;
    }

    public function getValue(): string
    {
        return $this->value;
    }

    public function getLine(): int
    {
        return $this->line;
    }

    public function getColumn(): int
    {
        return $this->column;
    }
}
