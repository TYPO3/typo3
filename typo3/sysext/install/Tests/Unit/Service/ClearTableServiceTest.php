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

namespace TYPO3\CMS\Install\Tests\Unit\Service;

use TYPO3\CMS\Install\Service\ClearTableService;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class ClearTableServiceTest extends UnitTestCase
{
    /**
     * @test
     */
    public function clearSelectedTableThrowsWithInvalidTableName(): void
    {
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionCode(1501942151);
        (new ClearTableService())->clearSelectedTable('foo');
    }
}
