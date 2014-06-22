<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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
 * Returns true, if a specific extension is loaded
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class IsExtensionLoadedViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Checks whether an extension is loaded.
	 *
	 * @param string $extensionKey The extension's key
	 * @return boolean TRUE if extension is loaded, FALSE otherwise
	 */
	public function render($extensionKey) {
		return \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::isLoaded($extensionKey);
	}

}
