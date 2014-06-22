<?php
namespace TYPO3\CMS\Install\ViewHelpers\File;

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
 * Get file path relative to PATH_site from absolute path
 *
 * = Examples =
 *
 * <code title="Defaults">
 * <f:file.relativePath>/var/www/typo3/instance/typo3temp/foo.jpg</f:file.relativePath>
 * </code>
 * <output>
 * typo3temp/foo.jpg
 * </output>
 */
class RelativePathViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Get relative path
	 *
	 * @return string Relative path
	 */
	public function render() {
		$absolutePath = $this->renderChildren();
		return \TYPO3\CMS\Core\Utility\PathUtility::stripPathSitePrefix($absolutePath);
	}
}
