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
 * Login frameset
 *
 * This script generates a login-frameset used when the used must relogin.
 *
 * @author	Kasper Skårhøj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */




define("TYPO3_PROCEED_IF_NO_USER", 1);
require ("init.php");



// ******************************	
// Start document output
// ******************************

?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.0 Transitional//EN">
<html>
<head>
	<title>TYPO3 <?php echo $TYPO_VERSION;?> Re-Login (<?php echo $TYPO3_CONF_VARS["SYS"]["sitename"];?>)</title>
</head>
<frameset rows="*,1" framespacing="0" frameborder="0" border="no">
<frame name="login" src="index.php?loginRefresh=1" marginwidth="0" marginheight="0" frameborder="no" scrolling="no" noresize>
<frame name="dummy" src="dummy.php" marginwidth="0" marginheight="0" frameborder="no" scrolling="auto" noresize>
</frameset>
</html>