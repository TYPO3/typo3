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
