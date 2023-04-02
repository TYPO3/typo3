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

use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Recycler\Task\CleanerFieldProvider;
use TYPO3\CMS\Recycler\Task\CleanerTask;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

final class CleanerFieldProviderTest extends FunctionalTestCase
{
    protected array $coreExtensionsToLoad = ['scheduler'];

    public static function validateAdditionalFieldsLogsPeriodErrorDataProvider(): array
    {
        return [
            ['abc'],
            [new \stdClass()],
            [null],
            [''],
            [0],
            ['1234abc'],
        ];
    }

    /**
     * @test
     * @dataProvider validateAdditionalFieldsLogsPeriodErrorDataProvider
     */
    public function validateAdditionalFieldsLogsPeriodError(mixed $period): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $submittedData = [
            'RecyclerCleanerPeriod' => $period,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $subject = new CleanerFieldProvider();
        $subject->validateAdditionalFields($submittedData, $this->get(SchedulerModuleController::class));
        self::assertFalse($this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->isEmpty());
    }

    public static function validateAdditionalFieldsDataProvider(): array
    {
        return [
            ['abc'],
            [new \stdClass()],
            [null],
            [123],
        ];
    }

    /**
     * @test
     * @dataProvider validateAdditionalFieldsDataProvider
     */
    public function validateAdditionalFieldsLogsTableError(mixed $table): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => $table,
        ];
        $subject = new CleanerFieldProvider();
        $subject->validateAdditionalFields($submittedData, $this->get(SchedulerModuleController::class));
        self::assertFalse($this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->isEmpty());
    }

    /**
     * @test
     */
    public function validateAdditionalFieldsIsTrueIfValid(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $GLOBALS['TCA']['pages'] = ['foo' => 'bar'];
        $subject = new CleanerFieldProvider();
        self::assertTrue($subject->validateAdditionalFields($submittedData, $this->get(SchedulerModuleController::class)));
    }

    /**
     * @test
     */
    public function saveAdditionalFieldsSavesFields(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $task = new CleanerTask();
        $subject = new CleanerFieldProvider();
        $subject->saveAdditionalFields($submittedData, $task);
        self::assertSame(14, $task->getPeriod());
        self::assertSame(['pages'], $task->getTcaTables());
    }
}
