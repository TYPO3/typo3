<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2010-2011 Xavier Perseguers <typo3@perseguers.ch>
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
 *  A copy is found in the textfile GPL.txt and important notices to the license
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
 * Interface for classes which hook into TCEmain and do additional processing
 * after the upload of a file.
 *
 * @author	Xavier Perseguers <typo3@perseguers.ch>
 * @package TYPO3
 * @subpackage t3lib
 */

interface t3lib_TCEmain_processUploadHook {

	/**
	 * Post-process a file upload.
	 *
	 * @param	string			The uploaded file
	 * @param	t3lib_TCEmain	parent t3lib_TCEmain object
	 * @return	void
	 */
	public function processUpload_postProcessAction(&$filename, t3lib_TCEmain $parentObject);

}

?>