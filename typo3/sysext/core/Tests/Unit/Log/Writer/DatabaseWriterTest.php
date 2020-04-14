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

namespace TYPO3\CMS\Core\Tests\Unit\Log\Writer;

use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Log\Writer\DatabaseWriter;
use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\TestingFramework\Core\Unit\UnitTestCase;

/**
 * Test case
 */
class DatabaseWriterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function getTableReturnsPreviouslySetTable()
    {
        $logTable = StringUtility::getUniqueId('logtable_');
        /** @var DatabaseWriter|MockObject $subject */
        $subject = $this->getMockBuilder(DatabaseWriter::class)
            ->setMethods(['dummy'])
            ->disableOriginalConstructor()
            ->getMock();
        $subject->setLogTable($logTable);
        self::assertSame($logTable, $subject->getLogTable());
    }
}
