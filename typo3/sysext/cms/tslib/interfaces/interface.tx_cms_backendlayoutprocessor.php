<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2012 Claus Due, Wildside A/S <claus@wildside.dk>
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
 * Interface for classes which hook into tx_cms_BackendLayout for processing layouts
 *
 * @author	Claus Due, Wildside A/S <claus@wildside.dk>
 * @package TYPO3
 * @subpackage cms
 */
interface tx_cms_BackendLayoutHook {
	/**
	 * Postprocesses a selected backend layout
	 *
	 * @param	integer		$id: Starting page id when parsing the rootline
	 * @param	array		$backendLayout: The backend layout which was detected from page id
	 * @return	void
	 */
	public function postProcessBackendLayout(&$id, &$backendLayout);

	/**
	 * Preprocesses the page id used to detect the backend layout record
	 * @param	integer		$id: Starting page id when parsing the rootline
	 * @return	void
	 */
	public function preProcessBackendLayoutPageUid(&$id);

	/**
	 * Postprocesses the colpos list
	 * @param	integer		$id: Starting page id when parsing he rootline
	 * @param	array		$tcaItems: The current set of colpos TCA items
	 * @param	t3lib_TCEForms	$tceForms: A back reference to the TCEforms object which generated the item list
	 * @return	void
	 */
	public function postProcessColumnPositionListItemsParsed(&$id, array &$tcaItems, t3lib_TCEForms &$tceForms);

	/**
	 * Allows manipulation of the colPos selector option values
	 * @param	array		$params: Parameters for the selector
	 * @return	void
	 */
	public function postProcessColumnPositionProcessingFunctionItems(array &$params);
}
?>
