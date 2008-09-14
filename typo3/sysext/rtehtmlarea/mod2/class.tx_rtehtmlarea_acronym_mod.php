<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2008 Stanislas Rolland <typo3(arobas)sjbr.ca>
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
 * Acronym content for htmlArea RTE
 *
 * @author	Stanislas Rolland <typo3(arobas)sjbr.ca>
 *
 * $Id$  *
 */

class tx_rtehtmlarea_acronym_mod {
	var $content;
	var $modData;

	/**
	 * document template object
	 *
	 * @var template
	 */
	var $doc;

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH;
		
		$this->doc = t3lib_div::makeInstance('template');
		$this->doc->backPath = $BACK_PATH;
		$this->doc->styleSheetFile = "";
		$this->doc->styleSheetFile_post = "";
		$this->doc->bodyTagAdditions = 'class="popupwin" onload="init();"';
		$this->doc->form = '<form action="" id="content" name="content" method="POST">';
		$JScode='
			var dialog = window.opener.HTMLArea.Dialog.Acronym;
			var editor = dialog.plugin.editor;
			var param = null;
			var html = editor.getSelectedHTML();
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			var abbr = editor._activeElement(sel);
				// Working around Safari issue
			if (!abbr && editor._statusBarTree.selected) {
				abbr = editor._statusBarTree.selected;
			}
			var abbrType = null;
			var acronyms = new Object();
			var abbreviations = new Object();
			if (!(abbr != null && /^(acronym|abbr)$/i.test(abbr.nodeName))) {
				abbr = editor._getFirstAncestor(sel, ["acronym", "abbr"]);
			}
			if (abbr != null && /^(acronym|abbr)$/i.test(abbr.nodeName)) {
				param = { title : abbr.title, text : abbr.innerHTML};
				abbrType = abbr.nodeName.toLowerCase();
			} else {
				param = { title : "", text : html};
			}
			
			function setType() {
				if(document.content.acronym.checked) {
					abbrType = "acronym";
					document.getElementById("abbrType").innerHTML = "' . $LANG->getLL('Acronym') . '";
				} else {
					abbrType = "abbr";
					document.getElementById("abbrType").innerHTML = "' . $LANG->getLL('Abbreviation') . '";
				}
				document.getElementById("title").value = param["title"];
				fillSelect(param["text"]);
				dialog.resize();
			}
			
			function init() {
				dialog.initialize("noLocalize", "noResize");
				var abbrData = dialog.plugin.getJavascriptFile(dialog.plugin.acronymUrl, "noEval");
				if (abbrData) eval(abbrData);
				if (dialog.plugin.pageTSConfiguration.noAcronym || dialog.plugin.pageTSConfiguration.noAbbr) {
					document.getElementById("type").style.display = "none";
				}
				if (abbrType != null) {
					document.getElementById("type").style.display = "none";
				} else {
					abbrType = !dialog.plugin.pageTSConfiguration.noAbbr ? "abbr" : "acronym";
				}
				if (abbrType == "acronym" && !dialog.plugin.pageTSConfiguration.noAcronym) {
					document.content.acronym.checked = true;
				} else {
					document.content.abbreviation.checked = true;
				}
				setType();
				HTMLArea._addEvents(document.content.title,["keypress", "keydown", "dragdrop", "drop", "paste", "change"],function(ev) { document.content.termSelector.selectedIndex=-1; document.content.acronymSelector.selectedIndex=-1; });
				document.getElementById("title").focus();
			};
			
			function fillSelect(text) {
				var termSelector = document.getElementById("termSelector");
				var acronymSelector = document.getElementById("acronymSelector");
				while(termSelector.options.length>1) termSelector.options[termSelector.length-1] = null;
				while(acronymSelector.options.length>1) acronymSelector.options[acronymSelector.length-1] = null;
				if(abbrType == "acronym") var abbrObj = acronyms;
					else var abbrObj = abbreviations;
				if(abbrObj != "") {
					for(var i in abbrObj) {
						same = (i==text);
						termSelector.options[termSelector.options.length] = new Option(abbrObj[i], abbrObj[i], false, same);
						acronymSelector.options[acronymSelector.options.length] = new Option(i, i, false, same);
						if(same) document.content.title.value = abbrObj[i];
					}
				}
				if(acronymSelector.options.length == 1) {
					document.getElementById("selector").style.display = "none";
				} else {
					document.getElementById("selector").style.display = "block";
				}
			};
			
			function processAcronym(title) {
				if (title == "" || title == null) {
					if (abbr) {
						dialog.plugin.removeMarkup(abbr);
					}
				} else {
					var doc = editor._doc;
					if (!abbr) {
						abbr = doc.createElement(abbrType);
						abbr.title = title;
						if(document.content.acronymSelector.options.length != 1 && document.content.termSelector.selectedIndex > 0 && document.content.termSelector.options[document.content.termSelector.selectedIndex].value == title) {
							html = document.content.acronymSelector.options[document.content.acronymSelector.selectedIndex].value;
						}
						abbr.innerHTML = html;
						if (HTMLArea.is_ie) range.pasteHTML(abbr.outerHTML);
							else editor.insertNodeAtSelection(abbr);
					} else {
						abbr.title = title;
						if(document.content.acronymSelector.options.length != 1 && document.content.termSelector.selectedIndex > 0 && document.content.termSelector.options[document.content.termSelector.selectedIndex].value == title) abbr.innerHTML = document.content.acronymSelector.options[document.content.acronymSelector.selectedIndex].value;
					}
				}
			};
			
			function onOK() {
				processAcronym(document.getElementById("title").value);
				dialog.close();
				return false;
			};
			
			function onDelete() {
				processAcronym("");
				dialog.close();
				return false;
			};
			function onCancel() {
				dialog.close();
				return false;
			};
		';
		
		$this->doc->JScode .= $this->doc->wrapScriptTags($JScode);
		
		$this->modData = $BE_USER->getModuleData('acronym.php','ses');
		$BE_USER->pushModuleData('acronym.php',$this->modData);
	}
	
	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		
		$this->content='';
		$this->content.=$this->main_acronym($this->modData['openKeys']);
	}
	
	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function printContent()	{
		echo $this->content;
	}
	
	/**
	 * Rich Text Editor (RTE) acronym selector
	 * 
	 * @param	[type]		$openKeys: ...
	 * @return	[type]		...
	 */
	function main_acronym($openKeys)	{
		global $LANG, $BE_USER;

		$content.=$this->doc->startPage($LANG->getLL('Insert/Modify Acronym',1));
		
		$RTEtsConfigParts = explode(':',t3lib_div::_GP('RTEtsConfigParams'));
		$RTEsetup = $BE_USER->getTSConfig('RTE',t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup['properties'],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);
		
		$content.='
	<div class="title" id="abbrType">' . $LANG->getLL('Acronym',1) . '</div>
	<fieldset id="type">
		<legend>' . $LANG->getLL('Type_of_abridged_form',1) . '</legend>
		<label for="abbreviation" class="checkbox">' . $LANG->getLL('Abbreviation',1) . '</label><input type="radio" name="type" id="abbreviation" value="abbreviation" checked="checked" onclick="setType();" />
		<label for="acronym" class="checkbox">' . $LANG->getLL('Acronym',1) . '</label><input type="radio" name="type" id="acronym" value="acronym" onclick="setType();" />
	</fieldset>
	<fieldset id="selector">
		<legend>' . $LANG->getLL('Defined_term',1) . '</legend>
		<label for="termSelector" class="fl" id="termSelectorLabel" title="' . $LANG->getLL('Select_a_term',1) . '">' . $LANG->getLL('Unabridged_term',1) . '</label>
		<select id="termSelector" name="termSelector"  title="' . $LANG->getLL('Select_a_term',1) . '"
			onChange="document.content.acronymSelector.selectedIndex=document.content.termSelector.selectedIndex; document.content.title.value=document.content.termSelector.options[document.content.termSelector.selectedIndex].value;">
			<option value=""></option>
		</select>
		<label for="acronymSelector" id="acronymSelectorLabel" title="' . $LANG->getLL('Select_an_acronym',1) . '">' . $LANG->getLL('Abridged_term',1) . '</label>
		<select id="acronymSelector" name="acronymSelector"  title="' . $LANG->getLL('Select_an_acronym',1) . '"
			onChange="document.content.termSelector.selectedIndex=document.content.acronymSelector.selectedIndex; document.content.title.value=document.content.termSelector.options[document.content.termSelector.selectedIndex].value;">
			<option value=""></option>
		</select>
	</fieldset>
	<fieldset>
		<legend>' . $LANG->getLL('Term_to_abridge',1) . '</legend>
		<label for="title" class="fl" title="' . $LANG->getLL('Use_this_term_explain',1) . '">' . $LANG->getLL('Use_this_term',1) . '</label>
		<input type="text" id="title" name="title" size="60" title="' . $LANG->getLL('Use_this_term_explain',1) . '" />
	</fieldset>
	<div class="buttons">
		<button type="button" title="' . $LANG->getLL('OK',1) . '"onclick="return onOK();">' . $LANG->getLL('OK',1) . '</button>
		<button type="button" title="' . $LANG->getLL('Delete',1) . '" onclick="return onDelete();">' . $LANG->getLL('Delete',1) . '</button>
		<button type="button" title="' . $LANG->getLL('Cancel',1)  . '" onclick="return onCancel();">' . $LANG->getLL('Cancel',1) . '</button>
	</div>';
		$content.= $this->doc->endPage();
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod2/class.tx_rtehtmlarea_acronym_mod.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod2/class.tx_rtehtmlarea_acronym_mod.php']);
}

?>
