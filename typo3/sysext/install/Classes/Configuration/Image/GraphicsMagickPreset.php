<?php
namespace TYPO3\CMS\Install\Configuration\Image;

/*
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

use TYPO3\CMS\Install\Configuration;

/**
 * Preset for GraphicsMagick
 */
class GraphicsMagickPreset extends AbstractImagePreset implements Configuration\PresetInterface
{
    /**
     * @var string Name of preset
     */
    protected $name = 'GraphicsMagick';

    /**
     * @var int Priority of preset
     */
    protected $priority = 80;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'GFX/image_processing' => 1,
        'GFX/im' => 1,
        // im_path and im_path_lzw are determined and set by path lookup methods
        'GFX/im_path' => '',
        'GFX/im_path_lzw' => '',
        'GFX/im_version_5' => 'gm',
        'GFX/im_v5effects' => -1,
        'GFX/im_mask_temp_ext_gif' => 1,
        'GFX/colorspace' => 'RGB',
    ];

    /**
     * Find executable in path, wrapper for specific ImageMagick/GraphicsMagick find methods.
     *
     * @param array $searchPaths
     * @return mixed
     */
    protected function findExecutableInPath(array $searchPaths)
    {
        return $this->findGraphicsMagickInPaths($searchPaths);
    }

    /**
     * Search for GraphicsMagick executables in given paths.
     *
     * @param array $searchPaths List of pathes to search for
     * @return bool TRUE if graphics magick was found in path
     */
    protected function findGraphicsMagickInPaths(array $searchPaths)
    {
        $result = false;
        foreach ($searchPaths as $path) {
            if (TYPO3_OS === 'WIN') {
                $executable = 'gm.exe';
            } else {
                $executable = 'gm';
            }
            if (@is_file($path . $executable)) {
                $command = escapeshellarg($path . $executable) . ' -version';
                $executingResult = false;
                \TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $executingResult);
                // First line of exec command should contain string GraphicsMagick
                $firstResultLine = array_shift($executingResult);
                if (strpos($firstResultLine, 'GraphicsMagick') !== false) {
                    $this->foundPath = $path;
                    $result = true;
                    break;
                }
            }
        }
        return $result;
    }
}
