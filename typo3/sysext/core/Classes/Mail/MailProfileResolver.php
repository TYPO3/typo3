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

namespace TYPO3\CMS\Core\Mail;

use Symfony\Component\DependencyInjection\Attribute\Autoconfigure;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\PathUtility;

/**
 * Resolves the mail settings of a named mail profile.
 *
 * Profiles are defined in $GLOBALS['TYPO3_CONF_VARS']['MAIL']['profiles'] and use the same
 * keys as the global MAIL configuration. A profile is merged on top of the global settings,
 * so it only needs to define the keys that differ from them.
 *
 * The spool of the global settings is not inherited: spooled emails are sent through the
 * transport of the settings they were spooled with, so a profile spools only if it
 * configures a spool of its own, in a directory no other configuration spools into.
 *
 * A profile that defines its own "transport", "transport_smtp_server" or "dsn" does not
 * inherit "transport_smtp_username", "transport_smtp_password" and "dsn" either, so the
 * credentials of the global server are never sent to another one.
 */
#[Autoconfigure(public: true)]
readonly class MailProfileResolver
{
    public function hasProfile(string $profileName): bool
    {
        return is_array($this->getProfiles()[$profileName] ?? null);
    }

    /**
     * @return list<string>
     */
    public function getProfileNames(): array
    {
        return array_keys($this->getProfiles());
    }

    /**
     * Returns the mail settings of the given profile, merged on top of the global settings.
     *
     * Without a profile name, the global settings are returned unchanged.
     *
     * @throws \InvalidArgumentException if the given profile is not configured
     */
    public function getSettings(?string $profileName = null): array
    {
        $globalSettings = $this->getGlobalSettings();
        if ($profileName === null || $profileName === '') {
            return $globalSettings;
        }
        $profile = $this->getProfiles()[$profileName] ?? null;
        if (!is_array($profile)) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Mail profile "%s" is not configured in $GLOBALS[\'TYPO3_CONF_VARS\'][\'MAIL\'][\'profiles\'].',
                    $profileName
                ),
                1785920446
            );
        }
        if (!empty($profile['dsn']) && !isset($profile['transport'])) {
            $profile['transport'] = 'dsn';
        }
        $this->assertSpoolDirectoryIsNotShared($profileName, $profile, $globalSettings);
        unset($globalSettings['profiles'], $globalSettings['transport_spool_type'], $globalSettings['transport_spool_filepath']);
        if ($this->definesOwnConnection($profile)) {
            unset($globalSettings['transport_smtp_username'], $globalSettings['transport_smtp_password'], $globalSettings['dsn']);
        }
        return array_replace($globalSettings, $profile);
    }

    private function definesOwnConnection(array $profile): bool
    {
        return isset($profile['transport']) || isset($profile['transport_smtp_server']) || !empty($profile['dsn']);
    }

    /**
     * @throws \InvalidArgumentException if the file spool of the profile is used by the global settings or another profile
     */
    private function assertSpoolDirectoryIsNotShared(string $profileName, array $profile, array $globalSettings): void
    {
        $spoolDirectory = $this->getFileSpoolDirectory($profile);
        if ($spoolDirectory === null) {
            return;
        }
        $sharedWith = null;
        if ($this->getFileSpoolDirectory($globalSettings) === $spoolDirectory) {
            $sharedWith = 'the global mail settings';
        } else {
            foreach ($this->getProfiles() as $otherProfileName => $otherProfile) {
                if ((string)$otherProfileName !== $profileName && $this->getFileSpoolDirectory($otherProfile) === $spoolDirectory) {
                    $sharedWith = sprintf('mail profile "%s"', $otherProfileName);
                    break;
                }
            }
        }
        if ($sharedWith !== null) {
            throw new \InvalidArgumentException(
                sprintf(
                    'Mail profile "%s" must not spool into the same "transport_spool_filepath" as %s, as its emails would be sent through the wrong transport.',
                    $profileName,
                    $sharedWith
                ),
                1794593262
            );
        }
    }

    /**
     * Resolves the directory of a file spool the same way as TransportFactory, so that
     * two spellings of one directory are detected as the same.
     */
    private function getFileSpoolDirectory(array $settings): ?string
    {
        $path = (string)($settings['transport_spool_filepath'] ?? '');
        if (($settings['transport_spool_type'] ?? '') !== TransportFactory::SPOOL_FILE || $path === '') {
            return null;
        }
        $resolvedPath = GeneralUtility::isAllowedAbsPath($path) ? $path : GeneralUtility::getFileAbsFileName($path);
        return rtrim(PathUtility::getCanonicalPath($resolvedPath !== '' ? $resolvedPath : $path), '/');
    }

    private function getProfiles(): array
    {
        return array_filter(
            (array)($this->getGlobalSettings()['profiles'] ?? []),
            static fn(mixed $profile): bool => is_array($profile)
        );
    }

    private function getGlobalSettings(): array
    {
        return (array)($GLOBALS['TYPO3_CONF_VARS']['MAIL'] ?? []);
    }
}
