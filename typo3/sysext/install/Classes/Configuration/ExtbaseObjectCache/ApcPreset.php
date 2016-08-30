<?php
namespace TYPO3\CMS\Install\Configuration\ExtbaseObjectCache;

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
 * APC preset
 */
class ApcPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Apc';

    /**
     * @var int Priority of preset
     */
    protected $priority = 80;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/caching/cacheConfigurations/extbase_object' => [
            'frontend' => \TYPO3\CMS\Core\Cache\Frontend\VariableFrontend::class,
            'backend' => \TYPO3\CMS\Core\Cache\Backend\ApcBackend::class,
            'options' => [
                'defaultLifetime' => 0,
            ],
            'groups' => ['system']
        ]
    ];

    /**
     * APC preset is available if extension is loaded and at least ~5MB are free.
     *
     * @return bool TRUE
     */
    public function isAvailable()
    {
        $result = false;
        if (extension_loaded('apc')) {
            $memoryInfo = @apc_sma_info();
            $availableMemory = $memoryInfo['avail_mem'];

            // If more than 5MB free
            if ($availableMemory > (5 * 1024 * 1024)) {
                $result = true;
            }
        }
        return $result;
    }
}
