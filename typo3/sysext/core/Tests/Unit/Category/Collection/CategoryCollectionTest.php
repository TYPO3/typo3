<?php
namespace TYPO3\CMS\Core\Tests\Unit\Category\Collection;

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
use TYPO3\CMS\Core\Category\Collection\CategoryCollection;

/**
 * Test case for \TYPO3\CMS\Core\Category\Collection\CategoryCollection
 */
class CategoryCollectionTest extends \TYPO3\TestingFramework\Core\Unit\UnitTestCase
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
