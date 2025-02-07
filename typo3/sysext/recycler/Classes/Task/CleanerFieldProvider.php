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

namespace TYPO3\CMS\Recycler\Task;

use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Schema\Capability\TcaSchemaCapability;
use TYPO3\CMS\Core\Schema\TcaSchemaFactory;
use TYPO3\CMS\Core\Type\ContextualFeedbackSeverity;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\SchedulerManagementAction;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * A task that should be run regularly that deletes
 * datasets flagged as "deleted" from the DB.
 * @internal
 */
class CleanerFieldProvider extends AbstractAdditionalFieldProvider
{
    protected TcaSchemaFactory $tcaSchemaFactory;

    /**
     * We cannot use Autoconfigure because Services.yaml excludes this provider explicitly
     * because this file depends on typo3/cms-scheduler being present.
     */
    public function __construct(
        ?TcaSchemaFactory $tcaSchemaFactory = null
    ) {
        $this->tcaSchemaFactory = $tcaSchemaFactory ?? GeneralUtility::makeInstance(TcaSchemaFactory::class);
    }

    /**
     * Gets additional fields to render in the form to add/edit a task
     *
     * @param array $taskInfo Values of the fields from the add/edit task form
     * @param CleanerTask $task The task object being edited. NULL when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the scheduler backend module
     * @return array A two-dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if ($currentSchedulerModuleAction === SchedulerManagementAction::EDIT) {
            $taskInfo['RecyclerCleanerTCA'] = $task->getTcaTables();
            $taskInfo['RecyclerCleanerPeriod'] = $task->getPeriod();
        }

        $additionalFields = [];
        $fieldCode = '<input type="number" class="form-control" name="tx_scheduler[RecyclerCleanerPeriod]" value="' . ($taskInfo['RecyclerCleanerPeriod'] ?? 0) . '" min="1">';
        $additionalFields['period'] = [
            'code' => $fieldCode,
            'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskPeriod',
            'cshKey' => '',
            'cshLabel' => 'task_recyclerCleaner_selectedPeriod',
            'type' => 'input',
        ];

        $additionalFields['tca'] = [
            'code' => $this->getTcaSelectHtml($taskInfo['RecyclerCleanerTCA'] ?? []),
            'label' => 'LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskTCA',
            'cshKey' => '',
            'cshLabel' => 'task_recyclerCleaner_selectedTables',
            'type' => 'select',
        ];

        return $additionalFields;
    }

    /**
     * Gets the select-box from the TCA-fields
     */
    protected function getTcaSelectHtml(array $selectedTables): string
    {
        $tcaSelectHtml = '<select name="tx_scheduler[RecyclerCleanerTCA][]" multiple="multiple" class="form-select" size="10">';

        $options = [];
        foreach ($this->tcaSchemaFactory->all() as $table => $schema) {
            if (!$schema->hasCapability(TcaSchemaCapability::SoftDelete)) {
                continue;
            }
            $selected = in_array($table, $selectedTables, true) ? ' selected="selected"' : '';
            $tableTitle = !empty($schema->getRawConfiguration()['title']) ? $this->getLanguageService()->sL($schema->getRawConfiguration()['title']) : '';
            $options[$tableTitle . ' ' . $table] = '<option' . $selected . ' value="' . $table . '">' . htmlspecialchars($tableTitle . ' (' . $table . ')') . '</option>';
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
        $validPeriod = $this->validateAdditionalFieldPeriod($submittedData['RecyclerCleanerPeriod']);
        $validTca = $this->validateAdditionalFieldTca($submittedData['RecyclerCleanerTCA'] ?? []);

        return $validPeriod && $validTca;
    }

    /**
     * Validates the selected Tables
     *
     * @param array $tca The given TCA-tables as array(or mixed, incoming data)
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldTca(mixed $tca): bool
    {
        return $this->checkTcaIsNotEmpty($tca) && $this->checkTcaIsValid($tca);
    }

    /**
     * Checks if the array is empty
     *
     * @param mixed $tca The given TCA-tables as array (or mixed, incoming data)
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function checkTcaIsNotEmpty(mixed $tca): bool
    {
        if (is_array($tca) && !empty($tca)) {
            $validTca = true;
        } else {
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorTCAempty'),
                ContextualFeedbackSeverity::ERROR
            );
            $validTca = false;
        }

        return $validTca;
    }

    /**
     * Checks if the given tables are in the TCA
     *
     * @param array $tca The given TCA-tables as array
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function checkTcaIsValid(array $tca): bool
    {
        $checkTca = false;
        foreach ($tca as $tcaTable) {
            if (!$this->tcaSchemaFactory->has($tcaTable)) {
                $checkTca = false;
                $this->addMessage(
                    sprintf($this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorTCANotSet'), $tcaTable),
                    ContextualFeedbackSeverity::ERROR
                );
                break;
            }
            $checkTca = true;
        }

        return $checkTca;
    }

    /**
     * Validates the input of period
     *
     * @param int $period The given period as integer
     * @return bool TRUE if validation was ok, FALSE otherwise
     */
    protected function validateAdditionalFieldPeriod($period): bool
    {
        if (filter_var($period, FILTER_VALIDATE_INT) !== false && $period > 0) {
            $validPeriod = true;
        } else {
            $this->addMessage(
                $this->getLanguageService()->sL('LLL:EXT:recycler/Resources/Private/Language/locallang_tasks.xlf:cleanerTaskErrorPeriod'),
                ContextualFeedbackSeverity::ERROR
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
     * @throws \InvalidArgumentException
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        if (!$task instanceof CleanerTask) {
            throw new \InvalidArgumentException(
                'Expected a task of type ' . CleanerTask::class . ', but got ' . get_class($task),
                1329219449
            );
        }

        $task->setTcaTables($submittedData['RecyclerCleanerTCA']);
        $task->setPeriod((int)$submittedData['RecyclerCleanerPeriod']);
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
