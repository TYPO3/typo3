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

use TYPO3\CMS\Core\Core\Environment;

/**
 * Preset for ImageMagick version 6 or higher
 * @internal only to be used within EXT:install
 */
class ImageMagick6Preset extends AbstractImagePreset
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
        'GFX/processor_enabled' => true,
        // processor_path and processor_path_lzw are determined and set by path lookup methods
        'GFX/processor_path' => '',
        'GFX/processor_path_lzw' => '',
        'GFX/processor' => 'ImageMagick',
        'GFX/processor_effects' => true,
        'GFX/processor_allowTemporaryMasksAsPng' => false,
        'GFX/processor_colorspace' => 'sRGB',
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
            if (Environment::isWindows()) {
                $executable = 'identify.exe';

                if (!@is_file($path . $executable)) {
                    $executable = 'magick.exe';
                }
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
