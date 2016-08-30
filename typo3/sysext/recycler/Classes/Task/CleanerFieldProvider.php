<?php
namespace TYPO3\CMS\Recycler\Task;

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

use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes
 * datasets flagged as "deleted" from the DB.
 */
class CleanerFieldProvider implements \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface
{
    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param \TYPO3\CMS\Recycler\Task\CleanerTask $task The task object being edited. NULL when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        if ($schedulerModule->CMD === 'edit') {
            $taskInfo['RecyclerCleanerTCA'] = $task->getTcaTables();
            $taskInfo['RecyclerCleanerPeriod'] = $task->getPeriod();
        }

        $additionalFields['period'] = [
            'code' => '<input type="text" class="form-control" name="tx_scheduler[RecyclerCleanerPeriod]" value="' . $taskInfo['RecyclerCleanerPeriod'] . '">',
            'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskPeriod',
            'cshKey' => '',
            'cshLabel' => 'task_recyclerCleaner_selectedPeriod'
        ];

        $additionalFields['tca'] = [
            'code' => $this->getTcaSelectHtml($taskInfo['RecyclerCleanerTCA']),
            'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTCA',
            'cshKey' => '',
            'cshLabel' => 'task_recyclerCleaner_selectedTables'
        ];

        return $additionalFields;
    }

    /**
     * Gets the select-box from the TCA-fields
     *
     * @param array $selectedTables
     * @return string
     */
    protected function getTcaSelectHtml($selectedTables = [])
    {
        if (!is_array($selectedTables)) {
            $selectedTables = [];
        }
        $tcaSelectHtml = '<select name="tx_scheduler[RecyclerCleanerTCA][]" multiple="multiple" class="form-control" size="10">';

        $options = [];
        foreach ($GLOBALS['TCA'] as $table => $tableConf) {
            if (!$tableConf['ctrl']['adminOnly'] && !empty($tableConf['ctrl']['delete'])) {
                $selected = in_array($table, $selectedTables, true) ? ' selected="selected"' : '';
                $tableTitle = $this->getLanguageService()->sL($tableConf['ctrl']['title']);
                $options[$tableTitle] = '<option' . $selected . ' value="' . $table . '">' . htmlspecialchars($tableTitle . ' (' . $table . ')') . '</option>';
            }
        }
        ksort($options);

        $tcaSelectHtml .= implode('', $options);
        $tcaSelectHtml .= '</select>';

        return $tcaSelectHtml;
    }

    /**
     * Validates the additional fields' values
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $validPeriod = $this->validateAdditionalFieldPeriod($submittedData['RecyclerCleanerPeriod'], $schedulerModule);
        $validTca = $this->validateAdditionalFieldTca($submittedData['RecyclerCleanerTCA'], $schedulerModule);

        return $validPeriod && $validTca;
    }

    /**
     * Validates the selected Tables
     *
     * @param array $tca The given TCA-tables as array
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldTca($tca, SchedulerModuleController $schedulerModule)
    {
        return $this->checkTcaIsNotEmpty($tca, $schedulerModule) && $this->checkTcaIsValid($tca, $schedulerModule);
    }

    /**
     * Checks if the array is empty
     *
     * @param array $tca The given TCA-tables as array
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function checkTcaIsNotEmpty($tca, SchedulerModuleController $schedulerModule)
    {
        if (is_array($tca) && !empty($tca)) {
            $validTca = true;
        } else {
            $schedulerModule->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorTCAempty', true),
                FlashMessage::ERROR
            );
            $validTca = false;
        }

        return $validTca;
    }

    /**
     * Checks if the given tables are in the TCA
     *
     * @param array $tca The given TCA-tables as array
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function checkTcaIsValid(array $tca, SchedulerModuleController $schedulerModule)
    {
        $checkTca = false;
        foreach ($tca as $tcaTable) {
            if (!isset($GLOBALS['TCA'][$tcaTable])) {
                $checkTca = false;
                $schedulerModule->addMessage(
                    sprintf($this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorTCANotSet', true), $tcaTable),
                    FlashMessage::ERROR
                );
                break;
            } else {
                $checkTca = true;
            }
        }

        return $checkTca;
    }

    /**
     * Validates the input of period
     *
     * @param int $period The given period as integer
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldPeriod($period, SchedulerModuleController $schedulerModule)
    {
        if (!empty($period) && filter_var($period, FILTER_VALIDATE_INT) !== false) {
            $validPeriod = true;
        } else {
            $schedulerModule->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorPeriod', true),
                FlashMessage::ERROR
            );
            $validPeriod = false;
        }

        return $validPeriod;
    }

    /**
     * Takes care of saving the additional fields' values in the task's object
     *
     * @param array $submittedData An array containing the data submitted by the add/edit task form
     * @param AbstractTask $task Reference to the scheduler backend module
     * @return void
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof CleanerTask) {
            throw new \InvalidArgumentException(
                'Expected a task of type \TYPO3\CMS\Recycler\Task\CleanerTask, but got ' . get_class($task),
                1329219449
            );
        }

        $task->setTcaTables($submittedData['RecyclerCleanerTCA']);
        $task->setPeriod($submittedData['RecyclerCleanerPeriod']);
    }

    /**
     * @return \TYPO3\CMS\Lang\LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
