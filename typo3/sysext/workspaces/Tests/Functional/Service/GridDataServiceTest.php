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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceStageRepository;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class GridDataServiceTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];
    private ?BackendUserAuthentication $backendUser = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/sys_workspace.csv');

        $this->backendUser = $this->setUpBackendUser(1);
        $this->backendUser->workspace = 91;
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($this->backendUser);
    }

    public static function getRowDetailsThrowsExceptionDataProvider(): \Generator
    {
        $editStage = StagesService::STAGE_EDIT_ID;
        yield 'non-existing table' => ['table' => 'does-not-exist', 'liveId' => 0, 'versionId' => 0, 'stage' => $editStage];
        yield 'workspace-unaware table' => ['table' => 'sys_note', 'liveId' => 0, 'versionId' => 0, 'stage' => $editStage];
    }

    #[Test]
    #[DataProvider('getRowDetailsThrowsExceptionDataProvider')]
    public function getRowDetailsThrowsException(string $table, int $liveId, int $versionId, int $stage): void
    {
        $workspace = $this->get(WorkspaceRepository::class)->findByUid($this->backendUser->workspace);
        $stages = $this->get(WorkspaceStageRepository::class)->findAllStagesByWorkspace(
            $this->backendUser,
            $workspace
        );

        $instruction = new \stdClass();
        $instruction->table = $table;
        $instruction->t3ver_oid = $liveId;
        $instruction->uid = $versionId;
        $instruction->stage = $stage;

        $subject = $this->get(GridDataService::class);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1756882012);
        $subject->getRowDetails($stages, $instruction);
    }
}
