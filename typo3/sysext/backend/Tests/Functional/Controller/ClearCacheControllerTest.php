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

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Backend\Controller\ClearCacheController;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ClearCacheControllerTest extends FunctionalTestCase
{
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
        $this->importCSVDataSet(__DIR__ . '/../../../../core/Tests/Functional/Fixtures/be_users_admin.csv');
        $this->importCSVDataSet(__DIR__ . '/Fixtures/ClearCacheController/pages.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        $pageCache->set('root', ['dummy'], ['pageId_1']);
        $pageCache->set('page', ['dummy'], ['pageId_2']);
    }

    #[Test]
    public function flushCachePageActionFlushesCacheOfThePageAndLogsTheCommand(): void
    {
        $response = $this->get(ClearCacheController::class)->flushCachePageAction(
            new ServerRequest()->withParsedBody(['id' => 2])
        );

        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        self::assertTrue(json_decode((string)$response->getBody(), true)['success']);
        self::assertTrue($pageCache->has('root'));
        self::assertFalse($pageCache->has('page'));
        self::assertSame(1, $this->getConnectionPool()->getConnectionForTable('sys_log')->count('*', 'sys_log', ['type' => 3]));
    }

    #[Test]
    public function flushCacheGroupPagesActionFlushesAllPageCaches(): void
    {
        $this->get(ClearCacheController::class)->flushCacheGroupPagesAction(new ServerRequest());

        $pageCache = $this->get(CacheManager::class)->getCache('pages');
        self::assertFalse($pageCache->has('root'));
        self::assertFalse($pageCache->has('page'));
    }
}
