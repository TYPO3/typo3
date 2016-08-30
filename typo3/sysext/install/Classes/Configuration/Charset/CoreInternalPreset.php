<?php
namespace TYPO3\CMS\Install\Configuration\Charset;

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
 * Internal core charset handling preset
 */
class CoreInternalPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'CoreInternal';

    /**
     * @var int Priority of preset
     */
    protected $priority = 20;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/t3lib_cs_convMethod' => '',
        'SYS/t3lib_cs_utils' => '',
    ];

    /**
     * Internal core handling is always available
     *
     * @return bool TRUE
     */
    public function isAvailable()
    {
        return true;
    }
}
