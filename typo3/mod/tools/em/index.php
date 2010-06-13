<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2010 Kasper Skaarhoj (kasperYYYY@typo3.com)
 * (c) 2005-2010 Karsten Dambekalns <karsten@typo3.org>
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
 * Module: Extension manager
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Karsten Dambekalns <karsten@typo3.org>
 */


unset($MCONF);
require('conf.php');
require($BACK_PATH.'init.php');
require($BACK_PATH.'template.php');
$BE_USER->modAccess($MCONF,1);

require_once('class.em_index.php');

// Make instance:
$SOBE = t3lib_div::makeInstance('SC_mod_tools_em_index');
$SOBE->init();
foreach($SOBE->include_once as $INC_FILE) {
	include_once($INC_FILE);
}
$SOBE->checkExtObj();

$SOBE->main();
$SOBE->printContent();
?>