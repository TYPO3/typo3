<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009-2011 Ingo Renner <ingo@typo3.org>
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
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/


/**
 * Interface for classes who want to provide additional fields when adding a task
 *
 * @author		Ingo Renner <ingo@typo3.org>
 * @package		TYPO3
 * @subpackage	tx_scheduler
 */
interface tx_scheduler_AdditionalFieldProvider {

	/**
	 * Gets additional fields to render in the form to add/edit a task
	 *
	 * @param	array					Values of the fields from the add/edit task form
	 * @param	tx_scheduler_Task		The task object being eddited. Null when adding a task!
	 * @param	tx_scheduler_Module		Reference to the scheduler backend module
	 * @return	array					A two dimensional array, array('Identifier' => array('fieldId' => array('code' => '', 'label' => '', 'cshKey' => '', 'cshLabel' => ''))
	 */
	public function getAdditionalFields(array &$taskInfo, $task, tx_scheduler_Module $schedulerModule);

	/**
	 * Validates the additional fields' values
	 *
	 * @param	array					An array containing the data submitted by the add/edit task form
	 * @param	tx_scheduler_Module		Reference to the scheduler backend module
	 * @return	boolean					TRUE if validation was ok (or selected class is not relevant), FALSE otherwise
	 */
	public function validateAdditionalFields(array &$submittedData, tx_scheduler_Module $schedulerModule);

	/**
	 * Takes care of saving the additional fields' values in the task's object
	 *
	 * @param	array					An array containing the data submitted by the add/edit task form
	 * @param	tx_scheduler_Task		Reference to the scheduler backend module
	 * @return	void
	 */
	public function saveAdditionalFields(array $submittedData, tx_scheduler_Task $task);
}


?>