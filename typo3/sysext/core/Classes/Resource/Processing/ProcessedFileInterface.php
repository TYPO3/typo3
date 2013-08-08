<?php
namespace TYPO3\CMS\Core\Resource\Processing;

use TYPO3\CMS\Core\Resource\BasicFileInterface;

// TODO check if it is good to extend BasicFileInterface here
interface ProcessedFileInterface extends BasicFileInterface {
	public function getTask();

	public function getOriginalFile();
}
