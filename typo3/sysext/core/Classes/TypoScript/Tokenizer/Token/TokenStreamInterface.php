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
 * A generic stream of tokens used in single LineInterface lines.
 *
 * The tokenizers create these streams for various lists of tokens, the generic
 * implementation is class TokenStream. For lists of identifier tokens the special
 * class TokenStreamIdentifier is created.
 *
 * @internal: Internal tokenizer structure.
 */
interface TokenStreamInterface
{
    /**
     * Create a source string from given tokens.
     */
    public function __toString(): string;

    /**
     * Stream creation.
     */
    public function append(TokenInterface $token): self;

    /**
     * We sometimes create a stream but don't add tokens.
     * This method returns true if tokens have been added.
     */
    public function isEmpty(): bool;

    /**
     * Reset current pointer. Typically, call this before iterating with getNext().
     */
    public function reset(): self;

    /**
     * Get next token and raise pointer.
     */
    public function getNext(): ?TokenInterface;

    /**
     * Get next token but do not raise pointer.
     */
    public function peekNext(): ?TokenInterface;

    /**
     * Only used internally when one Stream is transferred to another,
     * in particular when a TokenStream is turned into TokenStreamConstantAware.
     *
     * @return TokenInterface[]
     */
    public function getAll(): array;

    /**
     * Only used internally when one Stream is transferred to another,
     * in particular when a TokenStream is turned into TokenStreamConstantAware.
     *
     * @param TokenInterface[] $tokens
     */
    public function setAll(array $tokens): self;
}
