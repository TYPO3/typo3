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

namespace TYPO3\CMS\Install\Configuration\Context;

use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Install\Configuration\AbstractPreset;

/**
 * Live preset
 * @internal only to be used within EXT:install
 */
class LivePreset extends AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Live';

    /**
     * @var int Priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/debug' => false,
        'FE/debug' => false,
        'SYS/devIPmask' => '',
        'SYS/displayErrors' => 0,
        // Values below are not available in UI
        'LOG/TYPO3/CMS/deprecations/writerConfiguration/' . LogLevel::NOTICE . '/' . FileWriter::class . '/disabled' => true,
        // E_RECOVERABLE_ERROR
        'SYS/exceptionalErrors' => 4096,
    ];

    /**
     * Production preset is always available
     *
     * @return bool Always TRUE
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * If context is set to production, priority
     * of this preset is raised.
     *
     * @return int Priority of preset
     */
    public function getPriority()
    {
        $priority = $this->priority;
        if (Environment::getContext()->isProduction()) {
            $priority = $priority + 20;
        }
        return $priority;
    }
}
