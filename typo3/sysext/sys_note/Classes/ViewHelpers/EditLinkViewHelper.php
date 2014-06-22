<?php
namespace TYPO3\CMS\SysNote\ViewHelpers;

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
 * ViewHelper to create a link to edit a note
 *
 * @author Georg Ringer <typo3@ringerge.org>
 */
class EditLinkViewHelper extends \TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper {

	/**
	 * @param integer $id
	 * @return string
	 */
	public function render($id) {
		$returnUrl = \TYPO3\CMS\Core\Utility\GeneralUtility::getIndpEnv('REQUEST_URI');
		return $GLOBALS['BACK_PATH'] . 'alt_doc.php?&edit[sys_note][' . $id . ']=edit&returnUrl=' . rawurlencode($returnUrl);
	}

}
