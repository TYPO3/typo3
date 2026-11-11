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

namespace TYPO3\CMS\Reports\Tests\Functional\Integrity;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Reports\Integrity\DatabaseIntegrityCheck;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class DatabaseIntegrityCheckTest extends FunctionalTestCase
{
    #[Test]
    public function genTreeVisitsEachPageOnlyOnceWithCircularVersionReferences(): void
    {
        $this->importCSVDataSet(__DIR__ . '/Fixtures/CircularPages.csv');
        // Bound the recursion depth, so a missing cycle guard ends in a failing
        // assertion instead of exhausting memory or prepared statements.
        $subject = new class ($this->get(TcaSchemaFactory::class), $this->get(ConnectionPool::class)) extends DatabaseIntegrityCheck {
            private int $depth = 0;

            public function genTree(int $theID, bool $versions = false): void
            {
                if ($this->depth >= 50) {
                    return;
                }
                $this->depth++;
                try {
                    parent::genTree($theID, $versions);
                } finally {
                    $this->depth--;
                }
            }
        };

        $subject->genTree(0);

        self::assertSame([1, 2, 3, 4], array_keys($subject->getPageIdArray()));
        self::assertSame([1 => 4], $subject->getRecStats()['doktype']);
    }
}
