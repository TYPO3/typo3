<?php

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

namespace TYPO3\CMS\Backend\Tests\Functional\Command;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Tests\Functional\Command\AbstractCommandTestCase;

final class DebugBackendRoutesCommandTest extends AbstractCommandTestCase
{
    #[Test]
    public function anonymousRoutesCanBeListed(): void
    {
        $result = $this->executeConsoleCommand('debug:backend:routes --json --access=anonymous');

        self::assertSame(0, $result['status']);
        $routes = json_decode($result['stdout'], true, flags: JSON_THROW_ON_ERROR);
        $names = array_column($routes, 'name');
        self::assertContains('login', $names);
        self::assertContains('ajax_login_refresh', $names);
        self::assertNotContains('main', $names);
        foreach ($routes as $route) {
            self::assertSame('anonymous', $route['access']);
            self::assertFalse($route['requestToken']);
        }
    }

    #[Test]
    public function invalidAccessIsRejected(): void
    {
        $result = $this->executeConsoleCommand('debug:backend:routes --access=everyone');

        self::assertSame(1, $result['status']);
    }
}
