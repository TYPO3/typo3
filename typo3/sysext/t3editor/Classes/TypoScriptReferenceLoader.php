<?php
namespace TYPO3\CMS\T3Editor;

/**
 * Loads TSref information from a XML file an responds to an AJAX call.
 *
 * @author Stephan Petzl <spetzl@gmx.at>
 * @author Christian Kartnig <office@hahnepeter.de>
 */
class TypoScriptReferenceLoader {

	/**
	 * @var \DOMDocument
	 */
	protected $xmlDoc;

	/**
	 * @var \TYPO3\CMS\Core\Http\AjaxRequestHandler
	 */
	protected $ajaxObj;

	/**
	 * General processor for AJAX requests.
	 * (called by typo3/ajax.php)
	 *
	 * @param array $params Additional parameters (not used here)
	 * @param \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj The TYPO3AJAX object of this request
	 * @return void
	 * @author Oliver Hader <oliver@typo3.org>
	 */
	public function processAjaxRequest($params, \TYPO3\CMS\Core\Http\AjaxRequestHandler &$ajaxObj) {
		$this->ajaxObj = $ajaxObj;
		// Load the TSref XML information:
		$this->loadFile(\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath('t3editor') . 'res/tsref/tsref.xml');
		$ajaxIdParts = explode('::', $ajaxObj->getAjaxID(), 2);
		$ajaxMethod = $ajaxIdParts[1];
		$response = array();
		// Process the AJAX requests:
		if ($ajaxMethod == 'getTypes') {
			$ajaxObj->setContent($this->getTypes());
			$ajaxObj->setContentFormat('jsonbody');
		} elseif ($ajaxMethod == 'getDescription') {
			$ajaxObj->addContent('', $this->getDescription(\TYPO3\CMS\Core\Utility\GeneralUtility::_GP('typeId'), \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('parameterName')));
			$ajaxObj->setContentFormat('plain');
		}
	}

	/**
	 * Load XML file
	 *
	 * @param string $filepath
	 * @return void
	 */
	protected function loadFile($filepath) {
		$this->xmlDoc = new \DOMDocument('1.0', 'utf-8');
		$this->xmlDoc->load($filepath);
		// @TODO: oliver@typo3.org: I guess this is not required here
		$this->xmlDoc->saveXML();
	}

	/**
	 * Get types from XML
	 *
	 * @return array
	 */
	protected function getTypes() {
		$types = $this->xmlDoc->getElementsByTagName('type');
		$typeArr = array();
		foreach ($types as $type) {
			$typeId = $type->getAttribute('id');
			$typeName = $type->getAttribute('name');
			if (!$typeName) {
				$typeName = $typeId;
			}
			$properties = $type->getElementsByTagName('property');
			$propArr = array();
			foreach ($properties as $property) {
				$p = array();
				$p['name'] = $property->getAttribute('name');
				$p['type'] = $property->getAttribute('type');
				$propArr[$property->getAttribute('name')] = $p;
			}
			$typeArr[$typeId] = array();
			$typeArr[$typeId]['properties'] = $propArr;
			$typeArr[$typeId]['name'] = $typeName;
			if ($type->hasAttribute('extends')) {
				$typeArr[$typeId]['extends'] = $type->getAttribute('extends');
			}
		}
		return $typeArr;
	}

	/**
	 * Get description
	 *
	 * @param string $typeId
	 * @param string $parameterName
	 * @return string
	 */
	protected function getDescription($typeId, $parameterName = '') {
		if (!$typeId) {
			$this->ajaxObj->setError($GLOBALS['LANG']->getLL('typeIDMissing'));
			return '';
		}
		// getElementById does only work with schema
		$type = $this->getType($typeId);
		// Retrieve propertyDescription
		if ($parameterName) {
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
		}
		return '';
	}

	/**
	 * Get type
	 *
	 * @param string $typeId
	 * @return \DOMNode
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