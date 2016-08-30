<?php
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

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Core version service
 */
class CoreVersionService
{
    /**
     * @var \TYPO3\CMS\Extbase\Object\ObjectManager
     */
    protected $objectManager;

    /**
     * @var \TYPO3\CMS\Core\Registry
     */
    protected $registry;

    /**
     * Base URI for TYPO3 downloads
     *
     * @var string
     */
    protected $downloadBaseUri;

    /**
     * @param \TYPO3\CMS\Extbase\Object\ObjectManager $objectManager
     */
    public function injectObjectManager(\TYPO3\CMS\Extbase\Object\ObjectManager $objectManager)
    {
        $this->objectManager = $objectManager;
    }

    /**
     * @param \TYPO3\CMS\Core\Registry $registry
     */
    public function injectRegistry(\TYPO3\CMS\Core\Registry $registry)
    {
        $this->registry = $registry;
    }

    /**
     * @return mixed
     */
    public function getDownloadBaseUri()
    {
        return $this->downloadBaseUri;
    }

    /**
     * Initialize update URI
     */
    public function __construct()
    {
        $this->downloadBaseUri = 'https://get.typo3.org/';
    }

    /**
     * Update version matrix from remote and store in registry
     *
     * @return void
     * @throws Exception\RemoteFetchException
     */
    public function updateVersionMatrix()
    {
        $versionArray = $this->fetchVersionMatrixFromRemote();
        $installedMajorVersion = (int)$this->getInstalledMajorVersion();

        foreach ($versionArray as $versionNumber => $versionDetails) {
            if (is_array($versionDetails) && (int)$this->getMajorVersion($versionNumber) < $installedMajorVersion) {
                unset($versionArray[$versionNumber]);
            }
        }

        $this->registry->set('TYPO3.CMS.Install', 'coreVersionMatrix', $versionArray);
    }

    /**
     * Development git checkout versions always end with '-dev'. They are
     * not "released" as such and can not be updated.
     *
     * @return bool FALSE If some development version is installed
     */
    public function isInstalledVersionAReleasedVersion()
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
    public function getTarGzSha1OfVersion($version)
    {
        $this->ensureVersionExistsInMatrix($version);

        $majorVersion = $this->getMajorVersion($version);
        $versionMatrix = $this->getVersionMatrix();

        if (empty($versionMatrix[$majorVersion]['releases'][$version]['checksums']['tar']['sha1'])) {
            throw new Exception\CoreVersionServiceException(
                'Release sha1 of version ' . $version . ' not found in version matrix.'
                . ' This is probably a bug on get.typo3.org.',
                1381263173
            );
        }

        return $versionMatrix[$majorVersion]['releases'][$version]['checksums']['tar']['sha1'];
    }

    /**
     * Get current installed version number
     *
     * @return string
     */
    public function getInstalledVersion()
    {
        return VersionNumberUtility::getCurrentTypo3Version();
    }

    /**
     * Checks if TYPO3 version (e.g. 6.2) is an actively maintained version
     *
     * @return bool TRUE if version is actively maintained
     */
    public function isVersionActivelyMaintained()
    {
        $majorVersion = $this->getInstalledMajorVersion();
        $versionMatrix = $this->getVersionMatrix();
        return (bool)$versionMatrix[$majorVersion]['active'];
    }

    /**
     * Returns TRUE if a younger patch level release exists in version matrix.
     *
     * @return bool TRUE if younger patch release is exists
     */
    public function isYoungerPatchReleaseAvailable()
    {
        $version = $this->getInstalledVersion();
        $youngestVersion = $this->getYoungestPatchRelease();
        return $youngestVersion !== $version;
    }

    /**
     * Returns TRUE if a younger patch level release exists in version matrix that may be a development release.
     *
     * @return bool TRUE if younger patch release is exists
     */
    public function isYoungerPatchDevelopmentReleaseAvailable()
    {
        $result = false;
        $version = $this->getInstalledVersion();
        $youngestVersion = $this->getYoungestPatchDevelopmentRelease();
        if ($youngestVersion !== $version) {
            $result = true;
        }
        return $result;
    }

    /**
     * Returns TRUE if an upgrade from current version is security relevant
     *
     * @return bool TRUE if there is a pending security update
     */
    public function isUpdateSecurityRelevant()
    {
        $result = false;
        $version = $this->getInstalledVersion();
        $youngestVersion = $this->getYoungestReleaseByType(['security']);
        if ($youngestVersion !== $version) {
            $result = true;
        }
        return $result;
    }

    /**
     * Youngest patch release, e.g., 6.2.2
     *
     * @return string Version string of youngest patch level release
     */
    public function getYoungestPatchRelease()
    {
        return $this->getYoungestReleaseByType(['release', 'security', 'regular']);
    }

    /**
     * Youngest development patch release, e.g., 6.2.0alpha3 or 6.2-snapshot-20131004
     *
     * @return string
     */
    public function getYoungestPatchDevelopmentRelease()
    {
        return $this->getYoungestReleaseByType(['release', 'security', 'regular', 'development']);
    }

    /**
     * Get youngest release version string.
     * Returns same version number if no younger release was found.
     *
     * @param array $types List of allowed types: development, release, security, regular
     * @throws Exception\CoreVersionServiceException
     * @return string Youngest release, e.g., 7.2.0alpha3 or 7.3.0
     */
    protected function getYoungestReleaseByType(array $types)
    {
        $version = $this->getInstalledVersion();

        $majorVersion = $this->getMajorVersion($version);
        $versionMatrix = $this->getVersionMatrix();

        $youngestRelease = $version;
        $versionReleaseTimestamp = $this->getReleaseTimestampOfVersion($version);

        $patchLevelVersions = $versionMatrix[$majorVersion]['releases'];
        foreach ($patchLevelVersions as $aVersionNumber => $aVersionDetails) {
            if (!array_key_exists('type', $aVersionDetails)) {
                throw new Exception\CoreVersionServiceException(
                    'Release type of version ' . $aVersionNumber . ' not found in version matrix.'
                        . ' This is probably a bug on get.typo3.org.',
                    1380909029
                );
            }
            $type = $aVersionDetails['type'];
            $aVersionNumberReleaseTimestamp = $this->getReleaseTimestampOfVersion($aVersionNumber);
            if (
                $aVersionNumberReleaseTimestamp > $versionReleaseTimestamp
                && in_array($type, $types)
            ) {
                $youngestRelease = $aVersionNumber;
                $versionReleaseTimestamp = $aVersionNumberReleaseTimestamp;
            }
        }
        return $youngestRelease;
    }

    /**
     * Get 'major version' from installed version of TYPO3, e.g., '7' from '7.3.0'
     *
     * @return string For example 7
     */
    protected function getInstalledMajorVersion()
    {
        return $this->getMajorVersion($this->getInstalledVersion());
    }

    /**
     * Get 'major version' of version, e.g., '7' from '7.3.0'
     *
     * @param string $version to check
     * @return string Major version, e.g., '7'
     */
    protected function getMajorVersion($version)
    {
        $explodedVersion = explode('.', $version);
        return $explodedVersion[0];
    }

    /**
     * Get version matrix from registry
     *
     * @return array
     * @throws Exception
     */
    protected function getVersionMatrix()
    {
        $versionMatrix = $this->registry->get('TYPO3.CMS.Install', 'coreVersionMatrix');
        if (empty($versionMatrix) || !is_array($versionMatrix)) {
            throw new Exception\CoreVersionServiceException(
                'No version matrix found in registry, call updateVersionMatrix() first.',
                1380898792
            );
        }
        return $versionMatrix;
    }

    /**
     * Get available version string from get.typo3.org
     *
     * @return array
     * @throws Exception\RemoteFetchException
     */
    protected function fetchVersionMatrixFromRemote()
    {
        $url = $this->downloadBaseUri . 'json';
        $versionJson = GeneralUtility::getUrl($url);
        if (!$versionJson) {
            throw new Exception\RemoteFetchException(
                'Fetching ' . $url . ' failed. Maybe this instance can not connect to the remote system properly.',
                1380897593
            );
        }
        return json_decode($versionJson, true);
    }

    /**
     * Returns release timestamp of a specific version
     *
     * @param $version String to check in version matrix, e.g., 7.2.0alpha3 or 7.3.0
     * @throws Exception\CoreVersionServiceException
     * @return int Timestamp of release
     */
    protected function getReleaseTimestampOfVersion($version)
    {
        $majorVersion = $this->getMajorVersion($version);
        $versionMatrix = $this->getVersionMatrix();
        $this->ensureVersionExistsInMatrix($version);
        if (!array_key_exists('date', $versionMatrix[$majorVersion]['releases'][$version])) {
            throw new Exception\CoreVersionServiceException(
                'Release date of version ' . $version . ' not found in version matrix. This is probably a bug on get.typo3.org',
                1380905853
            );
        }
        $dateString = $versionMatrix[$majorVersion]['releases'][$version]['date'];
        $date = new \DateTime($dateString);
        return $date->getTimestamp();
    }

    /**
     * Throws an exception if specified version does not exist in version matrix
     *
     * @param $version String to check in version matrix, e.g., 7.2.0alpha3 or 7.3.0
     * @throws Exception\CoreVersionServiceException
     */
    protected function ensureVersionExistsInMatrix($version)
    {
        $majorVersion = $this->getMajorVersion($version);
        $versionMatrix = $this->getVersionMatrix();
        if (!array_key_exists($majorVersion, $versionMatrix)) {
            throw new Exception\CoreVersionServiceException(
                'Major release ' . $majorVersion . ' not found in version matrix.',
                1380905851
            );
        }
        if (!array_key_exists($version, $versionMatrix[$majorVersion]['releases'])) {
            throw new Exception\CoreVersionServiceException(
                'Patch level release ' . $version . ' not found in version matrix.',
                1380905852
            );
        }
    }
}
