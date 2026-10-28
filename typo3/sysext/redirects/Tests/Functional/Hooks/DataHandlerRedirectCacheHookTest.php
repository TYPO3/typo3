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

namespace TYPO3\CMS\Redirects\Tests\Functional\Hooks;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\DataHandling\DataHandler;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Redirects\Service\RedirectCacheService;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DataHandlerRedirectCacheHookTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['redirects'];

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
        $this->importCSVDataSet(__DIR__ . '/Fixtures/RedirectCache/sys_redirect.csv');
        $backendUser = $this->setUpBackendUser(1);
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->createFromUserPreferences($backendUser);
    }

    #[Test]
    public function changingSourceHostRebuildsCachesOfPreviousAndNewSourceHost(): void
    {
        self::assertSame([1], $this->getCachedRedirectUids('old.example.com'));
        self::assertSame([], $this->getCachedRedirectUids('new.example.com'));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['sys_redirect' => [1 => ['source_host' => 'new.example.com']]], []);
        $dataHandler->process_datamap();

        self::assertSame([], $this->getCachedRedirectUids('old.example.com'));
        self::assertSame([1], $this->getCachedRedirectUids('new.example.com'));
    }

    #[Test]
    public function creatingRedirectRebuildsCacheOfItsSourceHost(): void
    {
        self::assertSame([2], $this->getCachedRedirectUids('other.example.com'));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start(['sys_redirect' => ['NEW1' => [
            'pid' => 0,
            'source_host' => 'other.example.com',
            'source_path' => '/new',
            'target' => 'https://example.com/target',
        ]]], []);
        $dataHandler->process_datamap();

        self::assertSame([2, (int)$dataHandler->substNEWwithIDs['NEW1']], $this->getCachedRedirectUids('other.example.com'));
    }

    #[Test]
    public function deletingRedirectRebuildsCacheOfItsSourceHost(): void
    {
        self::assertSame([1], $this->getCachedRedirectUids('old.example.com'));

        $dataHandler = $this->get(DataHandler::class);
        $dataHandler->start([], ['sys_redirect' => [1 => ['delete' => 1]]]);
        $dataHandler->process_cmdmap();

        self::assertSame([], $this->getCachedRedirectUids('old.example.com'));
    }

    /**
     * @return list<int>
     */
    private function getCachedRedirectUids(string $sourceHost): array
    {
        $uids = [];
        foreach ($this->get(RedirectCacheService::class)->getRedirects($sourceHost) as $redirectsByPath) {
            foreach ($redirectsByPath as $redirects) {
                foreach ($redirects as $redirect) {
                    $uids[] = (int)$redirect['uid'];
                }
            }
        }
        sort($uids);
        return $uids;
    }
}
