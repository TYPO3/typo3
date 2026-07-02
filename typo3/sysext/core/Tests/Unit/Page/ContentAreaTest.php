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

namespace TYPO3\CMS\Core\Tests\Unit\Page;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Page\ContentArea;
use TYPO3\CMS\Core\Page\ContentSlideMode;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class ContentAreaTest extends UnitTestCase
{
    #[Test]
    public function recordsCanBeCounted(): void
    {
        $subject = new ContentArea(
            'main',
            'Main',
            0,
            ContentSlideMode::None,
            [],
            [],
            [],
            [
                ['uid' => 1],
                ['uid' => 2],
            ],
        );

        self::assertEquals(2, $subject->count());
    }
}
