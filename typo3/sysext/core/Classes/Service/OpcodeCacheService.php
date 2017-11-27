<?php
namespace TYPO3\CMS\Core\Service;

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
 * Class with helper functions for clearing the PHP opcache.
 * It auto detects the opcache system and invalidates/resets it.
 * http://forge.typo3.org/issues/55252
 * Supported opcaches are: OPcache >= 7.0 (PHP 5.5)
 */
class OpcodeCacheService
{
    /**
     * Returns all supported and active opcaches
     *
     * @return array Array filled with supported and active opcaches
     */
    public function getAllActive()
    {
        $supportedCaches = [
            // The ZendOpcache aka OPcache since PHP 5.5
            // http://php.net/manual/de/book.opcache.php
            'OPcache' => [
                'active' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1',
                'version' => phpversion('Zend OPcache'),
                'canReset' => true, // opcache_reset() ... it seems that it doesn't reset for current run.
                // From documentation this function exists since first version (7.0.0) but from Changelog
                // this function exists since OPcache 7.0.2
                // http://pecl.php.net/package-changelog.php?package=ZendOpcache&release=7.0.2
                // PHP 7.0 onward is delivered minimum OPcache 7.0.6-dev
                'canInvalidate' => true,
                'error' => false,
                'clearCallback' => function ($fileAbsPath) {
                    if ($fileAbsPath !== null) {
                        opcache_invalidate($fileAbsPath);
                    } else {
                        opcache_reset();
                    }
                }
            ],
        ];

        $activeCaches = [];
        foreach ($supportedCaches as $opcodeCache => $properties) {
            if ($properties['active']) {
                $activeCaches[$opcodeCache] = $properties;
            }
        }
        return $activeCaches;
    }

    /**
     * Clears a file from an opcache, if one exists.
     *
     * @param string|null $fileAbsPath The file as absolute path to be cleared or NULL to clear completely.
     */
    public function clearAllActive($fileAbsPath = null)
    {
        foreach ($this->getAllActive() as $properties) {
            $callback = $properties['clearCallback'];
            $callback($fileAbsPath);
        }
    }
}
