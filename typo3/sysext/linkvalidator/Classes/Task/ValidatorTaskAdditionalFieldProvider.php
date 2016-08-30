<?php
namespace TYPO3\CMS\Linkvalidator\Task;

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

use TYPO3\CMS\Backend\Utility\BackendUtility;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Lang\LanguageService;
use TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;

/**
 * This class provides Scheduler Additional Field plugin implementation
 */
class ValidatorTaskAdditionalFieldProvider implements AdditionalFieldProviderInterface
{
    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array $taskInfo Array information of task to return
     * @param ValidatorTask $task Task object
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return array Additional fields
     * @see \TYPO3\CMS\Scheduler\AdditionalFieldProviderInterface->getAdditionalFields($taskInfo, $task, $schedulerModule)
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
        if (empty($taskInfo['configuration'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['configuration'] = '';
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['configuration'] = $task->getConfiguration();
            } else {
                $taskInfo['configuration'] = $task->getConfiguration();
            }
        }
        if (empty($taskInfo['depth'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['depth'] = [];
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['depth'] = $task->getDepth();
            } else {
                $taskInfo['depth'] = $task->getDepth();
            }
        }
        if (empty($taskInfo['page'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['page'] = '';
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['page'] = $task->getPage();
            } else {
                $taskInfo['page'] = $task->getPage();
            }
        }
        if (empty($taskInfo['email'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['email'] = '';
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['email'] = $task->getEmail();
            } else {
                $taskInfo['email'] = $task->getEmail();
            }
        }
        if (empty($taskInfo['emailOnBrokenLinkOnly'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['emailOnBrokenLinkOnly'] = 1;
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            } else {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            }
        }
        if (empty($taskInfo['emailTemplateFile'])) {
            if ($schedulerModule->CMD === 'add') {
                $taskInfo['emailTemplateFile'] = 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
            } elseif ($schedulerModule->CMD === 'edit') {
                $taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
            } else {
                $taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
            }
        }
        $fieldId = 'task_page';
        $fieldCode = '<input type="text" class="form-control" name="tx_scheduler[linkvalidator][page]" id="' . $fieldId . '" value="' . htmlspecialchars($taskInfo['page']) . '">';
        $lang = $this->getLanguageService();
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.page');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        // input for depth
        $fieldId = 'task_depth';
        $fieldValueArray = [
            '0' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_0'),
            '1' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_1'),
            '2' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_2'),
            '3' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_3'),
            '4' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_4'),
            '999' => $lang->sL('LLL:EXT:lang/locallang_core.xlf:labels.depth_infi')
        ];
        $fieldCode = '<select class="form-control" name="tx_scheduler[linkvalidator][depth]" id="' . $fieldId . '">';
        foreach ($fieldValueArray as $depth => $label) {
            $fieldCode .= "\t" . '<option value="' . htmlspecialchars($depth) . '"' .
                        (($depth == $taskInfo['depth']) ? ' selected="selected"' : '') .
                        '>' . $label . '</option>';
        }
        $fieldCode .= '</select>';
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.depth');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_configuration';
        $fieldCode = '<textarea class="form-control" name="tx_scheduler[linkvalidator][configuration]" id="' . $fieldId . '" >' .
                    htmlspecialchars($taskInfo['configuration']) . '</textarea>';
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.conf');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_email';
        $fieldCode = '<textarea class="form-control" rows="5" cols="50" name="tx_scheduler[linkvalidator][email]" id="' . $fieldId . '">' .
                    htmlspecialchars($taskInfo['email']) . '</textarea>';
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.email');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_emailOnBrokenLinkOnly';
        $fieldCode = '<div class="checkbox"><label><input type="checkbox" name="tx_scheduler[linkvalidator][emailOnBrokenLinkOnly]" id="' . $fieldId . '" ' .
                    (htmlspecialchars($taskInfo['emailOnBrokenLinkOnly']) ? 'checked="checked"' : '') . '></label></div>';
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailOnBrokenLinkOnly');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_emailTemplateFile';
        $fieldCode = '<input class="form-control" type="text"  name="tx_scheduler[linkvalidator][emailTemplateFile]" id="' . $fieldId .
                    '" value="' . htmlspecialchars($taskInfo['emailTemplateFile']) . '">';
        $label = $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.emailTemplateFile');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        return $additionalFields;
    }

    /**
     * Mark current value as selected by returning the "selected" attribute
     *
     * @param array $configurationArray Array of configuration
     * @param string $currentValue Value of selector object
     * @return string Html fragment for a selected option or empty
     */
    protected function getSelectedState(array $configurationArray, $currentValue)
    {
        $selected = '';
        if (in_array($currentValue, $configurationArray, true)) {
            $selected = 'selected="selected" ';
        }
        return $selected;
    }

    /**
     * This method checks any additional data that is relevant to the specific task.
     * If the task class is not relevant, the method is expected to return TRUE.
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule)
    {
        $isValid = true;
        // @todo add validation to validate the $submittedData['configuration'] which is normally a comma separated string
        $lang = $this->getLanguageService();
        if (!empty($submittedData['linkvalidator']['email'])) {
            $emailList = GeneralUtility::trimExplode(',', $submittedData['linkvalidator']['email']);
            foreach ($emailList as $emailAdd) {
                if (!GeneralUtility::validEmail($emailAdd)) {
                    $isValid = false;
                    $schedulerModule->addMessage($lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidEmail'), FlashMessage::ERROR);
                }
            }
        }
        if ($res = $this->getDatabaseConnection()->exec_SELECTquery('*', 'pages', 'uid = ' . (int)$submittedData['linkvalidator']['page'])) {
            if ($this->getDatabaseConnection()->sql_num_rows($res) == 0 && $submittedData['linkvalidator']['page'] > 0) {
                $isValid = false;
                $schedulerModule->addMessage(
                    $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidPage'),
                    FlashMessage::ERROR
                );
            }
            $this->getDatabaseConnection()->sql_free_result($res);
        } else {
            $isValid = false;
            $schedulerModule->addMessage(
                $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidPage'),
                FlashMessage::ERROR
            );
        }
        if ($submittedData['linkvalidator']['depth'] < 0) {
            $isValid = false;
            $schedulerModule->addMessage(
                $lang->sL('LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf:tasks.validate.invalidDepth'),
                FlashMessage::ERROR
            );
        }
        return $isValid;
    }

    /**
     * This method is used to save any additional input into the current task object
     * if the task class matches.
     *
     * @param array $submittedData Array containing the data submitted by the user
     * @param AbstractTask $task Reference to the current task object
     * @return void
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var $task ValidatorTask */
        $task->setDepth($submittedData['linkvalidator']['depth']);
        $task->setPage($submittedData['linkvalidator']['page']);
        $task->setEmail($submittedData['linkvalidator']['email']);
        if ($submittedData['linkvalidator']['emailOnBrokenLinkOnly']) {
            $task->setEmailOnBrokenLinkOnly(1);
        } else {
            $task->setEmailOnBrokenLinkOnly(0);
        }
        $task->setConfiguration($submittedData['linkvalidator']['configuration']);
        $task->setEmailTemplateFile($submittedData['linkvalidator']['emailTemplateFile']);
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }

    /**
     * @return \TYPO3\CMS\Core\Database\DatabaseConnection
     */
    protected function getDatabaseConnection()
    {
        return $GLOBALS['TYPO3_DB'];
    }
}
