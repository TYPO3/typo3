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

use TYPO3\CMS\Core\Tests\Functional\SiteHandling\SiteBasedTestTrait;

/**
 * @todo: This test mixes commands from various extensions (like EM and install),
 *        and should be split test the command in the according extension only.
 */
final class CliCommandTest extends AbstractCommandTestCase
{
    use SiteBasedTestTrait;

    protected const LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
    ];

    protected array $coreExtensionsToLoad = [
        'lowlevel',
        'redirects',
        'extensionmanager',
        'scheduler',
        'workspaces',
        'styleguide',
        'install',
    ];

    public function setUp(): void
    {
        parent::setUp();

        $this->writeSiteConfiguration(
            'show-me',
            $this->buildSiteConfiguration(8000, 'https://show.me/')
        );
    }

    public static function commandTestDataProvider(): array
    {
        return [
            ['command' => 'styleguide:generate tca', 'args' => [], 'code' => 0],
            ['command' => 'styleguide:generate frontend', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:localprocessedfiles', 'args' => ['-v'], 'code' => 0],
            ['command' => 'cache:flush', 'args' => [], 'code' => 0],
            ['command' => 'cache:warmup', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:flexforms', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:deletedrecords', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:orphanrecords', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:previewlinks', 'args' => [], 'code' => 0],
            ['command' => 'cleanup:versions', 'args' => [], 'code' => 0],
            ['command' => 'extension:list', 'args' => [], 'code' => 0],
            ['command' => 'extension:setup', 'args' => [], 'code' => 0],
            ['command' => 'extension:deactivate workspaces', 'args' => [], 'code' => 0],
            ['command' => 'extension:activate workspaces', 'args' => [], 'code' => 0],
            ['command' => 'language:update', 'args' => [], 'code' => 0],
            ['command' => 'mailer:spool:send', 'args' => [], 'code' => 1],
            ['command' => 'redirects:checkintegrity', 'args' => [], 'code' => 0],
            ['command' => 'redirects:cleanup', 'args' => [], 'code' => 0],
            ['command' => 'referenceindex:update', 'args' => ['--check'], 'code' => 0],
            ['command' => 'scheduler:run', 'args' => [], 'code' => 0],
            ['command' => 'site:list', 'args' => [], 'code' => 0],
            ['command' => 'site:show show-me', 'args' => [], 'code' => 0],
            ['command' => 'syslog:list', 'args' => [], 'code' => 0],
            ['command' => 'upgrade:list', 'args' => [], 'code' => 0],
        ];
    }

    /**
     * @test
     * @dataProvider commandTestDataProvider
     */
    public function cliCommand(string $command, array $args, int $expectedExitCode): void
    {
        $result = $this->executeConsoleCommand($command, ...$args);

        self::assertEquals($expectedExitCode, $result['status']);
    }
}
