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
 * Interface for classes which hook into extFileFunctions and do additional processData processing.
 *
 * @author	Xavier Perseguers <typo3@perseguers.ch>
 * @package TYPO3
 * @subpackage t3lib
 */

interface t3lib_extFileFunctions_processDataHook {

	/**
	 * Post-process a file action.
	 *
	 * @param	string						The action
	 * @param	array						The parameter sent to the action handler
	 * @param	array						The results of all calls to the action handler
	 * @param	t3lib_extFileFunctions		parent t3lib_extFileFunctions object
	 * @return	void
	 */
	public function processData_postProcessAction($action, array $cmdArr, array $result, t3lib_extFileFunctions $parentObject);

}

?>