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

namespace TYPO3\CMS\Adminpanel\Tests\Unit\Modules;

use TYPO3\CMS\Adminpanel\Modules\PreviewModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

class PreviewModuleTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public function simulateDateDataProvider(): array
    {
        return [
            'timestamp' => [
                (string)(new \DateTime('2018-01-01 12:00:15 UTC'))->getTimestamp(),
                (new \DateTime('2018-01-01 12:00:15 UTC'))->getTimestamp(),
                (new \DateTime('2018-01-01 12:00:00 UTC'))->getTimestamp(),
            ],
            'timestamp_1970' => [
                (string)(new \DateTime('1970-01-01 00:00:15 UTC'))->getTimestamp(),
                (new \DateTime('1970-01-01 00:00:60 UTC'))->getTimestamp(),
                (new \DateTime('1970-01-01 00:00:60 UTC'))->getTimestamp(),
            ],
        ];
    }

    /**
     * @test
     * @dataProvider simulateDateDataProvider
     */
    public function initializeFrontendPreviewSetsDateForSimulation(string $dateToSimulate, int $expectedExecTime, int $expectedAccessTime): void
    {
        $configurationService = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationService->expects(self::once())->method('getMainConfiguration')->willReturn([]);
        $valueMap = [
            ['preview', 'showHiddenPages', ''],
            ['preview', 'simulateDate', $dateToSimulate],
            ['preview', 'simulateUserGroup', ''],
            ['preview', 'showScheduledRecords', ''],
            ['preview', 'showHiddenRecords', ''],
            ['preview', 'showFluidDebug', ''],
        ];
        $configurationService->method('getConfigurationOption')->withAnyParameters()->willReturnMap($valueMap);

        $previewModule = new PreviewModule();
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich(new ServerRequest());

        self::assertSame($GLOBALS['SIM_EXEC_TIME'], $expectedExecTime, 'EXEC_TIME');
        self::assertSame($GLOBALS['SIM_ACCESS_TIME'], $expectedAccessTime, 'ACCESS_TIME');
    }

    /**
     * @test
     */
    public function initializeFrontendPreviewSetsUserGroupForSimulation(): void
    {
        $request = (new ServerRequest())->withAttribute('frontend.user', $this->getMockBuilder(FrontendUserAuthentication::class)->getMock());

        $configurationService = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationService->expects(self::once())->method('getMainConfiguration')->willReturn([]);
        $valueMap = [
            ['preview', 'showHiddenPages', '0'],
            ['preview', 'simulateDate', '0'],
            ['preview', 'simulateUserGroup', '1'],
            ['preview', 'showScheduledRecords', '0'],
            ['preview', 'showHiddenRecords', '0'],
            ['preview', 'showFluidDebug', '0'],
        ];
        $configurationService->method('getConfigurationOption')->withAnyParameters()->willReturnMap($valueMap);

        $context = $this->getMockBuilder(Context::class)->getMock();
        $context->method('hasAspect')->with('frontend.preview')->willReturn(false);
        $context->expects(self::any())->method('setAspect')
            ->willReturnCallback(fn (string $name): bool => match (true) {
                $name === 'date',
                $name === 'visibility',
                $name === 'frontend.user',
                $name === 'frontend.preview' => true,
                default => throw new \LogicException('Unexpected argument "' . $name . '" provided.', 1679482900),
            });
        GeneralUtility::setSingletonInstance(Context::class, $context);

        $previewModule = new PreviewModule();
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich($request);
    }
}
