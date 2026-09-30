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

namespace TYPO3\CMS\Backend\Tests\Unit\Routing;

use PHPUnit\Framework\Attributes\IgnoreDeprecations;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Routing\Route;
use TYPO3\CMS\Backend\Routing\RouteAccess;
use TYPO3\CMS\Backend\Routing\Router;
use TYPO3\CMS\Core\Routing\BackendEntryPointResolver;
use TYPO3\CMS\Core\Routing\RequestContextFactory;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RouterAccessTest extends UnitTestCase
{
    #[Test]
    #[IgnoreDeprecations]
    public function legacyPublicAccessIsMigratedAndDeprecated(): void
    {
        $subject = $this->createSubject();
        $subject->addRoute('my_route', new Route('/my-route', ['access' => 'public']));

        $route = $subject->getRoute('my_route');
        self::assertSame(RouteAccess::AuthenticatedWithoutToken->value, $route->getOption('access'));
    }

    #[Test]
    public function moduleAccessGateIdentifierIsNotTreatedAsLegacyAccess(): void
    {
        $subject = $this->createSubject();
        $subject->addRoute('my_module', new Route('/module/my', ['access' => 'admin', 'module' => new \stdClass()]));

        self::assertSame('admin', $subject->getRoute('my_module')->getOption('access'));
    }

    private function createSubject(): Router
    {
        return new Router(
            self::createStub(RequestContextFactory::class),
            self::createStub(BackendEntryPointResolver::class)
        );
    }
}
