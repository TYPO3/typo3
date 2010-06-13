<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007-2010 Ingo Renner <ingo@typo3.org>
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
 * interface for classes which hook into tslib_content and do additional getData processing
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
interface tslib_content_getDataHook {

	/**
	 * Extends the getData()-Method of tslib_cObj to process more/other commands
	 *
	 * @param	string		full content of getData-request e.g. "TSFE:id // field:title // field:uid"
	 * @param	array		current field-array
	 * @param	string		currently examined section value of the getData request e.g. "field:title"
	 * @param	string		current returnValue that was processed so far by getData
	 * @param	tslib_cObj	parent content object
	 * @return	string		get data result
	 */
	public function getDataExtension($getDataString, array $fields, $sectionValue, $returnValue, tslib_cObj &$parentObject);

}


?>