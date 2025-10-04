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

namespace TYPO3\CMS\Scheduler\Controller;

use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use TYPO3\CMS\Backend\Attribute\AsController;
use TYPO3\CMS\Backend\Routing\UriBuilder;
use TYPO3\CMS\Backend\View\BackendViewFactory;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Core\Localization\LanguageService;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Scheduler\Event\ModifyNewSchedulerTaskWizardItemsEvent;
use TYPO3\CMS\Scheduler\Service\TaskService;

/**
 * New Scheduler Task wizard. This is the modal that pops up when clicking "New Task" in scheduler module.
 *
 * @internal This class is a specific Backend controller implementation and is not considered part of the Public TYPO3 API.
 */
#[AsController]
class NewSchedulerTaskController
{
    protected string $returnUrl = '';
    protected array $defaultValues = [];

    public function __construct(
        protected readonly UriBuilder $uriBuilder,
        protected readonly BackendViewFactory $backendViewFactory,
        protected readonly EventDispatcherInterface $eventDispatcher,
        protected readonly TaskService $taskService,
    ) {}

    public function handleRequest(ServerRequestInterface $request): ResponseInterface
    {
        $this->returnUrl = GeneralUtility::sanitizeLocalUrl((string)($request->getParsedBody()['returnUrl'] ?? $request->getQueryParams()['returnUrl'] ?? ''));
        $this->defaultValues = $request->getParsedBody()['defaultValues'] ?? $request->getQueryParams()['defaultValues'] ?? [];

        $wizardItems = $this->eventDispatcher->dispatch(
            new ModifyNewSchedulerTaskWizardItemsEvent(
                $this->getWizardItemsFromTaskRegistry(),
                $request,
            )
        )->getWizardItems();

        $view = $this->backendViewFactory->create($request);
        $view->assign('categoriesJson', GeneralUtility::jsonEncodeForHtmlAttribute($this->organizeWizardItems($wizardItems), false));
        return new HtmlResponse($view->render('NewSchedulerTask/Wizard'));
    }

    /**
     * Convert TaskService categorized tasks to wizard items
     */
    protected function getWizardItemsFromTaskRegistry(): array
    {
        $wizardItems = [];

        foreach ($this->taskService->getCategorizedTaskTypes() as $category => $tasks) {
            // Add category header
            $wizardItems[$category] = [
                'header' => ucfirst($category),
            ];
            // Add tasks for this category
            foreach ($tasks as $taskType => $taskInfo) {
                $wizardItems[$category . '_' . str_replace('\\', '_', $taskType)] = [
                    'title' => $taskInfo['title'],
                    'description' => $taskInfo['description'],
                    // @todo change once tx_scheduler_task get's icons
                    'icon' => $taskInfo['icon'] ?? 'mimetypes-x-tx_scheduler_task_group',
                    'iconOverlay' => $taskInfo['iconOverlay'] ?? '',
                    'taskType' => $taskType,
                    'taskClass' => $taskInfo['className'],
                ];
            }
        }

        return $wizardItems;
    }

    /**
     * Organize wizard items into the categories
     */
    protected function organizeWizardItems(array $wizardItems): array
    {
        $categories = [];
        $currentKey = '';

        foreach ($wizardItems as $wizardKey => $wizardItem) {
            if (isset($wizardItem['header'])) {
                // This is a category
                $currentKey = $wizardKey;
                $categories[$currentKey] = [
                    'identifier' => $currentKey,
                    'label' => $wizardItem['header'],
                    'items' => [],
                ];
            } else {
                if (!($wizardItem['taskType'] ?? false)) {
                    continue;
                }
                // This is a task item
                $item = [
                    'identifier' => $wizardKey,
                    'icon' => $wizardItem['icon'] ?? 'mimetypes-x-tx_scheduler_task_group',
                    'iconOverlay' => $wizardItem['iconOverlay'] ?? '',
                    'label' => $wizardItem['title'] ?? '',
                    'description' => $wizardItem['description'] ?? '',
                    'taskType' => $wizardItem['taskType'],
                ];

                $item['url'] = (string)$this->uriBuilder->buildUriFromRoute('record_edit', [
                    'edit' => [
                        'tx_scheduler_task' => [
                            0 => 'new',
                        ],
                    ],
                    'defVals' => [
                        'tx_scheduler_task' => array_replace_recursive($this->defaultValues, [
                            'pid' => 0,
                            'tasktype' => $wizardItem['taskType'],
                        ]),
                    ],
                    'returnUrl' => $this->returnUrl,
                ]);

                if (!empty($currentKey)) {
                    $categories[$currentKey]['items'][] = $item;
                }
            }
        }

        // Remove empty categories
        return array_filter($categories, static fn(array $category): bool => !empty($category['items']));
    }

    protected function getLanguageService(): LanguageService
    {
        return $GLOBALS['LANG'];
    }
}
