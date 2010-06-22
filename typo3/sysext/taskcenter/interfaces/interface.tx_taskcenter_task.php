<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2010 Georg Ringer <typo3@ringerge.org>
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
 * Interface for classes which provide a task.
 *
 * @author		Georg Ringer <typo3@ringerge.org
 * @package		TYPO3
 * @subpackage	tx_taskcenter
 *
 */
interface tx_taskcenter_Task {

	/**
	 * returns the content for a task
	 *
	 * @return	string	A task rendered HTML
	 */
	public function getTask();

	/**
	 * returns the overview of a task
	 *
	 * @return	string	A task rendered HTML
	 */
	public function getOverview();
}

?>