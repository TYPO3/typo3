<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Super admin configuration and main script (sample)
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 */
/*
 * WHAT IS IT:
 * This script is intended to provide administrator information and
 * relevant links to multiple Typo3 sites on a webserver.
 *
 * LOCATION:
 * This script must be located in a directory with the Typo3 source available in typo3_src/
 * The script includes the class t3lib/class.t3lib_superadmin.php
 *
 * IMPORTANT:
 * This script MUST be secured as it reads out password information an provides direct login links to sites!
 * It's recommended to use the script over a secure connection and to use the strongest webserver http-based authentication, you can.
 * Furthermore it's adviced to out-comment the 'die'-line below when you're not using the script.
 *
 * CONFIGURATION:
 * The point is that you configure one or more directories (parent directories) on the webserver to the script.
 * The script expects these directories (parents) to contain other directories (childs) exclusively with Typo3 sites in + any number of directories names 'typo3_src*' which will be ignored.
 * Every Typo3 site (child) in these parent directories will get listed in the interface.
 *
 * For each 'parent directory' you enter information like this:
 *
 * $parentDirs[] = array(
 * 	'dir'=> '/www/htdocs/typo3/32/',
 * 	'url' => 'http://192.168.1.4/typo3/32/'
 * );
 *
 * 'dir' is the absolute path of the parent directory where the sites are located in subdirs
 * 'url' is the web-accessible url of the parent directory.
 */


// *****************
// Security:
// *****************
die('Script secured by a die() function. Comment the line if you want to use the script!');


// *****************
// Including:
// *****************
require_once ('./typo3_src/t3lib/class.t3lib_superadmin.php');


// *****************
// Configuration:
// This is just an example!
// *****************
$parentDirs = array();
$parentDirs[] = array(
	'dir'=> '/www/htdocs/typo3/commercial_sites/',
	'url' => 'http://123.234.43.212/typo3/commercial_sites/'
);
$parentDirs[] = array(
	'dir'=> '/www/htdocs/typo3/nonprofit_sites/',
	'url' => 'http://123.234.43.212/typo3/nonprofit_sites/'
);



// *****************
// Start
// *****************
$superadmin = t3lib_div::makeInstance('t3lib_superadmin');
$superadmin->init($parentDirs);
$superadmin->defaultSet();
?>