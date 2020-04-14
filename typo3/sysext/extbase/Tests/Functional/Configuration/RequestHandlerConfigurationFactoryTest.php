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

use TYPO3\CMS\Extbase\Configuration\RequestHandlersConfigurationFactory;
use TYPO3\CMS\Extbase\Mvc\Web\BackendRequestHandler;
use TYPO3\CMS\Extbase\Mvc\Web\FrontendRequestHandler;
use TYPO3\CMS\Fluid\Core\Widget\WidgetRequestHandler;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class RequestHandlerConfigurationFactoryTest extends FunctionalTestCase
{
    /**
     * @var array
     */
    protected $coreExtensionsToLoad = ['extbase', 'fluid'];

    /**
     * @test
     */
    public function requestHandlerConfigurationFactoryLoadsRequestHandlersOfExtbaseAndFluid(): void
    {
        $configuration = (new RequestHandlersConfigurationFactory())
            ->createRequestHandlersConfiguration();

        self::assertSame(
            [
                FrontendRequestHandler::class,
                BackendRequestHandler::class,
                WidgetRequestHandler::class,
            ],
            $configuration->getRegisteredRequestHandlers()
        );
    }
}
