<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

/**
 * The registry for task types.
 */
class TaskTypeRegistry implements \TYPO3\CMS\Core\SingletonInterface {

	protected $registeredTaskTypes = array();

	public function __construct() {
		$this->registeredTaskTypes = $GLOBALS['TYPO3_CONF_VARS']['SYS']['fal']['processingTaskTypes'];
	}

	/**
	 * Returns the class that implements the given task type.
	 *
	 * @param string $taskType
	 * @return string
	 */
	public function getClassForTaskType($taskType) {
		return $this->registeredTaskTypes[$taskType];
	}
}

?>