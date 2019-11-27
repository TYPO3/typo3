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
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Messaging\FlashMessage;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\AbstractAdditionalFieldProvider;
use TYPO3\CMS\Scheduler\Controller\SchedulerModuleController;
use TYPO3\CMS\Scheduler\Task\AbstractTask;
use TYPO3\CMS\Scheduler\Task\Enumeration\Action;

/**
 * This class provides Scheduler Additional Field plugin implementation
 * @internal This class is a specific Scheduler task implementation and is not part of the TYPO3's Core API.
 */
class ValidatorTaskAdditionalFieldProvider extends AbstractAdditionalFieldProvider
{
    /**
     * Default language file of the extension linkvalidator
     *
     * @var string
     */
    protected $languageFile = 'LLL:EXT:linkvalidator/Resources/Private/Language/locallang.xlf';

    /**
     * Render additional information fields within the scheduler backend.
     *
     * @param array $taskInfo Array information of task to return
     * @param ValidatorTask|null $task The task object being edited. Null when adding a task!
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return array Additional fields
     * @see AdditionalFieldProviderInterface::getAdditionalFields
     */
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule)
    {
        $additionalFields = [];
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();

        if (empty($taskInfo['configuration'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['configuration'] = $taskInfo['linkvalidator']['configuration'];
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['configuration'] = $task->getConfiguration();
            } else {
                $taskInfo['configuration'] = $task->getConfiguration();
            }
        }

        if (empty($taskInfo['depth'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['depth'] = $taskInfo['linkvalidator']['depth'];
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['depth'] = $task->getDepth();
            } else {
                $taskInfo['depth'] = $task->getDepth();
            }
        }

        if (empty($taskInfo['page'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['page'] = $taskInfo['linkvalidator']['page'];
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['page'] = $task->getPage();
            } else {
                $taskInfo['page'] = $task->getPage();
            }
        }
        if (empty($taskInfo['email'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['email'] = $taskInfo['linkvalidator']['email'];
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['email'] = $task->getEmail();
            } else {
                $taskInfo['email'] = $task->getEmail();
            }
        }
        if (empty($taskInfo['emailOnBrokenLinkOnly'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['emailOnBrokenLinkOnly'] = $taskInfo['linkvalidator']['emailOnBrokenLinkOnly'] ?: 1;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            } else {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            }
        }
        if (empty($taskInfo['emailTemplateFile'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['emailTemplateFile'] = $taskInfo['linkvalidator']['emailTemplateFile'] ?: 'EXT:linkvalidator/Resources/Private/Templates/mailtemplate.html';
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
            } else {
                $taskInfo['emailTemplateFile'] = $task->getEmailTemplateFile();
            }
        }
        $fieldId = 'task_page';
        $fieldCode = '<input type="number" min="0" class="form-control" name="tx_scheduler[linkvalidator][page]" id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars($taskInfo['page'])
            . '">';
        $lang = $this->getLanguageService();
        $label = $lang->sL($this->languageFile . ':tasks.validate.page');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $pageTitle = '';
        if (!empty($taskInfo['page'])) {
            $pageTitle = $this->getPageTitle((int)$taskInfo['page']);
        }
        $additionalFields[$fieldId] = [
            'browser' => 'page',
            'pageTitle' => $pageTitle,
            'code' => $fieldCode,
            'label' => $label
        ];
        // input for depth
        $fieldId = 'task_depth';
        $fieldValueArray = [
            '0' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
            '1' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
            '2' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
            '3' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
            '4' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
            '999' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi')
        ];
        $fieldCode = '<select class="form-control" name="tx_scheduler[linkvalidator][depth]" id="' . $fieldId . '">';
        foreach ($fieldValueArray as $depth => $label) {
            $fieldCode .= "\t" . '<option value="' . htmlspecialchars($depth) . '"'
                . (($depth == $taskInfo['depth']) ? ' selected="selected"' : '') . '>'
                . $label
                . '</option>';
        }
        $fieldCode .= '</select>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.depth');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_configuration';
        $fieldCode = '<textarea class="form-control" name="tx_scheduler[linkvalidator][configuration]" id="'
            . $fieldId
            . '" >'
            . htmlspecialchars($taskInfo['configuration'])
            . '</textarea>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.conf');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_email';
        $fieldCode = '<textarea class="form-control" rows="5" cols="50" name="tx_scheduler[linkvalidator][email]" id="'
            . $fieldId
            . '">'
            . htmlspecialchars($taskInfo['email'])
            . '</textarea>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.email');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_emailOnBrokenLinkOnly';
        $fieldCode = '<div class="checkbox"><label>'
            . '<input type="checkbox" name="tx_scheduler[linkvalidator][emailOnBrokenLinkOnly]" id="' . $fieldId . '" '
            . (htmlspecialchars($taskInfo['emailOnBrokenLinkOnly']) ? 'checked="checked"' : '')
            . '></label></div>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.emailOnBrokenLinkOnly');
        $label = BackendUtility::wrapInHelp('linkvalidator', $fieldId, $label);
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'label' => $label
        ];
        $fieldId = 'task_emailTemplateFile';
        $fieldCode = '<input class="form-control" type="text"  name="tx_scheduler[linkvalidator][emailTemplateFile]" '
            . 'id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars($taskInfo['emailTemplateFile'])
            . '">';
        $label = $lang->sL($this->languageFile . ':tasks.validate.emailTemplateFile');
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
        // @todo add validation to validate the $submittedData['configuration']
        // @todo which is normally a comma separated string
        $lang = $this->getLanguageService();
        if (!empty($submittedData['linkvalidator']['email'])) {
            if (strpos($submittedData['linkvalidator']['email'], ',') !== false) {
                $emailList = GeneralUtility::trimExplode(',', $submittedData['linkvalidator']['email']);
            } else {
                $emailList = GeneralUtility::trimExplode(LF, $submittedData['linkvalidator']['email']);
            }
            foreach ($emailList as $emailAdd) {
                if (!GeneralUtility::validEmail($emailAdd)) {
                    $isValid = false;
                    $this->addMessage(
                        $lang->sL($this->languageFile . ':tasks.validate.invalidEmail'),
                        FlashMessage::ERROR
                    );
                }
            }
        }

        $row = BackendUtility::getRecord('pages', (int)$submittedData['linkvalidator']['page'], '*', '', false);
        if (empty($row)) {
            $isValid = false;
            $this->addMessage(
                $lang->sL($this->languageFile . ':tasks.validate.invalidPage'),
                FlashMessage::ERROR
            );
        }
        if ($submittedData['linkvalidator']['depth'] < 0) {
            $isValid = false;
            $this->addMessage(
                $lang->sL($this->languageFile . ':tasks.validate.invalidDepth'),
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
     */
    public function saveAdditionalFields(array $submittedData, AbstractTask $task)
    {
        /** @var ValidatorTask $task */
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
     * Get the title of the selected page
     *
     * @param int $pageId
     * @return string Page title or empty string
     */
    private function getPageTitle($pageId)
    {
        $page = BackendUtility::getRecord('pages', $pageId, 'title', '', false);
        if ($page === null) {
            return '';
        }
        return $page['title'];
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService()
    {
        return $GLOBALS['LANG'];
    }
}
