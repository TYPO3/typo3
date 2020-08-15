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

namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\SingletonInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * Registry for images processors.
 */
class ProcessorRegistry implements SingletonInterface
{
    /**
     * @var array
     */
    protected $registeredProcessors = [];

    /**
     * Auto register processors from configuration
     */
    public function __construct()
    {
        $this->registeredProcessors = GeneralUtility::makeInstance(DependencyOrderingService::class)
            ->orderByDependencies($this->getRegisteredProcessors());
    }

    /**
     * Finds a matching processor that can process the given task.
     * Registered processors will be tested by their priority from high to low.
     *
     * @param TaskInterface $task
     * @return ProcessorInterface
     */
    public function getProcessorByTask(TaskInterface $task): ProcessorInterface
    {
        $processor = null;

        foreach ($this->registeredProcessors as $key => $processorConfiguration) {
            if (!isset($processorConfiguration['className'])) {
                throw new \RuntimeException(
                    'Missing key "className" for processor configuration "' . $key . '".',
                    1560875741
                );
            }

            $processor = GeneralUtility::makeInstance($processorConfiguration['className']);

            if (!$processor instanceof ProcessorInterface) {
                throw new \RuntimeException(
                    'Processor "' . get_class($processor) . '" needs to implement interface "' . ProcessorInterface::class . '".',
                    1560876288
                );
            }

            if ($processor->canProcessTask($task)) {
                /*
                 * Stop checking for further processors to speed up image processing.
                 * If another processor should be used, it can be registered with higher priority.
                 */
                break;
            }

            $processor = null;
        }

        if ($processor === null) {
            throw new \RuntimeException(
                sprintf('No matching file processor found for task type "%s" and name "%s".', $task->getType(), $task->getName()),
                1560876294
            );
        }

        return $processor;
    }

    /**
     * @return array
     */
    protected function getRegisteredProcessors(): array
    {
        return $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processors'] ?? [];
    }
}
