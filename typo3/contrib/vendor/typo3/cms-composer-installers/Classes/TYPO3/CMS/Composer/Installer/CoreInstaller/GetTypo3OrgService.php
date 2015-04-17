<?php
namespace TYPO3\CMS\Composer\Installer\CoreInstaller;

/***************************************************************
 * Copyright notice
 *
 * (c) 2014 Thomas Maroschik <tmaroschik@dfau.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * A service that enriches the packages with information from get.typo3.org
 *
 * @author Thomas Maroschik <tmaroschik@dfau.de>
 */
class GetTypo3OrgService {

	/**
	 * @var string
	 */
	protected $file;

	/**
	 * @var array
	 */
	protected $data = array();

	/**
	 * @param \Composer\IO\IOInterface $io
	 * @param string $jsonUrl
	 */
	public function __construct(\Composer\IO\IOInterface $io, $jsonUrl = 'https://get.typo3.org/json') {
		$this->file = new \Composer\Json\JsonFile($jsonUrl, new \Composer\Util\RemoteFilesystem($io));
	}

	/**
	 *
	 */
	protected function initializeData() {
		if (empty($this->data)) {
			$this->data = $this->file->read();
		}
	}

	/**
	 * @param \Composer\Package\Package $package
	 */
	public function addDistToPackage(\Composer\Package\Package $package) {
		$this->initializeData();
		$versionDigits = explode('.', $package->getPrettyVersion());
		if (count($versionDigits) === 3) {
			$branchVersion = $versionDigits[0] . '.' . $versionDigits[1];
			$patchlevelVersion = $versionDigits[0] . '.' . $versionDigits[1] . '.' . $versionDigits[2];
			if (isset($this->data[$branchVersion]) && isset($this->data[$branchVersion]['releases'][$patchlevelVersion])) {
				$releaseData = $this->data[$branchVersion]['releases'][$patchlevelVersion];
				if (isset($releaseData['checksums']['tar']['sha1']) && isset($releaseData['url']['tar'])) {
					$package->setDistType('tar');
					$package->setDistReference($patchlevelVersion);
					$package->setDistUrl($releaseData['url']['tar']);
					$package->setDistSha1Checksum($releaseData['checksums']['tar']['sha1']);
				}
			}
		}
	}

}