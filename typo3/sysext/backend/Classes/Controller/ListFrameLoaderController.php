<?php
namespace TYPO3\CMS\Backend\Controller;

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
 * Script Class for redirecting shortcut actions to the correct script
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @deprecated since TYPO3 CMS 7, this file will be removed in TYPO3 CMS 8, this logic is not needed anymore
 */
class ListFrameLoaderController {

	/**
	 * Main content generated
	 *
	 * @return void
	 * @deprecated since TYPO3 CMS 7, will be removed in TYPO3 CMS 8
	 */
	public function main() {
		\TYPO3\CMS\Core\Utility\GeneralUtility::logDeprecatedFunction();
		$GLOBALS['TBE_TEMPLATE']->divClass = '';
		$this->content .= $GLOBALS['TBE_TEMPLATE']->startPage('List Frame Loader');
		$this->content .= $GLOBALS['TBE_TEMPLATE']->wrapScriptTags('
			var theUrl = top.getModuleUrl("");
			if (theUrl)	window.location.href=theUrl;
		');
		// End page:
		$this->content .= $GLOBALS['TBE_TEMPLATE']->endPage();
		// Output:
		echo $this->content;
	}

}
