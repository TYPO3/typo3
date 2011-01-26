<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2011 Kasper Skårhøj (kasperYYYY@typo3.com)
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
 * Logo menu
 *
 * $Id$
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 */


require ('init.php');
require ('template.php');


/**
 * Script Class for rendering logo menu
 *
 * @author	Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_logomenu extends t3lib_topmenubase {

	var $id = '_logomenu';

	/**
	 * Main function
	 *
	 * @return	void
	 */
	function main()	{
		switch((string)t3lib_div::_GET('cmd'))	{
			case 'menuitem':
				echo '
				<img src="gfx/x_t3logo.png" width="61" height="16" hspace="3" alt="" />';

				$menuItems = array(
					array(
						'title' => 'About TYPO3',
						'xurl' => TYPO3_URL_GENERAL,
						'subitems' => array(
							array(
								'title' => 'License',
								'xurl' => TYPO3_URL_LICENSE,
							),
							array(
								'title' => 'Support',
								'subitems' => array(
									array(
										'title' => 'Mailing lists',
										'xurl' => TYPO3_URL_MAILINGLISTS,
									),
									array(
										'title' => 'Documentation',
										'xurl' => TYPO3_URL_DOCUMENTATION,
									),
									array(
										'title' => 'Find consultancy',
										'xurl' => TYPO3_URL_CONSULTANCY,
									),
								)
							),
							array(
								'title' => 'Contribute',
								'xurl' => TYPO3_URL_CONTRIBUTE
							),
							array(
								'title' => 'Donate',
								'xurl' => TYPO3_URL_DONATE,
								'icon' => '1'
							)
						)
					),
					array(
						'title' => 'Extensions',
						'url' => 'mod/tools/em/index.php'
					),
					array(
						'title' => 'Menu preferences and such things',
						'onclick' => 'alert("A dialog is now shown which will allow user configuration of items in the menu");event.stopPropagation();',
						'state' => 'checked'
					),
					array(
						'title' => '--div--'
					),
					array(
						'title' => 'Recent Items',
						'id' => $this->id.'_recent',
						'subitems' => array(),
						'html' => $this->menuItemObject($this->id.'_recent','
							fetched: false,
							onActivate: function() {
//								if (!this.fetched)	{
									//Element.update("'.$this->id.'_recent-layer","asdfasdf");
									getElementContent("'.$this->id.'_recent-layer", 0, "logomenu.php?cmd=recent")
									this.fetched = true;
//								}
							}
						')
					),
					array(
						'title' => '--div--'
					),
					array(
						'title' => 'View frontend',
						'xurl' => t3lib_div::getIndpEnv('TYPO3_SITE_URL')
					),
					array(
						'title' => 'Log out',
						'onclick' => "top.document.location='logout.php';"
					),
				);

				echo $this->menuLayer($menuItems);
			break;
			case 'recent':

				$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
							'sys_log.*, MAX(sys_log.tstamp) AS tstamp_MAX',
							'sys_log,pages',
							'pages.uid=sys_log.event_pid AND sys_log.userid='.intval($GLOBALS['BE_USER']->user['uid']).
								' AND sys_log.event_pid>0 AND sys_log.type=1 AND sys_log.action=2 AND sys_log.error=0',
							'tablename,recuid',
							'tstamp_MAX DESC',
							20
						);

				$items = array();

				while($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res))	{
					$elRow = t3lib_BEfunc::getRecord($row['tablename'],$row['recuid']);
					if (is_array($elRow))	{
						$items[] = array(
							'title' => t3lib_div::fixed_lgd_cs(t3lib_BEfunc::getRecordTitle($row['tablename'], $elRow), $GLOBALS['BE_USER']->uc['titleLen']) . ' - ' . t3lib_BEfunc::calcAge($GLOBALS['EXEC_TIME'] - $row['tstamp_MAX']),
							'icon' => array(t3lib_iconworks::getIcon($row['tablename'],$elRow),'width="18" height="16"'),
							'onclick' => 'content.'.t3lib_BEfunc::editOnClick('&edit['.$row['tablename'].']['.$row['recuid'].']=edit','','dummy.php')
						);
					}
				}

				echo $this->menuItems($items);
			break;
		}
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/logomenu.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['typo3/logomenu.php']);
}



// Make instance:
$SOBE = t3lib_div::makeInstance('SC_logomenu');
$SOBE->main();

?>
