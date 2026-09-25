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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\ViewHelpers\SearchResult;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\IndexedSearch\ViewHelpers\SearchResult\RatingViewHelper;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class RatingViewHelperTest extends UnitTestCase
{
    #[Test]
    public function rankFreqRendersZeroPercentForZeroOrderValue(): void
    {
        $subject = new RatingViewHelper();
        $subject->setArguments([
            'row' => ['order_val' => 0],
            'firstRow' => [],
            'sortOrder' => 'rank_freq',
        ]);
        self::assertSame('0%', $subject->render());
    }

    #[Test]
    public function rankFlagRendersZeroPercentForRowWithoutRating(): void
    {
        $subject = new RatingViewHelper();
        $subject->setArguments([
            'row' => ['order_val1' => 0, 'order_val2' => 0],
            'firstRow' => ['order_val2' => 5],
            'sortOrder' => 'rank_flag',
        ]);
        self::assertSame('0%', $subject->render());
    }
}
