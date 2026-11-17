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

namespace TYPO3\CMS\Core\Security;

/**
 * The result of verifying a submitted password against a stored hash.
 *
 * @internal not part of TYPO3 Core API yet, still experimental as part of the
 *           authentication pipeline groundwork
 */
enum PasswordVerification
{
    case Verified;
    case VerifiedWithOutdatedHash;
    case NoSuitableHashMethod;
    case Invalid;
}
