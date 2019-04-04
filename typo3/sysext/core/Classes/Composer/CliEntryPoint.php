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
use Composer\Util\Filesystem as FilesystemUtility;
use Symfony\Component\Filesystem\Filesystem;
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;

class CliEntryPoint implements InstallerScript
{
    /**
     * Absolute path to entry script source
     *
     * @var string
     */
    private $source;

    /**
     * The target file relative to the web directory
     *
     * @var string
     */
    private $target;

    public function __construct(string $source, string $target)
    {
        $this->source = $source;
        $this->target = $target;
    }

    public function run(Event $event): bool
    {
        $composer = $event->getComposer();
        $filesystemUtility = new FilesystemUtility();
        $filesystem = new Filesystem();
        $pluginConfig = Config::load($composer);

        $entryPointContent = file_get_contents($this->source);
        $targetFile = $pluginConfig->get('root-dir') . '/' . $this->target;
        $autoloadFile = $composer->getConfig()->get('vendor-dir') . '/autoload.php';

        $entryPointContent = preg_replace(
            '/__DIR__ . \'[^\']*\'/',
            $filesystemUtility->findShortestPathCode($targetFile, $autoloadFile),
            $entryPointContent
        );

        $filesystemUtility->ensureDirectoryExists(dirname($targetFile));
        $filesystem->dumpFile($targetFile, $entryPointContent);
        $filesystem->chmod($targetFile, 0755);

        return $filesystem->exists($targetFile);
    }
}
