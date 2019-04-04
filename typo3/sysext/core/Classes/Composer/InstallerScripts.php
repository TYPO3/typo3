<?php
declare(strict_types = 1);
namespace TYPO3\CMS\Core\Composer;

/*
 * This file is part of the TYPO3 project.
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

use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScriptsRegistration;
use TYPO3\CMS\Composer\Plugin\Core\ScriptDispatcher;

/**
 * Hook into Composer build to generate TYPO3 cli tool entry script
 * @internal only used for TYPO3 internally for setting up the installation.
 */
class InstallerScripts implements InstallerScriptsRegistration
{
    /**
     * @param Event $event
     * @param ScriptDispatcher $scriptDispatcher
     */
    public static function register(Event $event, ScriptDispatcher $scriptDispatcher)
    {
        $source = dirname(__DIR__, 2) . '/Resources/Private/Php/cli.php';
        $target = 'typo3/sysext/core/bin/typo3';

        $scriptDispatcher->addInstallerScript(new CliEntryPoint($source, $target));
    }
}
