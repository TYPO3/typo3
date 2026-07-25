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
use TYPO3\CMS\Composer\Plugin\Config;
use TYPO3\CMS\Composer\Plugin\Core\InstallerScript;
use TYPO3\CMS\Core\Utility\ArrayUtility;

final class FrameworkPackageWriter implements InstallerScript
{
    private const string CORE_RESOURCE_PATH = '/typo3/sysext/core/Resources/Private/Php/framework-packages.php';

    public function run(Event $event): bool
    {
        $config = Config::load($event->getComposer(), $event->getIO());
        $basePath = $config->get('base-dir');
        $frameworkPackageNames = $this->getFrameworkPackageNames($config);
        $io = $event->getIO();
        $io->writeError('TYPO3: Dumping framework package names', true, $io::VERBOSE);
        file_put_contents(
            $basePath . self::CORE_RESOURCE_PATH,
            '<?php'
            . chr(10)
            . chr(10)
            . 'return '
            . ArrayUtility::arrayExport($frameworkPackageNames)
            . ';'
            . chr(10)
        );
        return true;
    }

    /**
     * @return string[]
     */
    private function getFrameworkPackageNames(Config $config): array
    {
        $typo3Json = file_get_contents($config->get('base-dir') . '/composer.json');
        if ($typo3Json === false) {
            throw new \RuntimeException('The main TYPO3 composer.json file was not found.', 1774091461);
        }
        return array_keys(
            array_filter(
                json_decode($typo3Json, true, 512, JSON_THROW_ON_ERROR)['replace'] ?? [],
                static fn($value) => $value === 'self.version'
            )
        );
    }
}
