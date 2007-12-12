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

define('WARNING', 1);
define('FATAL', 2);

require_once(PATH_site.'t3lib/class.t3lib_parsehtml.php');

/**
 * Contains all stuff that is needed to print out the results from the modules.
 *
 * $Id$
 *
 * @author	Thomas Hempel	<thomas@work.de>
 * @author	Sebastian Kurfuerst	<sebastian@typo3.org>
 * @author	Ingo Renner	<ingo@typo3.org>
 */
class tx_install_view {
	/**
	 * The local doc object
	 *
	 * @var template
	 */
	private $doc = NULL;
	
	/**
	 * The plain content section which is included in the doc
	 *
	 * @var string
	 */
	private $content = '';
	
	/**
	 * A read-only array with all allowed render methods
	 *
	 * @var array
	 */
	private $availableRenderMethods = array('plain', 'html', 'errors', 'box', 'list', 'checklist', 'message', 'table', 'cell', 'image', 'form', 'formelement');
	
	/**
	 * This holds all error messages that might occur during run time.
	 *
	 * @var array
	 */
	private $errors  = array ('general' => array(), 'fields' => array());

	/**
	 * Contains the last message
	 *
	 * @var string
	 */
	private $lastMessage = '';

	/**
	 * parent tx_install object
	 *
	 * @var	tx_install
	 */
	private $pObj    = NULL;

	/**
	 * Last openend fieldset
	 *
	 * @var string
	 */
	private $lastFieldset = NULL;
	
	/**
	 * Constructor
	 * 
	 * @return	void
	 */
	public function __construct() {
		$this->doc = t3lib_div::makeInstance('bigDoc');
	}

	/**
	 * Initialisation
	 * 
	 * TODO move this into the constructor
	 *
	 * @param	object		reference to "tx_install" object
	 */
	public function init($pObj) {
		$this->pObj = $pObj;
		$this->doc->backPath = $this->pObj->getBackPath();
		$this->doc->JScode .= '<script src="'.$this->doc->backPath.'contrib/prototype/prototype.js"></script>';
		$this->doc->JScode .= '<script src="'.$this->doc->backPath.'contrib/scriptaculous/scriptaculous.js?load=effects,controls"></script>';
		$this->doc->JScode .= '<script src="'.$this->pObj->getBasicsObject()->getInstallerWebPath().'mod/scripts.js"></script>';
		$this->doc->styleSheetFile2 = t3lib_extMgm::extRelPath('install').'modules/setup/res/styles.css';
	}

	/**
	 * Returns the complete document with all content on it.
	 * 
	 * @return	string		content
	 */
	public function getDocCode() {
		$this->content .= $this->doc->sectionEnd().
				$this->doc->postCode.
				$this->doc->endPageJS().
				$this->doc->parseTime().
				($this->doc->form ? '</form>' : '');

		$this->content .= '</html>';

		return $this->doc->startPage('').$this->content;
	}

	/**
	 * Appends the given content as new section to the document.
	 * 
	 * @param	string		The title of the new section
	 * @param	string		The content of the new section
	 * @return	void
	 */
	public function addContent($title, $content) {
		$this->content .= $this->doc->section($title, $content);
	}
	
	/**
	 * Getter for private variable content
	 *
	 * @return string
	 */
	public function getContent()	{
		return $this->content;
	}
	
	/**
	 * adds a message which will be displayed using a JS alert()
	 *
	 * @param	string		message to display
	 */
	public function addJSmessage($message) {
		$this->content = '<script language="javascript" type="text/javascript">alert(unescape(\''.rawurlencode($message).'\'));</script>' .$this->content;
	}
	
	/**
	 * adds javascript code
	 *
	 * @param	string		javascript code
	 */
	public function addJS($js) {
		$this->doc->JScodeArray[] = $js;
	}
	
	
	/**
	 * Renders an option from a module. This method checks if an option should be 
	 * displayed and how it should be rendered.
	 * 
	 * @param	string	option name
	 * @param	array	option configuration
	 * @param	boolean	whether the configuration shall be returned or not
	 * @return	mixed	
	 */
	public function renderOption($optionName, $optionConfig, $returnConfig = false) {
		$basicsObj = $this->pObj->getBasicsObject();
		
		if(isset($optionConfig['displayFunc'])) {
			if(!$basicsObj->executeMethod($optionConfig['displayFunc'])) {
				return false;
			}
		}
		
			// get the value of the field. Basically, the value of this field is defined by the field value.
		$value = $optionConfig['value'];
		
			// load value from localconfCache
		if(substr($value, 0, 3) == 'LC:') {
			$value = $basicsObj->getLocalconfValue(substr($value, 3));
		}
		
			// if a userfunction is set, call it and send the current value as argument
		if(isset($optionConfig['valueFunc']) && !empty($optionConfig['valueFunc'])) {
			// $value = t3lib_div::callUserFunction($optionConfig['valueFunc'], $value, $this);
			$value = $basicsObj->executeMethod($optionConfig['valueFunc'], $value);
		}
		
			// if the value is empty now, set it to the default value
		if(empty($value)) {
			$value = $optionConfig['default'];
		}
		
		$renderConfig = array (
			'elementType' => $optionConfig['elementType'],
			'label'       => $optionConfig['title'],
			'options'     => array(
				'value' => $value,
				'id'    => $optionName,
				'name'  => $optionName
			)
		);
		
		if(isset($optionConfig['overruleOptions']) && is_array($optionConfig['overruleOptions'])) {
			$renderConfig['options'] = t3lib_div::array_merge_recursive_overrule($renderConfig['options'], $optionConfig['overruleOptions']);
		}
		
		if($returnConfig) {
			return array('type' => 'formelement', 'value' => $renderConfig);
		} else {
			return $this->renderFormelement($renderConfig);
		}
	}


	/**
	 * RENDER-OBJECT
	 * 
	 * Render a single element. This method is a dispatcher to more methods
	 * 
	 * Format: 
	 * type  => $availableRenderMethods
	 * value => RENDER-OBJECT
	 *
	 * @param	array		single element to render. array ( 'type' => ..., 'value' => ...)
	 * @return	string		HTML output
	 */
	public function render($element) {
		$content = '';
		
		if(is_array($element)) {
				// look for the keys "value" and "type" and render them
			if(isset($element['value']) && $element['type']) {
				$data = $element['value'];
				$type = $element['type'];
				
				if(!in_array($type, $this->availableRenderMethods)){
					$type = 'list';
				}
				$renderMethod = 'render'.ucfirst($type);
				
				if(method_exists($this, $renderMethod)) {	
					
					$content = $this->$renderMethod($data);
				} else {
					$content = sprintf($this->pObj->getBasicsObject()->getLabel('no_method'), $type);
				}
			} else {
					// if those elements where not found, try to render each single element
				foreach ($element as $subElement) {
					$content .= $this->render($subElement);
				}
			}
		} else {
			$content = $this->renderPlain($element);
		}
		
		return $content;
	}
	
	/**
	 * Renders the value wraped by a given tag
	 *
	 * @param string 	The HTML Tag (e.g. strong)
	 * @param string 	The wrapped value
	 * @return string
	 */
	public function renderTag($tag, $value, $extraParameters = null)	{
		$tag = htmlspecialchars(strtolower($tag));
		$result = '<'.$tag;
		if (is_array($extraParameters))	{
			foreach ($extraParameters as $paramName => $paramValue)	{
				$result .= ' '.$paramName.'="'.((empty($paramValue)) ? $paramName : $paramValue).'"';
			}
		}
		$result .= '>'.$value.'</'.$tag.'>';
		return $result;
	}
	
	
	private function renderBox($data)	{
		$elementCode = '';
		if (is_array($data['elements']))	{
			foreach ($data['elements'] as $elementConfig)	{
				$elementCode .= $this->render($elementConfig);
			}
		}
		
		return $this->renderTag('div', $elementCode, array('class' => $data['class'], 'id' => $data['id']));
	}
	
	
	/**
	 * Returns a box with all errors requested. Which errors are returned depends on the mode the method
	 * is called with.
	 * Mode can be "general" or "fields". If mode is "field", a fieldname has to be given. The method will
	 * return all errors for this field.
	 * 
	 * @param	boolean		whether headers should be rendered or not
	 * @param	string		mode, which errors should be returned (general, fields)
	 * @param	string		required field name
	 * @return	mixed		error messages if erros where found, false in case there were no errors
	 */
	public function renderErrors($renderHeader = false, $mode = 'general', $reqFieldName = '') {
		$hasErrors = false;
		$content   = '<div class="errors">';
		
		if($renderHeader) {
			$content .= '<div class="error-header">'.$this->pObj->getBasicsObject()->getLabel('msg_error_occured').'</div>';
		}
		$content .= '<ul>';
		
		switch ($mode) {
			case 'general':
				if(is_array($this->errors['general'])) {
					$hasErrors = true;

					foreach ($this->errors['general'] as $errorItem) {
						$content .= '<li>'.$this->renderError($errorItem).'</li>';
					}
				}
				break;
			case 'fields':
				if(is_array($this->errors['fields'][$reqFieldName])) {
					$hasErrors = true;

					foreach ($this->errors['fields'][$reqFieldName] as $errorItem) {
						$content .= '<li>'.$this->renderError($errorItem).'</li>';
					}
				}
				break;
		}
		
		$content .= '</ul><div>';
		
		$returnValue = $content;
		if(!$hasErrors) {
			$returnValue = $hasErrors;
		}
		
		return $returnValue;
	}
	
	/**
	 * Returns the HTML markup for a single error message, adds CSS class depending on the severity,
	 * adds a "FATAL" if we have a fatal error and wraps the message with <span></span>.
	 * 
	 * @param	array		array with error severity and message.
	 * @return	string		The error message with HTML markup 
	 */
	private function renderError($error) {
		$content = $error['message'];
		$class   = '';

		switch($error['severity']) {
			case FATAL:
				$content = 'FATAL! '.$content;
				$class   = ' error-fatal';
				break;
		}

		return '<span class="error'.$class.'">'.$content.'</span>';
	}
	
	/**
	 * Clears the local error array
	 *
	 */
	public function clearErrors()	{
		$this->errors = array('general' => array(), 'fields' => array());
	}
	
	
	/**
	 * LIST
	 * 
	 * Renders a list with UL and LI
	 * 
	 * Format:
	 * type => list
	 * value => array (
	 * 		item1 => RENDER-OBJECT
	 * 		itemN => RENDER-OBJECT
	 * )
	 * 
	 * @param	array		$data: An array of elements. Every element can be another render-objects 
	 * @return	string		HTML output
	 */
	private function renderList($data) {
		$content = '';
		if(count($data)) {
			foreach ($data as $singleElement) {
				switch ($singleElement['status'])	{
					case 'ok':
						$style = ' style="list-style-image: url('.$this->pObj->getBasicsObject()->getInstallerWebPath().'imgs/icons/ok.png)"';
						break;
					case 'warning':
						$style = ' style="list-style-image: url('.$this->pObj->getBasicsObject()->getInstallerWebPath().'imgs/icons/warning.png)"';
						break;
					default:
						$style = '';
						break;
				}
				$content .= '<li'.$style.'>'.$this->render($singleElement).'</li>';
			}
			
			$content = '<ul>'.$content.'</ul>';
		}

		return $content;
	}
	
	/**
	 * RENDER-OBJECT::CHECKLIST
	 * 
	 * Wraps the render-objects in the incomming array into table rows. The first column of each row
	 * will contain an image based on the property "severity" in the value of that row.
	 * 
	 * Format:
	 * type => checklist
	 * value => array (
	 * 		row1 => RENDER-OBJECT::MESSAGE
	 * 		rowN => RENDER-OBJECT::MESSAGE
	 * ) 
	 * 
	 * @patam	array		$data: An array of elements. Every element can be another render-objects 
	 * @return	string		HTML output
	 */
	private function renderChecklist($data) {
		$tableData = array();
		
		// FIXME: The path is very ugly. I think this is a sideeffect with the installation as local extension.
		foreach ($data as $dataRow) {
			$row = array(
				'<img src="../../typo3conf/ext/install/imgs/'.$dataRow['value']['severity'].'.png" width="22" height="22" alt="'.$dataRow['value']['severity'].'" />',
				$dataRow
			);
			$tableData[] = $row;
		}
		
		return $this->renderTable($tableData);
	}
	
	/**
	 * RENDER-OBJECT::IMAGE
	 * 
	 * Renders an image from a given path
	 * 
	 * Format:
	 * type => image
	 * value => array (
	 * 		path => string
	 * 		link => string
	 * )
	 * 
	 * @param	string		$data: The content that should be rendered
	 * @return	string		HTML output
	 */
	private function renderImage($data) {
		$content       = '';
		$imgServerPath = t3lib_extMgm::extPath('install').$data['path'];

		if(file_exists($imgServerPath))	{
			$imgSize    = getimagesize($imgServerPath);
			$imgWebPath = $this->pObj->getBasicsObject()->getInstallerWebPath().$data['path'];
			$imgTag     = '<img src="'.$imgWebPath.'" '.$imgSize[3].' border="0" alt="'.$data['altTitle'].'" title="'.$data['altTitle'].'" />';
		
			if(!empty($data['link'])) {
				$imgTag = '<a href="'.$data['link'].'">'.$imgTag.'</a>';
			}
		
			$content = $imgTag;
		} else {
			$content = $data['default'];
		}
		
		return $content;
	}
	
	/**
	 * RENDER-OBJECT::HTML
	 * 
	 * Renders HTML content
	 * 
	 * Format:
	 * type => html
	 * value => array (
	 * 		template => HTML-Template
	 * 		marker => array with markers
	 * )
	 * 
	 * @param	string		$data: The content that should be rendered
	 * @return	string		HTML output
	 */
	private function renderHtml($data) {
		$result = $data['template'];
		
		if(is_array($data['marker'])) {
			foreach ($data['marker'] as $marker => $value) {
				$result = str_replace($marker, $value, $result);
			}
		}
		
		if(is_array($data['subparts']))	{
			foreach ($data['subparts'] as $subpart => $value)	{
				// var_dump(array($subpart, $value));
				$result = t3lib_parsehtml::substituteSubpart($result, $subpart, $value, 0);
			}
		}
		
		return $result;
	}
	

	/**
	 * RENDER-OBJECT::PLAIN
	 * 
	 * Render plain text, only uses nl2br
	 * 
	 * Format:
	 * type => plain
	 * value => string
	 * 
	 * @param	array		$data: The content that should be rendered
	 * @return	string		HTML output
	 */
	private function renderPlain($data) {
		return nl2br($data);
	}
	
	/**
	 * RENDER-OBJECT::MESSAGE
	 * 
	 * Render a message. A message has a severity, a label and an explanation
	 * 
	 * Format:
	 * type => message
	 * value => array (
	 * 		severity => string
	 * 		label => string
	 * 		message => string
	 * )
	 * 
	 * @param	array		$data: The content that should be rendered
	 * @return	string		HTML output
	 */
	private function renderMessage($data) {
		$out = '<div'.(($data['severity']) ? ' class="severity_'.$data['severity'].'"' : '').'>';
		if ($data['label'])	{
			if (is_array($data['label']))	{
				$tag = $data['label'][0];
				$label = $data['label'][1];
				$br = '';
			} else {
				$tag = 'strong';
				$label = $data['label'];
				$br = '<br />';
			}
			$out .= $this->renderTag($tag, $label).$br;
		}
		$out .= ($data['message']) ? $this->render($data['message']) : '';	
		$out .= '</div>';
		
		return $out;
	}
	
	/**
	 * RENDER-OBJECT::FORM
	 * 
	 * Renders a form that consits of a set of form-elements
	 * 
	 * Format:
	 * type => form
	 * value => array (
	 * 		elements => array of RENDER-OBJECT
	 * 		hidden => array of name=>value pairs for hidden fields
	 * 		options => array (
	 * 			submit => string
	 * 			name => string
	 * 			id => string
	 * 			method => set(post,get)
	 * 			action => string
	 * 		)
	 * )
	 */
	private function renderForm($data) {
		$content = '<form'.
			$this->getAttributeString('name', $data['options']['name']).
			$this->getAttributeString('id', $data['options']['id']).
			$this->getAttributeString('method', $data['options']['method'], 'post');
			
		if(isset($data['options']['action']) && $data['options']['ajax'] == false) {
			$content .= $this->getAttributeString('action', $data['options']['action']);
		}
		$content .= '>'."\n";
		
		if(is_array($data['hidden'])) {
			foreach ($data['hidden'] as $name => $value) {
				$content .= '<input type="hidden" name="'.$name.'" value="'.$value.'" />'."\n";
			}
		}

		if(is_array($data['elements'])) {
			foreach ($data['elements'] as $formElement) {
				if(is_array($formElement) && $formElement['value']['elementType'] == 'password' && $formElement['value']['renderTwice']) {
					$formElementName                         = $formElement['value']['options']['name'];
					$formElement['value']['options']['name'] = $formElementName.'1';
					$content .= $this->render($formElement);
					
					$formElement['value']['options']['name'] = $formElementName.'2';
					$formElement['value']['label']           = $formElement['value']['label'].'_2';
					$content .= $this->render($formElement);
				} else {
					$content .= $this->render($formElement);
				}
			}
		}
		
		if (!is_null($this->lastFieldset))	{
			$content .= '</fieldset>';
		}
		
		if ($data['options']['ajax'] == true)	{
			$content .= '<br /><button class="submit" onclick="return '.$data['options']['action'].'">'.$data['options']['submit'].'</button>';
		} else {
			$content .= '<br /><input type="submit" class="submit" value="'.$data['options']['submit'].'" />';
		}
		
		$content .= '</form>';
		
		return $content;
	}

	/**
	 * RENDER-OBJECT::FORMELEMENT
	 * 
	 * Renders any kind of form element
	 * 
	 * Format:
	 * type => form_element
	 * value => array (
	 * 		elementType => set(input,text,submit,checkbox,radio,checkbox_group,radio_group)
	 * 		options => array with field settings (see field render methods) {
	 *			name => string
	 *			id => string
	 *			label => string
	 *			description => string
	 * 		}
	 * )
	 * 
	 * @param	string		$data: The content that should be rendered
	 * @return	string		HTML output
	 */
	public function renderFormelement($data) {
		$content   = '';
		$viewObj   = $this->pObj->getViewObject();
		$basicsObj = $this->pObj->getBasicsObject();

		if ($data['elementType'] == 'fieldset')	{
			if (!is_null($this->lastFieldset) && $this->lastFieldset != $data['label'])	{
				$content = '</fieldset>';
			}
			
			$content .= '<fieldset><legend>'.$data['label'].'</legend>';
			$this->lastFieldset = $data['label'];	
		} else {
		
			$elementRenderMethod = 'renderFormelement'.ucfirst($data['elementType']);
			if(method_exists($this, $elementRenderMethod)) {
					// set the value of the field from the environment if no error is recognized for it
					// also set an error string if error was found
				$errors = $viewObj->getErrors();
				if(!isset($errors['fields'][$data['options']['name']])) {
					$environment = $this->pObj->getEnvironment();
					if(!empty($environment[$data['options']['name']])) {
						$data['options']['value'] = $environment[$data['options']['name']];
					}
					$errorStr = '';
				} else {
					$errorStr = $viewObj->renderErrors(true, 'fields', $data['options']['name']);
				}
				
					// render the form element
				$formElementCode = $this->$elementRenderMethod($data['options']);
				
					// create a label if set in option
				if(isset($data['label'])) {
					$label = '<label for="'.$data['options']['id'].'">'.$basicsObj->getLabel($data['label'], $data['label']).'</label>';
					
						// align the label (default is left)
					switch ($data['label_align']) {
						case 'right':
							$formElementCode = $errorStr.$formElementCode.$label;
							break;
						case 'left':
						default:
							$formElementCode = $label.$errorStr.$formElementCode;
					}
				}
				
					// return the element wrapped in a div
				$content = '<div class="formElement formElement'.ucfirst($data['elementType']).'">'.$formElementCode.'</div>';
			}
		}
		
		return $content;
	}
	
	/**
	 * RENDER-OBJECT::FORMELEMENT::INPUT
	 * 
	 * Renders a simple input field
	 * 
	 * Format:
	 * type => input
	 * value => array (
	 * 		size => integer
	 * 		value => string
	 * )
	 */
	private function renderFormelementInput($data) {
		// debug($data, 'renderFormelement_input');
		$content = '<input type="text"'.
			$this->getAttributeString('name',  $data['name']).
			$this->getAttributeString('id',    $data['id']).
			$this->getAttributeString('size',  $data['size'], 20).	
			$this->getAttributeString('value', $data['value'], '').
			' />';

		return $content;
	}
	
	/**
	 * RENDER-OBJECT::FORMELEMENT::PASSWORD
	 * 
	 * Renders a password input field
	 * 
	 * Format:
	 * type => password
	 * value => array (
	 * 		size => integer
	 * 		value => string
	 * )
	 */
	private function renderFormelementPassword($data) {
		// debug($data, 'renderFormelement_password');
		$content = '<input type="password"'.
			$this->getAttributeString('name', $data['name']).
			$this->getAttributeString('id',   $data['id']).
			$this->getAttributeString('size', $data['size'], 20).
			' />';

		return $content;
	}
	
	/**
	 * RENDER-OBJECT::FORMELEMENT::SELECTBOX
	 * 
	 * Renders a selectbox from a list of elements
	 * 
	 * Format:
	 * type => selectbox
	 * value => array (
	 * 		size => integer
	 * 		selected => string
	 * 		elements => array with key => value pairs
	 * )
	 */
	private function renderFormelementSelectbox($data) {
		// t3lib_div::debug($data, 'renderFormelement_selectbox');
		$basicsObj = $this->pObj->getBasicsObject();
		
		$content = '<select'.
			$this->getAttributeString('name', $data['name']).
			$this->getAttributeString('id',   $data['id']).
			$this->getAttributeString('size', $data['size']).
			'>';
			
		if($data['empty'] == true) {
			$content .= '<option value="">'.$data['empty'].'</option>';
		}
		
		if(is_string($data['elements'])) {
			$data['elements'] = $basicsObj->executeMethod($data['elements']);
		}
		
		if(is_array($data['elements'])) {
			foreach ($data['elements'] as $key => $value) {
				if (!empty($data['default']))	{
					if (empty($data['value']))	{
						$selected = ($key == $data['default']);
					} else {
						$selected = ($key == $data['value']);
					}
				} else {	
					$selected = ($key == $data['value']);	
				}
				$selected = ($selected == true) ? $this->getAttributeString('selected', 'selected') : '';
				$content .= '<option value="'.$key.'"'.$selected.'>'.$value.'</option>'."\n";
			}
		}
		$content .= '</select>';

		return $content;
	}
	
	/**
	 * RENDER-OBJECT::FORMELEMENT::CHECKBOX
	 * 
	 * Renders a checkbox
	 * 
	 * Format:
	 * type => checkbox
	 * value => array (
	 * 		name => string
	 * 		id => string
	 * 		value => integer (0 | 1)
	 * )
	 *
	 * @param array $data: configuration array
	 * @return HTML
	 */
	private function renderFormelementCheckbox($data)	{
		$content = '<input type="checkbox"'.
			$this->getAttributeString('name',	$data['name']).
			$this->getAttributeString('id',		$data['id']).
			$this->getAttributeString('value',	$data['name']);
		
		$checked = $data['default'];
		
		if (isset($data['value']))	{
			$checked = $data['value'] == 1;
		}

		if ($checked)	{
			$content .= $this->getAttributeString('checked', 'checked');
		}
		
		$content .= ' />';
		
		return $content;
	}
	
	/**
	 * Renders a div with a help text that can be toggled via a button
	 *
	 * @param 	string 	$helpStr
	 * @param	string	$id: A unique ID for the container. A random value is appended
	 * @return	array ('button', 'conatiner')
	 */
	public function renderHelp($helpStr, $id)	{
		if (empty($helpStr))	{
			$result = false;
		} else {
			$result = array('button' => '', 'container' => '');
			
			$id = $id.'_'.md5(microtime());
			
			$helpLabel = $this->renderImage(array('path' => 'imgs/icons/help.png', 'altTitle' => $this->pObj->getBasicsObject()->getLabel('label_help')));
			
			$result['button'] = '<a href="#" onclick="toggleHelp(\''.$id.'\'); return false;">'.$helpLabel.'</a>';
			$result['container'] = '<div id="'.$id.'" style="display:none" class="help_container">'.$helpStr.'</div>';
		}
		
		return $result;
	}
	
	/**
	 * Retrieves a name and a value and returns a HTML attribute string (name="value") if the value is not empty.
	 * Otherwise it will return an  empty string.
	 * 
	 * @param	string		$name: The name of the attribute
	 * @param	string		$value: The value of the attribute
	 * @return	string		The HTML attribute string prepended with a whitespace
	 */
	private function getAttributeString($name, $value = NULL, $default = NULL) {
		$content = '';
		
		$value = (isset($value)) ? $value : $default;
		// $value = ($value == NULL && $default == NULL) ? $name : '';

		if(!is_null($value)) {
			$content = ' '.strtolower($name).'="'.$value.'"';
		}

		return $content;
	}
	
	/**
	 * gets the errors
	 *
	 * @param	string	optional type to narrow down the request range of errors
	 * @return	array
	 */
	public function getErrors($type = '') {
		$errors = $this->errors;
		
		if($type) {
			$errors = $this->errors[$type];
		}
		
		return $errors;
	}
	
	/**
	 * adds an error message
	 * 
	 * TODO add checks
	 *
	 * @param	string	error type
	 * @param	string	error message
	 */
	public function addError($type, $errorMessage, $errorField = '') {
		
		if($type == 'fields') {
			$this->errors[$type][$errorField][] = $errorMessage;
		} else {
			$this->errors[$type][] = $errorMessage;
		}
	}
	
	/**
	 * Adds a string to this->lastMessage
	 *
	 * @param	string	message
	 */
	public function addMessage($message) {
		$this->lastMessage .= $message;
	}
	
	/**
	 * Returns the value in this->lastMessage
	 *
	 * @param boolean $clear: If set, the lastMessage is cleared after return
	 * @return string
	 */
	public function getLastMessage($clear = false)	{
		$result = $this->lastMessage;
		if ($clear)	{
			$this->clearLastMessage();
		}
		return $result;
	}
	
	/**
	 * Clears the value of this->lastMessage
	 *
	 */
	public function clearLastMessage()	{
		$this->lastMessage = '';
	}

}

if(defined('TYPO3_MODE') && $TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_view.php']) {
	include_once($TYPO3_CONF_VARS[TYPO3_MODE]['XCLASS']['ext/install/mod/class.tx_install_view.php']);
}

?>