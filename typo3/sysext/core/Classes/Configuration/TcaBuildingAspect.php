<?php
namespace TYPO3\CMS\Core\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Helmut Hummel <helmut.hummel@typo3.org>
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

/**
 * Assembles TCA overrides from packages to build the final TCA
 */
class TcaBuildingAspect {

	const TCA_OVERRIDES_PATH = 'Configuration/TCA/Overrides';

	/**
	 * @var \TYPO3\CMS\Core\Package\PackageManager
	 * @inject
	 */
	protected $packageManager;

	/**
	 * @var \TYPO3\CMS\Core\Category\CategoryRegistry
	 * @inject
	 */
	protected $categoryRegistry;

	/**
	 * Scans active packages for TCA override code and executes it.
	 * Also applies category registry changes after that, so that
	 * registering calls to the registry can be (and should be) in TCA override files.
	 *
	 * @return array
	 */
	public function applyTcaOverrides() {
		$this->categoryRegistry->applyTcaForPreRegisteredTables();
		foreach ($this->packageManager->getActivePackages() as $package) {
			$tcaOverridesPathForPackage = $package->getPackagePath() . self::TCA_OVERRIDES_PATH;
			if (is_dir($tcaOverridesPathForPackage)) {
				$files = scandir($tcaOverridesPathForPackage);
				foreach ($files as $file) {
					if (
						is_file($tcaOverridesPathForPackage . '/' . $file)
						&& ($file !== '.')
						&& ($file !== '..')
						&& (substr($file, -4, 4) === '.php')
					) {
						require($tcaOverridesPathForPackage . '/' . $file);
					}
				}
			}

		}
		return array($GLOBALS['TCA']);
	}
}