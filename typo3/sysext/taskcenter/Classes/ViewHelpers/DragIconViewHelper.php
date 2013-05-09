<?php
namespace TYPO3\CMS\Taskcenter\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Wouter Wolters <typo3@wouterwolters.nl>
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
 * Drag icon ViewHelper
 *
 * @author Wouter Wolters <typo3@wouterwolters.nl>
 */
class DragIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Render
	 */
	public function render() {
  		return '<img' . \TYPO3\CMS\Backend\Utility\IconUtility::skinImg($GLOBALS['BACK_PATH'], 'gfx/move.gif', 'width="16" height="16" hspace="2"')
		. ' title="' . \TYPO3\CMS\Extbase\Utility\LocalizationUtility::translate('LLL:EXT:lang/locallang_core.xlf:labels.move', 1) . '" alt="" />';
	}

}

?>