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

namespace TYPO3\CMS\Core\Tests\Unit\Resource\Driver;

use TYPO3\CMS\Core\Resource\Driver\AbstractDriver;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

final class AbstractDriverTest extends UnitTestCase
{
    /**
     * @test
     */
    public function isCaseSensitiveFileSystemReturnsTrueIfNothingIsConfigured(): void
    {
        $subject = $this->getMockForAbstractClass(AbstractDriver::class, [], '', false);
        self::assertTrue($subject->isCaseSensitiveFileSystem());
    }
}
