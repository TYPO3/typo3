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
 * guest_submit.inc
 * 
 * .notifyEmail =	email address that should be notified of submissions.
 * See TSref document / FEDATA section for details on how to use this script.
 * The static template 'plugin.tt_guest' provides a working example of configuration.
 * 
 * @author	Kasper Skårhøj <kasper@typo3.com>
 */

if (is_object($this))	{
	if (is_array($this->newData["tt_guest"]["NEW"]))	{
		$query = $this->getNEWinsert("tt_guest", $this->newData["tt_guest"]["NEW"]);
//		debug($query);
		$res = mysql(TYPO3_db,$query);
		echo mysql_error();
		$this->clear_cacheCmd(intval($this->newData["tt_guest"]["NEW"]["pid"]));

		$conf = $this->getConf("tt_guest");
		if ($conf["notifyEmail"])	{
			$name = $this->newData["tt_guest"]["NEW"]["cr_name"];
			$email = $this->newData["tt_guest"]["NEW"]["cr_email"];
		
			mail ($conf["notifyEmail"], "tt_guest item submitted at ".t3lib_div::getIndpEnv("HTTP_HOST"), '
Page-id, tt_guest: '.$this->newData["tt_guest"]["NEW"][pid].'
Current page uid/title: '.$GLOBALS["TSFE"]->page[title].'/'.$GLOBALS["TSFE"]->page[uid].'
Name: '.$name.'
Email: '.$email.'
Message: '.$this->newData["tt_guest"]["NEW"]["title"].'
'.$this->newData["tt_guest"]["NEW"]["note"].'
			
			','From: '.$name.' <'.$email.'>');
		}
	}
}

?>