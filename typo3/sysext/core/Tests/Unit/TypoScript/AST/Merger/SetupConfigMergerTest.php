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

namespace TYPO3\CMS\Core\Tests\Unit\TypoScript\AST\Merger;

use TYPO3\CMS\Core\TypoScript\AST\Merger\SetupConfigMerger;
use TYPO3\CMS\Core\TypoScript\AST\Node\ChildNode;
use TYPO3\CMS\Core\TypoScript\AST\Node\RootNode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class SetupConfigMergerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function emptyConfigReturnsEmptyRootNode(): void
    {
        self::assertEquals(new RootNode(), (new SetupConfigMerger())->merge(null, null));
    }

    /**
     * @test
     */
    public function existingConfigIsKept(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $expected = new RootNode();
        $expected->addChild($configChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, null));
    }

    /**
     * @test
     */
    public function newChildInPageConfigIsAdded(): void
    {
        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfigChild->setValue('foo1');
        $pageConfig->addChild($pageConfigChild);

        $expected = new RootNode();
        $expected->addChild($pageConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge(null, $pageConfig));
    }

    /**
     * @test
     */
    public function newChildInPageConfigIsAddedToExistingConfig(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('bar');
        $pageConfigChild->setValue('bar1');
        $pageConfig->addChild($pageConfigChild);

        $expected = new RootNode();
        $expected->addChild($configChild);
        $expected->addChild($pageConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function newNestedChildInPageConfigIsAddedToExistingConfig(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('bar');
        $pageConfigChild->setValue('bar1');
        $pageConfig->addChild($pageConfigChild);
        $pageConfigNestedChild = new ChildNode('nestedBar');
        $pageConfigNestedChild->setValue('nestedBar1');
        $pageConfigChild->addChild($pageConfigNestedChild);

        $expected = new RootNode();
        $expected->addChild($configChild);
        $expected->addChild($pageConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function childInPageConfigWithoutValueDoesNotOverrideExistingChildConfigValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfig->addChild($pageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expectedChild->setValue('foo1');
        $expected->addChild($expectedChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function childInPageConfigWithEmptyValueOverridesExistingChildConfigValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfigChild->setValue('');
        $pageConfig->addChild($pageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expectedChild->setValue('');
        $expected->addChild($expectedChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function childInPageConfigWithDifferentValueOverridesExistingChildConfigValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $configChild->setValue('foo1');
        $config->addChild($configChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfigChild->setValue('foo2');
        $pageConfig->addChild($pageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expectedChild->setValue('foo2');
        $expected->addChild($expectedChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function newNestedChildInPageConfigIsAddedToExistingChildConfig(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $config->addChild($configChild);
        $nestedConfigChild = new ChildNode('nestedConfigChild');
        $nestedConfigChild->setValue('nestedConfigChildValue');
        $configChild->addChild($nestedConfigChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfig->addChild($pageConfigChild);
        $nestedPageConfigChild = new ChildNode('nestedPageConfigChild');
        $nestedPageConfigChild->setValue('nestedPageConfigChildValue');
        $pageConfigChild->addChild($nestedPageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expected->addChild($expectedChild);
        $expectedNestedConfigChild = new ChildNode('nestedConfigChild');
        $expectedNestedConfigChild->setValue('nestedConfigChildValue');
        $expectedChild->addChild($expectedNestedConfigChild);
        $expectedNestedPageConfigChild = new ChildNode('nestedPageConfigChild');
        $expectedNestedPageConfigChild->setValue('nestedPageConfigChildValue');
        $expectedChild->addChild($expectedNestedPageConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function nestedChildInPageConfigWithoutValueDoesNotOverrideExistingNestedChildValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $config->addChild($configChild);
        $nestedConfigChild = new ChildNode('nestedConfigChild');
        $nestedConfigChild->setValue('nestedConfigChildValue');
        $configChild->addChild($nestedConfigChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfig->addChild($pageConfigChild);
        $nestedPageConfigChild = new ChildNode('nestedConfigChild');
        $pageConfigChild->addChild($nestedPageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expected->addChild($expectedChild);
        $expectedNestedConfigChild = new ChildNode('nestedConfigChild');
        $expectedNestedConfigChild->setValue('nestedConfigChildValue');
        $expectedChild->addChild($expectedNestedConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function nestedChildInPageConfigWithEmptyValueOverridesExistingNestedChildValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $config->addChild($configChild);
        $nestedConfigChild = new ChildNode('nestedConfigChild');
        $nestedConfigChild->setValue('nestedConfigChildValue');
        $configChild->addChild($nestedConfigChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfig->addChild($pageConfigChild);
        $nestedPageConfigChild = new ChildNode('nestedConfigChild');
        $nestedPageConfigChild->setValue('');
        $pageConfigChild->addChild($nestedPageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expected->addChild($expectedChild);
        $expectedNestedConfigChild = new ChildNode('nestedConfigChild');
        $expectedNestedConfigChild->setValue('');
        $expectedChild->addChild($expectedNestedConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }

    /**
     * @test
     */
    public function nestedChildInPageConfigWithValueOverridesExistingNestedChildValue(): void
    {
        $config = new ChildNode('config');
        $configChild = new ChildNode('foo');
        $config->addChild($configChild);
        $nestedConfigChild = new ChildNode('nestedConfigChild');
        $nestedConfigChild->setValue('nestedConfigChildValue1');
        $configChild->addChild($nestedConfigChild);

        $pageConfig = new ChildNode('config');
        $pageConfigChild = new ChildNode('foo');
        $pageConfig->addChild($pageConfigChild);
        $nestedPageConfigChild = new ChildNode('nestedConfigChild');
        $nestedPageConfigChild->setValue('nestedConfigChildValue2');
        $pageConfigChild->addChild($nestedPageConfigChild);

        $expected = new RootNode();
        $expectedChild = new ChildNode('foo');
        $expected->addChild($expectedChild);
        $expectedNestedConfigChild = new ChildNode('nestedConfigChild');
        $expectedNestedConfigChild->setValue('nestedConfigChildValue2');
        $expectedChild->addChild($expectedNestedConfigChild);

        self::assertEquals($expected, (new SetupConfigMerger())->merge($config, $pageConfig));
    }
}
