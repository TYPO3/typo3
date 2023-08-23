<?php

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

namespace TYPO3\CMS\Install\Configuration\Image;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Utility\CommandUtility;

/**
 * Preset for GraphicsMagick
 * @internal only to be used within EXT:install
 */
class GraphicsMagickPreset extends AbstractImagePreset
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
        'GFX/processor_enabled' => true,
        // processor_path is determined and set by path lookup methods
        'GFX/processor_path' => '',
        'GFX/processor' => 'GraphicsMagick',
        'GFX/processor_effects' => false,
        'GFX/processor_allowTemporaryMasksAsPng' => false,
        'GFX/processor_colorspace' => 'RGB',
    ];

    /**
     * Find executable in path, wrapper for specific ImageMagick/GraphicsMagick find methods.
     *
     * @return mixed
     */
    protected function findExecutableInPath(array $searchPaths)
    {
        return $this->findGraphicsMagickInPaths($searchPaths);
    }

    /**
     * Search for GraphicsMagick executables in given paths.
     *
     * @param array $searchPaths List of paths to search for
     * @return bool TRUE if graphics magick was found in path
     */
    protected function findGraphicsMagickInPaths(array $searchPaths)
    {
        $result = false;
        foreach ($searchPaths as $path) {
            if (Environment::isWindows()) {
                $executable = 'gm.exe';
            } else {
                $executable = 'gm';
            }

            $binaryPath = $path . $executable;
            if (!file_exists($binaryPath) || !is_executable($binaryPath)) {
                continue;
            }

            $command = escapeshellarg($binaryPath) . ' -version';
            $executingResult = [];
            @CommandUtility::exec($command, $executingResult);
            // First line of exec command should contain string GraphicsMagick
            $firstResultLine = array_shift($executingResult);
            if (is_string($firstResultLine) && str_contains($firstResultLine, 'GraphicsMagick')) {
                $this->foundPath = $path;
                $result = true;
                break;
            }
        }

        return $result;
    }
}
