<?php
namespace TYPO3\CMS\Belog\ViewHelpers;

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
 * Display error icon from error integer value
 *
 * @author Christian Kuhn <lolli@schwarzbu.ch>
 */
class ErrorIconViewHelper extends \TYPO3\CMS\Fluid\ViewHelpers\Be\AbstractBackendViewHelper {

	/**
	 * Renders an error icon link as known from the TYPO3 backend.
	 * Error codes 2 and three are mapped to "error" and 1 is mapped to "warning".
	 *
	 * @param integer $errorNumber The error number (0 ... 3)
	 * @return string the rendered error icon link
	 */
	public function render($errorNumber = 0) {
		$errorSymbols = array(
			'0' => '',
			'1' => \TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_WARNING,
			'2' => \TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_ERROR,
			'3' => \TYPO3\CMS\Backend\Template\DocumentTemplate::STATUS_ICON_ERROR
		);
		return $this->getDocInstance()->icons($errorSymbols[$errorNumber]);
	}

}

?>