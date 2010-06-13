<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2008-2010 Stephan Petzl <spetzl@gmx.at> and Christian Kartnig <office@hahnepeter.de>
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
 * Loads TSref information from a XML file an responds to an AJAX call.
 *
 * @TODO Refactor and correct phpDoc comments
 * @package TYPO3
 * @author Stephan Petzl <spetzl@gmx.at>
 * @author Christian Kartnig <office@hahnepeter.de>
 */

$GLOBALS['LANG']->includeLLFile('EXT:t3editor/locallang.xml');

class tx_t3editor_TSrefLoader {
	/** @var DOMDocument */
	protected $xmlDoc;

	/** @var TYPO3AJAX */
	protected $ajaxObj;

	/**
	 * General processor for AJAX requests.
	 * (called by typo3/ajax.php)
	 *
	 * @param	array		$params: additional parameters (not used here)
	 * @param	TYPO3AJAX	&$ajaxObj: the TYPO3AJAX object of this request
	 * @return	void
	 * @author	Oliver Hader <oliver@typo3.org>
	 */
	public function processAjaxRequest($params, TYPO3AJAX &$ajaxObj) {
		$this->ajaxObj = $ajaxObj;

		// Load the TSref XML information:
		$this->loadFile(t3lib_extMgm::extPath('t3editor') . 'res/tsref/tsref.xml');

		$ajaxIdParts = explode('::', $ajaxObj->getAjaxID(), 2);
		$ajaxMethod = $ajaxIdParts[1];
		$response = array();

		// Process the AJAX requests:
		if ($ajaxMethod == 'getTypes') {
			$ajaxObj->setContent($this->getTypes());
			$ajaxObj->setContentFormat('jsonbody');
		} elseif ($ajaxMethod == 'getDescription') {
			$ajaxObj->addContent(
				'',
				$this->getDescription(
					t3lib_div::_GP('typeId'),
					t3lib_div::_GP('parameterName')
				)
			);
			$ajaxObj->setContentFormat('plain');
		}
	}

	/**
	 * Enter description here...
	 *
	 * @param	string		$filepath
	 * @return	void
	 */
	protected function loadFile($filepath) {
		$this->xmlDoc = new DOMDocument('1.0', 'utf-8');
		$this->xmlDoc->load($filepath);

		// @TODO: oliver@typo3.org: I guess this is not required here
		$this->xmlDoc->saveXML();
	}

	/**
	 * Enter description here...
	 *
	 * @return	array
	 */
	protected function getTypes() {
		$types = $this->xmlDoc->getElementsByTagName('type');
		$typeArr = array();
		foreach($types as $type){
			$typeId = $type->getAttribute('id');
			$typeName = $type->getAttribute('name');
			if(!$typeName) {
				$typeName = $typeId;
			}
			$properties = $type->getElementsByTagName('property');
			$propArr = array();
			foreach($properties as $property) {
				$p = array();
				$p['name'] = $property->getAttribute('name');
				$p['type'] = $property->getAttribute('type');
				$propArr[$property->getAttribute('name')] = $p;
			}
			$typeArr[$typeId] = array();
			$typeArr[$typeId]['properties'] = $propArr;
			$typeArr[$typeId]['name'] = $typeName;
			if($type->hasAttribute('extends')) {
				$typeArr[$typeId]['extends'] = $type->getAttribute('extends');
			}
		}
		return $typeArr;
	}

	/**
	 * Enter description here...
	 *
	 * @param	string		$typeId
	 * @param	string		$parameterName
	 * @return	string
	 */
	protected function getDescription($typeId, $parameterName = '') {
		if (!$typeId) {
			$this->ajaxObj->setError($GLOBALS['LANG']->getLL('typeIDMissing'));
			return '';
		}

		// getElementById does only work with schema
		$type = $this->getType($typeId);
		if ($parameterName) {  //retrieve propertyDescription
			$properties = $type->getElementsByTagName('property');
			foreach ($properties as $propery) {
				$propName = $propery->getAttribute('name');
				if ($propName == $parameterName) {
					$descriptions = $propery->getElementsByTagName('description');
					if ($descriptions->length) {
						$description = $descriptions->item(0)->textContent;
						$description = htmlspecialchars($description);
						$description = nl2br($description);
						return $description;
					}
				}
			}
		} else {  // retrieve typedescription
			/*
			$descriptions = $type->getElementsByTagName('description');
			if($descriptions->length){
				$description = $descriptions->item(0)->textContent;

				return htmlspecialchars($description);
			}*/
		}

		return '';
	}

	/**
	 * Enter description here...
	 *
	 * @param	string		$typeId
	 * @return	DOMNode
	 */
	protected function getType($typeId) {
		$types = $this->xmlDoc->getElementsByTagName('type');
		foreach ($types as $type) {
			if ($type->getAttribute('id') == $typeId) {
				return $type;
			}
		}
	}
}

?>