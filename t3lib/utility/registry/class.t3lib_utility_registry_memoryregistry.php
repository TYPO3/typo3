<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Oliver Hader <oliver.hader@typo3.org>
 *
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
 * A class to store and retrieve entries in a registry in the memory.
 *
 * The intention is to have a place where we can store things (mainly settings)
 * that should live only for the current request.
 *
 * @author	Oliver Hader <oliver.hader@typo3.org>
 * @package TYPO3
 * @subpackage t3lib
 */
class t3lib_utility_registry_MemoryRegistry extends t3lib_utility_registry_AbstractRegistry implements t3lib_Singleton {

}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/utility/registry/class.t3lib_utility_registry_memoryregistry.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/utility/registry/class.t3lib_utility_registry_memoryregistry.php']);
}

?>