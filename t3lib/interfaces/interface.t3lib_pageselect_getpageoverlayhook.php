<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Tolleiv Nietsch <nietsch@aoemedia.de>
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
 * interface for classes which hook into t3lib_pageSelect
 *
 * @author	Tolleiv Nietsch <nietsch@aoemedia.de>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_pageSelect_getPageOverlayHook {

	/**
	 * enables to preprocess the pageoverlay
	 *
	 * @param array 			$pageInput	- the page record
	 * @param integer 			$lUid		- the overlay language
	 * @param t3lib_pageSelect	$parent		- the calling parent object
	 * @return void
	 */
	public function getPageOverlay_preProcess(&$pageInput, &$lUid, t3lib_pageSelect $parent);

}

?>
