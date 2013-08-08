<?php

interface IndexableFileInterface {
	/**
	 * Returns the uid of this file record.
	 *
	 * @return integer
	 */
	public function getUid();

	/**
	 * Returns the index status of this file.
	 *
	 * @return boolean
	 */
	public function isIndexed();

	/**
	 * Returns all properties that have been changed in this instance of the
	 * file. Note that this only gives a useful result for files that have
	 * already been indexed.
	 *
	 * @return array
	 */
	public function getChangedProperties();

	/**
	 * Returns TRUE if properties of this file have been changed.
	 *
	 * @return boolean
	 */
	public function isChanged();
}
