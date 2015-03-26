<?php
namespace TYPO3\CMS\Beuser\ViewHelpers;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;

/**
 * Edit Record ViewHelper, see FormEngine logic
 *
 * @internal
 */
class EditRecordViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * Returns a URL to link to FormEngine
	 *
	 * @param string $parameters Is a set of GET params to send to FormEngine
	 * @return string URL to FormEngine module + parameters
	 * @see \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleUrl()
	 */
	public function render($parameters) {
		$parameters = \TYPO3\CMS\Core\Utility\GeneralUtility::explodeUrl2Array($parameters);
		return BackendUtility::getModuleUrl('record_edit', $parameters);
	}

}
