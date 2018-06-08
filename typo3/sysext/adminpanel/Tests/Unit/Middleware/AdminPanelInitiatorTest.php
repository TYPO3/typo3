<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Middleware;

use Prophecy\Argument;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use TYPO3\CMS\Adminpanel\Controller\MainController;
use TYPO3\CMS\Adminpanel\Middleware\AdminPanelInitiator;
use TYPO3\CMS\Adminpanel\View\AdminPanelView;
use TYPO3\CMS\Backend\FrontendBackendUserAuthentication;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
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
            'TSFE_adminConfig' => [
                'display_top' => true
            ]
        ];
        $userAuthentication = $this->prophesize(FrontendBackendUserAuthentication::class);
        $userAuthentication->getTSConfig(Argument::any())->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication->reveal();

        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe;

        $controller = $this->prophesize(MainController::class);
        GeneralUtility::setSingletonInstance(MainController::class, $controller->reveal());
        GeneralUtility::addInstance(AdminPanelView::class, $this->prophesize(AdminPanelView::class)->reveal());
        $handler = $this->prophesizeHandler();
        $request = $this->prophesize(ServerRequest::class);
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
            'TSFE_adminConfig' => [
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
            'TSFE_adminConfig' => [
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
        $userAuthentication->getTSConfig(Argument::any())->willReturn($tsConfig);
        $userAuthentication->uc = $uc;
        $GLOBALS['BE_USER'] = $userAuthentication->reveal();

        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe;

        $controller = $this->prophesize(MainController::class);
        GeneralUtility::setSingletonInstance(MainController::class, $controller->reveal());
        $handler = $this->prophesizeHandler();
        $request = $this->prophesize(ServerRequest::class);
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
     * @return \Prophecy\Prophecy\ObjectProphecy|\Psr\Http\Server\RequestHandlerInterface
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
