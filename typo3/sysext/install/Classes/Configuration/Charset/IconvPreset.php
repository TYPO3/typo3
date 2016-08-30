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
 * Iconv charset preset
 */
class IconvPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Iconv';

    /**
     * @var int Priority of preset
     */
    protected $priority = 80;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'SYS/t3lib_cs_convMethod' => 'iconv',
        'SYS/t3lib_cs_utils' => 'iconv',
    ];

    /**
     * Check if iconv PHP module is loaded
     *
     * @return bool TRUE if iconv PHP module is loaded
     */
    public function isAvailable()
    {
        $result = false;
        if (extension_loaded('iconv')) {
            $result = true;
        }
        return $result;
    }
}
