<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012-2013 Andreas Wolf <andreas.wolf@typo3.org>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * The registry for task types.
 */
class TaskTypeRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	/**
	 * @var array
	 */
	protected $registeredTaskTypes = array();

	/**
	 * Register task types from configuration
	 */
	public function __construct() {
		$this->registeredTaskTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processingTaskTypes'];
	}

	/**
	 * Returns the class that implements the given task type.
	 *
	 * @param string $taskType
	 * @return string
	 */
	protected function getClassForTaskType($taskType) {
		return isset($this->registeredTaskTypes[$taskType]) ? $this->registeredTaskTypes[$taskType] : NULL;
	}

	/**
	 * @param string $taskType
	 * @param \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile
	 * @param array $processingConfiguration
	 * @return TaskInterface
	 * @throws \RuntimeException
	 */
	public function getTaskForType($taskType, \TYPO3\CMS\Core\Resource\ProcessedFile $processedFile, array $processingConfiguration) {
		$taskClass = $this->getClassForTaskType($taskType);
		if ($taskClass === NULL) {
			throw new \RuntimeException('Unknown processing task "' . $taskType . '"');
		}

		return \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance($taskClass,
			$processedFile, $processingConfiguration
		);
	}
}

?>