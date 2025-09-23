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

namespace TYPO3\CMS\Recycler\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Recycler\Task\CleanerTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CleanerTaskTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['recycler', 'scheduler'];

    #[Test]
    public function taskRemovesDeletedPages(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/Fixtures/pages.csv');
        $subject = new CleanerTask();
        $subject->setTaskParameters(['selected_tables' => 'pages']);
        $result = $subject->execute();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/Assertion/pages_deleted.csv');
        self::assertTrue($result);
    }

    #[Test]
    public function taskRemovesOnlyPagesLongerDeletedThanPeriod(): void
    {
        $this->importCSVDataSet(__DIR__ . '/DataSet/Fixtures/pages.csv');
        $subject = new CleanerTask();
        $utcTimeZone = new \DateTimeZone('UTC');

        // this is when the test was created. One of the fixtures (uid 4) has this date
        $creationDate = date_create_immutable_from_format('Y-m-d H:i:s', '2020-09-28 00:00:00', $utcTimeZone);
        // we want to set the period in a way that older records get deleted, but not the one created today
        $difference = $creationDate->diff(new \DateTime('today', $utcTimeZone), true);
        // let's set the amount of days one higher than the reference date
        $period = (int)$difference->format('%a') + 1;
        $subject->setTaskParameters(['selected_tables' => 'pages', 'number_of_days' => $period]);
        $result = $subject->execute();
        $this->assertCSVDataSet(__DIR__ . '/DataSet/Assertion/pages_deleted_with_period.csv');
        self::assertTrue($result);
    }

    #[Test]
    public function taskFailsOnError(): void
    {
        $subject = new CleanerTask();
        $subject->setTaskParameters(['selected_tables' => 'not_existing_table']);
        $result = $subject->execute();
        self::assertFalse($result);
    }
}
