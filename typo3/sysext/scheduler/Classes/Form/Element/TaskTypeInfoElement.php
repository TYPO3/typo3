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

namespace TYPO3\CMS\Scheduler\Form\Element;

use TYPO3\CMS\Backend\Form\Element\AbstractFormElement;
use TYPO3\CMS\Core\Imaging\IconFactory;
use TYPO3\CMS\Scheduler\Service\TaskService;

/**
 * @internal This is a specific hook implementation and is not considered part of the Public TYPO3 API.
 */
class TaskTypeInfoElement extends AbstractFormElement
{
    public function __construct(
        private readonly TaskService $taskService,
        private readonly IconFactory $iconFactory,
    ) {}

    public function render(): array
    {
        $languageService = $this->getLanguageService();
        $resultArray = $this->initializeResultArray();
        $parameterArray = $this->data['parameterArray'];
        $selectedValue = '';
        if (!empty($parameterArray['itemFormElValue'])) {
            if (is_array($parameterArray['itemFormElValue'])) {
                $selectedValue = (string)$parameterArray['itemFormElValue'][0];
            } else {
                $selectedValue = (string)$parameterArray['itemFormElValue'];
            }
        }

        $task = $this->taskService->getAllTaskTypes()[$selectedValue] ?? null;
        if ($task) {
            $resultArray['html'] = '
                <div class="card mb-0">
                    <div class="card-header">
                        <div class="card-icon">
                            ' . $this->iconFactory->getIcon(($task['icon'] ?? '') ?: 'mimetypes-x-tx_scheduler_task_group')->render() . '
                        </div>
                        <div class="card-header-body">
                            <h2 class="card-title">' . htmlspecialchars($task['title']) . '</h2>
                            <span class="card-subtitle">' . htmlspecialchars($task['description']) . '</span>
                        </div>
                    </div>
                </div>
            ';
        } else {
            $resultArray['html'] = '<div class="alert alert-warning">' . htmlspecialchars($languageService->sL('LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:msg.invalidTaskType')) . ': <code>' . htmlspecialchars($selectedValue) . '</code></div>';
        }

        $resultArray['additionalHiddenFields'][] = '<input type="hidden" name="' . $parameterArray['itemFormElName'] . '" value="' . htmlspecialchars($selectedValue) . '" />';
        return $resultArray;
    }
}
