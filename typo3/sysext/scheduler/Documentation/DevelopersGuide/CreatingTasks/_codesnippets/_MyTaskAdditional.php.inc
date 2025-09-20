<?php

declare(strict_types=1);

namespace MyVendor\MyExtension\Tasks;

use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

final class MyTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    public function getAdditionalFields(
        array &$taskInfo,
        $task, SchedulerModuleController $schedulerModule,
    ): array {
        $fieldId = 'task_myextension_someField';
        $fieldName = 'tx_scheduler[scheduler_myextension_someField]';
        $fieldHtml = '<input class="form-select" name="' . $fieldName . '" id="' . $fieldId . '" />';
        return [
            $fieldId => [
                'code' => $fieldHtml,
                'label' => 'Label or LLL:EXT reference',
                'type' => 'select',
            ]
        ];
    }

    public function validateAdditionalFields(
        array &$submittedData,
        SchedulerModuleController $schedulerModule,
    ): bool {
        if ($submittedData === []) {
            return false;
        }
        // do some other tests
        return true;
    }

    public function saveAdditionalFields(
        array $submittedData,
        AbstractTask $task,
    ): void {
        $task->setTaskParameters([
            'someField' => $submittedData['scheduler_myextension_someField'] ?? '',
        ]);
    }
}
