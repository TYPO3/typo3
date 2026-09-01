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
use Symfony\Component\DependencyInjection\Container;
use TYPO3\CMS\Core\Authentication\BackendUserAuthentication;
use TYPO3\CMS\Core\EventDispatcher\ListenerProvider;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceRepository;
use TYPO3\CMS\Workspaces\Domain\Repository\WorkspaceStageRepository;
use TYPO3\CMS\Workspaces\Event\AfterDataGeneratedForWorkspaceEvent;
use TYPO3\CMS\Workspaces\Service\GridDataService;
use TYPO3\CMS\Workspaces\Service\StagesService;
use TYPO3\CMS\Workspaces\Service\WorkspaceService;
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
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/GridData/pages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/GridData/tt_content.csv');

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

    #[Test]
    public function generateGridListFromVersionsReturnsNoAdditionalColumnsWithoutListener(): void
    {
        $result = $this->generateGridList();

        self::assertSame([], $result['additionalColumns']);
        self::assertNotEmpty($result['data']);
        foreach ($result['data'] as $row) {
            self::assertArrayNotHasKey('additional', $row);
        }
    }

    #[Test]
    public function generateGridListFromVersionsRendersAdditionalColumnsAddedToTheDataArray(): void
    {
        $this->registerAdditionalColumnListener(static function (array $row): ?array {
            if ($row['table'] !== 'tt_content') {
                return null;
            }
            return [
                'label' => 'Scheduled',
                'value' => '2026-03-01',
                'icon' => 'actions-clock',
                'title' => 'in 3 days',
                'url' => '/typo3/module/scheduled',
            ];
        });

        $result = $this->generateGridList();

        self::assertSame(['scheduled' => 'Scheduled'], $result['additionalColumns']);

        $rowsWithValue = array_filter($result['data'], static fn(array $row): bool => isset($row['additional']));
        self::assertCount(1, $rowsWithValue);
        $row = array_pop($rowsWithValue);
        self::assertSame('tt_content:101', $row['id']);
        self::assertSame(
            [
                'label' => 'Scheduled',
                'value' => '2026-03-01',
                'icon' => 'actions-clock',
                'title' => 'in 3 days',
                'url' => '/typo3/module/scheduled',
            ],
            $row['additional']['scheduled']
        );
    }

    #[Test]
    public function additionalColumnLabelFallsBackToTheColumnIdentifier(): void
    {
        $this->registerAdditionalColumnListener(static fn(array $row): array => ['value' => 'no label given']);

        $result = $this->generateGridList();

        self::assertSame(['scheduled' => 'scheduled'], $result['additionalColumns']);
    }

    #[Test]
    public function additionalColumnsAreDeterminedFromAllRowsAndNotOnlyFromTheCurrentPage(): void
    {
        $this->registerAdditionalColumnListener(static function (array $row): ?array {
            if ($row['table'] !== 'tt_content') {
                return null;
            }
            return ['label' => 'Scheduled', 'value' => '2026-03-01'];
        });

        $firstPage = $this->generateGridList(1);

        // The only row carrying a value is not part of the first page, the column is rendered nevertheless
        self::assertCount(1, $firstPage['data']);
        self::assertSame('pages:102', $firstPage['data'][0]['id']);
        self::assertArrayNotHasKey('additional', $firstPage['data'][0]);
        self::assertSame(['scheduled' => 'Scheduled'], $firstPage['additionalColumns']);
    }

    /**
     * Registers a listener that adds an "additional" section to every row for which the
     * given callback returns one, the way a third party extension would do it.
     */
    private function registerAdditionalColumnListener(callable $valueForRow): void
    {
        /** @var Container $container */
        $container = $this->get('service_container');
        $container->set(
            'after-data-generated-for-workspace-listener',
            static function (AfterDataGeneratedForWorkspaceEvent $event) use ($valueForRow): void {
                $data = $event->getData();
                foreach ($data as $identifier => $row) {
                    $value = $valueForRow($row);
                    if ($value !== null) {
                        $data[$identifier]['additional']['scheduled'] = $value;
                    }
                }
                $event->setData($data);
            }
        );
        $container->get(ListenerProvider::class)
            ->addListener(AfterDataGeneratedForWorkspaceEvent::class, 'after-data-generated-for-workspace-listener');
    }

    private function generateGridList(?int $limit = null): array
    {
        $workspace = $this->get(WorkspaceRepository::class)->findByUid($this->backendUser->workspace);
        $stages = $this->get(WorkspaceStageRepository::class)->findAllStagesByWorkspace(
            $this->backendUser,
            $workspace
        );
        $versions = $this->get(WorkspaceService::class)->selectVersionsInWorkspace(
            $this->backendUser->workspace,
            -99,
            -1,
            999
        );

        $parameter = new \stdClass();
        if ($limit !== null) {
            $parameter->limit = $limit;
        }

        return $this->get(GridDataService::class)->generateGridListFromVersions($stages, $versions, $parameter);
    }
}
