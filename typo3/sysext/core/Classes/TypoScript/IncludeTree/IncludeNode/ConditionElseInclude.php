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

namespace TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode;

/**
 * A node representing the [ELSE] body of a condition:
 *
 * [foo = bar]
 *     ...
 * [ELSE]
 *     baz = bazValue
 *
 * The LineStream is the body of the else block, the condition token
 * is set to the token of the condition "[foo = bar]".
 *
 * @internal: Internal tree structure.
 */
final class ConditionElseInclude extends AbstractConditionInclude
{
    public function isConditionNegated(): bool
    {
        return true;
    }
}
