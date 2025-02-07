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

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Localization\LanguageServiceFactory;
use TYPO3\CMS\Core\Messaging\FlashMessageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
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

    #[DataProvider('validateAdditionalFieldsLogsPeriodErrorDataProvider')]
    #[Test]
    public function validateAdditionalFieldsLogsPeriodError(mixed $period): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $submittedData = [
            'RecyclerCleanerPeriod' => $period,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $subject = GeneralUtility::makeInstance(CleanerFieldProvider::class);
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

    #[DataProvider('validateAdditionalFieldsDataProvider')]
    #[Test]
    public function validateAdditionalFieldsLogsTableError(mixed $table): void
    {
        $GLOBALS['LANG'] = $this->get(LanguageServiceFactory::class)->create('default');
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => $table,
        ];
        $subject = GeneralUtility::makeInstance(CleanerFieldProvider::class);
        $subject->validateAdditionalFields($submittedData, $this->get(SchedulerModuleController::class));
        self::assertFalse($this->get(FlashMessageService::class)->getMessageQueueByIdentifier()->isEmpty());
    }

    #[Test]
    public function validateAdditionalFieldsIsTrueIfValid(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $GLOBALS['TCA']['pages'] = ['foo' => 'bar'];
        $subject = GeneralUtility::makeInstance(CleanerFieldProvider::class);
        self::assertTrue($subject->validateAdditionalFields($submittedData, $this->get(SchedulerModuleController::class)));
    }

    #[Test]
    public function saveAdditionalFieldsSavesFields(): void
    {
        $submittedData = [
            'RecyclerCleanerPeriod' => 14,
            'RecyclerCleanerTCA' => ['pages'],
        ];
        $task = new CleanerTask();
        $subject = GeneralUtility::makeInstance(CleanerFieldProvider::class);
        $subject->saveAdditionalFields($submittedData, $task);
        self::assertSame(14, $task->getPeriod());
        self::assertSame(['pages'], $task->getTcaTables());
    }
}
