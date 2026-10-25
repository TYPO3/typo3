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

namespace TYPO3\CMS\Backend\Module;

/**
 * Result of a module access gate decision.
 *
 * @deprecated since v15, will be removed in v16. Decide module access with a security voter, see #[AsSecurityVoter].
 */
enum ModuleAccessResult
{
    /**
     * Access is explicitly granted.
     */
    case Granted;

    /**
     * Access is explicitly denied.
     */
    case Denied;

    /**
     * The gate cannot decide - pass to next gate.
     */
    case Abstain;
}
