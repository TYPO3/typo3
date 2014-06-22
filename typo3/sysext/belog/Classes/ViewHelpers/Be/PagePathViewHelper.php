<?php
namespace TYPO3\CMS\Belog\ViewHelpers\Be;

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
 * Get page path string from page id
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class PagePathViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Resolve page id to page path string (with automatic cropping to maximum given length).
	 *
	 * @param integer $pid Pid of the page
	 * @param integer $titleLimit Limit of the page title
	 * @return string Page path string
	 */
	public function render($pid, $titleLimit = 20) {
		return \TYPO3\CMS\Backend\Utility\BackendUtility::getRecordPath($pid, '', $titleLimit);
	}

}
