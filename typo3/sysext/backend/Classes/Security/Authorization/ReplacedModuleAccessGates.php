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

namespace TYPO3\CMS\Backend\Security\Authorization;

use TYPO3\CMS\Backend\Module\AccessGate\AdminGate;
use TYPO3\CMS\Backend\Module\AccessGate\SystemMaintainerGate;
use TYPO3\CMS\Backend\Module\AccessGate\UserGate;
use TYPO3\CMS\Backend\Module\ModuleAccessGateRegistry;

/**
 * Tells whether an extension replaced the module access gate of TYPO3 Core
 * for one of the access values "user", "admin" or "systemMaintainer", which
 * is possible since TYPO3 v14.2. A replaced gate keeps deciding that access
 * value, through the ModuleAccessGateVoter, instead of the ModuleVoter.
 *
 * @internal Bridge for deprecated module access gates, to be removed with them.
 */
final readonly class ReplacedModuleAccessGates
{
    private const array CORE_GATES = [
        'user' => UserGate::class,
        'admin' => AdminGate::class,
        'systemMaintainer' => SystemMaintainerGate::class,
    ];

    public function __construct(
        private ModuleAccessGateRegistry $gateRegistry,
    ) {}

    public function isReplaced(string $access): bool
    {
        $coreGate = self::CORE_GATES[$access] ?? null;
        return $coreGate !== null
            && $this->gateRegistry->has($access)
            && !$this->gateRegistry->get($access) instanceof $coreGate;
    }
}
