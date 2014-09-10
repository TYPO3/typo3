<?php
namespace TYPO3\CMS\IndexedSearch\ViewHelpers\Format;

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
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;

/**
 * DateTime viewhelper
 */
class DateTimeViewHelper extends AbstractViewHelper {


	/**
	 * Render the given timestamp as date & time
	 *
	 * @return string
	 */
	public function render() {
		return htmlspecialchars(BackendUtility::datetime($this->renderChildren()));
	}

}
