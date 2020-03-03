<?php
declare(strict_types = 1);

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Modules;

use Prophecy\Argument;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Adminpanel\Modules\PreviewModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\CMS\Frontend\Controller\TypoScriptFrontendController;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PreviewModuleTest extends UnitTestCase
{
    public function simulateDateDataProvider(): array
    {
        return [
            'timestamp' => [
                (new \DateTime('2018-01-01 12:00:15 UTC'))->getTimestamp(),
                (int)(new \DateTime('2018-01-01 12:00:15 UTC'))->getTimestamp(),
                (int)(new \DateTime('2018-01-01 12:00:00 UTC'))->getTimestamp(),
            ],
            'timestamp_1970' => [
                (string)(new \DateTime('1970-01-01 00:00:15 UTC'))->getTimestamp(),
                (int)(new \DateTime('1970-01-01 00:00:60 UTC'))->getTimestamp(),
                (int)(new \DateTime('1970-01-01 00:00:60 UTC'))->getTimestamp(),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider simulateDateDataProvider
     * @param string $dateToSimulate
     * @param int $expectedExecTime
     * @param int $expectedAccessTime
     */
    public function initializeFrontendPreviewSetsDateForSimulation(string $dateToSimulate, int $expectedExecTime, int $expectedAccessTime): void
    {
        $this->resetSingletonInstances = true;
        $request = $this->prophesize(ServerRequestInterface::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $GLOBALS['TSFE'] = $tsfe->reveal();
        $configurationService = $this->prophesize(ConfigurationService::class);
        $configurationService->getMainConfiguration()->willReturn([]);
        $configurationService->getConfigurationOption('preview', 'simulateDate')->willReturn($dateToSimulate);
        $configurationService->getConfigurationOption('preview', Argument::any())->willReturn('');

        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationService->reveal());

        $previewModule = new PreviewModule();
        $previewModule->initializeModule($request->reveal());

        self::assertSame($GLOBALS['SIM_EXEC_TIME'], $expectedExecTime, 'EXEC_TIME');
        self::assertSame($GLOBALS['SIM_ACCESS_TIME'], $expectedAccessTime, 'ACCESS_TIME');
    }

    /**
     * @test
     */
    public function initializeFrontendPreviewSetsUsergroupForSimulation(): void
    {
        $this->resetSingletonInstances = true;
        $request = $this->prophesize(ServerRequestInterface::class);
        $tsfe = $this->prophesize(TypoScriptFrontendController::class);
        $feUser = $this->prophesize(FrontendUserAuthentication::class);
        $tsfe->fe_user = $feUser->reveal();
        $GLOBALS['TSFE'] = $tsfe->reveal();
        $configurationService = $this->prophesize(ConfigurationService::class);
        $configurationService->getMainConfiguration()->willReturn([]);
        $configurationService->getConfigurationOption('preview', 'showHiddenPages')->willReturn('0');
        $configurationService->getConfigurationOption('preview', 'simulateDate')->willReturn('0');
        $configurationService->getConfigurationOption('preview', 'showHiddenRecords')->willReturn('0');
        $configurationService->getConfigurationOption('preview', 'showFluidDebug')->willReturn('0');
        $configurationService->getConfigurationOption('preview', 'simulateUserGroup')->willReturn('1');
        $context = $this->prophesize(Context::class);
        GeneralUtility::setSingletonInstance(Context::class, $context->reveal());

        GeneralUtility::setSingletonInstance(ConfigurationService::class, $configurationService->reveal());

        $previewModule = new PreviewModule();
        $previewModule->initializeModule($request->reveal());

        $context->setAspect('frontend.user', Argument::any())->shouldHaveBeenCalled();
    }
}
