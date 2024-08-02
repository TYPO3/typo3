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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
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
        'felogin',
        'fluid_styled_content',
        'seo',
        'styleguide',
        'form',
        'indexed_search',
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
            ['command' => 'styleguide:generate tca', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'styleguide:generate frontend', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:localprocessedfiles', 'args' => ['-v'], 'expectedExitCode' => 0],
            ['command' => 'cache:flush', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cache:warmup', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:flexforms', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:deletedrecords', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:orphanrecords', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:previewlinks', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cleanup:versions', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:setup', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:deactivate workspaces', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:activate workspaces', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'language:update', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'mailer:spool:send', 'args' => [], 'expectedExitCode' => 1],
            ['command' => 'redirects:checkintegrity', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'redirects:cleanup', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'referenceindex:update', 'args' => ['--check'], 'expectedExitCode' => 0],
            ['command' => 'scheduler:run', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'site:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'site:show show-me', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'syslog:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'upgrade:list', 'args' => [], 'expectedExitCode' => 0],
        ];
    }

    #[DataProvider('commandTestDataProvider')]
    #[Test]
    public function cliCommand(string $command, array $args, int $expectedExitCode): void
    {
        $result = $this->executeConsoleCommand($command, ...$args);

        self::assertEquals($expectedExitCode, $result['status']);
    }
}
