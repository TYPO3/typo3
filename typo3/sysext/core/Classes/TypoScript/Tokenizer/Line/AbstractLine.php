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
 * Implement main LineInterface methods.
 *
 * @internal: Internal tokenizer structure.
 */
abstract class AbstractLine implements LineInterface
{
    protected TokenStreamInterface $tokenStream;

    public function setTokenStream(TokenStreamInterface $tokenStream): static
    {
        $this->tokenStream = $tokenStream;
        return $this;
    }

    public function getTokenStream(): TokenStreamInterface
    {
        return $this->tokenStream;
    }
}
