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

use Prophecy\Argument;
use Prophecy\Prophecy\ObjectProphecy;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class AdminPanelInitiatorTest extends UnitTestCase
{
    /**
     * @var bool Reset singletons created by subject
     */
    protected $resetSingletonInstances = true;

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
                'display_top' => true
            ]
        ];
        $userAuthentication = $this->prophesize(FrontendBackendUserAuthentication::class);
        $userAuthentication->getTSConfig()->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication->reveal();

        $controller = $this->prophesize(MainController::class);
        GeneralUtility::setSingletonInstance(MainController::class, $controller->reveal());
        $handler = $this->prophesizeHandler();
        $request = $this->prophesize(ServerRequestInterface::class);
        $request->withAttribute(Argument::cetera())->willReturn($request);
        $controller->initialize($request->reveal())->willReturn($request);

        // Act
        $adminPanelInitiator = new AdminPanelInitiator();
        $adminPanelInitiator->process(
            $request->reveal(),
            $handler->reveal()
        );
        // Assert
        $controller->initialize(Argument::any())->shouldHaveBeenCalled();
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
                'display_top' => false
            ]
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
                'display_top' => true
            ]
        ];
        $this->checkAdminPanelDoesNotCallInitialize($tsConfig, $uc);
    }

    /**
     * @param $tsConfig
     * @param $uc
     * @param $typoScript
     */
    protected function checkAdminPanelDoesNotCallInitialize($tsConfig, $uc): void
    {
        $userAuthentication = $this->prophesize(FrontendBackendUserAuthentication::class);
        $userAuthentication->getTSConfig()->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication->reveal();

        $controller = $this->prophesize(MainController::class);
        GeneralUtility::setSingletonInstance(MainController::class, $controller->reveal());
        $handler = $this->prophesizeHandler();
        $request = $this->prophesize(ServerRequestInterface::class);
        // Act
        $adminPanelInitiator = new AdminPanelInitiator();
        $adminPanelInitiator->process(
            $request->reveal(),
            $handler->reveal()
        );
        // Assert
        $controller->initialize(Argument::any())->shouldNotHaveBeenCalled();
    }

    /**
     * @return ObjectProphecy|RequestHandlerInterface
     */
    protected function prophesizeHandler()
    {
        $handler = $this->prophesize(RequestHandlerInterface::class);
        $handler
            ->handle(Argument::any())
            ->willReturn(
                $this->prophesize(ResponseInterface::class)->reveal()
            );
        return $handler;
    }
}
