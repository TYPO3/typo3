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

namespace TYPO3\CMS\Backend\Composer;

use Composer\Script\Event;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\EntryPoint;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScriptsRegistration;
use TYPO3\CMS\Composer\Plugin\Core\ScriptDispatcher;

/**
 * Hook into Composer build to generate TYPO3 backend entry script
 *
 * @internal this is used internally for TYPO3 core only
 */
class InstallerScripts implements InstallerScriptsRegistration
{
    public static function register(Event $event, ScriptDispatcher $scriptDispatcher)
    {
        $extra = $event->getComposer()->getPackage()->getExtra();
        $installDeprecatedTypo3IndexPhp = $extra['typo3/cms']['install-deprecated-typo3-index-php'] ?? true;
        if (!$installDeprecatedTypo3IndexPhp) {
            return;
        }
        $scriptDispatcher->addInstallerScript(
            new EntryPoint(
                dirname(__DIR__, 2) . '/Resources/Private/Php/legacy-backend.php',
                'typo3/index.php'
            )
        );
    }
}
