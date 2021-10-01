<?php

declare(strict_types=1);

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

namespace TYPO3\CMS\Core\Composer;

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
        if ($event->getComposer()->getPackage()->getName() === 'typo3/cms') {
            // We don't need the binary in Composer mode (as we have typo3/cms-cli providing it)
            $source = dirname(__DIR__, 2) . '/Resources/Private/Php/cli.php';
            $target = 'typo3/sysext/core/bin/typo3';
            $scriptDispatcher->addInstallerScript(new CliEntryPoint($source, $target));
        } else {
            // We don't need package artifact creation for our dev package/ TYPO3 classic mode
            $scriptDispatcher->addInstallerScript(new PackageArtifactBuilder());
        }
    }
}
