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
 * Supported opcaches are: OPcache >= 7.0 (PHP 5.5), WinCache, XCache >= 3.0.1
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
        $xcVersion = phpversion('xcache');

        $supportedCaches = [
            // The ZendOpcache aka OPcache since PHP 5.5
            // http://php.net/manual/de/book.opcache.php
            'OPcache' => [
                'active' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1',
                'version' => phpversion('Zend OPcache'),
                'canReset' => true, // opcache_reset() ... it seems that it doesn't reset for current run.
                // From documentation this function exists since first version (7.0.0) but from Changelog
                // this function exists since 7.0.2
                // http://pecl.php.net/package-changelog.php?package=ZendOpcache&release=7.0.2
                'canInvalidate' => function_exists('opcache_invalidate'),
                'error' => false,
                'clearCallback' => function ($fileAbsPath) {
                    if ($fileAbsPath !== null && function_exists('opcache_invalidate')) {
                        opcache_invalidate($fileAbsPath);
                    } else {
                        opcache_reset();
                    }
                }
            ],

            // http://www.php.net/manual/de/book.wincache.php
            'WinCache' => [
                'active' => extension_loaded('wincache') && ini_get('wincache.ocenabled') === '1'
                    && version_compare(phpversion('wincache'), '2.0.0.0', '<'),
                'version' => phpversion('wincache'),
                'canReset' => true,
                'canInvalidate' => true, // wincache_refresh_if_changed()
                'error' => false,
                'clearCallback' => function ($fileAbsPath) {
                    if ($fileAbsPath !== null) {
                        wincache_refresh_if_changed([$fileAbsPath]);
                    } else {
                        // No argument means refreshing all.
                        wincache_refresh_if_changed();
                    }
                }
            ],

            // http://xcache.lighttpd.net/
            'XCache' => [
                'active' => extension_loaded('xcache'),
                'version' => $xcVersion,
                'canReset' => !ini_get('xcache.admin.enable_auth'), // xcache_clear_cache()
                'canInvalidate' => false,
                'error' => false,
                'clearCallback' => function ($fileAbsPath) {
                    if (!ini_get('xcache.admin.enable_auth')) {
                        xcache_clear_cache(XC_TYPE_PHP);
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
     * @param string|NULL $fileAbsPath The file as absolute path to be cleared or NULL to clear completely.
     *
     * @return void
     */
    public function clearAllActive($fileAbsPath = null)
    {
        foreach ($this->getAllActive() as $properties) {
            $callback = $properties['clearCallback'];
            $callback($fileAbsPath);
        }
    }
}
