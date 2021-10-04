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

namespace TYPO3\CMS\Linkvalidator\Task;

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
    public function getAdditionalFields(array &$taskInfo, $task, SchedulerModuleController $schedulerModule): array
    {
        $additionalFields = [];
        $currentSchedulerModuleAction = $schedulerModule->getCurrentAction();
        $lang = $this->getLanguageService();

        if (empty($taskInfo['configuration'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['configuration'] = $taskInfo['linkvalidator']['configuration'] ?? '';
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['configuration'] = $task->getConfiguration();
            } else {
                $taskInfo['configuration'] = $task->getConfiguration();
            }
        }
        if (empty($taskInfo['depth'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['depth'] = $taskInfo['linkvalidator']['depth'] ?? 0;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['depth'] = $task->getDepth();
            } else {
                $taskInfo['depth'] = $task->getDepth();
            }
        }
        if (empty($taskInfo['page'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['page'] = $taskInfo['linkvalidator']['page'] ?? 0;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['page'] = $task->getPage();
            } else {
                $taskInfo['page'] = $task->getPage();
            }
        }
        if (empty($taskInfo['languages'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['languages'] = $taskInfo['linkvalidator']['languages'] ?? '';
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['languages'] = $task->getLanguages();
            } else {
                $taskInfo['languages'] = $task->getLanguages();
            }
        }
        if (empty($taskInfo['email'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['email'] = $taskInfo['linkvalidator']['email'] ?? '';
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['email'] = $task->getEmail();
            } else {
                $taskInfo['email'] = $task->getEmail();
            }
        }
        if (empty($taskInfo['emailOnBrokenLinkOnly'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['emailOnBrokenLinkOnly'] = ($taskInfo['linkvalidator']['emailOnBrokenLinkOnly'] ?? false) ? (bool)$taskInfo['linkvalidator']['emailOnBrokenLinkOnly'] : true;
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            } else {
                $taskInfo['emailOnBrokenLinkOnly'] = $task->getEmailOnBrokenLinkOnly();
            }
        }
        if (empty($taskInfo['emailTemplateName'])) {
            if ($currentSchedulerModuleAction->equals(Action::ADD)) {
                $taskInfo['emailTemplateName'] = ($taskInfo['linkvalidator']['emailTemplateName'] ?? false) ? $taskInfo['linkvalidator']['emailTemplateName'] : '';
            } elseif ($currentSchedulerModuleAction->equals(Action::EDIT)) {
                $taskInfo['emailTemplateName'] = $task->getEmailTemplateName();
            } else {
                $taskInfo['emailTemplateName'] = $task->getEmailTemplateName();
            }
        }
        $fieldId = 'task_page';
        $fieldCode = '<input type="number" min="0" class="form-control" name="tx_scheduler[linkvalidator][page]" id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars((string)$taskInfo['page'])
            . '">';
        $label = $lang->sL($this->languageFile . ':tasks.validate.page');
        $pageTitle = '';
        if (!empty($taskInfo['page'])) {
            $pageTitle = $this->getPageTitle((int)$taskInfo['page']);
        }
        $additionalFields[$fieldId] = [
            'browser' => 'page',
            'pageTitle' => $pageTitle,
            'code' => $fieldCode,
            'cshTable' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        // input for depth
        $fieldId = 'task_depth';
        $fieldValueArray = [
            '0' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_0'),
            '1' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_1'),
            '2' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_2'),
            '3' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_3'),
            '4' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_4'),
            '999' => $lang->sL('LLL:EXT:core/Resources/Private/Language/locallang_core.xlf:labels.depth_infi'),
        ];
        /** @var array<string, string> $fieldValueArray */
        $fieldCode = '<select class="form-select" name="tx_scheduler[linkvalidator][depth]" id="' . $fieldId . '">';
        foreach ($fieldValueArray as $depth => $label) {
            $fieldCode .= "\t" . '<option value="' . htmlspecialchars((string)$depth) . '"'
                . (($depth === $taskInfo['depth']) ? ' selected="selected"' : '') . '>'
                . $label
                . '</option>';
        }
        $fieldCode .= '</select>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.depth');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        $fieldId = 'task_languages';
        $fieldCode = '<input class="form-control" type="text"  name="tx_scheduler[linkvalidator][languages]" '
            . 'id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars((string)$taskInfo['languages'])
            . '">';
        $label = $lang->sL($this->languageFile . ':tasks.validate.languages');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        $fieldId = 'task_configuration';
        $fieldCode = '<textarea class="form-control" name="tx_scheduler[linkvalidator][configuration]" id="'
            . $fieldId
            . '" >'
            . htmlspecialchars((string)$taskInfo['configuration'])
            . '</textarea>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.conf');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        $fieldId = 'task_email';
        $fieldCode = '<textarea class="form-control" rows="5" cols="50" name="tx_scheduler[linkvalidator][email]" id="'
            . $fieldId
            . '">'
            . htmlspecialchars((string)$taskInfo['email'])
            . '</textarea>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.email');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        $fieldId = 'task_emailOnBrokenLinkOnly';
        $fieldCode = '<div class="form-check">'
            . '<input type="checkbox" class="form-check-input" name="tx_scheduler[linkvalidator][emailOnBrokenLinkOnly]" id="'
            . $fieldId . '" ' . ((bool)$taskInfo['emailOnBrokenLinkOnly'] ? 'checked="checked"' : '')
            . '></div>';
        $label = $lang->sL($this->languageFile . ':tasks.validate.emailOnBrokenLinkOnly');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        $fieldId = 'task_emailTemplateName';
        $fieldCode = '<input class="form-control" type="text"  name="tx_scheduler[linkvalidator][emailTemplateName]" '
            . 'id="'
            . $fieldId
            . '" value="'
            . htmlspecialchars((string)$taskInfo['emailTemplateName'])
            . '">';
        $label = $lang->sL($this->languageFile . ':tasks.validate.emailTemplateName');
        $additionalFields[$fieldId] = [
            'code' => $fieldCode,
            'cshKey' => 'linkvalidator',
            'cshLabel' => $fieldId,
            'label' => $label,
        ];
        return $additionalFields;
    }

    /**
     * This method checks any additional data that is relevant to the specific task.
     * If the task class is not relevant, the method is expected to return TRUE.
     *
     * @param array $submittedData Reference to the array containing the data submitted by the user
     * @param SchedulerModuleController $schedulerModule Reference to the BE module of the Scheduler
     * @return bool TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
     */
    public function validateAdditionalFields(array &$submittedData, SchedulerModuleController $schedulerModule): bool
    {
        $isValid = true;
        $lang = $this->getLanguageService();
        $email = (string)($submittedData['linkvalidator']['email'] ?? '');
        if ($email !== '') {
            $emailList = GeneralUtility::trimExplode((str_contains($email, ',')) ? ',' : LF, $email);
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
        if ($row === null) {
            $isValid = false;
            $this->addMessage(
                $lang->sL($this->languageFile . ':tasks.validate.invalidPage'),
                FlashMessage::ERROR
            );
        }
        if ((int)$submittedData['linkvalidator']['depth'] < 0) {
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
    public function saveAdditionalFields(array $submittedData, AbstractTask $task): void
    {
        $task
            ->setDepth((int)$submittedData['linkvalidator']['depth'])
            ->setPage((int)$submittedData['linkvalidator']['page'])
            ->setLanguages($submittedData['linkvalidator']['languages'])
            ->setEmail($submittedData['linkvalidator']['email'])
            ->setEmailOnBrokenLinkOnly((bool)($submittedData['linkvalidator']['emailOnBrokenLinkOnly'] ?? false))
            ->setConfiguration($submittedData['linkvalidator']['configuration'])
            ->setEmailTemplateName($submittedData['linkvalidator']['emailTemplateName']);
    }

    /**
     * Get the title of the selected page
     *
     * @param int $pageId
     * @return string Page title or empty string
     */
    private function getPageTitle(int $pageId): string
    {
        return (string)(BackendUtility::getRecord('pages', $pageId, 'title', '', false)['title'] ?? '');
    }

    /**
     * @return LanguageService
     */
    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
