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

use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\BackupGlobals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\LoggerInterface;
use TYPO3\CMS\Adminpanel\Modules\PreviewModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Authentication\GroupResolver;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\View\ViewFactoryInterface;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

#[AllowMockObjectsWithoutExpectations]
#[BackupGlobals(true)]
final class PreviewModuleTest extends UnitTestCase
{
    protected bool $resetSingletonInstances = true;

    public static function simulateDateDataProvider(): array
    {
        return [
            'timestamp' => [
                (string)new \DateTime('2018-01-01 12:00:15 UTC')->getTimestamp(),
                new \DateTime('2018-01-01 12:00:15 UTC')->getTimestamp(),
                new \DateTime('2018-01-01 12:00:00 UTC')->getTimestamp(),
            ],
            'timestamp_1970' => [
                (string)new \DateTime('1970-01-01 00:00:15 UTC')->getTimestamp(),
                new \DateTime('1970-01-01 00:00:60 UTC')->getTimestamp(),
                new \DateTime('1970-01-01 00:00:60 UTC')->getTimestamp(),
            ],
        ];
    }

    #[DataProvider('simulateDateDataProvider')]
    #[Test]
    public function initializeFrontendPreviewSetsDateForSimulation(string $dateToSimulate, int $expectedExecTime, int $expectedAccessTime): void
    {
        $configurationService = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationService->expects($this->once())->method('getMainConfiguration')->willReturn([]);
        $valueMap = [
            ['preview', 'showHiddenPages', ''],
            ['preview', 'simulateDate', $dateToSimulate],
            ['preview', 'simulateUserGroup', ''],
            ['preview', 'showScheduledRecords', ''],
            ['preview', 'showHiddenRecords', ''],
            ['preview', 'showFluidDebug', ''],
        ];
        $configurationService->method('getConfigurationOption')->willReturnMap($valueMap);

        $previewModule = new PreviewModule(
            self::createStub(CacheManager::class),
            self::createStub(ViewFactoryInterface::class),
            self::createStub(LoggerInterface::class),
            self::createStub(GroupResolver::class),
        );
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich(new ServerRequest());

        $dateAspect = GeneralUtility::makeInstance(Context::class)->getAspect('date');
        self::assertSame($expectedExecTime, $GLOBALS['SIM_EXEC_TIME'], 'EXEC_TIME');
        self::assertSame($expectedAccessTime, $dateAspect->getTimestampWithMinutePrecision(), 'ACCESS_TIME');
    }

    #[Test]
    public function initializeFrontendPreviewSetsUserGroupForSimulation(): void
    {
        $frontendUser = self::createStub(FrontendUserAuthentication::class);
        $request = new ServerRequest()->withAttribute('frontend.user', $frontendUser);

        $configurationService = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationService->expects($this->once())->method('getMainConfiguration')->willReturn([]);
        $valueMap = [
            ['preview', 'showHiddenPages', '0'],
            ['preview', 'simulateDate', '0'],
            ['preview', 'simulateUserGroup', '1'],
            ['preview', 'showScheduledRecords', '0'],
            ['preview', 'showHiddenRecords', '0'],
            ['preview', 'showFluidDebug', '0'],
        ];
        $configurationService->method('getConfigurationOption')->willReturnMap($valueMap);

        $context = new Context();
        GeneralUtility::setSingletonInstance(Context::class, $context);
        $groupResolver = self::createStub(GroupResolver::class);
        $groupResolver->method('resolveGroupsForUser')->willReturn([['uid' => 1]]);

        $previewModule = new PreviewModule(
            self::createStub(CacheManager::class),
            self::createStub(ViewFactoryInterface::class),
            self::createStub(LoggerInterface::class),
            $groupResolver,
        );
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich($request);

        self::assertSame('1', $frontendUser->user[$frontendUser->usergroup_column]);
        self::assertSame([-2, 1], $context->getPropertyFromAspect('frontend.user', 'groupIds'));
        self::assertTrue($context->getPropertyFromAspect('frontend.preview', 'isPreview'));
    }
}
