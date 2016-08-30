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
        return isset($this->registeredTaskTypes[$taskType]) ? $this->registeredTaskTypes[$taskType] : null;
    }

    /**
     * @param string $taskType
     * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
     * @param array $processingConfiguration
     * @return TaskInterface
     * @throws \RuntimeException
     */
    public function getTaskForType($taskType, \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, array $processingConfiguration)
    {
        $taskClass = $this->getClassForTaskType($taskType);
        if ($taskClass === null) {
            throw new \RuntimeException('Unknown processing task "' . $taskType . '"');
        }

        return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($taskClass,
            $processedFile, $processingConfiguration
        );
    }
}
