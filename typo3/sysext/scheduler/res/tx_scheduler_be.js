/***************************************************************
*  Copyright notice
*
*  (c) 2009-2010 Francois Suter <francois@typo3.org>
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
 * This javascript file is used in the Scheduler's backend module
 * It relies on ExtJS core being loaded
 *
 * @author	Francois Suter <francois@typo3.org>
 */

/**
 * Global variable to keep track of checked/unchecked status of all
 * checkboxes for execution selection
 *
 * @var	boolean
 */
var allCheckedStatus = false;

/**
 * This method reacts on changes to the task class
 * It switches on or off the relevant extra fields
 *
 * @param	theSelector: select form item where the selection was made
 * @return	void
 */
function actOnChangedTaskClass(theSelector) {
	var taskClass = theSelector.options[theSelector.selectedIndex].value;
		// Hide all extra fields
		// Show only relevant extra fields
	Ext.select('.extraFields').setDisplayed(false);
	Ext.select('.extra_fields_' + taskClass).setDisplayed(true);
}

/**
 * This method reacts on changes to the type of a task, i.e. single or recurring,
 * by showing or hiding the relevant form fields
 *
 * @param	theSelector: select form item where the selection was made
 * @return	void
 */
function actOnChangedTaskType(theSelector) {
		// Get task type from selected value, or set default value
	var taskType;
	if (theSelector.selectedIndex) {
		taskType = theSelector.options[theSelector.selectedIndex].value;
	} else {
		taskType = 1;
	}
		// Single task
		// Hide all fields related to recurring tasks
	if (taskType == 1) {
		Ext.fly('task_end_row').setDisplayed(false);
		Ext.fly('task_frequency_row').setDisplayed(false);
		Ext.fly('task_multiple_row').setDisplayed(false);

		// Recurring task
		// Show all fields related to recurring tasks
	} else {
		Ext.fly('task_end_row').setDisplayed(true);
		Ext.fly('task_frequency_row').setDisplayed(true);
		Ext.fly('task_multiple_row').setDisplayed(true);
	}
}

/**
 * This method reacts on the checking of a toggle,
 * activating or not the check of all other checkboxes
 *
 * @return	void
 */
function toggleCheckboxes() {
		// Toggle status of global variable
	allCheckedStatus = !allCheckedStatus;
		// Get all checkboxes with proper class
	var checkboxes = Ext.select('.checkboxes');
	var count = checkboxes.getCount();
		// Set them all to same status as main checkbox
	for (var i = 0; i < count; i++) {
		checkboxes.item(i).dom.checked = allCheckedStatus;
	}
}

/**
 * Ext.onReader functions
 *
 * onClick event for scheduler task execution from backend module
 */
Ext.onReady(function(){
	Ext.addBehaviors({
			// Add a listener for click on scheduler execute button
		'#scheduler_executeselected@click' : function(e, t){
				// Get all active checkboxes with proper class
			var checkboxes = Ext.select('.checkboxes:checked');
			var count = checkboxes.getCount();
			var idParts;

				// Set the status icon all to same status: running
			for (var i = 0; i < count; i++) {
				idParts = checkboxes.item(i).id.split('_');
				Ext.select('#executionstatus_' + idParts[1]).item(0).dom.src = TYPO3.settings.scheduler.runningIcon;
			}
		}
	});
});
