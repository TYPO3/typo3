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

namespace TYPO3\CMS\Core\TypoScript\Tokenizer;

use TYPO3\CMS\Core\TypoScript\Tokenizer\Line\LineStream;

/**
 * A lossless tokenizer for TypoScript syntax.
 *
 * tokenize() creates a stream of LineInterface objects from a TypoScript string, each line
 * contains the important streams or tokens of a single line.
 *
 * There are two tokenizer implementations:
 * - LossyTokenizer: This one skip all invalid lines and comments and everything that is
 *                   not needed for AST building.
 * - LosslessTokenizer: This one creates a stream of lines useful for backend template module
 *                      to elaborate on details and failures in TypoScript.
 *
 * The tokenizer *does not* parse conditions or includes itself (no file / db lookups),
 * this is part of the IncludeTree parser.
 *
 * @internal: Internal tokenizer structure.
 */
interface TokenizerInterface
{
    public function tokenize(string $source): LineStream;
}
