<?php
namespace TYPO3\CMS\Dbal;

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
 * Cache engine helper for generated queries.
 */
class QueryCache
{
    /**
     * Returns a proper cache key.
     *
     * @param mixed $config
     * @return void
     */
    public static function getCacheKey($config)
    {
        if (is_array($config)) {
            return md5(serialize($config));
        } else {
            return $config;
        }
    }
}
