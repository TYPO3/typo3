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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Task\Fixtures;

use TYPO3\CMS\Scheduler\Task\TableGarbageCollectionTask;

/**
 * Removes rows in batches of three, which keeps the number of rows a test has to
 * create to run into more than one batch small.
 */
final class SmallBatchTableGarbageCollectionTask extends TableGarbageCollectionTask
{
    protected const DELETE_BATCH_SIZE = 3;
}
