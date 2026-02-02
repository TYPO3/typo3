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

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @internal This class is not part of the TYPO3 Core API yet.
 */
readonly class UserSettingsFactory
{
    private UserSettingsSchema $schema;

    public function __construct(
        ?UserSettingsSchema $schema = null,
    ) {
        $this->schema = $schema ?? GeneralUtility::makeInstance(UserSettingsSchema::class);
    }

    public function createFromUserRecord(array $userRecord, array $uc = []): UserSettings
    {
        $dbColumnSettings = $this->extractDbColumnSettings($userRecord);
        $jsonFieldSettings = $this->extractJsonFieldSettings($userRecord, $uc);

        return new UserSettings(array_merge($dbColumnSettings, $jsonFieldSettings));
    }

    public function createFromUc(array $uc): UserSettings
    {
        $settings = [];
        foreach ($this->schema->getJsonFieldSettingKeys() as $key) {
            if (array_key_exists($key, $uc)) {
                $settings[$key] = $uc[$key];
            }
        }

        return new UserSettings($settings);
    }

    private function extractJsonFieldSettings(array $userRecord, array $uc): array
    {
        $settings = [];

        // Primary source: user_settings JSON field
        if (!empty($userRecord['user_settings'])) {
            $decoded = is_string($userRecord['user_settings'])
                ? json_decode($userRecord['user_settings'], true)
                : $userRecord['user_settings'];
            if (is_array($decoded)) {
                $settings = $decoded;
            }
        }

        // Fallback: fill missing values from uc (migration period)
        foreach ($this->schema->getJsonFieldSettingKeys() as $key) {
            if (!array_key_exists($key, $settings) && array_key_exists($key, $uc)) {
                $settings[$key] = $uc[$key];
            }
        }

        return $settings;
    }

    private function extractDbColumnSettings(array $userRecord): array
    {
        $settings = [];

        foreach ($this->schema->getDbColumnSettingKeys() as $key) {
            if (array_key_exists($key, $userRecord)) {
                $settings[$key] = $userRecord[$key];
            }
        }

        return $settings;
    }
}
