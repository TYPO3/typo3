<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 * Interface for file processors. All classes capable of processing a file have to implement this interface.
 */
interface Processor {

	/**
	 * Returns TRUE if this processor can process the given task.
	 *
	 * @param Task $task
	 * @return boolean
	 */
	public function canProcessTask(Task $task);

	/**
	 * Processes the given task and sets the processing result in the task object.
	 *
	 * @param Task $task
	 * @return void
	 */
	public function processTask(Task $task);
}

?>