<?php

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

namespace TYPO3\CMS\Core\Tests\Unit\Category\Collection;

use TYPO3\CMS\Core\Category\Collection\CategoryCollection;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case for \TYPO3\CMS\Core\Category\Collection\CategoryCollection
 */
class CategoryCollectionTest extends UnitTestCase
{
    /**
     * @test
     * @covers \TYPO3\CMS\Core\Category\Collection\CategoryCollection::__construct
     */
    public function missingTableNameArgumentForObjectCategoryCollection()
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1341826168);

        new CategoryCollection();
    }
}
