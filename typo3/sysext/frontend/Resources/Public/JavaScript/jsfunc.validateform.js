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


function validateForm(theFormname,theFieldlist,goodMess,badMess,emailMess) {
	var formObject = document[theFormname];
	if (!formObject) {
		formObject = document.getElementById(theFormname);
	}
	if (formObject && theFieldlist) {
		var index=1;
		var theField = split(theFieldlist, ",", index);
		var msg="";
		var theEreg = '';
		var theEregMsg = '';
		var specialMode = '';
		var theLabel, a;

		while (theField) {
			theEreg = '';
			specialMode = '';

				// Check special modes:
			if (theField == '_EREG')	{	// EREG mode: _EREG,[error msg],[JS ereg],[fieldname],[field Label]
				specialMode = theField;

				index++;
				theEregMsg = split(theFieldlist, ",", index);
				index++;
				theEreg = split(theFieldlist, ",", index);
			} else if (theField == '_EMAIL') {
				specialMode = theField;
			}

				// Get real field name if special mode has been set:
			if (specialMode) {
				index++;
				theField = split(theFieldlist, ",", index);
			}

			index++;
			theLabel = split(theFieldlist, ",", index);
			theField = theField;
			if (formObject[theField]) {
				var fObj = formObject[theField];
				var type=fObj.type;
				if (!fObj.type) {
					type="radio";
				}
				var value="";
				switch(type) {
					case "text":
					case "textarea":
					case "password":
					case "file":
						value = fObj.value;
					break;
					case "select-one":
						if (fObj.selectedIndex>=0) {
							value = fObj.options[fObj.selectedIndex].value;
						}
					break;
					case "select-multiple":
						var l=fObj.length;
						for (a=0;a<l;a++) {
							if (fObj.options[a].selected) {
								 value+= fObj.options[a].value;
							}
						}
					break;
					case "radio":
					case "checkbox":
						var len=fObj.length;
						if (len) {
							for (a=0;a<len;a++) {
								if (fObj[a].checked) {
									value = fObj[a].value;
								}
							}
						} else {
							if (fObj.checked) {
								value = fObj.value;
							}
						}
					break;
					default:
						value = 1;
				}

				switch(specialMode) {
					case "_EMAIL":
						var theRegEx_notValid = new RegExp("(@.*@)|(\.\.)|(@\.)|(\.@)|(^\.)", "gi");
						var theRegEx_isValid = new RegExp("^.+\@[a-zA-Z0-9\-\.]+\.([a-zA-Z]{2,4}|[0-9]{1,3})$","");
						if (!theRegEx_isValid.test(value))	{	// This part was supposed to be a part of the condition: " || theRegEx_notValid.test(value)" - but I couldn't make it work (Mozilla Firefox, linux) - Anyone knows why?
							msg+="\n"+theLabel+' ('+(emailMess ? emailMess : 'Email address not valid!')+')';
						}
					break;
					case "_EREG":
						var theRegEx_isValid = new RegExp(theEreg,"");
						if (!theRegEx_isValid.test(value)) {
							msg+="\n"+theLabel+' ('+theEregMsg+')';
						}
					break;
					default:
						if (!value) {
							msg+="\n"+theLabel;
						}
				}
			}
			index++;
			theField = split(theFieldlist, ",", index);
		}
		if (msg) {
			var theBadMess = badMess;
			if (!theBadMess) {
				theBadMess = "You must fill in these fields:";
			}
			theBadMess+="\n";
			alert(theBadMess+msg);
			return false;
		} else {
			var theGoodMess = goodMess;
			if (theGoodMess) {
				alert(theGoodMess);
			}
			return true;
		}
	}
}
function split(theStr1, delim, index) {
	var theStr = ''+theStr1;
	var lengthOfDelim = delim.length;
	var sPos = -lengthOfDelim;
	var a, ePos;
	if (index<1) {index=1;}
	for (a=1; a<index; a++) {
		sPos = theStr.indexOf(delim, sPos+lengthOfDelim);
		if (sPos==-1)	{return null;}
	}
	ePos = theStr.indexOf(delim, sPos+lengthOfDelim);
	if(ePos == -1)	{ePos = theStr.length;}
	return (theStr.substring(sPos+lengthOfDelim,ePos));
}
