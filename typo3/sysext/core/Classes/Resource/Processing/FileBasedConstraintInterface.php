<?php
namespace TYPO3\CMS\Core\Resource\Processing;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2014 Frans Saris <franssaris@gmail.com>
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

/**
 * Base Interface for file processing task like Extractor and FileProcessor that
 * can be constrained to certain Drivers or FileTypes.
 */
interface FileBasedConstraintInterface {

	/**
	 * Returns an array of supported file types
	 * An empty array indicates all file types
	 *
	 * @return array
	 */
	public function getFileTypeRestrictions();

	/**
	 * Get all supported DriverTypes
	 *
	 * Since some processors may only work for local files, and other
	 * are especially made for processing files from remote.
	 *
	 * Returns array of strings with driver names of Drivers which are supported,
	 * If the driver did not register a name, it's the class name.
	 * empty array indicates no restrictions
	 *
	 * @return array
	 */
	public function getDriverRestrictions();

	/**
	 * Returns the data priority of the processing Service.
	 * Defines the precedence if several processors
	 * can handle the same file.
	 *
	 * Should be between 1 and 100, 100 is more important than 1
	 *
	 * @return integer
	 */
	public function getPriority();

}