<?php
/***************************************************************
*  Copyright notice
*  
*  (c) 1999-2003 Kasper Skaarhoj (kasper@typo3.com)
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
 * GET vars:
 * u=	default username
 * p=	default password
 * L=	'OUT' = logout
 * redirect_url=	URL to redirect to instead of starting the TBE
 * 
 * commandLI
 * loginRefresh
 * interface
 *
 * @author	Kasper Skaarhoj <kasper@typo3.com>
 * @package TYPO3
 * @subpackage core
 *
 */

define("TYPO3_PROCEED_IF_NO_USER", 1);
require ("init.php");
require ("template.php");
require_once (PATH_t3lib."class.t3lib_loadmodules.php");


// ***************************
// Script Classes
// ***************************
class SC_index {
	var $content;
	
	var $loadModules;
	var $redirect_url;
	var $redirect_url_input;
	var $GPinterface;
	var $u;
	var $p;
	var $L;
	var $L_vars;
	var $interfaceSelector;

	/**
	 * Initialize the login box. Will also react on a &L=OUT flag and exit.
	 */ 
	function init()	{
		global $BE_USER,$TYPO3_CONF_VARS;
		
		// ******************************
		// Registering Global Vars
		// ******************************
			// URL to redirect to.
		$this->redirect_url = t3lib_div::GPvar("redirect_url");
		$this->redirect_url_input = $this->redirect_url ? 1 : 0;	
		$this->GPinterface = t3lib_div::GPvar("interface");
		$this->L_vars = explode("|",$TYPO3_CONF_VARS["BE"]["loginLabels"]);
		
			// Only change redirect_url if it has not been set from outside...
		if (!$this->redirect_url_input)	{$this->redirect_url="alt_main.php";}	
		
		$this->u = t3lib_div::GPvar("u");		// preset username
		$this->p = t3lib_div::GPvar("p");		// preset password
		$this->L = t3lib_div::GPvar("L");		// If "L" is "OUT", then any logged in used is logged out. If redirect_url is given, we redirect to it
		
		// *********
		// Logout?
		// *********
		if ($this->L=="OUT" && is_object($BE_USER))	{
			$BE_USER->logoff();
			if ($this->redirect_url)	header("Location: ".t3lib_div::locationHeaderUrl($this->redirect_url));
			exit;
		}
	}

	/**
	 * Main function - just calling subfunctions.
	 */
	function main()	{
		$this->content="";
		$this->content.=$this->makeLoginLogoutForm();
		$this->content.=$this->makeStartHTML();
	}

	/**
	 * Making the login/logout form
	 */
	function makeLoginLogoutForm()	{
		global $BE_USER,$TYPO3_CONF_VARS,$TBE_TEMPLATE;

		$TBE_TEMPLATE->bgColor="#CCCCCC";
		
		// Code for the challenged form.
		$challenge = md5(uniqid(""));
		
		$content='
		<div align="center">
		<form action="index.php" method="POST" name="loginform" autocomplete="off">
		<table border=0 cellspacing=2 cellpadding=10 width="100%">
		<tr>
			<td bgcolor="'.$TBE_TEMPLATE->bgColor2.'" align="center">
				<table border=0 cellspacing=0 cellpadding=2>
					<tr>
						<td><img src="clear.gif" width=1 height=1 hspace=35></td>
						<td><img src="clear.gif" width=1 height=1 hspace=5></td>
						<td><img src="clear.gif" width=1 height=1 hspace=25></td>
					</tr>
		';
		
			// MAKING interface selector:
		$this->interfaceSelector = "";
		$interfaceHidden="";
		$interfaceSelector_jump = "";
		if ($TYPO3_CONF_VARS["BE"]["interfaces"] && !$this->redirect_url_input)	{
			$parts = t3lib_div::trimExplode(",",$TYPO3_CONF_VARS["BE"]["interfaces"]);
			if (count($parts)>1)	{
				$tempLabels=explode(",",$this->L_vars[5]);
				$labels=array();
				$labels["backend"]=$tempLabels[0];
				$labels["frontend"]=$tempLabels[1];
				
				$jumpScript=array();
				$jumpScript["backend"]="alt_main.php";
				$jumpScript["frontend"]="../";
				
				reset($parts);
				while(list(,$valueStr)=each($parts))	{
					$this->interfaceSelector.='<option value="'.$valueStr.'">'.htmlspecialchars($labels[$valueStr]).'</option>';
					$interfaceSelector_jump.='<option value="'.$jumpScript[$valueStr].'">'.htmlspecialchars($labels[$valueStr]).'</option>';
				}
				$this->interfaceSelector='<select name="interface">'.$this->interfaceSelector.'</select>';
				$interfaceSelector_jump='<select name="interface" onChange="document.location=this.options[this.selectedIndex].value;">'.$interfaceSelector_jump.'</select>';
			} elseif (!$this->redirect_url_input) {
				$interfaceHidden='<input type="hidden" name="interface" value="'.trim($TYPO3_CONF_VARS["BE"]["interfaces"]).'">';
				$this->GPinterface=trim($TYPO3_CONF_VARS["BE"]["interfaces"]);
			}
		}

			// COPYRIGHT notice
		$loginCopyrightWarrantyProvider = strip_tags(trim($GLOBALS["TYPO3_CONF_VARS"]["SYS"]["loginCopyrightWarrantyProvider"]));
		$loginCopyrightWarrantyURL = strip_tags(trim($GLOBALS["TYPO3_CONF_VARS"]["SYS"]["loginCopyrightWarrantyURL"]));
		
		if (strlen($loginCopyrightWarrantyProvider)>=2 && strlen($loginCopyrightWarrantyURL)>=10)	{
			$warrantyNote='Warranty is supplied by '.$loginCopyrightWarrantyProvider.'; <a href="'.$loginCopyrightWarrantyURL.'" target="_blank">click for details.</a>';
		} else {
			$warrantyNote='TYPO3 comes with ABSOLUTELY NO WARRANTY; <a href="http://typo3.com/1316.0.html" target="_blank">click for details.</a>';
		}
		
			// No user session:
		if (!$BE_USER->user["uid"])	{
			$content.='
					<tr>
						<td nowrap="nowrap"><font face="VERDANA,ARIAL,SANS-SERIF" size="2"><b>'.$this->L_vars[0].':</b></font></td>
						<td></td>
						<td nowrap="nowrap"><input type="Text" name="username" value="'.$this->u.'"'.$TBE_TEMPLATE->formWidth(10).' onBlur="if(parent.typoWin && parent.typoWin.TS){this.value=parent.typoWin.TS.username;}"></td>
					</tr>
					<tr>
						<td nowrap="nowrap"><font face="VERDANA,ARIAL,SANS-SERIF" size="2"><b>'.$this->L_vars[1].':</b></font></td>
						<td></td>
						<td nowrap="nowrap"><input type="password" name="p_field" value="'.$this->p.'"'.$TBE_TEMPLATE->formWidth(10).'></td>
					</tr>';
			if ($this->interfaceSelector && !t3lib_div::GPvar("loginRefresh"))	{
					$content.='<tr>
							<td nowrap="nowrap"><font face="VERDANA,ARIAL,SANS-SERIF" size="2"><b>'.$this->L_vars[2].':</b></font></td>
							<td></td>
							<td nowrap="nowrap">'.$this->interfaceSelector.'</td>
						</tr>';
			}
			$content.='<tr>
						<td nowrap="nowrap"></td>
						<td></td>
						<td nowrap="nowrap"><input type="submit" name="commandLI" value="'.$this->L_vars[3].'" onClick="document.loginform.login_status.value=\'login\';doChallengeResponse();"></td>
					</tr>
			';
			$content.='<tr>
						<td colspan=3 align="center"><font face="VERDANA,ARIAL,SANS-SERIF" size="1" color="#666666">'.$this->L_vars[7].'</font></td>
					</tr>
			';
		} else {	// If there is a user session already:
			if ($interfaceSelector_jump)	{
			$content.='<tr>
							<td nowrap="nowrap"><font face="VERDANA,ARIAL,SANS-SERIF" size="2"><b>'.$this->L_vars[2].':</b></font></td>
							<td></td>
							<td nowrap="nowrap">'.$interfaceSelector_jump.'</td>
						</tr>';
			$content.='<tr>
							<td colspan=3>&nbsp;</td>
						</tr>';
			}
			$content.='
					<tr>
						<td nowrap="nowrap"><B><font face="VERDANA,ARIAL,SANS-SERIF" size="2">&nbsp;&nbsp;'.$BE_USER->user["username"].'&nbsp;&nbsp;</font></b></td>
						<td></td>
						<td nowrap="nowrap"><input type="hidden" name="p_field" value=""><input type="Submit" name="commandLO" value="'.$this->L_vars[4].'" onClick="document.loginform.p_field.value=\'\'; document.loginform.login_status.value=\'logout\';"></td>
					</tr>
			';
		}
		
			// Ending form:
		$content.='
				</table>
			</td>
		</tr>
		</table>

		
		<div align="left" style="text-align:left;font-family: verdana,arial,helvetica; font-size:10px; margin-top:10px; width:500px;"><a href="http://typo3.com/" target="_blank"><img src="gfx/loginlogo_transp.gif" width="75" vspace=2 height="19" alt="TYPO3 logo" border="0" align="left">TYPO3 CMS'.($GLOBALS["TYPO3_CONF_VARS"]["SYS"]["loginCopyrightShowVersion"]?' ver. '.htmlspecialchars($GLOBALS["TYPO_VERSION"]):'').'</a>. Copyright &copy; 1998-2003 Kasper Sk&#229;rh&#248;j. Extensions are copyright of their respective owners. Go to <a href="http://typo3.com/" target="_blank">http://typo3.com/</a> for details. 
		'.strip_tags($warrantyNote,'<a>').' This is free software, and you are welcome to redistribute it under certain conditions; <a href="http://typo3.com/1316.0.html" target="_blank">click for details</a>. Obstructing the appearance of this notice is prohibited by law.
		</div>
		
		<input type="Hidden" name="userident" value="">
		<input type="Hidden" name="challenge" value="'.$challenge.'">
		<input type="Hidden" name="redirect_url" value="'.htmlspecialchars($this->redirect_url).'">
		<input type="Hidden" name="loginRefresh" value="'.t3lib_div::GPvar("loginRefresh").'">
		<input type="Hidden" name="login_status" value="">
		'.$interfaceHidden.'
		</form>
		</div>
		';
		
			// This returns the login form.
		return $content;
	}
	
	/**
	 * Make the HTML which will start the BE:
	 */
	function makeStartHTML()	{
		global $BE_USER,$LANG,$BACK_PATH,$TCA_DESCR,$TCA,$HTTP_GET_VARS,$HTTP_POST_VARS,$CLIENT,$TYPO3_CONF_VARS;
		global $TBE_MODULES,$TBE_TEMPLATE;

		$content="";;

			// This should output the username by default into the re-login form
		if (!$BE_USER->user["uid"])	{
			$content.='
			<script language="javascript" type="text/javascript">
			  if (parent.typoWin && parent.typoWin.TS)	{
				  document.loginform.username.value = parent.typoWin.TS.username;
			  }
			  if (document.loginform.username.value == "") {
			    document.loginform.username.focus();
			  } else {
			    document.loginform.p_field.focus();
			  }
			</script>
			';
		}
		
		// If a users is logged in:
		// AND if either the login is just done (commandLI) or a loginRefresh is done or the interface-selector is NOT enabled (If it is on the other hand, it should not just load an interface, because people has to choose then...)
		if ($BE_USER->user["uid"] && (t3lib_div::GPvar("commandLI") || t3lib_div::GPvar("loginRefresh") || !$this->interfaceSelector))	{

				// If no cookie has been set previously we tell people that this is a problem. This assumes that a cookie-setting script (like this one) has been hit at least once prior to this instance.
			if (!$GLOBALS["HTTP_COOKIE_VARS"][$BE_USER->name])	{
				t3lib_BEfunc::typo3PrintError ("Login-error","Yeah, that's a classic. No cookies, no TYPO3.<BR><BR>Please accept cookies from TYPO3 - otherwise you'll not be able to use the system.",0);
				exit;
			}

				// based on specific setting of interface we set the redirect script:
			switch ($this->GPinterface)	{
				case "backend":
					$this->redirect_url = "alt_main.php";
				break;
				case "frontend":
					$this->redirect_url = "../";
				break;
			}

				// If there is a redirect URL AND if loginRefresh is not set...
			if ($this->redirect_url && !t3lib_div::GPvar("loginRefresh"))	{
				header("Location: ".t3lib_div::locationHeaderUrl($this->redirect_url));
				exit;
			} else {
				$content.='
				<script language="javascript" type="text/javascript">
					if (parent.typoWin && parent.typoWin.busy) {
						parent.typoWin.busy.loginRefreshed();
						parent.close();
					}
				</script>
				';
			}
		}
		return $content;
	}
	
	/**
	 * Output it all...
	 */
	function printContent()	{
		global $TBE_TEMPLATE;

		echo $TBE_TEMPLATE->startPage("TYPO3 Login");
		echo '
		<script language="javascript" type="text/javascript" src="md5.js"></script>
		<script language="javascript" type="text/javascript">
		  function doChallengeResponse() {
		  	password = document.loginform.p_field.value;
			if (password)	{
				password = MD5(password);	// this makes it superchallenged!!
			    str = document.loginform.username.value+":"+password+":"+document.loginform.challenge.value;
			    document.loginform.userident.value = MD5(str);
			    document.loginform.p_field.value = "";
			    document.loginform.submit();
			}
		  }
		</script>';
		
		t3lib_BEfunc::typo3PrintError ($this->L_vars[6],$this->content,"",0);
		echo $TBE_TEMPLATE->endPage();
	}
}

// Include extension?
if (defined("TYPO3_MODE") && $TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/index.php"])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]["XCLASS"]["typo3/index.php"]);
}










// Make instance:
$SOBE = t3lib_div::makeInstance("SC_index");
$SOBE->init();
$SOBE->main();
$SOBE->printContent();
?>
