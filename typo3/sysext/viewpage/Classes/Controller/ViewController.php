<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
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
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Controller for viewing the frontend
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage viewpage
 */
class Tx_Viewpage_Controller_ViewController extends Tx_Extbase_MVC_Controller_ActionController {

	/**
	 * @var integer
	 */
	protected $id;

	/**
	 * @var integer
	 */
	protected $type;

	/**
	 * @var array
	 */
	protected $pageinfo;

	/**
	 * @var string
	 */
	protected $permissionsClause;

	/**
	 * @var array
	 */
	protected $modTSconfig;

	/**
	 * Initialize member variables
	 */
	protected function initializeAction() {
		$this->id = intval(t3lib_div::_GP('id'));
		$this->permissionsClause = $GLOBALS['BE_USER']->getPagePermsClause(1);

			// Page/be_user TSconfig settings and blinding of menu-items
		$this->modTSconfig = t3lib_BEfunc::getModTSconfig($this->id, 'mod.web_view');
		$this->type = intval($this->modTSconfig['properties']['type']);
	}

	/**
	 * Show selected page from pagetree in iframe
	 *
	 * @return void
	 */
	public function showAction() {
			// Access check ...
			// The page will show only if there is a valid page and if this page may be viewed by the user
		$this->pageinfo = t3lib_BEfunc::readPageAccess($this->id, $this->permissionsClause);
		$access = is_array($this->pageinfo) ? 1 : 0;
		$addCmd = '';
		if ($this->id && $access)	{
			$addCmd = '&ADMCMD_view=1&ADMCMD_editIcons=1' . t3lib_BEfunc::ADMCMD_previewCmds($this->pageinfo);
		}

		$parts = parse_url(t3lib_div::getIndpEnv('TYPO3_SITE_URL'));
		$domainName = t3lib_BEfunc::getDomainStartPage($parts['host'],$parts['path']) ?
			t3lib_BEfunc::firstDomainRecord(t3lib_BEfunc::BEgetRootLine($this->id)):
			'';

			// Preview of mount pages
		$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');

		/** @var t3lib_pageSelect $sysPage */
		$sysPage->init(FALSE);
		$mountPointInfo = $sysPage->getMountPointInfo($this->id);
		if ($mountPointInfo && $mountPointInfo['overlay']) {
			$this->id = $mountPointInfo['mount_pid'];
			$addCmd .= '&MP=' . $mountPointInfo['MPvar'];
		}

		$page = (array) $sysPage->getPage($this->id);

		$urlScheme = 'http';
		if ($page['url_scheme'] == 2 || $page['url_scheme'] == 0 && t3lib_div::getIndpEnv('TYPO3_SSL')) {
			$urlScheme = 'https';
		}

		$url = ($domainName
			? $urlScheme . '://' . $domainName
			: $GLOBALS['BACK_PATH'] . '..') . '/index.php?id=' . $this->id .
				($this->type ? '&type=' . $this->type : '') . $addCmd;

		$this->view->assign('url', $url);
	}

}

?>