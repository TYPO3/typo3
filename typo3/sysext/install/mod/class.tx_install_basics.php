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

/**
 * This provides some basic methods that are needed by the install tool and it's modules.
 *
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 * @author	Ingo Renner	<ingo@typo3.org>
 */
class tx_install_basics	{
		// Private
	private $localconfCache    = array();	// contains cached localconf file
	private $localconfModified = false;	// boolean: set to true if localconf needs to be saved
	
	/**
	 * parent tx_install object
	 *
	 * @var	tx_install
	 */
	private $pObj;

	private $categoryData       = NULL;
	private $moduleDeliverables = array('checks', 'options', 'methods');

	/**
	 * Initializes the object.
	 * 
	 * TODO move this into a __construct() method
	 * 
	 * @param	object	The installer object. Needed to get access to some variables
	 * @return	void
	 */
	public function init($pObj)	{
		$this->pObj = $pObj;
	}

	/**
	 * Get's a label from the LOCAL_LANG variable in the main-class. This is a wrapper so that
	 * none has to know where the labels are stored internally.
	 * 
	 * @param	string		$label: The name of the label
	 * @param	string		$alternative: A string the is returned if no label was found for the index
	 * @return	The label from the locallang data
	 */
	public function getLabel($label, $alternative = '')	{
		$langObj = $this->pObj->getLanguageObject();
		
		if(substr($label, 0, 4) == 'LLL:') {
			$label = substr($label, 4);
		}
		
		$resultLabel = $langObj->getLLL($label, $this->pObj->getLocalLang());

		return (empty($resultLabel)) ? $alternative : $resultLabel;
	}
	
	/**
	 * Retrieves the absolute path to a module.
	 * 
	 * @param	string		$moduleName: The name of the module
	 * @return	mixed		The absolute path to the module or FALSE if the module does not exist
	 */
	public function getModulePath($moduleName)	{
		$moduleClass = $this->getModuleClass($moduleName);
		
		$returnValue = false;
		if($moduleClass) {
			$returnValue = $this->getModulepathFromClass($moduleClass);
		}
		
		return $returnValue;
	}
	
	/**
	 * Enter description here...
	 *
	 * @param unknown_type $moduleClass
	 * @return	string		module path
	 */
	private function getModulePathFromClass($moduleClass)	{
		$moduleClass = t3lib_div::revExplode(':', $moduleClass, 2);
		$moduleClass = dirname($moduleClass[0]);
		
		return $moduleClass . '/';
	}
	
	/**
	 * Retrieves the path to a module that is reachable from a browser.
	 * 
	 * @param	string		$moduleName: The name of the module
	 * @return	mixed		The web path to the module or FALSE if the module does not exist
	 */
	private function getModuleWebPath($moduleName)	{
		$moduleClass = $this->getModuleClass($moduleName);
		
		$returnValue = false;
		if($moduleClass) {
			$returnValue = $this->getInstallerWebPath().'modules/'.$moduleName.'/';
		}
		
		return $returnValue;
	}
	
	
	/**
	 * Checks if a module exists or not.
	 * 
	 * @param	string		name of the moldule that should be checked
	 * @return	mixed		The module class or false if the module was not registered.
	 */
	private function getModuleClass($moduleName)	{
		$moduleClass = $GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['modules'][$moduleName];
		$returnValue = false;

		if(empty($moduleClass))	{
			$this->addError(sprintf($this->getLabel('msg_error_noclassfile'), $moduleClass, $moduleName), FATAL);
		} else {
			$returnValue = $moduleClass;
		}
		
		return $returnValue;
	}
	
	public function getModuleDeliverables()	{
		return $this->moduleDeliverables;
	}
	
	
	/**
	 * Loads the config of a module and returns it in the result. If the config was already loaded
	 * the config is not loaded again.
	 * 
	 * @param	string		$moduleName: The name of the requested module
	 * @return				The
	 */
	public function loadModuleConfig($moduleName)	{
		if(!is_array($GLOBALS['MCA'][$moduleName]))	{
				// get the path to the module
			$modulePath = $this->getModulePath($moduleName);
			
				// try to load config or return error
			if(!file_exists($modulePath.'conf.php')) {
				$this->addError(sprintf($this->getLabel('msg_error_nomoduleconfig'), $moduleName), FATAL);
				return false;
			}
			
			require_once($modulePath.'conf.php');
		}
		
		return $GLOBALS['MCA'][$moduleName];
	}
	
	
	/**
	 * Instanciates a module and returns the instance
	 * 
	 * @param	string		$moduleName: The name of the module that should be loaded
	 * @return	mixed		the request module or boolean false on failure
	 */
	public function loadModule($moduleName) {
		$moduleClassName = $this->getModuleClass($moduleName);
		$returnValue     = false;
		
		if($moduleClassName) {
			$modules = $this->pObj->getLoadedModules();
			if(!is_object($modules[$moduleName]))	{
				$res = $this->loadModuleConfig($moduleName);
				if($this->loadModuleConfig($moduleName) !== false) {
						// try to load the language data before we load the module
					$this->loadModuleLocallang($moduleName);

						// Now load the module
					$module = t3lib_div::getUserObj($moduleClassName);
					$module->init($this->pObj);
					$this->pObj->addLoadedModule($moduleName, $module);
					
					$returnValue = $module;
				}
			} else {
				$returnValue = $modules[$moduleName];
			}
		}
		
		return $returnValue;
	}
	
	public function loadModuleLocallang($moduleName)	{
		$modulePath = t3lib_extMgm::extPath('install').'modules/'.$moduleName.'/';

			// try to load the language data before we load the module
		$languageFile = $modulePath.'locallang.xml';
		if(file_exists($languageFile)) {
			$languageData = $this->pObj->getLanguageObject()->includeLLFile($languageFile, false);
			$this->pObj->setLocalLang(t3lib_div::array_merge_recursive_overrule($this->pObj->getLocalLang(), $languageData));
		}
	}
	
	
	/**
	 * Executes a requested method.
	 * The method has to be given in combination with the module. It can be given by strinf or array. If type is
	 * string the format is modulename:methodname. If the type is an array, the first array element has to be the
	 * module name and the second has to be the methodname.
	 * 
	 * The module will be instaciated automatically if it is not already instanciated.
	 * 
	 * @param	mixed		$method: The module and the method name
	 * @param	mixed		$args: If this is not NULL this argument will be passed to the called method as is
	 * @return	mixed		The return value of the method or false if something went wrong 
	 */
	public function executeMethod($method, $args = NULL) {
		$returnValue = false;
		
		if(!is_array($method)) {
			$method = t3lib_div::trimExplode(':', $method);
		}
		
		$moduleName = $method[0];
		$methodName = $method[1];
		$moduleObj  = $this->loadModule($moduleName);
		
		if(!$moduleObj) {
			$this->addError(sprintf($this->getLabel('msg_error_modulenotloaded'), $moduleName), FATAL);
			$returnValue = false;
		} else if(!method_exists($moduleObj, $methodName)) {
			$this->addError(sprintf($this->getLabel('msg_error_methodnotfound'), $methodName, $moduleName), FATAL);
			$returnValue = false;
		} else if (is_null($args)) {
			$returnValue = $moduleObj->$methodName();
		} else {
			$returnValue = $moduleObj->$methodName($args);
		}
		
		return $returnValue;
	}
	
	
	/**
	 * Builds an array with all categories and sub-catagories of all registered modules.
	 * The category tree is saved in a class variable and is returned from there if it already
	 * exists.
	 * 
	 * @return	array	module category data
	 */
	public function getModuleCategoryData() {
		$returnValue = false;
		
		if(!is_null($this->categoryData)) {
			$returnValue = $this->categoryData;
		} else if(!is_array($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['modules'])) {
			$this->addError($this->getLabel('msg_error_nomodregister'));
		} else {
			foreach($GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['ext/install']['modules'] as $modName => $modClass) {
				$modConfig = $this->loadModuleConfig($modName);
				
				$this->loadModuleLocallang($modName);
				
				if($modConfig === false) {
					continue;
				}

				foreach ($this->moduleDeliverables as $deliverable) {
					
					if(is_array($modConfig[$deliverable])) {
						foreach ($modConfig[$deliverable] as $name => $config) {
							if(empty($config['categoryMain'])) {
								continue;
							}
								// finally store the stuff
							if (empty($config['categorySub']))	{
								$config['categorySub'] = 'root';
							}

							$this->categoryData[$config['categoryMain']][$config['categorySub']][$deliverable][$name] = $modName;
								
								// add the labels to the label index
							$this->pObj->addLabelIndex($config['categoryMain'], $config['categorySub'], $deliverable, $name, $config['title']);
							$this->pObj->addLabelIndex($config['categoryMain'], $config['categorySub'], $deliverable, $name, $config['help']);
							$this->pObj->addLabelIndex($config['categoryMain'], $config['categorySub'], $deliverable, $name, $config['description']);
						}
					}
				}
			}
			
			$returnValue = $this->categoryData;
		}
		
		return $returnValue;
	}
	
	/**
	 * Returns all options, checks or methods that are assigned to the selected category.
	 */
	public function getCategoryModuleDeliverables($categoryMain, $categorySub = null)	{
		$result = false;
		$categoryData = $this->getModulecategoryData();
		if (!isset($categoryData[$categoryMain]))	{
			$this->addError(sprintf($this->getLabel('msg_warning_nomaincat'), $categoryMain), FATAL);
		}
		
		$result = $categoryData[$categoryMain];
		if (!is_null($categorySub))	{
			if (!isset($result[$categorySub]))	{
				$this->addError(sprintf($this->getLabel('msg_warning_nosubcat'), $categorySub));
				$result = false;
			} else {
				$result = $result[$categorySub];
			}
		}
		
		return $result;
	}
	
	public function getModuleDeliverableConfigs($modName, $deliverable)	{
		$categoryData = $this->getModuleCategoryData();
		t3lib_div($categoryData);
	}


	/**
	 * Retrieves the absolute path to the installer that is reachable with a webbrowser
	 *
	 * @return	string		Web-path to the installer
	 */
	public function getInstallerWebPath() {
		return t3lib_div::getIndpEnv('TYPO3_SITE_URL').$GLOBALS['TYPO3_LOADED_EXT']['install']['siteRelPath'];
	}
	
	
	/**
	 * Retrieves the saved password in localconf
	 * 
	 * return	string		The password (md5 hash)
	 */
	private function getPassword() {
		if(isset($this->localconfCache['data']['BE']['installToolPassword'])) {
			$installToolPassword = $this->localconfCache['data']['BE']['installToolPassword'];
		} else {
			$installToolPassword = $this->localconfCache['userSettings']['data']['BE']['installToolPassword'];
		}
		
		return $installToolPassword;
	}
	
	
	/**
	 * Tries to login with a given password. The password has to be submitted as plain text. The generation
	 * of the md5 hash will be created as first step inside the method.
	 * If the login does not work, an error message is saved in $this->errorMessage. This message is
	 * compatible to RENDER-OBJECT::MESSAGE
	 * 
	 * @param	string		The plaintext password
	 * @return	boolean		true on successfull log in, false on failure
	 */
	private function doLogIn($password) {
		$md5Password  = md5($password);
		$loginSuccess = false;

		if($md5Password == $this->getPassword()) {
			$_SESSION['installTool']['password'] = $md5Password;
			$loginSuccess = true;
		} else {
			$this->statusMessage = array(
				'severity' => 'error',
				'label' => $this->getLabel('login_not_sucessful'),
				'message' => sprintf($this->getLabel('login_not_sucessful_message'), $md5Password)
			);
		}
		
		return $loginSuccess;
	}
	
	/**
	 * Checks if a valid login is present.
	 * This method checks the data from localconf and 
	 */
	private function checkLogIn() {
		$environment = $this->pObj->getEnvironment();
		return ($environment['installTool']['password'] === $this->getPassword());
	}
	

	/**
	 * Creates a link for the current module and adds all given parameters to it
	 * 
	 * @param	array		$params: A list of parameter names. The values will be retrieved from the environment
	 * @param	array		$paramValues: A list of key value pairs that will overrule the values in the resultstring
	 * @return	string		The URL to the module
	 */
	private function getModuleLink_URL($params, $paramValues = array()) {
		$moduleURL     = 'index.php?';
		$environment   = $this->pObj->getEnvironment();
		$parameterList = array('module' => $environment['module']);
			
			// compile a list of params from the values inside the environment
		if(is_array($params)) {
			foreach($params as $param) {
				$parameterList[$param] = $environment[$param];
			}
		}
		
			// merge the list of parameters for overrule
		$parameterList = t3lib_div::array_merge_recursive_overrule($parameterList, $paramValues);
		
			// build URL string
		$urlParameter = array();
		foreach ($parameterList as $parameter => $parameterValue) {
			$urlParameter[$parameter] = $parameter.'='.$parameterValue;
		}
		
			// create result string
		$moduleURL .= implode('&', $urlParameter);
		
		return $moduleURL;
	}
	
	
	/**
	 * Returns a full link with.
	 * 
	 * @see	getModuleLink_URL
	 * 
	 * @param	string		$title: The title that is wrapped by the a-tag
	 * @param	array		$tagAttributes: A list of parameters that will be used for the tag
	 * @param	array		$params: A list of parameter names. The values will be retrieved from the environment
	 * @param	array		$paramValues: A list of key value pairs that will overrule the values in the resultstring
	 * @return	string		The complete HTML code for a link
	 */
	private function getModuleLink($title, $tagAttributes, $params, $paramValues = array()) {
		$url        = $this->getModuleLink_URL($params, $paramValues);
		$moduleLink = '<a href="'.$url.'"';

		if(is_array($tagAttributes)) {
			foreach ($tagAttributes as $attrName => $attrValue) {
				$moduleLink .= ' '.$attrName.'="'.$attrValue.'"';
			}
		}
		$moduleLink .= '>'.$title.'</a>';
		
		return $moduleLink;
	}
	

	/**
	 * Modifies a localconf setting.
	 *
	 * Expects an array (multi-dimensional) which should be inserted/updated into localconf.
	 * @param	array		localconf array of values which should be changed
	 * @return	void
	 */
	private function modifyLocalconf($overrideArr) {
			// if localconf cache is empty, load localconf file
		if(!count($this->localconfCache)) {
			$this->loadLocalconf();
		}

			// update data part
		$this->localconfCache['data'] = t3lib_div::array_merge_recursive_overrule($this->localconfCache['data'], $overrideArr);
		$this->localconfModified = 1;
	}

	
	/**
	 * Writes a value to the localconf cache at the given path.
	 *
	 * @param string $path
	 * @param string $value
	 * @param string $type: The type of the value
	 */
	public function addToLocalconf($path, $value, $type)	{
		// var_dump(array('addToLocalconf', $value));
		$path = t3lib_div::trimExplode(':', $path);
		
		if (!empty($type))	{
			settype($value, $type);
		}
		
		if ($path[0] == 'LC')	{
			array_shift($path);
		}
		
		if ($path[0] == 'db')	{
			$this->addDbDataToLocalconf(array($path[1] => $value));
		}
		
		$path = t3lib_div::trimExplode('/', $path[0]);
		
		$data = &$this->localconfCache['data'];
		foreach ($path as $pathSegment)	{
			$data = &$data[$pathSegment];
		}
		$data = $value;
		
		$this->localconfModified = 1;
	}
	
	/**
	 * Adds special database data to localconf. This is a special method because database
	 * data is not stored in an array but plain in localconf file.
	 * Sets this->localconfModified to true!
	 *
	 * @param array $dbSettings associative array with optionname => optionvalue
	 */
	public function addDbDataToLocalconf($dbSettings) {
		if(!count($this->localconfCache)) {
			$this->loadLocalconf();
		}
		
		foreach ($dbSettings as $varName => $varValue) {
			$this->localconfCache['db'][$varName] = $varValue;
		}
		
		$this->localconfModified = 1;
	}
	
	
	/**
	 * Returns the value of value in the localconf file addressed by a path
	 *
	 * @param	string $path to value
	 * @param	string $default data if localconf value in path is empty
	 * @return mixed value stored in localconf.php
	 */
	public function getLocalconfValue($path, $default = '') {
		$path = t3lib_div::trimExplode(':', $path);

		if ($path[0] == 'LC')	{
			array_shift($path);
		}
		
		if($path[0] == 'db') {
			$data = $this->localconfCache['db'][$path[1]];
		} else {
			$data = $this->localconfCache['data'];
			
			$path = t3lib_div::trimExplode('/', $path[0]);
			if(is_array($path)) {
				foreach ($path as $pathElement) {
					$data = $data[$pathElement];
				}
			}
			
			if (!isset($data))	{
				$data = $default;
			}
		}

		return $data;
	}


	public function getLocalconfPath($mainSec, $opName = NULL)	{
		$result = '';
		if ($opName != NULL)	{
			$result = '$TYPO3_CONF_VARS[\''.$mainSec.'\'][\''.$opName.'\']';
		} else {
			$path = t3lib_div::trimExplode(':', $mainSec);
			if ($path[0] == 'LC')	{
				array_shift($path);
			}
			if($path[0] == 'db') {
				$result = '$'.$path[1];
			} else {
				$path = t3lib_div::trimExplode('/', $path[0]);
				$result = '$TYPO3_CONF_VARS[\''.$path[0].'\'][\''.$path[1].'\']';
			}
		}
		
		return $result;
	}
	
	/**
	 * Saves the localconf to file. Needs to be called from somewhere within the framework
	 *
	 * @param	string		absolute path to localconf file. If empty, defaults to typo3conf/localconf.php
	 * @return	boolean
	 */
	public function saveLocalconf($file = '')	{
		$hasNoErrors = true;
		
		if($this->localconfModified)	{
			$viewObj = $this->pObj->getViewObject();
			
				// build up file contents
			$fileContents = '<?php'.$this->localconfCache['userSettings']['string']."\n".
				$this->localconfCache['installToolToken']."\n\n";
	
				// save db data
			if(is_array($this->localconfCache['db'])) {
				foreach ($this->localconfCache['db'] as $variableName => $variableValue) {
					$fileContents .= '$'.$variableName.' = \''.stripslashes($variableValue).'\';'."\n";
				}
				$fileContents .= "\n";
			}
				
				// and the TYPO3_CONF_VARS
			if(count($this->localconfCache['data']) > 0)	{
				foreach ($this->localconfCache['data'] as $mainSec => $secOptions)	{
					if (is_array($secOptions))	{
						foreach ($secOptions as $secOptionsName => $secOptionsValue)	{
							$fileContents .= $this->getLocalconfPath($mainSec, $secOptionsName).' = '.var_export($secOptionsValue, true).';'.chr(10);
						}
					}
					$fileContents .= chr(10);
				}
			}
			$fileContents .= chr(10).'?>';
			
				// initialize saving of localconf
			$tmpExt  = '.TMP.php';
			$file    = $file ? $file : PATH_typo3conf.'localconf.php';
			$tmpFile = $file.$tmpExt;
	
				// Checking write state of localconf.php
			if(!$this->pObj->updateLocalconfAllowed()) {
				$viewObj->addError($this->getLabel('msg_noallowupdate_flag'), FATAL);
				$hasNoErrors = false;
			}
			
			if($hasNoErrors && !@is_writable($file)) {
				$viewObj->addError(sprintf($this->getLabel('msg_filenotwriteable'), $file), FATAL);
				$hasNoErrors = false;
			}
	
				// write localconf!
			if ($hasNoErrors)	{
				if (!t3lib_div::writeFile($tmpFile,$fileContents))	{
					$viewObj->addError(sprintf($this->getLabel('msg_filenotwriteable'), $tmpFile), FATAL);
					$hasNoErrors = false;
				} else if(strcmp(t3lib_div::getUrl($tmpFile), $fileContents)) {
					@unlink($tmpFile);
					$viewObj->addError(sprintf($this->getLabel('msg_error_filenotmatch'), $tmpFile), FATAL);
					$hasNoErrors = false;
				} else if(!@copy($tmpFile,$file)) {
					$viewObj->addError(sprintf($this->getLabel('msg_error_filenotcopy'), $file, $tmpFile), FATAL);
					$hasNoErrors = false;
				} else {
					@unlink($tmpFile);
					$viewObj->addMessage(sprintf($this->getLabel('label_localconfwritten'), $file));
				}
			}
		}
		
		return $hasNoErrors;
	}

	/**
	 * Loads localconf file
	 *
	 * @param	string		absolute path to localconf file. If empty, defaults to typo3conf/localconf.php
	 * @return void
	 * @private
	 */
	public function loadLocalconf($file = '') {
		$TYPO3_CONF_VARS = array();	// needs to be declared LOCALLY

			// load file
		$file         = $file ? $file:PATH_typo3conf.'localconf.php';
		$fileContents = str_replace(chr(13),'',trim(t3lib_div::getUrl($file)));

			// split file by the install script edit point token
		if (preg_match('/<?php(.*?)\n((.*?)INSTALL SCRIPT EDIT POINT TOKEN(.*?))\n(.*)\?>$/s', $fileContents, $matches));

			// get user settings
		eval($matches[1]);
		$userSettings    = $TYPO3_CONF_VARS;
		$TYPO3_CONF_VARS = array();

			// eval PHP code -> the local variable $TYPO3_CONF_VARS is set now
		eval($matches[5]);

			// save cache
		$this->localconfCache = array (
			'userSettings'     => array(
				'string' => $matches[1],
				'data'   => $userSettings
			),
			'installToolToken' => $matches[2],
			'data'             => $TYPO3_CONF_VARS,
			'db'               => array (
				'typo_db_host'     => $typo_db_host,
				'typo_db_username' => $typo_db_username,
				'typo_db_password' => $typo_db_password,
				'typo_db'          => $typo_db
			)
		);
	}

	
	/**
	 * Adds an error to the global view object.
	 * 
	 * @param	string		$errorMsg: The error message or a label index (if prepended with LLL: its treated like a locallang label)
	 * @param	integer		$errorSeverity: The severity of the error (defined in view object!)
	 * @param	string		$errorContext: The context of the error (general or fields)
	 * @param	string		$errorField: The field where the error occured if errorContext is field
	 * @param	boolean		$onTop: If true, the error message is inserted on top of the list and not at the end
	 * @return	void
	 */
	public function addError($errorMsg, $errorSeverity = WARNING, $errorContext = 'general', $errorField = NULL, $onTop = false) {
		$viewObj = $this->pObj->getViewObject();
		
		if(substr($errorMsg, 0, 4) == 'LLL:') {
			$errorMsg = $this->getLabel(substr($errorMsg, 4));
		}
		$error = array('severity' => $errorSeverity, 'message' => $errorMsg);
		
		switch ($errorContext) {
			case 'fields':
				$viewObj->addError($errorContext, $error, $errorField, $onTop);
				break;
			case 'general':
			default:
				$viewObj->addError($errorContext, $error, '', $onTop);
				break;
		}
	}
	
	/**
	 * gets the localconf cache
	 *
	 * @return	array
	 */
	public function getLocalconfCache() {
		return $this->localconfCache;
	}
}

if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_basics.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_basics.php']);
}
?>
