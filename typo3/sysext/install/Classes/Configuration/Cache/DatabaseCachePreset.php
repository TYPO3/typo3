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

namespace TYPO3\CMS\Install\Configuration\Cache;

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Install\Configuration\AbstractPreset;

class DatabaseCachePreset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Database';

    /**
     * @var int Priority of preset
     */
    protected $priority = 60;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/caching/cacheConfigurations/hash/backend' => Typo3DatabaseBackend::class,
        'SYS/caching/cacheConfigurations/pages/backend' => Typo3DatabaseBackend::class,
        'SYS/caching/cacheConfigurations/pages/options/compression' => 1,
        'SYS/caching/cacheConfigurations/pagesection/backend' => Typo3DatabaseBackend::class,
        'SYS/caching/cacheConfigurations/pagesection/options/compression' => 1,
        'SYS/caching/cacheConfigurations/imagesizes/backend' => Typo3DatabaseBackend::class,
        'SYS/caching/cacheConfigurations/imagesizes/options/compression' => 1,
        'SYS/caching/cacheConfigurations/rootline/backend' => Typo3DatabaseBackend::class,
        'SYS/caching/cacheConfigurations/rootline/options/compression' => 1,
    ];

    /**
     * Database is always enabled
     *
     * @return bool TRUE if sendmail path if set
     */
    public function isAvailable()
    {
        return true;
    }
}
