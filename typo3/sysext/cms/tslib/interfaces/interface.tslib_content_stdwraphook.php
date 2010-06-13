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
 * interface for classes which hook into tslib_content and do additional stdWrap processing
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
interface tslib_content_stdWrapHook {

	/**
	 * Hook for modifying $content before core's stdWrap does anything
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapPreProcess($content, array $configuration, tslib_cObj &$parentObject);

	/**
	 * Hook for modifying $content after core's stdWrap has processed setContentToCurrent, setCurrent, lang, data, field, current, cObject, numRows, filelist and/or preUserFunc
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapOverride($content, array $configuration, tslib_cObj &$parentObject);

	/**
	 * Hook for modifying $content after core's stdWrap has processed override, preIfEmptyListNum, ifEmpty, ifBlank, listNum, trim and/or more (nested) stdWraps
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript "stdWrap properties".
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapProcess($content, array $configuration, tslib_cObj &$parentObject);

	/**
	 * Hook for modifying $content after core's stdWrap has processed anything but debug
	 *
	 * @param	string		input value undergoing processing in this function. Possibly substituted by other values fetched from another source.
	 * @param	array		TypoScript stdWrap properties
	 * @param	tslib_cObj	parent content object
	 * @return	string		further processed $content
	 */
	public function stdWrapPostProcess($content, array $configuration, tslib_cObj &$parentObject);

}

?>