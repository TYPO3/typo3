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

use TYPO3\CMS\Core\Resource\Index\ExtractorInterface;
use TYPO3\CMS\Core\Resource\Index\ExtractorRegistry;
use TYPO3\CMS\Core\Resource\ResourceFactory;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Core\Utility\MathUtility;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;

/**
 * Additional BE fields for task which extracts metadata from storage
 *
 */
class FileStorageExtractionAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Add additional fields
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|NULL $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     * @throws \InvalidArgumentException
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $parentObject)
    {
        if ($task !== null && !$task instanceof FileStorageExtractionTask) {
            throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275695);
        }
        $additionalFields['scheduler_fileStorageIndexing_storage'] = $this->getAllStoragesField($task);
        $additionalFields['scheduler_fileStorageIndexing_fileCount'] = $this->getFileCountField($task);
        $additionalFields['scheduler_fileStorageIndexing_registeredExtractors'] = $this->getRegisteredExtractorsField($task);
        return $additionalFields;
    }

    /**
     * Returns a field configuration including a selectbox for available storages
     *
     * @param FileStorageExtractionTask $task When editing, reference to the current task object. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getAllStoragesField(FileStorageExtractionTask $task = null)
    {
        /** @var \TYPO3\CMS\Core\Resource\ResourceStorage[] $storages */
        $storages = GeneralUtility::makeInstance(\TYPO3\CMS\Core\Resource\StorageRepository::class)->findAll();
        $options = [];
        foreach ($storages as $storage) {
            if ($task !== null && $task->storageUid === $storage->getUid()) {
                $options[] = '<option value="' . $storage->getUid() . '" selected="selected">' . $storage->getName() . '</option>';
            } else {
                $options[] = '<option value="' . $storage->getUid() . '">' . $storage->getName() . '</option>';
            }
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
     * Returns a field configuration including an input field for the file count
     *
     * @param FileStorageExtractionTask $task When editing, reference to the current task object. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getFileCountField(FileStorageExtractionTask $task = null)
    {
        $fieldName = 'tx_scheduler[scheduler_fileStorageIndexing_fileCount]';
        $fieldId = 'scheduler_fileStorageIndexing_fileCount';
        $fieldValue = $task !== null ? (int)$task->maxFileCount : 100;
        $fieldHtml = '<input type="text" class="form-control" name="' . $fieldName . '" id="' . $fieldId . '" value="' . htmlspecialchars($fieldValue) . '">';

        $fieldConfiguration = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.fileCount',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $fieldConfiguration;
    }

    /**
     * Returns a field configuration telling about the status of registered extractors.
     *
     * @param FileStorageExtractionTask $task When editing, reference to the current task object. NULL when adding.
     * @return array Array containing all the information pertaining to the additional fields
     */
    protected function getRegisteredExtractorsField(FileStorageExtractionTask $task = null)
    {
        $extractors = ExtractorRegistry::getInstance()->getExtractors();

        if (empty($extractors)) {
            $labelKey = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.without_extractors';
            $content = '<span class="label label-warning">'
                . htmlspecialchars($this->getLanguageService()->sL($labelKey))
                . '</span>';
        } else {
            // Assemble the extractor bullet list first.
            $labelKey = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.extractor';
            $bullets = [];
            foreach ($extractors as $extractor) {
                $bullets[] = sprintf(
                    '<li title="%s">%s</li>',
                    get_class($extractor),
                    sprintf($this->getLanguageService()->sL($labelKey), $this->formatExtractorClassName($extractor), $extractor->getPriority())
                );
            }

            // Finalize content assembling.
            $labelKey = 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors.with_extractors';
            $title = $this->getLanguageService()->sL($labelKey);
            $content = '<p>' . htmlspecialchars($title) . '</p>';
            $content .= '<ul>' . implode(LF, $bullets) . '</ul>';
        }

        $fieldConfiguration = [
            'code' => $content,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.fileStorageExtraction.registeredExtractors',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => 'scheduler_fileStorageIndexing_registeredExtractors'
        ];
        return $fieldConfiguration;
    }

    /**
     * Validate additional fields
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $parentObject Reference to the calling object (Scheduler's BE module)
     * @return bool True if validation was ok (or selected class is not relevant), false otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $parentObject)
    {
        if (
            !MathUtility::canBeInterpretedAsInteger($submittedData['scheduler_fileStorageIndexing_storage'])
            || !MathUtility::canBeInterpretedAsInteger($submittedData['scheduler_fileStorageIndexing_fileCount'])
        ) {
            return false;
        } elseif (ResourceFactory::getInstance()->getStorageObject($submittedData['scheduler_fileStorageIndexing_storage']) === null) {
            return false;
        } elseif (!MathUtility::isIntegerInRange($submittedData['scheduler_fileStorageIndexing_fileCount'], 1, 9999)) {
            return false;
        }
        return true;
    }

    /**
     * Save additional field in task
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     * @return void
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, \TYPO3\CMS\Scheduler\Task\AbstractTask $task)
    {
        if ($task !== null && !$task instanceof FileStorageExtractionTask) {
            throw new \InvalidArgumentException('Task not of type FileStorageExtractionTask', 1384275698);
        }
        $task->storageUid = (int)$submittedData['scheduler_fileStorageIndexing_storage'];
        $task->maxFileCount = (int)$submittedData['scheduler_fileStorageIndexing_fileCount'];
    }

    /**
     * Since the class name can be very long considering the namespace, only take the final
     * part for better readability. The FQN of the class will be displayed as tooltip.
     *
     * @param ExtractorInterface $extractor
     * @return string
     */
    protected function formatExtractorClassName(ExtractorInterface $extractor)
    {
        $extractorParts = explode('\\', get_class($extractor));
        return array_pop($extractorParts);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
