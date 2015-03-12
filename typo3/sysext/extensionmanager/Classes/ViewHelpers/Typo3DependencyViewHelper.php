<?php
namespace TYPO3\CMS\Extensionmanager\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Core\Utility\VersionNumberUtility;
use TYPO3\CMS\Extensionmanager\Domain\Model\Dependency;
use TYPO3\CMS\Extensionmanager\Domain\Model\Extension;

/**
 * Shows the version numbers of the TYPO3 dependency, if any
 *
 * @internal
 */
class Typo3DependencyViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Finds and returns the suitable TYPO3 versions of an extension
	 *
	 * @param Extension $extension
	 * @return string
	 */
	public function render(Extension $extension) {
		/** @var Dependency $dependency */
		foreach ($extension->getDependencies() as $dependency) {
			if ($dependency->getIdentifier() === 'typo3') {
				$lowestVersion = $dependency->getLowestVersion();
				$highestVersion = $dependency->getHighestVersion();
				$cssClass = $this->isVersionSuitable($lowestVersion, $highestVersion) ? 'success' : 'default';
				return
					'<span class="label label-' . $cssClass . '">'
						. htmlspecialchars($lowestVersion) . ' - ' . htmlspecialchars($highestVersion)
					. '</span>';
			}
		}
		return '';
	}

	/**
	 * Check if current TYPO3 version is suitable for the extension
	 *
	 * @param string $lowestVersion
	 * @param string $highestVersion
	 * @return bool
	 */
	protected function isVersionSuitable($lowestVersion, $highestVersion) {
		$numericTypo3Version = VersionNumberUtility::convertVersionNumberToInteger(VersionNumberUtility::getNumericTypo3Version());
		$numericLowestVersion = VersionNumberUtility::convertVersionNumberToInteger($lowestVersion);
		$numericHighestVersion = VersionNumberUtility::convertVersionNumberToInteger($highestVersion);
		return MathUtility::isIntegerInRange($numericTypo3Version, $numericLowestVersion, $numericHighestVersion);
	}
}
