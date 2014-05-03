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
 * Preset for GraphicsMagick
 */
class GraphicsMagickPreset extends AbstractImagePreset implements Configuration\PresetInterface {

	/**
	 * @var string Name of preset
	 */
	protected $name = 'GraphicsMagick';

	/**
	 * @var integer Priority of preset
	 */
	protected $priority = 80;

	/**
	 * @var array Configuration values handled by this preset
	 */
	protected $configurationValues = array(
		'GFX/image_processing' => 1,
		'GFX/im' => 1,
		// im_path and im_path_lzw are determined and set by path lookup methods
		'GFX/im_path' => '',
		'GFX/im_path_lzw' => '',
		'GFX/im_version_5' => 'gm',
		'GFX/im_v5effects' => -1,
		'GFX/im_mask_temp_ext_gif' => 1,
		'GFX/colorspace' => 'RGB',
	);

	/**
	 * Find executable in path, wrapper for specific ImageMagick/GraphicsMagick find methods.
	 *
	 * @param array $searchPaths
	 * @return mixed
	 */
	protected function findExecutableInPath(array $searchPaths) {
		return $this->findGraphicsMagickInPaths($searchPaths);
	}

	/**
	 * Search for GraphicsMagick executables in given paths.
	 *
	 * @param array $searchPaths List of pathes to search for
	 * @return boolean TRUE if graphics magick was found in path
	 */
	protected function findGraphicsMagickInPaths(array $searchPaths) {
		$result = FALSE;
		foreach ($searchPaths as $path) {
			if (TYPO3_OS === 'WIN') {
				$executable = 'gm.exe';
			} else {
				$executable = 'gm';
			}
			if (@is_file($path . $executable)) {
				$command = escapeshellarg($path . $executable) . ' -version';
				$executingResult = FALSE;
				\TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $executingResult);
				// First line of exec command should contain string GraphicsMagick
				$firstResultLine = array_shift($executingResult);
				if (strpos($firstResultLine, 'GraphicsMagick') !== FALSE) {
					$this->foundPath = $path;
					$result = TRUE;
					break;
				}
			}
		}
		return $result;
	}
}
