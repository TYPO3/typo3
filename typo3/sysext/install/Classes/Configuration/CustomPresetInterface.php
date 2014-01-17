<?php
namespace TYPO3\CMS\Install\Configuration;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Christian Kuhn <lolli@schwarzbu.ch>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Custom preset interface
 *
 * Interface for presets not caught by other presets.
 * Represents "custom" configuration options of a feature.
 *
 * There must be only one custom preset per feature!
 */
interface CustomPresetInterface extends PresetInterface {

	/**
	 * Mark preset as active.
	 * The custom features do not know by itself if they are
	 * active or not since the configuration options may overlay
	 * with other presets.
	 * Marking the custom preset as active is therefor taken care
	 * off by the feature itself if no other preset is active.
	 *
	 * @return void
	 */
	public function setActive();
}
