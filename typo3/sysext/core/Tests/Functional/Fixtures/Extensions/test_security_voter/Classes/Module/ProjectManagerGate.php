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

namespace TYPO3Tests\TestSecurityVoter\Module;

use TYPO3\CMS\Backend\Module\ModuleAccessGateInterface;
use TYPO3\CMS\Backend\Module\ModuleAccessResult;
use TYPO3\CMS\Backend\Module\ModuleInterface;
use TYPO3\CMS\Core\Attribute\AsModuleAccessGate;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;

/**
 * Grants modules with the access "test_project_manager" to users whose
 * group data lists the custom option "project:manage".
 */
#[AsModuleAccessGate(identifier: 'test_project_manager')]
final readonly class ProjectManagerGate implements ModuleAccessGateInterface
{
    public function decide(ModuleInterface $module, BackendUserAuthentication $user): ModuleAccessResult
    {
        if ($module->getAccess() !== 'test_project_manager') {
            return ModuleAccessResult::Abstain;
        }
        return str_contains(',' . ($user->groupData['custom_options'] ?? '') . ',', ',project:manage,')
            ? ModuleAccessResult::Granted
            : ModuleAccessResult::Denied;
    }
}
