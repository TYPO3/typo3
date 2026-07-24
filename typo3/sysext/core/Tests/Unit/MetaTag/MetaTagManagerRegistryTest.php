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

namespace TYPO3\CMS\Core\Tests\Unit\MetaTag;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\MetaTag\GenericMetaTagManager;
use TYPO3\CMS\Core\MetaTag\Html5MetaTagManager;
use TYPO3\CMS\Core\MetaTag\MetaTagManagerRegistry;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class MetaTagManagerRegistryTest extends UnitTestCase
{
    #[Test]
    public function getManagerForPropertyReturnsFirstManagerHandlingTheProperty(): void
    {
        $html5Manager = new Html5MetaTagManager();
        $genericManager = new GenericMetaTagManager();

        $registry = new MetaTagManagerRegistry(['html5' => $html5Manager, 'generic' => $genericManager]);

        self::assertSame($html5Manager, $registry->getManagerForProperty('description'));
        self::assertSame($genericManager, $registry->getManagerForProperty('unknown-property'));
    }

    #[Test]
    public function getAllManagersReturnsManagersKeyedByIdentifierInGivenOrder(): void
    {
        $html5Manager = new Html5MetaTagManager();
        $genericManager = new GenericMetaTagManager();

        $registry = new MetaTagManagerRegistry(['html5' => $html5Manager, 'generic' => $genericManager]);

        self::assertSame(['html5' => $html5Manager, 'generic' => $genericManager], $registry->getAllManagers());
    }

    #[Test]
    public function stateIsAppliedToRegisteredManagerInstances(): void
    {
        $html5Manager = new Html5MetaTagManager();
        $genericManager = new GenericMetaTagManager();
        $registry = new MetaTagManagerRegistry(['html5' => $html5Manager, 'generic' => $genericManager]);

        $html5Manager->addProperty('description', 'foo');
        $state = $registry->getState();
        self::assertSame(
            [['content' => 'foo', 'subProperties' => []]],
            $state['instances'][Html5MetaTagManager::class]['properties']['description'],
        );

        $registry->updateState(['instances' => []]);
        self::assertSame([], $html5Manager->getProperty('description'));

        $registry->updateState($state);
        self::assertSame($state, $registry->getState());
    }
}
