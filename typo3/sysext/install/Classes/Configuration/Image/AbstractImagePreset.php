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
 * Abstract class implements common image preset code
 */
abstract class AbstractImagePreset extends Configuration\AbstractPreset
{
    /**
     * @var array Default paths to search for executable, with trailing slash
     */
    protected $defaultExecutableSearchPaths = [
        '/usr/local/bin/',
        '/opt/local/bin/',
        '/usr/bin/',
        '/usr/X11R6/bin/',
        '/opt/bin/',
        'C:/php/ImageMagick/',
        'C:/php/GraphicsMagick/',
        'C:/apache/ImageMagick/',
        'C:/apache/GraphicsMagick/',
    ];

    /**
     * @var string Absolute path with found executable
     */
    protected $foundPath = '';

    /**
     * Path where executable was found
     *
     * @return string Fonud path
     */
    public function getFoundPath()
    {
        return $this->foundPath;
    }

    /**
     * Check is preset is currently active on the system.
     * Overwrites parent method to ignore im_path and im_path_lzw settings
     *
     * @return bool TRUE if preset is active
     */
    public function isActive()
    {
        $isActive = true;
        foreach ($this->configurationValues as $configurationKey => $configurationValue) {
            if ($configurationKey !== 'GFX/im_path'
                && $configurationKey !== 'GFX/im_path_lzw'
            ) {
                $currentValue = $this->configurationManager->getConfigurationValueByPath($configurationKey);
                if ($currentValue !== $configurationValue) {
                    $isActive = false;
                    break;
                }
            }
        }
        return $isActive;
    }

    /**
     * Find out if GraphicsMagick is available
     *
     * @return bool TRUE if GraphicsMagick executable is found in path
     */
    public function isAvailable()
    {
        $searchPaths = $this->getSearchPaths();
        return $this->findExecutableInPath($searchPaths);
    }

    /**
     * Get configuration values to activate prefix
     *
     * @return array Configuration values needed to activate prefix
     */
    public function getConfigurationValues()
    {
        $this->findExecutableInPath($this->getSearchPaths());
        $configurationValues = $this->configurationValues;
        $configurationValues['GFX/im_path'] = $this->getFoundPath();
        $configurationValues['GFX/im_path_lzw'] = $this->getFoundPath();
        return $configurationValues;
    }

    /**
     * Find executable in path, wrapper for specific ImageMagick/GraphicsMagick find methods.
     *
     * @param array $searchPaths
     * @return mixed
     */
    abstract protected function findExecutableInPath(array $searchPaths);

    /**
     * Get list of paths to search for image handling executables
     *
     * @return array List of pathes to search for
     */
    protected function getSearchPaths()
    {
        $searchPaths = $this->defaultExecutableSearchPaths;

        // Add configured im_path on top
        $imPath = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
        if ((string)$imPath !== '' && !in_array($imPath, $searchPaths)) {
            $path = $this->cleanUpPath($imPath);
            array_unshift($searchPaths, $path);
        }

        // Add configured im_path_lzw on top
        $imLzwSearchPath = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'];
        if ((string)$imLzwSearchPath !== '' && !in_array($imLzwSearchPath, $searchPaths)) {
            $path = $this->cleanUpPath($imLzwSearchPath);
            array_unshift($searchPaths, $path);
        }

        // Add additional search path from form if given
        if (isset($this->postValues['additionalSearchPath'])
            && (string)$this->postValues['additionalSearchPath'] !== ''
            && !in_array($this->postValues['additionalSearchPath'], $searchPaths)
        ) {
            $path = $this->cleanUpPath($this->postValues['additionalSearchPath']);
            array_unshift($searchPaths, $path);
        }

        return $searchPaths;
    }

    /**
     * Consolidate between Windows and Unix and add trailing slash im missing
     *
     * @param string $path Given path
     * @return string Cleaned up path
     */
    protected function cleanUpPath($path)
    {
        $path = \TYPO3\CMS\Core\Utility\GeneralUtility::fixWindowsFilePath($path);
        // Add trailing slash if missing
        if (!preg_match('/[\\/]$/', $path)) {
            $path .= '/';
        }
        return $path;
    }
}
