<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2004 Kasper Skaarhoj (kasper@typo3.com)
*  (c) 2004 Philipp Borgmann <philipp.borgmann@gmx.de>
*  (c) 2004-2005 Stanislas Rolland <stanislas.rolland@fructifor.ca>
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
 * Internal page and image browsers for the htmlArea RTE
 *
 * @author	Philipp Borgmann <philipp.borgmann@gmx.de>
 * @author	Stanislas Rolland <stanislas.rolland@fructifor.ca>
 */

error_reporting (E_ALL ^ E_NOTICE);
unset($MCONF);
define('TYPO3_OS', (stristr(PHP_OS,'win') && !stristr(PHP_OS,'darwin')) ? 'WIN' : '');
define('MY_PATH_thisScript',str_replace('//','/', str_replace('\\','/', (php_sapi_name()=='cgi'||php_sapi_name()=='xcgi'||php_sapi_name()=='isapi' ||php_sapi_name()=='cgi-fcgi')&&($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED'])? ($_SERVER['ORIG_PATH_TRANSLATED']?$_SERVER['ORIG_PATH_TRANSLATED']:$_SERVER['PATH_TRANSLATED']):($_SERVER['ORIG_SCRIPT_FILENAME']?$_SERVER['ORIG_SCRIPT_FILENAME']:$_SERVER['SCRIPT_FILENAME']))));

require ('conf.php');
require ($BACK_PATH.'init.php');
require ($BACK_PATH.'template.php');
require_once (PATH_t3lib.'class.t3lib_div.php');

$query_string = t3lib_div::getIndpEnv('QUERY_STRING');
$popupname = t3lib_div::_GET('popupname');
$src = t3lib_div::_GET('srcpath');
$editorNo =  t3lib_div::_GET('editorNo');
switch( $popupname ) {
	case "link" : $title = "Insert/Modify Link"; break;
	case "image" : $title = "Insert Image"; break;
	case "user" : $title = "Insert Custom Element"; break;
	case "acronym" : $title = "Insert/Modify Acronym"; break;
	default : $title = "Editor configuration problem!";
}
?>
<html >
<head>
<title><?php echo $title;?></title>
<script type="text/javascript" src="htmlarea/popups/popup.js"></script>
<script type="text/javascript">
	/*<![CDATA[*/
	var HTMLArea = window.opener.HTMLArea;
	var _editor_CSS = window.opener._editor_CSS;
	function Init() {
  		__dlg_translate(HTMLArea.I18N.dialogs);
		__dlg_init(null, true);
  		document.body.onkeypress = __dlg_close_on_esc;
	};
<?php
	echo '
	var editor = window.opener.RTEarea[' . $editorNo . ']["editor"];
';
?>
	/*]]>*/
</script>
<style type="text/css">
	/*<![CDATA[*/
	<!--
	/* Opera 9 TP1 does not recognize the changing size of iframe contents and does not add scrollbars when required */
iframe { width: 100%; height: 2000px; border-style: none; border-width: 0; margin: 0; padding: 0; }
div#htmlarea-popup-div { width: 100%; height: 100%; margin: 0; padding: 0; overflow: scroll; }
* html iframe, :root iframe { height: 100%; overflow: visible; }
* html div#htmlarea-popup-div, :root div#htmlarea-popup-div { overflow: hidden; }
	-->
	/*]]>*/
</style>
</head>
<body style="background:ButtonFace; margin: 0; padding: 0; border-style: none;" onload="Init();">
<div id="htmlarea-popup-div">
<?php
	echo '
		<iframe id="idPopup" src="' . $src . '?' . $query_string . '"></iframe>
	';
?>
</div>
</body></html>
