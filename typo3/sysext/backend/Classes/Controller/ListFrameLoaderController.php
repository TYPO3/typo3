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
 */
class ListFrameLoaderController {

	/**
	 * Main content generated
	 *
	 * @return void
	 */
	public function main() {
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
