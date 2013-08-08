<?php
namespace TYPO3\CMS\Core\Resource;

/**
 * A file with (optional) additional meta information.
 */
interface EnrichedFileInterface extends BasicFileInterface, LocalizableFileInterface {

	// TODO add property bag implementation here -> all default properties are in a "default" bag

	public function getProperty($key);

	public function setProperty($key, $value);

	public function getProperties();

	public function getAvailableProperties();
}
