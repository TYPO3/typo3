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
        $handle = proc_open(
            $cmd,
            [
                // For details, see https://www.php.net/manual/en/function.proc-open
                ['pipe', 'r'], // stdin is a pipe that the child will read from
                ['pipe', 'w'], // stdout is a pipe that the child will write to
                ['pipe', 'w'], // stderr is a pipe that the child will write to
            ],
            $pipes
        );

        if (!is_resource($handle)) {
            throw new \Exception('Failed to create proc_open handle', 1700678064);
        }

        fclose($pipes[0]);
        $stdout = stream_get_contents($pipes[1]);
        fclose($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[2]);

        $status = proc_close($handle);

        return [
            'status' => $status,
            'stdout' => $stdout,
            'stderr' => $stderr,
        ];
    }
}
