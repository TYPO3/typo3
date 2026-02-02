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

namespace TYPO3\CMS\Core\Authentication;

/**
 * @internal This class is not part of the TYPO3 Core API yet.
 */
class UserSettingsSchema
{
    /**
     * @return string[]
     */
    public function getJsonFieldSettingKeys(): array
    {
        $keys = [];
        foreach ($GLOBALS['TYPO3_USER_SETTINGS']['columns'] ?? [] as $key => $config) {
            // Fields with 'table' => 'be_users' are stored in be_users columns directly
            // Also skip non-storable types like 'button' and 'mfa'
            if (($config['table'] ?? '') !== 'be_users'
                && !in_array($config['type'] ?? '', ['button', 'mfa'], true)
            ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * @return string[]
     */
    public function getDbColumnSettingKeys(): array
    {
        $keys = [];
        foreach ($GLOBALS['TYPO3_USER_SETTINGS']['columns'] ?? [] as $key => $config) {
            if (($config['table'] ?? '') === 'be_users'
                && !in_array($config['type'] ?? '', ['button', 'mfa', 'password'], true)
            ) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    public function isJsonFieldSetting(string $key): bool
    {
        return in_array($key, $this->getJsonFieldSettingKeys(), true);
    }

    public function isDbColumnSetting(string $key): bool
    {
        return in_array($key, $this->getDbColumnSettingKeys(), true);
    }

    public function getDefault(string $key): mixed
    {
        return $GLOBALS['TYPO3_USER_SETTINGS']['columns'][$key]['default'] ?? null;
    }
}
