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

interface t3lib_pageSelect_getRecordOverlayHook {

	/**
	 * Enables to preprocess a record overlay
	 *
	 * @param string $table
	 * @param array $row
	 * @param integer $sys_language_content
	 * @param string $OLmode
	 * @param t3lib_pageSelect $parent
	 */
	public function getRecordOverlay_preProcess($table, &$row, &$sys_language_content, $OLmode, t3lib_pageSelect $parent);

	/**
	 * Enables to postprocess a record overlay
	 *
	 * @param string $table
	 * @param array $row
	 * @param integer $sys_language_content
	 * @param string $OLmode
	 * @param t3lib_pageSelect $parent
	 */
	public function getRecordOverlay_postProcess($table, &$row, &$sys_language_content, $OLmode, t3lib_pageSelect $parent);

}

?>
