<?php
namespace TYPO3\CMS\Version\Hook;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Francois Suter (francois.suter@typo3.org)
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
 * Implements a hook for \TYPO3\CMS\Backend\Utility\IconUtility
 */
class IconUtilityHook {

	/**
	 * Visualizes the deleted status for a versionized record.
	 *
	 * @param string $table Name of the table
	 * @param array $row Record row containing the field values
	 * @param array $status Status to be used for rendering the icon
	 * @return void
	 */
	public function overrideIconOverlay($table, array $row, array &$status) {
		if (isset($row['t3ver_state']) && $row['t3ver_state'] == 2) {
			$status['deleted'] = TRUE;
		}
	}

}


?>