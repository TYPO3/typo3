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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer\Line;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Token\TokenStreamInterface;

/**
 * The TypoScript tokenizers deliver streams of lines. This is the main line interface.
 *
 * Each line is represented by a specific line type. For instance, "foo.bar {" creates
 * a IdentifierBlockOpenLine and has the additional method getIdentifierTokenStream()
 * to retrieve the "foo" and "bar" identifier tokens.
 *
 * @internal: Internal tokenizer structure.
 */
interface LineInterface
{
    /**
     * Set and get the token stream that represents the full line. This is mostly used
     * in backend to for instance create a TypoScript string back from tokenized lines.
     *
     * Note: Only the LosslessTokenizer fills this 'full line' stream, LossyTokenizer
     * does not for performance reasons.
     */
    public function setTokenStream(TokenStreamInterface $tokenStream): static;
    public function getTokenStream(): TokenStreamInterface;
}
