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

namespace TYPO3\CMS\Install\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\Command\AbstractCommandTestCase;

final class CliCommandTest extends AbstractCommandTestCase
{
    protected array $coreExtensionsToLoad = [
        'install',
    ];

    public static function commandTestDataProvider(): array
    {
        return [
            ['command' => 'install:password:set %s %s', 'args' => ['--no-interaction', '--dry-run'], 'expectedExitCode' => 0],
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
