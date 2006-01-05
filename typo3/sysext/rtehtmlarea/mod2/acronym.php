<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2005-2006 Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
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
 * @author	Stanislas Rolland <stanislas.rolland(arobas)fructifor.ca>
 *
 * $Id$  *
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
$LANG->includeLLFile('EXT:rtehtmlarea/mod2/locallang.php');

class tx_rtehtmlarea_acronym {
	var $content;
	var $modData;
	var $siteUrl;
	var $doc;
	var $ID = 'rtehtmlarea';

	/**
	 * @return	[type]		...
	 */
	function init()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->siteUrl = t3lib_div::getIndpEnv("TYPO3_SITE_URL");
				// get the http-path to typo3:
		$this->httpTypo3Path = substr( substr( t3lib_div::getIndpEnv('TYPO3_SITE_URL'), strlen( t3lib_div::getIndpEnv('TYPO3_REQUEST_HOST') ) ), 0, -1 );
		if (strlen($this->httpTypo3Path) == 1) {
			$this->httpTypo3Path = "/";
		} else {
			$this->httpTypo3Path .= "/";
		}
			// Get the path to this extension:
		$this->extHttpPath = $this->httpTypo3Path . t3lib_extMgm::siteRelPath($this->ID);

		$this->doc = t3lib_div::makeInstance("template");
		$this->doc->backPath = $BACK_PATH;
		$this->doc->bodyTagAdditions = 'class="popupwin" onload="init();"';
		$this->doc->form = '<form action="" id="acronymForm" name="acronymForm" method="POST">';
		$this->doc->JScode='
		<script type="text/javascript">
			/*<![CDATA[*/
			var editor = parent.editor;
			var HTMLArea = parent.HTMLArea;
			var head = document.getElementsByTagName("head")[0];
			var link = document.createElement("link");
			link.rel = "stylesheet";
			link.href = parent._editor_CSS;
			head.appendChild(link);
			document.documentElement.className = "popupwin";
			var param = null;
			var html = editor.getSelectedHTML();
			var sel = editor._getSelection();
			var range = editor._createRange(sel);
			var abbr = editor._activeElement(sel);
			var abbrType = null;
			var acronyms = new Object();
			var abbreviations = new Object();
			if(!(abbr != null && (abbr.tagName.toLowerCase() == "acronym" || abbr.tagName.toLowerCase() == "abbr"))) { 
				abbr = editor._getFirstAncestor(sel, ["acronym", "abbr"]);
				if(abbr != null && (abbr.tagName.toLowerCase() == "acronym" || abbr.tagName.toLowerCase() == "abbr")) { 
					param = { title : abbr.title, text : abbr.innerHTML};
					abbrType = abbr.tagName.toLowerCase();
				} else {
					param = { title : "", text : html};
				}
			}

			function setType() {
				if(document.acronymForm.acronym.checked) {
					abbrType = "acronym";
					document.getElementById("abbrType").innerHTML = "' . $LANG->getLL("Acronym") . '";
				} else {
					abbrType = "abbr";
					document.getElementById("abbrType").innerHTML = "' . $LANG->getLL("Abbreviation") . '";
				}
				document.getElementById("title").value = param["title"];
				fillSelect(param["text"]);
			}

			function init() {
				if(abbrType != null) document.getElementById("type").style.display = "none";
					else abbrType = "acronym";
				if(HTMLArea.is_ie) document.getElementById("type").style.display = "none";
				if(abbrType == "acronym") document.acronymForm.acronym.checked = true;
					else  document.acronymForm.abbreviation.checked = true;
				var acronymUrl = parent.window.opener.RTEarea[editor._doc._editorNo]["acronymUrl"];
				var abbrData = HTMLArea._getScript(0, false, acronymUrl);
				if(abbrData) eval(abbrData);
				setType();
				HTMLArea._addEvents(document.acronymForm.title,["keypress", "keydown", "dragdrop", "drop", "paste", "change"],function(ev) { document.acronymForm.termSelector.selectedIndex=-1; document.acronymForm.acronymSelector.selectedIndex=-1; });
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
						if(same) document.acronymForm.title.value = abbrObj[i];
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
						var child = abbr.innerHTML;
						abbr.parentNode.removeChild(abbr);
						editor.insertHTML(child);
					}
				} else {
					var doc = editor._doc;
					if (!abbr) {
						abbr = doc.createElement(abbrType);
						abbr.title = title;
						if(document.acronymForm.acronymSelector.options.length != 1 && document.acronymForm.termSelector.selectedIndex > 0 && document.acronymForm.termSelector.options[document.acronymForm.termSelector.selectedIndex].value == title) html = document.acronymForm.acronymSelector.options[document.acronymForm.acronymSelector.selectedIndex].value;
						abbr.innerHTML = html;
						if (HTMLArea.is_ie) range.pasteHTML(abbr.outerHTML);
							else editor.insertNodeAtSelection(abbr);
					} else {
						abbr.title = title;
						if(document.acronymForm.acronymSelector.options.length != 1 && document.acronymForm.termSelector.selectedIndex > 0 && document.acronymForm.termSelector.options[document.acronymForm.termSelector.selectedIndex].value == title) abbr.innerHTML = document.acronymForm.acronymSelector.options[document.acronymForm.acronymSelector.selectedIndex].value;
					}
				}
			};

			function onOK() {
				processAcronym(document.getElementById("title").value);
				parent.__dlg_close(null);
				return false;
			};

			function onDelete() {
				processAcronym("");
				parent.__dlg_close(null);
				return false;
			};
			function onCancel() {
				parent.__dlg_close(null);
				return false;
			};
			/*]]>*/
		</script>
		';

		$this->modData = $BE_USER->getModuleData("rtehtmlarea_acronym.php","ses");
		if (t3lib_div::_GP("OC_key"))	{
			$parts = explode("|",t3lib_div::_GP("OC_key"));
			$this->modData["openKeys"][$parts[1]] = $parts[0]=="O" ? 1 : 0;
			$BE_USER->pushModuleData("rtehtmlarea_acronym.php",$this->modData);
		}
	}

	/**
	 * [Describe function...]
	 * 
	 * @return	[type]		...
	 */
	function main()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$CLIENT,$TYPO3_CONF_VARS;

		$this->content="";
		$this->content.=$this->main_acronym($this->modData["openKeys"]);
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
		global $SOBE,$LANG,$BACK_PATH;

		$content.=$this->doc->startPage("RTE acronym");
		$RTEtsConfigParts = explode(":",t3lib_div::_GP("RTEtsConfigParams"));
		$RTEsetup = $GLOBALS["BE_USER"]->getTSConfig("RTE",t3lib_BEfunc::getPagesTSconfig($RTEtsConfigParts[5]));
		$thisConfig = t3lib_BEfunc::RTEsetup($RTEsetup["properties"],$RTEtsConfigParts[0],$RTEtsConfigParts[2],$RTEtsConfigParts[4]);

		$content.='
	<div class="title" id="abbrType">' . $LANG->getLL("Acronym") . '</div>
	<fieldset id="type">
		<legend>' . $LANG->getLL("Type_of_abridged_form") . '</legend>
		<label for="acronym" class="checkbox">' . $LANG->getLL("Acronym") . '</label><input type="radio" name="type" id="acronym" value="acronym" checked="checked" onclick="setType();" />
		<label for="abbreviation" class="checkbox">' . $LANG->getLL("Abbreviation") . '</label><input type="radio" name="type" id="abbreviation" value="abbreviation" onclick="setType();" />
	</fieldset>
	<fieldset id="selector">
		<legend>' . $LANG->getLL("Defined_term") . '</legend>
		<label for="termSelector" class="fl" id="termSelectorLabel" title="' . $LANG->getLL("Select_a_term") . '">' . $LANG->getLL("Unabridged_term") . '</label>
		<select id="termSelector" name="termSelector"  title="' . $LANG->getLL("Select_a_term") . '"
			onChange="document.acronymForm.acronymSelector.selectedIndex=document.acronymForm.termSelector.selectedIndex; document.acronymForm.title.value=document.acronymForm.termSelector.options[document.acronymForm.termSelector.selectedIndex].value;">
			<option value=""></option>
		</select>
		<label for="acronymSelector" id="acronymSelectorLabel" title="' . $LANG->getLL("Select_an_acronym") . '">' . $LANG->getLL("Abridged_term") . '</label>
		<select id="acronymSelector" name="acronymSelector"  title="' . $LANG->getLL("Select_an_acronym") . '"
			onChange="document.acronymForm.termSelector.selectedIndex=document.acronymForm.acronymSelector.selectedIndex; document.acronymForm.title.value=document.acronymForm.termSelector.options[document.acronymForm.termSelector.selectedIndex].value;">
			<option value=""></option>
		</select>
	</fieldset>
	<fieldset>
		<legend>' . $LANG->getLL("Term_to_abridge") . '</legend>
		<label for="title" class="fl" title="' . $LANG->getLL('Use_this_term_explain') . '">' . $LANG->getLL('Use_this_term') . '</label>
		<input type="text" id="title" name="title" size="60" title="' . $LANG->getLL('Use_this_term_explain') . '" />
	</fieldset>
	<div class="buttons">
		<button type="button" title="' . $LANG->getLL("OK") . '"onclick="return onOK();">' . $LANG->getLL("OK") . '</button>
		<button type="button" title="' . $LANG->getLL("Delete") . '" onclick="return onDelete();">' . $LANG->getLL("Delete") . '</button>
		<button type="button" title="' . $LANG->getLL("Cancel")  . '" onclick="return onCancel();">' . $LANG->getLL("Cancel") . '</button>
	</div>';

	//debug($RTEtsConfigParts);
	//debug($thisConfig);
	
		$content.= $this->doc->endPage();
		return $content;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod2/acronym.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/rtehtmlarea/mod2/acronym.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('tx_rtehtmlarea_acronym');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
