<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Oliver Hader <oliver@typo3.org>
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
 * Interface for classes which hook into tx_cms_layout and do additional tt_content_drawItem processing.
 *
 * @author	Oliver Hader <oliver@typo3.org>
 * @package TYPO3
 * @subpackage cms
 */
interface tx_cms_layout_tt_content_drawItemHook {
	/**
	 * Preprocesses the preview rendering of a content element.
	 *
	 * @param	tx_cms_layout		$parentObject: Calling parent object
	 * @param	boolean				$drawItem: Whether to draw the item using the default functionalities
	 * @param	string				$headerContent: Header content
	 * @param	string				$itemContent: Item content
	 * @param	array				$row: Record row of tt_content
	 * @return	void
	 */
	public function preProcess(tx_cms_layout &$parentObject, &$drawItem, &$headerContent, &$itemContent, array &$row);
}
?>