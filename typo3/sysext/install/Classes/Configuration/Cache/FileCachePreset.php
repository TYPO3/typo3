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

use TYPO3\CMS\Core\Cache\Backend\FileBackend;
use TYPO3\CMS\Core\Cache\Backend\SimpleFileBackend;
use TYPO3\CMS\Install\Configuration\AbstractPreset;

class FileCachePreset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'File';

    /**
     * @var int Priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/caching/cacheConfigurations/hash/backend' => FileBackend::class,
        'SYS/caching/cacheConfigurations/pages/backend' => FileBackend::class,
        'SYS/caching/cacheConfigurations/pages/options/compression' => '__UNSET',
        'SYS/caching/cacheConfigurations/pagesection/backend' => FileBackend::class,
        'SYS/caching/cacheConfigurations/pagesection/options/compression' => '__UNSET',
        'SYS/caching/cacheConfigurations/imagesizes/backend' => SimpleFileBackend::class,
        'SYS/caching/cacheConfigurations/imagesizes/options/compression' => '__UNSET',
        'SYS/caching/cacheConfigurations/rootline/backend' => FileBackend::class,
        'SYS/caching/cacheConfigurations/rootline/options/compression' => '__UNSET',
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
