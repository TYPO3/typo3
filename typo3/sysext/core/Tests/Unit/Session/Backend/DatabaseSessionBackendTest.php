<?php
declare(strict_types=1);
namespace TYPO3\CMS\Core\Tests\Unit\Session\Backend;

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

use TYPO3\CMS\Core\Session\Backend\DatabaseSessionBackend;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseSessionBackendTest extends UnitTestCase
{
    /**
     * @test
     */
    public function validateConfigurationThrowsExceptionIfTableNameIsMissingInConfiguration()
    {
        $subject = new DatabaseSessionBackend();
        $subject->initialize('default', []);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionCode(1442996707);
        $subject->validateConfiguration();
    }
}
