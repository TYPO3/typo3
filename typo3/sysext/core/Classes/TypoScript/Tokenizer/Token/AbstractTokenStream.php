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
 * A generic implementation of TokenStreamInterface.
 *
 * @internal: Internal tokenizer structure.
 */
abstract class AbstractTokenStream implements TokenStreamInterface
{
    /**
     * @var TokenInterface[]
     */
    protected array $tokens = [];
    protected int $currentIndex = -1;

    /**
     * Create a source string from given tokens.
     */
    public function __toString(): string
    {
        $source = '';
        $this->reset();
        while ($token = $this->getNext()) {
            $source .= $token;
        }
        return $source;
    }

    /**
     * When storing to cache, we only store FE relevant properties and skip
     * irrelevant things. For instance $currentIndex should always initialize
     * to -1 and does not need to be stored.
     */
    final public function __serialize(): array
    {
        return $this->serialize();
    }

    protected function serialize(): array
    {
        $result['tokens'] = $this->tokens;
        return $result;
    }

    /**
     * Stream creation.
     */
    public function append(TokenInterface $token): self
    {
        $this->tokens[] = $token;
        return $this;
    }

    /**
     * We sometimes create a stream but don't add tokens.
     * This method returns true if tokens have been added.
     */
    public function isEmpty(): bool
    {
        return empty($this->tokens);
    }

    /**
     * Reset current pointer. Typically, call this before iterating with getNext().
     */
    public function reset(): static
    {
        $this->currentIndex = -1;
        return $this;
    }

    /**
     * Get next token and raise pointer.
     */
    public function getNext(): ?TokenInterface
    {
        $this->currentIndex++;
        return $this->tokens[$this->currentIndex] ?? null;
    }

    public function peekNext(): ?TokenInterface
    {
        return $this->tokens[$this->currentIndex + 1] ?? null;
    }

    public function getAll(): array
    {
        return $this->tokens;
    }

    public function setAll(array $tokens): self
    {
        $this->tokens = $tokens;
        return $this;
    }
}
