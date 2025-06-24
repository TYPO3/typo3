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

namespace TYPO3\CMS\Workspaces\Tests\Functional\Service;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Workspace service test
 */
final class WorkspaceServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function emptyWorkspaceReturnsEmptyArray(): void
    {
        $service = new WorkspaceService();
        $result = $service->selectVersionsInWorkspace(90);
        self::assertEmpty($result, 'The workspace 90 contains no changes and the result was supposed to be empty');
        self::assertIsArray($result, 'Even the empty result from workspace 90 is supposed to be an array');
    }

    #[Test]
    public function versionsFromSpecificWorkspaceCanBeFound(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $service = new WorkspaceService();
        $result = $service->selectVersionsInWorkspace(91, -99, 2);
        self::assertIsArray($result, 'The result from workspace 91 is supposed to be an array');
        self::assertCount(
            1,
            $result['pages'],
            'The result is supposed to contain one version for this page in workspace 91'
        );
        self::assertEquals(102, $result['pages'][0]['uid'], 'Wrong workspace overlay record picked');
        self::assertEquals(1, $result['pages'][0]['livepid'], 'Real pid wasn\'t resolved correctly');
    }

    #[Test]
    public function versionsCanBeFoundRecursive(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $service = new WorkspaceService();
        $result = $service->selectVersionsInWorkspace(91, -99, 1, 99);
        self::assertIsArray($result, 'The result from workspace 91 is supposed to be an array');
        self::assertCount(
            4,
            $result['pages'],
            'The result is supposed to contain four versions for this page in workspace 91'
        );
    }

    #[Test]
    public function versionsCanBeFilteredToSpecificStage(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
        $service = new WorkspaceService();
        // testing stage 1
        $result = $service->selectVersionsInWorkspace(91, 1, 1, 99);
        self::assertIsArray($result, 'The result from workspace 91 is supposed to be an array');
        self::assertCount(
            2,
            $result['pages'],
            'The result is supposed to contain two versions for this page in workspace 91'
        );
        self::assertEquals(102, $result['pages'][0]['uid'], 'First records is supposed to have the uid 102');
        self::assertEquals(105, $result['pages'][1]['uid'], 'First records is supposed to have the uid 105');
        // testing stage 2
        $result = $service->selectVersionsInWorkspace(91, 2, 1, 99);
        self::assertIsArray($result, 'The result from workspace 91 is supposed to be an array');
        self::assertCount(
            2,
            $result['pages'],
            'The result is supposed to contain two versions for this page in workspace 91'
        );
        self::assertEquals(104, $result['pages'][0]['uid'], 'First records is supposed to have the uid 106');
        self::assertEquals(106, $result['pages'][1]['uid'], 'First records is supposed to have the uid 106');
    }

    #[Test]
    public function movedElementsCanBeFoundAtTheirDestination(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceServiceTestMovedContent.csv');
        // Test if the placeholder can be found when we ask using recursion (same result)
        $service = new WorkspaceService();
        $result = $service->selectVersionsInWorkspace(91, -99, 5, 99);
        self::assertCount(1, $result['pages'], 'Wrong amount of page versions found within workspace 91');
        self::assertEquals(103, $result['pages'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
        self::assertEquals(5, $result['pages'][0]['wspid'], 'Wrong workspace-pointer found for page 3 in workspace 91');
        self::assertEquals(2, $result['pages'][0]['livepid'], 'Wrong live-pointer found for page 3 in workspace 91');
        self::assertCount(1, $result['tt_content'], 'Wrong amount of tt_content versions found within workspace 91');
        self::assertEquals(106, $result['tt_content'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
        self::assertEquals(7, $result['tt_content'][0]['wspid'], 'Wrong workspace-pointer found for page 3 in workspace 91');
        self::assertEquals(2, $result['tt_content'][0]['livepid'], 'Wrong live-pointer found for page 3 in workspace 91');
    }

    #[Test]
    public function movedElementsCanBeFoundUsingTheirLiveUid(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/WorkspaceServiceTestMovedContent.csv');
        // Test if the placeholder can be found when we ask using recursion (same result)
        $service = new WorkspaceService();
        $result = $service->selectVersionsInWorkspace(91, -99, 3, 99);
        self::assertCount(1, $result, 'Wrong amount of versions found within workspace 91');
        self::assertCount(1, $result['pages'], 'Wrong amount of page versions found within workspace 91');
        self::assertEquals(103, $result['pages'][0]['uid'], 'Wrong move-to pointer found for page 3 in workspace 91');
    }
}
