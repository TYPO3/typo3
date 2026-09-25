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

namespace TYPO3\CMS\IndexedSearch\Tests\Unit\Utility;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\IndexedSearch\Utility\IndexedSearchUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class IndexedSearchUtilityTest extends UnitTestCase
{
    #[Test]
    public function getExplodedSearchStringIgnoresSingleMultiByteCharacterWords(): void
    {
        self::assertSame([], IndexedSearchUtility::getExplodedSearchString('é', 'AND', []));
    }

    #[Test]
    public function getExplodedSearchStringKeepsMultiByteWordsWithTwoCharacters(): void
    {
        $result = IndexedSearchUtility::getExplodedSearchString('éa', 'AND', []);
        self::assertSame('éa', $result[0]['sword'] ?? null);
    }
}
