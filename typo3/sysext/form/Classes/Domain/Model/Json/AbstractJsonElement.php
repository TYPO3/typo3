<?php
namespace TYPO3\CMS\Form\Domain\Model\Json;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011-2013 Patrick Broens (patrick@patrickbroens.nl)
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * JSON element abstract
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 */
class AbstractJsonElement {

	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = '';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array();

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array();

	/**
	 * Child elements allowed withing this element
	 *
	 * Some elements like select handle their own child elements
	 *
	 * @var boolean
	 */
	protected $childElementsAllowed = TRUE;

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	public function setParameters(array $parameters) {
		foreach ($this->configuration as $key => $value) {
			switch ($key) {
				case 'attributes':
					$this->setAttributes($parameters);
					break;
				case 'filters':
					$this->setFilters($parameters);
					break;
				case 'label':
					$this->setLabel($parameters);
					break;
				case 'layout':
					$this->setLayout($parameters);
					break;
				case 'validation':
					$this->setValidation($parameters);
					break;
				}
		}
	}

	/**
	 * Check if child elements are allowed within this element
	 *
	 * @return boolean TRUE if allowed
	 */
	public function childElementsAllowed() {
		return $this->childElementsAllowed;
	}

	/**
	 * Set the attiobutes according to the allowed attributes of this element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setAttributes(array $parameters) {
		foreach ($this->allowedAttributes as $allowedAttribute) {
			if (isset($parameters[$allowedAttribute])) {
				$this->configuration['attributes'][$allowedAttribute] = $parameters[$allowedAttribute];
			} elseif (!isset($this->configuration['attributes'][$allowedAttribute])) {
				$this->configuration['attributes'][$allowedAttribute] = '';
			}
		}
	}

	/**
	 * Set the filters of the element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setFilters(array $parameters) {
		if (isset($parameters['filters.']) && is_array($parameters['filters.'])) {
			$filters = $parameters['filters.'];
			foreach ($filters as $key => $filterName) {
				if (intval($key) && !strstr($key, '.')) {
					$filterConfiguration = array();
					if (isset($filters[$key . '.'])) {
						$filterConfiguration = $filters[$key . '.'];
					}
					$this->configuration['filters'][$filterName] = $filterConfiguration;
				}
			}
		} else {
			$this->configuration['filters'] = new \stdClass();
		}
	}

	/**
	 * Set the label of the element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setLabel(array $parameters) {
		if (isset($parameters['label']) && !isset($parameters['label.'])) {
			$this->configuration['label']['value'] = $parameters['label'];
		} elseif (!isset($parameters['label']) && isset($parameters['label.'])) {
			$this->configuration['label']['value'] = $parameters['label.']['value'];
		}
	}

	/**
	 * Set the layout of the element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setLayout(array $parameters) {
		if (isset($parameters['layout'])) {
			if ($this->configuration['layout'] === 'front') {
				$this->configuration['layout'] = 'back';
			} else {
				$this->configuration['layout'] = 'front';
			}
		}
	}

	/**
	 * Set the validation rules for the element
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 */
	protected function setValidation(array $parameters) {
		if (isset($parameters['validation']) && is_array($parameters['validation'])) {
			$this->configuration['validation'] = $parameters['validation'];
		} else {
			$this->configuration['validation'] = new \stdClass();
		}
	}

}

?>