<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Steffen Kamper <info@sk-typo3.de>
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
 * interface for classes which hook into ClearCacheMenu and manipulate CacheMenuItems array
 *
 * @author	Steffen Kamper <info@sk-typo3.de>
 * @package TYPO3
 * @subpackage backend
 */
interface backend_cacheActionsHook {

	/**
	 * modifies CacheMenuItems array
	 *
	 * @param	array	array of CacheMenuItems
	 * @param	array	array of AccessConfigurations-identifiers (typically  used by userTS with options.clearCache.identifier)
	 * @return
	 */
	public function manipulateCacheActions(&$cacheActions, &$optionValues);

}

?>
