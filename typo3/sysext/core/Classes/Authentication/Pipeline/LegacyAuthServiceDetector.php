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

namespace TYPO3\CMS\Core\Authentication\Pipeline;

use TYPO3\CMS\Core\Authentication\AuthenticationService;

/**
 * Detects whether authentication must be routed through the legacy
 * authentication service chain: either because the available "auth"
 * services differ from the core authentication service (an extension
 * registered an additional service, or the core service is disabled), or
 * because the chain behavior is modified through service configuration, the
 * core service class is replaced (service className or XCLASS) or a
 * "postUserLookUp" hook is registered, which only the legacy chain calls.
 * Services that are not available are ignored, as the legacy chain never
 * runs them. The native pipeline authenticators step aside in that case,
 * so existing extensions and configurations keep working unchanged.
 *
 * @internal not part of TYPO3 Core API, only exists for the migration period
 *           towards native pipeline authenticators
 */
final class LegacyAuthServiceDetector
{
    private const array CORE_SERVICE_KEYS = [
        AuthenticationService::class,
    ];

    public function requiresLegacyServiceChain(): bool
    {
        $availableServiceKeys = array_keys(array_filter(
            $GLOBALS['T3_SERVICES']['auth'] ?? [],
            static fn(array $service): bool => (bool)($service['available'] ?? false)
        ));
        if (array_diff($availableServiceKeys, self::CORE_SERVICE_KEYS) !== []
            || array_diff(self::CORE_SERVICE_KEYS, $availableServiceKeys) !== []
        ) {
            return true;
        }
        foreach (self::CORE_SERVICE_KEYS as $serviceKey) {
            $className = $GLOBALS['T3_SERVICES']['auth'][$serviceKey]['className'] ?? null;
            if ($className !== $serviceKey || !empty($GLOBALS['TYPO3_CONF_VARS']['SYS']['Objects'][$className]['className'])) {
                return true;
            }
        }
        if (!empty($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['t3lib/class.t3lib_userauth.php']['postUserLookUp'])) {
            return true;
        }
        // Chain behavior modifiers such as BE_alwaysFetchUser or FE_alwaysAuthUser
        return !empty($GLOBALS['TYPO3_CONF_VARS']['SVCONF']['auth']['setup']);
    }
}
