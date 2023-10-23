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
 * A casual token created from TypoScript source:
 * When having a TypoScript line like "# a comment", then a LineComment
 * is created having a token "T_COMMENT_ONELINE_HASH" and value "# a comment" as
 * assigned TokenStream.
 * See TokenType for on overview on which TokenTypes can exist.
 *
 * @internal: Internal tokenizer structure.
 */
final class Token extends AbstractToken {}
