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
use TYPO3\CMS\Core\Attribute\AsModuleAccessGate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Grants access only to system maintainers (admins listed in
 * $GLOBALS['TYPO3_CONF_VARS']['SYS']['systemMaintainers']).
 *
 * @deprecated since v15, will be removed in v16. Decide module access with a security voter, see #[AsSecurityVoter].
 *             TYPO3 Core decides the access value "systemMaintainer" with the ModuleVoter.
 */
#[AsModuleAccessGate(identifier: 'systemMaintainer')]
final readonly class SystemMaintainerGate implements ModuleAccessGateInterface
{
    public function decide(ModuleInterface $module, BackendUserAuthentication $user): ModuleAccessResult
    {
        if ($module->getAccess() !== BackendUserAuthentication::ROLE_SYSTEMMAINTAINER) {
            return ModuleAccessResult::Abstain;
        }
        return $user->isSystemMaintainer() ? ModuleAccessResult::Granted : ModuleAccessResult::Denied;
    }
}
