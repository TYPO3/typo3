<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
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
 * Dev-script: Dumps the TypoScrip template tables.
 *
 * This script prints out all language-labels in TYPO3 and provides a way of checking for missing translations.
 * Must be logged in as admin.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

die("<strong>This script is for typo3 development and maintenance only. You'll probably find it useless for what you do.</strong><br><br>MUST remove this line in descriptions script before it'll work for you. This is a security precaution. Anyways, you must be logged in as admin as well.");
 
 
$BACK_PATH="../";
define("TYPO3_MOD_PATH", "dev/");
require ($BACK_PATH."init.php");

if (!$BE_USER->user["admin"])	die("You must be logged in as admin user!");


// ***************************
// Registering Incoming data
// ***************************
t3lib_div::setGPvars("table,uid,hide");
//debug($HTTP_POST_VARS);


// ***************************
// Functions
// ***************************
function getTemplateOutput($row)	{
	$title = "TITLE: ".$row["title"]."                                                                      ";
	$info = "PID: ".$row["pid"]."  UID: ".$row["uid"]."                                                                    ";
	
	$out="";
	$out.="[*******************************************************************]\n";
	$out.="[*** ".substr($title,0,59)." ***]\n";
	$out.="[*** ".substr($info,0,59)." ***]\n";
	$out.="[*******************************************************************]\n";
	$out.="[***                          CONSTANTS                          ***]\n";
	$out.="[*******************************************************************]\n";

	$out.=htmlspecialchars($row["constants"]);
	$out.="\n\n";

	$out.="[*******************************************************************]\n";
	$out.="[***                           SETUP                             ***]\n";
	$out.="[*******************************************************************]\n";

	$out.=htmlspecialchars($row["config"]);
	$out.="\n\n";
	
	return $out;
}



// ***************************
// Starting document output
// ***************************

if (!$hide)	{
?>

<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">

<html>
<head>
	<title>Untitled</title>
</head>

<body>

<form action="dump_template_tables.php" method="post">
Table:
<br>
<select name="table">
	<option value="static_template"<?php if($table=="static_template") echo" selected";?>>static_template</option>
	<option value="sys_template"<?php if($table=="sys_template") echo" selected";?>>sys_template</option>
</select><br>
Specific Uid: <br>
<input type="text" name="uid" size="5"><br>
Hide this control:<br>
<input type="checkbox" name="hide" value="1"><br>
<input type="submit">
</form>
<hr>
<br>
<?php 
}

if ($table=="sys_template" || $table=="static_template")	{
	$out="";
	$where = ($table=="sys_template") ? "NOT deleted" : "1=1";
	if (intval($uid))	$where.=" AND uid=".intval($uid);
	$query = "SELECT uid,pid,constants,config,title FROM ".$table." WHERE ".$where." ORDER BY title";
	$res = mysql(TYPO3_db,$query);
	while($row=mysql_fetch_assoc($res))	{
		$out.= getTemplateOutput($row);
	}
	echo (!$hide?"MD5: ".md5($out):"")."<PRE>
".$out."
</PRE>";

}


if (!$hide)	{
?>


</body>
</html>
<?php
}
?>
