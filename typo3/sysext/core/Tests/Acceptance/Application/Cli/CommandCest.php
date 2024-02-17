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
use Codeception\Scenario;
use TYPO3\CMS\Core\Tests\Acceptance\Support\ApplicationTester;

/**
 * Tests the styleguide backend module can be loaded
 */
class CommandCest
{
    /**
     * @dataProvider commandTestDataProvider
     * @param ApplicationTester $I
     * @param Example $testData
     * @param Scenario $scenario
     */
    public function runCommand(ApplicationTester $I, Example $testData, Scenario $scenario): void
    {
        $isComposerMode = str_contains($scenario->current('env'), 'composer');
        $binDir = $isComposerMode ? 'vendor/bin' : '../../../../bin';
        if ($isComposerMode && $testData['skipComposer'] ?? false) {
            $scenario->skip('This test is skipped in composer mode');
            return;
        }
        $I->runShellCommand(sprintf('%s/typo3 %s', $binDir, $testData['command']), false);
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
            ['command' => 'cache:flush', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cache:warmup', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:flexforms', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:deletedrecords', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:multiplereferencedfiles --dry-run --update-refindex', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:lostfiles --dry-run --update-refindex', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:missingfiles --dry-run --update-refindex', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:missingrelations --dry-run --update-refindex', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:orphanrecords', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:previewlinks', 'code' => 0, 'skipComposer' => false],
            ['command' => 'cleanup:versions', 'code' => 0, 'skipComposer' => false],
            ['command' => 'extension:list', 'code' => 0, 'skipComposer' => false],
            ['command' => 'extension:setup', 'code' => 0, 'skipComposer' => false],
            ['command' => 'extension:deactivate workspaces', 'code' => 0, 'skipComposer' => true],
            ['command' => 'extension:activate workspaces', 'code' => 0, 'skipComposer' => true],
            ['command' => 'language:update', 'code' => 0, 'skipComposer' => false],
            ['command' => 'mailer:spool:send', 'code' => 1, 'skipComposer' => false],
            ['command' => 'redirects:checkintegrity', 'code' => 0, 'skipComposer' => false],
            ['command' => 'redirects:cleanup', 'code' => 0, 'skipComposer' => false],
            ['command' => 'referenceindex:update --check', 'code' => 0, 'skipComposer' => false],
            ['command' => 'scheduler:run', 'code' => 0, 'skipComposer' => false],
            ['command' => 'site:list', 'code' => 0, 'skipComposer' => false],
            ['command' => 'site:show styleguide-demo-51', 'code' => 0, 'skipComposer' => false],
            ['command' => 'syslog:list', 'code' => 0, 'skipComposer' => false],
            ['command' => 'upgrade:list', 'code' => 0, 'skipComposer' => false],
        ];
    }
}
