<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2005 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Starter-script for install screen
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */



// **************************************************************************
// Insert some security here, if you don't trust the Install Tool Password:
// **************************************************************************

	// This checks for my own IP at home. You can just remove the if-statement.
if (1==0 || (substr($_SERVER['REMOTE_ADDR'],0,7)!='192.168' && $_SERVER['REMOTE_ADDR']!='127.0.0.1'))		{
	die("In the source distribution of TYPO3, the install script is disabled by a die() function call.<br/><b>Fix:</b> Open the file typo3/install/index.php and remove/out-comment the line that outputs this message!");
}



// *****************************************************************************
// Defining constants necessary for the install-script to invoke the installer
// *****************************************************************************
define('TYPO3_MOD_PATH', 'install/');
$BACK_PATH='../';

	// Defining this variable and setting it non-false will invoke the install-screen called from init.php
define('TYPO3_enterInstallScript', '1');
require ('../init.php');
?>