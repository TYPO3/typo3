<?php
namespace TYPO3\CMS\Scheduler\Task;

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

/**
 * Additional BE fields for tasks which indexes files in a storage
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class FileStorageIndexingAdditionalFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     * @throws \InvalidArgumentException
     */
    public function getAdditionalFields(array &$taskInfo, $task, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        if ($task !== null && !$task instanceof FileStorageIndexingTask) {
            throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275696);
        }
        $additionalFields['scheduler_fileStorageIndexing_storage'] = $this->getAllStoragesField($task, $taskInfo);
        return $additionalFields;
    }

    /**
     * Add a select field of available storages.
     *
     * @param FileStorageIndexingTask $task When editing, reference to the current task object. NULL when adding.
     * @param array $taskInfo
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getAllStoragesField(FileStorageIndexingTask $task = null, $taskInfo)
    {
        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage[] $storages */
        $storages = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class)->findAll();
        $options = [];
        foreach ($storages as $storage) {
            $selected = '';
            if ($task !== null && $task->storageUid === $storage->getUid()) {
                $selected = ' selected="selected"';
            } elseif ((int)$taskInfo['scheduler_fileStorageIndexing_storage'] === $storage->getUid()) {
                $selected = ' selected="selected"';
            }
            $options[] = '<option value="' . $storage->getUid() . '" ' . $selected . ' >' . $storage->getName() . '</option>';
        }

        $fieldName = 'tx_scheduler[scheduler_fileStorageIndexing_storage]';
        $fieldId = 'scheduler_fileStorageIndexing_storage';
        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '">' . implode("\n", $options) . '</select>';

        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageIndexing.storage',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * Validate additional fields
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, \TYPO3\CMS\Scheduler\Controller\SchedulerModuleController $parentObject)
    {
        $value = $submittedData['scheduler_fileStorageIndexing_storage'];
        if (!\TYPO3\CMS\Core\Utility\MathUtility::canBeInterpretedAsInteger($value)) {
            return false;
        }
        if (\TYPO3\CMS\Core\Resource\ResourceFactory::getInstance()->getStorageObject($submittedData['scheduler_fileStorageIndexing_storage']) !== null) {
            return true;
        }
        return false;
    }

    /**
     * Save additional field in task
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        if (!$task instanceof FileStorageIndexingTask) {
            throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275697);
        }
        $task->storageUid = (int)$submittedData['scheduler_fileStorageIndexing_storage'];
    }
}
