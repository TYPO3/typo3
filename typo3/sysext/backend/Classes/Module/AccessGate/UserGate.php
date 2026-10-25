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

namespace TYPO3\CMS\Backend\Module\AccessGate;

use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Backend\Module\ModuleRegistry;
use TYPO3\CMS\Core\Attribute\AsModuleAccessGate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Grants access based on explicit module permissions in
 * be_users.userMods / be_groups.groupMods. Admin users
 * are always granted access.
 *
 * @deprecated since v15, will be removed in v16. Decide module access with a security voter, see #[AsSecurityVoter].
 *             TYPO3 Core decides the access value "user" with the ModuleVoter.
 */
#[AsModuleAccessGate(identifier: 'user')]
final readonly class UserGate implements ModuleAccessGateInterface
{
    public function __construct(
        private ModuleRegistry $moduleRegistry,
    ) {}

    public function decide(ModuleInterface $module, BackendUserAuthentication $user): ModuleAccessResult
    {
        if ($module->getAccess() !== 'user') {
            return ModuleAccessResult::Abstain;
        }
        if ($user->isAdmin()) {
            return ModuleAccessResult::Granted;
        }
        if ($user->check('modules', $module->getIdentifier())) {
            return ModuleAccessResult::Granted;
        }
        $alias = array_search($module->getIdentifier(), $this->moduleRegistry->getModuleAliases(), true);
        if ($alias !== false && $user->check('modules', $alias)) {
            return ModuleAccessResult::Granted;
        }
        return ModuleAccessResult::Denied;
    }
}
