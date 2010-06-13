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
 * interface for classes which hook into browse_links
 *
 * @author	Ingo Renner <ingo@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
interface t3lib_browseLinksHook {

	/**
	 * initializes the hook object
	 *
	 * @param	browse_links	parent browse_links object
	 * @param	array		additional parameters
	 * @return	void
	 */
	public function init($parentObject, $additionalParameters);

	/**
	 * adds new items to the currently allowed ones and returns them
	 *
	 * @param	array	currently allowed items
	 * @return	array	currently allowed items plus added items
	 */
	public function addAllowedItems($currentlyAllowedItems);

	/**
	 * modifies the menu definition and returns it
	 *
	 * @param	array	menu definition
	 * @return	array	modified menu definition
	 */
	public function modifyMenuDefinition($menuDefinition);

	/**
	 * returns a new tab for the browse links wizard
	 *
	 * @param	string		current link selector action
	 * @return	string		a tab for the selected link action
	 */
	public function getTab($linkSelectorAction);

	/**
	 * checks the current URL and determines what to do
	 *
	 * @param	unknown_type		$href
	 * @param	unknown_type		$siteUrl
	 * @param	unknown_type		$info
	 * @return	unknown_type
	 */
	public function parseCurrentUrl($href, $siteUrl, $info);

}

?>