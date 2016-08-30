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
 * Preset for ImageMagick version 6 or higher
 */
class ImageMagick6Preset extends AbstractImagePreset implements Configuration\PresetInterface
{
    /**
     * @var string Name of preset
     */
    protected $name = 'ImageMagick6';

    /**
     * @var int Priority of preset
     */
    protected $priority = 70;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'GFX/image_processing' => 1,
        'GFX/im' => 1,
        // im_path and im_path_lzw are determined and set by path lookup methods
        'GFX/im_path' => '',
        'GFX/im_path_lzw' => '',
        'GFX/im_version_5' => 'im6',
        'GFX/im_v5effects' => 1,
        'GFX/im_mask_temp_ext_gif' => 1,
        'GFX/colorspace' => 'sRGB',
    ];

    /**
     * Find executable in path, wrapper for specific ImageMagick/GraphicsMagick find methods.
     *
     * @param array $searchPaths
     * @return mixed
     */
    protected function findExecutableInPath(array $searchPaths)
    {
        return $this->findImageMagick6InPaths($searchPaths);
    }

    /**
     * Search for GraphicsMagick executables in given paths.
     *
     * @param array $searchPaths List of paths to search for
     * @return bool TRUE if graphics magick was found in path
     */
    protected function findImageMagick6InPaths(array $searchPaths)
    {
        $result = false;
        foreach ($searchPaths as $path) {
            if (TYPO3_OS === 'WIN') {
                $executable = 'identify.exe';
            } else {
                $executable = 'identify';
            }
            if (@is_file($path . $executable)) {
                $command = escapeshellarg($path . $executable) . ' -version';
                $executingResult = false;
                \TYPO3\CMS\Core\Utility\CommandUtility::exec($command, $executingResult);
                // First line of exec command should contain string GraphicsMagick
                $firstResultLine = array_shift($executingResult);
                // Example: "Version: ImageMagick 6.6.0-4 2012-05-02 Q16 http://www.imagemagick.org"
                if (strpos($firstResultLine, 'ImageMagick') !== false) {
                    list(, $version) = explode('ImageMagick', $firstResultLine);
                    // Example: "6.6.0-4"
                    list($version) = explode(' ', trim($version));
                    if (version_compare($version, '6.0.0') >= 0) {
                        $this->foundPath = $path;
                        $result = true;
                        break;
                    }
                }
            }
        }
        return $result;
    }
}
