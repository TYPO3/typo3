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

namespace TYPO3\CMS\Core\Tests\Unit\Routing\Enhancer;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Routing\Enhancer\PluginEnhancer;
use TYPO3\CMS\Core\Routing\Enhancer\VariableProcessor;
use TYPO3\CMS\Core\Routing\Enhancer\VariableProcessorCache;
use TYPO3\CMS\Core\Routing\Route;
use TYPO3\CMS\Core\Routing\RouteCollection;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class PluginEnhancerTest extends UnitTestCase
{
    #[Test]
    public function enhanceForMatchingSeparatesNumericNamespaceFromObjectId(): void
    {
        $this->provideVariableProcessors(1);
        $collection = $this->createCollection();
        $subject = new PluginEnhancer(['namespace' => 'news1', 'routePath' => '/detail/{news_id}']);

        $subject->enhanceForMatching($collection);

        $variantNames = array_values(array_diff(array_keys($collection->all()), ['default']));
        self::assertCount(1, $variantNames);
        self::assertStringStartsWith('enhancer_news1_', $variantNames[0]);
    }

    #[Test]
    public function enhanceForGenerationSeparatesNumericNamespaceFromObjectId(): void
    {
        $this->provideVariableProcessors(1);
        $collection = $this->createCollection();
        $subject = new PluginEnhancer(['namespace' => 'news1', 'routePath' => '/detail/{news_id}']);

        $subject->enhanceForGeneration($collection, ['news1' => ['news_id' => 1]]);

        $variantNames = array_values(array_diff(array_keys($collection->all()), ['default']));
        self::assertCount(1, $variantNames);
        self::assertStringStartsWith('enhancer_news1_', $variantNames[0]);
    }

    #[Test]
    public function enhanceForMatchingKeepsVariantsOfSeveralNamespacesApart(): void
    {
        $this->provideVariableProcessors(2);
        $collection = $this->createCollection();
        $first = new PluginEnhancer(['namespace' => 'news', 'routePath' => '/detail/{news_id}']);
        $second = new PluginEnhancer(['namespace' => 'blog', 'routePath' => '/post/{blog_id}']);

        $first->enhanceForMatching($collection);
        $second->enhanceForMatching($collection);

        $names = array_keys($collection->all());
        self::assertCount(3, $names);
        self::assertSame($names, array_unique($names));
    }

    #[Test]
    public function enhanceForMatchingKeepsVariantsOfTheSameNamespaceApart(): void
    {
        $this->provideVariableProcessors(2);
        $collection = $this->createCollection();
        $first = new PluginEnhancer(['namespace' => 'news', 'routePath' => '/detail/{news_id}']);
        $second = new PluginEnhancer(['namespace' => 'news', 'routePath' => '/list/{page_id}']);

        $first->enhanceForMatching($collection);
        $second->enhanceForMatching($collection);

        $names = array_keys($collection->all());
        self::assertCount(3, $names);
        self::assertSame($names, array_unique($names));
    }

    private function createCollection(): RouteCollection
    {
        $collection = new RouteCollection();
        $collection->add('default', new Route('/'));
        return $collection;
    }

    /**
     * The enhancers resolve the VariableProcessor through GeneralUtility::makeInstance(), which
     * cannot autowire the constructor dependency in a unit test context. Each enhancer instance
     * fetches and then caches exactly one of them.
     */
    private function provideVariableProcessors(int $count): void
    {
        for ($i = 0; $i < $count; $i++) {
            GeneralUtility::addInstance(VariableProcessor::class, new VariableProcessor(new VariableProcessorCache()));
        }
    }
}
