<?php
namespace TYPO3\CMS\Belog\ViewHelpers\Be;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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

?>