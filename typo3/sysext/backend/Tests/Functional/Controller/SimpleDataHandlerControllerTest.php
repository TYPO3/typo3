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
use TYPO3\CMS\Core\Core\Environment;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SimpleDataHandlerControllerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['workspaces'];

    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ClearCacheController/pages.csv');
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
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $backendUser->setWorkspace($workspaceId);
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

        (new SimpleDataHandlerController())->processAjaxRequest(
            (new ServerRequest())->withParsedBody(['data' => $data])
        );

        $updateSignals = $backendUser->getModuleData(BackendUtility::class . '::getUpdateSignal', 'ses') ?? [];
        self::assertSame($expected, isset($updateSignals['updatePageTree']));
    }
}
