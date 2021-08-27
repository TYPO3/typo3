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

namespace TYPO3\CMS\Core\Tests\Acceptance\Application\Cli;

use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests the styleguide backend module can be loaded
 */
class CommandCest
{
    protected string $typo3Cli = '../../../../bin/typo3 ';

    /**
     * @param ApplicationTester $I
     */
    public function runCommand(ApplicationTester $I): void
    {
        foreach ($this->commandTestDataProvider() as $command => $expectedCode) {
            $I->runShellCommand($this->typo3Cli . $command, false);
            $I->seeResultCodeIs($expectedCode);
        }
    }

    /**
     * Test cli commands for their exit status
     *
     * 'site:show' and 'mailer:spool:send' fail
     * due to missing configuration or unpredictable
     * params.
     */
    protected function commandTestDataProvider(): array
    {
        return [
            'cache:flush' => 0,
            'cache:warmup' => 0,
            'cleanup:flexforms' => 0,
            'cleanup:deletedrecords' => 0,
            'cleanup:multiplereferencedfiles --dry-run --update-refindex' => 0,
            'cleanup:lostfiles --dry-run --update-refindex' => 0,
            'cleanup:missingfiles --dry-run --update-refindex' => 0,
            'cleanup:missingrelations --dry-run --update-refindex' => 0,
            'cleanup:orphanrecords' => 0,
            'cleanup:previewlinks' => 0,
            'cleanup:versions' => 0,
            'extension:list' => 0,
            'extension:setup' => 0,
            'extension:deactivate workspaces' => 0,
            'extension:activate workspaces' => 0,
            'language:update' => 0,
            'mailer:spool:send' => 1,
            'redirects:checkintegrity' => 0,
            'redirects:cleanup' => 0,
            'referenceindex:update --check' => 0,
            'scheduler:run' => 0,
            'site:list' => 0,
            'site:show' => 1,
            'syslog:list' => 0,
            'upgrade:list' => 0,
        ];
    }
}
