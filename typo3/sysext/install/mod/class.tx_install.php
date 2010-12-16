<?php
/***************************************************************
*  Copyright notice
*
*  (c) 1999-2009 Kasper Skaarhoj (kasperYYYY@typo3.com)
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
 * Contains the class for the Install Tool
 *
 * $Id$
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Ingmar Schlecht <ingmar@typo3.org>
 */
/**
 * [CLASS/FUNCTION INDEX of SCRIPT]
 *
 *
 *
 *  162: class tx_install extends t3lib_install
 *  234:     function tx_install()
 *  318:     function checkPassword()
 *  362:     function loginForm()
 *  396:     function init()
 *  574:     function stepOutput()
 *  836:     function checkTheConfig()
 *  867:     function typo3conf_edit()
 *  976:     function phpinformation()
 *
 *              SECTION: typo3temp/ manager
 * 1079:     function typo3TempManager()
 * 1199:     function getSelectorOptions($deleteType,$tt='')
 *
 *              SECTION: cleanup manager
 * 1231:     function cleanupManager()
 *
 *              SECTION: CONFIGURATION FORM
 * 1299:     function generateConfigForm($type='')
 * 1367:     function getDefaultConfigArrayComments($string,$mainArray=array(),$commentArray=array())
 *
 *              SECTION: CHECK CONFIGURATION FUNCTIONS
 * 1419:     function checkConfiguration()
 * 1572:     function check_mail($cmd='')
 * 1611:     function checkExtensions()
 * 1673:     function checkDirs()
 * 1762:     function checkImageMagick($paths)
 * 1837:     function _checkImageMagickGifCapability($path)
 * 1880:     function _checkImageMagick_getVersion($file, $path)
 * 1915:     function checkDatabase()
 * 1977:     function setupGeneral($cmd='')
 * 2166:     function writeToLocalconf_control($lines="", $showOutput=1)
 * 2190:     function outputExitBasedOnStep($content)
 * 2206:     function setLabelValueArray($arr,$type)
 * 2246:     function getFormElement($labels,$values,$fieldName,$default,$msg='')
 * 2266:     function getDatabaseList()
 * 2290:     function setupGeneralCalculate()
 * 2368:     function getGDPartOfPhpinfo()
 * 2387:     function isTTF($phpinfo='')
 *
 *              SECTION: ABOUT the isXXX functions.
 * 2436:     function isGD()
 * 2447:     function isGIF()
 * 2459:     function isJPG()
 * 2470:     function isPNG()
 * 2482:     function ImageTypes()
 * 2493:     function getGDSoftwareInfo()
 * 2505:     function generallyAboutConfiguration()
 *
 *              SECTION: IMAGE processing
 * 2565:     function checkTheImageProcessing()
 * 3046:     function isExtensionEnabled($ext, $headCode, $short)
 * 3062:     function displayTwinImage ($imageFile, $IMcommands=array(), $note='')
 * 3130:     function getTwinImageMessage($message, $label_1="", $label_2='')
 * 3146:     function formatImCmds($arr)
 * 3167:     function imagemenu()
 *
 *              SECTION: DATABASE analysing
 * 3209:     function checkTheDatabase()
 * 3849:     function updateWizard()
 * 3873:     function updateWizard_parts($action)
 * 3987:     function getUpgradeObjInstance($className, $identifier)
 * 4000:     function isBackendAdminUser()
 * 4011:     function isStaticTemplates()
 * 4023:     function isBasicComplete($tLabel)
 * 4063:     function generateUpdateDatabaseForm($type, $arr_update, $arr_remove, $action_type)
 * 4094:     function getUpdateDbFormWrap($action_type, $content, $label='Write to database')
 * 4107:     function displayFields($arr, $pre=0, $label='')
 * 4132:     function displayFieldComp($arr, $arr_db)
 * 4174:     function displaySuggestions($arr, $excludeList='')
 * 4204:     function compareDatabaseAndTCA($FDsrc, $TCA, $onlyFields=0)
 * 4262:     function compareTCAandDatabase($TCA, $FDcomp)
 * 4296:     function suggestFieldDefinition($fieldInfo)
 * 4373:     function getItemArrayType($arr)
 * 4401:     function getItemBlobSize($len)
 * 4412:     function suggestTCAFieldDefinition($fieldName,$fieldInfo)
 * 4555:     function includeTCA()
 *
 *              SECTION: GENERAL FUNCTIONS
 * 4597:     function linkIt($url,$link='')
 * 4611:     function message($head, $short_string='', $long_string='', $type=0, $force=0)
 * 4632:     function printSection($head, $short_string, $long_string, $type)
 * 4673:     function fw($str,$size=1)
 * 4696:     function fwheader($str)
 * 4707:     function wrapInCells($label,$content)
 * 4716:     function printAll()
 * 4735:     function outputWrapper($content)
 * 4801:     function menu()
 * 4823:     function stepHeader()
 * 4865:     function note123()
 * 4879:     function endNotes()
 * 4898:     function convertByteSize($bytes)
 * 4912:     function securityRisk()
 * 4930:     function alterPasswordForm()
 * 4946:     function messageBasicFinished()
 * 4968:     function setScriptName($type)
 * 4981:     function formWidth($size=48,$textarea=0,$styleOverride='')
 * 5002:     function formWidthText($size=48,$styleOverride='',$wrap='')
 * 5018:     function getBackupFilename($filename)
 *
 * TOTAL FUNCTIONS: 82
 * (This index is automatically created/updated by the extension "extdeveval")
 *
 */

// include requirements definition:
require_once(t3lib_extMgm::extPath('install') . 'requirements.php');

// include update classes
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_compatversion.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_cscsplit.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_notinmenu.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_mergeadvanced.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_installsysexts.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_imagescols.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_installversioning.php');
require_once(t3lib_extMgm::extPath('install').'updates/class.tx_coreupdates_installnewsysexts.php');
require_once(t3lib_extMgm::extPath('install') . 'mod/class.tx_install_session.php');

/**
 * Install Tool module
 *
 * @author	Kasper Skaarhoj <kasperYYYY@typo3.com>
 * @author	Ingmar Schlecht <ingmar@typo3.org>
 * @package TYPO3
 * @subpackage tx_install
 */
class tx_install extends t3lib_install {
	var $getGD_start_string='<h2 align="center"><a name="module_gd">gd</a></h2>';	// Used to identify the GD section in the output from phpinfo()
	var $getGD_end_string = '</table>';	// Used to identify the end of the GD section (found with getGD_start_string) in the output from phpinfo()
	var $getTTF_string = 'with TTF library';	// Used to identify whether TTF-lib is included with GD
	var $getTTF_string_alt = 'with freetype';       // Used to identify whether TTF-lib is included with GD
	var $action = '';		// The url that calls this script
	var $scriptSelf = 'index.php';		// The url that calls this script
	var $fontTag2='<div class="bodytext">';
	var $fontTag1='<div class="smalltext">';
	var $updateIdentity = 'TYPO3 Install Tool';
	var $headerStyle ='';
	var $contentBeforeTable='';
	var $setAllCheckBoxesByDefault=0;

	var $allowFileEditOutsite_typo3conf_dir=0;

	var $INSTALL =array();		// In constructor: is set to global GET/POST var TYPO3_INSTALL
	var $checkIMlzw = 0;		// If set, lzw capabilities of the available ImageMagick installs are check by actually writing a gif-file and comparing size
	var $checkIM = 0;			// If set, ImageMagick is checked.
	var $dumpImCommands=1;			// If set, the image Magick commands are always outputted in the image processing checker
	var $mode = '';	// If set to "123" then only most vital information is displayed.
	var $step = 0;	// If set to 1,2,3 or GO it signifies various functions.

	// internal
	var $passwordOK=0;			// This is set, if the password check was ok. The function init() will exit if this is not set
	var $silent=1;				// If set, the check routines don't add to the message-array
	var $messageFunc_nl2br=1;
	var $sections=array();		// Used to gather the message information.
	var $fatalError=0;			// This is set if some error occured that will definitely prevent TYpo3 from running.
	var $sendNoCacheHeaders=1;
	var $config_array = array(	// Flags are set in this array if the options are available and checked ok.
		'gd'=>0,
		'gd_gif'=>0,
		'gd_png'=>0,
		'gd_jpg'=>0,
		'freetype' => 0,
		'safemode' => 0,
		'dir_typo3temp' => 0,
		'dir_temp' => 0,
		'im_versions' => array(),
		'im' => 0,
		'sql.safe_mode_user' => '',
		'mysqlConnect' => 0,
		'no_database' => 0
	);
	var $typo3temp_path='';
	/**
	 * the session handling object
	 *
	 * @var tx_install_session
	 */
	protected $session = NULL;

	var $menuitems = array(
		'config' => 'Basic Configuration',
		'database' => 'Database Analyser',
		'update' => 'Update Wizard',
		'images' => 'Image Processing',
		'extConfig' => 'All Configuration',
		'typo3temp' => 'typo3temp/',
		'cleanup' => 'Clean up database',
		'phpinfo' => 'phpinfo()',
		'typo3conf_edit' => 'Edit files in typo3conf/',
		'about' => 'About'
	);
	var $JSmessage = '';






	/**
	 * Constructor
	 *
	 * @return	[type]		...
	 */
	function tx_install()	{
		parent::t3lib_install();

		if (!$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])	die("Install Tool deactivated.<br />You must enable it by setting a password in typo3conf/localconf.php. If you insert the line below, the password will be 'joh316':<br /><br />\$TYPO3_CONF_VARS['BE']['installToolPassword'] = 'bacb98acf97e0b6112b1d1b650b84971';");

		if ($this->sendNoCacheHeaders)	{
			header('Expires: Mon, 26 Jul 1997 05:00:00 GMT');
			header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
			header('Expires: 0');
			header('Cache-Control: no-cache, must-revalidate');
			header('Pragma: no-cache');
		}

			// ****************************
			// Initializing incoming vars.
			// ****************************
		$this->INSTALL = t3lib_div::_GP('TYPO3_INSTALL');
		$this->mode = t3lib_div::_GP('mode');
		if ($this->mode !== '123') {
			$this->mode = '';
		}
		if (t3lib_div::_GP('step') === 'go') {
			$this->step = 'go';
		} else {
			$this->step = intval(t3lib_div::_GP('step'));
		}
		$this->redirect_url = t3lib_div::sanitizeLocalUrl(t3lib_div::_GP('redirect_url'));

		$this->INSTALL['type'] = '';
		if ($_GET['TYPO3_INSTALL']['type']) {
			$allowedTypes = array(
				'config', 'database', 'update', 'images', 'extConfig',
				'typo3temp', 'cleanup', 'phpinfo', 'typo3conf_edit', 'about'
			);

			if (in_array($_GET['TYPO3_INSTALL']['type'], $allowedTypes)) {
				$this->INSTALL['type'] = $_GET['TYPO3_INSTALL']['type'];
			}
		}

		if ($this->step == 3) {
			$this->INSTALL['type'] = 'database';
		}

		if ($this->mode=='123')	{
			$tempItems = $this->menuitems;
			$this->menuitems = array(
				'config' => $tempItems['config'],
				'database' => $tempItems['database']
			);
			if (!$this->INSTALL['type'] || !isset($this->menuitems[$this->INSTALL['type']])) {
				$this->INSTALL['type'] = 'config';
			}
		} else {
			if (!$this->INSTALL['type'] || !isset($this->menuitems[$this->INSTALL['type']])) {
				$this->INSTALL['type'] = 'about';
			}
		}

		$this->action = $this->scriptSelf .
			'?TYPO3_INSTALL[type]=' . $this->INSTALL['type'] .
			($this->mode? '&mode=' . $this->mode : '') .
			($this->step? '&step=' . $this->step : '');
		$this->typo3temp_path = PATH_site.'typo3temp/';
		if (!is_dir($this->typo3temp_path) || !is_writeable($this->typo3temp_path)) {
			die('Install Tool needs to write to typo3temp/. Make sure this directory is writeable by your webserver: '. $this->typo3temp_path);
		}

		try {
			$this->session = t3lib_div::makeInstance('tx_install_session');
		} catch (Exception $exception) {
			$this->outputErrorAndExit($exception->getMessage());
		}

			// *******************
			// Check authorization
			// *******************
		if (!$this->session->hasSession()) {
			$this->session->startSession();

			$this->JSmessage='SECURITY:
Make sure to protect the Install Tool with another password than "joh316".
Better yet you can add a die() function call to typo3/install/index.php after usage.

IF THE INSTALL TOOL CRASHES...
The Install Tool is checking PHPs support for image formats. However certain versions of PHP (fx. 4.3.0 with bundled GD) will crash when trying to read the PNG test file. If this happens you will see a blank screen or error message.
Workaround: Open the file typo3/sysext/install/mod/class.tx_install.php, go to the line where the function "isPNG()" is defined and make it return "0" hardcoded. PNG is not checked anymore and the rest of the Install Tool will work as expected. The same has been known with the other image formats as well. You can use a similar method to bypass the testing if that is also a problem.
On behalf of PHP we regret this inconvenience.

BTW: This Install Tool will only work if cookies are accepted by your web browser. If this dialog pops up over and over again you didn\'t enable cookies.
';
		}

		if ($this->session->isAuthorized() || $this->checkPassword())	{
			$this->passwordOK=1;
			$this->session->refreshSession();

			$enableInstallToolFile = PATH_typo3conf . 'ENABLE_INSTALL_TOOL';
			if (is_file ($enableInstallToolFile)) {
					// Extend the age of the ENABLE_INSTALL_TOOL file by one hour
				@touch($enableInstallToolFile);
			}

			if($this->redirect_url)	{
				header('Location: '.$this->redirect_url);
			}
		} else {
			$this->loginForm();
		}
	}

	/**
	 * Returns true if submitted password is ok.
	 *
	 * If password is ok, set session as "authorized".
	 *
	 * @return boolean true if the submitted password was ok and session was
	 *                 authorized, false otherwise
	 */
	function checkPassword() {
		$p = t3lib_div::_GP('password');

		if ($p && md5($p)==$GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])	{
			$this->session->setAuthorized();

				// Sending warning email
			$wEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
			if ($wEmail)	{
				$subject="Install Tool Login at '".$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']."'";
				$email_body="There has been a Install Tool login at TYPO3 site '".$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']."' (".t3lib_div::getIndpEnv('HTTP_HOST').") from remote address '".t3lib_div::getIndpEnv('REMOTE_ADDR')."' (".t3lib_div::getIndpEnv('REMOTE_HOST').')';
				mail($wEmail,
					$subject,
					$email_body,
					'From: TYPO3 Install Tool WARNING <>'
				);
			}
			return true;
		} else {
				// Bad password, send warning:
			if ($p)	{
				$wEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
				if ($wEmail)	{
					$subject="Install Tool Login ATTEMPT at '".$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']."'";
					$email_body="There has been an Install Tool login attempt at TYPO3 site '".$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']."' (".t3lib_div::getIndpEnv('HTTP_HOST').").
The MD5 hash of the last 5 characters of the password tried was '".substr(md5($p), -5)."'
REMOTE_ADDR was '".t3lib_div::getIndpEnv('REMOTE_ADDR')."' (".t3lib_div::getIndpEnv('REMOTE_HOST').')';
					mail($wEmail,
						$subject,
						$email_body,
						'From: TYPO3 Install Tool WARNING <>'
					);
				}
			}
			return false;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function loginForm()	{
		$p = t3lib_div::_GP('password');
		$redirect_url = $this->redirect_url ? $this->redirect_url : $this->action;

		$this->messageFunc_nl2br=0;
		$this->silent=0;

		$content = '<form action="index.php" method="post" name="passwordForm">
			<input type="password" name="password"><br />
			<input type="hidden" name="redirect_url" value="'.htmlspecialchars($redirect_url).'">
			<input type="submit" value="Log in"><br />
			<br />

			'.$this->fw('The Install Tool Password is <i>not</i> the admin password of TYPO3.<br />
				If you don\'t know the current password, you can set a new one by setting the value of $TYPO3_CONF_VARS[\'BE\'][\'installToolPassword\'] in typo3conf/localconf.php to the md5() hash value of the password you desire.'.
				($p ? '<br /><br />The password you just tried has this md5-value: <br /><br />'.md5($p) : '')
				).'
			</form>
			<script type="text/javascript">
			<!--
				document.passwordForm.password.focus();
			//-->
			</script>';

		if (!$this->session->isAuthorized() && $this->session->isExpired()) {
			$this->message('Password', 'Your install tool session has expired', '', 3);
		}
		$this->message('Password', 'Enter the Install Tool Password', $content, 0);
		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * Calling function that checks system, IM, GD, dirs, database and lets you alter localconf.php
	 * This method is called from init.php to start the install Tool.
	 *
	 * @return	void
	 */
	function init()	{
		if (!defined('PATH_typo3'))	exit;		// Must be called after inclusion of init.php (or from init.php)
		if (!$this->passwordOK)	exit;

			// Setting stuff...
		$this->check_mail();
		$this->setupGeneral();
		$this->generateConfigForm();
		if (count($this->messages))	t3lib_div::debug($this->messages);

		if ($this->step)	{
			$this->output($this->outputWrapper($this->stepOutput()));
		} else {
				// Menu...
			switch($this->INSTALL['type'])	{
				case 'images':
					$this->checkIM=1;
					$this->checkTheConfig();
					$this->silent=0;
					$this->checkTheImageProcessing();
				break;
				case 'database':
					$this->checkTheConfig();
					$this->silent=0;
					$this->checkTheDatabase();
				break;
				case 'update':
					$this->checkDatabase();
					$this->silent=0;
					$this->updateWizard();
				break;
				case 'config':
					$this->silent=0;
					$this->checkIM=1;
					$this->message('About configuration','How to configure TYPO3',$this->generallyAboutConfiguration());
					$this->checkTheConfig();

					$ext = 'Write config to localconf.php';
					if ($this->fatalError)	{
						if ($this->config_array['no_database'] || !$this->config_array['mysqlConnect'])	{
							$this->message($ext, 'Database not configured yet!', '
								You need to specify database username, password and host as one of the first things.
								Next you\'ll have to select a database to use with TYPO3.
								Use the form below:
							',2);
						} else {
							$this->message($ext, 'Fatal error encountered!', '
								Somewhere above a fatal configuration problem is encountered. Please make sure that you\'ve fixed this error before you submit the configuration. TYPO3 will not run if this problem is not fixed!
								You should also check all warnings that may appear.
							',2);
						}
					} elseif ($this->mode=='123') {
						if (!$this->fatalError)	{
							$this->message($ext, 'Basic configuration completed', '
								You have no fatal errors in your basic configuration. You may have warnings though. Please pay attention to them! However you may continue and install the database.

								<strong><span style="color:#f00;">Step 2:</span></strong> <a href="'.$this->scriptSelf.'?TYPO3_INSTALL[type]=database'.($this->mode?'&mode='.rawurlencode($this->mode):'').'">Click here to install the database.</a>
							',-1,1);
						}
					}
					$this->message($ext, 'Very Important: Changing Image Processing settings', "
						When you change the settings for Image Processing you <i>must</i> take into account that <u>old images</u> may still be in typo3temp/ folder and prevent new files from being generated! This is especially important to know, if you're trying to set up image processing for the very first time.
						The problem is solved by <a href=\"".htmlspecialchars($this->setScriptName('typo3temp'))."\">clearing the typo3temp/ folder</a>. Also make sure to clear the cache_pages table.
						",1,1);
					$this->message($ext, 'Very Important: Changing Encryption Key setting', "
						When you change the setting for the Encryption Key you <i>must</i> take into account that a change to this value might invalidate temporary information, URLs etc.
						The problem is solved by <a href=\"".htmlspecialchars($this->setScriptName('typo3temp'))."\">clearing the typo3temp/ folder</a>. Also make sure to clear the cache_pages table.
						",1,1);
					$this->message($ext, 'Update localconf.php', "
						This form updates the localconf.php file with the suggested values you see below. The values are based on the analysis above.
						You can change the values in case you have alternatives to the suggested defaults.
						By this final step you will configure TYPO3 for immediate use provided that you have no fatal errors left above."
						.$this->setupGeneral('get_form'),0,1);

					$this->output($this->outputWrapper($this->printAll()));

				break;
				case 'extConfig':
					$this->silent=0;

					$this->generateConfigForm('get_form');

					$content = $this->printAll();
					$content = '<form action="'.$this->action.'" method="post">'.$content.'<input type="submit" value="Write to localconf.php"><br /><br />
					'.$this->fw('<strong>NOTICE: </strong>By clicking this button, localconf.php is updated with new values for the parameters listed above!<br />').'
					</form>';
					$this->output($this->outputWrapper($content));
				break;
				case 'typo3temp':
					$this->checkTheConfig();
					$this->silent=0;
					$this->typo3TempManager();
				break;
				case 'cleanup':
					$this->checkTheConfig();
					$this->silent=0;
					$this->cleanupManager();
				break;
				case 'phpinfo':
					$this->silent=0;
					$this->phpinformation();
				break;
				case 'typo3conf_edit':
					$this->silent=0;
					$this->typo3conf_edit();
				break;
				case 'about':
				default:
					$this->silent=0;
					$this->message('About', 'Warning - very important!', $this->securityRisk().$this->alterPasswordForm(),2);

					$this->message('About', 'Using this script', "
					Installing TYPO3 has always been a hot topic on the mailing list and forums. Therefore we've developed this tool which will help you through configuration and testing.
					There are three primary steps for you to take:

					<strong>1: Basic Configuration</strong>
					In this step your PHP-configuration is checked. If there are any settings that will prevent TYPO3 from running correctly you'll get warnings and errors with a description of the problem.
					You'll have to enter a database username, password and hostname. Then you can choose to create a new database or select an existing one.
					Finally the image processing settings are entered and verified and you can choose to let the script update the configuration file, typo3conf/localconf.php with the suggested settings.

					<strong>2: Database Analyser</strong>
					In this step you can either install a new database or update the database from any previous TYPO3 version.
					You can also get an overview of extra/missing fields/tables in the database compared to a raw sql-file.
					The database is also verified agains your 'tables.php' configuration (\$TCA) and you can even see suggestions to entries in \$TCA or new fields in the database.

					<strong>3: Update Wizard</strong>
					Here you will find update methods taking care of changes to the TYPO3 core which are not backwards compatible.
					It is recommended to run this wizard after every update to make sure everything will still work flawlessly.

					<strong>4: Image Processing</strong>
					This step is a visual guide to verify your configuration of the image processing software.
					You'll be presented to a list of images that should all match in pairs. If some irregularity appears, you'll get a warning. Thus you're able to track an error before you'll discover it on your website.

					<strong>5: All Configuration</strong>
					This gives you access to any of the configuration options in the TYPO3_CONF_VARS array. Every option is also presented with a comment explaining what it does.

					<strong>6: typo3temp/</strong>
					Here you can manage the files in typo3temp/ folder in a simple manner. typo3temp/ contains temporary files, which may still be used by the website, but some may not. By searching for files with old access-dates, you can possibly manage to delete unused files rather than files still used. However if you delete a temporary file still in use, it's just regenerated as long as you make sure to clear the cache tables afterwards.
					");

					$this->message('About', 'Why is this script stand-alone?', "
					You would think that this script should rather be a module in the backend and access-controlled to only admin-users from the database. But that's not how it works.
					The reason is, that this script must not be depending on the success of the configuration of TYPO3 and whether or not there is a working database behind. Therefore the script is invoked from the backend init.php file, which allows access if the constant 'TYPO3_enterInstallScript' has been defined and is not false. That is and should be the case <i>only</i> when calling the script 'typo3/install/index.php' - this script!
					");


					$headCode='Header legend';
					$this->message($headCode, 'Notice!', '
					Indicates that something is important to be aware of.
					This does <em>not</em> indicate an error.
					',1);
					$this->message($headCode, 'Just information', '
					This is a simple message with some information about something.
					');
					$this->message($headCode, 'Check was successful', '
					Indicates that something was checked and returned an expected result.
					',-1);
					$this->message($headCode, 'Warning!', '
					Indicates that something may very well cause trouble and you should definitely look into it before proceeding.
					This indicates a <em>potential</em> error.
					',2);
					$this->message($headCode, 'Error!', '
					Indicates that something is definitely wrong and that TYPO3 will most likely not perform as expected if this problem is not solved.
					This indicates an actual error.
					',3);

					$this->output($this->outputWrapper($this->printAll()));
				break;
			}
		}
	}

	/**
	 * Controls the step 1-2-3-go process
	 *
	 * @return	string	the content to output to the screen
	 */
	function stepOutput()	{
		$this->checkTheConfig();
		$error_missingConnect='<br />
			'.$this->fontTag2.'<img src="'.$this->backPath.'gfx/icon_fatalerror.gif" width="18" height="16" class="absmiddle">
			There is no connection to the database!<br />
			(Username: <i>' . htmlspecialchars(TYPO3_db_username) . '</i>, Host: <i>' . htmlspecialchars(TYPO3_db_host) . '</i>, Using Password: YES) . <br />
			<br />
			<strong>Go to Step 1</strong> and enter a proper username/password!</span>
			<br />
			<br />
		';
		$error_missingDB='<br />
			'.$this->fontTag2.'<img src="'.$this->backPath.'gfx/icon_fatalerror.gif" width="18" height="16" class="absmiddle">
			There is no access to the database (<i>' . htmlspecialchars(TYPO3_db) . '</i>)!<br />
			<br />
			<strong>Go to Step 2</strong> and select an accessible database!</span>
			<br />
			<br />
		';

			// only get the number of tables if it is not the first step in the 123-installer
			// (= no DB connection yet)
		$whichTables = ($this->step != 1 ? $this->getListOfTables() : array());
		$dbInfo='
					<table border="0" cellpadding="1" cellspacing="0">
					   	<tr>
					   		<td valign="top" nowrap="nowrap" colspan="2" align="center">'.$this->fontTag2.'<strong><img src="'.$this->backPath.'gfx/icon_note.gif" hspace="5" width="18" height="16" class="absmiddle">Database summary:</strong></span></td>
						</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap">'.$this->fontTag1.'Username:</span></td>
					   		<td valign="top" nowrap="nowrap"><strong>'.$this->fontTag1.'' . htmlspecialchars(TYPO3_db_username) . '</span></strong></td>
						</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap">'.$this->fontTag1.'Host:</span></td>
					   		<td valign="top" nowrap="nowrap"><strong>'.$this->fontTag1.'' . htmlspecialchars(TYPO3_db_host) . '</span></strong></td>
						</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap">'.$this->fontTag1.'Database:</span></td>
					   		<td valign="top" nowrap="nowrap"><strong>'.$this->fontTag1.'' . htmlspecialchars(TYPO3_db) . '</span></strong></td>
						</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap">'.$this->fontTag1.'# of tables:</span></td>
					   		<td valign="top" nowrap="nowrap"><strong>'.$this->fontTag1.''.(count($whichTables)?'<span style="color:#f00;">'.count($whichTables).'</span>':count($whichTables)).'</span></strong></td>
						</tr>
					</table>
		';
		$error_emptyDB='<br />
			'.$this->fontTag2.'<img src="'.$this->backPath.'gfx/icon_fatalerror.gif" width="18" height="16" class="absmiddle">
			The database is still empty. There are no tables!<br />
			<br />
			<strong>Go to Step 3</strong> and import a database!</span>
			<br />
			<br />
		';

		switch(strtolower($this->step))	{
			case 1:
				$msg='
					<br />
					<br />
					<table border="0">
					   <form action="'.$this->action.'" method="post">
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><strong>
								  '.$this->fontTag2.'Username:</span></strong>
					   		</td>
					   		<td>	&nbsp;
					   		</td>
					   		<td valign="top">
								  '.$this->fontTag2.'
								  <input type="text" name="TYPO3_INSTALL[localconf.php][typo_db_username]" value="' . htmlspecialchars(TYPO3_db_username) . '"></span><br />
					   		</td>
					   	</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><strong>
								  '.$this->fontTag2.'Password:</span></strong>
					   		</td>
					   		<td>	&nbsp;
					   		</td>
					   		<td valign="top">
								  '.$this->fontTag2.'
								  <input type="password" name="TYPO3_INSTALL[localconf.php][typo_db_password]" value="' . htmlspecialchars(TYPO3_db_password) . '"></span><br />
					   		</td>
					   	</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><strong>
								  '.$this->fontTag2.'Host:</span></strong>
					   		</td>
					   		<td>	&nbsp;
					   		</td>
					   		<td valign="top">
								  '.$this->fontTag2.'
								  <input type="text" name="TYPO3_INSTALL[localconf.php][typo_db_host]" value="'.(TYPO3_db_host? htmlspecialchars(TYPO3_db_host) :'localhost').'"></span><br />
					   		</td>
					   	</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><strong>
								  '.$this->fontTag1.'</span></strong>
					   		</td>
					   		<td>	&nbsp;
					   		</td>
					   		<td valign="top">
								  '.$this->fontTag1.'<br />
							   <input type="hidden" name="step" value="2">
							   <input type="hidden" name="TYPO3_INSTALL[localconf.php][encryptionKey]" value="' . $this->createEncryptionKey() . '">
								 <input type="hidden" name="TYPO3_INSTALL[localconf.php][compat_version]" value="'.TYPO3_branch.'">
								  <input type="submit" value="Continue"><br /><br /><strong>NOTICE: </strong>By clicking this button, typo3conf/localconf.php is updated with new values for the parameters listed above!</span><br />
					   		</td>
					   	</tr>
					   </form>
					</table>
					<br />
					<br />';
			break;
			case 2:
				if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
					$dbArr = $this->getDatabaseList();
					$options = '<option value="">[ SELECT DATABASE ]</option>';
					$dbIncluded = 0;
					foreach ($dbArr as $dbname) {
						$options.='<option value="'.htmlspecialchars($dbname).'"'.($dbname==TYPO3_db?' selected="selected"':'').'>'.htmlspecialchars($dbname).'</option>';
						if ($dbname==TYPO3_db)	$dbIncluded=1;
					}
					if (!$dbIncluded && TYPO3_db)	{
						$options.='<option value="'.htmlspecialchars(TYPO3_db).'" selected="selected">'.htmlspecialchars(TYPO3_db).' (NO ACCESS!)</option>';
					}
					$select='<select name="TYPO3_INSTALL[localconf.php][typo_db]">'.$options.'</select>';
					$msg='
<br />
<br />
					<table border="0">
					   <form action="'.$this->action.'" method="post">
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><strong>
								  '.$this->fontTag2.'
							   You have two options:<br />
							   <br /><br />

							   1: Select an existing <u>EMPTY</u> database:</span></strong>
					   		</td>
						</tr>
						<tr>
					   		<td valign="top">
								  '.$this->fontTag1.'Any existing tables which are used by TYPO3 will be overwritten in Step 3. So make sure this database is empty:<br />'.$select.'</span><br />
					   		</td>
					   	</tr>
					   	<tr>
					   		<td valign="top" nowrap="nowrap"><br />
							<br />
<strong>
								  '.$this->fontTag2.'2: Create new database (recommended):</span></strong>
					   		</td>
						</tr>
						<tr>
					   		<td valign="top">
								  '.$this->fontTag1.'Enter the desired name of the database here:<br /><input type="text" name="TYPO3_INSTALL[localconf.php][NEW_DATABASE_NAME]" value=""></span><br />
					   		</td>
					   	</tr>
					   	<tr>
					   		<td valign="top">   		   <br />

								  '.$this->fontTag1.'<br />
							   <input type="hidden" name="step" value="3">
								  <input type="submit" value="Continue"><br /><br /><strong>NOTICE: </strong>By clicking this button, typo3conf/localconf.php is updated with new values for the parameters listed above!</span><br />
					   		</td>
					   	</tr>
					   </form>
					</table>
<br />
<br />
				';
				} else {
					$msg=$error_missingConnect;
				}
			break;
			case 3:
				if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
					if ($GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
						$sFiles = t3lib_div::getFilesInDir(PATH_typo3conf,'sql',1,1);

							// Check if default database scheme "database.sql" already exists, otherwise create it
						if (!strstr(implode(',',$sFiles).',', '/database.sql,'))	{
							array_unshift($sFiles,'Create default database tables');
						}

						$opt='';
						foreach ($sFiles as $f)	{
							if ($f=='Create default database tables')	$key='CURRENT_TABLES+STATIC';
							else $key=htmlspecialchars($f);

							$opt.='<option value="import|'.$key.'">'.htmlspecialchars(basename($f)).'</option>';
						}


						$content='
							'.$this->fontTag2.'Please select a database dump:</span><br />
							<input type="hidden" name="TYPO3_INSTALL[database_import_all]" value=1>
							<input type="hidden" name="step" value="">
							<input type="hidden" name="goto_step" value="go">
							<select name="TYPO3_INSTALL[database_type]">'.$opt.'</select><br />';

						$content = $this->getUpdateDbFormWrap('import', $content, 'Import database');

						$msg='
						<br />
						'.$dbInfo.'<br />
						<br />
						'.$content.'

						';

					} else {
						$msg=$error_missingDB;
					}
				} else {
					$msg=$error_missingConnect;
				}
			break;
			case 'go':
				if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
					if ($GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))	{
						if (count($whichTables))	{
							$msg='
							<br />
							'.$this->fontTag2.'
							'.nl2br($this->messageBasicFinished()).'
							<br />
							<hr />
							<div align="center"><strong><img src="'.$this->backPath.'gfx/icon_warning.gif" hspace="5" width="18" height="16" class="absmiddle">IMPORTANT</strong></div><br />
							<span class="smalltext">'.nl2br($this->securityRisk()).'
							<br />
							<strong>Enter <a href="'.$this->scriptSelf.'">"Normal" mode for the Install Tool</a> to change this!</strong><br />

							</span>
							</span><br />
							';
						} else {
							$msg=$error_emptyDB;
						}
					} else {
						$msg=$error_missingDB;
					}
				} else {
					$msg=$error_missingConnect;
				}
			break;
		}
		return $msg;
	}

	/**
	 * Calling the functions that checks the system
	 *
	 * @return	[type]		...
	 */
	function checkTheConfig()	{
			// Order important:
		$this->checkDirs();
		$this->checkConfiguration();
		$this->checkExtensions();

		if (TYPO3_OS=='WIN')	{
			$paths=array($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'], $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'], 'c:\\php\\imagemagick\\', 'c:\\php\\GraphicsMagick\\', 'c:\\apache\\ImageMagick\\', 'c:\\apache\\GraphicsMagick\\');
		} else {
			$paths=array($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'], $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'], '/usr/local/bin/','/usr/bin/','/usr/X11R6/bin/');
		}

		asort($paths);
		if (ini_get('safe_mode'))	{
			$paths=array(ini_get('safe_mode_exec_dir'),'/usr/local/php/bin/');
		}
		if ($this->INSTALL['checkIM']['lzw'])	{
			$this->checkIMlzw=1;
		}
		if ($this->INSTALL['checkIM']['path'])	{
			$paths[]=trim($this->INSTALL['checkIM']['path']);
		}
		if ($this->checkIM)	$this->checkImageMagick($paths);
		$this->checkDatabase();
	}

	/**
	 * Editing files in typo3conf directory (or elsewhere if enabled)
	 *
	 * @return	[type]		...
	 */
	function typo3conf_edit()	{
		$EDIT_path = PATH_typo3conf;	// default:
		if ($this->allowFileEditOutsite_typo3conf_dir && $this->INSTALL['FILE']['EDIT_path'])	{
			if (t3lib_div::validPathStr($this->INSTALL['FILE']['EDIT_path']) && substr($this->INSTALL['FILE']['EDIT_path'],-1)=='/')	{
				$tmp_path = PATH_site.$this->INSTALL['FILE']['EDIT_path'];
				if (is_dir($tmp_path))	{
					$EDIT_path=$tmp_path;
				} else {debug("'".$tmp_path."' was not dir");}
			} else {
				debug('BAD DIR_NAME (must be like t3lib/ or media/script/)');
			}
		}

		$headCode = 'Edit files in '.basename($EDIT_path).'/';
		$this->contentBeforeTable='';

		if ($this->INSTALL['SAVE_FILE'])	{
			$save_to_file = $this->INSTALL['FILE']['name'];
			if (@is_file($save_to_file))	{
				$save_to_file_md5 = md5($save_to_file);
				if (isset($this->INSTALL['FILE'][$save_to_file_md5]) && t3lib_div::isFirstPartOfStr($save_to_file,$EDIT_path.'') && substr($save_to_file,-1)!='~' && !strstr($save_to_file,'_bak'))	{
					$this->INSTALL['typo3conf_files'] = $save_to_file;
					$save_fileContent = $this->INSTALL['FILE'][$save_to_file_md5];

					if ($this->INSTALL['FILE']['win_to_unix_br'])	{
						$save_fileContent = str_replace(chr(13).chr(10),chr(10),$save_fileContent);
					}

					$backupFile = $this->getBackupFilename($save_to_file);
					if ($this->INSTALL['FILE']['backup'])	{
						if (@is_file($backupFile))	{ unlink($backupFile); }
						rename($save_to_file,$backupFile);
						$this->contentBeforeTable.='Backup written to <strong>'.$backupFile.'</strong><br />';
					}

					t3lib_div::writeFile($save_to_file,$save_fileContent);
					$this->contentBeforeTable.='
						File saved: <strong>'.$save_to_file.'</strong><br />
						MD5-sum: '.$this->INSTALL['FILE']['prevMD5'].' (prev)<br />
						MD5-sum: '.md5($save_fileContent).' (new)<br />
					';
				}
			}
		}

			// Filelist:
		$typo3conf_files = t3lib_div::getFilesInDir($EDIT_path,'',1,1);
		reset($typo3conf_files);
		$lines=array();
		$fileFound = 0;
		while(list($k,$file)=each($typo3conf_files))	{
				// Delete temp_CACHED files if option is set
			if ( $this->INSTALL['delTempCached'] && preg_match('|/temp_CACHED_[a-z0-9_]+\.php|', $file))	{
				unlink($file);
				continue;
			}
			if ($this->INSTALL['typo3conf_files'] && !strcmp($this->INSTALL['typo3conf_files'],$file))	{
				$wrap=array('<strong><span style="color:navy;">','</span></strong>');
				$fileFound = 1;
			} else {$wrap=array();}
			$lines[]='<tr><td nowrap="nowrap"><a href="'.$this->action.'&TYPO3_INSTALL[typo3conf_files]='.rawurlencode($file).($this->allowFileEditOutsite_typo3conf_dir?'&TYPO3_INSTALL[FILE][EDIT_path]='.rawurlencode($this->INSTALL['FILE']['EDIT_path']):"").'">'.$this->fw($wrap[0].basename($file).$wrap[1].'&nbsp;&nbsp;&nbsp;').'</a></td><td>'.$this->fw(t3lib_div::formatSize(filesize($file))).'</td></tr>';
		}
		$fileList='<table border="0" cellpadding="0" cellspacing="0">'.implode('',$lines).'</table>';
		$fileList.='<br />('.$EDIT_path.')';

		if ($this->allowFileEditOutsite_typo3conf_dir)	{
			$fileList.='<br /><form action="'.$this->action.'" method="post">
			'.PATH_site.'<input type="text" name="TYPO3_INSTALL[FILE][EDIT_path]" value="'.$this->INSTALL['FILE']['EDIT_path'].'"><input type="submit" name="" value="Set">
			</form>';
		}

			// create link for deleting temp_CACHED files
		$fileList .= '<br /><br /><a href="'.$this->action.'&TYPO3_INSTALL[delTempCached]=1">Delete temp_CACHED* files</a>';

		if ($fileFound && @is_file($this->INSTALL['typo3conf_files']))	{

			$backupFile = $this->getBackupFilename($this->INSTALL['typo3conf_files']);
			$fileContent = t3lib_div::getUrl($this->INSTALL['typo3conf_files']);
			$this->contentBeforeTable.= '<div class="editFile"><form action="'.$this->action.'" method="post">'.(substr($this->INSTALL['typo3conf_files'],-1)!='~' && !strstr($this->INSTALL['typo3conf_files'],'_bak') ? '
				<input type="submit" name="TYPO3_INSTALL[SAVE_FILE]" value="Save file">&nbsp;' : '').'
				<input type="submit" name="_close" value="Close">
				<br />File: '.$this->INSTALL['typo3conf_files'].'
				<br />MD5-sum: '.md5($fileContent).'
				<br />

				<input type="hidden" name="TYPO3_INSTALL[FILE][name]" value="'.$this->INSTALL['typo3conf_files'].'">
				'.($this->allowFileEditOutsite_typo3conf_dir?'<input type="hidden" name="TYPO3_INSTALL[FILE][EDIT_path]" value="'.$this->INSTALL['FILE']['EDIT_path'].'">':'').'
				<input type="hidden" name="TYPO3_INSTALL[FILE][prevMD5]" value="'.md5($fileContent).'">
				<textarea rows="30" name="TYPO3_INSTALL[FILE]['.md5($this->INSTALL['typo3conf_files']).']" wrap="off"'.$this->formWidthText(48,'width:98%;height:80%','off').' class="fixed-font enable-tab">'.t3lib_div::formatForTextarea($fileContent).'</textarea><br />
				<input type="checkbox" name="TYPO3_INSTALL[FILE][win_to_unix_br]" id="win_to_unix_br" value="1"'.(TYPO3_OS=='WIN'?'':' checked="checked"').'> <label for="win_to_unix_br">Convert Windows linebreaks (13-10) to Unix (10)</label><br />
				<input type="checkbox" name="TYPO3_INSTALL[FILE][backup]" id="backup" value="1"'.(@is_file($backupFile) ? ' checked="checked"' : '').'> <label for="backup">Make backup copy (rename to '.basename($backupFile).')</label><br />
				'.
			'</form></div>';
		}

		if ($this->contentBeforeTable)	{
			$this->contentBeforeTable = $this->fw($this->contentBeforeTable);
		}

		$this->message($headCode,'Files in folder',$fileList);

		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * Outputs system information
	 *
	 * @return	[type]		...
	 */
	function phpinformation()	{
		$headCode = 'PHP information';

		$sVar = t3lib_div::getIndpEnv('_ARRAY');
		$sVar['CONST: PHP_OS']=PHP_OS;
		$sVar['CONST: TYPO3_OS']=TYPO3_OS;
		$sVar['CONST: PATH_thisScript']=PATH_thisScript;
		$sVar['CONST: php_sapi_name()']=PHP_SAPI;
		$sVar['OTHER: TYPO3_VERSION']=TYPO3_version;
		$sVar['OTHER: PHP_VERSION']=phpversion();
		$sVar['imagecreatefromgif()']=function_exists('imagecreatefromgif');
		$sVar['imagecreatefrompng()']=function_exists('imagecreatefrompng');
		$sVar['imagecreatefromjpeg()']=function_exists('imagecreatefromjpeg');
		$sVar['imagegif()']=function_exists('imagegif');
		$sVar['imagepng()']=function_exists('imagepng');
		$sVar['imagejpeg()']=function_exists('imagejpeg');
		$sVar['imagettftext()']=function_exists('imagettftext');
		$sVar['OTHER: IMAGE_TYPES']=function_exists('imagetypes') ? imagetypes() : 0;
		$sVar['OTHER: memory_limit']=ini_get('memory_limit');

		$gE_keys = explode(',','SERVER_PORT,SERVER_SOFTWARE,GATEWAY_INTERFACE,SCRIPT_NAME,PATH_TRANSLATED');
		while(list(,$k)=each($gE_keys))	{
			$sVar['SERVER: '.$k]=$_SERVER[$k];
		}

		$gE_keys = explode(',','image_processing,gdlib,gdlib_png,gdlib_2,im,im_path,im_path_lzw,im_version_5,im_negate_mask,im_imvMaskState,im_combine_filename');
		while(list(,$k)=each($gE_keys))	{
			$sVar['T3CV_GFX: '.$k]=$GLOBALS['TYPO3_CONF_VARS']['GFX'][$k];
		}

		$debugInfo=array();
		$debugInfo[]='### DEBUG SYSTEM INFORMATION - START ###';
		reset($sVar);
		while(list($kkk,$vvv)=each($sVar))	{
			$debugInfo[]=str_pad(substr($kkk,0,20),20).': '.$vvv;
		}
		$debugInfo[]='### DEBUG SYSTEM INFORMATION - END ###';

		$buf=$this->messageFunc_nl2br;
		$this->messageFunc_nl2br=0;
		$this->message($headCode,'DEBUG information','Please copy/paste the information from this text field into an email or bug-report as "Debug System Information" whenever you wish to get support or report problems. This information helps others to check if your system has some obvious misconfiguration and you\'ll get your help faster!<br />
		<form action=""><textarea rows="10" '.$this->formWidthText(80,'width:100%; height:500px;','off').' wrap="off" class="fixed-font">'.t3lib_div::formatForTextarea(implode(chr(10),$debugInfo)).'</textarea></form>');
		$this->messageFunc_nl2br=$buf;

		$getEnvArray = array();
		$gE_keys = explode(',','QUERY_STRING,HTTP_ACCEPT,HTTP_ACCEPT_ENCODING,HTTP_ACCEPT_LANGUAGE,HTTP_CONNECTION,HTTP_COOKIE,HTTP_HOST,HTTP_USER_AGENT,REMOTE_ADDR,REMOTE_HOST,REMOTE_PORT,SERVER_ADDR,SERVER_ADMIN,SERVER_NAME,SERVER_PORT,SERVER_SIGNATURE,SERVER_SOFTWARE,GATEWAY_INTERFACE,SERVER_PROTOCOL,REQUEST_METHOD,SCRIPT_NAME,PATH_TRANSLATED,HTTP_REFERER,PATH_INFO');
		while(list(,$k)=each($gE_keys))	{
			$getEnvArray[$k] = getenv($k);
		}
		$this->message($headCode,'t3lib_div::getIndpEnv()',t3lib_div::view_array(t3lib_div::getIndpEnv('_ARRAY')));
		$this->message($headCode,'getenv()',t3lib_div::view_array($getEnvArray));
		$this->message($headCode,'_ENV',t3lib_div::view_array($_ENV));
		$this->message($headCode,'_SERVER',t3lib_div::view_array($_SERVER));
		$this->message($headCode,'_COOKIE',t3lib_div::view_array($_COOKIE));
		$this->message($headCode,'_GET',t3lib_div::view_array($_GET));

		ob_start();
		phpinfo();
		$contents = explode('<body>',ob_get_contents());
		ob_end_clean();
		$contents = explode('</body>',$contents[1]);

		$this->message($headCode,'phpinfo()','<div class="phpinfo">' . $contents[0] . '</div>');

		$this->output($this->outputWrapper($this->printAll()));
	}













	/*******************************
	 *
	 * typo3temp/ manager
	 *
	 *******************************/

	/**
	 * Provides a tool for deleting temporary files located in typo3temp/
	 *
	 * @return	string		HTML output
	 */
	function typo3TempManager()	{
		$headCode = 'typo3temp/ directory';
		$this->message($headCode,'What is it?','
		TYPO3 uses this directory for temporary files, mainly processed and cached images.
		The filenames are very cryptic; They are unique representations of the file properties made by md5-hashing a serialized array with information.
		Anyway this directory may contain many thousand files and a lot of them may be of no use anymore.

		With this test you can delete the files in this folder. When you do that, you should also clear the cache database tables afterwards.
		');

		if (!$this->config_array['dir_typo3temp'])	{
			$this->message('typo3temp/ directory','typo3temp/ not writable!',"
				You must make typo3temp/ write enabled before you can proceed with this test.
			",2);
			$this->output($this->outputWrapper($this->printAll()));
			return;
		}

			// Run through files
		$fileCounter = 0;
		$deleteCounter = 0;
		$criteriaMatch = 0;
		$tmap=array('day'=>1, 'week'=>7, 'month'=>30);
		$tt = $this->INSTALL['typo3temp_delete'];
		$subdir = $this->INSTALL['typo3temp_subdir'];
		if (strlen($subdir) && !preg_match('/^[[:alnum:]_]+\/$/',$subdir))	die('subdir "'.$subdir.'" was not allowed!');
		$action = $this->INSTALL['typo3temp_action'];
		$d = @dir($this->typo3temp_path.$subdir);
		if (is_object($d))	{
			while($entry=$d->read()) {
				$theFile = $this->typo3temp_path.$subdir.$entry;
				if (@is_file($theFile))	{
					$ok = 0;
					$fileCounter++;
					if ($tt)	{
						if (t3lib_div::testInt($tt))	{
							if (filesize($theFile) > $tt*1024)	$ok=1;
						} else {
							if (fileatime($theFile) < $GLOBALS['EXEC_TIME'] - (intval($tmap[$tt]) * 60 * 60 * 24)) {
								$ok = 1;
							}
						}
					} else {
						$ok = 1;
					}
					if ($ok)	{
						$hashPart=substr(basename($theFile),-14,10);
						if (!preg_match('/[^a-f0-9]/',$hashPart) || substr($theFile,-6)==='.cache' || substr($theFile,-4)==='.tbl' || substr(basename($theFile),0,8)==='install_')	{		// This is a kind of check that the file being deleted has a 10 char hash in it
							if ($action && $deleteCounter<$action)	{
								$deleteCounter++;
								unlink($theFile);
							} else {
								$criteriaMatch++;
							}
						}
					}
				}
			}
			$d->close();
		}

			// Find sub-dirs:
		$subdirRegistry = array(''=>'');
		$d = @dir($this->typo3temp_path);
		if (is_object($d))	{
			while($entry=$d->read()) {
				$theFile = $entry;
				if (@is_dir($this->typo3temp_path.$theFile) && $theFile!='..' && $theFile!='.')	{
					$subdirRegistry[$theFile.'/'] = $theFile.'/ (Files: '.count(t3lib_div::getFilesInDir($this->typo3temp_path.$theFile)).')';
				}
			}
		}

		$deleteType=array(
			'0' => 'All',
			'day' => 'Last access more than a day ago',
			'week' => 'Last access more than a week ago',
			'month' => 'Last access more than a month ago',
			'10' => 'Filesize greater than 10KB',
			'50' => 'Filesize greater than 50KB',
			'100' => 'Filesize greater than 100KB'
		);

		$actionType=array(
			'0' => "Don't delete, just display statistics",
			'100' => 'Delete 100',
			'500' => 'Delete 500',
			'1000' => 'Delete 1000'
		);

		$content='<select name="TYPO3_INSTALL[typo3temp_delete]">'.$this->getSelectorOptions($deleteType,$tt).'</select>
		<br />
Number of files at a time:
		<select name="TYPO3_INSTALL[typo3temp_action]">'.$this->getSelectorOptions($actionType).'</select>

From sub-directory:
		<select name="TYPO3_INSTALL[typo3temp_subdir]">'.$this->getSelectorOptions($subdirRegistry, $this->INSTALL['typo3temp_subdir']).'</select>
		';

		$form = '<form action="'.$this->action.'" method="post">'.$content.'

		<input type="submit" value="Execute">
		</form>
		This tool will delete files only if the last 10 characters before the extension (3 chars+\'.\') are hexadecimal valid ciphers, which are lowercase a-f and 0-9.';

		$this->message($headCode,'Statistics','
		Number of temporary files: <strong>'.($fileCounter-$deleteCounter)."</strong>
		Number matching '".htmlspecialchars($deleteType[$tt])."': <strong>".$criteriaMatch.'</strong>
		Number deleted: <strong>'.$deleteCounter.'</strong>
		<br />
		'.$form,1);

		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$deleteType: ...
	 * @param	[type]		$tt: ...
	 * @return	[type]		...
	 */
	function getSelectorOptions($deleteType,$tt='')	{
		$out='';
		if (is_array($deleteType))	{
			reset($deleteType);
			while(list($v,$l)=each($deleteType))	{
				$out.='<option value="'.htmlspecialchars($v).'"'.(!strcmp($v,$tt)?' selected="selected"':'').'>'.htmlspecialchars($l).'</option>';
			}
		}
		return $out;
	}









	/*******************************
	 *
	 * cleanup manager
	 *
	 *******************************/

	/**
	 * Provides a tool cleaning up various tables in the database
	 *
	 * @return	string		HTML output
	 * @author	Robert Lemke <rl@robertlemke.de>
	 * @todo	Add more functionality ...
	 */
	function cleanupManager()	{
		$headCode = 'Clean up database';
		$this->message($headCode,'What is it?','
		This function will become a general clean up manager for various tables used by TYPO3. By now you can only empty the cache which is used for storing image sizes of all pictures used in TYPO3.

		<strong>Clear cached image sizes</strong>
		Clears the cache used for memorizing sizes of all images used in your website. This information is cached in order to gain performance and will be stored each time a new image is being displayed in the frontend.

		You should <em>Clear All Cache</em> in the backend after clearing this cache.
		');

		$tables = $this->getListOfTables();
		$action = $this->INSTALL['cleanup_type'];

		if (($action == 'cache_imagesizes' || $action == 'all') && isset ($tables['cache_imagesizes'])) {
			$GLOBALS['TYPO3_DB']->exec_DELETEquery ('cache_imagesizes','');
		}

		$cleanupType = array (
			'all' => 'Clean up everything',
		);

			// Get cache_imagesizes info
		if (isset ($tables['cache_imagesizes'])) {
			$cleanupType['cache_imagesizes'] = 'Clear cached image sizes only';
			$cachedImageSizesCounter = intval($GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', 'cache_imagesizes'));
		} else {
			$this->message($headCode,'Table cache_imagesizes does not exist!',"
				The table cache_imagesizes was not found. Please check your database settings in Basic Configuration and compare your table definition with the Database Analyzer.
			",2);
			$cachedImageSizesCounter = 'unknown';
		}

		$content = '<select name="TYPO3_INSTALL[cleanup_type]">'.$this->getSelectorOptions($cleanupType).'</select> ';
		$form = '<form action="'.$this->action.'" method="post">'.$content.'<input type="submit" value="Execute"></form>';
		$this->message($headCode,'Statistics','
			Number cached image sizes: <strong>'.$cachedImageSizesCounter.'</strong><br />
		'.$form,1);

		$this->output($this->outputWrapper($this->printAll()));
	}













	/*******************************
	 *
	 * CONFIGURATION FORM
	 *
	 ********************************/

	/**
	 * Creating the form for editing the TYPO3_CONF_VARS options.
	 *
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function generateConfigForm($type='')	{
		$default_config_content = t3lib_div::getUrl(PATH_t3lib.'config_default.php');
		$commentArr = $this->getDefaultConfigArrayComments($default_config_content);

		switch($type)	{
			case 'get_form':
				reset($GLOBALS['TYPO3_CONF_VARS']);
				$this->messageFunc_nl2br=0;
				while(list($k,$va)=each($GLOBALS['TYPO3_CONF_VARS']))	{
					$ext='['.$k.']';
					$this->message($ext, '$TYPO3_CONF_VARS[\''.$k.'\']',$commentArr[0][$k],1);

					while(list($vk,$value)=each($va))	{
						$description = trim($commentArr[1][$k][$vk]);
						$isTextarea = preg_match('/^string \(textarea\)/i',$description) ? TRUE : FALSE;
						$doNotRender = preg_match('/^string \(exclude\)/i', $description) ? TRUE : FALSE;

						if (!is_array($value) && !$doNotRender && ($this->checkForBadString($value) || $isTextarea)) {
							$k2 = '['.$vk.']';
							$msg = htmlspecialchars($description).'<br /><br /><em>'.$ext.$k2.' = '.htmlspecialchars(t3lib_div::fixed_lgd_cs($value,60)).'</em><br />';

							if ($isTextarea)	{
								$form = '<textarea name="TYPO3_INSTALL[extConfig]['.$k.']['.$vk.']" cols="60" rows="5" wrap="off">'.htmlspecialchars($value).'</textarea>';
							} elseif (preg_match('/^boolean/i',$description)) {
								$form = '<input type="hidden" name="TYPO3_INSTALL[extConfig]['.$k.']['.$vk.']" value="0">';
								$form.= '<input type="checkbox" name="TYPO3_INSTALL[extConfig]['.$k.']['.$vk.']"'.($value?' checked="checked"':'').' value="'.($value&&strcmp($value,'0')?htmlspecialchars($value):1).'">';
							} else {
								$form = '<input type="text" size="80" name="TYPO3_INSTALL[extConfig][' . $k . '][' . $vk . ']" value="' . htmlspecialchars($value) . '">';
							}
							$this->message($ext, $k2,$msg.$form);
						}
					}
				}
			break;
			default:
				if (is_array($this->INSTALL['extConfig']))		{
					reset($this->INSTALL['extConfig']);
					$lines = $this->writeToLocalconf_control();
					while(list($k,$va)=each($this->INSTALL['extConfig']))	{
						if (is_array($GLOBALS['TYPO3_CONF_VARS'][$k]))	{
							while(list($vk,$value)=each($va))	{
								if (isset($GLOBALS['TYPO3_CONF_VARS'][$k][$vk]))	{
									$doit=1;
									if ($k=='BE' && $vk=='installToolPassword')	{
										if ($value)	{
											if (isset($_POST['installToolPassword_check']) && (!t3lib_div::_GP('installToolPassword_check') || strcmp(t3lib_div::_GP('installToolPassword_check'),$value)))	{
												$doit=0;
												t3lib_div::debug('ERROR: The two passwords did not match! The password was not changed.');
											}
											if (t3lib_div::_GP('installToolPassword_md5'))	$value =md5($value);
										} else $doit=0;
									}

									$description = trim($commentArr[1][$k][$vk]);
									if (preg_match('/^string \(textarea\)/i', $description))	{
										$value = str_replace(chr(13),'',$value);	// Force Unix linebreaks in textareas
										$value = str_replace(chr(10),"'.chr(10).'",$value);	// Preserve linebreaks
									}
									if (preg_match('/^boolean/i', $description)) {
											// When submitting settings in the Install Tool, values that default to "false" or "true" in config_default.php will be sent as "0" resp. "1". Therefore, reset the values to their boolean equivalent.
										if ($GLOBALS['TYPO3_CONF_VARS'][$k][$vk] === false && $value === '0') {
											$value = false;
										} elseif ($GLOBALS['TYPO3_CONF_VARS'][$k][$vk] === true && $value === '1') {
											$value = true;
										}
									}

									if ($doit && strcmp($GLOBALS['TYPO3_CONF_VARS'][$k][$vk],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\''.$k.'\'][\''.$vk.'\']', $value);
								}
							}
						}
					}
					$this->writeToLocalconf_control($lines);
				}
			break;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$string: ...
	 * @param	[type]		$mainArray: ...
	 * @param	[type]		$commentArray: ...
	 * @return	[type]		...
	 */
	function getDefaultConfigArrayComments($string,$mainArray=array(),$commentArray=array())	{
		$lines = explode(chr(10),$string);
		$in=0;
		$mainKey='';
		while(list(,$lc)=each($lines))	{
			$lc = trim($lc);
			if ($in) {
				if (!strcmp($lc,');'))	{
					$in=0;
				} else {
					if (preg_match('/["\']([[:alnum:]_-]*)["\'][[:space:]]*=>(.*)/i',$lc,$reg))	{
						preg_match('/,[\t\s]*\/\/(.*)/i',$reg[2],$creg);
						$theComment = trim($creg[1]);
						if (substr(strtolower(trim($reg[2])),0,5)=='array' && !strcmp($reg[1],strtoupper($reg[1])))	{
							$mainKey=trim($reg[1]);
							$mainArray[$mainKey]=$theComment;
						} elseif ($mainKey) {
							$commentArray[$mainKey][$reg[1]]=$theComment;
						}
					}
				}
			}
			if (!strcmp($lc, '$TYPO3_CONF_VARS = array(')) {
				$in=1;
			}
		}
		return array($mainArray,$commentArray);
	}













	/*******************************
	 *
	 * CHECK CONFIGURATION FUNCTIONS
	 *
	 *******************************/

	/**
	 * Checking php.ini configuration and set appropriate messages and flags.
	 *
	 * @return	[type]		...
	 */
	function checkConfiguration()	{
		$ext='php.ini configuration checked';
		$this->message($ext);

			// *****************
			// Incoming values:
			// *****************

			// Includepath
		$incPaths = t3lib_div::trimExplode(TYPO3_OS=='WIN'?';':':', ini_get('include_path'));
		if (!in_array('.',$incPaths))	{
			$this->message($ext, 'Current directory (./) is not in include path!',"
				<i>include_path=".ini_get('include_path')."</i>
				Normally the current path, '.', is included in the include_path of PHP. Although TYPO3 does not rely on this, it is an unusual setting that may introduce problems for some extensions.
			",1);
		} else $this->message($ext, 'Current directory in include path',"",-1);

			// *****************
			// File uploads
			// *****************
		if (!ini_get('file_uploads'))	{
			$this->message($ext, 'File uploads not allowed',"
				<i>file_uploads=".ini_get('file_uploads')."</i>
				TYPO3 uses the ability to upload files from the browser in various cases.
				As long as this flag is disabled, you'll not be able to upload files.
				But it doesn't end here, because not only are files not accepted by the server - ALL content in the forms are discarded and therefore nothing at all will be editable if you don't set this flag!
				However if you cannot enable fileupload for some reason alternatively you change the default form encoding value with \$TYPO3_CONF_VARS[SYS][form_enctype].
			",3);
		} else $this->message($ext, 'File uploads allowed',"",-1);

		$upload_max_filesize = $this->convertByteSize(ini_get('upload_max_filesize'));
		$post_max_size = $this->convertByteSize(ini_get('post_max_size'));
		if ($upload_max_filesize<1024*1024*10)	{
			$this->message($ext, 'Maximum upload filesize too small?',"
				<i>upload_max_filesize=".ini_get('upload_max_filesize')."</i>
				By default TYPO3 supports uploading, copying and moving files of sizes up to 10MB (You can alter the TYPO3 defaults by the config option TYPO3_CONF_VARS[BE][maxFileSize]).
				Your current value is below this, so at this point, PHP sets the limits for uploaded filesizes and not TYPO3.
				<strong>Notice:</strong> The limits for filesizes attached to database records are set in the tables.php configuration files (\$TCA) for each group/file field. You may override these values in localconf.php or by page TSconfig settings.
			",1);
		}
		if ($upload_max_filesize > $post_max_size)	{
			$this->message($ext, 'Maximum size for POST requests is smaller than max. upload filesize','
				<i>upload_max_filesize='.ini_get('upload_max_filesize').', post_max_size='.ini_get('post_max_size').'</i>
				You have defined a maximum size for file uploads which exceeds the allowed size for POST requests. Therefore the file uploads can not be larger than '.ini_get('post_max_size').'
			',1);
		}

			// *****************
			// Memory and functions
			// *****************
		$memory_limit_value = $this->convertByteSize(ini_get('memory_limit'));

		if ($memory_limit_value && $memory_limit_value < t3lib_div::getBytesFromSizeMeasurement(TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT)) {
			$this->message($ext, 'Memory limit below ' . TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT,'
				<i>memory_limit=' . ini_get('memory_limit') . '</i>
				Your system is configured to enforce a memory limit of PHP scripts lower than ' . TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT . '. The Extension Manager needs to include more PHP-classes than will fit into this memory space. There is nothing else to do than raise the limit. To be safe, ask the system administrator of the webserver to raise the limit to over ' . TYPO3_REQUIREMENTS_MINIMUM_PHP_MEMORY_LIMIT . '.
			',3);
		} elseif(!$memory_limit_value) {
			$this->message($ext, 'Memory limit',"<i>No memory limit in effect.</i>",-1);
		} else $this->message($ext, 'Memory limit',"<i>memory_limit=".ini_get('memory_limit')."</i>",-1);
		if (ini_get('max_execution_time')<30)	{
			$this->message($ext, 'Maximum execution time below 30 seconds',"
				<i>max_execution_time=".ini_get('max_execution_time')."</i>
				May impose problems if too low.
			",1);
		} else {
			$this->message($ext, 'Maximum execution time',"<i>max_execution_time=".ini_get('max_execution_time')."</i>",-1);
		}
		if (ini_get('disable_functions'))	{
			$this->message($ext, 'Functions disabled!',"
				<i>disable_functions=".ini_get('disable_functions')."</i>
				The above list of functions are disabled. If TYPO3 use any of these there might be trouble.
				TYPO3 is designed to use the default set of PHP4.3.0+ functions plus the functions of GDLib.
				Possibly these functions are disabled due to security risks and most likely the list would include a function like <i>exec()</i> which is use by TYPO3 to access ImageMagick.
			",2);
		} else {
			$this->message($ext, 'Functions disabled: none',"",-1);
		}
		// Mail tests
		if (TYPO3_OS == 'WIN') {
			$smtp = ini_get('SMTP');
			$bad_smtp = false;
			if (!t3lib_div::validIP($smtp)) {
				$smtp_addr = @gethostbyname($smtp);
				$bad_smtp = ($smtp_addr == $smtp);
			}
			else {
				$smtp_addr = $smtp;
			}
			if (!$smtp || $bad_smtp || !t3lib_div::testInt(ini_get('smtp_port'))) {
				$this->message($ext, 'Mail configuration is not set correctly','
					Mail configuration is not set
					PHP mail() function requires SMTP and smtp_port to have correct values on Windows.',
					2);
			} else {
				if (($smtp_addr == '127.0.0.1' || $smtp_addr == '::1') && ($_SERVER['SERVER_ADDR'] == '127.0.0.1' || $_SERVER['SERVER_ADDR'] == '::1')) {
					$this->message($ext, 'Mail is configured (potential problem exists!)',
						'<i>SMTP=' . $smtp . '</i> - <b>Note:</b> this server! Are you sure it runs SMTP server?
						<i>smtp_port=' . ini_get('smtp_port') . '</i>
						'.$this->check_mail('get_form'), 1);
				} else {
					$this->message($ext, 'Mail is configured',
						'<i>SMTP=' . $smtp . '</i>
						<i>smtp_port=' . ini_get('smtp_port') . '</i>
						'.$this->check_mail('get_form'), -1);
				}
			}
		} elseif (!ini_get('sendmail_path')) {
			$this->message($ext, 'Sendmail path not defined!','
					This may be critical to TYPO3\'s use of the mail() function. Please be sure that the mail() function in your php-installation works!
					' . $this->check_mail('get_form'),1);
		} else {
			list($prg) = explode(' ', ini_get('sendmail_path'));
			if (!@is_executable($prg)) {
				$this->message($ext, 'Sendmail program not found or not executable?','
					<i>sendmail_path=' . ini_get('sendmail_path') . '</i>
					This may be critical to TYPO3\'s use of the mail() function. Please be sure that the mail() function in your php-installation works!
				'.$this->check_mail('get_form'), 1);
			} else {
				$this->message($ext, 'Sendmail OK','
					<i>sendmail_path=' . ini_get('sendmail_path') . '</i>
				'.$this->check_mail('get_form'),-1);
			}
		}

			// *****************
			// Safe mode related
			// *****************
		if (ini_get('safe_mode'))	{
			$this->message($ext, 'Safe mode turned on',"
				<i>safe_mode=".ini_get('safe_mode')."</i>
				In safe_mode PHP is restricted in several ways. This is a good thing because it adds protection to your (and others) scripts. But it may also introduce problems. In TYPO3 this <em>may be</em> a problem in two areas: File administration and execution of external programs, in particular ImageMagick.
				If you just ignore this warning, you'll most likely find, that TYPO3 seems to work except from the image-generation. The problem in that case is that the external ImageMagick programs are not allowed to be executed from the regular paths like \"/usr/bin/\" or \"/usr/X11R6/bin/\".
				If you use safe_mode with TYPO3, you should disable use of external programs ([BE][disable_exec_function]=1).
				In safe mode you must ensure that all the php-scripts and upload folders are owned by the same user.

					<i>safe_mode_exec_dir=".ini_get('safe_mode_exec_dir')."</i>
					If the ImageMagick utilities are located in this directory, everything is fine. Below on this page, you can see if ImageMagick is found here. If not, ask you ISP to put the three ImageMagick programs, 'convert', 'combine'/'composite' and 'identify' there (eg. with symlinks if Unix server)


					<strong>Example of safe_mode settings:</strong>
					Set this in the php.ini file:

					; Safe Mode
					safe_mode               =       On
					safe_mode_exec_dir      = /usr/bin/

					...and the ImageMagick '/usr/bin/convert' will be executable.
					The last slash is important (..../) and you can only specify one directory.

					<strong>Notice: </strong>
					ImageMagick 4.2.9 is recommended and the binaries are normally installed by RPM in /usr/X11R6/bin or by compiling in /usr/local/bin. Please look in the \"Inside TYPO3\" pdf-document for extensive information about ImageMagick issues.
					Paths to ImageMagick are defined in localconf.php and may be something else than /usr/bin/, but this is default for ImageMagick 5+


			",2);
			if (ini_get('doc_root'))	{
				$this->message($ext, 'doc_root set',"
					<i>doc_root=".ini_get('doc_root')."</i>
					PHP cannot execute scripts outside this directory. If that is a problem is please correct it.
				",1);
			}
			$this->config_array['safemode']=1;
		} else $this->message($ext, 'safe_mode: off',"",-1);
		if (ini_get('sql.safe_mode'))	{
			$this->message($ext, 'sql.safe_mode is enabled',"
				<i>sql.safe_mode=".ini_get('sql.safe_mode').'</i>
				This means that you can only connect to the database with a username corresponding to the user of the webserver process or fileowner. Consult your ISP for information about this. Also see '.$this->linkIt('http://www.wrox.com/Consumer/Store/Books/2963/29632002.htm').'
				The owner of the current file is: <strong>'.get_current_user ()."</strong>
			",1);
			$this->config_array['sql.safe_mode_user'] = get_current_user();
		} else $this->message($ext, 'sql.safe_mode: off',"",-1);
		if (ini_get('open_basedir'))	{
			$this->message($ext, 'open_basedir set',"
				<i>open_basedir=".ini_get('open_basedir')."</i>
				This restricts TYPO3 to open and include files only in this path. Please make sure that this does not prevent TYPO3 from running.
				<strong>Notice (UNIX):</strong> Before checking a path according to open_basedir, PHP resolves all symbolic links.
			",1);
//	????			If this option was set falsely you probably didn't see this page in the first place, but this option <strong>may spoil this configuration test</strong> when checking for such as ImageMagick executables.
		} else $this->message($ext, 'open_basedir: off',"",-1);

			// Check availability of PHP session support
		if (extension_loaded('session')) {
			$this->message($ext, 'PHP sessions availiable','
				<i>PHP Sessions availiabe</i>
				PHP is compiled with session support and session support is available.
			',-1);
		} else {
			$this->message($ext, 'PHP Sessions not availiabe','
				PHP is not compiled with session support, or session support is disabled in php.ini.
				TYPO3 needs session support
			',3);
		}

				// Suhosin/Hardened PHP:
		$suhosinDescription = 'Suhosin limits the number of elements that can be submitted in forms to the server. ' .
			'This will affect for example the "All configuration" section in the Install Tool or Inline Relational ' .
			'Record Editing (IRRE) with many child records.';
		if (extension_loaded('suhosin')) {
			$suhosinSuggestion = 'At least a value of 400 is suggested.';

			$suhosinRequestMaxVars = ini_get('suhosin.request.max_vars');
			$suhosinPostMaxVars = ini_get('suhosin.post.max_vars');
			$suhosinRequestMaxVarsType = ($suhosinRequestMaxVars < 400 ? 2 : -1);
			$suhosinPostMaxVarsType = ($suhosinPostMaxVars < 400 ? 2 : -1);
			$suhosinType = ($suhosinRequestMaxVars < 400 || $suhosinPostMaxVars < 400 ? 2 : -1);

			$this->message($ext, 'Suhosin/Hardened PHP is loaded', $suhosinDescription, $suhosinType);
			$this->message($ext, 'suhosin.request.max_vars: ' . $suhosinRequestMaxVars, $suhosinSuggestion, $suhosinRequestMaxVarsType);
			$this->message($ext, 'suhosin.post.max_vars: ' . $suhosinPostMaxVars, $suhosinSuggestion, $suhosinPostMaxVarsType);
		} else {
			$this->message($ext, 'Suhosin/Hardened PHP is not loaded', $suhosinDescription, 0);
		}

			// Check for stripped PHPdoc comments that are required to evaluate annotations:
		$method = new ReflectionMethod('tx_install', 'check_mail');
		if (strlen($method->getDocComment()) === 0) {
			$description = 'The system extension Extbase evaluates annotations in PHPdoc comments ' .
				'and thus requires eAccelerator not to strip away these parts. However, this is currently ' .
				'the only part in the TYPO3 Core (beside deprecation log and unit tests). If Extbase is not ' .
				'used, recompiling eAccelerator is not required at all.<br/><br/>' .
				'If you do not want comments to be stripped by eAccelerator, please recompile with the following ' .
				'configuration setting (<a href="http://eaccelerator.net/ticket/229" target="_blank">more details</a>):<br />' .
				'<i>--with-eaccelerator-doc-comment-inclusion</i>';
			$this->message($ext, 'PHPdoc comments are stripped', $description, 2);
		}
	}

	/**
	 * Check if PHP function mail() works
	 *
	 * @param	string		$cmd	If "get_form" then a formfield for the mail-address is shown. If not, it's checked if "check_mail" was in the INSTALL array and if so a test mail is sent to the recipient given.
	 * @return	[type]		...
	 */
	function check_mail($cmd='')	{
		switch($cmd)	{
			case 'get_form':
				$out='
				You can check the mail() function by entering your email address here and press the button. You should then receive a testmail from test@test.test.<br /> Since almost all mails in TYPO3 are sent using the t3lib_htmlmail class, sending with this class can be tested by checking the box <strong>Test t3lib_htmlmail</strong> below. The return-path of the mail is set to null@'.t3lib_div::getIndpEnv('HTTP_HOST').'. Some mail servers won\'t send the mail if the host of the return-path is not resolved correctly.
				<form action="'.$this->action.'" method="post"><input type="text" name="TYPO3_INSTALL[check_mail]"><br /><input type="checkbox" name="TYPO3_INSTALL[use_htmlmail]" id="use_htmlmail" ><label for="use_htmlmail">Test t3lib_htmlmail.</label>
					<input type="submit" value="Send test mail"></form>';
			break;
			default:
				if (trim($this->INSTALL['check_mail']))	{
					$subject = 'TEST SUBJECT';
					$email = trim($this->INSTALL['check_mail']);

					if($this->INSTALL['use_htmlmail'])	{
					  	$emailObj = t3lib_div::makeInstance('t3lib_htmlmail');
					  	/* @var $emailObj t3lib_htmlmail */
						$emailObj->start();
						$emailObj->subject = $subject;
						$emailObj->from_email = 'test@test.test';
						$emailObj->from_name = 'TYPO3 Install Tool';
						$emailObj->returnPath = 'null@'.t3lib_div::getIndpEnv('HTTP_HOST');
						$emailObj->addPlain('TEST CONTENT');
						$emailObj->setHTML($emailObj->encodeMsg('<html><body>HTML TEST CONTENT</body></html>'));
						$emailObj->send($email);
					} else {
						t3lib_div::plainMailEncoded($email,$subject,'TEST CONTENT','From: test@test.test');
					}
					$this->messages[]= 'MAIL WAS SENT TO: '.$email;
				}
			break;
		}
		return $out;
	}

	/**
	 * Checking php extensions, specifically GDLib and Freetype
	 *
	 * @return	[type]		...
	 */
	function checkExtensions()	{
		$ext = 'GDLib';
		$this->message($ext);

		$software_info=1;
		if (extension_loaded('gd') && $this->isGD())	{
			$this->config_array['gd']=1;
			$this->message($ext, 'GDLib found',"",-1);
			if ($this->isPNG()) {
				$this->config_array['gd_png']=1;
				$this->message($ext, 'PNG supported',"",-1);
			}
			if ($this->isGIF()) {
				$this->config_array['gd_gif']=1;
				$this->message($ext, 'GIF supported',"",-1);
			}
			if ($this->isJPG()) {
				$this->config_array['gd_jpg']=1;
				$this->message($ext, 'JPG supported (not used by TYPO3)','');
			}
			if (!$this->config_array['gd_gif'] && !$this->config_array['gd_png'])	{
				$this->message($ext, 'PNG or GIF not supported', nl2br(trim('
					Your GDLib supports either GIF nor PNG. It must support either one of them.
				')), 2);
			} else {
				$msg=array();
				if ($this->config_array['gd_gif'] && $this->config_array['gd_png'])		{
					$msg[0]='You can choose between generating GIF or PNG files, as your GDLib supports both.';
				}
				if ($this->config_array['gd_gif'])		{
					$msg[10]="You should watch out for the generated size of the GIF-files because some versions of the GD library do not compress them with LZW, but RLE and ImageMagick is subsequently used to compress with LZW. But in the case of ImageMagick failing this task (eg. not being compiled with LZW which is the case with some versions) you'll end up with GIF-filesizes all too big!
					This install tool tests what kinds of GIF compression are available in the ImageMagick installations by a physical test. You can also check it manually by opening a TYPO3 generated gif-file with Photoshop and save it in a new file. If the file sizes of the original and the new file are almost the same, you're having LZW compression and everything is fine.";
				}
				if ($this->config_array['gd_png'])		{
					$msg[20]='TYPO3 prefers the use of GIF-files and most likely your visitors on your website does too as not all browsers support PNG yet.';
				}
				$this->message($ext, 'GIF / PNG issues', nl2br(trim(implode($msg,chr(10)))), 1);
			}
			if (!$this->isTTF())	{
				$this->message($ext, 'FreeType is apparently not installed', "
					It looks like the FreeType library is not compiled into GDLib. This is required when TYPO3 uses GDLib and you'll most likely get errors like 'ImageTTFBBox is not a function' or 'ImageTTFText is not a function'.
				", 2);
			} else {
				$this->message($ext, 'FreeType quick-test ('.($this->isGIF()?'as GIF':'as PNG').')', '<img src="'.htmlspecialchars(t3lib_div::getIndpEnv('REQUEST_URI').'&testingTrueTypeSupport=1').'" alt=""><br />(If the text is exceeding the image borders you are using Freetype 2 and need to set TYPO3_CONF_VARS[GFX][TTFdpi]=96.<br />If there is no image at all Freetype is most likely NOT available and you can just as well disable GDlib for TYPO3...)', -1);
				$this->config_array['freetype']=1;
			}
		} else {
			$this->message($ext, 'GDLib not found', "
				GDLib is required if you want to use the GIFBUILDER object in TypoScript. GIFBUILDER is in charge of all advanced image generation in TypoScript, including graphical menuitems.
				GDLib is also used in the TYPO3 Backend (TBE) to generate record icons and new module tabs.
				It's highly recommended to install this library. Remember to compile GD with FreeType which is also required.
				If you choose not to install GDLib, you can disable it in the configuration with [GFX][gdlib]=0;.
			", 2);
		}
		$this->message($ext, 'GDLib software information', nl2br(trim($this->getGDSoftwareInfo())));
	}

	/**
	 * Checking and testing that the required writable directories are writable.
	 *
	 * @return	[type]		...
	 */
	function checkDirs()	{
		// Check typo3/temp/
		$ext='Directories';
		$this->message($ext);

		$uniqueName = md5(uniqid(microtime()));

			// The requirement level (the integer value, ie. the second value of the value array) has the following meanings:
			// -1 = not required, but if it exists may be writable or not
			//  0 = not required, if it exists the dir should be writable
			//  1 = required, don't has to be writable
			//  2 = required, has to be writable

		$checkWrite=array(
			'typo3temp/' => array('This folder is used by both the frontend (FE) and backend (BE) interface for all kind of temporary and cached files.',2,'dir_typo3temp'),
			'typo3temp/pics/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3temp/temp/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3temp/llxml/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3temp/cs/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3temp/GB/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3temp/locks/' => array('This folder is part of the typo3temp/ section. It needs to be writable, too.',2,'dir_typo3temp'),
			'typo3conf/' => array('This directory contains the local configuration files of your website. TYPO3 must be able to write to these configuration files during setup and when the Extension Manager (EM) installs extensions.',2),
			'typo3conf/ext/' => array('Location for local extensions. Must be writable if the Extension Manager is supposed to install extensions for this website.',0),
			'typo3conf/l10n/' => array('Location for translations. Must be writable if the Extension Manager is supposed to install translations for extensions.',0),
			TYPO3_mainDir.'ext/' => array('Location for global extensions. Must be writable if the Extension Manager is supposed to install extensions globally in the source.',-1),
			'uploads/' => array('Location for uploaded files from RTE, in the subdirectories for uploaded files of content elements.',2),
			'uploads/pics/' => array('Typical location for uploaded files (images especially).',0),
			'uploads/media/' => array('Typical location for uploaded files (non-images especially).',0),
			'uploads/tf/' => array('Typical location for uploaded files (TS template resources).',0),
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] => array('Location for local files such as templates, independent uploads etc.',-1),
			$GLOBALS['TYPO3_CONF_VARS']['BE']['fileadminDir'] . '_temp_/' => array('Typical temporary location for default upload of files by administrators.',0),
		);

		foreach ($checkWrite as $relpath => $descr)	{

				// Check typo3temp/
			$general_message = $descr[0];

			if (!@is_dir(PATH_site.$relpath))	{	// If the directory is missing, try to create it
				t3lib_div::mkdir(PATH_site.$relpath);
			}

			if (!@is_dir(PATH_site.$relpath))	{
				if ($descr[1])	{	// required...
					$this->message($ext, $relpath.' directory does not exist and could not be created','
					<em>Full path: '.PATH_site.$relpath.'</em>
					'.$general_message.'

					This error should not occur as '.$relpath.' must always be accessible in the root of a TYPO3 website.
					',3);
				} else {
					if ($descr[1] == 0)	{
						$msg = 'This directory does not necessarily have to exist but if it does it must be writable.';
					} else {
						$msg = 'This directory does not necessarily have to exist and if it does it can be writable or not.';
					}
					$this->message($ext, $relpath.' directory does not exist','
					<em>Full path: '.PATH_site.$relpath.'</em>
					'.$general_message.'

					'.$msg.'
					',2);
				}
			} else {
				$file = PATH_site.$relpath.$uniqueName;
				@touch($file);
				if (@is_file($file))	{
					unlink($file);
					if ($descr[2])	{ $this->config_array[$descr[2]]=1; }
					$this->message($ext, $relpath.' writable','',-1);
				} else {
					$severity = ($descr[1]==2 || $descr[1]==0) ? 3 : 2;
					if ($descr[1] == 0 || $descr[1] == 2) {
						$msg = 'The directory '.$relpath.' must be writable!';
					} elseif ($descr[1] == -1 || $descr[1] == 1) {
						$msg = 'The directory '.$relpath.' does not neccesarily have to be writable.';
					}
					$this->message($ext, $relpath.' directory not writable','
					<em>Full path: '.$file.'</em>
					'.$general_message.'

					Tried to write this file (with touch()) but didn\'t succeed.
					'.$msg.'
					',$severity);
				}
			}
		}
	}

	/**
	 * Checking for existing ImageMagick installs.
	 *
	 * This tries to find available ImageMagick installations and tries to find the version numbers by executing "convert" without parameters. If the ->checkIMlzw is set, LZW capabilities of the IM installs are check also.
	 *
	 * @param	[type]		$paths: ...
	 * @return	[type]		...
	 */
	function checkImageMagick($paths)	{
		$ext='Check Image Magick';
		$this->message($ext);

		$paths = array_unique($paths);

		$programs = explode(',','gm,convert,combine,composite,identify');
		$isExt = TYPO3_OS=="WIN" ? ".exe" : "";
		$this->config_array['im_combine_filename']='combine';
		reset($paths);
		while(list($k,$v)=each($paths))	{
			reset($programs);
			if (!preg_match('/[\\/]$/',$v)) $v.='/';
			while(list(,$filename)=each($programs))	{
				if (ini_get('open_basedir') || (file_exists($v)&&@is_file($v.$filename.$isExt))) {
					$version = $this->_checkImageMagick_getVersion($filename,$v);
					if($version > 0)	{
						if($filename=='gm')	{	// Assume GraphicsMagick
							$index[$v]['gm']=$version;
							continue;	// No need to check for "identify" etc.
						} else	{	// Assume ImageMagick
							$index[$v][$filename]=$version;
						}
					}
				}
			}
			if (count($index[$v])>=3 || $index[$v]['gm'])	{ $this->config_array['im']=1; }

			if ($index[$v]['gm'] || (!$index[$v]['composite'] && $index[$v]['combine'])) {
				$this->config_array['im_combine_filename']='combine';
			} elseif ($index[$v]['composite'] && !$index[$v]['combine'])  {
				$this->config_array['im_combine_filename']='composite';
			}

			if (isset($index[$v]['convert']) && $this->checkIMlzw)	{
				$index[$v]['gif_capability'] = ''.$this->_checkImageMagickGifCapability($v);
			}
		}
		$this->config_array['im_versions']=$index;
		if (!$this->config_array['im'])	{
			$this->message($ext, 'No ImageMagick installation available',"
			It seems that there is no adequate ImageMagick installation available at the checked locations (".implode($paths, ', ').")
			An 'adequate' installation for requires 'convert', 'combine'/'composite' and 'identify' to be available
			",2);
		} else {
			$theCode='';
			reset($this->config_array['im_versions']);
			while(list($p,$v)=each($this->config_array['im_versions']))	{
				$ka=array();
				reset($v);
				while(list($ka[])=each($v)){}
				$theCode.='<tr><td>'.$this->fw($p).'</td><td>'.$this->fw(implode($ka,'<br />')).'</td><td>'.$this->fw(implode($v,'<br />')).'</td></tr>';
			}
			$this->message($ext, 'Available ImageMagick/GraphicsMagick installations:','<table border="1" cellpadding="2" cellspacing="2">'.$theCode.'</table>',-1);
		}
		$this->message($ext, 'Search for ImageMagick:','
			<form action="'.$this->action.'" method="post">
				<input type="checkbox" name="TYPO3_INSTALL[checkIM][lzw]" id="checkImLzw" value="1"'.($this->INSTALL['checkIM']['lzw']?' checked="checked"':'').'> <label for="checkImLzw">Check LZW capabilities.</label>

				Check this path for ImageMagick installation:
				<input type="text" name="TYPO3_INSTALL[checkIM][path]" value="'.htmlspecialchars($this->INSTALL['checkIM']['path']).'">
				(Eg. "D:\wwwroot\im537\ImageMagick\" for Windows or "/usr/bin/" for Unix)<br />

				<input type="submit" value="Send">
			</form>
		',0);

	}

	/**
	 * Checking GIF-compression capabilities of ImageMagick install
	 *
	 * @param	[type]		$file: ...
	 * @return	[type]		...
	 */
	function _checkImageMagickGifCapability($path)	{
		if ($this->config_array['dir_typo3temp'])	{		//  && !$this->config_array['safemode']
			$tempPath = $this->typo3temp_path;
			$uniqueName = md5(uniqid(microtime()));
			$dest = $tempPath.$uniqueName.'.gif';
			$src = $this->backPath.'gfx/typo3logo.gif';
			if (@is_file($src) && !strstr($src,' ') && !strstr($dest,' '))	{
				$cmd = t3lib_div::imageMagickCommand('convert', $src.' '.$dest, $path);
				exec($cmd);
			} else die('No typo3/gfx/typo3logo.gif file!');
			$out='';
			if (@is_file($dest))	{
				$new_info = @getimagesize($dest);
				clearstatcache();
				$new_size = filesize($dest);
				$src_info = @getimagesize($src);
				clearstatcache();
				$src_size = @filesize($src);

				if ($new_info[0]!=$src_info[0] || $new_info[1]!=$src_info[1] || !$new_size || !$src_size)	{
					$out='error';
				} else {
					if ($new_size/$src_size > 4) {	// NONE-LZW ratio was 5.5 in test
						$out='NONE';
					} elseif ($new_size/$src_size > 1.5) {	// NONE-RLE ratio was not tested
						$out='RLE';
					} else {
						$out='LZW';
					}
				}
				unlink($dest);
			}
			return $out;
		}
	}

	/**
	 * Extracts the version number for ImageMagick
	 *
	 * @param	string		The program name to execute in order to find out the version number
	 * @param	string		Path for the above program
	 * @return	string		Version number of the found ImageMagick instance
	 */
	function _checkImageMagick_getVersion($file, $path)	{
			// Temporarily override some settings
		$im_version = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'];
		$combine_filename = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'];

		if ($file=='gm')	{
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] = 'gm';
			$file = 'identify';		// Work-around, preventing execution of "gm gm"
			$parameters = '-version';	// Work-around - GM doesn't like to be executed without any arguments
		} else {
			$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] = 'im5';

			if($file=='combine' || $file=='composite')	{	// Override the combine_filename setting
				$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'] = $file;
			}
		}

		$cmd = t3lib_div::imageMagickCommand($file, $parameters, $path);
		$retVal = false;
		exec($cmd, $retVal);
		$string = $retVal[0];
		list(,$ver) = explode('Magick', $string);
		list($ver) = explode(' ',trim($ver));

			// Restore the values
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5'] = $im_version;
		$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'] = $combine_filename;
		return trim($ver);
	}

	/**
	 * Checks database username/password/host/database
	 *
	 * @return	[type]		...
	 */
	function checkDatabase()		{
		$ext='Check database';
		$this->message($ext);

		if (!extension_loaded('mysql') && !t3lib_extMgm::isLoaded('dbal'))	{
			$this->message($ext, 'MySQL not available',"
				PHP does not feature MySQL support (which is pretty unusual).
			",2);
		} else {
			$cInfo='
				Username: <strong>' . htmlspecialchars(TYPO3_db_username) . '</strong>
				Host: <strong>' . htmlspecialchars(TYPO3_db_host) . '</strong>
			';
			if (!TYPO3_db_host || !TYPO3_db_username)	{
				$this->message($ext, 'Username, password or host not set',"
					You may need to enter data for these values:
					".trim($cInfo)."

					Use the form below.
				",2);
			}
			if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
				$this->message($ext, 'Connected to SQL database successfully',"
				".trim($cInfo)."
				",-1,1);
				$this->config_array['mysqlConnect']=1;
				if (!TYPO3_db)	{
					$this->message($ext, 'No database selected',"
						Currently you have no database selected.
						Please select one or create a new database.
					",3);
					$this->config_array['no_database']=1;
				} elseif (!$GLOBALS['TYPO3_DB']->sql_select_db(TYPO3_db))  {
					$this->message($ext, 'Database',"
						'" . htmlspecialchars(TYPO3_db) . "' could not be selected as database!
						Please select another one or create a new database.
					",3,1);
					$this->config_array['no_database']=1;
				} else  {
					$this->message($ext, 'Database',"
						<strong>" . htmlspecialchars(TYPO3_db) . "</strong> is selected as database.
					",1,1);
				}
			} else {
				$this->message($ext, 'Could not connect to SQL database!',"
				Connecting to SQL database failed with these settings:
				".trim($cInfo)."

				Make sure you're using the correct set of data.".($this->config_array['sql.safe_mode_user']? "
				<strong>Notice:</strong> <em>sql.safe_mode</em> is turned on, so apparently your username to the database is the same as the scriptowner, which is '".$this->config_array['sql.safe_mode_user']."'":"")."
				",3);
			}
		}
	}

	/**
	 * Prints form for updating localconf.php or updates localconf.php depending on $cmd
	 *
	 * @param	string		$cmd	If "get_form" it outputs the form. Default is to write "localconf.php" based on input in ->INSTALL[localconf.php] array and flag ->setLocalconf
	 * @return	[type]		...
	 */
	function setupGeneral($cmd='')	{
		switch($cmd)	{
			case 'get_form':
					// Database:
				$out='
				<form name="setupGeneral" action="'.$this->action.'" method="post">
				<table border="0" cellpadding="0" cellspacing="0">';

				$out.=$this->wrapInCells('Username:', '<input type="text" name="TYPO3_INSTALL[localconf.php][typo_db_username]" value="'.htmlspecialchars(TYPO3_db_username?TYPO3_db_username:($this->config_array['sql.safe_mode_user']?$this->config_array['sql.safe_mode_user']:"")).'">'.($this->config_array['sql.safe_mode_user']?"<br />sql.safe_mode_user: <strong>".$this->config_array['sql.safe_mode_user']."</strong>":""));
				$out.=$this->wrapInCells('Password:', '<input type="password" name="TYPO3_INSTALL[localconf.php][typo_db_password]" value="'.htmlspecialchars(TYPO3_db_password).'">');
				$out.=$this->wrapInCells('Host:', '<input type="text" name="TYPO3_INSTALL[localconf.php][typo_db_host]" value="'.htmlspecialchars(TYPO3_db_host).'">');
				if ($this->config_array['mysqlConnect'])	{
					$dbArr = $this->getDatabaseList();
					reset($dbArr);
					$options='';
					$dbIncluded=0;
					while(list(,$dbname)=each($dbArr))	{
						$options.='<option value="'.htmlspecialchars($dbname).'"'.($dbname==TYPO3_db?' selected="selected"':'').'>'.htmlspecialchars($dbname).'</option>';
						if ($dbname==TYPO3_db)	$dbIncluded=1;
					}
					if (!$dbIncluded && TYPO3_db)	{
						$options.='<option value="'.htmlspecialchars(TYPO3_db).'" selected="selected">'.htmlspecialchars(TYPO3_db).' (NO ACCESS!)</option>';
					}
					$theCode='<select name="TYPO3_INSTALL[localconf.php][typo_db]">'.$options.'</select><br />Create database? (Enter name):<br /><input type="text" name="TYPO3_INSTALL[localconf.php][NEW_DATABASE_NAME]" value="">';
				} else {
					$theCode='<strong>'.htmlspecialchars(TYPO3_db).'</strong><br />(Can only select database if username/password/host is correctly set first)<input type="hidden" name="TYPO3_INSTALL[localconf.php][typo_db]" value="'.htmlspecialchars(TYPO3_db).'">';
				}
				$out.=$this->wrapInCells('', '<br />');
				$out.=$this->wrapInCells('Database:', $theCode);
				$out.=$this->wrapInCells('', '<br />');

				if ($this->mode!='123')	{
					$this->headerStyle .= chr(10) .
						'<script type="text/javascript" src="' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'contrib/prototype/prototype.js"></script>
						 <script type="text/javascript" src="' . t3lib_div::getIndpEnv('TYPO3_SITE_URL') . TYPO3_mainDir . 'sysext/install/mod/install.js"></script>';


					$out.=$this->wrapInCells('Site name:', '<input type="text" name="TYPO3_INSTALL[localconf.php][sitename]" value="'.htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']).'">');
					$out.=$this->wrapInCells('', '<br />');
					$out.=$this->wrapInCells('Encryption key:', '<a name="set_encryptionKey"></a><input type="text" name="TYPO3_INSTALL[localconf.php][encryptionKey]" value="'.htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey']).'"><br /><input type="button" onclick="EncryptionKey.load(this)" value="Generate random key">');
					$out.=$this->wrapInCells('', '<br />');

						// Other
					$fA = $this->setupGeneralCalculate();

					if (is_array($fA['disable_exec_function']))	{
						$out.=$this->wrapInCells('[BE][disable_exec_function]=', $this->getFormElement($fA['disable_exec_function'], $fA['disable_exec_function'], 'TYPO3_INSTALL[localconf.php][disable_exec_function]', $GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function']));
					}
					if (is_array($fA['gdlib']))	{
						$out.=$this->wrapInCells('[GFX][gdlib]=', $this->getFormElement($fA['gdlib'], $fA['gdlib'], 'TYPO3_INSTALL[localconf.php][gdlib]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']));
						if (is_array($fA['gdlib_png']) && $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib'])	{
							$out.=$this->wrapInCells('[GFX][gdlib_png]=', $this->getFormElement($this->setLabelValueArray($fA['gdlib_png'],2), $fA['gdlib_png'], 'TYPO3_INSTALL[localconf.php][gdlib_png]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']));
						}
					}
					if (is_array($fA['im']))	{
						$out.=$this->wrapInCells('[GFX][im]=', $this->getFormElement($fA['im'], $fA['im'], 'TYPO3_INSTALL[localconf.php][im]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['im']));
						$out.=$this->wrapInCells('[GFX][im_combine_filename]=', $this->getFormElement($fA['im_combine_filename'], ($fA['im_combine_filename']?$fA['im_combine_filename']:"combine"), 'TYPO3_INSTALL[localconf.php][im_combine_filename]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename']));
						$out.=$this->wrapInCells('[GFX][im_version_5]=', $this->getFormElement($fA['im_version_5'], ($fA['im_version_5']?$fA['im_version_5']:''), 'TYPO3_INSTALL[localconf.php][im_version_5]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']));
						if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im'])	{
							if (is_array($fA['im_path']))	{
								$out.=$this->wrapInCells('[GFX][im_path]=', $this->getFormElement($this->setLabelValueArray($fA['im_path'],1), $this->setLabelValueArray($fA['im_path'],0), 'TYPO3_INSTALL[localconf.php][im_path]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path']));
							}
							if (is_array($fA['im_path_lzw']))	{
								$out.=$this->wrapInCells('[GFX][im_path_lzw]=', $this->getFormElement($this->setLabelValueArray($fA['im_path_lzw'],1), $this->setLabelValueArray($fA['im_path_lzw'],0), 'TYPO3_INSTALL[localconf.php][im_path_lzw]', $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw']));
							}
						}
					}
					$out.=$this->wrapInCells('[GFX][TTFdpi]=', '<input type="text" name="TYPO3_INSTALL[localconf.php][TTFdpi]" value="'.htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi']).'">');
				}


				$out.=$this->wrapInCells('', '<br /><input type="submit" value="Update localconf.php"><br />
				<strong>NOTICE: </strong>By clicking this button, localconf.php is updated with new values for the parameters listed above!');
				$out.='
				</table>
				</form>';
			break;
			default:
				if (is_array($this->INSTALL['localconf.php']))		{
					$errorMessages=array();
					$lines = $this->writeToLocalconf_control();

						// New database?
#debug($this->INSTALL);
					if (trim($this->INSTALL['localconf.php']['NEW_DATABASE_NAME']))	{
						$newdbname=trim($this->INSTALL['localconf.php']['NEW_DATABASE_NAME']);
						if (!preg_match('/[^[:alnum:]_-]/',$newdbname))	{
							if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
								if ($GLOBALS['TYPO3_DB']->admin_query('CREATE DATABASE '.$newdbname)) {
									$this->INSTALL['localconf.php']['typo_db'] = $newdbname;
									$this->messages[]= "Database '".$newdbname."' created";
								} else $this->messages[]= $errorMessages[] = "Could not create database '".$newdbname."' (...not created)";
							} else $this->messages[]= $errorMessages[] = "Could not connect to database when creating database '".$newdbname."' (...not created)";
						} else $this->messages[]= $errorMessages[] = "The NEW database name '".$newdbname."' was not alphanumeric, a-zA-Z0-9_- (...not created)";
					}
#debug($this->messages)		;
						// Parsing values
					reset($this->INSTALL['localconf.php']);
					while(list($key,$value)=each($this->INSTALL['localconf.php']))		{
						switch((string)$key)	{
							case 'typo_db_username':
								if (strlen($value)<50)	{
									if (strcmp(TYPO3_db_username,$value))		$this->setValueInLocalconfFile($lines, '$typo_db_username', trim($value));
								} else $this->messages[]= $errorMessages[] = "Username '".$value."' was longer than 50 chars (...not saved)";
							break;
							case 'typo_db_password':
								if (strlen($value)<50)	{
									if (strcmp(TYPO3_db_password,$value))		$this->setValueInLocalconfFile($lines, '$typo_db_password',  trim($value));
								} else $this->messages[]= $errorMessages[] = "Password was longer than 50 chars (...not saved)";
							break;
							case 'typo_db_host':
								if (preg_match('/^[a-zA-Z0-9_\.-]+(:.+)?$/',$value) && strlen($value)<50)	{
									if (strcmp(TYPO3_db_host,$value))		$this->setValueInLocalconfFile($lines, '$typo_db_host', $value);
								} else $this->messages[]= $errorMessages[] = "Host '".$value."' was not alphanumeric (a-z, A-Z, 0-9 or _-.), or longer than 50 chars (...not saved)";
							break;
							case 'typo_db':
								if (strlen($value)<50)	{
									if (strcmp(TYPO3_db,$value))		$this->setValueInLocalconfFile($lines, '$typo_db',  trim($value));
								} else $this->messages[]= $errorMessages[] = "Database name '".$value."' was longer than 50 chars (...not saved)";
							break;
							case 'disable_exec_function':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['BE']['disable_exec_function'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'BE\'][\'disable_exec_function\']', $value?1:0);
							break;
							case 'sitename':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'SYS\'][\'sitename\']', $value);
							break;
							case 'encryptionKey':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['SYS']['encryptionKey'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'SYS\'][\'encryptionKey\']', $value);
							break;
							case 'compat_version':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['SYS']['compat_version'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'SYS\'][\'compat_version\']', $value);
							break;
							case 'im_combine_filename':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_combine_filename'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'im_combine_filename\']', $value);
							break;
							case 'gdlib':
							case 'gdlib_png':
							case 'im':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['GFX'][$key], $value)) {
									$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'' . $key . '\']', ($value ? 1 : 0));
								}
							break;
							case 'im_path':
								list($value,$version) = explode('|',$value);
								if (!preg_match('/[[:space:]]/',$value,$reg) && strlen($value)<100)	{
									if (strcmp($GLOBALS['TYPO3_CONF_VARS']['GFX'][$key], $value)) {
										$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'' . $key . '\']', $value);
									}
									if(doubleval($version)>0 && doubleval($version)<4)	{	// Assume GraphicsMagick
										$value_ext = 'gm';
									} elseif(doubleval($version)<5)	{	// Assume ImageMagick 4.x
										$value_ext = '';
									} elseif(doubleval($version) >= 6) {	// Assume ImageMagick 6.x
										$value_ext = 'im6';
									} else	{	// Assume ImageMagick 5.x
										$value_ext = 'im5';
									}
									if (strcmp(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']),$value_ext))	{
										$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'im_version_5\']', $value_ext);
									}
	// 								if (strcmp(strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']),$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS['GFX']['im_version_5']', $value);
								} else $this->messages[]= $errorMessages[] = "Path '".$value."' contains spaces or is longer than 100 chars (...not saved)";
							break;
							case 'im_path_lzw':
								list($value) = explode('|',$value);
								if (!preg_match('/[[:space:]]/',$value) && strlen($value)<100)	{
									if (strcmp($GLOBALS['TYPO3_CONF_VARS']['GFX'][$key], $value)) {
										$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'' . $key . '\']', $value);
									}
								} else $this->messages[]= $errorMessages[] = "Path '".$value."' contains spaces or is longer than 100 chars (...not saved)";
							break;
							case 'TTFdpi':
								if (strcmp($GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi'],$value))	$this->setValueInLocalconfFile($lines, '$TYPO3_CONF_VARS[\'GFX\'][\'TTFdpi\']', $value);
							break;
						}


					}

					if (count($errorMessages))	{
						echo '<h3>ERRORS</h3>';
						echo t3lib_div::view_array($errorMessages);
						echo 'Click the browsers "Back" button to reenter the values.';
						exit;
					}
					$this->writeToLocalconf_control($lines);
				}
			break;
		}
		return $out;
	}

	/**
	 * Writes or returns lines from localconf.php
	 *
	 * @param	array		Array of lines to write back to localconf.php. Possibly
	 * @param	boolean		If TRUE then print what has been done.
	 * @return	mixed		If $lines is not an array it will return an array with the lines from localconf.php. Otherwise it will return a status string, either "continue" (updated) or "nochange" (not updated)
	 * @see parent::writeToLocalconf_control()
	 */
	function writeToLocalconf_control($lines='', $showOutput=TRUE)	{
		$returnVal = parent::writeToLocalconf_control($lines);

		if ($showOutput)	{
			switch($returnVal)	{
				case 'continue':
					$content = '<br /><br />'.implode($this->messages,'<hr />').'<br /><br /><a href="'.$this->action.'">Click to continue...</a>';
					$this->outputExitBasedOnStep($content);
				break;
				case 'nochange':
					$content = '<strong>Writing to \'localconf.php\':</strong><br /><br />No values were changed, so nothing is updated!<br /><br /><a href="'.$this->action.'">Click to continue...</a>';
					$this->outputExitBasedOnStep('<br />'.$content);
				break;
			}
		}
		return $returnVal;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function outputExitBasedOnStep($content)	{
		if ($this->step) {
			t3lib_utility_Http::redirect($this->action);
		} else {
			$this->output($this->outputWrapper($content));
		}
		exit;
	}

	/**
	 * This appends something to value in the input array based on $type. Private.
	 *
	 * @param	[type]		$arr: ...
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function setLabelValueArray($arr,$type)	{
		reset($arr);
		while(list($k,$v)=each($arr))	{
			if($this->config_array['im_versions'][$v]['gm'])	{
				$program = 'gm';
			} else	{
				$program = 'convert';
			}

			switch($type)	{
				case 0:	// value, im
					$arr[$k].='|'.$this->config_array['im_versions'][$v][$program];
				break;
				case 1:	// labels, im
					if($this->config_array['im_versions'][$v][$program])	{
						$arr[$k].= ' ('.$this->config_array['im_versions'][$v][$program];
						$arr[$k].= ($this->config_array['im_versions'][$v]['gif_capability'] ? ', '.$this->config_array['im_versions'][$v]['gif_capability'] : '');
						$arr[$k].= ')';
					} else	{
						$arr[$k].= '';
					}
				break;
				case 2: // labels, gd
					$arr[$k].=' ('.($v==1?'PNG':'GIF').')';
				break;
			}
		}
		return $arr;
	}

	/**
	 * Returns a form-element for the localconf.php update form
	 *
	 * @param	[type]		$labels: ...
	 * @param	[type]		$values: ...
	 * @param	[type]		$fieldName: ...
	 * @param	[type]		$default: ...
	 * @param	[type]		$msg: ...
	 * @return	[type]		...
	 */
	function getFormElement($labels,$values,$fieldName,$default,$msg='')	{
		$out.='<strong>'.htmlspecialchars(current($labels)).'</strong><br />current value is '.htmlspecialchars($default).($msg?'<br />'.$msg:'');
		if (count($labels)>1)		{
			reset($labels);
			while(list($k,$v)=each($labels))	{
				list($cleanV) =explode('|',$values[$k]);
				$options.='<option value="'.htmlspecialchars($values[$k]).'"'.(!strcmp($default,$cleanV)?' selected="selected"':'').'>'.htmlspecialchars($v).'</option>';
			}
			$out.='<br /><select name="'.$fieldName.'">'.$options.'</select>';
		} else {
			$out.='<input type="hidden" name="'.$fieldName.'" value="'.htmlspecialchars(current($values)).'">';
		}
		return $out.'<br />';
	}

	/**
	 * Returns the list of available databases (with access-check based on username/password)
	 *
	 * @return	[type]		...
	 */
	function getDatabaseList()	{
		$dbArr=array();
		if ($result = $GLOBALS['TYPO3_DB']->sql_pconnect(TYPO3_db_host, TYPO3_db_username, TYPO3_db_password))	{
			$dbArr = $GLOBALS['TYPO3_DB']->admin_get_dbs();
		}
		return $dbArr;
	}

	/**
	 * Calculates the suggested setup that should be written to localconf.php
	 *
	 * If safe_mode
	 * - disable_exec_function = 1
	 * - im = 0
	 *
	 * if PNG/GIF/GD
	 * - disable gdlib if nothing
	 * 	- select png/gif if only one of them is available, else PNG/GIF selector, defaulting to GIF
	 * - (safe_mode is on)
	 * 	- im_path (default to 4.2.9, preferable with LZW)		im_ver5-flag is set based on im_path being 4.2.9 or 5+
	 * 	- im_path_lzw (default to LZW version, pref. 4.2.9)
	 *
	 * @return	[type]		...
	 */
	function setupGeneralCalculate()	{
		$formArray['disable_exec_function']=array(0);
		$formArray['im_path']=array('');
		$formArray['im_path_lzw']=array('');
		$formArray['im_combine_filename']=array('');
		$formArray['im_version_5']=array('');
		$formArray['im']=array(1);
		$formArray['gdlib']=array(1);
		if ($this->config_array['gd'] && ($this->config_array['gd_gif'] || $this->config_array['gd_png']))	{
			if ($this->config_array['gd_gif'] && !$this->config_array['gd_png'])	{
				$formArray['gdlib_png']=array(0);
			} elseif (!$this->config_array['gd_gif'] && $this->config_array['gd_png'])	{
				$formArray['gdlib_png']=array(1);
			} else {
				$formArray['gdlib_png']=array(0,1);
			}
		} else {
			$formArray['gdlib']=array(0);
		}
		if ($this->config_array['safemode'])	{
			$formArray['disable_exec_function']=array(1);
		}
		if ($this->config_array['im'])	{
			$formArray['im']=array(1);
			$LZW_found=0;
			$found=0;
			reset($this->config_array['im_versions']);
			$totalArr=array();
			while(list($path,$dat)=each($this->config_array['im_versions']))	{
				if (count($dat)>=3)	{
					if (doubleval($dat['convert'])<5)	{
						$formArray['im_version_5']=array(0);
						if ($dat['gif_capability']=='LZW')	{
							$formArray['im_path']=array($path);
							$found=2;
						} elseif ($found<2)	{
							$formArray['im_path']=array($path);
							$found=1;
						}
					} elseif (doubleval($dat['convert']) >= 6) {
						$formArray['im_version_5'] = array('im6');
						if ($dat['gif_capability'] == 'LZW') {
							$formArray['im_path'] = array($path);
							$found = 2;
						} elseif ($found < 2)	{
							$formArray['im_path'] = array($path);
							$found = 1;
						}
					} elseif (!$found) {
						$formArray['im_version_5']=array('im5');
						$formArray['im_path']=array($path);
						$found=1;
					}
				} elseif ($dat['gm'])	{
					$formArray['im_version_5']=array('gm');
					if ($dat['gif_capability']=='LZW')	{
						$formArray['im_path']=array($path);
						$found=2;
					} elseif ($found<2)	{
						$formArray['im_path']=array($path);
						$found=1;
					}
				}
				if ($dat['gif_capability']=='LZW')	{
					if (doubleval($dat['convert'])<5 || !$LZW_found)	{
						$formArray['im_path_lzw']=array($path);
						$LZW_found=1;
					}
				} elseif ($dat['gif_capability']=="RLE" && !$LZW_found)	{
					$formArray['im_path_lzw']=array($path);
				}
				$totalArr[]=$path;
			}
			$formArray['im_path']=array_unique(array_merge($formArray['im_path'],$totalArr));
			$formArray['im_path_lzw']=array_unique(array_merge($formArray['im_path_lzw'],$totalArr));
			$formArray['im_combine_filename']=array($this->config_array['im_combine_filename']);
		} else {
			$formArray['im']=array(0);
		}
		return $formArray;
	}

	/**
	 * Returns the part of phpinfo() output that tells about GD library (HTML-code)
	 *
	 * @return	[type]		...
	 */
	function getGDPartOfPhpinfo()	{
		ob_start();
		phpinfo();
		$contents = ob_get_contents();
		ob_end_clean();
		$start_string = $this->getGD_start_string;
		$end_string = $this->getGD_end_string;
		list(,$gdpart_tmp) = explode($start_string,$contents,2);
		list($gdpart) = explode($end_string,$start_string.$gdpart_tmp,2);
		$gdpart.=$end_string;
		return $gdpart;
	}

	/**
	 * Returns true if TTF lib is install according to phpinfo(). If $phpinfo supply as parameter that string is searched instead.
	 *
	 * @param	[type]		$phpinfo: ...
	 * @return	[type]		...
	 */
	function isTTF($phpinfo='')	{
/*		$phpinfo = $phpinfo?$phpinfo:$this->getGDPartOfPhpinfo();
		if (stristr($phpinfo, $this->getTTF_string))    return 1;
		if (stristr($phpinfo, $this->getTTF_string_alt))        return 1;
		*/

		if (!function_exists('imagettftext'))	return 0;	// Return right away if imageTTFtext does not exist.

			// try, print truetype font:
		$im = @imagecreate (300, 50);
		$background_color = imagecolorallocate ($im, 255, 255, 55);
		$text_color = imagecolorallocate ($im, 233, 14, 91);

		$test = @imagettftext($im, t3lib_div::freetypeDpiComp(20), 0, 10, 20, $text_color, PATH_t3lib."/fonts/vera.ttf", 'Testing Truetype support');
		if (t3lib_div::_GP('testingTrueTypeSupport'))	{
			if ($this->isGIF())	{
				header ('Content-type: image/gif');
				imagegif ($im);
			} else {
				header ('Content-type: image/png');
				imagepng ($im);
			}
			exit;
		}
		return is_array($test)?1:0;
	}











	/*****************************************
	 *
	 * ABOUT the isXXX functions.
	 *
	 * I had a very real experience that these checks DID NOT fail eg PNG support if it didn't exist!
	 * So first (1) we check if the functions are there. If they ARE we are going to make further investigations (2) by creating an actual image.
	 * And if THAT succeeds also, then we can be certain of the support!
	 */

	/**
	 * @return	[type]		...
	 */
	function isGD()	{
		if (function_exists('imagecreate'))	{
			if (@imagecreate (50, 100))	return 1;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isGIF()	{
		if (function_exists('imagecreatefromgif') && function_exists('imagegif') && ($this->ImageTypes() & IMG_GIF))	{	// If GIF-functions exists, also do a real test of them:
			$im = @imagecreatefromgif(t3lib_extMgm::extPath('install').'imgs/jesus.gif');
			return $im?1:0;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isJPG()	{
		if (function_exists('imagecreatefromjpeg') && function_exists('imagejpeg') && ($this->ImageTypes() & IMG_JPG))	{
			return 1;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isPNG()	{
		if (function_exists('imagecreatefrompng') && function_exists('imagepng') && ($this->ImageTypes() & IMG_PNG))	{
			$im = imagecreatefrompng(t3lib_extMgm::extPath('install').'imgs/jesus.png');
			return $im?1:0;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function ImageTypes()	{
		return imagetypes();
	}

	/**
	 * Returns general information about GDlib
	 *
	 * @return	[type]		...
	 */
	function getGDSoftwareInfo()	{
		return trim('
		You can get GDLib in the PNG version from ' . $this->linkIt('http://www.libgd.org/') .
		'. <br />FreeType is for download at ' . $this->linkIt('http://www.freetype.org/') .
		'. <br />Generally, TYPO3 packages are listed at ' . $this->linkIt('http://typo3.org/download/packages/') . '.'
		);
	}

	/**
	 * Returns general information about configuration of TYPO3.
	 *
	 * @return	[type]		...
	 */
	function generallyAboutConfiguration()	{
		$out='
		Local configuration is done by overriding default values in the included file, typo3conf/localconf.php. In this file you enter the database information along with values in the global array TYPO3_CONF_VARS.
		The options in the TYPO3_CONF_VARS array and how to use it for your own purposes is discussed in the base configuration file, t3lib/config_default.php. This file sets up the default values and subsequently includes the localconf.php file in which you can then override values.
		See this page for '.$this->linkIt('http://typo3.org/1275.0.html','more information about system requirements.').'
		';
		return trim($out);
	}













	/**********************
	 *
	 * IMAGE processing
	 *
	 **********************/

	/**
	 * jesus.TIF:	IBM/LZW
	 * jesus.GIF:	Save for web, 32 colors
	 * jesus.JPG:	Save for web, 30 quality
	 * jesus.PNG:	Save for web, PNG-24
	 * jesus.tga	24 bit TGA file
	 * jesus.pcx
	 * jesus.bmp	24 bit BMP file
	 * jesus_ps6.PDF:	PDF w/layers and vector data
	 * typo3logo.ai:	Illustrator 8 file
	 * pdf_from_imagemagick.PDF	PDF-file made by Acrobat Distiller from InDesign PS-file
	 *
	 *
	 * Imagemagick
	 * - Read formats
	 * - Write png, gif, jpg
	 *
	 * Problems may arise from the use of safe_mode (eg. png)
	 * In safemode you will automatically execute the program convert in the safe_mode_exec_path no matter what other path you specify
	 * check fileexist before anything...
	 *
	 * - compare gif size
	 * - scaling (by stdgraphic)
	 * - combining (by stdgraphic)
	 *
	 * GDlib:
	 * - create from:....
	 * - ttf text
	 *
	 * From TypoScript: (GD only, GD+IM, IM)
	 *
	 * @return	[type]		...
	 */
	function checkTheImageProcessing()	{
		$this->message('Image Processing','What is it?',"
		TYPO3 is known for its ability to process images on the server.
		In the backend interface (TBE) thumbnails are automatically generated (by ImageMagick in thumbs.php) as well as icons, menu items and pane tabs (by GDLib).
		In the TypoScript enabled frontend all kinds of graphical elements are processed. Typically images are scaled down to fit the pages (by ImageMagick) and menu items, graphical headers and such are generated automatically (by GDLib + ImageMagick).
		In addition TYPO3 is able to handle many file formats (thanks to ImageMagick), for example TIF, BMP, PCX, TGA, AI and PDF in addition to the standard web formats; JPG, GIF, PNG.

		In order to do this, TYPO3 uses two sets of tools:

		<strong>ImageMagick:</strong>
		For conversion of non-web formats to webformats, combining images with alpha-masks, performing image-effects like blurring and sharpening.
		ImageMagick is a collection of external programs on the server called by the exec() function in PHP. TYPO3 uses three of these, namely 'convert' (converting fileformats, scaling, effects), 'combine'/'composite' (combining images with masks) and 'identify' (returns image information).
		Because ImageMagick are external programs, two requirements must be met: 1) The programs must be installed on the server and working and 2) if safe_mode is enabled, the programs must be located in the folder defined by the php.ini setting, <em>safe_mode_exec_dir</em> (else they are not executed).
		ImageMagick is available for both Windows and Unix. The current version is 5+, but TYPO3 enthusiasts prefer an old version 4.2.9 because that version has three main advantages: It's faster in some operations, the blur-function works, the sharpen-function works. Anyway you do it, you must tell TYPO3 by configuration whether you're using version 5+ or 4.2.9. (flag: [GFX][im_version_5])
		ImageMagick homepage is at ".$this->linkIt('http://www.imagemagick.org/')."

		<strong>GDLib:</strong>
		For drawing boxes and rendering text on images with truetype fonts. Also used for icons, menuitems and generally the TypoScript GIFBUILDER object is based on GDlib, but extensively utilizing ImageMagick to process intermediate results.
		GDLib is accessed through internal functions in PHP, so in this case, you have no safe_mode problems, but you'll need a version of PHP with GDLib compiled in. Also in order to use TrueType fonts with GDLib you'll need FreeType compiled in as well.
		".$this->getGDSoftwareInfo().'

		You can disable all image processing options in TYPO3 ([GFX][image_processing]=0), but that would seriously disable TYPO3.
		');

		$this->message('Image Processing','Verifying the image processing capabilities of your server',"
		This page performs image processing and displays the result. It's a thorough check that everything you've configured is working correctly.
		It's quite simple to verify your installation; Just look down the page, the images in pairs should look like each other. If some images are not alike, something is wrong. You may also notice warnings and errors if this tool found signs of any problems.

		The image to the right is the reference image (how it should be) and to the left the image made by your server.
		The reference images are made with the classic ImageMagick install based on the 4.2.9 RPM and 5.2.3 RPM. If the version 5 flag is set, the reference images are made by the 5.2.3 RPM.

		This test will work only if your ImageMagick/GDLib configuration allows it to. The typo3temp/ folder must be writable for all the temporary image files. They are all prefixed 'install_' so they are easy to recognize and delete afterwards.
		");

		$im_path = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path'];
		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']=='gm')	{
			$im_path_version = $this->config_array['im_versions'][$im_path]['gm'];
		} else {
			$im_path_version = $this->config_array['im_versions'][$im_path]['convert'];
		}
		$im_path_lzw = $GLOBALS['TYPO3_CONF_VARS']['GFX']['im_path_lzw'];
		$im_path_lzw_version = $this->config_array['im_versions'][$im_path_lzw]['convert'];
		$msg = '
		ImageMagick enabled: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['im']) . '</strong>
		ImageMagick path: <strong>' . htmlspecialchars($im_path) . '</strong> (' . htmlspecialchars($im_path_version) . ')
		ImageMagick path/LZW: <strong>' . htmlspecialchars($im_path_lzw) . '</strong>  (' . htmlspecialchars($im_path_lzw_version) . ')
		Version 5/GraphicsMagick flag: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']) . '</strong>

		GDLib enabled: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']) . '</strong>
		GDLib using PNG: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_png']) . '</strong>
		GDLib 2 enabled: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib_2']) . '</strong>
		IM5 effects enabled: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_v5effects']) . '</strong> (Blurring/Sharpening with IM 5+)
		Freetype DPI: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['TTFdpi']) . '</strong> (Should be 96 for Freetype 2)
		Mask invert: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_imvMaskState']) . '</strong> (Should be set for some IM versions approx. 5.4+)

		File Formats: <strong>' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext']) . '</strong>
		';

			// Various checks to detect IM/GM version mismatches
		$mismatch=false;
		switch (strtolower($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']))	{
			case 'gm':
				if (doubleval($im_path_version)>=2)	$mismatch=true;
			break;
			case 'im4':
				if (doubleval($im_path_version)>=5)	$mismatch=true;
			break;
			default:
				if (($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']?true:false) != (doubleval($im_path_version)>=5))	$mismatch=true;
			break;
		}

		if ($mismatch)	{
			$msg.= 'Warning: Mismatch between the version of ImageMagick'.
					' (' . htmlspecialchars($im_path_version) . ') and the configuration of '.
					'[GFX][im_version_5] (' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']) . ')';
			$etype=2;
		} else $etype=1;

		if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']=='gm')	{
			$msg = str_replace('ImageMagick','GraphicsMagick',$msg);
		}

		$this->message('Image Processing','Current configuration',$msg,$etype);




		if (!$GLOBALS['TYPO3_CONF_VARS']['GFX']['image_processing'])	{
			$this->message('Image Processing','Image Processing disabled!',"
				Image Processing is disabled by the config flag <nobr>[GFX][image_processing]</nobr> set to false (zero)
			",2);
			$this->output($this->outputWrapper($this->printAll()));
			return;
		}
		if (!$this->config_array['dir_typo3temp'])	{
			$this->message('Image Processing','typo3temp/ not writable!',"
				You must make typo3temp/ write enabled before you can proceed with this test.
			",2);
			$this->output($this->outputWrapper($this->printAll()));
			return;
		}



		$msg='<a name="testmenu"></a>Click each of these links in turn to test a topic. <strong>Please be aware that each test may take several seconds!</strong>:

		'.$this->imagemenu();
		$this->message('Image Processing','Testmenu',$msg,'');


		$this->messageFunc_nl2br=0;
		$parseStart = t3lib_div::milliseconds();
		$imageProc = t3lib_div::makeInstance('t3lib_stdGraphic');
		$imageProc->init();
		$imageProc->tempPath = $this->typo3temp_path;
		$imageProc->dontCheckForExistingTempFile=1;
//		$imageProc->filenamePrefix='install_'.($GLOBALS['TYPO3_CONF_VARS']['GFX']['im_version_5']?"v5":"");
		$imageProc->filenamePrefix='install_';
		$imageProc->dontCompress=1;
		$imageProc->alternativeOutputKey='TYPO3_INSTALL_SCRIPT';
		$imageProc->noFramePrepended=$GLOBALS['TYPO3_CONF_VARS']['GFX']['im_noFramePrepended'];

		// Very temporary!!!
		$imageProc->dontUnlinkTempFiles=0;


		$imActive = ($this->config_array['im'] && $im_path);
		$gdActive = ($this->config_array['gd'] && $GLOBALS['TYPO3_CONF_VARS']['GFX']['gdlib']);

		switch($this->INSTALL['images_type'])	{
			case 'read':
				$refParseTime='5600';	// 4.2.9
				$refParseTime='3300';	// 5.2.3
				$headCode = 'Reading and converting images';
				$this->message($headCode,'Supported file formats',"
				This verifies that your ImageMagick installation is able to read the nine default file formats; JPG, GIF, PNG, TIF, BMP, PCX, TGA, PDF, AI.
				The tool 'identify' will be used to read the pixeldimensions of non-web formats.
				The tool 'convert' is used to read the image and write a temporary JPG-file
				");

				if ($imActive)	{
						// Reading formats - writing JPG

					$extArr = explode(',','jpg,gif,png,tif,bmp,pcx,tga');
					while(list(,$ext)=each($extArr))	{
						if ($this->isExtensionEnabled($ext, $headCode, "Read ".strtoupper($ext)))	{
							$imageProc->IM_commands=array();
							$theFile = t3lib_extMgm::extPath('install').'imgs/jesus.'.$ext;
							if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

							$imageProc->imageMagickConvert_forceFileNameBody='read_'.$ext;
							$fileInfo = $imageProc->imageMagickConvert($theFile,'jpg',"",'',"",'',"",1);
							$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
							$this->message($headCode,"Read ".strtoupper($ext),$result[0],$result[1]);
						}
					}

					if ($this->isExtensionEnabled('pdf', $headCode, 'Read PDF'))	{
						$imageProc->IM_commands=array();
						$theFile = t3lib_extMgm::extPath('install').'imgs/pdf_from_imagemagick.pdf';
						if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

						$imageProc->imageMagickConvert_forceFileNameBody='read_pdf';
						$fileInfo = $imageProc->imageMagickConvert($theFile,'jpg',"170",'',"",'',"",1);
						$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
						$this->message($headCode,'Read PDF',$result[0],$result[1]);
					}
					if ($this->isExtensionEnabled('ai', $headCode, 'Read AI'))	{
						$imageProc->IM_commands=array();
						$theFile = t3lib_extMgm::extPath('install').'imgs/typo3logotype.ai';
						if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

						$imageProc->imageMagickConvert_forceFileNameBody='read_ai';
						$fileInfo = $imageProc->imageMagickConvert($theFile,'jpg',"170",'',"",'',"",1);
						$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
						$this->message($headCode,'Read AI',$result[0],$result[1]);
					}
				} else {
					$this->message($headCode,'Test skipped',"
					Use of ImageMagick has been disabled in the configuration.
					Refer to section 'Basic Configuration' to change or review you configuration settings
					",2);
				}
			break;
			case 'write':
				$refParseTime='300';

					// Writingformats - writing JPG
				$headCode = 'Writing images';
				$this->message($headCode,'Writing GIF and PNG','
				This verifies that ImageMagick is able to write GIF and PNG files.
				The GIF-file is attempted compressed with LZW by the t3lib_div::gif_compress() function.
				');

				if ($imActive)	{
						// Writing GIF
					$imageProc->IM_commands=array();
					$theFile = t3lib_extMgm::extPath('install').'imgs/jesus.gif';
					if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

					$imageProc->imageMagickConvert_forceFileNameBody='write_gif';
					$fileInfo = $imageProc->imageMagickConvert($theFile,'gif',"",'',"",'',"",1);
					if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress'])	{
						clearstatcache();
						$prevSize=t3lib_div::formatSize(@filesize($fileInfo[3]));
						$returnCode = t3lib_div::gif_compress($fileInfo[3],'');
						clearstatcache();
						$curSize=t3lib_div::formatSize(@filesize($fileInfo[3]));
						$note = array('Note on gif_compress() function:',"The 'gif_compress' method used was '".$returnCode."'.<br />Previous filesize: ".$prevSize.'. Current filesize:'.$curSize);
					} else  $note=array('Note on gif_compress() function:','<em>Not used! Disabled by [GFX][gif_compress]</em>');
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands, $note);
					$this->message($headCode,'Write GIF',$result[0],$result[1]);


						// Writing PNG
					$imageProc->IM_commands=array();
					$theFile = t3lib_extMgm::extPath('install').'imgs/jesus.gif';

					$imageProc->imageMagickConvert_forceFileNameBody='write_png';
					$fileInfo = $imageProc->imageMagickConvert($theFile,'png',"",'',"",'',"",1);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Write PNG',$result[0],$result[1]);
				} else {
					$this->message($headCode,'Test skipped',"
					Use of ImageMagick has been disabled in the configuration.
					Refer to section 'Basic Configuration' to change or review you configuration settings
					",2);
				}
			break;
			case 'scaling':
				$refParseTime='650';

					// Scaling
				$headCode = 'Scaling images';
				$this->message($headCode,'Scaling transparent images','
				This shows how ImageMagick reacts when scaling transparent GIF and PNG files.
				');

				if ($imActive)	{
						// Scaling transparent image
					$imageProc->IM_commands=array();
					$theFile = t3lib_extMgm::extPath('install').'imgs/jesus2_transp.gif';
					if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

					$imageProc->imageMagickConvert_forceFileNameBody='scale_gif';
					$fileInfo = $imageProc->imageMagickConvert($theFile,'gif',"150",'',"",'',"",1);
					if ($GLOBALS['TYPO3_CONF_VARS']['GFX']['gif_compress'])	{
						clearstatcache();
						$prevSize=t3lib_div::formatSize(@filesize($fileInfo[3]));
						$returnCode = t3lib_div::gif_compress($fileInfo[3],'');
						clearstatcache();
						$curSize=t3lib_div::formatSize(@filesize($fileInfo[3]));
						$note = array('Note on gif_compress() function:',"The 'gif_compress' method used was '".$returnCode."'.<br />Previous filesize: ".$prevSize.'. Current filesize:'.$curSize);
					} else $note=array('Note on gif_compress() function:','<em>Not used! Disabled by [GFX][gif_compress]</em>');
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands,$note);
					$this->message($headCode,'GIF to GIF, 150 pixels wide',$result[0],$result[1]);

					$imageProc->IM_commands=array();
					$theFile = t3lib_extMgm::extPath('install').'imgs/jesus2_transp.png';
					if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');

					$imageProc->imageMagickConvert_forceFileNameBody='scale_png';
					$fileInfo = $imageProc->imageMagickConvert($theFile,'png',"150",'',"",'',"",1);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'PNG to PNG, 150 pixels wide',$result[0],$result[1]);

					$imageProc->IM_commands=array();
					$theFile = t3lib_extMgm::extPath('install').'imgs/jesus2_transp.gif';
					if (!@is_file($theFile))	die('Error: '.$theFile.' was not a file');
					$imageProc->imageMagickConvert_forceFileNameBody='scale_jpg';
					$fileInfo = $imageProc->imageMagickConvert($theFile,'jpg',"150",'',"",'',"",1);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'GIF to JPG, 150 pixels wide',$result[0],$result[1]);
				} else {
					$this->message($headCode,'Test skipped',"
					Use of ImageMagick has been disabled in the configuration.
					Refer to section 'Basic Configuration' to change or review you configuration settings
					",2);
				}
			break;
			case 'combining':
				$refParseTime='150';	// 4.2.9
				$refParseTime='250';	// 5.2.3
									// Combine
				$headCode = 'Combining images';
				$this->message($headCode,'Combining images',"
				This verifies that the ImageMagick tool, 'combine'/'composite', is able to combine two images through a grayscale mask.<br />
				If the masking seems to work but inverted, that just means you'll have to make sure the invert flag is set (some combination of im_negate_mask/im_imvMaskState)
				");

				if ($imActive)	{
					$imageProc->IM_commands=array();
					$input = t3lib_extMgm::extPath('install').'imgs/greenback.gif';
					$overlay = t3lib_extMgm::extPath('install').'imgs/jesus.jpg';
					$mask = t3lib_extMgm::extPath('install').'imgs/blackwhite_mask.gif';
						if (!@is_file($input))	die('Error: '.$input.' was not a file');
						if (!@is_file($overlay))	die('Error: '.$overlay.' was not a file');
						if (!@is_file($mask))	die('Error: '.$mask.' was not a file');

					$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5($imageProc->alternativeOutputKey.'combine1').'.jpg';
					$imageProc->combineExec($input,$overlay,$mask,$output, true);
					$fileInfo = $imageProc->getImageDimensions($output);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Combine using a GIF mask with only black and white',$result[0],$result[1]);

					// Combine
					$imageProc->IM_commands=array();
					$input = t3lib_extMgm::extPath('install').'imgs/combine_back.jpg';
					$overlay = t3lib_extMgm::extPath('install').'imgs/jesus.jpg';
					$mask = t3lib_extMgm::extPath('install').'imgs/combine_mask.jpg';
						if (!@is_file($input))	die('Error: '.$input.' was not a file');
						if (!@is_file($overlay))	die('Error: '.$overlay.' was not a file');
						if (!@is_file($mask))	die('Error: '.$mask.' was not a file');

					$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5($imageProc->alternativeOutputKey.'combine2').'.jpg';
					$imageProc->combineExec($input,$overlay,$mask,$output, true);
					$fileInfo = $imageProc->getImageDimensions($output);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Combine using a JPG mask with graylevels',$result[0],$result[1]);
				} else {
					$this->message($headCode,'Test skipped',"
					Use of ImageMagick has been disabled in the configuration.
					Refer to section 'Basic Configuration' to change or review you configuration settings
					",2);
				}
			break;
			case 'gdlib':
				$refParseTime='1800';	// GIF / 4.2.9 / LZW (5.2.3)
				$refParseTime='2700';	// PNG / 4.2.9 / LZW (5.2.3)
				$refParseTime='1600';	// GIF / 5.2.3 / LZW (5.2.3)
					// GDLibrary
				$headCode = 'GDLib';
				$this->message($headCode,'Testing GDLib','
				This verifies that the GDLib installation works properly.
				');


				if ($gdActive)	{
					// GD with box
					$imageProc->IM_commands=array();
					$im = $imageProc->imageCreate(170,136);
					$Bcolor = ImageColorAllocate ($im, 0, 0, 0);
					ImageFilledRectangle($im, 0, 0, 170, 136, $Bcolor);
					$workArea=array(0,0,170,136);
					$conf=array(
						'dimensions' => '10,50,150,36',
						'color' => 'olive'
					);
					$imageProc->makeBox($im,$conf,$workArea);
					$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5('GDbox').'.'.$imageProc->gifExtension;
					$imageProc->ImageWrite($im,$output);
					$fileInfo = $imageProc->getImageDimensions($output);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Create simple image',$result[0],$result[1]);


					// GD from image with box
					$imageProc->IM_commands=array();
					$input = t3lib_extMgm::extPath('install').'imgs/jesus.'.$imageProc->gifExtension;
						if (!@is_file($input))	die('Error: '.$input.' was not a file');
					$im = $imageProc->imageCreateFromFile($input);
					$workArea=array(0,0,170,136);
					$conf=array();
					$conf['dimensions']='10,50,150,36';
					$conf['color']='olive';
					$imageProc->makeBox($im,$conf,$workArea);
					$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5('GDfromImage+box').'.'.$imageProc->gifExtension;
					$imageProc->ImageWrite($im,$output);
					$fileInfo = $imageProc->getImageDimensions($output);
					$GDWithBox_filesize = @filesize($output);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Create image from file',$result[0],$result[1]);


					// GD with text
					$imageProc->IM_commands=array();
					$im = $imageProc->imageCreate(170,136);
					$Bcolor = ImageColorAllocate ($im, 128,128,150);
					ImageFilledRectangle($im, 0, 0, 170, 136, $Bcolor);
					$workArea=array(0,0,170,136);
					$conf=array(
						'iterations' => 1,
						'angle' => 0,
						'antiAlias' => 1,
						'text' => 'HELLO WORLD',
						'fontColor' => '#003366',
						'fontSize' => 18,
						'fontFile' => $this->backPath.'../t3lib/fonts/vera.ttf',
						'offset' => '17,40'
					);
					$conf['BBOX'] = $imageProc->calcBBox($conf);
					$imageProc->makeText($im,$conf,$workArea);

					$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5('GDwithText').'.'.$imageProc->gifExtension;
					$imageProc->ImageWrite($im,$output);
					$fileInfo = $imageProc->getImageDimensions($output);
					$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands);
					$this->message($headCode,'Render text with TrueType font',$result[0],$result[1]);

					if ($imActive)	{
							// extension: GD with text, niceText
						$conf['offset'] = '17,65';
						$conf['niceText'] = 1;
						$imageProc->makeText($im,$conf,$workArea);

						$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5('GDwithText-niceText').'.'.$imageProc->gifExtension;
						$imageProc->ImageWrite($im,$output);
						$fileInfo = $imageProc->getImageDimensions($output);
						$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands, array("Note on 'niceText':","'niceText' is a concept that tries to improve the antialiasing of the rendered type by actually rendering the textstring in double size on a black/white mask, downscaling the mask and masking the text onto the image through this mask. This involves ImageMagick 'combine'/'composite' and 'convert'."));
						$this->message($headCode,"Render text with TrueType font using 'niceText' option",
							"(If the image has another background color than the image above (eg. dark background color with light text) then you will have to set TYPO3_CONF_VARS[GFX][im_imvMaskState]=1)<br /><br />".
							$result[0],$result[1]);
					} else {
						$this->message($headCode,"Render text with TrueType font using 'niceText' option","
						<strong>Test is skipped!</strong><br /><br />

						Use of ImageMagick has been disabled in the configuration. ImageMagick is needed to generate text with the niceText option.
						Refer to section 'Basic Configuration' to change or review you configuration settings
						",2);
					}

					if ($imActive)	{
							// extension: GD with text, niceText AND shadow
						$conf['offset'] = '17,90';
						$conf['niceText'] = 1;
						$conf['shadow.'] = array(
							'offset'=>'2,2',
							'blur' => $imageProc->V5_EFFECTS?"20":"90",
							'opacity' => '50',
							'color' => 'black'
						);
						$imageProc->makeShadow($im,$conf['shadow.'],$workArea,$conf);
						$imageProc->makeText($im,$conf,$workArea);

						$output = $imageProc->tempPath.$imageProc->filenamePrefix.t3lib_div::shortMD5('GDwithText-niceText-shadow').'.'.$imageProc->gifExtension;
						$imageProc->ImageWrite($im,$output);
						$fileInfo = $imageProc->getImageDimensions($output);
						$result = $this->displayTwinImage($fileInfo[3],$imageProc->IM_commands, array('Note on drop shadows:','Drop shadows are done by using ImageMagick to blur a mask through which the drop shadow is generated. The blurring of the mask only works in ImageMagick 4.2.9 and <i>not</i> ImageMagick 5 - which is why you may see a hard and not soft shadow.'));
						$this->message($headCode,"Render 'niceText' with a shadow under",
							"(This test makes sense only if the above test had a correct output. But if so, you may not see a soft dropshadow from the third text string as you should. In that case you are most likely using ImageMagick 5 and should set the flag TYPO3_CONF_VARS[GFX][im_v5effects]. However this may cost server performance!<br />Finally if you see no drop shadow at all or if the shadow is still not soft, then check if you are using GDlib2 and if so set TYPO3_CONF_VARS[GFX][gdlib_2]=1)<br /><br />".
							$result[0],$result[1]);
					} else {
						$this->message($headCode,"Render 'niceText' with a shadow under","
						<strong>Test is skipped!</strong><br /><br />

						Use of ImageMagick has been disabled in the configuration. ImageMagick is needed to generate shadows.
						Refer to section 'Basic Configuration' to change or review you configuration settings
						",2);
					}

					if ($imageProc->gifExtension=='gif')	{
						$buffer=20;
						$assess = "This assessment is based on the filesize from 'Create image from file' test, which were ".$GDWithBox_filesize.' bytes';
						$goodNews = "If the image was LZW compressed you would expect to have a size of less than 9000 bytes. If you open the image with Photoshop and saves it from Photoshop, you'll a filesize like that.<br />The good news is (hopefully) that your [GFX][im_path_lzw] path is correctly set so the gif_compress() function will take care of the compression for you!";
						if ($GDWithBox_filesize<8784+$buffer)	{
							$msg="<strong>Your GDLib appears to have LZW compression!</strong><br />
								This assessment is based on the filesize from 'Create image from file' test, which were ".$GDWithBox_filesize." bytes.<br />
								This is a real advantage for you because you don't need to use ImageMagick for LZW compressing. In order to make sure that GDLib is used, <strong>please set the config option [GFX][im_path_lzw] to an empty string!</strong><br />
								When you disable the use of ImageMagick for LZW compressing, you'll see that the gif_compress() function has a return code of 'GD' (for GDLib) instead of 'IM' (for ImageMagick)
								";
						} elseif ($GDWithBox_filesize>19000)	{
							$msg='<strong>Your GDLib appears to have no compression at all!</strong><br />
								'.$assess.'<br />'.$goodNews;
						} else {
							$msg='Your GDLib appears to have RLE compression<br />
								'.$assess.'<br />'.$goodNews;
						}
						$this->message($headCode,'GIF compressing in GDLib',"
						".$msg."
						",1);
					}

				} else {
					$this->message($headCode,'Test skipped',"
					Use of GDLib has been disabled in the configuration.
					Refer to section 'Basic Configuration' to change or review you configuration settings
					",2);
				}
			break;
		}

		if ($this->INSTALL['images_type'])	{
			// End info
			if ($this->fatalError)	{
				$this->message('Info','Errors',"
				It seems that you had some fatal errors in this test. Please make sure that your ImageMagick and GDLib settings are correct.
				Refer to the 'Basic Configuration' section for more information and debugging of your settings.
				");
			}

			$parseMS = t3lib_div::milliseconds() - $parseStart;
			$this->message('Info','Parsetime',$parseMS.' ms');
		}
		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$ext: ...
	 * @param	[type]		$headCode: ...
	 * @param	[type]		$short: ...
	 * @return	[type]		...
	 */
	function isExtensionEnabled($ext, $headCode, $short)	{
		if (!t3lib_div::inList($GLOBALS['TYPO3_CONF_VARS']['GFX']['imagefile_ext'],$ext))	{
			$this->message($headCode,$short,'Skipped - extension not in the list of allowed extensions ([GFX][imagefile_ext]).',1);
		} else {
			return 1;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$imageFile: ...
	 * @param	[type]		$IMcommands: ...
	 * @param	[type]		$note: ...
	 * @return	[type]		...
	 */
	function displayTwinImage ($imageFile, $IMcommands=array(), $note='')	{
		$ex_rows='';
		$errorLevels=array(-1);
		if ($imageFile)	{
			$verifyFile = t3lib_extMgm::extPath('install').'verify_imgs/'.basename($imageFile);
#debug(array($imageFile,$this->backPath.'../'.substr($imageFile,strlen(PATH_site))),1);
			$destImg = @getImageSize($imageFile);
			$destImgCode ='<img src="'.$this->backPath.'../'.substr($imageFile,strlen(PATH_site)).'" '.$destImg[3].'>';
			$verifyImg = @getImageSize($verifyFile);
			$verifyImgCode = '<img src="'.$this->backPath.t3lib_extMgm::extRelPath('install').'verify_imgs/'.basename($verifyFile).'" '.$verifyImg[3].'>';
			if (!$verifyImg)	{
				$gifVersion=1;
				$verifyFile_alt = substr($verifyFile,0,-3).'gif';
				$verifyImg = @getImageSize($verifyFile_alt);
				if ($verifyImg)	{
// FIXME what is that? old code? t3lib/install/verify_imgs/ do not exist
					$verifyImgCode = '<img src="'.$this->backPath.'t3lib/install/verify_imgs/'.basename($verifyFile_alt).'" '.$verifyImg[3].'>';
					$verifyImgCode= '<table border="0" cellpadding="4" cellspacing="0" bgcolor="red"><tr><td align="center">'.$verifyImgCode.'<br />'.$this->fw('<strong>NO REFERENCE FOUND!</strong><br /><br />GIF version looks like this.').'</td></tr></table>';
				} else {
					$verifyImgCode= '<table border="0" cellpadding="4" cellspacing="0" bgcolor="red"><tr><td align="center">'.$this->fw('<strong>NO REFERENCE FOUND!</strong><br /><br />'.basename($verifyFile)).'</td></tr></table>';
				}
			}

			clearstatcache();
			$destImg['filesize'] = @filesize($imageFile);
			clearstatcache();
			$verifyImg['filesize'] = @filesize($verifyFile);

			$ex_rows.='<tr>';
			$ex_rows.='<td>'.$destImgCode.'</td>';
			$ex_rows.='<td><img src="clear.gif" width="30" height="1"></td>';
			$ex_rows.='<td>'.$verifyImgCode.'</td>';
			$ex_rows.='</tr>';

			$ex_rows.=$this->getTwinImageMessage('', 'Your server:', 'Reference:');
			$ex_rows.=$this->getTwinImageMessage('', t3lib_div::formatSize($destImg['filesize']).', '.$destImg[0].'x'.$destImg[1].' pixels', t3lib_div::formatSize($verifyImg['filesize']).', '.$verifyImg[0].'x'.$verifyImg[1].' pixels');

			if (($destImg['filesize']!=$verifyImg['filesize']) && (intval($destImg['filesize']) && ($destImg['filesize']-$verifyImg['filesize']) > 2048))	{	// Display a warning if the generated image is more than 2KB larger than its reference...
				$ex_rows.=$this->getTwinImageMessage('File size is very different from reference', $destImg['filesize'], $verifyImg['filesize']);
				$errorLevels[]=2;
			}
			if ($destImg[0]!=$verifyImg[0] || $destImg[1]!=$verifyImg[1])	{
				$ex_rows.=$this->getTwinImageMessage('Pixel dimension are not equal!');
				$errorLevels[]=2;
			}
			if ($note)	{
				$ex_rows.=$this->getTwinImageMessage($note[0],$note[1]);
			}
			if ($this->dumpImCommands && count($IMcommands))	{
				$ex_rows.=$this->getTwinImageMessage('ImageMagick commands executed:',$this->formatImCmds($IMcommands));
			}
		} else {
			$ex_rows.=$this->getTwinImageMessage('There was no result from the ImageMagick operation', "Below there's a dump of the ImageMagick commands executed:<br />".$this->formatImCmds($IMcommands));
			$errorLevels[]=3;
		}
		$out='<table border="0" cellpadding="0" cellspacing="0" align="center" width="300">'.$ex_rows.'</table>';

		return array($out,max($errorLevels));
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$message: ...
	 * @param	[type]		$label_1: ...
	 * @param	[type]		$label_2: ...
	 * @return	[type]		...
	 */
	function getTwinImageMessage($message, $label_1="", $label_2='')	{
		if ($message)	$out.='<tr><td colspan="3"><strong>'.$this->fw($message).'</strong></td></tr>';
		if ($label_1 && !$label_2)	{
			$out.='<tr><td colspan="3">'.$this->fw($label_1).'</td></tr>';
		} elseif ($label_1 || $label_2)	{
			$out.='<tr><td>'.$this->fw($label_1).'</td><td></td><td>'.$this->fw($label_2).'</td></tr>';
		}
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function formatImCmds($arr)	{
		$out=array();
		if (is_array($arr))	{
			reset($arr);
			while(list($k,$v)=each($arr))	{
				$out[]=$v[1];
				if ($v[2])	$out[]='   RETURNED: '.$v[2];
			}
		}
		if (count($out))	{
			$col = t3lib_div::intInRange(count($out),2,10);
			$outputStr = '<textarea cols=40 rows='.$col.' wrap="off" class="fixed-font">'.htmlspecialchars(implode($out,chr(10))).'</textarea>';
			return '<form action="">'.$outputStr.'</form>';
		};
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function imagemenu()	{
		$menuitems = array(
			'read' => 'Reading image formats',
			'write' => 'Writing GIF and PNG',
			'scaling' => 'Scaling images',
			'combining' => 'Combining images',
			'gdlib' => 'GD library functions'
		);
		reset($menuitems);
		$c=0;
		$out=array();
		while(list($k,$v)=each($menuitems))	{
			$bgcolor = ($this->INSTALL['images_type']==$k ? ' class="activeMenu"' : ' class="generalTableBackground"');
			$c++;
			$out[]='<tr><td'.$bgcolor.'><a href="'.htmlspecialchars($this->action.'&TYPO3_INSTALL[images_type]='.$k.'#testmenu').'">'.$this->fw($c.': '.$v).'</a></td></tr>';
		}

		$code = '<table border="0" cellpadding="0" cellspacing="1">'.implode($out,'').'</table>';
		$code = '<table border="0" cellpadding="0" cellspacing="0" id="imageMenu"><tr><td>'.$code.'</td></tr></table>';
		return '<div align="center">'.$code.'</div>';
	}












	/**********************
	 *
	 * DATABASE analysing
	 *
	 **********************/

	/**
	 * @return	[type]		...
	 */
	function checkTheDatabase()	{
		if (!$this->config_array['mysqlConnect'])	{
			$this->message('Database Analyser','Your database connection failed',"
				Please go to the 'Basic Configuration' section and correct this problem first.
			",2);
			$this->output($this->outputWrapper($this->printAll()));
			return;
		}
		if ($this->config_array['no_database'])	{
			$this->message('Database Analyser','No database selected',"
				Please go to the 'Basic Configuration' section and correct this problem first.
			",2);
			$this->output($this->outputWrapper($this->printAll()));
			return;
		}

			// Getting current tables
		$whichTables=$this->getListOfTables();


			// Getting number of static_template records
		if ($whichTables['static_template'])	{
			$static_template_count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'static_template');
		}
		$static_template_count=intval($static_template_count);

		$headCode ='Database Analyser';
		$this->message($headCode,'What is it?',"
			In this section you can get an overview of your currently selected database compared to sql-files. You can also import sql-data directly into the database or upgrade tables from earlier versions of TYPO3.
		",0);

		$cInfo='
			Username: <strong>' . htmlspecialchars(TYPO3_db_username) . '</strong>
			Host: <strong>' . htmlspecialchars(TYPO3_db_host) . '</strong>
		';
		$this->message($headCode, 'Connected to SQL database successfully',"
		".trim($cInfo)."
		",-1,1);
		$this->message($headCode, 'Database',"
			<strong>" . htmlspecialchars(TYPO3_db) . '</strong> is selected as database.
			Has <strong>'.count($whichTables)."</strong> tables.
		",-1,1);


			// Menu
		$this->messageFunc_nl2br = 0;

		$sql_files = array_merge(
			t3lib_div::getFilesInDir(PATH_typo3conf,'sql',1,1),
			array()
		);

		$action_type = $this->INSTALL['database_type'];
		$actionParts = explode('|',$action_type);
		if (count($actionParts)<2)	{
			$action_type='';
		}

		$out='';
		$out.='<tr>
				<td nowrap="nowrap"><strong>'.$this->fw('Update required tables').'</strong></td>
				<td' . ($action_type == 'cmpFile|CURRENT_TABLES' ? ' class="notice"' : '') . '>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=cmpFile|CURRENT_TABLES#bottom') . '"><strong>COMPARE</strong></a>') . '</td>
				<td>'.$this->fw('&nbsp;').'</td>
				<td>'.$this->fw('&nbsp;').'</td>
			</tr>';

		$out.='<tr>
				<td nowrap="nowrap"><strong>'.$this->fw('Dump static data').'</strong></td>
				<td>'.$this->fw('&nbsp;').'</td>
				<td nowrap="nowrap"' . ($action_type == 'import|CURRENT_STATIC' ? ' class="notice"' : '').'>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=import|CURRENT_STATIC#bottom') . '"><strong>IMPORT</strong></a>') . '</td>
				<td>'.$this->fw('&nbsp;').'</td>
			</tr>';

		$out.='<tr>
				<td colspan="4">&nbsp;</td>
			</tr>';


		reset($sql_files);
		$directJump='';
		while(list($k,$file)=each($sql_files))	{
			if ($this->mode=="123" && !count($whichTables) && strstr($file,'_testsite'))	{
				$directJump = $this->action.'&TYPO3_INSTALL[database_type]=import|'.rawurlencode($file);
			}
			$lf=t3lib_div::testInt($k);
			$fShortName = substr($file,strlen(PATH_site));

			$spec1 = $spec2 = '';

			$out.='<tr>
				<td nowrap="nowrap">'.$this->fw($fShortName.' ('.t3lib_div::formatSize(filesize($file)).')').'</td>
				<td' . ($action_type == 'cmpFile|' . $file ? ' class="notice"' : '') . '>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=cmpFile|' . rawurlencode($file) . '#bottom') . '"><strong>COMPARE</strong></a>') . '</td>
				<td nowrap="nowrap"' . ($action_type == 'import|' . $file ? ' class="notice"' : '') . '>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=import|' . rawurlencode($file) . '#bottom') . '"><strong>IMPORT' . $spec1 . $spec2 . '</strong></a>') . '</td>
				<td nowrap="nowrap"' . ($action_type == 'view|' . $file ? ' class="notice"' : '') . '>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=view|' . rawurlencode($file) . '#bottom') . '"><strong>VIEW' . $spec1 . $spec2 . '</strong></a>') . '</td>
			</tr>';
		}
			// TCA
		$out.='<tr>
			<td></td>
			<td colspan="3"' . ($action_type == "cmpTCA|" ? ' class="notice"' : '') . '><strong>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=cmpTCA|#bottom') . '">Compare with $TCA</a>') . '</strong></td>
		</tr>';
		$out.='<tr>
			<td></td>
			<td colspan="3"' . ($action_type == "adminUser|" ? ' class="notice"' : '') . '><strong>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=adminUser|#bottom') . '">Create "admin" user</a>') . '</strong></td>
		</tr>';
		$out.='<tr>
			<td></td>
			<td colspan="3"' . ($action_type == "UC|" ? ' class="notice"' : '') . '><strong>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=UC|#bottom') . '">Reset user preferences</a>') . '</strong></td>
		</tr>';
		$out.='<tr>
			<td></td>
			<td colspan="3"' . ($action_type == "cache|" ? ' class="notice"' : '') . '><strong>' . $this->fw('<a href="' . htmlspecialchars($this->action . '&TYPO3_INSTALL[database_type]=cache|#bottom') . '">Clear tables</a>') . '</strong></td>
		</tr>';

		$theForm='<table border="0" cellpadding="2" cellspacing="2">'.$out.'</table>';
		$theForm.='<a name="bottom"></a>';

		if ($directJump)	{
			if (!$action_type)	{
				$this->message($headCode, 'Menu','
				<script language="javascript" type="text/javascript">
				window.location.href = "'.$directJump.'";
				</script>',0,1);
			}
		} else {
			$this->message($headCode, 'Menu',"
			From this menu you can select which of the available SQL files you want to either compare or import/merge with the existing database.<br /><br />
			<strong>COMPARE:</strong> Compares the tables and fields of the current database and the selected file. It also offers to 'update' the difference found.<br />
			<strong>IMPORT:</strong> Imports the SQL-dump file into the current database. You can either dump the raw file or choose which tables to import. In any case, you'll see a new screen where you must confirm the operation.<br />
			<strong>VIEW:</strong> Shows the content of the SQL-file, limiting characters on a single line to a reader-friendly amount.<br /><br />
			The SQL-files are selected from typo3conf/ (here you can put your own) and t3lib/stddb/ (TYPO3 distribution). The SQL-files should be made by the <em>mysqldump</em> tool or at least be formatted like that tool would do.
	<br />
			<br />
			".$theForm."
			",0,1);
		}


		if ($action_type)	{
			switch($actionParts[0])	{
				case 'cmpFile':
					$tblFileContent='';
					if (!strcmp($actionParts[1],'CURRENT_TABLES')) {
						$tblFileContent = t3lib_div::getUrl(PATH_t3lib.'stddb/tables.sql');

						foreach ($GLOBALS['TYPO3_LOADED_EXT'] as $loadedExtConf) {
							if (is_array($loadedExtConf) && $loadedExtConf['ext_tables.sql'])	{
								$tblFileContent.= chr(10).chr(10).chr(10).chr(10).t3lib_div::getUrl($loadedExtConf['ext_tables.sql']);
							}
						}
					} elseif (@is_file($actionParts[1]))	{
						$tblFileContent = t3lib_div::getUrl($actionParts[1]);
					}
					if ($tblFileContent)	{
						$fileContent = implode(
							chr(10),
							$this->getStatementArray($tblFileContent,1,'^CREATE TABLE ')
						);
						$FDfile = $this->getFieldDefinitions_fileContent($fileContent);
						if (!count($FDfile))	{
							die ("Error: There were no 'CREATE TABLE' definitions in the provided file");
						}

							// Updating database...
						if (is_array($this->INSTALL['database_update']))	{
							$FDdb = $this->getFieldDefinitions_database();
							$diff = $this->getDatabaseExtra($FDfile, $FDdb);
							$update_statements = $this->getUpdateSuggestions($diff);
							$diff = $this->getDatabaseExtra($FDdb, $FDfile);
							$remove_statements = $this->getUpdateSuggestions($diff,'remove');

							$this->performUpdateQueries($update_statements['clear_table'],$this->INSTALL['database_update']);

							$this->performUpdateQueries($update_statements['add'],$this->INSTALL['database_update']);
							$this->performUpdateQueries($update_statements['change'],$this->INSTALL['database_update']);
							$this->performUpdateQueries($remove_statements['change'],$this->INSTALL['database_update']);
							$this->performUpdateQueries($remove_statements['drop'],$this->INSTALL['database_update']);

							$this->performUpdateQueries($update_statements['create_table'],$this->INSTALL['database_update']);
							$this->performUpdateQueries($remove_statements['change_table'],$this->INSTALL['database_update']);
							$this->performUpdateQueries($remove_statements['drop_table'],$this->INSTALL['database_update']);
						}

							// Init again / first time depending...
						$FDdb = $this->getFieldDefinitions_database();

						$diff = $this->getDatabaseExtra($FDfile, $FDdb);
						$update_statements = $this->getUpdateSuggestions($diff);

						$diff = $this->getDatabaseExtra($FDdb, $FDfile);
						$remove_statements = $this->getUpdateSuggestions($diff,'remove');

						$tLabel = 'Update database tables and fields';

						if ($remove_statements || $update_statements)	{
							$formContent = $this->generateUpdateDatabaseForm('get_form',$update_statements,$remove_statements,$action_type);
							$this->message($tLabel,'Table and field definitions should be updated',"
							There seems to be a number of differencies between the database and the selected SQL-file.
							Please select which statements you want to execute in order to update your database:<br /><br />
							".$formContent."
							",2);
						} else {
							$formContent = $this->generateUpdateDatabaseForm('get_form',$update_statements,$remove_statements,$action_type);
							$this->message($tLabel,'Table and field definitions are OK.',"
							The tables and fields in the current database corresponds perfectly to the database in the selected SQL-file.
							",-1);
						}
					}
				break;
				case 'cmpTCA':
					$this->includeTCA();
					$FDdb = $this->getFieldDefinitions_database();

						// Displaying configured fields which are not in the database
					$tLabel='Tables and fields in $TCA, but not in database';
					$cmpTCA_DB = $this->compareTCAandDatabase($GLOBALS['TCA'],$FDdb);
					if (!count($cmpTCA_DB['extra']))	{
						$this->message($tLabel,'Table and field definitions OK','
						All fields and tables configured in $TCA appeared to exist in the database as well
						',-1);
					} else {
						$this->message($tLabel,'Invalid table and field definitions in $TCA!','
						There are some tables and/or fields configured in the $TCA array which do not exist in the database!
						This will most likely cause you trouble with the TYPO3 backend interface!
						',3);
						while(list($tableName, $conf)=each($cmpTCA_DB['extra']))	{
							$this->message($tLabel, $tableName,$this->displayFields($conf['fields'],0,'Suggested database field:'),2);
						}
					}

						// Displaying tables that are not setup in
					$cmpDB_TCA = $this->compareDatabaseAndTCA($FDdb,$GLOBALS['TCA']);
					$excludeTables='be_sessions,fe_session_data,fe_sessions';
					if (TYPO3_OS=='WIN')	{$excludeTables = strtolower($excludeTables);}
					$excludeFields = array(
						'be_users' => 'uc,lastlogin,usergroup_cached_list',
						'fe_users' => 'uc,lastlogin,fe_cruser_id',
						'pages' => 'SYS_LASTCHANGED',
						'sys_dmail' => 'mailContent',
						'tt_board' => 'doublePostCheck',
						'tt_guest' => 'doublePostCheck',
						'tt_products' => 'ordered'
					);
					$tCount=0;
					$fCount=0;
					$tLabel="Tables from database, but not in \$TCA";
					$fLabel="Fields from database, but not in \$TCA";
					$this->message($tLabel);
					if (is_array($cmpDB_TCA['extra']))	{
						while(list($tableName, $conf)=each($cmpDB_TCA['extra']))	{
							if (!t3lib_div::inList($excludeTables,$tableName)
									&& substr($tableName,0,4)!="sys_"
									&& substr($tableName,-3)!="_mm"
									&& substr($tableName,0,6)!="index_"
									&& substr($tableName,0,6)!='cache_')	{
								if ($conf['whole_table'])	{
									$this->message($tLabel, $tableName,$this->displayFields($conf['fields']),1);
									$tCount++;
								} else {
									list($theContent, $fC)	= $this->displaySuggestions($conf['fields'],$excludeFields[$tableName]);
									$fCount+=$fC;
									if ($fC)	$this->message($fLabel, $tableName,$theContent,1);
								}
							}
						}
					}
					if (!$tCount)	{
						$this->message($tLabel,'Correct number of tables in the database',"
						There are no extra tables in the database compared to the configured tables in the \$TCA array.
						",-1);
					} else {
						$this->message($tLabel,'Extra tables in the database',"
						There are some tables in the database which are not configured in the \$TCA array.
						You should probably not worry about this, but please make sure that you know what these tables are about and why they are not configured in \$TCA.
						",2);
					}

					if (!$fCount)	{
						$this->message($fLabel,'Correct number of fields in the database',"
						There are no additional fields in the database tables compared to the configured fields in the \$TCA array.
						",-1);
					} else {
						$this->message($fLabel,'Extra fields in the database',"
						There are some additional fields the database tables which are not configured in the \$TCA array.
						You should probably not worry about this, but please make sure that you know what these fields are about and why they are not configured in \$TCA.
						",2);
					}

						// Displaying actual and suggested field database defitions
					if (is_array($cmpTCA_DB['matching']))	{
						$tLabel="Comparison between database and \$TCA";

						$this->message($tLabel,'Actual and suggested field definitions',"
						This table shows you the suggested field definitions which are calculated based on the configuration in \$TCA.
						If the suggested value differs from the actual current database value, you should not panic, but simply check if the datatype of that field is sufficient compared to the data, you want TYPO3 to put there.
						",0);
						while(list($tableName, $conf)=each($cmpTCA_DB['matching']))	{
							$this->message($tLabel, $tableName,$this->displayFieldComp($conf['fields'], $FDdb[$tableName]['fields']),1);
						}
					}
				break;
				case 'import':
					$mode123Imported=0;
					$tblFileContent='';
					if (preg_match('/^CURRENT_/', $actionParts[1]))	{
						if (!strcmp($actionParts[1],'CURRENT_TABLES') || !strcmp($actionParts[1],'CURRENT_TABLES+STATIC'))	{
							$tblFileContent = t3lib_div::getUrl(PATH_t3lib.'stddb/tables.sql');

							reset($GLOBALS['TYPO3_LOADED_EXT']);
							while(list(,$loadedExtConf)=each($GLOBALS['TYPO3_LOADED_EXT']))	{
								if (is_array($loadedExtConf) && $loadedExtConf['ext_tables.sql'])	{
									$tblFileContent.= chr(10).chr(10).chr(10).chr(10).t3lib_div::getUrl($loadedExtConf['ext_tables.sql']);
								}
							}
						}
						if (!strcmp($actionParts[1],'CURRENT_STATIC') || !strcmp($actionParts[1],'CURRENT_TABLES+STATIC'))	{
							reset($GLOBALS['TYPO3_LOADED_EXT']);
							while(list(,$loadedExtConf)=each($GLOBALS['TYPO3_LOADED_EXT']))	{
								if (is_array($loadedExtConf) && $loadedExtConf['ext_tables_static+adt.sql'])	{
									$tblFileContent.= chr(10).chr(10).chr(10).chr(10).t3lib_div::getUrl($loadedExtConf['ext_tables_static+adt.sql']);
								}
							}
						}
					} elseif (@is_file($actionParts[1]))	{
						$tblFileContent = t3lib_div::getUrl($actionParts[1]);
					}

					if ($tblFileContent)	{
						$tLabel='Import SQL dump';
							// Getting statement array from
						$statements = $this->getStatementArray($tblFileContent,1);
						list($statements_table, $insertCount) = $this->getCreateTables($statements,1);

							// Updating database...
						if ($this->INSTALL['database_import_all'])	{
							$r=0;
							foreach ($statements as $k=>$v)	{
								$res = $GLOBALS['TYPO3_DB']->admin_query($v);
								$r++;
							}

								// Make a database comparison because some tables that are defined twice have not been created at this point. This applies to the "pages.*" fields defined in sysext/cms/ext_tables.sql for example.
							$fileContent = implode(
								$this->getStatementArray($tblFileContent,1,'^CREATE TABLE '),
								chr(10)
							);
							$FDfile = $this->getFieldDefinitions_fileContent($fileContent);
							$FDdb = $this->getFieldDefinitions_database();
							$diff = $this->getDatabaseExtra($FDfile, $FDdb);
							$update_statements = $this->getUpdateSuggestions($diff);
							if (is_array($update_statements['add']))	{
								foreach ($update_statements['add'] as $statement)	{
									$res = $GLOBALS['TYPO3_DB']->admin_query($statement);
								}
							}

							if ($this->mode=='123')	{
									// Create default be_user admin/password
								$username = 'admin';
								$pass = 'password';

								$count = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows(
									'uid',
									'be_users',
									'username=' . $GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'be_users')
								);
								if (!$count) {
									$insertFields = array(
										'username' => $username,
										'password' => md5($pass),
										'admin' => 1,
										'uc' => '',
										'fileoper_perms' => 0,
										'tstamp' => $GLOBALS['EXEC_TIME'],
										'crdate' => $GLOBALS['EXEC_TIME']
									);

									$GLOBALS['TYPO3_DB']->exec_INSERTquery('be_users', $insertFields);
								}
							}

							$this->message($tLabel,'Imported ALL',"
								Queries: ".$r."
							",1,1);
							if (t3lib_div::_GP('goto_step'))	{
								$this->action.='&step='.t3lib_div::_GP('goto_step');
								Header('Location: '.t3lib_div::locationHeaderUrl($this->action));
								exit;
							}
						} elseif (is_array($this->INSTALL['database_import']))	{
								// Traverse the tables
							reset($this->INSTALL['database_import']);
							while(list($table,$md5str)=each($this->INSTALL['database_import']))	{
								if ($md5str==md5($statements_table[$table]))	{
									$res = $GLOBALS['TYPO3_DB']->admin_query('DROP TABLE IF EXISTS '.$table);
									$res = $GLOBALS['TYPO3_DB']->admin_query($statements_table[$table]);

									if ($insertCount[$table])	{
										$statements_insert = $this->getTableInsertStatements($statements, $table);
										reset($statements_insert);
										while(list($k,$v)=each($statements_insert))	{
											$res = $GLOBALS['TYPO3_DB']->admin_query($v);
										}
									}

									$this->message($tLabel,"Imported '".$table."'","
										Rows: ".$insertCount[$table]."
									",1,1);
								}
							}
						}

						$mode123Imported=$this->isBasicComplete($tLabel);

						if (!$mode123Imported)	{
								// Re-Getting current tables - may have been changed during import
							$whichTables=$this->getListOfTables();

							if (count($statements_table))	{
								reset($statements_table);
								$out='';
								while(list($table,$definition)=each($statements_table))	{
									$exist=isset($whichTables[$table]);
									$out.='<tr>
										<td><input type="checkbox" name="TYPO3_INSTALL[database_import]['.$table.']" id="database_import_'.$table.'" value="'.md5($definition).'"></td>
										<td><label for="database_import_'.$table.'"><strong>'.$this->fw($table).'</strong></label></td>
										<td><img src="clear.gif" width="10" height="1"></td>
										<td nowrap="nowrap">'.$this->fw($insertCount[$table]?"Rows: ".$insertCount[$table]:"").'</td>
										<td><img src="clear.gif" width="10" height="1"></td>
										<td nowrap="nowrap">'.($exist?'<img src="'.$this->backPath.'gfx/icon_warning.gif" width="18" height="16" align="top" alt="">'.$this->fw('Table exists!'):'').'</td>
										</tr>';
								}

								$content ='';
								if ($this->mode!='123')	{
									$content.='<table border="0" cellpadding="0" cellspacing="0">'.$out.'</table>
									<hr />
									';
								}
								$content.='<input type="checkbox" name="TYPO3_INSTALL[database_import_all]" id="database_import_all" value="1"'.($this->mode=="123"||t3lib_div::_GP('presetWholeTable')?' checked="checked"':'').'> <label for="database_import_all">'.$this->fw("Import the whole file '".basename($actionParts[1])."' directly (ignores selections above)").'</label><br />

								';
								$form = $this->getUpdateDbFormWrap($action_type, $content);
								$this->message($tLabel,'Select tables to import',"
								This is an overview of the CREATE TABLE definitions in the SQL file.
								Select which tables you want to dump to the database.
								Any table you choose dump to the database is dropped from the database first, so you'll lose all data in existing tables.
								".$form,1,1);
							} else {
								$this->message($tLabel,'No tables',"
								There seems to be no CREATE TABLE definitions in the SQL file.
								This tool is intelligently creating one table at a time and not just dumping the whole content of the file uncritically. That's why there must be defined tables in the SQL file.
								",3,1);
							}
						}
					}
				break;
				case 'view':
					if (@is_file($actionParts[1])) {
						$tLabel = 'Import SQL dump';
							// Getting statement array from
						$fileContent = t3lib_div::getUrl($actionParts[1]);
						$statements = $this->getStatementArray($fileContent, 1);
						$maxL = 1000;
						$strLen = strlen($fileContent);
						$maxlen = 200+($maxL-t3lib_div::intInRange(($strLen-20000)/100,0,$maxL));
						if (count($statements))	{
							$out = '';
							foreach ($statements as $statement) {
								$out.= nl2br(htmlspecialchars(t3lib_div::fixed_lgd_cs($statement,$maxlen)).chr(10).chr(10));
							}
						}
						$this->message($tLabel,'Content of '.basename($actionParts[1]),$out,1);
					}
				break;
				case 'adminUser':	// Create admin user
					if ($whichTables['be_users'])	{
						if (is_array($this->INSTALL['database_adminUser']))	{
							$username = preg_replace('/[^\da-z._-]/i', '', trim($this->INSTALL['database_adminUser']['username']));
							$pass = trim($this->INSTALL['database_adminUser']['password2']);
							if ($username && $pass)	{
								if ($pass != trim($this->INSTALL['database_adminUser']['password'])) {
									$this->message($headCode, 'Passwords are not equal!', '
										The passwords entered twice are not equal.',2,1);
								} else {
								$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery('uid', 'be_users', 'username='.$GLOBALS['TYPO3_DB']->fullQuoteStr($username, 'be_users'));
								if (!$GLOBALS['TYPO3_DB']->sql_num_rows($res))	{

									$insertFields = array(
										'username' => $username,
										'password' => md5($pass),
										'admin' => 1,
										'uc' => '',
										'fileoper_perms' => 0,
										'tstamp' => $GLOBALS['EXEC_TIME'],
										'crdate' => $GLOBALS['EXEC_TIME']
									);

									$result = $GLOBALS['TYPO3_DB']->exec_INSERTquery('be_users', $insertFields);

									$this->isBasicComplete($headCode);

									if ($result) {
										$this->message($headCode,'User created','
												Username: <strong>'.htmlspecialchars($username).'</strong><br />',
											1,1);
									} else {
										$this->message($headCode,'User not created','
											Error: <strong>'.htmlspecialchars($GLOBALS['TYPO3_DB']->sql_error()).'</strong><br />',
											3,1);
									}
								} else {
									$this->message($headCode,'Username not unique!','
									The username, <strong>'.htmlspecialchars($username).'</strong>, was not unique.',2,1);
								}
							}
						}
						}
						$content = '
						<input type="text" name="TYPO3_INSTALL[database_adminUser][username]"> username - unique, no space, lowercase<br />
						<input type="password" name="TYPO3_INSTALL[database_adminUser][password]"> password
						<input type="password" name="TYPO3_INSTALL[database_adminUser][password2]"> password (repeated)
						';
						$form = $this->getUpdateDbFormWrap($action_type, $content);
						$this->message($headCode,'Create admin user',"
						Enter username and password for a new admin user.<br />
						You should use this function only if there are no admin users in the database, for instance if this is a blank database.<br />
						After you've created the user, log in and add the rest of the user information, like email and real name.<br />
						<br />
						".$form."
						",0,1);
					} else {
						$this->message($headCode,'Required table not in database',"
						'be_users' must be a table in the database!
						",3,1);
					}
				break;
				case 'UC':	// clear uc
					if ($whichTables['be_users'])	{
						if (!strcmp($this->INSTALL['database_UC'],1))	{
							$GLOBALS['TYPO3_DB']->exec_UPDATEquery('be_users', '', array('uc' => ''));
							$this->message($headCode,'Clearing be_users.uc','Done.',1);
						}
						$content = '
						<input type="checkbox" name="TYPO3_INSTALL[database_UC]" id="database_UC" value="1" checked="checked"> <label for="database_UC">Clear be_users preferences ("uc" field)</label>
						';
						$form = $this->getUpdateDbFormWrap($action_type, $content);
						$this->message($headCode,'Clear user preferences',"
						If you press this button all backend users from the tables be_users will have their user preferences cleared (field 'uc' set to an empty string).<br />
						This may come in handy in rare cases where that configuration may be corrupt.<br />
						Clearing this will clear all user settings from the 'Setup' module.<br />
						<br />
						".$form);
					} else {
						$this->message($headCode,'Required table not in database',"
						'be_users' must be a table in the database!
						",3);
					}
				break;
				case 'cache':
					$tableListArr = explode(',','cache_pages,cache_pagesection,cache_hash,cache_imagesizes,--div--,sys_log,sys_history,--div--,be_sessions,fe_sessions,fe_session_data'.
						(t3lib_extMgm::isLoaded('indexed_search') ? ',--div--,index_words,index_rel,index_phash,index_grlist,index_section,index_fulltext' : '').
						(t3lib_extMgm::isLoaded('tt_products') ? ',--div--,sys_products_orders,sys_products_orders_mm_tt_products' : '').
						(t3lib_extMgm::isLoaded('direct_mail') ? ',--div--,sys_dmail_maillog' : '').
						(t3lib_extMgm::isLoaded('sys_stat') ? ',--div--,sys_stat' : '')
					);

					if (is_array($this->INSTALL['database_clearcache']))	{
						$qList=array();
						reset($tableListArr);
						while(list(,$table)=each($tableListArr))	{
							if ($table!='--div--')	{
								$table_c = TYPO3_OS=='WIN' ? strtolower($table) : $table;
								if ($this->INSTALL['database_clearcache'][$table] && $whichTables[$table_c])	{
									$GLOBALS['TYPO3_DB']->exec_DELETEquery($table, '');
									$qList[] = $table;
								}
							}
						}
						if (count($qList))	{
							$this->message($headCode,'Clearing cache','
							The following tables were emptied:<br /><br />
							'.implode($qList,'<br />')
							,1);
						}
					}
						// Count entries and make checkboxes
					$labelArr = array(
						'cache_pages' => 'Pages',
						'cache_pagesection' => 'TS template related information',
						'cache_hash' => 'Multipurpose md5-hash cache',
						'cache_imagesizes' => 'Cached image sizes',
						'sys_log' => 'Backend action logging',
						'sys_stat' => 'Page hit statistics',
						'sys_history' => 'Addendum to the sys_log which tracks ALL changes to content through TCE. May become huge by time. Is used for rollback (undo) and the WorkFlow engine.',
						'be_sessions' => 'Backend User sessions',
						'fe_sessions' => 'Frontend User sessions',
						'fe_session_data' => 'Frontend User sessions data',
						'sys_dmail_maillog' => 'Direct Mail log',
						'sys_products_orders' => 'tt_product orders',
						'sys_products_orders_mm_tt_products' => 'relations between tt_products and sys_products_orders'
					);

					$checkBoxes=array();
					$countEntries=array();
					reset($tableListArr);
					while(list(,$table)=each($tableListArr))	{
						if ($table!='--div--')	{
							$table_c = TYPO3_OS=='WIN' ? strtolower($table) : $table;
							if ($whichTables[$table_c])	{
								$countEntries[$table] = $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('*', $table);
									// Checkboxes:
								$checkBoxes[]= '<input type="checkbox" name="TYPO3_INSTALL[database_clearcache]['.$table.']" id="TYPO3_INSTALL[database_clearcache]['.$table.']" value="1"'.($this->INSTALL['database_clearcache'][$table]||$_GET['PRESET']['database_clearcache'][$table]?' checked="checked"':'').'> <label for="TYPO3_INSTALL[database_clearcache]['.$table.']"><strong>'.$table.'</strong> ('.$countEntries[$table].' rows) - '.$labelArr[$table].'</label>';
							}
						} else {
								$checkBoxes[]= 	'<hr />';
						}
					}

					$content = implode('<br />',$checkBoxes).'<br /><br />';

					$form = $this->getUpdateDbFormWrap($action_type, $content);
					$this->message($headCode,'Clear out selected tables','
					Pressing this button will delete all records from the selected tables.<br />
					<br />
					'.$form.'
					');
				break;
			}
		}

		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * Generates update wizard, outputs it as well
	 *
	 * @return	void
	 */
	function updateWizard()	{
			// clear cache files
		t3lib_extMgm::removeCacheFiles(t3lib_extMgm::getCacheFilePrefix());

			// call wizard
		$action = ($this->INSTALL['database_type']?$this->INSTALL['database_type']:'checkForUpdate');
		$this->updateWizard_parts($action);
		$this->output($this->outputWrapper($this->printAll()));
	}

	/**
	 * Implements the steps for the update wizard
	 *
	 * @param	string		action which should be done.
	 * @return	void
	 */
	function updateWizard_parts($action)	{
		$content = '';
		switch ($action)	{
			case 'checkForUpdate':	// first step - check for updates available
				$title = 'Step 1 - Introduction';
				$updateWizardBoxes = '';
				if (!$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'])	{
					$content = '<strong>No updates registered!</strong>';
					break;
				}

					// step through list of updates, and check if update is needed and if yes, output an explanation
				foreach ($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'] as $identifier => $className)	{
					$tmpObj = $this->getUpgradeObjInstance($className, $identifier);
					if (method_exists($tmpObj,'checkForUpdate'))	{
						$explanation = '';
						if ($tmpObj->checkForUpdate($explanation))	{
							$updateWizardBoxes.= '
								<div class="updateWizardBoxes">
									<h3>'.$identifier.'</h3>
									<p>'.str_replace(chr(10),'<br />',$explanation).'</p>
									<input type="submit" name="TYPO3_INSTALL[update]['.$identifier.']" id="TYPO3_INSTALL[update]['.$identifier.']" value="Next" />
								</div>';
						}
					}
				}

				if ($updateWizardBoxes)	{
					$updateWizardBoxes = '<table><tr><td>'.$updateWizardBoxes.'</td></tr></table>';
					$content = '
						<form action="'.$this->action.'#bottom" method="post">
							<input type="hidden" name="TYPO3_INSTALL[database_type]" value="'.htmlspecialchars('getUserInput').'">
							'.$updateWizardBoxes.'</form>';
				} else {
					$content = '<strong>No updates to perform!</strong>';
				}
				$content .= '<table><tbody><tr><td>
				<div class="updateWizardBoxes">
				<h3>Final Step</h3>
				<p>When all updates are done you should check your database for required updates.<br />
				Perform <strong>COMPARE DATABASE</strong> as often until no more changes are required.<br /><br />
				<input type="button" onclick="document.location.href=\'index.php?TYPO3_INSTALL[type]=database&TYPO3_INSTALL[database_type]=cmpFile|CURRENT_TABLES#bottom\';"
				value="COMPARE DATABASE" />
				</p>
				</div>
				</td></tr></tbody></table>';
			break;
			case 'getUserInput':	// second step - get user input and ask for final confirmation
				$title = 'Step 2 - Configuration of updates';
				$formContent = '<p class="innerWidth"><strong>The following updates will be performed:</strong></p>';
				if (!$this->INSTALL['update'])	{
					$content = '<strong>No updates selected!</strong>';
					break;
				}
					// update methods might need to get custom data
				foreach ($this->INSTALL['update'] as $identifier => $tmp)	{
					$className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];

					$tmpObj = $this->getUpgradeObjInstance($className, $identifier);

					$formContent .= '<p><strong>'.$identifier.'</strong><input type="hidden" name="TYPO3_INSTALL[update][extList][]" value="'.$identifier.'" /><br />';
					if (method_exists($tmpObj,'getUserInput'))	{
						$formContent .= $tmpObj->getUserInput('TYPO3_INSTALL[update]['.$identifier.']');
					}
					$formContent.= '</p>';
				}
				$formContent.= '<input type="checkbox" name="TYPO3_INSTALL[update][showDatabaseQueries]" id="TYPO3_INSTALL[update][showDatabaseQueries]" value="1" /> <label for="TYPO3_INSTALL[update][showDatabaseQueries]">Show database queries performed</label><br />';
				$content = $this->getUpdateDbFormWrap('performUpdate', $formContent,'Perform updates!');
			break;
			case 'performUpdate':	// third step - perform update
				$title = 'Step 3 - Perform updates';
				if (!$this->INSTALL['update']['extList'])	{ break; }

				$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = TRUE;
				foreach ($this->INSTALL['update']['extList'] as $identifier)	{
					$className = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['update'][$identifier];

					$tmpObj = $this->getUpgradeObjInstance($className, $identifier);

					$content = '<p class="innerWidth"><strong>'.$identifier.'</strong></p>';
						// check user input if testing method is available
					if (method_exists($tmpObj,'checkUserInput'))	{
						$customOutput = '';
						if (!$tmpObj->checkUserInput($customOutput))	{
							$content.= ($customOutput?$customOutput:'Something went wrong').'<br /><br />';
							$content.= '<a href="javascript:history.back()">Go back to update configuration</a>';
							break;
						}
					}

					if (method_exists($tmpObj,'performUpdate'))	{
						$customOutput = '';
						$dbQueries = array();
						if ($tmpObj->performUpdate($dbQueries, $customOutput))	{
							$content .= '<strong>Update successful!</strong>';
						} else {
							$content.= '<strong>Update FAILED!</strong>';
						}
						if ($this->INSTALL['update']['showDatabaseQueries'])	{
							$content .= '<br />' . implode('<br />',$dbQueries);
						}
						if (strlen($customOutput))	{
							$content.= '<br />' . $customOutput;
						}
					} else {
						$content .= '<strong>No update method available!</strong>';
					}
				}
				$GLOBALS['TYPO3_DB']->store_lastBuiltQuery = FALSE;
			break;
		}
		$this->message('Update Wizard',$title,$content);
	}

	/**
	 * Creates instance of an upgrade object, setting the pObj, versionNumber and pObj
	 *
	 * @param	string		class name
	 * @param	identifier		identifier of upgrade object - needed to fetch user input
	 * @return	object		newly instanciated upgrade object
	 */
	function getUpgradeObjInstance($className, $identifier)	{
		$tmpObj = t3lib_div::getUserObj($className);
		$tmpObj->versionNumber = t3lib_div::int_from_ver(TYPO3_version);
		$tmpObj->pObj = $this;
		$tmpObj->userInput = $this->INSTALL['update'][$identifier];
		return $tmpObj;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isBackendAdminUser()	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'be_users', 'admin=1');
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function isStaticTemplates()	{
		return $GLOBALS['TYPO3_DB']->exec_SELECTcountRows('uid', 'static_template');
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$tLabel: ...
	 * @return	[type]		...
	 */
	function isBasicComplete($tLabel)	{
		if ($this->mode=='123')	{
			$tables = $this->getListOfTables();

			if (count($tables))	{
				$beuser = $this->isBackendAdminUser();
				$static = $this->isStaticTemplates();
			}
			if (count($tables) && $beuser && $static)	{
				$mode123Imported=1;
				$this->message($tLabel,'Basic Installation Completed',nl2br($this->messageBasicFinished()),-1,1);
				$this->message($tLabel,'Security Risk!',nl2br($this->securityRisk().$this->alterPasswordForm()),2,1);
			} else {
				$this->message($tLabel,'Still missing something?',nl2br('
				You may be missing one of these points before your TYPO3 installation is complete:

				'.(count($tables)?'':'- You haven\'t imported any tables yet.
				')
				.($static?'':'- You haven\'t imported the static_template table.
				')
				.($beuser?'':'- You haven\'t created an admin-user yet.
				')
				.'

				You you\'re about to import a database with a complete site in it, these three points should be met.
				'),-1,1);
			}
		}
		return $mode123Imported;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$type: ...
	 * @param	[type]		$arr_update: ...
	 * @param	[type]		$arr_remove: ...
	 * @param	[type]		$action_type: ...
	 * @return	[type]		...
	 */
	function generateUpdateDatabaseForm($type, $arr_update, $arr_remove, $action_type)	{
		$content = '';
		switch($type)	{
			case 'get_form':
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_update['clear_table'],'Clear tables (use with care!)',false,true);

				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_update['add'],'Add fields');
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_update['change'],'Changing fields',(t3lib_extMgm::isLoaded('dbal')?0:1),0,$arr_update['change_currentValue']);
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_remove['change'],'Remove unused fields (rename with prefix)',$this->setAllCheckBoxesByDefault,1);
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_remove['drop'],'Drop fields (really!)',$this->setAllCheckBoxesByDefault);

				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_update['create_table'],'Add tables');
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_remove['change_table'],'Removing tables (rename with prefix)',$this->setAllCheckBoxesByDefault,1,$arr_remove['tables_count'],1);
				$content.= $this->generateUpdateDatabaseForm_checkboxes($arr_remove['drop_table'],'Drop tables (really!)',$this->setAllCheckBoxesByDefault,0,$arr_remove['tables_count'],1);

				$content = $this->getUpdateDbFormWrap($action_type, $content);
			break;
			default:

			break;
		}
		return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$action_type: ...
	 * @param	[type]		$content: ...
	 * @param	[type]		$label: ...
	 * @return	[type]		...
	 */
	function getUpdateDbFormWrap($action_type, $content, $label='Write to database')	{
		$form = '<form action="' . $this->action . '#bottom" method="post">' .
			'<input type="hidden" name="TYPO3_INSTALL[database_type]" value="' . htmlspecialchars($action_type) . '">' .
				$content . '<br />' .
			'<input type="submit" value="' . $label . '">';
		return $form;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @param	[type]		$pre: ...
	 * @param	[type]		$label: ...
	 * @return	[type]		...
	 */
	function displayFields($arr, $pre=0, $label='')	{
		$out='';
		$out.='<tr><td class="tcaTableHeader" align="center"><strong>'.$this->fw('Field name:').'</strong></td><td class="tcaTableHeader" align="center"><strong>'.$this->fw($label?$label:'Info:').'</strong></td></tr>';
		if (is_array($arr))	{
			reset($arr);
			while(list($fieldname, $fieldContent)=each($arr))	{
				if ($pre)	{
					$fieldContent = '<pre>'.trim($fieldContent).'</pre>';
				} else {
					$fieldContent = $this->fw($fieldContent);
				}
				$out.='<tr><td class="tcaTableBackground">'.$this->fw($fieldname).'</td><td class="tcaTableBackground">'.$fieldContent.'</td></tr>';
			}
		}
		$out = '<table border="0" cellpadding="0" cellspacing="1">' . $out . '</table>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @param	[type]		$arr_db: ...
	 * @return	[type]		...
	 */
	function displayFieldComp($arr, $arr_db)	{
		$out='';
		$out.='<tr><td bgcolor="#9BA1A8" align="center"><strong>'.$this->fw('Field name:').'</strong></td>
			<td bgcolor="#9BA1A8" align="center"><strong>'.$this->fw("Suggested value from \$TCA:").'</strong></td>
			<td bgcolor="#9BA1A8" align="center"><strong>'.$this->fw('Actual value from database:').'</strong></td>
			</tr>';
		if (is_array($arr))	{
			reset($arr);
			while(list($fieldname, $fieldContent)=each($arr))	{
					// This tries to equalize the types tinyint and int
				$str1 = $fieldContent;
				$str2 = trim($arr_db[$fieldname]);
				$str1 = str_replace('tinyint(3)','tinyint(4)',$str1);
				$str2 = str_replace('tinyint(3)','tinyint(4)',$str2);
				$str1 = str_replace('int(10)','int(11)',$str1);
				$str2 = str_replace('int(10)','int(11)',$str2);
					// Compare:
				if (strcmp($str1,$str2))	{
					$bgcolor=' class="warning"';
				} else {
					$bgcolor=' class="generalTableBackground"';
				}
				$fieldContent = $this->fw($fieldContent);
				$fieldContent_db = $this->fw($arr_db[$fieldname]);
				$out.='<tr>
					<td class="generalTableBackground">'.$this->fw($fieldname).'</td>
					<td'.$bgcolor.'>'.$fieldContent.'</td>
					<td'.$bgcolor.'>'.$fieldContent_db.'</td>
					</tr>';
			}
		}
		$out= '<table border="0" cellpadding="2" cellspacing="2">'.$out.'</table>';
		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$arr: ...
	 * @param	[type]		$excludeList: ...
	 * @return	[type]		...
	 */
	function displaySuggestions($arr, $excludeList='')	{
		$out='';
		$out.='<tr><td bgcolor="#9BA1A8" align="center"><strong>'.$this->fw('Field name:').'</strong></td><td bgcolor="#9BA1A8" align="center"><strong>'.$this->fw('Info / Suggestion for the field:').'</strong></td></tr>';
		$fC=0;
		if (is_array($arr))	{
			reset($arr);
			while(list($fieldname, $fieldContent)=each($arr))	{
				if (!t3lib_div::inList($excludeList,$fieldname) && substr($fieldname,0,strlen($this->deletedPrefixKey))!=$this->deletedPrefixKey && substr($fieldname,-1)!='.')	{
					$fieldContent = $this->fw($fieldContent);
					if ($arr[$fieldname.'.'])	{
						$fieldContent.= '<hr />';
						$fieldContent.= '<pre>'.trim($arr[$fieldname.'.']).'</pre>';
					}
					$out.='<tr><td class="tcaTableBackground">'.$this->fw($fieldname).'</td><td class="tcaTableBackground">'.$fieldContent.'</td></tr>';
					$fC++;
				}
			}
		}
		$out= '<table border="0" cellpadding="2" cellspacing="2">'.$out.'</table>';
		return array($out,$fC);
	}

	/**
	 * Compares an array with field definitions with $TCA array
	 *
	 * @param	[type]		$FDsrc: ...
	 * @param	[type]		$TCA: ...
	 * @param	[type]		$onlyFields: ...
	 * @return	[type]		...
	 */
	function compareDatabaseAndTCA($FDsrc, $TCA, $onlyFields=0)	{
		$extraArr=array();
		if (is_array($FDsrc))	{
			reset($FDsrc);
			while(list($table,$info)=each($FDsrc))	{
				if (!isset($TCA[$table]))	{
					if (!$onlyFields)	{
						$extraArr[$table]=$info;		// If the table was not in the FDcomp-array, the result array is loaded with that table.
						$extraArr[$table]['whole_table']=1;
						unset($extraArr[$table]['keys']);
					}
				} else {
					$theKey='fields';
					$excludeListArr=array();
					if (is_array($TCA[$table]['ctrl']['enablecolumns']))	$excludeListArr[]=$TCA[$table]['ctrl']['enablecolumns'];
					$excludeListArr[]=$TCA[$table]['ctrl']['tstamp'];
					$excludeListArr[]=$TCA[$table]['ctrl']['sortby'];
					$excludeListArr[]=$TCA[$table]['ctrl']['delete'];
					$excludeListArr[]=$TCA[$table]['ctrl']['cruser_id'];
					$excludeListArr[]=$TCA[$table]['ctrl']['crdate'];
					$excludeListArr[]='uid';
					$excludeListArr[]='pid';
					if ($table=='pages')	{
						$excludeListArr[]='perms_userid';
						$excludeListArr[]='perms_groupid';
						$excludeListArr[]='perms_user';
						$excludeListArr[]='perms_group';
						$excludeListArr[]='perms_everybody';
					}
					if ($table=='sys_dmail')	{
						$excludeListArr[]='scheduled';
						$excludeListArr[]='scheduled_begin';
						$excludeListArr[]='scheduled_end';
						$excludeListArr[]='query_info';
					}

					if (is_array($info[$theKey]))	{
						reset($info[$theKey]);
						while(list($fieldN,$fieldC)=each($info[$theKey]))	{
							if (!isset($TCA[$table]['columns'][$fieldN]) && !in_array($fieldN,$excludeListArr))	{
								$extraArr[$table][$theKey][$fieldN] = $info['fields'][$fieldN];
								$extraArr[$table][$theKey][$fieldN.'.']=$this->suggestTCAFieldDefinition($fieldN,$fieldC);
							}
						}
					}
				}
			}
		}
		return array('extra'=>$extraArr);
	}

	/**
	 * Compares the $TCA array with a field definition array
	 *
	 * @param	[type]		$TCA: ...
	 * @param	[type]		$FDcomp: ...
	 * @return	[type]		...
	 */
	function compareTCAandDatabase($TCA, $FDcomp)	{
		$extraArr=array();
		$matchingArr=array();
		if (is_array($TCA))	{
			reset($TCA);
			while(list($table)=each($TCA))	{
				$info=$TCA[$table];
				if (!isset($FDcomp[$table]))	{
//					$extraArr[$table]=$info;		// If the table was not in the FDcomp-array, the result array is loaded with that table.
					$extraArr[$table]['whole_table']=1;
				} else {
					reset($info['columns']);
					while(list($fieldN,$fieldC)=each($info['columns']))	{
						$fieldDef = $this->suggestFieldDefinition($fieldC);
						if (!is_array($fieldDef))	{
							if (!isset($FDcomp[$table]['fields'][$fieldN]))	{
								$extraArr[$table]['fields'][$fieldN]=$fieldDef;
							} else {
								$matchingArr[$table]['fields'][$fieldN]=$fieldDef;
							}
						}
					}
				}
			}
		}
		return array('extra'=>$extraArr, 'matching'=>$matchingArr);
	}

	/**
	 * Suggests a field definition for a TCA config array.
	 *
	 * @param	[type]		$fieldInfo: ...
	 * @return	[type]		...
	 */
	function suggestFieldDefinition($fieldInfo)	{
		$out='';
		switch($fieldInfo['config']['type'])	{
			case 'input':
				if (preg_match('/date|time|int|year/',$fieldInfo['config']['eval']))	{
					$out = "int(11) NOT NULL default '0'";
				} else {
					$max = intval($fieldInfo['config']['max']);
					if ($max>0 && $max<200)	{
						$out = 'varchar('.$max.") NOT NULL default ''";
					} else {
						$out = 'tinytext';
					}
				}
			break;
			case 'text':
				$out = 'text';
			break;
			case 'check':
				if (is_array($fieldInfo['config']['items']) && count($fieldInfo['config']['items'])>8)	{
					$out = "int(11) NOT NULL default '0'";
				} else {
					$out = "tinyint(3) NOT NULL default '0'";
				}
			break;
			case 'radio':
				if (is_array($fieldInfo['config']['items']))	{
					$out = $this->getItemArrayType($fieldInfo['config']['items']);
				} else {
					$out = 'ERROR: Radiobox did not have items!';
				}
			break;
			case 'group':
				if ($fieldInfo['config']['internal_type']=='db')	{
					$max = t3lib_div::intInRange($fieldInfo['config']['maxitems'],1,10000);
					if (count(explode(',',$fieldInfo['config']['allowed']))>1)	{
						$len = $max*(10+1+5+1);		// Tablenames are 10, "_" 1, uid's 5, comma 1
						$out=$this->getItemBlobSize($len);
					} elseif ($max<=1) {
						$out = "int(11) NOT NULL default '0'";
					} else {
						$len = $max*(5+1);		// uid's 5, comma 1
						$out=$this->getItemBlobSize($len);
					}
				}
				if ($fieldInfo['config']['internal_type']=='file')	{
					$max = t3lib_div::intInRange($fieldInfo['config']['maxitems'],1,10000);
					$len = $max*(30+1);		// Filenames is 30+ chars....
					$out=$this->getItemBlobSize($len);
				}
			break;
			case 'select':
				$max = t3lib_div::intInRange($fieldInfo['config']['maxitems'],1,10000);
				if ($max<=1)	{
					if ($fieldInfo['config']['foreign_table'])	{
						$out = "int(11) NOT NULL default '0'";
					} else {
						$out = $this->getItemArrayType($fieldInfo['config']['items']);
					}
				} else {
						// five chars (special=10) + comma:
					$len = $max*(($fieldInfo['config']['special']?10:5)+1);
					$out=$this->getItemBlobSize($len);
				}
			break;
			default:
			break;
		}
		return $out?$out:$fieldInfo;
	}

	/**
	 * Private
	 *
	 * @param	[type]		$arr: ...
	 * @return	[type]		...
	 */
	function getItemArrayType($arr)	{
		if (is_array($arr))	{
			reset($arr);
			$type[]=0;
			$intSize[]=0;
			while(list(,$item)=each($arr))	{
				if (!t3lib_div::testInt($item[1]) && $item[1]!='--div--')	{
					$type[]=strlen($item[1]);
				} else {
					$intSize[]=$item[1];
				}
			}
			$us = min($intSize)>=0 ? ' unsigned' : '';
			if (max($type)>0)	{
				$out = 'varchar('.max($type).") NOT NULL default ''";
			} else {
				$out = "int(11) NOT NULL default '0'";
			}
		}
		return $out;
	}

	/**
	 * Private
	 *
	 * @param	[type]		$len: ...
	 * @return	[type]		...
	 */
	function getItemBlobSize($len)	{
		return ($len>255 ? 'tiny' : '').'blob';
	}

	/**
	 * Should suggest a TCA configuration for a specific field.
	 *
	 * @param	[type]		$fieldName: ...
	 * @param	[type]		$fieldInfo: ...
	 * @return	[type]		...
	 */
	function suggestTCAFieldDefinition($fieldName,$fieldInfo)	{
		list($type,$len) = preg_split('/ |\(|\)/', $fieldInfo, 3);
		switch($type)	{
			case 'int':
$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'input',
		'size' => '8',
		'max' => '20',
		'eval' => 'date',
		'default' => '0',
		'checkbox' => '0'
	)
),

----- OR -----

'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'select',
		'items' => array (
			array('[nothing]', 0),
			array('Extra choice! Only negative values here.', -1),
			array('__Divider:__', '--div--')
		),
		'foreign_table' => '[some_table_name]'
	)
),";
			break;
			case 'varchar':
				if ($len>10)	{
					$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'input',
		'size' => '8',
		'max' => '".$len."',
		'eval' => 'trim',
		'default' => ''
	)
),";
				} else {
					$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'select',
		'items' => array (
			array('Item number 1', 'key1'),
			array('Item number 2', 'key2'),
			array('-----', '--div--'),
			array('Item number 3', 'key3')
		),
		'default' => '1'
	)
),";
				}
			break;
			case 'tinyint':
					$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'select',
		'items' => array (
			array('Item number 1', '1'),
			array('Item number 2', '2'),
			array('-----', '--div--'),
			array('Item number 3', '3')
		),
		'default' => '1'
	)
),

----- OR -----

'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'check',
		'default' => '1'
	)
),";
			break;
			case 'tinytext':
$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'input',
		'size' => '40',
		'max' => '255',
		'eval' => '',
		'default' => ''
	)
),";
			break;
			case 'text':
			case 'mediumtext':
$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'config' => array (
		'type' => 'text',
		'cols' => '48',
		'rows' => '5'
	)
),";
			break;
			default:
				$out="
'".$fieldName."' => array (
	'label' => '".strtoupper($fieldName).":',
	'exclude' => 0,
	'config' => array (
		'type' => 'input',
		'size' => '30',
		'max' => '',
		'eval' => '',
		'default' => ''
	)
),";
			break;
		}
		return $out?$out:$fieldInfo;
	}

	/**
	 * Includes TCA
	 *
	 * @return	[type]		...
	 */
	function includeTCA()	{
		global $TCA;

		include (TYPO3_tables_script ? PATH_typo3conf.TYPO3_tables_script : PATH_t3lib.'stddb/tables.php');

			// Extension additions
		if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'])	{
			include(PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php');
		} else {
			include(PATH_t3lib.'stddb/load_ext_tables.php');
		}

		if (TYPO3_extTableDef_script)	{
			include (PATH_typo3conf.TYPO3_extTableDef_script);
		}

		reset($TCA);
		while(list($table)=each($TCA))	{
			t3lib_div::loadTCA($table);
		}
	}








	/**********************
	 *
	 * GENERAL FUNCTIONS
	 *
	 **********************/

	/**
	 * This creates a link to the given $url. If $link is set, that'll be the link-text
	 *
	 * @param	[type]		$url: ...
	 * @param	[type]		$link: ...
	 * @return	[type]		...
	 */
	function linkIt($url,$link='')	{
		return '<a href="'.$url.'" target="_blank">'.($link?$link:$url).'</a>';
	}

	/**
	 * Setting a message in the message-log and sets the fatalError flag if error type is 3.
	 *
	 * @param	string		Section header
	 * @param	string		A short description
	 * @param	string		A long (more detailed) description
	 * @param	integer		-1=OK sign, 0=message, 1=notification, 2=warning, 3=error
	 * @param	boolean		Print message also in "Advanced" mode (not only in 1-2-3 mode)
	 * @return	void
	 */
	function message($head, $short_string='', $long_string='', $type=0, $force=0)	{
		if (!$force && $this->mode=='123' && $type<2)	{ return; }	// Return directly if mode-123 is enabled.

		if ($type==3)	{ $this->fatalError=1; }
		if ($this->messageFunc_nl2br && !preg_match('/<\/table>/', $long_string))	{
			$long_string = nl2br(trim($long_string));
		} else {
			$long_string = trim($long_string);
		}
		if (!$this->silent)	$this->printSection($head, $short_string, $long_string, $type);
	}

	/**
	 * This "prints" a section with a message to the ->sections array
	 *
	 * @param	string		Section header
	 * @param	string		A short description
	 * @param	string		A long (more detailed) description
	 * @param	integer		-1=OK sign, 0=message, 1=notification, 2=warning , 3=error
	 * @return	void
	 */
	function printSection($head, $short_string, $long_string, $type)	{
		switch($type)	{
			case 3:
				$cssClass = ' class="typo3-message message-error"';
			break;
			case 2:
				$cssClass = ' class="typo3-message message-warning"';
			break;
			case 1:
				$cssClass =' class="typo3-message message-notice"';
				break;
			case 0:
				$cssClass = ' class="typo3-message message-information"';
				break;
			case -1:
				$cssClass = ' class="typo3-message message-ok"';
			break;
		}

		if (!trim($short_string))	{
			$this->sections[$head][]='';
		} else {
			$this->sections[$head][]='
			<tr><td' . $cssClass . ' nowrap="nowrap">' . '<strong>' . $this->fw($short_string) . '</strong></td></tr>' . (trim($long_string) ? '
			<tr><td>' . $this->fw($long_string) . '<br /><br /></td></tr>' : '');
		}
	}

	/**
	 * Wraps the str in a font-tag with verdana 1
	 *
	 * @param	[type]		$str: ...
	 * @param	[type]		$size: ...
	 * @return	[type]		...
	 */
	function fw($str,$size=1)	{

		if (preg_match('/^<table/', $str) && preg_match('/<\/table>$/', $str)) {
			// no wrap
		} else {
			if($size==1) {
				$size = 'class="smalltext"';
			} elseif ($size==2) {
				$size = 'class="bodytext"';
			} else {
				$size = 'size="size'.$size.'text"';
			}
			$str = '<span '.$size.'>'.$str.'</span>';
		}
		return $str;
	}

	/**
	 * Wraps the str in a font-tag with verdana 1
	 *
	 * @param	[type]		$str: ...
	 * @return	[type]		...
	 */
	function fwheader($str)	{
		return '<div align="center"><strong>'.$this->fw($str,3).'</strong></div>';
	}

	/**
	 * Wrapping labal/content in a table-row.
	 *
	 * @param	[type]		$label: ...
	 * @param	[type]		$content: ...
	 * @return	[type]		...
	 */
	function wrapInCells($label,$content)	{
		return '<tr><td valign="top" nowrap="nowrap"><strong>'.$this->fw($label).'</strong></td><td>&nbsp;</td><td valign="top">'.$this->fw($content).'<br /></td></tr>';
	}

	/**
	 * This prints all the messages in the ->section array
	 *
	 * @return	[type]		...
	 */
	function printAll()	{
		reset($this->sections);
		$out='';
		while(list($header,$valArray)=each($this->sections))	{
			$out.='
			<tr><td>&nbsp;</td></tr>
			<tr><td><div align="center"><strong>'.$this->fw($header.':',2).'</strong></div></td></tr>
			';
			$out.=implode($valArray,chr(10));
		}
		return '<table border="0" cellpadding="2" cellspacing="2">'.$out.'</table>';
	}

	/**
	 * This wraps and returns the main content of the page into proper html-code.
	 *
	 * @param	string		The page content
	 * @return	string		The full HTML page
	 */
	function outputWrapper($content)	{
		$out='
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN"
   "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset='.($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']?$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']:'iso-8859-1').'" />
'.$this->headerStyle.'
		<link rel="stylesheet" type="text/css" href="'.$this->backPath.'sysext/install/mod/install.css" >
 		<title>TYPO3 Install Tool</title>
		'.($this->JSmessage?'
<script language="javascript" type="text/javascript">alert(unescape(\'' . t3lib_div::rawUrlEncodeJS($this->JSmessage) . '\'));</script>

		':'').'
<script type="text/javascript" src="../contrib/prototype/prototype.js"></script>
	</head>
	<body>'.$this->contentBeforeTable.'
		<div align="center">';
		if($this->INSTALL['type'] == 'about') {
			$out .= '<table class="smallOuterTable" border="0" cellspacing="0" cellpadding="0">';
		} else {
			$out .= '<table class="outerTable" border="0" cellspacing="0" cellpadding="0">';
		}
		$out .= '<tr>
			<td class="logo"><img src="'.$this->backPath.'gfx/typo3logo.gif" width="123" height="34" vspace="10" hspace="50" alt="TYPO3"></td>
		</tr>
		<tr>
			<td class="createBorder">
				<table width="100%" border="0" cellspacing="1" cellpadding="10">
					<tr>
						<td class="generalTableBackground">
						<div align="center"><span class="size4text"><strong>TYPO3 '.TYPO3_branch.' Install Tool</strong></span></div>
						<div align="center"><span class="siteInfo"><strong>Site: ' . htmlspecialchars($GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']) . '</strong></span></div>
						'.($this->passwordOK ? '<div align="center"><span class="siteInfo"><strong>Version: '.TYPO3_version.'</strong></span></div>':'').'<br />

'.($this->step?$this->stepHeader():$this->menu()).$content.'<hr />'.$this->note123().$this->endNotes().'
						</td>
					</tr>
				</table>
			</td>
		</tr>
		</table>
		</div>
	</body>
</html>';
		return $out;
	}

	/**
	 * Outputs an error and dies.
	 * Should be used by all errors that occur before even starting the install tool process.
	 *
	 * @param string The content of the error
	 * @return void
	 */
	protected function outputErrorAndExit($content, $title = 'Install Tool error') {
			// Output the warning message and exit
		header('Content-Type: text/html; charset=utf-8');
		header('Cache-Control: no-cache, must-revalidate');
		header('Pragma: no-cache');
		echo '<h1>'.$title.'</h1>';
		echo $content;
		exit();
	}

	/**
	 * Sends the page to the client.
	 *
	 * @param	string		The HTML page
	 */
	function output($content)	{
		header ('Content-Type: text/html; charset=' .
			($GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']?$GLOBALS['TYPO3_CONF_VARS']['BE']['forceCharset']:'iso-8859-1'));
		echo $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function menu()	{
		if (!$this->passwordOK)	return;

		reset($this->menuitems);
		$c=0;
		$out=array();
		while(list($k,$v)=each($this->menuitems))	{
			$bgcolor = ($this->INSTALL['type']==$k ? ' class="activeMenu"' : ' class="generalTableBackground"');
			$c++;
			$out[]='<tr><td'.$bgcolor.'><a href="'.htmlspecialchars($this->scriptSelf.'?TYPO3_INSTALL[type]='.$k.($this->mode?'&mode='.rawurlencode($this->mode):'')).'">'.$this->fw($c.': '.$v).'</a></td></tr>';
		}

		$code = '<table border="0" cellpadding="0" cellspacing="1">'.implode($out,chr(10)).'</table>';
		$code = '<table border="0" cellpadding="0" cellspacing="0" id="menu"><tr><td>'.$code.'</td></tr></table>';
		return '<div align="center">'.$code.'</div>';
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function stepHeader()	{
		$msg1='Type in your database parameters here:';
		$msg2='Database';
		$msg3='Import the database sql-file';
		$msg4='You\'re done!';
		$out='<img src="'.$this->backPath.'gfx/123_'.$this->step.'.png" width="402" height="73" border="0" alt="" usemap="#id123_print_Map">
<map name="id123_print_Map">
<area title="'.$msg4.'" shape="poly" alt="" coords="299,35, 303,19, 313,9, 335,5, 366,6, 379,11, 388,21, 392,35, 390,47, 381,58, 376,64, 359,67, 320,66, 307,60, 302,51, 300,44" href="'.htmlspecialchars($this->scriptSelf.'?mode='.$this->mode.'&step=go').'">
<area title="'.$msg3.'" shape="circle" alt="" coords="234,36,32" href="'.htmlspecialchars($this->scriptSelf.'?mode='.$this->mode.'&step=3').'">
<area title="'.$msg2.'" shape="circle" alt="" coords="136,37,30" href="'.htmlspecialchars($this->scriptSelf.'?mode='.$this->mode.'&step=2').'">
<area title="'.$msg1.'" shape="circle" alt="" coords="40,36,29" href="'.htmlspecialchars($this->scriptSelf.'?mode='.$this->mode.'&step=1').'">
</map>


		<br />';
		$msg='';
		switch(strtolower($this->step))	{
			case 1:
				$msg=$msg1;
			break;
			case 2:
				$msg=$msg2;
			break;
			case 3:
				$msg=$msg3;
			break;
			case 'go':
				$msg=$msg4;
			break;
			default:
			break;
		}
		$out.='<br /><div align="center"><strong>'.$this->fw($msg,2).'</strong></div>';

		return $out;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function note123()	{
		if ($this->mode=='123')	{
			$c='<table border="0" cellpadding="0" cellspacing="0" width="100%">
				<tr><td class="notice" nowrap="nowrap"><img src="' . $this->backPath . 'gfx/icon_note.gif" width="18" height="16" align="top" alt=""><strong>' . $this->fontTag1 . 'NOTICE: Install Tool is running in \'123\' mode. <a href="' . $this->scriptSelf . '">Click here to disable.</a></span></strong></td></tr>
			</table>';
			return $c;
		}
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function endNotes()	{
		if ($this->mode!='123' && $this->passwordOK)	{
			$c.='OS detected: <strong>'.(TYPO3_OS=='WIN'?'WIN':'UNIX').'</strong><br />';
			$c.='UNIX/CGI detected: <strong>'.(PHP_SAPI=='cgi' ? 'YES' : 'NO').'</strong><br />';
			$c.='PATH_thisScript: <strong>'.PATH_thisScript.'</strong><br />';
			$c.='<br />';
			$c.='<a href="../index.php" target="install_backend">Backend admin in new window.</a><br />';
			$c.='<a href="../../index.php" target="install_frontend">Frontend website in new window.</a><br />';

			return $this->fw($c);
		}
	}

	/**
	 * Convert a size from human-readable form into bytes
	 *
	 * @param	string		A string containing the size in bytes, kilobytes or megabytes. Example: 64M
	 * @return	string		The string is returned in bytes and can also hold floating values
	 */
	function convertByteSize($bytes)	{
		if (stristr($bytes,'m'))	{
			$bytes=doubleval($bytes)*1024*1024;
		} elseif (stristr($bytes,'k'))	{
			$bytes=doubleval($bytes)*1024;
		}
		return $bytes;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function securityRisk()	{
		$c='This script is a <strong>great danger to the security of TYPO3</strong> if you don\'t secure it somehow.
			We suggest one of the following:

			- change the password as defined by the md5-hash in TYPO3_CONF_VARS[BE][installToolPassword].
			- delete the folder \'typo3/install/\' with this script in or just insert an \'exit;\' line in the script-file there.
			- password protect the \'typo3/install/\' folder, eg. with a .htaccess file

			The TYPO3_CONF_VARS[BE][installToolPassword] is always active, but choosing one of the other options will improve security and is recommended highly.
		';
		return $c;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function alterPasswordForm()	{
			$content = '<form action="'.$this->scriptSelf.'?TYPO3_INSTALL[type]=extConfig" method="post">
			Enter new password:
			<input type="password" name="TYPO3_INSTALL[extConfig][BE][installToolPassword]" /><br />Enter again:
			<input type="password" name="installToolPassword_check" />
			<input type="hidden" name="installToolPassword_md5" value="1" />
			<input type="submit" value="Set new password" /><br />
			</form>';
			return $content;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	function messageBasicFinished()	{
		$msg ='
				Apparently you have completed the basic setup of the TYPO3 database.
				Now you can choose between these options:

				- <a href="../../index.php"><strong>Go to the frontend pages</strong></a>

				- <a href="../index.php"><strong>Go to the backend login</strong></a>
				 (username may be: <i>admin</i>, password may be: <i>password</i>.)

				- <a href="'.$this->scriptSelf.'"><strong>Continue to configure TYPO3</strong></a> (Recommended).
				 This will let you analyse and verify that everything in your PHP installation is alright. Also if you want to configure TYPO3 to use all the cool features, you <em>must</em> dig into the this!
				';
		return $msg;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$type: ...
	 * @return	[type]		...
	 */
	function setScriptName($type)	{
		$value = $this->scriptSelf.'?TYPO3_INSTALL[type]='.$type.($this->mode?'&mode='.rawurlencode($this->mode):'').($this->step?'&step='.rawurlencode($this->step):'');
		return $value;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$size: ...
	 * @param	[type]		$textarea: ...
	 * @param	[type]		$styleOverride: ...
	 * @return	[type]		...
	 */
	function formWidth($size=48,$textarea=0,$styleOverride='') {
			// Input or text-field attribute (size or cols)
		$wAttrib = $textarea?'cols':'size';
		if (!$GLOBALS['CLIENT']['FORMSTYLE'])	{	// If not setting the width by style-attribute
			$size = ceil($size*1);
			$retVal = ' '.$wAttrib.'="'.$size.'"';
		} else {	// Setting width by style-attribute. "cols" MUST be avoided with NN6+
			$pixels = ceil($size*10);
			$retVal = $styleOverride ? ' style="'.$styleOverride.'"' : ' style="width:'.$pixels.'px;"';
		}
		return $retVal;
	}

	/**
	 * [Describe function...]
	 *
	 * @param	[type]		$size: ...
	 * @param	[type]		$styleOverride: ...
	 * @param	[type]		$wrap: ...
	 * @return	[type]		...
	 */
	function formWidthText($size=48,$styleOverride='',$wrap='') {
		$wTags = $this->formWidth($size,1,$styleOverride);
			// Netscape 6+ seems to have this ODD problem where there WILL ALWAYS be wrapping with the cols-attribute set and NEVER without the col-attribute...
		if (strtolower(trim($wrap))!='off' && $GLOBALS['CLIENT']['BROWSER']=='net' && $GLOBALS['CLIENT']['VERSION']>=5)	{
			$wTags.=' cols="'.$size.'"';
		}
		return $wTags;
	}

	/**
	 * Return the filename that will be used for the backup.
	 * It is important that backups of PHP files still stay as a PHP file, otherwise they could be viewed un-parsed in clear-text.
	 *
	 * @param	string		Full path to a file
	 * @return	string		The name of the backup file (again, including the full path)
	 */
	function getBackupFilename($filename)	{
		if (preg_match('/\.php$/', $filename))	{
			$backupFile = str_replace('.php', '_bak.php', $filename);
		} else {
			$backupFile = $filename.'~';
		}

		return $backupFile;
	}

	/**
	 * Returns a newly created TYPO3 encryption key with a given length.
	 *
	 * @param  integer  $keyLength  desired key length
	 * @return string
	 */
	protected function createEncryptionKey($keyLength = 96) {
		$bytes = t3lib_div::generateRandomBytes($keyLength);
		return substr(bin2hex($bytes), -96);
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install.php']);
}

?>
