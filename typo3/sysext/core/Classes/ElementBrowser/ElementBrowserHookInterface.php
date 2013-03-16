<?php
namespace TYPO3\CMS\Core\ElementBrowser;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2007-2013 Ingo Renner <ingo@typo3.org>
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
 * Interface for classes which hook into browse_links
 *
 * @author Ingo Renner <ingo@typo3.org>
 */
interface ElementBrowserHookInterface {
	/**
	 * Initializes the hook object
	 *
	 * @param \TYPO3\CMS\Recordlist\Browser\ElementBrowser Parent browse_links object
	 * @param array Additional parameters
	 * @return void
	 */
	public function init($parentObject, $additionalParameters);

	/**
	 * Adds new items to the currently allowed ones and returns them
	 *
	 * @param array Currently allowed items
	 * @return array Currently allowed items plus added items
	 */
	public function addAllowedItems($currentlyAllowedItems);

	/**
	 * Modifies the menu definition and returns it
	 *
	 * @param array	Menu definition
	 * @return array Modified menu definition
	 */
	public function modifyMenuDefinition($menuDefinition);

	/**
	 * Returns a new tab for the browse links wizard
	 *
	 * @param string Current link selector action
	 * @return string A tab for the selected link action
	 */
	public function getTab($linkSelectorAction);

	/**
	 * Checks the current URL and determines what to do
	 *
	 * @param string $href
	 * @param string $siteUrl
	 * @param array $info
	 * @return array
	 */
	public function parseCurrentUrl($href, $siteUrl, $info);

}

?>