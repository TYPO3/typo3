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
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 * @author	Ingo Renner	<ingo@typo3.org>
 */
class tx_install_module_base {
	
	/**
	 * parent tx_install object
	 *
	 * @var	tx_install
	 */
	protected $pObj;
	
	/**
	 * Global environment from installer
	 *
	 * @var array
	 */
	protected $env;
	
	/**
	 * A reference to the basics object from parent
	 *
	 * @var tx_install_basics
	 */
	protected $basicsObject;
	
	/*
	 * API FUNCTIONS
	 */
	
	/**
	 * Instanciates the module object. The parent object is set here.
	 * 
	 * @param	mixed		$pObj: The parent object 
	 */
	public function init($pObj)	{
		$this->pObj = &$pObj;
		$this->basicsObject = &$this->pObj->getBasicsObject();
		$this->env = $this->pObj->getEnvironment();
	}
	
	/**
	 * Simple wrapper method for basicsObj->getLabel
	 * 
	 * @param	string		$index: The index of the requested label
	 * 
	 * @return	Locallang label or $index if no label was found
	 */
	protected function get_LL($index, $alternative = '')	{
		return $this->basicsObject->getLabel($index, $alternative);
	}
	
	
	/**
	 * Adds an error to the basics object. This data can be used by the view later on.
	 * 
	 * @param	string		$errorMsg: The error message itself
	 * @param	integer		$errorSeverity: The severity of the error (WARNING or FATAL) [default: WARNING]
	 * @param	string		$errorContext: The context in which the error occured. This can be "general" or "fields". [default: general]
	 * @param	string		$errorField: If the context is "fields" the fieldname has to be set here. So the error can be displayed aboce that specific field.
	 * @param	boolean		$getLL: If true, the errorMessage is treated as index in a locallang file
	 * 
	 * @return	void
	 */
	protected function addError($errorMsg, $errorSeverity = WARNING, $errorContext = 'general', $errorField = NULL, $onTop = false)	{
		$this->basicsObject->addError($errorMsg, $errorSeverity, $errorContext, $errorField, $onTop);
	}
	
	/**
	 * Adds an array of errors to the general error-field. This uses the local addError method with default values
	 * for severity, context, field and onTop
	 *
	 * @param	array		$errors: Array with errormessages
	 */
	protected function addErrors($errors)	{
		if (is_array($errors))	{
			foreach ($errors as $error)	{
				$this->addError($error);
			}
		}
	}
}

if (defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/class.tx_install_base.php'])	{
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/modules/class.tx_install_base.php']);
}
?>
