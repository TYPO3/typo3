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

namespace TYPO3\CMS\Core\Tests\Functional\Command;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractCommandTestCase extends FunctionalTestCase
{
    protected function executeConsoleCommand(string $cmdline, ...$args): array
    {
        $cmd = vsprintf(PHP_BINARY . ' ' . GeneralUtility::getFileAbsFileName('EXT:core/bin/typo3') . ' ' . $cmdline, array_map('escapeshellarg', $args));

        $output = '';

        $handle = popen($cmd, 'r');
        while (!feof($handle)) {
            $output .= fgets($handle, 4096);
        }
        $status = pclose($handle);

        return [
            'status' => $status,
            'output' => $output,
        ];
    }
}
