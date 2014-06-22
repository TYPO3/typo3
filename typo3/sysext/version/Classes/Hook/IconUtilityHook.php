<?php
namespace TYPO3\CMS\Version\Hook;

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

use TYPO3\CMS\Core\Versioning\VersionState;

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
		if (
			isset($row['t3ver_state'])
			&& VersionState::cast($row['t3ver_state'])->equals(
				VersionState::DELETE_PLACEHOLDER
			)
		) {
			$status['deleted'] = TRUE;
		}
	}

}
