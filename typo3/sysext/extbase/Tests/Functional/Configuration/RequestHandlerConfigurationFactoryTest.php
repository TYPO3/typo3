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

namespace TYPO3\CMS\Extbase\Tests\Functional\Configuration;

use TYPO3\CMS\Core\Cache\Frontend\NullFrontend;
use TYPO3\CMS\Core\Package\PackageManager;
use TYPO3\CMS\Extbase\Configuration\RequestHandlersConfigurationFactory;
use TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RequestHandlerConfigurationFactoryTest extends FunctionalTestCase
{
    /**
     * @var bool Speed up this test case, it needs no database
     */
    protected $initializeDatabase = false;

    /**
     * @test
     */
    public function requestHandlerConfigurationFactoryLoadsRequestHandlersOfExtbaseAndFluid(): void
    {
        $configuration = (new RequestHandlersConfigurationFactory(new NullFrontend('extbase'), $this->get(PackageManager::class), 'PackageDependentCacheIdentifier'))
            ->createRequestHandlersConfiguration();

        self::assertSame(
            [
                FrontendRequestHandler::class,
            ],
            $configuration->getRegisteredRequestHandlers()
        );
    }
}
