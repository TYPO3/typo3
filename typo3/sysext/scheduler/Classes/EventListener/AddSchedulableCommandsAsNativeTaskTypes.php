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

namespace TYPO3\CMS\Scheduler\EventListener;

use TYPO3\CMS\Core\Attribute\AsEventListener;
use TYPO3\CMS\Core\Configuration\Event\BeforeTcaOverridesEvent;
use TYPO3\CMS\Core\Console\CommandRegistry;
use TYPO3\CMS\Scheduler\Task\ExecuteSchedulableCommandTask;

final readonly class AddSchedulableCommandsAsNativeTaskTypes
{
    public function __construct(
        private CommandRegistry $commandRegistry,
    ) {}

    #[AsEventListener]
    public function __invoke(BeforeTcaOverridesEvent $event): void
    {
        $tca = $event->getTca();
        foreach ($this->commandRegistry->getSchedulableCommandsConfiguration() as $commandIdentifier => $commandConfiguration) {
            $tca['tx_scheduler_task']['columns']['tasktype']['config']['items'][] = [
                'label' => $commandConfiguration['name'],
                'description' => $commandConfiguration['description'],
                'value' => $commandIdentifier,
                'icon' => 'mimetypes-x-tx_scheduler_task_group',
                'group' => explode(':', $commandIdentifier)[0],
            ];
            $tca['tx_scheduler_task']['types'][$commandIdentifier] = [
                'showitem' => '
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:general,
                        tasktype,
                        task_group,
                        description,
                        parameters,
                    --div--;LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:scheduler.form.palettes.timing,
                        execution_details,
                        nextexecution,
                        --palette--;;lastexecution,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:access,
                        disable,
                    --div--;LLL:EXT:core/Resources/Private/Language/Form/locallang_tabs.xlf:extended,
                ',
                'columnsOverrides' => [
                    'parameters' => [
                        'label' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommand.command_configuration',
                        'description' => 'LLL:EXT:scheduler/Resources/Private/Language/locallang.xlf:label.schedulableCommand.command_configuration.description',
                        'config' => [
                            'renderType' => 'schedulableCommandConfiguration',
                        ],
                    ],
                ],
                'taskOptions' => [
                    // @todo When introducing the AsRecordTypeHandler attribute we can get rid of this option again
                    'className' => ExecuteSchedulableCommandTask::class,
                ],
            ];
        }
        $event->setTca($tca);
    }
}
