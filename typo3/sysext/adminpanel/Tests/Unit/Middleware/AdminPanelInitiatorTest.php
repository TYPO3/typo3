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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Middleware;

use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class AdminPanelInitiatorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function processCallsInitialize(): void
    {
        $tsConfig = [
            'admPanel.' => [
                'enable.' => [
                    'all',
                ],
            ],
        ];
        $uc = [
            'AdminPanel' => [
                'display_top' => true,
            ],
        ];
        $userAuthentication = $this->getMockBuilder(FrontendBackendUserAuthentication::class)->getMock();
        $userAuthentication->expects(self::once())->method('getTSConfig')->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication;

        $controller = $this->getMockBuilder(MainController::class)->disableOriginalConstructor()->getMock();
        GeneralUtility::addInstance(MainController::class, $controller);
        $handler = $this->getHandlerMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $request->expects(self::any())->method('withAttribute')->withAnyParameters()->willReturn($request);
        $controller->expects(self::once())->method('initialize')->with($request)->willReturn($request);

        $adminPanelInitiator = new AdminPanelInitiator();
        $adminPanelInitiator->process($request, $handler);
    }

    /**
     * @test
     */
    public function processDoesNotCallInitializeIfAdminPanelIsNotEnabledInUC(): void
    {
        $tsConfig = [
            'admPanel.' => [
                'enable.' => [
                    'all',
                ],
            ],
        ];
        $uc = [
            'AdminPanel' => [
                'display_top' => false,
            ],
        ];
        $this->checkAdminPanelDoesNotCallInitialize($tsConfig, $uc);
    }

    /**
     * @test
     */
    public function processDoesNotCallInitializeIfNoAdminPanelModuleIsEnabled(): void
    {
        $tsConfig = [
            'admPanel.' => [],
        ];
        $uc = [
            'AdminPanel' => [
                'display_top' => true,
            ],
        ];
        $this->checkAdminPanelDoesNotCallInitialize($tsConfig, $uc);
    }

    protected function checkAdminPanelDoesNotCallInitialize(array $tsConfig, array $uc): void
    {
        $userAuthentication = $this->getMockBuilder(FrontendBackendUserAuthentication::class)->getMock();
        $userAuthentication->expects(self::once())->method('getTSConfig')->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication;

        $controller = $this->getMockBuilder(MainController::class)->disableOriginalConstructor()->getMock();
        GeneralUtility::addInstance(MainController::class, $controller);
        $handler = $this->getHandlerMock();
        $request = $this->getMockBuilder(ServerRequestInterface::class)->getMock();
        $controller->expects(self::never())->method('initialize');

        $adminPanelInitiator = new AdminPanelInitiator();
        $adminPanelInitiator->process($request, $handler);

        /** @var MainController&MockObject $controller */
        $controller = GeneralUtility::makeInstance(MainController::class);
    }

    protected function getHandlerMock(): RequestHandlerInterface&MockObject
    {
        $response = $this->getMockBuilder(ResponseInterface::class)->getMock();
        $handler = $this->getMockBuilder(RequestHandlerInterface::class)->onlyMethods(['handle'])->getMock();
        $handler->expects(self::any())->method('handle')->withAnyParameters()->willReturn($response);
        return $handler;
    }
}
