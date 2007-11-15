<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2007 Thomas Hempel (thomas@work.de)
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

class tx_install_module_installer extends tx_install_module_base	{
	private $moduleKey = 'installer';
	
	/**
	 * Defines the steps for the installation.
	 * The format is the same as for calling methods: module:method
	 *
	 * @var array
	 */
	private $steps = array (
			// these steps basically reimplements the old 1-2-3 installer with some small improvements
		// 1 => 'installer:initialChecks',
		1 => 'installer:selectLanguage',
		
		2 => 'database:connectDatabase',
		3 => 'database:createDatabase', 
		4 => 'database:createTables',
		5 => 'database:createAdmin',
		6 => 'installer:getBasicSettingsAndFinish'
		
			// here we define some optional steps
	);
	
	/**
	 * Here is a list of all checks that have to be performed at the very beginning of the installation
	 * The format is the same as for calling methods: module:method
	 *
	 * @var array
	 */
	private $initialChecks = array (
		'directories:checkDirs'
	);
	
	/**
	 * Defines which is the last mandatory step
	 *
	 * @var integer
	 */
	private $lastMandatoryStep = 6;
	
	/**
	 * This is the main method
	 */
	public function main()	{
		$this->step = (empty($this->env['step'])) ? 1 : intval($this->env['step']);
		
			// do we call first time or do we have to process?
		if ($this->env['action'] == 'process')	{
			$stepResult = $this->executeStep($this->step, true);
			if ($stepResult)	{
				$this->step++;
			}
		}

			// call the step (this can be current or next one)
		$stepContent = $this->executeStep($this->step);
		
			// build-up the output
		$marker = array (
			'###STEP###' => sprintf($this->get_LL('label_step'), $this->step),
			'###PROGRESS###' => $this->getProgress(),
			'###MSG_TITLE###' => $this->get_LL('msg_step'.$this->step.'_title'),
			'###MSG_DESCRIPTION###' => $this->get_LL('msg_step'.$this->step.'_description'),
			'###CONTENT###' => $stepContent
		);
		
		if (count($this->pObj->getViewObject()->getErrors('general')) > 0)	{
			$marker['###ERRORS_GENERAL###'] =  $this->pObj->getViewObject()->renderErrors(true);
		} else {
			$marker['###ERRORS_GENERAL###'] = '';
		}
		
		$result =  array(
			'title' => '',
			'content' => $this->pObj->getViewObject()->render(array (
			'type' => 'html',
				'value' => array(
					'template' => implode('', file($this->pObj->getBasicsObject()->getModulePath($this->moduleKey).'res/tpl_step.html')),
					'marker' => $marker
				)
			))
		);
		
		return $result;
	}
	
	private function executeStep($stepIndex, $process = false)	{
			// let's check if the step is defined, die if not
		if (!isset($this->steps[$stepIndex]))	{
			die($this->get_LL('msg_error_stepnotdefined'));
		}
		
			// get the information where the step logic is defined
		list($stepModuleName, $stepMethodName) = t3lib_div::trimExplode(':', $this->steps[$stepIndex]);
		$stepModule = $this->pObj->getBasicsObject()->loadModule($stepModuleName);
		
			// die if the module could not be loaded
		if (!$stepModule)	{
			die(sprintf($this->get_LL('msg_error_modulenotfound'), $stepModuleName));
		}
		
			// search for the processMethod if process flag is set
		if ($process)	{
			$stepMethodName .= 'Process';
		}
		
			// die if method is not defined in module
		if (!method_exists($stepModule, $stepMethodName))	{
			if ($process)	{
				return true;
			} else {
				die(sprintf($this->get_LL('msg_error_methodnotfound'), $stepMethodName, $stepModuleName));
			}
		}
		
			// create an array with environment variables that have to be sent via hidden fields
		$staticFields = array (
			'step' => $this->step,
			'mode' => '123',
			'action' => 'process'
		);
		
		$environment = $this->pObj->getEnvironment();
		if (isset($environment['L'])) {
			$staticFields['L'] = $environment['L'];
		}
		
			// now everything is checked and we can call the method and return the result
		return $stepModule->$stepMethodName($staticFields);
	}
	
	
	private function getProgress()	{
		$progressBar = '<div id="progressbar"><div class="';
		$progressBar .= ($this->step <= $this->lastMandatoryStep) ? 'mandatory' : 'optional';
		$progressBar .= '" style="width:'.(100 /count($this->steps) *$this->step).'%"></div></div>';
		return $progressBar;
	}
	
	/*
	 * STEP METHODS
	 */
	
	private function initialChecks($staticFields)	{
		$checkString = '';
		foreach ($this->initialChecks as $check)	{
			$result = $this->pObj->getBasicsObject()->executeMethod($check);
			if ($result)	{
				$checkString .= $this->pObj->getViewObject()->getLastMessage(true);
			} else {
				$checkString .= $this->pObj->getViewObject()->renderErrors(true);
			}
		}
		return $checkString;
		// t3lib_div::debug(array($this->pObj->getViewObject()->getLastMessage(true)));
	}
	 
	/**
	 * provides a selectbox with all available languages
	 */
	private function selectLanguage($staticFields)	{
		$languageData = $this->pObj->getLanguageObject()->includeLLFile('EXT:setup/mod/locallang.xml', false);
		$this->pObj->setLocalLang(t3lib_div::array_merge_recursive_overrule($this->pObj->getLocalLang(), $languageData));
				
		$theLanguages = t3lib_div::trimExplode('|',TYPO3_languages);
		foreach ($theLanguages as $val)	{
			$opt[$val] = $this->pObj->getBasicsObject()->getLabel('lang_'.$val);
		}
		
		$formConfig = array (
			'type' => 'form',
			'value' => array (
				'options' => array (
					'name' => 'form_getlanguage',
					'submit' => $this->pObj->getBasicsObject()->getLabel('label_next_step'),
				),
				'hidden' => $staticFields,
				'elements' => array (
					array (
						'type' => 'formelement',
						'value' => array (
							'label' => 'label_available_languages',
							'elementType' => 'selectbox',
							'options' => array (
								'name' => 'L',
								'elements' => $opt
							)
						)
					)
				)
			)
		);
		
		return $this->pObj->getViewObject()->render($formConfig);
	}
	
	private function getBasicSettingsAndFinish($staticFields)	{
		
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/installer/class.tx_install_module_installer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/installer/class.tx_install_module_installer.php']);
}
?>
