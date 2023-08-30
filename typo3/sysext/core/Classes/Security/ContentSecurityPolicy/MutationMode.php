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

namespace TYPO3\CMS\Core\Security\ContentSecurityPolicy;

/**
 * The mode used in mutations (sic!).
 */
enum MutationMode: string
{
    /**
     * sets (overrides) a directive completely
     */
    case Set = 'set';

    /**
     * just appends to a given directive
     */
    case Append = 'append';

    /**
     * inherits once from the corresponding ancestor chain
     */
    case InheritOnce = 'inherit-once';

    /**
     * inherits again from the corresponding ancestor chain and merges existing sources
     */
    case InheritAgain = 'inherit-again';

    /**
     * shortcut for `InheritOnce` and `Append`
     */
    case Extend = 'extend';

    /**
     * reduces a directive by a given aspect
     */
    case Reduce = 'reduce';

    /**
     * removes a directive completely
     */
    case Remove = 'remove';
}
