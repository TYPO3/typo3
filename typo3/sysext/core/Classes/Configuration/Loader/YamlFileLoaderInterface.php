<?php
namespace TYPO3\CMS\Core\Configuration\Loader;

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

/**
 * Interface for YAML file loaders
 */
interface YamlFileLoaderInterface
{
    /**
     * Loads and parses a YAML file, returns an array with the data found
     *
     * @param mixed $file
     * @return array the configuration as array
     */
    public function load($file): array;

    /**
     * Parses a string as YAML, returns an array with the data found
     *
     * @param string $content
     * @return array the configuration as array
     */
    public function loadFromContent(string $content): array;
}
