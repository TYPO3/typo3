<?php
namespace TYPO3\CMS\Install\Configuration\Image;

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

use TYPO3\CMS\Install\Configuration;

/**
 * Custom preset is a fallback if no other preset fits
 */
class CustomPreset extends Configuration\AbstractCustomPreset implements Configuration\CustomPresetInterface {

	/**
	 * @var array Configuration values handled by this preset
	 */
	protected $configurationValues = array(
		'GFX/image_processing' => 0,
		'GFX/im' => 0,
		'GFX/im_path' => '',
		'GFX/im_path_lzw' => '',
		'GFX/im_version_5' => '',
		'GFX/im_v5effects' => 0,
		'GFX/im_mask_temp_ext_gif' => 0,
		'GFX/colorspace' => '',
	);
}
