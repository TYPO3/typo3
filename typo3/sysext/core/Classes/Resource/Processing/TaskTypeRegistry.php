<?php
namespace TYPO3\CMS\Core\Resource\Processing;

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

use TYPO3\CMS\Core\Resource\ProcessedFile;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * The registry for task types.
 */
class TaskTypeRegistry implements \TYPO3\CMS\Core\SingletonInterface
{
    /**
     * @var array
     */
    protected $registeredTaskTypes = [];

    /**
     * Register task types from configuration
     */
    public function __construct()
    {
        $this->registeredTaskTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processingTaskTypes'];
    }

    /**
     * Returns the class that implements the given task type.
     *
     * @param string $taskType
     * @return string
     */
    protected function getClassForTaskType($taskType)
    {
        return $this->registeredTaskTypes[$taskType] ?? null;
    }

    /**
     * @param string $taskType
     * @param ProcessedFile $processedFile
     * @param array $processingConfiguration
     * @return TaskInterface
     * @throws \RuntimeException
     */
    public function getTaskForType($taskType, ProcessedFile $processedFile, array $processingConfiguration)
    {
        $taskClass = $this->getClassForTaskType($taskType);
        if ($taskClass === null) {
            throw new \RuntimeException('Unknown processing task "' . $taskType . '"', 1476049767);
        }

        return GeneralUtility::makeInstance($taskClass, $processedFile, $processingConfiguration);
    }
}
