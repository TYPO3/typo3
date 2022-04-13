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

namespace TYPO3\CMS\Core\Tests\Functional\TypoScript\IncludeTree;

use TYPO3\CMS\Core\Cache\CacheManager;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\CMS\Core\TypoScript\IncludeTree\IncludeNode\RootInclude;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Traverser\IncludeTreeTraverser;
use TYPO3\CMS\Core\TypoScript\IncludeTree\TreeBuilder;
use TYPO3\CMS\Core\TypoScript\IncludeTree\Visitor\IncludeTreeAstBuilderVisitor;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

class TreeBuilderTest extends FunctionalTestCase
{
    public function setUp(): void
    {
        parent::setUp();
        $this->get(CacheManager::class)->getCache('typoscript')->flush();
    }

    /**
     * @test
     */
    public function singleRootTemplate(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
    }

    /**
     * @test
     */
    public function singleRootTemplateLoadsFromGlobals(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/singleRootTemplate.csv');
        $GLOBALS['TYPO3_CONF_VARS']['FE']['defaultTypoScript_constants'] = 'bar = barValue';
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    /**
     * @test
     */
    public function twoPagesTwoTemplates(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplates.csv');
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    /**
     * @test
     */
    public function twoPagesTwoTemplatesBothClear(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoPagesTwoTemplatesBothClear.csv');
        $rootline = [
            [
                'uid' => 2,
                'pid' => 1,
                'is_siteroot' => 0,
            ],
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertNull($ast->getChildByName('foo'));
        self::assertSame('barValue', $ast->getChildByName('bar')->getValue());
    }

    /**
     * @test
     */
    public function twoTemplatesOnPagePrefersTheOneWithLowerSorting(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/twoTemplatesOnPage.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertNull($ast->getChildByName('bar'));
    }

    /**
     * @test
     */
    public function basedOnSimple(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnSimple.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('loadedByBasedOn', $ast->getChildByName('bar')->getValue());
    }

    /**
     * @test
     */
    public function basedOnAfterIncludeStatic(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnAfterIncludeStatic.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('loadedByBasedOn', $ast->getChildByName('bar')->getValue());
    }

    /**
     * @test
     */
    public function basedOnBeforeIncludeStatic(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/SysTemplate/basedOnBeforeIncludeStatic.csv');
        $rootline = [
            [
                'uid' => 1,
                'pid' => 0,
                'is_siteroot' => 0,
            ],
        ];
        /** @var TreeBuilder $treeBuilder */
        $treeBuilder = $this->get(TreeBuilder::class);
        $includeTree = $treeBuilder->getTreeByRootline($rootline, 'constants', false);
        $ast = $this->getAst($includeTree);
        self::assertSame('fooValue', $ast->getChildByName('foo')->getValue());
        self::assertSame('includeStaticTarget', $ast->getChildByName('bar')->getValue());
    }

    /**
     * Helper to calculate AST from given includeTree. Asserting AST
     * details is easier than looking up streams on the IncludeTree,
     * and we can test the Traverser and AstVisitor along the way.
     */
    private function getAst(RootInclude $rootInclude): RootNode
    {
        /** @var IncludeTreeAstBuilderVisitor $astBuilderVisitor */
        $astBuilderVisitor = $this->get(IncludeTreeAstBuilderVisitor::class);
        $traverser = new IncludeTreeTraverser();
        $traverser->addVisitor($astBuilderVisitor);
        $traverser->traverse($rootInclude);
        return $astBuilderVisitor->getAst();
    }
}
