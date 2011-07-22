<?php
declare(encoding = 'utf-8');

/***************************************************************
*  Copyright notice
*
*  (c) 2011 Patrick Broens (patrick@patrickbroens.nl)
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
 * JSON select
 *
 * @author Patrick Broens <patrick@patrickbroens.nl>
 * @package TYPO3
 * @subpackage form
 */
class tx_form_domain_model_json_select extends tx_form_domain_model_json_element {
	/**
	 * The ExtJS xtype of the element
	 *
	 * @var string
	 */
	public $xtype = 'typo3-form-wizard-elements-basic-select';

	/**
	 * The configuration array for the xtype
	 *
	 * @var array
	 */
	public $configuration = array(
		'attributes' => array(),
		'filters' => array(),
		'label' => array(
			'value' => ''
		),
		'layout' => 'front',
		'options' => array(),
		'validation' => array()
	);

	/**
	 * Allowed attributes for this object
	 *
	 * @var array
	 */
	protected $allowedAttributes = array(
		'class',
		'disabled',
		'id',
		'lang',
		'multiple',
		'name',
		'size',
		'style',
		'tabindex',
		'title'
	);

	protected $childElementsAllowed = FALSE;

	/**
	 * Constructor
	 *
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	public function __construct() {
		parent::__construct();
	}

	/**
	 * Set all the parameters for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 * @see tx_form_domain_model_json_element::setParameters()
	 */
	public function setParameters($parameters) {
		parent::setParameters($parameters);
		$this->setOptions($parameters);
	}

	/**
	 * Set the options for this object
	 *
	 * @param array $parameters Configuration array
	 * @return void
	 * @author Patrick Broens <patrick@patrickbroens.nl>
	 */
	private function setOptions($parameters) {
		if (is_array($parameters)) {
			$keys = t3lib_TStemplate::sortedKeyList($parameters);
			foreach ($keys as $key)	{
				$class = $parameters[$key];
				if (intval($key) && !strstr($key, '.')) {
					if(isset($parameters[$key . '.']) && $class === 'OPTION') {
						$childElementArguments = $parameters[$key . '.'];
						if (isset($childElementArguments['selected'])) {
							$childElementArguments['attributes']['selected'] = $childElementArguments['selected'];
							unset($childElementArguments['selected']);
						}
						$this->configuration['options'][] = $childElementArguments;
					}
				}
			}
		}
	}
}
?>