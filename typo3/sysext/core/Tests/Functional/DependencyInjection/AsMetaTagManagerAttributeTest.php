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

namespace TYPO3\CMS\Core\Tests\Functional\DependencyInjection;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;
use TYPO3Tests\TestMetaTagManager\MetaTag\FixtureMetaTagManager;

final class AsMetaTagManagerAttributeTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        'typo3/sysext/core/Tests/Functional/Fixtures/Extensions/test_meta_tag_manager',
    ];

    #[Test]
    public function metaTagManagersAreRegisteredViaAttribute(): void
    {
        $registry = $this->get(MetaTagManagerRegistry::class);

        self::assertInstanceOf(FixtureMetaTagManager::class, $registry->getManagerForProperty('x-fixture'));
    }

    #[Test]
    public function genericMetaTagManagerIsOrderedLastAndHandlesUnclaimedProperties(): void
    {
        $registry = $this->get(MetaTagManagerRegistry::class);
        $managers = $registry->getAllManagers();

        self::assertSame('generic', array_key_last($managers));
        self::assertInstanceOf(GenericMetaTagManager::class, $registry->getManagerForProperty('some-unclaimed-property'));
        self::assertContains('fixture', array_keys($managers));
    }
}
