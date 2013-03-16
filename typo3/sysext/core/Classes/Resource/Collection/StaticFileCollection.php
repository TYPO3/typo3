<?php
namespace TYPO3\CMS\Core\Resource\Collection;

/***************************************************************
 * Copyright notice
 *
 * (c) 2011-2013 Andreas Wolf <andreas.wolf@ikt-werk.de>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * A collection containing a static set of files. This collection is persisted
 * to the database with references to all files it contains.
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 */
class StaticFileCollection extends \TYPO3\CMS\Core\Resource\Collection\AbstractFileCollection {

	/**
	 * @var string
	 */
	static protected $type = 'static';

	/**
	 * @var string
	 */
	static protected $itemsCriteriaField = 'items';

	/**
	 * @var string
	 */
	protected $itemTableName = 'sys_file_reference';

	/**
	 * Populates the content-entries of the storage
	 *
	 * Queries the underlying storage for entries of the collection
	 * and adds them to the collection data.
	 *
	 * If the content entries of the storage had not been loaded on creation
	 * ($fillItems = false) this function is to be used for loading the contents
	 * afterwards.
	 *
	 * @return void
	 */
	public function loadContents() {
		/** @var \TYPO3\CMS\Core\Resource\FileRepository $fileRepository */
		$fileRepository = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\FileRepository');
		$fileReferences = $fileRepository->findByRelation('sys_file_collection', 'files', $this->getIdentifier());
		foreach ($fileReferences as $file) {
			$this->add($file);
		}
	}

}


?>