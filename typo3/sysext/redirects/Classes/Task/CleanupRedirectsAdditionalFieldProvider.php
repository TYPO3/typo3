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

namespace TYPO3\CMS\Redirects\Task;

use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * This class provides Scheduler Additional Field plugin implementation
 * @internal This class is a specific Scheduler task implementation and is not part of the TYPO3's Core API.
 */
class CleanupRedirectsAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Default language file of the extension redirects
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:redirects/Resources/Private/Language/locallang.xlf';

    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array $taskInfo Array information of task to return
     * @param CleanupRedirectsTask|null $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return array Additional fields
     * @see AdditionalFieldProviderInterface::getAdditionalFields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        $additionalFields = [];
        foreach (['hitCount', 'days', 'domains', 'statusCodes', 'path'] as $setting) {
            if ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $getter = 'get' . ucfirst($setting);
                $taskInfo[$setting] = $task->$getter();
            }
            $additionalFields[$setting] = $this->getAdditionalField($taskInfo, $setting);
        }
        return $additionalFields;
    }

    protected function getAdditionalField(array &$taskInfo, string $setting): array
    {
        $fieldName = 'tx_scheduler[' . $setting . ']';
        $value = $taskInfo[$setting] ?? '';
        if (is_array($value)) {
            $value = implode(',', $value);
        }
        $fieldHtml = sprintf('<input %s>', GeneralUtility::implodeAttributes([
            'class' => 'form-control',
            'type' => 'text',
            'name' => $fieldName,
            'id' => $setting,
            'value' => $value,
        ], true));

        return [
            'code' => $fieldHtml,
            'label' => $this->languageFile . ':cleanupRedirectsTask.label.' . $setting,
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $setting
        ];
    }

    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): array
    {
        return $submittedData;
    }

    /**
     * @param array $submittedData
     * @param AbstractTask|CleanupRedirectsTask $task
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $domains = $submittedData['domains'] ?? '';
        $domains = $domains !== '' ? GeneralUtility::trimExplode(',', $domains) : [];
        $statusCodes = $submittedData['statusCodes'] ?? '';
        $statusCodes = $statusCodes !== '' ? GeneralUtility::intExplode(',', $statusCodes) : [];
        $task
            ->setDays((int)$submittedData['days'])
            ->setHitCount((int)$submittedData['hitCount'])
            ->setPath((string)$submittedData['path'])
            ->setStatusCodes($statusCodes)
            ->setDomains($domains);
    }
}
