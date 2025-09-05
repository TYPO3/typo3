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

namespace TYPO3\CMS\Adminpanel\Tests\Functional\Modules;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use TYPO3\CMS\Adminpanel\Modules\PreviewModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendUserAuthentication;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class PreviewModuleTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['adminpanel'];

    #[Test]
    #[TestWith(['1', [1, 2, 3, 4]], 'Group 1 contains all other groups')]
    #[TestWith(['2', [2]], 'Group 2 does not have subgroups')]
    #[TestWith(['3', [3, 4]], 'Group 3 has a single subgroup')]
    public function simulateUserRespectsSubgroups(string $simulatedUserGroup, array $expectedGroupUids): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PreviewModuleTestSimulateUsergroup.csv');
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $frontendUser = new FrontendUserAuthentication();
        $request = (new ServerRequest())->withAttribute('frontend.user', $frontendUser);

        $configurationService = $this->getMockBuilder(ConfigurationService::class)->disableOriginalConstructor()->getMock();
        $configurationService->expects($this->once())->method('getMainConfiguration')->willReturn([]);
        $valueMap = [
            ['preview', 'showHiddenPages', '0'],
            ['preview', 'simulateDate', '0'],
            ['preview', 'simulateUserGroup', $simulatedUserGroup],
            ['preview', 'showScheduledRecords', '0'],
            ['preview', 'showHiddenRecords', '0'],
            ['preview', 'showFluidDebug', '0'],
        ];
        $configurationService->method('getConfigurationOption')->withAnyParameters()->willReturnMap($valueMap);

        $previewModule = $this->get(PreviewModule::class);
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich($request);

        /** @var UserAspect $frontendUserAspect */
        $frontendUserAspect = GeneralUtility::makeInstance(Context::class)->getAspect('frontend.user');

        // Check groups inside user object
        $groupUidsInFrontendUser = array_column($frontendUser->userGroups, 'uid');
        self::assertEqualsCanonicalizing($expectedGroupUids, $groupUidsInFrontendUser);

        // Check aspect property. Additional internal group -2 (general logged in state) required
        self::assertEqualsCanonicalizing(array_merge($expectedGroupUids, [-2]), $frontendUserAspect->get('groupIds'));
    }
}
