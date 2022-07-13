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

namespace TYPO3\CMS\Backend\Tests\Functional\Routing;

use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class UriBuilderTest extends FunctionalTestCase
{
    protected bool $initializeDatabase = false;

    protected array $coreExtensionsToLoad = ['workspaces'];

    /**
     * @test
     */
    public function buildUriFromRouteResolvesAliasWhenLinking(): void
    {
        $subject = GeneralUtility::makeInstance(UriBuilder::class);
        $route = $subject->buildUriFromRoute('workspaces_admin');
        $routeFromAlias = $subject->buildUriFromRoute('web_WorkspacesWorkspaces');
        self::assertEquals($routeFromAlias->getPath(), $route->getPath());
    }
}
