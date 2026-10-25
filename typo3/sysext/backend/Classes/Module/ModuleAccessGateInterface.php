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

use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Interface for module access gates.
 *
 * Gates are responsible for deciding whether a backend user has access to a module.
 * Each gate is registered with a unique identifier that corresponds to the `access`
 * value in a module's configuration.
 *
 * Module access is decided by the authorization checker, see ModuleVoter. A
 * security voter can decide on an access value of its own, and can restrict access
 * to any module, also to modules of TYPO3 Core and other extensions. Gates are
 * still asked through the ModuleAccessGateVoter until TYPO3 v16.
 *
 * @deprecated since v15, will be removed in v16. Decide module access with a security voter, see #[AsSecurityVoter].
 */
interface ModuleAccessGateInterface
{
    public function decide(ModuleInterface $module, BackendUserAuthentication $user): ModuleAccessResult;
}
