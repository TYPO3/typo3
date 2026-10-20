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

final class CliCommandTest extends AbstractCommandTestCase
{
    use SiteBasedTestTrait;

    protected const array LANGUAGE_PRESETS = [
        'EN' => ['id' => 0, 'title' => 'English', 'locale' => 'en_US.UTF8'],
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
            ['command' => 'cache:flush', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'cache:warmup', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'extension:setup', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'language:update', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'mailer:spool:send', 'args' => [], 'expectedExitCode' => 1],
            ['command' => 'site:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'site:show show-me', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'site:validate', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'site:validate show-me', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'upgrade:list', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'completion bash', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'completion fish', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'completion zsh', 'args' => [], 'expectedExitCode' => 0],
            ['command' => 'completion', 'args' => [], 'expectedExitCode' => 2],
        ];
    }

    #[DataProvider('commandTestDataProvider')]
    #[Test]
    public function cliCommand(string $command, array $args, int $expectedExitCode): void
    {
        $result = $this->executeConsoleCommand($command, ...$args);

        self::assertSame($expectedExitCode, $result['status'], json_encode($result, JSON_PRETTY_PRINT));
    }
}
