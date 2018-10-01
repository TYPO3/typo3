<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Install\Service;

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

use TYPO3\CMS\Core\Registry;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Core version service
 * @internal This class is only meant to be used within EXT:install and is not part of the TYPO3 Core API.
 */
class CoreVersionService
{
    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * Base URI for TYPO3 Version REST api
     *
     * @var string
     */
    protected $apiBaseUrl = 'https://get.typo3.org/v1/api/';

    /**
     * Initialize update URI
     *
     * @param Registry $registry Deprecated
     */
    public function __construct(Registry $registry = null)
    {
        if (null !== $registry) {
            trigger_error(
                'The constructor parameter $registry for CoreVersionService is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0.',
                E_USER_DEPRECATED
            );
            $this->registry = $registry;
        } else {
            $this->registry = GeneralUtility::makeInstance(Registry::class);
        }
    }

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
     * Get sha1 of a version from version matrix
     *
     * @param string $version A version to get sha1 of
     * @return string sha1 of version
     * @throws Exception\CoreVersionServiceException
     */
    public function getTarGzSha1OfVersion(string $version): string
    {
        $url = 'release/' . $version;
        $result = $this->fetchFromRemote($url);

        return $result['tar_package']['sha1sum'] ?? '';
    }

    /**
     * Get current installed version number
     *
     * @return string
     */
    public function getInstalledVersion(): string
    {
        return VersionNumberUtility::getCurrentTypo3Version();
    }

    /**
     * Checks if TYPO3 version (e.g. 6.2) is an actively maintained version
     *
     * @return bool TRUE if version is actively maintained
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function isVersionActivelyMaintained(): bool
    {
        $url = 'major/' . $this->getInstalledMajorVersion();
        $result = $this->fetchFromRemote($url);

        return !isset($result['maintained_until']) ||
               (
                   new \DateTimeImmutable($result['maintained_until']) >=
                   new \DateTimeImmutable('now', new \DateTimeZone('UTC'))
               );
    }

    /**
     * Returns TRUE if a younger patch level release exists in version matrix.
     *
     * @return bool TRUE if younger patch release is exists
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function isYoungerPatchReleaseAvailable(): bool
    {
        return version_compare($this->getInstalledVersion(), $this->getYoungestPatchRelease()) === -1;
    }

    /**
     * Returns TRUE if an upgrade from current version is security relevant
     *
     * @return bool TRUE if there is a pending security update
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function isUpdateSecurityRelevant(): bool
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release/latest/security';
        $result = $this->fetchFromRemote($url);

        if (isset($result['version'])) {
            return version_compare($this->getInstalledVersion(), $result['version']) === -1;
        }
        return false;
    }

    /**
     * Youngest patch release, e.g., 6.2.2
     *
     * @return string Version string of youngest patch level release
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function getYoungestPatchRelease(): string
    {
        $url = 'major/' . $this->getInstalledMajorVersion() . '/release/latest';
        $result = $this->fetchFromRemote($url);
        return $result['version'];
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
        return $this->getMajorVersion($this->getInstalledVersion());
    }

    /**
     * Get 'major version' of version, e.g., '7' from '7.3.0'
     *
     * @param string $version to check
     * @return string Major version, e.g., '7'
     */
    protected function getMajorVersion(string $version): string
    {
        $explodedVersion = explode('.', $version);
        return $explodedVersion[0];
    }

    /**
     * Update version matrix from remote and store in registry
     *
     * @deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0 - use new REST API directly (see https://get.typo3.org/v1/api/doc)
     * @throws Exception\RemoteFetchException
     */
    public function updateVersionMatrix(): void
    {
        trigger_error('Method updateVersionMatrix will be removed in TYPO3 v10.0, use new REST API directly (see https://get.typo3.org/v1/api/doc).', E_USER_DEPRECATED);
        $url = 'https://get.typo3.org/json';
        $versionJson = GeneralUtility::getUrl($url);
        if (!$versionJson) {
            $this->throwFetchException($url);
        }
        $versionArray = json_decode($versionJson, true);
        $installedMajorVersion = (int)$this->getInstalledMajorVersion();

        foreach ($versionArray as $versionNumber => $versionDetails) {
            if (is_array($versionDetails) && (int)$this->getMajorVersion((string)$versionNumber) < $installedMajorVersion) {
                unset($versionArray[$versionNumber]);
            }
        }

        $this->registry->set('TYPO3.CMS.Install', 'coreVersionMatrix', $versionArray);
    }

    /**
     * Youngest development patch release, e.g., 6.2.0alpha3 or 6.2-snapshot-20131004
     *
     * @deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0 - TYPO3 release cycles do not contain development releases anymore
     * @return string
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function getYoungestPatchDevelopmentRelease(): string
    {
        trigger_error(
            'Method getYoungestPatchDevelopmentRelease() is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0, use getYoungestPatchRelease() instead.',
            E_USER_DEPRECATED
        );
        return $this->getYoungestPatchRelease();
    }

    /**
     * Returns TRUE if a younger patch level release exists in version matrix that may be a development release.
     *
     * @deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0 - TYPO3 release cycles do not contain development releases anymore
     * @return bool TRUE if younger patch release is exists
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    public function isYoungerPatchDevelopmentReleaseAvailable(): bool
    {
        trigger_error(
            'Method isYoungerPatchDevelopmentReleaseAvailable() is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0, use isYoungerPatchReleaseAvailable() instead.',
            E_USER_DEPRECATED
        );
        return $this->isYoungerPatchReleaseAvailable();
    }

    /**
     * @deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0, use 'https://get.typo3.org' directly
     * @return string
     */
    public function getDownloadBaseUrl(): string
    {
        trigger_error(
            'Method getDownloadBaseUrl() is deprecated since TYPO3 v9 and will be removed in TYPO3 v10.0, use https://get.typo3.org directly.',
            E_USER_DEPRECATED
        );
        return $this->apiBaseUrl;
    }

    /**
     * Helper method to throw same exception in multiple places
     *
     * @param string $url
     * @throws \TYPO3\CMS\Install\Service\Exception\RemoteFetchException
     */
    protected function throwFetchException(string $url): void
    {
        throw new Exception\RemoteFetchException(
            'Fetching ' .
            $url .
            ' failed. Maybe this instance can not connect to the remote system properly.',
            1380897593
        );
    }
}
