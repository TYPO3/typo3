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
use Composer\Util\Filesystem;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScripts\EntryPoint;
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

        $scriptDispatcher->addInstallerScript(
            // @todo: Add support to `typo3/cms-composer-installers` to create executable entry points
            new class($source, $target) extends EntryPoint {
                /** @var string */
                private $target;

                public function __construct(string $source, string $target)
                {
                    parent::__construct($source, $target);
                    $this->target = $target;
                }

                public function run(Event $event): bool
                {
                    parent::run($event);

                    $filesystem = new Filesystem();
                    $composer = $event->getComposer();
                    $pluginConfig = \TYPO3\CMS\Composer\Plugin\Config::load($composer);

                    $targetFile = $pluginConfig->get('web-dir') . '/' . $this->target;

                    @chmod($targetFile, 0755);

                    return true;
                }
            }
        );
    }
}
