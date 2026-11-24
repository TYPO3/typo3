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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\Attributes\TestWith;
use TYPO3\CMS\Adminpanel\Modules\PreviewModule;
use TYPO3\CMS\Adminpanel\Service\ConfigurationService;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Context\Context;
use TYPO3\CMS\Core\Context\UserAspect;
use TYPO3\CMS\Core\Core\SystemEnvironmentBuilder;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Http\Uri;
use TYPO3\CMS\Core\Site\Entity\SiteLanguage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Frontend\Authentication\FrontendBackendUserAuthentication;
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
        self::assertEqualsCanonicalizing($expectedGroupUids, array_keys($frontendUser->userGroups));

        // Check aspect property. Additional internal group -2 (general logged in state) required
        self::assertEqualsCanonicalizing(array_merge($expectedGroupUids, [-2]), $frontendUserAspect->get('groupIds'));
    }

    #[Test]
    #[TestWith(['2', [2]], 'A single group')]
    #[TestWith(['2,3', [2, 3, 4]], 'Several groups with their subgroups')]
    public function simulateUserFromPreviewParameterRespectsAllGroups(string $simulatedUserGroups, array $expectedGroupUids): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PreviewModuleTestSimulateUsergroup.csv');
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();

        $frontendUser = new FrontendUserAuthentication();
        $request = (new ServerRequest())
            ->withAttribute('frontend.user', $frontendUser)
            ->withQueryParams(['ADMCMD_simUser' => $simulatedUserGroups]);

        $configurationService = self::createStub(ConfigurationService::class);
        $configurationService->method('getMainConfiguration')->willReturn([]);
        $configurationService->method('getConfigurationOption')->willReturnMap([
            ['preview', 'showHiddenPages', '0'],
            ['preview', 'simulateDate', '0'],
            ['preview', 'simulateUserGroup', '0'],
            ['preview', 'showScheduledRecords', '0'],
            ['preview', 'showHiddenRecords', '0'],
            ['preview', 'showFluidDebug', '0'],
        ]);

        $previewModule = $this->get(PreviewModule::class);
        $previewModule->injectConfigurationService($configurationService);
        $previewModule->enrich($request);

        self::assertSame($simulatedUserGroups, $frontendUser->user[$frontendUser->usergroup_column]);
        self::assertEqualsCanonicalizing($expectedGroupUids, array_keys($frontendUser->userGroups));
        self::assertEqualsCanonicalizing(
            array_merge($expectedGroupUids, [-2]),
            $this->get(Context::class)->getPropertyFromAspect('frontend.user', 'groupIds')
        );
    }

    #[Test]
    public function previewSimulatorRunsAfterAdminPanelSoPreviewParametersTakePrecedence(): void
    {
        // The dispatcher wraps each middleware around the ones before it, so the last entry runs first.
        $executionOrder = array_flip(array_reverse(array_keys($this->get('frontend.middlewares')->getArrayCopy())));

        self::assertGreaterThan(
            $executionOrder['typo3/cms-adminpanel/initiator'],
            $executionOrder['typo3/cms-frontend/preview-simulator']
        );
    }

    #[Test]
    #[TestWith(['1', true])]
    #[TestWith(['0', false])]
    public function showFluidDebugActivatesFluidDebugAndDisablesCache(string $showFluidDebug, bool $expected): void
    {
        $GLOBALS['BE_USER'] = new BackendUserAuthentication();
        $request = (new ServerRequest())->withAttribute('frontend.user', new FrontendUserAuthentication());

        $configurationService = self::createStub(ConfigurationService::class);
        $configurationService->method('getMainConfiguration')->willReturn([]);
        $configurationService->method('getConfigurationOption')->willReturnMap([
            ['preview', 'showHiddenPages', '0'],
            ['preview', 'simulateDate', '0'],
            ['preview', 'simulateUserGroup', '0'],
            ['preview', 'showScheduledRecords', '0'],
            ['preview', 'showHiddenRecords', '0'],
            ['preview', 'showFluidDebug', $showFluidDebug],
        ]);

        $previewModule = $this->get(PreviewModule::class);
        $previewModule->injectConfigurationService($configurationService);
        $request = $previewModule->enrich($request);

        self::assertSame($expected, $request->getAttribute('admin-panel.fluid.debug', false));
        self::assertSame($expected, !($request->getAttribute('frontend.cache.instruction')?->isCachingAllowed() ?? true));
    }

    public static function pageSettingsOfferFrontendUserGroupsToSimulateDataProvider(): array
    {
        return [
            'groups on pages the backend user may show, ordered by title' => [2, [[2, 'Authors'], [1, 'Members']]],
            'no groups if the backend user may show no page with groups' => [3, []],
            'admin gets visible groups on all pages, but not on root level' => [1, [[2, 'Authors'], [1, 'Members'], [3, 'Staff']]],
        ];
    }

    #[Test]
    #[DataProvider('pageSettingsOfferFrontendUserGroupsToSimulateDataProvider')]
    public function pageSettingsOfferFrontendUserGroupsToSimulate(int $backendUserUid, array $expectedGroups): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/PreviewModuleTestFrontendUserGroups.csv');
        $backendUser = new FrontendBackendUserAuthentication();
        $backendUser->user = $this->getBackendUserRecordFromDatabase($backendUserUid);
        $backendUser->fetchGroupData();
        $GLOBALS['BE_USER'] = $backendUser;
        $request = new ServerRequest('https://example.com/');
        $GLOBALS['TYPO3_REQUEST'] = $request
            ->withAttribute('applicationType', SystemEnvironmentBuilder::REQUESTTYPE_FE)
            ->withAttribute('language', new SiteLanguage(0, 'en_US.UTF-8', new Uri('https://example.com/'), []));

        $pageSettings = $this->get(PreviewModule::class)->getPageSettings();

        preg_match_all('/<option value="(\d+)"[^>]*>\s*(.*?)\s*<\/option>/s', $pageSettings, $matches, PREG_SET_ORDER);
        $options = array_map(static fn(array $match): array => [(int)$match[1], $match[2]], $matches);
        self::assertSame([[0, ''], ...$expectedGroups], $options);
    }
}
