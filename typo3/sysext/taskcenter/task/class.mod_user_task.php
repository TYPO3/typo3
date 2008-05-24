<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2008 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
* Module class for task module
*
* @author Kasper Skårhøj <kasperYYYY@typo3.com>
* @author Christian Jul Jensen <christian(at)jul(dot)net>
*
* Revision for TYPO3 3.8.0 / Native Workflow System
*/

require_once(PATH_t3lib.'class.t3lib_extobjbase.php');

class mod_user_task extends t3lib_extobjbase {
	var $getUserNamesFields = 'username,usergroup,usergroup_cached_list,uid,realName,email';
	var $userGroupArray = array();
	var $perms_clause = '';

	var $backPath;

	/**
	 * BE user
	 *
	 * @var t3lib_beUserAuth
	 */
	var $BE_USER;

	function JScode() {

	}

	/**
	 * Send an email...
	 *
	 * @param	string		$email: the email address to send to
	 * @param	string		$subject: the subject of the emil
	 * @param	string		$message: the message body of the email
	 * @return	void
	 */
	function sendEmail($recipient, $subject, $message) {
		$message .= '

				--------
				'.sprintf($GLOBALS['LANG']->getLL('messages_emailFooter'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'], t3lib_div::getIndpEnv('HTTP_HOST'));

		require_once(PATH_t3lib.'class.t3lib_htmlmail.php');
		$email = t3lib_div::makeInstance('t3lib_htmlmail');
		$email->start();
		$email->useBase64();
		$email->subject = $GLOBALS['TYPO3_CONF_VARS']['BE']['notificationPrefix'].' '.$subject;
		$email->from_email = $this->BE_USER->user['email'];
		$email->from_name = $this->BE_USER->user['realName'];
		$email->addPlain($message);
		$email->setHTML($email->encodeMsg($message));
		$email->setHeaders();
		$email->setContent();
		$email->recipient = $recipient;
		$email->sendTheMail();
	}

	/**
	 * Initialise the object
	 *
	 * @param	object		$BE_USER: instance of t3lib_beuserauth representing the current be user.
	 * @return	void
	 */
	function mod_user_task_init($BE_USER) {
		$this->BE_USER = $BE_USER;
		$this->perms_clause = $this->BE_USER->getPagePermsClause(1);
	}

	/**
	 * Return helpbubble image
	 *
	 * @return	string		image tag (HTML)
	 */
	function helpBubble() {
		return '<img src="'.$this->backPath.'gfx/helpbubble.gif" width="14" height="14" hspace=2 align=top'.$GLOBALS['SOBE']->doc->helpStyle().'>';
	}


	/**
	 * Create a link to the module with the name of the module as link text.
	 *
	 * @param	string		$key: the classname of the module
	 * @param	bool		$dontLink: Just return the name of the module without a link.
	 * @param	stting		$params: HTTP GET parameter string to add to the link (not used if dontLink true)
	 * @return	string		link (HTML) / name of module (regular string)
	 */
	function headLink($key, $dontLink = false, $params = '') {
		$str = $GLOBALS['SOBE']->MOD_MENU['function'][$key];
		if (!$dontLink) $str = '<a href="index.php?SET[function]='.$key.$params.'" onClick="this.blur();">'.htmlspecialchars($str).'</a>';
		return $str;
	}

	/**
	 * Return a string cropped to a fixed length according to system setting or parameter
	 *
	 * @param	string		$str: string to be cropped.
	 * @param	int		$len: length of the cropped string, system settings is used if none is given
	 * @return	string		cropped string
	 */
	function fixed_lgd($str, $len = 0) {
		return t3lib_div::fixed_lgd($str, $len?$len:$this->BE_USER->uc['titleLen']);
	}

	/**
	 * Return an error icon
	 *
	 * @return	string		Image tag (HTML)
	 */
	function errorIcon() {
		return '<img src="'.$this->backPath.'gfx/icon_fatalerror.gif" width="18" height="16" align=top>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	array		...
	 */
	function getUserAndGroupArrays() {
		// Get groupnames for todo-tasks
		$be_group_Array = t3lib_BEfunc::getListGroupNames('title,uid');
		$groupArray = array_keys($be_group_Array);
		// Usernames
		$be_user_Array = $be_user_Array_o = t3lib_BEfunc::getUserNames($this->getUserNamesFields);
		if (!$GLOBALS['BE_USER']->isAdmin()) $be_user_Array = t3lib_BEfunc::blindUserNames($be_user_Array, $groupArray, 1);

		$this->userGroupArray = array($be_user_Array, $be_group_Array, $be_user_Array_o);
		return $this->userGroupArray;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$tstamp: ...
	 * @param	[type]		$prefix: ...
	 * @return	[type]		...
	 */
	function dateTimeAge($tstamp, $prefix = 1) {
		return t3lib_BEfunc::dateTimeAge($tstamp, $prefix);
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$mod: ...
	 * @return	[type]		...
	 */
	function accessMod($mod) {
		return $this->BE_USER->modAccess(array('name' => $mod, 'access' => 'user,group'), 0);
	}

	/**
	 * Create configuration for entry in the left tab menu.
	 *
	 * @param	string		$htmlContent: Content that does not get escaped, use this for icons links etc. (HTML)
	 * @param	string		$label: bTitle of the tab, escaped for HTML, dispalyed after html content.
	 * @param	string		$content: html content that gets displayed when the tab is activated. (HTML)
	 * @param	string		$popUpDescription: alt-text for the tab text
	 * @return	array		proper configuration for the tab menu.
	 */
	function mkMenuConfig($htmlContent, $label = "", $content = "", $popUpDescription = '') {
		$configArr = Array();
		if ((string) $htmlContent) $configArr['icon'] = $htmlContent;
		if ((string) $label) $configArr['label'] = $label;
		if ((string) $content) $configArr['content'] = $content;
		if ((string) $linkTitle) $configArr['linkTitle'] = $linkTitle;
		return $configArr;
	}

	/**
	 * Returns HTML code to dislay an url in an iframe with the right side of the taskcenter
	 *
	 * @param	string		$url: url to display
	 * @param	[type]		$max: ...
	 * @return	string		code that inserts the iframe (HTML)
	 */
	function urlInIframe($url,$max=0) {
		return '<iframe onload="resizeIframe(this,'.$max.');" scrolling="auto" width="100%" src="'.$url.'" name="list_frame" id="list_frame" frameborder="no" style="border: none;"></iframe>';
	}


}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/class.mod_user_task.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/taskcenter/task/class.mod_user_task.php']);
}


?>
