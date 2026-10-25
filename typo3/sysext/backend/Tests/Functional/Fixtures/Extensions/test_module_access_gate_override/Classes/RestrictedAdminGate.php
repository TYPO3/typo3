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

namespace TYPO3Tests\TestModuleAccessGateOverride;

use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsModuleAccessGate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Replaces the module access gate of TYPO3 Core for the access value
 * "admin": only the administrator with uid 1 is granted.
 */
#[AsModuleAccessGate(identifier: 'admin')]
final readonly class RestrictedAdminGate implements ModuleAccessGateInterface
{
    public function decide(ModuleInterface $module, BackendUserAuthentication $user): ModuleAccessResult
    {
        if ($module->getAccess() !== 'admin') {
            return ModuleAccessResult::Abstain;
        }
        return $user->isAdmin() && $user->getUserId() === 1 ? ModuleAccessResult::Granted : ModuleAccessResult::Denied;
    }
}
