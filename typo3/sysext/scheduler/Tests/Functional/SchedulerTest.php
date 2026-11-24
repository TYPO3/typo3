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

namespace TYPO3\CMS\Scheduler\Tests\Functional;

use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Scheduler\Domain\Repository\SchedulerTaskRepository;
use TYPO3\CMS\Scheduler\Scheduler;
use TYPO3\CMS\Scheduler\Task\TaskSerializer;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class SchedulerTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    #[Test]
    public function constructorSkipsTasksWithEmptyExecutions(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable('tx_scheduler_task');
        $connection->insert(
            'tx_scheduler_task',
            ['uid' => 1, 'serialized_executions' => ''],
            ['serialized_executions' => Connection::PARAM_LOB]
        );

        new Scheduler(
            new NullLogger(),
            self::createStub(TaskSerializer::class),
            self::createStub(SchedulerTaskRepository::class),
        );

        $value = $connection->select(['serialized_executions'], 'tx_scheduler_task', ['uid' => 1])->fetchOne();
        // PostgreSQL returns bytea columns as stream resources
        self::assertSame('', is_resource($value) ? (string)stream_get_contents($value) : (string)$value);
    }
}
