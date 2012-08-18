<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Oliver Hader <oliver.hader@typo3.org>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Converter which transforms simple types to Tx_Extbase_Domain_Model_FileReference.
 *
 * @api experimental! This class is experimental and subject to change!
 */
class Tx_Extbase_Property_TypeConverter_FileReferenceConverter extends Tx_Extbase_Property_TypeConverter_AbstractFileFolderConverter implements t3lib_Singleton {
	/**
	 * @var array<string>
	 */
	protected $sourceTypes = array('integer');

	/**
	 * @var string
	 */
	protected $targetType = 'Tx_Extbase_Domain_Model_FileReference';

	/**
	 * @var string
	 */
	protected $expectedObjectType = 't3lib_file_FileReference';

	/**
	 * @param integer $source
	 * @return t3lib_file_FileReference
	 */
	protected function getObject($source) {
		return $this->fileFactory->getFileReferenceObject($source);
	}
}
?>