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

namespace TYPO3\CMS\Core\Authentication\Mfa;

use TYPO3\CMS\Core\Type\Enumeration;

/**
 * Enumeration of possible view types for MFA providers
 *
 * @internal This is an experimental TYPO3 Core API and subject to change until v11 LTS
 */
class MfaViewType extends Enumeration
{
    public const SETUP = 'setup';
    public const EDIT ='edit';
    public const AUTH ='auth';
}
