<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use \TYPO3\CMS\Core\Resource;

interface Processor {
	public function canProcessTask(Task $task);

	public function processTask(Task $task);
}