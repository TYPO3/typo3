<?php
namespace TYPO3\CMS\Install\Service;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;

/**
 * Core version service
 */
class CoreVersionService {

	/**
	 * @var \TYPO3\CMS\Extbase\Object\ObjectManager
	 * @inject
	 */
	protected $objectManager;

	/**
	 * @var \TYPO3\CMS\Core\Registry
	 * @inject
	 */
	protected $registry;

	/**
	 * Base URI for TYPO3 downloads
	 *
	 * @var string
	 */
	protected $downloadBaseUri;

	/**
	 * @return mixed
	 */
	public function getDownloadBaseUri() {
		return $this->downloadBaseUri;
	}

	/**
	 * Initialize update URI
	 */
	public function __construct() {
		$this->downloadBaseUri = 'https://get.typo3.org/';
	}

	/**
	 * Update version matrix from remote and store in registry
	 *
	 * @return void
	 * @throws Exception\RemoteFetchException
	 */
	public function updateVersionMatrix() {
		$versionArray = $this->fetchVersionMatrixFromRemote();
		// This is a 'hack' to keep the string stored in the registry small. We are usually only
		// interested in information from 6.2 and up and older releases do not matter in current
		// use cases. If this unset() is removed and everything is stored for some reason, the
		// table sys_file field entry_value needs to be extended from blob to longblob.
		unset($versionArray['6.1'], $versionArray['6.0'], $versionArray['4.7'], $versionArray['4.6'],
			$versionArray['4.5'], $versionArray['4.4'], $versionArray['4.3'], $versionArray['4.2'],
			$versionArray['4.1'], $versionArray['4.0'], $versionArray['3.8'], $versionArray['3.7'],
			$versionArray['3.6'], $versionArray['3.5'], $versionArray['3.3']);
		$this->registry->set('TYPO3.CMS.Install', 'coreVersionMatrix', $versionArray);
	}

	/**
	 * Development git checkout versions always end with '-dev'. They are
	 * not "released" as such and can not be updated.
	 *
	 * @return boolean FALSE If some development version is installed
	 */
	public function isInstalledVersionAReleasedVersion() {
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
	public function getTarGzSha1OfVersion($version) {
		$this->ensureVersionExistsInMatrix($version);

		$minorVersion = $this->getMinorVersion($version);
		$versionMatrix = $this->getVersionMatrix();

		if (empty($versionMatrix[$minorVersion]['releases'][$version]['checksums']['tar']['sha1'])) {
			throw new Exception\CoreVersionServiceException(
				'Release sha1 of version ' . $version . ' not found in version matrix.'
				. ' This is probably a bug on get.typo3.org.',
				1381263173
			);
		}

		return $versionMatrix[$minorVersion]['releases'][$version]['checksums']['tar']['sha1'];
	}

	/**
	 * Get current installed version number
	 *
	 * @return string
	 */
	public function getInstalledVersion() {
		return VersionNumberUtility::getCurrentTypo3Version();
	}

	/**
	 * Returns TRUE if a younger patch level release exists in version matrix.
	 *
	 * @return boolean TRUE if younger patch release is exists
	 */
	public function isYoungerPatchReleaseAvailable() {
		$result = FALSE;
		$version = $this->getInstalledVersion();
		$youngestVersion = $this->getYoungestPatchRelease();
		if ($youngestVersion !== $version) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Returns TRUE if a younger patch level release exists in version matrix that may be a development release.
	 *
	 * @return boolean TRUE if younger patch release is exists
	 */
	public function isYoungerPatchDevelopmentReleaseAvailable() {
		$result = FALSE;
		$version = $this->getInstalledVersion();
		$youngestVersion = $this->getYoungestPatchDevelopmentRelease();
		if ($youngestVersion !== $version) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Returns TRUE if an upgrade from current version is security relevant
	 *
	 * @return boolean TRUE if there is a pending security update
	 */
	public function isUpdateSecurityRelevant() {
		$result = FALSE;
		$version = $this->getInstalledVersion();
		$youngestVersion = $this->getYoungestReleaseByType(array('security'));
		if ($youngestVersion !== $version) {
			$result = TRUE;
		}
		return $result;
	}

	/**
	 * Youngest patch release, eg. 6.2.2
	 *
	 * @return string Version string of youngest patch level release
	 */
	public function getYoungestPatchRelease() {
		return $this->getYoungestReleaseByType(array('release', 'security', 'regular'));
	}

	/**
	 * Youngest development patch release, eg. 6.2.0alpha3 or 6.2-snapshot-20131004
	 *
	 * @return string
	 */
	public function getYoungestPatchDevelopmentRelease() {
		return $this->getYoungestReleaseByType(array('release', 'security', 'regular', 'development'));
	}

	/**
	 * Get youngest release version string.
	 * Returns same version number if no younger release was found.
	 *
	 * @param array $types List of allowed types: development, release, security, regular
	 * @throws Exception\CoreVersionServiceException
	 * @return string Youngest release, eg. 6.2.3 or 6.2.alpha3
	 */
	protected function getYoungestReleaseByType(array $types) {
		$version = $this->getInstalledVersion();

		$minorVersion = $this->getMinorVersion($version);
		$versionMatrix = $this->getVersionMatrix();

		$youngestRelease = $version;
		$versionReleaseTimestamp = $this->getReleaseTimestampOfVersion($version);

		$patchLevelVersions = $versionMatrix[$minorVersion]['releases'];
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
	 * Get 'minor version' from version string, eg '6.2' from '6.2.2'
	 *
	 * @return string For example 6.2
	 */
	protected function getInstalledMinorVersion() {
		return $this->getMinorVersion($this->getInstalledVersion());
	}

	/**
	 * Get 'minor version' of version, eg. '6.2' from '6.2.2'
	 *
	 * @param string $version to check
	 * @return string Minor version, eg. '6.2'
	 */
	protected function getMinorVersion($version) {
		$explodedVersion = explode('.', $version);
		$minor = explode('-', $explodedVersion[1]);
		return $explodedVersion[0] . '.' . $minor[0];
	}

	/**
	 * Get version matrix from registry
	 *
	 * @return array
	 * @throws Exception
	 */
	protected function getVersionMatrix() {
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
	protected function fetchVersionMatrixFromRemote() {
		$url = $this->downloadBaseUri . 'json';
		$versionJson = GeneralUtility::getUrl($url);
		if (!$versionJson) {
			throw new Exception\RemoteFetchException(
				'Fetching ' . $url . ' failed. Maybe this instance can not connect to the remote system properly.',
				1380897593
			);
		}
		return json_decode($versionJson, TRUE);
	}

	/**
	 * Returns release timestamp of a specific version
	 *
	 * @param $version String to check in version matrix, eg. 6.2.0alpha3 or 6.2.2
	 * @throws Exception\CoreVersionServiceException
	 * @return integer Timestamp of release
	 */
	protected function getReleaseTimestampOfVersion($version) {
		$minorVersion = $this->getMinorVersion($version);
		$versionMatrix = $this->getVersionMatrix();
		$this->ensureVersionExistsInMatrix($version);
		if (!array_key_exists('date', $versionMatrix[$minorVersion]['releases'][$version])) {
			throw new Exception\CoreVersionServiceException(
				'Release date of version ' . $version . ' not found in version matrix. This is probably a bug on get.typo3.org',
				1380905853
			);
		}
		$dateString = $versionMatrix[$minorVersion]['releases'][$version]['date'];
		$date = new \DateTime($dateString);
		return $date->getTimestamp();
	}

	/**
	 * Throws an exception if specified version does not exist in version matrix
	 *
	 * @param $version String to check in version matrix, eg. 6.2.0alpha3 or 6.2.2
	 * @throws Exception\CoreVersionServiceException
	 */
	protected function ensureVersionExistsInMatrix($version) {
		$minorVersion = $this->getMinorVersion($version);
		$versionMatrix = $this->getVersionMatrix();
		if (!array_key_exists($minorVersion, $versionMatrix)) {
			throw new Exception\CoreVersionServiceException(
				'Minor release ' . $minorVersion . ' not found in version matrix.',
				1380905851
			);
		}
		if (!array_key_exists($version, $versionMatrix[$minorVersion]['releases'])) {
			throw new Exception\CoreVersionServiceException(
				'Patch level release ' . $version . ' not found in version matrix.',
				1380905852
			);
		}
	}
}