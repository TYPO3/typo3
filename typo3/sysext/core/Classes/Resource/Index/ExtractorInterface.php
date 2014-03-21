<?php
namespace TYPO3\CMS\Core\Resource\Index;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2013 Steffen Ritter <steffen.ritter@typo3.org>
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
 *  A copy is found in the text file GPL.txt and important notices to the license
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

use TYPO3\CMS\Core\Resource;

/**
 * An Interface for MetaData extractors the FAL Indexer uses
 */
interface ExtractorInterface {

	/**
	 * Returns an array of supported file types;
	 * An empty array indicates all filetypes
	 *
	 * @return array
	 */
	public function getFileTypeRestrictions();


	/**
	 * Get all supported DriverClasses
	 *
	 * Since some extractors may only work for local files, and other extractors
	 * are especially made for grabbing data from remote.
	 *
	 * Returns array of string with driver names of Drivers which are supported,
	 * If the driver did not register a name, it's the classname.
	 * empty array indicates no restrictions
	 *
	 * @return array
	 */
	public function getDriverRestrictions();

	/**
	 * Returns the data priority of the extraction Service.
	 * Defines the precedence of Data if several extractors
	 * extracted the same property.
	 *
	 * Should be between 1 and 100, 100 is more important than 1
	 *
	 * @return integer
	 */
	public function getPriority();

	/**
	 * Returns the execution priority of the extraction Service
	 * Should be between 1 and 100, 100 means runs as first service, 1 runs at last service
	 *
	 * @return integer
	 */
	public function getExecutionPriority();

	/**
	 * Checks if the given file can be processed by this Extractor
	 *
	 * @param Resource\File $file
	 * @return boolean
	 */
	public function canProcess(Resource\File $file);

	/**
	 * The actual processing TASK
	 *
	 * Should return an array with database properties for sys_file_metadata to write
	 *
	 * @param Resource\File $file
	 * @param array $previousExtractedData optional, contains the array of already extracted data
	 * @return array
	 */
	public function extractMetaData(Resource\File $file, array $previousExtractedData = array());


}
