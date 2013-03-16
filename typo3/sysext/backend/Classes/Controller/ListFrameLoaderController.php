<?php
namespace TYPO3\CMS\Backend\Controller;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 1999-2013 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

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
	 * @todo Define visibility
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


?>