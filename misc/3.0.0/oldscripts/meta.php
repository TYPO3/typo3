<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skårhøj (kasper@typo3.com)
*  All rights reserved
*
*  This script is part of the Typo3 project. The Typo3 project is 
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
 * Creates meta tags. 
 * See static_template 'plugin.meta'
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */


if (!is_object($this))	die("Not called from cObj!");

$globalMeta = $conf["global."];
$local = $conf["local."];
$regular = array();
$DC = array();

$localDescription = trim($this->stdWrap($local["description"],$local["description."]));
$localKeywords = trim($this->stdWrap($local["keywords"],$local["keywords."]));

// Unsetting secondary description and keywords if constant is not substituted!
if (substr($globalMeta["description_2"],0,2)=='{$')		{$globalMeta["description_2"] = "";}
if (substr($globalMeta["keywords_2"],0,2)=='{$')		{$globalMeta["keywords_2"] = "";}
if (!$conf["flags."]["useSecondaryDescKey"])	{
	unset($globalMeta["keywords_2"]);
	unset($globalMeta["description_2"]);
}

// Process them:
if ($globalMeta["description"] || $globalMeta["description_2"] || $localDescription)	{
	$val = trim($globalMeta["description"]);
	if ($globalMeta["description_2"])	{
		$val = ($val?ereg_replace("\.$","",$val).". ":"").$globalMeta["description_2"];
	}
	if ($localDescription)	{
		if ($conf["flags."]["alwaysGlobalDescription"] )	{
			$val = ereg_replace("\.$","",$localDescription).". ".$val;
		} else {
			$val = $localDescription;
		}
	}
	$val=trim($val);
	$regular[] = '<META NAME="Description" CONTENT="'.htmlspecialchars($val).'">';
	$DC[] = '<META NAME="DC.Description" CONTENT="'.htmlspecialchars($val).'">';
}
if ($globalMeta["keywords"] || $globalMeta["keywords_2"] || $localKeywords)	{
	$val = trim($globalMeta["keywords"]);
	if ($globalMeta["keywords_2"])	{
		$val = ereg_replace(",$","",$val).",".$globalMeta["keywords_2"];
	}
	if ($localKeywords)	{
		if ($conf["flags."]["alwaysGlobalKeywords"] )	{
			$val = ereg_replace(",$","",$localKeywords).",".$val;
		} else {
			$val = $localKeywords;
		}
	}
	$val=trim(ereg_replace(",$","",trim($val)));
	$val=implode(",",t3lib_div::trimExplode(",",$val,1));
	$regular[] = '<META NAME="Keywords" CONTENT="'.htmlspecialchars($val).'">';
	$DC[] = '<META NAME="DC.Subject" CONTENT="'.htmlspecialchars($val).'">';
}
if ($globalMeta["robots"])	{
	$regular[] = '<META NAME="Robots" CONTENT="'.htmlspecialchars($globalMeta["robots"]).'">';
}
if ($globalMeta["copyright"])	{
	$regular[] = '<META NAME="Copyright" CONTENT="'.htmlspecialchars($globalMeta["copyright"]).'">';
	$DC[] = '<META NAME="DC.Rights" CONTENT="'.htmlspecialchars($globalMeta["copyright"]).'">';
}
if ($globalMeta["language"])	{
	$regular[] = '<META HTTP-EQUIV="Content-language" CONTENT="'.htmlspecialchars($globalMeta["language"]).'">';
	$DC[] = '<META NAME="DC.Language" scheme="NISOZ39.50" CONTENT="'.htmlspecialchars($globalMeta["language"]).'">';
}
if ($globalMeta["email"])	{
	$regular[] = '<LINK REV=made href="mailto:'.htmlspecialchars($globalMeta["email"]).'">';
	$regular[] = '<META HTTP-EQUIV="Reply-to" CONTENT="'.htmlspecialchars($globalMeta["email"]).'">';
}
if ($globalMeta["author"])	{
	$regular[] = '<META NAME="Author" CONTENT="'.htmlspecialchars($globalMeta["author"]).'">';
	$DC[] = '<META NAME="DC.Creator" CONTENT="'.htmlspecialchars($globalMeta["author"]).'">';
}
if ($globalMeta["distribution"])	{
	$regular[] = '<META NAME="Distribution" CONTENT="'.htmlspecialchars($globalMeta["distribution"]).'">';
}
if ($globalMeta["rating"])	{
	$regular[] = '<META NAME="Rating" CONTENT="'.htmlspecialchars($globalMeta["rating"]).'">';
}
if ($globalMeta["revisit"])	{
	$regular[] = '<META NAME="Revisit-after" CONTENT="'.htmlspecialchars($globalMeta["revisit"]).'">';
}

$DC[] = '<LINK REL="schema.dc" HREF="http://purl.org/metadata/dublin_core_elements">';


if (!$conf["flags."]["DC"])	{$DC=array();}

$content ="";
$content.= implode($regular,chr(10)).chr(10);
$content.= implode($DC,chr(10)).chr(10);

?>