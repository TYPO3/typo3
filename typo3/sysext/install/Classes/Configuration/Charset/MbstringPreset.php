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
 * Mbstring charset preset
 */
class MbstringPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Mbstring';

    /**
     * @var int Priority of preset
     */
    protected $priority = 90;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/t3lib_cs_convMethod' => 'mbstring',
        'SYS/t3lib_cs_utils' => 'mbstring',
    ];

    /**
     * Check if mbstring PHP module is loaded
     *
     * @return bool TRUE if mbstring PHP module is loaded
     */
    public function isAvailable()
    {
        $result = false;
        if (extension_loaded('mbstring')) {
            $result = true;
        }
        return $result;
    }
}
