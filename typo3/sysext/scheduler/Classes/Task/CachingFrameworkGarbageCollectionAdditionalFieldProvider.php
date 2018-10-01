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

use TYPO3\CMS\Core\Cache\Backend\Typo3DatabaseBackend;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * Additional BE fields for caching framework garbage collection task.
 * Creates a multi selectbox with all available cache backends to select from.
 * @internal This class is a specific scheduler task implementation is not considered part of the Public TYPO3 API.
 */
class CachingFrameworkGarbageCollectionAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Add a multi select box with all available cache backends.
     *
     * @param array $taskInfo Reference to the array containing the info used in the add/edit form
     * @param AbstractTask|null $task When editing, reference to the current task. NULL when adding.
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return array Array containing all the information pertaining to the additional fields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        // Initialize selected fields
        if (empty($taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'])) {
            $taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'] = [];
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                // In case of new task, set to dbBackend if it's available
                if (in_array(Typo3DatabaseBackend::class, $this->getRegisteredBackends())) {
                    $taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'][] = Typo3DatabaseBackend::class;
                }
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                // In case of editing the task, set to currently selected value
                $taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends'] = $task->selectedBackends;
            }
        }
        $fieldName = 'tx_scheduler[scheduler_cachingFrameworkGarbageCollection_selectedBackends][]';
        $fieldId = 'task_cachingFrameworkGarbageCollection_selectedBackends';
        $fieldOptions = $this->getCacheBackendOptions($taskInfo['scheduler_cachingFrameworkGarbageCollection_selectedBackends']);
        $fieldHtml = '<select class="form-control" name="' . $fieldName . '" id="' . $fieldId . '" class="from-control" size="10" multiple="multiple">' . $fieldOptions . '</select>';
        $additionalFields[$fieldId] = [
            'code' => $fieldHtml,
            'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.cachingFrameworkGarbageCollection.selectBackends',
            'cshKey' => '_MOD_system_txschedulerM1',
            'cshLabel' => $fieldId
        ];
        return $additionalFields;
    }

    /**
     * Checks that all selected backends exist in available backend list
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the calling object (Scheduler's BE module)
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validData = true;
        $availableBackends = $this->getRegisteredBackends();
        if (is_array($submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'])) {
            $invalidBackends = array_diff($submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'], $availableBackends);
            if (!empty($invalidBackends)) {
                $this->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.selectionOfNonExistingCacheBackends'), FlashMessage::ERROR);
                $validData = false;
            }
        } else {
            $this->addMessage($GLOBALS['LANG']->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.noCacheBackendSelected'), FlashMessage::ERROR);
            $validData = false;
        }
        return $validData;
    }

    /**
     * Save selected backends in task object
     *
     * @param array $submittedData Contains data submitted by the user
     * @param \TYPO3\CMS\Scheduler\Task\AbstractTask $task Reference to the current task object
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        $task->selectedBackends = $submittedData['scheduler_cachingFrameworkGarbageCollection_selectedBackends'];
    }

    /**
     * Build select options of available backends and set currently selected backends
     *
     * @param array $selectedBackends Selected backends
     * @return string HTML of selectbox options
     */
    protected function getCacheBackendOptions(array $selectedBackends)
    {
        $options = [];
        $availableBackends = $this->getRegisteredBackends();
        foreach ($availableBackends as $backendName) {
            if (in_array($backendName, $selectedBackends)) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $options[] = '<option value="' . $backendName . '"' . $selected . '>' . $backendName . '</option>';
        }
        return implode('', $options);
    }

    /**
     * Get all registered caching framework backends
     *
     * @return array Registered backends
     */
    protected function getRegisteredBackends()
    {
        $backends = [];
        $cacheConfigurations = $GLOBALS['TYPO3_CONF_VARS']['SYS']['caching']['cacheConfigurations'];
        if (is_array($cacheConfigurations)) {
            foreach ($cacheConfigurations as $cacheConfiguration) {
                $backend = $cacheConfiguration['backend'];
                if (!in_array($backend, $backends)) {
                    $backends[] = $backend;
                }
            }
        }
        return $backends;
    }
}
