<?php
namespace TYPO3\CMS\Core\Resource\Utility;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Frans Saris <franssaris (at) gmail.com>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Some Backend Utility functions for working with resources
 */
class BackendUtility {

	/**
	 * Create a flash message for a file that is marked as missing
	 *
	 * @param \TYPO3\CMS\Core\Resource\AbstractFile $file
	 * @return \TYPO3\CMS\Core\Messaging\FlashMessage
	 */
	static public function getFlashMessageForMissingFile(\TYPO3\CMS\Core\Resource\AbstractFile $file) {
		/** @var \TYPO3\CMS\Core\Messaging\FlashMessage $flashMessage */
		$flashMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage',
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing_text') .
			' <abbr title="' . htmlspecialchars($file->getStorage()->getName() . ' :: '.$file->getIdentifier()) . '">' .
			htmlspecialchars($file->getName()) . '</abbr>',
			$GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xlf:warning.file_missing'),
			\TYPO3\CMS\Core\Messaging\FlashMessage::ERROR
		);

		return $flashMessage;
	}

}
