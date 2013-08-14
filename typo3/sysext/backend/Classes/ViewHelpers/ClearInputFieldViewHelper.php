<?php
namespace TYPO3\CMS\Backend\ViewHelpers;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Weiske <cweiske@cweiske.de>
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
 * Add a "clear input field" button
 *
 * @author Christian Weiske <cweiske@cweiske.de>
 */
class ClearInputFieldViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Render the clear input field button
	 *
	 * @param string $field Name of field the button is for
	 * @return string HTML code
	 */
	public function render($field) {
		$label = htmlspecialchars(
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_login.xlf:clear')
		);
return <<<HTM
<div class="t3-clearInputField">
	<a id="${field}-clearIcon" style="display: none;" class="clearIcon">
		<img src="sysext/t3skin/icons/common-input-clear.png" alt="${label}" title="${label}" />
	</a>
</div>
HTM;
	}
}
?>
