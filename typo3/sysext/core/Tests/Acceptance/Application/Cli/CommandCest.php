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

use Codeception\Example;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests the styleguide backend module can be loaded
 */
class CommandCest
{
    protected string $typo3Cli = '../../../../bin/typo3 ';

    /**
     * @dataProvider commandTestDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     */
    public function runCommand(ApplicationTester $I, Example $testData): void
    {
        $I->runShellCommand($this->typo3Cli . $testData['command'], false);
        $I->seeResultCodeIs($testData['code']);
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
            ['command' => 'cache:flush', 'code' => 0],
            ['command' => 'cache:warmup', 'code' => 0],
            ['command' => 'cleanup:flexforms', 'code' => 0],
            ['command' => 'cleanup:deletedrecords', 'code' => 0],
            ['command' => 'cleanup:multiplereferencedfiles --dry-run --update-refindex', 'code' => 0],
            ['command' => 'cleanup:lostfiles --dry-run --update-refindex', 'code' => 0],
            ['command' => 'cleanup:missingfiles --dry-run --update-refindex', 'code' => 0],
            ['command' => 'cleanup:missingrelations --dry-run --update-refindex', 'code' => 0],
            ['command' => 'cleanup:orphanrecords', 'code' => 0],
            ['command' => 'cleanup:previewlinks', 'code' => 0],
            ['command' => 'cleanup:versions', 'code' => 0],
            ['command' => 'extension:list', 'code' => 0],
            ['command' => 'extension:setup', 'code' => 0],
            ['command' => 'extension:deactivate workspaces', 'code' => 0],
            ['command' => 'extension:activate workspaces', 'code' => 0],
            ['command' => 'language:update', 'code' => 0],
            ['command' => 'mailer:spool:send', 'code' => 1],
            ['command' => 'redirects:checkintegrity', 'code' => 0],
            ['command' => 'redirects:cleanup', 'code' => 0],
            ['command' => 'referenceindex:update --check', 'code' => 0],
            ['command' => 'scheduler:run', 'code' => 0],
            ['command' => 'site:list', 'code' => 0],
            ['command' => 'site:show styleguide-demo-51', 'code' => 0],
            ['command' => 'syslog:list', 'code' => 0],
            ['command' => 'upgrade:list', 'code' => 0],
        ];
    }
}
