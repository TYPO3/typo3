/***************************************************************
*
*  Universal formupdate-function
*
*
*
*  Copyright notice
*
*  (c) 1998-2011 Kasper Skaarhoj
*  All rights reserved
*
*  This script is part of the TYPO3 t3lib/ library provided by
*  Kasper Skaarhoj <kasper@typo3.com> together with TYPO3
*
*  Released under GNU/GPL (see license file in tslib/)
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.
*
*  This copyright notice MUST APPEAR in all copies of this script
***************************************************************/


function updateForm(formname,fieldname,value) {
	if (document[formname] && document[formname][fieldname]) {
		var fObj = document[formname][fieldname];
		var type=fObj.type;
		if (!fObj.type) {
			type="radio";
		}
		switch(type) {
			case "text":
			case "textarea":
			case "hidden":
			case "password":
				fObj.value = value;
			break;
			case "checkbox":
				fObj.checked = ((value && value!=0) ? "on":"");
			break;
			case "select-one":
				var l=fObj.length;
				for (a=0;a<l;a++) {
					if (fObj.options[a].value == value) {
						fObj.selectedIndex = a;
					}
				}
			break;
			case "select-multiple":
				var l=fObj.length;
				for (a=0;a<l;a++) {
					if (fObj.options[a].value == value) {
						fObj.options[a].selected = 1;
					}
				}
			break;
			case "radio":
				var l=fObj.length;
				for (a=0; a<l;a++) {
					if (fObj[a].value==value) {
						fObj[a].checked = 1;
					}
				}
			break;
			default:
		}
	}
}
