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

namespace TYPO3\CMS\Backend\Tests\Functional\Controller;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\SimpleDataHandlerController;
use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SimpleDataHandlerControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    'pages' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ClearCacheController/pages.csv');
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('root', ['dummy'], ['pageId_1']);
        $pageCache->set('page', ['dummy'], ['pageId_2']);
    }

    #[Test]
    public function cacheCommandFlushesCacheOfThePageAndLogsTheCommand(): void
    {
        $this->setUpBackendUserWithLanguage(1);

        $this->get(SimpleDataHandlerController::class)->processAjaxRequest(
            new ServerRequest()->withParsedBody(['cacheCmd' => '2'])
        );

        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        self::assertTrue($pageCache->has('root'));
        self::assertFalse($pageCache->has('page'));
        self::assertSame(1, $this->getConnectionPool()->getConnectionForTable('sys_log')->count('*', 'sys_log', ['type' => 3]));
    }

    #[Test]
    public function cacheCommandPagesIsNotFlushedForEditorsWithoutPermission(): void
    {
        $this->setUpBackendUserWithLanguage(3);

        $this->get(SimpleDataHandlerController::class)->processAjaxRequest(
            new ServerRequest()->withParsedBody(['cacheCmd' => 'pages'])
        );

        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        self::assertTrue($pageCache->has('root'));
        self::assertTrue($pageCache->has('page'));
    }

    public static function pageTreeRefreshDataProvider(): \Generator
    {
        yield 'page title changed' => [0, ['pages' => [2 => ['title' => 'Changed page']]], true];
        yield 'page field without effect on the page tree changed' => [0, ['pages' => [2 => ['description' => 'Changed description']]], false];
        yield 'content created in live' => [0, ['tt_content' => ['NEW1' => ['pid' => 2, 'header' => 'Content']]], false];
        yield 'content created in a workspace' => [1, ['tt_content' => ['NEW1' => ['pid' => 2, 'header' => 'Content']]], true];
    }

    #[Test]
    #[DataProvider('pageTreeRefreshDataProvider')]
    public function pageTreeIsRefreshedWhenDataHandlerReportsIt(int $workspaceId, array $data, bool $expected): void
    {
        $this->getConnectionPool()->getConnectionForTable('sys_workspace')->insert('sys_workspace', ['uid' => 1, 'title' => 'Workspace']);
        $this->setUpBackendUserWithLanguage(1);
        $GLOBALS['BE_USER']->setWorkspace($workspaceId);
        // BackendUtility::setUpdateSignal() is a no-op in CLI context
        Environment::initialize(
            Environment::getContext(),
            false,
            Environment::isComposerMode(),
            Environment::getProjectPath(),
            Environment::getPublicPath(),
            Environment::getVarPath(),
            Environment::getConfigPath(),
            Environment::getCurrentScript(),
            Environment::isWindows() ? 'WINDOWS' : 'UNIX'
        );

        $this->get(SimpleDataHandlerController::class)->processAjaxRequest(
            new ServerRequest()->withParsedBody(['data' => $data])
        );

        $updateSignals = $GLOBALS['BE_USER']->getModuleData(BackendUtility::class . '::getUpdateSignal', 'ses') ?? [];
        self::assertSame($expected, isset($updateSignals['updatePageTree']));
    }

    private function setUpBackendUserWithLanguage(int $uid): void
    {
        $backendUser = $this->setUpBackendUser($uid);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }
}
