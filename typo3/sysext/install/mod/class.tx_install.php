<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2006-2007 Thomas Hempel (thomas@work.de)
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

require('conf.php');

require_once(PATH_site.'typo3/sysext/lang/lang.php');
require (PATH_site.'typo3/template.php');

require_once('class.tx_install_view.php');
require_once('class.tx_install_basics.php');
require_once(t3lib_extMgm::extPath('install').'modules/class.tx_install_module_base.php');

/**
 * Main controller class for Installer
 *
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@typo3-unleashed.net>
 * @author	Ingo Renner	<ingo@typo3.org>
 */
class tx_install {
	private $env              = NULL;
	private $collectedResults = array();
	private $backPath         = '../../typo3/';

	private $defaults = array (
		'module'   => 'setup',
		'language' => 'default'
	);
	
	private $language   = 'default';
	private $LOCAL_LANG = NULL;
	
	/**
	 * view object
	 *
	 * @var	tx_install_view
	 */
	private $viewObj   = NULL;
	
	/**
	 * basics object
	 *
	 * @var	tx_install_basics
	 */
	private $basicsObj = NULL;
	
	/**
	 * language object
	 *
	 * @var	language
	 */
	private $langObj   = NULL;
	
	/**
	 * An index that stores which deliverable is associated to which label
	 *
	 * @var array
	 */
	private $labelIndex = NULL;
	
	/**
	 * The name of the cookie that is used for setup login
	 *
	 * @var string
	 */
	private $cookieName = 'Typo3InstallTool';
	
	/**
	 * Stores if the password entered is correct. Eventually this stores if a user if sucessfully logged in.
	 *
	 * @var boolean
	 */
	private $passwordOK  = false;
	
	/**
	 * Array with all load modules
	 *
	 * @var array
	 */
	private $loadedModules = array();
	
	/**
	 * Set to true if writing data to localconf.php should be allowed
	 *
	 * @var boolean
	 */
	private $allowUpdateLocalConf;
	
	/**
	 * The results from the search. Current searchString can be found above the category tree
	 *
	 * @var array
	 */
	private $filterResults = array();

	/**
	 * Constructor, gets called from TYPO3 backend
	 * 
	 * @return	void
	 */
	public function init() {	 	
	 	global $PAGE_TYPES, $TBE_STYLES;
	 	
		if (!defined('PATH_typo3'))	exit;
		
	 	$this->loadedModules = array();
		session_start();
				
			// load the temp_CACHED files. This is necesarry to get some environment information like
			// $GLOBALS['TBE_STYLES']. Without this information we can't use skins that might be installed.
		if(TYPO3_MODE == 'BE') {
			include (TYPO3_tables_script ? PATH_typo3conf.TYPO3_tables_script : PATH_t3lib.'stddb/tables.php');
			if ($GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE']) {
				include (PATH_typo3conf.$GLOBALS['TYPO3_LOADED_EXT']['_CACHEFILE'].'_ext_tables.php');
			}
		}
		
			// Build the environment by processing incoming variables - the session is the base; POST and GET vars will overrule the data in the session while
			// POST overrules GET
		$this->env = (array) $_SESSION;
		$get       = t3lib_div::_GET();
		$post      = t3lib_div::_POST();
		$this->env = t3lib_div::array_merge_recursive_overrule($this->env, $get);
		$this->env = t3lib_div::array_merge_recursive_overrule($this->env, $post);
		
			// instanciate the needed objects
		$this->viewObj   = t3lib_div::makeInstance('tx_install_view');
		$this->basicsObj = t3lib_div::makeInstance('tx_install_basics');
		$this->viewObj->init($this);
		$this->basicsObj->init($this);
		
			// select the language
			// TODO: Here we have to determine which language is currently selected for the backend
		$this->language = (isset($this->env['L'])) ? $this->env['L'] : $this->defaults['language'];
		
			// instanciate the language module
		$this->langObj = t3lib_div::makeInstance('language');
		$this->langObj->init($this->language);

		if (!isset($GLOBALS['LANG'])) {
			$GLOBALS['LANG'] = $this->langObj;
		}
		
			// load the basic language files for the installer-module itself
		$this->LOCAL_LANG = $this->langObj->includeLLFile(t3lib_extMgm::extPath('install').'mod/locallang_mod.xml', FALSE);
		
			// check for a password
		if(!isset($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'])) {
			$this->viewObj->addContent('', 'Install Tool deactivated.<br />You must enable it by setting a password in typo3conf/localconf.php. If you insert the line below, the password will be "joh316":<br /><br />$TYPO3_CONF_VARS[\'BE\'][\'installToolPassword\'] = \'bacb98acf97e0b6112b1d1b650b84971\';');
			echo $this->viewObj->getDocCode();
			die();
		}	
		
			// set some default module if nothing was set
		if (empty($this->env['module']))	{
			if($this->env['mode'] == '123') {
				$this->env['module'] = 'installer';
				$this->passwordOK = true;
			} else {
				$this->env['module'] = $this->defaults['module'];
			}
		}
			
			// load localconf file
		$this->basicsObj->loadLocalconf();
		
			// do the login if mode is not 123
		if($this->env['mode'] != '123') {
			$this->passwordOK = false;
			
			$randomKey = $_COOKIE[$this->cookieName.'_key'];
			if(!$randomKey) {
				$randomKey = md5(uniqid(microtime()));
				setcookie($this->cookieName.'_key', $randomKey, 0, '/');		// Cookie is set

					// add a message that pops up in a JavaScript alert
				$this->viewObj->addJSmessage($this->basicsObj->getLabel('msg_setup_firstcall'));
			}
			
			$combinedKey = $_COOKIE[$this->cookieName];
			if(md5($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'].'|'.$randomKey) == $combinedKey || $this->checkPassword($randomKey)) {
				$this->passwordOK = true;
			} else {
				$this->viewObj->addContent($this->basicsObj->getLabel('label_login'), $this->loginForm());
				echo $this->viewObj->getDocCode();
			}
		}
		
		if($this->passwordOK) {
			
				// Try to connect to the database
			if ($GLOBALS['TYPO3_DB']->link === false)	{
				$moduleContent = $this->basicsObj->executeMethod(array('database', 'checkDatabaseConnect'));
			}
			
				// load module and execute main method
			$method = 'main';
			if ($this->env['method'])	{
				$method = $this->env['method'];
			}
			
				// execute given method and save the result in a local variable
				// This method is only be executed if we have database connection
			$moduleContent = $this->basicsObj->executeMethod(array($this->env['module'], $method));
			
				// check if we have to handle the module content with AJAX
			if ($this->env['ajax'] == 1)	{
				header('X-JSON: (true)');
				header('Content-type: text/html; charset=utf-8');
				
					// render errors if module returned FALSE
				if ($moduleContent == false)	{
					$moduleContent = $this->viewObj->renderErrors();
				}
				
				echo $moduleContent;
			} else {
				if($moduleContent === false) {
						// if we reach this point, something went wrong with the module. Print all collected general errors
					$this->viewObj->addContent($this->basicsObj->getLabel('msg_error_occured'), $this->viewObj->renderErrors());
					echo $this->viewObj->getDocCode();
				} else {
						// depending on the mode, we print the result directly or use the doc object				
					if($this->env['mode'] == '123') {
						echo $moduleContent['content'];
					} else {
							// add the module content to the document
						$this->viewObj->addContent($moduleContent['title'], $moduleContent['content']);
						
							// and print out the result
						echo $this->viewObj->getDocCode();
					}
				}
			}
		}
	}
	
	/**
	 * Checks whether the submitted password is ok or not.
	 * 
	 * @param	String	a random key for combination and compare
	 * @return	boolean	true if submitted password is ok, false otherwise
	 */
	private function checkPassword($randomKey) {
		$password     = t3lib_div::_GP('typo3_install_password');
		$warningEmail = $GLOBALS['TYPO3_CONF_VARS']['BE']['warning_email_addr'];
		$passwordOk   = false;
		
		if($password && md5($password) == $GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword']) {
			$combinedKey = md5($GLOBALS['TYPO3_CONF_VARS']['BE']['installToolPassword'].'|'.$randomKey);
			setcookie($this->cookieName, $combinedKey, 0, '/');

				// Sending warning email
			if($warningEmail) {
				$subject   = sprintf($this->basicsObj->getLabel('login_emailwarning_true_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
				$emailBody = sprintf($this->basicsObj->getLabel('login_emailwarning_true_message'),
					$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
					t3lib_div::getIndpEnv('HTTP_HOST'),
					t3lib_div::getIndpEnv('REMOTE_ADDR'),
					t3lib_div::getIndpEnv('REMOTE_HOST')
				);
				
				mail($warningEmail,
					$subject,
					$emailBody,
					'From: TYPO3 Install Tool WARNING <>'
				);
			}

			$passwordOk = true;
		} else {
				// Bad password, send warning:
			if($password) {
				if($warningEmail) {
					$subject   = sprintf($this->basicsObj->getLabel('login_emailwarning_false_subject'), $GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename']);
					$emailBody = sprintf($this->basicsObj->getLabel('login_emailwarning_false_subject'),
						$GLOBALS['TYPO3_CONF_VARS']['SYS']['sitename'],
						t3lib_div::getIndpEnv('HTTP_HOST'),
						$password,
						t3lib_div::getIndpEnv('REMOTE_ADDR'),
						t3lib_div::getIndpEnv('REMOTE_HOST')
					);
						
					mail($warningEmail,
						$subject,
						$emailBody,
						'From: TYPO3 Install Tool WARNING <>'
					);
				}

				$this->basicsObj->addError(sprintf($this->basicsObj->getLabel('msg_error_login_not_sucessful_message'), md5($password)), FATAL, 'fields', 'password');
			}
		}
		
		return $passwordOk;
	}

	/**
	 * [Describe function...]
	 *
	 * @return	[type]		...
	 */
	private function loginForm() {
		$formConfig = array(
			'type' => 'form',
			'value' => array(
				'options'  => array(
					'name'   => 'form_setuplogin',
					'submit' => $this->basicsObj->getLabel('label_login'),
				),
				'elements' => array(
					array(
						'type'  => 'formelement',
						'value' => array(
							'label'       => 'label_setup_password',
							'elementType' => 'password',
							'options'     => array(
								'name' => 'typo3_install_password'
							)
						)
					),
					array(
						'type'  => 'plain',
						'value' => $this->basicsObj->getLabel('msg_login_passwordhint')
					)
				)
			)
		);
		
		return $this->viewObj->render($formConfig);
	}
	
	/**
	 * gets the basics object
	 *
	 * @return	tx_install_basics
	 */
	public function getBasicsObject() {
		return $this->basicsObj;
	}
	
	/**
	 * gets the view object
	 *
	 * @return	tx_install_view
	 */
	public function getViewObject() {
		return $this->viewObj;
	}
	
	/**
	 * gets the language object
	 *
	 * @return	language
	 */
	public function getLanguageObject() {
		return $this->langObj;
	}
	
	/**
	 * gets LOCAL_LANG
	 *
	 * @return	array	arrayof language labels
	 */
	public function getLocalLang() {
		return $this->LOCAL_LANG;
	}
	
	/**
	 * Enter description here...
	 *
	 * TODO add check to $value
	 * 
	 * @param	array	$value
	 */
	public function setLocalLang($value) {
		$this->LOCAL_LANG = $value;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return	array
	 */
	public function getLoadedModules() {
		return $this->loadedModules;
	}
	
	/**
	 * adds a module to the loaded modules
	 *
	 * TODO add checks
	 * 
	 * @param	string	moduel name
	 * @param	object	module object
	 */
	public function addLoadedModule($moduleName, $moduleObject) {
		 $this->loadedModules[$moduleName] = $moduleObject;
	}
	
	/**
	 * gets the environment
	 *
	 * @return	array
	 */
	public function getEnvironment() {
		return	$this->env;
	}
	
	/**
	 * Enter description here...
	 *
	 * @return	boolean
	 */
	public function updateLocalconfAllowed() {
		return $this->allowUpdateLocalConf;
	}
	
	/**
	 * Enter description here...
	 *
	 * TODO add checks
	 * 
	 * @param	boolean	$value
	 */
	public function setAllowUpdateLocalConf($value) {
		$this->allowUpdateLocalConf = $value;
	}
	
	/**
	 * gets the backpath
	 *
	 * @return	string	backpath
	 */
	public function getBackPath() {
		return $this->backPath;
	}
	
	public function getLanguage()	{
		return $this->language;
	}
	
	public function getLabelIndex()	{
		return $_SESSION['installer']['labelIndex'];
	}
	
	public function getLabelIndexItem($index)	{
		return $_SESSION['installer']['labelIndex'][$index];
	}
	
	public function addLabelIndex($mainCat, $subCat, $deliverableType, $deliverable, $index)	{
		$_SESSION['installer']['labelIndex'][$index] = array (
			'mainCat' => $mainCat,
			'subCat' => $subCat,
			'deliverableType' => $deliverableType,
			'deliverable' => $deliverable
		);
	}
	
	public function getFilterResults()	{
		$this->filterResults = $_SESSION['installer']['filterResults'];
		return $this->filterResults;
	}
	
	public function setFilterResults($filterResults)	{
		$this->filterResults = $filterResults;
		$_SESSION['installer']['filterResults'] = $this->filterResults;
	}
	
	public function __set($property, $value) {
			// needed for comaptibility with /typo3/init.php
			// TODO fix init.php and remove this method
		switch($property) {
			case 'allowUpdateLocalConf':
				$this->allowUpdateLocalConf = (bool) $value;
				break;
//			case 'backPath':
//				$this->backPath = (string) $value;
//				break;
			default:
				// nothing
		}	
	}
}

if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install.php']);
}

?>