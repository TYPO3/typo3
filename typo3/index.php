<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2004 Kasper Skaarhoj (kasper@typo3.com)
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
 * Login-screen of TYPO3.
 *
 * $Id$
 * Revised for TYPO3 3.6 December/2003 by Kasper Skaarhoj
 * XHTML compliant
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *   87: class SC_index
 *  120:     function init()
 *  151:     function main()
 *  237:     function printContent()
 *
 *              SECTION: Various functions
 *  261:     function makeLoginForm()
 *  304:     function makeLogoutForm()
 *  346:     function wrapLoginForm($content)
 *  406:     function checkRedirect()
 *  449:     function makeInterfaceSelectorBox()
 *  503:     function makeCopyrightNotice()
 *  536:     function makeLoginBoxImage()
 *  575:     function makeLoginNews()
 *
 * TOTAL FUNCTIONS: 11
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */


define('TYPO3_PROCEED_IF_NO_USER', 1);
require ('init.php');
require ('template.php');















/**
 * Script Class for rendering the login form
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class SC_index {

		// Internal, GPvars:
	var $redirect_url;			// GPvar: redirect_url; The URL to redirect to after login.
	var $GPinterface;			// GPvar: Defines which interface to load (from interface selector)
	var $u;						// GPvar: preset username
	var $p;						// GPvar: preset password
	var $L;						// GPvar: If "L" is "OUT", then any logged in used is logged out. If redirect_url is given, we redirect to it
	var $loginRefresh;			// Login-refresh boolean; The backend will call this script with this value set when the login is close to being expired and the form needs to be redrawn.
	var $commandLI;				// Value of forms submit button for login.

		// Internal, static:
	var $redirectToURL;			// Set to the redirect URL of the form (may be redirect_url or "alt_main.php")
	var $L_vars;				// Set to the labels used for the login screen.

		// Internal, dynamic:
	var $content;				// Content accumulation

	var $interfaceSelector;			// A selector box for selecting value for "interface" may be rendered into this variable
	var $interfaceSelector_jump;	// A selector box for selecting value for "interface" may be rendered into this variable - this will have an onchange action which will redirect the user to the selected interface right away
	var $interfaceSelector_hidden;	// A hidden field, if the interface is not set.






	/**
	 * Initialize the login box. Will also react on a &L=OUT flag and exit.
	 *
	 * @return	void
	 */
	function init()	{
		global $BE_USER,$TYPO3_CONF_VARS;

			// GPvars:
		$this->redirect_url = t3lib_div::_GP('redirect_url');
		$this->GPinterface = t3lib_div::_GP('interface');
		$this->u = t3lib_div::_GP('u');							// preset username
		$this->p = t3lib_div::_GP('p');							// preset password
		$this->L = t3lib_div::_GP('L');							// If "L" is "OUT", then any logged in used is logged out. If redirect_url is given, we redirect to it
		$this->loginRefresh = t3lib_div::_GP('loginRefresh');		// Login
		$this->commandLI = t3lib_div::_GP('commandLI');			// Value of "Login" button. If set, the login button was pressed.

			// Getting login labels:
		$this->L_vars = explode('|',$TYPO3_CONF_VARS['BE']['loginLabels']);

			// Setting the redirect URL to "alt_main.php" if no alternative input is given:
		$this->redirectToURL = $this->redirect_url ? $this->redirect_url : 'alt_main.php';

			// Logout?
		if ($this->L=='OUT' && is_object($BE_USER))	{
			$BE_USER->logoff();
			if ($this->redirect_url)	header('Location: '.t3lib_div::locationHeaderUrl($this->redirect_url));
			exit;
		}
	}

	/**
	 * Main function - creating the login/logout form
	 *
	 * @return	void
	 */
	function main()	{
		global $TBE_TEMPLATE, $TYPO3_CONF_VARS, $BE_USER;

			// Initialize template object:
		$TBE_TEMPLATE->docType='xhtml_trans';

			// Set JavaScript for creating a MD5 hash of the password:
		$TBE_TEMPLATE->JScode.='
			<script type="text/javascript" src="md5.js"></script>
			'.$TBE_TEMPLATE->wrapScriptTags('
				function doChallengeResponse() {	//
					password = document.loginform.p_field.value;
					if (password)	{
						password = MD5(password);	// this makes it superchallenged!!
						str = document.loginform.username.value+":"+password+":"+document.loginform.challenge.value;
						document.loginform.userident.value = MD5(str);
						document.loginform.p_field.value = "";
						return true;
					}
				}
			');


			// Checking, if we should make a redirect.
			// Might set JavaScript in the header to close window.
		$this->checkRedirect();

			// Initialize interface selectors:
		$this->makeInterfaceSelectorBox();

			// Creating form based on whether there is a login or not:
		if (!$BE_USER->user['uid'])	{
			$TBE_TEMPLATE->form = '
				<form action="index.php" method="post" name="loginform" onsubmit="doChallengeResponse();">
				<input type="hidden" name="login_status" value="login" />
				';
			$loginForm = $this->makeLoginForm();
		} else {
			$TBE_TEMPLATE->form = '
				<form action="index.php" method="post" name="loginform">
				<input type="hidden" name="login_status" value="logout" />
				';
			$loginForm = $this->makeLogoutForm();
		}


			// Starting page:
		$this->content.=$TBE_TEMPLATE->startPage('TYPO3 Login: '.$TYPO3_CONF_VARS['SYS']['sitename']);

			// Add login form:
		$this->content.=$this->wrapLoginForm($loginForm);

			// Ending form:
		$this->content.= '
			<input type="hidden" name="userident" value="" />
			<input type="hidden" name="challenge" value="'.md5(uniqid('')).'" />
			<input type="hidden" name="redirect_url" value="'.htmlspecialchars($this->redirectToURL).'" />
			<input type="hidden" name="loginRefresh" value="'.htmlspecialchars($this->loginRefresh).'" />
			'.$this->interfaceSelector_hidden.'
			';

			// This moves focus to the right input field:
		$this->content.=$TBE_TEMPLATE->wrapScriptTags('

				// If the login screen is shown in the login_frameset window for re-login, then try to get the username of the current/former login from opening windows main frame:
			if (parent.opener && parent.opener.TS && parent.opener.TS.username && document.loginform && document.loginform.username)	{
				document.loginform.username.value = parent.opener.TS.username;
			}

				// If for some reason there already is a username in the username for field, move focus to the password field:
			if (document.loginform.username && document.loginform.username.value == "") {
				document.loginform.username.focus();
			} else if (document.loginform.p_field && document.loginform.p_field.type!="hidden") {
				document.loginform.p_field.focus();
			}
		');

			// End page:
		$this->content.=$TBE_TEMPLATE->endPage();
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return	void
	 */
	function printContent()	{

		echo $this->content;
	}








	/*****************************
	 *
	 * Various functions
	 *
	 ******************************/

	/**
	 * Creates the login form
	 * This is drawn when NO login exists.
	 *
	 * @return	string		HTML output
	 */
	function makeLoginForm()	{

		$content.='

							<!--
								Login form:
							-->
							<table cellspacing="0" cellpadding="0" border="0" id="logintable">
									<tr>
										<td colspan="2"><h2>'.htmlspecialchars($this->L_vars[6]).'</h2></td>
									</tr>'.($this->commandLI ? '
									<tr class="c-wrong">
										<td colspan="2"><p class="c-wrong">'.htmlspecialchars($this->L_vars[9]).'</p></td>
									</tr>' : '').'
									<tr class="c-username">
										<td><p class="c-username">'.htmlspecialchars($this->L_vars[0]).':</p></td>
										<td><input type="text" name="username" value="'.htmlspecialchars($this->u).'" class="c-username" /></td>
									</tr>
									<tr class="c-password">
										<td><p class="c-password">'.htmlspecialchars($this->L_vars[1]).':</p></td>
										<td><input type="password" name="p_field" value="'.htmlspecialchars($this->p).'" class="c-password" /></td>
									</tr>'.($this->interfaceSelector && !$this->loginRefresh ? '
									<tr class="c-interfaceselector">
										<td><p class="c-interfaceselector">'.htmlspecialchars($this->L_vars[2]).':</p></td>
										<td>'.$this->interfaceSelector.'</td>
									</tr>' : '' ).'
									<tr class="c-submit">
										<td></td>
										<td><input type="submit" name="commandLI" value="'.htmlspecialchars($this->L_vars[3]).'" class="c-submit" /></td>
									</tr>
									<tr class="c-info">
										<td></td>
										<td><p class="c-info">'.htmlspecialchars($this->L_vars[7]).'</p></td>
									</tr>
								</table>';

			// Return content:
		return $content;
	}

	/**
	 * Creates the logout form
	 * This is drawn if a user login already exists.
	 *
	 * @return	string		HTML output
	 */
	function makeLogoutForm()	{
		global $BE_USER;


		$content.='

							<!--
								Login form:
							-->
							<table cellspacing="0" cellpadding="0" border="0" id="logintable">
									<tr>
										<td></td>
										<td><h2>'.htmlspecialchars($this->L_vars[6]).'</h2></td>
									</tr>
									<tr class="c-username">
										<td><p class="c-username">'.htmlspecialchars($this->L_vars[0]).':</p></td>
										<td><p class="c-username-current">'.htmlspecialchars($BE_USER->user['username']).'</p></td>
									</tr>'.($this->interfaceSelector_jump ? '
									<tr class="c-interfaceselector">
										<td><p class="c-interfaceselector">'.htmlspecialchars($this->L_vars[2]).':</p></td>
										<td>'.$this->interfaceSelector_jump.'</td>
									</tr>' : '' ).'
									<tr class="c-submit">
										<td><input type="hidden" name="p_field" value="" /></td>
										<td><input type="submit" name="commandLO" value="'.htmlspecialchars($this->L_vars[4]).'" class="c-submit" /></td>
									</tr>
									<tr class="c-info">
										<td></td>
										<td><p class="c-info">'.htmlspecialchars($this->L_vars[7]).'</p></td>
									</tr>
								</table>';

			// Return content:
		return $content;
	}

	/**
	 * Wrapping the login form table in another set of tables etc:
	 *
	 * @param	string		HTML content for the login form
	 * @return	string		The HTML for the page.
	 */
	function wrapLoginForm($content)	{

			// Logo:
		$logo = $GLOBALS['TBE_STYLES']['logo_login'] ?
					'<img src="'.htmlspecialchars($GLOBALS['BACK_PATH'].$GLOBALS['TBE_STYLES']['logo_login']).'" alt="" />' :
					'<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/typo3logo.gif','width="333" height="43"').' alt="" />';

			// Login box image:
		$loginboxImage = $this->makeLoginBoxImage();

			// Compile the page content:
		$content='

		<!--
			Wrapper table for the login form:
		-->
		<table cellspacing="0" cellpadding="0" border="0" id="wrapper">
			<tr>
				<td class="c-wrappercell" align="center">

					<!--
						Login form image:
					-->
					<div id="loginimage">
											'.$logo.'
					</div>

					<!--
						Login form wrapper:
					-->
					<table cellspacing="0" cellpadding="0" border="0" id="loginwrapper">
						<tr>
							<td>'.$loginboxImage.'</td>
							<td>
								'.$content.'
							</td>
						</tr>
					</table>

					<!--
						Copy right notice:
					-->
					<div id="copyrightnotice">
						'.$this->makeCopyrightNotice().'
					</div>

					'.$this->makeLoginNews().'
				</td>
			</tr>
		</table>';

			// Return content:
		return $content;
	}

	/**
	 * Checking, if we should perform some sort of redirection OR closing of windows.
	 *
	 * @return	void
	 */
	function checkRedirect()	{
		global $BE_USER,$TBE_TEMPLATE;

			// Do redirect:
			// If a user is logged in AND a) if either the login is just done (commandLI) or b) a loginRefresh is done or c) the interface-selector is NOT enabled (If it is on the other hand, it should not just load an interface, because people has to choose then...)
		if ($BE_USER->user['uid'] && ($this->commandLI || $this->loginRefresh || !$this->interfaceSelector))	{

				// If no cookie has been set previously we tell people that this is a problem. This assumes that a cookie-setting script (like this one) has been hit at least once prior to this instance.
			if (!$GLOBALS['HTTP_COOKIE_VARS'][$BE_USER->name])	{
				t3lib_BEfunc::typo3PrintError ('Login-error',"Yeah, that's a classic. No cookies, no TYPO3.<br /><br />Please accept cookies from TYPO3 - otherwise you'll not be able to use the system.",0);
				exit;
			}

				// Based on specific setting of interface we set the redirect script:
			switch ($this->GPinterface)	{
				case 'backend':
					$this->redirectToURL = 'alt_main.php';
				break;
				case 'frontend':
					$this->redirectToURL = '../';
				break;
			}

				// If there is a redirect URL AND if loginRefresh is not set...
			if (!$this->loginRefresh)	{
				header('Location: '.t3lib_div::locationHeaderUrl($this->redirectToURL));
				exit;
			} else {
				$TBE_TEMPLATE->JScode.=$TBE_TEMPLATE->wrapScriptTags('
					if (parent.opener && parent.opener.busy)	{
						parent.opener.busy.loginRefreshed();
						parent.close();
					}
				');
			}
		}
	}

	/**
	 * Making interface selector:
	 *
	 * @return	void
	 */
	function makeInterfaceSelectorBox()	{
		global $TYPO3_CONF_VARS;

			// Reset variables:
		$this->interfaceSelector = '';
		$this->interfaceSelector_hidden='';
		$this->interfaceSelector_jump = '';
#debug($this->redirect_url);
			// If interfaces are defined AND no input redirect URL in GET vars:
		if ($TYPO3_CONF_VARS['BE']['interfaces'] && !$this->redirect_url)	{
			$parts = t3lib_div::trimExplode(',',$TYPO3_CONF_VARS['BE']['interfaces']);
			if (count($parts)>1)	{	// Only if more than one interface is defined will we show the selector:

					// Initialize:
				$tempLabels=explode(',',$this->L_vars[5]);
				$labels=array();
				$labels['backend']=$tempLabels[0];
				$labels['frontend']=$tempLabels[1];

				$jumpScript=array();
				$jumpScript['backend']='alt_main.php';
				$jumpScript['frontend']='../';

					// Traverse the interface keys:
				foreach($parts as $valueStr)	{
					$this->interfaceSelector.='
							<option value="'.htmlspecialchars($valueStr).'">'.htmlspecialchars($labels[$valueStr]).'</option>';
					$this->interfaceSelector_jump.='
							<option value="'.htmlspecialchars($jumpScript[$valueStr]).'">'.htmlspecialchars($labels[$valueStr]).'</option>';
				}
				$this->interfaceSelector='
						<select name="interface" class="c-interfaceselector">'.$this->interfaceSelector.'
						</select>';
				$this->interfaceSelector_jump='
						<select name="interface" class="c-interfaceselector" onchange="document.location=this.options[this.selectedIndex].value;">'.$this->interfaceSelector_jump.'
						</select>';

			} else {	// If there is only ONE interface value set:

				$this->interfaceSelector_hidden='<input type="hidden" name="interface" value="'.trim($TYPO3_CONF_VARS['BE']['interfaces']).'" />';
			}
		}
	}

	/**
	 * COPYRIGHT notice
	 *
	 * Warning:
	 * DO NOT prevent this notice from being shown in ANY WAY.
	 * According to the GPL license an interactive application must show such a notice on start-up ('If the program is interactive, make it output a short notice... ' - see GPL.txt)
	 * Therefore preventing this notice from being properly shown is a violation of the license, regardless of whether you remove it or use a stylesheet to obstruct the display.
	 *
	 * @return	string		Text/Image (HTML) for copyright notice.
	 */
	function makeCopyrightNotice()	{

			// Get values from TYPO3_CONF_VARS:
		$loginCopyrightWarrantyProvider = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyProvider']));
		$loginCopyrightWarrantyURL = strip_tags(trim($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightWarrantyURL']));

			// Make warranty note:
		if (strlen($loginCopyrightWarrantyProvider)>=2 && strlen($loginCopyrightWarrantyURL)>=10)	{
			$warrantyNote='Warranty is supplied by '.htmlspecialchars($loginCopyrightWarrantyProvider).'; <a href="'.htmlspecialchars($loginCopyrightWarrantyURL).'" target="_blank">click for details.</a>';
		} else {
			$warrantyNote='TYPO3 comes with ABSOLUTELY NO WARRANTY; <a href="http://typo3.com/1316.0.html" target="_blank">click for details.</a>';
		}

			// Compile full copyright notice:
		$copyrightNotice = '<a href="http://typo3.com/" target="_blank">'.
					'<img src="gfx/loginlogo_transp.gif" width="75" height="19" alt="TYPO3 logo" align="left" />'.
					'TYPO3 CMS'.($GLOBALS['TYPO3_CONF_VARS']['SYS']['loginCopyrightShowVersion']?' ver. '.htmlspecialchars($GLOBALS['TYPO_VERSION']):'').
					'</a>. '.
					'Copyright &copy; 1998-2004 Kasper Sk&#229;rh&#248;j. Extensions are copyright of their respective owners. '.
					'Go to <a href="http://typo3.com/" target="_blank">http://typo3.com/</a> for details. '.
					$warrantyNote.' '.
					'This is free software, and you are welcome to redistribute it under certain conditions; <a href="http://typo3.com/1316.0.html" target="_blank">click for details</a>. '.
					'Obstructing the appearance of this notice is prohibited by law.';

			// Return notice:
		return $copyrightNotice;
	}

	/**
	 * Returns the login box image, whether the default or an image from the rotation folder.
	 *
	 * @return	string		HTML image tag.
	 */
	function makeLoginBoxImage()	{
		$loginboxImage = '';
		if ($GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder'])	{		// Look for rotation image folder:
			$absPath = t3lib_div::resolveBackPath(PATH_typo3.$GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder']);

				// Get rotation folder:
			$dir = t3lib_div::getFileAbsFileName($absPath);
			if ($dir && @is_dir($dir))	{

					// Get files for rotation into array:
				$files = t3lib_div::getFilesInDir($dir,'png,jpg,gif');

					// Pick random file:
				srand((float) microtime() * 10000000);
				$randImg = array_rand($files, 1);

					// Get size of random file:
				$imgSize = @getimagesize($dir.$files[$randImg]);

					// Create image tag:
				if (is_array($imgSize))	{
					$loginboxImage = '<img src="'.htmlspecialchars($GLOBALS['TBE_STYLES']['loginBoxImage_rotationFolder'].$files[$randImg]).'" '.$imgSize[3].' id="loginbox-image" alt="" />';
				}
			}
		} else {	// If no rotation folder configured, print default image:
			$imagecopy = 'Photo: &copy; 2004 Kasper Sk&#229;rh&#248;j';	// Directly outputted in image attributes...
			$loginboxImage = '<img'.t3lib_iconWorks::skinImg($GLOBALS['BACK_PATH'],'gfx/loginbox_image_dev.png','width="200" height="133"').' id="loginbox-image" alt="'.$imagecopy.'" title="'.$imagecopy.'" />';
		}

			// Return image tag:
		return $loginboxImage;
	}

	/**
	 * Make login news - renders the HTML content for a list of news shown under the login form. News data is added through $TYPO3_CONF_VARS
	 *
	 * @return	string		HTML content
	 * @credits			Idea by Jan-Hendrik Heuing
	 */
	function makeLoginNews()	{

			// Reset output variable:
		$newsContent= '';

			// Traverse news array IF there are records in it:
		if (is_array($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']) && count($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews']))	{
			foreach($GLOBALS['TYPO3_CONF_VARS']['BE']['loginNews'] as $newsItem)	{
				$newsContent.='
						<tr>
							<td class="c-date">'.htmlspecialchars($newsItem['date']).'</td>
							<td class="c-header">'.htmlspecialchars($newsItem['header']).'</td>
						</tr>
						<tr>
							<td></td>
							<td class="c-content">'.trim($newsItem['content']).'</td>
						</tr>
						<tr class="c-spacer">
							<td colspan="2"></td>
						</tr>
				';
			}

				// Wrap in a table:
			$newsContent= '

					<!--
						Login screen news:
					-->
					<div id="loginNews">
					<h2>'.htmlspecialchars($this->L_vars[8]).'</h2>
					<table border="0" cellpadding="0" cellspacing="0">
						'.$newsContent.'
					</table>
					</div>
			';
		}

			// Return content:
		return $newsContent;
	}
}

// Include extension?
if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/index.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['typo3/index.php']);
}










// Make instance:
$SOBE = t3lib_div::makeInstance('SC_index');
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
