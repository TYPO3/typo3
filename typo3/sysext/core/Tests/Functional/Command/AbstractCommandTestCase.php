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

use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

abstract class AbstractCommandTestCase extends FunctionalTestCase
{
    /**
     * Upper bound in seconds for a single console command. Commands are expected
     * to finish in seconds, the limit only ensures that one which never returns
     * fails its test instead of stalling the whole test run.
     */
    protected float $consoleCommandTimeout = 300.0;

    protected function executeConsoleCommand(string $cmdline, ...$args): array
    {
        $cmd = vsprintf(PHP_BINARY . ' ' . GeneralUtility::getFileAbsFileName('EXT:core/bin/typo3') . ' ' . $cmdline, array_map('escapeshellarg', $args));

        // Process reads stdout and stderr simultaneously. Consuming one of them
        // to the end before the other one is read at all deadlocks as soon as a
        // command writes more to the pending pipe than its buffer holds, which
        // is why this must not be done with plain proc_open() and
        // stream_get_contents().
        $process = Process::fromShellCommandline($cmd, null, null, null, $this->consoleCommandTimeout);

        try {
            $process->run();
        } catch (ProcessTimedOutException) {
            self::fail(sprintf(
                'Command "%s" did not finish within %s seconds.' . PHP_EOL
                    . 'stdout: %s' . PHP_EOL
                    . 'stderr: %s',
                $cmd,
                $this->consoleCommandTimeout,
                $process->getOutput(),
                $process->getErrorOutput()
            ));
        }

        return [
            'status' => $process->getExitCode(),
            'stdout' => $process->getOutput(),
            'stderr' => $process->getErrorOutput(),
        ];
    }
}
