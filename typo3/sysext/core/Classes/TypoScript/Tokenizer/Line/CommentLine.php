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
 * A commented TypoScript line: Lines that start with "#", "//" and multiline comments "/* ... *\/"
 *
 * Note multiline comments often represent multiple source lines: An opening "/*" as
 * first source line, then the comment body with one or more source lines, then finally
 * the closing "*\/". These still create only one "CommentLine".
 *
 * @internal: Internal tokenizer structure.
 */
final class CommentLine extends AbstractLine
{
}
