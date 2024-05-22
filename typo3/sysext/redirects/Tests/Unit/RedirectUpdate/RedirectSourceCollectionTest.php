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

namespace TYPO3\CMS\Redirects\Tests\Unit\RedirectUpdate;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceCollection;
use TYPO3\CMS\Redirects\RedirectUpdate\RedirectSourceInterface;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RedirectSourceCollectionTest extends UnitTestCase
{
    #[Test]
    public function countReturnsZeroIfNoItemsAdded(): void
    {
        $count = (new RedirectSourceCollection())->count();
        self::assertSame(0, $count);
    }

    #[Test]
    public function countReturnsCorrectCountOfItemsAdded(): void
    {
        $item = $this->createMock(RedirectSourceInterface::class);
        $subject = new RedirectSourceCollection($item, $item, $item);
        self::assertCount(3, $subject);
    }

    #[Test]
    public function allReturnsItemsInTheSameOrderTheyHaveBeenAdded(): void
    {
        $item1 = $this->createMock(RedirectSourceInterface::class);
        $item2 = $this->createMock(RedirectSourceInterface::class);
        $item3 = $this->createMock(RedirectSourceInterface::class);
        $subject = new RedirectSourceCollection($item3, $item1, $item2);
        self::assertSame([$item3, $item1, $item2], $subject->all());
    }

    #[Test]
    public function throwsTypeExceptionIfInvalidItemIsAdded(): void
    {
        $this->expectException(\TypeError::class);

        /** @phpstan-ignore-next-line Explicitly testing invalid code, thus tell phpstan to not report it. */
        new RedirectSourceCollection(new \stdClass());
    }
}
