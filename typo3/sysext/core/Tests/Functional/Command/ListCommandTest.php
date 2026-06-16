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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Core\Environment;

final class ListCommandTest extends AbstractCommandTestCase
{
    protected array $coreExtensionsToLoad = ['extensionmanager'];

    #[Test]
    public function listCanBeRendered(): void
    {
        $result = $this->executeConsoleCommand('list');

        self::assertEquals(0, $result['status']);
        self::assertStringContainsString('Available commands:', $result['stdout']);
    }

    #[Test]
    public function extensionClassicModeCommandsAreListed(): void
    {
        $result = $this->executeConsoleCommand('list');

        self::assertEquals(0, $result['status']);
        // The following assertions are dependant on (and only true for) TYPO3 classic-mode
        // Current functional tests always run in classic-mode.
        // This assertion ensures we adapt this test, once we allow real dual mode tests
        // (in which case the commands `extension:activate` and `extension:deactivate`
        // shall *not* be listed in composer mode).
        self::assertFalse(Environment::isComposerMode());
        self::assertStringContainsString('extension:activate', $result['stdout']);
        self::assertStringContainsString('extension:deactivate', $result['stdout']);
    }
}
