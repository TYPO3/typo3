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

namespace TYPO3\CMS\Scheduler\Tests\Functional\Task;

use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Scheduler\Task\OptimizeDatabaseTableTask;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class OptimizeDatabaseTableTaskTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    #[Test]
    public function executedSuccessfullyWithOneSelectedTable(): void
    {
        $selectedTables = ['be_users'];
        $optimizeDatabaseTableTask = new OptimizeDatabaseTableTask();
        $optimizeDatabaseTableTask->setTaskParameters([
            'tables' => $selectedTables,
        ]);
        self::assertSame($selectedTables, (new \ReflectionProperty($optimizeDatabaseTableTask, 'selectedTables'))->getValue($optimizeDatabaseTableTask));
        self::assertTrue($optimizeDatabaseTableTask->execute());
    }

    #[Test]
    public function executedSuccessfullyWithMultipleSelectedTables(): void
    {
        $selectedTables = [
            'be_users',
            'be_groups',
            'pages',
            'tt_content',
        ];
        $optimizeDatabaseTableTask = new OptimizeDatabaseTableTask();
        $optimizeDatabaseTableTask->setTaskParameters([
            'tables' => $selectedTables,
        ]);
        self::assertSame($selectedTables, (new \ReflectionProperty($optimizeDatabaseTableTask, 'selectedTables'))->getValue($optimizeDatabaseTableTask));
        self::assertTrue($optimizeDatabaseTableTask->execute());
    }
}
