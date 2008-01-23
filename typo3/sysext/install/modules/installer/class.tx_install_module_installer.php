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
	 * 
	 * Each step consists of a set of methods, checks and options in various states of processing.
	 * Each step can have the following states
	 * 
	 * 	- pre:	Only checks are allowed here! This checks are executed each time a step is called. If any of
	 * 			the checks return false, the step can't be be processed.
	 * 
	 * 	- main:	The main method that is executed in this step. In most cases this will be a formconfig for
	 * 			the render engine.
	 * 
	 * 	- post:	Here are all things located that has to be executed before a step can be finished. This is
	 * 			the right place for processing and checking the input data from the main method.
	 * 			If any of this checks returns false the main state is called again.
	 * 
	 * @var array
	 */
	private $stepConfig = array (
			// these steps basically reimplements the old 1-2-3 installer with some small improvements
		
			// inital checks / no further processing
		0 => array (
			'pre' => array (
				0 => 'php:checkVersion',
				1 => 'directories:checkDirs'
			),
			'main' => array (
				0 => array (
					'type' => 'form',
					'module' => 'installer',
					'method' => 'selectLanguage'
				)
			)
		),
		
			// database connection
		1 => array (
			'main' => array (
				0 => array (
					'type' => 'form',
					'module' => 'database',
					'method' => 'databaseConnectionData'
				)
			),
			'post' => array (
				'database:connectDatabaseProcess'
			) 
		),
		
			// select / create database
		2 => array (
			'main' => array (
				array (
					'type' => 'form',
					'module' => 'database',
					'method' => 'selectDatabaseForm'
				)
			),
			'post' => array (
				'database:createDatabase'
			)
		),
		
			// create / import tables from file
		3 => array (
			'preMode' => 'skipMain',
			'pre' => array (
				'database:checkForStaticFiles'
			),
			'main' => array (
				array (
					'type' => 'form',
					'module' => 'database',
					'method' => 'selectStaticFileForm'
				)
			),
			'post' => array (
				'database:importTables'
			)
		),
		
			// create an admin user
		4 => array (
			'main' => array (
				array (
					'type' => 'form',
					'module' => 'database',
					'method' => 'createAdminForm'
				)
			),
			'post' => array (
				'database:createAdmin'
			)
		),
		
		5 => array (
			'main' => array (
				array (
					'type' => 'label',
					'index' => 'msg_step5_done'
				)
			)
		)
		
	);
	
	/**
	 * Defines which is the last mandatory step
	 *
	 * @var integer
	 */
	private $lastMandatoryStep = 5;
	
	/**
	 * Holds the current step
	 *
	 * @var integer
	 */
	private $step = null; 
	
	/**
	 * This is the main method
	 */
	public function main()	{
			// get the current step
		$this->step = (empty($this->env['step'])) ? 0 : intval($this->env['step']);
		
			// get the state
		$stepResult = $this->executeStep($this->step);
		
			// render back button if step is bigger than 1
		if ($this->step > 0)	{
			$formConfig = array (
				'type'  => 'form',
				'value' => array(
					'options' => array(
						'name'   => 'form_stepData',
						'submit' => $this->get_LL('label_prev_step'),
					),
					'hidden'  => array (
						'step' => $this->step-1,
						'mode' => '123',
						'state' => 'pre'
					),
				)
			);

			$btnBack = $this->pObj->getViewObject()->render($formConfig);
		} else {
			$btnBack = '';
		}
		
			// build-up the output
		$marker = array (
			'###STEP###' => sprintf($this->get_LL('label_step'), $this->step),
			'###PROGRESS###' => $this->getProgress(),
			'###MSG_TITLE###' => $this->get_LL('msg_step'.$this->step.'_title'),
			'###MSG_DESCRIPTION###' => $this->get_LL('msg_step'.$this->step.'_description'),
			'###BTN_BACK###' => $btnBack,
			'###CONTENT###' => $stepResult,
			'###PREV_RESULT###' => $this->pObj->getViewObject()->getLastMessage(true)
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
	
	/**
	 * Executes all states of a certain step.
	 */
	private function executeStep()	{
			// let's check if the step is defined, die if not
		if (!isset($this->stepConfig[$this->step]))	{
			$this->addError(sprintf($this->get_LL('msg_error_stepnotdefined'), $this->step), FATAL);
			return false;
		}
		
			// process pre state - result is true if this state is not configured
		if (isset($this->stepConfig[$this->step]['pre']))	{
			$preResult = $this->executeStepState('pre');
		} else {
			$preResult = true;
		}
		
			// exit if pre checks are not sucessfull
		if ($preResult === false)	{
				// depending on preMode we only skip state main or stop here immediately
			if (!isset($this->stepConfig[$this->step]['preMode']) &&Ê($this->stepConfig[$this->step]['preMode'] != 'skipMain'))	{
				return false;
			} else {
				$this->env['state'] = 'post';
			}
		}
		
			// if state is post, execute that state
		$processResult = true;
		if ($this->env['state'] == 'post')	{
				// execute process methods (has to be configured)
			$processResult = $this->executeStepState('post');
			$this->env['state'] = 'pre';
			
				// go to next step if process was ok
			if ($processResult === true)	{
				$this->step++;
				return $this->executeStep();
			}
		}
		
			// execute main state (has to be configured!)
		$mainResult = $this->executeStepState('main');
				
		if ($mainResult === false)	{
			return false;
		}
		
		return $mainResult;
	}
	
	
	/**
	 * Executes a certain state in a step. It dispatches all the config to the particular handlers.
	 * For example rendering forms, or calling the methods in that specific module.
	 *
	 * @param	integer		$state: The state that has to be executed
	 * @return	The collected results of all given methods (In state pre and post it returns false if any of the methods returns false!) 
	 */
	private function executeStepState($state)	{
		$stateConfig = $this->stepConfig[$this->step][$state];
		
			// check if there is any config for this state in this particular step
		if (!is_array($stateConfig))	{
			if ($state == 'pre' || $state == 'post')	{
					// state pre and post don't have to be set, if they are not configured we estimate that everything is ok and return true
				return true;
			} else {
				$this->addError(sprintf($this->get_LL('msg_error_stepstatenotdefined'), $state, $this->step), FATAL);
				return false;
			}
		}
		
			// prepare result streams
		$formElements = array('normal' => NULL, 'advanced' => NULL);
		$stateResult = '';
		
			// cycle through the various methods defined for this state
		foreach ($stateConfig as $index => $stateMethod)	{
			$stateMethodResult = $this->dispatchStateMethod($stateMethod, $formElements);
			
			if ($state == 'main')	{
				if ($stateMethodResult == false)	{
					return false;
				}
				if (is_string($stateMethodResult))	{
					$stateResult .= $stateMethodResult;
				}
			} else {
				$stateResult = $stateMethodResult;
				
					// exit cycle if in pre or post state any state method returned false 
				if ($stateResult === false)	{
					break;
				}
			}
		}
		
			// render form if any fields are set
		if ($state == 'main' && (is_array($formElements['normal']) || is_array($formElements['advanced'])))	{
			$elements = array(array(
				'type' => 'formelement',
				'value' => array (
					'elementType' => 'fieldset',
					'label' => $this->get_LL('label_normalFields')
				)
			));
			
			$elements = array_merge($elements, $formElements['normal']);
			
			if (is_array($formElements['advanced']))	{
				$elements[] = array (
					'type' => 'formelement',
					'value' => array (
						'elementType' => 'fieldset',
						'label' => $this->get_LL('label_advancedFields'),
						'class' => 'fieldset-advanced'
					)
				);
				$elements = array_merge($elements, $formElements['advanced']);
			}
			
			$formConfig = array (
				'type'  => 'form',
				'value' => array(
					'options' => array(
						'name'   => 'form_stepData',
						'submit' => $this->get_LL('label_next_step'),
					),
					'hidden'  => array (
						'step' => $this->step,
						'mode' => '123',
						'state' => 'post'
					),
					'elements' => $elements
				)
			);

			$stateResult = $this->pObj->getViewObject()->render($formConfig).$stateResult;
		}
		
		return $stateResult;
	}
	
	/**
	 * Receives a state config and executs the respective method and returns the result
	 *
	 * @param mixed $stateMethod: The method to call and the optional the type.
	 * @param array $formElements: An array that collects the form elements for the step (PASS BY REFERENCE)
	 * @return False if somethign went wrong, otherwise the result of the called method
	 */
	private function dispatchStateMethod($stateMethod, &$formElements) {
		$result = true;
		if ($stateMethod['type'] == 'label') {
			$methodResult = nl2br($this->get_LL($stateMethod['index']));
		} else {
			if (is_string($stateMethod)) {
				$methodResult = $this->pObj->getBasicsObject()->executeMethod($stateMethod);
			} else {
				$methodResult = $this->pObj->getBasicsObject()->executeMethod(array($stateMethod['module'], $stateMethod['method']));
			}
		}
		
		if ($methodResult === false) {
			return false;
		}
		
		switch ($stateMethod['type']) {
			case 'form':
				$formElements = array_merge($formElements, $methodResult);
				break;
			default:
				$result = $methodResult;
				break;
		}
		
		return $result;
	}
	
	
	/**
	 * Returns an XHTML fragment with a progressbar, that shows the progress in the installation process.
	 *
	 * @return	string
	 */
	private function getProgress()	{
		$progressBar = '<div id="progressbar"><div class="mandatory" ';
		$progressBar .= 'style="width:'.(100 /count($this->stepConfig) *$this->step).'%"></div></div>';
		return $progressBar;
	}
	
	/*
	 * STEP METHODS
	 */
	
	/**
	 * Shows the welcome screen.
	 *
	 */
	public function welcomeScreen()	{
		return 'Hi';
	}
	
	/**
	 * provides a selectbox with all available languages
	 */
	public function selectLanguage()	{
		$languageData = $this->pObj->getLanguageObject()->includeLLFile('EXT:setup/mod/locallang.xml', false);
		$this->pObj->setLocalLang(t3lib_div::array_merge_recursive_overrule($this->pObj->getLocalLang(), $languageData));
				
		$theLanguages = t3lib_div::trimExplode('|',TYPO3_languages);
		foreach ($theLanguages as $val)	{
			$opt[$val] = $this->pObj->getBasicsObject()->getLabel('lang_'.$val);
		}
		
		$elements['normal'] = array (
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
		);
		
		return $elements;
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/installer/class.tx_install_module_installer.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/installer/class.tx_install_module_installer.php']);
}
?>
