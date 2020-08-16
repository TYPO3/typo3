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

namespace TYPO3\CMS\Install\Service;

use TYPO3\CMS\Core\Information\Typo3Version;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Install\CoreVersion\CoreRelease;
use TYPO3\CMS\Install\CoreVersion\MaintenanceWindow;
use TYPO3\CMS\Install\CoreVersion\MajorRelease;
use TYPO3\CMS\Install\Service\Exception\RemoteFetchException;

/**
 * Core version service
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CoreVersionService
{
    /**
     * Base URI for TYPO3 Version REST api
     *
     * @var string
     */
    protected $apiBaseUrl = 'https://get.typo3.org/api/v1/';

    /**
     * Development git checkout versions always end with '-dev'. They are
     * not "released" as such and can not be updated.
     *
     * @return bool FALSE If some development version is installed
     */
    public function isInstalledVersionAReleasedVersion(): bool
    {
        $version = $this->getInstalledVersion();
        return substr($version, -4) !== '-dev';
    }

    /**
     * Get current installed version number
     *
     * @return string
     */
    public function getInstalledVersion(): string
    {
        return (string)GeneralUtility::makeInstance(Typo3Version::class);
    }

    public function getMaintenanceWindow(): MaintenanceWindow
    {
        $url = 'major/' . $this->getInstalledMajorVersion();
        $result = $this->fetchFromRemote($url);

        return MaintenanceWindow::fromApiResponse($result);
    }

    /**
     * @todo docblock
     * @return array{community: string[], elts: string[]}
     */
    public function getSupportedMajorReleases(): array
    {
        $url = 'major';
        $result = $this->fetchFromRemote($url);

        $majorReleases = [
            'community' => [],
            'elts' => [],
        ];
        foreach ($result as $release) {
            $majorRelease = MajorRelease::fromApiResponse($release);
            $maintenanceWindow = $majorRelease->getMaintenanceWindow();

            if ($maintenanceWindow->isSupportedByCommunity()) {
                $group = 'community';
            } elseif ($maintenanceWindow->isSupportedByElts()) {
                $group = 'elts';
            } else {
                // Major version is unsupported
                continue;
            }

            $majorReleases[$group][] = $majorRelease->getLts() ?? $majorRelease->getVersion();
        }

        return $majorReleases;
    }

    public function isPatchReleaseSuitableForUpdate(CoreRelease $coreRelease): bool
    {
        return version_compare($this->getInstalledVersion(), $coreRelease->getVersion()) === -1;
    }

    /**
     * Returns TRUE if an upgrade from current version is security relevant
     *
     * @return bool TRUE if there is a pending security update
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function isUpdateSecurityRelevant(CoreRelease $releaseToCheck): bool
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release';
        $result = $this->fetchFromRemote($url);

        $installedVersion = $this->getInstalledVersion();
        foreach ($result as $release) {
            $coreRelease = CoreRelease::fromApiResponse($release);
            if ($coreRelease->isSecurityUpdate()
                && version_compare($installedVersion, $coreRelease->getVersion()) === -1 // installed version is lower than release
                && version_compare($releaseToCheck->getVersion(), $coreRelease->getVersion()) > -1 // release to check is equal or higher than release
            ) {
                return true;
            }
        }

        return false;
    }

    public function isCurrentInstalledVersionElts(): bool
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release';
        $result = $this->fetchFromRemote($url);

        $installedVersion = $this->getInstalledVersion();
        foreach ($result as $release) {
            if (version_compare($installedVersion, $release['version']) === 0) {
                return $release['elts'] ?? false;
            }
        }

        return false;
    }

    /**
     * Youngest patch release
     *
     * @return CoreRelease
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function getYoungestPatchRelease(): CoreRelease
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release/latest';
        $result = $this->fetchFromRemote($url);
        return CoreRelease::fromApiResponse($result);
    }

    public function getYoungestCommunityPatchRelease(): CoreRelease
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release';
        $result = $this->fetchFromRemote($url);

        // Make sure all releases are sorted by their version
        $columns = array_column($result, 'version');
        array_multisort($columns, SORT_NATURAL, $result);

        // Remove any ELTS release
        $releases = array_filter($result, static function (array $release) {
            return ($release['elts'] ?? false) === false;
        });

        $latestRelease = end($releases);

        return CoreRelease::fromApiResponse($latestRelease);
    }

    /**
     * @param string $url
     * @return array
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    protected function fetchFromRemote(string $url): array
    {
        $url = $this->apiBaseUrl . $url;
        $json = GeneralUtility::getUrl($url);

        if (!$json) {
            $this->throwFetchException($url);
        }
        return json_decode($json, true);
    }

    /**
     * Get 'major version' from installed version of TYPO3, e.g., '7' from '7.3.0'
     *
     * @return string For example 7
     */
    protected function getInstalledMajorVersion(): string
    {
        return (string)GeneralUtility::makeInstance(Typo3Version::class)->getMajorVersion();
    }

    /**
     * Helper method to throw same exception in multiple places
     *
     * @param string $url
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    protected function throwFetchException(string $url): void
    {
        throw new RemoteFetchException(
            'Fetching ' .
            $url .
            ' failed. Maybe this instance can not connect to the remote system properly.',
            1380897593
        );
    }
}
