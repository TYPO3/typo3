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

namespace TYPO3\CMS\Core\Security\Authorization\Policy;

/**
 * The scopes TYPO3 Core decides on, the attributes of isGranted() for
 * them. A decision service may support further scopes, a scope it does
 * not know is denied.
 */
final class Scope
{
    /**
     * Accessing a resource, e.g. a backend module
     */
    public const string ACCESS = 'access';
}
