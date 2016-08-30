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

use TYPO3\CMS\Install\Configuration;

/**
 * Debug preset
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
        'SYS/enableDeprecationLog' => 'file',
        'SYS/sqlDebug' => 1,
        'SYS/systemLogLevel' => 0,
        // E_WARNING | E_RECOVERABLE_ERROR | E_DEPRECATED | E_USER_DEPRECATED
        'SYS/exceptionalErrors' => 28674,
        'SYS/clearCacheSystem' => true,
    ];

    /**
     * Development preset is always available
     *
     * @return bool TRUE if mbstring PHP module is loaded
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
