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

namespace TYPO3\CMS\Core\Tests\Functional\Site\Set;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Site\Set\CategoryRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CategoryRegistryTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_sets',
    ];

    #[Test]
    public function resolveCategoriesAndSettings(): void
    {
        $categoryRegistry = $this->get(CategoryRegistry::class);

        $categories = $categoryRegistry->getCategories('typo3tests/set-1');
        self::assertCount(1, $categories);
        self::assertSame('Foo', $categories[0]->label);

        self::assertCount(1, $categories[0]->settings);
        self::assertSame('foo.bar', $categories[0]->settings[0]->key);
        self::assertSame('Foo Bar', $categories[0]->settings[0]->label);
    }

    #[Test]
    public function categoriesAndSettingsCanBeOverriden(): void
    {
        $categoryRegistry = $this->get(CategoryRegistry::class);

        $categories = $categoryRegistry->getCategories('typo3tests/set-1-override');
        self::assertCount(1, $categories);
        self::assertSame('Foo Override', $categories[0]->label);

        self::assertCount(1, $categories[0]->settings);
        self::assertSame('foo.bar', $categories[0]->settings[0]->key);
        self::assertSame('Foo Bar Override', $categories[0]->settings[0]->label);
    }
}
