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

/**
 * A completely empty line, or a line consisting of tabs or whitespaces only.
 *
 * This is not created when the TypoScript source line is within multiline "("
 * assignments and multiline "/*" comments: The T_BLANK and T_NEWLINE tokens
 * are part of the value steram in these contexts.
 *
 * Note the LossyTokenizers does not create these and just skips them since
 * they have no semantic meaning for the resulting TypoScript tree.
 *
 * @internal: Internal tokenizer structure.
 */
final class EmptyLine extends AbstractLine {}
