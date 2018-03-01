<?php
namespace TYPO3\CMS\Install\Configuration\Context;

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

use TYPO3\CMS\Core\Log\LogLevel;
use TYPO3\CMS\Core\Log\Writer\FileWriter;
use TYPO3\CMS\Install\Configuration;

/**
 * Debug preset
 * @internal only to be used within EXT:install
 */
class DebugPreset extends Configuration\AbstractPreset
{
    /**
     * @var string Name of preset
     */
    protected $name = 'Debug';

    /**
     * @var int Priority of preset
     */
    protected $priority = 50;

    /**
     * @var array Configuration values handled by this preset
     */
    protected $configurationValues = [
        'BE/debug' => true,
        'FE/debug' => true,
        'SYS/devIPmask' => '*',
        'SYS/displayErrors' => 1,
        'SYS/systemLogLevel' => 0,
        // Values below are not available in UI
        'LOG/TYPO3/CMS/deprecations/writerConfiguration/' . LogLevel::NOTICE . '/' . FileWriter::class . '/disabled' => false,
        // E_WARNING | E_RECOVERABLE_ERROR | E_DEPRECATED
        'SYS/exceptionalErrors' => 12290,
    ];

    /**
     * Development preset is always available
     *
     * @return bool Always TRUE
     */
    public function isAvailable()
    {
        return true;
    }

    /**
     * If context is set to development, priority
     * of this preset is raised.
     *
     * @return int Priority of preset
     */
    public function getPriority()
    {
        $context = \TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext();
        $priority = $this->priority;
        if ($context->isDevelopment()) {
            $priority = $priority + 20;
        }
        return $priority;
    }
}
