<?php
namespace TYPO3\CMS\Core\Configuration;

/**
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