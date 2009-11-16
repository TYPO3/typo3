/***************************************************************
*  Copyright notice
*
*  (c) 2003 dynarch.com. Authored by Mihai Bazon, sponsored by www.americanbible.org.
*  (c) 2004-2009 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
*  This script is a modified version of a script published under the htmlArea License.
*  A copy of the htmlArea License may be found in the textfile HTMLAREA_LICENSE.txt.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/
/*
 * Spell Checker Plugin for TYPO3 htmlArea RTE
 *
 * TYPO3 SVN ID: $Id$
 */
var dialog = window.opener.HTMLArea.Dialog.SpellChecker;
var frame = null;
var currentElement = null;
var wrongWords = null;
var modified = false;
var allWords = {};
var fixedWords = [];
var suggested_words = {};

var to_p_dict = []; // List of words to add to personal dictionary
var to_r_list = []; // List of words to add to replacement list

function makeCleanDoc(leaveFixed) {
	// document.getElementById("status").innerHTML = 'Please wait: rendering valid HTML';
	var words = wrongWords.concat(fixedWords);
	for (var i = words.length; --i >= 0;) {
		var el = words[i];
		if (!(leaveFixed && /HA-spellcheck-fixed/.test(el.className))) {
			el.parentNode.insertBefore(el.firstChild, el);
			el.parentNode.removeChild(el);
		} else
			el.className = "HA-spellcheck-fixed";
	}
	return window.opener.HTMLArea.getHTML(frame.contentWindow.document.body, false, dialog.plugin.editor);
};

function recheckClicked() {
	document.getElementById("status").innerHTML = dialog.plugin.localize("Please wait: changing dictionary to") + ': "' + document.getElementById("f_dictionary").value + '".';
	var field = document.getElementById("f_content");
	field.value = makeCleanDoc(true);
	field.form.submit();
};

function saveClicked() {
	if (modified) {
		dialog.plugin.editor.getPluginInstance("EditorMode").setHTML(makeCleanDoc(false));
	}
	if ((to_p_dict.length || to_r_list.length) && dialog.plugin.enablePersonalDicts) {
		var data = {};
		for (var i = 0;i < to_p_dict.length;i++) {
			data['to_p_dict[' + i + ']'] = to_p_dict[i];
		}
		for (var i = 0;i < to_r_list.length;i++) {
			data['to_r_list[' + i + '][0]'] = to_r_list[i][0];
			data['to_r_list[' + i + '][1]'] = to_r_list[i][1];
		}
		data['cmd'] = 'learn';
		data['enablePersonalDicts'] = dialog.plugin.enablePersonalDicts;
		data['userUid'] = dialog.plugin.userUid;
		data['dictionary'] = dialog.plugin.contentISOLanguage;
		data['pspell_charset'] = dialog.plugin.contentCharset;
		data['pspell_mode'] = dialog.plugin.spellCheckerMode;
		window.opener.HTMLArea._postback(dialog.plugin.pageTSconfiguration.path, data);
	}
	window.close();
	return false;
};

function cancelClicked() {
	var ok = true;
	if (modified) {
		ok = confirm(dialog.plugin.localize("QUIT_CONFIRMATION"));
	}
	if (ok) {
		dialog.close();
	}
	return false;
};

function replaceWord(el) {
	var replacement = document.getElementById("v_replacement").value;
	var this_word_modified = (el.innerHTML != replacement);
	if (this_word_modified)
		modified = true;
	if (el) {
		el.className = el.className.replace(/\s*HA-spellcheck-(hover|fixed)\s*/g, " ");
	}
	el.className += " HA-spellcheck-fixed";
	el.__msh_fixed = true;
	if (!this_word_modified) {
		return false;
	}
	to_r_list.push([el.innerHTML, replacement]);
	el.innerHTML = replacement;
};

function replaceClicked() {
	replaceWord(currentElement);
	var start = currentElement.__msh_id;
	var index = start;
	do {
		++index;
		if (index == wrongWords.length) index = 0;
	} while((index != start) && wrongWords[index].__msh_fixed);
	if (index == start) {
		index = 0;
		alert(dialog.plugin.localize("Finished list of mispelled words"));
	}
	wrongWords[index].__msh_wordClicked(true);
	return false;
};

function revertClicked() {
	document.getElementById("v_replacement").value = currentElement.__msh_origWord;
	replaceWord(currentElement);
	currentElement.className = "HA-spellcheck-error HA-spellcheck-current";
	return false;
};

function replaceAllClicked() {
	var replacement = document.getElementById("v_replacement").value;
	var ok = true;
	var spans = allWords[currentElement.__msh_origWord];
	if (spans.length == 0) {
		alert("An impossible condition just happened.  Call FBI.  ;-)");
	} else if (spans.length == 1) {
		replaceClicked();
		return false;
	}
	/*
	var message = "The word \"" + currentElement.__msh_origWord + "\" occurs " + spans.length + " times.\n";
	if (replacement == currentElement.__msh_origWord) {
		ok = confirm(message + "Ignore all occurrences?");
	} else {
		ok = confirm(message + "Replace all occurrences with \"" + replacement + "\"?");
	}
	*/
	if (ok) {
		for (var i = 0; i < spans.length; ++i) {
			if (spans[i] != currentElement) {
				replaceWord(spans[i]);
			}
		}
		// replace current element the last, so that we jump to the next word ;-)
		replaceClicked();
	}
	return false;
};

function ignoreClicked() {
	document.getElementById("v_replacement").value = currentElement.__msh_origWord;
	replaceClicked();
	return false;
};

function ignoreAllClicked() {
	document.getElementById("v_replacement").value = currentElement.__msh_origWord;
	replaceAllClicked();
	return false;
};

function learnClicked() {
	to_p_dict.push(currentElement.__msh_origWord);
	return ignoreAllClicked();
};

function initDocument() {
	dialog.initialize();
	var plugin = dialog.plugin;
	var editor = plugin.editor;

	modified = false;
	document.title = dialog.plugin.localize("Spell Checker");
	document.getElementById("spellcheck_form").action = plugin.pageTSconfiguration.path;
	frame = document.getElementById("i_framecontent");
	var field = document.getElementById("f_content");
	field.value = HTMLArea.getHTML(editor._doc.body, false, editor);
	document.getElementById("f_init").value = "0";
	document.getElementById("f_dictionary").value = plugin.defaultDictionary ? plugin.defaultDictionary : plugin.contentISOLanguage;
	document.getElementById("f_charset").value = plugin.contentCharset;
	document.getElementById("f_pspell_mode").value = plugin.spellCheckerMode;
	document.getElementById("f_user_uid").value = plugin.userUid;
	document.getElementById("f_personal_dicts").value = plugin.enablePersonalDicts;
	document.getElementById("f_show_dictionaries").value = plugin.showDictionaries;
	document.getElementById("f_restrict_to_dictionaries").value = plugin.restrictToDictionaries;
	field.form.submit();
		// assign some global event handlers
	var select = document.getElementById("v_suggestions");
	select.onchange = function() {
		document.getElementById("v_replacement").value = this.value;
	};
	HTMLArea._addEvent(select, "dblclick", replaceClicked);

	document.getElementById("b_replace").onclick = replaceClicked;
	if (plugin.enablePersonalDicts) document.getElementById("b_learn").onclick = learnClicked;
		else document.getElementById("b_learn").style.display = 'none';
	document.getElementById("b_replall").onclick = replaceAllClicked;
	document.getElementById("b_ignore").onclick = ignoreClicked;
	document.getElementById("b_ignall").onclick = ignoreAllClicked;
	document.getElementById("b_recheck").onclick = recheckClicked;
	document.getElementById("b_revert").onclick = revertClicked;
	document.getElementById("b_info").onclick = displayInfo;

	document.getElementById("b_ok").onclick = saveClicked;
	document.getElementById("b_cancel").onclick = cancelClicked;

	select = document.getElementById("v_dictionaries");
	select.onchange = function() {
		document.getElementById("f_dictionary").value = this.value;
	};
};

function getAbsolutePos(el) {
	var r = { x: el.offsetLeft, y: el.offsetTop };
	if (el.offsetParent) {
		var tmp = getAbsolutePos(el.offsetParent);
		r.x += tmp.x;
		r.y += tmp.y;
	}
	return r;
};

function wordClicked(scroll) {
	var self = this;
	if (scroll) (function() {
		var pos = getAbsolutePos(self);
		var ws = { x: frame.offsetWidth - 4,
			   y: frame.offsetHeight - 4 };
		var wp = { x: frame.contentWindow.document.body.scrollLeft,
			   y: frame.contentWindow.document.body.scrollTop };
		pos.x -= Math.round(ws.x/2);
		if (pos.x < 0) pos.x = 0;
		pos.y -= Math.round(ws.y/2);
		if (pos.y < 0) pos.y = 0;
		frame.contentWindow.scrollTo(pos.x, pos.y);
	})();
	if (currentElement) {
		var a = allWords[currentElement.__msh_origWord];
		currentElement.className = currentElement.className.replace(/\s*HA-spellcheck-current\s*/g, " ");
		for (var i = 0; i < a.length; ++i) {
			var el = a[i];
			if (el != currentElement) {
				el.className = el.className.replace(/\s*HA-spellcheck-same\s*/g, " ");
			}
		}
	}
	currentElement = this;
	this.className += " HA-spellcheck-current";
	var a = allWords[currentElement.__msh_origWord];
	for (var i = 0; i < a.length; ++i) {
		var el = a[i];
		if (el != currentElement) {
			el.className += " HA-spellcheck-same";
		}
	}
	// document.getElementById("b_replall").disabled = (a.length <= 1);
	// document.getElementById("b_ignall").disabled = (a.length <= 1);
	var txt;
	var txt2;
	if (a.length == 1) {
		txt = dialog.plugin.localize("One occurrence");
		txt2 = dialog.plugin.localize("was found.");
	} else if (a.length == 2) {
		txt = dialog.plugin.localize("Two occurrences");
		txt2 = dialog.plugin.localize("were found.");
	} else {
		txt = a.length + " " + dialog.plugin.localize("occurrences");
		txt2 = dialog.plugin.localize("were found.");
	}
	var suggestions = suggested_words[this.__msh_origWord];
	if (suggestions) suggestions = suggestions.split(/,/);
		else suggestions = [];
	var select = document.getElementById("v_suggestions");
	document.getElementById("statusbar").innerHTML = txt + " " + dialog.plugin.localize("of the word") +
		' "<b>' + currentElement.__msh_origWord + '</b>"' + " " + txt2;
	for (var i = select.length; --i >= 0;) {
		select.remove(i);
	}
	for (var i = 0; i < suggestions.length; ++i) {
		var txt = suggestions[i];
		var option = document.createElement("option");
		option.value = txt;
		option.appendChild(document.createTextNode(txt));
		select.appendChild(option);
	}
	document.getElementById("v_currentWord").innerHTML = this.__msh_origWord;
	if (suggestions.length > 0) {
		select.selectedIndex = 0;
		select.onchange();
	} else {
		document.getElementById("v_replacement").value = this.innerHTML;
	}
	select.style.display = "none";
	select.style.display = "inline";
	return false;
};

function wordMouseOver() {
	this.className += " HA-spellcheck-hover";
};

function wordMouseOut() {
	this.className = this.className.replace(/\s*HA-spellcheck-hover\s*/g, " ");
};

function displayInfo() {
	var info = frame.contentWindow.spellcheck_info;
	if (!info)
		alert(dialog.plugin.localize("No information available"));
	else {
		var txt = dialog.plugin.localize("Document information") + "\n" ;
		for (var i in info) {
			txt += "\n" + dialog.plugin.localize(i) + " : " + info[i];
		}
		txt += " " + dialog.plugin.localize("seconds");
		alert(txt);
	}
	return false;
};

function finishedSpellChecking() {
	// initialization of global variables
	currentElement = null;
	wrongWords = null;
	allWords = {};
	fixedWords = [];
	suggested_words = frame.contentWindow.suggested_words;

	document.getElementById("status").innerHTML = dialog.plugin.localize("HTMLArea Spell Checker");
	var doc = frame.contentWindow.document;
	var spans = doc.getElementsByTagName("span");
	var sps = [];
	var id = 0;
	for (var i = 0; i < spans.length; ++i) {
		var el = spans[i];
		if (/HA-spellcheck-error/.test(el.className)) {
			sps.push(el);
			el.__msh_wordClicked = wordClicked;
			el.onclick = function(ev) {
				ev || (ev = window.event);
				ev && HTMLArea._stopEvent(ev);
				return this.__msh_wordClicked(false);
			};
			el.onmouseover = wordMouseOver;
			el.onmouseout = wordMouseOut;
			el.__msh_id = id++;
			var txt = (el.__msh_origWord = el.firstChild.data);
			el.__msh_fixed = false;
			if (typeof allWords[txt] == "undefined") {
				allWords[txt] = [el];
			} else {
				allWords[txt].push(el);
			}
		} else if (/HA-spellcheck-fixed/.test(el.className)) {
			fixedWords.push(el);
		}
	}
	wrongWords = sps;
	if (sps.length == 0) {
		if (!modified) {
			alert(dialog.plugin.localize("NO_ERRORS_CLOSING"));
			window.close();
		} else {
			alert(dialog.plugin.localize("NO_ERRORS"));
		}
		return false;
	}
	(currentElement = sps[0]).__msh_wordClicked(true);
	var as = doc.getElementsByTagName("a");
	for (var i = as.length; --i >= 0;) {
		var a = as[i];
		a.onclick = function() {
			if (confirm(dialog.plugin.localize("CONFIRM_LINK_CLICK") + ":\n" +
				    this.href + "\n" + dialog.plugin.localize("I will open it in a new page."))) {
				window.open(this.href);
			}
			return false;
		};
	}
	var dicts = doc.getElementById("HA-spellcheck-dictionaries");
	if (dicts) {
		dicts.parentNode.removeChild(dicts);
		dicts = dicts.innerHTML.split(/,/);
		var select = document.getElementById("v_dictionaries");
		for (var i = select.length; --i >= 0;) {
			select.remove(i);
		}
		var selectedOptionIndex = 0;
		for (var i = 0; i < dicts.length; ++i) {
			var txt = dicts[i];
			var option = document.createElement("option");
			if (/^@(.*)$/.test(txt)) {
				txt = RegExp.$1;
				selectedOptionIndex = i;
				if (HTMLArea.is_ie) option.selected = true;
				document.getElementById("f_dictionary").value = txt;
			}
			option.value = txt;
			option.appendChild(document.createTextNode(txt));
			select.appendChild(option);
		}
		select.selectedIndex = selectedOptionIndex;
	}
};

