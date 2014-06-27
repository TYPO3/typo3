/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */


/**
 * observes clicks edit icons and make inline edit
 *
 * @author	Steffen Kamper
 * @author	Jeff Segars
 */

var ModuleConstantEditor = Class.create({
	/**
	 * initially register event listeners
	 */
	initialize: function() {

			// initialize event listeners
		Event.observe(document, 'dom:loaded', function(){
			$$('.typo3-tstemplate-ceditor-control').invoke('observe', 'click', this.changeProperty);
			$$('.typo3-tstemplate-ceditor-color-select').invoke('observe', 'change', this.updateColorFromSelect);
			$$('.typo3-tstemplate-ceditor-color-input').invoke('observe', 'change', this.updateColorFromInput);
		}.bind(this));
		
	},
	
	/**
	 * initially register event listeners
	 */
	changeProperty: function(event) {
		var editIcon = Event.element(event);
		var paramName = editIcon.readAttribute('rel');
		var defaultDiv = $('defaultTS-'+paramName);
		var userDiv = $('userTS-'+paramName);
		var checkBox = $('check[' + paramName + ']');
		
		if (editIcon.hasClassName('editIcon')) {
			$(defaultDiv).hide(); 
			$(userDiv).show().setStyle({backgroundColor: '#fdf8bd'}); 
			$(checkBox).enable().setValue('checked');
		} 
		
		if (editIcon.hasClassName('undoIcon')) {
			$(userDiv).hide(); 
			$(defaultDiv).show(); 
			$(checkBox).setValue('').disable();
		}
	},
	
	updateColorFromSelect: function(event) {
		var colorSelect = Event.element(event);
		var paramName = colorSelect.readAttribute('rel');
		
		var colorValue = colorSelect.getValue();
		var colorInput = $('input-'+paramName);
		var colorBox = $('colorbox-'+paramName);
		
		$(colorInput).setValue(colorValue);
		$(colorBox).setStyle({backgroundColor: colorValue});
	},
	
	updateColorFromInput: function(event) {
		var colorInput = Event.element(event);
		var paramName = colorInput.readAttribute('rel');
		
		var colorValue = colorInput.getValue();
		var colorBox = $('colorbox-'+paramName);
		var colorSelect = $('select-'+paramName);

		$(colorBox).setStyle({backgroundColor: colorValue});
		
		$(colorSelect).childElements().each(function(option) {
			if (option.value === colorValue) {
				option.selected = true;
			} else {
				option.selected = false;
			}
		});
	}
	
});


var TYPO3ModuleConstantEditor = new ModuleConstantEditor();
