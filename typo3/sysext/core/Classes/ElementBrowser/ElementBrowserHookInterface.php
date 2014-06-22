<?php
namespace TYPO3\CMS\Core\ElementBrowser;

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
