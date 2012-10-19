<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 * Interface which defines a Processor of a file
 */
interface Processor {

	/**
	 * Checks wether the Task is processable by this
	 * processor or if some perequesites are not met
	 *
	 * @param Task $task
	 *
	 * @return boolean
	 */
	public function canProcessTask(Task $task);

	/**
	 * Performs the actual work
	 *
	 * @param Task $task
	 *
	 * @return void
	 */
	public function processTask(Task $task);
}

?>