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

namespace TYPO3\CMS\Frontend\Tests\Functional\ContentObject;

use PHPUnit\Framework\Attributes\Test;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Cache\CacheDataCollector;
use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\Http\ServerRequest;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\FrontendTypoScript;
use TYPO3\CMS\Frontend\Cache\CacheInstruction;
use TYPO3\CMS\Frontend\ContentObject\ContentObjectRenderer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class ContentObjectRendererStdWrapCacheTest extends FunctionalTestCase
{
    protected array $configurationToUseInTestInstance = [
        'SYS' => [
            'caching' => [
                'cacheConfigurations' => [
                    // The tests rely on a persisted "hash" cache, testing-framework
                    // usually sets this to NullBackend which would defeat them.
                    'hash' => [
                        'backend' => Typo3DatabaseBackend::class,
                    ],
                ],
            ],
        ],
    ];

    #[Test]
    public function cacheStoreWritesContentIfCachingIsAllowed(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->createRequest(new CacheInstruction()));

        $subject->stdWrap('cachedContent', ['cache.' => ['key' => 'myCacheKey']]);

        self::assertSame('cachedContent', $this->get(CacheManager::class)->getCache('hash')->get('myCacheKey')['content']);
    }

    #[Test]
    public function cacheStoreDoesNotWriteContentIfCachingIsDisabled(): void
    {
        $cacheInstruction = new CacheInstruction();
        $cacheInstruction->disableCache('Test: Disabled cache due to enabled frontend.preview aspect isPreview.');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->createRequest($cacheInstruction));

        $subject->stdWrap('previewContent', ['cache.' => ['key' => 'myCacheKey']]);

        self::assertFalse($this->get(CacheManager::class)->getCache('hash')->get('myCacheKey'));
    }

    #[Test]
    public function cacheReadGetsExistingCachedContentAndDoesNotChangeCacheIfCachingIsAllowed(): void
    {
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->createRequest(new CacheInstruction()));
        $this->get(CacheManager::class)->getCache('hash')->set('myCacheKey', ['content' => 'cachedContent', 'cacheTags' => []]);

        self::assertSame('cachedContent', $subject->stdWrap('voidContent', ['cache.' => ['key' => 'myCacheKey']]));
        self::assertSame('cachedContent', $this->get(CacheManager::class)->getCache('hash')->get('myCacheKey')['content']);
    }

    #[Test]
    public function cacheReadDoesNotGetExistingCachedContentAndDoesNotChangeCacheIfCachingIsDisabled(): void
    {
        $cacheInstruction = new CacheInstruction();
        $cacheInstruction->disableCache('Test: Disabled cache due to enabled frontend.preview aspect isPreview.');
        $subject = $this->get(ContentObjectRenderer::class);
        $subject->setRequest($this->createRequest($cacheInstruction));
        $this->get(CacheManager::class)->getCache('hash')->set('myCacheKey', ['content' => 'cachedContent', 'cacheTags' => []]);

        self::assertSame('previewContent', $subject->stdWrap('previewContent', ['cache.' => ['key' => 'myCacheKey']]));
        self::assertSame('cachedContent', $this->get(CacheManager::class)->getCache('hash')->get('myCacheKey')['content']);
    }

    private function createRequest(CacheInstruction $cacheInstruction): ServerRequestInterface
    {
        $frontendTypoScript = new FrontendTypoScript(new RootNode(), [], [], []);
        $frontendTypoScript->setSetupArray([]);
        $frontendTypoScript->setConfigArray([]);
        return new ServerRequest()
            ->withAttribute('frontend.typoscript', $frontendTypoScript)
            ->withAttribute('frontend.cache.instruction', $cacheInstruction)
            ->withAttribute('frontend.cache.collector', new CacheDataCollector());
    }
}
