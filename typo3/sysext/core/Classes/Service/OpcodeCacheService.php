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

namespace TYPO3\CMS\Core\Service;

/**
 * Class with helper functions for clearing the PHP opcache.
 * It auto detects the opcache system and invalidates/resets it.
 * https://forge.typo3.org/issues/55252
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
            'OPcache' => [
                'active' => extension_loaded('Zend OPcache') && ini_get('opcache.enable') === '1',
                'version' => phpversion('Zend OPcache'),
                'warning' => self::isClearable() ? false : 'Either opcache_invalidate or opcache_reset are disabled in this installation. Clearing will not work.',
                'clearCallback' => static function ($fileAbsPath) {
                    if (self::isClearable()) {
                        if ($fileAbsPath !== null) {
                            opcache_invalidate($fileAbsPath);
                        } else {
                            opcache_reset();
                        }
                    }
                },
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

    /**
     * @return bool
     */
    protected static function isClearable(): bool
    {
        $disabled = explode(',', (string)ini_get('disable_functions'));
        return !(in_array('opcache_invalidate', $disabled, true) || in_array('opcache_reset', $disabled, true));
    }
}
